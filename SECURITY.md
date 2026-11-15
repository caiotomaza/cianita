# Segurança — Cianita

## Estado de segurança

A Cianita é **experimental (alpha)**. Ainda não passou por auditoria de segurança nem validação de backup/restauração em produção. Não exponha a instância diretamente na internet; prefira acesso local, HTTPS por proxy confiável ou rede privada Tailscale.

## Reporte responsável

Para vulnerabilidades que permitam acesso indevido, perda de notas, bypass de permissões ou execução de código, evite abrir uma issue pública com detalhes exploráveis, notas privadas ou credenciais. Use o recurso de reporte privado de vulnerabilidade do GitHub, **caso esteja habilitado para este repositório**; caso contrário, solicite ao mantenedor um canal seguro antes de divulgar provas de conceito.

Não há SLA de atendimento publicado nesta fase.

## Política inicial

- O acesso a cofres e notas deve ser autorizado no servidor; filtros de frontend não constituem proteção.
- Tokens para integrações futuras exigirão escopos explícitos, rotação, expiração e revogação.
- Não versionar `.env`, `APP_KEY`, credenciais ou dumps; rotação de `APP_KEY` exige considerar dados criptografados existentes.
- PostgreSQL e Redis não devem ser publicados na rede do host.
- Volume de notas, banco, chave de aplicação e segredos de integrações devem ser incluídos em um procedimento consistente de recuperação, **ainda não implementado**.
- Markdown e uploads externos não são confiáveis: renderização deverá sanitizar HTML e bloquear URLs perigosas.
- É necessário revisar logs, backup, retenção e restore antes de implantar dados importantes.

Consulte [Arquitetura](docs/ARCHITECTURE.md) e [Roadmap](docs/ROADMAP.md).
