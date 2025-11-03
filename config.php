<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vantracing');

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
    if (!isset($_SESSION['usuario'])) {
        header("Location: login.php");
        exit();
    }
    if ($rolePermitido && $_SESSION['usuario']['role'] !== $rolePermitido) {
        die("Acesso negado.");
    }
}


// Em config.php
function gerar_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

?>