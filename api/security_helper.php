<?php
/**
 * Security Helper / Auxiliar de Segurança
 * 
 * Easy-to-use security functions for VanTracing API endpoints
 * Funções de segurança fáceis de usar para endpoints da API VanTracing
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Define constants / Definir constantes
define('VANTRACING_API', true);
define('SECURITY_MIDDLEWARE_MANUAL', true);

require_once 'security_middleware.php';

/**
 * Security Helper Class / Classe Auxiliar de Segurança
 */
class SecurityHelper {
    private static $middleware;
    private static $config;
    
    /**
     * Initialize security for API endpoint / Inicializar segurança para endpoint da API
     */
    public static function initAPI($options = []) {
        self::loadConfig();
        self::$middleware = new SecurityMiddleware();
        
        // Apply security headers / Aplicar cabeçalhos de segurança
        self::$middleware->applySecurityHeaders();
        
        // Enforce HTTPS / Forçar HTTPS
        self::$middleware->enforceHTTPS();
        
        // Protect API with rate limiting / Proteger API com limitação de taxa
        $max_requests = $options['rate_limit'] ?? self::$config['rate_limiting']['api_requests_per_minute'];
        $window = $options['window'] ?? self::$config['rate_limiting']['window_minutes'];
        
        self::$middleware->protectAPI($max_requests, $window);
        
        // Start secure session if needed / Iniciar sessão segura se necessário
        if ($options['session'] ?? true) {
            self::startSecureSession();
        }
        
        return self::$middleware;
    }
    
    /**
     * Load security configuration / Carregar configuração de segurança
     */
    private static function loadConfig() {
        if (!self::$config) {
            self::$config = require_once 'security_config.php';
        }
    }
    
    /**
     * Start secure session / Iniciar sessão segura
     */
    public static function startSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure secure session parameters / Configurar parâmetros de sessão segura
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            if (self::isHTTPS()) {
                ini_set('session.cookie_secure', 1);
            }
            
            session_start();
            
            // Regenerate session ID periodically / Regenerar ID da sessão periodicamente
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Validate CSRF token / Validar token CSRF
     */
    public static function validateCSRF($token = null) {
        if (!$token) {
            $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        }
        
        if (!isset($_SESSION['csrf_token']) || !$token) {
            self::sendError('CSRF token missing', 'Token CSRF ausente', 403);
            return false;
        }
        
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            self::sendError('Invalid CSRF token', 'Token CSRF inválido', 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate CSRF token / Gerar token CSRF
     */
    public static function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Sanitize input data / Sanitizar dados de entrada
     */
    public static function sanitizeInput($data, $type = 'string') {
        if (!self::$middleware) {
            self::$middleware = new SecurityMiddleware();
        }
        
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $data);
        }
        
        return self::$middleware->sanitizeInput($data, $type);
    }
    
    /**
     * Validate required authentication / Validar autenticação obrigatória
     */
    public static function requireAuth() {
        self::startSecureSession();
        
        if (!isset($_SESSION['user_id'])) {
            self::sendError('Authentication required', 'Autenticação obrigatória', 401);
            return false;
        }
        
        return $_SESSION['user_id'];
    }
    
