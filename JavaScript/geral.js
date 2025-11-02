// ---------------------------------------------------------------------------------
// SECTION 1: THEME MANAGEMENT (LIGHT/DARK)
// SEÇÃO 1: GERENCIAMENTO DE TEMA (CLARO/ESCURO)
// ---------------------------------------------------------------------------------

/**
 * Toggles between light and dark theme, saving the preference to localStorage.
 * Alterna entre o tema claro e escuro, salvando a preferência no localStorage.
 */
function toggleTheme() {
    const body = document.body;
    const isDark = body.classList.toggle('dark-theme'); // toggle retorna true se a classe foi adicionada

    if (isDark) {
        localStorage.setItem('theme', 'dark');
        updateThemeIcon('light_mode'); // Icon to activate light mode / Ícone para ativar modo claro
    } else {
        localStorage.setItem('theme', 'light');
        updateThemeIcon('dark_mode'); // Icon to activate dark mode / Ícone para ativar modo escuro
    }
}

/**
 * Updates the theme toggle button icon.
 * Atualiza o ícone do botão de alternância de tema.
 * @param {string} iconName - The icon name to display (e.g., 'light_mode', 'dark_mode') / O nome do ícone a ser exibido.
 */
function updateThemeIcon(iconName) {
    const themeToggleIcon = document.getElementById('theme-toggle-icon');
    if (themeToggleIcon) {
        themeToggleIcon.textContent = iconName;
    }
}

/**
 * Detects the user's preferred theme (saved in localStorage or system preference) and applies it on page load.
 * Detecta o tema preferido do usuário (salvo no localStorage ou preferência do sistema) e o aplica ao carregar a página.
 */
function detectPreferredTheme() {
    const savedTheme = localStorage.getItem('theme');

    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        updateThemeIcon('light_mode');
    } else if (savedTheme === 'light') {
        document.body.classList.remove('dark-theme');
        updateThemeIcon('dark_mode');
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        // If no theme saved, use OS preference / Se não houver tema salvo, usa a preferência do sistema operacional
        document.body.classList.add('dark-theme');
        updateThemeIcon('light_mode');
    }
}

/**
 * Creates and attaches the floating theme toggle button to the page body.
 * Cria e anexa o botão flutuante de alternância de tema ao corpo da página.
 */
function createThemeToggle() {
    const themeToggle = document.createElement('div');
    themeToggle.className = 'theme-toggle';
    themeToggle.setAttribute('title', 'Alternar tema');
    themeToggle.setAttribute('aria-label', 'Alternar entre tema claro e escuro');
    themeToggle.innerHTML = '<i id="theme-toggle-icon" class="material-icons">dark_mode</i>';
    themeToggle.addEventListener('click', toggleTheme);

    document.body.appendChild(themeToggle);
}


// ---------------------------------------------------------------------------------
// SECTION 2: AUTHENTICATION AND SESSION MANAGEMENT
// SEÇÃO 2: AUTENTICAÇÃO E GERENCIAMENTO DE SESSÃO
// ---------------------------------------------------------------------------------

/**
 * Verifies user login credentials against saved data in localStorage.
 * Verifica as credenciais de login de um usuário contra os dados salvos no localStorage.
 * @param {string} email - The user's email / O e-mail do usuário.
 * @param {string} senha - The user's password / A senha do usuário.
 * @returns {boolean} - Returns true if login is successful, false otherwise / Retorna true se o login for bem-sucedido.
 */
function verificarLogin(email, senha) {
    const usuarios = JSON.parse(localStorage.getItem('usuarios') || '[]');
    const usuarioEncontrado = usuarios.find(usuario => usuario.email === email && usuario.senha === senha);

    if (usuarioEncontrado) {
        // Stores logged-in user data in browser session / Armazena os dados do usuário logado na sessão do navegador
        sessionStorage.setItem('usuarioLogado', JSON.stringify(usuarioEncontrado));
        return true;
    }
    return false;
}

