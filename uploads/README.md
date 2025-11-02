# Uploads Directory / Diretório de Uploads

This directory stores user-uploaded files.
Este diretório armazena arquivos enviados pelos usuários.

## Structure / Estrutura

```
uploads/
├── avatars/       # User profile pictures / Fotos de perfil
├── documents/     # Documents and files / Documentos e arquivos
├── images/        # General images / Imagens gerais
└── temp/          # Temporary uploads / Uploads temporários
```

## Security Notes / Notas de Segurança

- **Do not commit uploaded files** - Add to .gitignore
  **Não faça commit de arquivos enviados** - Adicione ao .gitignore

- **Validate file types** - Only allow safe file types
  **Valide tipos de arquivo** - Apenas permita tipos seguros

- **Check file sizes** - Limit maximum file size
  **Verifique tamanhos** - Limite tamanho máximo

- **Sanitize filenames** - Remove dangerous characters
  **Sanitize nomes** - Remova caracteres perigosos

- **Store outside webroot** - If possible, for better security
  **Armazene fora da webroot** - Se possível, para melhor segurança

## Configuration / Configuração

File upload settings are configured in `.env`:

```env
MAX_UPLOAD_SIZE=5242880  # 5MB
ALLOWED_IMAGE_TYPES=jpg,jpeg,png,gif,webp
ALLOWED_DOCUMENT_TYPES=pdf,doc,docx
UPLOAD_PATH=uploads/
AVATAR_PATH=uploads/avatars/
DOCUMENTS_PATH=uploads/documents/
```

## Permissions / Permissões

Ensure proper file permissions:
Garanta permissões adequadas:

```bash
# Linux/macOS
chmod 755 uploads/
chmod 755 uploads/avatars/
chmod 755 uploads/documents/
chmod 755 uploads/images/
chmod 755 uploads/temp/

# Or / Ou
find uploads/ -type d -exec chmod 755 {} \;
find uploads/ -type f -exec chmod 644 {} \;
```

## File Naming / Nomenclatura de Arquivos

Use this pattern for uploaded files:
Use este padrão para arquivos enviados:

```
[timestamp]_[user_id]_[random].[extension]
Example: 1699123456_123_abc123.jpg
```

## Cleanup / Limpeza

Regularly clean up temporary files:
Limpe regularmente arquivos temporários:

```bash
# Delete files older than 24 hours / Deletar arquivos com mais de 24h
find uploads/temp/ -type f -mtime +1 -delete
```
