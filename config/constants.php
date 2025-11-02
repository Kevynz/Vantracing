<?php
/**
 * VanTracing - Application Constants and Configuration
 * Constantes e Configurações da Aplicação VanTracing
 * 
 * This file defines all application constants and configuration values
 * Este arquivo define todas as constantes e valores de configuração da aplicação
 * 
 * @package VanTracing
 * @version 2.0
 * @author Kevyn
 */

// Prevent direct access / Prevenir acesso direto
if (!defined('VANTRACING_LOADED')) {
    define('VANTRACING_LOADED', true);
}

// ============================================================================
// APPLICATION CONSTANTS / CONSTANTES DA APLICAÇÃO
// ============================================================================

// Application Information / Informações da Aplicação
define('APP_NAME', getenv('APP_NAME') ?: 'VanTracing');
define('APP_VERSION', getenv('APP_VERSION') ?: '2.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/VanTracing');

// Database Constants / Constantes do Banco de Dados
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'vantracing_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Security Constants / Constantes de Segurança
define('PASSWORD_MIN_LENGTH', (int)(getenv('PASSWORD_MIN_LENGTH') ?: 8));
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 7200));
define('CSRF_TOKEN_NAME', '_token');
define('RATE_LIMIT_LOGIN', (int)(getenv('RATE_LIMIT_LOGIN') ?: 5));
define('RATE_LIMIT_API', (int)(getenv('RATE_LIMIT_API') ?: 60));

// File Upload Constants / Constantes de Upload de Arquivos
define('MAX_UPLOAD_SIZE', (int)(getenv('MAX_UPLOAD_SIZE') ?: 5242880)); // 5MB
define('UPLOAD_PATH', getenv('UPLOAD_PATH') ?: 'uploads/');
define('AVATAR_PATH', getenv('AVATAR_PATH') ?: 'uploads/fotos_perfil/');
define('DOCUMENTS_PATH', getenv('DOCUMENTS_PATH') ?: 'uploads/documents/');

// Allowed file types / Tipos de arquivo permitidos
define('ALLOWED_IMAGE_TYPES', explode(',', getenv('ALLOWED_IMAGE_TYPES') ?: 'jpg,jpeg,png,gif,webp'));
define('ALLOWED_DOCUMENT_TYPES', explode(',', getenv('ALLOWED_DOCUMENT_TYPES') ?: 'pdf,doc,docx'));

// Localization Constants / Constantes de Localização
define('DEFAULT_LOCALE', getenv('DEFAULT_LOCALE') ?: 'pt');
define('FALLBACK_LOCALE', getenv('FALLBACK_LOCALE') ?: 'pt');
define('AVAILABLE_LOCALES', explode(',', getenv('AVAILABLE_LOCALES') ?: 'pt,en'));
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo');

// API Constants / Constantes da API
define('API_PREFIX', getenv('API_PREFIX') ?: 'api');
define('API_VERSION', getenv('API_VERSION') ?: 'v1');

// Cache Constants / Constantes de Cache
define('CACHE_ENABLED', getenv('CACHE_DRIVER') !== 'none');
define('CACHE_TTL', (int)(getenv('CACHE_TTL') ?: 3600));
define('CACHE_PREFIX', getenv('CACHE_PREFIX') ?: 'vantracing_');

// Feature Flags / Flags de Funcionalidades
define('FEATURE_REGISTRATION', getenv('FEATURE_REGISTRATION') === 'true');
define('FEATURE_PASSWORD_RESET', getenv('FEATURE_PASSWORD_RESET') === 'true');
define('FEATURE_EMAIL_VERIFICATION', getenv('FEATURE_EMAIL_VERIFICATION') === 'true');
define('FEATURE_TWO_FACTOR_AUTH', getenv('FEATURE_TWO_FACTOR_AUTH') === 'true');
define('FEATURE_LOCATION_TRACKING', getenv('FEATURE_LOCATION_TRACKING') !== 'false');
define('FEATURE_NOTIFICATIONS', getenv('FEATURE_NOTIFICATIONS') !== 'false');
define('FEATURE_CHAT', getenv('FEATURE_CHAT') !== 'false');

// ============================================================================
// PATH CONSTANTS / CONSTANTES DE CAMINHO
// ============================================================================

