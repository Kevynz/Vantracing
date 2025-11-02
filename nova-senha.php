<?php
require_once 'config.php';
require_once 'classes/GerenciadorUsuarios.php';
$mensagem = '';
$sucesso = false;
$tokenValido = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    // Apenas para exibir o formulário, a validação real acontece no POST
    if (!empty($token)) {
        $tokenValido = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenPost = $_POST['token'];
    $novaSenha = $_POST['nova_senha'];
    $confirmaSenha = $_POST['confirma_senha'];

    if ($novaSenha !== $confirmaSenha) {
        $mensagem = 'As senhas não coincidem.';
        $tokenValido = true;
    } else {
        $gerenciador = new GerenciadorUsuarios($pdo);
        if ($gerenciador->atualizarSenhaPorToken($tokenPost, $novaSenha)) {
            $mensagem = 'Senha atualizada com sucesso! Você já pode fazer login com sua nova senha.';
            $sucesso = true;
            $tokenValido = false; // Oculta o formulário após o sucesso
        } else {
            $mensagem = 'Token inválido, expirado ou erro ao atualizar. Por favor, solicite um novo link.';
            $tokenValido = false;
        }
    }
}
?>

<?php
// Exemplo em dashboard.php
$tituloPagina = 'Dashboard'; // Define um título específico para a página
require_once 'includes/header.php';
proteger_pagina();
$usuario = $_SESSION['usuario'];
?>

<h2>Dashboard de <?php echo htmlspecialchars($usuario['nome']); ?></h2>
<?php
require_once 'includes/footer.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <title>Definir Nova Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5 pt-5" style="max-width: 500px;">
    <div class="card shadow">
        <div class="card-body text-center p-5">
            <h2 class="mb-4">Definir Nova Senha</h2>
            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $sucesso ? 'success' : 'danger'; ?>"><?php echo $mensagem; ?></div>
            <?php endif; ?>

            <?php if ($tokenValido): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="mb-3">
                    <label class="form-label">Nova Senha</label>
                    <input type="password" class="form-control" name="nova_senha" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar Nova Senha</label>
                    <input type="password" class="form-control" name="confirma_senha" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Salvar Nova Senha</button>
            </form>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <a href="login.php" class="btn btn-success">Ir para Login</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>