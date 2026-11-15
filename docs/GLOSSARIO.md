# Glossário e convenções

| Termo | Significado na Cianita |
| --- | --- |
| **Instância** | Instalação administrativa única da Cianita, incluindo aplicação, banco, cache e volumes |
| **Cofre (vault)** | Unidade de isolamento lógico e autorização de notas, membros e anexos |
| **Nota** | Documento textual Markdown identificado internamente por UUID |
| **Membro** | Usuário associado a um cofre por uma função (role) |
| **Proprietário** | Usuário associado ao campo `vaults.owner_id`; titular do cofre na base |
| **Editor / leitor** | Papéis atuais no pivot `vault_user`: `editor` / `viewer` |
| **Markdown** | Formato-fonte textual `.md`; não confundir com HTML renderizado |
| **Frontmatter** | Metadados YAML incorporados ao início da nota; suporte semântico planejado |
| **Wikilink** | Referência textual no formato `[[Outra nota]]`, ainda não resolvida pela aplicação |
| **Roundtrip** | Exportar e reimportar e verificar preservação documentada do conteúdo e anexos |
| **Storage privado** | Volume acessado pela aplicação após autorização; não deve ser público |
| **Snapshot consistente** | Conjunto de banco e arquivos referente ao mesmo ponto lógico no tempo |
| **RPO** | Janela máxima de dados que a organização aceita perder na recuperação |
| **RTO** | Duração máxima aceitável para restaurar o serviço |
| **ADR** | Registro de Decisão Arquitetural (contexto, decisão, consequências, status) |
| **MVP** | Produto mínimo utilizável de acordo com critérios de aceite, não apenas protótipo |
| **CI** | Integração contínua: validação automatizada de mudanças |
| **CD** | Entrega/implantação contínua: processo de publicação após aprovação e gates |
| **SLO** | Objetivo mensurável de serviço, não garantia já estabelecida |
| **IDOR** | Acesso indevido a recurso por manipulação de identificador |
| **CSRF** | Ataque de requisição forjada com sessão autenticada |
| **XSS** | Execução de conteúdo malicioso no navegador por renderização insegura |
| **SSRF** | Uso indevido do servidor para realizar requisições a destinos internos |
| **Webhook** | Notificação HTTP disparada pela aplicação para consumidor externo |
| **Idempotência** | Reprocessar uma operação/evento sem gerar efeitos duplicados indevidos |
| **P0/P1/P2/P3** | Prioridades conforme [requisitos](ESCOPO_E_REQUISITOS.md) |

Convenções: documentos em português brasileiro; identificadores de código, HTTP, comandos e tabelas conservam os nomes implementados (em inglês). A expressão **planejado** nunca implica que uma feature esteja disponível.
