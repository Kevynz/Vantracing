// Funções de tema
function toggleTheme() {
  const body = document.body;
  
  // Alterna a classe 'dark-theme' no body
  if (body.classList.contains('dark-theme')) {
    body.classList.remove('dark-theme');
    localStorage.setItem('theme', 'light');
    updateThemeIcon('dark_mode'); // Atualiza o ícone para modo escuro (para quando está no modo claro)
  } else {
    body.classList.add('dark-theme');
    localStorage.setItem('theme', 'dark');
    updateThemeIcon('light_mode'); // Atualiza o ícone para modo claro (para quando está no modo escuro)
  }
}

function updateThemeIcon(iconName) {
  const themeToggleIcon = document.getElementById('theme-toggle-icon');
  if (themeToggleIcon) {
    themeToggleIcon.textContent = iconName;
  }
}

function detectPreferredTheme() {
  // Verificar primeiro se há um tema salvo
  const savedTheme = localStorage.getItem('theme');
  
  if (savedTheme === 'dark') {
    document.body.classList.add('dark-theme');
    updateThemeIcon('light_mode');
  } else if (savedTheme === 'light') {
    // Mantém o tema claro padrão
    updateThemeIcon('dark_mode');
  } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    // Se não houver tema salvo, usa a preferência do sistema
    document.body.classList.add('dark-theme');
    updateThemeIcon('light_mode');
  }
}

function createThemeToggle() {
  const themeToggle = document.createElement('div');
  themeToggle.className = 'theme-toggle';
  themeToggle.setAttribute('title', 'Alternar tema');
  themeToggle.setAttribute('aria-label', 'Alternar entre tema claro e escuro');
  themeToggle.innerHTML = '<i id="theme-toggle-icon" class="material-icons">dark_mode</i>';
  themeToggle.addEventListener('click', toggleTheme);
  
  document.body.appendChild(themeToggle);
}

// Funções para gerenciamento de usuários
function salvarUsuario(email, senha, role) {
  // Obter usuários já cadastrados
  const usuarios = JSON.parse(localStorage.getItem('usuarios') || '[]');
  
  // Verificar se o email já está cadastrado
  const emailExistente = usuarios.some(usuario => usuario.email === email);
  if (emailExistente) {
      alert('Este e-mail já está cadastrado. Por favor, use outro e-mail ou faça login.');
      return false;
  }
  
  // Adicionar novo usuário
  usuarios.push({
      email: email,
      senha: senha, // Em um sistema real, a senha NUNCA deve ser armazenada em texto simples
      role: role
  });
  
  // Salvar no localStorage
  localStorage.setItem('usuarios', JSON.stringify(usuarios));
  return true;
}

// Função para verificar a força da senha
function verificarForcaSenha(senha) {
  // Critérios de força de senha
  const comprimento = senha.length >= 8;
  const temMaiuscula = /[A-Z]/.test(senha);
  const temMinuscula = /[a-z]/.test(senha);
  const temNumero = /[0-9]/.test(senha);
  const temEspecial = /[^A-Za-z0-9]/.test(senha);
  
  // Pontuação de força (0 a 5)
  let forca = 0;
  if (comprimento) forca++;
  if (temMaiuscula) forca++;
  if (temMinuscula) forca++;
  if (temNumero) forca++;
  if (temEspecial) forca++;
  
  // Classificação baseada na pontuação
  if (forca === 0) return { nivel: 'muito-fraca', texto: 'Muito fraca', cor: 'darkred' };
  if (forca === 1) return { nivel: 'fraca', texto: 'Fraca', cor: 'red' };
  if (forca === 2) return { nivel: 'media', texto: 'Média', cor: 'orange' };
  if (forca === 3) return { nivel: 'boa', texto: 'Boa', cor: 'gold' };
  if (forca === 4) return { nivel: 'forte', texto: 'Forte', cor: 'limegreen' };
  if (forca === 5) return { nivel: 'muito-forte', texto: 'Muito forte', cor: 'green' };
}

