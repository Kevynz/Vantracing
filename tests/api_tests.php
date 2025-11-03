<?php
/**
 * Basic API Test Suite / Suite de Testes Básicos da API
 * 
 * Simple PHP test runner for VanTracing API endpoints
 * Runner de testes simples em PHP para endpoints da API do VanTracing
 * 
 * Usage / Uso: php tests/api_tests.php
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 1.0
 */

class ApiTester {
    private $base_url;
    private $tests_passed = 0;
    private $tests_failed = 0;
    private $test_results = [];
    
    public function __construct($base_url = 'http://localhost:8000') {
        $this->base_url = rtrim($base_url, '/');
        echo "🚐 VanTracing API Test Suite\n";
        echo "===========================\n";
        echo "Base URL: {$this->base_url}\n\n";
    }
    
    /**
     * Run HTTP request / Executar requisição HTTP
     */
    private function request($method, $endpoint, $data = null, $headers = []) {
        $url = $this->base_url . '/' . ltrim($endpoint, '/');
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: VanTracing-Test-Suite/1.0'
            ], $headers)
        ]);
        
        if ($data && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['error' => $error, 'http_code' => 0, 'body' => null];
        }
        
        return [
            'http_code' => $http_code,
            'body' => $response,
            'json' => json_decode($response, true),
            'error' => null
        ];
    }
    
    /**
     * Assert test result / Validar resultado do teste
     */
    private function assert($condition, $test_name, $message = '') {
        if ($condition) {
            $this->tests_passed++;
            echo "✅ PASS: $test_name\n";
            $this->test_results[] = ['name' => $test_name, 'status' => 'PASS', 'message' => $message];
        } else {
            $this->tests_failed++;
            echo "❌ FAIL: $test_name" . ($message ? " - $message" : "") . "\n";
            $this->test_results[] = ['name' => $test_name, 'status' => 'FAIL', 'message' => $message];
        }
    }
    
    /**
     * Test database connection endpoint / Testar endpoint de conexão com banco
     */
    public function testDatabaseConnection() {
        echo "\n📋 Testing Database Connection / Testando Conexão com Banco...\n";
        
        // Test if we can include db_connect without errors
        // Testa se podemos incluir db_connect sem erros
        $response = $this->request('GET', '/api/db_connect.php');
        $this->assert(
            $response['http_code'] !== 500,
            'Database Connection Script',
            "HTTP {$response['http_code']}"
        );
    }
    
    /**
     * Test user registration / Testar registro de usuário
     */
    public function testUserRegistration() {
        echo "\n👤 Testing User Registration / Testando Registro de Usuário...\n";
        
        // Test with missing fields / Testar com campos faltando
        $response = $this->request('POST', '/api/register.php', [
            'nome' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $this->assert(
            $response['http_code'] === 200,
            'Registration Endpoint Accessible',
            "HTTP {$response['http_code']}"
        );
        
        if ($response['json']) {
            $this->assert(
                isset($response['json']['success']),
                'Registration Response Format',
                'Response contains success field'
            );
        }
        
        // Test with valid data / Testar com dados válidos
        $test_email = 'test_' . time() . '@example.com';
        $response = $this->request('POST', '/api/register.php', [
            'nome' => 'Test User',
            'email' => $test_email,
            'senha' => 'test123456',
            'role' => 'responsavel',
            'cpf' => '12345678901',
            'dataNascimento' => '1990-01-01'
        ]);
        
        if ($response['json']) {
            $this->assert(
                is_bool($response['json']['success']),
                'Registration Success Field Type',
                'Success field is boolean'
            );
        }
    }
    
    /**
     * Test login functionality / Testar funcionalidade de login
     */
    public function testLogin() {
        echo "\n🔐 Testing Login / Testando Login...\n";
        
        // Test with invalid credentials / Testar com credenciais inválidas
        $response = $this->request('POST', '/api/login.php', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ]);
        
        $this->assert(
            $response['http_code'] === 200,
            'Login Endpoint Accessible',
            "HTTP {$response['http_code']}"
        );
        
        if ($response['json']) {
            $this->assert(
                isset($response['json']['success']),
                'Login Response Format',
                'Response contains success field'
            );
            
            $this->assert(
                $response['json']['success'] === false,
                'Invalid Login Rejected',
                'Invalid credentials properly rejected'
            );
        }
    }
    
    /**
     * Test CSRF token endpoint / Testar endpoint de token CSRF
     */
    public function testCSRFEndpoint() {
        echo "\n🛡️  Testing CSRF Token / Testando Token CSRF...\n";
        
        $response = $this->request('GET', '/api/csrf.php');
        
        $this->assert(
            $response['http_code'] === 200,
            'CSRF Endpoint Accessible',
            "HTTP {$response['http_code']}"
        );
        
        if ($response['json']) {
            $this->assert(
                isset($response['json']['csrf_token']) || isset($response['json']['error']),
                'CSRF Response Format',
                'Response contains csrf_token or error field'
            );
        }
    }
    
    /**
     * Test location tracking endpoints / Testar endpoints de rastreamento
     */
    public function testLocationEndpoints() {
        echo "\n📍 Testing Location Tracking / Testando Rastreamento de Localização...\n";
        
        // Test update location endpoint / Testar endpoint de atualização de localização
        $response = $this->request('POST', '/api/update_location.php', [
            'lat' => -23.5505,
            'lng' => -46.6333,
            'accuracy' => 10
        ]);
        
        $this->assert(
            $response['http_code'] === 200,
            'Update Location Endpoint Accessible',
            "HTTP {$response['http_code']}"
        );
        
        // Test get location endpoint / Testar endpoint de obtenção de localização
        $response = $this->request('GET', '/api/get_location.php');
        
        $this->assert(
            $response['http_code'] === 200,
            'Get Location Endpoint Accessible',
            "HTTP {$response['http_code']}"
        );
    }
    
    /**
     * Test password reset functionality / Testar funcionalidade de redefinição de senha
     */
    public function testPasswordReset() {
        echo "\n🔄 Testing Password Reset / Testando Redefinição de Senha...\n";
        
        $response = $this->request('POST', '/api/request_reset.php', [
            'email' => 'test@example.com'
        ]);
        
        $this->assert(
            $response['http_code'] === 200,
            'Password Reset Request Accessible',
            "HTTP {$response['http_code']}"
        );
        
        if ($response['json']) {
            $this->assert(
                isset($response['json']['success']),
                'Password Reset Response Format',
                'Response contains success field'
            );
        }
    }
    
    /**
     * Test email notification system / Testar sistema de notificação por email
     */
    public function testEmailNotifications() {
        echo "\n📧 Testing Email Notifications / Testando Notificações por Email...\n";
        
        // Test email notification class can be loaded
        // Testa se a classe de notificação por email pode ser carregada
        try {
            require_once __DIR__ . '/../api/email_notifications.php';
            $emailer = new EmailNotification();
            
            $this->assert(
                true,
                'Email Notification Class Loaded',
                'EmailNotification class instantiated successfully'
            );
            
            // Test configuration test method
            // Testa método de teste de configuração
            $test_result = $emailer->testConfiguration();
            $this->assert(
                isset($test_result['success']),
                'Email Configuration Test Method',
                'testConfiguration method returns expected format'
            );
            
        } catch (Exception $e) {
            $this->assert(
                false,
                'Email Notification Class Loaded',
                'Error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test file security / Testar segurança de arquivos
     */
    public function testFileSecurity() {
        echo "\n🔒 Testing File Security / Testando Segurança de Arquivos...\n";
        
        // Test that .env file is not accessible / Testar que arquivo .env não é acessível
        $response = $this->request('GET', '/.env');
        $this->assert(
            $response['http_code'] === 404 || $response['http_code'] === 403,
            '.env File Protection',
            "HTTP {$response['http_code']} (should be 403/404)"
        );
        
        // Test that database migrations are not accessible / Testar que migrações não são acessíveis
        $response = $this->request('GET', '/database/migrations/001_init.sql');
        $this->assert(
            $response['http_code'] === 404 || $response['http_code'] === 403,
            'Database Migrations Protection',
            "HTTP {$response['http_code']} (should be 403/404)"
        );
        
        // Test that logs directory is not accessible / Testar que diretório de logs não é acessível
        $response = $this->request('GET', '/logs/');
        $this->assert(
            $response['http_code'] === 404 || $response['http_code'] === 403,
            'Logs Directory Protection',
            "HTTP {$response['http_code']} (should be 403/404)"
        );
    }
    
    /**
     * Run all tests / Executar todos os testes
     */
    public function runAllTests() {
        $start_time = microtime(true);
        
        $this->testDatabaseConnection();
        $this->testUserRegistration();
        $this->testLogin();
        $this->testCSRFEndpoint();
        $this->testLocationEndpoints();
        $this->testPasswordReset();
        $this->testEmailNotifications();
        $this->testFileSecurity();
        
        $end_time = microtime(true);
        $execution_time = round($end_time - $start_time, 2);
        
        $this->printSummary($execution_time);
    }
    
    /**
     * Print test summary / Imprimir resumo dos testes
     */
    private function printSummary($execution_time) {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 TEST SUMMARY / RESUMO DOS TESTES\n";
        echo str_repeat("=", 50) . "\n";
        echo "✅ Passed / Aprovados: {$this->tests_passed}\n";
        echo "❌ Failed / Falharam: {$this->tests_failed}\n";
        echo "⏱️  Execution Time / Tempo de Execução: {$execution_time}s\n";
        echo "🎯 Success Rate / Taxa de Sucesso: " . 
             round(($this->tests_passed / ($this->tests_passed + $this->tests_failed)) * 100, 1) . "%\n";
        
        if ($this->tests_failed > 0) {
            echo "\n❌ FAILED TESTS / TESTES FALHARAM:\n";
            foreach ($this->test_results as $result) {
                if ($result['status'] === 'FAIL') {
                    echo "  - {$result['name']}: {$result['message']}\n";
                }
            }
        }
        
        echo "\n" . str_repeat("=", 50) . "\n";
        
        if ($this->tests_failed === 0) {
            echo "🎉 All tests passed! / Todos os testes passaram!\n";
            exit(0);
        } else {
            echo "⚠️  Some tests failed. Please check the results above.\n";
            echo "   Alguns testes falharam. Verifique os resultados acima.\n";
            exit(1);
        }
    }
}

// Run tests if executed directly / Executar testes se executado diretamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $base_url = $argv[1] ?? 'http://localhost:8000';
    
    echo "Starting API tests with base URL: $base_url\n";
    echo "Make sure the server is running with: php -S localhost:8000 -t .\n\n";
    
    $tester = new ApiTester($base_url);
    $tester->runAllTests();
}
?>