<?php

ob_start();
header('Content-Type: application/json');

function send_json_response($success, $msg) {
    ob_end_clean();
    echo json_encode(['success' => $success, 'msg' => $msg]);
    exit();
}

@require 'db_connect.php';

if (!isset($conn) || $conn->connect_error) {
    send_json_response(false, 'Falha na conexão com a base de dados.');
}

$nome = $_POST['nome'] ?? '';
$data_nascimento = $_POST['data_nascimento'] ?? '';
$escola = $_POST['escola'] ?? '';
$observacoes = $_POST['observacoes'] ?? '';
$usuario_id = $_POST['usuario_id'] ?? 0;

if (empty($nome) || empty($data_nascimento) || $usuario_id <= 0) {
    send_json_response(false, 'Nome, data de nascimento e ID do responsável são obrigatórios.');
}

$sql = "INSERT INTO criancas (nome, data_nascimento, escola, observacoes, usuario_id) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    send_json_response(false, 'Erro ao preparar a query: ' . $conn->error);
}

$stmt->bind_param("ssssi", $nome, $data_nascimento, $escola, $observacoes, $usuario_id);

if ($stmt->execute()) {
    send_json_response(true, 'Criança registada com sucesso!');
} else {
    send_json_response(false, 'Erro ao registar a criança: ' . $stmt->error);
}

$stmt->close();
$conn->close();

?>