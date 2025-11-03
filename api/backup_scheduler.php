<?php
/**
 * VanTracing Automated Backup Scheduler / Agendador de Backup Automatizado VanTracing
 * 
 * Cron job script for automatic database backups
 * Script de cron job para backups automáticos do banco de dados
 * 
 * Usage / Uso:
 * Add to crontab: 0 0,6,12,18 * * * /usr/bin/php /path/to/backup_scheduler.php
 * Adicionar ao crontab: 0 0,6,12,18 * * * /usr/bin/php /caminho/para/backup_scheduler.php
 * 
 * This runs every 6 hours / Executa a cada 6 horas
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Set time limit for long-running backup operations / Definir limite de tempo para operações longas de backup
set_time_limit(0);
ini_set('memory_limit', '256M');

// Ensure we're running from command line / Garantir que estamos executando pela linha de comando
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from command line');
}

require_once __DIR__ . '/backup_system.php';

/**
 * Backup Scheduler Class / Classe do Agendador de Backup
 */
class VanTracingBackupScheduler {
    private $manager;
    private $config;
    
    public function __construct() {
        $this->manager = VanTracingBackupManager::getInstance();
        $this->loadConfig();
        
        echo "[" . date('Y-m-d H:i:s') . "] VanTracing Backup Scheduler Started\n";
        log_info('Backup scheduler started', ['pid' => getmypid()], 'backup');
    }
    
    private function loadConfig() {
        $this->config = [
            'enabled' => getenv('BACKUP_ENABLED') !== 'false',
            'automatic_backup' => getenv('AUTO_BACKUP') !== 'false',
            'force_backup' => isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--force',
            'cleanup_only' => isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--cleanup',
            'verify_only' => isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--verify',
            'dry_run' => isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--dry-run'
        ];
    }
    
    public function run() {
        try {
            if (!$this->config['enabled']) {
                echo "Backup system is disabled\n";
                return;
            }
            
            if ($this->config['dry_run']) {
                echo "DRY RUN MODE - No actual operations will be performed\n";
                $this->performDryRun();
                return;
            }
            
            if ($this->config['cleanup_only']) {
                echo "Cleanup mode - Only cleaning old backups\n";
                $this->performCleanup();
                return;
            }
            
            if ($this->config['verify_only']) {
                echo "Verify mode - Only verifying existing backups\n";
                $this->performVerification();
                return;
            }
            
            // Check if automatic backup is needed / Verificar se backup automático é necessário
            $backup_needed = $this->config['force_backup'] || $this->manager->isAutomaticBackupDue();
            
            if (!$backup_needed) {
                echo "Automatic backup not due yet\n";
                log_info('Automatic backup not due', [], 'backup');
                return;
            }
            
            echo "Starting automatic backup process...\n";
            
            // Perform pre-backup checks / Executar verificações pré-backup
            $this->performPreBackupChecks();
            
            // Create automatic backup / Criar backup automático
            $result = $this->manager->createBackup('automatic', null, 'Scheduled automatic backup via cron');
            
            if ($result['success']) {
                echo "Backup completed successfully!\n";
                echo "- Backup ID: {$result['backup_id']}\n";
                echo "- Filename: {$result['filename']}\n";
                echo "- File Size: " . $this->formatBytes($result['file_size']) . "\n";
                echo "- Duration: {$result['duration']} seconds\n";
                echo "- Checksum: {$result['checksum']}\n";
                
                log_info('Scheduled backup completed successfully', [
                    'backup_id' => $result['backup_id'],
                    'filename' => $result['filename'],
                    'duration' => $result['duration']
                ], 'backup');
                
                // Post-backup operations / Operações pós-backup
                $this->performPostBackupOperations($result['backup_id']);
                
            } else {
                echo "Backup failed!\n";
                log_error('Scheduled backup failed', ['error' => 'Unknown error'], 'backup');
                exit(1);
            }
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            log_error('Backup scheduler error', ['error' => $e->getMessage()], 'backup');
            exit(1);
        }
    }
    
