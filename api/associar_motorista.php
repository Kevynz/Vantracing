<?php
// api/associar_motorista.php
require_once '../config.php';
require_once '../classes/Crianca.php';
proteger_pagina('responsavel');

header('Content-Type: application/json');

$dados = json_decode(file_get_contents('php://input'), true);

$crianca_id = $dados['crianca_id'] ?? 0;
$motorista_id = $dados['motorista_id'] ?? 0;
$responsavel_id = $_SESSION['usuario']['id'];

if (!$crianca_id || !$motorista_id) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Dados inválidos.']);
    exit();
}

$criancaManager = new Crianca($pdo);
$sucesso = $criancaManager->associarMotorista($crianca_id, $motorista_id, $responsavel_id);

if ($sucesso) {
    // Busca o nome do motorista para retornar e atualizar a UI
    $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt->execute([$motorista_id]);
    $motorista = $stmt->fetch();
    
    echo json_encode(['status' => 'sucesso', 'motorista_nome' => $motorista['nome']]);
} else {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Não foi possível associar o motorista.']);
}
?>