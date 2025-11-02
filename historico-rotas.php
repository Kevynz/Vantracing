<?php
require_once 'config.php';
require_once 'classes/Rota.php';
proteger_pagina('responsavel');

$rotaManager = new Rota($pdo);
// Lógica para pegar o motorista associado à criança do responsável
$motorista_id = 1; // Substituir pela lógica real
$historico = $rotaManager->listarHistorico($motorista_id);
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
    <title>Histórico de Rotas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Histórico de Rotas</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historico as $rota): ?>
                <tr>
                    <td><?php echo date("d/m/Y", strtotime($rota['data_rota'])); ?></td>
                    <td><?php echo $rota['hora_inicio']; ?></td>
                    <td><?php echo $rota['hora_fim']; ?></td>
                    <td><a href="#" class="btn btn-sm btn-info">Ver no Mapa</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($historico)): ?>
                    <tr><td colspan="4">Nenhum histórico encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
            <td>
                <a href="visualizar_rota.php?id=<?php echo $rota['id']; ?>" class="btn btn-sm btn-info">
                Ver no Mapa
                </a>
            </td>
        </table>
    </div>
</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>  