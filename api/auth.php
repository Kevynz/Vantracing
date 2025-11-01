<?php
// Common auth/session helpers for APIs
// Utilitários comuns de sessão/autenticação para APIs

if (session_status() === PHP_SESSION_NONE) {
    // Use secure-ish defaults where possible
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookieParams['path'] ?? '/',
        'domain' => $cookieParams['domain'] ?? '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function json_response($code, $payload = []) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo json_encode($payload);
    exit;
}

function ensure_logged_in() {
    if (empty($_SESSION['user_id'])) {
        json_response(401, ['success' => false, 'error' => 'Not authenticated']);
    }
}

function ensure_role($role) {
    ensure_logged_in();
    if (empty($_SESSION['role']) || $_SESSION['role'] !== $role) {
        json_response(403, ['success' => false, 'error' => 'Forbidden']);
    }
}

function current_user_id() {
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_user_role() {
    return (string)($_SESSION['role'] ?? '');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token_from_request() {
    // Accept either header X-CSRF-Token or POST field csrf_token
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';
    $token = $headerToken ?: $postToken;
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        json_response(403, ['success' => false, 'error' => 'Invalid CSRF token']);
    }
}

function rate_limit($key, $seconds) {
    $now = time();
    if (!isset($_SESSION['_rate'])) $_SESSION['_rate'] = [];
    $last = $_SESSION['_rate'][$key] ?? 0;
    if (($now - $last) < $seconds) {
        json_response(429, ['success' => false, 'error' => 'Too Many Requests']);
    }
    $_SESSION['_rate'][$key] = $now;
}

function correlation_id() {
    if (empty($_SESSION['_cid'])) $_SESSION['_cid'] = bin2hex(random_bytes(8));
    return $_SESSION['_cid'];
}

function log_api($level, $message, array $context = []) {
    $cid = $context['cid'] ?? correlation_id();
    $ts = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $line = sprintf('%s [%s] (%s) %s %s%s', $ts, strtoupper($level), $cid, $ip, $message, PHP_EOL);
    $logFile = __DIR__ . '/../logs/api.log';
    @file_put_contents($logFile, $line, FILE_APPEND);
}

?>
