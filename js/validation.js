/**
 * VanTracing - Advanced Form Validation
 * Validação Avançada de Formulários
 * 
 * Enhanced client-side form validation with real-time feedback
 * Validação client-side aprimorada com feedback em tempo real
 * 
 * @package VanTracing
 * @version 2.0
 * @author Kevyn
 */

class FormValidator {
    
    constructor() {
        this.rules = {};
        this.messages = {
            pt: {
                required: 'Este campo é obrigatório',
                email: 'Insira um email válido',
                minLength: 'Mínimo de {min} caracteres',
                maxLength: 'Máximo de {max} caracteres',
                pattern: 'Formato inválido',
                confirm: 'As senhas não coincidem',
                phone: 'Telefone inválido',
                cpf: 'CPF inválido',
                cnh: 'CNH inválida',
                placa: 'Placa do veículo inválida'
            },
            en: {
                required: 'This field is required',
                email: 'Please enter a valid email',
                minLength: 'Minimum {min} characters',
                maxLength: 'Maximum {max} characters',
                pattern: 'Invalid format',
                confirm: 'Passwords do not match',
                phone: 'Invalid phone number',
                cpf: 'Invalid CPF',
                cnh: 'Invalid driver license',
                placa: 'Invalid vehicle plate'
            }
        };
        this.currentLang = window.currentLanguage || 'pt';
    }
    
    /**
     * Initialize form validation for a specific form
     * Inicializar validação para um formulário específico
     */
    init(formSelector) {
        const form = document.querySelector(formSelector);
        if (!form) return;
        
        // Add real-time validation / Adicionar validação em tempo real
        this.addRealTimeValidation(form);
        
        // Add submit validation / Adicionar validação no envio
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
        
        form.classList.add('needs-validation');
    }
    
