<?php
require_once '../config.php';
proteger_pagina('responsavel');

// Lógica para achar o motorista associado à criança do responsável
// (Neste exemplo, vamos buscar um motorista fixo para simplificar)
$motorista_id = 1; // Substituir pela lógica real

$stmt = $pdo->prepare("SELECT latitude, longitude FROM localizacoes_ativas WHERE motorista_id = ?");
$stmt->execute([$motorista_id]);
$localizacao = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($localizacao ?: ['error' => 'Localização não encontrada']);
?>