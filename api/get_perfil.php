<?php
// api/get_perfil.php (PDO + normalized profile)
require 'db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    echo json_encode(['success' => false, 'msg' => 'ID de usuário inválido.']);
    exit();
}

try {
    // Busca dados principais do usuário (sem senha)
    $stmt = $conn->prepare('SELECT id, nome, email, role FROM usuarios WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'msg' => 'Usuário não encontrado.']);
        exit();
    }

    // Busca perfil específico
    if ($user['role'] === 'motorista') {
        $stmtP = $conn->prepare('SELECT cpf, data_nascimento, cnh FROM motoristas WHERE usuario_id = ?');
        $stmtP->execute([$user['id']]);
        $profile = $stmtP->fetch(PDO::FETCH_ASSOC);
        if ($profile) $user['profile'] = $profile;
    } elseif ($user['role'] === 'responsavel') {
        $stmtP = $conn->prepare('SELECT cpf, data_nascimento FROM responsaveis WHERE usuario_id = ?');
        $stmtP->execute([$user['id']]);
        $profile = $stmtP->fetch(PDO::FETCH_ASSOC);
        if ($profile) $user['profile'] = $profile;
    }

    echo json_encode(['success' => true, 'user' => $user]);
} catch (Throwable $e) {
    error_log('get_perfil error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Erro no servidor.']);
}
?>
