<?php
/**
 * Secure API Example / Exemplo de API Segura
 * 
 * Example showing how to integrate security middleware into VanTracing API endpoints
 * Exemplo mostrando como integrar middleware de segurança nos endpoints da API VanTracing
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Include security helper / Incluir auxiliar de segurança
require_once 'security_helper.php';

// Initialize security for this API endpoint / Inicializar segurança para este endpoint da API
// This applies security headers, rate limiting, and HTTPS enforcement
// Isso aplica cabeçalhos de segurança, limitação de taxa e aplicação de HTTPS
$security = secure_api([
    'rate_limit' => 30, // 30 requests per minute
    'window' => 1,      // 1 minute window
    'session' => true   // Start secure session
]);

/**
 * Example 1: Public API endpoint with basic security
 * Exemplo 1: Endpoint de API público com segurança básica
 */
function handlePublicEndpoint() {
    // Validate request method / Validar método da requisição
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        send_error('Method not allowed', 'Método não permitido', 405);
    }
    
    // Sanitize input / Sanitizar entrada
    $query = clean_input($_GET['q'] ?? '', 'string');
    
    // Example response / Resposta de exemplo
    send_success([
        'query' => $query,
        'results' => 'Public data here / Dados públicos aqui'
    ], 'Data retrieved successfully', 'Dados recuperados com sucesso');
}

/**
 * Example 2: Authenticated API endpoint
 * Exemplo 2: Endpoint de API autenticado
 */
function handleAuthenticatedEndpoint() {
    // Require authentication / Exigir autenticação
    $user_id = require_auth();
    
    // Validate CSRF token for POST requests / Validar token CSRF para requisições POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validate_csrf();
        
        // Rate limit for sensitive actions / Limitar taxa para ações sensíveis
        SecurityHelper::rateLimitAction('update_profile', 10, 60); // 10 attempts per hour
    }
    
    // Sanitize and validate input / Sanitizar e validar entrada
    $data = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'name' => clean_input($_POST['name'] ?? '', 'string'),
            'email' => clean_input($_POST['email'] ?? '', 'email'),
            'phone' => clean_input($_POST['phone'] ?? '', 'string')
        ];
        
        // Validate email / Validar email
        if (!SecurityHelper::validateEmail($data['email'])) {
            send_error('Invalid email format', 'Formato de email inválido', 400);
        }
    }
    
    // Log the action / Registrar a ação
    SecurityHelper::logEvent('profile_access', [
        'user_id' => $user_id,
        'action' => $_SERVER['REQUEST_METHOD'],
        'ip' => SecurityHelper::getClientIP()
    ]);
    
    // Example response / Resposta de exemplo
    send_success([
        'user_id' => $user_id,
        'data' => $data
    ], 'Profile updated successfully', 'Perfil atualizado com sucesso');
}

/**
 * Example 3: Admin-only endpoint with elevated permissions
 * Exemplo 3: Endpoint somente admin com permissões elevadas
 */
