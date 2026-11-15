# Editor, notas e interoperabilidade

## Implementado

O frontend Vue apresenta seletor de cofres, lista de notas e edição de texto Markdown com `textarea` e botão de salvar. Há alerta de descarte de alterações, indicação de mudanças não salvas e controle de versão na API. **Não** há CodeMirror, preview Markdown, pastas, backlinks, tags funcionais, anexos ou renderizador sanitizado.

## Experiência-alvo (planejada)

- Editor CodeMirror 6 com realce Markdown, atalhos, navegação por teclado e busca na nota.
- Visualização de Markdown com HTML sanitizado e URLs autorizadas; usar CSP e não executar scripts de notas.
- Organizador de diretórios e painel de propriedades/frontmatter.
- Links `[[Nota]]`, backlinks e tags, com resolução preservando nomes originais.
- Escrita responsiva, indicadores de estado, recuperação de rascunho e conflitos sem perda silenciosa.
- Anexos com caminho privado, prévia para formatos seguros e download autenticado.

## Modelo de arquivo interoperável

Markdown UTF-8 deve sobreviver à importação/exportação sem transformação destrutiva. Em caso de conversão opcional, oferecer preview/diff e manifesto de mudanças. Resolver caminhos relativos e links somente dentro do cofre autorizado. Colisões de nome, caracteres inválidos, diferenças entre Linux/Windows e links para arquivos ausentes devem ser relatados.

O formato interno atual `vaults/{id}/notes/{uuid}.md` **não equivale** à árvore do Obsidian; uma camada de caminho lógico precisa ser projetada. A pasta `.obsidian` contém preferências de outro aplicativo e não deve ser executada ou tratada automaticamente como configuração de sistema da Cianita.

## Matriz de compatibilidade (estado)

| Elemento | Hoje | Meta futura |
| --- | --- | --- |
| Corpo `.md` texto UTF-8 | Sim | Preservar conteúdo |
| Nome e pasta originais | Não | Manter com mapeamento lógico |
| Links relativos e `[[wikilinks]]` | Texto literal | Resolver/atualizar preservando sintaxe |
| YAML frontmatter | Texto literal | Interpretar sem destruir o original |
| Anexos/imagens | Não | Importar, referenciar e exportar |
| Extensões e plugins Obsidian | Não | **Não prometer compatibilidade** |
| Canvas/protocolos proprietários | Não | Avaliar separadamente |

## Segurança de importação/exportação

Antes de importar arquivos compactados: controlar tamanho descompactado e contagem de entradas; rejeitar paths absolutos, `..`, links simbólicos que escapem do cofre, arquivos especiais e nomes conflitantes; verificar hashes/MIME/limites. A importação deve ser transacional ou recuperável, com preview e logs sem conteúdo privado. Exportações privadas exigem autorização atual no momento do download e expiração curta do artefato.

**Critério de aceite:** roundtrip com fixtures de teste (pastas, links, Unicode, imagens, frontmatter, colisões, arquivo malicioso). Registrar incompatibilidades conhecidas em vez de declarar paridade com Obsidian. Ver [#5](https://github.com/caiotomaza/cianita/issues/5).
