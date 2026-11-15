# Armazenamento, integridade e concorrência

## Ponto de partida (implementado)

O conteúdo de cada nota é salvo em Markdown privado no volume Docker `cianita_storage` (diretório interno `/app/storage/app/private/vaults/{id}/notes/{uuid}.md`). O banco mantém título, associação ao cofre, versão e soft delete. Arquivos são acessados pelo disk `local` e não por URL direta.

**Criação atual:** insere metadados em transação DB e escreve o arquivo. Uma falha no commit DB pode deixar arquivo órfão.  
**Edição atual:** obtém lock da linha, valida `version`, grava temporário, substitui arquivo e atualiza banco. Um crash entre operações pode causar divergência.  
**Arquivamento atual:** marca a nota como excluída no banco; mantém Markdown.  
**Leitura atual:** retorna 503 se o arquivo esperado não existe.

Nenhuma dessas operações equivale a transação atômica entre PostgreSQL e filesystem. O `version` protege contra edição concorrente stale **quando o fluxo é executado normalmente**, mas não garante recuperação pós-crash.

## Requisitos para o desenho final

1. Gravação de um arquivo por vez sem corromper conteúdo existente; usar temporário no mesmo filesystem e troca atômica quando garantida.
2. Versionamento imutável ou journal durável que permita descobrir qual corpo pertence a qual versão DB.
3. Estados de recuperação observáveis e repetíveis; recovery **não pode** sobrescrever a última versão confirmada sem evidência.
4. Garbage collection separado, conservador, com quarentena e retenção antes de apagar órfãos.
5. Consistência para renomeações, anexos, caminhos e exportação.
6. Cenários com `SIGKILL`, queda de disco, rollback de transaction, falta de espaço e restauração parcial testados.

## Estratégia candidata (ainda não escolhida)

- Registrar operação com idempotency key, hash, versão esperada e fase (preparada/confirmada).
- Escrever conteúdo imutável ou temporário verificável; forçar integridade conforme garantias do filesystem.
- Confirmar no banco a referência da versão e marcar journal como confirmado.
- Atualizar apontador de leitura somente quando houver estado coerente.
- Recovery reexecutável por ID, auditável e conservador.

Comparar custo desse modelo com armazenar corpos temporariamente no PostgreSQL, usar um outbox de arquivos ou snapshots de revisão. **A decisão ainda depende da [ADR 0002](../DECISOES/0002-consistencia-de-notas.md)**; não afirmar que journal já existe.

## Concorrência futura

Edição entre duas abas: cliente envia versão atual; em conflito HTTP 409, preservar rascunho local e permitir comparar/mesclar. Edição colaborativa simultânea real, CRDT e PWA/offline ficam fora do MVP. Lock do banco não serve como protocolo de sincronização entre dispositivos desconectados.

## Portabilidade

O UUID físico é detalhe interno, não caminho lógico de exportação. Para exportar cofre interoperável é necessário mapear hierarquia, títulos e anexos a caminhos Markdown estáveis, tratar nomes duplicados, normalização Unicode, links relativos e nomes inválidos em diferentes sistemas de arquivos. Não executar extração ZIP sem sanitização e limites. Ver [editor/importação](EDITOR_E_INTEROPERABILIDADE.md).

## Testes

Criar cenários repetíveis para crash após escrita, antes do commit, arquivo ausente, hash diferente, versão stale, duas edições simultâneas e leitura sem permissão. A recuperação deve emitir apenas metadados não sensíveis e evidências de integridade. Dependências: [#3](https://github.com/caiotomaza/cianita/issues/3) e [backup](../INFRAESTRUTURA/BACKUP_E_RESTAURACAO.md).
