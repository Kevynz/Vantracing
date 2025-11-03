<?php
/**
 * VanTracing Metrics Collector Scheduler / Agendador do Coletor de Métricas VanTracing
 * 
 * Automated metrics collection script for continuous monitoring
 * Script de coleta automatizada de métricas para monitoramento contínuo
 * 
 * Usage / Uso:
 * Add to crontab: * * * * * /usr/bin/php /path/to/metrics_collector.php
 * Adicionar ao crontab: * * * * * /usr/bin/php /caminho/para/metrics_collector.php
 * 
 * This runs every minute / Executa a cada minuto
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Set time limit and memory / Definir limite de tempo e memória
set_time_limit(30);
ini_set('memory_limit', '128M');

// Ensure we're running from command line / Garantir que estamos executando pela linha de comando
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from command line');
}

require_once __DIR__ . '/metrics_system.php';

/**
 * Metrics Collector Scheduler Class / Classe do Agendador do Coletor de Métricas
 */
class VanTracingMetricsScheduler {
    private $collector;
    private $config;
    private $start_time;
    
    public function __construct() {
        $this->start_time = microtime(true);
        $this->collector = VanTracingMetricsCollector::getInstance();
        $this->loadConfig();
        
        echo "[" . date('Y-m-d H:i:s') . "] VanTracing Metrics Collector Started\n";
        log_info('Metrics collector scheduler started', [
            'pid' => getmypid(),
            'memory_limit' => ini_get('memory_limit')
        ], 'performance');
    }
    
    private function loadConfig() {
        $this->config = [
            'enabled' => getenv('METRICS_ENABLED') !== 'false',
            'collection_interval' => (int)(getenv('METRICS_INTERVAL') ?: 60), // 60 seconds default
            'cleanup_enabled' => getenv('METRICS_CLEANUP') !== 'false',
            'cleanup_days' => (int)(getenv('METRICS_CLEANUP_DAYS') ?: 30), // 30 days default
            'alerts_enabled' => getenv('METRICS_ALERTS') !== 'false',
            'detailed_output' => isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--verbose',
            'dry_run' => isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--dry-run',
            'force_collection' => isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--force',
            'cleanup_only' => isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--cleanup'
        ];
    }
    
    public function run() {
        try {
            if (!$this->config['enabled']) {
                echo "Metrics collection is disabled\n";
                return;
            }
            
            if ($this->config['dry_run']) {
                echo "DRY RUN MODE - No actual collection will be performed\n";
                $this->performDryRun();
                return;
            }
            
            if ($this->config['cleanup_only']) {
                echo "Cleanup mode - Only cleaning old metrics\n";
                $this->performCleanup();
                return;
            }
            
            // Check if collection should run based on interval
            if (!$this->config['force_collection'] && !$this->shouldCollectNow()) {
                if ($this->config['detailed_output']) {
                    echo "Collection not due yet based on interval\n";
                }
                return;
            }
            
            echo "Starting metrics collection process...\n";
            
            // Perform pre-collection checks
            $this->performPreCollectionChecks();
            
            // Collect system metrics
            $result = $this->collector->collectSystemMetrics();
            
            if ($result['success']) {
                $duration = round(microtime(true) - $this->start_time, 3);
                
                echo "Metrics collection completed successfully!\n";
                echo "- Metrics Collected: {$result['metrics_collected']}\n";
                echo "- Collection Time: {$result['collection_time']}ms\n";
                echo "- Total Duration: {$duration}s\n";
                
                if ($this->config['detailed_output']) {
                    $memory_peak = memory_get_peak_usage(true);
                    echo "- Peak Memory Usage: " . $this->formatBytes($memory_peak) . "\n";
                }
                
                // Post-collection operations
                $this->performPostCollectionOperations();
                
                // Update collection timestamp
                $this->updateCollectionTimestamp();
                
            } else {
                echo "Metrics collection failed!\n";
                log_error('Scheduled metrics collection failed', [
                    'error' => 'Unknown error in collection result'
                ], 'performance');
                exit(1);
            }
            
        } catch (Exception $e) {
            $duration = round(microtime(true) - $this->start_time, 3);
            
            echo "ERROR: " . $e->getMessage() . "\n";
            echo "Duration before error: {$duration}s\n";
            
            log_error('Metrics collector scheduler error', [
                'error' => $e->getMessage(),
                'duration' => $duration,
                'memory_usage' => memory_get_usage(true)
            ], 'performance');
            
            exit(1);
        }
    }
    
