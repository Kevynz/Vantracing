# Internationalization (i18n) Quick Reference
# Referência Rápida de Internacionalização (i18n)

## Overview / Visão Geral

VanTracing uses a custom i18n system that automatically detects and applies the user's preferred language.
VanTracing usa um sistema i18n personalizado que detecta e aplica automaticamente o idioma preferido do usuário.

---

## 🌍 Supported Languages / Idiomas Suportados

- 🇧🇷 **Portuguese (pt)** - Português
- 🇺🇸 **English (en)** - Inglês

---

## 📖 How to Use / Como Usar

### In HTML Files / Em Arquivos HTML

Add the `data-i18n` attribute to any element you want to translate:
Adicione o atributo `data-i18n` a qualquer elemento que você deseja traduzir:

```html
<!-- For text content / Para conteúdo de texto -->
<h1 data-i18n="login">Login</h1>
<p data-i18n="welcome">Bem-vindo</p>

<!-- For placeholders / Para placeholders -->
<input type="text" data-i18n="enterName" placeholder="Digite seu nome">

<!-- For buttons / Para botões -->
<button data-i18n="submit">Enviar</button>
```

### Required Scripts / Scripts Necessários

Include these scripts in your HTML:
Inclua esses scripts no seu HTML:

```html
<!-- Internationalization Module / Módulo de internacionalização -->
<script src="JavaScript/i18n.js"></script>

<!-- Include BEFORE your other scripts -->
<!-- Inclua ANTES dos seus outros scripts -->
```

### Required Styles / Estilos Necessários

```html
<!-- Internationalization Styles / Estilos de internacionalização -->
<link rel="stylesheet" href="css/i18n.css">
```

---

## 🔧 Adding New Translations / Adicionando Novas Traduções

### Step 1: Edit i18n.js / Passo 1: Edite i18n.js

Open `JavaScript/i18n.js` and add your translation key to both language objects:

```javascript
const translations = {
    pt: {
        // ... existing translations
        myNewKey: "Meu texto em português"
    },
    
    en: {
        // ... existing translations
        myNewKey: "My text in English"
    }
};
```

### Step 2: Use in HTML / Passo 2: Use no HTML

```html
<div data-i18n="myNewKey">Default text</div>
```

---

## 🎯 Common Use Cases / Casos de Uso Comuns

### Login Form / Formulário de Login

```html
<form id="loginForm">
    <h2 data-i18n="login">Login</h2>
    
    <label data-i18n="email">E-mail</label>
    <input type="email" data-i18n="enterEmail" placeholder="Digite seu e-mail">
    
    <label data-i18n="password">Senha</label>
    <input type="password" data-i18n="enterPassword" placeholder="Digite sua senha">
    
    <button data-i18n="loginButton">ENTRAR</button>
    
    <p>
        <span data-i18n="noAccount">Ainda não tem uma conta?</span>
        <a href="register.html" data-i18n="register">Cadastrar</a>
    </p>
</form>
```

### Navigation Menu / Menu de Navegação

```html
<nav>
    <a href="dashboard.html" data-i18n="dashboard">Painel</a>
    <a href="profile.html" data-i18n="profile">Perfil</a>
    <a href="routes.html" data-i18n="routes">Rotas</a>
    <a href="#" data-i18n="logout">Sair</a>
</nav>
```

### Messages / Mensagens

```html
<div class="alert alert-success">
    <span data-i18n="success">Sucesso!</span>
    <span data-i18n="dataSaved">Dados salvos com sucesso</span>
</div>
```

---

## 💻 Programmatic Usage / Uso Programático

### Get Translation in JavaScript / Obter Tradução em JavaScript

```javascript
// Get translation for a key / Obter tradução para uma chave
const loginText = t('login');
console.log(loginText); // Output: "Login" or "Login" (depending on language)

// Use in alerts / Usar em alertas
alert(t('success'));

// Use in dynamic content / Usar em conteúdo dinâmico
document.getElementById('message').textContent = t('welcome');
```

### Change Language Programmatically / Mudar Idioma Programaticamente

```javascript
// Set language to English / Definir idioma para inglês
setLanguage('en');

// Set language to Portuguese / Definir idioma para português
setLanguage('pt');

// Get current language / Obter idioma atual
console.log(currentLanguage); // 'pt' or 'en'
```

### Detect Language / Detectar Idioma

```javascript
// Automatic detection on page load / Detecção automática ao carregar página
const detectedLang = detectLanguage();
console.log(detectedLang); // 'pt' or 'en'
```

