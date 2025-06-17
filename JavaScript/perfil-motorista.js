document.addEventListener('DOMContentLoaded', function() {
    console.log("Script do perfil do motorista carregado com sucesso!");

    try {
        const usuarioLogado = JSON.parse(sessionStorage.getItem('usuarioLogado'));
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

        // Event listener para o formulário de Alterar Informações
        const updateInfoForm = document.getElementById('updateInfoForm');
        if (updateInfoForm) {
            updateInfoForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const newName = document.getElementById('new-name').value;
                const newEmail = document.getElementById('new-email').value;
                const currentPassword = document.getElementById('info-current-password').value;

                if (!currentPassword) {
                    return alert('Você precisa digitar sua senha atual para confirmar a alteração.');
                }

                const formData = new FormData();
                formData.append('id', usuarioLogado.id);
                formData.append('new_name', newName);
                formData.append('new_email', newEmail);
                formData.append('current_password', currentPassword);

                const response = await fetch(apiUpdateUrl, { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.msg);

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

                if (newPassword !== confirmPassword) return alert('As novas senhas não coincidem.');
                if (newPassword.length < 8) return alert('A nova senha deve ter no mínimo 8 caracteres.');
                
                const formData = new FormData();
                formData.append('id', usuarioLogado.id);
                formData.append('new_password', newPassword);
                formData.append('current_password', currentPassword);

                const response = await fetch(apiUpdateUrl, { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.msg);
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
        console.error("Erro crítico ao carregar perfil do motorista:", error.message);
    }
});