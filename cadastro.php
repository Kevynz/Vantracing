<?php
/**
 * VanTracing - Registration Page
 * Página de Cadastro do VanTracing
 * 
 * This page handles user registration for both drivers and guardians
 * Esta página gerencia o cadastro de usuários para motoristas e responsáveis
 * 
 * @package VanTracing
 * @version 2.0
 * @author Kevyn
 * @since 1.0
 */

// Page configuration / Configuração da página
$tituloPagina = 'Cadastro - VanTracing';
$descricaoPagina = 'Cadastre-se no VanTracing - Sistema de rastreamento escolar';
$palavrasChave = 'cadastro, registro, motorista, responsável, transporte escolar';

// Include header and security / Incluir header e segurança
require_once 'includes/header.php';
proteger_pagina();

// Get user session / Obter sessão do usuário
$usuario = $_SESSION['usuario'] ?? null;
?>

<h2>Dashboard de <?php echo htmlspecialchars($usuario['nome']); ?></h2>
<?php
require_once 'includes/footer.php';
?>