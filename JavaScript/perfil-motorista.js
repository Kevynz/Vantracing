document.addEventListener('DOMContentLoaded', async function() {
    console.log("Script do perfil do motorista carregado com sucesso!");

    try {
        // Allow debug mode for local testing when no session exists
        // Permite modo de depuração para testes locais quando não há sessão
        const urlParams = new URLSearchParams(window.location.search);
        const isDebug = urlParams.get('debug') === '1' || urlParams.get('mock') === '1';

        let usuarioLogado = null;
        try {
            usuarioLogado = JSON.parse(sessionStorage.getItem('usuarioLogado'));
        } catch (_) { /* ignore */ }

        if (!usuarioLogado && isDebug && location.hostname === 'localhost') {
            // Minimal mock user for local debug only
            // Usuário simulado mínimo apenas para depuração local
            usuarioLogado = {
                id: 1,
                nome: 'Motorista Demo',
                email: 'motorista@demo.local',
                role: 'motorista',
                profile: { cpf: '12345678901', cnh: '12345678901' }
            };
            sessionStorage.setItem('usuarioLogado', JSON.stringify(usuarioLogado));
            console.info('Debug mode: using mocked usuarioLogado for local testing.');
        }

        if (!usuarioLogado || usuarioLogado.role !== 'motorista') {
            alert("Acesso negado. Faça login como motorista.");
            window.location.href = 'index.html';
            return;
        }

        // --- PREENCHE OS DADOS INICIAIS ---
        document.getElementById('user-nome-display').textContent = usuarioLogado.nome;
        document.getElementById('new-name').value = usuarioLogado.nome;
        document.getElementById('new-email').value = usuarioLogado.email;
        
        function maskDocument(number) {
            if (!number || typeof number !== 'string') return '';
            const cleanNumber = number.replace(/\D/g, '');
            if (cleanNumber.length <= 6) return cleanNumber; 
            const start = cleanNumber.substring(0, 3);
            const end = cleanNumber.substring(cleanNumber.length - 3);
            return `${start}...${end}`;
        }
        
        if (usuarioLogado.profile) {
            const cpfInput = document.getElementById('motorista-cpf');
            const cnhInput = document.getElementById('motorista-cnh');
            if (cpfInput && usuarioLogado.profile.cpf) {
                cpfInput.value = maskDocument(usuarioLogado.profile.cpf);
            }
            if (cnhInput && usuarioLogado.profile.cnh) {
                cnhInput.value = maskDocument(usuarioLogado.profile.cnh);
            }
        }

        document.querySelectorAll('.list-group-item[data-section]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector('.list-group-item.active').classList.remove('active');
                this.classList.add('active');
                document.querySelectorAll('.profile-section').forEach(sec => sec.classList.remove('active'));
                document.getElementById(this.getAttribute('data-section')).classList.add('active');
            });
        });
        
        const apiUpdateUrl = 'api/update_account.php';
        const apiDeleteUrl = 'api/delete_account.php';
    let csrfToken = null;

        // Fetch CSRF token if available (session-based backends)
        // Busca token CSRF se disponível (backends com sessão)
        try {
            const r = await fetch('api/csrf.php', { cache: 'no-store', credentials: 'include' });
            if (r.ok) {
                const j = await r.json();
                if (j && j.success && j.csrf_token) csrfToken = j.csrf_token;
            }
        } catch(_) { /* ignore */ }

        // Simple Bootstrap alert helper / Helper simples de alertas Bootstrap
        function showAlert(type, message) {
            let container = document.getElementById('alert-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'alert-container';
                container.style.position = 'sticky';
                container.style.top = '4rem';
                container.style.zIndex = '1030';
                document.querySelector('.container')?.prepend(container);
            }
            const div = document.createElement('div');
            div.className = `alert alert-${type} alert-dismissible fade show`;
            div.setAttribute('role', 'alert');
            div.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
            container.appendChild(div);
            setTimeout(()=>{ div.classList.remove('show'); div.remove(); }, 5000);
        }

        // Event listener para o formulário de Alterar Informações
        const updateInfoForm = document.getElementById('updateInfoForm');
        if (updateInfoForm) {
            updateInfoForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const newName = document.getElementById('new-name').value;
                const newEmail = document.getElementById('new-email').value;
                const currentPassword = document.getElementById('info-current-password').value;

                if (!currentPassword) {
                    return showAlert('warning','Você precisa digitar sua senha atual para confirmar a alteração.');
                }

                const formData = new FormData();
                formData.append('id', usuarioLogado.id);
                formData.append('new_name', newName);
                formData.append('new_email', newEmail);
                formData.append('current_password', currentPassword);
                if (csrfToken) formData.append('csrf_token', csrfToken);

                const response = await fetch(apiUpdateUrl, { method: 'POST', body: formData, credentials: 'include' });
                const result = await response.json();
                showAlert(result.success ? 'success' : 'danger', result.msg);

                if (result.success) {
                    if (result.updatedFields.newName) usuarioLogado.nome = result.updatedFields.newName;
                    if (result.updatedFields.newEmail) usuarioLogado.email = result.updatedFields.newEmail;
                    sessionStorage.setItem('usuarioLogado', JSON.stringify(usuarioLogado));
                    document.getElementById('user-nome-display').textContent = usuarioLogado.nome;
                    document.getElementById('info-current-password').value = '';
                }
            });
        }
        
        // Event listener para o formulário de Alterar Senha
        const updatePasswordForm = document.getElementById('updatePasswordForm');
        if (updatePasswordForm) {
             updatePasswordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-new-password').value;
                const currentPassword = document.getElementById('pass-current-password').value;

                if (newPassword !== confirmPassword) return showAlert('warning','As novas senhas não coincidem.');
                if (newPassword.length < 8) return showAlert('warning','A nova senha deve ter no mínimo 8 caracteres.');
                
                const formData = new FormData();
                formData.append('id', usuarioLogado.id);
                formData.append('new_password', newPassword);
                formData.append('current_password', currentPassword);
                if (csrfToken) formData.append('csrf_token', csrfToken);

                const response = await fetch(apiUpdateUrl, { method: 'POST', body: formData, credentials: 'include' });
                const result = await response.json();
                showAlert(result.success ? 'success' : 'danger', result.msg);
                if (result.success) this.reset();
            });
        }

        // Event listener para o botão de Excluir Conta
        const deleteBtn = document.getElementById('delete-account-btn');
        if(deleteBtn) {
            deleteBtn.addEventListener('click', async function() {
                const confirmation = prompt("Atenção: Esta ação é irreversível.\nPara confirmar, digite sua senha atual:");
                if (confirmation !== null) {
                    const formData = new FormData();
                    formData.append('id', usuarioLogado.id);
                    formData.append('current_password', confirmation);
                    if (csrfToken) formData.append('csrf_token', csrfToken);
                    
                    const response = await fetch(apiDeleteUrl, { method: 'POST', body: formData, credentials: 'include' });
                    const result = await response.json();
                    showAlert(result.success ? 'success' : 'danger', result.msg);

                    if (result.success) {
                        sessionStorage.clear();
                        window.location.href = 'index.html';
                    }
                }
            });
        }

    } catch (error) {
        console.error("Erro crítico ao carregar perfil do motorista:", error.message);
    }
});