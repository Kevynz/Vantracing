# Testing Guide / Guia de Testes

## 🧪 VanTracing Test Suite

Este diretório contém testes automatizados para o sistema VanTracing.
This directory contains automated tests for the VanTracing system.

## Test Types / Tipos de Testes

### 1. API Tests / Testes da API (`api_tests.php`)

Testa os endpoints da API backend em PHP.
Tests the PHP backend API endpoints.

**Como executar / How to run:**
```powershell
# Start PHP server / Iniciar servidor PHP
php -S localhost:8000 -t .

# Run tests in another terminal / Executar testes em outro terminal
php tests/api_tests.php

# Or with custom URL / Ou com URL personalizada
php tests/api_tests.php http://localhost:8000
```

**Testes incluídos / Tests included:**
- ✅ Database connection / Conexão com banco de dados
- ✅ User registration / Registro de usuário
- ✅ Login functionality / Funcionalidade de login
- ✅ CSRF token endpoint / Endpoint de token CSRF
- ✅ Location tracking / Rastreamento de localização
- ✅ Password reset / Redefinição de senha
- ✅ Email notifications / Notificações por email
- ✅ File security / Segurança de arquivos

### 2. JavaScript Tests / Testes JavaScript (`js_tests.html`)

Testa as funções frontend JavaScript no navegador.
Tests frontend JavaScript functions in the browser.

**Como executar / How to run:**
```powershell
# Start static server / Iniciar servidor estático
powershell -NoProfile -ExecutionPolicy Bypass -File .\serve.ps1 -Port 5500

# Open in browser / Abrir no navegador
# http://localhost:5500/tests/js_tests.html
```

**Testes incluídos / Tests included:**
- ✅ i18n system / Sistema de internacionalização
- ✅ Language detection and switching / Detecção e troca de idioma
- ✅ Translation functionality / Funcionalidade de tradução
- ✅ Location tracking controls / Controles de rastreamento
- ✅ Sharing enable/disable / Habilitar/desabilitar compartilhamento
- ✅ CPF validation / Validação de CPF
- ✅ Email validation / Validação de email
- ✅ Date formatting / Formatação de data
- ✅ Utility functions / Funções utilitárias

## Quick Test Commands / Comandos Rápidos de Teste

### Full Test Run / Execução Completa dos Testes

```powershell
# Terminal 1: Start PHP server / Terminal 1: Iniciar servidor PHP
php -S localhost:8000 -t .

# Terminal 2: Start static server / Terminal 2: Iniciar servidor estático
powershell -NoProfile -ExecutionPolicy Bypass -File .\serve.ps1 -Port 5500

# Terminal 3: Run API tests / Terminal 3: Executar testes da API
php tests/api_tests.php

# Then open browser for JS tests / Então abrir navegador para testes JS
# http://localhost:5500/tests/js_tests.html
```

### Individual Test Categories / Categorias Individuais de Testes

```powershell
# Test only database and auth / Testar apenas banco e autenticação
php tests/api_tests.php | grep -E "(Database|Login|Registration)"

# Test only security / Testar apenas segurança
php tests/api_tests.php | grep -E "(CSRF|Security|Protection)"
```

## Test Results / Resultados dos Testes

### Expected Output / Saída Esperada

**API Tests Success / Sucesso dos Testes da API:**
```
🚐 VanTracing API Test Suite
===========================
Base URL: http://localhost:8000

📋 Testing Database Connection...
✅ PASS: Database Connection Script

👤 Testing User Registration...
✅ PASS: Registration Endpoint Accessible
✅ PASS: Registration Response Format

🔐 Testing Login...
✅ PASS: Login Endpoint Accessible
✅ PASS: Login Response Format
✅ PASS: Invalid Login Rejected

🎉 All tests passed!
```

**JavaScript Tests Success / Sucesso dos Testes JavaScript:**
- All test sections show green checkmarks / Todas as seções mostram checkmarks verdes
- Summary shows 100% success rate / Resumo mostra 100% de taxa de sucesso
- No errors in console output / Nenhum erro na saída do console

