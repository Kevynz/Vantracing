/**
 * VanTracing - Internationalization Module
 * 
 * This module provides automatic language detection and translation support
 * for the VanTracing application. It detects the user's language based on
 * browser settings or geolocation and applies the appropriate translations.
 * 
 * Módulo de Internacionalização do VanTracing
 * 
 * Este módulo fornece detecção automática de idioma e suporte a tradução
 * para a aplicação VanTracing. Detecta o idioma do usuário com base nas
 * configurações do navegador ou geolocalização e aplica as traduções apropriadas.
 */

// ---------------------------------------------------------------------------------
// TRANSLATION DICTIONARIES / DICIONÁRIOS DE TRADUÇÃO
// ---------------------------------------------------------------------------------

const translations = {
    pt: {
        // Login Page / Página de Login
        login: "Login",
        email: "E-mail",
        password: "Senha",
        enterEmail: "Digite seu e-mail",
        enterPassword: "Digite sua senha",
        loginButton: "ENTRAR",
        noAccount: "Ainda não tem uma conta?",
        driver: "Motorista",
        guardian: "Responsável",
        forgotPassword: "Esqueceu a senha?",
        changePassword: "Trocar senha",
        
        // Registration Page / Página de Cadastro
        register: "Cadastro",
        confirmPassword: "Confirmar Senha",
        confirmPasswordPlaceholder: "Confirme sua senha",
        chooseRole: "Escolha seu papel:",
        registerButton: "CADASTRAR",
        alreadyHaveAccount: "Já tem uma conta?",
        login: "Logar",
        
        // Dashboard / Painel
        dashboard: "Painel de Controle",
        profile: "Perfil",
        children: "Crianças",
        routes: "Rotas",
        realTimeTracking: "Rastreamento em Tempo Real",
        routeHistory: "Histórico de Rotas",
        logout: "Sair",
        
        // Profile / Perfil
        myProfile: "Meu Perfil",
        name: "Nome",
        phone: "Telefone",
        address: "Endereço",
        updateProfile: "Atualizar Perfil",
        deleteAccount: "Excluir Conta",
        
        // Children / Crianças
        addChild: "Adicionar Criança",
        childName: "Nome da Criança",
        childAge: "Idade",
        school: "Escola",
        save: "Salvar",
        cancel: "Cancelar",
        edit: "Editar",
        delete: "Excluir",
        
        // Routes / Rotas
        startRoute: "Iniciar Rota",
        endRoute: "Finalizar Rota",
        currentRoute: "Rota Atual",
        noActiveRoute: "Nenhuma rota ativa",
        
        // Messages / Mensagens
        success: "Sucesso!",
        error: "Erro!",
        loading: "Carregando...",
        confirm: "Confirmar",
        confirmDelete: "Tem certeza que deseja excluir?",
        
        // Validation / Validação
        requiredField: "Campo obrigatório",
        invalidEmail: "E-mail inválido",
        passwordMismatch: "As senhas não coincidem",
        weakPassword: "Senha fraca",
        mediumPassword: "Senha média",
        strongPassword: "Senha forte",
        
        // Password Reset / Redefinição de Senha
        resetPassword: "Redefinir Senha",
        sendResetLink: "Enviar Link de Redefinição",
        newPassword: "Nova Senha",
        resetSuccess: "Senha redefinida com sucesso!",
        
        // Common / Comum
        yes: "Sim",
        no: "Não",
        ok: "OK",
        close: "Fechar",
        back: "Voltar",
        next: "Próximo",
        previous: "Anterior",
        search: "Buscar",
        filter: "Filtrar",
        settings: "Configurações"
    },
    
    en: {
        // Login Page
        login: "Login",
        email: "Email",
        password: "Password",
        enterEmail: "Enter your email",
        enterPassword: "Enter your password",
        loginButton: "LOGIN",
        noAccount: "Don't have an account yet?",
        driver: "Driver",
        guardian: "Guardian",
        forgotPassword: "Forgot password?",
        changePassword: "Change password",
        
        // Registration Page
        register: "Register",
        confirmPassword: "Confirm Password",
        confirmPasswordPlaceholder: "Confirm your password",
        chooseRole: "Choose your role:",
        registerButton: "REGISTER",
        alreadyHaveAccount: "Already have an account?",
        login: "Login",
        
        // Dashboard
        dashboard: "Dashboard",
        profile: "Profile",
        children: "Children",
        routes: "Routes",
        realTimeTracking: "Real-Time Tracking",
        routeHistory: "Route History",
        logout: "Logout",
        
        // Profile
        myProfile: "My Profile",
        name: "Name",
        phone: "Phone",
        address: "Address",
        updateProfile: "Update Profile",
        deleteAccount: "Delete Account",
        
        // Children
        addChild: "Add Child",
        childName: "Child's Name",
        childAge: "Age",
        school: "School",
        save: "Save",
        cancel: "Cancel",
        edit: "Edit",
        delete: "Delete",
        
        // Routes
        startRoute: "Start Route",
        endRoute: "End Route",
        currentRoute: "Current Route",
        noActiveRoute: "No active route",
        
        // Messages
        success: "Success!",
        error: "Error!",
        loading: "Loading...",
        confirm: "Confirm",
        confirmDelete: "Are you sure you want to delete?",
        
        // Validation
        requiredField: "Required field",
        invalidEmail: "Invalid email",
        passwordMismatch: "Passwords do not match",
        weakPassword: "Weak password",
        mediumPassword: "Medium password",
        strongPassword: "Strong password",
        
        // Password Reset
        resetPassword: "Reset Password",
        sendResetLink: "Send Reset Link",
        newPassword: "New Password",
        resetSuccess: "Password reset successfully!",
        
        // Common
        yes: "Yes",
        no: "No",
        ok: "OK",
        close: "Close",
        back: "Back",
        next: "Next",
        previous: "Previous",
        search: "Search",
        filter: "Filter",
        settings: "Settings"
    }
};

