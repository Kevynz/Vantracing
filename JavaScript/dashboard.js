// Função para fazer logout
function logout() {
  // Remover informações do usuário da sessão
  sessionStorage.removeItem('usuarioLogado');
  
  // Redirecionar para a página de login
  window.location.href = 'index.html';
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

// Inicialização baseada na página atual
document.addEventListener('DOMContentLoaded', function() {
  // Verificar se o usuário está logado
  const usuarioLogado = sessionStorage.getItem('usuarioLogado');
  
  if (!usuarioLogado) {
      // Se não estiver logado, redirecionar para a página de login
      window.location.href = 'index.html';
      return;
  }
  
  // Se estiver logado, obter informações do usuário
  const usuario = JSON.parse(usuarioLogado);
  
  // Configurar o botão de logout
  const logoutButton = document.getElementById('logout-button');
  if (logoutButton) {
      logoutButton.addEventListener('click', logout);
  }
});

  // Exibir o email do usuário logado
  const userEmailElement = document.getElementById('user-email');
  if (userEmailElement) {
      userEmailElement.textContent = usuario.email;
  }
