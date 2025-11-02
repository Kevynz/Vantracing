<?php
// login.php
require_once 'config.php';
// ... (lógica de autenticação em PHP, que, se bem-sucedida, faz:)
// $usuario = $resultado_do_banco;
// $_SESSION['usuario'] = $usuario;

if ($usuario['role'] === 'motorista') {
    header("Location: dashboard_motorista.php");
} else {
    header("Location: pages/dashboard_responsavel.php");
}
exit();
?>