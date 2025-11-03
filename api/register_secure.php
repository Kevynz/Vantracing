<?php
/**
 * User Registration Handler / Manipulador de Registro de Usuário
 * 
 * Secure registration endpoint with validation and email notifications
 * Endpoint de registro seguro com validação e notificações por email
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Initialize security middleware / Inicializar middleware de segurança
require_once 'security_helper.php';

// Apply security with rate limiting for registration attempts
// Aplicar segurança com limitação de taxa para tentativas de registro
secure_api([
    'rate_limit' => 3,    // Only 3 registration attempts per hour
    'window' => 60,       // 60 minute window
    'session' => false    // No session needed for registration
]);

require 'db_connect.php';

// Validate request method / Validar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 'Método não permitido', 405);
}

// Rate limit registration attempts / Limitar tentativas de registro
SecurityHelper::rateLimitAction('registration', 3, 3600); // 3 attempts per hour

// Sanitize and validate input / Sanitizar e validar entrada
$nome = clean_input($_POST['nome'] ?? '', 'string');
$email = clean_input($_POST['email'] ?? '', 'email');
$senha = clean_input($_POST['senha'] ?? '', 'string');
$role = clean_input($_POST['role'] ?? '', 'string');
$cpf = clean_input($_POST['cpf'] ?? '', 'string');
$data_nascimento = clean_input($_POST['dataNascimento'] ?? '', 'string');
$cnh = !empty($_POST['cnh']) ? clean_input($_POST['cnh'], 'string') : null;

// Validação básica
if (empty($nome) || empty($email) || empty($senha) || empty($role) || empty($cpf) || empty($data_nascimento)) {
    send_error('Missing required fields', 'Todos os campos obrigatórios devem ser preenchidos.', 400);
}

// Validate email format / Validar formato do email
if (!SecurityHelper::validateEmail($email)) {
    send_error('Invalid email format', 'Formato de e-mail inválido.', 400);
}

// Validate password strength / Validar força da senha
$password_validation = SecurityHelper::validatePassword($senha);
if ($password_validation !== true) {
    send_error('Weak password', implode(', ', $password_validation), 400);
}

// Validate role / Validar função
$allowed_roles = ['responsavel', 'motorista'];
if (!in_array($role, $allowed_roles)) {
    send_error('Invalid role', 'Função inválida.', 400);
}

if ($role === 'motorista' && empty($cnh)) {
    send_error('CNH required for driver', 'A CNH é obrigatória para o motorista.', 400);
}

// Log registration attempt / Registrar tentativa de registro
SecurityHelper::logEvent('registration_attempt', [
    'email' => $email,
    'role' => $role,
    'ip' => SecurityHelper::getClientIP()
]);

// Iniciar transação para garantir a integridade dos dados
$conn->beginTransaction();

try {
    // Check for existing email / Verificar email existente
    $check_stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check_stmt->execute([$email]);
    if ($check_stmt->fetch()) {
        throw new Exception("Este e-mail já está registrado.");
    }

    // Check for existing CPF / Verificar CPF existente
    $cpf_check_sql = $role === 'responsavel' ? 
        "SELECT usuario_id FROM responsaveis WHERE cpf = ?" :
        "SELECT usuario_id FROM motoristas WHERE cpf = ?";
    $cpf_stmt = $conn->prepare($cpf_check_sql);
    $cpf_stmt->execute([$cpf]);
    if ($cpf_stmt->fetch()) {
        throw new Exception("Este CPF já está registrado.");
    }

    // Check CNH for drivers / Verificar CNH para motoristas
    if ($role === 'motorista' && $cnh) {
        $cnh_stmt = $conn->prepare("SELECT usuario_id FROM motoristas WHERE cnh = ?");
        $cnh_stmt->execute([$cnh]);
        if ($cnh_stmt->fetch()) {
            throw new Exception("Esta CNH já está registrada.");
        }
    }

    // 1. Insert into main 'usuarios' table / Inserir na tabela principal 'usuarios'
    $senha_hash = SecurityHelper::hashPassword($senha);
    $sql_user = "INSERT INTO usuarios (nome, email, senha, role) VALUES (?, ?, ?, ?)";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->execute([$nome, $email, $senha_hash, $role]);

    // Get the newly created user ID / Obter o ID do usuário recém-criado
    $user_id = $conn->lastInsertId();
    if (!$user_id) {
        throw new Exception("Falha ao criar o registro de usuário principal.");
    }

    // 2. Insert into specific profile table / Inserir na tabela de perfil específica
    if ($role === 'responsavel') {
        $sql_profile = "INSERT INTO responsaveis (usuario_id, cpf, data_nascimento) VALUES (?, ?, ?)";
        $stmt_profile = $conn->prepare($sql_profile);
        $stmt_profile->execute([$user_id, $cpf, $data_nascimento]);
    } elseif ($role === 'motorista') {
        $sql_profile = "INSERT INTO motoristas (usuario_id, cpf, data_nascimento, cnh) VALUES (?, ?, ?, ?)";
        $stmt_profile = $conn->prepare($sql_profile);
        $stmt_profile->execute([$user_id, $cpf, $data_nascimento, $cnh]);
    }
    
    // Se tudo correu bem, confirma a transação
    $conn->commit();
    
    // Log successful registration / Registrar registro bem-sucedido
    SecurityHelper::logEvent('registration_successful', [
        'user_id' => $user_id,
        'email' => $email,
        'role' => $role,
        'ip' => SecurityHelper::getClientIP()
    ]);
    
    // Send welcome email / Enviar email de boas-vindas
    $email_sent = true;
    try {
        require_once 'email_notifications.php';
        $email_sent = sendNotification('welcome', [
            'email' => $email,
            'name' => $nome,
            'role' => $role
        ]);
    } catch (Exception $e) {
        error_log("Failed to send welcome email: " . $e->getMessage());
        $email_sent = false;
    }
    
    send_success([
        'user_id' => $user_id,
        'email_sent' => $email_sent
    ], 'Registration successful', 'Utilizador registado com sucesso!');

} catch (Exception $e) {
    // Se algo falhou, reverte a transação
    $conn->rollBack();
    
    // Log registration failure / Registrar falha no registro
    SecurityHelper::logEvent('registration_failed', [
        'email' => $email,
        'error' => $e->getMessage(),
        'ip' => SecurityHelper::getClientIP()
    ]);
    
    // Check for specific errors / Verificar erros específicos
    $message = $e->getMessage();
    if (strpos($message, 'já está registr') !== false) {
        send_error('Duplicate data', $message, 409);
    } else {
        send_error('Registration failed', 'Erro ao registar utilizador. Tente novamente.', 500);
    }
}

// PDO connections are closed automatically / Conexões PDO são fechadas automaticamente
?>