    /**
     * Add real-time validation to form fields
     * Adicionar validação em tempo real aos campos do formulário
     */
    addRealTimeValidation(form) {
        const fields = form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            // Validate on blur / Validar ao sair do campo
            field.addEventListener('blur', () => {
                this.validateField(field);
            });
            
            // Validate on input for certain types / Validar durante digitação para certos tipos
            if (field.type === 'email' || field.type === 'password') {
                field.addEventListener('input', () => {
                    // Debounce validation / Validação com delay
                    clearTimeout(field.validationTimeout);
                    field.validationTimeout = setTimeout(() => {
                        this.validateField(field);
                    }, 500);
                });
            }
        });
    }
    
    /**
     * Validate individual field
     * Validar campo individual
     */
    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name || field.id;
        const rules = this.getFieldRules(field);
        
        // Remove previous validation classes / Remover classes de validação anteriores
        field.classList.remove('is-valid', 'is-invalid');
        this.clearFieldError(field);
        
        // Validate each rule / Validar cada regra
        for (const rule of rules) {
            const result = this.validateRule(value, rule, field);
            if (!result.valid) {
                this.showFieldError(field, result.message);
                field.classList.add('is-invalid');
                return false;
            }
        }
        
        // Field is valid / Campo é válido
        field.classList.add('is-valid');
        return true;
    }
    
    /**
     * Get validation rules for a field
     * Obter regras de validação para um campo
     */
    getFieldRules(field) {
        const rules = [];
        
        // Required validation / Validação de obrigatório
        if (field.hasAttribute('required') || field.classList.contains('required')) {
            rules.push({ type: 'required' });
        }
        
        // Email validation / Validação de email
        if (field.type === 'email') {
            rules.push({ type: 'email' });
        }
        
        // Length validation / Validação de tamanho
        if (field.hasAttribute('minlength')) {
            rules.push({ 
                type: 'minLength', 
                min: parseInt(field.getAttribute('minlength'))
            });
        }
        
        if (field.hasAttribute('maxlength')) {
            rules.push({ 
                type: 'maxLength', 
                max: parseInt(field.getAttribute('maxlength'))
            });
        }
        
        // Pattern validation / Validação de padrão
        if (field.hasAttribute('pattern')) {
            rules.push({ 
                type: 'pattern', 
                pattern: new RegExp(field.getAttribute('pattern'))
            });
        }
        
        // Custom validations based on data attributes / Validações customizadas baseadas em atributos
        if (field.hasAttribute('data-validation')) {
            const validationType = field.getAttribute('data-validation');
            rules.push({ type: validationType });
        }
        
        // Password confirmation / Confirmação de senha
        if (field.hasAttribute('data-confirm')) {
            const targetField = document.querySelector(field.getAttribute('data-confirm'));
            rules.push({ 
                type: 'confirm', 
                target: targetField
            });
        }
        
        return rules;
    }
    
    /**
     * Validate a single rule
     * Validar uma regra individual
     */
    validateRule(value, rule, field) {
        const messages = this.messages[this.currentLang];
        
        switch (rule.type) {
            case 'required':
                return {
                    valid: value.length > 0,
                    message: messages.required
                };
                
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return {
                    valid: !value || emailRegex.test(value),
                    message: messages.email
                };
                
            case 'minLength':
                return {
                    valid: !value || value.length >= rule.min,
                    message: messages.minLength.replace('{min}', rule.min)
                };
                
            case 'maxLength':
                return {
                    valid: !value || value.length <= rule.max,
                    message: messages.maxLength.replace('{max}', rule.max)
                };
                
            case 'pattern':
                return {
                    valid: !value || rule.pattern.test(value),
                    message: messages.pattern
                };
                
            case 'confirm':
                return {
                    valid: !value || value === rule.target.value,
                    message: messages.confirm
                };
                
            case 'phone':
                const phoneRegex = /^(?:\+55\s?)?(?:\(\d{2}\)\s?|\d{2}\s?)(?:9\s?)?\d{4}[-\s]?\d{4}$/;
                return {
                    valid: !value || phoneRegex.test(value),
                    message: messages.phone
                };
                
            case 'cpf':
                return {
                    valid: !value || this.validateCPF(value),
                    message: messages.cpf
                };
                
            case 'cnh':
                const cnhRegex = /^\d{11}$/;
                return {
                    valid: !value || cnhRegex.test(value.replace(/\D/g, '')),
                    message: messages.cnh
                };
                
            case 'placa':
                const placaRegex = /^[A-Z]{3}[-\s]?\d{4}$|^[A-Z]{3}[-\s]?\d[A-Z]\d{2}$/;
                return {
                    valid: !value || placaRegex.test(value.toUpperCase()),
                    message: messages.placa
                };
                
            default:
                return { valid: true };
        }
    }
    
    /**
     * Validate CPF (Brazilian tax ID)
     * Validar CPF (documento brasileiro)
     */
    validateCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            return false;
        }
        
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cpf.charAt(i)) * (10 - i);
        }
        
        let remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.charAt(9))) return false;
        
        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cpf.charAt(i)) * (11 - i);
        }
        
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        return remainder === parseInt(cpf.charAt(10));
    }
    
    /**
     * Show field error
     * Mostrar erro do campo
     */
    showFieldError(field, message) {
        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.appendChild(errorDiv);
        }
        
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
    
    /**
     * Clear field error
     * Limpar erro do campo
     */
    clearFieldError(field) {
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }
    
    /**
     * Validate entire form
     * Validar formulário inteiro
     */
    validateForm(form) {
        const fields = form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Add input masks for better UX
     * Adicionar máscaras de entrada para melhor UX
     */
    addInputMasks() {
        // CPF mask / Máscara de CPF
        document.querySelectorAll('[data-mask="cpf"]').forEach(field => {
            field.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            });
        });
        
        // Phone mask / Máscara de telefone
        document.querySelectorAll('[data-mask="phone"]').forEach(field => {
            field.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 11) {
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                } else if (value.length >= 7) {
                    value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                } else if (value.length >= 3) {
                    value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
                }
                e.target.value = value;
            });
        });
        
        // Vehicle plate mask / Máscara de placa de veículo
        document.querySelectorAll('[data-mask="placa"]').forEach(field => {
            field.addEventListener('input', (e) => {
                let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                if (value.length >= 4) {
                    value = value.replace(/([A-Z]{3})(\d{1,4})/, '$1-$2');
                }
                e.target.value = value;
            });
        });
    }
    
    /**
     * Update language for validation messages
     * Atualizar idioma para mensagens de validação
     */
    updateLanguage(lang) {
        this.currentLang = lang;
    }
}

// Auto-initialize form validation / Auto-inicializar validação de formulários
document.addEventListener('DOMContentLoaded', function() {
    const validator = new FormValidator();
    
    // Initialize validation for common forms / Inicializar validação para formulários comuns
    const forms = ['#loginForm', '#cadastroForm', '#perfilForm', '#resetForm'];
    forms.forEach(formSelector => {
        if (document.querySelector(formSelector)) {
            validator.init(formSelector);
        }
    });
    
    // Add input masks / Adicionar máscaras de entrada
    validator.addInputMasks();
    
    // Update language when changed / Atualizar idioma quando alterado
    if (window.addEventListener) {
        window.addEventListener('languageChanged', function(e) {
            validator.updateLanguage(e.detail.language);
        });
    }
    
    // Make validator globally available / Tornar validador globalmente disponível
    window.FormValidator = validator;
});