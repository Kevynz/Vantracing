<?php
require_once 'config.php';
require_once 'classes/GerenciadorUsuarios.php';
proteger_pagina();

$gerenciador = new GerenciadorUsuarios($pdo);
$usuario = $_SESSION['usuario']; // Dados da sessão
$mensagem = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica o token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erro de validação CSRF. Ação bloqueada.');
    }
    // Remove o token da sessão para que ele seja regenerado
    unset($_SESSION['csrf_token']);

    $action = $_POST['action'] ?? '';

// Busca os dados mais recentes do usuário no banco para preencher o formulário
$dadosAtuais = $gerenciador->obterPorId($usuario['id']);
} 
else {
    // Se não for uma requisição POST, apenas recarrega os dados atuais do usuário
    $dadosAtuais = $gerenciador->obterPorId($usuario['id']);
}

// Lógica para processar os formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        // Caso 1: Atualizar informações do perfil
        case 'atualizar_perfil':
            $dados = [
                'nome' => $_POST['nome'],
                'profissao' => $_POST['profissao'],
                'bio' => $_POST['bio'],
                'site' => $_POST['site']
            ];
            if ($gerenciador->atualizarPerfil($usuario['id'], $dados)) {
                $mensagem = 'Perfil atualizado com sucesso!';
                $sucesso = true;
                // Atualiza os dados na sessão
                $_SESSION['usuario']['nome'] = $dados['nome'];
            } else {
                $mensagem = 'Erro ao atualizar o perfil.';
            }
            break;

        // Caso 2: Atualizar a senha
        case 'atualizar_senha':
            if ($_POST['nova_senha'] !== $_POST['confirma_senha']) {
                $mensagem = 'As novas senhas não coincidem.';
            } elseif ($gerenciador->atualizarSenha($usuario['id'], $_POST['senha_atual'], $_POST['nova_senha'])) {
                $mensagem = 'Senha alterada com sucesso!';
                $sucesso = true;
            } else {
                $mensagem = 'Senha atual incorreta ou erro ao atualizar.';
            }
            break;

        // Caso 3: Atualizar a foto de perfil
        case 'atualizar_foto':
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
                $arquivo = $_FILES['foto_perfil'];
                $pastaUpload = 'uploads/fotos_perfil/';
                // Gera um nome de arquivo único para evitar conflitos
                $nomeArquivo = uniqid() . '-' . basename($arquivo['name']);
                $caminhoCompleto = $pastaUpload . $nomeArquivo;
                
                // Validação básica de tipo e tamanho
                $tipoPermitido = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($arquivo['type'], $tipoPermitido) && $arquivo['size'] < 2 * 1024 * 1024) { // Limite de 2MB
                    if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                        if ($gerenciador->atualizarFoto($usuario['id'], $caminhoCompleto)) {
                            $mensagem = 'Foto atualizada com sucesso!';
                            $sucesso = true;
                            $_SESSION['usuario']['foto_perfil'] = $caminhoCompleto; // Atualiza na sessão
                        } else {
                            $mensagem = 'Erro ao salvar o caminho da foto no banco de dados.';
                        }
                    } else {
                        $mensagem = 'Erro ao mover o arquivo enviado.';
                    }
                } else {
                    $mensagem = 'Arquivo inválido! Apenas imagens (JPG, PNG, GIF) de até 2MB são permitidas.';
                }
            } else {
                $mensagem = 'Nenhum arquivo enviado ou ocorreu um erro no upload.';
            }
            break;
    }
    // Recarrega os dados após a atualização
    $dadosAtuais = $gerenciador->obterPorId($usuario['id']);
    // Gera um novo token para o próximo carregamento de formulário
    gerar_csrf_token();
} else {
    // Se não for POST, apenas recarrega os dados atuais do usuário
    $dadosAtuais = $gerenciador->obterPorId($usuario['id']);
    // Gera um novo token para o próximo carregamento de formulário
    gerar_csrf_token();
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil - VanTracing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="css/estilo.css">
    <style> .profile-section { display: none; } .profile-section.active { display: block; } </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        </nav>

    <div class="container mt-5 pt-5">
        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $sucesso ? 'success' : 'danger'; ?>"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="<?php echo htmlspecialchars($dadosAtuais['foto_perfil']); ?>" class="rounded-circle mb-3" width="150" alt="Foto">
                        <h4><?php echo htmlspecialchars($dadosAtuais['nome']); ?></h4>
                    </div>
                </div>
                <div class="list-group mb-4">
                    <a href="#" data-section="perfil-info" class="list-group-item list-group-item-action active">Perfil</a>
                    <a href="#" data-section="perfil-seguranca" class="list-group-item list-group-item-action">Segurança da conta</a>
                </div>
            </div>
            <div class="col-md-9">
                <div id="perfil-info" class="profile-section active">
                    <div class="card">
                        <div class="card-header"><h4>Perfil Público</h4></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="atualizar_perfil">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome</label>
                                    <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($dadosAtuais['nome']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="profissao" class="form-label">Profissão ou Título</label>
                                    <input type="text" class="form-control" name="profissao" value="<?php echo htmlspecialchars($dadosAtuais['profissao'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea class="form-control" name="bio" rows="3"><?php echo htmlspecialchars($dadosAtuais['bio'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="site" class="form-label">Links</label>
                                    <input type="url" class="form-control" name="site" value="<?php echo htmlspecialchars($dadosAtuais['site'] ?? ''); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Salvar Perfil</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="perfil-seguranca" class="profile-section">
                    <div class="card">
                        <div class="card-header"><h4>Segurança da Conta</h4></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="atualizar_senha">
                                <div class="mb-3">
                                    <label class="form-label">E-mail Atual</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($dadosAtuais['email']); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Senha Atual</label>
                                    <input type="password" class="form-control" name="senha_atual" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control" name="nova_senha" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" name="confirma_senha" required>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="action" value="atualizar_senha">
                                    <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                                    <button type="submit" class="btn btn-primary">Atualizar Senha</button>
                                </form>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="perfil-foto" class="profile-section">
    <div class="card">
        <div class="card-header"><h4>Foto do Perfil</h4></div>
            <div class="card-body text-center">
                <p>Atualize sua foto de perfil. A imagem será redimensionada.</p>
            
                <div class="photo-preview mb-3">
                    <img src="<?php echo htmlspecialchars($dadosAtuais['foto_perfil']); ?>" id="photo-preview-img" class="img-thumbnail" alt="Prévia da Foto" style="max-width: 200px;">
                </div>
            
                    <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="atualizar_foto">
                    <div class="mb-3">
                    <input type="file" class="form-control" name="foto_perfil" id="foto-upload" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar Foto</button>
                    </form>
                    </div>
                </div>
                    </div>

                        <div class="list-group mb-4">
                        <a href="#" data-section="perfil-info" class="list-group-item list-group-item-action active">Perfil</a>
                        <a href="#" data-section="perfil-foto" class="list-group-item list-group-item-action">Foto</a> <a href="#" data-section="perfil-seguranca" class="list-group-item list-group-item-action">Segurança da conta</a>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Script para alternar entre as seções do perfil
        document.querySelectorAll('.list-group-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const sectionId = this.getAttribute('data-section');
                
                document.querySelectorAll('.list-group-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                document.querySelectorAll('.profile-section').forEach(section => section.classList.remove('active'));
                document.getElementById(sectionId).classList.add('active');
            });
        });
         const fotoUploadInput = document.getElementById('foto-upload');
    const photoPreviewImg = document.getElementById('photo-preview-img');

    if (fotoUploadInput) {
        fotoUploadInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreviewImg.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }
    </script>
</body>
</html>