/**
 * Handles the login form submission.
 * Lida com a submissão do formulário de login.
 * @param {Event} e - The form event object / O objeto de evento do formulário.
 */
async function handleLoginSubmit(e) {
    e.preventDefault();

    const email = document.getElementById('login-email').value;
    const senha = document.getElementById('login-senha').value;
    const formData = new FormData();
    formData.append('email', email);
    formData.append('senha', senha);

    if (!email || !senha) {
        alert('Por favor, preencha todos os campos.');
        return;
    }

    try {
        const response = await fetch('api/login.php', {
            method: 'POST',
            body: formData,
        });

        const data = await response.json();

        if (!data.success) {
            alert(data.msg || 'E-mail ou senha incorretos.');
            return;
        }

        // Login successful! / Login bem-sucedido!
        // Front-end save logic remains the same / A lógica de salvar no front-end permanece a mesma
        sessionStorage.setItem('usuarioLogado', JSON.stringify(data.user));
        window.location.href = 'dashboard.html';

    } catch (error) {
        console.error('Error connecting to API / Erro ao conectar com a API:', error);
        alert('Could not connect to server. Try again later. / Não foi possível conectar ao servidor. Tente novamente mais tarde.');
    }
}

/**
 * Checks if a user is already logged in (used on public pages like login/register).
 * Verifica se um usuário já está logado (usado em páginas públicas como login/cadastro).
 * If logged in, redirects to dashboard. / Se estiver logado, redireciona para o dashboard.
 */
function verificarUsuarioLogado() {
    if (sessionStorage.getItem('usuarioLogado')) {
        window.location.href = 'dashboard.html';
    }
}

/**
 * Checks if the user is authenticated to access protected pages.
 * Verifica se o usuário está autenticado para acessar páginas protegidas.
 * If not logged in, redirects to login page. / Se não estiver logado, redireciona para a página de login.
 * @returns {object | undefined} The user object if logged in / O objeto do usuário se estiver logado.
 */
function verificarAutenticacao() {
    const usuarioLogado = sessionStorage.getItem('usuarioLogado');

    if (!usuarioLogado) {
        window.location.href = 'index.html';
        return;
    }
    return JSON.parse(usuarioLogado);
}

/**
 * Logs out the user, clearing session data and redirecting to login.
 * Realiza o logout do usuário, limpando os dados da sessão e redirecionando para o login.
 */
function logout() {
    sessionStorage.removeItem('usuarioLogado');
    // Clears other session or local data that may exist / Limpa outros dados de sessão ou locais que possam existir
    localStorage.removeItem('authToken');
    sessionStorage.removeItem('userData');
    // Use replace to prevent user from going back to dashboard with browser back button
    // Use replace para evitar que o usuário volte para a página anterior (dashboard) com o botão "Voltar" do navegador
    window.location.replace('index.html');
}


// ---------------------------------------------------------------------------------
// SECTION 3: USER REGISTRATION AND PASSWORD VALIDATION
// SEÇÃO 3: CADASTRO DE USUÁRIO E VALIDAÇÃO DE SENHA
// ---------------------------------------------------------------------------------

/**
 * Saves a new user to localStorage after validation.
 * Salva um novo usuário no localStorage após validação.
 * @param {string} email - User's email / E-mail do usuário.
 * @param {string} senha - User's password / Senha do usuário.
 * @param {string} role - User's role ('responsavel' or 'motorista') / Perfil do usuário.
 * @param {string} cpf - User's CPF / CPF do usuário.
 * @param {string} dataNascimento - User's birth date / Data de nascimento do usuário.
 * @param {string} cnh - User's driver's license (if applicable) / CNH do usuário (se aplicável).
 * @returns {boolean} - Returns true if user was saved, false if email already exists / Retorna true se o usuário foi salvo.
 */
