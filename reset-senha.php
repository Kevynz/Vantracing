<?php
require_once 'config.php';
require_once 'classes/GerenciadorUsuarios.php';
$mensagem = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $gerenciador = new GerenciadorUsuarios($pdo);
    $token = $gerenciador->gerarTokenReset($email);

    if ($token) {
        $linkReset = "http://localhost/VanTracing/nova-senha.php?token=" . $token;
        
        // --- SIMULAÇÃO DE ENVIO DE E-MAIL ---
        // Em um projeto real, você usaria uma biblioteca como PHPMailer.
        $corpoEmail = "Olá! Para resetar sua senha, clique no link a seguir: <br>";
        $corpoEmail .= "<a href='$linkReset'>$linkReset</a>";
        
        $mensagem = "<strong>E-mail de recuperação (simulação):</strong><br>Se o e-mail estiver correto, um link de recuperação foi 'enviado'. <br> Por favor, clique neste link para prosseguir: <a href='$linkReset'>Resetar Senha</a>";
        $sucesso = true;
        // Fim da simulação
    } else {
        $mensagem = "Se o e-mail estiver em nosso sistema, um link de recuperação foi enviado.";
        $sucesso = true; // Mostramos uma mensagem genérica por segurança
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
    <title>Resetar Senha - VanTracing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5 pt-5" style="max-width: 500px;">
    <div class="card shadow">
        <div class="card-body text-center p-5">
            <h2 class="mb-4">Resetar Senha</h2>
            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $sucesso ? 'info' : 'danger'; ?>"><?php echo $mensagem; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="reset-email" class="form-label">Digite seu e-mail</label>
                    <input type="email" class="form-control" name="email" id="reset-email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Enviar Link de Recuperação</button>
                <p class="mt-3"><a href="login.php">Voltar para o Login</a></p>
            </form>
        </div>
    </div>
</div>
</body>
</html>