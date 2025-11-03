<?php
/**
 * Advanced Logging System / Sistema de Logging Avançado
 * 
 * Structured logging with different levels, rotation, and formatting
 * Sistema de logging estruturado com níveis, rotação e formatação
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

class VanTracingLogger {
    
    // Log levels / Níveis de log
    const LEVEL_DEBUG = 100;
    const LEVEL_INFO = 200;
    const LEVEL_WARNING = 300;
    const LEVEL_ERROR = 400;
    const LEVEL_CRITICAL = 500;
    
    private static $instance;
    private $config;
    private $log_levels = [
        self::LEVEL_DEBUG => 'DEBUG',
        self::LEVEL_INFO => 'INFO',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_ERROR => 'ERROR',
        self::LEVEL_CRITICAL => 'CRITICAL'
    ];
    
    private function __construct() {
        $this->config = $this->loadConfig();
        $this->createLogDirectories();
    }
    
    /**
     * Get singleton instance / Obter instância singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load logging configuration / Carregar configuração de logging
     */
    private function loadConfig() {
        return [
            'log_dir' => __DIR__ . '/../logs/',
            'max_file_size' => 10 * 1024 * 1024, // 10MB
            'max_files' => 5,
            'date_format' => 'Y-m-d H:i:s',
            'timezone' => 'UTC',
            'min_level' => getenv('LOG_LEVEL') === 'DEBUG' ? self::LEVEL_DEBUG : self::LEVEL_INFO,
            'channels' => [
                'app' => 'application.log',
                'security' => 'security.log',
                'api' => 'api.log',
                'database' => 'database.log',
                'email' => 'email.log',
                'error' => 'error.log',
                'performance' => 'performance.log'
            ]
        ];
    }
    
    /**
     * Create log directories if they don't exist / Criar diretórios de log se não existirem
     */
    private function createLogDirectories() {
        if (!is_dir($this->config['log_dir'])) {
            mkdir($this->config['log_dir'], 0755, true);
        }
        
        // Create .htaccess to protect log files / Criar .htaccess para proteger arquivos de log
        $htaccess_file = $this->config['log_dir'] . '.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Require all denied\n");
        }
    }
    
    /**
     * Debug level logging / Logging nível debug
     */
    public static function debug($message, $context = [], $channel = 'app') {
        return self::getInstance()->log(self::LEVEL_DEBUG, $message, $context, $channel);
    }
    
    /**
     * Info level logging / Logging nível info
     */
    public static function info($message, $context = [], $channel = 'app') {
        return self::getInstance()->log(self::LEVEL_INFO, $message, $context, $channel);
    }
    
    /**
     * Warning level logging / Logging nível warning
     */
    public static function warning($message, $context = [], $channel = 'app') {
        return self::getInstance()->log(self::LEVEL_WARNING, $message, $context, $channel);
    }
    
    /**
     * Error level logging / Logging nível error
     */
    public static function error($message, $context = [], $channel = 'app') {
        return self::getInstance()->log(self::LEVEL_ERROR, $message, $context, $channel);
    }
    
    /**
     * Critical level logging / Logging nível crítico
     */
    public static function critical($message, $context = [], $channel = 'app') {
        return self::getInstance()->log(self::LEVEL_CRITICAL, $message, $context, $channel);
    }
    
    /**
     * Security specific logging / Logging específico de segurança
     */
    public static function security($level, $message, $context = []) {
        return self::getInstance()->log($level, $message, $context, 'security');
    }
    
    /**
     * API request logging / Logging de requisições da API
     */
    public static function apiRequest($method, $endpoint, $response_code, $response_time, $context = []) {
        $context = array_merge($context, [
            'method' => $method,
            'endpoint' => $endpoint,
            'response_code' => $response_code,
            'response_time_ms' => $response_time,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        return self::getInstance()->log(self::LEVEL_INFO, 'API Request', $context, 'api');
    }
    
    /**
     * Database operation logging / Logging de operações do banco de dados
     */
    public static function database($operation, $table, $execution_time = null, $context = []) {
        $context = array_merge($context, [
            'operation' => $operation,
            'table' => $table,
            'execution_time_ms' => $execution_time
        ]);
        
        return self::getInstance()->log(self::LEVEL_DEBUG, 'Database Operation', $context, 'database');
    }
    
    /**
     * Email operation logging / Logging de operações de email
     */
    public static function email($type, $recipient, $status, $context = []) {
        $context = array_merge($context, [
            'email_type' => $type,
            'recipient' => $recipient,
            'status' => $status
        ]);
        
        return self::getInstance()->log(self::LEVEL_INFO, 'Email Operation', $context, 'email');
    }
    
    /**
     * Performance monitoring logging / Logging de monitoramento de performance
     */
    public static function performance($metric, $value, $unit = 'ms', $context = []) {
        $context = array_merge($context, [
            'metric' => $metric,
            'value' => $value,
            'unit' => $unit,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ]);
        
        return self::getInstance()->log(self::LEVEL_DEBUG, 'Performance Metric', $context, 'performance');
    }
    
    /**
     * Main logging method / Método principal de logging
     */
    public function log($level, $message, $context = [], $channel = 'app') {
        // Check if log level meets minimum threshold / Verificar se nível atende ao mínimo
        if ($level < $this->config['min_level']) {
            return false;
        }
        
        // Get log file path / Obter caminho do arquivo de log
        $log_file = $this->getLogFilePath($channel);
        
        // Check if log rotation is needed / Verificar se rotação é necessária
        $this->rotateLogIfNeeded($log_file);
        
        // Format log entry / Formatar entrada do log
        $formatted_entry = $this->formatLogEntry($level, $message, $context);
        
        // Write to log file / Escrever no arquivo de log
        return $this->writeToLog($log_file, $formatted_entry);
    }
    
    /**
     * Get log file path for channel / Obter caminho do arquivo para canal
     */
    private function getLogFilePath($channel) {
        $filename = $this->config['channels'][$channel] ?? $this->config['channels']['app'];
        return $this->config['log_dir'] . $filename;
    }
    
    /**
     * Format log entry / Formatar entrada do log
     */
    private function formatLogEntry($level, $message, $context) {
        $timestamp = date($this->config['date_format']);
        $level_name = $this->log_levels[$level] ?? 'UNKNOWN';
        
        // Add request context / Adicionar contexto da requisição
        $request_context = [
            'request_id' => $this->getRequestId(),
            'session_id' => session_id() ?: 'no-session',
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        // Merge contexts / Mesclar contextos
        $full_context = array_merge($request_context, $context);
        
        // Create structured log entry / Criar entrada estruturada
        $log_entry = [
            'timestamp' => $timestamp,
            'level' => $level_name,
            'message' => $message,
            'context' => $full_context
        ];
        
        // Format as JSON for structured logging / Formatar como JSON para logging estruturado
        if (getenv('LOG_FORMAT') === 'json') {
            return json_encode($log_entry) . PHP_EOL;
        }
        
        // Format as readable text / Formatar como texto legível
        $context_str = !empty($full_context) ? ' ' . json_encode($full_context) : '';
        return "[{$timestamp}] {$level_name}: {$message}{$context_str}" . PHP_EOL;
    }
    
    /**
     * Get or generate request ID / Obter ou gerar ID da requisição
     */
    private function getRequestId() {
        if (!isset($_SERVER['REQUEST_ID'])) {
            $_SERVER['REQUEST_ID'] = uniqid('req_', true);
        }
        return $_SERVER['REQUEST_ID'];
    }
    
    /**
     * Write entry to log file / Escrever entrada no arquivo de log
     */
    private function writeToLog($log_file, $entry) {
        $result = file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
        
        // If write fails, try to create directory and retry / Se falhar, tentar criar diretório e tentar novamente
        if ($result === false) {
            $dir = dirname($log_file);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $result = file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Check if log rotation is needed and perform it / Verificar se rotação é necessária e executar
     */
    private function rotateLogIfNeeded($log_file) {
        if (!file_exists($log_file)) {
            return;
        }
        
        $file_size = filesize($log_file);
        if ($file_size >= $this->config['max_file_size']) {
            $this->rotateLog($log_file);
        }
    }
    
    /**
     * Rotate log file / Rotacionar arquivo de log
     */
    private function rotateLog($log_file) {
        $max_files = $this->config['max_files'];
        
        // Remove oldest rotated file / Remover arquivo rotacionado mais antigo
        $oldest_file = $log_file . '.' . $max_files;
        if (file_exists($oldest_file)) {
            unlink($oldest_file);
        }
        
        // Rotate existing files / Rotacionar arquivos existentes
        for ($i = $max_files - 1; $i >= 1; $i--) {
            $old_file = $log_file . '.' . $i;
            $new_file = $log_file . '.' . ($i + 1);
            
            if (file_exists($old_file)) {
                rename($old_file, $new_file);
            }
        }
        
        // Move current log file to .1 / Mover arquivo atual para .1
        if (file_exists($log_file)) {
            rename($log_file, $log_file . '.1');
        }
    }
    
    /**
     * Get log statistics / Obter estatísticas dos logs
     */
    public function getLogStats($channel = null) {
        $stats = [];
        
        $channels_to_check = $channel ? [$channel] : array_keys($this->config['channels']);
        
        foreach ($channels_to_check as $ch) {
            $log_file = $this->getLogFilePath($ch);
            
            if (file_exists($log_file)) {
                $stats[$ch] = [
                    'file_size' => filesize($log_file),
                    'file_size_human' => $this->formatBytes(filesize($log_file)),
                    'last_modified' => date('Y-m-d H:i:s', filemtime($log_file)),
                    'line_count' => $this->countLogLines($log_file)
                ];
            } else {
                $stats[$ch] = [
                    'file_size' => 0,
                    'file_size_human' => '0 B',
                    'last_modified' => null,
                    'line_count' => 0
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Count lines in log file / Contar linhas no arquivo de log
     */
    private function countLogLines($file) {
        $line_count = 0;
        $handle = fopen($file, 'r');
        
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line_count++;
            }
            fclose($handle);
        }
        
        return $line_count;
    }
    
    /**
     * Format bytes to human readable / Formatar bytes para legível
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Clean old log files / Limpar arquivos de log antigos
     */
    public function cleanOldLogs($days = 30) {
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        $cleaned_files = [];
        
        $log_files = glob($this->config['log_dir'] . '*.log*');
        
        foreach ($log_files as $file) {
            if (filemtime($file) < $cutoff_time) {
                if (unlink($file)) {
                    $cleaned_files[] = basename($file);
                }
            }
        }
        
        return $cleaned_files;
    }
    
    /**
     * Search logs / Pesquisar logs
     */
    public function searchLogs($pattern, $channel = null, $limit = 100) {
        $results = [];
        $channels_to_search = $channel ? [$channel] : array_keys($this->config['channels']);
        
        foreach ($channels_to_search as $ch) {
            $log_file = $this->getLogFilePath($ch);
            
            if (!file_exists($log_file)) {
                continue;
            }
            
            $handle = fopen($log_file, 'r');
            if (!$handle) {
                continue;
            }
            
            $line_number = 0;
            while (($line = fgets($handle)) !== false && count($results) < $limit) {
                $line_number++;
                
                if (stripos($line, $pattern) !== false) {
                    $results[] = [
                        'channel' => $ch,
                        'line_number' => $line_number,
                        'content' => trim($line),
                        'file' => basename($log_file)
                    ];
                }
            }
            
            fclose($handle);
        }
        
        return $results;
    }
}

/**
 * Global helper functions / Funções auxiliares globais
 */

function log_debug($message, $context = [], $channel = 'app') {
    return VanTracingLogger::debug($message, $context, $channel);
}

function log_info($message, $context = [], $channel = 'app') {
    return VanTracingLogger::info($message, $context, $channel);
}

function log_warning($message, $context = [], $channel = 'app') {
    return VanTracingLogger::warning($message, $context, $channel);
}

function log_error($message, $context = [], $channel = 'app') {
    return VanTracingLogger::error($message, $context, $channel);
}

function log_critical($message, $context = [], $channel = 'app') {
    return VanTracingLogger::critical($message, $context, $channel);
}

function log_api_request($method, $endpoint, $response_code, $response_time, $context = []) {
    return VanTracingLogger::apiRequest($method, $endpoint, $response_code, $response_time, $context);
}

function log_security($level, $message, $context = []) {
    return VanTracingLogger::security($level, $message, $context);
}

function log_database($operation, $table, $execution_time = null, $context = []) {
    return VanTracingLogger::database($operation, $table, $execution_time, $context);
}

function log_email($type, $recipient, $status, $context = []) {
    return VanTracingLogger::email($type, $recipient, $status, $context);
}

function log_performance($metric, $value, $unit = 'ms', $context = []) {
    return VanTracingLogger::performance($metric, $value, $unit, $context);
}
?>