    private function performPreCollectionChecks() {
        if ($this->config['detailed_output']) {
            echo "Performing pre-collection checks...\n";
        }
        
        // Check memory usage
        $memory_usage = memory_get_usage(true);
        $memory_limit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        if ($memory_limit > 0) {
            $memory_percentage = ($memory_usage / $memory_limit) * 100;
            
            if ($memory_percentage > 80) {
                echo "WARNING: High memory usage detected ({$memory_percentage}%)\n";
                log_warning('High memory usage during metrics collection', [
                    'memory_percentage' => $memory_percentage,
                    'memory_usage' => $memory_usage,
                    'memory_limit' => $memory_limit
                ], 'performance');
            }
            
            if ($this->config['detailed_output']) {
                echo "- Memory usage: " . $this->formatBytes($memory_usage) . 
                     " ({$memory_percentage}% of limit)\n";
            }
        }
        
        // Check database connectivity
        try {
            global $conn;
            $stmt = $conn->query("SELECT 1");
            
            if ($this->config['detailed_output']) {
                echo "- Database connectivity: OK\n";
            }
        } catch (Exception $e) {
            throw new Exception("Database connectivity check failed: " . $e->getMessage());
        }
        
        // Check system load (if available)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            
            if ($load[0] > 10.0) { // Very high load threshold
                echo "WARNING: Very high system load detected ({$load[0]})\n";
                log_warning('Very high system load during metrics collection', [
                    'load_1min' => $load[0],
                    'load_5min' => $load[1],
                    'load_15min' => $load[2]
                ], 'performance');
            }
            
            if ($this->config['detailed_output']) {
                echo "- System load: {$load[0]} (1min), {$load[1]} (5min), {$load[2]} (15min)\n";
            }
        }
        
