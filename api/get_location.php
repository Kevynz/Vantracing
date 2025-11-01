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
require_once __DIR__ . '/auth.php';

try {
    // For now, only allow the motorista to fetch their own latest location via session
    // Por ora, apenas o motorista pode obter sua própria localização via sessão
    ensure_role('motorista');
    $driver_id = current_user_id();

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
    log_api('error', 'get_location error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
