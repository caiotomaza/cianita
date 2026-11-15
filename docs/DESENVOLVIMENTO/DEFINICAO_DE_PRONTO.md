# Definição de pronto (DoD) e gates de release

O checklist distingue feature compilada, MVP demonstrável e produção segura. Um PR com CI verde não é automaticamente release.

## DoD de issue/feature

- [ ] Issue com motivação, RF/RNF, escopo, riscos e critérios observáveis.
- [ ] Implementação revisável sem duplicação ou abstração desnecessária.
- [ ] Auth, autorização e validação revisadas no backend.
- [ ] Testes positivos, negativos, de concorrência/falha conforme impacto.
- [ ] CI PHP/TypeScript/infra relevante aprovada no commit exato.
- [ ] Migrations, rollback e impactos sobre storage descritos.
- [ ] Interface cobre erros, acessibilidade, estados e responsividade.
- [ ] `docs/` e ADRs relevantes atualizados no mesmo PR.
- [ ] Sem segredos, dados privados ou logs sensíveis no diff.
- [ ] PR para branch correta, revisão e merge conforme política.

## Gate de MVP demonstrável

- [ ] Login seguro e isolamento efetivo por cofre.
- [ ] Gestão de membros com revogação/sem escalada de privilégios.
- [ ] Editor, organização, anexos e exportação/importação interoperável.
- [ ] Lixeira/restauração de notas na interface e conflitos sem perda silenciosa.
- [ ] Testes E2E, acessibilidade, compatibilidade e documentação dos limites.
- [ ] Bloqueios P0 fechados **ou** comunicação explícita de protótipo experimental, sem uso como único repositório real.

## Gate de release estável de produção

- [ ] Dependências travadas, auditadas e imagens identificadas por digest.
- [ ] Consistência PostgreSQL/Markdown após crash comprovada.
- [ ] Backup **e restauração em host limpo** testados e documentados.
- [ ] RPO/RTO definidos e medidos no exercício de recuperação.
- [ ] HTTPS, cookies, proxy, secrets e autenticação revisados no ambiente final.
- [ ] Testes de isolamento, segurança, regressão e carga adequados.
- [ ] Métricas, alertas, runbooks e responsáveis operacionais definidos.
- [ ] Migrações e plano de rollback aprovados, sem volume removal.
- [ ] Regras de proteção reais de `main`/`develop` ativadas.
- [ ] CI aprovada na referência da release, revisão humana, tag e notas de versão.
- [ ] Operador aprova janela de implantação e contingência.

**Regra:** sem prova dos gates críticos, a aplicação continua experimental, mesmo com CI verde.

## Gate documental

- [ ] Links relativos sem quebras, com [índice central](../README.md) atualizado.
- [ ] Termos e RF/RNF consistentes; decisões propostas explicitadas como propostas.
- [ ] Toda afirmação de entrega corresponde a código, testes ou evidência de CI.
- [ ] Issues, riscos, documentação e instruções operacionais rastreáveis.

Ver [qualidade](TESTES_E_QUALIDADE.md), [roadmap](../ROADMAP.md) e [riscos](../REGISTRO_DE_RISCOS.md).