// ---------------------------------------------------------------------------------
// LANGUAGE DETECTION AND MANAGEMENT / DETECÇÃO E GERENCIAMENTO DE IDIOMA
// ---------------------------------------------------------------------------------

/**
 * Detects the user's preferred language based on browser settings or saved preference.
 * Priority: 1) localStorage, 2) navigator.language, 3) default to 'pt'
 * 
 * Detecta o idioma preferido do usuário com base nas configurações do navegador ou preferência salva.
 * Prioridade: 1) localStorage, 2) navigator.language, 3) padrão 'pt'
 * 
 * @returns {string} Language code ('pt' or 'en')
 */
function detectLanguage() {
    // Check saved preference / Verifica preferência salva
    const savedLang = localStorage.getItem('language');
    if (savedLang && translations[savedLang]) {
        return savedLang;
    }
    
    // Check browser language / Verifica idioma do navegador
    const browserLang = navigator.language || navigator.userLanguage;
    const langCode = browserLang.split('-')[0]; // Get language without region (e.g., 'pt' from 'pt-BR')
    
    // Return detected language if supported, otherwise default to Portuguese
    // Retorna idioma detectado se suportado, caso contrário padrão Português
    return translations[langCode] ? langCode : 'pt';
}

/**
 * Sets the application language and saves the preference.
 * Aplica o idioma da aplicação e salva a preferência.
 * 
 * @param {string} lang - Language code ('pt' or 'en')
 */
function setLanguage(lang) {
    if (!translations[lang]) {
        console.warn(`Language '${lang}' not supported. Defaulting to 'pt'.`);
        lang = 'pt';
    }
    
    localStorage.setItem('language', lang);
    currentLanguage = lang;
    
    // Update HTML lang attribute / Atualiza atributo lang do HTML
    document.documentElement.lang = lang;
    
    // Apply translations to current page / Aplica traduções na página atual
    applyTranslations();
}

