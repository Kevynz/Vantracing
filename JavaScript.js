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

// Função para atualizar o feedback visual do CPF
function atualizarFeedbackCPF(cpf) {
  const cpfInput = document.getElementById('cadastro-cpf');
  const feedback = document.getElementById('cpf-feedback');
  
  // Se o campo estiver vazio, limpar feedback
  if (!cpf) {
      feedback.innerHTML = '';
      cpfInput.classList.remove('is-valid', 'is-invalid');
      return false;
  }
  
  // Valida o CPF
  const cpfLimpo = cpf.replace(/\D/g, '');
  const isValid = (cpfLimpo.length === 11) && validarCPF(cpfLimpo);
  
  // Atualiza classes e feedback visuais
  if (isValid) {
      cpfInput.classList.add('is-valid');
      cpfInput.classList.remove('is-invalid');
      feedback.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> CPF válido</span>';
  } else if (cpfLimpo.length === 11) {
      cpfInput.classList.add('is-invalid');
      cpfInput.classList.remove('is-valid');
      feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> CPF inválido</span>';
  } else {
      cpfInput.classList.add('is-invalid');
      cpfInput.classList.remove('is-valid');
      feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> CPF incompleto</span>';
  }
  
  return isValid;
}

// Função para validar a data de nascimento
function validarDataNascimento(dataNascimento) {
  const nascimentoInput = document.getElementById('cadastro-nascimento');
  const feedback = document.getElementById('nascimento-feedback');
  
  // Se o campo estiver vazio, limpar feedback
  if (!dataNascimento) {
      feedback.innerHTML = '';
      nascimentoInput.classList.remove('is-valid', 'is-invalid');
      return false;
  }
  
  // Converte a string para objeto Date
  const dataNasc = new Date(dataNascimento);
  const hoje = new Date();
  
  // Calcula a idade
  let idade = hoje.getFullYear() - dataNasc.getFullYear();
  const mesAtual = hoje.getMonth();
  const mesNasc = dataNasc.getMonth();
  
  // Ajusta a idade se ainda não fez aniversário no ano atual
  if (mesNasc > mesAtual || (mesNasc === mesAtual && dataNasc.getDate() > hoje.getDate())) {
      idade--;
  }
  
  // Validações
  let isValid = true;
  let mensagem = '';
  
  // Verifica se a data está no futuro
  if (dataNasc > hoje) {
      isValid = false;
      mensagem = '<span class="text-danger"><i class="fas fa-times-circle"></i> A data não pode estar no futuro</span>';
  } 
  // Verifica se tem pelo menos 18 anos
  else if (idade < 18) {
      isValid = false;
      mensagem = '<span class="text-danger"><i class="fas fa-times-circle"></i> Você precisa ter pelo menos 18 anos</span>';
  }
  // Verifica se a idade é razoável (menor que 120 anos)
  else if (idade > 120) {
      isValid = false;
      mensagem = '<span class="text-danger"><i class="fas fa-times-circle"></i> Data de nascimento inválida</span>';
  }
  // Data válida
  else {
      mensagem = '<span class="text-success"><i class="fas fa-check-circle"></i> Data válida</span>';
  }
  
  // Atualiza classes e feedback visuais
  if (isValid) {
      nascimentoInput.classList.add('is-valid');
      nascimentoInput.classList.remove('is-invalid');
  } else {
      nascimentoInput.classList.add('is-invalid');
      nascimentoInput.classList.remove('is-valid');
  }
  
  feedback.innerHTML = mensagem;
  return isValid;
}
document.addEventListener('DOMContentLoaded', function() {
  const cpfInput = document.getElementById('cadastro-cpf');
  const nascimentoInput = document.getElementById('cadastro-nascimento');
  
  // Configuração do campo de CPF
  if (cpfInput) {
      // Cria elemento de feedback se não existir
      if (!document.getElementById('cpf-feedback')) {
          const feedbackDiv = document.createElement('div');
          feedbackDiv.id = 'cpf-feedback';
          feedbackDiv.className = 'cpf-feedback mt-1 text-start';
          cpfInput.parentNode.appendChild(feedbackDiv);
      }
      
      // Evento para formatar o CPF durante a digitação
      cpfInput.addEventListener('input', function() {
          // Mantém o cursor na posição correta durante a formatação
          const cursorPos = this.selectionStart;
          const valorOriginal = this.value;
          const valorFormatado = formatarCPF(valorOriginal);
          
          // Calcula a diferença entre os comprimentos para ajustar o cursor
          const diferencaLength = valorFormatado.length - valorOriginal.length;
          
          this.value = valorFormatado;
          
          // Ajusta a posição do cursor após formatação
          if (cursorPos + diferencaLength > 0) {
              this.setSelectionRange(cursorPos + diferencaLength, cursorPos + diferencaLength);
          }
          
          // Atualiza o feedback visual
          atualizarFeedbackCPF(this.value);
      });
      
      // Evento para validação completa ao perder o foco
      cpfInput.addEventListener('blur', function() {
          atualizarFeedbackCPF(this.value);
      });
  }
  
  // Configuração do campo de Data de Nascimento
  if (nascimentoInput) {
      // Cria elemento de feedback se não existir
      if (!document.getElementById('nascimento-feedback')) {
          const feedbackDiv = document.createElement('div');
          feedbackDiv.id = 'nascimento-feedback';
          feedbackDiv.className = 'nascimento-feedback mt-1 text-start';
          nascimentoInput.parentNode.appendChild(feedbackDiv);
      }
      
      // Define a data máxima como hoje
      const hoje = new Date();
      const anoAtual = hoje.getFullYear();
      const mesAtual = String(hoje.getMonth() + 1).padStart(2, '0');
      const diaAtual = String(hoje.getDate()).padStart(2, '0');
      nascimentoInput.setAttribute('max', `${anoAtual}-${mesAtual}-${diaAtual}`);
      
      // Define a data mínima como 120 anos atrás (idade máxima razoável)
      nascimentoInput.setAttribute('min', `${anoAtual - 120}-${mesAtual}-${diaAtual}`);
      
      // Evento para validar a data ao mudar
      nascimentoInput.addEventListener('change', function() {
          validarDataNascimento(this.value);
      });
      
      // Evento para validar a data ao perder o foco
      nascimentoInput.addEventListener('blur', function() {
          validarDataNascimento(this.value);
      });
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

// Função para atualizar o feedback visual da CNH
function atualizarFeedbackCNH(cnh) {
  const cnhInput = document.getElementById('cadastro-cnh');
  const feedback = document.getElementById('cnh-feedback');

  if (!cnh) {
    feedback.innerHTML = '';
    cnhInput.classList.remove('is-valid', 'is-invalid');
    return false;
  }

  const cnhLimpa = cnh.replace(/\D/g, '');
  const isValid = (cnhLimpa.length === 11) && validarCNH(cnhLimpa);

  if (isValid) {
    cnhInput.classList.add('is-valid');
    cnhInput.classList.remove('is-invalid');
    feedback.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> CNH válida</span>';
  } else if (cnhLimpa.length === 11) {
    cnhInput.classList.add('is-invalid');
    cnhInput.classList.remove('is-valid');
    feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> CNH inválida</span>';
  } else {
    cnhInput.classList.add('is-invalid');
    cnhInput.classList.remove('is-valid');
    feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> CNH incompleta</span>';
  }

  return isValid;
}

// Ativar a validação automática enquanto digita
document.getElementById('cadastro-cnh').addEventListener('input', function () {
  atualizarFeedbackCNH(this.value);
});
  
  // Modificar a função de validação do formulário para incluir o CPF, data de nascimento e cnh
  // Verifica se o formulário de cadastro existe
  const formCadastro = document.getElementById('cadastroForm');
  if (formCadastro) {
      // Salva a função original de validação se existir
      const validarFormularioOriginal = window.validarFormulario || function() {};
      
      // Substitui a função de validação
      window.validarFormulario = function(e) {
          e.preventDefault();
          
          const email = document.getElementById('cadastro-email').value;
          const cpf = document.getElementById('cadastro-cpf').value;
          const cpfLimpo = cpf.replace(/\D/g, ''); // Remove caracteres não numéricos
          const cnh = document.getElementById('cadastro-cnh').value;
          const dataNascimento = document.getElementById('cadastro-nascimento').value;
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
          
          // Verificar se o CPF está preenchido e é válido
          if (!cpf || !validarCPF(cpf)) {
              isValid = false;
              mensagemErro += 'Digite um CPF válido.\n';
          }

          if (!cnh || !validarCNH(cnh)) {
            isValid = false;
            mensagemErro += 'Digite uma CNH válida.\n';
          }
          
          // Verificar se a data de nascimento está preenchida e é válida
          if (!dataNascimento || !validarDataNascimento(dataNascimento)) {
              isValid = false;
              mensagemErro += 'Informe uma data de nascimento válida (mínimo 18 anos).\n';
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
          
          // Se houver erros, mostrar alerta e impedir o envio
          if (!isValid) {
              alert('Por favor, corrija os seguintes erros:\n' + mensagemErro);
              return false;
          }
          
          // Salvar os dados do usuário
          const role = roleResponsavel ? 'responsavel' : 'motorista';
          if (salvarUsuario(email, senha, role, cpf, dataNascimento, new Date().toISOString())) {
              // Se o cadastro for bem-sucedido, mostrar mensagem de sucesso
              alert('Cadastro realizado com sucesso! Agora você pode fazer login.');
              
              // Redirecionar para a página de login
              window.location.href = 'index.html';
          }
          
          return false;
      };
      
      // Atualizar o handler do formulário
      formCadastro.addEventListener('submit', window.validarFormulario);
  }
});

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
