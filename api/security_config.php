<?php
/**
 * Security Configuration / Configuração de Segurança
 * 
 * Central configuration for VanTracing security features
 * Configuração central para recursos de segurança do VanTracing
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
    
    // Environment settings / Configurações de ambiente
    'environment' => [
        'force_https' => getenv('FORCE_HTTPS') !== 'false',
        'debug_mode' => getenv('DEBUG') === 'true',
        'app_env' => getenv('APP_ENV') ?: 'production'
    ],
    
    // Rate limiting settings / Configurações de limitação de taxa
    'rate_limiting' => [
        'enabled' => true,
        'api_requests_per_minute' => 60,
        'login_attempts_per_minute' => 5,
        'registration_attempts_per_hour' => 3,
        'password_reset_attempts_per_hour' => 3,
        'window_minutes' => 1
    ],
    
    // Security headers configuration / Configuração de cabeçalhos de segurança
    'headers' => [
        'hsts' => [
            'enabled' => true,
            'max_age' => 31536000, // 1 year
            'include_subdomains' => true,
            'preload' => true
        ],
        'csp' => [
            'enabled' => true,
            'report_only' => false,
            'directives' => [
                'default-src' => "'self'",
                'script-src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com",
                'style-src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com",
                'font-src' => "'self' https://cdn.jsdelivr.net https://fonts.gstatic.com https://cdnjs.cloudflare.com",
                'img-src' => "'self' data: blob: https:",
                'connect-src' => "'self' https:",
                'media-src' => "'self'",
                'object-src' => "'none'",
                'frame-src' => "'none'",
                'base-uri' => "'self'",
                'form-action' => "'self'"
            ]
        ],
        'frame_options' => 'SAMEORIGIN',
        'content_type_options' => true,
        'xss_protection' => true,
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'geolocation=(self), camera=(), microphone=()'
    ],
    
    // Input validation settings / Configurações de validação de entrada
    'input_validation' => [
        'max_input_length' => 10000,
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
        'max_file_size' => 5242880, // 5MB
        'strip_tags' => true,
        'encode_html' => true
    ],
    
    // Suspicious pattern detection / Detecção de padrões suspeitos
    'threat_detection' => [
        'enabled' => true,
        'patterns' => [
            'script_injection' => '/(<script[^>]*>.*?<\/script>)/is',
            'sql_injection' => '/(\b(?:union|select|insert|delete|update|drop|create|alter|exec|execute)\b)/is',
            'path_traversal' => '/(\.\.\/|\.\.\\\\|%2e%2e%2f|%2e%2e%5c)/i',
            'template_injection' => '/(\$\{.*\}|\{\{.*\}\})/i',
            'code_execution' => '/(eval\s*\(|exec\s*\(|system\s*\(|shell_exec\s*\()/i',
            'dangerous_protocols' => '/(javascript:|vbscript:|data:|file:|ftp:)/i',
            'php_injection' => '/(<\?php|<\?=|\?>)/i',
            'command_injection' => '/(;|\||&|`|\$\(|\${)/i'
        ],
        'block_on_detection' => true,
        'log_incidents' => true
    ],
    
    // Session security / Segurança da sessão
    'session' => [
        'secure_cookies' => true,
        'httponly_cookies' => true,
        'samesite_cookies' => 'Strict',
        'regenerate_id' => true,
        'timeout_minutes' => 30,
        'check_ip' => true,
        'check_user_agent' => false // Can cause issues with mobile apps
    ],
    
    // API security settings / Configurações de segurança da API
    'api' => [
        'require_authentication' => true,
        'csrf_protection' => true,
        'validate_content_type' => true,
        'allowed_origins' => [], // Empty array = same origin only
        'max_request_size' => 1048576, // 1MB
        'timeout_seconds' => 30
    ],
    
    // Logging and monitoring / Registro e monitoramento
    'logging' => [
        'security_log_enabled' => true,
        'security_log_file' => '../logs/security.log',
        'access_log_enabled' => true,
        'access_log_file' => '../logs/access.log',
        'error_log_enabled' => true,
        'log_rotation' => [
            'enabled' => true,
            'max_size' => 10485760, // 10MB
            'max_files' => 5
        ]
    ],
    
    // File protection / Proteção de arquivos
    'file_protection' => [
        'protect_sensitive_files' => true,
        'sensitive_extensions' => ['.env', '.log', '.sql', '.bak', '.config'],
        'sensitive_directories' => ['logs/', 'config/', 'migrations/', 'backups/'],
        'deny_direct_access' => true
    ],
    
    // Database security / Segurança do banco de dados
    'database' => [
        'use_prepared_statements' => true,
        'validate_input' => true,
        'log_queries' => false, // Only enable for debugging
        'connection_timeout' => 30,
        'max_connections' => 10
    ],
    
    // Error handling / Tratamento de erros
    'error_handling' => [
        'hide_system_errors' => true,
        'custom_error_pages' => true,
        'log_errors' => true,
        'display_errors' => false, // Only true in development
        'error_reporting' => E_ALL & ~E_NOTICE
    ],
    
    // Cache security / Segurança do cache
    'cache' => [
        'no_cache_sensitive_pages' => true,
        'sensitive_page_patterns' => ['/api/', '/perfil', '/dashboard', '/admin'],
        'cache_control_headers' => [
            'public_pages' => 'public, max-age=3600',
            'private_pages' => 'private, no-cache, no-store, must-revalidate',
            'api_responses' => 'no-cache, no-store, must-revalidate'
        ]
    ],
    
    // Backup and recovery / Backup e recuperação
    'backup' => [
        'encrypt_backups' => true,
        'backup_retention_days' => 30,
        'secure_backup_location' => '../backups/',
        'verify_backup_integrity' => true
    ]
];
?>