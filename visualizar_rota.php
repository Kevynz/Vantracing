<?php
require_once 'config.php';
require_once 'classes/Rota.php';
proteger_pagina('responsavel'); // Apenas responsáveis podem ver

$rota_id = $_GET['id'] ?? 0;
if (!$rota_id) {
    die("ID da rota não fornecido.");
}

$rotaManager = new Rota($pdo);
$rota = $rotaManager->obterHistoricoPorId($rota_id, $_SESSION['usuario']['id']);

if (!$rota) {
    die("Rota não encontrada ou acesso negado.");
}

// O trajeto está salvo como JSON, pronto para ser usado pelo JavaScript
$trajetoJson = $rota['trajeto'];
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
    <meta charset="UTF-8">
    <title>Visualizar Rota Histórica - VanTracing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>#mapa-historico { height: 600px; width: 100%; }</style>
</head>
<body>
    <div class="container mt-4">
        <h3>Histórico da Rota - <?php echo date("d/m/Y", strtotime($rota['data_rota'])); ?></h3>
        <p>Trajeto realizado entre <?php echo $rota['hora_inicio']; ?> e <?php echo $rota['hora_fim']; ?>.</p>
        <div id="mapa-historico" class="border rounded"></div>
        <a href="historico-rotas.php" class="btn btn-secondary mt-3">Voltar ao Histórico</a>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Pega os dados do trajeto passados pelo PHP
            const trajeto = <?php echo $trajetoJson; ?>;
            
            if (!trajeto || trajeto.length === 0) {
                document.getElementById('mapa-historico').innerHTML = '<p class="text-center p-5">Não há dados de localização para esta rota.</p>';
                return;
            }

            // Cria um array de coordenadas [latitude, longitude]
            const coordenadas = trajeto.map(ponto => [ponto.latitude, ponto.longitude]);

            // Inicializa o mapa
            const mapa = L.map('mapa-historico');

            // Adiciona a camada de mapa (tiles) do OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(mapa);
            
            // Cria a linha (polyline) com as coordenadas do trajeto
            const polyline = L.polyline(coordenadas, {color: 'blue'}).addTo(mapa);
            
            // Adiciona marcadores de início e fim
            L.marker(coordenadas[0]).addTo(mapa).bindPopup('Início da Rota');
            L.marker(coordenadas[coordenadas.length - 1]).addTo(mapa).bindPopup('Fim da Rota');

            // Ajusta o zoom e a centralização do mapa para mostrar toda a rota
            mapa.fitBounds(polyline.getBounds());
        });
    </script>
</body>
</html>