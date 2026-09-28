# Bloqueadores de entrada em produção

O repositório publicado é uma **fundação de desenvolvimento**, não um marketplace em produção. Não adicionar credenciais financeiras reais ou publicar pedidos até estes gates passarem:

- [ ] Processo empresarial e contrato do vendedor validados; política de categorias/produtos restritos.
- [ ] Autenticação com e-mail real, reset testado, MFA obrigatório para equipe da plataforma.
- [ ] Isolamento entre vendedores auditado, inclusive anexos, exportações e tarefas.
- [ ] Catálogo, estoque, reserva, compra e subpedidos com testes concorrentes.
- [ ] Comissão e responsabilidade fiscal aprovadas.
- [ ] Mercado Pago marketplace/OAuth vendedor testado; split 1:1 conforme contrato.
- [ ] Webhook com assinatura, idempotência, reconciliação e eventos fora de ordem testados.
- [ ] Frete por vendedor, regras de devolução e reembolso aceitas.
- [ ] Inventário LGPD, contratos, política de privacidade, retenção e atendimento de titulares.
- [ ] Secrets externos ao Git; HTTPS, firewall, MySQL sem porta pública.
- [ ] Backups cifrados externos, restauração cronometrada e alertas testados.
- [ ] Teste de migração e rollback seguro; locks de dependências versionados.
- [ ] Homologação separada de produção e smoke test pós-deploy.

VPS único não fornece alta disponibilidade. Aprovação comercial do seller **não** habilita vendas; PSP, logística, produtos e governança são gates adicionais.

