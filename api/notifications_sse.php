<?php
/**
 * Server-Sent Events (SSE) Endpoint for Real-Time Notifications
 * Endpoint Server-Sent Events (SSE) para Notificações em Tempo Real
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Security check / Verificação de segurança
require_once 'security_helper.php';
require_once 'notification_system.php';

// Apply light security for SSE (no rate limiting to allow continuous connection)
// Aplicar segurança leve para SSE (sem limitação de taxa para permitir conexão contínua)
secure_api([
    'session' => true,
    'rate_limit' => false // Disable rate limiting for SSE
]);

// Get user ID from session / Obter ID do usuário da sessão
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    exit('Unauthorized');
}

// SSE Headers / Cabeçalhos SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

// Prevent timeout / Prevenir timeout
set_time_limit(0);
ignore_user_abort(false);

/**
 * Send SSE event / Enviar evento SSE
 */
function sendSSEEvent($event_type, $data, $id = null) {
    if ($id) {
        echo "id: $id\n";
    }
    echo "event: $event_type\n";
    echo "data: " . json_encode($data) . "\n\n";
    
    // Flush output immediately / Descarregar saída imediatamente
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();
}

/**
 * Send heartbeat to keep connection alive / Enviar heartbeat para manter conexão ativa
 */
function sendHeartbeat() {
    sendSSEEvent('heartbeat', [
        'timestamp' => time(),
        'server_time' => date('Y-m-d H:i:s')
    ]);
}

// Initialize connection / Inicializar conexão
sendSSEEvent('connected', [
    'user_id' => $user_id,
    'message' => 'Connected to VanTracing notifications',
    'timestamp' => time()
]);

$last_heartbeat = time();
$last_notification_check = time();
$notification_manager = VanTracingNotificationManager::getInstance();

// Main SSE loop / Loop principal SSE
while (connection_status() == CONNECTION_NORMAL) {
    $current_time = time();
    
    // Send heartbeat every 30 seconds / Enviar heartbeat a cada 30 segundos
    if ($current_time - $last_heartbeat >= 30) {
        sendHeartbeat();
        $last_heartbeat = $current_time;
    }
    
    // Check for new notifications every 2 seconds / Verificar novas notificações a cada 2 segundos
    if ($current_time - $last_notification_check >= 2) {
        try {
            // Get cached SSE notifications / Obter notificações SSE em cache
            $cache_key = "sse_notifications_user_{$user_id}";
            $notifications = cache_get($cache_key, []);
            
            if (!empty($notifications)) {
                foreach ($notifications as $notification) {
                    sendSSEEvent('notification', $notification, $notification['id']);
                }
                
                // Clear processed notifications from cache / Limpar notificações processadas do cache
                cache_delete($cache_key);
            }
            
            // Also check for any system-wide notifications / Também verificar notificações do sistema
            $system_notifications = cache_get('sse_system_notifications', []);
            if (!empty($system_notifications)) {
                foreach ($system_notifications as $notification) {
                    sendSSEEvent('system_notification', $notification, $notification['id']);
                }
            }
            
        } catch (Exception $e) {
            // Log error but don't break the connection / Registrar erro mas não quebrar a conexão
            error_log("SSE Error for user $user_id: " . $e->getMessage());
        }
        
        $last_notification_check = $current_time;
    }
    
    // Sleep for 500ms to reduce CPU usage / Dormir por 500ms para reduzir uso de CPU
    usleep(500000);
    
    // Break if client disconnected / Quebrar se cliente desconectou
    if (connection_aborted()) {
        break;
    }
}

// Connection closed / Conexão fechada
log_info('SSE connection closed', ['user_id' => $user_id], 'notifications');
?>