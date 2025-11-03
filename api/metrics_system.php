<?php
/**
 * VanTracing System Metrics & Monitoring / Sistema de Métricas e Monitoramento VanTracing
 * 
 * Comprehensive performance monitoring and metrics collection system
 * Sistema abrangente de monitoramento de performance e coleta de métricas
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

require_once 'db_connect.php';
require_once 'security_helper.php';
require_once 'logger.php';
require_once 'cache_system.php';

class VanTracingMetricsCollector {
    private static $instance;
    private $pdo;
    private $cache;
    private $config;
    private $metrics_data = [];
    
    private function __construct() {
        global $conn;
        $this->pdo = $conn;
        $this->cache = VanTracingCache::getInstance();
        $this->config = $this->loadConfig();
        $this->setupMetricsTable();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig() {
        return [
            'enabled' => getenv('METRICS_ENABLED') !== 'false',
            'collection_interval' => (int)(getenv('METRICS_INTERVAL') ?: 60), // 60 seconds default
            'retention_days' => (int)(getenv('METRICS_RETENTION') ?: 30), // 30 days default
            'alert_thresholds' => [
                'cpu_usage' => (float)(getenv('ALERT_CPU_THRESHOLD') ?: 80.0),
                'memory_usage' => (float)(getenv('ALERT_MEMORY_THRESHOLD') ?: 85.0),
                'disk_usage' => (float)(getenv('ALERT_DISK_THRESHOLD') ?: 90.0),
                'response_time' => (float)(getenv('ALERT_RESPONSE_THRESHOLD') ?: 2000.0), // 2 seconds
                'error_rate' => (float)(getenv('ALERT_ERROR_RATE') ?: 5.0), // 5%
            ],
            'realtime_metrics' => getenv('REALTIME_METRICS') !== 'false',
            'detailed_logging' => getenv('DETAILED_METRICS_LOG') === 'true',
        ];
    }
    
    private function setupMetricsTable() {
        try {
            // Main metrics table / Tabela principal de métricas
            $sql = "
                CREATE TABLE IF NOT EXISTS system_metrics (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    metric_type VARCHAR(50) NOT NULL,
                    metric_name VARCHAR(100) NOT NULL,
                    metric_value DECIMAL(15,4) NOT NULL,
                    metric_unit VARCHAR(20),
                    tags JSON,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_type_name_time (metric_type, metric_name, timestamp),
                    INDEX idx_timestamp (timestamp)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $this->pdo->exec($sql);
            
            // Performance snapshots table / Tabela de snapshots de performance
            $sql = "
                CREATE TABLE IF NOT EXISTS performance_snapshots (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    snapshot_type ENUM('system', 'database', 'application', 'custom') DEFAULT 'system',
                    cpu_usage DECIMAL(5,2),
                    memory_usage DECIMAL(5,2),
                    disk_usage DECIMAL(5,2),
                    network_io JSON,
                    database_connections INT,
                    active_sessions INT,
                    cache_hit_ratio DECIMAL(5,2),
                    average_response_time DECIMAL(8,2),
                    error_count INT,
                    warning_count INT,
                    custom_metrics JSON,
                    alerts_triggered JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_type_time (snapshot_type, created_at),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $this->pdo->exec($sql);
            
            // Alerts history table / Tabela de histórico de alertas
            $sql = "
                CREATE TABLE IF NOT EXISTS metric_alerts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    alert_type VARCHAR(50) NOT NULL,
                    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                    metric_name VARCHAR(100) NOT NULL,
                    threshold_value DECIMAL(15,4),
                    actual_value DECIMAL(15,4),
                    message TEXT NOT NULL,
                    resolved BOOLEAN DEFAULT FALSE,
                    resolved_at TIMESTAMP NULL,
                    resolved_by INT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_type_severity (alert_type, severity),
                    INDEX idx_resolved_created (resolved, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $this->pdo->exec($sql);
            
        } catch (Exception $e) {
            log_error('Failed to setup metrics tables', [
                'error' => $e->getMessage()
            ], 'database');
        }
    }
    
    /**
     * Collect system metrics / Coletar métricas do sistema
     */
    public function collectSystemMetrics() {
        if (!$this->config['enabled']) {
            return;
        }
        
        try {
            $start_time = microtime(true);
            
            // System metrics / Métricas do sistema
            $system_metrics = $this->getSystemMetrics();
            
            // Database metrics / Métricas do banco de dados
            $database_metrics = $this->getDatabaseMetrics();
            
            // Application metrics / Métricas da aplicação
            $app_metrics = $this->getApplicationMetrics();
            
            // Cache metrics / Métricas do cache
            $cache_metrics = $this->getCacheMetrics();
            
            // Combine all metrics / Combinar todas as métricas
            $all_metrics = array_merge($system_metrics, $database_metrics, $app_metrics, $cache_metrics);
            
            // Store metrics / Armazenar métricas
            $this->storeMetrics($all_metrics);
            
            // Create performance snapshot / Criar snapshot de performance
            $this->createPerformanceSnapshot($all_metrics);
            
            // Check for alerts / Verificar alertas
            $this->checkAlertThresholds($all_metrics);
            
            $collection_time = round((microtime(true) - $start_time) * 1000, 2);
            
            if ($this->config['detailed_logging']) {
                log_info('Metrics collection completed', [
                    'metrics_collected' => count($all_metrics),
                    'collection_time_ms' => $collection_time,
                    'system_metrics' => count($system_metrics),
                    'database_metrics' => count($database_metrics),
                    'application_metrics' => count($app_metrics),
                    'cache_metrics' => count($cache_metrics)
                ], 'performance');
            }
            
            return [
                'success' => true,
                'metrics_collected' => count($all_metrics),
                'collection_time' => $collection_time
            ];
            
        } catch (Exception $e) {
            log_error('Failed to collect metrics', [
                'error' => $e->getMessage()
            ], 'performance');
            
            throw $e;
        }
    }
    
    /**
     * Get system-level metrics / Obter métricas do sistema
     */
    private function getSystemMetrics() {
        $metrics = [];
        
        try {
            // CPU Usage (if available) / Uso de CPU (se disponível)
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $metrics[] = [
                    'type' => 'system',
                    'name' => 'cpu_load_1min',
                    'value' => $load[0],
                    'unit' => 'load',
                    'tags' => ['period' => '1min']
                ];
                $metrics[] = [
                    'type' => 'system',
                    'name' => 'cpu_load_5min',
                    'value' => $load[1],
                    'unit' => 'load',
                    'tags' => ['period' => '5min']
                ];
                $metrics[] = [
                    'type' => 'system',
                    'name' => 'cpu_load_15min',
                    'value' => $load[2],
                    'unit' => 'load',
                    'tags' => ['period' => '15min']
                ];
            }
            
            // Memory usage / Uso de memória
            $memory_usage = memory_get_usage(true);
            $memory_peak = memory_get_peak_usage(true);
            $memory_limit = $this->parseMemoryLimit(ini_get('memory_limit'));
            
            $metrics[] = [
                'type' => 'system',
                'name' => 'memory_usage',
                'value' => $memory_usage,
                'unit' => 'bytes',
                'tags' => ['type' => 'current']
            ];
            
            $metrics[] = [
                'type' => 'system',
                'name' => 'memory_peak',
                'value' => $memory_peak,
                'unit' => 'bytes',
                'tags' => ['type' => 'peak']
            ];
            
            if ($memory_limit > 0) {
                $memory_percentage = ($memory_usage / $memory_limit) * 100;
                $metrics[] = [
                    'type' => 'system',
                    'name' => 'memory_usage_percentage',
                    'value' => $memory_percentage,
                    'unit' => 'percent',
                    'tags' => ['type' => 'percentage']
                ];
            }
            
            // Disk usage (backup directory) / Uso do disco (diretório de backup)
            $backup_dir = __DIR__ . '/../backups/';
            if (is_dir($backup_dir)) {
                $disk_free = disk_free_space($backup_dir);
                $disk_total = disk_total_space($backup_dir);
                
                if ($disk_free !== false && $disk_total !== false) {
                    $disk_used = $disk_total - $disk_free;
                    $disk_usage_percentage = ($disk_used / $disk_total) * 100;
                    
                    $metrics[] = [
                        'type' => 'system',
                        'name' => 'disk_usage_percentage',
                        'value' => $disk_usage_percentage,
                        'unit' => 'percent',
                        'tags' => ['path' => 'backups']
                    ];
                    
                    $metrics[] = [
                        'type' => 'system',
                        'name' => 'disk_free_space',
                        'value' => $disk_free,
                        'unit' => 'bytes',
                        'tags' => ['path' => 'backups']
                    ];
                }
            }
            
        } catch (Exception $e) {
            log_warning('Error collecting system metrics', [
                'error' => $e->getMessage()
            ], 'performance');
        }
        
        return $metrics;
    }
    
    /**
     * Get database-level metrics / Obter métricas do banco de dados
     */
    private function getDatabaseMetrics() {
        $metrics = [];
        
        try {
            // Connection count / Contagem de conexões
            $stmt = $this->pdo->query("SHOW STATUS LIKE 'Threads_connected'");
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $metrics[] = [
                    'type' => 'database',
                    'name' => 'connections_active',
                    'value' => (int)$row['Value'],
                    'unit' => 'count',
                    'tags' => ['status' => 'connected']
                ];
            }
            
            // Query statistics / Estatísticas de consultas
            $query_stats = [
                'Com_select' => 'queries_select',
                'Com_insert' => 'queries_insert',
                'Com_update' => 'queries_update',
                'Com_delete' => 'queries_delete'
            ];
            
            foreach ($query_stats as $mysql_var => $metric_name) {
                $stmt = $this->pdo->query("SHOW STATUS LIKE '$mysql_var'");
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $metrics[] = [
                        'type' => 'database',
                        'name' => $metric_name,
                        'value' => (int)$row['Value'],
                        'unit' => 'count',
                        'tags' => ['type' => 'cumulative']
                    ];
                }
            }
            
            // Slow queries / Consultas lentas
            $stmt = $this->pdo->query("SHOW STATUS LIKE 'Slow_queries'");
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $metrics[] = [
                    'type' => 'database',
                    'name' => 'slow_queries',
                    'value' => (int)$row['Value'],
                    'unit' => 'count',
                    'tags' => ['type' => 'cumulative']
                ];
            }
            
            // Table sizes / Tamanhos das tabelas
            $stmt = $this->pdo->query("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    table_rows
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $metrics[] = [
                    'type' => 'database',
                    'name' => 'table_size',
                    'value' => $row['size_mb'],
                    'unit' => 'MB',
                    'tags' => ['table' => $row['table_name']]
                ];
                
                $metrics[] = [
                    'type' => 'database',
                    'name' => 'table_rows',
                    'value' => (int)$row['table_rows'],
                    'unit' => 'count',
                    'tags' => ['table' => $row['table_name']]
                ];
            }
            
        } catch (Exception $e) {
            log_warning('Error collecting database metrics', [
                'error' => $e->getMessage()
            ], 'performance');
        }
        
        return $metrics;
    }
    
    /**
     * Get application-level metrics / Obter métricas da aplicação
     */
    private function getApplicationMetrics() {
        $metrics = [];
        
        try {
            // User statistics / Estatísticas de usuários
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM usuarios");
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $metrics[] = [
                    'type' => 'application',
                    'name' => 'users_total',
                    'value' => (int)$row['total'],
                    'unit' => 'count',
                    'tags' => ['entity' => 'users']
                ];
            }
            
            // Active users (logged in last 24 hours) / Usuários ativos (logados nas últimas 24 horas)
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as active 
                FROM usuarios 
                WHERE ultimo_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $metrics[] = [
                    'type' => 'application',
                    'name' => 'users_active_24h',
                    'value' => (int)$row['active'],
                    'unit' => 'count',
                    'tags' => ['entity' => 'users', 'period' => '24h']
                ];
            }
            
            // Children statistics / Estatísticas de crianças
            if ($this->tableExists('criancas')) {
                $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM criancas");
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $metrics[] = [
                        'type' => 'application',
                        'name' => 'children_total',
                        'value' => (int)$row['total'],
                        'unit' => 'count',
                        'tags' => ['entity' => 'children']
                    ];
                }
            }
            
            // Routes statistics / Estatísticas de rotas
            if ($this->tableExists('rotas')) {
                $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM rotas");
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $metrics[] = [
                        'type' => 'application',
                        'name' => 'routes_total',
                        'value' => (int)$row['total'],
                        'unit' => 'count',
                        'tags' => ['entity' => 'routes']
                    ];
                }
            }
            
            // Backup statistics / Estatísticas de backup
            if ($this->tableExists('backup_history')) {
                $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM backup_history");
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $metrics[] = [
                        'type' => 'application',
                        'name' => 'backups_total',
                        'value' => (int)$row['total'],
                        'unit' => 'count',
                        'tags' => ['entity' => 'backups']
                    ];
                }
                
                // Recent backups / Backups recentes
                $stmt = $this->pdo->query("
                    SELECT COUNT(*) as recent 
                    FROM backup_history 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ");
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $metrics[] = [
                        'type' => 'application',
                        'name' => 'backups_recent_7d',
                        'value' => (int)$row['recent'],
                        'unit' => 'count',
                        'tags' => ['entity' => 'backups', 'period' => '7d']
                    ];
                }
            }
            
            // Notifications statistics / Estatísticas de notificações
            if ($this->tableExists('notifications')) {
                $stmt = $this->pdo->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
                    FROM notifications
                ");
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $metrics[] = [
                        'type' => 'application',
                        'name' => 'notifications_total',
                        'value' => (int)$row['total'],
                        'unit' => 'count',
                        'tags' => ['entity' => 'notifications']
                    ];
                    
                    $metrics[] = [
                        'type' => 'application',
                        'name' => 'notifications_unread',
                        'value' => (int)$row['unread'],
                        'unit' => 'count',
                        'tags' => ['entity' => 'notifications', 'status' => 'unread']
                    ];
                }
            }
            
            // Security events / Eventos de segurança
            if ($this->tableExists('security_events')) {
                $stmt = $this->pdo->query("
                    SELECT COUNT(*) as total 
                    FROM security_events 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ");
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $metrics[] = [
                        'type' => 'security',
                        'name' => 'security_events_24h',
                        'value' => (int)$row['total'],
                        'unit' => 'count',
                        'tags' => ['entity' => 'security', 'period' => '24h']
                    ];
                }
            }
            
        } catch (Exception $e) {
            log_warning('Error collecting application metrics', [
                'error' => $e->getMessage()
            ], 'performance');
        }
        
        return $metrics;
    }
    
    /**
     * Get cache-level metrics / Obter métricas do cache
     */
    private function getCacheMetrics() {
        $metrics = [];
        
        try {
            $cache_stats = $this->cache->getStats();
            
            foreach ($cache_stats as $stat_name => $value) {
                $unit = 'count';
                $tags = ['type' => 'cache'];
                
                // Determine appropriate unit / Determinar unidade apropriada
                if (strpos($stat_name, 'ratio') !== false || strpos($stat_name, 'percentage') !== false) {
                    $unit = 'percent';
                } elseif (strpos($stat_name, 'time') !== false) {
                    $unit = 'ms';
                } elseif (strpos($stat_name, 'size') !== false || strpos($stat_name, 'memory') !== false) {
                    $unit = 'bytes';
                }
                
                $metrics[] = [
                    'type' => 'cache',
                    'name' => $stat_name,
                    'value' => is_numeric($value) ? (float)$value : 0,
                    'unit' => $unit,
                    'tags' => $tags
                ];
            }
            
        } catch (Exception $e) {
            log_warning('Error collecting cache metrics', [
                'error' => $e->getMessage()
            ], 'performance');
        }
        
        return $metrics;
    }
    
    /**
     * Store metrics in database / Armazenar métricas no banco de dados
     */
    private function storeMetrics($metrics) {
        if (empty($metrics)) {
            return;
        }
        
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO system_metrics (metric_type, metric_name, metric_value, metric_unit, tags) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($metrics as $metric) {
                $stmt->execute([
                    $metric['type'],
                    $metric['name'],
                    $metric['value'],
                    $metric['unit'],
                    json_encode($metric['tags'])
                ]);
            }
            
            $this->pdo->commit();
            
            // Cache latest metrics for real-time access / Cachear métricas mais recentes para acesso em tempo real
            if ($this->config['realtime_metrics']) {
                $this->cache->set('latest_metrics', $metrics, 120); // Cache for 2 minutes
            }
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            log_error('Failed to store metrics', [
                'error' => $e->getMessage(),
                'metrics_count' => count($metrics)
            ], 'performance');
            
            throw $e;
        }
    }
    
    /**
     * Create performance snapshot / Criar snapshot de performance
     */
    private function createPerformanceSnapshot($metrics) {
        try {
            // Extract key metrics for snapshot / Extrair métricas principais para snapshot
            $snapshot_data = [
                'cpu_usage' => $this->extractMetricValue($metrics, 'cpu_load_1min'),
                'memory_usage' => $this->extractMetricValue($metrics, 'memory_usage_percentage'),
                'disk_usage' => $this->extractMetricValue($metrics, 'disk_usage_percentage'),
                'database_connections' => $this->extractMetricValue($metrics, 'connections_active'),
                'cache_hit_ratio' => $this->extractMetricValue($metrics, 'hit_ratio'),
                'error_count' => 0, // Will be filled from logs if available
                'warning_count' => 0, // Will be filled from logs if available
            ];
            
            // Calculate network I/O if available / Calcular I/O de rede se disponível
            $network_io = [];
            foreach ($metrics as $metric) {
                if ($metric['type'] === 'network' || strpos($metric['name'], 'network') !== false) {
                    $network_io[$metric['name']] = $metric['value'];
                }
            }
            
            // Collect custom metrics / Coletar métricas personalizadas
            $custom_metrics = [];
            foreach ($metrics as $metric) {
                if ($metric['type'] === 'custom' || $metric['type'] === 'application') {
                    $custom_metrics[$metric['name']] = [
                        'value' => $metric['value'],
                        'unit' => $metric['unit'],
                        'tags' => $metric['tags']
                    ];
                }
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO performance_snapshots (
                    cpu_usage, memory_usage, disk_usage, network_io, 
                    database_connections, cache_hit_ratio, custom_metrics
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $snapshot_data['cpu_usage'],
                $snapshot_data['memory_usage'],
                $snapshot_data['disk_usage'],
                json_encode($network_io),
                $snapshot_data['database_connections'],
                $snapshot_data['cache_hit_ratio'],
                json_encode($custom_metrics)
            ]);
            
        } catch (Exception $e) {
            log_error('Failed to create performance snapshot', [
                'error' => $e->getMessage()
            ], 'performance');
        }
    }
    
    /**
     * Check alert thresholds / Verificar limites de alerta
     */
    private function checkAlertThresholds($metrics) {
        try {
            $alerts = [];
            
            foreach ($metrics as $metric) {
                $alert = $this->evaluateMetricAlert($metric);
                if ($alert) {
                    $alerts[] = $alert;
                }
            }
            
            // Store alerts / Armazenar alertas
            if (!empty($alerts)) {
                $this->storeAlerts($alerts);
            }
            
        } catch (Exception $e) {
            log_error('Failed to check alert thresholds', [
                'error' => $e->getMessage()
            ], 'performance');
        }
    }
    
    /**
     * Evaluate metric against alert thresholds / Avaliar métrica contra limites de alerta
     */
    private function evaluateMetricAlert($metric) {
        $thresholds = $this->config['alert_thresholds'];
        
        // CPU Load Alert / Alerta de carga da CPU
        if ($metric['name'] === 'cpu_load_1min' && isset($thresholds['cpu_usage'])) {
            if ($metric['value'] > $thresholds['cpu_usage']) {
                return [
                    'type' => 'cpu_overload',
                    'severity' => $metric['value'] > $thresholds['cpu_usage'] * 1.5 ? 'critical' : 'high',
                    'metric_name' => $metric['name'],
                    'threshold_value' => $thresholds['cpu_usage'],
                    'actual_value' => $metric['value'],
                    'message' => "High CPU load detected: {$metric['value']} (threshold: {$thresholds['cpu_usage']})"
                ];
            }
        }
        
        // Memory Usage Alert / Alerta de uso de memória
        if ($metric['name'] === 'memory_usage_percentage' && isset($thresholds['memory_usage'])) {
            if ($metric['value'] > $thresholds['memory_usage']) {
                return [
                    'type' => 'memory_overuse',
                    'severity' => $metric['value'] > $thresholds['memory_usage'] * 1.2 ? 'critical' : 'high',
                    'metric_name' => $metric['name'],
                    'threshold_value' => $thresholds['memory_usage'],
                    'actual_value' => $metric['value'],
                    'message' => "High memory usage detected: {$metric['value']}% (threshold: {$thresholds['memory_usage']}%)"
                ];
            }
        }
        
        // Disk Usage Alert / Alerta de uso de disco
        if ($metric['name'] === 'disk_usage_percentage' && isset($thresholds['disk_usage'])) {
            if ($metric['value'] > $thresholds['disk_usage']) {
                return [
                    'type' => 'disk_space_low',
                    'severity' => $metric['value'] > $thresholds['disk_usage'] * 1.1 ? 'critical' : 'high',
                    'metric_name' => $metric['name'],
                    'threshold_value' => $thresholds['disk_usage'],
                    'actual_value' => $metric['value'],
                    'message' => "Low disk space detected: {$metric['value']}% used (threshold: {$thresholds['disk_usage']}%)"
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Store alerts in database / Armazenar alertas no banco de dados
     */
    private function storeAlerts($alerts) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO metric_alerts (alert_type, severity, metric_name, threshold_value, actual_value, message) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($alerts as $alert) {
                $stmt->execute([
                    $alert['type'],
                    $alert['severity'],
                    $alert['metric_name'],
                    $alert['threshold_value'],
                    $alert['actual_value'],
                    $alert['message']
                ]);
                
                // Log high/critical alerts / Registrar alertas críticos/altos
                if (in_array($alert['severity'], ['high', 'critical'])) {
                    log_warning('System alert triggered', $alert, 'performance');
                }
            }
            
        } catch (Exception $e) {
            log_error('Failed to store alerts', [
                'error' => $e->getMessage(),
                'alerts_count' => count($alerts)
            ], 'performance');
        }
    }
    
    /**
     * Get metrics data for dashboard / Obter dados de métricas para dashboard
     */
    public function getMetricsForDashboard($time_range = '1h', $metric_types = []) {
        try {
            $time_condition = $this->getTimeCondition($time_range);
            $type_condition = '';
            
            if (!empty($metric_types)) {
                $placeholders = str_repeat('?,', count($metric_types) - 1) . '?';
                $type_condition = " AND metric_type IN ($placeholders)";
            }
            
            $sql = "
                SELECT 
                    metric_type, metric_name, metric_value, metric_unit, tags, timestamp
                FROM system_metrics 
                WHERE timestamp >= $time_condition $type_condition
                ORDER BY timestamp ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($metric_types);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process results for charts / Processar resultados para gráficos
            $processed_data = [];
            foreach ($results as $row) {
                $key = $row['metric_type'] . '.' . $row['metric_name'];
                
                if (!isset($processed_data[$key])) {
                    $processed_data[$key] = [
                        'name' => $row['metric_name'],
                        'type' => $row['metric_type'],
                        'unit' => $row['metric_unit'],
                        'data' => []
                    ];
                }
                
                $processed_data[$key]['data'][] = [
                    'timestamp' => $row['timestamp'],
                    'value' => (float)$row['metric_value'],
                    'tags' => json_decode($row['tags'], true)
                ];
            }
            
            return $processed_data;
            
        } catch (Exception $e) {
            log_error('Failed to get metrics for dashboard', [
                'error' => $e->getMessage(),
                'time_range' => $time_range
            ], 'performance');
            
            return [];
        }
    }
    
    /**
     * Get system health summary / Obter resumo de saúde do sistema
     */
    public function getSystemHealthSummary() {
        try {
            // Get latest metrics / Obter métricas mais recentes
            $latest_metrics = $this->cache->get('latest_metrics');
            
            if (!$latest_metrics) {
                $stmt = $this->pdo->query("
                    SELECT metric_type, metric_name, metric_value, metric_unit 
                    FROM system_metrics 
                    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                    ORDER BY timestamp DESC
                ");
                $latest_metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Calculate health scores / Calcular pontuações de saúde
            $health_summary = [
                'overall_score' => 100,
                'cpu_health' => 100,
                'memory_health' => 100,
                'disk_health' => 100,
                'database_health' => 100,
                'cache_health' => 100,
                'alerts' => [],
                'recommendations' => []
            ];
            
            // Evaluate each metric / Avaliar cada métrica
            foreach ($latest_metrics as $metric) {
                $this->evaluateHealthMetric($metric, $health_summary);
            }
            
            // Get recent alerts / Obter alertas recentes
            $stmt = $this->pdo->query("
                SELECT alert_type, severity, message 
                FROM metric_alerts 
                WHERE resolved = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ORDER BY severity DESC, created_at DESC
                LIMIT 10
            ");
            $health_summary['alerts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate overall score / Calcular pontuação geral
            $health_summary['overall_score'] = round(
                ($health_summary['cpu_health'] + 
                 $health_summary['memory_health'] + 
                 $health_summary['disk_health'] + 
                 $health_summary['database_health'] + 
                 $health_summary['cache_health']) / 5
            );
            
            return $health_summary;
            
        } catch (Exception $e) {
            log_error('Failed to get system health summary', [
                'error' => $e->getMessage()
            ], 'performance');
            
            return [
                'overall_score' => 0,
                'error' => 'Failed to get health data'
            ];
        }
    }
    
    // Helper methods / Métodos auxiliares
    
    private function parseMemoryLimit($limit) {
        if ($limit === '-1') return PHP_INT_MAX;
        
        $unit = strtolower(substr($limit, -1));
        $value = (int)$limit;
        
        switch ($unit) {
            case 'g': return $value * 1024 * 1024 * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'k': return $value * 1024;
            default: return $value;
        }
    }
    
    private function tableExists($table_name) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() AND table_name = ?
            ");
            $stmt->execute([$table_name]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function extractMetricValue($metrics, $metric_name) {
        foreach ($metrics as $metric) {
            if ($metric['name'] === $metric_name) {
                return $metric['value'];
            }
        }
        return null;
    }
    
    private function getTimeCondition($time_range) {
        switch ($time_range) {
            case '5m': return 'DATE_SUB(NOW(), INTERVAL 5 MINUTE)';
            case '15m': return 'DATE_SUB(NOW(), INTERVAL 15 MINUTE)';
            case '1h': return 'DATE_SUB(NOW(), INTERVAL 1 HOUR)';
            case '6h': return 'DATE_SUB(NOW(), INTERVAL 6 HOUR)';
            case '24h': return 'DATE_SUB(NOW(), INTERVAL 24 HOUR)';
            case '7d': return 'DATE_SUB(NOW(), INTERVAL 7 DAY)';
            case '30d': return 'DATE_SUB(NOW(), INTERVAL 30 DAY)';
            default: return 'DATE_SUB(NOW(), INTERVAL 1 HOUR)';
        }
    }
    
    private function evaluateHealthMetric($metric, &$health_summary) {
        $thresholds = $this->config['alert_thresholds'];
        
        switch ($metric['metric_name']) {
            case 'cpu_load_1min':
                if (isset($thresholds['cpu_usage'])) {
                    $health_summary['cpu_health'] = max(0, 100 - (($metric['metric_value'] / $thresholds['cpu_usage']) * 100));
                }
                break;
                
            case 'memory_usage_percentage':
                if (isset($thresholds['memory_usage'])) {
                    $health_summary['memory_health'] = max(0, 100 - $metric['metric_value']);
                }
                break;
                
            case 'disk_usage_percentage':
                if (isset($thresholds['disk_usage'])) {
                    $health_summary['disk_health'] = max(0, 100 - $metric['metric_value']);
                }
                break;
        }
    }
}

/**
 * Helper functions / Funções auxiliares
 */

function collect_system_metrics() {
    $collector = VanTracingMetricsCollector::getInstance();
    return $collector->collectSystemMetrics();
}

function get_metrics_for_dashboard($time_range = '1h', $metric_types = []) {
    $collector = VanTracingMetricsCollector::getInstance();
    return $collector->getMetricsForDashboard($time_range, $metric_types);
}

function get_system_health_summary() {
    $collector = VanTracingMetricsCollector::getInstance();
    return $collector->getSystemHealthSummary();
}
?>