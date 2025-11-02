<?php
require_once '../config.php';
require_once '../classes/Chat.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    exit();
}

$dados = json_decode(file_get_contents('php://input'), true);
$remetente_id = $_SESSION['usuario']['id'];
$destinatario_id = $dados['destinatario_id'];
$mensagem = $dados['mensagem'];

if (empty($destinatario_id) || empty($mensagem)) {
    http_response_code(400);
    exit();
}

$chat = new Chat($pdo);
if ($chat->enviarMensagem($remetente_id, $destinatario_id, $mensagem)) {
    echo json_encode(['status' => 'sucesso']);
} else {
    echo json_encode(['status' => 'erro']);
}
?>