document.addEventListener("DOMContentLoaded", () => {
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

    let users = JSON.parse(localStorage.getItem("users")) || [];
    let currentRole = "responsavel";

    // Função para alternar telas e controlar a barra de navegação
    function showScreen(screenId) {
        screens.forEach(screen => {
            screen.classList.toggle("active", screen.id === screenId);
        });
        screenLinks.forEach(link => {
            link.classList.toggle("active", link.getAttribute("data-screen") === screenId);
        });

        // Mostrar a barra apenas nas telas pós-login
        const isLoggedInScreen = ["perfil", "rota-tempo-real", "historico-rotas", "chat"].includes(screenId);
        topNav.classList.toggle("visible", isLoggedInScreen);
    }

    // Inicialmente, a tela de login está ativa, então a barra fica oculta
    showScreen("login");

    screenLinks.forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            showScreen(link.getAttribute("data-screen"));
        });
    });

    // Alternar Seções no Perfil
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

    // Seleção de Papel no Cadastro
    roleButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            roleButtons.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            currentRole = btn.getAttribute("data-role");
        });
    });

    // Login
    if (loginForm) {
        loginForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const email = document.querySelector("#login-email").value;
            const senha = document.querySelector("#login-senha").value;
            const user = users.find(u => u.email === email && u.senha === senha);

            if (user) {
                alert("Login realizado com sucesso!");
                userNome.textContent = user.nome || "Usuário";
                document.querySelector("#seguranca-email").value = user.email;
                showScreen("perfil");
            } else {
                alert("E-mail ou senha incorretos!");
            }
        });
    }

    // Cadastro
    if (cadastroForm) {
        cadastroForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const email = document.querySelector("#cadastro-email").value;
            const senha = document.querySelector("#cadastro-senha").value;
            const confirmaSenha = document.querySelector("#cadastro-confirma-senha").value;

            if (senha !== confirmaSenha) {
                alert("As senhas não coincidem!");
                return;
            }

            if (users.some(u => u.email === email)) {
                alert("E-mail já cadastrado!");
                return;
            }

            users.push({ email, senha, role: currentRole });
            localStorage.setItem("users", JSON.stringify(users));
            alert("Cadastro realizado com sucesso!");
            showScreen("login");
        });
    }

    // Perfil
    if (perfilForm) {
        perfilForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const nome = document.querySelector("#nome").value;
            const profissao = document.querySelector("#profissao").value;
            const bio = document.querySelector("#bio").value;
            const idioma = document.querySelector("#idioma").value;
            const site = document.querySelector("#site").value;

            userNome.textContent = nome || "Nome e Sobrenome";
            alert("Perfil salvo com sucesso!");
            console.log({ nome, profissao, bio, idioma, site });
        });
    }

    // Foto
    if (fotoForm) {
        const previewImg = document.querySelector("#photo-preview-img");
        document.querySelector("#foto-upload").addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = () => previewImg.src = reader.result;
                reader.readAsDataURL(file);
            }
        });

        fotoForm.addEventListener("submit", (e) => {
            e.preventDefault();
            alert("Foto salva com sucesso!");
        });
    }

    // Segurança
    if (segurancaForm) {
        segurancaForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const senhaAtual = document.querySelector("#seguranca-senha-atual").value;
            const senhaNova = document.querySelector("#seguranca-senha-nova").value;
            const confirmaSenha = document.querySelector("#seguranca-confirma-senha").value;
            const email = document.querySelector("#seguranca-email").value;

            const user = users.find(u => u.email === email && u.senha === senhaAtual);
            if (!user) {
                alert("Senha atual incorreta!");
                return;
            }

            if (senhaNova !== confirmaSenha) {
                alert("As novas senhas não coincidem!");
                return;
            }

            user.senha = senhaNova;
            localStorage.setItem("users", JSON.stringify(users));
            alert("Senha atualizada com sucesso!");
        });
    }

    // Chat
    if (chatForm) {
        const chatMessages = document.querySelector("#chat-messages");
        chatForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const message = document.querySelector("#chat-input").value;
            if (message) {
                const msgDiv = document.createElement("div");
                msgDiv.classList.add("message", "sent");
                msgDiv.textContent = message;
                chatMessages.appendChild(msgDiv);
                document.querySelector("#chat-input").value = "";
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });
    }
});