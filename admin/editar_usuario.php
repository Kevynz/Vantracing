<?php
// admin/editar_usuario.php
require_once '../config.php';
require_once '../classes/GerenciadorUsuarios.php';
proteger_pagina('admin');

$gerenciador = new GerenciadorUsuarios($pdo);
$idUsuario = $_GET['id'] ?? null;
$mensagem = '';
$sucesso = false;

if (!$idUsuario) {
    header("Location: gerenciar_usuarios.php");
    exit();
}

// Lógica de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($gerenciador->atualizarUsuarioAdmin($idUsuario, $_POST['nome'], $_POST['email'], $_POST['role'])) {
        $mensagem = "Usuário atualizado com sucesso!";
        $sucesso = true;
    } else {
        $mensagem = "Erro ao atualizar o usuário.";
    }
}

$usuario = $gerenciador->obterPorId($idUsuario);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Editar Usuário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">...</nav>
<div class="container mt-4">
    <h2>Editar Usuário: <?php echo htmlspecialchars($usuario['nome']); ?></h2>
    
    <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $sucesso ? 'success' : 'danger'; ?>"><?php echo $mensagem; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome</label>
            <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>">
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Perfil (Role)</label>
            <select name="role" class="form-select">
                <option value="responsavel" <?php echo $usuario['role'] == 'responsavel' ? 'selected' : ''; ?>>Responsável</option>
                <option value="motorista" <?php echo $usuario['role'] == 'motorista' ? 'selected' : ''; ?>>Motorista</option>
                <option value="admin" <?php echo $usuario['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="gerenciar_usuarios.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>