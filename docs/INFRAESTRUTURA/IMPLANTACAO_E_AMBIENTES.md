# Implantação e ambientes

> **Procedimento de laboratório/alpha.** A Cianita ainda carece de lockfiles, proteção de branches e restore validado; produção aqui descreve critérios futuros, não passos de publicação aberta.

## Ambientes e promoção

| Ambiente | Objetivo | Fonte de código | Dados |
| --- | --- | --- | --- |
| Desenvolvimento local | Ciclo rápido e testes | Feature ou `develop` | Fictícios |
| CI | Validar PHP, frontend e Docker | Commit de PR/branch | Efêmeros |
| Homologação (planejada) | Testar release e restore | Candidato oriundo de `develop` | Sintéticos |
| Produção (não homologada) | Dados reais após gates | `main` e tag aprovada | Protegidos com backup externo |

**`main` é alvo da linha estável, não prova de que já exista versão implantável.** O pipeline atual é CI, não CD: não publica imagens nem faz deploy automático.

## Laboratório Docker — passos existentes

Pré-requisitos: Docker Engine/Compose instalados, acesso ao GitHub, conexão para imagens, porta local livre e armazenamento adequado. Não usar como única cópia de dados importantes.

```bash
git clone https://github.com/caiotomaza/cianita.git
cd cianita
git switch develop
cp .env.example .env
# Editar DB_PASSWORD; alinhar APP_URL e CIANITA_PORT quando necessário.
docker compose config --quiet
docker compose build
docker compose run --rm --no-deps app php artisan key:generate --show
```

Copiar a chave gerada para `APP_KEY` no `.env` sem divulgá-la. Então:

```bash
docker compose run --rm app php artisan migrate --force
docker compose run --rm -it app php artisan cianita:user:create exemplo@dominio.invalid
docker compose up -d
docker compose ps
```

Validar `http://localhost:8080/login` (ou a porta escolhida) e fazer CRUD em cofre de **testes**. A sequência segue README/CI, mas **não foi certificada em instalações de usuário**. Não usar `docker compose down -v`.

### Worker opcional

```bash
docker compose --profile workers up -d
docker compose logs --tail=100 app
```

O worker não significa que tarefas de backup ou n8n já foram implementadas.

## Publicação futura e HTTPS

Fora de loopback, projetar proxy TLS seguro. Checklist: domínio/URL corretos; certificado; trusted proxies; `Secure` cookies; headers de segurança; somente HTTPS exposto; DB/Redis privados; autenticação forte; backup e restore exercitados; rollback testado. Tailscale pode fornecer rede privada, mas sua configuração é **externa** ao projeto atual.

## Upgrade e rollback

Não usar migrações destrutivas, `down -v` ou `volume prune` como “rollback”. Fluxo futuro: registrar versão, verificar cópia/restore, usar imagem imutável, migrar com compatibilidade, smoke test, monitorar e promover. Rollback de código não reverte automaticamente migrações nem alterações Markdown.

Ver [operação](OPERACAO_E_INCIDENTES.md), [release](../DESENVOLVIMENTO/FLUXO_GIT_CI_CD.md) e [backup](BACKUP_E_RESTAURACAO.md).
