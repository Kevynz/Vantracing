# Contributing to VanTracing
# Contribuindo para o VanTracing

Thank you for considering contributing to VanTracing! / Obrigado por considerar contribuir para o VanTracing!

## How to Contribute / Como Contribuir

### Reporting Bugs / Reportando Bugs

1. Check if the bug has already been reported / Verifique se o bug já foi reportado
2. Open a new issue with:
   - Clear title / Título claro
   - Detailed description / Descrição detalhada
   - Steps to reproduce / Passos para reproduzir
   - Expected vs actual behavior / Comportamento esperado vs atual
   - Screenshots (if applicable) / Capturas de tela (se aplicável)

### Suggesting Features / Sugerindo Funcionalidades

1. Check existing feature requests / Verifique solicitações existentes
2. Open an issue with:
   - Feature description / Descrição da funcionalidade
   - Use case / Caso de uso
   - Proposed implementation (optional) / Implementação proposta (opcional)

### Pull Requests

1. Fork the repository / Faça um fork do repositório
2. Create a feature branch / Crie uma branch de funcionalidade
   ```bash
   git checkout -b feature/YourFeatureName
   ```
3. Make your changes / Faça suas alterações
4. Add bilingual comments (English and Portuguese) / Adicione comentários bilíngues
5. Test thoroughly / Teste minuciosamente
6. Commit with clear messages / Faça commit com mensagens claras
   ```bash
   git commit -m "feat: Add new feature / Adiciona nova funcionalidade"
   ```
7. Push to your fork / Faça push para seu fork
   ```bash
   git push origin feature/YourFeatureName
   ```
8. Open a Pull Request / Abra um Pull Request

## Code Style / Estilo de Código

### PHP
- Follow PSR-12 standards / Siga padrões PSR-12
- Use meaningful variable names / Use nomes de variáveis significativos
- Add bilingual comments for complex logic / Adicione comentários bilíngues para lógica complexa

```php
/**
 * Validates user input / Valida entrada do usuário
 * @param string $input The input to validate / A entrada para validar
 * @return bool True if valid / True se válido
 */
function validateInput($input) {
    // Implementation / Implementação
}
```

### JavaScript
- Use ES6+ features / Use recursos ES6+
- Prefer `const` and `let` over `var`
- Add JSDoc comments / Adicione comentários JSDoc

```javascript
/**
 * Fetches user data from API / Busca dados do usuário da API
 * Obtém os dados do usuário através da API
 * @param {number} userId - User ID / ID do usuário
 * @returns {Promise<Object>} User data / Dados do usuário
 */
async function fetchUserData(userId) {
    // Implementation / Implementação
}
```

### HTML
- Use semantic HTML5 / Use HTML5 semântico
- Include `data-i18n` attributes for translations / Inclua atributos `data-i18n` para traduções
- Add descriptive alt text for images / Adicione texto alt descritivo para imagens

### CSS
- Use BEM methodology when possible / Use metodologia BEM quando possível
- Add comments for complex selectors / Adicione comentários para seletores complexos
- Support dark mode / Suporte modo escuro

## Commit Message Convention / Convenção de Mensagem de Commit

Use conventional commits / Use commits convencionais:

- `feat`: New feature / Nova funcionalidade
- `fix`: Bug fix / Correção de bug
- `docs`: Documentation / Documentação
- `style`: Code style / Estilo de código
- `refactor`: Code refactoring / Refatoração de código
- `test`: Tests / Testes
- `chore`: Maintenance / Manutenção

Examples / Exemplos:
```
feat: Add real-time notification system / Adiciona sistema de notificações em tempo real
fix: Resolve login redirect issue / Resolve problema de redirecionamento de login
docs: Update README with new API endpoints / Atualiza README com novos endpoints da API
```

## Translation / Tradução

When adding new text:
1. Add to both `pt` and `en` sections in `JavaScript/i18n.js`
2. Use `data-i18n` attributes in HTML
3. Test in both languages / Teste em ambos os idiomas

## Testing / Testes

Before submitting:
- Test all functionality / Teste toda funcionalidade
- Test in multiple browsers / Teste em múltiplos navegadores
- Test responsive design / Teste design responsivo
- Test dark/light themes / Teste temas escuro/claro
- Test both languages / Teste ambos os idiomas

## Questions? / Dúvidas?

Feel free to open an issue for questions / Sinta-se à vontade para abrir uma issue com perguntas!

---

**Thank you for contributing! / Obrigado por contribuir!** ❤️
