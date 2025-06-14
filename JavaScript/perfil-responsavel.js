document.addEventListener('DOMContentLoaded', function() {
    try {
        const usuarioLogado = JSON.parse(sessionStorage.getItem('usuarioLogado'));
        if (!usuarioLogado) { throw new Error("Utilizador não autenticado."); }

        // Preenche os dados iniciais do utilizador
        document.getElementById('user-nome-display').textContent = usuarioLogado.nome;
        if(document.getElementById('new-name')) document.getElementById('new-name').value = usuarioLogado.nome;
        if(document.getElementById('new-email')) document.getElementById('new-email').value = usuarioLogado.email;

        // --- LÓGICA DE NAVEGAÇÃO POR ABAS ---
        document.querySelectorAll('.list-group-item[data-section]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector('.list-group-item.active').classList.remove('active');
                this.classList.add('active');
                document.querySelectorAll('.profile-section').forEach(sec => sec.classList.remove('active'));
                document.getElementById(this.getAttribute('data-section')).classList.add('active');
            });
        });

        // --- LÓGICA DE GESTÃO DE CRIANÇAS ---
        const listaCriancasElement = document.getElementById('lista-criancas');
        
        async function carregarCriancas() {
            listaCriancasElement.innerHTML = '<div class="list-group-item">A carregar...</div>';
            try {
                const response = await fetch(`/Vantracing/api/get_children.php?usuario_id=${usuarioLogado.id}`);
                if (!response.ok) {
                    throw new Error(`Erro na rede: ${response.status} - ${response.statusText}`);
                }
                const data = await response.json();
                listaCriancasElement.innerHTML = '';
                if (data.success && data.children.length > 0) {
                    data.children.forEach(crianca => {
                        const item = document.createElement('div');
                        item.className = 'list-group-item d-flex justify-content-between align-items-center';
                        item.innerHTML = `<span><strong>${crianca.nome}</strong></span><button class="btn btn-sm btn-outline-danger" onclick="window.excluirCrianca(${crianca.id})"><i class="fas fa-trash-alt"></i></button>`;
                        listaCriancasElement.appendChild(item);
                    });
                } else {
                    listaCriancasElement.innerHTML = `<div class="list-group-item text-muted">${data.msg || 'Nenhuma criança registada.'}</div>`;
                }
            } catch (error) {
                console.error("Erro detalhado ao carregar crianças:", error);
                listaCriancasElement.innerHTML = `<div class="list-group-item text-danger">
                    <strong>Falha ao carregar a lista de crianças.</strong><br>
                    <small>Erro técnico: ${error.message}</small>
                </div>`;
            }
        }

        window.excluirCrianca = async function(childId) {
            if (confirm('Tem a certeza que deseja apagar esta criança?')) {
                const formData = new FormData();
                formData.append('id', childId);
                const response = await fetch('/Vantracing/api/delete_child.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.msg);
                if (result.success) carregarCriancas();
            }
        };
        
        // --- LÓGICA DO CALENDÁRIO ---
        const selectDia = document.getElementById('dia-crianca');
        const selectMes = document.getElementById('mes-crianca');
        const selectAno = document.getElementById('ano-crianca');
        for (let i = 1; i <= 31; i++) selectDia.add(new Option(i, i));
        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        meses.forEach((mes, index) => selectMes.add(new Option(mes, index + 1)));
        const anoAtual = new Date().getFullYear();
        for (let i = anoAtual; i >= anoAtual - 18; i--) selectAno.add(new Option(i, i));

        // --- FORMULÁRIOS ---
        document.getElementById('criancaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const dataNascimento = `${selectAno.value}-${String(selectMes.value).padStart(2, '0')}-${String(selectDia.value).padStart(2, '0')}`;
            const formData = new FormData();
            formData.append('usuario_id', usuarioLogado.id);
            formData.append('nome', document.getElementById('nome-crianca').value);
            formData.append('data_nascimento', dataNascimento);
            formData.append('escola', document.getElementById('escola-crianca').value);
            formData.append('observacoes', document.getElementById('obs-crianca').value);
            const response = await fetch('/Vantracing/api/register_child.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.msg);
            if (result.success) {
                this.reset();
                carregarCriancas();
            }
        });

        document.getElementById('updateInfoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            // Lógica para atualizar info aqui...
        });

        document.getElementById('updatePasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            // Lógica para atualizar senha aqui...
        });

        document.getElementById('delete-account-btn').addEventListener('click', async function() {
            // Lógica para apagar conta aqui...
        });
        
        // --- CARREGAMENTO INICIAL ---
        carregarCriancas(); 

    } catch (error) {
        console.error("Ocorreu um erro crítico ao carregar o perfil:", error.message);
        alert("Ocorreu um erro ao carregar o seu perfil. Por favor, faça login novamente.");
        window.location.href = '/Vantracing/index.html';
    }
});