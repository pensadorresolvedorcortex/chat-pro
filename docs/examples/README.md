# Catálogo de exemplos do app

Este diretório reúne payloads de referência usados para sincronizar o app Flutter com o painel Strapi. Cada arquivo JSON está
pronto para alimentar o Codex, seeds Strapi ou fixtures de testes, garantindo que a home siga a experiência do QConcursos.com
(Planos Pix, destaque de alunos, desafios, mentoria, biblioteca e suporte).

| Área | Arquivo | Destaques |
| --- | --- | --- |
| Usuários e personas | [`usuarios.json`](usuarios.json) | Perfis premium, planos grátis aprovados, fila de aprovação e papéis administrativos (`super-admin`, mentor). |
| Home / Dashboard | [`dashboard_home.json`](dashboard_home.json) | Hero de boas-vindas, métricas semanais, destaques Pix, lives e notícias sincronizadas com os mesmos IDs de planos e usuários. |
| Planos Pix | [`planos.json`](planos.json) | Planos pagos e Planos Grátis para Alunos com aprovação do super admin e chave Pix copia-e-cola/QR code. |
| Assinaturas Pix | [`assinaturas_pix.json`](assinaturas_pix.json) | Histórico com status, chave Pix e timestamps alinhados ao painel. |
| Cobranças Pix | [`cobrancas_pix.json`](cobrancas_pix.json) | Casos pendente, pago e expirado, vinculados aos mesmos usuários e assinaturas. |
| Chave Pix principal | [`pix_chave_principal.json`](pix_chave_principal.json) | Payload reutilizável para exibir copiar chave e QR code. |
| Onboarding | [`onboarding.json`](onboarding.json) | Fluxo completo com objetivos, hero cards e chamadas para o paywall Pix. |
| Perfil & Configurações | [`perfil.json`](perfil.json), [`configuracoes.json`](configuracoes.json) | Preferências, dark mode, privacidade e integrações sociais. |
| Questões | [`questoes.json`](questoes.json) | Enunciados, alternativas, explicações e estatísticas estilo QConcursos. |
| Resoluções | [`resolucoes_questoes.json`](resolucoes_questoes.json) | Histórico de respostas amarrado aos usuários recém-adicionados (`user-aluno-marina`, `user-aluno-diego`). |
| Filtros | [`filtros.json`](filtros.json) | Filtros salvos simples/avançados com contagem de questões. |
| Cadernos | [`cadernos.json`](cadernos.json) | Progresso e status por questão para retomar estudos. |
| Simulados | [`simulados.json`](simulados.json) | Modalidade express/customizada com estatísticas em tempo real. |
| Metas | [`metas.json`](metas.json) | Metas semanais por usuário com progresso percentual. |
| Desafios & Comunidade | [`desafios.json`](desafios.json) | Maratona TJ-CE com ranking e fila de discursivas. |
| Biblioteca | [`biblioteca.json`](biblioteca.json) | Mapas mentais, checklists e PDFs com tags e status. |
| Cursos & Aulas | [`cursos.json`](cursos.json) | Trilhas, aulas em alta e recomendações por objetivo. |
| Mentorias | [`mentorias.json`](mentorias.json) | Slots reservados/abertos com mentor oficial e IDs de usuários. |
| Lives | [`lives.json`](lives.json) | Aulões ao vivo com contagem de inscritos e materiais extras. |
| Desempenho | [`desempenho.json`](desempenho.json) | KPIs de 30 dias, ranking e disciplina destaque. |
| Notificações | [`notificacoes.json`](notificacoes.json) | Push segmentados para premium e transmissões de live. |
| NPS | [`nps.json`](nps.json) | Pesquisas, notas e follow-ups para o dashboard de satisfação. |
| Suporte | [`suporte.json`](suporte.json) | Tickets com timeline, tags e responsáveis. |

> Sempre execute [`python ../../scripts/validate_sync.py`](../../scripts/validate_sync.py) após alterar qualquer exemplo para
confirmar que todos os vínculos (`userId`, `planoId`, `assinaturaId`) permanecem sincronizados entre o app e o painel.
