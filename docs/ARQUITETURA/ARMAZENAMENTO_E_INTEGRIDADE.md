# Armazenamento, integridade e concorrência

**Status:** nova persistência experimental em revisão; não homologada para dados importantes.

## Duas formas de armazenar notas

- **Revisão nova:** vaults/{cofre}/notes/{nota}/revisions/{revisao}.md no disk local privado. Cada revisão é um objeto imutável separado, publicado a partir de temporário.
- **Legado:** vaults/{cofre}/notes/{nota}.md preservado para leitura. Edição bloqueada até backup e migração supervisionada.

PostgreSQL guarda em **notes** a versão lógica e o identificador da revisão confirmada. **note_revisions** registra o estado preparado/confirmado, versão-base, versão-alvo, título e hash SHA-256. Criar começa com versão 0 e revisão não publicada; a listagem ignora notas nessa situação.

## Sequência e reconciliação

Após o primeiro commit do journal (prepared), o backend grava um objeto novo, verifica SHA-256 e, numa segunda transação sob lock, publica a referência e marca a revisão committed. Nenhuma escrita posterior modifica a versão anterior. Antes de atualizar, lê e verifica o objeto atual.

Em falha, um objeto pode estar ausente ou existir sem referência atual. Nova escrita sobre nota pendente retorna 409; leitura de revisão ausente/divergente retorna 503. **notes:reconcile** apenas inspeciona; **notes:reconcile --apply** promove operação preparada somente se hash, cofre, versão-base e estado coincidem. Sem limpeza automática ou overwrite.

DELETE exige a versão recebida, arquiva só metadados e incrementa versão; POST de restore usa lock, exige versão válida e autorização do cofre. Não há exclusão física de notas pelo fluxo HTTP.

## Limites de garantia

Não há atomicidade entre banco e filesystem, nem garantia comprovada de fsync/queda de energia. Reconciliação nunca inventa bytes perdidos: integridade inválida exige investigação e backup pareado. Revisões legadas ainda não podem ser migradas pela aplicação. Não prometer recuperação de dados sem execução de testes PostgreSQL e crash real.

Veja [ADR 0002](../DECISOES/0002-consistencia-de-notas.md), [modelo de dados](MODELO_DE_DADOS.md) e [runbook](../RUNBOOK.md).
