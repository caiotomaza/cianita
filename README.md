# Cianita

**Cianita** é uma aplicação web self-hosted para organizar conhecimento em cofres Markdown, inspirada na experiência de edição do Obsidian. O projeto utiliza **Laravel 13, PHP 8.4, Vue 3, TypeScript, PostgreSQL, Redis e Docker Compose**.

> **Estado: base inicial (alpha).** Ainda não é uma alternativa completa ao Obsidian nem está pronta para guardar dados insubstituíveis. Não há rotina automática de backup/restauração nem lockfiles versionados.

**Documentação do projeto:** [índice canônico](docs/README.md), [Handbook de engenharia](docs/HANDBOOK.md), [Runbook operacional](docs/RUNBOOK.md) e [plano de desenvolvimento/implantação](docs/PLANEJAMENTO.md). O estágio atual é alpha; as etapas propostas não representam funcionalidades implementadas.

## O que funciona nesta base

- Login por sessão Laravel (CSRF, regeneração de sessão e limite de tentativas).
- Criação de usuários pelo console administrativo, sem cadastro público.
- Criação e seleção de cofres.
- Modelo de permissões por cofre: proprietário, editor e leitor.
- Criação, leitura, edição e arquivamento lógico de notas Markdown.
- Markdown persistido em volume privado (não acessível diretamente pelo servidor web).
- Controle otimista por versão: edições desatualizadas retornam HTTP 409.
- Interface Vue responsiva, escura, com editor de texto Markdown.
- Contêineres isolados para aplicação, PostgreSQL, Redis e worker opcional.
- Endpoint Laravel `/up` e testes iniciais de isolamento/CRUD.

**Ainda não implementado:** importação/exportação de cofres, backup/restauração validada, interface de gestão de membros, API com tokens, integração n8n, anexos, pesquisa, backlinks, visualização Markdown, grafo, auditoria e edição colaborativa. Consulte [ROADMAP](docs/ROADMAP.md).

## Pré-requisitos

Docker Engine com plugin Docker Compose (ou instalação equivalente compatível), conectividade para baixar imagens/dependências e uma porta local livre. O projeto não foi testado ainda em máquinas de usuários.

## Primeira inicialização

1. Copie `.env.example` para `.env` no diretório do projeto (pelo gerenciador de arquivos ou shell).
2. Edite `.env` e configure **DB_PASSWORD** com um segredo aleatório forte. Não publique o arquivo. Se alterar a porta, alinhe `CIANITA_PORT` e `APP_URL`.
3. Na raiz do repositório, execute:

```shell
docker compose build
docker compose run --rm --no-deps app php artisan key:generate --show
```

4. Copie a chave `base64:...` retornada para `APP_KEY` no seu `.env`. **Nunca a publique nem regenere após armazenar dados sem um plano de recuperação.**
5. Prepare o banco e crie seu primeiro usuário (a senha é solicitada sem eco):

```shell
docker compose run --rm app php artisan migrate --force
docker compose run --rm -it app php artisan cianita:user:create seu-email@exemplo.com
docker compose up -d
```

6. Acesse `http://localhost:8080` (ou a porta configurada) e entre com a conta criada.

A publicação da porta é restrita a `127.0.0.1` intencionalmente. Para acesso em outros dispositivos, implemente HTTPS por um proxy reverso com controle de acesso ou pela sua rede privada (ex.: Tailscale), depois configure `APP_URL` adequadamente. **Não exponha o serviço diretamente à internet nessa fase.**

### Operação

```shell
docker compose ps
docker compose logs --tail=100 app
docker compose exec app php artisan about
docker compose --profile workers up -d
```

O último comando ativa o worker opcional. `docker compose down` preserva volumes por padrão; **`docker compose down -v` remove os volumes e pode destruir os dados**. Não use isso para atualização ou troubleshooting de rotina.

## Estrutura

```text
app/
  Console/Commands/           provisionamento de usuários
  Http/Controllers/            autenticação e API web de notas
  Models/                      User, Vault, Note
  Policies/                    autorização por cofre
database/migrations/           esquema persistente
resources/js/                  interface Vue 3
resources/views/               páginas Blade e login
docker/                        servidor HTTP FrankenPHP/Caddy
docs/                          arquitetura e planejamento
compose.yaml                   app, banco, cache e worker
```

**Importante:** O conteúdo Markdown é salvo como `storage/app/private/vaults/{id}/notes/{uuid}.md`. O PostgreSQL guarda títulos, associações, versões e permissões. Os arquivos não reproduzem ainda os caminhos originais de um cofre Obsidian: importação e exportação interoperáveis requerem desenvolvimento e validação adicionais.

## Desenvolvimento e verificação

O repositório inclui testes PHPUnit e CI para PHP 8.4 e Node 22. Um desenvolvedor com PHP, Composer e Node compatíveis pode executar:

```shell
composer install
npm install
npm run build
php artisan test
```

Para testes PHP, configure `.env` e banco SQLite conforme `phpunit.xml`. O projeto ainda não inclui `composer.lock` ou `package-lock.json`, portanto **não há garantia de builds reproduzíveis**. A geração, revisão e inclusão dos lockfiles são necessárias antes de classificar uma versão como estável.

## Segurança e dados

- O banco e o Redis não publicam portas no host.
- Não há registro público de contas; o provisionamento inicial exige acesso ao console do container.
- O acesso aos arquivos Markdown depende de autorização na API; nunca sirva o diretório `storage/app/private` pelo proxy.
- Notas arquivadas permanecem no disco e podem exigir recuperação administrativa.
- Banco de dados e sistema de arquivos **não formam uma transação atômica**. Uma falha durante gravações ou commit pode exigir reconciliação manual.
- **Sem um procedimento validado de backup e restauração, não utilize esta versão como repositório único de notas importantes.**

Leia [Arquitetura](docs/ARCHITECTURE.md) para decisões e limitações.

## Licença

O repositório mantém a licença original **GNU AGPL-3.0** em [LICENSE](LICENSE). A base Laravel foi iniciada a partir do template oficial [laravel/laravel](https://github.com/laravel/laravel), distribuído sob a licença MIT; consulte [atribuições](docs/THIRD_PARTY.md).
