<?php
// ARQUIVO: api/login.php

require 'db_connect.php';
require_once __DIR__ . '/auth.php';
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
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    send_json_response(false, ['msg' => 'Credenciais inválidas.']);
}

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
    $stmt_profile->execute([$user['id']]);
    $profile_row = $stmt_profile->fetch(PDO::FETCH_ASSOC);
    if ($profile_row) {
        $user['profile'] = $profile_row;
    }
}

// Start server-side session auth / Inicia sessão autenticada no servidor
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = (string)$user['role'];
$_SESSION['nome'] = (string)$user['nome'];
$_SESSION['email'] = (string)$user['email'];

// Generate CSRF token for subsequent POSTs
$csrf = generate_csrf_token();

$stmt = null; // release statement
// PDO connection will be closed by shutdown handler

// Envia a resposta final com todos os dados + csrf_token para o frontend
send_json_response(true, ['user' => $user, 'csrf_token' => $csrf]);
?>
