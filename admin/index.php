<?php
// admin/index.php
require_once '../config.php';
proteger_pagina('admin'); // Apenas admins aqui!

// Lógica para buscar estatísticas
$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalMotoristas = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE role = 'motorista'")->fetchColumn();
$totalCriancas = $pdo->query("SELECT COUNT(*) FROM criancas")->fetchColumn();

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
    <title>Painel Administrativo - VanTracing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Admin VanTracing</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="gerenciar_usuarios.php">Usuários</a></li>
            </ul>
            <a href="../logout.php" class="btn btn-outline-light">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Dashboard Administrativo</h2>
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Total de Usuários</div>
                <div class="card-body">
                    <h5 class="card-title display-4"><?php echo $totalUsuarios; ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Total de Motoristas</div>
                <div class="card-body">
                    <h5 class="card-title display-4"><?php echo $totalMotoristas; ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-header">Total de Crianças</div>
                <div class="card-body">
                    <h5 class="card-title display-4"><?php echo $totalCriancas; ?></h5>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>