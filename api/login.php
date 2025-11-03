<?php
/**
 * User Login Handler / Manipulador de Login de Usuário
 * 
 * Secure authentication endpoint with rate limiting and security monitoring
 * Endpoint de autenticação seguro com limitação de taxa e monitoramento de segurança
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Initialize security middleware / Inicializar middleware de segurança
require_once 'security_helper.php';

// Apply security with stricter rate limiting for login attempts
// Aplicar segurança com limitação de taxa mais rigorosa para tentativas de login
secure_api([
    'rate_limit' => 5,    // Only 5 login attempts per minute
    'window' => 1,        // 1 minute window
    'session' => true     // Enable secure session management
]);

require 'db_connect.php';
require_once __DIR__ . '/auth.php';

// Validate request method / Validar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 'Método não permitido', 405);
}

// Rate limit login attempts / Limitar tentativas de login
SecurityHelper::rateLimitAction('login', 5, 900); // 5 attempts per 15 minutes

// Sanitize and validate input / Sanitizar e validar entrada
$email = clean_input($_POST['email'] ?? '', 'email');
$senha = clean_input($_POST['senha'] ?? '', 'string');

if (empty($email) || empty($senha)) {
    send_error('Missing credentials', 'E-mail e senha são obrigatórios.', 400);
}

if (!SecurityHelper::validateEmail($email)) {
    send_error('Invalid email format', 'Formato de e-mail inválido.', 400);
}

// Log login attempt / Registrar tentativa de login
SecurityHelper::logEvent('login_attempt', [
    'email' => $email,
    'ip' => SecurityHelper::getClientIP(),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

// Procura o utilizador na tabela principal 'usuarios'
$sql = "SELECT id, nome, email, role, senha FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Log failed login attempt / Registrar tentativa de login falhada
    SecurityHelper::logEvent('login_failed', [
        'email' => $email,
        'reason' => 'user_not_found',
        'ip' => SecurityHelper::getClientIP()
    ]);
    send_error('Invalid credentials', 'Credenciais inválidas.', 401);
}

// Verifica a senha
if (!password_verify($senha, $user['senha'])) {
    // Log failed password attempt / Registrar tentativa de senha incorreta
    SecurityHelper::logEvent('login_failed', [
        'email' => $email,
        'user_id' => $user['id'],
        'reason' => 'invalid_password',
        'ip' => SecurityHelper::getClientIP()
    ]);
    send_error('Invalid credentials', 'Credenciais inválidas.', 401);
}

// Remove a senha do array antes de continuar
unset($user['senha']);

// Busca os dados do perfil específico (motorista ou responsável)
$profile_sql = "";
if ($user['role'] === 'responsavel') {
    $profile_sql = "SELECT cpf, data_nascimento FROM responsaveis WHERE usuario_id = ?";
} elseif ($user['role'] === 'motorista') {
    $profile_sql = "SELECT cpf, data_nascimento, cnh FROM motoristas WHERE usuario_id = ?";
}

if (!empty($profile_sql)) {
    $stmt_profile = $conn->prepare($profile_sql);
    $stmt_profile->execute([$user['id']]);
    $profile_row = $stmt_profile->fetch(PDO::FETCH_ASSOC);
    if ($profile_row) {
        $user['profile'] = $profile_row;
    }
}

// Start server-side session auth / Inicia sessão autenticada no servidor
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_role'] = (string)$user['role']; // Updated for SecurityHelper compatibility
$_SESSION['nome'] = (string)$user['nome'];
$_SESSION['email'] = (string)$user['email'];

// Generate CSRF token for subsequent requests / Gerar token CSRF para requisições subsequentes
$csrf = SecurityHelper::generateCSRF();

// Log successful login / Registrar login bem-sucedido
SecurityHelper::logEvent('login_successful', [
    'user_id' => $user['id'],
    'email' => $email,
    'role' => $user['role'],
    'ip' => SecurityHelper::getClientIP(),
    'session_id' => session_id()
]);

$stmt = null; // release statement
// PDO connection will be closed by shutdown handler

// Envia a resposta final com todos os dados + csrf_token para o frontend
send_success([
    'user' => $user, 
    'csrf_token' => $csrf,
    'session_timeout' => 30 * 60 // 30 minutes in seconds
], 'Login successful', 'Login realizado com sucesso');
?>
