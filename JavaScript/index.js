// login.js - Funções específicas da página de login

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
    // Login bem-sucedido, salvar dados do usuário na sessão
    const usuario = obterUsuario(email);
    window.usuarioLogado = usuario;
    
    // Redirecionar para o dashboard
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

// Função para verificar se o usuário já está logado
function verificarUsuarioLogado() {
  if (window.usuarioLogado) {
    // Se o usuário já estiver logado, redirecionar para o dashboard
    window.location.href = 'dashboard.html';
  }
}

// Inicialização específica da página de login
document.addEventListener('DOMContentLoaded', function() {
  // Verificar se o usuário já está logado
  verificarUsuarioLogado();
  
  // Configurar o formulário de login
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', handleLoginSubmit);
  }
  
  // Focar no campo de email quando a página carregar
  const emailInput = document.getElementById('login-email');
  if (emailInput) {
    emailInput.focus();
  }
});
// Funções para gerenciamento de usuários
function salvarUsuario(email, senha, role) {
  // Obter usuários já cadastrados
  const usuarios = JSON.parse(localStorage.getItem('usuarios') || '[]');
}