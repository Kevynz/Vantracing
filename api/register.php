<?php
// api/register.php

// Inclui o arquivo de conexão
require 'db_connect.php';

// Define o cabeçalho como JSON para a resposta
header('Content-Type: application/json');

// Pega os dados enviados pelo front-end (via POST)
$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$cnh = $_POST['cnh'] ?? null; // CNH pode ser nula
$data_nascimento = $_POST['dataNascimento'] ?? '';
$senha = $_POST['senha'] ?? '';
$role = $_POST['role'] ?? '';

// Validação simples dos dados
if (empty($nome) || empty($email) || empty($cpf) || empty($data_nascimento) || empty($senha) || empty($role)) {
    echo json_encode(['success' => false, 'msg' => 'Todos os campos obrigatórios devem ser preenchidos.']);
    exit();
}

// --- SEGURANÇA: Criptografa a senha ---
// NUNCA salve a senha em texto puro!
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// --- SEGURANÇA: Prepara a query para evitar SQL Injection ---
$sql = "INSERT INTO usuarios (nome, email, cpf, cnh, data_nascimento, senha, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'msg' => 'Erro ao preparar a query.']);
    exit();
}

// Associa os parâmetros
$stmt->bind_param("sssssss", $nome, $email, $cpf, $cnh, $data_nascimento, $senha_hash, $role);

// Executa a query
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'msg' => 'Usuário registrado com sucesso!']);
} else {
    // Verifica se o erro é de entrada duplicada (email ou cpf)
    if ($conn->errno == 1062) {
        echo json_encode(['success' => false, 'msg' => 'Este e-mail ou CPF já está cadastrado.']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Erro ao registrar usuário: ' . $stmt->error]);
    }
}

// Fecha a conexão
$stmt->close();
$conn->close();
?>