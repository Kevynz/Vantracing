<?php
/**
 * VanTracing Admin Panel Controller / Controlador do Painel Administrativo VanTracing
 * 
 * Complete administrative dashboard for system management
 * Dashboard administrativo completo para gerenciamento do sistema
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

require_once 'security_helper.php';
require_once 'logger.php';
require_once 'cache_system.php';
require_once 'performance_monitor.php';
require_once 'notification_system.php';

// Apply strict security for admin panel / Aplicar segurança rigorosa para painel admin
secure_api([
    'session' => true,
    'admin_only' => true,
    'rate_limit' => 60, // 60 requests per minute for admin
    'csrf' => true
]);

// Start performance monitoring / Iniciar monitoramento de performance
PerformanceMonitor::startTimer('admin_panel_request');

class VanTracingAdminPanel {
    private $pdo;
    private $current_admin;
    private $config;
    
    public function __construct() {
        global $conn;
        $this->pdo = $conn;
        $this->current_admin = $this->getCurrentAdmin();
        $this->config = $this->loadConfig();
        
        // Log admin access / Registrar acesso admin
        log_info('Admin panel accessed', [
            'admin_id' => $this->current_admin['id'],
            'admin_name' => $this->current_admin['nome'],
            'ip' => SecurityHelper::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], 'security');
    }
    
    private function getCurrentAdmin() {
        $user_id = $_SESSION['user_id'];
        
        $stmt = $this->pdo->prepare("
            SELECT id, nome, email, tipo, created_at, last_login 
            FROM usuarios 
            WHERE id = ? AND tipo IN ('admin', 'administrador')
        ");
        $stmt->execute([$user_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            send_error('Access denied', 'Acesso negado - privilégios administrativos necessários', 403);
        }
        
        return $admin;
    }
    
    private function loadConfig() {
        return [
            'app_name' => 'VanTracing',
            'version' => '2.0',
            'debug_mode' => getenv('APP_DEBUG') === 'true',
            'cache_enabled' => getenv('CACHE_ENABLED') !== 'false',
            'notifications_enabled' => getenv('NOTIFICATIONS_ENABLED') !== 'false',
            'performance_monitoring' => getenv('PERFORMANCE_MONITORING') !== 'false'
        ];
    }
    
    /**
     * Handle admin panel requests / Manipular requisições do painel admin
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? 'dashboard';
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($action) {
                case 'dashboard':
                    return $this->getDashboardData();
                    
                case 'users':
                    return $method === 'GET' ? $this->getUsers() : $this->manageUser();
                    
                case 'system_info':
                    return $this->getSystemInfo();
                    
                case 'logs':
                    return $this->getLogs();
                    
                case 'cache':
                    return $method === 'GET' ? $this->getCacheInfo() : $this->manageCache();
                    
                case 'notifications':
                    return $method === 'GET' ? $this->getNotifications() : $this->sendNotification();
                    
                case 'settings':
                    return $method === 'GET' ? $this->getSettings() : $this->updateSettings();
                    
                case 'security':
                    return $this->getSecurityInfo();
                    
                case 'database':
                    return $this->getDatabaseInfo();
                    
                case 'performance':
                    return $this->getPerformanceData();
                    
                default:
                    send_error('Invalid action', 'Ação inválida', 400);
            }
        } catch (Exception $e) {
            log_error('Admin panel error', [
                'action' => $action,
                'method' => $method,
                'error' => $e->getMessage(),
                'admin_id' => $this->current_admin['id']
            ], 'admin');
            
            send_error('Internal server error', 'Erro interno do servidor', 500);
        }
    }
    
    /**
     * Get dashboard overview data / Obter dados de visão geral do dashboard
     */
    private function getDashboardData() {
        $dashboard_data = [
            'system_status' => $this->getSystemStatus(),
            'user_stats' => $this->getUserStats(),
            'recent_activity' => $this->getRecentActivity(),
            'performance_metrics' => $this->getQuickPerformanceMetrics(),
            'security_alerts' => $this->getSecurityAlerts(),
            'system_health' => $this->getSystemHealth()
        ];
        
        return send_success($dashboard_data, 'Dashboard data retrieved', 'Dados do dashboard obtidos');
    }
    
    private function getSystemStatus() {
        return [
            'uptime' => $this->getSystemUptime(),
            'version' => $this->config['version'],
            'debug_mode' => $this->config['debug_mode'],
            'cache_enabled' => $this->config['cache_enabled'],
            'notifications_enabled' => $this->config['notifications_enabled'],
            'performance_monitoring' => $this->config['performance_monitoring'],
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            'php_version' => PHP_VERSION,
            'server_time' => date('Y-m-d H:i:s')
        ];
    }
    
    private function getUserStats() {
        $stats = [];
        
        // Total users / Total de usuários
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Active users (logged in last 30 days) / Usuários ativos (login nos últimos 30 dias)
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as active 
            FROM usuarios 
            WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
        
        // New users this month / Novos usuários este mês
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as new_users 
            FROM usuarios 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['new_users_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['new_users'];
        
        // Users by type / Usuários por tipo
        $stmt = $this->pdo->query("
            SELECT tipo, COUNT(*) as count 
            FROM usuarios 
            GROUP BY tipo
        ");
        $stats['users_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    private function getRecentActivity() {
        // Get recent logs / Obter logs recentes
        $logger = VanTracingLogger::getInstance();
        $recent_logs = $logger->getRecentLogs(20);
        
        // Get recent notifications / Obter notificações recentes
        $stmt = $this->pdo->query("
            SELECT n.*, u.nome as user_name
            FROM notifications n
            LEFT JOIN usuarios u ON n.user_id = u.id
            ORDER BY n.created_at DESC
            LIMIT 10
        ");
        $recent_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'recent_logs' => $recent_logs,
            'recent_notifications' => $recent_notifications
        ];
    }
    
    private function getQuickPerformanceMetrics() {
        return PerformanceMonitor::getSummary(1); // Last hour
    }
    
    private function getSecurityAlerts() {
        $logger = VanTracingLogger::getInstance();
        
        // Get security-related logs from last 24 hours / Obter logs de segurança das últimas 24 horas
        $security_logs = $logger->searchLogs('', 'security', 50, 0, [
            'start_date' => date('Y-m-d H:i:s', strtotime('-24 hours')),
            'end_date' => date('Y-m-d H:i:s'),
            'level' => ['WARNING', 'ERROR', 'CRITICAL']
        ]);
        
        $alerts = [];
        $alert_count = 0;
        
        foreach ($security_logs as $log) {
            if (in_array($log['level'], ['WARNING', 'ERROR', 'CRITICAL'])) {
                $alerts[] = [
                    'level' => $log['level'],
                    'message' => $log['message'],
                    'timestamp' => $log['timestamp'],
                    'context' => $log['context']
                ];
                $alert_count++;
            }
        }
        
        return [
            'alert_count' => $alert_count,
            'alerts' => array_slice($alerts, 0, 10) // Last 10 alerts
        ];
    }
    
    private function getSystemHealth() {
        $health = [];
        
        // Database health / Saúde do banco de dados
        try {
            $stmt = $this->pdo->query("SELECT 1");
            $health['database'] = 'healthy';
        } catch (Exception $e) {
            $health['database'] = 'error';
        }
        
        // Cache health / Saúde do cache
        try {
            VanTracingCache::set('health_check', time(), 10);
            $cached_value = VanTracingCache::get('health_check');
            $health['cache'] = $cached_value ? 'healthy' : 'warning';
        } catch (Exception $e) {
            $health['cache'] = 'error';
        }
        
        // Memory health / Saúde da memória
        $memory_usage = memory_get_usage(true);
        $memory_limit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memory_percent = ($memory_usage / $memory_limit) * 100;
        
        if ($memory_percent < 70) {
            $health['memory'] = 'healthy';
        } elseif ($memory_percent < 85) {
            $health['memory'] = 'warning';
        } else {
            $health['memory'] = 'critical';
        }
        
        $health['memory_usage_percent'] = round($memory_percent, 1);
        
        return $health;
    }
    
    /**
     * Get users data / Obter dados dos usuários
     */
    private function getUsers() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        $search = clean_input($_GET['search'] ?? '', 'string');
        $type_filter = clean_input($_GET['type'] ?? '', 'string');
        
        // Build query / Construir consulta
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(nome LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($type_filter)) {
            $where_conditions[] = "tipo = ?";
            $params[] = $type_filter;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Get total count / Obter contagem total
        $count_sql = "SELECT COUNT(*) as total FROM usuarios $where_clause";
        $stmt = $this->pdo->prepare($count_sql);
        $stmt->execute($params);
        $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get users / Obter usuários
        $sql = "
            SELECT id, nome, email, tipo, created_at, last_login,
                   (SELECT COUNT(*) FROM criancas WHERE usuario_id = usuarios.id) as children_count
            FROM usuarios 
            $where_clause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return send_success([
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total_users / $limit),
                'total_users' => $total_users,
                'limit' => $limit
            ],
            'filters' => [
                'search' => $search,
                'type' => $type_filter
            ]
        ], 'Users retrieved', 'Usuários obtidos');
    }
    
    /**
     * Get system information / Obter informações do sistema
     */
    private function getSystemInfo() {
        $system_info = [
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'operating_system' => PHP_OS,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'timezone' => date_default_timezone_get()
            ],
            'database' => $this->getDatabaseInfo(false),
            'cache' => VanTracingCache::getStats(),
            'performance' => PerformanceMonitor::getSystemMetrics(),
            'security' => [
                'https_enabled' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'session_secure' => ini_get('session.cookie_secure'),
                'session_httponly' => ini_get('session.cookie_httponly'),
                'display_errors' => ini_get('display_errors')
            ]
        ];
        
        return send_success($system_info, 'System information retrieved', 'Informações do sistema obtidas');
    }
    
    /**
     * Get database information / Obter informações do banco de dados
     */
    private function getDatabaseInfo($send_response = true) {
        try {
            // Get database version / Obter versão do banco
            $stmt = $this->pdo->query("SELECT VERSION() as version");
            $db_version = $stmt->fetch(PDO::FETCH_ASSOC)['version'];
            
            // Get table information / Obter informações das tabelas
            $stmt = $this->pdo->query("
                SELECT 
                    TABLE_NAME as table_name,
                    TABLE_ROWS as row_count,
                    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as size_mb
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY size_mb DESC
            ");
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $db_info = [
                'version' => $db_version,
                'tables' => $tables,
                'total_tables' => count($tables),
                'total_size_mb' => array_sum(array_column($tables, 'size_mb'))
            ];
            
            if ($send_response) {
                return send_success($db_info, 'Database information retrieved', 'Informações do banco obtidas');
            }
            
            return $db_info;
            
        } catch (Exception $e) {
            $error_info = ['error' => $e->getMessage()];
            
            if ($send_response) {
                return send_error('Database error', 'Erro ao obter informações do banco', 500);
            }
            
            return $error_info;
        }
    }
    
    // Helper methods / Métodos auxiliares
    
    private function getSystemUptime() {
        // This is a simplified uptime calculation
        // For accurate uptime, you'd need to track application start time
        return '24h 30m'; // Placeholder
    }
    
    private function parseMemoryLimit($memory_limit) {
        $memory_limit = trim($memory_limit);
        $last_char = strtolower($memory_limit[strlen($memory_limit) - 1]);
        $value = (int) $memory_limit;
        
        switch ($last_char) {
            case 'g': return $value * 1024 * 1024 * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'k': return $value * 1024;
            default: return $value;
        }
    }
}

// Handle the request / Manipular a requisição
$admin_panel = new VanTracingAdminPanel();
$admin_panel->handleRequest();

// End performance monitoring / Finalizar monitoramento de performance
PerformanceMonitor::endTimer('admin_panel_request', [
    'action' => $_GET['action'] ?? 'dashboard',
    'admin_id' => $_SESSION['user_id']
]);
?>