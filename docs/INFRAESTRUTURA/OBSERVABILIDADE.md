# Observabilidade e monitoramento

## Instrumentação atual

- Endpoint HTTP `GET /up` fornecido pelo Laravel.
- Healthchecks Compose: app (HTTP `/up`), db (`pg_isready`), redis (`PING`).
- Logs da aplicação em `stderr` pelo `.env.example`, disponíveis em `docker compose logs`.
- CI executa build Docker, migrations efêmeras e smoke test `/login`.

**Não existe:** painel administrativo de métricas, Uptime Kuma configurado, Prometheus, alertas, Laravel Horizon, métricas de integridade ou relatórios de backup. Redis/`queue:work` não são Horizon.

## Camadas propostas

| Camada | Sinais | Reação desejada |
| --- | --- | --- |
| Disponibilidade | Health HTTP, latência e status | Alerta de indisponibilidade |
| Dependências | PostgreSQL, Redis, conexão/fila | Separar falha de app e dependência |
| Integridade | Notas sem arquivo, órfãos, divergências de hash | Quarentena/recovery seguro |
| Capacidade | Disco, volumes, inodes, saturação DB | Cotas e planejamento |
| Jobs | Erros, retries, idade de fila, dead-letter | Reprocessamento idempotente |
| Backups | Última cópia **verificada**, idade e teste restore | Alerta e bloqueio de release |
| Segurança | Login negado, permissão recusada, alteração de membros | Auditoria protegida |

## Correlação, privacidade e auditoria

Padronizar `timestamp UTC`, `request_id`, `actor_id` quando aplicável, `vault_id` minimizado, ação, status e duração. Restringir acesso a metadados e definir retenção. **Nunca** registrar Authorization, cookies, senhas, corpo Markdown ou ZIP privado.

Separar logs técnicos de trilha de auditoria (quem acessou/alterou e quando). A auditoria deverá resistir a alteração indevida e ter autorização própria.

## Ferramentas candidatas

Uptime Kuma, Prometheus/OpenTelemetry e Laravel Horizon **são opções de implementação**, não dependências instaladas. Avaliar consumo, manutenção, retenção e valor antes de incorporar.

## Critério de aceite

Uma falha simulada de DB, fila parada, disco cheio ou backup vencido deve gerar sinal distinto, com instrução de ação conforme [runbook](OPERACAO_E_INCIDENTES.md), sem vazar dados.
