# Fluxo Git, CI/CD e releases

## Estratégia de branches adotada

```mermaid
flowchart TD
 F["feat/*, fix/*, docs/*, ci/*"] -->|PR revisado| D["develop: integração"]
 D -->|PR de release após gates| M["main: linha estável"]
 M -->|tag versionada e aprovação| P["Deploy controlado (futuro)"]
```

- `main`: linha estável **após uma release aprovada**; ainda não há versão de produção homologada.
- `develop`: integração da alpha e trabalho em andamento.
- `feat/*`, `fix/*`, `refactor/*`, `docs/*`, `chore/*`, `ci/*` e `test/*`: branches curtas criadas de `develop`.
- `hotfix/*` (futuro): originada da tag de release quando houver incidente crítico; retorno da correção também à `develop`.

PRs funcionais devem ter escopo revisável, commits semânticos em português e testes/documentação correspondentes. Preferir squash merge para alterações comuns. Releases para `main` exigem checklist independente do merge da feature. Rebase/force push de branches compartilhadas não é política padrão.

### Exemplos de commits

```text
feat(cofres): adiciona convite de membros
fix(notas): impede acesso cruzado entre cofres
test(auth): cobre expiração de sessão
docs: documenta processo de restauração
ci: valida migrações com PostgreSQL
feat(api)!: altera esquema de eventos

BREAKING CHANGE: clientes devem migrar para o novo contrato.
```

## CI implementada

Workflow: `.github/workflows/ci.yml`. Gatilhos: push em `main` e `develop`, e PR direcionado a essas branches.

| Job | Validação real |
| --- | --- |
| `php` | PHP 8.4, Composer, lint e PHPUnit em SQLite em memória |
| `frontend` | Node 22, TypeScript (`vue-tsc`) e build Vite |
| `docker` | Config Compose, build, PostgreSQL/Redis, migrations e HTTP `/up` + `/login` |

Limitações: sem lockfiles, sem pipeline de publish/deploy, sem testes de backup, restore, segurança aprofundada, E2E ou host de homologação. Não declarar CD existente. A CI executou smoke tests com dados efêmeros; isso não cobre operação real.

## Governança no GitHub — pendente ativar

Os documentos estabelecem a política, mas **a proteção efetiva das branches ainda não está habilitada**. Configurar nas regras do repositório:

1. Exigir PR para `main` e `develop` e bloquear force push/exclusão.
2. Exigir checks relevantes de CI no commit atual, sem permitir merge com job pendente/falhando.
3. Definir revisão antes de merge e política explícita de bypass para mantenedores.
4. Usar menor privilégio para GitHub Actions; secrets de produção indisponíveis para PRs.
5. Habilitar prevenção de vazamento de secrets e revisão de dependências quando disponível.

## CD e versionamento futuros

1. Build determinístico com lockfiles e imagens pinadas.
2. Testes e auditoria de dependências; artefato com tag/digest imutável.
3. Deploy em homologação com restore testado e aprovação.
4. PR de release `develop → main`; tag SemVer (`vMAJOR.MINOR.PATCH`) e changelog.
5. Deploy manual ou automação com aprovação explícita, backup prévio válido e smoke tests.
6. Monitorar regressões; reverter imagem **somente se** schema/arquivos forem compatíveis, caso contrário restauração coordenada.

O esquema acima é **desenho**, sem deploy configurado no presente. Referências: [qualidade](TESTES_E_QUALIDADE.md), [DoD](DEFINICAO_DE_PRONTO.md), [deploy](../INFRAESTRUTURA/IMPLANTACAO_E_AMBIENTES.md), [CONTRIBUTING](../../CONTRIBUTING.md).
