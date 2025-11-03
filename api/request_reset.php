<?php
/**
 * Password Reset Request Handler / Manipulador de Solicitação de Redefinição de Senha
 * 
 * Secure endpoint for requesting password reset tokens
 * Endpoint seguro para solicitar tokens de redefinição de senha
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Initialize security middleware / Inicializar middleware de segurança
require_once 'security_helper.php';

// Apply security with stricter rate limiting for password resets
// Aplicar segurança com limitação de taxa mais rigorosa para redefinições de senha
secure_api([
    'rate_limit' => 3,    // Only 3 password reset attempts per minute
    'window' => 60,       // 60 minute window for rate limiting
    'session' => false    // No session needed for password reset
]);

// Inclui o arquivo que faz a conexão com o banco de dados.
require 'db_connect.php';

// Validate request method / Validar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 'Método não permitido', 405);
}

// Rate limit password reset attempts / Limitar tentativas de redefinição de senha
SecurityHelper::rateLimitAction('password_reset', 3, 3600); // 3 attempts per hour

// Sanitize and validate input / Sanitizar e validar entrada
$email = clean_input($_POST['email'] ?? '', 'email');

// Validação inicial: verifica se o e-mail não está vazio e se tem um formato válido.
if (empty($email) || !SecurityHelper::validateEmail($email)) {
    send_error('Invalid email format', 'Por favor, insira um e-mail válido.', 400);
}

// 1. Prepara uma consulta SQL segura (usando prepared statements) para verificar se o e-mail existe na tabela de usuários.
$stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
// Executa a consulta com o parâmetro.
$stmt->execute([$email]);
// Pega o resultado da consulta.
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Se a consulta não retornar nenhuma linha, o e-mail não está cadastrado.
if (!$user) {
    // Por segurança, não informamos ao usuário que o e-mail não foi encontrado.
    // Isso evita que pessoas mal-intencionadas descubram quais e-mails estão cadastrados no sistema.
    // Log the attempt / Registrar a tentativa
    SecurityHelper::logEvent('password_reset_invalid_email', [
        'email' => $email,
        'ip' => SecurityHelper::getClientIP()
    ]);
    
    send_success(['message' => 'Se o e-mail estiver cadastrado, um código de recuperação foi enviado.']);
}

// 2. Se o e-mail existe, gera um token (código) numérico aleatório de 6 dígitos.
$token = random_int(100000, 999999); 
// Cria um objeto de data para o momento atual, definindo o fuso horário como UTC para evitar problemas de timezone.
$expires = new DateTime('now', new DateTimeZone('UTC'));
// Adiciona 15 minutos ao tempo atual para definir a expiração do token.
$expires->add(new DateInterval('PT15M')); 
// Formata a data de expiração para o formato do banco de dados (AAAA-MM-DD HH:MM:SS).
$expires_at = $expires->format('Y-m-d H:i:s');

// 3. Prepara uma consulta para inserir o novo token ou atualizar um existente para o mesmo e-mail.
// A cláusula 'ON DUPLICATE KEY UPDATE' é útil para que o usuário possa solicitar um novo código sem gerar um erro.
$stmt_update = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
// Executa com todos os parâmetros.
$stmt_update->execute([$email, $token, $expires_at, $token, $expires_at]);

// 4. Get user name from the already fetched user data / Obter nome do usuário dos dados já obtidos
$user_name = $user['nome'] ?? 'Usuário';

// Send password reset email / Enviar email de redefinição de senha
$is_email_sent = true;
try {
    require_once 'email_notifications.php';
    $is_email_sent = sendNotification('password_reset', [
        'email' => $email,
        'name' => $user_name,
        'token' => $token
    ]);
} catch (Exception $e) {
    error_log("Failed to send password reset email: " . $e->getMessage());
    $is_email_sent = false;
}

// Log successful password reset request / Registrar solicitação de redefinição bem-sucedida
SecurityHelper::logEvent('password_reset_requested', [
    'email' => $email,
    'user_id' => $user['id'],
    'token_generated' => true,
    'email_sent' => $is_email_sent,
    'ip' => SecurityHelper::getClientIP()
]);

if ($is_email_sent) {
    // Return success response with test token for development / Retorna resposta de sucesso com token de teste para desenvolvimento
    $response_data = [];
    if (getenv('APP_DEBUG') === 'true') {
        $response_data['debug_token'] = $token;
        $response_data['debug_expires'] = $expires_at;
    }
    send_success($response_data, 'Password reset code sent', 'Um código de recuperação foi enviado para seu e-mail.');
} else {
    send_error('Email delivery failed', 'Não foi possível enviar o e-mail de recuperação. Tente novamente mais tarde.', 500);
}

// PDO connections are closed automatically / Conexões PDO são fechadas automaticamente

?>