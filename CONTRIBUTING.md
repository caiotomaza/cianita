# Como contribuir com a Cianita

A Cianita é um projeto self-hosted em estágio **alpha**. Leia o [roadmap](docs/ROADMAP.md) e as [diretrizes para agentes](AGENTS.md).

## Estratégia de branches

```text
main                          versão estável / produção
└── develop                   integração contínua das mudanças
    ├── feat/editor-markdown  funcionalidade
    ├── fix/isolamento-cofre  correção
    └── docs/implantacao     documentação
```

Toda alteração nasce da `develop`, recebe commits semânticos em português e retorna por PR à `develop`. Releases são PRs de `develop` para `main` depois da aprovação da CI, revisão de segurança, migrações e estratégia de rollback. Um PR aberto para `main` não representa automaticamente uma release autorizada.

Formato de commits: `tipo(escopo opcional): descrição no imperativo em português`. Tipos aceitos: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `ci`, `build`, `perf` e `revert`. Mudanças incompatíveis devem marcar `!` e explicar `BREAKING CHANGE:` no corpo.

## Qualidade mínima

1. Abrir uma issue ou documentar a motivação no PR, incluindo riscos e critérios de aceite.
2. Escrever testes de autorização negativa: usuário não membro, membro leitor e nota de outro cofre.
3. Não capturar ou exibir dados privados em logs e mensagens de erro.
4. Executar `php artisan test` e `npm run build`; quando modificar containers, validar `docker compose config` e smoke test HTTP.
5. Garantir revisão de migrações, uso de volumes e controle de acesso. Não usar rollback destrutivo.
6. Documentar recursos não implementados como pendentes.

## Organização

`app/Http` contém a borda HTTP; `app/Models`, as entidades de persistência; `app/Policies`, as autorizações; operações com armazenamento/compensação devem ser encapsuladas em serviços específicos conforme cresçam. O frontend reside em `resources/js`, e a infraestrutura em `docker/` e `compose.yaml`.

Dados reais de notas não devem constar de fixtures do repositório público.

## Configuração de CI e proteção

Administradores do repositório devem configurar proteção de `main` e `develop`, requerendo PR e checks de CI aprovados. **A existência deste documento não ativa essas proteções no GitHub.**

## Estado da entrega

A infraestrutura ainda não inclui backup/restauração consistente, lockfiles versionados ou integração n8n. Até essas condições serem verificadas, não classifique a aplicação como pronta para produção.
