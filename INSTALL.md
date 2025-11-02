# VanTracing - Installation Guide
# Guia de Instalação do VanTracing

## Quick Setup / Configuração Rápida

### 1. Copy .env.example to .env / Copie .env.example para .env
```powershell
copy .env.example .env
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

### 4. Apply database migrations / Aplique as migrações do banco
```powershell
mysql -u your_username -p vantracing_db < database/migrations/001_init.sql
mysql -u your_username -p vantracing_db < database/migrations/002_profile_split.sql
```

### 5. Run locally / Execute localmente
- Frontend (static):
```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\serve.ps1 -Port 5500
# open http://localhost:5500
```
- APIs (PHP built-in):
```powershell
php -S localhost:8000 -t .
# APIs at http://localhost:8000/api/...
```

### 6. Access the application / Acesse a aplicação
Navigate to: http://localhost/Vantracing

## Troubleshooting / Solução de Problemas

### Database connection error / Erro de conexão com banco de dados
- Verify credentials in .env file / Verifique credenciais no arquivo .env
- Ensure MySQL service is running / Certifique-se de que o serviço MySQL está rodando
- Check user permissions / Verifique permissões do usuário

### File permission issues / Problemas de permissão de arquivos
- Windows: Execute o terminal como Administrador
- Linux/macOS:
```bash
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
```

### Blank page or errors / Página em branco ou erros
- Check PHP error logs / Verifique logs de erro do PHP
- Enable display_errors in development / Habilite display_errors em desenvolvimento
- Verify all files are uploaded / Verifique se todos os arquivos foram enviados

For more help, see README.md / Para mais ajuda, veja README.md