// Função para atualizar o indicador de força da senha
function atualizarIndicadorForca(senha) {
  const feedback = document.getElementById('senha-feedback');
  
  // Se o campo estiver vazio, não mostrar feedback
  if (!senha) {
      feedback.innerHTML = '';
      return;
  }
  
  const forcaSenha = verificarForcaSenha(senha);
  
  // Atualiza o texto e a cor do feedback
  feedback.innerHTML = `
      <div class="mt-1">Força: 
          <span style="color: ${forcaSenha.cor}; font-weight: bold;">
              ${forcaSenha.texto}
          </span>
      </div>
      <div class="progress mt-1" style="height: 5px;">
          <div class="progress-bar" role="progressbar" 
              style="width: ${(forcaSenha.nivel === 'muito-fraca' ? 10 : 
                     forcaSenha.nivel === 'fraca' ? 25 : 
                     forcaSenha.nivel === 'media' ? 50 : 
                     forcaSenha.nivel === 'boa' ? 75 : 
                     forcaSenha.nivel === 'forte' ? 90 : 100)}%; 
                     background-color: ${forcaSenha.cor};" 
              aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
          </div>
      </div>
      <small class="text-muted">A senha deve conter pelo menos 8 caracteres, letras maiúsculas, minúsculas, números e caracteres especiais.</small>
  `;
  
  // Retorna se a senha é forte o suficiente (nível bom ou superior)
  return forcaSenha.nivel === 'boa' || forcaSenha.nivel === 'forte' || forcaSenha.nivel === 'muito-forte';
}

// Função para validar o formulário de cadastro
function validarFormulario(e) {
  e.preventDefault();
  
  const email = document.getElementById('cadastro-email').value;
  const senha = document.getElementById('cadastro-senha').value;
  const confirmaSenha = document.getElementById('cadastro-confirma-senha').value;
  const roleResponsavel = document.getElementById('role-responsavel').checked;
  const roleMotorista = document.getElementById('role-motorista').checked;
  
  // Verificações de validação
  let isValid = true;
  let mensagemErro = '';
  
  // Verificar se o email está preenchido e é válido
  if (!email || !/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/.test(email)) {
      isValid = false;
      mensagemErro += 'Digite um e-mail válido.\n';
  }
  
  // Verificar força da senha
  const forcaSenha = verificarForcaSenha(senha);
  if (forcaSenha.nivel === 'muito-fraca' || forcaSenha.nivel === 'fraca' || forcaSenha.nivel === 'media') {
      isValid = false;
      mensagemErro += 'A senha precisa ser pelo menos BOA (8+ caracteres com letras maiúsculas, minúsculas, números e símbolos).\n';
  }
  
  // Verificar se as senhas coincidem
  if (senha !== confirmaSenha) {
      isValid = false;
      mensagemErro += 'As senhas não coincidem.\n';
  }
  
  // Verificar se um papel foi selecionado
  if (!roleResponsavel && !roleMotorista) {
      isValid = false;
      mensagemErro += 'Selecione um papel (Responsável ou Motorista).\n';
  }
  
  // Se houver erros, mostrar alerta e impedir o envio
  if (!isValid) {
      alert('Por favor, corrija os seguintes erros:\n' + mensagemErro);
      return false;
  }
  
  // Determinar o papel selecionado
  const role = roleResponsavel ? 'responsavel' : 'motorista';
  
  // Salvar os dados do usuário
  if (salvarUsuario(email, senha, role)) {
      alert('Cadastro realizado com sucesso! Agora você pode fazer login.');
      
      // Redirecionar para a página de login
      window.location.href = 'index.html';
  }
  
  return true;
}

