<?php
/**
 * Email Notification System / Sistema de Notificação por Email
 * 
 * Handles sending email notifications for various system events
 * Gerencia o envio de notificações por email para vários eventos do sistema
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

require_once 'db_connect.php';

class EmailNotification {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $smtp_encryption;
    private $from_address;
    private $from_name;
    private $enabled;
    
    public function __construct() {
        // Load email configuration from environment
        // Carrega configuração de email do ambiente
        $this->enabled = getenv('MAIL_ENABLED') !== 'false';
        $this->smtp_host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
        $this->smtp_port = (int)(getenv('MAIL_PORT') ?: 587);
        $this->smtp_username = getenv('MAIL_USERNAME');
        $this->smtp_password = getenv('MAIL_PASSWORD');
        $this->smtp_encryption = getenv('MAIL_ENCRYPTION') ?: 'tls';
        $this->from_address = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@vantracing.com';
        $this->from_name = getenv('MAIL_FROM_NAME') ?: 'VanTracing';
    }
    
    /**
     * Send email using PHP's mail function with SMTP headers
     * Envia email usando função mail() do PHP com cabeçalhos SMTP
     */
    private function sendMail($to, $subject, $message, $headers = '') {
        if (!$this->enabled) {
            error_log("Email notifications disabled, skipping: $to - $subject");
            return false;
        }
        
        // Default headers / Cabeçalhos padrão
        $default_headers = [
            "From: {$this->from_name} <{$this->from_address}>",
            "Reply-To: {$this->from_address}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "X-Mailer: VanTracing v2.0"
        ];
        
        if ($headers) {
            $default_headers[] = $headers;
        }
        
        $header_string = implode("\r\n", $default_headers);
        
        try {
            $result = mail($to, $subject, $message, $header_string);
            if ($result) {
                error_log("Email sent successfully to: $to - Subject: $subject");
            } else {
                error_log("Failed to send email to: $to - Subject: $subject");
            }
            return $result;
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send welcome email to new users
     * Envia email de boas-vindas para novos usuários
     */
    public function sendWelcomeEmail($user_email, $user_name, $user_role) {
        $role_pt = $user_role === 'motorista' ? 'motorista' : 'responsável';
        $role_en = $user_role === 'motorista' ? 'driver' : 'guardian';
        
        $subject = "Bem-vindo ao VanTracing! / Welcome to VanTracing!";
        
        $message = $this->getEmailTemplate('welcome', [
            'user_name' => htmlspecialchars($user_name),
            'role_pt' => $role_pt,
            'role_en' => $role_en,
            'login_url' => getenv('APP_URL') ?: 'http://localhost'
        ]);
        
        return $this->sendMail($user_email, $subject, $message);
    }
    
    /**
     * Send password reset email
     * Envia email de redefinição de senha
     */
    public function sendPasswordResetEmail($user_email, $user_name, $reset_token) {
        $reset_url = (getenv('APP_URL') ?: 'http://localhost') . "/reset-senha.html?token=" . urlencode($reset_token);
        
        $subject = "Redefinição de senha / Password Reset - VanTracing";
        
        $message = $this->getEmailTemplate('password_reset', [
            'user_name' => htmlspecialchars($user_name),
            'reset_url' => $reset_url,
            'expires_in' => getenv('PASSWORD_RESET_EXPIRATION') ?: '60'
        ]);
        
        return $this->sendMail($user_email, $subject, $message);
    }
    
    /**
     * Send route notification to guardians
     * Envia notificação de rota para responsáveis
     */
    public function sendRouteNotification($guardian_email, $guardian_name, $driver_name, $route_status, $child_name = null) {
        $status_messages = [
            'started' => [
                'pt' => 'iniciou a rota',
                'en' => 'started the route'
            ],
            'ended' => [
                'pt' => 'finalizou a rota',
                'en' => 'ended the route'
            ],
            'delayed' => [
                'pt' => 'está com atraso na rota',
                'en' => 'is delayed on the route'
            ]
        ];
        
        $status_pt = $status_messages[$route_status]['pt'] ?? 'atualizou a rota';
        $status_en = $status_messages[$route_status]['en'] ?? 'updated the route';
        
        $subject = "Atualização da rota / Route Update - VanTracing";
        
        $message = $this->getEmailTemplate('route_notification', [
            'guardian_name' => htmlspecialchars($guardian_name),
            'driver_name' => htmlspecialchars($driver_name),
            'status_pt' => $status_pt,
            'status_en' => $status_en,
            'child_name' => $child_name ? htmlspecialchars($child_name) : null,
            'track_url' => (getenv('APP_URL') ?: 'http://localhost') . '/rota-tempo-real.html'
        ]);
        
        return $this->sendMail($guardian_email, $subject, $message);
    }
    
    /**
     * Send security alert email
     * Envia email de alerta de segurança
     */
    public function sendSecurityAlert($user_email, $user_name, $alert_type, $details = []) {
        $alert_messages = [
            'login_attempt' => [
                'subject' => 'Tentativa de login suspeita / Suspicious login attempt',
                'title_pt' => 'Tentativa de Login Detectada',
                'title_en' => 'Login Attempt Detected'
            ],
            'password_changed' => [
                'subject' => 'Senha alterada / Password changed',
                'title_pt' => 'Senha Alterada com Sucesso',
                'title_en' => 'Password Changed Successfully'
            ],
            'account_locked' => [
                'subject' => 'Conta bloqueada / Account locked',
                'title_pt' => 'Conta Temporariamente Bloqueada',
                'title_en' => 'Account Temporarily Locked'
            ]
        ];
        
        $alert_info = $alert_messages[$alert_type] ?? $alert_messages['login_attempt'];
        
        $message = $this->getEmailTemplate('security_alert', [
            'user_name' => htmlspecialchars($user_name),
            'title_pt' => $alert_info['title_pt'],
            'title_en' => $alert_info['title_en'],
            'alert_type' => $alert_type,
            'timestamp' => date('d/m/Y H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ]);
        
        return $this->sendMail($user_email, $alert_info['subject'], $message);
    }
    
    /**
     * Get email template with variables replaced
     * Obtém template de email com variáveis substituídas
     */
    private function getEmailTemplate($template_name, $variables = []) {
        $templates = [
            'welcome' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Bem-vindo / Welcome</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f8f9fa; }
                        .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
                        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>🚐 VanTracing</h1>
                        </div>
                        <div class="content">
                            <h2>Bem-vindo, {{user_name}}! / Welcome, {{user_name}}!</h2>
                            
                            <p><strong>Português:</strong><br>
                            Obrigado por se cadastrar no VanTracing como {{role_pt}}. Sua conta foi criada com sucesso!</p>
                            
                            <p>Agora você pode:</p>
                            <ul>
                                <li>Fazer login na plataforma</li>
                                <li>Gerenciar seu perfil</li>
                                <li>Acessar as funcionalidades do sistema</li>
                            </ul>
                            
                            <hr style="margin: 20px 0;">
                            
                            <p><strong>English:</strong><br>
                            Thank you for registering with VanTracing as a {{role_en}}. Your account has been created successfully!</p>
                            
                            <p>You can now:</p>
                            <ul>
                                <li>Log in to the platform</li>
                                <li>Manage your profile</li>
                                <li>Access system features</li>
                            </ul>
                            
                            <a href="{{login_url}}" class="btn">Fazer Login / Login</a>
                        </div>
                        <div class="footer">
                            <p>VanTracing - Sistema de Rastreamento Escolar / School Tracking System</p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            
            'password_reset' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Redefinição de Senha / Password Reset</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f8f9fa; }
                        .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
                        .btn { display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 10px 0; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>🔐 Redefinição de Senha / Password Reset</h1>
                        </div>
                        <div class="content">
                            <h2>Olá, {{user_name}}! / Hello, {{user_name}}!</h2>
                            
                            <p><strong>Português:</strong><br>
                            Recebemos uma solicitação para redefinir sua senha. Clique no link abaixo para criar uma nova senha:</p>
                            
                            <hr style="margin: 20px 0;">
                            
                            <p><strong>English:</strong><br>
                            We received a request to reset your password. Click the link below to create a new password:</p>
                            
                            <a href="{{reset_url}}" class="btn">Redefinir Senha / Reset Password</a>
                            
                            <div class="warning">
                                <p><strong>⚠️ Importante / Important:</strong></p>
                                <p><strong>PT:</strong> Este link expira em {{expires_in}} minutos. Se você não solicitou esta redefinição, ignore este email.</p>
                                <p><strong>EN:</strong> This link expires in {{expires_in}} minutes. If you did not request this reset, please ignore this email.</p>
                            </div>
                        </div>
                        <div class="footer">
                            <p>VanTracing - Sistema de Rastreamento Escolar / School Tracking System</p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            
            'route_notification' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Atualização de Rota / Route Update</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f8f9fa; }
                        .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
                        .btn { display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>🚐 Atualização de Rota / Route Update</h1>
                        </div>
                        <div class="content">
                            <h2>Olá, {{guardian_name}}! / Hello, {{guardian_name}}!</h2>
                            
                            <p><strong>Português:</strong><br>
                            O motorista <strong>{{driver_name}}</strong> {{status_pt}}{{child_name ? " para " + child_name : ""}}.</p>
                            
                            <hr style="margin: 20px 0;">
                            
                            <p><strong>English:</strong><br>
                            Driver <strong>{{driver_name}}</strong> {{status_en}}{{child_name ? " for " + child_name : ""}}.</p>
                            
                            <p>Acompanhe em tempo real: / Track in real-time:</p>
                            <a href="{{track_url}}" class="btn">Ver Localização / View Location</a>
                            
                            <p><em>Data/Time: {{timestamp}}</em></p>
                        </div>
                        <div class="footer">
                            <p>VanTracing - Sistema de Rastreamento Escolar / School Tracking System</p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            
            'security_alert' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Alerta de Segurança / Security Alert</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f8f9fa; }
                        .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
                        .alert { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
                        .details { background: #e9ecef; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>🚨 {{title_pt}} / {{title_en}}</h1>
                        </div>
                        <div class="content">
                            <h2>Olá, {{user_name}}! / Hello, {{user_name}}!</h2>
                            
                            <div class="alert">
                                <p><strong>Português:</strong> Detectamos uma atividade de segurança em sua conta.</p>
                                <p><strong>English:</strong> We detected a security activity on your account.</p>
                            </div>
                            
                            <p><strong>Detalhes / Details:</strong></p>
                            <div class="details">
                                <p>Tipo / Type: {{alert_type}}</p>
                                <p>Data/Time: {{timestamp}}</p>
                                <p>IP: {{ip_address}}</p>
                                <p>User Agent: {{user_agent}}</p>
                            </div>
                            
                            <p><strong>PT:</strong> Se não foi você, altere sua senha imediatamente e entre em contato conosco.</p>
                            <p><strong>EN:</strong> If this wasn\'t you, change your password immediately and contact us.</p>
                        </div>
                        <div class="footer">
                            <p>VanTracing - Sistema de Rastreamento Escolar / School Tracking System</p>
                        </div>
                    </div>
                </body>
                </html>
            '
        ];
        
        $template = $variables['timestamp'] = date('d/m/Y H:i:s');
        $template = $templates[$template_name] ?? $templates['welcome'];
        
        // Replace variables / Substituir variáveis
        foreach ($variables as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{{' . $key . '}}', $value, $template);
            }
        }
        
        return $template;
    }
    
    /**
     * Test email configuration
     * Testa configuração de email
     */
    public function testConfiguration() {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Email notifications are disabled'];
        }
        
        $test_email = $this->smtp_username ?: 'test@example.com';
        $result = $this->sendMail($test_email, 'VanTracing - Test Email', '<p>This is a test email from VanTracing.</p>');
        
        return [
            'success' => $result,
            'message' => $result ? 'Test email sent successfully' : 'Failed to send test email'
        ];
    }
}

// Helper function to send notifications easily
// Função auxiliar para enviar notificações facilmente
function sendNotification($type, $params) {
    $notification = new EmailNotification();
    
    switch ($type) {
        case 'welcome':
            return $notification->sendWelcomeEmail($params['email'], $params['name'], $params['role']);
        case 'password_reset':
            return $notification->sendPasswordResetEmail($params['email'], $params['name'], $params['token']);
        case 'route':
            return $notification->sendRouteNotification(
                $params['guardian_email'], 
                $params['guardian_name'], 
                $params['driver_name'], 
                $params['status'], 
                $params['child_name'] ?? null
            );
        case 'security':
            return $notification->sendSecurityAlert(
                $params['email'], 
                $params['name'], 
                $params['alert_type'], 
                $params['details'] ?? []
            );
        default:
            return false;
    }
}
?>