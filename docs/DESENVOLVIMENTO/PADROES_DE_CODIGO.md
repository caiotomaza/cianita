# Padrões de código e organização do monólito

## Princípios

Usar arquitetura Laravel convencional e **módulos por responsabilidade**, sem criar MVC artificial ou microsserviços prematuros. A alpha já possui controllers, Eloquent models, policies e Vue numa aplicação única. Refatorar incrementalmente conforme apareçam operações de domínio reais.

## Distribuição de responsabilidades

| Local | Responsabilidade |
| --- | --- |
| `routes/web.php` | URLs, grupos de autenticação e CSRF |
| `app/Http/Controllers` | Request/response e delegação de regras |
| `app/Http/Requests` (futuro) | Validação/autorizações de entrada de caso de uso |
| `app/Policies` | Permissões por cofre e por ação |
| `app/Models` | Persistência, relações e invariantes simples |
| `app/Actions` ou `app/Services` (futuro) | Operações transacionais e integrações |
| `app/Jobs` / `app/Events` (futuro) | Processamento assíncrono e eventos idempotentes |
| `database/migrations` | Alterações explícitas, seguras e revisáveis do esquema |
| `resources/js` | Componentes Vue, estado e acessibilidade |
| `tests` | Comportamento, autorização e falhas |
| `docker` / `compose.yaml` | Runtime e ambiente reprodutíveis |

Regra de persistência/compensação complexa não deve permanecer indefinidamente nos controllers. Services não devem depender da camada HTTP, e uma API externa estável não deve expor serializações Eloquent sem contrato controlado.

## Estilo e ferramentas

- PHP com tipos, validação no servidor, policies e tratamento explícito de falhas; formatar com Laravel Pint/PSR-12.
- TypeScript tipado, `vue-tsc`, componentes pequenos e tratamento de concorrência/erros no editor.
- Nomes internos seguem convenções Laravel; documentação, commits e texto ao usuário em português.
- Toda operação de nota recebe ou valida contexto do cofre; nunca assumir autorização a partir do ID.
- Caminhos físicos não são construídos com títulos ou arquivos enviados diretamente pelo usuário.
- Migrações devem preservar dados existentes e ter avaliação de upgrade/rollback.
- Evitar traces, tokens e conteúdo Markdown em logs e mensagens de erro.

## Dependências

Antes de adicionar pacote, registrar necessidade, alternativas nativas, licenças, suporte, manutenção e impacto de segurança. Não instalar monitoramento/serviços só para “preparar o futuro”. Os ranges atuais estão sem lockfiles versionados ([#2](https://github.com/caiotomaza/cianita/issues/2)).

## Critérios em cada PR

Relacionar RF/RNF, atualizar testes e `docs/`, revisar dados e migrações, documentar rollback e risco de conteúdo privado. Decisões transversais exigem ADR aceita antes de mudança irreversível. Referências: [CONTRIBUTING](../../CONTRIBUTING.md), [AGENTS](../../AGENTS.md), [arquitetura](../ARCHITECTURE.md).