function handleAdminEndpoint() {
    // Require admin permissions / Exigir permissões de admin
    require_permission('admin');
    
    // Additional rate limiting for admin actions / Limitação de taxa adicional para ações de admin
    SecurityHelper::rateLimitAction('admin_action', 20, 60); // 20 admin actions per hour
    
    // Validate CSRF for all admin actions / Validar CSRF para todas as ações de admin
    validate_csrf();
    
    // Sanitize admin input / Sanitizar entrada do admin
    $action = clean_input($_POST['action'] ?? '', 'string');
    $target_id = clean_input($_POST['target_id'] ?? '', 'int');
    
    // Validate required fields / Validar campos obrigatórios
    if (empty($action) || empty($target_id)) {
        send_error('Missing required fields', 'Campos obrigatórios ausentes', 400);
    }
    
    // Log admin action / Registrar ação do admin
    SecurityHelper::logEvent('admin_action', [
        'admin_id' => $_SESSION['user_id'],
        'action' => $action,
        'target_id' => $target_id,
        'ip' => SecurityHelper::getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Example response / Resposta de exemplo
    send_success([
        'action' => $action,
        'target_id' => $target_id,
        'admin_id' => $_SESSION['user_id']
    ], 'Admin action completed', 'Ação do admin concluída');
}

/**
 * Example 4: File upload endpoint with security checks
 * Exemplo 4: Endpoint de upload de arquivo com verificações de segurança
 */
function handleFileUpload() {
    // Require authentication / Exigir autenticação
    $user_id = require_auth();
    
    // Validate CSRF / Validar CSRF
    validate_csrf();
    
    // Rate limit file uploads / Limitar taxa de uploads de arquivo
    SecurityHelper::rateLimitAction('file_upload', 5, 60); // 5 uploads per hour
    
    // Check if file was uploaded / Verificar se o arquivo foi carregado
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        send_error('No file uploaded or upload error', 'Nenhum arquivo carregado ou erro no upload', 400);
    }
    
    $file = $_FILES['file'];
    
    // Validate file size / Validar tamanho do arquivo
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        send_error('File too large (max 5MB)', 'Arquivo muito grande (máx 5MB)', 400);
    }
    
    // Validate file type / Validar tipo do arquivo
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowed_types)) {
        send_error('Invalid file type', 'Tipo de arquivo inválido', 400);
    }
    
    // Sanitize filename / Sanitizar nome do arquivo
    $filename = clean_input($file['name'], 'string');
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    
    // Generate secure filename / Gerar nome de arquivo seguro
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $secure_filename = SecurityHelper::generateSecureToken(16) . '.' . $extension;
    
    // Example: Save file (implement your own logic) / Exemplo: Salvar arquivo (implemente sua própria lógica)
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $target_path = $upload_dir . $secure_filename;
    
    // Log file upload / Registrar upload do arquivo
    SecurityHelper::logEvent('file_upload', [
        'user_id' => $user_id,
        'original_filename' => $filename,
        'secure_filename' => $secure_filename,
        'file_size' => $file['size'],
        'file_type' => $file['type']
    ]);
    
    // Example response / Resposta de exemplo
    send_success([
        'filename' => $secure_filename,
        'size' => $file['size'],
        'type' => $file['type']
    ], 'File uploaded successfully', 'Arquivo carregado com sucesso');
}

/**
 * Route requests to appropriate handlers / Rotear requisições para manipuladores apropriados
 */
function routeRequest() {
    $endpoint = $_GET['endpoint'] ?? '';
    
    switch ($endpoint) {
        case 'public':
            handlePublicEndpoint();
            break;
            
        case 'profile':
            handleAuthenticatedEndpoint();
            break;
            
        case 'admin':
            handleAdminEndpoint();
            break;
            
        case 'upload':
            handleFileUpload();
            break;
            
        default:
            send_error('Unknown endpoint', 'Endpoint desconhecido', 404);
    }
}

// Handle the request / Manipular a requisição
try {
    routeRequest();
} catch (Exception $e) {
    // Log the exception / Registrar a exceção
    SecurityHelper::logEvent('api_exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Send generic error response / Enviar resposta de erro genérica
    send_error('Internal server error', 'Erro interno do servidor', 500);
}

/**
 * Usage Examples / Exemplos de Uso:
 * 
 * 1. Public endpoint / Endpoint público:
 *    GET /api/secure_example.php?endpoint=public&q=search_term
 * 
 * 2. Authenticated endpoint / Endpoint autenticado:
 *    POST /api/secure_example.php?endpoint=profile
 *    Content-Type: application/x-www-form-urlencoded
 *    Body: csrf_token=ABC123&name=John&email=john@example.com
 * 
 * 3. Admin endpoint / Endpoint admin:
 *    POST /api/secure_example.php?endpoint=admin
 *    Content-Type: application/x-www-form-urlencoded
 *    Body: csrf_token=ABC123&action=delete_user&target_id=123
 * 
 * 4. File upload endpoint / Endpoint de upload:
 *    POST /api/secure_example.php?endpoint=upload
 *    Content-Type: multipart/form-data
 *    Body: csrf_token=ABC123&file=@image.jpg
 */
?>