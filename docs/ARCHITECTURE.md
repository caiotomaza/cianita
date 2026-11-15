# Cianita — decisões de arquitetura (versão inicial)

## Contexto

A Cianita busca ser uma plataforma self-hosted de conhecimento. Nesta etapa, há uma única instância de aplicação web e um banco relacional, com volume privado para notas. Não há sincronização entre nós nem edição em tempo real.

## Fronteiras

- **Laravel/PHP:** autenticação, autorização, validação, versionamento e API web.
- **Vue/TypeScript:** interface. Toda ação de leitura/escrita é revalidada pelo servidor.
- **PostgreSQL:** contas, cofres, membros, metadados, versões e exclusão lógica.
- **Volume privado:** corpo das notas `.md`; nome estável baseado no UUID, separado do título editável.
- **Redis:** cache e filas de trabalho. Não é fonte principal das notas.
- **FrankenPHP/Caddy:** servidor HTTP no container; porta publicada apenas em loopback no host.

## Autorização

`VaultPolicy` decide permissões para cada usuário e cofre. O proprietário controla o cofre; leitores podem listar/abrir; editores podem alterar/criar/arquivar notas. A API recebe notas pelo par `vault_id` + `note_id` e verifica ambos antes de ler o arquivo.

O esquema suporta a tabela `vault_user`. **Ainda não há interface de gerenciamento de membros ou fluxo de convite.** A autorização não deve depender de restrições no frontend.

## Persistência e concorrência

A nota contém `id`, `vault_id`, `title`, `version` e `deleted_at`. O Markdown está em `storage/app/private/vaults/<vault-id>/notes/<uuid>.md`.

Ao editar, a API exige a versão atual e retorna 409 se a nota foi modificada. Para reduzir risco, prepara um arquivo temporário e o move antes de atualizar os metadados sob bloqueio transacional no banco.

**Limitação conhecida:** operações envolvendo PostgreSQL e filesystem não têm commit distribuído. Falhas extremas podem causar divergência. Antes de produção, implementar journal/reconciliação, recuperação automatizada, backup consistente e testes de falha injetada. A mudança de título não modifica nome do arquivo, evitando renomeações e path traversal nesta etapa.

Ao arquivar uma nota, somente o registro é marcado como excluído: o arquivo permanece para recuperação. A restauração via UI ainda não existe.

## Próximos contratos

- API externa autenticada com tokens revogáveis e escopos por cofre.
- Webhooks assinados e idempotência de eventos para n8n.
- Importação/exportação com manifesto versionado, checagem SHA-256 e sanitização de caminhos.
- Backup consistente do banco, arquivos e segredos, com teste automatizado de restore.
- Pesquisa e índice derivado de arquivos Markdown, reconciliável com o storage.
- Inventário de permissões e auditoria sem registrar corpos de notas ou segredos.

## Garantias atuais

Esta base oferece somente testes automatizados de regras centrais. **Não há validação end-to-end em um servidor real concluída nesta entrega** até evidência de CI e implantação local. Alterações de infraestrutura devem manter rollback por commit/branch e preservar volumes de dados.
