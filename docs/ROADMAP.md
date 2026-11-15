# Roadmap orientado por riscos e dependências

> O roadmap não estabelece datas ou versões numéricas fictícias. Cada marco só é concluído com evidências de teste, revisão e documentação. A ordem privilegia **integridade e recuperação de dados** antes de expansão da superfície do produto.

## Marco F0 — Fundação alpha (implementado, com ressalvas)

- [x] Laravel, Vue/TypeScript, PostgreSQL, Redis, Docker Compose e HTTP local.
- [x] Login de sessão e criação de usuário administrativo por CLI.
- [x] Cofres e papéis proprietário/editor/leitor na camada de autorização.
- [x] Notas Markdown em volume privado, versões otimistas e exclusão lógica.
- [x] CI PHP, build de frontend e smoke test Docker no GitHub Actions.
- [x] Documentação arquitetural e guias de contribuição.
- [ ] Proteção efetiva das branches no GitHub (documentar ≠ configurar).
- [ ] Validar implantação em host limpo fora da CI.

**Saída:** fundação demonstrável, **não release para guardar conteúdo insubstituível**.

## Marco F1 — Confiabilidade do núcleo (bloqueadores P0)

- [ ] [#2](https://github.com/caiotomaza/cianita/issues/2): lockfiles, instalação reprodutível, revisão de dependências e imagens.
- [ ] [#3](https://github.com/caiotomaza/cianita/issues/3): journal/reconciliação DB + arquivos e testes de falhas injetadas.
- [ ] [#4](https://github.com/caiotomaza/cianita/issues/4): backup consistente, checksums e restauração em host limpo.
- [ ] Testes de autorização em rotas, arquivos, concorrência e recuperação.
- [ ] Definição operacional de RPO/RTO e validação em ensaio.

**Gate:** não perder ou sobrescrever notas silenciosamente em cenários de falha ensaiados; restauração comprovada.

## Marco F2 — MVP de conhecimento

- [ ] [#6](https://github.com/caiotomaza/cianita/issues/6): convites, gestão de membros e revogação imediata.
- [ ] Editor CodeMirror com preview Markdown sanitizado e acessível.
- [ ] Pastas, links `[[...]]`, backlinks, tags e frontmatter sem conversão destrutiva.
- [ ] Anexos com upload seguro, autorização e cotas.
- [ ] [#5](https://github.com/caiotomaza/cianita/issues/5): importação/exportação interoperável e testes roundtrip.
- [ ] Restauração de notas arquivadas na interface.
- [ ] Validação de usabilidade responsiva, teclado e leitura.

**Gate:** jornadas de criação/edição/compartilhamento e portabilidade passam E2E, com isolamento por cofre.

## Marco F3 — Operação e automação

- [ ] Hardening HTTPS, 2FA, auditoria, métricas e alarmes.
- [ ] Painel operacional para falhas, filas, espaço e resultado de backups.
- [ ] [#7](https://github.com/caiotomaza/cianita/issues/7): API versionada com tokens restritos e webhooks n8n assinados.
- [ ] Política de upgrades, retenção de backups e exercícios de recuperação recorrentes.
- [ ] Testes de segurança e carga representativa.

**Gate:** automações não burlam permissões; incidentes detectáveis e reversíveis.

## Marco F4 — Evolução opcional

Pesquisa avançada e grafo, PWA/offline, colaboração multiusuário em tempo real, plugins, temas, storage externo/HA e IA local **somente após ADR, demanda demonstrada e testes de consistência**. Esses itens não são compromisso do MVP.

## Dependências críticas

`F0 → F1 → F2 → F3` é o fluxo principal. Algumas frentes de UX podem ser prototipadas em paralelo, mas **nenhuma release estável** deve contornar os gates de F1. Tokens e webhooks não devem ser abertos antes de autorização e auditoria. Compatibilidade Obsidian depende de modelo de caminhos/anexos e testes de roundtrip.

Veja [planejamento](PLANEJAMENTO.md), [requisitos](ESCOPO_E_REQUISITOS.md), [DoD](DESENVOLVIMENTO/DEFINICAO_DE_PRONTO.md) e [riscos](REGISTRO_DE_RISCOS.md).
