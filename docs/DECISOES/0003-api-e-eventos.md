# ADR 0003 — Separação entre API web, API pública e eventos

**Status:** proposta — **não aceita e não implementada**

## Contexto

O prefixo `/api` atual é parte do grupo web Laravel: requer login de sessão e CSRF; atende ao frontend Vue. O projeto pretende integrar n8n e scripts externos sem conceder acesso irrestrito.

## Alternativas

1. Expor as rotas existentes com sessão e cookies aos robôs — inseguro para automação e difícil de revogar.
2. Criar API versionada independente com tokens de escopo por cofre, usando services/policies comuns à interface — proposta preferida.
3. Integrar diretamente com o banco/volumes — rejeitada; contorna autorização e invariantes de persistência.

## Proposta de contrato

- API dedicada `/api/v1`; esquema OpenAPI e controle de compatibilidade.
- Tokens com escopos sobre cofre+ação, expiração, revogação e rate limit.
- Eventos mínimos, IDs estáveis, entrega por outbox/worker e retries observáveis.
- Webhooks com HMAC, antirreplay, proteção SSRF e idempotência do consumidor.
- Sem presumir *exactly once* ou que webhook entregue equivale a transação de negócio.

## Gate da decisão

Antes da implementação: especificar modelo de autorização, formato da assinatura, rotação, esquema JSON, seleção de eventos, política de reentrega e testes de falhas/replay. Validar isolamento em ambiente com dois cofres e tokens diferentes. Acompanhar [#7](https://github.com/caiotomaza/cianita/issues/7).

Ver [contratos HTTP](../ARQUITETURA/CONTRATOS_HTTP_E_EVENTOS.md) e [integrações](../ARQUITETURA/INTEGRACOES_E_AUTOMACOES.md).
