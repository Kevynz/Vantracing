<?php
require_once 'config.php';
require_once 'classes/Crianca.php';
proteger_pagina('responsavel');

$usuario = $_SESSION['usuario'];
$criancaManager = new Crianca($pdo);
$mensagem = '';
$sucesso = false;

// Lógica para cadastrar nova criança
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_crianca'])) {
    if (!empty($_POST['nome']) && !empty($_POST['escola'])) {
        if ($criancaManager->cadastrar($_POST['nome'], $_POST['escola'], $usuario['id'])) {
            $mensagem = 'Criança cadastrada com sucesso!';
            $sucesso = true;
        } else {
            $mensagem = 'Erro ao cadastrar criança.';
        }
    } else {
        $mensagem = 'Nome e escola são obrigatórios.';
    }
}

// Lógica para associar motorista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['associar_motorista'])) {
    if (!empty($_POST['crianca_id']) && !empty($_POST['motorista_id'])) {
        if ($criancaManager->associarMotorista($_POST['crianca_id'], $_POST['motorista_id'], $usuario['id'])) {
            $mensagem = 'Motorista associado com sucesso!';
            $sucesso = true;
        } else {
            $mensagem = 'Erro ao associar motorista.';
        }
    }
}


// Busca a lista de crianças e motoristas para exibir na página
$listaCriancas = $criancaManager->listarPorResponsavel($usuario['id']);
$listaMotoristas = $criancaManager->listarMotoristas();

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
    <title>Gerenciar Crianças - VanTracing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gerenciar Crianças</h2>
            <a href="dashboard_responsavel.php" class="btn btn-secondary">Voltar ao Dashboard</a>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $sucesso ? 'success' : 'danger'; ?>"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Cadastrar Nova Criança</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="cadastrar_crianca" value="1">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="nome" class="form-label">Nome da Criança</label>
                            <input type="text" class="form-control" name="nome" id="nome" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label for="escola" class="form-label">Escola</label>
                            <input type="text" class="form-control" name="escola" id="escola" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end mb-3">
                            <button type="submit" class="btn btn-primary w-100">Adicionar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Minhas Crianças</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($listaCriancas as $crianca): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
    <div>
        <strong><?php echo htmlspecialchars($crianca['nome']); ?></strong>
        <small class="d-block text-muted">
            Motorista Atual: 
            <span id="motorista-nome-<?php echo $crianca['id']; ?>">
                <?php echo $crianca['motorista_nome'] ? htmlspecialchars($crianca['motorista_nome']) : 'Nenhum'; ?>
            </span>
        </small>
    </div>
    <div class="d-flex association-form">
        <input type="hidden" class="crianca-id" value="<?php echo $crianca['id']; ?>">
        <select class="form-select me-2 motorista-id" style="width: 200px;">
            <option value="">Associar a...</option>
            <?php foreach ($listaMotoristas as $motorista): ?>
                <option value="<?php echo $motorista['id']; ?>"><?php echo htmlspecialchars($motorista['nome']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-outline-primary btn-sm btn-salvar-associacao">Salvar</button>
    </div>
</li>

<script>
document.querySelectorAll('.btn-salvar-associacao').forEach(button => {
    button.addEventListener('click', async function() {
        const formContainer = this.closest('.association-form');
        const criancaId = formContainer.querySelector('.crianca-id').value;
        const motoristaId = formContainer.querySelector('.motorista-id').value;

        try {
    // ... (código do fetch)
    const result = await response.json();

    if (result.status === 'sucesso') {
        document.getElementById(`motorista-nome-${criancaId}`).textContent = result.motorista_nome;
        
        // Feedback visual aprimorado
        this.classList.remove('btn-outline-primary');
        this.classList.add('btn-success');
        this.innerHTML = '<i class="fas fa-check"></i> Salvo!';
        setTimeout(() => {
            this.innerHTML = originalButtonText;
            this.classList.remove('btn-success');
            this.classList.add('btn-outline-primary');
        }, 2500);

    } else {
        // ... (lógica de erro)
    }
        
        if (!motoristaId) {
            alert('Por favor, selecione um motorista.');
            return;
        }

        const originalButtonText = this.innerHTML;
        this.innerHTML = 'Salvando...';
        this.disabled = true;

        try {
            const response = await fetch('api/associar_motorista.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    crianca_id: criancaId,
                    motorista_id: motoristaId
                })
            });

            const result = await response.json();

            if (result.status === 'sucesso') {
                // Atualiza o nome do motorista na tela, sem recarregar!
                document.getElementById(`motorista-nome-${criancaId}`).textContent = result.motorista_nome;
                
                // Feedback visual de sucesso
                this.innerHTML = 'Salvo!';
                setTimeout(() => { this.innerHTML = originalButtonText; }, 2000);

            } else {
                alert('Erro: ' + result.mensagem);
                this.innerHTML = originalButtonText;
            }
        } catch (error) {
            alert('Ocorreu um erro de conexão.');
            this.innerHTML = originalButtonText;
        } finally {
            this.disabled = false;
        }
    });
});
</script>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>