# Escopo e requisitos rastreáveis

Este é o registro **normativo do comportamento desejado**. Status refletem a revisão da branch `develop` em 15/11/2026, não uma promessa de lançamento. `P0` é bloqueador de confiabilidade ou segurança; `P1`, essencial ao MVP; `P2`, evolução de valor; `P3`, exploratório.

## Funcionais

| ID | Requisito e critério de aceitação resumido | Prioridade | Status |
| --- | --- | --- | --- |
| RF-01 | Autenticação por sessão, login com limitação de tentativas e logout que invalida a sessão | P1 | Implementado (base) |
| RF-02 | Provisionamento administrativo de usuários, sem autorregistro público | P1 | Implementado via CLI |
| RF-03 | Criar/listar cofres visíveis ao usuário | P1 | Implementado (base) |
| RF-04 | Papéis por cofre e autorização no backend, inclusive impedir acesso a outro cofre | P0 | Implementado (base); gestão pendente |
| RF-05 | CRUD de notas Markdown com conteúdo persistente e versão de concorrência | P0 | Implementado (com risco de inconsistência) |
| RF-06 | Arquivar nota sem apagar arquivo; oferecer restauração pela interface | P1 | Arquivar implementado; restaurar planejado |
| RF-07 | Gestão de membros por convite, edição, remoção e revogação | P1 | Planejado — [#6](https://github.com/caiotomaza/cianita/issues/6) |
| RF-08 | Editor Markdown com sintaxe, preview seguro, atalhos e acessibilidade | P1 | Texto simples implementado; editor avançado planejado |
| RF-09 | Pastas, links `[[nota]]`, backlinks, tags e frontmatter preservados | P1 | Planejado |
| RF-10 | Upload/download de anexos privados com validação, cotas e políticas | P1 | Planejado |
| RF-11 | Importar cofres Markdown/Obsidian e exportar ZIP/Markdown com verificação de roundtrip | P1 | Planejado — [#5](https://github.com/caiotomaza/cianita/issues/5) |
| RF-12 | Busca textual autorizada e indexação reconstruível | P2 | Planejado |
| RF-13 | Gerar backup consistente, restaurar em host limpo e verificar integridade | P0 | Planejado — [#4](https://github.com/caiotomaza/cianita/issues/4) |
| RF-14 | API externa com tokens por cofre e rate limiting | P2 | Planejado — [#7](https://github.com/caiotomaza/cianita/issues/7) |
| RF-15 | Webhooks assinados, retries e idempotência para n8n | P2 | Planejado — [#7](https://github.com/caiotomaza/cianita/issues/7) |
| RF-16 | Auditoria, monitoramento de aplicação, filas e backup | P1 | Healthcheck simples implementado; restante planejado |
| RF-17 | 2FA e gerenciamento de sessões/tokens | P1 | Planejado |
| RF-18 | Grafo de relações, temas e funcionalidades offline | P3 | Exploração futura |

## Não funcionais

| ID | Requisito verificável | Prioridade | Evidência necessária |
| --- | --- | --- | --- |
| RNF-01 | Cofres isolados em API, dados persistentes, jobs, pesquisa e downloads | P0 | Testes negativos/IDOR e revisão de policies |
| RNF-02 | Nenhum segredo, conteúdo de nota ou dado sensível em logs e telemetria | P0 | Revisão + testes de redaction |
| RNF-03 | Criação/edição recuperável após falha DB/filesystem, sem sobrescrita silenciosa | P0 | [#3](https://github.com/caiotomaza/cianita/issues/3), fault injection e reconciliação |
| RNF-04 | Build determinístico e dependências com versões travadas e auditadas | P0 | [#2](https://github.com/caiotomaza/cianita/issues/2), lockfiles e CI |
| RNF-05 | Implantação self-hosted com Docker Compose, sem banco/cache expostos | P1 | Config e smoke test |
| RNF-06 | Exportação útil sem dependência exclusiva do aplicativo | P1 | Teste fora da Cianita |
| RNF-07 | Procedimento de backup/restore com RPO/RTO definidos e testados | P0 | Logs de exercícios e checksums |
| RNF-08 | HTTPS em acesso remoto, cookies seguros e proteção CSRF/XSS/SSRF | P0 | Testes de ambiente e segurança |
| RNF-09 | Acessibilidade de tarefas centrais por teclado e dispositivos móveis | P1 | Auditoria automatizada e manual |
| RNF-10 | Atualização com migrações revisadas e plano de rollback de aplicação/dados | P0 | Simulação de upgrade e restore |
| RNF-11 | Licenças e atribuições preservadas | P1 | Revisão de [THIRD_PARTY](THIRD_PARTY.md) e SBOM futura |
| RNF-12 | Respostas HTTP privadas não armazenadas por caches intermediários | P0 | Teste do middleware `no-store` |

## Critério para classificar como MVP

Um conjunto de telas não basta. O MVP requer RF-01 a RF-11 com estados claramente documentados, RNF-01/02/03/04/05/06/08/09 e testes correspondentes. **Uso com dados importantes ou release para produção** exige adicionalmente RF-13, RNF-07 e RNF-10, mesmo que essas entregas apareçam em fase operacional posterior.

## Contratos ainda abertos

As decisões de formato de manifesto, escopos de tokens, política de versionamento de API, tamanho máximo de anexos, cotas, RPO/RTO e modelo de pesquisa precisam de ADR ou especificação técnica antes de codificar. Não inventar comportamento como se estivesse implementado.

## Rastreabilidade

IDs devem aparecer em issues, critérios de aceite, testes e PRs. Referências: [fluxos e qualidade](DESENVOLVIMENTO/TESTES_E_QUALIDADE.md), [permissões](SEGURANCA/MATRIZ_DE_ACESSO.md), [roadmap](ROADMAP.md).
