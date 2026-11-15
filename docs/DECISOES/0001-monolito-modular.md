# ADR 0001 — Monólito modular e persistência de notas

**Status:** aceita para a fase alpha

## Contexto

A Cianita é uma aplicação web self-hosted com usuários e cofres isolados, editor Markdown e futuras integrações n8n. A necessidade inicial é simplicidade operacional com autorização consistente.

## Decisão

- Utilizar Laravel como aplicação monolítica modular, com Vue/TypeScript na interface.
- Manter regras de negócio em serviços/actions quando necessário; controllers validam, autorizam e delegam; policies decidem acesso.
- PostgreSQL é fonte autoritativa de usuários, permissões e metadados.
- Arquivos Markdown são fonte autoritativa dos corpos de notas, em volume privado; nomes de arquivo baseados em UUID, sem reutilizar entrada de usuário como caminho físico.
- Redis serve apenas para cache e filas, nunca como fonte única de conteúdo.
- Implantar via Docker Compose com HTTP restrito a loopback por padrão.

## Consequências

Menor custo de implantação e auditoria que microsserviços, mas as mudanças entre PostgreSQL e filesystem não são transacionais. A versão alpha limita o risco com bloqueio e controle de versão; **não garante atomicidade após falha de processo ou máquina**. Exigir journal/reconciliação, testes de falha, backup consistente e verificação de restauração antes de produção.

A compatibilidade com diretórios de cofres do Obsidian **não existe ainda**; importação/exportação deve preservar caminhos, imagens, referências e configurações quando implementada, com roundtrip testado.

## Alternativas rejeitadas nesta fase

- Conteúdo exclusivamente em banco: reduz interoperabilidade nativa e facilita lock-in.
- Microsserviços desde o início: multiplicam pontos de falha e custos operacionais.
- Sincronização distribuída em tempo real no MVP: exige modelo de conflito que ainda não foi especificado.
