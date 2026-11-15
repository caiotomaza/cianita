# Diretrizes do projeto Cianita

Estas regras orientam contribuições humanas e agentes de desenvolvimento.

## Fluxo de branches

- `main`: código promovido para produção; **não enviar commits diretos**.
- `develop`: linha de integração das funcionalidades ainda não lançadas.
- `feat/<resumo>`, `fix/<resumo>`, `refactor/<resumo>`, `docs/<resumo>`, `chore/<resumo>`, `test/<resumo>` e `ci/<resumo>`: branches curtas criadas de `develop`.
- Abrir pull request para `develop` com testes e revisão. Promover `develop` para `main` somente por PR de release, após critérios de qualidade e operações de rollback definidas.
- Não reescrever histórico compartilhado, não forçar push e não mesclar código com CI falhando.
- Commits no padrão Conventional Commits, **descrições em português**: `feat: adiciona criação de cofres`, `fix: valida acesso por cofre`.

## Arquitetura e regras

- Monólito modular Laravel com apresentação Vue/TypeScript. Não criar microsserviços antes de haver necessidade real.
- Organizar regras de domínio fora dos controllers quando crescerem: serviços/actions, FormRequests, policies e testes. Evitar abstrações desnecessárias.
- PostgreSQL armazena metadados; Markdown vive em armazenamento privado e é o formato portável. Nunca servir diretamente `storage/app/private`.
- **Toda operação** por cofre deve verificar vínculo e autorização no backend, inclusive importação, exportação, pesquisa, anexos, jobs e webhooks.
- Não inventar migração automática entre cofres Obsidian e Cianita: escrever testes de ida-e-volta antes de afirmar compatibilidade.
- Garantir proteção contra path traversal, symlinks indevidos, SSRF, uploads perigosos e XSS ao manipular Markdown e anexos.
- Encriptação, chaves, tokens, senhas e dados de notas não podem aparecer em logs, commits ou issues.
- Evitar comandos destrutivos em volumes (`docker compose down -v`, `docker volume prune`, operações sem backup).
- SQLite é usado nos testes unitários/de feature; validar também PostgreSQL na CI e implantação antes de release.

## Entrega

- Documentar limitações com clareza; não declarar componentes ou funcionalidades prontas se forem apenas planejadas.
- Executar testes de PHP, build/TypeScript, CI e verificação do contêiner.
- Mudanças em armazenamento e migrations precisam de avaliação de backup, reversibilidade, impacto de migração e testes de falhas.
- Não publicar o software como produção antes de implementar e testar um backup/restauração consistentes.
- Consultar `CONTRIBUTING.md`, `SECURITY.md`, `docs/ARCHITECTURE.md` e `docs/ROADMAP.md`.
