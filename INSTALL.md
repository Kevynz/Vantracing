# VanTracing - Installation Guide
# Guia de Instalação do VanTracing

## Quick Setup / Configuração Rápida

### 1. Copy .env.example to .env / Copie .env.example para .env
```bash
cp .env.example .env
```

### 2. Edit .env with your credentials / Edite .env com suas credenciais
```env
DB_HOST=localhost
DB_NAME=vantracing_db
DB_USER=your_username
DB_PASSWORD=your_password
```

### 3. Create database / Crie o banco de dados
```sql
CREATE DATABASE vantracing_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Import database schema / Importe o esquema do banco de dados
```bash
mysql -u your_username -p vantracing_db < api/vantracing_db.sql
```

### 5. Set file permissions / Configure as permissões
```bash
chmod -R 755 .
chmod -R 775 api/
```

### 6. Access the application / Acesse a aplicação
Navigate to: http://localhost/Vantracing

## Troubleshooting / Solução de Problemas

### Database connection error / Erro de conexão com banco de dados
- Verify credentials in .env file / Verifique credenciais no arquivo .env
- Ensure MySQL service is running / Certifique-se de que o serviço MySQL está rodando
- Check user permissions / Verifique permissões do usuário

### File permission issues / Problemas de permissão de arquivos
```bash
# Linux/macOS
sudo chown -R www-data:www-data .
sudo chmod -R 755 .

# Windows: Run as administrator
# Execute como administrador
```

### Blank page or errors / Página em branco ou erros
- Check PHP error logs / Verifique logs de erro do PHP
- Enable display_errors in development / Habilite display_errors em desenvolvimento
- Verify all files are uploaded / Verifique se todos os arquivos foram enviados

For more help, see README.md / Para mais ajuda, veja README.md
