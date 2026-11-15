# Visão do produto

**Produto:** Cianita  
**Categoria:** plataforma web de conhecimento self-hosted com cofres Markdown  
**Situação:** conceito definido; fundação alpha implementada parcialmente.

## Problema

Usuários e equipes desejam centralizar anotações em Markdown sem terceirizar a custódia dos dados, com autenticação, separação de cofres, permissões, acesso via navegador e automação. Um editor local-first atende muito bem a escrita individual, mas não cobre automaticamente governança multiusuário e operação de servidor.

## Proposta de valor

A Cianita deve oferecer uma experiência de conhecimento **inspirada** em editores como Obsidian, acrescentando:

1. Cofres separados por finalidade, equipe ou usuário, com privilégios independentes.
2. Interface web responsiva, acessível em uma instalação Docker de propriedade do operador.
3. Conteúdo preservado como arquivos Markdown legíveis e exportáveis.
4. API e webhooks governados por escopos para automações (incluindo n8n).
5. Monitoramento, trilha de auditoria e recuperação confiável como requisitos operacionais — não recursos decorativos.

**Não é** produto oficial ou derivado do Obsidian; compatibilidade com plugins, temas ou sincronização do Obsidian **não é promessa**. O objetivo é interoperabilidade de arquivos, após testes.

## Personas e jornadas

| Persona | Objetivo | Jornada de referência |
| --- | --- | --- |
| Administrador da instância | Instalar, proteger, atualizar e recuperar | Provisiona instância → conta → métricas → backup/restore |
| Proprietário de cofre | Organizar dados e delegar acesso | Cria cofre → notas → convida membros → revoga acesso |
| Editor | Produzir conhecimento e relacionar conteúdos | Abre nota → edita Markdown → vincula notas → salva |
| Leitor | Consultar conhecimento autorizado | Entra → busca → lê → exporta se tiver permissão |
| Automação | Criar/consultar conteúdo sem conta interativa | Obtém token restrito → chama API → recebe webhook |

**Hoje:** há login por sessão, provisionamento de usuário pela CLI, criação de cofres e notas, papéis proprietário/editor/leitor e edição textual básica. Convites, busca, exportação, tokens, webhooks, backup e monitoramento avançado são futuros.

## Princípios de produto

- **Posse dos dados:** cópias exportadas devem ser úteis fora da Cianita.
- **Segurança entre cofres:** negação por padrão e checagem em toda rota/operação.
- **Confiabilidade antes de complexidade:** não introduzir HA, colaboração ao vivo ou sincronização offline antes de provar durabilidade e restore.
- **Operação transparente:** arquivos, esquemas, imagens, logs e procedimentos inspecionáveis.
- **Melhorias incrementais:** cada etapa entrega comportamento utilizável, testado e reversível.
- **Acessibilidade:** teclado, contraste, responsividade e semântica avaliados desde o MVP.
- **Sem dependência obrigatória de nuvem:** nenhuma conta SaaS para executar o núcleo.

## Escopo positivo e exclusões

O MVP pretende oferecer autenticação, cofres com papéis, edição Markdown competente, estruturas de pastas, anexos, importação/exportação e recuperação de notas. A operação confiável introduzirá backups validados, monitoramento, auditoria e integrações.

Fora do MVP: edição simultânea em tempo real, sincronização offline multidispositivo, ecossistema de plugins compatível com o Obsidian, IA obrigatória, cluster Kubernetes, HA e busca semântica. Esses itens dependem de validação de demanda e novos ADRs.

## Métricas de sucesso (metas propostas, não resultados)

- **Qualidade:** suíte de isolamento entre cofres executada em cada PR; falhas críticas abertas impedem release.
- **Portabilidade:** testes de ida-e-volta entre importação/exportação preservam bytes de notas quando não há conversão declarada, e referências/anexos segundo matriz de compatibilidade.
- **Recuperação:** teste de restauração em ambiente limpo sem perda além do RPO aceito; definir RPO/RTO mensurados antes da release.
- **Operação:** healthchecks, alertas e auditoria não revelam conteúdos privados.
- **Uso:** tarefas centrais (criar, abrir, editar, organizar e exportar) passam por testes de ponta a ponta e de acessibilidade.

Não definir metas de desempenho numéricas sem carga representativa e máquina de referência. Decisões e prioridades: [requisitos](ESCOPO_E_REQUISITOS.md), [roadmap](ROADMAP.md) e [planejamento](PLANEJAMENTO.md).
