# Autenticação, identidade e autorização

## Estado implementado

- Autenticação web com sessão do Laravel; login `GET/POST /login` e logout `POST /logout`.
- Tentativas de login limitadas por `throttle:5,1`; sessão regenerada após autenticação e invalidada no logout.
- Cadastro público inexistente; contas criadas via `php artisan cianita:user:create <email>`, com senha digitada de forma oculta.
- Rotas `/api/*` **internas ao navegador**, protegidas por `auth` do grupo `web` e token CSRF; respostas da API têm cabeçalhos `Cache-Control: private, no-store`.
- `VaultPolicy`: proprietário (`vaults.owner_id`) pode ler/escrever; `editor` pode ler/escrever; `viewer` pode ler; demais são negados.
- Controller de notas verifica `note.vault_id === vault.id` antes de ler ou modificar conteúdo.

**Não existe atualmente:** 2FA, login externo/OIDC, reset de senha por interface, convite de usuários, painel de administração, token Sanctum para API, papéis de administrador por cofre, gestão de sessões e auditoria.

## Modelo recomendado (planejado)

Separar três conceitos:

1. **Conta:** identidade humana ou de serviço; login da interface não deve usar tokens de automação.
2. **Administração da instância:** capacidade operacional delegada, sem autorização implícita para ler todos os cofres privados.
3. **Papel por cofre:** proprietário, administrador de cofre (futuro), editor, leitor, automação com escopos.

Papéis não devem ser aceitos de forma arbitrária a partir do cliente. Definir privilégio mínimo, negar por padrão, negar a própria escalada de papel, preservar um proprietário e registrar mudanças de associação. Se o administrador da instância precisar de acesso de emergência a conteúdo, definir mecanismo excepcional com justificação e auditoria antes de implementá-lo.

## Verificações obrigatórias

- Autenticação e expiração de sessão.
- Busca de cofre no contexto do usuário; uma referência válida a outro cofre não dá direito a acesso.
- Confirmação de que a nota, anexo, revisão ou job pertence ao cofre solicitado.
- Permissão específica à ação (`view`, `update`, `manage-members` etc.).
- Revalidação em workers, importações/exportações e callbacks, não só no request inicial.
- Revogação imediata (invalidar tokens/caches relevantes, cancelar jobs sensíveis quando necessário).

A visibilidade de botões no Vue é apenas UX; não substitui autorização no backend.

## 2FA, cookies e HTTPS — planejamento

Tornar 2FA opcional por conta e exigível por política da instância; armazenar secrets de recuperação criptografados. Definir configuração de cookies `Secure`, `HttpOnly`, `SameSite` e `SESSION_DOMAIN` conforme domínio e proxy; validar `TrustProxies` antes de TLS termination. Rate limit por identidade/IP e políticas anti-enumeração devem ser testados. **Não considerar 2FA/HTTPS de acesso remoto presentes na alpha.**

## Automação

Tokens de serviço futuros devem ter hash em repouso, escopo explícito por cofre/operação, TTL, rotação, revogação e auditoria sem registrar o valor bruto. Não aceitar um token global com acesso irrestrito como padrão. Detalhes em [contratos](CONTRATOS_HTTP_E_EVENTOS.md), [n8n](INTEGRACOES_E_AUTOMACOES.md) e [matriz](../SEGURANCA/MATRIZ_DE_ACESSO.md).
