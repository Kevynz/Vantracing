// Função para alternar entre os temas claro e escuro
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
  
  // Função para atualizar o ícone do botão de tema
  function updateThemeIcon(iconName) {
    const themeToggleIcon = document.getElementById('theme-toggle-icon');
    if (themeToggleIcon) {
      themeToggleIcon.textContent = iconName;
    }
  }
  
  // Detecta o tema preferido do usuário e aplica o tema escuro se necessário
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
  
  // Função para criar o botão flutuante de alternância de tema
  function createThemeToggle() {
    const themeToggle = document.createElement('div');
    themeToggle.className = 'theme-toggle';
    themeToggle.setAttribute('title', 'Alternar tema');
    themeToggle.setAttribute('aria-label', 'Alternar entre tema claro e escuro');
    themeToggle.innerHTML = '<i id="theme-toggle-icon" class="material-icons">dark_mode</i>';
    themeToggle.addEventListener('click', toggleTheme);
    
    document.body.appendChild(themeToggle);
  }
  
  // Inicializar quando o DOM estiver carregado
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