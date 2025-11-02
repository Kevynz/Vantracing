<?php
require_once '../config.php';
require_once '../classes/Chat.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    exit();
}

$usuario_logado_id = $_SESSION['usuario']['id'];
$outro_usuario_id = $_GET['com_usuario_id'];

if (empty($outro_usuario_id)) {
    http_response_code(400);
    exit();
}

$chat = new Chat($pdo);
$mensagens = $chat->obterConversa($usuario_logado_id, $outro_usuario_id);

header('Content-Type: application/json');
echo json_encode($mensagens);
?>