// Funções para autenticação
function verificarLogin(email, senha) {
  // Obter usuários cadastrados
  const usuarios = JSON.parse(localStorage.getItem('usuarios') || '[]');
  
  // Procurar por um usuário com o email e senha fornecidos
  const usuario = usuarios.find(user => user.email === email && user.senha === senha);
  
  if (usuario) {
      // Se encontrou o usuário, armazenar informações na sessão atual
      sessionStorage.setItem('usuarioLogado', JSON.stringify({
          email: usuario.email,
          role: usuario.role
      }));
      return true;
  }
  
  return false;
}

// Verificar se o usuário já está logado
function verificarUsuarioLogado() {
  const usuarioLogado = sessionStorage.getItem('usuarioLogado');
  
  if (usuarioLogado) {
      // Se o usuário já estiver logado, redirecionar para o dashboard
      window.location.href = 'dashboard.html';
  }
}

// Função para lidar com a submissão do formulário de login
function handleLoginSubmit(e) {
  e.preventDefault();
  
  const email = document.getElementById('login-email').value;
  const senha = document.getElementById('login-senha').value;
  
  // Verificar se os campos estão preenchidos
  if (!email || !senha) {
      alert('Por favor, preencha todos os campos.');
      return false;
  }
  
  // Verificar as credenciais
  if (verificarLogin(email, senha)) {
      // Login bem-sucedido, redirecionar para o dashboard
      window.location.href = 'dashboard.html';
  } else {
      // Login falhou
      alert('E-mail ou senha incorretos. Por favor, tente novamente.');
      
      // Limpar o campo de senha
      document.getElementById('login-senha').value = '';
      
      // Focar no campo de email
      document.getElementById('login-email').focus();
  }
  
  return false;
}

// Verificar se o usuário está logado e obter informações
function verificarAutenticacao() {
  const usuarioLogado = sessionStorage.getItem('usuarioLogado');
  
  if (!usuarioLogado) {
      // Se não estiver logado, redirecionar para a página de login
      window.location.href = 'index.html';
      return;
  }
  
  // Se estiver logado, obter informações do usuário
  const usuario = JSON.parse(usuarioLogado);
  return usuario;
}

// Função para fazer logout
function logout() {
  // Remover informações do usuário da sessão
  sessionStorage.removeItem('usuarioLogado');
  
  // Redirecionar para a página de login
  window.location.href = 'index.html';
}

// Configurar a interface do dashboard de acordo com o papel do usuário
function configurarDashboard(usuario) {
  // Exibir o email do usuário logado
  const userEmailElement = document.getElementById('user-email');
  if (userEmailElement) {
      userEmailElement.textContent = usuario.email;
  }
  
  // Exibir o papel do usuário
  const userRoleElement = document.getElementById('user-role');
  if (userRoleElement) {
      const roleTraduzido = usuario.role === 'responsavel' ? 'Responsável' : 'Motorista';
      userRoleElement.textContent = roleTraduzido;
  }
  
  // Exibir menus específicos do papel
  if (usuario.role === 'responsavel') {
      // Exibir menu e conteúdo específico para responsáveis
      document.getElementById('menu-responsavel').style.display = 'block';
      document.getElementById('conteudo-responsavel').style.display = 'flex';
  } else if (usuario.role === 'motorista') {
      // Exibir menu e conteúdo específico para motoristas
      document.getElementById('menu-motorista').style.display = 'block';
      document.getElementById('conteudo-motorista').style.display = 'flex';
  }
}