/**
 * Gets a translation for a given key in the current language.
 * Obtém uma tradução para uma chave no idioma atual.
 * 
 * @param {string} key - Translation key
 * @returns {string} Translated text or key if not found
 */
function t(key) {
    const lang = currentLanguage || detectLanguage();
    return translations[lang][key] || key;
}

/**
 * Applies translations to all elements with data-i18n attribute.
 * Aplica traduções a todos os elementos com atributo data-i18n.
 */
function applyTranslations() {
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        const translation = t(key);
        
        // Check if it's a placeholder / Verifica se é um placeholder
        if (element.hasAttribute('placeholder')) {
            element.setAttribute('placeholder', translation);
        } else {
            element.textContent = translation;
        }
    });
    
    // Update title if it has translation key / Atualiza título se tiver chave de tradução
    const titleElement = document.querySelector('title[data-i18n]');
    if (titleElement) {
        document.title = t(titleElement.getAttribute('data-i18n'));
    }
}

/**
 * Attempts to detect user's country via geolocation API and adjust language.
 * This is an optional enhancement that requires user permission.
 * 
 * Tenta detectar o país do usuário via API de geolocalização e ajustar idioma.
 * Este é um aprimoramento opcional que requer permissão do usuário.
 */
async function detectLanguageByGeolocation() {
    // Only attempt if no language is already saved
    // Apenas tenta se nenhum idioma já estiver salvo
    if (localStorage.getItem('language')) {
        return;
    }
    
    try {
        // Request geolocation / Solicita geolocalização
        const position = await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, {
                timeout: 5000,
                maximumAge: 3600000 // Cache for 1 hour / Cache por 1 hora
            });
        });
        
        const { latitude, longitude } = position.coords;
        
        // Use a reverse geocoding service to get country
        // You would need to implement this with a service like OpenCage, Google Maps, etc.
        // For now, we'll just use browser language as fallback
        // Você precisaria implementar isso com um serviço como OpenCage, Google Maps, etc.
        // Por enquanto, usamos o idioma do navegador como fallback
        
        console.log('Geolocation detected:', latitude, longitude);
        
    } catch (error) {
        console.log('Geolocation not available or denied:', error.message);
    }
}

/**
 * Creates a language switcher button and adds it to the page.
 * Cria um botão de troca de idioma e adiciona à página.
 */
function createLanguageSwitcher() {
    const switcher = document.createElement('button');
    switcher.id = 'language-switcher';
    switcher.className = 'btn btn-sm btn-outline-secondary language-switcher';
    switcher.innerHTML = currentLanguage === 'pt' ? '🇺🇸 EN' : '🇧🇷 PT';
    switcher.title = currentLanguage === 'pt' ? 'Switch to English' : 'Mudar para Português';
    
    switcher.addEventListener('click', () => {
        const newLang = currentLanguage === 'pt' ? 'en' : 'pt';
        setLanguage(newLang);
        switcher.innerHTML = newLang === 'pt' ? '🇺🇸 EN' : '🇧🇷 PT';
        switcher.title = newLang === 'pt' ? 'Switch to English' : 'Mudar para Português';
    });
    
    // Try to add to navbar or body / Tenta adicionar ao navbar ou body
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        navbar.appendChild(switcher);
    } else {
        document.body.appendChild(switcher);
    }
}

// ---------------------------------------------------------------------------------
// INITIALIZATION / INICIALIZAÇÃO
// ---------------------------------------------------------------------------------

// Global variable to store current language / Variável global para armazenar idioma atual
let currentLanguage = detectLanguage();

/**
 * Initialize the internationalization system on page load.
 * Inicializa o sistema de internacionalização ao carregar a página.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Set initial language / Define idioma inicial
    setLanguage(currentLanguage);
    
    // Create language switcher / Cria trocador de idioma
    createLanguageSwitcher();
    
    // Optional: Try geolocation detection / Opcional: Tenta detecção por geolocalização
    // detectLanguageByGeolocation();
});

// Export for use in other modules / Exporta para uso em outros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { t, setLanguage, detectLanguage, applyTranslations };
}
