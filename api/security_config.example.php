<?php
/**
 * Security Configuration Example / Exemplo de Configuração de Segurança
 * 
 * IMPORTANTE: Copie este arquivo para security_config.php e configure com seus dados reais
 * IMPORTANT: Copy this file to security_config.php and configure with your real data
 * 
 * NÃO FAÇA COMMIT do arquivo security_config.php - ele deve permanecer no .gitignore
 * DO NOT COMMIT the security_config.php file - it should remain in .gitignore
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Prevent direct access / Prevenir acesso direto
if (!defined('VANTRACING_API')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

/**
 * Security configuration array / Array de configuração de segurança
 */
return [
    // Rate Limiting Configuration / Configuração de Rate Limiting
    'rate_limiting' => [
        'enabled' => true,
        'default_limit' => 60,           // Requests per minute / Requisições por minuto
        'login_limit' => 5,              // Login attempts per minute / Tentativas de login por minuto
        'api_limit' => 120,              // API requests per minute / Requisições da API por minuto
        'strict_mode' => false,          // Strict mode (block instead of warn) / Modo estrito
        'whitelist_ips' => [             // IPs com rate limit relaxado / IPs with relaxed rate limit
            '127.0.0.1',
            '::1'
        ]
    ],

    // CSRF Protection / Proteção CSRF
    'csrf' => [
        'enabled' => true,
        'token_lifetime' => 3600,        // Token lifetime in seconds / Tempo de vida do token em segundos
        'regenerate_on_use' => false,    // Regenerate token after each use / Regenerar token após uso
        'strict_referer' => true         // Check referer header / Verificar header referer
    ],

    // Input Validation / Validação de Entrada
    'input_validation' => [
        'enabled' => true,
        'max_input_length' => 10000,     // Maximum input length / Comprimento máximo de entrada
        'allow_html' => false,           // Allow HTML in inputs / Permitir HTML nas entradas
        'xss_protection' => true,        // XSS protection / Proteção XSS
        'sql_injection_protection' => true // SQL injection protection / Proteção contra SQL injection
    ],

    // Session Security / Segurança de Sessão
    'session' => [
        'secure' => false,               // Requires HTTPS (set to true in production) / Requer HTTPS
        'httponly' => true,              // HTTPOnly cookies / Cookies HTTPOnly
        'samesite' => 'Strict',          // SameSite policy / Política SameSite
        'regenerate_id' => true,         // Regenerate session ID / Regenerar ID da sessão
        'timeout' => 1800,               // Session timeout in seconds / Timeout da sessão em segundos
        'max_lifetime' => 86400          // Maximum session lifetime / Tempo máximo de vida da sessão
    ],

    // Password Security / Segurança de Senhas
    'password' => [
        'min_length' => 8,               // Minimum password length / Comprimento mínimo da senha
        'require_uppercase' => true,     // Require uppercase letters / Requer letras maiúsculas
        'require_lowercase' => true,     // Require lowercase letters / Requer letras minúsculas
        'require_numbers' => true,       // Require numbers / Requer números
        'require_special' => false,      // Require special characters / Requer caracteres especiais
        'hash_algorithm' => PASSWORD_DEFAULT // Password hashing algorithm / Algoritmo de hash
    ],

    // Threat Detection / Detecção de Ameaças
    'threat_detection' => [
        'enabled' => true,
        'blocked_patterns' => [          // Padrões suspeitos / Suspicious patterns
            '/union.*select/i',          // SQL Injection
            '/<script.*>/i',             // XSS
            '/javascript:/i',            // XSS
            '/expression\s*\(/i',        // CSS Expression
            '/import\s*\(/i'             // JavaScript import
        ],
        'blocked_user_agents' => [       // User agents bloqueados / Blocked user agents
            'sqlmap',
            'nikto',
            'nessus',
            'w3af'
        ],
        'max_failed_attempts' => 5,      // Max failed login attempts / Máx tentativas de login falhadas
        'lockout_duration' => 900        // Account lockout duration in seconds / Duração do bloqueio
    ],

    // File Upload Security / Segurança de Upload de Arquivos
    'file_upload' => [
        'enabled' => true,
        'max_file_size' => 5242880,      // 5MB in bytes / 5MB em bytes
        'allowed_extensions' => [        // Extensões permitidas / Allowed extensions
            'jpg', 'jpeg', 'png', 'gif', 'webp'
        ],
        'allowed_mime_types' => [        // MIME types permitidos / Allowed MIME types
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ],
        'scan_for_malware' => false,     // Enable malware scanning / Habilitar scan de malware
        'quarantine_suspicious' => true  // Quarantine suspicious files / Quarentena de arquivos suspeitos
    ],

    // API Security / Segurança da API
    'api' => [
        'require_authentication' => true,  // Require auth for API / Requer autenticação para API
        'allowed_origins' => [             // CORS allowed origins / Origens permitidas pelo CORS
            'http://localhost',
            'http://127.0.0.1',
            'https://seudominio.com'       // Substitua pelo seu domínio real / Replace with your real domain
        ],
        'require_https' => false,          // Require HTTPS (set to true in production) / Requer HTTPS
        'api_key_required' => false,       // Require API key / Requer chave da API
        'rate_limit_per_key' => 1000       // Rate limit per API key / Rate limit por chave da API
    ],

    // Logging and Monitoring / Log e Monitoramento
    'logging' => [
        'log_failed_attempts' => true,     // Log failed login attempts / Log tentativas de login falhadas
        'log_suspicious_activity' => true, // Log suspicious activity / Log atividade suspeita
        'log_successful_logins' => false,  // Log successful logins / Log logins bem-sucedidos
        'log_file_uploads' => true,        // Log file uploads / Log uploads de arquivos
        'log_api_requests' => false,       // Log API requests / Log requisições da API
        'sensitive_data_masking' => true   // Mask sensitive data in logs / Mascarar dados sensíveis nos logs
    ],

    // Security Headers / Cabeçalhos de Segurança
    'security_headers' => [
        'enabled' => true,
        'hsts' => false,                   // HTTP Strict Transport Security (enable with HTTPS)
        'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net;",
        'x_frame_options' => 'DENY',       // X-Frame-Options
        'x_content_type_options' => 'nosniff', // X-Content-Type-Options
        'x_xss_protection' => '1; mode=block'  // X-XSS-Protection
    ],

    // Environment Configuration / Configuração do Ambiente
    'environment' => [
        'debug_mode' => false,             // Debug mode (set to false in production) / Modo debug
        'error_reporting' => false,        // Error reporting (set to false in production) / Relatório de erros
        'display_errors' => false,         // Display errors (set to false in production) / Exibir erros
        'log_errors' => true,              // Log errors / Log de erros
        'maintenance_mode' => false        // Maintenance mode / Modo manutenção
    ],

    // Encryption / Criptografia
    'encryption' => [
        'algorithm' => 'AES-256-CBC',      // Encryption algorithm / Algoritmo de criptografia
        'key' => 'sua_chave_de_32_caracteres_aqui!!',  // Encryption key (32 characters) / Chave de criptografia
        'iv_length' => 16                  // IV length / Comprimento do IV
    ]
];
?>