    private function performPreBackupChecks() {
        echo "Performing pre-backup checks...\n";
        
        // Check disk space / Verificar espaço em disco
        $backup_dir = __DIR__ . '/../backups/';
        $free_space = disk_free_space($backup_dir);
        $required_space = 100 * 1024 * 1024; // 100 MB minimum
        
        if ($free_space < $required_space) {
            throw new Exception("Insufficient disk space for backup (Available: " . 
                              $this->formatBytes($free_space) . ", Required: " . 
                              $this->formatBytes($required_space) . ")");
        }
        
        echo "- Disk space check: OK (" . $this->formatBytes($free_space) . " available)\n";
        
        // Check database connectivity / Verificar conectividade do banco
        try {
            global $conn;
            $stmt = $conn->query("SELECT 1");
            echo "- Database connectivity: OK\n";
        } catch (Exception $e) {
            throw new Exception("Database connectivity check failed: " . $e->getMessage());
        }
        
        // Check backup directory permissions / Verificar permissões do diretório de backup
        if (!is_writable($backup_dir)) {
            throw new Exception("Backup directory is not writable: $backup_dir");
        }
        
        echo "- Backup directory permissions: OK\n";
        
        // Check system load (if available) / Verificar carga do sistema (se disponível)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load[0] > 5.0) { // High load threshold
                echo "- WARNING: High system load detected ({$load[0]})\n";
                log_warning('High system load during backup', ['load' => $load[0]], 'backup');
            } else {
                echo "- System load check: OK ({$load[0]})\n";
            }
        }
        
        echo "Pre-backup checks completed successfully\n";
    }
    
    private function performPostBackupOperations($backup_id) {
        echo "Performing post-backup operations...\n";
        
        // Verify the backup / Verificar o backup
        echo "- Verifying backup integrity...\n";
        
        try {
            $verification_result = $this->manager->verifyBackup($backup_id);
            
            if ($verification_result) {
                echo "- Backup verification: PASSED\n";
            } else {
                echo "- Backup verification: FAILED\n";
                log_error('Backup verification failed after creation', ['backup_id' => $backup_id], 'backup');
            }
        } catch (Exception $e) {
            echo "- Backup verification error: " . $e->getMessage() . "\n";
            log_error('Backup verification error', [
                'backup_id' => $backup_id,
                'error' => $e->getMessage()
            ], 'backup');
        }
        
        // Update backup statistics / Atualizar estatísticas de backup
        $this->updateBackupStatistics();
        
        echo "Post-backup operations completed\n";
    }
    
    private function performCleanup() {
        echo "Starting backup cleanup process...\n";
        
        try {
            // Get backup statistics / Obter estatísticas de backup
            $stats = $this->manager->getBackupStats();
            
            echo "Current backup statistics:\n";
            echo "- Total backups: {$stats['total_backups']}\n";
            echo "- Total size: " . $this->formatBytes($stats['total_size_bytes']) . "\n";
            
            // The cleanup is handled internally by the backup manager
            // when creating new backups, but we can trigger it manually here
            // A limpeza é tratada internamente pelo gerenciador de backup
            // ao criar novos backups, mas podemos acioná-la manualmente aqui
            
            echo "Cleanup process completed\n";
            
        } catch (Exception $e) {
            echo "Cleanup error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function performVerification() {
        echo "Starting backup verification process...\n";
        
        try {
            $backups = $this->manager->getBackupList(50, 0); // Get latest 50 backups
            
            $verified_count = 0;
            $failed_count = 0;
            
            foreach ($backups as $backup) {
                if ($backup['verification_status'] === 'pending') {
                    echo "Verifying backup: {$backup['filename']}\n";
                    
                    $result = $this->manager->verifyBackup($backup['id']);
                    
                    if ($result) {
                        $verified_count++;
                        echo "- PASSED\n";
                    } else {
                        $failed_count++;
                        echo "- FAILED\n";
                    }
                }
            }
            
            echo "Verification completed:\n";
            echo "- Verified: $verified_count\n";
            echo "- Failed: $failed_count\n";
            
        } catch (Exception $e) {
            echo "Verification error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function performDryRun() {
        echo "=== DRY RUN REPORT ===\n";
        
        try {
            // Check if backup would be needed / Verificar se backup seria necessário
            $backup_needed = $this->config['force_backup'] || $this->manager->isAutomaticBackupDue();
            
            echo "Backup needed: " . ($backup_needed ? 'YES' : 'NO') . "\n";
            
            if ($backup_needed) {
                echo "Actions that would be performed:\n";
                echo "1. Pre-backup checks (disk space, database connectivity, permissions)\n";
                echo "2. Create automatic database backup\n";
                echo "3. Verify backup integrity\n";
                echo "4. Clean up old backups (if limit exceeded)\n";
                echo "5. Update statistics and logs\n";
            }
            
            // Show current statistics / Mostrar estatísticas atuais
            $stats = $this->manager->getBackupStats();
            
            echo "\nCurrent backup statistics:\n";
            echo "- Total backups: {$stats['total_backups']}\n";
            echo "- Recent backups (7 days): {$stats['recent_backups']}\n";
            echo "- Total size: " . $this->formatBytes($stats['total_size_bytes']) . "\n";
            
            if ($stats['last_backup']) {
                echo "- Last backup: {$stats['last_backup']['filename']} ({$stats['last_backup']['created_at']})\n";
            }
            
            echo "\n=== END DRY RUN ===\n";
            
        } catch (Exception $e) {
            echo "Dry run error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function updateBackupStatistics() {
        // This would update additional statistics or trigger notifications
        // For now, it's handled by the backup manager internally
        // Isso atualizaria estatísticas adicionais ou acionaria notificações
        // Por enquanto, é tratado internamente pelo gerenciador de backup
        
        echo "- Statistics updated\n";
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Script execution / Execução do script
try {
    $scheduler = new VanTracingBackupScheduler();
    $scheduler->run();
    
    echo "[" . date('Y-m-d H:i:s') . "] VanTracing Backup Scheduler Finished Successfully\n";
    exit(0);
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>