<?php

// Inclui o arquivo que faz a conexão com o banco de dados.
require 'db_connect.php';
// Define o cabeçalho da resposta como JSON, para que o JavaScript entenda o que está recebendo.
header('Content-Type: application/json');

/**
 * Função auxiliar para enviar uma resposta JSON padronizada e encerrar o script.
 * @param bool $success - Se a operação teve sucesso.
 * @param string $msg - A mensagem a ser enviada.
 * @param array $data - Dados extras para incluir na resposta (como o token para teste).
 */
function send_json_response($success, $msg, $data = []) {
    // Monta o array de resposta e o converte para o formato JSON.
    echo json_encode(['success' => $success, 'msg' => $msg] + $data);
    // Encerra a execução do script para não enviar mais nada.
    exit();
}

// Pega o e-mail enviado pelo formulário via método POST. O '??' '' evita erros se o campo não existir.
$email = $_POST['email'] ?? '';

// Validação inicial: verifica se o e-mail não está vazio e se tem um formato válido.
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json_response(false, 'Por favor, insira um e-mail válido.');
}

// 1. Prepara uma consulta SQL segura (usando prepared statements) para verificar se o e-mail existe na tabela de usuários.
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
// Associa o valor da variável $email ao '?' na consulta, definindo o tipo como string ('s').
$stmt->bind_param("s", $email);
// Executa a consulta.
$stmt->execute();
// Pega o resultado da consulta.
$result = $stmt->get_result();

// Se a consulta não retornar nenhuma linha, o e-mail não está cadastrado.
if ($result->num_rows === 0) {
    // Por segurança, não informamos ao usuário que o e-mail não foi encontrado.
    // Isso evita que pessoas mal-intencionadas descubram quais e-mails estão cadastrados no sistema.
    send_json_response(true, 'Se o e-mail estiver cadastrado, um código de recuperação foi enviado.');
}

// 2. Se o e-mail existe, gera um token (código) numérico aleatório de 6 dígitos.
$token = random_int(100000, 999999); 
// Cria um objeto de data para o momento atual, definindo o fuso horário como UTC para evitar problemas de timezone.
$expires = new DateTime('now', new DateTimeZone('UTC'));
// Adiciona 15 minutos ao tempo atual para definir a expiração do token.
$expires->add(new DateInterval('PT15M')); 
// Formata a data de expiração para o formato do banco de dados (AAAA-MM-DD HH:MM:SS).
$expires_at = $expires->format('Y-m-d H:i:s');

// 3. Prepara uma consulta para inserir o novo token ou atualizar um existente para o mesmo e-mail.
// A cláusula 'ON DUPLICATE KEY UPDATE' é útil para que o usuário possa solicitar um novo código sem gerar um erro.
$stmt_update = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
// Associa os valores às 5 posições '?' da consulta.
$stmt_update->bind_param("sssss", $email, $token, $expires_at, $token, $expires_at);
$stmt_update->execute();

// 4. Get user name and send password reset email / Obter nome do usuário e enviar email de redefinição
$user = $result->fetch_assoc();
$stmt_name = $conn->prepare("SELECT nome FROM usuarios WHERE email = ?");
$stmt_name->bind_param("s", $email);
$stmt_name->execute();
$user_result = $stmt_name->get_result();
$user_name = $user_result->fetch_assoc()['nome'] ?? 'Usuário';

// Send password reset email / Enviar email de redefinição de senha
$is_email_sent = true;
try {
    require_once 'email_notifications.php';
    $is_email_sent = sendNotification('password_reset', [
        'email' => $email,
        'name' => $user_name,
        'token' => $token
    ]);
} catch (Exception $e) {
    error_log("Failed to send password reset email: " . $e->getMessage());
    $is_email_sent = false;
}

if ($is_email_sent) {
    // Return success response with test token for development / Retorna resposta de sucesso com token de teste para desenvolvimento
    $response_data = ['token_para_teste' => $token];
    if (getenv('APP_DEBUG') === 'true') {
        $response_data['debug_token'] = $token;
    }
    send_json_response(true, 'Um código de recuperação foi enviado para seu e-mail.', $response_data);
} else {
    send_json_response(false, 'Não foi possível enviar o e-mail de recuperação.');
}

// Fecha as conexões para liberar recursos.
$stmt->close();
$conn->close();

?>