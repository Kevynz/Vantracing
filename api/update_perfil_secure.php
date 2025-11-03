<?php
/**
 * Profile Update Handler / Manipulador de Atualização de Perfil
 * 
 * Secure endpoint for updating user profile information
 * Endpoint seguro para atualizar informações do perfil do usuário
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Initialize security middleware / Inicializar middleware de segurança
require_once 'security_helper.php';

// Apply security with authentication required
// Aplicar segurança com autenticação obrigatória
secure_api([
    'rate_limit' => 10,   // 10 profile updates per hour
    'window' => 60,       // 60 minute window
    'session' => true     // Require active session
]);

require 'db_connect.php';

// Validate request method / Validar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 'Método não permitido', 405);
}

// Require authentication / Exigir autenticação
$user_id = require_auth();

// Validate CSRF token / Validar token CSRF
validate_csrf();

// Rate limit profile updates / Limitar atualizações de perfil
SecurityHelper::rateLimitAction('profile_update', 10, 3600); // 10 updates per hour

// Sanitize and validate input / Sanitizar e validar entrada
$current_password = clean_input($_POST['current_password'] ?? '', 'string');
$new_name = clean_input($_POST['new_name'] ?? '', 'string');
$new_email = clean_input($_POST['new_email'] ?? '', 'email');
$new_password = clean_input($_POST['new_password'] ?? '', 'string');

// Current password is required for any changes / Senha atual é obrigatória para qualquer alteração
if (empty($current_password)) {
    send_error('Current password required', 'A sua senha atual é necessária para fazer qualquer alteração.', 400);
}

try {
    // Verify current password / Verificar senha atual
    $stmt_check = $conn->prepare('SELECT nome, email, senha FROM usuarios WHERE id = ?');
    $stmt_check->execute([$user_id]);
    $user_data = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_data || !SecurityHelper::verifyPassword($current_password, $user_data['senha'])) {
        SecurityHelper::logEvent('profile_update_invalid_password', [
            'user_id' => $user_id,
            'ip' => SecurityHelper::getClientIP()
        ]);
        send_error('Invalid current password', 'Senha atual incorreta.', 401);
    }
    
    $changes_made = [];
    
    // Update name if provided / Atualizar nome se fornecido
    if (!empty($new_name) && $new_name !== $user_data['nome']) {
        if (strlen($new_name) < 2 || strlen($new_name) > 100) {
            send_error('Invalid name length', 'Nome deve ter entre 2 e 100 caracteres.', 400);
        }
        
        $stmt_name = $conn->prepare('UPDATE usuarios SET nome = ? WHERE id = ?');
        $stmt_name->execute([$new_name, $user_id]);
        $changes_made[] = 'nome';
        
        // Update session data / Atualizar dados da sessão
        $_SESSION['nome'] = $new_name;
    }
    
    // Update email if provided / Atualizar email se fornecido
    if (!empty($new_email) && $new_email !== $user_data['email']) {
        // Validate email format / Validar formato do email
        if (!SecurityHelper::validateEmail($new_email)) {
            send_error('Invalid email format', 'Formato de e-mail inválido.', 400);
        }
        
        // Check if email already exists / Verificar se email já existe
        $stmt_email_check = $conn->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
        $stmt_email_check->execute([$new_email, $user_id]);
        if ($stmt_email_check->fetch()) {
            send_error('Email already exists', 'Este e-mail já está em uso.', 409);
        }
        
        $stmt_email = $conn->prepare('UPDATE usuarios SET email = ? WHERE id = ?');
        $stmt_email->execute([$new_email, $user_id]);
        $changes_made[] = 'email';
        
        // Update session data / Atualizar dados da sessão
        $_SESSION['email'] = $new_email;
    }
    
    // Update password if provided / Atualizar senha se fornecida
    if (!empty($new_password)) {
        // Validate password strength / Validar força da senha
        $password_validation = SecurityHelper::validatePassword($new_password);
        if ($password_validation !== true) {
            send_error('Weak password', implode(', ', $password_validation), 400);
        }
        
        // Check if new password is different from current / Verificar se nova senha é diferente da atual
        if (SecurityHelper::verifyPassword($new_password, $user_data['senha'])) {
            send_error('Same password', 'A nova senha deve ser diferente da senha atual.', 400);
        }
        
        $new_password_hash = SecurityHelper::hashPassword($new_password);
        $stmt_pass = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
        $stmt_pass->execute([$new_password_hash, $user_id]);
        $changes_made[] = 'senha';
    }
    
    if (empty($changes_made)) {
        send_error('No changes provided', 'Nenhuma alteração foi fornecida.', 400);
    }
    
    // Log successful profile update / Registrar atualização de perfil bem-sucedida
    SecurityHelper::logEvent('profile_updated', [
        'user_id' => $user_id,
        'changes' => $changes_made,
        'ip' => SecurityHelper::getClientIP(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Send security notification if email or password changed / Enviar notificação de segurança se email ou senha mudaram
    if (in_array('email', $changes_made) || in_array('senha', $changes_made)) {
        try {
            require_once 'email_notifications.php';
            sendNotification('security_alert', [
                'email' => $new_email ?? $user_data['email'],
                'name' => $new_name ?? $user_data['nome'],
                'action' => 'Alteração de perfil',
                'details' => 'Dados de login (email/senha) foram alterados'
            ]);
        } catch (Exception $e) {
            error_log("Failed to send security notification: " . $e->getMessage());
        }
    }
    
    send_success([
        'changes_made' => $changes_made,
        'updated_fields' => count($changes_made)
    ], 'Profile updated successfully', 'Perfil atualizado com sucesso!');
    
} catch (Exception $e) {
    // Log error / Registrar erro
    SecurityHelper::logEvent('profile_update_error', [
        'user_id' => $user_id,
        'error' => $e->getMessage(),
        'ip' => SecurityHelper::getClientIP()
    ]);
    
    send_error('Update failed', 'Erro ao atualizar perfil. Tente novamente.', 500);
}

// PDO connections are closed automatically / Conexões PDO são fechadas automaticamente
?>