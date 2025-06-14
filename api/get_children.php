<?php

// Inicia o buffer de saída para capturar qualquer output indesejado.
ob_start();

// Define o tipo de conteúdo como JSON desde o início.
header('Content-Type: application/json');

// Função para enviar uma resposta JSON limpa e terminar a execução.
function send_json_response($success, $data) {
    // Limpa qualquer output que possa ter sido gerado (avisos, etc.).
    ob_end_clean();
    // Adiciona o status de sucesso ao array de dados.
    $data['success'] = $success;
    // Envia a resposta JSON final.
    echo json_encode($data);
    exit();
}

// Tenta incluir o ficheiro de conexão.
@require 'db_connect.php';

// Verifica se a conexão falhou.
if (!isset($conn) || $conn->connect_error) {
    send_json_response(false, ['msg' => 'Falha crítica na conexão com a base de dados. Verifique o db_connect.php.', 'children' => []]);
}

$usuario_id = $_GET['usuario_id'] ?? 0;

if ($usuario_id <= 0) {
    send_json_response(false, ['msg' => 'ID de utilizador inválido.', 'children' => []]);
}

$sql = "SELECT id, nome, data_nascimento, escola FROM criancas WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    send_json_response(false, ['msg' => 'Erro ao preparar a query: ' . $conn->error, 'children' => []]);
}

$stmt->bind_param("i", $usuario_id);

if (!$stmt->execute()) {
    send_json_response(false, ['msg' => 'Erro ao executar a query: ' . $stmt->error, 'children' => []]);
}

$result = $stmt->get_result();
$children = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();

send_json_response(true, ['children' => $children]);

?>