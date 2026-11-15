# Handbook de engenharia — Cianita

> **Entrada técnica da documentação canônica.** Estado verificado em `develop` em 15/11/2026: aplicação **alpha**. Este handbook aponta para documentos especializados; não substitui o código, os testes ou as ADRs.

## Orientação para desenvolvimento

- **Produto e limites:** [visão do produto](VISAO_DO_PRODUTO.md), [requisitos com IDs e status](ESCOPO_E_REQUISITOS.md) e [roadmap](ROADMAP.md).
- **Ordem de implementação e entregas verificáveis:** [plano de desenvolvimento e implantação](PLANEJAMENTO.md), [riscos](REGISTRO_DE_RISCOS.md) e [definição de pronto](DESENVOLVIMENTO/DEFINICAO_DE_PRONTO.md).
- **Arquitetura:** [visão geral](ARCHITECTURE.md), [modelo de dados](ARQUITETURA/MODELO_DE_DADOS.md) e [contratos HTTP](ARQUITETURA/CONTRATOS_HTTP_E_EVENTOS.md).
- **Segurança:** [autenticação e autorização](ARQUITETURA/AUTENTICACAO_E_AUTORIZACAO.md), [matriz de acesso](SEGURANCA/MATRIZ_DE_ACESSO.md) e [modelo de ameaças](SEGURANCA/MODELO_DE_AMEACAS.md).
- **Engenharia:** [convenções de código](DESENVOLVIMENTO/PADROES_DE_CODIGO.md), [testes](DESENVOLVIMENTO/TESTES_E_QUALIDADE.md) e [Git/CI/CD](DESENVOLVIMENTO/FLUXO_GIT_CI_CD.md).
- **Operação:** [runbook](RUNBOOK.md), [Docker](INFRAESTRUTURA/TOPOLOGIA_DOCKER.md), [configuração/segredos](INFRAESTRUTURA/CONFIGURACAO_E_SEGREDOS.md) e [backup/restauração](INFRAESTRUTURA/BACKUP_E_RESTAURACAO.md).

## Estrutura real e limites entre camadas

| Local | Responsabilidade no código atual | Evolução permitida |
| --- | --- | --- |
| `app/Http/Controllers/` e `routes/` | Borda HTTP, sessão, cofres e notas | Controllers enxutos e FormRequests quando houver complexidade |
| `app/Models/`, `app/Policies/` | Metadados Eloquent e autorização | Toda nova operação de cofre mantém checagem no servidor |
| `app/Console/` | Provisionamento via CLI | Sem autorregistro público por padrão |
| `database/migrations/` | Esquema do PostgreSQL | Mudanças com testes, avaliação de dados e recuperação |
| `resources/js/`, `resources/views/` | Vue/TypeScript e integração com Laravel | Melhorias incrementais, sem segundo backend |
| `storage/app/private/` | Conteúdo Markdown em volume privado | Sem publicação web direta, sem tratar UUID físico como caminho lógico |
| `docker/`, `compose.yaml`, `Dockerfile` | Contêineres e ambiente | Conservar PostgreSQL, Redis, FrankenPHP e volumes persistentes |
| `tests/` e `.github/workflows/ci.yml` | Testes e verificações em CI | Ampliar testes PostgreSQL, falha, autorização e recuperação |
| `docs/` | Documentação canônica em Markdown | Preservar links relativos e diretórios existentes |

**Decidido:** manter a estrutura convencional Laravel na raiz. Não mover `app/` para `src/`, nem subdividir prematuramente em pacotes, módulos físicos ou microsserviços. Uma extração para Actions/Services ocorre por necessidade de domínio e junto de testes.

## Decisões arquiteturais

- [ADR 0001 — monólito modular](DECISOES/0001-monolito-modular.md): **aceita**.
- [ADR 0002 — consistência entre PostgreSQL e Markdown](DECISOES/0002-consistencia-de-notas.md): **proposta**. O código atual não oferece recuperação completa após crash.
- [ADR 0003 — API/eventos externos](DECISOES/0003-api-e-eventos.md): **proposta**, sem n8n integrado.

## Entrada para uma tarefa

1. Verificar `AGENTS.md` da raiz e a versão da branch `develop`; ler issue, requisitos e ADR pertinente.
2. Abrir branch curta de `develop`; definir teste negativo de acesso entre cofres e casos de falha quando houver persistência.
3. Modificar uma fatia revisável de código, testes e documentação afetada, sem mudanças cosméticas no restante.
4. Rodar verificações apropriadas; registrar o que foi **executado**, **falhou** ou **não executado**.
5. Abrir PR para `develop`, validar checks do SHA correto, integrar e confirmar o resultado antes de excluir a branch.

A licença permanece **AGPL-3.0-only**. Uma aprovação de CI isolada não comprova integridade dos dados, segurança de produção ou restauração.
