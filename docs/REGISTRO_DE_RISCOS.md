# Registro de riscos do projeto

Classificação qualitativa inicial: **crítico** (perda/vazamento de dados), **alto** (impede operação confiável), **médio** (atraso/retrabalho) e **baixo** (impacto limitado). Não são probabilidades mensuradas. Proprietário indicado por **função**, não por pessoa.

| ID | Risco e situação | Severidade | Mitigação e critério de encerramento | Dono |
| --- | --- | --- | --- | --- |
| R-01 | Perda/divergência entre metadata DB e arquivo Markdown (aberto) | Crítico | [#3](https://github.com/caiotomaza/cianita/issues/3), journal, reconciliação e testes de falhas | Backend |
| R-02 | Ausência de restauração completa comprovada (aberto) | Crítico | [#4](https://github.com/caiotomaza/cianita/issues/4), snapshot consistente, checksum e restore em host limpo | Operação |
| R-03 | Autorização futura incompleta em anexos/exportações/jobs (risco de expansão) | Crítico | Guardas em todas as operações e testes IDOR por cofre | Segurança/backend |
| R-04 | Dependências não travadas (aberto) | Alto | [#2](https://github.com/caiotomaza/cianita/issues/2), lockfiles e verificação de cadeia de suprimentos | DevOps |
| R-05 | Exposição pública da alpha, sessões/cookies sem hardening de HTTPS | Alto | Manter loopback e rede privada; testar proxy/HTTPS antes de acesso remoto | Operação |
| R-06 | Importação maliciosa (ZIP bomb/path traversal, HTML, anexo executável) | Crítico | Limites, caminhos canônicos, sandbox e testes adversariais antes da feature | Backend |
| R-07 | Webhooks/tokens n8n permitirem acesso lateral | Crítico | Escopos por cofre, assinatura, antirreplay, auditoria, rate limit | Integrações |
| R-08 | Incompatibilidade com arquivos Obsidian e perda de referências | Alto | Matriz de formatos, roundtrip e indicação explícita de limitações | Produto |
| R-09 | Edição simultânea descartar conteúdo | Alto | Versionamento 409 atual; no futuro definir histórico e política de conflito | Backend/editor |
| R-10 | Prometer produção por ter CI verde | Alto | Gatilhos separados: CI ≠ homologação ≠ restore ≠ release; checklist bloqueante | Maintainer |
| R-11 | Proteções de branches não ativadas (aberto) | Médio | Configurar required checks e PR obrigatório no GitHub | Maintainer |
| R-12 | Uso elevado de armazenamento/anexos sem quota | Médio | Cotas, limites de upload, métricas e alertas | Operação |
| R-13 | Licenciamento e direitos de terceiros em componentes de UI | Médio | Atribuições e revisão de licenças; não copiar identidade do Obsidian | Maintainer |

## Rotina

Cada risco deve ter issue/controle verificável, status e evidência de encerramento. Revisar antes de cada release; reabrir se a evidência deixar de valer. Novas funcionalidades de importação, exportação ou n8n exigem reavaliação de R-03, R-06 e R-07. Ver [ameaças](SEGURANCA/MODELO_DE_AMEACAS.md) e [checklist de release](DESENVOLVIMENTO/DEFINICAO_DE_PRONTO.md).
