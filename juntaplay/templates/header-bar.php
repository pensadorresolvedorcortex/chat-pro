<?php
/**
 * Header account shortcode.
 *
 * @var array<string, mixed> $header_context
 * @var array<int, array<string, string>> $header_menu_items
 * @var bool $header_guest
 * @var int $header_notifications_unread
 * @var string $header_login_url
 * @var string $header_register_url
 * @var bool   $header_auth_modal
 * @var array<string, mixed> $header_auth_context
 * @var string $header_auth_auto_open
 * @var array<int, array<string, string>> $header_notifications
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('juntaplay_header_icon')) {
    function juntaplay_header_icon(string $icon): string
    {
        return match ($icon) {
            'grid' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4" y="4" width="7" height="7" rx="1.5" ry="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/><rect x="13" y="4" width="7" height="7" rx="1.5" ry="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/><rect x="4" y="13" width="7" height="7" rx="1.5" ry="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/><rect x="13" y="13" width="7" height="7" rx="1.5" ry="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>',
            'user' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4"/><path d="M5 20a7 7 0 0 1 14 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4"/></svg>',
            'wallet' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7.5c0-1.38 1.1-2.5 2.46-2.5h11.08a2 2 0 0 1 0 4H4Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><rect x="4" y="7.5" width="16" height="11" rx="2.2" fill="none" stroke="currentColor" stroke-width="1.4"/><circle cx="16.5" cy="13" r="1.25" fill="currentColor"/></svg>',
            'users' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M16.5 13a3.5 3.5 0 1 0-3.4-4.35" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.9 10.25a3.75 3.75 0 1 0 6.2 0" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.5 20c0-2.21-2.24-4-5-4h-.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M4.5 20c0-2.6 2.77-4.5 6.19-4.5S17 17.4 17 20" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'ticket' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2.2a2.3 2.3 0 0 0 0 3.6V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.2a2.3 2.3 0 0 0 0-3.6Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M12 8v8" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 11.5h0" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'message' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6h12a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H8.5a1 1 0 0 0-.7.29L5.5 19.5V10a4 4 0 0 1 .62-2.11A2 2 0 0 1 6 6Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="m8 10 2.8 2.1a3 3 0 0 0 3.4 0L17 10" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'receipt' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 3.5 9.5 5 12 3.5 14.5 5 17 3.5V20l-2.5-1.5L12 20l-2.5-1.5L7 20Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M9 9h6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M9 13h6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
            'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 17 20 12 15 7" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12H9" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 5h6v14H5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            default => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>',
        };
    }
}

$header_guest = isset($header_guest) ? (bool) $header_guest : false;
$header_context = is_array($header_context ?? null) ? $header_context : [];
$header_menu_items = is_array($header_menu_items ?? null) ? $header_menu_items : [];
$header_notifications = is_array($header_notifications ?? null) ? $header_notifications : [];
$header_notifications_unread = isset($header_notifications_unread) ? (int) $header_notifications_unread : 0;
$header_login_url    = isset($header_login_url) ? (string) $header_login_url : wp_login_url();
$header_register_url = isset($header_register_url) ? (string) $header_register_url : '';
$header_auth_modal   = isset($header_auth_modal) ? (bool) $header_auth_modal : false;
$header_auth_context = is_array($header_auth_context ?? null) ? $header_auth_context : [];
$header_auth_auto_open = isset($header_auth_auto_open) ? (string) $header_auth_auto_open : '';
$profile_page_id   = (int) get_option('juntaplay_page_perfil');
$messages_base_url = $profile_page_id ? get_permalink($profile_page_id) : home_url('/perfil/');
$messages_endpoint = user_trailingslashit(trailingslashit($messages_base_url) . 'juntaplay-chat');
$messages_base_url = add_query_arg('section', 'juntaplay-chat', $messages_base_url);
$messages_entry_url = $messages_base_url !== '' ? $messages_base_url : $messages_endpoint;

if (!in_array($header_auth_auto_open, ['login', 'register'], true)) {
    $header_auth_auto_open = '';
}

$messages_label        = __('Mensagens', 'juntaplay');
$has_messages_menu     = false;
$normalized_menu_items = [];

foreach ($header_menu_items as $menu_item) {
    $label = isset($menu_item['label']) ? (string) $menu_item['label'] : '';

    if (!$has_messages_menu && $label !== '' && strcasecmp($label, $messages_label) === 0) {
        $has_messages_menu = true;
    }

    $normalized_menu_items[] = $menu_item;
}

if (!$has_messages_menu) {
    array_unshift(
        $normalized_menu_items,
        [
            'label' => $messages_label,
            'icon'  => 'message',
            'url'   => $messages_endpoint ?: $messages_base_url,
        ]
    );
}

$header_menu_items = $normalized_menu_items;

$name       = isset($header_context['name']) ? (string) $header_context['name'] : '';
$first_name = isset($header_context['first_name']) ? (string) $header_context['first_name'] : '';
$email      = isset($header_context['email']) ? (string) $header_context['email'] : '';
$avatar_url = isset($header_context['avatar_url']) ? (string) $header_context['avatar_url'] : '';
$initial    = isset($header_context['initial']) ? (string) $header_context['initial'] : 'J';
$current_user_id = isset($header_context['user_id']) ? (int) $header_context['user_id'] : get_current_user_id();

$unread_meta = $current_user_id ? get_user_meta($current_user_id, 'juntaplay_mensagens_nao_lidas', true) : [];
$read_meta   = $current_user_id ? get_user_meta($current_user_id, 'juntaplay_mensagens_lidas', true) : [];

if ($current_user_id) {
    if (!is_array($unread_meta)) {
        $unread_meta = [];
        update_user_meta($current_user_id, 'juntaplay_mensagens_nao_lidas', $unread_meta);
    }

    if (!is_array($read_meta)) {
        $read_meta = [];
        update_user_meta($current_user_id, 'juntaplay_mensagens_lidas', $read_meta);
    }
}

$unread_messages = is_array($unread_meta) ? $unread_meta : [];
$read_messages   = is_array($read_meta) ? $read_meta : [];

$chat_section = isset($_GET['section']) ? (string) wp_unslash($_GET['section']) : '';

if ($current_user_id && $chat_section === 'juntaplay-chat' && $unread_messages) {
    $read_messages   = array_values(array_merge($read_messages, $unread_messages));
    $unread_messages = [];

    update_user_meta($current_user_id, 'juntaplay_mensagens_lidas', $read_messages);
    update_user_meta($current_user_id, 'juntaplay_mensagens_nao_lidas', $unread_messages);
}

if ($first_name === '') {
    $first_name = $name !== '' ? $name : '';
}

$avatar_label = $name !== '' ? $name : ($first_name !== '' ? $first_name : __('assinante', 'juntaplay'));
$recipient_name = $first_name !== '' ? $first_name : ($name !== '' ? $name : __('assinante', 'juntaplay'));

$notification_messages = [];

$thread_notifications_total = 0;

if ($current_user_id && function_exists('juntaplay_chat_threads_for_user')) {
    $thread_list = juntaplay_chat_threads_for_user($current_user_id);

    foreach ($thread_list as $thread_entry) {
        $unread_count = isset($thread_entry['unread_count']) ? (int) $thread_entry['unread_count'] : 0;

        if ($unread_count <= 0) {
            continue;
        }

        $thread_notifications_total += $unread_count;

        $counterpart_name = isset($thread_entry['counterpart_name']) ? (string) $thread_entry['counterpart_name'] : '';
        $admin_id         = isset($thread_entry['admin_id']) ? (int) $thread_entry['admin_id'] : 0;
        $subscriber_id    = isset($thread_entry['subscriber_id']) ? (int) $thread_entry['subscriber_id'] : 0;
        $admin_name       = isset($thread_entry['admin_name']) ? (string) $thread_entry['admin_name'] : '';
        $subscriber_name  = isset($thread_entry['subscriber_name']) ? (string) $thread_entry['subscriber_name'] : '';

        if ($counterpart_name === '') {
            if ($current_user_id === $admin_id) {
                $counterpart_name = $subscriber_name;
            } elseif ($current_user_id === $subscriber_id) {
                $counterpart_name = $admin_name;
            }
        }

        if ($counterpart_name === '') {
            $counterpart_name = __('Contato', 'juntaplay');
        }

        $notification_messages[] = [
            'message' => sprintf(__('%s te enviou uma mensagem.', 'juntaplay'), $counterpart_name),
            'url'     => $messages_entry_url,
        ];
    }
}

if ($thread_notifications_total > 0) {
    $header_notifications_unread += $thread_notifications_total;
}

if ($thread_notifications_total === 0 && $header_notifications_unread === 0 && $unread_messages) {
    $header_notifications_unread = count($unread_messages);
}

if (!$notification_messages) {
    foreach ($unread_messages as $message_data) {
        $sender_id   = isset($message_data['sender_id']) ? (int) $message_data['sender_id'] : 0;
        $sender_name = isset($message_data['sender']) ? (string) $message_data['sender'] : __('Administrador', 'juntaplay');

        if ($sender_id > 0) {
            $sender_user = get_user_by('id', $sender_id);
            if ($sender_user) {
                $sender_name = (string) $sender_user->display_name;
            }
        }

        $notification_messages[] = [
            'message' => sprintf(__('%s te enviou uma mensagem.', 'juntaplay'), $sender_name),
            'url'     => $messages_entry_url,
        ];
    }
}
?>
<div class="juntaplay-header<?php echo $header_guest ? ' juntaplay-header--guest' : ''; ?>" data-jp-header>
    <div class="juntaplay-header__inner">
        <?php if ($header_guest) : ?>
            <div class="juntaplay-header__guest">
                <div class="juntaplay-header__guest-actions">
                    <div class="juntaplay-auth__switch" role="tablist">
                        <a class="juntaplay-auth__switch-btn is-active" href="<?php echo esc_url($header_login_url); ?>" role="tab" aria-selected="true" data-jp-auth-open="login">
                            <?php esc_html_e('Entrar', 'juntaplay'); ?>
                        </a>
                        <?php if ($header_register_url !== '') : ?>
                            <a class="juntaplay-auth__switch-btn" href="<?php echo esc_url($header_register_url); ?>" role="tab" aria-selected="false" data-jp-auth-open="register">
                                <?php esc_html_e('Cadastre-se', 'juntaplay'); ?>
                            </a>
                        <?php else : ?>
                            <button type="button" class="juntaplay-auth__switch-btn" role="tab" aria-selected="false" disabled>
                                <?php esc_html_e('Cadastre-se', 'juntaplay'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="juntaplay-header__guest-mobile" data-guest-mobile>
                    <button type="button" class="juntaplay-header__guest-icon" data-jp-auth-open="login" aria-label="<?php esc_attr_e('Entrar para ver notificações', 'juntaplay'); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2Zm7-6v-5a7 7 0 0 0-5-6.71V4a2 2 0 1 0-4 0v.29A7 7 0 0 0 5 11v5l-1.45 2.9A1 1 0 0 0 4.45 20h15.1a1 1 0 0 0 .9-1.45Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" />
                        </svg>
                    </button>
                    <div class="juntaplay-header__guest-account" data-guest-menu>
                        <button type="button" class="juntaplay-header__guest-toggle" data-guest-menu-toggle aria-haspopup="true" aria-expanded="false">
                            <span class="juntaplay-header__guest-toggle-icon" aria-hidden="true">
                                <?php echo juntaplay_header_icon('user'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </span>
                            <span class="juntaplay-header__guest-caret" aria-hidden="true">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" />
                                </svg>
                            </span>
                            <span class="screen-reader-text"><?php esc_html_e('Abrir opções da conta', 'juntaplay'); ?></span>
                        </button>
                        <div class="juntaplay-header__guest-dropdown" data-guest-menu-panel aria-hidden="true">
                            <a class="juntaplay-header__guest-link" href="<?php echo esc_url($header_login_url); ?>" data-jp-auth-open="login">
                                <?php esc_html_e('Entrar', 'juntaplay'); ?>
                            </a>
                            <?php if ($header_register_url !== '') : ?>
                                <a class="juntaplay-header__guest-link" href="<?php echo esc_url($header_register_url); ?>" data-jp-auth-open="register">
                                    <?php esc_html_e('Cadastre-se', 'juntaplay'); ?>
                                </a>
                            <?php else : ?>
                                <button type="button" class="juntaplay-header__guest-link is-disabled" disabled>
                                    <?php esc_html_e('Cadastre-se', 'juntaplay'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="juntaplay-header__summary">
                <div class="juntaplay-header__notifications" data-jp-notifications-root>
                    <button type="button" class="juntaplay-notification-bell" data-jp-notifications aria-haspopup="true" aria-expanded="false"<?php if ($header_notifications_unread > 0) : ?> data-count="<?php echo esc_attr($header_notifications_unread); ?>"<?php endif; ?>>
                        <span class="screen-reader-text"><?php esc_html_e('Abrir notificações', 'juntaplay'); ?></span>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2Zm7-6v-5a7 7 0 0 0-5-6.71V4a2 2 0 1 0-4 0v.29A7 7 0 0 0 5 11v5l-1.45 2.9A1 1 0 0 0 4.45 20h15.1a1 1 0 0 0 .9-1.45Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" />
                        </svg>
                    </button>
                        <div class="juntaplay-notifications" data-jp-notifications-panel aria-hidden="true">
                            <div class="juntaplay-notifications__header">
                                <h4><?php esc_html_e('Notificações', 'juntaplay'); ?></h4>
                            </div>
                            <ul class="juntaplay-notifications__list" data-jp-notifications-list>
                                <?php if (!empty($header_notifications)) : ?>
                                    <?php foreach ($header_notifications as $notification) : ?>
                                        <li class="juntaplay-notifications__item">
                                            <a href="<?php echo esc_url($notification['url']); ?>">
                                                <?php echo esc_html($notification['text']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <li class="juntaplay-notifications__empty">
                                        <?php esc_html_e('Nenhuma notificação por enquanto.', 'juntaplay'); ?>
                                    </li>
                                <?php endif; ?>
                            </ul>
                            <div class="juntaplay-notifications__footer">
                                <button type="button" class="juntaplay-notifications__clear" data-jp-notifications-clear><?php esc_html_e('Apagar notificações', 'juntaplay'); ?></button>
                                <button type="button" class="juntaplay-notifications__close" data-jp-notifications-close><?php esc_html_e('Fechar', 'juntaplay'); ?></button>
                            </div>
                    </div>
                </div>
                <div class="juntaplay-header__account" data-jp-account>
                    <button type="button" class="juntaplay-header__trigger" data-jp-account-toggle aria-haspopup="true" aria-expanded="false">
                        <span class="juntaplay-header__avatar">
                            <?php if ($avatar_url !== '') : ?>
                                <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr(sprintf(__('Avatar de %s', 'juntaplay'), $avatar_label)); ?>" class="juntaplay-header__avatar-img" loading="lazy" />
                            <?php else : ?>
                                <span class="juntaplay-header__avatar-initial"><?php echo esc_html($initial); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="juntaplay-header__meta">
                            <span class="juntaplay-header__greeting"><?php esc_html_e('Bem-vindo', 'juntaplay'); ?></span>
                            <span class="juntaplay-header__name"><?php echo esc_html($first_name !== '' ? $first_name : $avatar_label); ?></span>
                        </span>
                        <span class="juntaplay-header__chevron" aria-hidden="true">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" />
                            </svg>
                        </span>
                    </button>
                    <div class="juntaplay-header__menu" data-jp-account-menu aria-hidden="true">
                        <div class="juntaplay-header__menu-inner">
                            <div class="juntaplay-header__menu-heading">
                                <span class="juntaplay-header__menu-label"><?php esc_html_e('Minha conta', 'juntaplay'); ?></span>
                                <?php if ($email !== '') : ?>
                                    <span class="juntaplay-header__menu-subtext"><?php echo esc_html($email); ?></span>
                                <?php endif; ?>
                            </div>
                            <ul class="juntaplay-header__menu-list">
                                <?php foreach ($header_menu_items as $item) : ?>
                                    <?php
                                    $url   = isset($item['url']) ? (string) $item['url'] : '';
                                    $label = isset($item['label']) ? (string) $item['label'] : '';
                                    $icon  = isset($item['icon']) ? (string) $item['icon'] : '';
                                    $type  = isset($item['type']) ? (string) $item['type'] : '';

                                    if ($label === '') {
                                        continue;
                                    }
                                    ?>
                                    <li>
                                        <?php if ($url !== '') : ?>
                                            <a class="juntaplay-header__menu-link<?php echo $type === 'logout' ? ' is-logout' : ''; ?>" href="<?php echo esc_url($url); ?>">
                                                <span class="juntaplay-header__menu-icon"><?php echo juntaplay_header_icon($icon); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                                <span class="juntaplay-header__menu-text"><?php echo esc_html($label); ?></span>
                                            </a>
                                        <?php else : ?>
                                            <span class="juntaplay-header__menu-link">
                                                <span class="juntaplay-header__menu-icon"><?php echo juntaplay_header_icon($icon); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                                <span class="juntaplay-header__menu-text"><?php echo esc_html($label); ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
