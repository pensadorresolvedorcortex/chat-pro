<?php
$names = [
    'usuario-demo' => 'Patrícia',
];

function getName($userId) {
    global $names;
    return $names[$userId] ?? 'Cliente';
}
