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

$child_id = $_POST['id'] ?? 0;

if ($child_id <= 0) {
    send_json_response(false, 'ID da criança inválido.');
}

$sql = "DELETE FROM criancas WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    send_json_response(false, 'Erro ao preparar a query: ' . $conn->error);
}

$stmt->bind_param("i", $child_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    send_json_response(true, 'Criança apagada com sucesso.');
} else {
    send_json_response(false, 'Erro ao apagar a criança ou criança não encontrada.');
}

$stmt->close();
$conn->close();

?>
