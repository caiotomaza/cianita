# ADR 0002 — Estratégia de consistência entre banco e Markdown

**Status:** proposta — **não aceita e não implementada**  
**Contexto:** [armazenamento e integridade](../ARQUITETURA/ARMAZENAMENTO_E_INTEGRIDADE.md)

## Problema

A nota usa PostgreSQL para título/versão e filesystem para corpo. As transações do banco não incluem rename/escrita de arquivo. Um processo pode parar após atualizar o corpo, mas antes do commit dos metadados. Transações invertidas criam o risco simétrico de uma versão apontar para conteúdo ausente.

## Alternativas

1. **Journal/outbox de operações com objetos imutáveis:** gravar objetos por versão e reconciliar estado. Favorece recuperação, aumenta complexidade/armazenamento.
2. **Corpo no PostgreSQL + exportação Markdown:** atomicidade transacional mais simples, mas altera a decisão existente e a estratégia de storage.
3. **Escrita direta com compensação em catch:** menor esforço, mas não cobre crash, kill ou falha do sistema de arquivos; **insuficiente** para dados importantes.
4. **Snapshot periódico sem journal:** facilita recuperação do conjunto, mas pode perder atualizações recentes e não detecta necessariamente inconsistência atual.

## Proposta para prova de conceito

Avaliar primeiro objetos imutáveis com hash, versão e registro durável da operação, mantendo a exportação Markdown intacta. Não substituir o fluxo atual até haver:

- Prova de invariantes em `create`, `update`, `archive` e futuro restore;
- Fault injection nos pontos de corte banco/arquivo;
- Recovery idempotente, conservador e com evidências;
- Análise de desempenho, consumo de espaço e limpeza de órfãos;
- Procedimento de migração e rollback sem perda silenciosa.

## Decisão pendente

Concluir estudo comparativo em [#3](https://github.com/caiotomaza/cianita/issues/3), escolher alternativa, atualizar este ADR para **aceita/rejeitada** e somente então implementar. Nenhum trecho deste documento equivale a código pronto.
