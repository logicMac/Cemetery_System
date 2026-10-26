<?php
/**
 * Relocate Record API (admin)
 * POST action=relocate&record_id=N — moves an expired burial record's marker
 *      to the configured relocation area (original coords preserved).
 * POST action=restore&record_id=N — moves it back to its original plot.
 */

session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$record_id = (int)($_POST['record_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!in_array($action, ['relocate', 'restore', 'relocate_all'], true)
    || ($action !== 'relocate_all' && $record_id <= 0)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    if ($action === 'relocate_all') {
        $lat = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'relocation_lat'")->fetchColumn();
        $lng = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'relocation_lng'")->fetchColumn();
        if ($lat === false || $lng === false || $lat === '' || $lng === '') {
            echo json_encode(['success' => false, 'message' => 'Set the relocation area first']);
            exit;
        }
        $expired = $pdo->query("SELECT id FROM burial_records WHERE is_relocated = 0 AND expiration_date IS NOT NULL AND expiration_date < CURDATE()")->fetchAll(PDO::FETCH_COLUMN);
        $count = (int)$pdo->query("SELECT COUNT(*) FROM burial_records WHERE is_relocated = 1")->fetchColumn();
        $metersPerLat = 111320;
        $metersPerLng = 111320 * cos(deg2rad((float)$lat)) ?: 1;
        $upd = $pdo->prepare("UPDATE burial_records SET is_relocated = 1, orig_latitude = latitude, orig_longitude = longitude, latitude = ?, longitude = ?, relocated_at = NOW() WHERE id = ?");
        foreach ($expired as $id) {
            $angle = $count * 2.4;
            $radius = 1.2 * ceil($count / 8 + 0.5);
            $upd->execute([
                (float)$lat + ($radius * cos($angle)) / $metersPerLat,
                (float)$lng + ($radius * sin($angle)) / $metersPerLng,
                $id,
            ]);
            $count++;
        }
        echo json_encode(['success' => true, 'message' => count($expired) . ' expired plot(s) relocated to the holding area']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, latitude, longitude, is_relocated, orig_latitude, orig_longitude, expiration_date FROM burial_records WHERE id = ?");
    $stmt->execute([$record_id]);
    $rec = $stmt->fetch();
    if (!$rec) {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
        exit;
    }

    if ($action === 'relocate') {
        if ((int)$rec['is_relocated'] === 1) {
            echo json_encode(['success' => false, 'message' => 'Record is already relocated']);
            exit;
        }

        $lat = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'relocation_lat'")->fetchColumn();
        $lng = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'relocation_lng'")->fetchColumn();
        if ($lat === false || $lng === false || $lat === '' || $lng === '') {
            echo json_encode(['success' => false, 'message' => 'Set the relocation area first']);
            exit;
        }

        // Spread relocated markers in a small spiral so they don't stack on one point
        $count = (int)$pdo->query("SELECT COUNT(*) FROM burial_records WHERE is_relocated = 1")->fetchColumn();
        $metersPerLat = 111320;
        $metersPerLng = 111320 * cos(deg2rad((float)$lat));
        $angle = $count * 2.4; // golden-ish angle
        $radius = 1.2 * ceil($count / 8 + 0.5); // grows slowly outward
        $newLat = (float)$lat + ($radius * cos($angle)) / $metersPerLat;
        $newLng = (float)$lng + ($radius * sin($angle)) / ($metersPerLng ?: 1);

        $upd = $pdo->prepare("
            UPDATE burial_records
            SET is_relocated = 1,
                orig_latitude = latitude,
                orig_longitude = longitude,
                latitude = ?, longitude = ?,
                relocated_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([$newLat, $newLng, $record_id]);
        echo json_encode(['success' => true, 'message' => 'Record relocated to the holding area']);
    } else {
        if ((int)$rec['is_relocated'] !== 1) {
            echo json_encode(['success' => false, 'message' => 'Record is not relocated']);
            exit;
        }
        if ($rec['orig_latitude'] === null || $rec['orig_longitude'] === null) {
            echo json_encode(['success' => false, 'message' => 'Original coordinates were not saved']);
            exit;
        }
        $upd = $pdo->prepare("
            UPDATE burial_records
            SET is_relocated = 0,
                latitude = orig_latitude,
                longitude = orig_longitude,
                orig_latitude = NULL, orig_longitude = NULL,
                relocated_at = NULL
            WHERE id = ?
        ");
        $upd->execute([$record_id]);
        echo json_encode(['success' => true, 'message' => 'Record restored to its original plot']);
    }
} catch (PDOException $e) {
    error_log('Relocate record error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
