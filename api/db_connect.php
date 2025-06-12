<?php
// api/db_connect.php

// Configurações do banco de dados
$servername = "localhost";
$username = "root"; // Padrão do XAMPP
$password = "3545";     // Padrão do XAMPP
$dbname = "vantracing_db";

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define o charset para utf8 para evitar problemas com acentos
$conn->set_charset("utf8");
?>