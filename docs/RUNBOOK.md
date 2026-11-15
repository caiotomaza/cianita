# Runbook operacional — Cianita

> **Estado: alpha — uso de laboratório.** Este documento é o ponto de entrada operacional e remete aos procedimentos especializados. Não há backup/restauração implementados e validados, deploy de produção, política operacional de RPO/RTO ou reversão de migrações comprovada.

## Ambientes e fontes

- [Implantação de laboratório e ambientes](INFRAESTRUTURA/IMPLANTACAO_E_AMBIENTES.md): sequência atual de inicialização.
- [Topologia Docker e redes](INFRAESTRUTURA/TOPOLOGIA_DOCKER.md): contêineres, portas e volumes.
- [Configuração e segredos](INFRAESTRUTURA/CONFIGURACAO_E_SEGREDOS.md): `.env`, `APP_KEY`, permissões e acesso.
- [Operação e incidentes](INFRAESTRUTURA/OPERACAO_E_INCIDENTES.md): diagnóstico, evidência e interrupção segura.
- [Backup e restauração](INFRAESTRUTURA/BACKUP_E_RESTAURACAO.md): **especificação proposta**, não procedimento homologado.
- [Observabilidade](INFRAESTRUTURA/OBSERVABILIDADE.md): estado atual e controles planejados.
- [Gates de release](DESENVOLVIMENTO/DEFINICAO_DE_PRONTO.md): critérios para promover versões.

## Pré-verificações para laboratório

1. Confirmar branch/commit exato e que se trata de **dados de teste**, não da única cópia de notas importantes.
2. Revisar `.env.example` e preencher segredos somente em `.env` local, não versionado. Nunca registrar `APP_KEY`, conteúdo de notas ou credenciais.
3. Verificar recursos, armazenamento e portas disponíveis sem modificar volumes existentes.
4. Validar a configuração e consultar saúde dos serviços; contêiner ativo não equivale a aplicação funcional.

```bash
docker compose config --quiet
docker compose ps
docker compose logs --tail=100 app
```

Para uma instância nova, seguir **exatamente** o [procedimento de laboratório](INFRAESTRUTURA/IMPLANTACAO_E_AMBIENTES.md), incluindo criação controlada de chave e migrações. Depois testar `/up`, `/login`, autenticação e operações em cofre fictício. Não considerar o endpoint `/up` um teste completo do PostgreSQL e do storage.

## Verificações não destrutivas e smoke de laboratório

Após inicialização, em um terminal no host com Compose e variáveis de ambiente configuradas:

```bash
docker compose ps
docker compose exec -T db sh -c 'pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB"'
docker compose exec -T redis redis-cli ping
curl --fail --silent --show-error http://127.0.0.1:8080/up
curl --fail --silent --show-error --output /dev/null http://127.0.0.1:8080/login
```

Ajustar a porta dos comandos HTTP se `CIANITA_PORT` for diferente. Banco e Redis **não devem** ter bindings de portas no host. Inspecionar os bindings reais (valor `null` para a porta interna indica ausência de publicação):

```bash
docker inspect --format '{{json .NetworkSettings.Ports}}' "$(docker compose ps -q db)"
docker inspect --format '{{json .NetworkSettings.Ports}}' "$(docker compose ps -q redis)"
```

O script `scripts/smoke_laboratorio.py` foi ligado ao job Docker da CI: usa usuários/notas fictícios, verifica CSRF, isolamento, CRUD, versão 409 e persistência após restart de serviços. Só é apropriado para **volumes novos e descartáveis**: `CIANITA_SMOKE_LAB=1 python3 scripts/smoke_laboratorio.py`. Não executar em instância com dados importantes, porque o script altera dados e reinicia serviços. A execução da CI é evidência limitada àquele runner e SHA; um host real/NAS continua **não testado**.

## Interrupção segura e coleta de evidências

Em caso de erro de escrita, divergência entre banco e Markdown, falta de volume ou suspeita de violação de permissão:

1. Interromper **novas gravações** e preservar banco, storage e evidências. Não executar tarefas de limpeza ou migração exploratória.
2. Identificar commit, fuso, ambiente, operação, horário e sintomas; coletar somente metadados não sensíveis dos logs.
3. Inspecionar contêineres e recursos sem sobrescrever os volumes originais.
4. Tratar divergência de consistência como **bloqueador** até análise. A [ADR 0002](DECISOES/0002-consistencia-de-notas.md) ainda não foi aceita/implementada.
5. Restaurar apenas por procedimento validado e usando cópia íntegra e isolada. **Não testar restore sobre o único conjunto de dados existente.**

Não executar `docker compose down -v`, `docker volume prune`, exclusão de órfãos ou regeneração de `APP_KEY` para tentar reparar a instância.

## Evidências por execução relevante

Usar o mesmo identificador `YYYY-MM-DD_HH-MM-SS__acao__alvo` em `.artifacts/logs/`, `.artifacts/relatorios/` e `.artifacts/evidencias/` quando houver execução local controlada; manter essas pastas fora do Git. Relatar fuso, ambiente, SHA, comando, exit code, resultado e limites. Nunca guardar conteúdo Markdown privado, senhas ou tokens.

## Homologação e implantação futura

Só planejar o uso com dados importantes após fechar [#2 — build reprodutível](https://github.com/caiotomaza/cianita/issues/2), [#3 — consistência](https://github.com/caiotomaza/cianita/issues/3) e [#4 — backup/restauração](https://github.com/caiotomaza/cianita/issues/4), executar restauração em host limpo e cumprir os [gates de release](DESENVOLVIMENTO/DEFINICAO_DE_PRONTO.md).

A arquitetura-alvo é NAS/homelab com Docker Compose, volumes protegidos, acesso por LAN/VPN autenticada ou proxy HTTPS bem configurado, banco/Redis não publicados, cópia externa e monitoramento. Essas características não comprovam implantação. Consulte o [plano por marcos](PLANEJAMENTO.md).
