# Estratégia de testes, qualidade e evidências

## Implementado

- PHPUnit em `tests/Feature` e `tests/Unit`.
- `VaultAndNoteTest` testa sessão obrigatória, criação de cofre/nota, isolamento entre usuários, leitor sem escrita, editor compartilhado, versão `409`, soft delete e resposta privada `no-store`.
- CI roda lint PHP, testes SQLite, `vue-tsc`/build Vite e smoke test Docker com PostgreSQL. O job Docker foi ampliado para executar `scripts/smoke_laboratorio.py` com contas artificiais, login real/CSRF, CRUD, isolamento, HTTP 409, checagem de portas e persistência após restart. Verificar o resultado do workflow no SHA exato antes de classificar como aprovado.
- **Não existe ainda** suíte E2E, testes de carga, testes de recuperação real, integração n8n, fault injection nem análise estática abrangente.

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
composer install
npm install
npm run build
php artisan test
```

O `phpunit.xml` usa SQLite em memória; para ambiente local ajustar `.env`. Sem lockfiles, os comandos ainda não garantem builds idênticos. Ver [laboratório Docker](../INFRAESTRUTURA/IMPLANTACAO_E_AMBIENTES.md) para testes em contêiner.

## Evidências e gates

Guardar logs **saneados**, resultados de CI e testes de recovery por commit/tag. Nunca anexar notas, cookies, senhas ou tokens a bugs públicos. Falhas críticas de autorização, integridade e restore bloqueiam promoção à `main` mesmo se build e testes comuns passarem.
