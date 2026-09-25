<?php
/**
 * Delete Plot Grid API
 * Removes the grid from an available plot and clears compartment reservations.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$plot_id = filter_var($input['plot_id'] ?? null, FILTER_VALIDATE_INT);

if (!$plot_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid plot ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if the plot exists and has a grid
    $stmt = $pdo->prepare("SELECT has_grid, plot_number FROM available_plots WHERE id = ?");
    $stmt->execute([$plot_id]);
    $plot = $stmt->fetch();

    if (!$plot) {
        echo json_encode(['success' => false, 'error' => 'Plot not found']);
        exit;
    }

    if ($plot['has_grid'] != 1) {
        echo json_encode(['success' => false, 'error' => 'This plot has no grid to delete']);
        exit;
    }

    // Check for active reservations on this plot's compartments
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM plot_reservations 
        WHERE plot_id = ? AND compartment_id IS NOT NULL AND status IN ('pending', 'approved')
    ");
    $stmt->execute([$plot_id]);
    $activeReservations = (int)$stmt->fetchColumn();

    if ($activeReservations > 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'Cannot delete grid: this plot has active reservations. Cancel or reassign them first.'
        ]);
        exit;
    }

    // Remove the grid from the plot
    $stmt = $pdo->prepare("
        UPDATE available_plots 
        SET has_grid = 0, grid_rows = NULL, grid_cols = NULL, compartment_count = 0, grid_angle = NULL
        WHERE id = ?
    ");
    $stmt->execute([$plot_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Grid deleted successfully for plot ' . htmlspecialchars($plot['plot_number'])
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Delete plot grid error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
