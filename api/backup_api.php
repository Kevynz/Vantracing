<?php
/**
 * VanTracing Backup API / API de Backup VanTracing
 * 
 * RESTful API for backup management operations
 * API RESTful para operações de gerenciamento de backup
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

require_once 'backup_system.php';
require_once 'security_helper.php';

// Apply security middleware / Aplicar middleware de segurança
secure_api(['rate_limit' => 60, 'interval' => 3600]); // 60 requests per hour

header('Content-Type: application/json; charset=utf-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = isset($_GET['action']) ? $_GET['action'] : '';
    
    $response = [];
    
    switch ($method) {
        case 'GET':
            switch ($path) {
                case 'list':
                    $limit = (int)($_GET['limit'] ?? 50);
                    $offset = (int)($_GET['offset'] ?? 0);
                    
                    $backups = get_backup_list($limit, $offset);
                    $stats = get_backup_stats();
                    
                    $response = [
                        'success' => true,
                        'backups' => $backups,
                        'stats' => $stats,
                        'pagination' => [
                            'limit' => $limit,
                            'offset' => $offset,
                            'total' => $stats['total_backups']
                        ]
                    ];
                    break;
                    
                case 'stats':
                    $stats = get_backup_stats();
                    $manager = VanTracingBackupManager::getInstance();
                    
                    $response = [
                        'success' => true,
                        'stats' => $stats,
                        'automatic_backup_due' => $manager->isAutomaticBackupDue(),
                        'config' => [
                            'automatic_backup_enabled' => getenv('AUTO_BACKUP') !== 'false',
                            'backup_interval_hours' => (int)(getenv('BACKUP_INTERVAL') ?: 6),
                            'max_backups' => (int)(getenv('MAX_BACKUPS') ?: 30),
                            'compression_enabled' => getenv('BACKUP_COMPRESSION') !== 'false',
                            'verification_enabled' => getenv('VERIFY_BACKUPS') !== 'false'
                        ]
                    ];
                    break;
                    
                case 'check_automatic':
                    $manager = VanTracingBackupManager::getInstance();
                    
                    $response = [
                        'success' => true,
                        'backup_due' => $manager->isAutomaticBackupDue(),
                        'last_automatic_backup' => null
                    ];
                    
                    // Get last automatic backup info / Obter info do último backup automático
                    $backups = get_backup_list(1, 0);
                    if (!empty($backups) && $backups[0]['backup_type'] === 'automatic') {
                        $response['last_automatic_backup'] = $backups[0];
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid GET action');
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($path) {
                case 'create':
                    require_permission('admin');
                    
                    $type = $input['type'] ?? 'manual';
                    $notes = SecurityHelper::sanitizeInput($input['notes'] ?? '');
                    $user_id = $_SESSION['user_id'] ?? null;
                    
                    // Validate backup type / Validar tipo de backup
                    if (!in_array($type, ['manual', 'automatic', 'scheduled'])) {
                        throw new Exception('Invalid backup type');
                    }
                    
                    log_info('Creating backup via API', [
                        'type' => $type,
                        'user_id' => $user_id,
                        'notes' => $notes
                    ], 'backup');
                    
                    $result = create_backup($type, $user_id, $notes);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Backup created successfully',
                        'backup' => $result
                    ];
                    break;
                    
                case 'verify':
                    require_permission('admin');
                    
                    $backup_id = (int)($input['backup_id'] ?? 0);
                    
                    if ($backup_id <= 0) {
                        throw new Exception('Invalid backup ID');
                    }
                    
                    $verification_result = verify_backup($backup_id);
                    
                    $response = [
                        'success' => true,
                        'message' => $verification_result ? 'Backup verification passed' : 'Backup verification failed',
                        'verified' => $verification_result,
                        'backup_id' => $backup_id
                    ];
                    break;
                    
                case 'restore':
                    require_permission('admin');
                    
                    $backup_id = (int)($input['backup_id'] ?? 0);
                    $user_id = $_SESSION['user_id'] ?? null;
                    
                    if ($backup_id <= 0) {
                        throw new Exception('Invalid backup ID');
                    }
                    
                    // Additional confirmation check / Verificação adicional de confirmação
                    $confirm = $input['confirm'] ?? false;
                    if (!$confirm) {
                        throw new Exception('Database restore requires explicit confirmation');
                    }
                    
                    log_warning('Database restore initiated via API', [
                        'backup_id' => $backup_id,
                        'user_id' => $user_id,
                        'ip_address' => SecurityHelper::getClientIP()
                    ], 'backup');
                    
                    $result = restore_backup($backup_id, $user_id);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Database restored successfully',
                        'restore' => $result
                    ];
                    break;
                    
                case 'run_automatic':
                    require_permission('admin');
                    
                    $manager = VanTracingBackupManager::getInstance();
                    
                    if (!$manager->isAutomaticBackupDue()) {
                        $response = [
                            'success' => true,
                            'message' => 'Automatic backup not due yet',
                            'backup_created' => false
                        ];
                    } else {
                        $result = create_backup('automatic', null, 'Triggered via API');
                        
                        $response = [
                            'success' => true,
                            'message' => 'Automatic backup completed',
                            'backup_created' => true,
                            'backup' => $result
                        ];
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid POST action');
            }
            break;
            
        case 'DELETE':
            require_permission('admin');
            
            if ($path === 'delete') {
                $backup_id = (int)($_GET['backup_id'] ?? 0);
                $user_id = $_SESSION['user_id'] ?? null;
                
                if ($backup_id <= 0) {
                    throw new Exception('Invalid backup ID');
                }
                
                delete_backup($backup_id, $user_id);
                
                $response = [
                    'success' => true,
                    'message' => 'Backup deleted successfully',
                    'backup_id' => $backup_id
                ];
                
            } else {
                throw new Exception('Invalid DELETE action');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
    // Log successful API operation / Registrar operação bem-sucedida da API
    log_info('Backup API operation completed', [
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
    log_error('Backup API error', [
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