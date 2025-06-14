<?php
require 'db_connect.php';
header('Content-Type: application/json');

// Iniciar sessão para obter o ID do usuário, se necessário.
// No entanto, é mais seguro passar o ID via POST a partir de dados de sessão do front-end.
$userId = $_POST['id'] ?? 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'msg' => 'ID de usuário inválido.']);
    exit();
}

// Opcional: verificação de senha para confirmar a exclusão
// $senha = $_POST['senha'] ?? '';
// ... (lógica para verificar a senha) ...

$conn->begin_transaction();

try {
    // A tabela 'criancas' tem ON DELETE CASCADE, então os filhos serão removidos.
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'msg' => 'Conta excluída com sucesso.']);
    } else {
        throw new Exception('Nenhum usuário encontrado com este ID.');
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'msg' => 'Erro ao excluir a conta: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>