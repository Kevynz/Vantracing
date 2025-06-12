<?php
// api/update_perfil.php

require 'db_connect.php';
header('Content-Type: application/json');

// Pega os dados enviados pelo front-end via POST
$userId = $_POST['id'] ?? 0;
$nome = $_POST['nome'] ?? '';
// O e-mail e outros dados sensíveis como CPF geralmente não são alterados aqui,
// mas incluímos o nome como exemplo de campo editável.
// Você pode adicionar outros campos como 'bio', 'profissao', etc.

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