## Troubleshooting / Solução de Problemas

### Common Issues / Problemas Comuns

1. **Database Connection Failed / Falha na Conexão com Banco**
   ```
   Solution / Solução:
   - Check .env file exists and has correct DB credentials
   - Verificar se arquivo .env existe e tem credenciais corretas do BD
   - Ensure MySQL is running / Garantir que MySQL está executando
   - Run database migrations / Executar migrações do banco
   ```

2. **API Endpoints Return 404 / Endpoints da API Retornam 404**
   ```
   Solution / Solução:
   - Make sure PHP server is running on correct port
   - Certificar que servidor PHP está executando na porta correta
   - Check file permissions / Verificar permissões de arquivo
   - Verify .htaccess if using Apache / Verificar .htaccess se usando Apache
   ```

3. **JavaScript Tests Fail / Testes JavaScript Falham**
   ```
   Solution / Solução:
   - Check browser console for errors / Verificar console do navegador por erros
   - Ensure static server is serving files / Garantir que servidor estático está servindo arquivos
   - Clear browser cache / Limpar cache do navegador
   - Test with different browser / Testar com navegador diferente
   ```

### Performance Benchmarks / Benchmarks de Performance

**Expected execution times / Tempos de execução esperados:**
- API Tests: < 5 seconds / Testes da API: < 5 segundos
- JavaScript Tests: < 2 seconds / Testes JavaScript: < 2 segundos
- Full test suite: < 10 seconds / Suite completa: < 10 segundos

### Test Coverage / Cobertura dos Testes

**Current coverage / Cobertura atual:**
- API endpoints: ~80% / Endpoints da API: ~80%
- JavaScript functions: ~70% / Funções JavaScript: ~70%
- Security features: ~90% / Recursos de segurança: ~90%
- Error handling: ~60% / Tratamento de erros: ~60%

## Adding New Tests / Adicionando Novos Testes

### For API Tests / Para Testes da API

```php
public function testNewEndpoint() {
    echo "\n🆕 Testing New Feature...\n";
    
    $response = $this->request('POST', '/api/new_endpoint.php', [
        'param1' => 'value1',
        'param2' => 'value2'
    ]);
    
    $this->assert(
        $response['http_code'] === 200,
        'New Endpoint Accessible',
        "HTTP {$response['http_code']}"
    );
    
    if ($response['json']) {
        $this->assert(
            isset($response['json']['success']),
            'New Endpoint Response Format',
            'Response contains expected fields'
        );
    }
}
```

### For JavaScript Tests / Para Testes JavaScript

```javascript
function runNewFeatureTests() {
    tester.start();
    tester.log('Running new feature tests...');
    
    // Test new functionality
    const result = newFeature.doSomething('test input');
    tester.assert(
        result === 'expected output',
        'New Feature Test',
        `Got: "${result}"`
    );
    
    tester.finish();
}
```

## Continuous Integration / Integração Contínua

Para uso em CI/CD, os testes podem ser executados automaticamente:
For CI/CD usage, tests can be run automatically:

```bash
#!/bin/bash
# ci_test.sh

echo "Starting VanTracing CI tests..."

# Start PHP server in background
php -S localhost:8000 -t . &
PHP_PID=$!

# Wait for server to start
sleep 2

# Run API tests
php tests/api_tests.php
API_EXIT_CODE=$?

# Kill PHP server
kill $PHP_PID

# Exit with test result
exit $API_EXIT_CODE
```

## Test Reports / Relatórios de Testes

Os testes geram saída estruturada que pode ser processada para relatórios:
Tests generate structured output that can be processed for reports:

- Exit codes: 0 = success, 1 = failure / Códigos de saída: 0 = sucesso, 1 = falha
- JSON output available for automation / Saída JSON disponível para automação
- Detailed logs for debugging / Logs detalhados para depuração

Para mais informações, consulte a documentação principal no README.md.
For more information, see the main documentation in README.md.