async function salvarUsuario(nome, email, senha, role, cpf, dataNascimento, cnh) {
        const formData = new FormData();
        formData.append('nome', nome);
        formData.append('email', email);
        formData.append('senha', senha);
        formData.append('role', role);
        formData.append('cpf', cpf);
        formData.append('dataNascimento', dataNascimento);
        if (cnh) {
            formData.append('cnh', cnh);
        }

        try {
            const response = await fetch('api/register.php', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (!data.success) {
                alert(data.msg || 'Ocorreu um erro no cadastro.');
                return false;
            }

            return true;

        } catch (error) {
            console.error('Erro ao conectar com a API:', error);
            alert('Não foi possível conectar ao servidor. Tente novamente mais tarde.');
            return false;
        }
}   

/**
 * Verifica a força de uma senha com base em múltiplos critérios.
 * @param {string} senha - A senha a ser verificada.
 * @returns {object} - Um objeto contendo o nível de força, texto e cor para o feedback.
 */
function verificarForcaSenha(senha) {
    const criterios = {
        comprimento: senha.length >= 8,
        maiuscula: /[A-Z]/.test(senha),
        minuscula: /[a-z]/.test(senha),
        numero: /[0-9]/.test(senha),
        especial: /[^A-Za-z0-9]/.test(senha)
    };

    const forca = Object.values(criterios).filter(Boolean).length;

    switch (forca) {
        case 0:
        case 1: return { nivel: 'fraca', texto: 'Fraca', cor: 'red' };
        case 2: return { nivel: 'media', texto: 'Média', cor: 'orange' };
        case 3: return { nivel: 'boa', texto: 'Boa', cor: 'gold' };
        case 4: return { nivel: 'forte', texto: 'Forte', cor: 'limegreen' };
        case 5: return { nivel: 'muito-forte', texto: 'Muito Forte', cor: 'green' };
        default: return { nivel: 'muito-fraca', texto: 'Muito Fraca', cor: 'darkred' };
    }
}

/**
 * Atualiza o indicador visual de força da senha na interface.
 * @param {string} senha - A senha digitada pelo usuário.
 */
function atualizarIndicadorForca(senha) {
    const feedback = document.getElementById('senha-feedback');
    if (!feedback) return;

    if (!senha) {
        feedback.innerHTML = '';
        return;
    }

    const forcaSenha = verificarForcaSenha(senha);
    const larguraBarra = { 'fraca': 20, 'media': 40, 'boa': 60, 'forte': 80, 'muito-forte': 100 };

    feedback.innerHTML = `
      <div class="mt-1">Força: 
          <span style="color: ${forcaSenha.cor}; font-weight: bold;">
              ${forcaSenha.texto}
          </span>
      </div>
      <div class="progress mt-1" style="height: 5px;">
          <div class="progress-bar" role="progressbar" 
               style="width: ${larguraBarra[forcaSenha.nivel] || 0}%; background-color: ${forcaSenha.cor};">
          </div>
      </div>
      <small class="text-muted">Use 8+ caracteres com letras, números e símbolos.</small>
    `;
}


// ---------------------------------------------------------------------------------
// SEÇÃO 4: VALIDAÇÃO DE CAMPOS (CPF, CNH, DATA DE NASCIMENTO)
// ---------------------------------------------------------------------------------

/**
 * Valida um número de CPF usando o algoritmo oficial.
 * @param {string} cpf - O CPF a ser validado (pode conter máscara).
 * @returns {boolean} - True se o CPF for válido.
 */
function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

    let soma = 0;
    for (let i = 1; i <= 9; i++) soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
    let resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.substring(9, 10))) return false;

    soma = 0;
    for (let i = 1; i <= 10; i++) soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.substring(10, 11))) return false;

    return true;
}

/**
 * Formata um CPF com a máscara ###.###.###-## enquanto o usuário digita.
 * @param {string} cpf - O valor do campo CPF.
 * @returns {string} - O CPF formatado.
 */
