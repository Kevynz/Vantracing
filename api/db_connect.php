<?php
/**
 * Database Connection Handler / Gerenciador de Conexão com Banco de Dados
 * 
 * This file establishes a secure connection to the MySQL database using PDO.
 * It reads configuration from environment variables for enhanced security.
 * 
 * Este arquivo estabelece uma conexão segura com o banco de dados MySQL usando PDO.
 * Lê a configuração de variáveis de ambiente para segurança aprimorada.
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
        // Skip comments / Pula comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE format / Analisa formato CHAVE=VALOR
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present / Remove aspas se presentes
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            // Set environment variable if not already set
            // Define variável de ambiente se ainda não estiver definida
            if (!array_key_exists($key, $_ENV)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Load .env file from parent directory / Carrega arquivo .env do diretório pai
loadEnv(__DIR__ . '/../.env');

// Database configuration with fallback to legacy values
// Configuração do banco de dados com fallback para valores legados
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'vantracing_db';
$db_user = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASSWORD') ?: '3545';
$db_charset = getenv('DB_CHARSET') ?: 'utf8mb4';

// PDO options for security and performance
// Opções do PDO para segurança e desempenho
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      // Throw exceptions on errors / Lança exceções em erros
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,            // Fetch associative arrays / Busca arrays associativos
    PDO::ATTR_EMULATE_PREPARES   => false,                       // Use real prepared statements / Usa prepared statements reais
    PDO::ATTR_PERSISTENT         => false,                       // Don't use persistent connections / Não usa conexões persistentes
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$db_charset}"    // Set charset / Define charset
];

try {
    // Create PDO connection / Cria conexão PDO
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    $conn = new PDO($dsn, $db_user, $db_password, $options);
    
    // Legacy support: Also create mysqli connection for backward compatibility
    // Suporte legado: Também cria conexão mysqli para compatibilidade retroativa
    $conn_mysqli = new mysqli($db_host, $db_user, $db_password, $db_name);
    
    if ($conn_mysqli->connect_error) {
        throw new Exception("MySQLi connection failed: " . $conn_mysqli->connect_error);
    }
    
    $conn_mysqli->set_charset($db_charset);
    
} catch (PDOException $e) {
    // Log error securely without exposing sensitive information
    // Registra erro de forma segura sem expor informações sensíveis
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display user-friendly error message / Exibe mensagem de erro amigável
    if (getenv('APP_DEBUG') === 'true') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Unable to connect to the database. Please try again later. / Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.");
    }
} catch (Exception $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection error. / Erro na conexão com o banco de dados.");
}

/**
 * Helper function to execute prepared statements safely
 * Função auxiliar para executar prepared statements com segurança
 * 
 * @param PDO $pdo Database connection / Conexão com banco de dados
 * @param string $query SQL query with placeholders / Query SQL com placeholders
 * @param array $params Parameters to bind / Parâmetros para vincular
 * @return PDOStatement
 */
function executeQuery($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Execution Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Close database connections gracefully
 * Fecha conexões com banco de dados graciosamente
 */
function closeConnection() {
    global $conn, $conn_mysqli;
    $conn = null;
    if ($conn_mysqli) {
        $conn_mysqli->close();
    }
}

// Register shutdown function to close connections
// Registra função de encerramento para fechar conexões
register_shutdown_function('closeConnection');
