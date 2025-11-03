<?php
/**
 * Security Middleware / Middleware de Segurança
 * 
 * Provides security headers and protection mechanisms for VanTracing
 * Fornece cabeçalhos de segurança e mecanismos de proteção para o VanTracing
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

class SecurityMiddleware {
    private $config;
    private $environment;
    
    public function __construct() {
        $this->environment = getenv('APP_ENV') ?: 'production';
        $this->config = [
            'force_https' => getenv('FORCE_HTTPS') !== 'false',
            'hsts_max_age' => (int)(getenv('HSTS_MAX_AGE') ?: 31536000), // 1 year
            'csp_enabled' => getenv('CSP_ENABLED') !== 'false',
            'frame_options' => getenv('FRAME_OPTIONS') ?: 'SAMEORIGIN',
            'content_type_options' => true,
            'xss_protection' => true,
            'referrer_policy' => getenv('REFERRER_POLICY') ?: 'strict-origin-when-cross-origin',
            'permissions_policy' => getenv('PERMISSIONS_POLICY') ?: 'geolocation=(self), camera=(), microphone=()'
        ];
    }
    
    /**
     * Apply security headers to all responses
     * Aplicar cabeçalhos de segurança a todas as respostas
     */
    public function applySecurityHeaders() {
        // Content Security Policy / Política de Segurança de Conteúdo
        if ($this->config['csp_enabled']) {
            $csp = $this->buildCSP();
            header("Content-Security-Policy: $csp");
        }
        
        // HTTP Strict Transport Security / Segurança de Transporte Estrito HTTP
        if ($this->config['force_https'] && $this->isHTTPS()) {
            header("Strict-Transport-Security: max-age={$this->config['hsts_max_age']}; includeSubDomains; preload");
        }
        
        // X-Frame-Options / Opções de Frame X
        header("X-Frame-Options: {$this->config['frame_options']}");
        
        // X-Content-Type-Options / Opções de Tipo de Conteúdo X
        if ($this->config['content_type_options']) {
            header("X-Content-Type-Options: nosniff");
        }
        
        // X-XSS-Protection / Proteção XSS X
        if ($this->config['xss_protection']) {
            header("X-XSS-Protection: 1; mode=block");
        }
        
        // Referrer Policy / Política de Referenciador
        header("Referrer-Policy: {$this->config['referrer_policy']}");
        
        // Permissions Policy / Política de Permissões
        header("Permissions-Policy: {$this->config['permissions_policy']}");
        
        // Remove server information / Remover informações do servidor
        header_remove("X-Powered-By");
        header_remove("Server");
        
        // Cache Control for sensitive pages / Controle de Cache para páginas sensíveis
        if ($this->isSensitivePage()) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
        }
    }
    
    /**
     * Build Content Security Policy / Construir Política de Segurança de Conteúdo
     */
    private function buildCSP() {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https:",
            "media-src 'self'",
            "object-src 'none'",
            "frame-src 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        // Add unsafe-eval for development / Adicionar unsafe-eval para desenvolvimento
        if ($this->environment === 'development') {
            $directives[1] .= " 'unsafe-eval'";
        }
        
        return implode('; ', $directives);
    }
    
    /**
     * Check if connection is HTTPS / Verificar se a conexão é HTTPS
     */
    private function isHTTPS() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Check if current page contains sensitive data / Verificar se a página atual contém dados sensíveis
     */
    private function isSensitivePage() {
        $sensitive_pages = ['/api/', '/perfil', '/dashboard', '/admin'];
        $current_path = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($sensitive_pages as $page) {
            if (strpos($current_path, $page) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enforce HTTPS redirect / Forçar redirecionamento HTTPS
     */
    public function enforceHTTPS() {
        if ($this->config['force_https'] && !$this->isHTTPS() && $this->environment === 'production') {
            $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirect_url", true, 301);
            exit();
        }
    }
    
    /**
     * Rate limiting protection / Proteção de limitação de taxa
     */
    public function checkRateLimit($identifier = null, $max_requests = 60, $window_minutes = 1) {
        if (!$identifier) {
            $identifier = $this->getClientIdentifier();
        }
        
        $window_start = floor(time() / ($window_minutes * 60)) * ($window_minutes * 60);
        $cache_key = "rate_limit:{$identifier}:{$window_start}";
        
        // Simple file-based rate limiting / Limitação de taxa simples baseada em arquivo
        $rate_limit_file = __DIR__ . "/../logs/rate_limit_{$identifier}_{$window_start}.tmp";
        
        $current_requests = 0;
        if (file_exists($rate_limit_file)) {
            $current_requests = (int)file_get_contents($rate_limit_file);
        }
        
        if ($current_requests >= $max_requests) {
            $this->sendRateLimitResponse();
            exit();
        }
        
        // Increment counter / Incrementar contador
        file_put_contents($rate_limit_file, $current_requests + 1);
        
        // Clean up old rate limit files / Limpar arquivos antigos de limite de taxa
        $this->cleanupRateLimitFiles();
        
        // Add rate limit headers / Adicionar cabeçalhos de limite de taxa
        header("X-RateLimit-Limit: $max_requests");
        header("X-RateLimit-Remaining: " . ($max_requests - $current_requests - 1));
        header("X-RateLimit-Reset: " . ($window_start + ($window_minutes * 60)));
    }
    
    /**
     * Get client identifier for rate limiting / Obter identificador do cliente para limitação de taxa
     */
    private function getClientIdentifier() {
        // Use IP + User Agent hash for identification / Usar hash de IP + User Agent para identificação
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // If user is logged in, use user ID / Se usuário estiver logado, usar ID do usuário
        session_start();
        if (isset($_SESSION['user_id'])) {
            return 'user_' . $_SESSION['user_id'];
        }
        
        return 'ip_' . hash('sha256', $ip . $user_agent);
    }
    
    /**
     * Send rate limit exceeded response / Enviar resposta de limite de taxa excedido
     */
    private function sendRateLimitResponse() {
        http_response_code(429);
        header('Content-Type: application/json');
        
        $response = [
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again later.',
            'message_pt' => 'Muitas solicitações. Tente novamente mais tarde.',
            'retry_after' => 60
        ];
        
        echo json_encode($response);
        
        // Log rate limit violation / Registrar violação de limite de taxa
        error_log("Rate limit exceeded for client: " . $this->getClientIdentifier());
    }
    
    /**
     * Clean up old rate limit files / Limpar arquivos antigos de limite de taxa
     */
    private function cleanupRateLimitFiles() {
        $logs_dir = __DIR__ . "/../logs/";
        if (!is_dir($logs_dir)) {
            return;
        }
        
        $files = glob($logs_dir . "rate_limit_*.tmp");
        $current_time = time();
        
        foreach ($files as $file) {
            if (filemtime($file) < ($current_time - 3600)) { // Remove files older than 1 hour / Remover arquivos mais antigos que 1 hora
                unlink($file);
            }
        }
    }
    
    /**
     * Validate and sanitize input / Validar e sanitizar entrada
     */
    public function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            case 'string':
            default:
                return filter_var($input, FILTER_SANITIZE_STRING);
        }
    }
    
    /**
     * Check for suspicious patterns / Verificar padrões suspeitos
     */
    public function detectSuspiciousActivity($input) {
        $suspicious_patterns = [
            '/(<script[^>]*>.*?<\/script>)/is',           // Script tags / Tags de script
            '/(\b(?:union|select|insert|delete|update|drop|create|alter)\b)/is', // SQL keywords / Palavras-chave SQL
            '/(\.\.\/|\.\.\\\\)/i',                       // Directory traversal / Travessia de diretório
            '/(\$\{.*\})/i',                             // Template injection / Injeção de template
            '/(eval\s*\(|exec\s*\()/i',                  // Code execution / Execução de código
            '/(javascript:|vbscript:|data:|file:)/i'      // Dangerous protocols / Protocolos perigosos
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSecurityIncident('suspicious_input', [
                    'pattern' => $pattern,
                    'input' => substr($input, 0, 200),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log security incidents / Registrar incidentes de segurança
     */
    public function logSecurityIncident($type, $data = []) {
        $log_entry = [
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'data' => $data
        ];
        
        $log_file = __DIR__ . '/../logs/security.log';
        $log_line = json_encode($log_entry) . PHP_EOL;
        
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
        
        // Also log to system error log / Também registrar no log de erro do sistema
        error_log("VanTracing Security Incident: $type - " . json_encode($data));
    }
    
    /**
     * Initialize security middleware / Inicializar middleware de segurança
     */
    public static function initialize() {
        $security = new self();
        
        // Apply security headers / Aplicar cabeçalhos de segurança
        $security->applySecurityHeaders();
        
        // Enforce HTTPS if configured / Forçar HTTPS se configurado
        $security->enforceHTTPS();
        
        return $security;
    }
    
    /**
     * Protect API endpoint / Proteger endpoint da API
     */
    public function protectAPI($max_requests = 60, $window_minutes = 1) {
        // Check rate limit / Verificar limite de taxa
        $this->checkRateLimit(null, $max_requests, $window_minutes);
        
        // Validate request method / Validar método da requisição
        $allowed_methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        if (!in_array($_SERVER['REQUEST_METHOD'], $allowed_methods)) {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }
        
        // Check for suspicious input in all request data / Verificar entrada suspeita em todos os dados da requisição
        $all_input = array_merge($_GET, $_POST, $_COOKIE);
        foreach ($all_input as $key => $value) {
            if (is_string($value) && $this->detectSuspiciousActivity($value)) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'Suspicious input detected',
                    'message' => 'Your request contains potentially malicious content.',
                    'message_pt' => 'Sua solicitação contém conteúdo potencialmente malicioso.'
                ]);
                exit();
            }
        }
    }
}

// Auto-initialize security if included / Inicializar segurança automaticamente se incluído
if (!defined('SECURITY_MIDDLEWARE_MANUAL')) {
    SecurityMiddleware::initialize();
}
?>