function formatarCPF(cpf) {
    return cpf.replace(/\D/g, '')
        .substring(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

/**
 * Atualiza o feedback visual (cores e ícones) para o campo CPF.
 * @param {string} cpf - O valor do campo CPF.
 * @returns {boolean} - True se o CPF for válido.
 */
function atualizarFeedbackCPF(cpf) {
    const cpfInput = document.getElementById('cadastro-cpf');
    const feedback = document.getElementById('cpf-feedback');
    if (!cpfInput || !feedback) return;

    if (!cpf) {
        feedback.innerHTML = '';
        cpfInput.classList.remove('is-valid', 'is-invalid');
        return false;
    }

    const isValid = validarCPF(cpf);
    const cpfLimpo = cpf.replace(/\D/g, '');

    if (cpfLimpo.length === 11) {
        cpfInput.classList.toggle('is-valid', isValid);
        cpfInput.classList.toggle('is-invalid', !isValid);
        feedback.innerHTML = isValid ?
            '<span class="text-success"><i class="fas fa-check-circle"></i> CPF válido</span>' :
            '<span class="text-danger"><i class="fas fa-times-circle"></i> CPF inválido</span>';
    } else {
        cpfInput.classList.remove('is-valid');
        cpfInput.classList.add('is-invalid');
        feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> CPF incompleto</span>';
    }
    return isValid;
}

/**
 * Valida uma data de nascimento, verificando a idade mínima (18 anos).
 * @param {string} dataNascimento - A data no formato 'AAAA-MM-DD'.
 * @returns {boolean} - True se a data for válida.
 */
function validarDataNascimento(dataNascimento) {
    const nascimentoInput = document.getElementById('cadastro-nascimento');
    const feedback = document.getElementById('nascimento-feedback');
    if (!nascimentoInput || !feedback) return false;
    
    if (!dataNascimento) {
        feedback.innerHTML = '';
        nascimentoInput.classList.remove('is-valid', 'is-invalid');
        return false;
    }

    const dataNasc = new Date(dataNascimento);
    const hoje = new Date();
    // Garante que a comparação seja feita sem considerar a hora do dia
    dataNasc.setUTCHours(0, 0, 0, 0);
    hoje.setUTCHours(0, 0, 0, 0);

    let idade = hoje.getFullYear() - dataNasc.getFullYear();
    const mesAniversario = dataNasc.getMonth();
    const diaAniversario = dataNasc.getDate();
    const mesAtual = hoje.getMonth();
    const diaAtual = hoje.getDate();
    
    if (mesAtual < mesAniversario || (mesAtual === mesAniversario && diaAtual < diaAniversario)) {
        idade--;
    }

    let isValid = true;
    let mensagem = '';

    if (dataNasc > hoje) {
        isValid = false;
        mensagem = '<span class="text-danger"><i class="fas fa-times-circle"></i> Data não pode estar no futuro.</span>';
    } else if (idade < 18) {
        isValid = false;
        mensagem = '<span class="text-danger"><i class="fas fa-times-circle"></i> Você deve ter no mínimo 18 anos.</span>';
    } else if (idade > 120) {
        isValid = false;
        mensagem = '<span class="text-danger"><i class="fas fa-times-circle"></i> Data de nascimento inválida.</span>';
    } else {
        mensagem = '<span class="text-success"><i class="fas fa-check-circle"></i> Data válida.</span>';
    }
    
    nascimentoInput.classList.toggle('is-valid', isValid);
    nascimentoInput.classList.toggle('is-invalid', !isValid);
    feedback.innerHTML = mensagem;
    return isValid;
}


/**
 * Valida um número de CNH usando o algoritmo oficial.
 * @param {string} cnh - O número da CNH.
 * @returns {boolean} - True se a CNH for válida.
 */
function validarCNH(cnh) {
    cnh = cnh.replace(/\D/g, '');
    if (cnh.length !== 11 || /^(\d)\1+$/.test(cnh)) return false;

    const nums = cnh.split('').map(Number);
    let soma = 0, j = 9, dsc = 0;

    for (let i = 0; i < 9; i++, j--) soma += nums[i] * j;
    
    let dv1 = soma % 11;
    if (dv1 >= 10) {
        dv1 = 0;
        dsc = 2;
    }

    soma = 0;
    j = 1;
    for (let i = 0; i < 9; i++, j++) soma += nums[i] * j;

    let dv2 = (soma % 11 < 10) ? soma % 11 : 0;
    
    return dv1 === nums[9] && dv2 === nums[10];
}

/**
 * Atualiza o feedback visual para o campo CNH.
 * @param {string} cnh - O valor do campo CNH.
 * @returns {boolean} - True se a CNH for válida.
 */
function atualizarFeedbackCNH(cnh) {
    const cnhInput = document.getElementById('cadastro-cnh');
    const feedback = document.getElementById('cnh-feedback');
    if (!cnhInput || !feedback) return;

    if (!cnh) {
        feedback.innerHTML = '';
        cnhInput.classList.remove('is-valid', 'is-invalid');
        return false;
    }
    
    const cnhLimpa = cnh.replace(/\D/g, '');
    const isValid = validarCNH(cnhLimpa);

    if (cnhLimpa.length === 11) {
        cnhInput.classList.toggle('is-valid', isValid);
        cnhInput.classList.toggle('is-invalid', !isValid);
        feedback.innerHTML = isValid ?
            '<span class="text-success"><i class="fas fa-check-circle"></i> CNH válida</span>' :
            '<span class="text-danger"><i class="fas fa-times-circle"></i> CNH inválida</span>';
    } else {
        cnhInput.classList.remove('is-valid');
        cnhInput.classList.add('is-invalid');
        feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> CNH incompleta</span>';
    }
    return isValid;
}

/**
 * Valida o formulário de cadastro completo antes da submissão.
 * @param {Event} e - O objeto do evento de submissão.
 */
async function validarFormularioCadastro(e) {
    e.preventDefault();

    // --- LÓGICA DE PERFIL ---
    // Define um perfil padrão e só muda se encontrar o campo do responsável
    let role = 'motorista'; 
    const radioResponsavel = document.getElementById('role-responsavel');
    if (radioResponsavel && radioResponsavel.checked) {
        role = 'responsavel';
    }

    // Obter valores dos campos
    const nome = document.getElementById('cadastro-nome').value;
    const email = document.getElementById('cadastro-email').value;
    const cpf = document.getElementById('cadastro-cpf').value;
    const dataNascimento = document.getElementById('cadastro-nascimento').value;
    const senha = document.getElementById('cadastro-senha').value;
    const confirmaSenha = document.getElementById('cadastro-confirma-senha').value;

    // O campo CNH só existe na página de motorista, então fazemos uma checagem segura
    const cnhInput = document.getElementById('cadastro-cnh');
    const cnh = cnhInput ? cnhInput.value : null;

    // Validações
    let isValid = true;
    let erros = [];

    if (!nome) {
        isValid = false;
        erros.push('O campo Nome é obrigatório.');
    }
    if (!/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/.test(email)) {
        isValid = false;
        erros.push('Digite um e-mail válido.');
    }
    if (!validarCPF(cpf)) {
        isValid = false;
        erros.push('Digite um CPF válido.');
    }
    // A CNH só é obrigatória se o perfil for de motorista
    if (role === 'motorista' && !validarCNH(cnh)) {
        isValid = false;
        erros.push('Digite uma CNH válida.');
    }
    if (!validarDataNascimento(dataNascimento)) {
        isValid = false;
        erros.push('Informe uma data de nascimento válida (mínimo 18 anos).');
    }
    const forca = verificarForcaSenha(senha).nivel;
    if (forca === 'fraca' || forca === 'media') {
        isValid = false;
        erros.push('A senha precisa ter força "Boa" ou superior.');
    }
    if (senha !== confirmaSenha) {
        isValid = false;
        erros.push('As senhas não coincidem.');
    }

    if (!isValid) {
        alert('Por favor, corrija os seguintes erros:\n\n- ' + erros.join('\n- '));
        return;
    }

    // Se tudo estiver válido, chama a função para salvar
    const cadastroSucesso = await salvarUsuario(nome, email, senha, role, cpf, dataNascimento, cnh);

    if (cadastroSucesso) {
        alert('Cadastro realizado com sucesso! Agora você pode fazer login.');
        window.location.href = 'index.html';
    }
}


// ---------------------------------------------------------------------------------
// SEÇÃO 5: LÓGICA DO DASHBOARD
// ---------------------------------------------------------------------------------

/**
 * Configura a interface do dashboard com base no perfil do usuário logado.
 * @param {object} usuario - O objeto do usuário contendo email e role.
 */
function configurarDashboard(usuario) {
    const userEmailElement = document.getElementById('user-email');
    const userRoleElement = document.getElementById('user-role');
    
    if (userEmailElement) userEmailElement.textContent = usuario.email;
    if (userRoleElement) userRoleElement.textContent = usuario.role === 'responsavel' ? 'Responsável' : 'Motorista';
    
    // Mostra/esconde menus e seções com base no perfil
    const menuResponsavel = document.getElementById('menu-responsavel');
    const conteudoResponsavel = document.getElementById('conteudo-responsavel');
    const menuMotorista = document.getElementById('menu-motorista');
    const conteudoMotorista = document.getElementById('conteudo-motorista');
    
    if(menuResponsavel) menuResponsavel.style.display = 'none';
    if(conteudoResponsavel) conteudoResponsavel.style.display = 'none';
    if(menuMotorista) menuMotorista.style.display = 'none';
    if(conteudoMotorista) conteudoMotorista.style.display = 'none';
    
    if (usuario.role === 'responsavel') {
        if(menuResponsavel) menuResponsavel.style.display = 'block';
        if(conteudoResponsavel) conteudoResponsavel.style.display = 'flex';
    } else if (usuario.role === 'motorista') {
        if(menuMotorista) menuMotorista.style.display = 'block';
        if(conteudoMotorista) conteudoMotorista.style.display = 'flex';
    }
}


// ---------------------------------------------------------------------------------
// SEÇÃO 6: INICIALIZAÇÃO E EVENT LISTENERS
// ---------------------------------------------------------------------------------

/**
 * Ponto de entrada principal. Executado quando o DOM está totalmente carregado.
 * Configura os event listeners e a lógica específica da página.
 */
document.addEventListener('DOMContentLoaded', function() {
    // --- Configuração Comum a Todas as Páginas ---
    createThemeToggle();
    detectPreferredTheme();

    const themeToggleBtn = document.getElementById('theme-toggle-btn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', toggleTheme);
    }

    // Ajusta dinamicamente o link de "Perfil" conforme o papel do usuário logado
    // Dynamically route the "Perfil" link based on the logged-in user's role
    try {
        const perfilLinkEl = document.getElementById('perfil-link');
        const rawSession = sessionStorage.getItem('usuarioLogado');
        if (perfilLinkEl && rawSession) {
            const user = JSON.parse(rawSession);
            if (user && user.role) {
                perfilLinkEl.setAttribute('href', user.role === 'motorista' ? 'perfilmotorista.html' : 'perfil.html');
            }
        }
    } catch (_) { /* noop */ }

    // --- Lógica Específica da Página de Login ---
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        verificarUsuarioLogado(); // Se já estiver logado, redireciona
        loginForm.addEventListener('submit', handleLoginSubmit);
    }

    // --- Lógica Específica da Página de Cadastro ---
    const cadastroForm = document.getElementById('cadastroForm');
    if (cadastroForm) {
        verificarUsuarioLogado(); // Se já estiver logado, redireciona

        const inputs = {
            cpf: document.getElementById('cadastro-cpf'),
            nascimento: document.getElementById('cadastro-nascimento'),
            cnh: document.getElementById('cadastro-cnh'),
            senha: document.getElementById('cadastro-senha'),
            confirmaSenha: document.getElementById('cadastro-confirma-senha')
        };
        
        // Criar elementos de feedback se não existirem
        for (const key in inputs) {
            if (inputs[key] && !document.getElementById(`${key}-feedback`)) {
                 const feedbackDiv = document.createElement('div');
                 feedbackDiv.id = `${key}-feedback`;
                 feedbackDiv.className = 'feedback-container mt-1 text-start';
                 inputs[key].parentNode.appendChild(feedbackDiv);
            }
        }
        
        // Listeners para validação em tempo real
        if(inputs.cpf) {
            inputs.cpf.addEventListener('input', (e) => {
                e.target.value = formatarCPF(e.target.value);
                atualizarFeedbackCPF(e.target.value);
            });
        }
        if(inputs.nascimento) inputs.nascimento.addEventListener('change', (e) => validarDataNascimento(e.target.value));
        if(inputs.cnh) inputs.cnh.addEventListener('input', (e) => atualizarFeedbackCNH(e.target.value));
        if(inputs.senha) inputs.senha.addEventListener('input', (e) => atualizarIndicadorForca(e.target.value));
        if(inputs.confirmaSenha) {
            inputs.confirmaSenha.addEventListener('input', (e) => {
                const feedback = document.getElementById('confirmaSenha-feedback');
                const senhaVal = inputs.senha.value;
                const confirmaVal = e.target.value;
                if (!confirmaVal) {
                    feedback.innerHTML = '';
                } else if (senhaVal === confirmaVal) {
                    feedback.innerHTML = '<span style="color: green;">Senhas coincidem ✓</span>';
                } else {
                    feedback.innerHTML = '<span style="color: red;">Senhas não coincidem ✗</span>';
                }
            });
        }

        // Listener para submissão do formulário
        cadastroForm.addEventListener('submit', validarFormularioCadastro);
    }
    
    // --- Lógica Específica da Página do Dashboard ---
    const logoutBtn = document.getElementById('logout-btn');
    // A verificação agora é feita diretamente no botão, que sabemos que existe no dashboard.html
    if (logoutBtn) { 
        const usuario = verificarAutenticacao(); // Protege a página
        if (usuario) {
            // A função configurarDashboard não existe no seu geral.js, 
            // mas se existisse, seria chamada aqui.
            // configurarDashboard(usuario); 

            // Adiciona o evento de clique diretamente
            logoutBtn.addEventListener('click', logout);
        }
    }
});

