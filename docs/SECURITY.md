# Security Middleware Documentation / Documentação do Middleware de Segurança

## Overview / Visão Geral

The VanTracing security middleware provides comprehensive security protection for the application, including security headers, rate limiting, input validation, CSRF protection, and threat detection.

O middleware de segurança VanTracing fornece proteção de segurança abrangente para a aplicação, incluindo cabeçalhos de segurança, limitação de taxa, validação de entrada, proteção CSRF e detecção de ameaças.

## Components / Componentes

### 1. SecurityMiddleware Class (`api/security_middleware.php`)

Core security functionality including:
- Security headers (HSTS, CSP, X-Frame-Options, etc.)
- Rate limiting protection
- Input sanitization and validation
- Suspicious pattern detection
- Security incident logging

Funcionalidade de segurança principal incluindo:
- Cabeçalhos de segurança (HSTS, CSP, X-Frame-Options, etc.)
- Proteção de limitação de taxa
- Sanitização e validação de entrada
- Detecção de padrões suspeitos
- Registro de incidentes de segurança

### 2. Security Configuration (`api/security_config.php`)

Centralized configuration for all security settings:
- Rate limiting parameters
- Security header policies
- Input validation rules
- Threat detection patterns
- Session security settings

Configuração centralizada para todas as configurações de segurança:
- Parâmetros de limitação de taxa
- Políticas de cabeçalhos de segurança
- Regras de validação de entrada
- Padrões de detecção de ameaças
- Configurações de segurança da sessão

### 3. Security Helper (`api/security_helper.php`)

Easy-to-use helper functions for API endpoints:
- Quick security initialization
- Authentication and authorization checks
- CSRF token generation and validation
- Input sanitization shortcuts
- Standardized response functions

Funções auxiliares fáceis de usar para endpoints da API:
- Inicialização rápida de segurança
- Verificações de autenticação e autorização
- Geração e validação de token CSRF
- Atalhos de sanitização de entrada
- Funções de resposta padronizadas

### 4. Apache Security Configuration (`.htaccess`)

Web server level security:
- Security headers configuration
- File and directory protection
- Request filtering and blocking
- Rate limiting (with mod_evasive)
- Custom error pages

Segurança no nível do servidor web:
- Configuração de cabeçalhos de segurança
- Proteção de arquivos e diretórios
- Filtragem e bloqueio de requisições
- Limitação de taxa (com mod_evasive)
- Páginas de erro personalizadas

## Quick Start / Início Rápido

### Basic API Protection / Proteção Básica da API

```php
<?php
// Include security helper
require_once 'security_helper.php';

// Initialize security with default settings
secure_api();

// Your API logic here
send_success(['message' => 'Hello World!']);
?>
```

### Authenticated Endpoint / Endpoint Autenticado

```php
<?php
require_once 'security_helper.php';

// Initialize security
secure_api();

// Require authentication
$user_id = require_auth();

// Validate CSRF for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
}

// Your authenticated logic here
send_success(['user_id' => $user_id]);
?>
```

### Admin-Only Endpoint / Endpoint Somente Admin

```php
<?php
require_once 'security_helper.php';

// Initialize security with stricter rate limiting
secure_api(['rate_limit' => 20]);

// Require admin permissions
require_permission('admin');

// Validate CSRF
validate_csrf();

// Your admin logic here
send_success(['message' => 'Admin action completed']);
?>
```

## Configuration / Configuração

### Environment Variables / Variáveis de Ambiente

Add these to your `.env` file:

```
# Security settings
FORCE_HTTPS=true
HSTS_MAX_AGE=31536000
CSP_ENABLED=true
FRAME_OPTIONS=SAMEORIGIN
REFERRER_POLICY=strict-origin-when-cross-origin
PERMISSIONS_POLICY=geolocation=(self), camera=(), microphone=()
DEBUG=false
APP_ENV=production
```

### Rate Limiting Configuration / Configuração de Limitação de Taxa

Modify `api/security_config.php`:

```php
'rate_limiting' => [
    'enabled' => true,
    'api_requests_per_minute' => 60,        // General API requests
    'login_attempts_per_minute' => 5,       // Login attempts
    'registration_attempts_per_hour' => 3,  // Registration attempts
    'password_reset_attempts_per_hour' => 3 // Password reset attempts
]
```

### Content Security Policy / Política de Segurança de Conteúdo

Update CSP directives in `api/security_config.php`:

```php
'csp' => [
    'enabled' => true,
    'directives' => [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline' https://trusted-cdn.com",
        'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com"
        // Add more directives as needed
    ]
]
```

## Security Features / Recursos de Segurança

### 1. Security Headers / Cabeçalhos de Segurança

