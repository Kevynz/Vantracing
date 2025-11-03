<?php
/**
 * VanTracing Backup Restoration Utility / Utilitário de Restauração de Backup VanTracing
 * 
 * Command-line tool for emergency database restoration
 * Ferramenta de linha de comando para restauração emergencial do banco de dados
 * 
 * Usage / Uso:
 * php backup_restore_cli.php --list
 * php backup_restore_cli.php --restore <backup_id>
 * php backup_restore_cli.php --restore <backup_id> --force
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Ensure we're running from command line / Garantir que estamos executando pela linha de comando
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from command line');
}

require_once __DIR__ . '/backup_system.php';

class VanTracingBackupRestoreCLI {
    private $manager;
    private $options;
    
    public function __construct($argv) {
        $this->manager = VanTracingBackupManager::getInstance();
        $this->options = $this->parseArguments($argv);
        
        echo "VanTracing Backup Restoration Utility v2.0\n";
        echo str_repeat("=", 50) . "\n\n";
    }
    
    private function parseArguments($argv) {
        $options = [
            'action' => null,
            'backup_id' => null,
            'force' => false,
            'help' => false
        ];
        
        for ($i = 1; $i < count($argv); $i++) {
            switch ($argv[$i]) {
                case '--list':
                case '-l':
                    $options['action'] = 'list';
                    break;
                    
                case '--restore':
                case '-r':
                    $options['action'] = 'restore';
                    if (isset($argv[$i + 1]) && !strpos($argv[$i + 1], '--')) {
                        $options['backup_id'] = (int)$argv[$i + 1];
                        $i++;
                    }
                    break;
                    
                case '--verify':
                case '-v':
                    $options['action'] = 'verify';
                    if (isset($argv[$i + 1]) && !strpos($argv[$i + 1], '--')) {
                        $options['backup_id'] = (int)$argv[$i + 1];
                        $i++;
                    }
                    break;
                    
                case '--force':
                case '-f':
                    $options['force'] = true;
                    break;
                    
                case '--help':
                case '-h':
                    $options['help'] = true;
                    break;
                    
                default:
                    if (is_numeric($argv[$i]) && !$options['backup_id']) {
                        $options['backup_id'] = (int)$argv[$i];
                    }
                    break;
            }
        }
        
        return $options;
    }
    
    public function run() {
        try {
            if ($this->options['help'] || !$this->options['action']) {
                $this->showHelp();
                return;
            }
            
            switch ($this->options['action']) {
                case 'list':
                    $this->listBackups();
                    break;
                    
                case 'restore':
                    $this->restoreBackup();
                    break;
                    
                case 'verify':
                    $this->verifyBackup();
                    break;
                    
                default:
                    echo "Unknown action: {$this->options['action']}\n";
                    $this->showHelp();
                    exit(1);
            }
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function listBackups() {
        echo "Available Backups:\n";
        echo str_repeat("-", 120) . "\n";
        printf("%-4s %-30s %-12s %-12s %-12s %-20s %-15s\n", 
               "ID", "Filename", "Type", "Size", "Status", "Created", "Duration");
        echo str_repeat("-", 120) . "\n";
        
        $backups = $this->manager->getBackupList(50, 0);
        
        if (empty($backups)) {
            echo "No backups found.\n";
            return;
        }
        
        foreach ($backups as $backup) {
            printf("%-4d %-30s %-12s %-12s %-12s %-20s %-15s\n",
                   $backup['id'],
                   substr($backup['filename'], 0, 30),
                   ucfirst($backup['backup_type']),
                   $this->formatBytes($backup['file_size']),
                   ucfirst($backup['verification_status']),
                   date('Y-m-d H:i:s', strtotime($backup['created_at'])),
                   $backup['backup_duration_seconds'] . 's'
            );
        }
        
        echo str_repeat("-", 120) . "\n";
        
        // Show statistics / Mostrar estatísticas
        $stats = $this->manager->getBackupStats();
        echo "\nBackup Statistics:\n";
        echo "- Total Backups: {$stats['total_backups']}\n";
        echo "- Total Size: " . $this->formatBytes($stats['total_size_bytes']) . "\n";
        echo "- Recent (7 days): {$stats['recent_backups']}\n";
        
        if ($stats['last_backup']) {
            echo "- Last Backup: {$stats['last_backup']['filename']} ({$stats['last_backup']['created_at']})\n";
        }
    }
    
    private function restoreBackup() {
        if (!$this->options['backup_id']) {
            echo "ERROR: Backup ID is required for restore operation.\n";
            echo "Use --list to see available backups.\n";
            exit(1);
        }
        
        $backup_id = $this->options['backup_id'];
        
        // Get backup info / Obter informações do backup
        $backups = $this->manager->getBackupList(1000, 0);
        $backup = null;
        
        foreach ($backups as $b) {
            if ($b['id'] == $backup_id) {
                $backup = $b;
                break;
            }
        }
        
        if (!$backup) {
            echo "ERROR: Backup with ID $backup_id not found.\n";
            exit(1);
        }
        
        // Show backup details / Mostrar detalhes do backup
        echo "Backup Details:\n";
        echo "- ID: {$backup['id']}\n";
        echo "- Filename: {$backup['filename']}\n";
        echo "- Type: " . ucfirst($backup['backup_type']) . "\n";
        echo "- Size: " . $this->formatBytes($backup['file_size']) . "\n";
        echo "- Created: {$backup['created_at']}\n";
        echo "- Status: " . ucfirst($backup['verification_status']) . "\n";
        echo "- Notes: " . ($backup['notes'] ?: 'None') . "\n\n";
        
        // Safety warnings / Avisos de segurança
        echo "⚠️  WARNING: DATABASE RESTORATION ⚠️\n";
        echo str_repeat("!", 50) . "\n";
        echo "This operation will:\n";
        echo "- COMPLETELY REPLACE the current database\n";
        echo "- DELETE all data created after this backup\n";
        echo "- Potentially cause system downtime\n";
        echo "- CANNOT BE UNDONE\n\n";
        
        if (!$this->options['force']) {
            echo "Type 'CONFIRM RESTORATION' to proceed: ";
            $handle = fopen("php://stdin", "r");
            $confirmation = trim(fgets($handle));
            fclose($handle);
            
            if ($confirmation !== 'CONFIRM RESTORATION') {
                echo "Restoration cancelled.\n";
                exit(0);
            }
        } else {
            echo "Force mode enabled - proceeding without confirmation...\n";
        }
        
        // Verify backup before restoration / Verificar backup antes da restauração
        if ($backup['verification_status'] !== 'passed') {
            echo "Verifying backup integrity before restoration...\n";
            
            $verification_result = $this->manager->verifyBackup($backup_id);
            
            if (!$verification_result) {
                echo "ERROR: Backup verification failed. Restoration aborted.\n";
                exit(1);
            }
            
            echo "✓ Backup verification passed.\n\n";
        }
        
        // Perform restoration / Executar restauração
        echo "Starting database restoration...\n";
        echo "Progress: [";
        
        $start_time = time();
        
        try {
            // Show progress simulation / Mostrar simulação de progresso
            for ($i = 0; $i <= 20; $i++) {
                if ($i == 10) {
                    // Actually start the restoration at 50% / Realmente iniciar a restauração em 50%
                    $result = $this->manager->restoreBackup($backup_id, null);
                }
                
                echo "█";
                usleep(100000); // 0.1 second delay
            }
            
            echo "] 100%\n\n";
            
            $duration = time() - $start_time;
            
            echo "✓ Database restoration completed successfully!\n";
            echo "- Restore Duration: {$duration} seconds\n";
            echo "- Backup Used: {$backup['filename']}\n";
            echo "- Data Restored: " . $this->formatBytes($backup['file_size']) . "\n\n";
            
            // Post-restoration checks / Verificações pós-restauração
            echo "Performing post-restoration checks...\n";
            
            // Test database connectivity / Testar conectividade do banco
            global $conn;
            $stmt = $conn->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
            $table_count = $stmt->fetch(PDO::FETCH_ASSOC)['table_count'];
            
            echo "✓ Database connectivity: OK\n";
            echo "✓ Tables restored: $table_count\n";
            
            // Log the restoration / Registrar a restauração
            log_info('Database restored via CLI', [
                'backup_id' => $backup_id,
                'filename' => $backup['filename'],
                'duration' => $duration,
                'forced' => $this->options['force']
            ], 'backup');
            
            echo "\n🎉 Database restoration completed successfully!\n";
            echo "The system is now running with data from: {$backup['created_at']}\n";
            
        } catch (Exception $e) {
            echo "\n❌ Restoration failed: " . $e->getMessage() . "\n";
            
            log_error('Database restoration failed via CLI', [
                'backup_id' => $backup_id,
                'error' => $e->getMessage()
            ], 'backup');
            
            exit(1);
        }
    }
    
    private function verifyBackup() {
        if (!$this->options['backup_id']) {
            echo "ERROR: Backup ID is required for verify operation.\n";
            echo "Use --list to see available backups.\n";
            exit(1);
        }
        
        $backup_id = $this->options['backup_id'];
        
        echo "Verifying backup ID: $backup_id\n";
        echo "Progress: [";
        
        try {
            // Show progress / Mostrar progresso
            for ($i = 0; $i <= 10; $i++) {
                if ($i == 5) {
                    $result = $this->manager->verifyBackup($backup_id);
                }
                echo "█";
                usleep(200000); // 0.2 second delay
            }
            
            echo "] 100%\n\n";
            
            if ($result) {
                echo "✓ Backup verification: PASSED\n";
                echo "The backup file is valid and can be used for restoration.\n";
            } else {
                echo "❌ Backup verification: FAILED\n";
                echo "The backup file is corrupted or invalid.\n";
                exit(1);
            }
            
        } catch (Exception $e) {
            echo "\n❌ Verification failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function showHelp() {
        echo "VanTracing Backup Restoration Utility\n\n";
        echo "USAGE:\n";
        echo "  php backup_restore_cli.php [OPTIONS]\n\n";
        echo "OPTIONS:\n";
        echo "  --list, -l                    List all available backups\n";
        echo "  --restore <id>, -r <id>       Restore database from backup ID\n";
        echo "  --verify <id>, -v <id>        Verify backup integrity\n";
        echo "  --force, -f                   Skip confirmation prompts (use with caution)\n";
        echo "  --help, -h                    Show this help message\n\n";
        echo "EXAMPLES:\n";
        echo "  php backup_restore_cli.php --list\n";
        echo "  php backup_restore_cli.php --restore 15\n";
        echo "  php backup_restore_cli.php --restore 15 --force\n";
        echo "  php backup_restore_cli.php --verify 15\n\n";
        echo "IMPORTANT:\n";
        echo "  - Database restoration is irreversible\n";
        echo "  - Always verify backups before restoration\n";
        echo "  - Consider system downtime during restoration\n";
        echo "  - Use --force option only in emergency situations\n\n";
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Main execution / Execução principal
try {
    $cli = new VanTracingBackupRestoreCLI($argv);
    $cli->run();
    exit(0);
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>