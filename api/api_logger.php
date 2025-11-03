<?php
/**
 * API Logging Middleware / Middleware de Logging de API
 * 
 * Automatic request/response logging for API endpoints
 * Logging automático de requisições/respostas para endpoints da API
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

require_once 'logger.php';

class ApiLogger {
    private static $start_time;
    private static $request_data = [];
    
    /**
     * Start logging API request / Iniciar logging da requisição da API
     */
    public static function start() {
        self::$start_time = microtime(true);
        
        // Capture request data / Capturar dados da requisição
        self::$request_data = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip' => self::getClientIP(),
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
            'user_id' => $_SESSION['user_id'] ?? null,
            'session_id' => session_id() ?: null
        ];
        
        // Log request start / Registrar início da requisição
        log_info('API Request Started', self::$request_data, 'api');
        
        // Register shutdown function to log response / Registrar função de encerramento para logar resposta
        register_shutdown_function([self::class, 'end']);
    }
    
    /**
     * End logging API request / Finalizar logging da requisição da API
     */
    public static function end() {
        if (self::$start_time === null) {
            return;
        }
        
        $end_time = microtime(true);
        $execution_time = ($end_time - self::$start_time) * 1000; // Convert to milliseconds
        
        // Get response information / Obter informações da resposta
        $response_code = http_response_code();
        $memory_usage = memory_get_peak_usage(true);
        
        // Determine log level based on response code / Determinar nível do log baseado no código de resposta
        $log_level = self::getLogLevelForResponseCode($response_code);
        
        $response_data = array_merge(self::$request_data, [
            'response_code' => $response_code,
            'execution_time_ms' => round($execution_time, 2),
            'memory_usage_mb' => round($memory_usage / 1024 / 1024, 2),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        // Log the complete request/response cycle / Registrar o ciclo completo requisição/resposta
        VanTracingLogger::getInstance()->log($log_level, 'API Request Completed', $response_data, 'api');
        
        // Log performance metrics / Registrar métricas de performance
        log_performance('api_response_time', $execution_time, 'ms', [
            'endpoint' => self::$request_data['uri'],
            'method' => self::$request_data['method'],
            'response_code' => $response_code
        ]);
        
        // Log slow queries / Registrar consultas lentas
        if ($execution_time > 1000) { // Slower than 1 second
            log_warning('Slow API Request', $response_data, 'performance');
        }
        
        // Log errors / Registrar erros
        if ($response_code >= 400) {
            $error_level = $response_code >= 500 ? VanTracingLogger::LEVEL_ERROR : VanTracingLogger::LEVEL_WARNING;
            VanTracingLogger::getInstance()->log($error_level, 'API Request Error', $response_data, 'error');
        }
    }
    
    /**
     * Get appropriate log level for response code / Obter nível de log apropriado para código de resposta
     */
    private static function getLogLevelForResponseCode($code) {
        if ($code >= 500) {
            return VanTracingLogger::LEVEL_ERROR;
        } elseif ($code >= 400) {
            return VanTracingLogger::LEVEL_WARNING;
        } elseif ($code >= 300) {
            return VanTracingLogger::LEVEL_INFO;
        } else {
            return VanTracingLogger::LEVEL_INFO;
        }
    }
    
    /**
     * Get client IP address / Obter endereço IP do cliente
     */
    private static function getClientIP() {
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
     * Log specific API action / Registrar ação específica da API
     */
    public static function logAction($action, $details = [], $level = VanTracingLogger::LEVEL_INFO) {
        $context = array_merge([
            'action' => $action,
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => self::getClientIP()
        ], $details);
        
        VanTracingLogger::getInstance()->log($level, "API Action: {$action}", $context, 'api');
    }
    
    /**
     * Log database query performance / Registrar performance de consulta do banco
     */
    public static function logDatabaseQuery($query, $execution_time, $affected_rows = null) {
        // Extract table name from query / Extrair nome da tabela da consulta
        $table = 'unknown';
        if (preg_match('/(?:FROM|UPDATE|INSERT INTO|DELETE FROM)\s+`?(\w+)`?/i', $query, $matches)) {
            $table = $matches[1];
        }
        
        // Determine operation type / Determinar tipo da operação
        $operation = 'unknown';
        if (preg_match('/^(SELECT|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER)/i', trim($query), $matches)) {
            $operation = strtoupper($matches[1]);
        }
        
        $context = [
            'query_preview' => substr($query, 0, 100) . (strlen($query) > 100 ? '...' : ''),
            'execution_time_ms' => round($execution_time * 1000, 2),
            'affected_rows' => $affected_rows,
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        log_database($operation, $table, $execution_time * 1000, $context);
        
        // Log slow queries / Registrar consultas lentas
        if ($execution_time > 0.1) { // Slower than 100ms
            log_warning('Slow Database Query', $context, 'database');
        }
    }
    
    /**
     * Log authentication events / Registrar eventos de autenticação
     */
    public static function logAuth($event, $user_id = null, $details = []) {
        $context = array_merge([
            'event' => $event,
            'user_id' => $user_id,
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ], $details);
        
        $level = in_array($event, ['login_failed', 'invalid_token', 'access_denied']) 
            ? VanTracingLogger::LEVEL_WARNING 
            : VanTracingLogger::LEVEL_INFO;
        
        VanTracingLogger::getInstance()->log($level, "Auth Event: {$event}", $context, 'security');
    }
    
    /**
     * Log email operations / Registrar operações de email
     */
    public static function logEmail($type, $recipient, $success, $details = []) {
        $status = $success ? 'sent' : 'failed';
        $level = $success ? VanTracingLogger::LEVEL_INFO : VanTracingLogger::LEVEL_WARNING;
        
        $context = array_merge([
            'email_type' => $type,
            'recipient' => $recipient,
            'status' => $status,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => self::getClientIP()
        ], $details);
        
        VanTracingLogger::getInstance()->log($level, "Email {$status}: {$type}", $context, 'email');
    }
}

/**
 * Auto-initialize API logging for included files / Auto-inicializar logging de API para arquivos incluídos
 */
if (!defined('API_LOGGING_DISABLED')) {
    ApiLogger::start();
}

/**
 * Helper functions / Funções auxiliares
 */

function api_log_action($action, $details = [], $level = VanTracingLogger::LEVEL_INFO) {
    return ApiLogger::logAction($action, $details, $level);
}

function api_log_database($query, $execution_time, $affected_rows = null) {
    return ApiLogger::logDatabaseQuery($query, $execution_time, $affected_rows);
}

function api_log_auth($event, $user_id = null, $details = []) {
    return ApiLogger::logAuth($event, $user_id, $details);
}

function api_log_email($type, $recipient, $success, $details = []) {
    return ApiLogger::logEmail($type, $recipient, $success, $details);
}
?>