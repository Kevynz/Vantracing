<?php
require_once 'config.php';
proteger_pagina('motorista'); // Só motoristas podem ver
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
    <title>Dashboard do Motorista</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Olá, <?php echo htmlspecialchars($usuario['nome']); ?>!</h1>
        <p>Painel do Motorista</p>

        <button id="btn-iniciar-rota" class="btn btn-lg btn-success">Iniciar Rota e Compartilhar Localização</button>
        <button id="btn-finalizar-rota" class="btn btn-lg btn-warning" style="display:none;">Finalizar Rota</button>
        <script>
            document.getElementById('btn-finalizar-rota').addEventListener('click', async () => {
            clearInterval(intervalId);
            intervalId = null;
    
            // Chama uma nova API para arquivar a rota
            await fetch('api/arquivar_rota.php', { method: 'POST' });

            document.getElementById('btn-iniciar-rota').style.display = 'block';
            document.getElementById('btn-finalizar-rota').style.display = 'none';
            alert('Rastreamento e rota finalizados!');
        });

            let intervalId = null;

            // Função para enviar localização para o servidor
            function enviarLocalizacao() {
                navigator.geolocation.getCurrentPosition(position => {
                    const { latitude, longitude } = position.coords;
                    
                    fetch('api/atualizar_localizacao.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ latitude, longitude })
                    });

                }, () => {
                    alert('Não foi possível obter a localização.');
                });
            }

            document.getElementById('btn-iniciar-rota').addEventListener('click', () => {
                enviarLocalizacao(); // Envia a primeira vez imediatamente
                // Envia a cada 10 segundos
                intervalId = setInterval(enviarLocalizacao, 10000); 
                document.getElementById('btn-iniciar-rota').style.display = 'none';
                document.getElementById('btn-finalizar-rota').style.display = 'block';
                alert('Rastreamento iniciado!');
            });

            document.getElementById('btn-finalizar-rota').addEventListener('click', () => {
                clearInterval(intervalId); // Para de enviar a localização
                intervalId = null;
                document.getElementById('btn-iniciar-rota').style.display = 'block';
                document.getElementById('btn-finalizar-rota').style.display = 'none';
                alert('Rastreamento finalizado!');
            });
        </script>
        
        <a href="logout.php" class="btn btn-danger mt-4">Sair</a>
    </div>
</body>
</html>