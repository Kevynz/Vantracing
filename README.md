# 🚐 VanTracing

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange.svg)](https://www.mysql.com/)

**VanTracing** is a comprehensive web-based solution for school transportation management, enabling real-time tracking and monitoring of van routes for parents and drivers. The system provides a secure platform for guardians to track their children's transportation and for drivers to manage their routes efficiently.

**VanTracing** é uma solução web abrangente para gerenciamento de transporte escolar, permitindo rastreamento e monitoramento em tempo real de rotas de vans para pais e motoristas. O sistema fornece uma plataforma segura para responsáveis rastrearem o transporte de seus filhos e para motoristas gerenciarem suas rotas com eficiência.

---

## 🌟 Features / Funcionalidades

### For Guardians / Para Responsáveis
- ✅ **Real-time tracking** of school van location / **Rastreamento em tempo real** da localização da van escolar
- 👶 **Child profile management** with multiple children support / **Gerenciamento de perfis de crianças** com suporte para múltiplas crianças
- 📍 **Route history** and notifications / **Histórico de rotas** e notificações
- 🔐 **Secure authentication** and account management / **Autenticação segura** e gerenciamento de conta

### For Drivers / Para Motoristas
- 🚗 **Route management** with start/stop controls / **Gerenciamento de rotas** com controles de início/parada
- 📊 **Dashboard** with trip statistics / **Painel de controle** com estatísticas de viagens
- 👥 **Passenger list** management / **Gerenciamento de lista** de passageiros
- 🗺️ **GPS integration** for real-time location sharing / **Integração GPS** para compartilhamento de localização em tempo real

### General / Geral
- 🌓 **Dark/Light theme** with system preference detection / **Tema escuro/claro** com detecção de preferência do sistema
- 🌍 **Internationalization (i18n)** with automatic language detection (Portuguese/English) / **Internacionalização (i18n)** com detecção automática de idioma (Português/Inglês)
- 📱 **Responsive design** for mobile and desktop / **Design responsivo** para celular e desktop
- 🔒 **Password recovery** system / Sistema de **recuperação de senha**
- ✨ **Modern UI** with Bootstrap 5 / **Interface moderna** com Bootstrap 5

---

## 🛠️ Technologies / Tecnologias

### Frontend
- **HTML5, CSS3, JavaScript (ES6+)**
- **Bootstrap 5.3** - UI framework / Framework de interface
- **Font Awesome 6.4** - Icons / Ícones
- **Google Material Icons** - Additional icons / Ícones adicionais

### Backend
- **PHP 7.4+** - Server-side logic / Lógica do servidor
- **MySQL 8.0+** - Database / Banco de dados
- **PDO** - Database abstraction / Abstração de banco de dados

### APIs & Services / APIs e Serviços
- **Geolocation API** - Location tracking / Rastreamento de localização
- **Browser Detection** - Automatic language selection / Seleção automática de idioma

---

## 📋 Prerequisites / Pré-requisitos

Before you begin, ensure you have the following installed:
Antes de começar, certifique-se de ter o seguinte instalado:

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 7.4 or higher
- **MySQL**: 8.0 or higher
- **Composer** (optional, for future dependency management)

---

## 🚀 Installation / Instalação

### 1. Clone the Repository / Clone o Repositório

```bash
git clone https://github.com/Kevynz/Vantracing.git
cd Vantracing
```

### 2. Database Setup / Configuração do Banco de Dados

1. Create a new MySQL database:
   Crie um novo banco de dados MySQL:

```sql
CREATE DATABASE vantracing_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
   Importe o esquema do banco de dados:

```bash
mysql -u your_username -p vantracing_db < api/vantracing_db.sql
```

### 3. Environment Configuration / Configuração do Ambiente

1. Copy the example environment file:
   Copie o arquivo de exemplo de ambiente:

```bash
cp .env.example .env
```

2. Edit `.env` with your database credentials:
   Edite `.env` com suas credenciais do banco de dados:

```env
DB_HOST=localhost
DB_NAME=vantracing_db
DB_USER=your_username
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
```

### 4. Web Server Configuration / Configuração do Servidor Web

#### Apache

Ensure `mod_rewrite` is enabled and create a virtual host:
Certifique-se de que `mod_rewrite` está habilitado e crie um host virtual:

```apache
<VirtualHost *:80>
    ServerName vantracing.local
    DocumentRoot /path/to/Vantracing
    
    <Directory /path/to/Vantracing>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/vantracing_error.log
    CustomLog ${APACHE_LOG_DIR}/vantracing_access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name vantracing.local;
    root /path/to/Vantracing;
    index index.html index.php;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 5. File Permissions / Permissões de Arquivos

Set appropriate permissions:
Defina as permissões apropriadas:

```bash
# For Linux/macOS
chmod -R 755 .
chmod -R 775 api/
chown -R www-data:www-data .

# Create upload directories if needed
mkdir -p uploads/avatars
chmod -R 775 uploads/
```

---

## 📖 Usage / Uso

### Accessing the Application / Acessando a Aplicação

1. Open your browser and navigate to:
   Abra seu navegador e navegue até:
   ```
   http://localhost/Vantracing
   or / ou
   http://vantracing.local
   ```

2. **First Time Setup / Configuração Inicial:**
   - Click "Motorista" or "Responsável" to register
   - Clique em "Motorista" ou "Responsável" para se registrar
   - Fill in the registration form with valid information
   - Preencha o formulário de registro com informações válidas
   - After registration, log in with your credentials
   - Após o registro, faça login com suas credenciais

### User Roles / Funções do Usuário

#### Guardian (Responsável)
- Add and manage children profiles / Adicionar e gerenciar perfis de crianças
- View real-time van location / Ver localização da van em tempo real
- Access route history / Acessar histórico de rotas
- Manage account settings / Gerenciar configurações da conta

#### Driver (Motorista)
- Start and end routes / Iniciar e finalizar rotas
- Share real-time location / Compartilhar localização em tempo real
- View passenger list / Ver lista de passageiros
- Manage profile and vehicle information / Gerenciar perfil e informações do veículo

---

## 🔒 Security / Segurança

### Best Practices Implemented / Melhores Práticas Implementadas

- ✅ **Password Hashing**: Using `password_hash()` with bcrypt / Usando `password_hash()` com bcrypt
- ✅ **SQL Injection Prevention**: Prepared statements with PDO / Prevenção de injeção SQL com PDO
- ✅ **XSS Protection**: Input sanitization and output escaping / Proteção XSS e sanitização de entrada
- ✅ **CSRF Protection**: Token validation (to be implemented) / Validação de token (a ser implementado)
- ✅ **Secure Sessions**: HTTPOnly and Secure cookie flags / Flags de cookie HTTPOnly e Secure
- ✅ **Environment Variables**: Sensitive data not in source code / Dados sensíveis não no código-fonte

### Recommendations / Recomendações

1. **HTTPS**: Always use SSL/TLS in production / Sempre use SSL/TLS em produção
2. **Regular Updates**: Keep dependencies up to date / Mantenha as dependências atualizadas
3. **Backup**: Regular database backups / Backups regulares do banco de dados
4. **Rate Limiting**: Implement API rate limiting / Implemente limitação de taxa de API
5. **Monitoring**: Set up error logging and monitoring / Configure registro e monitoramento de erros

---

## 🌍 Internationalization / Internacionalização

VanTracing automatically detects the user's preferred language based on:
VanTracing detecta automaticamente o idioma preferido do usuário com base em:

1. **Saved Preference** / Preferência salva (localStorage)
2. **Browser Language** / Idioma do navegador (navigator.language)
3. **Default**: Portuguese / Padrão: Português

### Supported Languages / Idiomas Suportados

- 🇧🇷 **Portuguese (pt)** - Português
- 🇺🇸 **English (en)** - Inglês

Users can manually switch languages using the language switcher button in the interface.
Os usuários podem alternar manualmente os idiomas usando o botão de troca de idioma na interface.

---

## 📁 Project Structure / Estrutura do Projeto

```
Vantracing/
├── api/                      # Backend PHP scripts / Scripts PHP do backend
│   ├── db_connect.php       # Database connection / Conexão com banco de dados
│   ├── login.php            # Login endpoint
│   ├── register.php         # Registration endpoint / Endpoint de registro
│   ├── get_children.php     # Get children list / Listar crianças
│   ├── register_child.php   # Add child / Adicionar criança
│   ├── delete_child.php     # Delete child / Excluir criança
│   ├── get_perfil.php       # Get profile / Obter perfil
│   ├── update_perfil.php    # Update profile / Atualizar perfil
│   ├── update_account.php   # Update account / Atualizar conta
│   ├── delete_account.php   # Delete account / Excluir conta
│   ├── request_reset.php    # Password reset request / Solicitação de redefinição de senha
│   ├── do_reset.php         # Execute password reset / Executar redefinição de senha
│   └── vantracing_db.sql    # Database schema / Esquema do banco de dados
├── css/                      # Stylesheets
│   └── i18n.css             # Internationalization styles / Estilos de internacionalização
├── JavaScript/               # Frontend scripts
│   ├── geral.js             # General utilities / Utilitários gerais
│   ├── i18n.js              # Internationalization / Internacionalização
│   ├── perfil-motorista.js  # Driver profile logic / Lógica do perfil do motorista
│   └── perfil-responsavel.js # Guardian profile logic / Lógica do perfil do responsável
├── img/                      # Images and assets / Imagens e recursos
├── cadastro.html            # Registration page / Página de cadastro
├── dashboard.html           # Main dashboard / Painel principal
├── historico-rotas.html     # Route history / Histórico de rotas
├── index.html               # Login page / Página de login
├── motorista.html           # Driver registration / Cadastro de motorista
├── nova-senha.html          # New password page / Página de nova senha
├── perfil.html              # Profile page / Página de perfil
├── perfilmotorista.html     # Driver profile / Perfil do motorista
├── perfilreponsável.html    # Guardian profile / Perfil do responsável
├── reset-senha.html         # Password reset / Redefinição de senha
├── responsavel.html         # Guardian registration / Cadastro de responsável
├── rota-tempo-real.html     # Real-time tracking / Rastreamento em tempo real
├── estilo.css               # Main stylesheet / Folha de estilo principal
├── .env.example             # Environment variables template / Modelo de variáveis de ambiente
├── .gitignore               # Git ignore file / Arquivo de ignore do Git
└── README.md                # This file / Este arquivo
```

---

## 🤝 Contributing / Contribuindo

Contributions are welcome! Please follow these steps:
Contribuições são bem-vindas! Por favor, siga estes passos:

1. Fork the repository / Faça um fork do repositório
2. Create a feature branch / Crie uma branch de funcionalidade
   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. Commit your changes / Faça commit das suas alterações
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```
4. Push to the branch / Faça push para a branch
   ```bash
   git push origin feature/AmazingFeature
   ```
5. Open a Pull Request / Abra um Pull Request

### Code Style / Estilo de Código

- Use meaningful variable names / Use nomes de variáveis significativos
- Comment complex logic in both English and Portuguese / Comente lógica complexa em inglês e português
- Follow PSR-12 for PHP code / Siga PSR-12 para código PHP
- Use ES6+ features for JavaScript / Use recursos ES6+ para JavaScript

---

## 📝 License / Licença

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

---

## 👥 Authors / Autores

- **Kevyn** - *Initial work* / *Trabalho inicial* - [Kevynz](https://github.com/Kevynz)

---

## 🙏 Acknowledgments / Agradecimentos

- Bootstrap team for the amazing UI framework / Equipe Bootstrap pelo incrível framework de UI
- Font Awesome for the icon library / Font Awesome pela biblioteca de ícones
- All contributors who help improve this project / Todos os contribuidores que ajudam a melhorar este projeto

---

## 📞 Support / Suporte

If you encounter any issues or have questions:
Se você encontrar algum problema ou tiver dúvidas:

- 🐛 **Report bugs**: [GitHub Issues](https://github.com/Kevynz/Vantracing/issues)
- 💬 **Discussions**: [GitHub Discussions](https://github.com/Kevynz/Vantracing/discussions)
- 📧 **Email**: your-email@example.com

---

## 🗺️ Roadmap / Roteiro

### Version 2.0 (Planned / Planejado)
- [ ] Mobile app (React Native)
- [ ] Push notifications / Notificações push
- [ ] Advanced analytics dashboard / Painel de análises avançado
- [ ] Multi-language support (Spanish, French) / Suporte multi-idioma (Espanhol, Francês)
- [ ] API documentation with Swagger / Documentação da API com Swagger
- [ ] Automated tests (PHPUnit, Jest) / Testes automatizados

### Version 1.5 (In Progress / Em Progresso)
- [x] Internationalization (i18n) / Internacionalização
- [x] Dark mode / Modo escuro
- [ ] Enhanced security features / Recursos de segurança aprimorados
- [ ] Email notifications / Notificações por email

---

**Made with ❤️ for safer school transportation**
**Feito com ❤️ por um transporte escolar mais seguro**
