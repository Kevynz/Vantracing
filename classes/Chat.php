<?php
// classes/Chat.php

class Chat {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Envia uma mensagem de um remetente para um destinatário.
     */
    public function enviarMensagem(int $remetente_id, int $destinatario_id, string $mensagem): bool {
        $sql = "INSERT INTO mensagens_chat (remetente_id, destinatario_id, mensagem) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$remetente_id, $destinatario_id, $mensagem]);
    }

    /**
     * Obtém todas as mensagens entre dois usuários.
     */
    public function obterConversa(int $usuario1_id, int $usuario2_id): array {
        $sql = "SELECT * FROM mensagens_chat 
                WHERE (remetente_id = ? AND destinatario_id = ?) 
                   OR (remetente_id = ? AND destinatario_id = ?)
                ORDER BY data_envio ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuario1_id, $usuario2_id, $usuario2_id, $usuario1_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>