// Inicialização baseada na página atual
document.addEventListener('DOMContentLoaded', function() {
  // Configuração comum para todas as páginas
  
  // Cria o botão flutuante de alternar tema
  createThemeToggle();
  
  // Aplica o tema preferido/salvo
  detectPreferredTheme();
  
  // Verifica se existe o botão na navbar e adiciona o evento
  const themeToggleBtn = document.getElementById('theme-toggle-btn');
  if (themeToggleBtn) {
      themeToggleBtn.addEventListener('click', toggleTheme);
  }
  
  // Detecta automaticamente qual página está sendo carregada
  
  // Verificar elementos da página de login
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
      // Estamos na página de login
      verificarUsuarioLogado(); // Redirecionar se já estiver logado
      loginForm.addEventListener('submit', handleLoginSubmit);
  }
  
  // Verificar elementos da página de cadastro
  const cadastroForm = document.getElementById('cadastroForm');
  if (cadastroForm) {
      // Estamos na página de cadastro
      cadastroForm.addEventListener('submit', validarFormulario);
      
      // Configurar feedback de senha
      const senhaInput = document.getElementById('cadastro-senha');
      if (senhaInput) {
          // Criar elemento de feedback se não existir
          if (!document.getElementById('senha-feedback')) {
              const feedbackDiv = document.createElement('div');
              feedbackDiv.id = 'senha-feedback';
              feedbackDiv.className = 'senha-feedback';
              senhaInput.parentNode.appendChild(feedbackDiv);
          }
          
          // Atualizar indicador quando o usuário digitar
          senhaInput.addEventListener('input', function() {
              atualizarIndicadorForca(this.value);
          });
      }
      
      // Adiciona validação de coincidência de senhas
      const confirmaSenhaInput = document.getElementById('cadastro-confirma-senha');
      if (confirmaSenhaInput) {
          confirmaSenhaInput.addEventListener('input', function() {
              const senha = document.getElementById('cadastro-senha').value;
              const confirmaSenha = this.value;
              
              // Criar elemento de feedback se não existir
              if (!document.getElementById('confirma-senha-feedback')) {
                  const feedbackDiv = document.createElement('div');
                  feedbackDiv.id = 'confirma-senha-feedback';
                  feedbackDiv.className = 'confirma-senha-feedback mt-1';
                  confirmaSenhaInput.parentNode.appendChild(feedbackDiv);
              }
              
              const feedback = document.getElementById('confirma-senha-feedback');
              
              if (!confirmaSenha) {
                  feedback.innerHTML = '';
              } else if (senha === confirmaSenha) {
                  feedback.innerHTML = '<span style="color: green;">Senhas coincidem ✓</span>';
              } else {
                  feedback.innerHTML = '<span style="color: red;">Senhas não coincidem ✗</span>';
              }
          });
      }
  }
  
  // Verificar elementos da página do dashboard
  const menuResponsavel = document.getElementById('menu-responsavel');
  const menuMotorista = document.getElementById('menu-motorista');
  if (menuResponsavel || menuMotorista) {
      // Estamos na página do dashboard
      const usuario = verificarAutenticacao();
      
      if (usuario) {
          // Configurar o dashboard conforme o papel do usuário
          configurarDashboard(usuario);
          
          // Adicionar evento de logout ao botão
          const logoutBtn = document.getElementById('logout-btn');
          if (logoutBtn) {
              logoutBtn.addEventListener('click', logout);
          }
      }
  }
});

// Função para fazer logout
function logout() {
  // Remover informações do usuário da sessão
  sessionStorage.removeItem('usuarioLogado');
  
  // Limpar quaisquer outros dados de autenticação
  localStorage.removeItem('authToken');
  sessionStorage.removeItem('userData');
  
  // Redirecionar para a página de login
  window.location.replace('index.html');
}

// Inicialização do botão de logout
document.addEventListener('DOMContentLoaded', function() {
  // Seleciona o botão de logout pelo seu ID
  const logoutBtn = document.getElementById('logout-btn');
  
  // Verifica se o botão existe na página atual
  if (logoutBtn) {
    // Remove todos os event listeners existentes (solução mais radical)
    const newLogoutBtn = logoutBtn.cloneNode(true);
    logoutBtn.parentNode.replaceChild(newLogoutBtn, logoutBtn);
    
    // Adiciona um novo event listener
    newLogoutBtn.addEventListener('click', function(event) {
      // Previne comportamento padrão
      event.preventDefault();
      
      // Chama a função de logout
      logout();
      
      // Impede propagação do evento
      event.stopPropagation();
      return false;
    });
  }
});