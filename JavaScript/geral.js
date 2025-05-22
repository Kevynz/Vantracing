// common.js - Funções compartilhadas entre todas as páginas

// Funções de tema
function toggleTheme() {
  const body = document.body;
  
  // Alterna a classe 'dark-theme' no body
  if (body.classList.contains('dark-theme')) {
    body.classList.remove('dark-theme');
    // Usar variável global em vez de localStorage
    window.currentTheme = 'light';
    updateThemeIcon('dark_mode'); // Atualiza o ícone para modo escuro (para quando está no modo claro)
  } else {
    body.classList.add('dark-theme');
    window.currentTheme = 'dark';
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

// Funções para gerenciamento de usuários (usando armazenamento em memória)
if (!window.usuarios) {
  window.usuarios = [];
}

function salvarUsuario(email, senha, role, cpf, dataNascimento, cnh) {
  // Verificar se o email já está cadastrado
  const emailExistente = window.usuarios.some(usuario => usuario.email === email);
  if (emailExistente) {
    alert('Este e-mail já está cadastrado. Por favor, use outro e-mail ou faça login.');
    return false;
  }
  
  // Adicionar novo usuário
  window.usuarios.push({
    email: email,
    senha: senha,
    role: role,
    cpf: cpf,
    dataNascimento: dataNascimento,
    cnh: cnh,
    dataCadastro: new Date().toISOString()
  });
  
  return true;
}

function verificarLogin(email, senha) {
  return window.usuarios.some(usuario => 
    usuario.email === email && usuario.senha === senha
  );
}

function obterUsuario(email) {
  return window.usuarios.find(usuario => usuario.email === email);
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

// Funções de validação de CPF
function validarCPF(cpf) {
  // Remove caracteres não numéricos
  cpf = cpf.replace(/[^\d]/g, '');
  
  // Verifica se tem 11 dígitos
  if (cpf.length !== 11) {
    return false;
  }
  
  // Verifica se todos os dígitos são iguais (caso inválido)
  if (/^(\d)\1{10}$/.test(cpf)) {
    return false;
  }
  
  // Algoritmo de validação do CPF
  let soma = 0;
  let resto;
  
  // Primeiro dígito verificador
  for (let i = 1; i <= 9; i++) {
    soma = soma + parseInt(cpf.substring(i-1, i)) * (11 - i);
  }
  
  resto = (soma * 10) % 11;
  if ((resto === 10) || (resto === 11)) {
    resto = 0;
  }
  
  if (resto !== parseInt(cpf.substring(9, 10))) {
    return false;
  }
  
  // Segundo dígito verificador
  soma = 0;
  for (let i = 1; i <= 10; i++) {
    soma = soma + parseInt(cpf.substring(i-1, i)) * (12 - i);
  }
  
  resto = (soma * 10) % 11;
  if ((resto === 10) || (resto === 11)) {
    resto = 0;
  }
  
  if (resto !== parseInt(cpf.substring(10, 11))) {
    return false;
  }
  
  return true;
}

// Função para formatar o CPF automaticamente (###.###.###-##)
function formatarCPF(cpf) {
  // Remove caracteres não numéricos
  cpf = cpf.replace(/\D/g, '');
  
  // Limita a 11 dígitos
  cpf = cpf.substring(0, 11);
  
  // Aplica a máscara
  cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
  cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
  cpf = cpf.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  
  return cpf;
}

// Função de validação da CNH (algoritmo do Detran)
function validarCNH(cnh) {
  cnh = cnh.replace(/\D/g, '');
  if (!/^\d{11}$/.test(cnh) || /^(\d)\1+$/.test(cnh)) return false;

  const nums = cnh.split('').map(Number);

  let dsc = 0;
  let soma = 0;
  for (let i = 0, j = 9; i < 9; i++, j--) {
    soma += nums[i] * j;
  }

  let dv1 = soma % 11;
  if (dv1 >= 10) {
    dv1 = 0;
    dsc = 2;
  }

  soma = 0;
  for (let i = 0, j = 1; i < 9; i++, j++) {
    soma += nums[i] * j;
  }

  let dv2 = soma % 11;
  if (dv2 >= 10) dv2 = 0;

  return dv1 === nums[9] && dv2 === nums[10];
}

// Inicialização comum para todas as páginas
document.addEventListener('DOMContentLoaded', function() {
  // Cria o botão flutuante de alternar tema
  createThemeToggle();
  
  // Aplica o tema preferido/salvo
  detectPreferredTheme();
  
  // Verifica se existe o botão na navbar e adiciona o evento
  const themeToggleBtn = document.getElementById('theme-toggle-btn');
  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', toggleTheme);
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
})

// Função para verificar se o usuário está logado
function verificarUsuarioLogado() {
  const usuarioLogado = sessionStorage.getItem('usuarioLogado');
  
  if (usuarioLogado) {
      // Se o usuário já estiver logado, redirecionar para o dashboard
      window.location.href = 'dashboard.html';
  }
}
// Função para verificar se o usuário tem permissão para acessar a página
function verificarPermissao() {
  const usuarioLogado = sessionStorage.getItem('usuarioLogado');
  
  if (!usuarioLogado) {
      // Se não estiver logado, redirecionar para a página de login
      window.location.href = 'index.html';
      return false;
  }
}

// Exportar funções principais para uso global
window.gerenciadorUsuarios = gerenciadorUsuarios;
window.usuarioEstaLogado = usuarioEstaLogado;
window.obterUsuarioLogado = obterUsuarioLogado;
window.logout = logout;