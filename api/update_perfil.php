<?php
// api/update_perfil.php

require 'db_connect.php';
header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'msg' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// Pega os dados enviados pelo front-end via POST
$userId = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : 0;
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
// Valida se o nome contém apenas letras, espaços e acentos comuns
if (!preg_match('/^[\p{L}\s\-\'\.]+$/u', $nome)) {
    echo json_encode(['success' => false, 'msg' => 'Nome inválido.']);
    exit();
}

if ($userId <= 0 || empty($nome)) {
    echo json_encode(['success' => false, 'msg' => 'Dados inválidos.']);
    exit();
}

// Prepara a query para ATUALIZAR o nome do usuário
// Adicione outros campos aqui conforme necessário (ex: SET nome = ?, profissao = ?, ...)
$sql = "UPDATE usuarios SET nome = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nome, $userId); // "s" para string (nome), "i" para integer (id)

if ($stmt->execute()) {
    // Verifica se alguma linha foi realmente alterada
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'msg' => 'Perfil atualizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Nenhum dado foi alterado.']);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'Erro ao atualizar o perfil.']);
}

$stmt->close();
$conn->close();
?>