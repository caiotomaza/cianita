# Proteção de dados, logs e retenção

## Classificação de dados

| Classe | Exemplos | Tratamento |
| --- | --- | --- |
| Público | Documentação do projeto, exemplos fictícios | Pode ser versionado |
| Dados de conta | Nome, e-mail, timestamps | Minimização, autorização e retenção explícita |
| Privado | Corpos Markdown, anexos, títulos de notas, histórico | Isolamento por cofre; não colocar em logs |
| Segredo | `APP_KEY`, `DB_PASSWORD`, tokens, chaves de assinatura | Nunca no repositório, rotação e guarda separada |
| Operacional | Métricas, health, IDs e contadores | Redaction e acesso administrativo restrito |

## Políticas propostas

- **Minimização:** coletar apenas metadados necessários; não fazer rastreamento/telemetria externa por padrão.
- **Acesso:** títulos e caminhos também podem ser sensíveis; APIs, logs e exportações respeitam permissões.
- **Auditoria:** registrar ação, ator, cofre, momento, resultado e correlation ID; evitar corpo do conteúdo, tokens, cabeçalhos sensíveis e payloads íntegros.
- **Retenção:** soft delete não é eliminação definitiva. Estabelecer períodos de retenção configuráveis para versões, lixeira, backups, logs e eventos; não existem prazos definidos na alpha.
- **Exportação:** separar pacote de cofre (Markdown/anexos) de backup completo (metadados, banco, config e segredos protegidos).
- **Backups:** criptografia, controle de acesso e cópia fora do host quando implementados; exclusão do dado original não elimina automaticamente backups retidos.
- **Privacidade e legislação:** operador define responsabilidades, base legal e prazo de retenção conforme sua jurisdição; a Cianita não fornece garantia automática de conformidade jurídica.

## Estado verificado

- `.env` está ignorado pelo Git; segredos precisam ser preenchidos pelo operador.
- Corpo da nota usa storage privado e API autenticada.
- Middleware `no-store` cobre respostas de `/api/*` na camada HTTP.
- Logs operacionais saem para `stderr` por padrão no exemplo.
- **Não há** painel de retenção, expurgo de dados, exportação de conta, criptografia de backup, auditoria detalhada ou relatório de conformidade.

## Incidentes

Se houver suspeita de exposição: limitar acesso, preservar logs **sem copiar notas/segredos**, revogar sessões/tokens afetados, avaliar integridade de volumes e restaurar somente depois de identificar causa e última cópia íntegra. Procedimento operacional detalhado: [incidentes](../INFRAESTRUTURA/OPERACAO_E_INCIDENTES.md).
