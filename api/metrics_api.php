<?php
/**
 * VanTracing Metrics API / API de Métricas VanTracing
 * 
 * RESTful API for system monitoring and metrics collection
 * API RESTful para monitoramento do sistema e coleta de métricas
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

require_once 'metrics_system.php';
require_once 'security_helper.php';

// Apply security middleware / Aplicar middleware de segurança
secure_api(['rate_limit' => 120, 'interval' => 60]); // 120 requests per minute for metrics

header('Content-Type: application/json; charset=utf-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = isset($_GET['action']) ? $_GET['action'] : '';
    
    $response = [];
    
    switch ($method) {
        case 'GET':
            switch ($path) {
                case 'collect':
                    // Trigger metrics collection / Acionar coleta de métricas
                    require_permission('admin');
                    
                    $result = collect_system_metrics();
                    
                    $response = [
                        'success' => true,
                        'message' => 'Metrics collected successfully',
                        'data' => $result
                    ];
                    break;
                    
                case 'dashboard':
                    // Get metrics for dashboard / Obter métricas para dashboard
                    $time_range = $_GET['range'] ?? '1h';
                    $metric_types = isset($_GET['types']) ? explode(',', $_GET['types']) : [];
                    
                    $metrics_data = get_metrics_for_dashboard($time_range, $metric_types);
                    
                    $response = [
                        'success' => true,
                        'data' => $metrics_data,
                        'meta' => [
                            'time_range' => $time_range,
                            'metric_types' => $metric_types,
                            'generated_at' => date('c')
                        ]
                    ];
                    break;
                    
                case 'health':
                    // Get system health summary / Obter resumo de saúde do sistema
                    $health_summary = get_system_health_summary();
                    
                    $response = [
                        'success' => true,
                        'data' => $health_summary,
                        'meta' => [
                            'generated_at' => date('c')
                        ]
                    ];
                    break;
                    
                case 'realtime':
                    // Get real-time metrics / Obter métricas em tempo real
                    $collector = VanTracingMetricsCollector::getInstance();
                    $cache = VanTracingCache::getInstance();
                    
                    $latest_metrics = $cache->get('latest_metrics');
                    
                    if (!$latest_metrics) {
                        // Collect fresh metrics if none cached / Coletar métricas frescas se nenhuma em cache
                        collect_system_metrics();
                        $latest_metrics = $cache->get('latest_metrics') ?: [];
                    }
                    
                    $response = [
                        'success' => true,
                        'data' => $latest_metrics,
                        'meta' => [
                            'cached' => true,
                            'generated_at' => date('c')
                        ]
                    ];
                    break;
                    
                case 'alerts':
                    // Get recent alerts / Obter alertas recentes
                    $limit = min((int)($_GET['limit'] ?? 20), 100);
                    $severity = $_GET['severity'] ?? '';
                    
                    $collector = VanTracingMetricsCollector::getInstance();
                    
                    // Build query / Construir consulta
                    $where_conditions = ['created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)'];
                    $params = [];
                    
                    if ($severity && in_array($severity, ['low', 'medium', 'high', 'critical'])) {
                        $where_conditions[] = 'severity = ?';
                        $params[] = $severity;
                    }
                    
                    $where_clause = implode(' AND ', $where_conditions);
                    
                    global $conn;
                    $stmt = $conn->prepare("
                        SELECT 
                            id, alert_type, severity, metric_name, 
                            threshold_value, actual_value, message, 
                            resolved, created_at
                        FROM metric_alerts 
                        WHERE $where_clause
                        ORDER BY 
                            CASE severity 
                                WHEN 'critical' THEN 1 
                                WHEN 'high' THEN 2 
                                WHEN 'medium' THEN 3 
                                WHEN 'low' THEN 4 
                            END,
                            created_at DESC
                        LIMIT ?
                    ");
                    
                    $params[] = $limit;
                    $stmt->execute($params);
                    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $response = [
                        'success' => true,
                        'data' => $alerts,
                        'meta' => [
                            'count' => count($alerts),
                            'limit' => $limit,
                            'severity_filter' => $severity ?: 'all'
                        ]
                    ];
                    break;
                    
                case 'performance_snapshots':
                    // Get performance snapshots / Obter snapshots de performance
                    $limit = min((int)($_GET['limit'] ?? 50), 200);
                    $hours = min((int)($_GET['hours'] ?? 24), 168); // Max 1 week
                    
                    global $conn;
                    $stmt = $conn->prepare("
                        SELECT 
                            id, cpu_usage, memory_usage, disk_usage, 
                            database_connections, cache_hit_ratio, 
                            custom_metrics, created_at
                        FROM performance_snapshots 
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                        ORDER BY created_at DESC
                        LIMIT ?
                    ");
                    
                    $stmt->execute([$hours, $limit]);
                    $snapshots = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Process custom metrics / Processar métricas customizadas
                    foreach ($snapshots as &$snapshot) {
                        if ($snapshot['custom_metrics']) {
                            $snapshot['custom_metrics'] = json_decode($snapshot['custom_metrics'], true);
                        }
                    }
                    
                    $response = [
                        'success' => true,
                        'data' => $snapshots,
                        'meta' => [
                            'count' => count($snapshots),
                            'hours_range' => $hours,
                            'limit' => $limit
                        ]
                    ];
                    break;
                    
                case 'stats':
                    // Get metrics statistics / Obter estatísticas das métricas
                    global $conn;
                    
                    // Total metrics collected / Total de métricas coletadas
                    $stmt = $conn->query("SELECT COUNT(*) as total FROM system_metrics");
                    $total_metrics = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    // Recent metrics (last hour) / Métricas recentes (última hora)
                    $stmt = $conn->query("
                        SELECT COUNT(*) as recent 
                        FROM system_metrics 
                        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    ");
                    $recent_metrics = $stmt->fetch(PDO::FETCH_ASSOC)['recent'];
                    
                    // Active alerts / Alertas ativos
                    $stmt = $conn->query("
                        SELECT COUNT(*) as active_alerts 
                        FROM metric_alerts 
                        WHERE resolved = 0
                    ");
                    $active_alerts = $stmt->fetch(PDO::FETCH_ASSOC)['active_alerts'];
                    
                    // Metrics by type / Métricas por tipo
                    $stmt = $conn->query("
                        SELECT metric_type, COUNT(*) as count 
                        FROM system_metrics 
                        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        GROUP BY metric_type
                    ");
                    $metrics_by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $response = [
                        'success' => true,
                        'data' => [
                            'total_metrics' => (int)$total_metrics,
                            'recent_metrics' => (int)$recent_metrics,
                            'active_alerts' => (int)$active_alerts,
                            'metrics_by_type' => $metrics_by_type
                        ],
                        'meta' => [
                            'generated_at' => date('c')
                        ]
                    ];
                    break;
                    
                default:
                    throw new Exception('Invalid GET action');
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($path) {
                case 'resolve_alert':
                    // Resolve an alert / Resolver um alerta
                    require_permission('admin');
                    
                    $alert_id = (int)($input['alert_id'] ?? 0);
                    $user_id = $_SESSION['user_id'] ?? null;
                    
                    if ($alert_id <= 0) {
                        throw new Exception('Invalid alert ID');
                    }
                    
                    global $conn;
                    $stmt = $conn->prepare("
                        UPDATE metric_alerts 
                        SET resolved = 1, resolved_at = NOW(), resolved_by = ?
                        WHERE id = ? AND resolved = 0
                    ");
                    
                    $updated = $stmt->execute([$user_id, $alert_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        log_info('Alert resolved', [
                            'alert_id' => $alert_id,
                            'resolved_by' => $user_id
                        ], 'performance');
                        
                        $response = [
                            'success' => true,
                            'message' => 'Alert resolved successfully',
                            'alert_id' => $alert_id
                        ];
                    } else {
                        throw new Exception('Alert not found or already resolved');
                    }
                    break;
                    
                case 'custom_metric':
                    // Store custom metric / Armazenar métrica personalizada
                    require_permission('user');
                    
                    $metric_name = clean_input($input['name'] ?? '');
                    $metric_value = (float)($input['value'] ?? 0);
                    $metric_unit = clean_input($input['unit'] ?? '');
                    $tags = $input['tags'] ?? [];
                    
                    if (empty($metric_name)) {
                        throw new Exception('Metric name is required');
                    }
                    
                    // Validate metric name / Validar nome da métrica
                    if (!preg_match('/^[a-zA-Z0-9_]+$/', $metric_name)) {
                        throw new Exception('Invalid metric name format');
                    }
                    
                    global $conn;
                    $stmt = $conn->prepare("
                        INSERT INTO system_metrics (metric_type, metric_name, metric_value, metric_unit, tags) 
                        VALUES ('custom', ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $metric_name,
                        $metric_value,
                        $metric_unit,
                        json_encode($tags)
                    ]);
                    
                    log_info('Custom metric stored', [
                        'metric_name' => $metric_name,
                        'metric_value' => $metric_value,
                        'user_id' => $_SESSION['user_id'] ?? null
                    ], 'performance');
                    
                    $response = [
                        'success' => true,
                        'message' => 'Custom metric stored successfully',
                        'metric_id' => $conn->lastInsertId()
                    ];
                    break;
                    
                default:
                    throw new Exception('Invalid POST action');
            }
            break;
            
        case 'DELETE':
            require_permission('admin');
            
            switch ($path) {
                case 'old_metrics':
                    // Clean old metrics / Limpar métricas antigas
                    $days = min((int)($_GET['days'] ?? 30), 365); // Max 1 year
                    
                    global $conn;
                    $stmt = $conn->prepare("
                        DELETE FROM system_metrics 
                        WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)
                    ");
                    
                    $stmt->execute([$days]);
                    $deleted_metrics = $stmt->rowCount();
                    
                    // Also clean old performance snapshots / Também limpar snapshots antigos
                    $stmt = $conn->prepare("
                        DELETE FROM performance_snapshots 
                        WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                    ");
                    
                    $stmt->execute([$days]);
                    $deleted_snapshots = $stmt->rowCount();
                    
                    log_info('Old metrics cleaned', [
                        'days' => $days,
                        'deleted_metrics' => $deleted_metrics,
                        'deleted_snapshots' => $deleted_snapshots,
                        'user_id' => $_SESSION['user_id'] ?? null
                    ], 'performance');
                    
                    $response = [
                        'success' => true,
                        'message' => 'Old metrics cleaned successfully',
                        'deleted' => [
                            'metrics' => $deleted_metrics,
                            'snapshots' => $deleted_snapshots
                        ]
                    ];
                    break;
                    
                default:
                    throw new Exception('Invalid DELETE action');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
    // Log successful API operation / Registrar operação bem-sucedida da API
    log_info('Metrics API operation completed', [
        'method' => $method,
        'action' => $path,
        'user_id' => $_SESSION['user_id'] ?? null,
        'success' => true
    ], 'api');
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Log API error / Registrar erro da API
    log_error('Metrics API error', [
        'method' => $method,
        'action' => $path ?? 'unknown',
        'error' => $e->getMessage(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip_address' => SecurityHelper::getClientIP()
    ], 'api');
    
    http_response_code(400);
    echo json_encode($error_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>