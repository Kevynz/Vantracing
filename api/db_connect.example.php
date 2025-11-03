<?php
/**
 * Database Connection Example / Exemplo de Conexão com Banco de Dados
 * 
 * IMPORTANTE: Copie este arquivo para db_connect.php e configure com seus dados reais
 * IMPORTANT: Copy this file to db_connect.php and configure with your real data
 * 
 * NÃO FAÇA COMMIT do arquivo db_connect.php - ele deve permanecer no .gitignore
 * DO NOT COMMIT the db_connect.php file - it should remain in .gitignore
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Load environment variables from .env file if it exists
// Carrega variáveis de ambiente do arquivo .env se existir
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Load environment variables / Carregar variáveis de ambiente
loadEnv(__DIR__ . '/../.env');

// Database configuration with environment variables fallback
// Configuração do banco de dados com fallback para variáveis de ambiente
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'vantracing_db';
$username = $_ENV['DB_USER'] ?? 'vantracing_user';
$password = $_ENV['DB_PASS'] ?? 'sua_senha_aqui';
$port = $_ENV['DB_PORT'] ?? 3306;
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

// SSL configuration (for production environments)
// Configuração SSL (para ambientes de produção)
$ssl_ca = $_ENV['DB_SSL_CA'] ?? null;
$ssl_cert = $_ENV['DB_SSL_CERT'] ?? null;
$ssl_key = $_ENV['DB_SSL_KEY'] ?? null;

try {
    // Build DSN / Construir DSN
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
    
    // PDO options for enhanced security and performance
    // Opções PDO para segurança e performance aprimoradas
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,          // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Fetch associative arrays by default
        PDO::ATTR_EMULATE_PREPARES => false,                  // Use native prepared statements
        PDO::ATTR_PERSISTENT => false,                        // Don't use persistent connections
        PDO::ATTR_TIMEOUT => 30,                              // Connection timeout
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        PDO::MYSQL_ATTR_FOUND_ROWS => true,                   // Return found rows instead of changed rows
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false       // Disable SSL cert verification for localhost
    ];
    
    // Add SSL options if configured / Adicionar opções SSL se configuradas
    if ($ssl_ca) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ssl_ca;
    }
    if ($ssl_cert) {
        $options[PDO::MYSQL_ATTR_SSL_CERT] = $ssl_cert;
    }
    if ($ssl_key) {
        $options[PDO::MYSQL_ATTR_SSL_KEY] = $ssl_key;
    }
    
    // Create PDO connection / Criar conexão PDO
    $conn = new PDO($dsn, $username, $password, $options);
    
    // Set timezone / Definir fuso horário
    $timezone = $_ENV['DB_TIMEZONE'] ?? 'America/Sao_Paulo';
    $conn->exec("SET time_zone = '{$timezone}'");
    
    // Set SQL mode for strict behavior / Definir modo SQL para comportamento rigoroso
    $conn->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    
    // Connection successful log (only in development)
    // Log de conexão bem-sucedida (apenas em desenvolvimento)
    if ($_ENV['APP_ENV'] === 'development') {
        error_log("[VanTracing] Database connection established successfully");
    }
    
} catch(PDOException $e) {
    // Log error securely / Registrar erro de forma segura
    $error_message = "Database connection failed at " . date('Y-m-d H:i:s');
    error_log($error_message . " - Error: " . $e->getMessage());
    
    // Don't expose database errors in production / Não expor erros de banco em produção
    if ($_ENV['APP_ENV'] === 'production') {
        die("Sistema temporariamente indisponível. Tente novamente em alguns minutos.");
    } else {
        die("Erro de conexão com o banco de dados: " . $e->getMessage());
    }
} catch(Exception $e) {
    // Handle other exceptions / Tratar outras exceções
    error_log("[VanTracing] Unexpected error during database connection: " . $e->getMessage());
    die("Erro interno do sistema. Contate o administrador.");
}

/**
 * Helper function to safely execute queries / Função auxiliar para executar consultas com segurança
 */
function safe_query($sql, $params = []) {
    global $conn;
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("[VanTracing] Query error: " . $e->getMessage() . " | SQL: " . $sql);
        throw new Exception("Erro na consulta ao banco de dados");
    }
}

/**
 * Helper function for transactions / Função auxiliar para transações
 */
function execute_transaction($callback) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        $result = $callback($conn);
        $conn->commit();
        return $result;
    } catch(Exception $e) {
        $conn->rollBack();
        error_log("[VanTracing] Transaction error: " . $e->getMessage());
        throw $e;
    }
}

// Test connection function / Função de teste de conexão
function test_database_connection() {
    global $conn;
    
    try {
        $stmt = $conn->query('SELECT 1');
        return $stmt !== false;
    } catch(PDOException $e) {
        return false;
    }
}

// Set global connection for backward compatibility / Definir conexão global para compatibilidade
$GLOBALS['pdo'] = $conn;

// Log successful initialization (only in development)
// Log de inicialização bem-sucedida (apenas em desenvolvimento)
if ($_ENV['APP_ENV'] === 'development') {
    error_log("[VanTracing] Database helper functions loaded successfully");
}
?>