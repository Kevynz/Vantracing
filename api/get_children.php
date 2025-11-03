<?php
/**
 * Get Children API with Cache Integration / API Obter Crianças com Integração de Cache
 * Optimized version with performance monitoring and cache
 * Versão otimizada com monitoramento de performance e cache
 */

// Inicia o buffer de saída para capturar qualquer output indesejado.
ob_start();

// Define o tipo de conteúdo como JSON desde o início.
header('Content-Type: application/json');

// Include required systems / Incluir sistemas necessários
require_once 'cache_system.php';
require_once 'performance_monitor.php';

// Start performance monitoring / Iniciar monitoramento de performance
PerformanceMonitor::startTimer('get_children_api');

// Função para enviar uma resposta JSON limpa e terminar a execução.
function send_json_response($success, $data) {
    // End performance monitoring / Finalizar monitoramento de performance
    PerformanceMonitor::endTimer('get_children_api', [
        'success' => $success,
        'children_count' => count($data['children'] ?? []),
        'cached' => $data['from_cache'] ?? false
    ]);
    
    // Limpa qualquer output que possa ter sido gerado (avisos, etc.).
    ob_end_clean();
    // Adiciona o status de sucesso ao array de dados.
    $data['success'] = $success;
    // Envia a resposta JSON final.
    echo json_encode($data);
    exit();
}

// Tenta incluir o ficheiro de conexão.
@require 'db_connect.php';

// Verifica se a conexão falhou.
if (!isset($conn)) {
    send_json_response(false, ['msg' => 'Falha crítica na conexão com a base de dados. Verifique o db_connect.php.', 'children' => []]);
}

$usuario_id = $_GET['usuario_id'] ?? 0;

if ($usuario_id <= 0) {
    send_json_response(false, ['msg' => 'ID de utilizador inválido.', 'children' => []]);
}

// Check cache first / Verificar cache primeiro
$cache_key = 'children_user_' . $usuario_id;
$cached_children = VanTracingCache::get($cache_key);

if ($cached_children !== null) {
    // Return cached data / Retornar dados do cache
    send_json_response(true, [
        'msg' => 'Lista de crianças obtida do cache.',
        'children' => $cached_children,
        'from_cache' => true
    ]);
}

// Execute database query with performance monitoring / Executar consulta do banco com monitoramento
$sql = "SELECT id, nome, data_nascimento, escola FROM criancas WHERE usuario_id = ?";

try {
    $children = PerformanceMonitor::monitorDatabaseQuery($sql, [$usuario_id], function() use ($conn, $sql, $usuario_id) {
        $stmt = $conn->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    });
    
    // Cache the results for 30 minutes / Cache os resultados por 30 minutos
    VanTracingCache::set($cache_key, $children, 1800);
    
    send_json_response(true, [
        'msg' => 'Lista de crianças obtida com sucesso.',
        'children' => $children,
        'from_cache' => false
    ]);
    
} catch (Exception $e) {
    // Log error with performance monitor / Registrar erro com monitor de performance
    PerformanceMonitor::recordMetric('database_error', 1, 'count', [
        'operation' => 'get_children',
        'error' => $e->getMessage()
    ]);
    
    send_json_response(false, [
        'msg' => 'Erro ao consultar crianças: ' . $e->getMessage(),
        'children' => []
    ]);
}

?>