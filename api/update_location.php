<?php
/**
 * Update Driver Location API
 * Atualiza a localização do motorista
 *
 * Request:
 *  - POST driver_id (int)
 *  - POST lat (float)
 *  - POST lng (float)
 *  - POST accuracy (float, optional)
 *
 * Response JSON:
 *  { success: bool, error?: string }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Require authenticated motorista and rate limit 1 req/s
    ensure_role('motorista');
    rate_limit('update_location', 1);

    $driver_id = current_user_id();
    $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
    $accuracy = isset($_POST['accuracy']) ? (float)$_POST['accuracy'] : null;

    if ($driver_id <= 0 || $lat === null || $lng === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }

    // Create table if not exists (simple schema) / Cria a tabela se não existir
    $createSql = "CREATE TABLE IF NOT EXISTS driver_locations (
        driver_id INT PRIMARY KEY,
        lat DOUBLE NOT NULL,
        lng DOUBLE NOT NULL,
        accuracy DOUBLE NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->exec($createSql);

    // Upsert current position / Atualiza ou insere a posição atual
    $sql = "INSERT INTO driver_locations (driver_id, lat, lng, accuracy) VALUES (:driver_id, :lat, :lng, :accuracy)
            ON DUPLICATE KEY UPDATE lat = VALUES(lat), lng = VALUES(lng), accuracy = VALUES(accuracy), updated_at = CURRENT_TIMESTAMP";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->bindValue(':lat', $lat);
    $stmt->bindValue(':lng', $lng);
    if ($accuracy !== null) {
        $stmt->bindValue(':accuracy', $accuracy);
    } else {
        $stmt->bindValue(':accuracy', null, PDO::PARAM_NULL);
    }
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    log_api('error', 'update_location error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
