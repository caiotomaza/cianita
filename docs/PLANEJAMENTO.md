# Plano de desenvolvimento, organização e implantação

> **Baseline verificada:** `develop` em 15/11/2026 (alpha). Este documento especifica sequência de execução e critérios de aceite; não marca como implementado o que está apenas planejado. Não há previsão de duração sem medições de esforço e ambiente.

## Objetivo e restrições

Entregar uma plataforma web **self-hosted** de cofres Markdown para uso pessoal ou pequenos grupos, em NAS/homelab; não desenvolver SaaS, multiempresa ou serviços externos obrigatórios. Priorizar **integridade de dados > segurança > recuperabilidade > confiabilidade > manutenibilidade > usabilidade > desempenho**.

Preservar o monólito Laravel 13 + Vue 3/TypeScript, PostgreSQL para metadados, Markdown privado para conteúdo, Redis, Docker Compose e FrankenPHP. Conservar **AGPL-3.0-only** e obrigações aplicáveis ao software disponibilizado por rede.

## Diagnóstico de partida

| Área | Estado verificado | Consequência |
| --- | --- | --- |
| Base Laravel/Vue/Compose | **Implementado (alpha)** por PR [#1](https://github.com/caiotomaza/cianita/pull/1) | É possível desenvolver incrementos sobre `develop` |
| Documentação central | **Implementada** por PR [#8](https://github.com/caiotomaza/cianita/pull/8) | Preservar pastas e links existentes |
| Autenticação/cofres/notas | **Implementado (base)**, com testes iniciais | Ampliar testes negativos e fluxos de erro |
| Controle otimista de versão | **Implementado** (HTTP 409) | Recuperação do rascunho e falha de persistência ainda precisam de teste |
| Lockfiles PHP/Node | **Implementado na PR #11**, [#2](https://github.com/caiotomaza/cianita/issues/2) | Instalar por locks; monitorar advisories e atualizar digests deliberadamente |
| Consistência banco/Markdown | **Bloqueador P0**, [#3](https://github.com/caiotomaza/cianita/issues/3) | A transação de banco não abrange arquivos |
| Backup e restauração | **Bloqueador P0**, [#4](https://github.com/caiotomaza/cianita/issues/4) | Não usar como cópia única de notas importantes |
| Proteção/limpeza de branches | **Pendente de configuração administrativa**, [#9](https://github.com/caiotomaza/cianita/issues/9) | Evitar merges incorretos e branches residuais |
| Anexos/importação/n8n | **Planejados**, [#5](https://github.com/caiotomaza/cianita/issues/5) e [#7](https://github.com/caiotomaza/cianita/issues/7) | Não expandir superfície antes dos controles centrais |

## Etapa G0 — Governança e fundação de engenharia

**Objetivo:** estabelecer fluxo rastreável para alterações pequenas e seguras.

1. Preservar `main` como branch padrão/linha de release e `develop` como integração; branches temporárias saem de `develop`.
2. Resolver [#9](https://github.com/caiotomaza/cianita/issues/9): proteção real de `main` e `develop`, checks de CI obrigatórios, configuração nativa **Automatically delete head branches** e eliminação de refs antigas já integradas. Uma PR fechada sem merge **não** autoriza exclusão sem revisão.
3. Revisar `AGENTS.md`, o template de PR, [fluxo Git](DESENVOLVIMENTO/FLUXO_GIT_CI_CD.md) e [definição de pronto](DESENVOLVIMENTO/DEFINICAO_DE_PRONTO.md). O merge para `develop` ocorre somente após checks exigidos e revisão do diff.
4. Manter `docs/README.md` como índice canônico e [Handbook](HANDBOOK.md)/[Runbook](RUNBOOK.md) como portas de entrada. Preservar diretórios documentais `ARQUITETURA/`, `DECISOES/`, `DESENVOLVIMENTO/`, `INFRAESTRUTURA/` e `SEGURANCA/`; não renomear em massa sem benefício e migração de links testada.

**Aceite:** duas branches permanentes com regras ativas; novo PR comum tem base `develop`, checks comprovados e limpeza de sua branch; documentação consistente com estado real. **Configuração de GitHub não é efetuada por este plano.**

## Etapa F1 — Núcleo confiável (P0, ordem crítica)

| Ordem | Trabalho | Evidência de aceite |
| --- | --- | --- |
| F1.1 | [#2 — reprodutibilidade](https://github.com/caiotomaza/cianita/issues/2): gerar, revisar e versionar `composer.lock` e `package-lock.json`; usar `npm ci`; revisar imagens e dependências | Instalação do zero e build CI com versões fixadas, lint, testes, typecheck e smoke test em PostgreSQL |
| F1.2 | [#3 — integridade](https://github.com/caiotomaza/cianita/issues/3): aceitar/rejeitar [ADR 0002](DECISOES/0002-consistencia-de-notas.md) após prova; journal ou objetos imutáveis + reconciliação conservadora | Fault injection em create/update/archive/crash, sem perda ou substituição silenciosa, sem exclusão de órfãos desconhecidos |
| F1.3 | [#4 — backup/restore](https://github.com/caiotomaza/cianita/issues/4): ponto lógico coerente de PostgreSQL + Markdown, manifesto e SHA-256, retenção/cópia externa, segredos separados | Restore em host **limpo** com hashes, login, isolamento por cofre e leitura/escrita verificáveis; RPO/RTO definidos e medidos |
| F1.4 | Endurecer testes/segurança do núcleo: autorização em todas as rotas, 409, CSRF, XSS, paths e versões | Testes negativos e de integração **com PostgreSQL real**, além da suíte rápida; resultados anexados ao PR |

**Dependência:** a solução de backup precisa conhecer o protocolo de consistência do storage. Testes com SQLite não substituem o PostgreSQL para invariantes de transação, locking e crash. Uma CI verde não substitui restauração validada.

## Etapa F2 — MVP útil de cofres

1. **Permissões e contas:** implementar [#6 — membros](https://github.com/caiotomaza/cianita/issues/6), revogação, auditoria de concessões e remoções, impedir escalada e último proprietário sem sucessor. Distinguir administrador da instância do proprietário de cofre.
2. **Editor confiável:** mover orquestração de persistência de controllers grandes para services/actions conforme a necessidade; melhorar Markdown/preview com sanitização, atalhos, estados de erro e rascunho recuperável após HTTP 409.
3. **Estrutura lógica:** especificar caminhos, pastas, títulos, `[[links]]`, backlinks, frontmatter e tags preservados. UUID físico não equivale ao caminho do Obsidian.
4. **Anexos privados:** validar extensões e tipo real, limites, autorização em upload/download, symlinks e caminhos; testar IDOR e arquivos maliciosos.
5. **Portabilidade:** implementar [#5 — importação/exportação](https://github.com/caiotomaza/cianita/issues/5), ZIP com manifesto versionado, prévia de conflitos e testes de ida e volta. Nunca afirmar compatibilidade de plugins ou sincronização nativa com Obsidian.
6. **Busca autorizada e restauração de notas arquivadas:** somente após política e esquema suficientes; índices reconstruíveis, sem vazamento entre cofres.

**Aceite:** jornadas completas de login, criar cofre, editar, compartilhar, anexar e exportar/reimportar em dados de teste, com isolamento, falhas de rede e concorrência ensaiados. Veja [requisitos](ESCOPO_E_REQUISITOS.md) e [roadmap](ROADMAP.md).

## Etapa F3 — Homologação e aplicação no NAS/homelab

**A implantação experimental de laboratório não é release.** A sequência abaixo é **planejada**, condicionada a F1 e critérios de segurança, sem execução implícita:

1. Definir máquina, volumes persistentes, capacidade, backup fora do host, redundância elétrica/energia e responsáveis pela recuperação. Não inventar SLA/RPO/RTO.
2. Separar desenvolvimento, CI, homologação e instância final por dados, credenciais e endpoints. Testar em host limpo com dados sintéticos antes de usar notas pessoais.
3. Fixar artefatos/imagens por revisão verificável, proteger `.env`/`APP_KEY` e restringir rede. Aplicação via loopback/HTTPS por proxy; acesso remoto preferencialmente VPN autenticada. PostgreSQL, Redis e storage não devem expor serviços.
4. Executar build, migrations revisadas, verificações PostgreSQL/Redis, smoke tests HTTP e CRUD com autorização. Serviço `healthy` não é prova de funcionalidade.
5. Ensaiar backup e **restauração independente**, incluindo falha parcial, permissões, hashes e continuidade de chaves; registrar tempos de recuperação medidos.
6. Promover `develop` a `main` só em PR de release com gates completos, tag no commit correto, changelog e rollback/roll-forward compatível com dados.
7. Monitorar logs sem conteúdo de notas, integridade, espaço, processos e resultados de backup; registrar falhas e executar exercícios periódicos de restore.

Referências operacionais: [Runbook](RUNBOOK.md), [implantação](INFRAESTRUTURA/IMPLANTACAO_E_AMBIENTES.md), [backup](INFRAESTRUTURA/BACKUP_E_RESTAURACAO.md) e [DoD](DESENVOLVIMENTO/DEFINICAO_DE_PRONTO.md).

## Etapa F4 — Automações e melhorias posteriores

Só depois de autorização robusta, auditoria e recuperação: [#7 — API com escopos por cofre e n8n](https://github.com/caiotomaza/cianita/issues/7), tokens revogáveis, webhooks assinados e idempotência. 2FA, métricas e alarmes entram na operação conforme riscos. Grafos, PWA/offline, colaboração simultânea e IA permanecem **exploratórios**, não pressupostos.

## Organização de pastas — decisão de manutenção

```text
AGENTS.md / README.md / CONTRIBUTING.md   entradas e regras da raiz
app/           Laravel (HTTP, Models, Policies; Services/Actions quando necessários)
bootstrap/ config/ database/ routes/ tests/   convenções nativas Laravel
resources/js/  Vue 3 e TypeScript
resources/views/  Blade e integração web
docker/        configuração FrankenPHP/Caddy
compose.yaml / Dockerfile   definição de serviços e imagem
docs/
  README.md    índice canônico
  HANDBOOK.md  entrada de engenharia
  RUNBOOK.md   entrada de operação
  PLANEJAMENTO.md  plano e dependências
  ARQUITETURA/ DECISOES/ DESENVOLVIMENTO/ INFRAESTRUTURA/ SEGURANCA/
.artifacts/    evidências locais fora do Git (não versionar)
```

**Não reorganizar** diretórios padrão do Laravel apenas para impor um layout genérico `src/`. Mudanças de módulos e caminhos físicos precisam de motivação, teste, atualização dos imports/autoload e ADR quando alterarem contrato ou persistência.

## Governança de tarefa, evidências e critérios de parada

- **Issue:** problema, risco, RF/RNF, escopo, aceite, testes positivos/negativos, recuperação e docs afetadas.
- **PR:** base `develop`, Conventional Commits em pt-BR, diff revisado, CI no SHA do head, análise de segurança e links para evidências. Atualização documental ocorre no mesmo PR quando houver impacto.
- **Rastreabilidade local:** identificar execuções relevantes como `YYYY-MM-DD_HH-MM-SS__acao__alvo` e guardar `.log`, `.relatorio.md` e `.evidencias.json` em `.artifacts/` ignorado, sem dados privados. CI guarda artefatos protegidos com retenção.
- **Parar e investigar:** perda ou divergência de Markdown/DB, acesso cruzado, segredos expostos, migração irreversível sem recuperação, CI obrigatória falha ou restore não confiável.
- **Entrega concluída:** commit/PR realmente integrados em `develop`, resultado de checks no commit exato, documentação revisada e confirmação da branch final. **Release** e **deploy** são processos separados, sem promoção automática.

Backlog existente: [issues](https://github.com/caiotomaza/cianita/issues); critérios de qualidade: [DoD](DESENVOLVIMENTO/DEFINICAO_DE_PRONTO.md).