---

## 🎨 Language Switcher / Trocador de Idioma

The language switcher button is automatically created and added to your page.
O botão de troca de idioma é criado e adicionado automaticamente à sua página.

### Customize Position / Personalizar Posição

Edit `css/i18n.css`:

```css
.language-switcher {
    position: fixed;
    top: 70px;     /* Adjust this / Ajuste isso */
    right: 20px;   /* Adjust this / Ajuste isso */
}
```

---

## 📋 Available Translation Keys / Chaves de Tradução Disponíveis

### Authentication / Autenticação
- `login` - Login
- `email` - E-mail/Email
- `password` - Senha/Password
- `enterEmail` - Digite seu e-mail/Enter your email
- `enterPassword` - Digite sua senha/Enter your password
- `loginButton` - ENTRAR/LOGIN
- `forgotPassword` - Esqueceu a senha?/Forgot password?
- `changePassword` - Trocar senha/Change password

### Registration / Cadastro
- `register` - Cadastro/Register
- `confirmPassword` - Confirmar Senha/Confirm Password
- `chooseRole` - Escolha seu papel/Choose your role
- `registerButton` - CADASTRAR/REGISTER
- `driver` - Motorista/Driver
- `guardian` - Responsável/Guardian

### Dashboard / Painel
- `dashboard` - Painel de Controle/Dashboard
- `profile` - Perfil/Profile
- `children` - Crianças/Children
- `routes` - Rotas/Routes
- `logout` - Sair/Logout

### Common / Comum
- `success` - Sucesso!/Success!
- `error` - Erro!/Error!
- `loading` - Carregando.../Loading...
- `save` - Salvar/Save
- `cancel` - Cancelar/Cancel
- `delete` - Excluir/Delete
- `edit` - Editar/Edit
- `confirm` - Confirmar/Confirm
- `yes` - Sim/Yes
- `no` - Não/No

*For a complete list, see `JavaScript/i18n.js`*
*Para uma lista completa, veja `JavaScript/i18n.js`*

---

## 🔍 Troubleshooting / Solução de Problemas

### Translations Not Appearing / Traduções Não Aparecem

1. ✅ Check if `i18n.js` is loaded before other scripts
2. ✅ Verify `data-i18n` attribute is correctly spelled
3. ✅ Confirm translation key exists in `translations` object
4. ✅ Check browser console for errors

### Language Not Switching / Idioma Não Muda

1. ✅ Clear browser cache and localStorage
2. ✅ Check if `currentLanguage` variable is set
3. ✅ Verify `applyTranslations()` is called after language change

### Wrong Language Detected / Idioma Errado Detectado

The system detects language in this order:
O sistema detecta o idioma nesta ordem:

1. **localStorage** - Saved user preference / Preferência salva do usuário
2. **Browser Language** - `navigator.language` / Idioma do navegador
3. **Default** - Portuguese (pt) / Padrão: Português

To override:
```javascript
setLanguage('en'); // Force English / Forçar inglês
```

---

## 🚀 Best Practices / Melhores Práticas

### 1. Always Add Both Languages / Sempre Adicione Ambos os Idiomas
```javascript
// ✅ Good / Bom
pt: { greeting: "Olá" },
en: { greeting: "Hello" }

// ❌ Bad / Ruim
pt: { greeting: "Olá" }
// Missing English / Faltando inglês
```

### 2. Use Descriptive Keys / Use Chaves Descritivas
```javascript
// ✅ Good / Bom
welcomeMessage: "Bem-vindo ao VanTracing"

// ❌ Bad / Ruim
msg1: "Bem-vindo ao VanTracing"
```

### 3. Keep Consistency / Mantenha Consistência
```javascript
// ✅ Good / Bom
loginButton: "ENTRAR"
registerButton: "CADASTRAR"

// ❌ Bad / Ruim
loginButton: "ENTRAR"
btnRegister: "CADASTRAR"
```

### 4. Test Both Languages / Teste Ambos os Idiomas
Always test your changes in both Portuguese and English.
Sempre teste suas mudanças em português e inglês.

---

## 📞 Need Help? / Precisa de Ajuda?

- 📖 Read the full README.md / Leia o README.md completo
- 🐛 Report issues on GitHub / Reporte problemas no GitHub
- 💬 Join discussions / Participe de discussões

---

**Happy coding! / Boa codificação!** 🚀
