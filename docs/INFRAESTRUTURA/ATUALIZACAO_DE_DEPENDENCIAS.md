# Atualização controlada de dependências PHP, JavaScript e imagens

**Escopo:** manutenção da Cianita self-hosted em branch temporária, com base em `develop`. Não atualizar diretamente `main` ou `develop`. Este procedimento não é plano de migração de dados, backup ou autorização de implantação.

## Baseline e instalação

- `composer.json` define restrições PHP (PHP 8.3+) e pacotes; `composer.lock` registra as versões Composer resolvidas. Execute `composer install --no-interaction --prefer-dist`, não `composer update`, em CI, builds e implantação.
- `package.json` define dependências Vue/TypeScript/Vite; `package-lock.json` é usado por `npm ci` em CI e no Dockerfile. Divergências entre manifesto e lockfile devem **falhar**, não gerar versões novas durante o build.
- Use PHP 8.4 e Node 22 no ambiente de CI definido em `.github/workflows/ci.yml`. O runtime PHP é FrankenPHP/PHP 8.4; PostgreSQL 17 e Redis 7 servem de base ao laboratório.
- Travas de pacotes não congelam automaticamente imagens Docker, versões do npm/Composer ou serviços externos. O Dockerfile e Compose usam tags identificáveis **com digests fixados** após inspeção real no GitHub Actions. Para cada atualização, conferir `docker buildx imagetools inspect <imagem>`, registrar o digest e substituir conscientemente `imagem@sha256:...`; não inventar hashes. A atualização de digest exige teste de build e compatibilidade de volume, sobretudo PostgreSQL.

## Atualização deliberada em branch

1. Conferir o HEAD de `develop`, `AGENTS.md`, o estado da issue/PR, os lockfiles e as restrições de PHP/Node. Abrir branch curta a partir de `develop`, sem interferir em alterações concorrentes.
2. Definir pacotes e motivação (bug, CVE, compatibilidade, suporte). Analisar release notes, mudanças de API, licenças de terceiros e impacto em scripts `post-install`. Não atualizar em massa nem executar `npm audit fix --force` automaticamente.
3. Para PHP, executar `composer update vendor/pacote --with-all-dependencies --no-interaction --prefer-dist` em ambiente com Composer e extensões corretas; para atualização abrangente **aprovada**, usar `composer update` deliberadamente. Para JS, atualizar restrições com `npm install pacote@versao` ou resolver apenas versões permitidas com `npm update --package-lock-only --ignore-scripts`. Commitar **manifestos e lockfiles** juntos quando ambos mudarem. Nunca editar lockfile manualmente.
4. Em ambiente de teste isolado, validar: `composer validate --strict --no-check-publish`, `composer install`, `composer audit --locked`, `vendor/bin/pint --test`, `php artisan test`, `npm ci`, `npm audit --audit-level=high`, `npm run typecheck` e `npm run build`. Investigar vulnerabilidades por pacote, gravidade, superfície real e versão corrigida; exceções exigem justificativa, responsável e prazo na issue, sem mascarar falha.
5. Executar `docker compose config --quiet` e build em contexto **sem dados reais**; testar PostgreSQL real, migrations e HTTP `/up` e `/login` no GitHub Actions. SQLite em memória é apenas suíte rápida. Revisar o diff para impedir `.env`, segredos, `vendor/`, `node_modules/`, caches e notas privadas versionados.
6. Abrir PR para `develop`, anexar SHA, auditorias, riscos, impacto em licenças (ver [terceiros](../THIRD_PARTY.md)), logs saneados e comprovantes dos checks. Mesclar apenas com CI positiva e política de branches cumprida. Nunca promover `main` como efeito desta atualização.

## Falhas, reversão e recuperação

- Em erro de resolução ou auditoria, parar; investigar versão transitiva e incompatibilidades antes de propor exceção. Não forçar instalação ignorando requisitos de plataforma, não suprimir vulnerabilidades sem justificativa.
- Antes do merge, reverta na branch **o conjunto coerente** de manifestos, lockfiles, Dockerfile e CI, sem regravar a história compartilhada. Após merge, uma nova PR pode restaurar a versão previamente testada, com revisão dos impactos.
- Reverter dependências ou imagem **não** restaura metadados/Markdown nem desfaz migrations. Se um upgrade tiver alterado schema ou volumes, aplicar plano de recuperação validado; nunca excluir volumes nem regenerar `APP_KEY` com dados existentes.
- Revisar novamente avisos e licenças de terceiros; preservar AGPL-3.0-only da Cianita e cumprir obrigações de disponibilização de fonte aplicáveis à utilização pela rede.

Ver [fluxo Git](../DESENVOLVIMENTO/FLUXO_GIT_CI_CD.md), [qualidade](../DESENVOLVIMENTO/TESTES_E_QUALIDADE.md), [topologia](TOPOLOGIA_DOCKER.md) e [recovery](BACKUP_E_RESTAURACAO.md).
