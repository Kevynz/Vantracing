<?php
require 'db_connect.php';
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

ensure_logged_in();
verify_csrf_token_from_request();

$userId = (int)($_POST['id'] ?? 0);
if ($userId <= 0 || $userId !== current_user_id()) {
    json_response(403, ['success' => false, 'msg' => 'Operação não autorizada para este usuário.']);
}

try {
    $conn->beginTransaction();

    // DELETE CASCADE will clean up children in related tables if configured
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);

    if ($stmt->rowCount() > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'msg' => 'Conta excluída com sucesso.']);
    } else {
        throw new Exception('Nenhum usuário encontrado com este ID.');
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    log_api('error', 'delete_account error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'Erro ao excluir a conta: ' . $e->getMessage()]);
}
?>