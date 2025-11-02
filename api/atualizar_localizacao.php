<?php
require_once '../config.php';
proteger_pagina('motorista');

$dados = json_decode(file_get_contents('php://input'), true);
$motorista_id = $_SESSION['usuario']['id'];
$latitude = $dados['latitude'];
$longitude = $dados['longitude'];

// 'INSERT ... ON DUPLICATE KEY UPDATE' é perfeito para isso
$sql = "INSERT INTO localizacoes_ativas (motorista_id, latitude, longitude)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE latitude = ?, longitude = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$motorista_id, $latitude, $longitude, $latitude, $longitude]);

echo json_encode(['status' => 'sucesso']);
?>