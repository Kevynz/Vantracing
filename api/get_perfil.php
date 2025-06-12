<?php
// api/get_perfil.php

require 'db_connect.php';
header('Content-Type: application/json');

// Pega o ID do usuário enviado pelo front-end via GET
$userId = $_GET['id'] ?? 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'msg' => 'ID de usuário inválido.']);
    exit();
}

// Prepara a query para buscar o usuário, mas NUNCA a senha!
$sql = "SELECT id, nome, email, cpf, cnh, data_nascimento, role FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId); // "i" significa que o parâmetro é um inteiro
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false, 'msg' => 'Usuário não encontrado.']);
}

$stmt->close();
$conn->close();
?>
