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

$userId = $_POST['id'] ?? 0;
$currentPassword = $_POST['current_password'] ?? '';
$newName = trim($_POST['new_name'] ?? '');
$newEmail = trim($_POST['new_email'] ?? '');
$newPassword = $_POST['new_password'] ?? '';

if ($userId <= 0 || empty($currentPassword)) {
    send_json_response(false, 'A sua senha atual é necessária para fazer qualquer alteração.');
}

// --- 1. Verificar a Senha Atual e buscar dados existentes ---
$sql_check_pass = "SELECT nome, email, senha FROM usuarios WHERE id = ?";
$stmt_check = $conn->prepare($sql_check_pass);
$stmt_check->bind_param('i', $userId);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows !== 1) {
    send_json_response(false, 'Utilizador não encontrado.');
}

$user = $result->fetch_assoc();
$stmt_check->close();

if (!password_verify($currentPassword, $user['senha'])) {
    send_json_response(false, 'A sua senha atual está incorreta.');
}

$updatesMade = [];
$message = [];

// --- 2. Atualizar o Nome ---
if (!empty($newName) && $newName !== $user['nome']) {
    $sql_update_name = "UPDATE usuarios SET nome = ? WHERE id = ?";
    $stmt_name = $conn->prepare($sql_update_name);
    $stmt_name->bind_param('si', $newName, $userId);
    if ($stmt_name->execute()) {
        $updatesMade['newName'] = $newName;
        $message[] = 'Nome atualizado.';
    }
    $stmt_name->close();
}

// --- 3. Atualizar o E-mail ---
if (!empty($newEmail) && $newEmail !== $user['email']) {
    $sql_check_email = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
    $stmt_email_check = $conn->prepare($sql_check_email);
    $stmt_email_check->bind_param('si', $newEmail, $userId);
    $stmt_email_check->execute();
    if ($stmt_email_check->get_result()->num_rows > 0) {
        send_json_response(false, 'O novo e-mail já está a ser utilizado por outra conta.');
    }
    $stmt_email_check->close();

    $sql_update_email = "UPDATE usuarios SET email = ? WHERE id = ?";
    $stmt_email = $conn->prepare($sql_update_email);
    $stmt_email->bind_param('si', $newEmail, $userId);
    if ($stmt_email->execute()) {
        $updatesMade['newEmail'] = $newEmail;
        $message[] = 'E-mail atualizado.';
    }
    $stmt_email->close();
}

// --- 4. Atualizar a Senha ---
if (!empty($newPassword)) {
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql_update_pass = "UPDATE usuarios SET senha = ? WHERE id = ?";
    $stmt_pass = $conn->prepare($sql_update_pass);
    $stmt_pass->bind_param('si', $newPasswordHash, $userId);
    if ($stmt_pass->execute()) {
        $updatesMade['passwordChanged'] = true;
        $message[] = 'Senha atualizada.';
    }
    $stmt_pass->close();
}

if (empty($updatesMade)) {
    send_json_response(false, 'Nenhum dado novo foi fornecido ou os dados são iguais aos atuais.');
}

send_json_response(true, 'Dados atualizados com sucesso! ' . implode(' ', $message), ['updatedFields' => $updatesMade]);

$conn->close();
?>