- **HSTS**: Forces HTTPS connections / Força conexões HTTPS
- **CSP**: Prevents XSS and injection attacks / Previne ataques XSS e injeção
- **X-Frame-Options**: Prevents clickjacking / Previne clickjacking
- **X-Content-Type-Options**: Prevents MIME sniffing / Previne MIME sniffing
- **X-XSS-Protection**: Browser XSS protection / Proteção XSS do navegador
- **Referrer-Policy**: Controls referrer information / Controla informações de referência

### 2. Rate Limiting / Limitação de Taxa

Protects against:
- Brute force attacks / Ataques de força bruta
- DDoS attempts / Tentativas de DDoS
- API abuse / Abuso da API

Configuration per endpoint:
- General API: 60 requests/minute / API geral: 60 requisições/minuto
- Login: 5 attempts/minute / Login: 5 tentativas/minuto
- Registration: 3 attempts/hour / Registro: 3 tentativas/hora

### 3. Input Validation / Validação de Entrada

Automatic detection and blocking of:
- SQL injection attempts / Tentativas de injeção SQL
- XSS attacks / Ataques XSS
- Path traversal / Travessia de caminho
- Template injection / Injeção de template
- Code execution attempts / Tentativas de execução de código

### 4. CSRF Protection / Proteção CSRF

- Automatic token generation / Geração automática de token
- Token validation for state-changing operations / Validação de token para operações que alteram estado
- Session-based token management / Gerenciamento de token baseado em sessão

### 5. Authentication & Authorization / Autenticação e Autorização

Role-based access control:
- **user**: Basic user permissions / Permissões básicas de usuário
- **driver**: Driver-specific permissions / Permissões específicas do motorista
- **responsible**: Parent/guardian permissions / Permissões de responsável
- **admin**: Full system access / Acesso total ao sistema

### 6. Logging & Monitoring / Registro e Monitoramento

Automatic logging of:
- Security incidents / Incidentes de segurança
- Authentication attempts / Tentativas de autenticação
- Rate limit violations / Violações de limite de taxa
- Suspicious activities / Atividades suspeitas

Log files location:
- Security log: `logs/security.log`
- Access log: `logs/access.log`
- Rate limit cache: `logs/rate_limit_*.tmp`

## Integration Examples / Exemplos de Integração

### Updating Existing Endpoints / Atualizando Endpoints Existentes

#### Before / Antes:
```php
<?php
require_once 'db_connect.php';

// Vulnerable endpoint
if ($_POST['action'] === 'update') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->execute([$name, $email, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
}
?>
```

#### After / Depois:
```php
<?php
require_once 'security_helper.php';
require_once 'db_connect.php';

// Secure endpoint
secure_api();
$user_id = require_auth();
validate_csrf();

if ($_POST['action'] === 'update') {
    // Sanitize input
    $name = clean_input($_POST['name'], 'string');
    $email = clean_input($_POST['email'], 'email');
    
    // Validate email
    if (!SecurityHelper::validateEmail($email)) {
        send_error('Invalid email', 'Email inválido', 400);
    }
    
    // Rate limit updates
    SecurityHelper::rateLimitAction('profile_update', 10, 60);
    
    // Database update
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->execute([$name, $email, $user_id]);
    
    // Log the action
    SecurityHelper::logEvent('profile_update', ['user_id' => $user_id]);
    
    send_success(['message' => 'Profile updated']);
}
?>
```

### File Upload Security / Segurança de Upload de Arquivo

```php
<?php
require_once 'security_helper.php';

secure_api();
$user_id = require_auth();
validate_csrf();

// Rate limit uploads
SecurityHelper::rateLimitAction('file_upload', 5, 60);

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Validate file type and size
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        send_error('Invalid file type', 'Tipo de arquivo inválido', 400);
    }
    
    if ($file['size'] > $max_size) {
        send_error('File too large', 'Arquivo muito grande', 400);
    }
    
    // Generate secure filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $secure_name = SecurityHelper::generateSecureToken(16) . '.' . $extension;
    
    // Move file to secure location
    $upload_dir = '../uploads/';
    move_uploaded_file($file['tmp_name'], $upload_dir . $secure_name);
    
    send_success(['filename' => $secure_name]);
}
?>
```

## Security Best Practices / Melhores Práticas de Segurança

### 1. Always Use HTTPS / Sempre Use HTTPS
- Force HTTPS redirects / Force redirecionamentos HTTPS
- Use HSTS headers / Use cabeçalhos HSTS
- Secure cookie settings / Configurações seguras de cookie

### 2. Input Validation / Validação de Entrada
- Sanitize all user input / Sanitize toda entrada do usuário
- Validate data types and formats / Valide tipos e formatos de dados
- Use prepared statements / Use declarações preparadas

