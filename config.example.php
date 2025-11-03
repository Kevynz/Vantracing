<?php
// config.example.php
// 
// IMPORTANTE: Copie este arquivo para config.php e configure com seus dados reais
// IMPORTANT: Copy this file to config.php and configure with your real data
//
// NÃO FAÇA COMMIT do arquivo config.php - ele deve permanecer no .gitignore
// DO NOT COMMIT the config.php file - it should remain in .gitignore

define('DB_HOST', 'localhost');           // Host do banco de dados
define('DB_USER', 'seu_usuario');         // Usuário do banco de dados
define('DB_PASS', 'sua_senha_segura');    // Senha do banco de dados
define('DB_NAME', 'vantracing');          // Nome do banco de dados

// Configurações de segurança / Security settings
define('JWT_SECRET', 'sua_chave_jwt_super_secreta_aqui'); // Chave secreta para JWT
define('ENCRYPT_KEY', 'sua_chave_encriptacao_aqui');      // Chave de encriptação

// Configurações de email / Email settings (se usar reset de senha)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu_email@gmail.com');
define('SMTP_PASS', 'sua_senha_email');
define('FROM_EMAIL', 'noreply@vantracing.com');
define('FROM_NAME', 'VanTracing System');

// Iniciar sessão em todas as páginas
session_start();

// Conexão com o banco de dados (PDO)
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERRO DE CONEXÃO: " . $e->getMessage());
}

// Função para proteger páginas
function proteger_pagina($rolePermitido = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.html");
        exit();
    }
    
    if ($rolePermitido && isset($_SESSION['role']) && $_SESSION['role'] !== $rolePermitido) {
        header("HTTP/1.1 403 Forbidden");
        exit("Acesso negado");
    }
}

// Função para sanitizar inputs
function limpar_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>