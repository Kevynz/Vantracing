<?php
// admin/gerenciar_usuarios.php
require_once '../config.php';
require_once '../classes/GerenciadorUsuarios.php';
proteger_pagina('admin');

$gerenciador = new GerenciadorUsuarios($pdo);

// Lógica para deletar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $gerenciador->deletarUsuario($_POST['delete_id']);
    // Redireciona para evitar reenvio do formulário
    header("Location: gerenciar_usuarios.php");
    exit();
}

$usuarios = $gerenciador->listarTodosUsuarios();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gerenciar Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">...</nav>

<div class="container mt-4">
    <h2>Gerenciar Usuários</h2>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Perfil (Role)</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?php echo $usuario['id']; ?></td>
                <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                <td><span class="badge bg-secondary"><?php echo ucfirst($usuario['role']); ?></span></td>
                <td>
                    <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja deletar este usuário?');">
                        <input type="hidden" name="delete_id" value="<?php echo $usuario['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Deletar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>