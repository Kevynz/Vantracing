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

<head>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style> #mapa { height: 500px; } </style>
</head>
<body>
    <div class="container">
        <h2>Rastreamento em Tempo Real</h2>
        <div id="mapa"></div>
    </div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        // Inicializa o mapa
        const mapa = L.map('mapa').setView([-23.5505, -46.6333], 13); // Ponto inicial (SP)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapa);

        let vanMarker = null;

        async function atualizarMapa() {
            const response = await fetch('api/obter_localizacao.php');
            const data = await response.json();

            if (data && !data.error) {
                const latLng = [data.latitude, data.longitude];

                if (!vanMarker) {
                    // Cria o marcador da van se ele não existir
                    vanMarker = L.marker(latLng).addTo(mapa)
                        .bindPopup('A van está aqui!').openPopup();
                } else {
                    // Apenas move o marcador existente
                    vanMarker.setLatLng(latLng);
                }
                mapa.setView(latLng, 15); // Centraliza o mapa na van
            }
        }

        // Atualiza o mapa a cada 5 segundos
        setInterval(atualizarMapa, 5000);
        atualizarMapa(); // Chama a primeira vez
    </script>
</body>