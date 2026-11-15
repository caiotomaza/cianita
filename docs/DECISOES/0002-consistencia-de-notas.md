# ADR 0002 — Consistência PostgreSQL e Markdown

**Status:** decisão adotada para prova experimental alpha; **não homologada** para dados importantes. [Issue #3](https://github.com/caiotomaza/cianita/issues/3).

## Problema e alternativas

PostgreSQL e filesystem não compartilham transação. A implementação anterior sobrescrevia o arquivo antes de confirmar metadados; uma compensação em catch não cobria crash.

| Alternativa | Avaliação |
| --- | --- |
| Journal PostgreSQL e objetos Markdown imutáveis com SHA-256 | **Escolhida para prova experimental:** preserva versões e permite reconciliação conservadora; aumenta storage e complexidade |
| Corpo PostgreSQL, exportando Markdown | Atomicidade mais simples, porém muda a arquitetura de Markdown privado |
| Sobrescrever arquivo e compensar | Rejeitada: não cobre SIGKILL ou commit incerto |
| Snapshots sem journal | Insuficiente para detectar e reparar uma operação recente |

## Protocolo adotado para notas novas

1. **PREPARED:** transação PostgreSQL grava uma revisão contendo cofre, nota, operação, versão-base, versão-alvo, título e SHA-256. Nota nova inicia com versão 0 e sem referência publicada. Edição bloqueia linha, verifica hash da revisão atual e exige versão recebida e ausência de operação preparada.
2. **WRITE:** escrever temporário privado e publicar um objeto novo, identificado por UUID de revisão, sob vaults/{cofre}/notes/{nota}/revisions/. Nenhum objeto anterior é sobrescrito. Validar os bytes e SHA-256 após publicação.
3. **COMMITTED:** segunda transação verifica estado preparado, escopo, versão-base e hash; atualiza simultaneamente título, versão, referência atual e estado da revisão.
4. **CRASH:** operação pode permanecer preparada, com arquivo presente ou ausente. A versão antiga continua apontada; não há exclusão nem compensação destrutiva. Pode existir commit com resposta HTTP perdida.
5. **RECOVERY:** comando **php artisan notes:reconcile** faz somente inspeção; com **--apply**, só confirma revisão preparada com objeto íntegro e versão-base atual. Reexecução não muda versão confirmada. Arquivo ausente, hash divergente, versão posterior ou nota arquivada geram investigação; não reparar por adivinhação.
6. **ARCHIVE/RESTORE:** alteram somente metadados em transação, com lock, versão e validação do objeto. Incrementam versão lógica, não removem Markdown. Autorização no backend e vínculo por cofre são obrigatórios.

Invariantes: revisões anteriores são imutáveis; o apontador de leitura só pode referenciar revisão confirmada do mesmo cofre/nota; erros retornam 409/503 sem descarte automático do corpo local; reconciliador não exclui órfãos, não sobrescreve versão nova e nunca presume que uma requisição recebeu confirmação.

## Restrições e trade-offs

O journal não substitui backup. Hash detecta corrupção, mas não reconstrói arquivo ausente. O driver filesystem do Laravel não prova fsync de conteúdo/diretório em queda de energia. Uma operação interrompida antes da gravação durável pode deixar corpo recuperável apenas no rascunho do navegador. Storage aumenta enquanto não houver política segura de retenção e garbage collection. Nada nesta ADR promete compatibilidade com Obsidian.

## Migração e recuperação de notas anteriores

**Sem migração automática de dados.** Migration adiciona esquema; o Markdown legado permanece legível no caminho anterior, mas gravações são bloqueadas com 503 até migração supervisionada. Protocolo planejado:

1. Congelar gravações; capturar **backup PostgreSQL + volume no mesmo ponto lógico** e verificar restauração em ambiente isolado.
2. Inventariar notas ativas/arquivadas e hashes, sem incluir corpo em logs.
3. Sob lock e pré-verificações, **copiar** cada arquivo para novo objeto imutável, validar hash/leitura e só então publicar referência transacional. Nunca mover nem excluir original.
4. Testar contagem, hash, versionamento, permissão, falha de processo, restauração e reversão em conjunto sintético.
5. Recuperar falha por roll-forward investigado ou restauração do snapshot pareado; **git revert** e rollback de schema isolado não recuperam conteúdo.

**A ferramenta de migração legada ainda não existe.** Não usar migrations destrutivas ou scripts improvisados sobre dados existentes.

## Gates pendentes

Os testes instrumentados injetam exceções em pontos de corte. Eles não são substitutos de SIGKILL real, falha de fsync, disco cheio, corrupção física ou restauração em host limpo. Exigir PostgreSQL 17 em CI, testes de crash independentes, migração legada ensaiada e evidência de hash para concluir a issue. Permanecem [#2](https://github.com/caiotomaza/cianita/issues/2) (lockfiles) e [#4](https://github.com/caiotomaza/cianita/issues/4) (backup/restore). Sem esses gates, apenas laboratório com dados sintéticos.
