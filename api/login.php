<?php
// ARQUIVO: api/login.php

require 'db_connect.php';
header('Content-Type: application/json');

// Função para enviar respostas JSON e terminar o script.
function send_json_response($success, $data) {
    ob_end_clean(); // Limpa qualquer output indesejado
    $data['success'] = $success;
    echo json_encode($data);
    exit();
}

ob_start(); // Inicia o buffer de saída

$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    send_json_response(false, ['msg' => 'E-mail e senha são obrigatórios.']);
}

// Procura o utilizador na tabela principal 'usuarios'
$sql = "SELECT id, nome, email, role, senha FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
if(!$stmt) {
    send_json_response(false, ['msg' => 'Erro ao preparar a query de usuário.']);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    send_json_response(false, ['msg' => 'Credenciais inválidas.']);
}

$user = $result->fetch_assoc();

// Verifica a senha
if (!password_verify($senha, $user['senha'])) {
    send_json_response(false, ['msg' => 'Credenciais inválidas.']);
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
    $stmt_profile->bind_param("i", $user['id']);
    $stmt_profile->execute();
    $profile_result = $stmt_profile->get_result();
    if ($profile_result->num_rows === 1) {
        // Adiciona os dados do perfil ao objeto do utilizador
        $user['profile'] = $profile_result->fetch_assoc();
    }
    $stmt_profile->close();
}

$stmt->close();
$conn->close();

// Envia a resposta final com todos os dados do utilizador e do seu perfil
send_json_response(true, ['user' => $user]);
?>
