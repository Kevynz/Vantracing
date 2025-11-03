<?php
/**
 * VanTracing Real-Time Notification System / Sistema de Notificações em Tempo Real VanTracing
 * 
 * WebSocket and Server-Sent Events implementation for real-time notifications
 * Implementação WebSocket e Server-Sent Events para notificações em tempo real
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

require_once 'db_connect.php';
require_once 'security_helper.php';
require_once 'logger.php';

/**
 * Notification Types / Tipos de Notificação
 */
class NotificationType {
    const LOCATION_UPDATE = 'location_update';
    const ROUTE_ALERT = 'route_alert';
    const SECURITY_ALERT = 'security_alert';
    const SYSTEM_MESSAGE = 'system_message';
    const EMERGENCY = 'emergency';
    const STATUS_UPDATE = 'status_update';
    const CHILD_PICKUP = 'child_pickup';
    const ARRIVAL_NOTIFICATION = 'arrival_notification';
}

/**
 * Main Notification Manager / Gerenciador Principal de Notificações
 */
class VanTracingNotificationManager {
    private static $instance;
    private $pdo;
    private $config;
    private $active_connections = [];
    
    private function __construct() {
        global $conn;
        $this->pdo = $conn;
        $this->config = $this->loadConfig();
        $this->setupDatabase();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig() {
        return [
            'enabled' => getenv('NOTIFICATIONS_ENABLED') !== 'false',
            'channels' => [
                'sse' => getenv('SSE_ENABLED') !== 'false',
                'email' => getenv('EMAIL_NOTIFICATIONS') !== 'false',
                'sms' => getenv('SMS_NOTIFICATIONS') === 'true'
            ],
            'retry_attempts' => (int)(getenv('NOTIFICATION_RETRY') ?: 3),
            'queue_size' => (int)(getenv('NOTIFICATION_QUEUE_SIZE') ?: 1000),
            'cleanup_interval' => (int)(getenv('NOTIFICATION_CLEANUP_HOURS') ?: 24)
        ];
    }
    
    /**
     * Setup database tables for notifications / Configurar tabelas do banco para notificações
     */
    private function setupDatabase() {
        try {
            // Create notifications table / Criar tabela de notificações
            $sql_notifications = "
                CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    title VARCHAR(200) NOT NULL,
                    message TEXT NOT NULL,
                    data JSON,
                    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                    status ENUM('pending', 'sent', 'delivered', 'read', 'failed') DEFAULT 'pending',
                    channels JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    sent_at TIMESTAMP NULL,
                    read_at TIMESTAMP NULL,
                    expires_at TIMESTAMP NULL,
                    retry_count INT DEFAULT 0,
                    INDEX idx_user_status (user_id, status),
                    INDEX idx_created_at (created_at),
                    INDEX idx_expires_at (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            
            // Create notification subscriptions table / Criar tabela de assinaturas de notificação
            $sql_subscriptions = "
                CREATE TABLE IF NOT EXISTS notification_subscriptions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    endpoint VARCHAR(500) NOT NULL,
                    p256dh VARCHAR(200),
                    auth VARCHAR(50),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE,
                    UNIQUE KEY unique_user_endpoint (user_id, endpoint(255)),
                    INDEX idx_user_active (user_id, is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            
            $this->pdo->exec($sql_notifications);
            $this->pdo->exec($sql_subscriptions);
            
        } catch (Exception $e) {
            log_error('Failed to setup notification database', [
                'error' => $e->getMessage()
            ], 'database');
        }
    }
    
    /**
     * Send notification to user(s) / Enviar notificação para usuário(s)
     */
    public function sendNotification($user_ids, $type, $title, $message, $data = [], $options = []) {
        if (!$this->config['enabled']) {
            return false;
        }
        
        // Ensure user_ids is an array / Garantir que user_ids seja um array
        if (!is_array($user_ids)) {
            $user_ids = [$user_ids];
        }
        
        $priority = $options['priority'] ?? 'medium';
        $channels = $options['channels'] ?? ['sse', 'email'];
        $expires_at = $options['expires_at'] ?? null;
        
        $notifications_created = [];
        
        foreach ($user_ids as $user_id) {
            try {
                // Insert notification record / Inserir registro de notificação
                $stmt = $this->pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, message, data, priority, channels, expires_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $user_id,
                    $type,
                    $title,
                    $message,
                    json_encode($data),
                    $priority,
                    json_encode($channels),
                    $expires_at
                ]);
                
                $notification_id = $this->pdo->lastInsertId();
                $notifications_created[] = $notification_id;
                
                // Send through enabled channels / Enviar pelos canais habilitados
                $this->processNotification($notification_id, $user_id, $type, $title, $message, $data, $channels, $priority);
                
            } catch (Exception $e) {
                log_error('Failed to create notification', [
                    'user_id' => $user_id,
                    'type' => $type,
                    'error' => $e->getMessage()
                ], 'notifications');
            }
        }
        
        log_info('Notifications created', [
            'notification_ids' => $notifications_created,
            'user_ids' => $user_ids,
            'type' => $type
        ], 'notifications');
        
        return $notifications_created;
    }
    
    /**
     * Process notification through different channels / Processar notificação através de diferentes canais
     */
    private function processNotification($notification_id, $user_id, $type, $title, $message, $data, $channels, $priority) {
        $success_channels = [];
        
        foreach ($channels as $channel) {
            try {
                switch ($channel) {
                    case 'sse':
                        if ($this->config['channels']['sse']) {
                            $this->sendSSENotification($user_id, $type, $title, $message, $data, $priority);
                            $success_channels[] = 'sse';
                        }
                        break;
                        
                    case 'email':
                        if ($this->config['channels']['email']) {
                            $this->sendEmailNotification($user_id, $type, $title, $message, $data);
                            $success_channels[] = 'email';
                        }
                        break;
                        
                    case 'push':
                        if ($this->config['channels']['push']) {
                            $this->sendWebPushNotification($user_id, $type, $title, $message, $data);
                            $success_channels[] = 'push';
                        }
                        break;
                }
            } catch (Exception $e) {
                log_error("Failed to send notification via $channel", [
                    'notification_id' => $notification_id,
                    'user_id' => $user_id,
                    'channel' => $channel,
                    'error' => $e->getMessage()
                ], 'notifications');
            }
        }
        
        // Update notification status / Atualizar status da notificação
        $status = !empty($success_channels) ? 'sent' : 'failed';
        $this->updateNotificationStatus($notification_id, $status, [
            'channels_sent' => $success_channels,
            'sent_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Send Server-Sent Events notification / Enviar notificação via Server-Sent Events
     */
    private function sendSSENotification($user_id, $type, $title, $message, $data, $priority) {
        $notification_data = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'priority' => $priority,
            'timestamp' => time(),
            'id' => uniqid('notif_')
        ];
        
        // Store in temporary storage for SSE endpoint / Armazenar em armazenamento temporário para endpoint SSE
        $cache_key = "sse_notifications_user_{$user_id}";
        $existing_notifications = cache_get($cache_key, []);
        $existing_notifications[] = $notification_data;
        
        // Keep only last 50 notifications / Manter apenas as últimas 50 notificações
        if (count($existing_notifications) > 50) {
            $existing_notifications = array_slice($existing_notifications, -50);
        }
        
        cache_set($cache_key, $existing_notifications, 300); // 5 minutes cache
        
        // Log SSE notification / Registrar notificação SSE
        log_info('SSE notification queued', [
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title
        ], 'notifications');
    }
    
    /**
     * Send email notification / Enviar notificação por email
     */
    private function sendEmailNotification($user_id, $type, $title, $message, $data) {
        // Get user email / Obter email do usuário
        $stmt = $this->pdo->prepare("SELECT email, nome FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("User not found for email notification");
        }
        
        // Send email using existing email system / Enviar email usando sistema de email existente
        if (file_exists(__DIR__ . '/email_notifications.php')) {
            require_once 'email_notifications.php';
            
            $email_data = [
                'email' => $user['email'],
                'name' => $user['nome'],
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'data' => $data
            ];
            
            sendNotification('custom_notification', $email_data);
        }
    }
    
    /**
     * Get user notifications / Obter notificações do usuário
     */
    public function getUserNotifications($user_id, $limit = 50, $offset = 0, $unread_only = false) {
        $where_clause = "user_id = ?";
        $params = [$user_id];
        
        if ($unread_only) {
            $where_clause .= " AND read_at IS NULL";
        }
        
        $stmt = $this->pdo->prepare("
            SELECT id, type, title, message, data, priority, status, created_at, read_at
            FROM notifications 
            WHERE $where_clause 
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON data / Decodificar dados JSON
        foreach ($notifications as &$notification) {
            $notification['data'] = json_decode($notification['data'], true);
        }
        
        return $notifications;
    }
    
    /**
     * Mark notification as read / Marcar notificação como lida
     */
    public function markAsRead($notification_ids, $user_id) {
        if (!is_array($notification_ids)) {
            $notification_ids = [$notification_ids];
        }
        
        $placeholders = str_repeat('?,', count($notification_ids) - 1) . '?';
        $params = array_merge($notification_ids, [$user_id]);
        
        $stmt = $this->pdo->prepare("
            UPDATE notifications 
            SET read_at = NOW(), status = 'read' 
            WHERE id IN ($placeholders) AND user_id = ? AND read_at IS NULL
        ");
        
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Clean expired notifications / Limpar notificações expiradas
     */
    public function cleanExpiredNotifications() {
        $stmt = $this->pdo->prepare("
            DELETE FROM notifications 
            WHERE expires_at < NOW() 
            OR (created_at < DATE_SUB(NOW(), INTERVAL ? HOUR) AND status IN ('read', 'failed'))
        ");
        
        $stmt->execute([$this->config['cleanup_interval']]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Update notification status / Atualizar status da notificação
     */
    private function updateNotificationStatus($notification_id, $status, $additional_data = []) {
        $update_fields = ['status = ?'];
        $params = [$status];
        
        if (isset($additional_data['sent_at'])) {
            $update_fields[] = 'sent_at = ?';
            $params[] = $additional_data['sent_at'];
        }
        
        if (isset($additional_data['retry_count'])) {
            $update_fields[] = 'retry_count = retry_count + 1';
        }
        
        $params[] = $notification_id;
        
        $sql = "UPDATE notifications SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    /**
     * Get notification statistics / Obter estatísticas de notificação
     */
    public function getNotificationStats($user_id = null, $hours = 24) {
        $where_clause = "created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
        $params = [$hours];
        
        if ($user_id) {
            $where_clause .= " AND user_id = ?";
            $params[] = $user_id;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN read_at IS NULL AND expires_at > NOW() THEN 1 ELSE 0 END) as unread,
                type,
                priority
            FROM notifications 
            WHERE $where_clause
            GROUP BY type, priority
        ");
        
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/**
 * Quick notification functions / Funções rápidas de notificação
 */

function notify_user($user_id, $type, $title, $message, $data = [], $options = []) {
    $manager = VanTracingNotificationManager::getInstance();
    return $manager->sendNotification($user_id, $type, $title, $message, $data, $options);
}

function notify_location_update($user_id, $location_data) {
    return notify_user($user_id, NotificationType::LOCATION_UPDATE, 'Atualização de Localização', 
        'Nova localização disponível', $location_data, ['priority' => 'medium', 'channels' => ['sse']]);
}

function notify_route_alert($user_ids, $alert_message, $route_data) {
    return notify_user($user_ids, NotificationType::ROUTE_ALERT, 'Alerta de Rota', 
        $alert_message, $route_data, ['priority' => 'high', 'channels' => ['sse', 'email']]);
}

function notify_emergency($user_ids, $emergency_message, $emergency_data) {
    return notify_user($user_ids, NotificationType::EMERGENCY, 'EMERGÊNCIA', 
        $emergency_message, $emergency_data, ['priority' => 'critical', 'channels' => ['sse', 'email', 'push']]);
}

function notify_child_pickup($user_id, $child_name, $location) {
    return notify_user($user_id, NotificationType::CHILD_PICKUP, 'Criança Recolhida', 
        "$child_name foi recolhida com segurança", ['child_name' => $child_name, 'location' => $location], 
        ['priority' => 'high', 'channels' => ['sse', 'email']]);
}

function notify_arrival($user_id, $destination, $estimated_time) {
    return notify_user($user_id, NotificationType::ARRIVAL_NOTIFICATION, 'Chegada Prevista', 
        "Chegada em $destination estimada para $estimated_time", 
        ['destination' => $destination, 'eta' => $estimated_time], 
        ['priority' => 'medium', 'channels' => ['sse']]);
}

function get_user_notifications($user_id, $limit = 50, $unread_only = false) {
    $manager = VanTracingNotificationManager::getInstance();
    return $manager->getUserNotifications($user_id, $limit, 0, $unread_only);
}

function mark_notifications_read($notification_ids, $user_id) {
    $manager = VanTracingNotificationManager::getInstance();
    return $manager->markAsRead($notification_ids, $user_id);
}
?>