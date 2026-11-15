# Runbook de operação, diagnóstico e incidentes

**Âmbito:** operação local da alpha e plano para produção futura. Comandos de diagnóstico não substituem backup nem autorizam exclusões de dados.

## Rotina de laboratório

```bash
docker compose ps
docker compose logs --tail=100 app
docker compose logs --tail=100 db redis
docker compose exec app php artisan about
```

Executar com `.env` válido e acesso autorizado. Antes de compartilhar logs, remover nomes privados, headers, chaves, tokens, endereços internos e qualquer corpo de nota.

## Triagem

| Sintoma | Primeiro diagnóstico | Evitar |
| --- | --- | --- |
| Login indisponível | `compose ps`, `app`, porta, `/login` | Expor DB/Redis ou ativar debug público |
| Erro 500 | Logs saneados, key, storage e banco | Apagar volumes, recriar `APP_KEY` |
| Migração falhou | Versão DB, migrations, cópia recuperável | `migrate:fresh` em dados reais |
| Nota retorna 503 | Arquivo privado, volume, DB/FS | Inventar conteúdo substituto |
| Edição retorna 409 | Revisão alterada em outra sessão | Forçar sobrescrita stale |
| Falta de espaço | Volumes, logs, filas, disco | Prune indiscriminado |
| Webhook futuro falhou | Retentativa, idempotência, assinatura | Reexecutar sem limite |

## Incidente — procedimento proposto

1. Classificar impacto em disponibilidade, confidencialidade e integridade.
2. Conter rede/escrita se houver risco de corrupção ou vazamento; preservar evidências.
3. Diagnosticar serviço, banco, volume, alterações recentes e última cópia validada.
4. Recuperar somente por procedimento ensaiado e autorizado, com par DB/storage consistente.
5. Validar login, cofres, permissões negativas, integridade e métricas.
6. Registrar causa-raiz, impacto, mitigação e ação preventiva na issue correspondente.

## Atualizações e rollback

Na alpha, atualizações apenas com dados de teste. Para produção: imagem imutável, migrations revisadas e compatíveis, janela controlada, backup restaurável, smoke tests e monitoramento. **Rollback de código não reverte necessariamente banco/Markdown.**

`docker compose down` preserva volumes por padrão, mas interrompe serviços. **Nunca executar** `docker compose down -v` ou `docker volume prune` na manutenção rotineira — pode destruir os dados.

Ver [backup](BACKUP_E_RESTAURACAO.md) e [topologia](TOPOLOGIA_DOCKER.md).
