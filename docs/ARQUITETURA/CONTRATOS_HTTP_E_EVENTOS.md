# Contratos HTTP e eventos

Este documento separa os **endpoints implementados atualmente** do **desenho ainda proposto** de uma API pública versionada. Os endpoints atuais pertencem a `routes/web.php` e autenticam por **sessão e CSRF**; não fornecem tokens para n8n.

## Rotas existentes

| Método | Caminho | Ação |
| --- | --- | --- |
| GET | `/login` | Formulário HTML |
| POST | `/login` | Autenticação por e-mail/senha |
| POST | `/logout` | Desconectar e invalidar sessão |
| GET | `/` | Aplicação Vue autenticada |
| GET | `/api/vaults` | Lista cofres visíveis |
| POST | `/api/vaults` | Cria cofre com `name` obrigatório, máx. 120 |
| GET | `/api/vaults/{vault}/notes` | Lista notas ativas |
| POST | `/api/vaults/{vault}/notes` | Cria nota (`title` obrigatório, `body` opcional) |
| GET | `/api/vaults/{vault}/notes/{note}` | Retorna nota e `body` |
| PUT | `/api/vaults/{vault}/notes/{note}` | Atualiza `title`, `body` e `version` obrigatório |
| DELETE | `/api/vaults/{vault}/notes/{note}` | Arquiva nota |
| GET | `/up` | Healthcheck básico do Laravel |

### Resposta típica de leitura (exemplo ilustrativo)

```json
{
  "data": {
    "id": "9e81d3a1-8c68-43bc-bd62-f5af1069be1b",
    "vault_id": 1,
    "title": "Exemplo",
    "version": 2,
    "body": "# Exemplo"
  }
}
```

Campos extras, como timestamps, podem aparecer por serialização Eloquent. Não tratar a resposta atual como contrato público sem estabilizar esquema. A listagem retorna metadados; o corpo é carregado na leitura específica.

### Semântica de erro atual

- `401`: requisição JSON sem sessão válida.
- `403`: usuário autenticado sem autorização de cofre.
- `404`: nota não encontrada ou vínculo com o cofre solicitado inválido.
- `409`: versão de edição desatualizada.
- `422`: validação da entrada falhou.
- `503`: arquivo Markdown esperado indisponível.

Não inferir paginação, ETags, PATCH, versionamento semântico ou idempotência onde ainda não foram implementados. Respostas privadas de `/api/*` usam cabeçalhos `no-store`.

## Proposta — API externa futura (não implementada)

- Base separada: `/api/v1/...`, fora do grupo de sessão web e com autenticação por token de serviço.
- Autorização por cofre **em todas** as rotas; token sozinho não transforma o usuário em proprietário.
- Paginação e filtro com limites, rate limit, idempotência para escrita automatizada, respostas de erro estáveis.
- Esquema documentado em OpenAPI quando houver implementação.
- API deve permitir geração/revogação de tokens sem expor valor após criação.

### Envelope proposto de evento

```json
{
  "event_id": "uuid-ilustrativo",
  "event_type": "note.updated",
  "schema_version": 1,
  "occurred_at": "2026-01-01T00:00:00Z",
  "vault_id": 123,
  "resource_id": "uuid-ilustrativo",
  "revision": 3
}
```

Este JSON é **conceitual**; não há publicador hoje. Incluir corpo de nota somente por opt-in e autorização explícita, preferindo evento mínimo e consulta posterior via token. Webhooks devem ter HMAC, timestamp/nonce, tentativa numerada, idempotência e expiração. Assinatura deve cobrir corpo bruto e parâmetros relevantes; validar tolerância a relógio.

## Política de evolução

Somente publicar contrato público depois de ADR aceita, testes de segurança e exemplos em execução. Mudanças incompatíveis exigem nova versão, janela de transição e documentação de migração. Ver [ADR 0003](../DECISOES/0003-api-e-eventos.md) e [integrações](INTEGRACOES_E_AUTOMACOES.md).
