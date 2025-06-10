<?php
class Bot {
    private $states = [];
    private $getUserName;
    private $tree;

    public function __construct(callable $getUserName) {
        $this->getUserName = $getUserName;
        $this->tree = [
            'start' => [
                'text' => "Seja muito bem-vindo à Agência Privilége! 😄🇧🇷\n\nMeu nome é Patrícia!\nComo posso te ajudar hoje?\n\n1 - Já sou cliente\n2 - E-commerce\n3 - Site Institucional\n4 - Landing Page\n5 - Gestão de Redes Sociais\n6 - Hospedagem de Sites\n7 - Outras Soluções em Marketing",
                'options' => [
                    '1' => 'existingClient',
                    '2' => 'ecommerce',
                    '3' => 'site',
                    '4' => 'landing',
                    '5' => 'social',
                    '6' => 'hosting',
                    '7' => 'marketing'
                ]
            ],
            'existingClient' => [
                'getText' => function($state) { return "Olá {$state['name']}, como podemos te ajudar hoje?"; },
                'end' => true
            ],
            'ecommerce' => [
                'text' => "Ótima escolha! 😄\nPodemos criar ou otimizar o seu e-commerce para atender melhor às suas necessidades. Como podemos te ajudar?\n\n1 - Criar um novo e-commerce\n2 - Melhorar o e-commerce existente\n3 - Integrar com plataformas de pagamento\n4 - Outras Soluções para E-commerce",
                'options' => [
                    '1' => 'ecommerce_new',
                    '2' => 'ecommerce_improve',
                    '3' => 'ecommerce_payment',
                    '4' => 'handoff'
                ]
            ],
            'ecommerce_new' => [
                'text' => "Perfeito! Vamos criar um e-commerce robusto e funcional. Qual é o seu segmento?\n1 - Moda\n2 - Tecnologia\n3 - Alimentos e Bebidas\n4 - Outros",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff', '4' => 'handoff']
            ],
            'ecommerce_improve' => [
                'text' => "Podemos otimizar sua plataforma em várias áreas. Em qual delas você gostaria de focar?\n1 - Design e experiência do usuário\n2 - Performance e velocidade\n3 - Funcionalidades de vendas\n4 - SEO e marketing digital",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff', '4' => 'handoff']
            ],
            'ecommerce_payment' => [
                'text' => "Podemos integrar seu e-commerce com as principais plataformas de pagamento. Qual delas você gostaria de utilizar?\n1 - Pix\n2 - PagSeguro\n3 - MercadoPago\n4 - Outras",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff', '4' => 'handoff']
            ],
            'site' => [
                'text' => "Perfeito! 😄\nPodemos criar um site institucional elegante e funcional que represente a sua empresa. O que você precisa?\n\n1 - Criar um novo site institucional\n2 - Atualizar o site existente\n3 - Melhorar a visibilidade do site\n4 - Outras Soluções para Sites Institucionais",
                'options' => [
                    '1' => 'site_new',
                    '2' => 'site_update',
                    '3' => 'site_vis',
                    '4' => 'handoff'
                ]
            ],
            'site_new' => [
                'text' => "Vamos construir um site responsivo e atraente. Qual é o setor da sua empresa?\n1 - Saúde\n2 - Educação\n3 - Serviços financeiros\n4 - Outros",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff', '4' => 'handoff']
            ],
            'site_update' => [
                'text' => "Podemos modernizar seu site com um novo design e melhorias. Quais áreas você gostaria de atualizar?\n1 - Design e layout\n2 - Conteúdo e estrutura\n3 - Funcionalidade",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff']
            ],
            'site_vis' => [
                'text' => "Podemos trabalhar em SEO e outras estratégias para aumentar o tráfego do seu site. Qual das opções você deseja priorizar?\n1 - SEO On-page\n2 - SEO Off-page\n3 - Google Ads",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff']
            ],
            'landing' => [
                'text' => "Ótima escolha! 😄\nPodemos criar landing pages poderosas e convertedoras. O que você precisa?\n\n1 - Criar uma landing page de vendas\n2 - Criar uma landing page de captura de leads\n3 - Otimizar uma landing page existente\n4 - Outras Soluções para Landing Pages",
                'options' => [
                    '1' => 'landing_sales',
                    '2' => 'landing_leads',
                    '3' => 'landing_opt',
                    '4' => 'handoff'
                ]
            ],
            'landing_sales' => [
                'text' => "Vamos criar uma página otimizada para conversões. Que tipo de produto você está vendendo?\n1 - Produtos físicos\n2 - Produtos digitais\n3 - Serviços",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff']
            ],
            'landing_leads' => [
                'text' => "Podemos criar uma página focada em coletar dados valiosos. O que você oferece para capturar leads?\n1 - Ebooks gratuitos\n2 - Webinars\n3 - Consultorias gratuitas",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff']
            ],
            'landing_opt' => [
                'text' => "Podemos otimizar seu design e a taxa de conversão. Quais aspectos você gostaria de melhorar?\n1 - Design\n2 - Copywriting\n3 - Formulários e CTAs",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff']
            ],
            'social' => [
                'text' => "Perfeito! 😄\nA gestão estratégica das redes sociais pode aumentar a sua visibilidade. Como podemos ajudar?\n\n1 - Gerenciar redes sociais para minha empresa\n2 - Criar conteúdo para as redes sociais\n3 - Melhorar a presença nas redes sociais\n4 - Outras Soluções para Redes Sociais",
                'options' => [
                    '1' => 'social_manage',
                    '2' => 'social_content',
                    '3' => 'social_presence',
                    '4' => 'handoff'
                ]
            ],
            'social_manage' => [
                'text' => "Podemos cuidar do planejamento e conteúdo para suas redes sociais. Em quais você está interessado?\n1 - Instagram\n2 - Facebook\n3 - LinkedIn\n4 - Twitter",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff', '4' => 'handoff']
            ],
            'social_content' => [
                'text' => "Podemos criar conteúdo visual e textual para suas redes sociais. Que tipo de conteúdo você precisa?\n1 - Imagens e gráficos\n2 - Vídeos curtos\n3 - Textos e postagens",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff']
            ],
            'social_presence' => [
                'text' => "Podemos aumentar seu engajamento e seguidores. O que você deseja priorizar?\n1 - Engajamento\n2 - Crescimento de seguidores\n3 - Anúncios pagos",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff']
            ],
            'hosting' => [
                'text' => "Perfeito! 😄\nPodemos hospedar seu site de maneira segura e eficiente. Como podemos ajudar?\n\n1 - Hospedar meu site\n2 - Melhorar a performance do meu site\n3 - Suporte e manutenção do meu site\n4 - Outras Soluções para Hospedagem",
                'options' => [
                    '1' => 'hosting_host',
                    '2' => 'hosting_perf',
                    '3' => 'hosting_support',
                    '4' => 'handoff'
                ]
            ],
            'hosting_host' => [
                'text' => "Podemos oferecer hospedagem com alta performance e segurança. Quais requisitos você possui?\n1 - Alta performance\n2 - Backup automático\n3 - Certificado SSL",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff']
            ],
            'hosting_perf' => [
                'text' => "Podemos otimizar a velocidade do seu site. O que você gostaria de melhorar?\n1 - Tempo de carregamento\n2 - Uptime\n3 - Otimização de imagens e arquivos",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff']
            ],
            'hosting_support' => [
                'text' => "Podemos garantir que seu site continue funcionando sem problemas. Qual a frequência que você prefere para manutenção?\n1 - Mensal\n2 - Trimestral\n3 - Sob demanda",
                'options' => ['1' => 'handoff', '2' => 'handoff', '3' => 'handoff']
            ],
            'marketing' => [
                'text' => "Claro! 😄\nNos diga mais sobre o que você precisa, e encontraremos a melhor solução para seu negócio.",
                'end' => true
            ],
            'handoff' => [
                'text' => 'Encaminhando você para um analista. Aguarde...',
                'end' => true
            ]
        ];
    }

