<?php
/**
 * VanTracing Automated Backup System / Sistema de Backup Automatizado VanTracing
 * 
 * Comprehensive database backup and restore system with integrity verification
 * Sistema abrangente de backup e restauração do banco com verificação de integridade
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

require_once 'db_connect.php';
require_once 'security_helper.php';
require_once 'logger.php';
require_once 'notification_system.php';

class VanTracingBackupManager {
    private static $instance;
    private $pdo;
    private $config;
    private $backup_directory;
    
    private function __construct() {
        global $conn;
        $this->pdo = $conn;
        $this->config = $this->loadConfig();
        $this->backup_directory = $this->setupBackupDirectory();
        $this->setupBackupTable();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig() {
        return [
            'enabled' => getenv('BACKUP_ENABLED') !== 'false',
            'automatic_backup' => getenv('AUTO_BACKUP') !== 'false',
            'backup_interval_hours' => (int)(getenv('BACKUP_INTERVAL') ?: 6), // 6 hours default
            'max_backups' => (int)(getenv('MAX_BACKUPS') ?: 30), // Keep 30 backups
            'compression' => getenv('BACKUP_COMPRESSION') !== 'false',
            'encryption' => getenv('BACKUP_ENCRYPTION') === 'true',
            'verify_backups' => getenv('VERIFY_BACKUPS') !== 'false',
            'notification_on_backup' => getenv('NOTIFY_BACKUP') === 'true',
            'notification_on_error' => getenv('NOTIFY_BACKUP_ERROR') !== 'false',
            'db_host' => getenv('DB_HOST') ?: 'localhost',
            'db_name' => getenv('DB_NAME') ?: 'vantracing_db',
            'db_user' => getenv('DB_USER') ?: 'root',
            'db_pass' => getenv('DB_PASS') ?: ''
        ];
    }
    
    private function setupBackupDirectory() {
        $backup_dir = __DIR__ . '/../backups/';
        
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Create .htaccess to protect backup files / Criar .htaccess para proteger arquivos de backup
        $htaccess_file = $backup_dir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Require all denied\nOptions -Indexes\n");
        }
        
        // Create index.php to prevent directory listing / Criar index.php para prevenir listagem de diretório
        $index_file = $backup_dir . 'index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, "<?php\n// Access Denied\nheader('HTTP/1.1 403 Forbidden');\nexit('Access Denied');\n?>");
        }
        
        return $backup_dir;
    }
    
    private function setupBackupTable() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS backup_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    file_path VARCHAR(500) NOT NULL,
                    file_size BIGINT NOT NULL,
                    checksum VARCHAR(64) NOT NULL,
                    backup_type ENUM('manual', 'automatic', 'scheduled') DEFAULT 'manual',
                    compression_used BOOLEAN DEFAULT FALSE,
                    encryption_used BOOLEAN DEFAULT FALSE,
                    tables_backed_up JSON,
                    backup_duration_seconds INT NOT NULL,
                    verification_status ENUM('pending', 'passed', 'failed', 'skipped') DEFAULT 'pending',
                    verification_details TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_by INT NULL,
                    notes TEXT,
                    INDEX idx_created_at (created_at),
                    INDEX idx_backup_type (backup_type),
                    INDEX idx_verification_status (verification_status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            
            $this->pdo->exec($sql);
            
        } catch (Exception $e) {
            log_error('Failed to setup backup table', [
                'error' => $e->getMessage()
            ], 'database');
        }
    }
    
    /**
     * Create a full database backup / Criar backup completo do banco de dados
     */
    public function createBackup($type = 'manual', $user_id = null, $notes = '') {
        if (!$this->config['enabled']) {
            throw new Exception('Backup system is disabled');
        }
        
        $start_time = microtime(true);
        
        try {
            log_info('Starting database backup', [
                'type' => $type,
                'user_id' => $user_id,
                'database' => $this->config['db_name']
            ], 'backup');
            
            // Generate backup filename / Gerar nome do arquivo de backup
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "vantracing_backup_{$timestamp}_{$type}.sql";
            
            if ($this->config['compression']) {
                $filename .= '.gz';
            }
            
            $file_path = $this->backup_directory . $filename;
            
            // Get list of tables / Obter lista de tabelas
            $tables = $this->getDatabaseTables();
            
            // Create backup using mysqldump (if available) or PHP method / Criar backup usando mysqldump (se disponível) ou método PHP
            $backup_success = false;
            
            if ($this->isMysqldumpAvailable()) {
                $backup_success = $this->createBackupWithMysqldump($file_path, $tables);
            } else {
                $backup_success = $this->createBackupWithPHP($file_path, $tables);
            }
            
            if (!$backup_success) {
                throw new Exception('Failed to create backup file');
            }
            
            $file_size = filesize($file_path);
            $checksum = hash_file('sha256', $file_path);
            $backup_duration = round(microtime(true) - $start_time, 2);
            
            // Save backup record / Salvar registro do backup
            $backup_id = $this->saveBackupRecord([
                'filename' => $filename,
                'file_path' => $file_path,
                'file_size' => $file_size,
                'checksum' => $checksum,
                'backup_type' => $type,
                'compression_used' => $this->config['compression'],
                'encryption_used' => $this->config['encryption'],
                'tables_backed_up' => json_encode($tables),
                'backup_duration_seconds' => $backup_duration,
                'created_by' => $user_id,
                'notes' => $notes
            ]);
            
            // Verify backup if enabled / Verificar backup se habilitado
            if ($this->config['verify_backups']) {
                $this->verifyBackup($backup_id);
            }
            
            // Clean old backups / Limpar backups antigos
            $this->cleanOldBackups();
            
            // Send notification if enabled / Enviar notificação se habilitado
            if ($this->config['notification_on_backup'] && $user_id) {
                $this->sendBackupNotification($backup_id, 'success');
            }
            
            log_info('Database backup completed successfully', [
                'backup_id' => $backup_id,
                'filename' => $filename,
                'file_size_mb' => round($file_size / 1024 / 1024, 2),
                'duration_seconds' => $backup_duration,
                'type' => $type
            ], 'backup');
            
            return [
                'success' => true,
                'backup_id' => $backup_id,
                'filename' => $filename,
                'file_size' => $file_size,
                'duration' => $backup_duration,
                'checksum' => $checksum
            ];
            
        } catch (Exception $e) {
            $error_duration = round(microtime(true) - $start_time, 2);
            
            log_error('Database backup failed', [
                'type' => $type,
                'user_id' => $user_id,
                'error' => $e->getMessage(),
                'duration_seconds' => $error_duration
            ], 'backup');
            
            // Send error notification / Enviar notificação de erro
            if ($this->config['notification_on_error'] && $user_id) {
                $this->sendBackupNotification(null, 'error', $e->getMessage());
            }
            
            throw $e;
        }
    }
    
    /**
     * Create backup using mysqldump / Criar backup usando mysqldump
     */
    private function createBackupWithMysqldump($file_path, $tables) {
        $host = escapeshellarg($this->config['db_host']);
        $user = escapeshellarg($this->config['db_user']);
        $password = escapeshellarg($this->config['db_pass']);
        $database = escapeshellarg($this->config['db_name']);
        
        // Build mysqldump command / Construir comando mysqldump
        $command = "mysqldump -h {$host} -u {$user}";
        
        if (!empty($this->config['db_pass'])) {
            $command .= " -p{$password}";
        }
        
        $command .= " --single-transaction --routines --triggers --events --hex-blob --default-character-set=utf8mb4 {$database}";
        
        // Add compression if enabled / Adicionar compressão se habilitado
        if ($this->config['compression']) {
            $command .= " | gzip";
        }
        
        $command .= " > " . escapeshellarg($file_path);
        
        // Execute command / Executar comando
        $output = [];
        $return_code = 0;
        
        exec($command . " 2>&1", $output, $return_code);
        
        if ($return_code !== 0) {
            log_error('Mysqldump command failed', [
                'command' => $command,
                'return_code' => $return_code,
                'output' => implode("\n", $output)
            ], 'backup');
            
            return false;
        }
        
        return file_exists($file_path) && filesize($file_path) > 0;
    }
    
    /**
     * Create backup using PHP / Criar backup usando PHP
     */
    private function createBackupWithPHP($file_path, $tables) {
        $handle = fopen($file_path, 'w');
        
        if (!$handle) {
            throw new Exception('Cannot create backup file: ' . $file_path);
        }
        
        try {
            // Write SQL header / Escrever cabeçalho SQL
            fwrite($handle, "-- VanTracing Database Backup\n");
            fwrite($handle, "-- Generated on: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Database: " . $this->config['db_name'] . "\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($handle, "SET time_zone = \"+00:00\";\n\n");
            
            foreach ($tables as $table) {
                $this->backupTable($handle, $table);
            }
            
            fwrite($handle, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
            
            fclose($handle);
            
            // Apply compression if enabled / Aplicar compressão se habilitado
            if ($this->config['compression']) {
                $this->compressFile($file_path);
            }
            
            return true;
            
        } catch (Exception $e) {
            fclose($handle);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            throw $e;
        }
    }
    
    /**
     * Backup a single table / Fazer backup de uma tabela
     */
    private function backupTable($handle, $table) {
        // Get table structure / Obter estrutura da tabela
        $stmt = $this->pdo->prepare("SHOW CREATE TABLE `{$table}`");
        $stmt->execute();
        $create_table = $stmt->fetch(PDO::FETCH_ASSOC);
        
        fwrite($handle, "\n-- Table structure for table `{$table}`\n");
        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($handle, $create_table['Create Table'] . ";\n\n");
        
        // Get table data / Obter dados da tabela
        fwrite($handle, "-- Dumping data for table `{$table}`\n");
        
        $stmt = $this->pdo->prepare("SELECT * FROM `{$table}`");
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $values = [];
            
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = $this->pdo->quote($value);
                }
            }
            
            $sql = "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
            fwrite($handle, $sql);
        }
        
        fwrite($handle, "\n");
    }
    
    /**
     * Verify backup integrity / Verificar integridade do backup
     */
    public function verifyBackup($backup_id) {
        try {
            $backup = $this->getBackupById($backup_id);
            
            if (!$backup || !file_exists($backup['file_path'])) {
                throw new Exception('Backup file not found');
            }
            
            // Verify file checksum / Verificar checksum do arquivo
            $current_checksum = hash_file('sha256', $backup['file_path']);
            
            if ($current_checksum !== $backup['checksum']) {
                throw new Exception('Backup file checksum mismatch');
            }
            
            // Try to read backup file / Tentar ler arquivo de backup
            $file_content = '';
            
            if ($backup['compression_used']) {
                $file_content = gzfile($backup['file_path']);
                $file_content = implode('', $file_content);
            } else {
                $file_content = file_get_contents($backup['file_path']);
            }
            
            if (empty($file_content)) {
                throw new Exception('Backup file is empty or corrupted');
            }
            
            // Basic SQL validation / Validação básica do SQL
            if (!strpos($file_content, 'CREATE TABLE') || !strpos($file_content, 'INSERT INTO')) {
                throw new Exception('Backup file does not contain valid SQL structure');
            }
            
            // Update verification status / Atualizar status de verificação
            $this->updateBackupVerification($backup_id, 'passed', 'Backup verification completed successfully');
            
            log_info('Backup verification passed', [
                'backup_id' => $backup_id,
                'filename' => $backup['filename']
            ], 'backup');
            
            return true;
            
        } catch (Exception $e) {
            $this->updateBackupVerification($backup_id, 'failed', $e->getMessage());
            
            log_error('Backup verification failed', [
                'backup_id' => $backup_id,
                'error' => $e->getMessage()
            ], 'backup');
            
            return false;
        }
    }
    
    /**
     * Restore database from backup / Restaurar banco de dados do backup
     */
    public function restoreBackup($backup_id, $user_id = null) {
        $backup = $this->getBackupById($backup_id);
        
        if (!$backup || !file_exists($backup['file_path'])) {
            throw new Exception('Backup file not found');
        }
        
        log_info('Starting database restore', [
            'backup_id' => $backup_id,
            'filename' => $backup['filename'],
            'user_id' => $user_id
        ], 'backup');
        
        $start_time = microtime(true);
        
        try {
            // Verify backup before restore / Verificar backup antes da restauração
            if ($backup['verification_status'] !== 'passed') {
                $this->verifyBackup($backup_id);
                $backup = $this->getBackupById($backup_id); // Reload
                
                if ($backup['verification_status'] !== 'passed') {
                    throw new Exception('Backup verification failed, restore aborted');
                }
            }
            
            // Read backup file / Ler arquivo de backup
            $sql_content = '';
            
            if ($backup['compression_used']) {
                $sql_content = gzfile($backup['file_path']);
                $sql_content = implode('', $sql_content);
            } else {
                $sql_content = file_get_contents($backup['file_path']);
            }
            
            if (empty($sql_content)) {
                throw new Exception('Cannot read backup file content');
            }
            
            // Execute SQL statements / Executar declarações SQL
            $this->executeSQLRestore($sql_content);
            
            $restore_duration = round(microtime(true) - $start_time, 2);
            
            log_info('Database restore completed successfully', [
                'backup_id' => $backup_id,
                'filename' => $backup['filename'],
                'duration_seconds' => $restore_duration,
                'user_id' => $user_id
            ], 'backup');
            
            // Send notification / Enviar notificação
            if ($user_id) {
                notify_user($user_id, NotificationType::SYSTEM_MESSAGE, 
                    'Restauração Concluída', 
                    "Banco de dados restaurado com sucesso do backup {$backup['filename']}",
                    ['backup_id' => $backup_id, 'duration' => $restore_duration],
                    ['priority' => 'high']);
            }
            
            return [
                'success' => true,
                'backup_id' => $backup_id,
                'filename' => $backup['filename'],
                'duration' => $restore_duration
            ];
            
        } catch (Exception $e) {
            log_error('Database restore failed', [
                'backup_id' => $backup_id,
                'error' => $e->getMessage(),
                'user_id' => $user_id
            ], 'backup');
            
            throw $e;
        }
    }
    
    /**
     * Execute SQL restore statements / Executar declarações de restauração SQL
     */
    private function executeSQLRestore($sql_content) {
        // Split SQL into statements / Dividir SQL em declarações
        $statements = array_filter(
            array_map('trim', explode(';', $sql_content)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        $this->pdo->beginTransaction();
        
        try {
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->pdo->exec($statement);
                }
            }
            
            $this->pdo->commit();
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    /**
     * Get list of backups / Obter lista de backups
     */
    public function getBackupList($limit = 50, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT 
                id, filename, file_size, backup_type, 
                compression_used, encryption_used, verification_status,
                backup_duration_seconds, created_at, notes,
                (SELECT nome FROM usuarios WHERE id = backup_history.created_by) as created_by_name
            FROM backup_history 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Delete backup / Deletar backup
     */
    public function deleteBackup($backup_id, $user_id = null) {
        $backup = $this->getBackupById($backup_id);
        
        if (!$backup) {
            throw new Exception('Backup not found');
        }
        
        try {
            // Delete physical file / Deletar arquivo físico
            if (file_exists($backup['file_path'])) {
                unlink($backup['file_path']);
            }
            
            // Delete database record / Deletar registro do banco
            $stmt = $this->pdo->prepare("DELETE FROM backup_history WHERE id = ?");
            $stmt->execute([$backup_id]);
            
            log_info('Backup deleted', [
                'backup_id' => $backup_id,
                'filename' => $backup['filename'],
                'user_id' => $user_id
            ], 'backup');
            
            return true;
            
        } catch (Exception $e) {
            log_error('Failed to delete backup', [
                'backup_id' => $backup_id,
                'error' => $e->getMessage(),
                'user_id' => $user_id
            ], 'backup');
            
            throw $e;
        }
    }
    
    // Helper methods / Métodos auxiliares
    
    private function getDatabaseTables() {
        $stmt = $this->pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function isMysqldumpAvailable() {
        $output = [];
        $return_code = 0;
        
        exec('mysqldump --version 2>&1', $output, $return_code);
        
        return $return_code === 0;
    }
    
    private function compressFile($file_path) {
        if (!function_exists('gzopen')) {
            return false;
        }
        
        $compressed_path = $file_path . '.gz';
        
        $input = fopen($file_path, 'r');
        $output = gzopen($compressed_path, 'w');
        
        if ($input && $output) {
            while (!feof($input)) {
                gzwrite($output, fread($input, 8192));
            }
            
            fclose($input);
            gzclose($output);
            
            // Replace original file with compressed version / Substituir arquivo original pela versão comprimida
            unlink($file_path);
            rename($compressed_path, $file_path);
            
            return true;
        }
        
        return false;
    }
    
    private function saveBackupRecord($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO backup_history (
                filename, file_path, file_size, checksum, backup_type, 
                compression_used, encryption_used, tables_backed_up, 
                backup_duration_seconds, created_by, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['filename'], $data['file_path'], $data['file_size'], 
            $data['checksum'], $data['backup_type'], $data['compression_used'],
            $data['encryption_used'], $data['tables_backed_up'], 
            $data['backup_duration_seconds'], $data['created_by'], $data['notes']
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    private function getBackupById($backup_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM backup_history WHERE id = ?");
        $stmt->execute([$backup_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function updateBackupVerification($backup_id, $status, $details = '') {
        $stmt = $this->pdo->prepare("
            UPDATE backup_history 
            SET verification_status = ?, verification_details = ? 
            WHERE id = ?
        ");
        
        $stmt->execute([$status, $details, $backup_id]);
    }
    
    private function cleanOldBackups() {
        if ($this->config['max_backups'] <= 0) {
            return;
        }
        
        // Get old backups / Obter backups antigos
        $stmt = $this->pdo->prepare("
            SELECT id, filename, file_path 
            FROM backup_history 
            ORDER BY created_at DESC 
            LIMIT 999999 OFFSET ?
        ");
        
        $stmt->execute([$this->config['max_backups']]);
        $old_backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($old_backups as $backup) {
            try {
                $this->deleteBackup($backup['id']);
            } catch (Exception $e) {
                log_error('Failed to delete old backup during cleanup', [
                    'backup_id' => $backup['id'],
                    'error' => $e->getMessage()
                ], 'backup');
            }
        }
        
        if (count($old_backups) > 0) {
            log_info('Cleaned up old backups', [
                'deleted_count' => count($old_backups),
                'max_backups' => $this->config['max_backups']
            ], 'backup');
        }
    }
    
    private function sendBackupNotification($backup_id, $status, $error_message = '') {
        try {
            if ($status === 'success') {
                $backup = $this->getBackupById($backup_id);
                
                notify_user(1, NotificationType::SYSTEM_MESSAGE, // Admin user
                    'Backup Concluído', 
                    "Backup automático concluído com sucesso: {$backup['filename']}",
                    [
                        'backup_id' => $backup_id,
                        'file_size_mb' => round($backup['file_size'] / 1024 / 1024, 2),
                        'duration' => $backup['backup_duration_seconds']
                    ],
                    ['priority' => 'low']);
            } else {
                notify_user(1, NotificationType::SYSTEM_MESSAGE, // Admin user
                    'Erro no Backup', 
                    "Falha no backup automático: $error_message",
                    ['error' => $error_message],
                    ['priority' => 'high']);
            }
        } catch (Exception $e) {
            log_error('Failed to send backup notification', [
                'error' => $e->getMessage()
            ], 'backup');
        }
    }
    
    /**
     * Get backup statistics / Obter estatísticas de backup
     */
    public function getBackupStats() {
        $stats = [];
        
        // Total backups / Total de backups
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM backup_history");
        $stats['total_backups'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Recent backups (last 7 days) / Backups recentes (últimos 7 dias)
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as recent 
            FROM backup_history 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stats['recent_backups'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent'];
        
        // Total backup size / Tamanho total dos backups
        $stmt = $this->pdo->query("SELECT SUM(file_size) as total_size FROM backup_history");
        $stats['total_size_bytes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_size'] ?: 0;
        $stats['total_size_mb'] = round($stats['total_size_bytes'] / 1024 / 1024, 2);
        
        // Verification status / Status de verificação
        $stmt = $this->pdo->query("
            SELECT verification_status, COUNT(*) as count 
            FROM backup_history 
            GROUP BY verification_status
        ");
        $stats['verification_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Last backup info / Informações do último backup
        $stmt = $this->pdo->query("
            SELECT filename, created_at, backup_type 
            FROM backup_history 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stats['last_backup'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    /**
     * Check if automatic backup is due / Verificar se backup automático é necessário
     */
    public function isAutomaticBackupDue() {
        if (!$this->config['automatic_backup']) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT created_at 
            FROM backup_history 
            WHERE backup_type = 'automatic' 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $last_backup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$last_backup) {
            return true; // No automatic backup found
        }
        
        $last_backup_time = strtotime($last_backup['created_at']);
        $interval_seconds = $this->config['backup_interval_hours'] * 3600;
        
        return (time() - $last_backup_time) >= $interval_seconds;
    }
}

/**
 * Helper functions / Funções auxiliares
 */

function create_backup($type = 'manual', $user_id = null, $notes = '') {
    $manager = VanTracingBackupManager::getInstance();
    return $manager->createBackup($type, $user_id, $notes);
}

function verify_backup($backup_id) {
    $manager = VanTracingBackupManager::getInstance();
    return $manager->verifyBackup($backup_id);
}

function restore_backup($backup_id, $user_id = null) {
    $manager = VanTracingBackupManager::getInstance();
    return $manager->restoreBackup($backup_id, $user_id);
}

function get_backup_list($limit = 50, $offset = 0) {
    $manager = VanTracingBackupManager::getInstance();
    return $manager->getBackupList($limit, $offset);
}

function delete_backup($backup_id, $user_id = null) {
    $manager = VanTracingBackupManager::getInstance();
    return $manager->deleteBackup($backup_id, $user_id);
}

function get_backup_stats() {
    $manager = VanTracingBackupManager::getInstance();
    return $manager->getBackupStats();
}

function is_automatic_backup_due() {
    $manager = VanTracingBackupManager::getInstance();
    return $manager->isAutomaticBackupDue();
}
?>