# Dados de demonstração do JuntaPlay

Este repositório inclui um semeador opcional (`Gerar dados de demonstração` na tela **Importação e Páginas Automáticas** do painel WordPress) para ajudar a testar buscas, aprovações e notificações dos grupos. O botão cria usuários fictícios, associações e grupos populares inspirados nas referências fornecidas.

> **Senha padrão**: `JuntaPlay#2024` para todos os perfis criados pelo semeador.

## Usuários criados

| Login              | Nome exibido          | E-mail                             | Função         | Avatar (URL)                         |
| ------------------ | --------------------- | ---------------------------------- | -------------- | ------------------------------------ |
| demo.superadmin    | Equipe JuntaPlay      | demo.superadmin@example.com        | administrator  | https://i.pravatar.cc/300?img=12     |
| ana.streaming      | Ana Streaming         | ana.streaming@example.com          | subscriber     | https://i.pravatar.cc/300?img=47     |
| bruno.sound        | Bruno Sound           | bruno.sound@example.com            | subscriber     | https://i.pravatar.cc/300?img=15     |
| carla.series       | Carla Séries          | carla.series@example.com           | subscriber     | https://i.pravatar.cc/300?img=32     |
| davi.cursos        | Davi Cursos           | davi.cursos@example.com            | subscriber     | https://i.pravatar.cc/300?img=38     |
| edu.livros         | Edu Livros            | edu.livros@example.com             | subscriber     | https://i.pravatar.cc/300?img=54     |
| fernanda.office    | Fernanda Office       | fernanda.office@example.com        | subscriber     | https://i.pravatar.cc/300?img=68     |
| gustavo.games      | Gustavo Games         | gustavo.games@example.com          | subscriber     | https://i.pravatar.cc/300?img=23     |
| helena.segura      | Helena Segura         | helena.segura@example.com          | subscriber     | https://i.pravatar.cc/300?img=9      |
| igor.ai            | Igor AI               | igor.ai@example.com                | subscriber     | https://i.pravatar.cc/300?img=5      |
| juliana.boloes     | Juliana Bolões        | juliana.boloes@example.com         | subscriber     | https://i.pravatar.cc/300?img=61     |

Caso um desses logins já exista no ambiente, o semeador preserva a conta original e apenas reutiliza o ID ao criar os grupos.

## Grupos criados

| Serviço/Grupo                     | Categoria          | Responsável         |
| --------------------------------- | ------------------ | ------------------- |
| YouTube Premium Família           | Vídeo e streaming  | ana.streaming       |
| MUBI Cinemateca                   | Vídeo e streaming  | carla.series        |
| NBA League Pass Squad             | Jogos e esportes   | gustavo.games       |
| PlayPlus Família                  | Vídeo e streaming  | (usuário atual ou demo.superadmin) |
| Spotify Premium Família           | Música e áudio     | bruno.sound         |
| Tidal HiFi Max Collective         | Música e áudio     | bruno.sound         |
| Brainly Premium Squad             | Cursos e educação  | davi.cursos         |
| Ubook Audiobooks Club             | Leitura e revistas | edu.livros          |
| Super Interessante Digital        | Leitura e revistas | edu.livros          |
| Veja Saúde Coletivo               | Leitura e revistas | edu.livros          |
| Perplexity Pro Research Hub       | Ferramentas de IA  | igor.ai             |
| Canva Pro Studios                 | Escritório         | fernanda.office     |
| Google One 2TB Compartilhado      | Escritório         | fernanda.office     |
| ExpressVPN Global Access          | Segurança & VPN    | helena.segura       |
| Bolão Mega da Virada 2024         | Bolões e rifas     | juliana.boloes      |

Todos os grupos são públicos e utilizam os mesmos campos, regras e descrições vistos nas telas de referência. Alguns registros ficam com status **Em análise** para validar o fluxo de aprovação manual do super administrador.

## Como usar

1. Acesse **JuntaPlay → Importar & Gerar Páginas** no painel do WordPress.
2. Clique em **Criar dados de demonstração** e confirme a ação.
3. Verifique o aviso verde com o total de usuários e grupos criados. A senha padrão é exibida junto com a mensagem de sucesso.

Você pode executar o semeador novamente: contas ou grupos já existentes serão ignorados para evitar duplicidade. Para ambientes de produção, recomenda-se remover os dados de teste manualmente após a validação.
