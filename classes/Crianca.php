<?php
// classes/Crianca.php

class Crianca {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Cadastra uma nova criança no banco de dados.
     */
    public function cadastrar(string $nome, string $escola, int $responsavel_id): bool {
        $sql = "INSERT INTO criancas (nome, escola, responsavel_id) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nome, $escola, $responsavel_id]);
    }

    /**
     * Lista todas as crianças de um determinado responsável.
     */
    public function listarPorResponsavel(int $responsavel_id): array {
        // Usamos um LEFT JOIN para também buscar o nome do motorista, se houver
        $sql = "SELECT c.*, u.nome as motorista_nome 
                FROM criancas c
                LEFT JOIN usuarios u ON c.motorista_id = u.id
                WHERE c.responsavel_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$responsavel_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Associa uma criança a um motorista.
     */
    public function associarMotorista(int $crianca_id, int $motorista_id, int $responsavel_id): bool {
        // A condição responsavel_id é uma segurança para garantir que um pai não edite o filho de outro
        $sql = "UPDATE criancas SET motorista_id = ? WHERE id = ? AND responsavel_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$motorista_id, $crianca_id, $responsavel_id]);
    }
    
    /**
     * Lista todos os motoristas disponíveis para associação.
     */
    public function listarMotoristas(): array {
        $stmt = $this->pdo->query("SELECT id, nome FROM usuarios WHERE role = 'motorista'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>