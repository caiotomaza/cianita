# Estratégia de testes, qualidade e evidências

## Implementado

- PHPUnit em `tests/Feature` e `tests/Unit`.
- `VaultAndNoteTest` testa sessão obrigatória, criação de cofre/nota, isolamento entre usuários, leitor sem escrita, editor compartilhado, versão `409`, soft delete e resposta privada `no-store`.
- CI roda lint PHP, testes SQLite, `vue-tsc`/build Vite e smoke test Docker com PostgreSQL.
- A correção experimental introduz NoteConsistencyTest com injeção de exceções, hashes e reconciliador. CI agrega job PostgreSQL 17, sujeito à confirmação por SHA. **Ainda faltam** SIGKILL real, fsync/disco cheio, restauração em host limpo, E2E e análise estática abrangente.

## Estratégia futura por camadas

| Camada | Exemplos | Quando obrigar |
| --- | --- | --- |
| Unitária | Normalização de caminhos, regras puras, manifestos, escopos | Todo PR relevante |
| Feature HTTP | Input, auth, policies, versões e erros | Todo endpoint |
| Integração | PostgreSQL, arquivos em disco, workers, exportação | Alterações de persistência |
| E2E | Login → cofre → nota → permissão → exportação | MVP |
| Segurança | IDOR, CSRF, XSS, SSRF, ZIP traversal, tokens | Antes de exposição |
| Resiliência | Kill/crash, disco cheio, falha commit, conflito | Antes de dados importantes |
| Recuperação | Restore em host novo e checagem de hashes | Antes de release |
| Carga | Pesquisa, tamanho e volume de notas/anexos | Após fluxo estável |

## Matriz de casos de aceite

- **Isolamento:** A não lê nota de B mesmo que altere IDs de URL; testar em todas as APIs novas.
- **Papéis:** leitor não escreve; editor não eleva papéis; último proprietário protegido.
- **Concorrência:** cliente stale recebe `409`; rascunho preservado no navegador.
- **Integridade:** crash entre escrita Markdown e commit DB, temporário órfão, falta de espaço e restore parcial.
- **Import/export:** preservação de links, estrutura, Unicode, frontmatter e anexos; arquivos maliciosos rejeitados.
- **Integrações:** HMAC errado, replay, outro cofre, escopo incorreto, evento duplicado ou fora de ordem.
- **Backup:** recuperar snapshot pareado, chaves corretas, permissões e hashes.

**Esses casos não estão todos implementados.** Somente marcar concluído com testes reais executados e vinculados à issue.

## Execução local existente

```bash
composer validate --strict --no-check-publish
composer install --no-interaction --prefer-dist
composer audit --locked
vendor/bin/pint --test
npm ci
npm audit --audit-level=high
npm run typecheck
npm run build
php artisan test
```

O `phpunit.xml` usa SQLite em memória; não substituir por ele a validação de migrations e HTTP contra PostgreSQL real no Compose. O `composer.lock` e o `package-lock.json` devem permanecer versionados. Em uma instalação limpa, usar `composer install` e `npm ci` (nunca resolver versões silenciosamente). Ver [atualização de dependências](../INFRAESTRUTURA/ATUALIZACAO_DE_DEPENDENCIAS.md). Ver [laboratório Docker](../INFRAESTRUTURA/IMPLANTACAO_E_AMBIENTES.md) para testes em contêiner.

## Evidências e gates

Guardar logs **saneados**, resultados de CI e testes de recovery por commit/tag. Nunca anexar notas, cookies, senhas ou tokens a bugs públicos. Falhas críticas de autorização, integridade e restore bloqueiam promoção à `main` mesmo se build e testes comuns passarem.


## Verificação adicional da issue #3

A suíte de integração cobre versões imutáveis, 409, cofre incorreto, 503 quando arquivo/hash falham, interrupção antes/depois de escrita e commit, repetição da reconciliação e arquivamento/restore. Exigir resultado do job PostgreSQL 17 no **commit exato**, além da suíte SQLite e lint/Pint. Teste de exceção em PHP não prova SIGKILL, falha física nem restauração independente. Esses riscos permanecem bloqueadores de homologação.
