<?php
declare(strict_types=1);

use JuntaPlay\Notifications\EmailHelper;

$items = [];
foreach ($quotas as $quota) {
    $items[] = (string) $quota;
}

echo EmailHelper::render(
    [
        [
            'type'    => 'paragraph',
            'content' => __('Obrigado pela sua compra. As cotas abaixo foram confirmadas:', 'juntaplay'),
        ],
        [
            'type'  => 'list',
            'items' => $items,
        ],
        [
            'type'    => 'paragraph',
            'content' => __('Se precisar de ajuda, basta responder este e-mail ou falar com o suporte JuntaPlay.', 'juntaplay'),
        ],
    ],
    [
        'headline' => __('Pagamento confirmado!', 'juntaplay'),
        'preheader' => __('Seu pedido foi aprovado e as cotas já estão reservadas.', 'juntaplay'),
    ]
);
