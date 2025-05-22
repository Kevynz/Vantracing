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
    }
