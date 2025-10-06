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
            'content' => __('As cotas abaixo expiraram e voltaram para o estoque por falta de pagamento:', 'juntaplay'),
        ],
        [
            'type'  => 'list',
            'items' => $items,
        ],
        [
            'type'    => 'paragraph',
            'content' => __('Faça uma nova reserva para garantir sua participação.', 'juntaplay'),
        ],
    ],
    [
        'headline'  => __('Sua reserva expirou', 'juntaplay'),
        'preheader' => __('Algumas cotas ficaram livres novamente por falta de pagamento.', 'juntaplay'),
    ]
);
