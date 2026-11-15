# Roadmap da Cianita

## Etapa 0 — Fundação (esta proposta)

- [x] Laravel, Vue e infraestrutura Docker inicial
- [x] Login, criação de conta administrativa via CLI
- [x] Cofres, modelo de papéis e notas Markdown básicas
- [x] Versionamento otimista e exclusão lógica
- [x] Testes iniciais e CI configurada
- [ ] Validar build Docker e testes em CI e host limpo
- [ ] Gerar/revisar `composer.lock` e `package-lock.json`
- [ ] Testar e fortalecer gravação atômica e recuperação de divergências

## Etapa 1 — MVP utilizável

- [ ] Criação/convite/remoção de membros e gestão de permissões na interface
- [ ] Editor CodeMirror 6 com preview Markdown sanitizado
- [ ] Pastas, links `[[wikilinks]]`, backlinks, tags e propriedades
- [ ] Upload seguro de anexos e limites de armazenamento
- [ ] Importação de cofres Obsidian com validação de caminhos e formatos
- [ ] Exportação Markdown/ZIP, manifestos e testes de roundtrip
- [ ] Restore operacional de notas arquivadas

## Etapa 2 — Operação confiável

- [ ] Backup com snapshot consistente de banco + notas + configuração
- [ ] SHA-256, retenção controlada e restauração testada
- [ ] Logs de auditoria, métricas e healthchecks de dependências
- [ ] API externa com tokens por cofre, limites e rotação
- [ ] Integração n8n (webhooks assinados, retries e idempotência)
- [ ] Instalação HTTPS/reverse proxy documentada e hardening
- [ ] Política de upgrades, migrações e rollback suportado

## Etapa 3 — Recursos avançados

- [ ] Pesquisa rápida, grafo de conhecimento e plugins
- [ ] PWA/offline com sincronização segura
- [ ] Colaboração multiusuário em tempo real (CRDT/OT)
- [ ] Temas e personalização
- [ ] Armazenamento externo e alta disponibilidade (após testes de consistência)
