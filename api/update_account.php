<?php
// FICHEIRO: api/update_account.php
// RESPONSABILIDADE: Atualizar o nome, e-mail ou senha do utilizador.

ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db_connect.php';

header('Content-Type: application/json');

// Função para enviar respostas JSON
function send_json_response($success, $msg, $data = []) {
    $response = ['success' => $success, 'msg' => $msg];
    echo json_encode(array_merge($response, $data));
    exit();
}

$userId = $_POST['id'] ?? 0;
$currentPassword = $_POST['current_password'] ?? '';
$newName = $_POST['new_name'] ?? '';
$newEmail = $_POST['new_email'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if ($userId <= 0 || empty($currentPassword)) {
    send_json_response(false, 'A sua senha atual é necessária para fazer qualquer alteração.');
}

// --- 1. Verificar a Senha Atual ---
$sql_check_pass = "SELECT senha, email FROM usuarios WHERE id = ?";
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

// --- 2. Atualizar o Nome ---
if (!empty($newName)) {
    $sql_update_name = "UPDATE usuarios SET nome = ? WHERE id = ?";
    $stmt_name = $conn->prepare($sql_update_name);
    $stmt_name->bind_param('si', $newName, $userId);
    $stmt_name->execute();
    if ($stmt_name->affected_rows > 0) {
        $updatesMade['newName'] = $newName;
    }
    $stmt_name->close();
}

// --- 3. Atualizar o E-mail ---
if (!empty($newEmail) && $newEmail !== $user['email']) {
    // Verifica se o novo e-mail já existe
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
    $stmt_email->execute();
    if ($stmt_email->affected_rows > 0) {
        $updatesMade['newEmail'] = $newEmail;
    }
    $stmt_email->close();
}

// --- 4. Atualizar a Senha ---
if (!empty($newPassword)) {
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql_update_pass = "UPDATE usuarios SET senha = ? WHERE id = ?";
    $stmt_pass = $conn->prepare($sql_update_pass);
    $stmt_pass->bind_param('si', $newPasswordHash, $userId);
    $stmt_pass->execute();
    if ($stmt_pass->affected_rows > 0) {
        $updatesMade['passwordChanged'] = true;
    }
    $stmt_pass->close();
}

if (empty($updatesMade)) {
    send_json_response(false, 'Nenhum dado novo foi fornecido ou os dados são iguais aos atuais.');
}

send_json_response(true, 'Dados atualizados com sucesso!', ['updatedFields' => $updatesMade]);

$conn->close();
?>
