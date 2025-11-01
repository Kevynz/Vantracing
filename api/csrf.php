<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');
ensure_logged_in();
$token = generate_csrf_token();
echo json_encode(['success' => true, 'csrf_token' => $token]);
?>