### 3. Authentication / Autenticação
- Implement strong password policies / Implemente políticas de senha fortes
- Use secure session management / Use gerenciamento de sessão seguro
- Implement account lockout mechanisms / Implemente mecanismos de bloqueio de conta

### 4. Authorization / Autorização
- Check permissions on every request / Verifique permissões em cada requisição
- Use role-based access control / Use controle de acesso baseado em função
- Implement least privilege principle / Implemente princípio do menor privilégio

### 5. Error Handling / Tratamento de Erros
- Don't expose system information / Não exponha informações do sistema
- Log security events / Registre eventos de segurança
- Use custom error pages / Use páginas de erro personalizadas

### 6. Monitoring / Monitoramento
- Monitor security logs regularly / Monitore logs de segurança regularmente
- Set up alerts for suspicious activities / Configure alertas para atividades suspeitas
- Regular security audits / Auditorias de segurança regulares

## Troubleshooting / Solução de Problemas

### Common Issues / Problemas Comuns

#### Rate Limit Exceeded / Limite de Taxa Excedido
```json
{
  "error": "Rate limit exceeded",
  "message": "Too many requests. Please try again later.",
  "retry_after": 60
}
```

**Solution**: Reduce request frequency or increase rate limits in configuration.
**Solução**: Reduza a frequência de requisições ou aumente os limites de taxa na configuração.

#### CSRF Token Invalid / Token CSRF Inválido
```json
{
  "error": "Invalid CSRF token",
  "message_pt": "Token CSRF inválido"
}
```

**Solution**: Ensure CSRF token is included in POST requests and session is active.
**Solução**: Certifique-se de que o token CSRF está incluído nas requisições POST e a sessão está ativa.

#### Content Security Policy Violations / Violações da Política de Segurança de Conteúdo

Check browser console for CSP errors and update policy in configuration.
Verifique o console do navegador para erros CSP e atualize a política na configuração.

### Log Analysis / Análise de Logs

Security log format / Formato do log de segurança:
```json
{
  "type": "suspicious_input",
  "timestamp": "2024-01-15 10:30:00",
  "ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "request_uri": "/api/login.php",
  "data": {
    "pattern": "/(<script[^>]*>.*?<\/script>)/is",
    "input": "<script>alert('xss')</script>"
  }
}
```

## Performance Considerations / Considerações de Performance

### Rate Limiting Storage / Armazenamento de Limitação de Taxa

The current implementation uses file-based storage for rate limiting. For high-traffic applications, consider:

A implementação atual usa armazenamento baseado em arquivo para limitação de taxa. Para aplicações de alto tráfego, considere:

- Redis or Memcached for rate limit storage / Redis ou Memcached para armazenamento de limite de taxa
- Database-based rate limiting / Limitação de taxa baseada em banco de dados
- CDN-level rate limiting / Limitação de taxa no nível CDN

### Security Header Optimization / Otimização de Cabeçalhos de Segurança

- Cache security headers at web server level / Cache cabeçalhos de segurança no nível do servidor web
- Use CDN for header injection / Use CDN para injeção de cabeçalhos
- Optimize CSP policies for performance / Otimize políticas CSP para performance

## Migration Guide / Guia de Migração

### Step 1: Install Security Files / Instalar Arquivos de Segurança

Copy the following files to your project:
- `api/security_middleware.php`
- `api/security_config.php`
- `api/security_helper.php`
- `.htaccess` (backup existing file first)

### Step 2: Update Environment Configuration / Atualizar Configuração do Ambiente

Add security variables to `.env`:
```
FORCE_HTTPS=true
CSP_ENABLED=true
DEBUG=false
APP_ENV=production
```

### Step 3: Update Existing Endpoints / Atualizar Endpoints Existentes

Replace vulnerable patterns with secure alternatives using examples from this documentation.

### Step 4: Test Security Features / Testar Recursos de Segurança

- Test rate limiting with rapid requests / Teste limitação de taxa com requisições rápidas
- Verify CSRF protection / Verifique proteção CSRF
- Test input validation with malicious payloads / Teste validação de entrada com payloads maliciosos
- Check security headers with online tools / Verifique cabeçalhos de segurança com ferramentas online

## Support / Suporte

For additional security questions or issues:
- Check security logs for detailed error information / Verifique logs de segurança para informações detalhadas de erro
- Review configuration files for proper settings / Revise arquivos de configuração para configurações adequadas
- Test with security scanners for vulnerabilities / Teste com scanners de segurança para vulnerabilidades

## Updates / Atualizações

Keep security components updated regularly:
- Monitor for new security vulnerabilities / Monitore novas vulnerabilidades de segurança
- Update threat detection patterns / Atualize padrões de detecção de ameaças
- Review and update security policies / Revise e atualize políticas de segurança