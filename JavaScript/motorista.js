// cadastro.js - Funções específicas da página de cadastro

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

// Função de validação do formulário de cadastro
function validarFormulario(e) {
  e.preventDefault();
  
  const email = document.getElementById('cadastro-email').value;
  const cpf = document.getElementById('cadastro-cpf').value;
  const cnh = document.getElementById('cadastro-cnh').value;
  const dataNascimento = document.getElementById('cadastro-nascimento').value;
  const senha = document.getElementById('cadastro-senha').value;
  const confirmaSenha = document.getElementById('cadastro-confirma-senha').value;
  const roleMotorista = document.getElementById('role-motorista').checked;

  // Verificar elementos da página de cadastro
const cadastroForm = document.getElementById('cadastroForm');
  if (cadastroForm) {
      // Estamos na página de cadastro
      cadastroForm.addEventListener('submit', validarFormulario);
  
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

  // Verificar se a CNH está preenchida e é válida
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

// Inicialização específica da página de cadastro
document.addEventListener('DOMContentLoaded', function() {
  const cadastroForm = document.getElementById('cadastroForm');
  const cpfInput = document.getElementById('cadastro-cpf');
  const cnhInput = document.getElementById('cadastro-cnh');
  const nascimentoInput = document.getElementById('cadastro-nascimento');
  const senhaInput = document.getElementById('cadastro-senha');
  const confirmaSenhaInput = document.getElementById('cadastro-confirma-senha');
  
  // Configurar o formulário de cadastro
  if (cadastroForm) {
    cadastroForm.addEventListener('submit', validarFormulario);
  }
  
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
  
  // Configuração do campo de CNH
  if (cnhInput) {
    // Cria elemento de feedback se não existir
    if (!document.getElementById('cnh-feedback')) {
      const feedbackDiv = document.createElement('div');
      feedbackDiv.id = 'cnh-feedback';
      feedbackDiv.className = 'cnh-feedback mt-1 text-start';
      cnhInput.parentNode.appendChild(feedbackDiv);
    }
    
    // Ativar a validação automática enquanto digita
    cnhInput.addEventListener('input', function() {
      atualizarFeedbackCNH(this.value);
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
  
  // Configuração do campo de senha
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
  
  // Configuração do campo de confirmação de senha
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
});
}
}
// ===== FUNÇÕES UTILITÁRIAS =====

// Função para validar CPF
function validarCPF(cpf) {
  cpf = cpf.replace(/\D/g, '');
  
  if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
    return false;
  }
  
  let soma = 0;
  for (let i = 0; i < 9; i++) {
    soma += parseInt(cpf.charAt(i)) * (10 - i);
  }
  let resto = 11 - (soma % 11);
  if (resto === 10 || resto === 11) resto = 0;
  if (resto !== parseInt(cpf.charAt(9))) return false;
  
  soma = 0;
  for (let i = 0; i < 10; i++) {
    soma += parseInt(cpf.charAt(i)) * (11 - i);
  }
  resto = 11 - (soma % 11);
  if (resto === 10 || resto === 11) resto = 0;
  if (resto !== parseInt(cpf.charAt(10))) return false;
  
  return true;
}

// Função para validar CNH
function validarCNH(cnh) {
  cnh = cnh.replace(/\D/g, '');
  
  if (cnh.length !== 11 || /^(\d)\1{10}$/.test(cnh)) {
    return false;
  }
  
  let soma = 0;
  for (let i = 0; i < 9; i++) {
    soma += parseInt(cnh.charAt(i)) * (9 - i);
  }
  
  let digito1 = soma % 11;
  if (digito1 >= 10) digito1 = 0;
  
  soma = 0;
  for (let i = 0; i < 9; i++) {
    soma += parseInt(cnh.charAt(i)) * (i + 1);
  }
  
  let digito2 = soma % 11;
  if (digito2 >= 10) digito2 = 0;
  
  return digito1 === parseInt(cnh.charAt(9)) && digito2 === parseInt(cnh.charAt(10));
}

// Função para formatar CPF
function formatarCPF(cpf) {
  cpf = cpf.replace(/\D/g, '');
  cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
  cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
  cpf = cpf.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  return cpf;
}

// Função para verificar força da senha
function verificarForcaSenha(senha) {
  let pontos = 0;
  
  if (senha.length >= 8) pontos += 2;
  if (senha.length >= 12) pontos += 1;
  if (/[a-z]/.test(senha)) pontos += 1;
  if (/[A-Z]/.test(senha)) pontos += 1;
  if (/\d/.test(senha)) pontos += 1;
  if (/[^a-zA-Z0-9]/.test(senha)) pontos += 2;
  
  if (pontos <= 2) return { nivel: 'muito-fraca', texto: 'Muito Fraca', cor: '#dc3545' };
  if (pontos <= 4) return { nivel: 'fraca', texto: 'Fraca', cor: '#fd7e14' };
  if (pontos <= 5) return { nivel: 'media', texto: 'Média', cor: '#ffc107' };
  if (pontos <= 6) return { nivel: 'boa', texto: 'Boa', cor: '#20c997' };
  return { nivel: 'forte', texto: 'Forte', cor: '#28a745' };
}

// ===== GERENCIAMENTO DE USUÁRIOS =====

// Classe para gerenciar usuários
class GerenciadorUsuarios {
  constructor() {
    this.usuarios = this.carregarUsuarios();
  }
  
  // Carrega usuários do armazenamento (simulando com array em memória)
  carregarUsuarios() {
    // Em uma aplicação real, você carregaria do localStorage ou banco de dados
    // Para este exemplo, vamos usar um array em memória
    return [];
  }
  
  // Salva usuários no armazenamento
  salvarUsuarios() {
    // Em uma aplicação real, você salvaria no localStorage ou banco de dados
    // Para este exemplo, mantemos em memória apenas
    console.log('Usuários salvos:', this.usuarios);
  }
  
  // Verifica se email já existe
  emailExiste(email) {
    return this.usuarios.some(usuario => usuario.email === email);
  }
  
  // Verifica se CPF já existe
  cpfExiste(cpf) {
    const cpfLimpo = cpf.replace(/\D/g, '');
    return this.usuarios.some(usuario => usuario.cpf === cpfLimpo);
  }
  
  // Verifica se CNH já existe
  cnhExiste(cnh) {
    const cnhLimpa = cnh.replace(/\D/g, '');
    return this.usuarios.some(usuario => usuario.cnh === cnhLimpa);
  }
  
  // Cadastra novo usuário
  cadastrarUsuario(dadosUsuario) {
    try {
      // Validações básicas
      if (this.emailExiste(dadosUsuario.email)) {
        throw new Error('Este e-mail já está cadastrado');
      }
      
      if (this.cpfExiste(dadosUsuario.cpf)) {
        throw new Error('Este CPF já está cadastrado');
      }
      
      if (this.cnhExiste(dadosUsuario.cnh)) {
        throw new Error('Esta CNH já está cadastrada');
      }
      
      // Cria objeto do usuário
      const novoUsuario = {
        id: Date.now().toString(),
        nome: dadosUsuario.nome,
        email: dadosUsuario.email,
        cpf: dadosUsuario.cpf.replace(/\D/g, ''),
        cnh: dadosUsuario.cnh.replace(/\D/g, ''),
        dataNascimento: dadosUsuario.dataNascimento,
        senha: this.criptografarSenha(dadosUsuario.senha),
        role: dadosUsuario.role,
        dataCadastro: new Date().toISOString(),
        ativo: true
      };
      
      // Adiciona à lista de usuários
      this.usuarios.push(novoUsuario);
      this.salvarUsuarios();
      
      return { sucesso: true, usuario: novoUsuario };
      
    } catch (error) {
      return { sucesso: false, erro: error.message };
    }
  }
  
  // Simula criptografia da senha (em produção use bcrypt ou similar)
  criptografarSenha(senha) {
    // Esta é uma implementação simples para demonstração
    // Em produção, use uma biblioteca de hash segura como bcrypt
    return btoa(senha + 'salt_secreto');
  }
  
  // Verifica senha
  verificarSenha(senhaTexto, senhaHash) {
    return this.criptografarSenha(senhaTexto) === senhaHash;
  }
  
  // Autentica usuário
  autenticarUsuario(email, senha) {
    const usuario = this.usuarios.find(u => u.email === email && u.ativo);
    
    if (!usuario) {
      return { sucesso: false, erro: 'E-mail não encontrado' };
    }
    
    if (!this.verificarSenha(senha, usuario.senha)) {
      return { sucesso: false, erro: 'Senha incorreta' };
    }
    
    // Remove senha do objeto retornado por segurança
    const { senha: _, ...usuarioSeguro } = usuario;
    
    return { sucesso: true, usuario: usuarioSeguro };
  }
}

// Instância global do gerenciador
const gerenciadorUsuarios = new GerenciadorUsuarios();

// ===== FUNÇÕES DA PÁGINA DE CADASTRO =====

// Função para atualizar o indicador de força da senha
function atualizarIndicadorForca(senha) {
  const feedback = document.getElementById('senha-feedback');
  
  if (!senha) {
    feedback.innerHTML = '';
    return;
  }
  
  const forcaSenha = verificarForcaSenha(senha);
  
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
  
  return forcaSenha.nivel === 'boa' || forcaSenha.nivel === 'forte';
}

// Função para atualizar o feedback visual do CPF
function atualizarFeedbackCPF(cpf) {
  const cpfInput = document.getElementById('cadastro-cpf');
  const feedback = document.getElementById('cpf-feedback');
  
  if (!cpf) {
    feedback.innerHTML = '';
    cpfInput.classList.remove('is-valid', 'is-invalid');
    return false;
  }
  
  const cpfLimpo = cpf.replace(/\D/g, '');
  const isValid = (cpfLimpo.length === 11) && validarCPF(cpfLimpo);
  
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

// Função para validar a data de nascimento
function validarDataNascimento(dataNascimento) {
  const nascimentoInput = document.getElementById('cadastro-nascimento');
  const feedback = document.getElementById('nascimento-feedback');
  
  if (!dataNascimento) {
    feedback.innerHTML = '';
    nascimentoInput.classList.remove('is-valid', 'is-invalid');
    return false;
  }
  
  const dataNasc = new Date(dataNascimento);
  const hoje = new Date();
  
  let idade = hoje.getFullYear() - dataNasc.getFullYear();
  const mesAtual = hoje.getMonth();
  const mesNasc = dataNasc.getMonth();
  
  if (mesNasc > mesAtual || (mesNasc === mesAtual && dataNasc.getDate() > hoje.getDate())) {
    idade--;
  }
  
  let isValid = true;
  let mensagem = '';
  
  if (dataNasc > hoje) {
    isValid = false;
    mensagem = '<span class="text-danger"><i class="fas fa-times-circle"></i> A data não pode estar no futuro</span>';
  } else if (idade < 18) {
    isValid = false;
    mensagem = '<span class="text-danger"><i class="fas fa-times-circle"></i> Você precisa ter pelo menos 18 anos</span>';
  } else if (idade > 120) {
    isValid = false;
    mensagem = '<span class="text-danger"><i class="fas fa-times-circle"></i> Data de nascimento inválida</span>';
  } else {
    mensagem = '<span class="text-success"><i class="fas fa-check-circle"></i> Data válida</span>';
  }
  
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

// Função de validação e submissão do formulário de cadastro
function validarFormulario(e) {
  e.preventDefault();
  
  const nome = document.getElementById('cadastro-nome').value.trim();
  const email = document.getElementById('cadastro-email').value.trim();
  const cpf = document.getElementById('cadastro-cpf').value;
  const cnh = document.getElementById('cadastro-cnh').value;
  const dataNascimento = document.getElementById('cadastro-nascimento').value;
  const senha = document.getElementById('cadastro-senha').value;
  const confirmaSenha = document.getElementById('cadastro-confirma-senha').value;
  const roleMotorista = document.getElementById('role-motorista') ? document.getElementById('role-motorista').checked : false;

  let isValid = true;
  let mensagemErro = '';
  
  // Validar nome
  if (!nome || nome.length < 2) {
    isValid = false;
    mensagemErro += 'Digite um nome válido (mínimo 2 caracteres).\n';
  }
  
  // Validar email
  if (!email || !/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/.test(email)) {
    isValid = false;
    mensagemErro += 'Digite um e-mail válido.\n';
  }
  
  // Validar CPF
  if (!cpf || !validarCPF(cpf)) {
    isValid = false;
    mensagemErro += 'Digite um CPF válido.\n';
  }

  // Validar CNH
  if (!cnh || !validarCNH(cnh)) {
    isValid = false;
    mensagemErro += 'Digite uma CNH válida.\n';
  }
  
  // Validar data de nascimento
  if (!dataNascimento || !validarDataNascimento(dataNascimento)) {
    isValid = false;
    mensagemErro += 'Informe uma data de nascimento válida (mínimo 18 anos).\n';
  }
  
  // Validar força da senha
  const forcaSenha = verificarForcaSenha(senha);
  if (forcaSenha.nivel === 'muito-fraca' || forcaSenha.nivel === 'fraca' || forcaSenha.nivel === 'media') {
    isValid = false;
    mensagemErro += 'A senha precisa ser pelo menos BOA (8+ caracteres com letras maiúsculas, minúsculas, números e símbolos).\n';
  }
  
  // Validar confirmação de senha
  if (senha !== confirmaSenha) {
    isValid = false;
    mensagemErro += 'As senhas não coincidem.\n';
  }

  // Se houver erros, exibir e parar
  if (!isValid) {
    alert('Erros encontrados:\n\n' + mensagemErro);
    return false;
  }

  // Tentar cadastrar o usuário
  const dadosUsuario = {
    nome: nome,
    email: email,
    cpf: cpf,
    cnh: cnh,
    dataNascimento: dataNascimento,
    senha: senha,
    role: roleMotorista ? 'motorista' : 'cliente'
  };

  const resultado = gerenciadorUsuarios.cadastrarUsuario(dadosUsuario);

  if (resultado.sucesso) {
    alert('Cadastro realizado com sucesso! Você já pode fazer login.');
    
    // Limpar formulário
    document.getElementById('cadastroForm').reset();
    
    // Redirecionar para página de login (se existir)
    // window.location.href = 'login.html';
    
  } else {
    alert('Erro no cadastro:\n\n' + resultado.erro);
  }

  return false;
}

// ===== FUNÇÕES DA PÁGINA DE LOGIN =====

// Função para validar e processar login
function processarLogin(e) {
  e.preventDefault();
  
  const email = document.getElementById('login-email').value.trim();
  const senha = document.getElementById('login-senha').value;
  
  if (!email || !senha) {
    alert('Por favor, preencha todos os campos.');
    return false;
  }
  
  const resultado = gerenciadorUsuarios.autenticarUsuario(email, senha);
  
  if (resultado.sucesso) {
    alert(`Bem-vindo(a), ${resultado.usuario.nome}!`);
    
    // Aqui você pode salvar informações da sessão
    // Em uma aplicação real, você salvaria um token JWT ou session
    sessionStorage.setItem('usuarioLogado', JSON.stringify(resultado.usuario));
    
    // Redirecionar para dashboard ou página principal
    // window.location.href = 'dashboard.html';
    
  } else {
    alert('Erro no login:\n\n' + resultado.erro);
  }
  
  return false;
}

// ===== INICIALIZAÇÃO =====

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
  
  // ===== CONFIGURAÇÃO DA PÁGINA DE CADASTRO =====
  const cadastroForm = document.getElementById('cadastroForm');
  if (cadastroForm) {
    cadastroForm.addEventListener('submit', validarFormulario);
    
    // Configurações dos campos de cadastro
    const cpfInput = document.getElementById('cadastro-cpf');
    const cnhInput = document.getElementById('cadastro-cnh');
    const nascimentoInput = document.getElementById('cadastro-nascimento');
    const senhaInput = document.getElementById('cadastro-senha');
    const confirmaSenhaInput = document.getElementById('cadastro-confirma-senha');
    
    // Campo CPF
    if (cpfInput) {
      if (!document.getElementById('cpf-feedback')) {
        const feedbackDiv = document.createElement('div');
        feedbackDiv.id = 'cpf-feedback';
        feedbackDiv.className = 'cpf-feedback mt-1 text-start';
        cpfInput.parentNode.appendChild(feedbackDiv);
      }
      
      cpfInput.addEventListener('input', function() {
        const cursorPos = this.selectionStart;
        const valorOriginal = this.value;
        const valorFormatado = formatarCPF(valorOriginal);
        const diferencaLength = valorFormatado.length - valorOriginal.length;
        
        this.value = valorFormatado;
        
        if (cursorPos + diferencaLength > 0) {
          this.setSelectionRange(cursorPos + diferencaLength, cursorPos + diferencaLength);
        }
        
        atualizarFeedbackCPF(this.value);
      });
      
      cpfInput.addEventListener('blur', function() {
        atualizarFeedbackCPF(this.value);
      });
    }
    
    // Campo CNH
    if (cnhInput) {
      if (!document.getElementById('cnh-feedback')) {
        const feedbackDiv = document.createElement('div');
        feedbackDiv.id = 'cnh-feedback';
        feedbackDiv.className = 'cnh-feedback mt-1 text-start';
        cnhInput.parentNode.appendChild(feedbackDiv);
      }
      
      cnhInput.addEventListener('input', function() {
        atualizarFeedbackCNH(this.value);
      });
    }
    
    // Campo Data de Nascimento
    if (nascimentoInput) {
      if (!document.getElementById('nascimento-feedback')) {
        const feedbackDiv = document.createElement('div');
        feedbackDiv.id = 'nascimento-feedback';
        feedbackDiv.className = 'nascimento-feedback mt-1 text-start';
        nascimentoInput.parentNode.appendChild(feedbackDiv);
      }
      
      const hoje = new Date();
      const anoAtual = hoje.getFullYear();
      const mesAtual = String(hoje.getMonth() + 1).padStart(2, '0');
      const diaAtual = String(hoje.getDate()).padStart(2, '0');
      nascimentoInput.setAttribute('max', `${anoAtual}-${mesAtual}-${diaAtual}`);
      nascimentoInput.setAttribute('min', `${anoAtual - 120}-${mesAtual}-${diaAtual}`);
      
      nascimentoInput.addEventListener('change', function() {
        validarDataNascimento(this.value);
      });
      
      nascimentoInput.addEventListener('blur', function() {
        validarDataNascimento(this.value);
      });
    }
    
    // Campo Senha
    if (senhaInput) {
      if (!document.getElementById('senha-feedback')) {
        const feedbackDiv = document.createElement('div');
        feedbackDiv.id = 'senha-feedback';
        feedbackDiv.className = 'senha-feedback';
        senhaInput.parentNode.appendChild(feedbackDiv);
      }
      
      senhaInput.addEventListener('input', function() {
        atualizarIndicadorForca(this.value);
      });
    }
    
    // Campo Confirmação de Senha
    if (confirmaSenhaInput) {
      confirmaSenhaInput.addEventListener('input', function() {
        const senha = document.getElementById('cadastro-senha').value;
        const confirmaSenha = this.value;
        
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
  
  // ===== CONFIGURAÇÃO DA PÁGINA DE LOGIN =====
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', processarLogin);
  }
  
});

// ===== FUNÇÕES AUXILIARES PARA GERENCIAMENTO DE SESSÃO =====

// Verificar se usuário está logado
function usuarioEstaLogado() {
  return sessionStorage.getItem('usuarioLogado') !== null;
}

// Obter dados do usuário logado
function obterUsuarioLogado() {
  const dadosUsuario = sessionStorage.getItem('usuarioLogado');
  return dadosUsuario ? JSON.parse(dadosUsuario) : null;
}

// Fazer logout
function logout() {
  sessionStorage.removeItem('usuarioLogado');
  // window.location.href = 'login.html';
}

// Exportar funções principais para uso global
window.gerenciadorUsuarios = gerenciadorUsuarios;
window.usuarioEstaLogado = usuarioEstaLogado;
window.obterUsuarioLogado = obterUsuarioLogado;
window.logout = logout;