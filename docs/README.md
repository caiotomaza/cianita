# Documentação oficial — Cianita

> **Estado do projeto: alpha (15/11/2026).** Esta documentação descreve simultaneamente o que **existe no código**, as **decisões adotadas** e o que **ainda precisa ser construído**. Não equivale a uma certificação de prontidão para produção.

## Como ler

| Rótulo | Interpretação |
| --- | --- |
| **Implementado** | Identificado no código da branch `develop`; pode ainda ter limitações |
| **Decidido** | Escolha arquitetural/documental, sem afirmar implementação |
| **Planejado** | Requisito aprovado para execução futura, não disponível |
| **Proposto** | Hipótese técnica ou política sujeita a ADR/revisão |
| **Bloqueador** | Impede classificar a aplicação como segura para dados importantes ou promover release |

A branch `main` permanece reservada à produção, mas **não representa uma versão publicada e aprovada** neste estágio. O desenvolvimento ocorre na `develop` e em branches curtas com pull requests.

## Pontos de entrada

- [Handbook de engenharia](HANDBOOK.md) — mapa técnico do código, normas, decisões e processo de contribuição.
- [Runbook operacional](RUNBOOK.md) — pré-verificações, operação de laboratório, incidentes e critérios de recuperação.
- [Plano de desenvolvimento e implantação](PLANEJAMENTO.md) — execução por dependências, aceite e aplicação futura em NAS/homelab.

O índice abaixo continua sendo a navegação canônica. Não criar uma segunda árvore de documentação nem mover diretórios Laravel por estética.

## Navegação por área

### Produto e planejamento

1. [Visão do produto](VISAO_DO_PRODUTO.md) — missão, público, diferenciais, limites e métricas.
2. [Escopo e requisitos](ESCOPO_E_REQUISITOS.md) — requisitos funcionais/não funcionais com IDs e prioridades.
3. [Roadmap](ROADMAP.md) — entregas ordenadas por dependências e gates de qualidade.
4. [Planejamento de execução](PLANEJAMENTO.md) — decomposição, critérios de priorização, issues e marcos.
5. [Registro de riscos](REGISTRO_DE_RISCOS.md) — ameaças ao projeto, mitigações e responsáveis por papel.
6. [Glossário](GLOSSARIO.md) — terminologia usada nos documentos.

### Arquitetura e dados

- [Visão de arquitetura](ARCHITECTURE.md) — componentes, fluxos e limites técnicos.
- [Modelo de dados](ARQUITETURA/MODELO_DE_DADOS.md) — esquema atual e evolução pretendida.
- [Autenticação e autorização](ARQUITETURA/AUTENTICACAO_E_AUTORIZACAO.md).
- [Persistência, concorrência e integridade](ARQUITETURA/ARMAZENAMENTO_E_INTEGRIDADE.md).
- [Contratos HTTP e eventos](ARQUITETURA/CONTRATOS_HTTP_E_EVENTOS.md).
- [Editor e interoperabilidade Markdown](ARQUITETURA/EDITOR_E_INTEROPERABILIDADE.md).
- [Integrações e n8n](ARQUITETURA/INTEGRACOES_E_AUTOMACOES.md).

### Infraestrutura e operações

- [Topologia Docker e rede](INFRAESTRUTURA/TOPOLOGIA_DOCKER.md).
- [Atualização deliberada de dependências](INFRAESTRUTURA/ATUALIZACAO_DE_DEPENDENCIAS.md).
- [Configuração e segredos](INFRAESTRUTURA/CONFIGURACAO_E_SEGREDOS.md).
- [Implantação e ambientes](INFRAESTRUTURA/IMPLANTACAO_E_AMBIENTES.md).
- [Backup, exportação e restauração](INFRAESTRUTURA/BACKUP_E_RESTAURACAO.md).
- [Observabilidade](INFRAESTRUTURA/OBSERVABILIDADE.md).
- [Operação, incidentes e atualização](INFRAESTRUTURA/OPERACAO_E_INCIDENTES.md).

### Engenharia e segurança

- [Git, CI/CD e releases](DESENVOLVIMENTO/FLUXO_GIT_CI_CD.md).
- [Estrutura de código e padrões](DESENVOLVIMENTO/PADROES_DE_CODIGO.md).
- [Estratégia de testes](DESENVOLVIMENTO/TESTES_E_QUALIDADE.md).
- [Definição de pronto e checklist de release](DESENVOLVIMENTO/DEFINICAO_DE_PRONTO.md).
- [Modelo de ameaças](SEGURANCA/MODELO_DE_AMEACAS.md).
- [Matriz de acesso](SEGURANCA/MATRIZ_DE_ACESSO.md).
- [Proteção de dados e retenção](SEGURANCA/DADOS_E_PRIVACIDADE.md).

### Decisões e atribuições

- [ADR 0001 — monólito modular](DECISOES/0001-monolito-modular.md) — aceita.
- [ADR 0002 — consistência DB/filesystem](DECISOES/0002-consistencia-de-notas.md) — proposta.
- [ADR 0003 — API e integração](DECISOES/0003-api-e-eventos.md) — proposta.
- [Atribuições e licenças de terceiros](THIRD_PARTY.md).

## Fonte da verdade e manutenção

- **Comportamento em execução:** código, testes e configuração na revisão correspondente.
- **Decisões:** ADRs com status; uma ADR proposta não é compromisso implementado.
- **Próximos trabalhos:** [issues do repositório](https://github.com/caiotomaza/cianita/issues) e [roadmap](ROADMAP.md).
- **Políticas de contribuição e segurança:** [CONTRIBUTING](../CONTRIBUTING.md), [AGENTS](../AGENTS.md), [SECURITY](../SECURITY.md).
- O documento técnico deve ser atualizado **no mesmo PR** de uma mudança de comportamento, esquema, segurança ou infraestrutura.
- Documentos usam português brasileiro, caminhos relativos e exemplos fictícios, sem dados privados.

**Bloqueios atuais conhecidos:** lockfiles não versionados ([#2](https://github.com/caiotomaza/cianita/issues/2)); persistência DB/arquivos sem recuperação automática ([#3](https://github.com/caiotomaza/cianita/issues/3)); backup/restore consistente ausente ([#4](https://github.com/caiotomaza/cianita/issues/4)); proteção de branches ainda não configurada. Não usar a Cianita alpha como cópia única de notas importantes.
