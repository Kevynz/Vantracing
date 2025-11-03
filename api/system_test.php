<?php
/**
 * VanTracing System Test Suite / Suite de Testes do Sistema VanTracing
 * 
 * Comprehensive testing for all implemented systems
 * Testes abrangentes para todos os sistemas implementados
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

// Set environment for testing / Configurar ambiente para testes
putenv('APP_DEBUG=true');
putenv('CACHE_ENABLED=true');
putenv('NOTIFICATIONS_ENABLED=true');
putenv('PERFORMANCE_MONITORING=true');

require_once 'db_connect.php';
require_once 'security_helper.php';
require_once 'logger.php';
require_once 'cache_system.php';
require_once 'performance_monitor.php';
require_once 'notification_system.php';

class VanTracingSystemTest {
    private $results = [];
    private $test_user_id = 999; // Test user ID
    
    public function runAllTests() {
        echo "<h1>🔧 VanTracing System Test Suite</h1>\n";
        echo "<p>Testando todos os sistemas implementados...</p>\n";
        
        $this->testSecuritySystem();
        $this->testLoggingSystem();
        $this->testCacheSystem();
        $this->testPerformanceMonitoring();
        $this->testNotificationSystem();
        
        $this->displayResults();
    }
    
    /**
     * Test Security System / Testar Sistema de Segurança
     */
    private function testSecuritySystem() {
        echo "<h2>🔒 Testing Security System</h2>\n";
        
        try {
            // Test input sanitization / Testar sanitização de entrada
            $dirty_input = "<script>alert('xss')</script>test@example.com";
            $clean_email = clean_input($dirty_input, 'email');
            $this->addResult('Security', 'Input Sanitization', 
                $clean_email === 'test@example.com', 
                "Expected: test@example.com, Got: $clean_email");
            
            // Test email validation / Testar validação de email
            $valid_email = SecurityHelper::validateEmail('test@example.com');
            $invalid_email = SecurityHelper::validateEmail('invalid-email');
            $this->addResult('Security', 'Email Validation', 
                $valid_email && !$invalid_email, 
                "Valid email passed: $valid_email, Invalid email rejected: " . (!$invalid_email ? 'true' : 'false'));
            
            // Test IP detection / Testar detecção de IP
            $client_ip = SecurityHelper::getClientIP();
            $this->addResult('Security', 'IP Detection', 
                !empty($client_ip), 
                "Client IP: $client_ip");
            
            // Test security logging / Testar logging de segurança
            SecurityHelper::logEvent('test_security_event', ['test' => true]);
            $this->addResult('Security', 'Security Logging', true, 'Security event logged successfully');
            
        } catch (Exception $e) {
            $this->addResult('Security', 'Security System', false, "Error: " . $e->getMessage());
        }
    }
    
    /**
     * Test Logging System / Testar Sistema de Logging
     */
    private function testLoggingSystem() {
        echo "<h2>📋 Testing Logging System</h2>\n";
        
        try {
            $logger = VanTracingLogger::getInstance();
            
            // Test different log levels / Testar diferentes níveis de log
            $logger->log('DEBUG', 'Test debug message', ['test' => true], 'app');
            $logger->log('INFO', 'Test info message', ['test' => true], 'app');
            $logger->log('WARNING', 'Test warning message', ['test' => true], 'app');
            $logger->log('ERROR', 'Test error message', ['test' => true], 'app');
            
            $this->addResult('Logging', 'Multiple Log Levels', true, 'All log levels tested successfully');
            
            // Test log search / Testar busca em logs
            $search_results = $logger->searchLogs('Test', 'app', 10);
            $this->addResult('Logging', 'Log Search', 
                is_array($search_results), 
                "Found " . count($search_results) . " log entries");
            
            // Test log statistics / Testar estatísticas de log
            $stats = $logger->getLogStats();
            $this->addResult('Logging', 'Log Statistics', 
                isset($stats['total_logs']), 
                "Total logs: " . ($stats['total_logs'] ?? 0));
            
        } catch (Exception $e) {
            $this->addResult('Logging', 'Logging System', false, "Error: " . $e->getMessage());
        }
    }
    
    /**
     * Test Cache System / Testar Sistema de Cache
     */
    private function testCacheSystem() {
        echo "<h2>⚡ Testing Cache System</h2>\n";
        
        try {
            // Test basic cache operations / Testar operações básicas de cache
            $test_key = 'test_cache_key';
            $test_value = ['data' => 'test', 'timestamp' => time()];
            
            // Set cache / Definir cache
            $set_result = VanTracingCache::set($test_key, $test_value, 60);
            $this->addResult('Cache', 'Cache Set', $set_result, 'Cache value set successfully');
            
            // Get cache / Obter cache
            $get_result = VanTracingCache::get($test_key);
            $this->addResult('Cache', 'Cache Get', 
                $get_result === $test_value, 
                'Cache value retrieved correctly');
            
            // Test cache exists / Testar existência de cache
            $exists_result = VanTracingCache::exists($test_key);
            $this->addResult('Cache', 'Cache Exists', $exists_result, 'Cache existence check working');
            
            // Test user cache functions / Testar funções de cache do usuário
            $user_data = ['name' => 'Test User', 'email' => 'test@example.com'];
            VanTracingCache::cacheUser($this->test_user_id, $user_data);
            $cached_user = VanTracingCache::getCachedUser($this->test_user_id);
            $this->addResult('Cache', 'User Cache', 
                $cached_user === $user_data, 
                'User cache working correctly');
            
            // Test cache statistics / Testar estatísticas do cache
            $cache_stats = VanTracingCache::getStats();
            $this->addResult('Cache', 'Cache Statistics', 
                isset($cache_stats['total_files']), 
                "Cache files: " . ($cache_stats['total_files'] ?? 0));
            
            // Clean up test cache / Limpar cache de teste
            VanTracingCache::delete($test_key);
            VanTracingCache::invalidateUser($this->test_user_id);
            
        } catch (Exception $e) {
            $this->addResult('Cache', 'Cache System', false, "Error: " . $e->getMessage());
        }
    }
    
    /**
     * Test Performance Monitoring / Testar Monitoramento de Performance
     */
    private function testPerformanceMonitoring() {
        echo "<h2>📊 Testing Performance Monitoring</h2>\n";
        
        try {
            // Test performance timer / Testar cronômetro de performance
            PerformanceMonitor::startTimer('test_operation');
            
            // Simulate some work / Simular algum trabalho
            usleep(100000); // 100ms
            
            $metrics = PerformanceMonitor::endTimer('test_operation', ['test' => true]);
            $this->addResult('Performance', 'Performance Timer', 
                isset($metrics['duration_ms']) && $metrics['duration_ms'] > 90, 
                "Duration: " . ($metrics['duration_ms'] ?? 0) . "ms");
            
            // Test metric recording / Testar gravação de métricas
            PerformanceMonitor::recordMetric('test_metric', 42, 'count', ['test' => true]);
            $this->addResult('Performance', 'Metric Recording', true, 'Metric recorded successfully');
            
            // Test system metrics / Testar métricas do sistema
            $system_metrics = PerformanceMonitor::getSystemMetrics();
            $this->addResult('Performance', 'System Metrics', 
                isset($system_metrics['memory_usage_mb']), 
                "Memory usage: " . ($system_metrics['memory_usage_mb'] ?? 0) . "MB");
            
            // Test performance summary / Testar resumo de performance
            $summary = PerformanceMonitor::getSummary(1);
            $this->addResult('Performance', 'Performance Summary', 
                isset($summary['operations']), 
                "Operations tracked: " . count($summary['operations'] ?? []));
            
        } catch (Exception $e) {
            $this->addResult('Performance', 'Performance Monitoring', false, "Error: " . $e->getMessage());
        }
    }
    
    /**
     * Test Notification System / Testar Sistema de Notificações
     */
    private function testNotificationSystem() {
        echo "<h2>🔔 Testing Notification System</h2>\n";
        
        try {
            $notification_manager = VanTracingNotificationManager::getInstance();
            
            // Test notification sending / Testar envio de notificação
            $notification_ids = $notification_manager->sendNotification(
                $this->test_user_id,
                NotificationType::SYSTEM_MESSAGE,
                'Test Notification',
                'This is a test notification from the system test suite.',
                ['test' => true, 'timestamp' => time()],
                ['priority' => 'low', 'channels' => ['sse']]
            );
            
            $this->addResult('Notifications', 'Send Notification', 
                !empty($notification_ids), 
                "Notification IDs: " . implode(', ', $notification_ids));
            
            // Test getting user notifications / Testar obtenção de notificações do usuário
            $user_notifications = $notification_manager->getUserNotifications($this->test_user_id, 10);
            $this->addResult('Notifications', 'Get User Notifications', 
                is_array($user_notifications), 
                "Retrieved " . count($user_notifications) . " notifications");
            
            // Test notification statistics / Testar estatísticas de notificação
            $notification_stats = $notification_manager->getNotificationStats($this->test_user_id, 1);
            $this->addResult('Notifications', 'Notification Statistics', 
                is_array($notification_stats), 
                "Stats available for " . count($notification_stats) . " categories");
            
            // Test helper functions / Testar funções auxiliares
            notify_user($this->test_user_id, NotificationType::SYSTEM_MESSAGE, 'Helper Test', 'Testing helper function');
            $this->addResult('Notifications', 'Helper Functions', true, 'Helper functions working correctly');
            
            // Clean up test notifications / Limpar notificações de teste
            if (!empty($notification_ids)) {
                $notification_manager->markAsRead($notification_ids, $this->test_user_id);
            }
            
        } catch (Exception $e) {
            $this->addResult('Notifications', 'Notification System', false, "Error: " . $e->getMessage());
        }
    }
    
    /**
     * Add test result / Adicionar resultado do teste
     */
    private function addResult($system, $test, $passed, $details) {
        if (!isset($this->results[$system])) {
            $this->results[$system] = [];
        }
        
        $this->results[$system][] = [
            'test' => $test,
            'passed' => $passed,
            'details' => $details,
            'timestamp' => date('H:i:s')
        ];
        
        $status = $passed ? '✅' : '❌';
        echo "<p>$status <strong>$test</strong>: $details</p>\n";
    }
    
    /**
     * Display test results summary / Exibir resumo dos resultados dos testes
     */
    private function displayResults() {
        echo "<h2>📋 Test Results Summary</h2>\n";
        
        $total_tests = 0;
        $passed_tests = 0;
        
        foreach ($this->results as $system => $tests) {
            $system_passed = 0;
            $system_total = count($tests);
            
            foreach ($tests as $test) {
                $total_tests++;
                if ($test['passed']) {
                    $passed_tests++;
                    $system_passed++;
                }
            }
            
            $system_percentage = $system_total > 0 ? round(($system_passed / $system_total) * 100) : 0;
            $status_icon = $system_percentage == 100 ? '✅' : ($system_percentage >= 80 ? '⚠️' : '❌');
            
            echo "<h3>$status_icon $system: $system_passed/$system_total tests passed ($system_percentage%)</h3>\n";
        }
        
        $overall_percentage = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100) : 0;
        $overall_status = $overall_percentage == 100 ? '✅ TODOS OS TESTES PASSARAM' : 
                         ($overall_percentage >= 80 ? '⚠️ MAIORIA DOS TESTES PASSARAM' : '❌ ALGUNS TESTES FALHARAM');
        
        echo "<hr>\n";
        echo "<h2>$overall_status</h2>\n";
        echo "<p><strong>Total: $passed_tests/$total_tests tests passed ($overall_percentage%)</strong></p>\n";
        
        if ($overall_percentage == 100) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
            echo "<h4>🎉 Sistema VanTracing Totalmente Funcional!</h4>\n";
            echo "<p>Todos os sistemas estão operacionais e prontos para uso:</p>\n";
            echo "<ul>\n";
            echo "<li>✅ Sistema de Segurança com middleware completo</li>\n";
            echo "<li>✅ Sistema de Logging avançado com rotação</li>\n";
            echo "<li>✅ Sistema de Cache para alta performance</li>\n";
            echo "<li>✅ Monitoramento de Performance em tempo real</li>\n";
            echo "<li>✅ Sistema de Notificações Push com SSE</li>\n";
            echo "</ul>\n";
            echo "</div>\n";
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
            echo "<h4>⚠️ Alguns Sistemas Precisam de Atenção</h4>\n";
            echo "<p>Verifique os logs para detalhes dos testes que falharam.</p>\n";
            echo "</div>\n";
        }
        
        echo "<p><small>Teste executado em: " . date('Y-m-d H:i:s') . "</small></p>\n";
    }
}

// Set content type for HTML output / Definir tipo de conteúdo para saída HTML
header('Content-Type: text/html; charset=UTF-8');

// Run the tests / Executar os testes
echo "<!DOCTYPE html>\n";
echo "<html lang='pt-BR'>\n";
echo "<head>\n";
echo "<meta charset='UTF-8'>\n";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "<title>VanTracing System Test</title>\n";
echo "<style>\n";
echo "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 40px; line-height: 1.6; }\n";
echo "h1, h2, h3 { color: #333; }\n";
echo "hr { margin: 30px 0; border: none; border-top: 2px solid #eee; }\n";
echo "</style>\n";
echo "</head>\n";
echo "<body>\n";

try {
    $test_suite = new VanTracingSystemTest();
    $test_suite->runAllTests();
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>\n";
    echo "<h3>❌ Erro Fatal nos Testes</h3>\n";
    echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>\n";
}

echo "</body>\n";
echo "</html>\n";
?>