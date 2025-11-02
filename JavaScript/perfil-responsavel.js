document.addEventListener('DOMContentLoaded', function() {
    console.log("Script do perfil do responsável carregado com sucesso!");

    try {
        // --- 1. INICIALIZAÇÃO E VERIFICAÇÃO DE LOGIN ---
        // A variável 'usuarioLogado' é definida aqui e fica acessível para todo o código dentro do 'try'.
        const usuarioLogado = JSON.parse(sessionStorage.getItem('usuarioLogado'));

        // Se o usuário não estiver logado ou não for um responsável, interrompe a execução e redireciona.
        if (!usuarioLogado || usuarioLogado.role !== 'responsavel') {
            alert("Acesso negado. Faça login como responsável.");
            window.location.href = 'index.html';
            return;
        }

        // Preenche os dados do usuário na tela.
        document.getElementById('user-nome-display').textContent = usuarioLogado.nome;
        document.getElementById('new-name').value = usuarioLogado.nome;
        document.getElementById('new-email').value = usuarioLogado.email;

        // --- 2. LÓGICA DE NAVEGAÇÃO POR ABAS ---
        // Faz os links do menu lateral ("Gerir Crianças", "Segurança") funcionarem.
        document.querySelectorAll('.list-group-item[data-section]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector('.list-group-item.active').classList.remove('active');
                this.classList.add('active');
                document.querySelectorAll('.profile-section').forEach(sec => sec.classList.remove('active'));
                document.getElementById(this.getAttribute('data-section')).classList.add('active');
            });
        });

        // --- 3. LÓGICA DE GESTÃO DE CRIANÇAS ---
        const listaCriancasElement = document.getElementById('lista-criancas');
        const criancaForm = document.getElementById('criancaForm');
        
        async function carregarCriancas() {
            listaCriancasElement.innerHTML = '<div class="list-group-item">A carregar...</div>';
            try {
                const response = await fetch(`api/get_children.php?usuario_id=${usuarioLogado.id}`);
                const data = await response.json();
                
                listaCriancasElement.innerHTML = '';
                
                if (data.success && data.children.length > 0) {
                    data.children.forEach(crianca => {
                        const item = document.createElement('div');
                        item.className = 'list-group-item d-flex justify-content-between align-items-center';
                        item.innerHTML = `
                            <div><strong>${crianca.nome}</strong><br><small class="text-muted">Escola: ${crianca.escola || 'Não informada'}</small></div>
                            <button class="btn btn-sm btn-outline-danger" onclick="excluirCrianca(${crianca.id})"><i class="fas fa-trash-alt"></i></button>
                        `;
                        listaCriancasElement.appendChild(item);
                    });
                } else {
                    listaCriancasElement.innerHTML = `<div class="list-group-item text-muted">${data.msg || 'Nenhuma criança registada.'}</div>`;
                }
            } catch (error) {
                console.error("Erro ao carregar crianças:", error);
                listaCriancasElement.innerHTML = `<div class="list-group-item text-danger">Falha ao carregar a lista de crianças.</div>`;
            }
        }

        window.excluirCrianca = async function(childId) {
            if (confirm('Tem a certeza que deseja apagar esta criança?')) {
                const formData = new FormData();
                formData.append('id', childId);
                const response = await fetch('api/delete_child.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.msg);
                if (result.success) carregarCriancas();
            }
        };
        
        const selectDia = document.getElementById('dia-crianca');
        const selectMes = document.getElementById('mes-crianca');
        const selectAno = document.getElementById('ano-crianca');
        for (let i = 1; i <= 31; i++) selectDia.add(new Option(i, i));
        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        meses.forEach((mes, index) => selectMes.add(new Option(mes, index + 1)));
        const anoAtual = new Date().getFullYear();
        for (let i = anoAtual; i >= anoAtual - 18; i--) selectAno.add(new Option(i, i));

        if (criancaForm) {
            criancaForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const dataNascimento = `${selectAno.value}-${String(selectMes.value).padStart(2, '0')}-${String(selectDia.value).padStart(2, '0')}`;
                
                const formData = new FormData();
                formData.append('usuario_id', usuarioLogado.id);
                formData.append('nome', document.getElementById('nome-crianca').value);
                formData.append('data_nascimento', dataNascimento);
                formData.append('escola', document.getElementById('escola-crianca').value);
                formData.append('observacoes', document.getElementById('obs-crianca').value);

                const response = await fetch('api/register_child.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.msg);

                if (result.success) {
                    this.reset();
                    carregarCriancas();
                }
            });
        }
        
        carregarCriancas();

        // --- 4. LÓGICA DOS FORMULÁRIOS DE SEGURANÇA E EXCLUSÃO ---
        const apiUpdateUrl = 'api/update_account.php';
        const apiDeleteUrl = 'api/delete_account.php';
        
        const updateInfoForm = document.getElementById('updateInfoForm');
        if(updateInfoForm) {
            updateInfoForm.addEventListener('submit', async function(e) {
                e.preventDefault(); // Impede o recarregamento da página

                // Pega os dados do formulário
                const newName = document.getElementById('new-name').value;
                const newEmail = document.getElementById('new-email').value;
                const currentPassword = document.getElementById('info-current-password').value;

                // Validação simples
                if (!currentPassword) {
                    alert('Você precisa digitar sua senha atual para confirmar a alteração.');
                    return;
                }

                // Prepara os dados para envio
                const formData = new FormData();
                formData.append('id', usuarioLogado.id);
                formData.append('new_name', newName);
                formData.append('new_email', newEmail);
                formData.append('current_password', currentPassword);

                try {
                    // Envia os dados para o script PHP (A LÓGICA QUE FALTAVA)
                    const response = await fetch('api/update_account.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json(); // Converte a resposta do servidor para JSON

                    // Exibe a mensagem do servidor (sucesso ou erro)
                    alert(result.msg);

                    // Se a atualização foi bem-sucedida, atualiza os dados na página
                    if (result.success) {
                        if (result.updatedFields.newName) {
                            usuarioLogado.nome = result.updatedFields.newName;
                            document.getElementById('user-nome-display').textContent = usuarioLogado.nome;
                        }
                        if (result.updatedFields.newEmail) {
                            usuarioLogado.email = result.updatedFields.newEmail;
                        }
                        sessionStorage.setItem('usuarioLogado', JSON.stringify(usuarioLogado));

                        // Limpa o campo de senha atual
                        document.getElementById('info-current-password').value = ''; 
                    }
                } catch (error) {
                    console.error('Erro ao tentar atualizar as informações:', error);
                    alert('Ocorreu um erro de comunicação. Verifique o console para mais detalhes.');
                }
            });
        }
        
                const updatePasswordForm = document.getElementById('updatePasswordForm');
        if (updatePasswordForm) {
            updatePasswordForm.addEventListener('submit', async function(e) {
                e.preventDefault(); // Impede o recarregamento da página

                // Pega os dados do formulário de senha
                const currentPassword = document.getElementById('pass-current-password').value;
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-new-password').value;

                // Validações no lado do cliente
                if (newPassword.length < 8) {
                    alert('A nova senha deve ter no mínimo 8 caracteres.');
                    return;
                }
                if (newPassword !== confirmPassword) {
                    alert('As novas senhas não coincidem.');
                    return;
                }
                if (!currentPassword) {
                    alert('Você precisa digitar sua senha atual para confirmar a alteração.');
                    return;
                }

                // Prepara os dados para envio
                const formData = new FormData();
                formData.append('id', usuarioLogado.id);
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);

                try {
                    // Envia os dados para o mesmo script PHP de antes
                    const response = await fetch('api/update_account.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json(); // Converte a resposta

                    // Exibe a mensagem do servidor
                    alert(result.msg);

                    // Se a alteração foi bem-sucedida, limpa os campos do formulário de senha
                    if (result.success) {
                        this.reset(); // 'this' se refere ao próprio formulário
                    }

                } catch (error) {
                    console.error('Erro ao tentar alterar a senha:', error);
                    alert('Ocorreu um erro de comunicação. Verifique o console para mais detalhes.');
                }
            });
        }

        const deleteBtn = document.getElementById('delete-account-btn');
        if(deleteBtn) {
            deleteBtn.addEventListener('click', async function() {
                const confirmation = prompt("Atenção: Esta ação é irreversível.\nPara confirmar, digite sua senha atual:");
                if (confirmation !== null) {
                    const formData = new FormData();
                    formData.append('id', usuarioLogado.id);
                    formData.append('current_password', confirmation);
                    
                    const response = await fetch(apiDeleteUrl, { method: 'POST', body: formData });
                    const result = await response.json();
                    alert(result.msg);

                    if (result.success) {
                        sessionStorage.clear();
                        window.location.href = 'index.html';
                    }
                }
            });
        }

    } catch (error) {
        console.error("Erro crítico ao carregar o perfil do responsável:", error);
        alert("Ocorreu um erro. Por favor, recarregue a página ou faça login novamente.");
    }
});