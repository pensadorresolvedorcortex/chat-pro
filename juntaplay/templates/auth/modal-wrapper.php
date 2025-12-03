<?php
/**
 * Authentication modal wrapper output in footer.
 *
 * @var array<string, mixed> $auth_context
 * @var string               $auto_open
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$auth_context = is_array($auth_context ?? null) ? $auth_context : [];
$auto_open    = isset($auto_open) ? (string) $auto_open : '';

if (!in_array($auto_open, ['login', 'register'], true)) {
    $auto_open = '';
}

$login_errors    = isset($auth_context['login_errors']) && is_array($auth_context['login_errors']) ? $auth_context['login_errors'] : [];
$register_errors = isset($auth_context['register_errors']) && is_array($auth_context['register_errors']) ? $auth_context['register_errors'] : [];
$active_view     = isset($auth_context['active_view']) ? (string) $auth_context['active_view'] : 'login';
$redirect_to     = isset($auth_context['redirect_to']) ? (string) $auth_context['redirect_to'] : '';

?>
<div class="juntaplay-modal juntaplay-modal--auth" data-auth-modal hidden aria-hidden="true">
    <div class="juntaplay-modal__overlay" data-modal-close></div>
    <div class="juntaplay-modal__dialog" role="dialog" aria-modal="true">
        <button type="button" class="juntaplay-modal__close" data-modal-close aria-label="<?php echo esc_attr__('Fechar', 'juntaplay'); ?>">
            <span aria-hidden="true">&times;</span>
        </button>
        <div class="juntaplay-modal__content" data-modal-content></div>
    </div>
</div>

<template id="jp-auth-modal-template" data-auto-open="<?php echo esc_attr($auto_open); ?>">
    <?php
    $header_auth_context = $auth_context;
    $header_auth_auto_open = $auto_open;
    include JP_DIR . 'templates/auth/modal.php';
    ?>
</template>
