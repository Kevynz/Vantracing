<?php
// classes/GerenciadorUsuarios.php

class GerenciadorUsuarios {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ... (métodos cadastrarUsuario e autenticarUsuario já existem) ...

    /**
     * Busca os dados completos de um usuário pelo seu ID.
     */
    public function obterPorId(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        return $usuario ?: null;
    }

    /**
     * Atualiza as informações do perfil público do usuário.
     */
    public function atualizarPerfil(int $id, array $dados): bool {
        $sql = "UPDATE usuarios SET nome = ?, profissao = ?, bio = ?, site = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $dados['nome'],
            $dados['profissao'],
            $dados['bio'],
            $dados['site'],
            $id
        ]);
    }

    /**
     * Atualiza o caminho da foto de perfil no banco de dados.
     */
    public function atualizarFoto(int $id, string $caminhoFoto): bool {
        $sql = "UPDATE usuarios SET foto_perfil = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$caminhoFoto, $id]);
    }
    
    /**
     * Atualiza a senha do usuário após verificar a senha atual.
     */
    public function atualizarSenha(int $id, string $senhaAtual, string $novaSenha): bool {
        $stmt = $this->pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se a senha atual está correta
        if ($usuario && password_verify($senhaAtual, $usuario['senha'])) {
            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmtUpdate = $this->pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            return $stmtUpdate->execute([$novaSenhaHash, $id]);
        }
        return false; // Senha atual incorreta
    }
    /**
     * Gera um token de reset de senha para um usuário.
     * Retorna o token em texto plano para ser enviado por e-mail.
     */
    public function gerarTokenReset(string $email): ?string {
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $token = bin2hex(random_bytes(32)); // Token seguro
            $tokenHash = hash('sha256', $token); // Armazenamos o hash, não o token
            $expira = date('Y-m-d H:i:s', time() + 3600); // Token expira em 1 hora

            $updateStmt = $this->pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expira = ? WHERE id = ?");
            $updateStmt->execute([$tokenHash, $expira, $usuario['id']]);

            return $token; // Retorna o token original para o e-mail
        }
        return null;
    }

    /**
     * Atualiza a senha de um usuário usando um token de reset válido.
     */
    public function atualizarSenhaPorToken(string $token, string $novaSenha): bool {
        $tokenHash = hash('sha256', $token);
        
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_token_expira > NOW()");
        $stmt->execute([$tokenHash]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            // Atualiza a senha e limpa o token
            $updateStmt = $this->pdo->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_token_expira = NULL WHERE id = ?");
            return $updateStmt->execute([$novaSenhaHash, $usuario['id']]);
        }
        return false;
    }
    /**
     * Lista TODOS os usuários do sistema para o painel de admin.
     */
    public function listarTodosUsuarios(): array {
        $stmt = $this->pdo->query("SELECT id, nome, email, role, data_cadastro FROM usuarios ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Atualiza os dados de um usuário (feito pelo admin).
     */
    public function atualizarUsuarioAdmin(int $id, string $nome, string $email, string $role): bool {
        $sql = "UPDATE usuarios SET nome = ?, email = ?, role = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nome, $email, $role, $id]);
    }

    /**
     * Deleta um usuário do sistema.
     */
    public function deletarUsuario(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>