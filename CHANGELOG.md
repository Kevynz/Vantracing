# Changelog / Registro de Alterações

All notable changes to this project will be documented in this file.
Todas as mudanças notáveis deste projeto serão documentadas neste arquivo.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.0.0] - 2025-11-01

### Added / Adicionado
- 🌍 **Internationalization system** with automatic language detection
  - Sistema de internacionalização com detecção automática de idioma
- 📝 **Complete documentation** (README, CONTRIBUTING, INSTALL, LICENSE)
  - Documentação completa
- 🔒 **Environment variable support** for database configuration
  - Suporte a variáveis de ambiente para configuração do banco de dados
- 🎨 **Language switcher button** with flag emojis
  - Botão de troca de idioma com emojis de bandeiras
- 📁 **Professional .gitignore** file
  - Arquivo .gitignore profissional
- 🌐 **Bilingual support** (Portuguese and English)
  - Suporte bilíngue (Português e Inglês)
- 📖 **.env.example** template for third-party deployments
  - Template .env.example para implantações de terceiros
- 💬 **Bilingual code comments** throughout the project
  - Comentários de código bilíngues em todo o projeto
 - 🗺️ **Session-based real-time tracking** endpoints (`api/update_location.php`, `api/get_location.php`)
   - Rastreamento em tempo real baseado em sessão
 - 🔄 **Location sharing toggle** on driver UI with status badge
   - Botão de alternância para compartilhamento de localização com status
 - 🧭 **Leaflet map integration** with polling and local fallback
   - Integração com Leaflet com polling e fallback local
 - 🧱 **SQL migrations** (`database/migrations/001_init.sql`, `002_profile_split.sql`)
   - Migrações SQL

### Changed / Alterado
- 🔧 **Refactored database connection** to use environment variables
  - Refatoração da conexão com banco de dados para usar variáveis de ambiente
- 📄 **Enhanced index.html** with i18n support
  - index.html aprimorado com suporte i18n
- 💻 **Improved JavaScript documentation** with JSDoc comments
  - Documentação JavaScript melhorada com comentários JSDoc
- 🎯 **Standardized file naming** (estilo.css consistency)
  - Padronização de nomes de arquivos
 - 🔐 **Secured APIs to session-based auth** with role checks
   - APIs protegidas por sessões com verificação de papéis

### Security / Segurança
- 🛡️ **Removed hardcoded credentials** from source code
  - Remoção de credenciais hardcoded do código-fonte
- 🔐 **Added .env file support** for sensitive configuration
  - Adicionado suporte a arquivo .env para configuração sensível
- 📋 **Implemented proper .gitignore** to prevent credential leaks
  - Implementado .gitignore adequado para prevenir vazamento de credenciais
- 🔒 **Enhanced error logging** without exposing sensitive data
  - Registro de erros aprimorado sem expor dados sensíveis
 - 🧪 **CSRF protection implemented** (`api/csrf.php`)
   - Proteção CSRF implementada
 - ⏱️ **API rate limiting** for `update_location` (1 req/s per session)
   - Limitação de taxa para `update_location` (1 req/s por sessão)
 - 🧾 **API logging** with correlation IDs to `logs/api.log`
   - Logs de API com correlation IDs em `logs/api.log`

### Documentation / Documentação
- 📚 **Added comprehensive README.md** with:
  - Adicionado README.md abrangente com:
  - Project description / Descrição do projeto
  - Installation instructions / Instruções de instalação
  - Usage guide / Guia de uso
  - Security best practices / Melhores práticas de segurança
  - Contributing guidelines / Diretrizes de contribuição
- 📖 **Created CONTRIBUTING.md** for contributors
  - Criado CONTRIBUTING.md para contribuidores
- 🚀 **Added INSTALL.md** with quick setup guide
  - Adicionado INSTALL.md com guia rápido de configuração
- 📋 **Added IMPROVEMENTS.md** documenting all changes
  - Adicionado IMPROVEMENTS.md documentando todas as mudanças

---

## [1.0.0] - 2025-10-XX

### Added / Adicionado
- ✅ User authentication system (Login/Register)
  - Sistema de autenticação de usuários
- 👥 User role management (Driver/Guardian)
  - Gerenciamento de papéis de usuário (Motorista/Responsável)
- 🚐 Real-time vehicle tracking
  - Rastreamento de veículo em tempo real
- 📍 Route history
  - Histórico de rotas
- 👶 Child profile management
  - Gerenciamento de perfis de crianças
- 🌓 Dark/Light theme toggle
  - Alternância de tema escuro/claro
- 🔑 Password reset functionality
  - Funcionalidade de redefinição de senha
- 📱 Responsive design with Bootstrap 5
  - Design responsivo com Bootstrap 5
- ✅ Form validation (CPF, CNH, Birth date)
  - Validação de formulários
- 🎨 Modern UI with Font Awesome icons
  - Interface moderna com ícones Font Awesome

### Technical / Técnico
- 🗄️ MySQL database integration
  - Integração com banco de dados MySQL
- 🐘 PHP backend API
  - API backend em PHP
- ⚡ JavaScript ES6+ frontend
  - Frontend em JavaScript ES6+
- 📦 Bootstrap 5 framework
  - Framework Bootstrap 5
- 🎨 Custom CSS styling
  - Estilização CSS personalizada

---

## Project Milestones / Marcos do Projeto

### Version 2.1 (Planned / Planejado)
- [ ] Update all HTML pages with i18n attributes
- [ ] Add email notification system
- [ ] Implement CSRF token protection (Completed in 2.0.0)
- [ ] Add API rate limiting (Completed in 2.0.0)
- [ ] Create automated tests

### Version 2.5 (Planned / Planejado)
- [ ] Mobile app (React Native)
- [ ] Push notifications
- [ ] Advanced analytics dashboard
- [ ] Multi-language support (Spanish, French)

### Version 3.0 (Future / Futuro)
- [ ] Microservices architecture
- [ ] WebSocket for real-time updates
- [ ] AI-powered route optimization
- [ ] Integration with payment systems

---

## Legend / Legenda

- 🌍 Internationalization / Internacionalização
- 🔒 Security / Segurança
- 📝 Documentation / Documentação
- 🐛 Bug Fix / Correção de Bug
- ✨ New Feature / Nova Funcionalidade
- ⚡ Performance / Desempenho
- 🎨 UI/UX / Interface
- 🔧 Configuration / Configuração
- 📦 Dependencies / Dependências
- 🚀 Deployment / Implantação

---

**For detailed changes, see IMPROVEMENTS.md**
**Para mudanças detalhadas, veja IMPROVEMENTS.md**
