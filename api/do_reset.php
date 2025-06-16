<?php

require 'db_connect.php';
header('Content-Type: application/json');

function send_json_response($success, $msg) {
    echo json_encode(['success' => $success, 'msg' => $msg]);
    exit();
}

$email = $_POST['email'] ?? '';
$token = $_POST['token'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (empty($email) || empty($token) || empty($new_password)) {
    send_json_response(false, 'Todos os campos são obrigatórios.');
}
if (strlen($new_password) < 8) {
    send_json_response(false, 'A nova senha deve ter no mínimo 8 caracteres.');
}

// 1. Verificar se o token é válido
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ?");
$stmt->bind_param("ss", $email, $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_json_response(false, 'Código de verificação inválido.');
}

$reset_data = $result->fetch_assoc();

// Pega a hora atual em UTC para a comparação
$now = new DateTime('now', new DateTimeZone('UTC'));

// ##############################################################################
// ## CORREÇÃO FINAL AQUI: Ao ler a data do banco, informamos que ela é UTC.  ##
// ##############################################################################
$expires = new DateTime($reset_data['expires_at'], new DateTimeZone('UTC'));


// Agora a comparação será entre duas datas que o PHP entende como sendo UTC.
if ($now > $expires) {
    send_json_response(false, 'O código de verificação expirou. Por favor, solicite um novo.');
}

// 2. Se o token for válido, atualizar a senha do usuário
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$stmt_update = $conn->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
$stmt_update->bind_param("ss", $hashed_password, $email);
$stmt_update->execute();

if ($stmt_update->affected_rows > 0) {
    // 3. Excluir o token para que não possa ser usado novamente
    $stmt_delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt_delete->bind_param("s", $email);
    $stmt_delete->execute();
    
    send_json_response(true, 'Sua senha foi redefinida com sucesso! Você já pode fazer login.');
} else {
    send_json_response(false, 'Ocorreu um erro ao atualizar sua senha.');
}

$stmt->close();
$conn->close();
?>