        if ($this->config['detailed_output']) {
            echo "Pre-collection checks completed\n";
        }
    }
    
    private function performPostCollectionOperations() {
        if ($this->config['detailed_output']) {
            echo "Performing post-collection operations...\n";
        }
        
        // Cleanup old metrics if enabled
        if ($this->config['cleanup_enabled']) {
            $this->performCleanup();
        }
        
        // Additional maintenance tasks
        $this->performMaintenance();
        
        if ($this->config['detailed_output']) {
            echo "Post-collection operations completed\n";
        }
    }
    
    private function performCleanup() {
        try {
            if ($this->config['detailed_output']) {
                echo "Starting metrics cleanup...\n";
            }
            
            $days = $this->config['cleanup_days'];
            
            global $conn;
            
            // Clean old metrics
            $stmt = $conn->prepare("
                DELETE FROM system_metrics 
                WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            $deleted_metrics = $stmt->rowCount();
            
            // Clean old performance snapshots
            $stmt = $conn->prepare("
                DELETE FROM performance_snapshots 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            $deleted_snapshots = $stmt->rowCount();
            
            // Clean old resolved alerts
            $stmt = $conn->prepare("
                DELETE FROM metric_alerts 
                WHERE resolved = 1 AND resolved_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            $deleted_alerts = $stmt->rowCount();
            
            if ($deleted_metrics > 0 || $deleted_snapshots > 0 || $deleted_alerts > 0) {
                echo "Cleanup completed:\n";
                echo "- Deleted metrics: $deleted_metrics\n";
                echo "- Deleted snapshots: $deleted_snapshots\n";
                echo "- Deleted resolved alerts: $deleted_alerts\n";
                
                log_info('Metrics cleanup completed', [
                    'days' => $days,
                    'deleted_metrics' => $deleted_metrics,
                    'deleted_snapshots' => $deleted_snapshots,
                    'deleted_alerts' => $deleted_alerts
                ], 'performance');
            } else if ($this->config['detailed_output']) {
                echo "No old metrics to clean up\n";
            }
            
        } catch (Exception $e) {
            echo "Cleanup error: " . $e->getMessage() . "\n";
            log_error('Metrics cleanup error', [
                'error' => $e->getMessage()
            ], 'performance');
        }
    }
    
    private function performMaintenance() {
        try {
            // Optimize metrics tables if they're getting large
            global $conn;
            
            $stmt = $conn->query("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    table_rows
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE() 
                AND table_name IN ('system_metrics', 'performance_snapshots', 'metric_alerts')
            ");
            
            $large_tables = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['size_mb'] > 100) { // Tables larger than 100MB
                    $large_tables[] = $row;
                }
            }
            
            if (!empty($large_tables) && $this->config['detailed_output']) {
                echo "Large tables detected (consider optimization):\n";
                foreach ($large_tables as $table) {
                    echo "- {$table['table_name']}: {$table['size_mb']}MB ({$table['table_rows']} rows)\n";
                }
            }
            
            // Update statistics (this helps with query performance)
            if ($this->config['detailed_output']) {
                echo "Updating table statistics...\n";
            }
            
            $tables = ['system_metrics', 'performance_snapshots', 'metric_alerts'];
            foreach ($tables as $table) {
                try {
                    $conn->exec("ANALYZE TABLE `$table`");
                } catch (Exception $e) {
                    if ($this->config['detailed_output']) {
                        echo "Warning: Could not analyze table $table: " . $e->getMessage() . "\n";
                    }
                }
            }
            
        } catch (Exception $e) {
            if ($this->config['detailed_output']) {
                echo "Maintenance error: " . $e->getMessage() . "\n";
            }
            
            log_warning('Metrics maintenance error', [
                'error' => $e->getMessage()
            ], 'performance');
        }
    }
    
    private function performDryRun() {
        echo "=== METRICS COLLECTION DRY RUN ===\n";
        
        try {
            echo "Configuration:\n";
            echo "- Enabled: " . ($this->config['enabled'] ? 'Yes' : 'No') . "\n";
            echo "- Collection Interval: {$this->config['collection_interval']} seconds\n";
            echo "- Cleanup Enabled: " . ($this->config['cleanup_enabled'] ? 'Yes' : 'No') . "\n";
            echo "- Cleanup Days: {$this->config['cleanup_days']}\n";
            echo "- Alerts Enabled: " . ($this->config['alerts_enabled'] ? 'Yes' : 'No') . "\n";
            
            echo "\nActions that would be performed:\n";
            echo "1. Pre-collection system checks\n";
            echo "2. Collect system metrics (CPU, memory, disk)\n";
            echo "3. Collect database metrics (connections, queries)\n";
            echo "4. Collect application metrics (users, routes, etc.)\n";
            echo "5. Collect cache metrics (hit ratio, size)\n";
            echo "6. Store metrics in database\n";
            echo "7. Create performance snapshot\n";
            echo "8. Check alert thresholds\n";
            echo "9. Clean up old metrics (if enabled)\n";
            echo "10. Perform maintenance tasks\n";
            
            // Show current database status
            global $conn;
            
            echo "\nCurrent database status:\n";
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM system_metrics");
            $total_metrics = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo "- Total metrics stored: $total_metrics\n";
            
            $stmt = $conn->query("
                SELECT COUNT(*) as recent 
                FROM system_metrics 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $recent_metrics = $stmt->fetch(PDO::FETCH_ASSOC)['recent'];
            echo "- Recent metrics (1 hour): $recent_metrics\n";
            
            $stmt = $conn->query("
                SELECT COUNT(*) as active_alerts 
                FROM metric_alerts 
                WHERE resolved = 0
            ");
            $active_alerts = $stmt->fetch(PDO::FETCH_ASSOC)['active_alerts'];
            echo "- Active alerts: $active_alerts\n";
            
            echo "\n=== END DRY RUN ===\n";
            
        } catch (Exception $e) {
            echo "Dry run error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function shouldCollectNow() {
        try {
            // Check last collection time from cache or database
            $cache = VanTracingCache::getInstance();
            $last_collection = $cache->get('last_metrics_collection');
            
            if (!$last_collection) {
                // Check database for last metric timestamp
                global $conn;
                $stmt = $conn->query("
                    SELECT MAX(timestamp) as last_timestamp 
                    FROM system_metrics
                ");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['last_timestamp']) {
                    $last_collection = strtotime($result['last_timestamp']);
                } else {
                    return true; // No previous collection found
                }
            }
            
            $current_time = time();
            $time_diff = $current_time - $last_collection;
            
            return $time_diff >= $this->config['collection_interval'];
            
        } catch (Exception $e) {
            // If we can't determine last collection time, collect anyway
            return true;
        }
    }
    
    private function updateCollectionTimestamp() {
        try {
            $cache = VanTracingCache::getInstance();
            $cache->set('last_metrics_collection', time(), 3600); // Cache for 1 hour
        } catch (Exception $e) {
            // Non-critical error, just log it
            if ($this->config['detailed_output']) {
                echo "Warning: Could not update collection timestamp: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Helper methods
    
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
    
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Script execution
try {
    $scheduler = new VanTracingMetricsScheduler();
    $scheduler->run();
    
    echo "[" . date('Y-m-d H:i:s') . "] VanTracing Metrics Collector Finished Successfully\n";
    exit(0);
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>