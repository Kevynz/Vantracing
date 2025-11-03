<?php
/**
 * Performance Monitor for VanTracing / Monitor de Performance VanTracing
 * 
 * Comprehensive performance monitoring and optimization system
 * Sistema abrangente de monitoramento e otimização de performance
 * 
 * @package VanTracing
 * @author Kevyn  
 * @version 2.0
 */

class PerformanceMonitor {
    private static $instance;
    private $metrics = [];
    private $start_times = [];
    private $memory_snapshots = [];
    private $config;
    
    private function __construct() {
        $this->config = $this->loadConfig();
        $this->recordSystemBaseline();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig() {
        return [
            'enabled' => getenv('PERFORMANCE_MONITORING') !== 'false',
            'log_slow_queries' => (float)(getenv('SLOW_QUERY_THRESHOLD') ?: 1.0), // seconds
            'log_memory_limit' => (int)(getenv('MEMORY_THRESHOLD') ?: 50), // MB
            'sample_rate' => (float)(getenv('SAMPLE_RATE') ?: 1.0), // 100% by default
            'store_metrics' => getenv('STORE_METRICS') !== 'false'
        ];
    }
    
    private function recordSystemBaseline() {
        $this->metrics['system_baseline'] = [
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'php_version' => PHP_VERSION,
            'server_load' => $this->getServerLoad()
        ];
    }
    
    /**
     * Start timing an operation / Iniciar cronometragem de uma operação
     */
    public static function startTimer($operation_name) {
        $instance = self::getInstance();
        
        if (!$instance->config['enabled'] || !$instance->shouldSample()) {
            return;
        }
        
        $instance->start_times[$operation_name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'queries_before' => $instance->getCurrentQueryCount()
        ];
    }
    
    /**
     * End timing an operation / Finalizar cronometragem de uma operação
     */
    public static function endTimer($operation_name, $additional_data = []) {
        $instance = self::getInstance();
        
        if (!$instance->config['enabled'] || !isset($instance->start_times[$operation_name])) {
            return;
        }
        
        $start_data = $instance->start_times[$operation_name];
        $end_time = microtime(true);
        $end_memory = memory_get_usage(true);
        
        $metrics = [
            'operation' => $operation_name,
            'start_time' => $start_data['start_time'],
            'end_time' => $end_time,
            'duration_ms' => ($end_time - $start_data['start_time']) * 1000,
            'memory_start' => $start_data['start_memory'],
            'memory_end' => $end_memory,
            'memory_used' => $end_memory - $start_data['start_memory'],
            'peak_memory' => memory_get_peak_usage(true),
            'queries_executed' => $instance->getCurrentQueryCount() - $start_data['queries_before'],
            'timestamp' => date('Y-m-d H:i:s'),
            'additional_data' => $additional_data
        ];
        
        // Store metrics / Armazenar métricas
        $instance->storeMetrics($operation_name, $metrics);
        
        // Log slow operations / Registrar operações lentas
        $duration_seconds = $metrics['duration_ms'] / 1000;
        if ($duration_seconds > $instance->config['log_slow_queries']) {
            $instance->logSlowOperation($metrics);
        }
        
        // Log high memory usage / Registrar alto uso de memória
        $memory_mb = $metrics['memory_used'] / 1024 / 1024;
        if ($memory_mb > $instance->config['log_memory_limit']) {
            $instance->logHighMemoryUsage($metrics);
        }
        
        unset($instance->start_times[$operation_name]);
        
        return $metrics;
    }
    
    /**
     * Record a single metric / Registrar uma métrica única
     */
    public static function recordMetric($name, $value, $unit = 'count', $tags = []) {
        $instance = self::getInstance();
        
        if (!$instance->config['enabled'] || !$instance->shouldSample()) {
            return;
        }
        
        $metric = [
            'name' => $name,
            'value' => $value,
            'unit' => $unit,
            'timestamp' => microtime(true),
            'tags' => $tags,
            'memory_usage' => memory_get_usage(true)
        ];
        
        $instance->storeMetrics($name, $metric);
        
        // Log to VanTracingLogger if available / Registrar no VanTracingLogger se disponível
        if (class_exists('VanTracingLogger')) {
            VanTracingLogger::getInstance()->log('INFO', "Metric recorded: $name = $value $unit", [
                'metric_name' => $name,
                'value' => $value,
                'unit' => $unit,
                'tags' => $tags
            ], 'performance');
        }
    }
    
    /**
     * Monitor database query performance / Monitorar performance de consulta do banco
     */
    public static function monitorDatabaseQuery($sql, $params, callable $executor) {
        $query_id = 'db_query_' . substr(md5($sql), 0, 8);
        
        self::startTimer($query_id);
        
        try {
            $result = $executor();
            
            $metrics = self::endTimer($query_id, [
                'sql' => substr($sql, 0, 200) . (strlen($sql) > 200 ? '...' : ''),
                'params_count' => count($params),
                'result_count' => is_array($result) ? count($result) : 1,
                'query_type' => self::detectQueryType($sql)
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            self::endTimer($query_id, [
                'sql' => substr($sql, 0, 200) . (strlen($sql) > 200 ? '...' : ''),
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Monitor API endpoint performance / Monitorar performance de endpoint da API
     */
    public static function monitorApiEndpoint($endpoint, callable $handler) {
        $endpoint_id = 'api_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $endpoint);
        
        self::startTimer($endpoint_id);
        
        try {
            $result = $handler();
            
            self::endTimer($endpoint_id, [
                'endpoint' => $endpoint,
                'success' => true,
                'response_size' => is_string($result) ? strlen($result) : 0
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            self::endTimer($endpoint_id, [
                'endpoint' => $endpoint,
                'success' => false,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Get performance summary / Obter resumo de performance
     */
    public static function getSummary($hours = 1) {
        $instance = self::getInstance();
        
        if (!$instance->config['store_metrics']) {
            return ['error' => 'Metrics storage is disabled'];
        }
        
        $since = time() - ($hours * 3600);
        $summary = [
            'period_hours' => $hours,
            'operations' => [],
            'database' => [
                'total_queries' => 0,
                'slow_queries' => 0,
                'average_duration_ms' => 0,
                'cache_hit_rate' => 0
            ],
            'api' => [
                'total_requests' => 0,
                'average_duration_ms' => 0,
                'error_rate' => 0
            ],
            'system' => [
                'average_memory_mb' => 0,
                'peak_memory_mb' => 0,
                'current_memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
            ]
        ];
        
        // Calculate summaries from stored metrics
        // Calcular resumos das métricas armazenadas
        foreach ($instance->metrics as $operation => $metrics_list) {
            if (empty($metrics_list)) continue;
            
            $recent_metrics = array_filter($metrics_list, function($metric) use ($since) {
                return isset($metric['timestamp']) && strtotime($metric['timestamp']) > $since;
            });
            
            if (empty($recent_metrics)) continue;
            
            $durations = array_column($recent_metrics, 'duration_ms');
            $memory_usage = array_column($recent_metrics, 'memory_used');
            
            $summary['operations'][$operation] = [
                'count' => count($recent_metrics),
                'average_duration_ms' => round(array_sum($durations) / count($durations), 2),
                'min_duration_ms' => min($durations),
                'max_duration_ms' => max($durations),
                'average_memory_kb' => round(array_sum($memory_usage) / count($memory_usage) / 1024, 2),
                'total_memory_mb' => round(array_sum($memory_usage) / 1024 / 1024, 2)
            ];
        }
        
        return $summary;
    }
    
    /**
     * Get real-time system metrics / Obter métricas do sistema em tempo real
     */
    public static function getSystemMetrics() {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit'),
            'execution_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3),
            'server_load' => self::getInstance()->getServerLoad(),
            'cache_stats' => class_exists('VanTracingCache') ? VanTracingCache::getStats() : null
        ];
    }
    
    /**
     * Clean old metrics / Limpar métricas antigas
     */
    public static function cleanOldMetrics($days = 7) {
        $instance = self::getInstance();
        $cutoff = time() - ($days * 24 * 3600);
        $cleaned = 0;
        
        foreach ($instance->metrics as $operation => &$metrics_list) {
            $original_count = count($metrics_list);
            
            $metrics_list = array_filter($metrics_list, function($metric) use ($cutoff) {
                return isset($metric['timestamp']) && strtotime($metric['timestamp']) > $cutoff;
            });
            
            $cleaned += $original_count - count($metrics_list);
        }
        
        return $cleaned;
    }
    
    // Private helper methods / Métodos auxiliares privados
    
    private function shouldSample() {
        return mt_rand() / mt_getrandmax() <= $this->config['sample_rate'];
    }
    
    private function storeMetrics($operation, $metrics) {
        if (!$this->config['store_metrics']) {
            return;
        }
        
        if (!isset($this->metrics[$operation])) {
            $this->metrics[$operation] = [];
        }
        
        $this->metrics[$operation][] = $metrics;
        
        // Keep only last 1000 metrics per operation
        // Manter apenas as últimas 1000 métricas por operação
        if (count($this->metrics[$operation]) > 1000) {
            $this->metrics[$operation] = array_slice($this->metrics[$operation], -1000);
        }
    }
    
    private function logSlowOperation($metrics) {
        if (class_exists('VanTracingLogger')) {
            VanTracingLogger::getInstance()->log('WARNING', 'Slow operation detected', [
                'operation' => $metrics['operation'],
                'duration_ms' => $metrics['duration_ms'],
                'memory_used_mb' => round($metrics['memory_used'] / 1024 / 1024, 2),
                'queries_executed' => $metrics['queries_executed'],
                'additional_data' => $metrics['additional_data']
            ], 'performance');
        }
    }
    
    private function logHighMemoryUsage($metrics) {
        if (class_exists('VanTracingLogger')) {
            VanTracingLogger::getInstance()->log('WARNING', 'High memory usage detected', [
                'operation' => $metrics['operation'],
                'memory_used_mb' => round($metrics['memory_used'] / 1024 / 1024, 2),
                'peak_memory_mb' => round($metrics['peak_memory'] / 1024 / 1024, 2),
                'duration_ms' => $metrics['duration_ms']
            ], 'performance');
        }
    }
    
    private function getCurrentQueryCount() {
        // This would need to be implemented based on your database wrapper
        // Isso precisaria ser implementado baseado no seu wrapper de banco de dados
        return 0;
    }
    
    private function getServerLoad() {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0]; // 1-minute average
        }
        
        return null;
    }
    
    private static function detectQueryType($sql) {
        $sql = trim(strtoupper($sql));
        
        if (strpos($sql, 'SELECT') === 0) return 'SELECT';
        if (strpos($sql, 'INSERT') === 0) return 'INSERT';
        if (strpos($sql, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($sql, 'DELETE') === 0) return 'DELETE';
        if (strpos($sql, 'CREATE') === 0) return 'CREATE';
        if (strpos($sql, 'ALTER') === 0) return 'ALTER';
        if (strpos($sql, 'DROP') === 0) return 'DROP';
        
        return 'OTHER';
    }
}

/**
 * Performance middleware for automatic monitoring / Middleware de performance para monitoramento automático
 */
class PerformanceMiddleware {
    /**
     * Wrap function with performance monitoring / Encapsular função com monitoramento de performance
     */
    public static function monitor($operation_name, callable $function, ...$args) {
        PerformanceMonitor::startTimer($operation_name);
        
        try {
            $result = $function(...$args);
            PerformanceMonitor::endTimer($operation_name);
            return $result;
            
        } catch (Exception $e) {
            PerformanceMonitor::endTimer($operation_name, ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Monitor database operations / Monitorar operações do banco de dados
     */
    public static function monitorDatabase($pdo, $sql, $params = []) {
        return PerformanceMonitor::monitorDatabaseQuery($sql, $params, function() use ($pdo, $sql, $params) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }
}

/**
 * Helper functions for performance monitoring / Funções auxiliares para monitoramento de performance
 */

function perf_start($operation) {
    PerformanceMonitor::startTimer($operation);
}

function perf_end($operation, $data = []) {
    return PerformanceMonitor::endTimer($operation, $data);
}

function perf_record($name, $value, $unit = 'count', $tags = []) {
    PerformanceMonitor::recordMetric($name, $value, $unit, $tags);
}

function perf_monitor($operation, callable $function, ...$args) {
    return PerformanceMiddleware::monitor($operation, $function, ...$args);
}

function perf_summary($hours = 1) {
    return PerformanceMonitor::getSummary($hours);
}

function perf_system_metrics() {
    return PerformanceMonitor::getSystemMetrics();
}
?>