    /**
     * Check user permissions / Verificar permissões do usuário
     */
    public static function checkPermission($required_role = 'user') {
        $user_id = self::requireAuth();
        
        if (!$user_id) {
            return false;
        }
        
        $user_role = $_SESSION['user_role'] ?? 'user';
        
        $role_hierarchy = [
            'user' => 1,
            'driver' => 2,
            'responsible' => 2,
            'admin' => 3
        ];
        
        $required_level = $role_hierarchy[$required_role] ?? 1;
        $user_level = $role_hierarchy[$user_role] ?? 0;
        
        if ($user_level < $required_level) {
            self::sendError('Insufficient permissions', 'Permissões insuficientes', 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Log security event / Registrar evento de segurança
     */
    public static function logEvent($type, $data = []) {
        // Use advanced logger if available / Usar logger avançado se disponível
        if (class_exists('VanTracingLogger')) {
            require_once 'logger.php';
            VanTracingLogger::security(VanTracingLogger::LEVEL_WARNING, $type, $data);
        } else {
            // Fallback to middleware logging / Fallback para logging do middleware
            if (!self::$middleware) {
                self::$middleware = new SecurityMiddleware();
            }
            self::$middleware->logSecurityIncident($type, $data);
        }
    }
    
    /**
     * Send standardized error response / Enviar resposta de erro padronizada
     */
    public static function sendError($message_en, $message_pt, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'error' => $message_en,
            'message' => $message_en,
            'message_pt' => $message_pt,
            'timestamp' => date('c')
        ]);
        
        // Log the error / Registrar o erro
        self::logEvent('api_error', [
            'code' => $code,
            'message' => $message_en,
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        
        exit();
    }
    
    /**
     * Send standardized success response / Enviar resposta de sucesso padronizada
     */
    public static function sendSuccess($data = [], $message_en = 'Success', $message_pt = 'Sucesso') {
        http_response_code(200);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => true,
            'message' => $message_en,
            'message_pt' => $message_pt,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        
        exit();
    }
    
    /**
     * Validate email format / Validar formato de email
     */
    public static function validateEmail($email) {
        $sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($sanitized, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength / Validar força da senha
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters / Senha deve ter pelo menos 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain uppercase letter / Senha deve conter letra maiúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain lowercase letter / Senha deve conter letra minúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain number / Senha deve conter número';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain special character / Senha deve conter caractere especial';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Rate limit specific actions / Limitar taxa de ações específicas
     */
    public static function rateLimitAction($action, $max_attempts = 5, $window_minutes = 60) {
        if (!self::$middleware) {
            self::$middleware = new SecurityMiddleware();
        }
        
        $identifier = $action . '_' . ($_SESSION['user_id'] ?? self::getClientIP());
        
        try {
            self::$middleware->checkRateLimit($identifier, $max_attempts, $window_minutes);
            return true;
        } catch (Exception $e) {
            self::sendError('Too many attempts', 'Muitas tentativas', 429);
            return false;
        }
    }
    
    /**
     * Get client IP address / Obter endereço IP do cliente
     */
    public static function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Check if connection is HTTPS / Verificar se a conexão é HTTPS
     */
    private static function isHTTPS() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Create secure random token / Criar token aleatório seguro
     */
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Hash password securely / Hash de senha seguro
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }
    
    /**
     * Verify password hash / Verificar hash da senha
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

/**
 * Quick security initialization for API endpoints
 * Inicialização rápida de segurança para endpoints da API
 * 
 * Usage / Uso:
 * require_once 'security_helper.php';
 * secure_api(); // Basic protection
 * secure_api(['rate_limit' => 30]); // Custom rate limit
 */
function secure_api($options = []) {
    return SecurityHelper::initAPI($options);
}

/**
 * Quick authentication check / Verificação rápida de autenticação
 */
function require_auth() {
    return SecurityHelper::requireAuth();
}

/**
 * Quick permission check / Verificação rápida de permissão
 */
function require_permission($role = 'user') {
    return SecurityHelper::checkPermission($role);
}

/**
 * Quick CSRF validation / Validação rápida de CSRF
 */
function validate_csrf($token = null) {
    return SecurityHelper::validateCSRF($token);
}

/**
 * Quick input sanitization / Sanitização rápida de entrada
 */
function clean_input($data, $type = 'string') {
    return SecurityHelper::sanitizeInput($data, $type);
}

/**
 * Quick success response / Resposta rápida de sucesso
 */
function send_success($data = [], $message_en = 'Success', $message_pt = 'Sucesso') {
    SecurityHelper::sendSuccess($data, $message_en, $message_pt);
}

/**
 * Quick error response / Resposta rápida de erro
 */
function send_error($message_en, $message_pt, $code = 400) {
    SecurityHelper::sendError($message_en, $message_pt, $code);
}
?>