<?php
/**
 * Notification Management API / API de Gerenciamento de Notificações
 * 
 * RESTful API for managing user notifications
 * API RESTful para gerenciar notificações do usuário
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

require_once 'security_helper.php';
require_once 'notification_system.php';
require_once 'performance_monitor.php';

// Apply security middleware / Aplicar middleware de segurança
secure_api([
    'session' => true,
    'rate_limit' => 30, // 30 requests per minute
    'csrf' => true
]);

// Start performance monitoring / Iniciar monitoramento de performance
PerformanceMonitor::startTimer('notification_api');

$user_id = $_SESSION['user_id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if (!$user_id) {
    send_error('Authentication required', 'Autenticação necessária', 401);
}

$notification_manager = VanTracingNotificationManager::getInstance();

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($notification_manager, $user_id, $action);
            break;
            
        case 'POST':
            handlePostRequest($notification_manager, $user_id, $action);
            break;
            
        case 'PUT':
            handlePutRequest($notification_manager, $user_id, $action);
            break;
            
        default:
            send_error('Method not allowed', 'Método não permitido', 405);
    }
    
} catch (Exception $e) {
    log_error('Notification API error', [
        'user_id' => $user_id,
        'method' => $method,
        'action' => $action,
        'error' => $e->getMessage()
    ], 'api');
    
    send_error('Internal server error', 'Erro interno do servidor', 500);
}

/**
 * Handle GET requests / Manipular requisições GET
 */
function handleGetRequest($notification_manager, $user_id, $action) {
    switch ($action) {
        case 'list':
            // Get user notifications / Obter notificações do usuário
            $limit = min((int)($_GET['limit'] ?? 50), 100); // Max 100
            $offset = max((int)($_GET['offset'] ?? 0), 0);
            $unread_only = filter_var($_GET['unread_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            $notifications = $notification_manager->getUserNotifications($user_id, $limit, $offset, $unread_only);
            
            send_success([
                'notifications' => $notifications,
                'count' => count($notifications),
                'limit' => $limit,
                'offset' => $offset,
                'unread_only' => $unread_only
            ], 'Notifications retrieved', 'Notificações obtidas');
            break;
            
        case 'unread_count':
            // Get unread notification count / Obter contagem de notificações não lidas
            $unread_notifications = $notification_manager->getUserNotifications($user_id, 1000, 0, true);
            
            send_success([
                'unread_count' => count($unread_notifications)
            ], 'Unread count retrieved', 'Contagem de não lidas obtida');
            break;
            
        case 'stats':
            // Get notification statistics / Obter estatísticas de notificação
            $hours = min((int)($_GET['hours'] ?? 24), 168); // Max 1 week
            $stats = $notification_manager->getNotificationStats($user_id, $hours);
            
            send_success([
                'stats' => $stats,
                'period_hours' => $hours
            ], 'Statistics retrieved', 'Estatísticas obtidas');
            break;
            
        default:
            send_error('Invalid action', 'Ação inválida', 400);
    }
}

/**
 * Handle POST requests / Manipular requisições POST
 */
function handlePostRequest($notification_manager, $user_id, $action) {
    switch ($action) {
        case 'send':
            // Send notification (admin only) / Enviar notificação (apenas admin)
            if (!isAdmin($user_id)) {
                send_error('Insufficient permissions', 'Permissões insuficientes', 403);
            }
            
            $target_user_ids = $_POST['user_ids'] ?? [];
            $type = clean_input($_POST['type'] ?? '', 'string');
            $title = clean_input($_POST['title'] ?? '', 'string');
            $message = clean_input($_POST['message'] ?? '', 'string');
            $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
            $priority = clean_input($_POST['priority'] ?? 'medium', 'string');
            $channels = $_POST['channels'] ?? ['sse'];
            
            if (empty($target_user_ids) || empty($type) || empty($title) || empty($message)) {
                send_error('Missing required fields', 'Campos obrigatórios ausentes', 400);
            }
            
            $options = [
                'priority' => $priority,
                'channels' => $channels
            ];
            
            $notification_ids = $notification_manager->sendNotification(
                $target_user_ids, $type, $title, $message, $data, $options
            );
            
            send_success([
                'notification_ids' => $notification_ids,
                'sent_to_users' => count($target_user_ids)
            ], 'Notifications sent', 'Notificações enviadas');
            break;
            
        case 'test':
            // Send test notification / Enviar notificação de teste
            $test_title = 'Notificação de Teste';
            $test_message = 'Esta é uma notificação de teste do sistema VanTracing.';
            
            $notification_ids = $notification_manager->sendNotification(
                $user_id, 
                NotificationType::SYSTEM_MESSAGE, 
                $test_title, 
                $test_message,
                ['test' => true, 'timestamp' => time()],
                ['priority' => 'low', 'channels' => ['sse']]
            );
            
            send_success([
                'notification_ids' => $notification_ids,
                'message' => 'Test notification sent'
            ], 'Test notification sent', 'Notificação de teste enviada');
            break;
            
        default:
            send_error('Invalid action', 'Ação inválida', 400);
    }
}

/**
 * Handle PUT requests / Manipular requisições PUT
 */
function handlePutRequest($notification_manager, $user_id, $action) {
    switch ($action) {
        case 'mark_read':
            // Mark notifications as read / Marcar notificações como lidas
            $input = json_decode(file_get_contents('php://input'), true);
            $notification_ids = $input['notification_ids'] ?? [];
            
            if (empty($notification_ids)) {
                send_error('No notification IDs provided', 'IDs de notificação não fornecidos', 400);
            }
            
            $updated_count = $notification_manager->markAsRead($notification_ids, $user_id);
            
            send_success([
                'updated_count' => $updated_count,
                'notification_ids' => $notification_ids
            ], 'Notifications marked as read', 'Notificações marcadas como lidas');
            break;
            
        case 'mark_all_read':
            // Mark all notifications as read / Marcar todas as notificações como lidas
            $unread_notifications = $notification_manager->getUserNotifications($user_id, 1000, 0, true);
            $notification_ids = array_column($unread_notifications, 'id');
            
            if (!empty($notification_ids)) {
                $updated_count = $notification_manager->markAsRead($notification_ids, $user_id);
                
                send_success([
                    'updated_count' => $updated_count
                ], 'All notifications marked as read', 'Todas as notificações marcadas como lidas');
            } else {
                send_success([
                    'updated_count' => 0
                ], 'No unread notifications', 'Nenhuma notificação não lida');
            }
            break;
            
        default:
            send_error('Invalid action', 'Ação inválida', 400);
    }
}

/**
 * Check if user is admin / Verificar se usuário é admin
 */
function isAdmin($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT tipo FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user && ($user['tipo'] === 'admin' || $user['tipo'] === 'administrador');
}

// End performance monitoring / Finalizar monitoramento de performance
PerformanceMonitor::endTimer('notification_api', [
    'method' => $method,
    'action' => $action,
    'user_id' => $user_id
]);
?>