<?php
// api/arquivar_rota.php
require_once '../config.php';
require_once '../classes/Rota.php';
proteger_pagina('motorista');

$rota = new Rota($pdo);
if ($rota->finalizarERegistrar($_SESSION['usuario']['id'])) {
    echo json_encode(['status' => 'sucesso']);
} else {
    echo json_encode(['status' => 'erro']);
}
?>