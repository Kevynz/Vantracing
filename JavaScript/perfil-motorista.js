document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log("A iniciar script do perfil do motorista...");
        
        const usuarioLogado = JSON.parse(sessionStorage.getItem('usuarioLogado'));
        if (!usuarioLogado || usuarioLogado.role !== 'motorista') {
            throw new Error("Usuário não autenticado como motorista.");
        }
        
        // ADICIONADO: Log para depuração dos dados do utilizador
        console.log("Dados do utilizador carregados da sessão:", usuarioLogado);

        // --- PREENCHE OS DADOS INICIAIS ---
        document.getElementById('user-nome-display').textContent = usuarioLogado.nome;
        document.getElementById('new-name').value = usuarioLogado.nome;
        document.getElementById('new-email').value = usuarioLogado.email;
        
        // Função para mascarar os números de documentos
        function maskDocument(number) {
            if (!number || typeof number !== 'string') return '';
            const cleanNumber = number.replace(/\D/g, '');
            if (cleanNumber.length <= 6) return cleanNumber; 
            const start = cleanNumber.substring(0, 3);
            const end = cleanNumber.substring(cleanNumber.length - 3);
            return `${start}...${end}`;
        }
        
        // Preenche os dados do perfil (CPF, CNH) mascarados
        if (usuarioLogado.profile) {
            console.log("Objeto 'profile' encontrado:", usuarioLogado.profile);
            const cpfInput = document.getElementById('motorista-cpf');
            const cnhInput = document.getElementById('motorista-cnh');

            if (cpfInput && usuarioLogado.profile.cpf) {
                cpfInput.value = maskDocument(usuarioLogado.profile.cpf);
                console.log(`CPF mascarado e definido como: ${cpfInput.value}`);
            } else {
                console.warn("Campo de CPF não encontrado no HTML ou dado de CPF em falta no perfil.");
            }
            if (cnhInput && usuarioLogado.profile.cnh) {
                cnhInput.value = maskDocument(usuarioLogado.profile.cnh);
                console.log(`CNH mascarada e definida como: ${cnhInput.value}`);
            } else {
                 console.warn("Campo de CNH não encontrado no HTML ou dado de CNH em falta no perfil.");
            }
        } else {
            // ADICIONADO: Aviso importante se o objeto 'profile' não for encontrado
            console.error("ERRO: O objeto 'usuarioLogado.profile' não foi encontrado. Verifique se o script de login (login.php) está a devolver os dados do perfil do motorista corretamente.");
        }

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
        
        const apiUpdateUrl = '/Vantracing/api/update_account.php';
        const apiDeleteUrl = '/Vantracing/api/delete_account.php';

        // --- FORMULÁRIO DE ATUALIZAÇÃO DE INFORMAÇÕES ---
        document.getElementById('updateInfoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const newName = document.getElementById('new-name').value;
            const newEmail = document.getElementById('new-email').value;
            const currentPassword = document.getElementById('info-current-password').value;

            if (!newName && !newEmail) {
                return alert('Preencha o nome ou o e-mail que deseja alterar.');
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

        // --- FORMULÁRIO DE ATUALIZAÇÃO DE SENHA ---
        document.getElementById('updatePasswordForm').addEventListener('submit', async function(e) {
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

        // --- BOTÃO DE EXCLUIR CONTA ---
        document.getElementById('delete-account-btn').addEventListener('click', async function() {
            const confirmation = prompt("Atenção: Esta ação é irreversível.\nPara confirmar, digite sua senha atual:");
            if (confirmation !== null) {
                const formData = new FormData();
                formData.append('id', usuarioLogado.id);
                formData.append('current_password', confirmation); // Usa a senha digitada para confirmar
                
                const response = await fetch(apiDeleteUrl, { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.msg);

                if (result.success) {
                    sessionStorage.clear();
                    window.location.href = '/Vantracing/index.html';
                }
            }
        });

    } catch (error) {
        console.error("Erro crítico ao carregar perfil do motorista:", error.message);
        alert("Ocorreu um erro ao carregar seu perfil. Por favor, faça login novamente.");
        window.location.href = '/Vantracing/index.html';
    }
});
