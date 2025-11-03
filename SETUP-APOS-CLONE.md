# 🚨 CONFIGURAÇÃO ESSENCIAL - SETUP APÓS CLONE

## ⚠️ **IMPORTANTE: Execute estas etapas IMEDIATAMENTE após clonar o repositório**

### 1. **Configurar Banco de Dados**

```bash
# Copie o arquivo de exemplo e configure com seus dados
cp api/db_connect.example.php api/db_connect.php
```

Edite `api/db_connect.php` e configure:
- `$host` - Host do banco (geralmente localhost)
- `$dbname` - Nome do banco de dados 
- `$username` - Usuário do banco
- `$password` - **Sua senha real do banco**

### 2. **Configurar Variáveis de Ambiente**

```bash
# Copie o arquivo de exemplo
cp .env.example .env
```

Edite `.env` e configure todas as variáveis, especialmente:
- `DB_PASS=sua_senha_real_aqui`
- `JWT_SECRET=sua_chave_jwt_super_secreta`
- `ENCRYPT_KEY=sua_chave_de_32_caracteres`

### 3. **Configurar Segurança**

```bash
# Copie o arquivo de segurança
cp api/security_config.example.php api/security_config.php
```

Edite `api/security_config.php` e ajuste conforme necessário.

### 4. **Configurar Sistema Básico**

```bash
# Copie a configuração básica
cp config.example.php config.php
```

### 5. **Criar Diretórios Necessários**

```bash
mkdir -p logs cache backups uploads
chmod 755 logs cache backups uploads
```

### 6. **Instalar Banco de Dados**

Execute o SQL que está em `api/` para criar as tabelas necessárias, ou use o sistema de migração se disponível.

## 🔒 **Arquivos que NUNCA devem ir para o Git:**

- `api/db_connect.php` - Contém credenciais do banco
- `config.php` - Configurações com senhas
- `api/security_config.php` - Configurações de segurança
- `.env` - Variáveis de ambiente com dados sensíveis
- `logs/` - Logs do sistema
- `cache/` - Arquivos de cache
- `backups/` - Backups do banco de dados
- `uploads/` - Arquivos enviados por usuários

## ✅ **Verificação de Setup**

Após configurar tudo, acesse:
- `http://localhost/vantracing` - Página principal
- `http://localhost/vantracing/admin_dashboard.html` - Dashboard administrativo
- `http://localhost/vantracing/monitoring_dashboard.html` - Monitoramento

## 🆘 **Em caso de problemas:**

1. Verifique se todos os arquivos `.example.php` foram copiados
2. Confirme se as credenciais do banco estão corretas
3. Verifique se os diretórios tem as permissões corretas
4. Consulte os logs em `logs/vantracing.log`

## 🚀 **Deployment para Produção:**

```bash
# Use o script de deployment automatizado
chmod +x deploy.sh
sudo DB_PASSWORD="senha_producao" DOMAIN="seusite.com" ./deploy.sh
```

---
**⚠️ LEMBRE-SE: Nunca commite arquivos com dados reais! Sempre use os arquivos .example como base.**