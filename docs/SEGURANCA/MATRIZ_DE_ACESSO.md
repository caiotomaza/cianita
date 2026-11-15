# Matriz de permissões por cofre

Esta matriz **distingue o que funciona** do modelo de permissões desejado. O sistema ainda não tem interface para gerir membros.

## Permissões na alpha (verificadas)

| Operação | Proprietário (`owner_id`) | Editor (`editor`) | Leitor (`viewer`) | Não membro |
| --- | --- | --- | --- | --- |
| Listar o cofre na própria listagem | Sim | Sim | Sim | Não |
| Ler/listar notas do cofre | Sim | Sim | Sim | Não |
| Criar/editar/arquivar notas | Sim | Sim | Não | Não |
| Criar **novo cofre próprio** | Sim | Sim | Sim | Sim |
| Excluir cofre (policy) | Autorizado na policy | Não | Não | Não |
| Excluir cofre (rota/UI) | **Não implementado** | — | — | — |
| Convidar/alterar/remover membros | **Não implementado** | — | — | — |
| Exportar/importar/anexos/tokens | **Não implementado** | — | — | — |

A linha “criar novo cofre próprio” indica que **qualquer usuário autenticado** pode criar cofre de sua propriedade, sem depender de seu papel em cofres de terceiros. Uma policy permitir a ação não implica que exista rota para executá-la.

## Modelo proposto

| Operação | Proprietário | Admin de cofre | Editor | Leitor | Automação |
| --- | --- | --- | --- | --- | --- |
| Ler notas e anexos | Sim | Sim | Sim | Sim | Com scope read |
| Criar/alterar notas | Sim | Sim | Sim | Não | Com scope write |
| Importar | Sim | Sim, se delegado | A definir | Não | Não por padrão |
| Exportar dados do cofre | Sim | Delegável | A definir | A definir | Não por padrão |
| Gerenciar membros | Sim | Sim, limitado | Não | Não | Não |
| Transferir propriedade | Sim, com confirmação | Não | Não | Não | Não |
| Remover cofre | Sim, com proteção/backup | Não | Não | Não | Não |
| Criar/revogar tokens próprios | Sim | Sim, se autorizado | Restrito | Restrito | Não |

**“A definir” não é autorização implícita**; até decisão formal, negar. O admin da **instância** não deve herdar automaticamente leitura de todos os cofres privados.

## Testes essenciais

- Usuário A tenta ler ID de nota de cofre B por rota do cofre A.
- Usuário não membro tenta listar, ler, editar e arquivar.
- Leitor tenta operações de escrita/convite.
- Editor tenta alterar papel ou transferir propriedade.
- Revogação ocorre entre autenticação inicial e download/job.
- Token futuro com escopo read tenta write ou outro cofre.
- URL exportada e cache não contornam autorização.

Critério de pronto: comportamento no servidor + testes automatizados; não confiar no controle visual do frontend. Ver [auth](../ARQUITETURA/AUTENTICACAO_E_AUTORIZACAO.md) e [#6](https://github.com/caiotomaza/cianita/issues/6).
