# Planejamento de desenvolvimento e governança

## Modelo de execução

Desenvolvimento incremental em monólito modular. Cada mudança começa com uma issue contendo problema, contexto, requisitos vinculados, critérios de aceite observáveis, impactos de segurança/dados, testes e plano de rollback. O PR implementa uma fatia vertical completa (HTTP, regra de negócio, persistência, frontend, teste e documentação) em vez de repartir antecipadamente por equipes/camadas.

**Processo de trabalho:** backlog → refinamento → desenho/ADR quando necessário → branch curta → implementação e testes → PR para `develop` → CI/revisão → integração → homologação → PR de release para `main` → publicação controlada. A esteira **não faz deploy automático em produção hoje**.

## Priorização

Ordenar por: (1) risco à confidencialidade/integridade e perda de dados; (2) pré-requisito técnico; (3) valor ao fluxo principal; (4) esforço, reversibilidade e manutenção. P0 é impeditivo, mesmo que a interface já funcione. Não pontuar estimativas sem conhecimento da equipe e ambiente.

| Sequência recomendada | Entrega | Por quê |
| --- | --- | --- |
| 1 | Lockfiles e proteção de branch | Reprodutibilidade e governança |
| 2 | Reconciliação e modelo de persistência | Evitar corrupção silenciosa |
| 3 | Backup/restore com testes | Recuperação mínima antes de dados reais |
| 4 | Gestão de membros e testes IDOR | Multiusuário sem escalada de privilégio |
| 5 | Editor, estrutura de notas e anexos | Fluxo de edição real |
| 6 | Importação/exportação com roundtrip | Portabilidade e confiança |
| 7 | Observabilidade, 2FA e integração n8n | Exposição segura e operação |
| 8 | Pesquisa, grafo, offline e colaboração | Incrementos opcionais |

## Formato de issue

`Contexto → problema → objetivo → RF/RNF → fora do escopo → critérios de aceite → riscos/segurança → estratégia de teste → rollback/recuperação → documentação afetada`. Uma issue grande demais deve ser dividida em subtarefas com integração funcional.

## Decisões

Usar ADR quando houver impacto transversal, alteração de contrato público, perda de dados possível, dependência adicional relevante, troca de estratégia de storage ou de controle de acesso. Uma ADR **proposta** exige discussão; uma ADR **aceita** vincula o desenho, mas não significa entrega pronta. Ver [decisões](DECISOES/0001-monolito-modular.md).

## Revisão de PR

- Revisar explicitamente autenticação/autorização, acesso a arquivos, concorrência e migrações.
- Exigir testes negativos de permissões (não membro, leitor, nota de cofre estranho).
- Não alterar schema sem revisar dados existentes e restore.
- Ligar issue, requisitos e arquivos de documentação afetados.
- Integrar apenas após checks obrigatórios aprovados e revisão humana quando disponível.

## Ambientes e responsabilidades

| Papel | Responsabilidade |
| --- | --- |
| Maintainer | Aceitar decisões, proteger branches, autorizar releases |
| Desenvolvedor | Código, testes, revisão de risco, docs no mesmo PR |
| Revisor | Verificar aceites, segurança, rollback e regressões |
| Operador | Segredos, implantação, cópias externas e exercícios de restore |

Uma pessoa pode acumular papéis em projeto pequeno, mas a validação não deve ser confundida com aprovação automática.

## Cadência e evidências

Não prometer datas sem dimensionamento. Para cada marco, produzir: alterações rastreáveis, resultado da CI, changelog/release notes, evidência de restore quando aplicável e registro de riscos remanescentes. O andamento é gerido no [GitHub Issues](https://github.com/caiotomaza/cianita/issues), não por porcentagem estimada.

Referências: [roadmap](ROADMAP.md), [fluxo Git](DESENVOLVIMENTO/FLUXO_GIT_CI_CD.md) e [DoD](DESENVOLVIMENTO/DEFINICAO_DE_PRONTO.md).