// --- INÍCIO DO CÓDIGO DE RESET DE SENHA ---

// Adiciona um "ouvinte" que espera o documento HTML inteiro ser carregado antes de executar o código dentro dele.
// Isso evita erros de tentar manipular elementos que ainda não existem na página.
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Lógica para a página reset-senha.html ---
    
    // Procura na página por um elemento com o id 'resetSenhaForm'.
    const resetSenhaForm = document.getElementById('resetSenhaForm');
    
    // Se encontrar o formulário (ou seja, se estivermos na página reset-senha.html)...
    if (resetSenhaForm) {
        // ...adiciona um "ouvinte" para o evento 'submit' (quando o botão 'type="submit"' é clicado).
        // A função 'async' permite o uso do 'await' para esperar respostas da API.
        resetSenhaForm.addEventListener('submit', async function(e) {
            // Previne o comportamento padrão do formulário, que é recarregar a página.
            e.preventDefault();
            
            // Pega os valores digitados pelo usuário.
            const email = document.getElementById('reset-email').value;
            const feedbackDiv = document.getElementById('feedback');
            // Mostra uma mensagem de carregamento para o usuário.
            feedbackDiv.textContent = 'Enviando...';
            
            // Cria um objeto FormData, que é a maneira correta de enviar dados de formulário via fetch.
            const formData = new FormData();
            formData.append('email', email);

            try {
                // Faz a requisição para a API (backend) de forma assíncrona.
                const response = await fetch('api/request_reset.php', {
                    method: 'POST', // Método de envio
                    body: formData  // Os dados a serem enviados
                });
                // Espera a resposta do servidor e a converte de JSON para um objeto JavaScript.
                const result = await response.json();

                // Verifica o campo 'success' da resposta do PHP.
                if (result.success) {
                    // Se deu certo, mostra a mensagem de sucesso.
                    feedbackDiv.innerHTML = `<div class="alert alert-success">${result.msg}</div>`;
                    
                    // Mostra o token em um alerta (apenas para testes) e redireciona o usuário.
                    alert(`PARA TESTES: O código de recuperação é ${result.token_para_teste}`);
                    window.location.href = `nova-senha.html?email=${encodeURIComponent(email)}`;
                } else {
                    // Se deu erro, mostra a mensagem de erro.
                    feedbackDiv.innerHTML = `<div class="alert alert-danger">${result.msg}</div>`;
                }
            } catch (error) {
                // Se houver um erro de conexão (ex: sem internet), mostra uma mensagem genérica.
                feedbackDiv.innerHTML = `<div class="alert alert-danger">Erro de conexão. Tente novamente.</div>`;
            }
        });
    }

    // --- Lógica para a página nova-senha.html ---
    
    // Procura na página por um elemento com o id 'novaSenhaForm'.
    const novaSenhaForm = document.getElementById('novaSenhaForm');
    
    // Se encontrar o formulário (ou seja, se estivermos na página nova-senha.html)...
    if (novaSenhaForm) {
        // ...executa a lógica para essa página.
        
        // Pega o e-mail que foi passado como parâmetro na URL.
        const urlParams = new URLSearchParams(window.location.search);
        const emailFromUrl = urlParams.get('email');
        // Se o e-mail existir na URL, preenche o campo oculto do formulário com ele.
        if (emailFromUrl) {
            document.getElementById('reset-email-hidden').value = emailFromUrl;
        }

        // Adiciona o "ouvinte" de 'submit' para o formulário da nova senha.
        novaSenhaForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Pega todos os valores dos campos do formulário.
            const email = document.getElementById('reset-email-hidden').value;
            const token = document.getElementById('reset-code').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const feedbackDiv = document.getElementById('feedback');

            // Validação simples no frontend para senhas que não coincidem.
            if (newPassword !== confirmPassword) {
                feedbackDiv.innerHTML = `<div class="alert alert-danger">As senhas não coincidem.</div>`;
                return; // Para a execução da função.
            }

            // Prepara os dados para enviar ao backend.
            const formData = new FormData();
            formData.append('email', email);
            formData.append('token', token);
            formData.append('new_password', newPassword);

            try {
                 // Envia os dados para o script que finaliza o processo.
                 const response = await fetch('api/do_reset.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                // Processa a resposta do servidor.
                if (result.success) {
                    alert('Senha redefinida com sucesso! Você será redirecionado para o login.');
                    // Redireciona para a página de login após o sucesso.
                    window.location.href = 'index.html';
                } else {
                    feedbackDiv.innerHTML = `<div class="alert alert-danger">${result.msg}</div>`;
                }
            } catch(error) {
                 feedbackDiv.innerHTML = `<div class="alert alert-danger">Erro de conexão. Tente novamente.</div>`;
            }
        });
    }
});
// --- FIM DO CÓDIGO DE RESET DE SENHA ---