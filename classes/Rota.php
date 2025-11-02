<?php
// classes/Rota.php
class Rota {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Pega a localização atual, arquiva no histórico e limpa a localização ativa.
     */
    public function finalizarERegistrar(int $motorista_id): bool {
        // 1. Pega os pontos da rota ativa (neste caso, apenas o último ponto)
        $stmt = $this->pdo->prepare("SELECT latitude, longitude FROM localizacoes_ativas WHERE motorista_id = ?");
        $stmt->execute([$motorista_id]);
        $localizacao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$localizacao) return false; // Nenhuma rota ativa para finalizar

        // 2. Cria um trajeto (aqui simplificado, mas poderia ser uma lista de pontos)
        $trajeto = json_encode([$localizacao]);

        // 3. Insere no histórico
        $sql = "INSERT INTO historico_rotas (motorista_id, data_rota, hora_inicio, hora_fim, trajeto)
                VALUES (?, CURDATE(), CURTIME(), CURTIME(), ?)"; // Hora de início e fim simplificada
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$motorista_id, $trajeto]);

        // 4. Limpa a localização ativa
        $stmtDelete = $this->pdo->prepare("DELETE FROM localizacoes_ativas WHERE motorista_id = ?");
        $stmtDelete->execute([$motorista_id]);
        
        return true;
    }

    /**
     * Lista o histórico de rotas de um motorista específico.
     */
    public function listarHistorico(int $motorista_id): array {
        $stmt = $this->pdo->prepare("SELECT * FROM historico_rotas WHERE motorista_id = ? ORDER BY data_rota DESC");
        $stmt->execute([$motorista_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obterHistoricoPorId(int $rota_id, int $responsavel_id): ?array {
        // A lógica aqui deve garantir que o responsável só possa ver rotas de seus motoristas associados.
        // Para simplificar, vamos buscar a rota diretamente. Em produção, adicione a verificação de permissão.
        $stmt = $this->pdo->prepare("SELECT * FROM historico_rotas WHERE id = ?");
        $stmt->execute([$rota_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

?>