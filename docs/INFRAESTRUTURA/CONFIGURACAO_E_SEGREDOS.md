# Configuração, parâmetros e segredos

**Estado:** o exemplo `.env.example` configura a alpha; valores aqui são **nomes de parâmetros**, não credenciais de uso real. Nunca salvar `.env` no Git nem copiar segredos em issues ou logs de CI.

## Variáveis usadas atualmente

| Parâmetro | Exemplo no repositório | Notas |
| --- | --- | --- |
| `APP_NAME` | Cianita | Nome do aplicativo |
| `APP_ENV` | production | Valor do exemplo **não significa prontidão para produção** |
| `APP_KEY` | vazio | Obrigatório para sessão/criptografia; segredo persistente |
| `APP_DEBUG` | false | Nunca ativar em acesso não confiável |
| `APP_URL` | http://localhost:8080 | Ajustar à origem efetiva e HTTPS quando aplicável |
| `APP_LOCALE` | pt_BR | Locale desejado |
| `LOG_CHANNEL` / `LOG_LEVEL` | stderr / info | Evitar payloads privados |
| `CIANITA_PORT` | 8080 | Porta publicada em loopback no Compose |
| `DB_CONNECTION` / `DB_HOST` | pgsql / db | Metadados PostgreSQL |
| `DB_PORT` / `DB_DATABASE` | 5432 / cianita | Rede interna |
| `DB_USERNAME` / `DB_PASSWORD` | cianita / vazio | Senha forte do operador |
| `SESSION_DRIVER` / `SESSION_LIFETIME` | database / 120 | Duração em minutos |
| `SESSION_ENCRYPT` | true | Depende de `APP_KEY` estável |
| `CACHE_STORE` / `QUEUE_CONNECTION` | redis / redis | Redis não é fonte das notas |
| `REDIS_CLIENT` / `REDIS_HOST` / `REDIS_PORT` | phpredis / redis / 6379 | Conexão Compose |
| `FILESYSTEM_DISK` | local | Armazenamento privado configurado |

Outros parâmetros do Laravel podem existir, mas **não presumir configuração implementada** sem revisar código e testar.

## Geração inicial

O README contém a sequência:

```bash
cp .env.example .env
# Edite .env e configure DB_PASSWORD forte.
docker compose build
docker compose run --rm --no-deps app php artisan key:generate --show
```

O comando exibe uma chave sensível no terminal: inseri-la **manualmente** em `APP_KEY` sem registrá-la em issue, log ou histórico. Não gerar nova chave com dados criptografados existentes sem plano de recuperação. O primeiro usuário é provisionado por CLI, não existe cadastro público.

## Política de segredos proposta

- Ambientes isolados usam `APP_KEY`, senhas e tokens próprios.
- Guardar arquivos/env secrets com permissões restritivas, acesso mínimo e cópia protegida.
- Preferir gerenciador de segredos quando houver infraestrutura apropriada; não impor SaaS ao uso local.
- Tokens n8n futuros terão TTL, escopo, revogação, rotação e auditoria sem valor bruto.
- CI usa credenciais efêmeras e não acessa dados reais; não expor secrets a PRs não confiáveis.
- No restore, recuperar chaves corretas por canal seguro; perder `APP_KEY` pode tornar conteúdo criptografado inacessível.

## Configuração por ambiente

Não copiar `APP_ENV=production` e presumir segurança. Definir URL, TLS, cookies, trusted proxies, armazenamento, limites, logs, RPO/RTO, backup e monitoramento no checklist de deploy. Validar `docker compose config` sem divulgar valores.

Ver [deploy](IMPLANTACAO_E_AMBIENTES.md), [segurança](../SEGURANCA/DADOS_E_PRIVACIDADE.md), [incidentes](OPERACAO_E_INCIDENTES.md).
