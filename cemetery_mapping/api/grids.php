<?php
/**
 * Grids API
 * Full CRUD for database-driven plot grids and cells.
 *
 * Actions:
 *   GET  ?action=list                       -> list all grids (standalone + record-linked)
 *   GET  ?action=get&record_id=N            -> get grid + cells for a record
 *   GET  ?action=get&grid_id=N              -> get grid + cells by grid id
 *   POST action=create                       -> create grid + cells (record_id optional)
 *   POST action=update                       -> update grid + recalculate cells
 *   POST action=delete                       -> delete grid + cells
 *   POST action=select_cell                  -> toggle cell selection
 *   POST action=assign_cell_record           -> assign a burial record to a cell
 *   POST action=unassign_cell_record         -> remove burial record from a cell
 *
 * Follows existing project API conventions (JSON output, session admin check).
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/database.php';

// Read action from GET/POST/JSON body
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?: [];
$action = $_GET['action'] ?? ($_POST['action'] ?? ($jsonInput['action'] ?? ''));

// Public read-only actions (accessible by visitors)
$publicActions = ['list', 'get'];

// Admin-only actions require admin session
if (!in_array($action, $publicActions) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    switch ($action) {
        case 'list':      handleList($pdo);  break;
        case 'get':       handleGet($pdo);  break;
        case 'create':    handleCreate($pdo);  break;
        case 'update':    handleUpdate($pdo);  break;
        case 'delete':    handleDelete($pdo);  break;
        case 'select_cell': handleSelectCell($pdo); break;
        case 'assign_cell_record': handleAssignCellRecord($pdo); break;
        case 'unassign_cell_record': handleUnassignCellRecord($pdo); break;
        case 'search_records': handleSearchRecords($pdo); break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (PDOException $e) {
    error_log('Grids API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ---------------------------------------------------------------
// LIST: list all grids (standalone + record-linked)
// ---------------------------------------------------------------
function handleList(PDO $pdo) {
    $stmt = $pdo->query(
        "SELECT pg.*,
                br.decedent_name AS record_name,
                br.plot_number AS record_plot
         FROM plot_grids pg
         LEFT JOIN burial_records br ON br.id = pg.record_id
         WHERE pg.status = 'active'
         ORDER BY pg.date_created DESC"
    );
    $grids = $stmt->fetchAll();

    $result = array_map(function ($g) {
        $n = normalizeGrid($g);
        $n['record_name'] = $g['record_name'];
        $n['record_plot'] = $g['record_plot'];
        return $n;
    }, $grids);

    echo json_encode(['success' => true, 'grids' => $result, 'count' => count($result)]);
}

// ---------------------------------------------------------------
// GET: retrieve grid + cells (by record_id OR grid_id)
// ---------------------------------------------------------------
function handleGet(PDO $pdo) {
    $gridId = (int)($_GET['grid_id'] ?? 0);
    $recordId = (int)($_GET['record_id'] ?? 0);

    if ($gridId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM plot_grids WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$gridId]);
    } elseif ($recordId > 0) {
        $stmt = $pdo->prepare(
            "SELECT * FROM plot_grids WHERE record_id = ? AND status = 'active' ORDER BY date_created DESC LIMIT 1"
        );
        $stmt->execute([$recordId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Provide grid_id or record_id']);
        return;
    }

    $grid = $stmt->fetch();

    if (!$grid) {
        echo json_encode(['success' => true, 'grid' => null, 'cells' => []]);
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT c.*, br.decedent_name, br.plot_number AS record_plot_number
         FROM plot_grid_cells c
         LEFT JOIN burial_records br ON br.id = c.record_id
         WHERE c.grid_id = ?
         ORDER BY c.row_idx, c.col_idx"
    );
    $stmt->execute([$grid['id']]);
    $cells = $stmt->fetchAll();

    $result = array_map(function($c) {
        $n = normalizeCell($c);
        $n['decedent_name'] = $c['decedent_name'];
        $n['record_plot_number'] = $c['record_plot_number'];
        return $n;
    }, $cells);

    echo json_encode([
        'success' => true,
        'grid' => normalizeGrid($grid),
        'cells' => $result
    ]);
}

// ---------------------------------------------------------------
// CREATE: create a new grid + calculated cells (record_id optional)
// ---------------------------------------------------------------
function handleCreate(PDO $pdo) {
    $input = readJsonInput();

    $errors = validateGridInput($input, false);
    if ($errors) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        return;
    }

    $recordId = isset($input['record_id']) && $input['record_id'] !== '' ? (int)$input['record_id'] : null;

    // If record_id provided, validate it exists and prevent duplicates
    if ($recordId !== null) {
        if (!recordExists($pdo, $recordId)) {
            echo json_encode(['success' => false, 'message' => 'Record does not exist']);
            return;
        }
        $stmt = $pdo->prepare("SELECT id FROM plot_grids WHERE record_id = ? AND status = 'active'");
        $stmt->execute([$recordId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'A grid already exists for this record. Use update instead.']);
            return;
        }
    }

    // Require either record_id or center_lat/center_lng for standalone grids
    $centerLat = isset($input['center_lat']) && $input['center_lat'] !== '' ? (float)$input['center_lat'] : null;
    $centerLng = isset($input['center_lng']) && $input['center_lng'] !== '' ? (float)$input['center_lng'] : null;
    if ($recordId === null && ($centerLat === null || $centerLng === null)) {
        echo json_encode(['success' => false, 'message' => 'Standalone grids require center_lat and center_lng.']);
        return;
    }

    $grid = computeGridGeometry($input);
    $grid['name'] = trim($input['name'] ?? '');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO plot_grids
                (record_id, name, center_lat, center_lng, start_x, start_y, end_x, end_y,
                 grid_width, grid_height, rows_count, cols_count, cell_width, cell_height, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')"
        );
        $stmt->execute([
            $recordId,
            $grid['name'],
            $centerLat,
            $centerLng,
            $grid['start_x'], $grid['start_y'], $grid['end_x'], $grid['end_y'],
            $grid['grid_width'], $grid['grid_height'],
            $grid['rows_count'], $grid['cols_count'],
            $grid['cell_width'], $grid['cell_height']
        ]);
        $gridId = (int)$pdo->lastInsertId();

        insertCells($pdo, $gridId, $grid);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Grid created successfully',
        'grid_id' => $gridId
    ]);
}

// ---------------------------------------------------------------
// UPDATE: update an existing grid + recalculate cells
// ---------------------------------------------------------------
function handleUpdate(PDO $pdo) {
    $input = readJsonInput();

    $errors = validateGridInput($input, false);
    if ($errors) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        return;
    }

    $gridId = (int)$input['grid_id'];
    $stmt = $pdo->prepare("SELECT * FROM plot_grids WHERE id = ?");
    $stmt->execute([$gridId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Grid not found']);
        return;
    }

    $grid = computeGridGeometry($input);
    $grid['name'] = trim($input['name'] ?? $existing['name']);
    $centerLat = isset($input['center_lat']) && $input['center_lat'] !== '' ? (float)$input['center_lat'] : $existing['center_lat'];
    $centerLng = isset($input['center_lng']) && $input['center_lng'] !== '' ? (float)$input['center_lng'] : $existing['center_lng'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "UPDATE plot_grids SET
                name = ?, center_lat = ?, center_lng = ?,
                start_x = ?, start_y = ?, end_x = ?, end_y = ?,
                grid_width = ?, grid_height = ?, rows_count = ?, cols_count = ?,
                cell_width = ?, cell_height = ?, date_updated = NOW()
             WHERE id = ?"
        );
        $stmt->execute([
            $grid['name'],
            $centerLat, $centerLng,
            $grid['start_x'], $grid['start_y'], $grid['end_x'], $grid['end_y'],
            $grid['grid_width'], $grid['grid_height'],
            $grid['rows_count'], $grid['cols_count'],
            $grid['cell_width'], $grid['cell_height'],
            $gridId
        ]);

        // Remove old cells, insert new ones
        $pdo->prepare("DELETE FROM plot_grid_cells WHERE grid_id = ?")->execute([$gridId]);
        insertCells($pdo, $gridId, $grid);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    echo json_encode(['success' => true, 'message' => 'Grid updated successfully']);
}

// ---------------------------------------------------------------
// DELETE: delete a grid + its cells (cascade handles cells)
// ---------------------------------------------------------------
function handleDelete(PDO $pdo) {
    $input = readJsonInput();
    $gridId = (int)($input['grid_id'] ?? 0);
    if ($gridId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid grid ID']);
        return;
    }

    // Verify grid exists
    $stmt = $pdo->prepare("SELECT id FROM plot_grids WHERE id = ?");
    $stmt->execute([$gridId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Grid not found']);
        return;
    }

    // Collect burial record IDs assigned to cells in this grid
    $stmt = $pdo->prepare("SELECT DISTINCT record_id FROM plot_grid_cells WHERE grid_id = ? AND record_id IS NOT NULL");
    $stmt->execute([$gridId]);
    $recordIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $pdo->beginTransaction();
    try {
        // Delete associated burial records (and their photos)
        if (!empty($recordIds)) {
            // Get photo filenames before deleting
            $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
            $stmt = $pdo->prepare("SELECT id, photo FROM burial_records WHERE id IN ($placeholders)");
            $stmt->execute($recordIds);
            $records = $stmt->fetchAll();

            foreach ($records as $rec) {
                if (!empty($rec['photo'])) {
                    $photoPath = '../uploads/photos/' . $rec['photo'];
                    if (file_exists($photoPath)) {
                        @unlink($photoPath);
                    }
                }
            }

            // Delete the burial records
            $stmt = $pdo->prepare("DELETE FROM burial_records WHERE id IN ($placeholders)");
            $stmt->execute($recordIds);
        }

        // Explicitly delete cells first (in case CASCADE FK is missing)
        $pdo->prepare("DELETE FROM plot_grid_cells WHERE grid_id = ?")->execute([$gridId]);
        // Now delete the grid
        $pdo->prepare("DELETE FROM plot_grids WHERE id = ?")->execute([$gridId]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    $deletedRecords = count($recordIds);
    $msg = $deletedRecords > 0
        ? "Grid deleted successfully. $deletedRecords associated burial record(s) also removed."
        : 'Grid deleted successfully.';

    echo json_encode(['success' => true, 'message' => $msg, 'deleted_records' => $deletedRecords]);
}

// ---------------------------------------------------------------
// SELECT_CELL: toggle cell selection
// ---------------------------------------------------------------
function handleSelectCell(PDO $pdo) {
    $input = readJsonInput();
    $cellId = (int)($input['cell_id'] ?? 0);
    $selected = isset($input['selected']) ? (int)$input['selected'] : 1;
    if ($cellId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid cell ID']);
        return;
    }
    $stmt = $pdo->prepare("UPDATE plot_grid_cells SET is_selected = ?, date_updated = NOW() WHERE id = ?");
    $stmt->execute([$selected, $cellId]);
    echo json_encode(['success' => true, 'message' => 'Cell updated']);
}

// ---------------------------------------------------------------
// ASSIGN_CELL_RECORD: link a burial record to a grid cell
// ---------------------------------------------------------------
function handleAssignCellRecord(PDO $pdo) {
    $input = readJsonInput();
    $cellId = (int)($input['cell_id'] ?? 0);
    $recordId = (int)($input['record_id'] ?? 0);
    if ($cellId <= 0 || $recordId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Cell ID and Record ID are required']);
        return;
    }

    // Verify record exists
    if (!recordExists($pdo, $recordId)) {
        echo json_encode(['success' => false, 'message' => 'Record does not exist']);
        return;
    }

    // Check if this record is already assigned to another cell in the same grid
    $stmt = $pdo->prepare(
        "SELECT c.id FROM plot_grid_cells c
         JOIN plot_grid_cells target ON target.grid_id = c.grid_id
         WHERE target.id = ? AND c.record_id = ? AND c.id != ?"
    );
    $stmt->execute([$cellId, $recordId, $cellId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This record is already assigned to another cell in this grid']);
        return;
    }

    $stmt = $pdo->prepare("UPDATE plot_grid_cells SET record_id = ?, date_updated = NOW() WHERE id = ?");
    $stmt->execute([$recordId, $cellId]);
    echo json_encode(['success' => true, 'message' => 'Record assigned to cell']);
}

// ---------------------------------------------------------------
// UNASSIGN_CELL_RECORD: remove burial record link from a cell
// ---------------------------------------------------------------
function handleUnassignCellRecord(PDO $pdo) {
    $input = readJsonInput();
    $cellId = (int)($input['cell_id'] ?? 0);
    if ($cellId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Cell ID is required']);
        return;
    }
    $stmt = $pdo->prepare("UPDATE plot_grid_cells SET record_id = NULL, date_updated = NOW() WHERE id = ?");
    $stmt->execute([$cellId]);
    echo json_encode(['success' => true, 'message' => 'Record removed from cell']);
}

// ---------------------------------------------------------------
// SEARCH_RECORDS: search burial records for cell assignment
// ---------------------------------------------------------------
function handleSearchRecords(PDO $pdo) {
    $q = trim($_GET['q'] ?? '');
    $sql = "SELECT id, decedent_name, family_name, plot_number FROM burial_records";
    $params = [];
    if ($q !== '') {
        $sql .= " WHERE decedent_name LIKE ? OR family_name LIKE ? OR plot_number LIKE ?";
        $param = "%$q%";
        $params = [$param, $param, $param];
    }
    $sql .= " ORDER BY decedent_name ASC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    echo json_encode(['success' => true, 'records' => $records]);
}

// ---------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------
function readJsonInput(): array {
    global $jsonInput;
    if (is_array($jsonInput) && !empty($jsonInput)) {
        return $jsonInput;
    }
    return $_POST;
}

function validateGridInput(array $input, bool $requireRecordId): array {
    $errors = [];
    // record_id is now optional for standalone grids; $requireRecordId kept for backward compat
    if ($requireRecordId && (int)($input['record_id'] ?? 0) <= 0) {
        $errors[] = 'Valid record ID is required.';
    }
    $sx = floatval($input['start_x'] ?? NAN);
    $sy = floatval($input['start_y'] ?? NAN);
    $ex = floatval($input['end_x'] ?? NAN);
    $ey = floatval($input['end_y'] ?? NAN);
    $rows = (int)($input['rows'] ?? 0);
    $cols = (int)($input['cols'] ?? 0);

    if (!is_numeric($input['start_x'] ?? null) || !is_numeric($input['start_y'] ?? null)
        || !is_numeric($input['end_x'] ?? null) || !is_numeric($input['end_y'] ?? null)) {
        $errors[] = 'All coordinates must be numeric.';
    }
    if ($ex <= $sx) $errors[] = 'Ending X must be greater than Starting X.';
    if ($ey <= $sy) $errors[] = 'Ending Y must be greater than Starting Y.';
    if ($rows < 1) $errors[] = 'Rows must be at least 1.';
    if ($cols < 1) $errors[] = 'Columns must be at least 1.';
    return $errors;
}

function recordExists(PDO $pdo, int $id): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM burial_records WHERE id = ?");
    $stmt->execute([$id]);
    return (bool)$stmt->fetch();
}

function computeGridGeometry(array $input): array {
    $sx = (float)$input['start_x'];
    $sy = (float)$input['start_y'];
    $ex = (float)$input['end_x'];
    $ey = (float)$input['end_y'];
    $rows = (int)$input['rows'];
    $cols = (int)$input['cols'];
    $width = $ex - $sx;
    $height = $ey - $sy;
    return [
        'start_x' => $sx, 'start_y' => $sy,
        'end_x' => $ex, 'end_y' => $ey,
        'grid_width' => $width, 'grid_height' => $height,
        'rows_count' => $rows, 'cols_count' => $cols,
        'cell_width' => $cols > 0 ? $width / $cols : 0,
        'cell_height' => $rows > 0 ? $height / $rows : 0,
    ];
}

function insertCells(PDO $pdo, int $gridId, array $grid): void {
    $stmt = $pdo->prepare(
        "INSERT INTO plot_grid_cells
            (grid_id, row_idx, col_idx, start_x, start_y, end_x, end_y, is_selected)
         VALUES (?, ?, ?, ?, ?, ?, ?, 0)"
    );
    for ($r = 0; $r < $grid['rows_count']; $r++) {
        for ($c = 0; $c < $grid['cols_count']; $c++) {
            $cx0 = $grid['start_x'] + $c * $grid['cell_width'];
            $cy0 = $grid['start_y'] + $r * $grid['cell_height'];
            $cx1 = $cx0 + $grid['cell_width'];
            $cy1 = $cy0 + $grid['cell_height'];
            $stmt->execute([$gridId, $r + 1, $c + 1, $cx0, $cy0, $cx1, $cy1]);
        }
    }
}

function normalizeGrid(array $g): array {
    return [
        'id' => (int)$g['id'],
        'record_id' => $g['record_id'] === null ? null : (int)$g['record_id'],
        'name' => $g['name'],
        'center_lat' => $g['center_lat'] === null ? null : (float)$g['center_lat'],
        'center_lng' => $g['center_lng'] === null ? null : (float)$g['center_lng'],
        'start_x' => (float)$g['start_x'],
        'start_y' => (float)$g['start_y'],
        'end_x' => (float)$g['end_x'],
        'end_y' => (float)$g['end_y'],
        'grid_width' => (float)$g['grid_width'],
        'grid_height' => (float)$g['grid_height'],
        'rows' => (int)$g['rows_count'],
        'cols' => (int)$g['cols_count'],
        'cell_width' => (float)$g['cell_width'],
        'cell_height' => (float)$g['cell_height'],
        'status' => $g['status'],
        'date_created' => $g['date_created'],
        'date_updated' => $g['date_updated'],
    ];
}

function normalizeCell(array $c): array {
    return [
        'id' => (int)$c['id'],
        'grid_id' => (int)$c['grid_id'],
        'record_id' => $c['record_id'] === null ? null : (int)$c['record_id'],
        'row' => (int)$c['row_idx'],
        'col' => (int)$c['col_idx'],
        'start_x' => (float)$c['start_x'],
        'start_y' => (float)$c['start_y'],
        'end_x' => (float)$c['end_x'],
        'end_y' => (float)$c['end_y'],
        'is_selected' => (int)$c['is_selected'],
        'notes' => $c['notes'],
    ];
}