    public function handleMessage($userId, $message) {
        $text = trim(mb_strtolower($message));
        if (!isset($this->states[$userId])) {
            $name = ($this->getUserName)($userId);
            $this->states[$userId] = ['step' => 'start', 'name' => $name];
            return $this->tree['start']['text'];
        }

        if ($text === 'menu') {
            $this->states[$userId]['step'] = 'start';
            return $this->tree['start']['text'];
        }

        if ($text === 'sair' || $text === 'exit') {
            unset($this->states[$userId]);
            return 'Atendimento finalizado.';
        }

        $state =& $this->states[$userId];
        $node = $this->tree[$state['step']] ?? null;
        if (!$node || !isset($node['options'])) {
            $state['step'] = 'start';
            return $this->tree['start']['text'];
        }

        $nextKey = $node['options'][$text] ?? null;
        if (!$nextKey) {
            return 'Por favor, escolha uma opção válida ou digite "menu".';
        }

        $state['step'] = $nextKey;
        $nextNode = $this->tree[$nextKey];
        $reply = $nextNode['text'] ?? '';
        if (isset($nextNode['getText']) && is_callable($nextNode['getText'])) {
            $reply = $nextNode['getText']($state);
        }
        if (!empty($nextNode['end'])) {
            $state['step'] = 'handoff';
        }
        return $reply;
    }
}
?>