// Base paths / Caminhos base
define('ROOT_PATH', dirname(__DIR__) . '/');
define('API_PATH', ROOT_PATH . 'api/');
define('PAGES_PATH', ROOT_PATH . 'pages/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('CLASSES_PATH', ROOT_PATH . 'classes/');
define('LOGS_PATH', ROOT_PATH . 'logs/');

// Asset paths / Caminhos de recursos
define('CSS_PATH', 'css/');
define('JS_PATH', 'js/');
define('IMG_PATH', 'img/');
define('ASSETS_PATH', 'assets/');

// ============================================================================
// ERROR CONSTANTS / CONSTANTES DE ERRO
// ============================================================================

// HTTP Status Codes / Códigos de Status HTTP
define('HTTP_OK', 200);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_CONFLICT', 409);
define('HTTP_UNPROCESSABLE_ENTITY', 422);
define('HTTP_TOO_MANY_REQUESTS', 429);
define('HTTP_INTERNAL_SERVER_ERROR', 500);

// Error Messages / Mensagens de Erro
define('ERROR_INVALID_CREDENTIALS', 'Credenciais inválidas');
define('ERROR_ACCESS_DENIED', 'Acesso negado');
define('ERROR_NOT_FOUND', 'Recurso não encontrado');
define('ERROR_VALIDATION_FAILED', 'Falha na validação');
define('ERROR_RATE_LIMIT_EXCEEDED', 'Limite de tentativas excedido');
define('ERROR_INTERNAL', 'Erro interno do servidor');

// ============================================================================
// USER ROLES / FUNÇÕES DO USUÁRIO
// ============================================================================

define('ROLE_ADMIN', 'admin');
define('ROLE_DRIVER', 'motorista');
define('ROLE_GUARDIAN', 'responsavel');

// Valid roles array / Array de funções válidas
define('VALID_ROLES', [ROLE_ADMIN, ROLE_DRIVER, ROLE_GUARDIAN]);

// ============================================================================
// NOTIFICATION CONSTANTS / CONSTANTES DE NOTIFICAÇÃO
// ============================================================================

define('NOTIFICATION_SUCCESS', 'success');
define('NOTIFICATION_ERROR', 'error');
define('NOTIFICATION_WARNING', 'warning');
define('NOTIFICATION_INFO', 'info');

// ============================================================================
// UTILITY FUNCTIONS / FUNÇÕES UTILITÁRIAS
// ============================================================================

/**
 * Get application configuration value
 * Obter valor de configuração da aplicação
 * 
 * @param string $key Configuration key / Chave de configuração
 * @param mixed $default Default value / Valor padrão
 * @return mixed
 */
function config($key, $default = null) {
    static $config = [];
    
    if (empty($config)) {
        $config = [
            'app.name' => APP_NAME,
            'app.version' => APP_VERSION,
            'app.env' => APP_ENV,
            'app.debug' => APP_DEBUG,
            'app.url' => APP_URL,
            'app.timezone' => APP_TIMEZONE,
            
            'db.host' => DB_HOST,
            'db.name' => DB_NAME,
            'db.user' => DB_USER,
            'db.charset' => DB_CHARSET,
            
            'security.password_min_length' => PASSWORD_MIN_LENGTH,
            'security.session_lifetime' => SESSION_LIFETIME,
            'security.rate_limit_login' => RATE_LIMIT_LOGIN,
            'security.rate_limit_api' => RATE_LIMIT_API,
            
            'upload.max_size' => MAX_UPLOAD_SIZE,
            'upload.path' => UPLOAD_PATH,
            'upload.avatar_path' => AVATAR_PATH,
            'upload.documents_path' => DOCUMENTS_PATH,
            
            'locale.default' => DEFAULT_LOCALE,
            'locale.fallback' => FALLBACK_LOCALE,
            'locale.available' => AVAILABLE_LOCALES,
            
            'features.registration' => FEATURE_REGISTRATION,
            'features.password_reset' => FEATURE_PASSWORD_RESET,
            'features.location_tracking' => FEATURE_LOCATION_TRACKING,
            'features.notifications' => FEATURE_NOTIFICATIONS,
            'features.chat' => FEATURE_CHAT,
        ];
    }
    
    return $config[$key] ?? $default;
}

/**
 * Check if feature is enabled
 * Verificar se funcionalidade está habilitada
 * 
 * @param string $feature Feature name / Nome da funcionalidade
 * @return bool
 */
function feature_enabled($feature) {
    return config("features.{$feature}", false);
}

/**
 * Get full URL for a path
 * Obter URL completa para um caminho
 * 
 * @param string $path Path / Caminho
 * @return string
 */
function url($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Get asset URL
 * Obter URL de recurso
 * 
 * @param string $asset Asset path / Caminho do recurso
 * @return string
 */
function asset($asset) {
    return url(ASSETS_PATH . ltrim($asset, '/'));
}

// Set timezone / Definir fuso horário
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(APP_TIMEZONE);
}

// Set error reporting based on environment / Definir relatório de erros baseado no ambiente
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Set error log file / Definir arquivo de log de erro
ini_set('error_log', LOGS_PATH . 'error.log');
?>