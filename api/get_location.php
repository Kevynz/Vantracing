<?php
/**
 * Get Driver Location API
 * Obtém a localização atual do motorista
 *
 * Request:
 *  - GET driver_id (int)
 *
 * Response JSON:
 *  { success: bool, data?: { lat, lng, accuracy, updated_at }, error?: string }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/db_connect.php';

try {
    $driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;
    if ($driver_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid driver_id']);
        exit;
    }

    // Ensure table exists / Garante que a tabela exista
    $createSql = "CREATE TABLE IF NOT EXISTS driver_locations (
        driver_id INT PRIMARY KEY,
        lat DOUBLE NOT NULL,
        lng DOUBLE NOT NULL,
        accuracy DOUBLE NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->exec($createSql);

    $stmt = $conn->prepare('SELECT lat, lng, accuracy, updated_at FROM driver_locations WHERE driver_id = :driver_id');
    $stmt->bindValue(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Not found']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $row]);
} catch (Throwable $e) {
    error_log('get_location error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
