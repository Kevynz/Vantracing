document.addEventListener("DOMContentLoaded", () => {
    // Seleção de elementos da interface
    const loginForm = document.querySelector("#loginForm");
    const cadastroForm = document.querySelector("#cadastroForm");
    const perfilForm = document.querySelector("#perfilForm");
    const fotoForm = document.querySelector("#fotoForm");
    const segurancaForm = document.querySelector("#segurancaForm");
    const chatForm = document.querySelector("#chatForm");
    const screens = document.querySelectorAll(".screen");
    const screenLinks = document.querySelectorAll("a[data-screen]");
    const sectionLinks = document.querySelectorAll("a[data-section]");
    const sections = document.querySelectorAll(".profile-section");
    const userNome = document.querySelector("#user-nome");
    const roleButtons = document.querySelectorAll(".role-btn");
    const topNav = document.querySelector("#top-nav");
    const logoutBtn = document.querySelector("#logout-btn") || document.createElement("button");
    
    // Se o botão de logout não existir no HTML, vamos criá-lo
    if (!document.querySelector("#logout-btn")) {
        logoutBtn.id = "logout-btn";
        logoutBtn.textContent = "Sair";
        logoutBtn.classList.add("logout-button");
        if (topNav) {
            topNav.appendChild(logoutBtn);
        }
    }

    // Variáveis de estado da aplicação
    let users = JSON.parse(localStorage.getItem("users")) || [];
    let currentRole = "responsavel";
    let currentUser = null;
    
    // Verifica se há uma sessão ativa
    function checkSession() {
        const sessionId = localStorage.getItem("sessionId");
        const sessionData = sessionId ? JSON.parse(localStorage.getItem("session_" + sessionId)) : null;
        
        if (sessionData && sessionData.expiresAt > new Date().getTime()) {
            // Sessão válida
            const user = users.find(u => u.email === sessionData.email);
            if (user) {
                loginUser(user, false);
                return true;
            }
        }
        
        // Se chegou aqui, não há sessão válida
        localStorage.removeItem("sessionId");
        return false;
    }
    
    // Verifica a sessão ao carregar a página
    if (checkSession()) {
        showScreen("dashboard"); // Vai para o dashboard se a sessão for válida
    } else {
        showScreen("login");     // Vai para o login se não houver sessão
    }
    
    // Função para criar uma nova sessão
    function createSession(user, rememberMe = false) {
        const sessionId = Date.now().toString(36) + Math.random().toString(36).substr(2);
        const expirationTime = rememberMe ? 30 * 24 * 60 * 60 * 1000 : 2 * 60 * 60 * 1000; // 30 dias ou 2 horas
        
        const sessionData = {
            email: user.email,
            role: user.role,
            createdAt: Date.now(),
            expiresAt: Date.now() + expirationTime
        };
        
        localStorage.setItem("sessionId", sessionId);
        localStorage.setItem("session_" + sessionId, JSON.stringify(sessionData));
        
        return sessionId;
    }
    
    // Função para encerrar a sessão (logout)
    function endSession() {
        const sessionId = localStorage.getItem("sessionId");
        if (sessionId) {
            localStorage.removeItem("session_" + sessionId);
            localStorage.removeItem("sessionId");
        }
        currentUser = null;
        showScreen("login");
    }
    
    // Evento de logout
    logoutBtn.addEventListener("click", () => {
        endSession();
        showNotification("Você saiu da sua conta com sucesso!");
    });

    // Sistema de notificações
    function showNotification(message, type = "info") {
        // Verifica se o contêiner de notificações existe
        let notifContainer = document.querySelector(".notification-container");
        if (!notifContainer) {
            notifContainer = document.createElement("div");
            notifContainer.className = "notification-container";
            document.body.appendChild(notifContainer);
        }
        
        // Cria a notificação
        const notification = document.createElement("div");
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        // Adiciona ao contêiner
        notifContainer.appendChild(notification);
        
        // Remove após 5 segundos
        setTimeout(() => {
            notification.classList.add("fade-out");
            setTimeout(() => {
                if (notifContainer.contains(notification)) {
                    notifContainer.removeChild(notification);
                }
            }, 500);
        }, 5000);
    }
    
    // Valida formato de email
    function isValidEmail(email) {
        return /^[\w-]+(\.[\w-]+)*@([\w-]+\.)+[a-zA-Z]{2,7}$/.test(email);
    }
    
    // Valida força da senha
    function isStrongPassword(password) {
        // Pelo menos 8 caracteres, uma letra maiúscula, uma minúscula, um número e um caractere especial
        return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password);
    }

    // Função para alternar telas
    function showScreen(screenId) {
        screens.forEach(screen => {
            screen.classList.toggle("active", screen.id === screenId);
        });
        
        screenLinks.forEach(link => {
            link.classList.toggle("active", link.getAttribute("data-screen") === screenId);
        });

        // Mostrar a barra de navegação apenas nas telas pós-login
        const isLoggedInScreen = ["dashboard", "perfil", "rota-tempo-real", "historico-rotas", "chat"].includes(screenId);
        topNav.classList.toggle("visible", isLoggedInScreen);
        
        // Se estiver indo para o dashboard, atualize os dados
        if (screenId === "dashboard" && currentUser) {
            updateDashboard();
        }
        
        // Se estiver indo para o chat, carregue as mensagens
        if (screenId === "chat" && currentUser) {
            loadChatHistory();
            loadContacts();
        }
    }

    screenLinks.forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            const targetScreen = link.getAttribute("data-screen");
            
            // Verificar se o usuário está logado para acessar telas protegidas
            const protectedScreens = ["dashboard", "perfil", "rota-tempo-real", "historico-rotas", "chat"];
            if (protectedScreens.includes(targetScreen) && !currentUser) {
                showNotification("Faça login para acessar esta funcionalidade", "error");
                showScreen("login");
                return;
            }
            
            showScreen(targetScreen);
        });
    });

    // Função para alternar seções no perfil
    function showSection(sectionId) {
        sections.forEach(section => {
            section.classList.toggle("active", section.id === sectionId);
        });
        
        sectionLinks.forEach(link => {
            link.classList.toggle("active", link.getAttribute("data-section") === sectionId);
        });
    }

    sectionLinks.forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            showSection(link.getAttribute("data-section"));
        });
    });

    // Seleção de papel no cadastro
    roleButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            roleButtons.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            currentRole = btn.getAttribute("data-role");
        });
    });

    // Função para atualizar o dashboard
    function updateDashboard() {
        const dashboardContainer = document.querySelector("#dashboard-container");
        if (!dashboardContainer || !currentUser) return;
        
        // Limpa o conteúdo existente
        dashboardContainer.innerHTML = "";
        
        // Cria cabeçalho
        const header = document.createElement("div");
        header.className = "dashboard-header";
        header.innerHTML = `
            <h2>Bem-vindo, ${currentUser.nome || "Usuário"}</h2>
            <p class="dashboard-date">${new Date().toLocaleDateString('pt-BR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
        `;
        dashboardContainer.appendChild(header);
        
        // Cria cards com informações relevantes
        const cardsContainer = document.createElement("div");
        cardsContainer.className = "dashboard-cards";
        
        // O conteúdo varia de acordo com o papel do usuário
        if (currentUser.role === "motorista") {
            cardsContainer.innerHTML = `
                <div class="dashboard-card">
                    <h3>Rotas Hoje</h3>
                    <p class="dashboard-number">3</p>
                    <p>2 completadas, 1 pendente</p>
                </div>
                <div class="dashboard-card">
                    <h3>Passageiros</h3>
                    <p class="dashboard-number">12</p>
                    <p>Ver lista completa</p>
                </div>
                <div class="dashboard-card">
                    <h3>Mensagens</h3>
                    <p class="dashboard-number">5</p>
                    <p>2 não lidas</p>
                </div>
            `;
        } else {
            cardsContainer.innerHTML = `
                <div class="dashboard-card">
                    <h3>Status da Rota</h3>
                    <p class="dashboard-status">Em andamento</p>
                    <p>Ônibus a 10 min do destino</p>
                </div>
                <div class="dashboard-card">
                    <h3>Motorista</h3>
                    <p>Carlos Silva</p>
                    <p>Tel: (11) 99999-1234</p>
                </div>
                <div class="dashboard-card">
                    <h3>Mensagens</h3>
                    <p class="dashboard-number">3</p>
                    <p>1 não lida</p>
                </div>
            `;
        }
        
        dashboardContainer.appendChild(cardsContainer);
        
        // Adiciona ações rápidas
        const quickActions = document.createElement("div");
        quickActions.className = "quick-actions";
        quickActions.innerHTML = `
            <h3>Ações Rápidas</h3>
            <div class="action-buttons">
                <button class="action-btn" data-screen="rota-tempo-real">Ver Rota Atual</button>
                <button class="action-btn" data-screen="chat">Abrir Chat</button>
                <button class="action-btn" data-screen="perfil">Editar Perfil</button>
            </div>
        `;
        dashboardContainer.appendChild(quickActions);
        
        // Adiciona eventos aos botões de ação rápida
        quickActions.querySelectorAll(".action-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                showScreen(btn.getAttribute("data-screen"));
            });
        });
    }

    // Função para fazer login
    function loginUser(user, createNewSession = true) {
        currentUser = user;
        userNome.textContent = user.nome || "Usuário";
        
        // Verifica se o elemento existe antes de tentar definir seu valor
        const segurancaEmail = document.querySelector("#seguranca-email");
        if (segurancaEmail) {
            segurancaEmail.value = user.email;
        }
        
        // Cria uma nova sessão se solicitado
        if (createNewSession) {
            const rememberMe = document.querySelector("#remember-me")?.checked || false;
            createSession(user, rememberMe);
        }
        
        showScreen("dashboard");
    }

    // Login
    if (loginForm) {
        // Adiciona campo "Lembrar-me"
        if (!document.querySelector("#remember-me-container")) {
            const rememberContainer = document.createElement("div");
            rememberContainer.id = "remember-me-container";
            rememberContainer.className = "form-group";
            rememberContainer.innerHTML = `
                <label>
                    <input type="checkbox" id="remember-me">
                    Lembrar-me neste dispositivo
                </label>
            `;
            
            const submitBtn = loginForm.querySelector("button[type='submit']");
            loginForm.insertBefore(rememberContainer, submitBtn);
        }
        
        // Processa o formulário de login
        loginForm.addEventListener("submit", (e) => {
            e.preventDefault();
            
            const email = document.querySelector("#login-email").value;
            const senha = document.querySelector("#login-senha").value;
            
            // Validação de formato de email
            if (!isValidEmail(email)) {
                showNotification("Digite um email válido", "error");
                return;
            }
            
            // Busca o usuário
            const user = users.find(u => u.email === email && u.senha === senha);

            if (user) {
                showNotification("Login realizado com sucesso!", "success");
                loginUser(user);
            } else {
                showNotification("Email ou senha incorretos", "error");
            }
        });
    }

    // Cadastro
    if (cadastroForm) {
        // Adiciona campos de nome completo e telefone
        if (!document.querySelector("#cadastro-nome")) {
            const nomeField = document.createElement("div");
            nomeField.className = "form-group";
            nomeField.innerHTML = `
                <label for="cadastro-nome">Nome Completo</label>
                <input type="text" id="cadastro-nome" required>
            `;
            
            const emailField = cadastroForm.querySelector("div:first-child");
            cadastroForm.insertBefore(nomeField, emailField);
            
            const telefoneField = document.createElement("div");
            telefoneField.className = "form-group";
            telefoneField.innerHTML = `
                <label for="cadastro-telefone">Telefone</label>
                <input type="tel" id="cadastro-telefone" placeholder="(00) 00000-0000">
            `;
            
            const senhaField = cadastroForm.querySelector("div:nth-child(3)");
            cadastroForm.insertBefore(telefoneField, senhaField);
        }
        
        // Adiciona mensagem de força da senha
        const senhaInput = document.querySelector("#cadastro-senha");
        if (senhaInput && !document.querySelector(".password-strength")) {
            const senhaMeter = document.createElement("div");
            senhaMeter.className = "password-strength";
            senhaMeter.innerHTML = '<span>Força da senha: <b class="strength-text">Fraca</b></span><div class="strength-meter"><div class="meter-fill weak"></div></div>';
            
            senhaInput.parentNode.appendChild(senhaMeter);
            
            // Adiciona evento para avaliar força da senha
            senhaInput.addEventListener("input", function() {
                const senha = this.value;
                const strengthText = senhaMeter.querySelector(".strength-text");
                const meterFill = senhaMeter.querySelector(".meter-fill");
                
                let strength = 0;
                let strengthClass = "weak";
                let strengthLabel = "Fraca";
                
                if (senha.length >= 8) strength += 1;
                if (/[A-Z]/.test(senha)) strength += 1;
                if (/[a-z]/.test(senha)) strength += 1;
                if (/[0-9]/.test(senha)) strength += 1;
                if (/[^A-Za-z0-9]/.test(senha)) strength += 1;
                
                if (strength === 3) {
                    strengthClass = "medium";
                    strengthLabel = "Média";
                } else if (strength >= 4) {
                    strengthClass = "strong";
                    strengthLabel = "Forte";
                }
                
                // Atualiza a visualização
                meterFill.className = `meter-fill ${strengthClass}`;
                meterFill.style.width = `${strength * 20}%`;
                strengthText.textContent = strengthLabel;
            });
        }
        
        // Processa o formulário de cadastro
        cadastroForm.addEventListener("submit", (e) => {
            e.preventDefault();
            
            // Verifica se estamos na tela de cadastro ou recuperação de senha
            const isPasswordReset = e.target.closest("#Senha");
            
            if (isPasswordReset) {
                const email = document.querySelector("#cadastro-email").value;
                
                if (!isValidEmail(email)) {
                    showNotification("Digite um email válido", "error");
                    return;
                }
                
                // Simula o envio de email para recuperação
                showNotification("Email de recuperação enviado. Verifique sua caixa de entrada.", "success");
                showScreen("login");
                return;
            }
            
            // Processa o cadastro normal
            const nome = document.querySelector("#cadastro-nome").value;
            const email = document.querySelector("#cadastro-email").value;
            const telefone = document.querySelector("#cadastro-telefone").value;
            const senha = document.querySelector("#cadastro-senha").value;
            const confirmaSenha = document.querySelector("#cadastro-confirma-senha").value;

            // Validações melhoradas
            if (!nome || nome.length < 3) {
                showNotification("Nome deve ter pelo menos 3 caracteres", "error");
                return;
            }
            
            if (!isValidEmail(email)) {
                showNotification("Digite um email válido", "error");
                return;
            }
            
            if (senha.length < 8) {
                showNotification("Senha deve ter pelo menos 8 caracteres", "error");
                return;
            }
            
            if (senha !== confirmaSenha) {
                showNotification("As senhas não coincidem", "error");
                return;
            }

            if (users.some(u => u.email === email)) {
                showNotification("Email já cadastrado", "error");
                return;
            }

            // Adiciona o novo usuário
            const newUser = {
                nome,
                email,
                telefone,
                senha,
                role: currentRole,
                dataCadastro: new Date().toISOString()
            };
            
            users.push(newUser);
            localStorage.setItem("users", JSON.stringify(users));
            
            showNotification("Cadastro realizado com sucesso!", "success");
            showScreen("login");
        });
    }

    // Perfil
    if (perfilForm) {
        perfilForm.addEventListener("submit", (e) => {
            e.preventDefault();
            
            if (!currentUser) {
                showNotification("Sessão expirada, faça login novamente", "error");
                endSession();
                return;
            }
            
            const nome = document.querySelector("#nome").value;
            const profissao = document.querySelector("#profissao").value;
            const bio = document.querySelector("#bio").value;
            const idioma = document.querySelector("#idioma").value;
            const site = document.querySelector("#site").value;

            // Atualiza as informações do usuário atual
            const userIndex = users.findIndex(u => u.email === currentUser.email);
            if (userIndex !== -1) {
                users[userIndex] = {
                    ...users[userIndex],
                    nome,
                    profissao,
                    bio,
                    idioma,
                    site,
                    atualizado: new Date().toISOString()
                };
                
                localStorage.setItem("users", JSON.stringify(users));
                currentUser = users[userIndex];
            }

            userNome.textContent = nome || "Usuário";
            showNotification("Perfil atualizado com sucesso!", "success");
        });
    }

    // Foto
    if (fotoForm) {
        const previewImg = document.querySelector("#photo-preview-img");
        
        // Adiciona botão para remover foto
        if (!document.querySelector("#remove-photo")) {
            const removeBtn = document.createElement("button");
            removeBtn.id = "remove-photo";
            removeBtn.type = "button";
            removeBtn.className = "remove-photo-btn";
            removeBtn.textContent = "Remover foto";
            
            const submitBtn = fotoForm.querySelector("button[type='submit']");
            fotoForm.insertBefore(removeBtn, submitBtn);
            
            removeBtn.addEventListener("click", () => {
                previewImg.src = "/assets/default-avatar.png";
                document.querySelector("#foto-upload").value = "";
            });
        }
        
        const fotoUpload = document.querySelector("#foto-upload");
        if (fotoUpload) {
            fotoUpload.addEventListener("change", (e) => {
                const file = e.target.files[0];
                if (file) {
                    // Verificações de tamanho e tipo
                    if (file.size > 2 * 1024 * 1024) {
                        showNotification("A imagem deve ter no máximo 2MB", "error");
                        return;
                    }
                    
                    if (!file.type.match('image.*')) {
                        showNotification("O arquivo deve ser uma imagem", "error");
                        return;
                    }
                    
                    // Preview da imagem
                    const reader = new FileReader();
                    reader.onload = () => previewImg.src = reader.result;
                    reader.readAsDataURL(file);
                }
            });
        }

        fotoForm.addEventListener("submit", (e) => {
            e.preventDefault();
            
            if (!currentUser) {
                showNotification("Sessão expirada, faça login novamente", "error");
                endSession();
                return;
            }
            
            // Salva a foto no perfil do usuário
            const userIndex = users.findIndex(u => u.email === currentUser.email);
            if (userIndex !== -1 && previewImg && previewImg.src) {
                users[userIndex].foto = previewImg.src;
                localStorage.setItem("users", JSON.stringify(users));
                currentUser = users[userIndex];
            }
            
            showNotification("Foto atualizada com sucesso!", "success");
        });
    }

    // Segurança
    if (segurancaForm) {
        segurancaForm.addEventListener("submit", (e) => {
            e.preventDefault();
            
            if (!currentUser) {
                showNotification("Sessão expirada, faça login novamente", "error");
                endSession();
                return;
            }
            
            const senhaAtual = document.querySelector("#seguranca-senha-atual").value;
            const senhaNova = document.querySelector("#seguranca-senha-nova").value;
            const confirmaSenha = document.querySelector("#seguranca-confirma-senha").value;
            const email = document.querySelector("#seguranca-email").value;

            // Validações melhoradas
            const userIndex = users.findIndex(u => u.email === email && u.senha === senhaAtual);
            if (userIndex === -1) {
                showNotification("Senha atual incorreta", "error");
                return;
            }

            if (senhaNova.length < 8) {
                showNotification("A nova senha deve ter pelo menos 8 caracteres", "error");
                return;
            }
            
            if (senhaNova !== confirmaSenha) {
                showNotification("As novas senhas não coincidem", "error");
                return;
            }

            // Atualiza a senha
            users[userIndex].senha = senhaNova;
            users[userIndex].senhaAtualizada = new Date().toISOString();
            localStorage.setItem("users", JSON.stringify(users));
            currentUser = users[userIndex];
            
            showNotification("Senha atualizada com sucesso!", "success");
            document.querySelector("#seguranca-senha-atual").value = "";
            document.querySelector("#seguranca-senha-nova").value = "";
            document.querySelector("#seguranca-confirma-senha").value = "";
        });
    }

    // Chat aprimorado
    if (chatForm) {
        const chatMessages = document.querySelector("#chat-messages");
        if (!chatMessages) return;
        
        const contactsList = document.createElement("div");
        contactsList.className = "chat-contacts";
        contactsList.innerHTML = "<h3>Contatos</h3><ul id='contacts-list'></ul>";
        
        // Adiciona a lista de contatos se ela não existir
        if (!document.querySelector(".chat-contacts")) {
            const chatContainer = document.querySelector(".chat-container") || chatForm.parentElement;
            chatContainer.insertBefore(contactsList, chatContainer.firstChild);
        }
        
        // Carrega contatos
        function loadContacts() {
            const contactsList = document.querySelector("#contacts-list");
            if (!contactsList || !currentUser) return;
            
            contactsList.innerHTML = "";
            
            // Obtém todos os usuários exceto o atual
            const contacts = users.filter(u => u.email !== currentUser.email);
            
            // Adiciona cada contato à lista
            contacts.forEach(contact => {
                const contactItem = document.createElement("li");
                contactItem.className = "contact-item";
                contactItem.setAttribute("data-email", contact.email);
                
                contactItem.innerHTML = `
                    <div class="contact-avatar">
                        <img src="${contact.foto || '/assets/default-avatar.png'}" alt="${contact.nome || 'Usuário'}">
                    </div>
                    <div class="contact-info">
                        <h4>${contact.nome || 'Usuário'}</h4>
                        <p>${contact.role === 'motorista' ? 'Motorista' : 'Responsável'}</p>
                    </div>
                    <div class="contact-status ${Math.random() > 0.5 ? 'online' : 'offline'}"></div>
                `;
                
                contactItem.addEventListener("click", () => {
                    // Marca este contato como ativo
                    document.querySelectorAll(".contact-item").forEach(item => {
                        item.classList.remove("active");
                    });
                    contactItem.classList.add("active");
                    
                    // Carrega o chat com este contato
                    const currentChat = document.querySelector("#current-chat-name");
                    if (currentChat) {
                        currentChat.textContent = contact.nome || "Usuário";
                    }
                    
                    loadChatHistory(contact.email);
                });
                
                contactsList.appendChild(contactItem);
            });
        }
        
        // Função para carregar o histórico de mensagens
        function loadChatHistory(contactEmail) {
            if (!currentUser) return;
            
            // Determina qual chat carregar
            const chatKey = contactEmail 
                ? `chat_${currentUser.email}_${contactEmail}` 
                : `chat_${currentUser.email}`;
            
            const userChat = JSON.parse(localStorage.getItem(chatKey)) || [];
            
            // Limpa as mensagens existentes
            chatMessages.innerHTML = "";
            
            // Adiciona as mensagens ao chat
            userChat.forEach(msg => {
                const msgDiv = document.createElement("div");
                msgDiv.className = `message ${msg.type}`;
                
                // Formato mais rico para as mensagens
                let timeStr = "";
                if (msg.timestamp) {
                    const msgDate = new Date(msg.timestamp);
                    timeStr = msgDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                }
                
                msgDiv.innerHTML = `
                    <div class="message-content">${msg.text}</div>
                    <div class="message-time">${timeStr}</div>
                `;
                
                chatMessages.appendChild(msgDiv);
            });
            
            // Rola para a última mensagem
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Adiciona cabeçalho ao chat se não existir
        if (!document.querySelector("#current-chat-name")) {
            const chatHeader = document.createElement("div");
            chatHeader.className = "chat-header";
            chatHeader.innerHTML = `<h3 id="current-chat-name">Chat Geral</h3>`;
            
            const chatContainer = document.querySelector(".chat-messages-container") || chatMessages.parentElement;
            chatContainer.insertBefore(chatHeader, chatContainer.firstChild);
        }
        
        // Carrega as mensagens quando a tela de chat é aberta
        const chatLink = document.querySelector('a[data-screen="chat"]');
        if (chatLink) {
            chatLink.addEventListener("click", () => {
                if (currentUser) {
                    loadChatHistory();
                    loadContacts();
                }
            });
        }
        
        // Processa o envio de novas mensagens
        chatForm.addEventListener("submit", (e) => {
            e.preventDefault();
            
            if (!currentUser) {
                showNotification("Sessão expirada, faça login novamente", "error");
                endSession();
                return;
            }
            
            const message = document.querySelector("#chat-input").value;
            if (!message.trim()) return;
            
            // Determina o destinatário
            const activeContact = document.querySelector(".contact-item.active");
            const contactEmail = activeContact ? activeContact.getAttribute("data-email") : null;
            
            // Determina a chave do chat
            const chatKey = contactEmail 
                ? `chat_${currentUser.email}_${contactEmail}` 
                : `chat_${currentUser.email}`;
            
            // Recupera o histórico atual
            const userChat = JSON.parse(localStorage.getItem(chatKey)) || [];
            
            // Adiciona a nova mensagem
            const newMessage = {
                text: message,
                type: "sent",
                timestamp: new Date().toISOString()
            };
            
            userChat.push(newMessage);
            localStorage.setItem(chatKey, JSON.stringify(userChat));
            
            // Atualiza a visualização
            const msgDiv = document.createElement("div");
            msgDiv.className = "message sent";
            msgDiv.innerHTML = `
                <div class="message-content">${message}</div>
                <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
            `;
            
            chatMessages.appendChild(msgDiv);
            document.querySelector("#chat-input").value = "";
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Simula resposta automática após 1-3 segundos
            setTimeout(() => {
                // Gera resposta automática mais contextual
                let resposta = "Olá! Recebi sua mensagem e retornarei em breve.";
                
                if (message.toLowerCase().includes("rota")) {
                    resposta = "A rota de hoje está normal, sem atrasos previstos. Estamos seguindo o cronograma habitual.";
                } else if (message.toLowerCase().includes("hora") || message.toLowerCase().includes("horário")) {
                    resposta = "O horário previsto para chegada é 15:30. Avisarei caso haja alterações.";
                } else if (message.toLowerCase().includes("problema") || message.toLowerCase().includes("ajuda")) {
                    resposta = "Entendi sua preocupação. Vamos resolver isso o mais rápido possível. Pode me dar mais detalhes?";
                }
                
                // Adiciona a resposta ao histórico
                const responseMsg = {
                    text: resposta,
                    type: "received",
                    timestamp: new Date().toISOString()
                };
                
                // Adiciona ao chat do localStorage
                userChat.push(responseMsg);
                localStorage.setItem(chatKey, JSON.stringify(userChat));
                
                // Adiciona à visualização
                const responseDiv = document.createElement("div");
                responseDiv.className = "message received";
                responseDiv.innerHTML = `
                    <div class="message-content">${resposta}</div>
                    <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                `;
                
                chatMessages.appendChild(responseDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, Math.floor(Math.random() * 2000) + 1000); // Responde entre 1-3 segundos
        });
    }
    
    // Cria uma seção para o dashboard se não existir
    if (!document.querySelector("#dashboard")) {
        const dashboardSection = document.createElement("section");
        dashboardSection.id = "dashboard";
        dashboardSection.className = "screen";
        dashboardSection.innerHTML = `
            <div class="content-container">
                <h2>Dashboard</h2>
                <div id="dashboard-container"></div>
            </div>
        `;
        
        document.querySelector("#app").appendChild(dashboardSection);
    }
});

document.addEventListener("DOMContentLoaded", () => {
    // Código existente mantido...
    
    // Adiciona o botão de alternar tema
    function setupThemeToggle() {
        const topNav = document.querySelector("#top-nav");
        const logoutBtn = document.querySelector("#logout-btn");
        
        // Se a barra de navegação existe, adiciona o botão
        if (topNav && !document.querySelector(".theme-toggle")) {
            const themeButton = document.createElement("button");
            themeButton.className = "theme-toggle";
            themeButton.innerHTML = '<span class="theme-icon"></span> <span class="toggle-text">Modo Noturno</span>';
            
            // Insere antes do botão de logout
            if (logoutBtn) {
                topNav.insertBefore(themeButton, logoutBtn);
            } else {
                topNav.appendChild(themeButton);
            }
            
            // Adiciona o evento de clique
            themeButton.addEventListener("click", toggleTheme);
        }

        // Adiciona o botão também na tela de perfil
        const loginScreen = document.querySelector("#perfiel .perfiel");
        if (loginScreen && !loginScreen.querySelector(".theme-toggle-login")) {
            const loginThemeBtn = document.createElement("button");
            loginThemeBtn.className = "theme-toggle-login";
            loginThemeBtn.innerHTML = '<span class="theme-icon"></span> Alternar Tema';
            loginThemeBtn.style.marginTop = "15px";
            
            loginScreen.appendChild(loginThemeBtn);
            loginThemeBtn.addEventListener("click", toggleTheme);
        }
        
        // Verifica o tema salvo no localStorage
        checkSavedTheme();
    }
    
    // Função para alternar entre os temas
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute("data-theme") || "light";
        const newTheme = currentTheme === "light" ? "dark" : "light";
        
        document.documentElement.setAttribute("data-theme", newTheme);
        localStorage.setItem("preferred-theme", newTheme);
        
        // Atualiza o texto do botão
        const toggleText = document.querySelector(".toggle-text");
        if (toggleText) {
            toggleText.textContent = newTheme === "light" ? "Modo Noturno" : "Modo Claro";
        }
        
        // Exibe uma notificação
        showNotification(`Tema alterado para modo ${newTheme === "light" ? "claro" : "escuro"}`, "success");
    }
    
    // Verifica o tema salvo
    function checkSavedTheme() {
        const savedTheme = localStorage.getItem("preferred-theme");
        
        if (savedTheme) {
            document.documentElement.setAttribute("data-theme", savedTheme);
            
            // Atualiza o texto do botão
            const toggleText = document.querySelector(".toggle-text");
            if (toggleText) {
                toggleText.textContent = savedTheme === "light" ? "Modo Noturno" : "Modo Claro";
            }
        }
    }
    
    // Inicializa o modo noturno
    setupThemeToggle();
    
    // Adiciona preferência de tema ao registrar novo usuário
    const cadastroForm = document.querySelector("#cadastroForm");
    if (cadastroForm) {
        const originalSubmitEvent = cadastroForm.onsubmit;
        
        cadastroForm.onsubmit = function(e) {
            // Preserva o comportamento original
            if (originalSubmitEvent) {
                originalSubmitEvent.call(this, e);
            }
            
            // Adiciona a preferência de tema ao perfil do usuário
            const currentTheme = document.documentElement.getAttribute("data-theme") || "light";
            
            // Busca o usuário recém-criado
            const users = JSON.parse(localStorage.getItem("users")) || [];
            const latestUser = users[users.length - 1];
            
            if (latestUser) {
                latestUser.preferredTheme = currentTheme;
                localStorage.setItem("users", JSON.stringify(users));
            }
        };
    }
    
    // Adiciona preferência de tema ao perfil do usuário
    function loginUser(user, createNewSession = true) {
        // Código existente de login...
        
        // Aplica o tema preferido do usuário, se existir
        if (user.preferredTheme) {
            document.documentElement.setAttribute("data-theme", user.preferredTheme);
            
            // Atualiza o texto do botão
            const toggleText = document.querySelector(".toggle-text");
            if (toggleText) {
                toggleText.textContent = user.preferredTheme === "light" ? "Modo Noturno" : "Modo Claro";
            }
        }
    }
    
    // Adiciona opção de salvar preferência de tema no formulário de perfil
    const perfilForm = document.querySelector("#perfilForm");
    if (perfilForm && !document.querySelector("#tema-preferido")) {
        const temaField = document.createElement("div");
        temaField.className = "form-group";
        temaField.innerHTML = `
            <label for="tema-preferido">Tema Preferido</label>
            <select id="tema-preferido">
                <option value="light">Claro</option>
                <option value="dark">Escuro</option>
                <option value="system">Seguir Sistema</option>
            </select>
        `;
        
        // Adiciona antes do botão de salvar
        const submitBtn = perfilForm.querySelector("button[type='submit']");
        perfilForm.insertBefore(temaField, submitBtn);
        
        // Seleciona o tema atual
        const temaSelect = document.querySelector("#tema-preferido");
        if (temaSelect) {
            const currentTheme = document.documentElement.getAttribute("data-theme") || "light";
            temaSelect.value = currentTheme;
            
            // Adiciona evento para mudar o tema em tempo real
            temaSelect.addEventListener("change", function() {
                const selectedTheme = this.value;
                
                if (selectedTheme === "system") {
                    // Verifica preferência do sistema
                    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
                    document.documentElement.setAttribute("data-theme", prefersDark ? "dark" : "light");
                } else {
                    document.documentElement.setAttribute("data-theme", selectedTheme);
                }
            });
        }
        
        // Modifica o evento de submit para salvar a preferência
        const originalSubmit = perfilForm.onsubmit;
        perfilForm.onsubmit = function(e) {
            // Mantém o comportamento original
            if (originalSubmit) {
                originalSubmit.call(this, e);
            }
            
            // Salva a preferência de tema
            const temaPreferido = document.querySelector("#tema-preferido").value;
            const currentUser = JSON.parse(localStorage.getItem("users")).find(u => u.email === document.querySelector("#seguranca-email").value);
            
            if (currentUser) {
                currentUser.preferredTheme = temaPreferido;
                localStorage.setItem("users", JSON.stringify(users));
            }
        };
    }
    
    // Adiciona suporte a preferência do sistema (claro/escuro)
    function checkSystemPreference() {
        const userTheme = localStorage.getItem("preferred-theme");
        
        // Se o usuário não tiver escolhido um tema, use a preferência do sistema
        if (!userTheme) {
            const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
            document.documentElement.setAttribute("data-theme", prefersDark ? "dark" : "light");
            
            // Atualiza o texto do botão
            const toggleText = document.querySelector(".toggle-text");
            if (toggleText) {
                toggleText.textContent = prefersDark ? "Modo Claro" : "Modo Noturno";
            }
        }
    }
    
    // Verifica a preferência do sistema
    checkSystemPreference();
    
    // Adiciona listener para mudanças de preferência do sistema
    window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", function(e) {
        // Só aplica se o usuário não tiver escolhido explicitamente um tema
        if (!localStorage.getItem("preferred-theme")) {
            document.documentElement.setAttribute("data-theme", e.matches ? "dark" : "light");
            
            // Atualiza o texto do botão
            const toggleText = document.querySelector(".toggle-text");
            if (toggleText) {
                toggleText.textContent = e.matches ? "Modo Claro" : "Modo Noturno";
            }
        }
    });
});