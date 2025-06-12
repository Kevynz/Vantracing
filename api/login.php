<?php
// api/login.php
require 'db_connect.php';
header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    echo json_encode(['success' => false, 'msg' => 'E-mail e senha são obrigatórios.']);
    exit();
}

// --- SEGURANÇA: Prepara a query para evitar SQL Injection ---
$sql = "SELECT id, nome, email, role, senha FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // --- SEGURANÇA: Verifica a senha criptografada ---
    if (password_verify($senha, $user['senha'])) {
        // Senha correta!
        // Remove a senha do array antes de enviar de volta para o front-end
        unset($user['senha']);

        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        // Senha incorreta
        echo json_encode(['success' => false, 'msg' => 'Credenciais inválidas.']);
    }
} else {
    // Usuário não encontrado
    echo json_encode(['success' => false, 'msg' => 'Credenciais inválidas.']);
}

$stmt->close();
$conn->close();
?>