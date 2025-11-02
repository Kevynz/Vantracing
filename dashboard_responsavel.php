<?php
require_once 'config.php';
proteger_pagina('responsavel'); // Só responsáveis podem ver esta página
$usuario = $_SESSION['usuario'];
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
    <title>Dashboard do Responsável</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Bem-vindo(a), <?php echo htmlspecialchars($usuario['nome']); ?>!</h1>
        <p>Painel do Responsável</p>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Rastrear Van</h5>
                        <p class="card-text">Veja a localização em tempo real.</p>
                        <a href="rastreamento.php" class="btn btn-primary">Ver Mapa</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Gerenciar Crianças</h5>
                        <p class="card-text">Cadastre ou edite os dados dos seus filhos.</p>
                        <a href="gerenciar_criancas.php" class="btn btn-info">Gerenciar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Chat com Motorista</h5>
                        <p class="card-text">Envie uma mensagem rápida.</p>
                        <a href="chat.php" class="btn btn-success">Abrir Chat</a>
                    </div>
                </div>
            </div>
        </div>
         <a href="logout.php" class="btn btn-danger mt-4">Sair</a>
    </div>
</body>
</html>