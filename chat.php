<?php
require_once 'config.php';
proteger_pagina();

$usuario = $_SESSION['usuario'];
$outro_usuario_id = null;
$outro_usuario_nome = "Chat";

// Lógica para determinar com quem o usuário está falando
// Ex: Se for responsável, pegar o motorista da primeira criança
if ($usuario['role'] === 'responsavel') {
    $stmt = $pdo->prepare("SELECT u.id, u.nome FROM usuarios u JOIN criancas c ON u.id = c.motorista_id WHERE c.responsavel_id = ? LIMIT 1");
    $stmt->execute([$usuario['id']]);
    $motorista = $stmt->fetch();
    if($motorista) {
        $outro_usuario_id = $motorista['id'];
        $outro_usuario_nome = $motorista['nome'];
    }
}
// Adicionar lógica para motorista falar com responsável
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
    <title>Chat - VanTracing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #chat-box {
            height: 400px;
            overflow-y: scroll;
            border: 1px solid #ccc;
            padding: 10px;
        }
        .msg-enviada { text-align: right; margin-left: 50px; background-color: #dcf8c6; }
        .msg-recebida { text-align: left; margin-right: 50px; background-color: #fff; }
        .msg { padding: 8px; border-radius: 8px; margin-bottom: 5px; max-width: 80%; display: inline-block;}
    </style>
</head>
<body>
    <div class="container mt-4">
        <h3>Chat com <?php echo htmlspecialchars($outro_usuario_nome); ?></h3>

        <?php if ($outro_usuario_id): ?>
            <div id="chat-box" class="mb-3">
                </div>
            <form id="form-mensagem">
                <div class="input-group">
                    <input type="text" id="input-mensagem" class="form-control" placeholder="Digite sua mensagem..." autocomplete="off">
                    <button class="btn btn-primary" type="submit">Enviar</button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">Nenhum motorista associado para iniciar o chat.</div>
        <?php endif; ?>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        const formMensagem = document.getElementById('form-mensagem');
        const inputMensagem = document.getElementById('input-mensagem');
        const outroUsuarioId = <?php echo json_encode($outro_usuario_id); ?>;
        const usuarioLogadoId = <?php echo json_encode($usuario['id']); ?>;

        async function carregarMensagens() {
            if (!outroUsuarioId) return;

            const response = await fetch(`api/obter_mensagens.php?com_usuario_id=${outroUsuarioId}`);
            const mensagens = await response.json();
            
            chatBox.innerHTML = ''; // Limpa o chat
            for (const msg of mensagens) {
                const div = document.createElement('div');
                const p = document.createElement('p');
                p.textContent = msg.mensagem;
                p.className = 'msg';
                div.className = msg.remetente_id == usuarioLogadoId ? 'msg-enviada' : 'msg-recebida';
                
                div.appendChild(p);
                chatBox.appendChild(div);
            }
            chatBox.scrollTop = chatBox.scrollHeight; // Rola para o final
        }

        formMensagem.addEventListener('submit', async (e) => {
            e.preventDefault();
            const mensagem = inputMensagem.value;
            if (mensagem.trim() === '' || !outroUsuarioId) return;

            await fetch('api/enviar_mensagem.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ destinatario_id: outroUsuarioId, mensagem: mensagem })
            });

            inputMensagem.value = '';
            carregarMensagens();
        });

        // Carrega mensagens a cada 3 segundos
        setInterval(carregarMensagens, 3000);
        carregarMensagens(); // Carga inicial
    </script>
</body>
</html>