<?php
// FILE: api/register.php

// Ativa a exibição de todos os erros do PHP. Essencial para depuração.
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db_connect.php';

/**
 * Função para enviar uma resposta JSON e terminar o script.
 * @param bool $success - Se a operação teve sucesso.
 * @param string $message - A mensagem a ser enviada.
 * @param array $extraData - Dados adicionais para incluir na resposta.
 */
function send_response($success, $message, $extraData = []) {
    header('Content-Type: application/json');
    $response = ['success' => $success, 'msg' => $message];
    echo json_encode(array_merge($response, $extraData));
    exit();
}

// Dados do formulário
$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';
$role = $_POST['role'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$data_nascimento = $_POST['dataNascimento'] ?? '';
$cnh = $_POST['cnh'] ?? null;

// Validação básica
if (empty($nome) || empty($email) || empty($senha) || empty($role) || empty($cpf) || empty($data_nascimento)) {
    send_response(false, 'Todos os campos obrigatórios devem ser preenchidos.');
}
if ($role === 'motorista' && empty($cnh)) {
    send_response(false, 'A CNH é obrigatória para o motorista.');
}

// Iniciar transação para garantir a integridade dos dados
$conn->begin_transaction();

try {
    // 1. Inserir na tabela principal 'usuarios'
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $sql_user = "INSERT INTO usuarios (nome, email, senha, role) VALUES (?, ?, ?, ?)";
    $stmt_user = $conn->prepare($sql_user);
    if ($stmt_user === false) {
        throw new Exception("Falha ao preparar a query de utilizador: " . $conn->error);
    }
    $stmt_user->bind_param("ssss", $nome, $email, $senha_hash, $role);
    $stmt_user->execute();

    // Obter o ID do usuário recém-criado
    $user_id = $conn->insert_id;
    if ($user_id == 0) {
        throw new Exception("Falha ao criar o registo de utilizador principal.");
    }

    // 2. Inserir na tabela de perfil específica ('responsaveis' ou 'motoristas')
    $stmt_profile = null;
    if ($role === 'responsavel') {
        $sql_profile = "INSERT INTO responsaveis (usuario_id, cpf, data_nascimento) VALUES (?, ?, ?)";
        $stmt_profile = $conn->prepare($sql_profile);
        if ($stmt_profile === false) {
            throw new Exception("Falha ao preparar a query de responsável: " . $conn->error);
        }
        $stmt_profile->bind_param("iss", $user_id, $cpf, $data_nascimento);
    } elseif ($role === 'motorista') {
        $sql_profile = "INSERT INTO motoristas (usuario_id, cpf, data_nascimento, cnh) VALUES (?, ?, ?, ?)";
        $stmt_profile = $conn->prepare($sql_profile);
        if ($stmt_profile === false) {
            throw new Exception("Falha ao preparar a query de motorista: " . $conn->error);
        }
        $stmt_profile->bind_param("isss", $user_id, $cpf, $data_nascimento, $cnh);
    } else {
        throw new Exception("Perfil ('role') inválido.");
    }
    
    $stmt_profile->execute();
    
    // Se tudo correu bem, confirma a transação
    $conn->commit();
    send_response(true, 'Utilizador registado com sucesso!');

} catch (Exception $e) {
    // Se algo falhou, reverte a transação
    $conn->rollback();
    
    // Verifica se o erro é de entrada duplicada (email, cpf, cnh)
    if ($conn->errno == 1062) { 
        send_response(false, 'Este e-mail, CPF ou CNH já está registado.');
    } else {
        // Envia uma mensagem de erro detalhada para depuração
        send_response(false, 'Erro ao registar utilizador: ' . $e->getMessage());
    }
}

// Fechar statements
if (isset($stmt_user)) $stmt_user->close();
if (isset($stmt_profile)) $stmt_profile->close();
$conn->close();

?>
