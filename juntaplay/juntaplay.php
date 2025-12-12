<?php
/**
 * Plugin Name: JuntaPlay — Gestão de Cotas
 * Description: Campanhas com cotas integradas ao WooCommerce e Elementor.
 * Version: 0.1.5
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * Author: Sua Empresa
 * Text Domain: juntaplay
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const JP_VERSION    = '0.1.5';
const JP_MIN_WP     = '6.2';
const JP_MIN_PHP    = '8.1';
const JP_DB_VERSION = '2.6.0';
const JP_SLUG       = 'juntaplay';
const JP_GROUP_COVER_PLACEHOLDER = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHdpZHRoPSc0OTUnIGhlaWdodD0nMzcwJyB2aWV3Qm94PScwIDA0OTUgMzcwJz4KICA8ZGVmcz4KICAgIDxsaW5lYXJHcmFkaWVudCBpZD0nZycgeDE9JzAnIHkxPScwJyB4Mj0nMScgeTI9JzEnPgogICAgICA8c3RvcCBvZmZzZXQ9JzAlJyBzdG9wLWNvbG9yPScjNUI2Q0ZGJy8+CiAgICAgIDxzdG9wIG9mZnNldD0nMTAwJScgc3RvcC1jb2xvcj0nIzhFNTRFOScvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICA8L2RlZnM+CiAgPHJlY3Qgd2lkdGg9JzQ5NScgaGVpZ2h0PSczNzAnIGZpbGw9J3VybCgjZyknIHJ4PSczMicvPgogIDxnIGZpbGw9JyNGRkZGRkYnIGZvbnQtZmFtaWx5PSdGcmVkb2thLCBGaWd0cmVlLCBzYW5zLXNlcmlmJyBmb250LXdlaWdodD0nNjAwJz4KICAgIDx0ZXh0IHg9JzUwJScgeT0nNDglJyBkb21pbmFudC1iYXNlbGluZT0nbWlkZGxlJyB0ZXh0LWFuY2hvcj0nbWlkZGxlJyBmb250LXNpemU9JzQwJz5KdW50YVBsYXk8L3RleHQ+CiAgICA8dGV4dCB4PSc1MCUnIHk9JzYwJScgZG9taW5hbnQtYmFzZWxpbmU9J21pZGRsZScgdGV4dC1hbmNob3I9J21pZGRsZScgZm9udC1zaXplPScyNCcgZm9udC13ZWlnaHQ9JzQwMCc+Q2FwYSBEZW1vbnN0cmF0aXZhPC90ZXh0PgogIDwvZz4KPC9zdmc+';

define('JP_FILE', __FILE__);
define('JP_DIR', plugin_dir_path(__FILE__));
define('JP_URL', plugin_dir_url(__FILE__));

spl_autoload_register(static function (string $class): void {
    $prefix   = 'JuntaPlay\\';
    $base_dir = JP_DIR . 'includes/';
    $len      = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file           = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

register_activation_hook(__FILE__, static function (): void {
    (new \JuntaPlay\Installer())->activate();
});

register_uninstall_hook(__FILE__, 'juntaplay_uninstall');

function juntaplay_uninstall(): void
{
    // Opcional: remover opções, eventos agendados etc. Não apagar dados por padrão.
}

add_action('plugins_loaded', static function (): void {
    if (version_compare(PHP_VERSION, JP_MIN_PHP, '<')) {
        return;
    }

    if (version_compare(get_bloginfo('version'), JP_MIN_WP, '<')) {
        return;
    }

(new \JuntaPlay\Plugin())->init();
});

if (file_exists(JP_DIR . 'chat.php')) {
    require_once JP_DIR . 'chat.php';
}

add_action('init', static function (): void {
    add_shortcode('juntaplay_chatonline', static function (): string {
        if (!is_user_logged_in()) {
            return '<div class="jp-chat-card"><p>' . esc_html__('Você precisa estar logado para acessar o chat.', 'juntaplay') . '</p></div>';
        }

        $current_user = wp_get_current_user();
        $current_id   = (int) $current_user->ID;

        $raw_groups = class_exists('\\JuntaPlay\\Data\\Groups')
            ? \JuntaPlay\Data\Groups::get_groups_for_user($current_id)
            : ['owned' => [], 'member' => []];

        $header_avatar = '';

        if (class_exists('\\JuntaPlay\\Front\\Profile')) {
            $profile_instance = new \JuntaPlay\Front\Profile();
            $header_context   = $profile_instance->get_header_context();
            $header_avatar    = isset($header_context['avatar_url']) ? (string) $header_context['avatar_url'] : '';
        }

        if ($header_avatar === '') {
            $header_avatar = get_avatar_url($current_id, ['size' => 64]);
        }

        $avatar_fallback      = $header_avatar ?: get_avatar_url($current_id, ['size' => 64]);
        $group_cover_fallback = JP_GROUP_COVER_PLACEHOLDER;

        $owned_groups   = is_array($raw_groups['owned'] ?? null) ? $raw_groups['owned'] : [];
        $member_groups  = is_array($raw_groups['member'] ?? null) ? $raw_groups['member'] : [];
        $is_admin       = !empty($owned_groups);
        $group_members  = [];
        $normalized_owned = [];

        foreach ($owned_groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $group_id = (int) ($group['id'] ?? 0);
            if ($group_id <= 0) {
                continue;
            }

            $normalized_owned[] = [
                'id'        => $group_id,
                'title'     => (string) ($group['title'] ?? ''),
                'subtitle'  => (string) ($group['pool_title'] ?? ''),
                'avatar'    => (string) ($group['icon_url'] ?? '') ?: $group_cover_fallback,
                'owner_id'  => (int) ($group['owner_id'] ?? 0),
            ];

            $member_ids = [];

            if (class_exists('\\JuntaPlay\\Data\\GroupMembers')) {
                if (method_exists('\JuntaPlay\Data\GroupMembers', 'get')) {
                    /** @phpstan-ignore-next-line */
                    $member_ids = \JuntaPlay\Data\GroupMembers::get($group_id, 'active');
                } elseif (method_exists('\JuntaPlay\Data\GroupMembers', 'get_user_ids')) {
                    $member_ids = \JuntaPlay\Data\GroupMembers::get_user_ids($group_id, 'active');
                }
            }

            $members = [];

            foreach ($member_ids as $member_id) {
                $member_id = (int) $member_id;

                if ($member_id <= 0 || $member_id === $current_id || (int) ($group['owner_id'] ?? 0) === $member_id) {
                    continue;
                }

                $user = get_user_by('id', $member_id);
                $member_avatar = get_avatar_url($member_id, ['size' => 64]) ?: $avatar_fallback;

                $members[] = [
                    'id'     => $member_id,
                    'name'   => $user ? (string) $user->display_name : '',
                    'avatar' => $member_avatar,
                    'group'  => (string) ($group['title'] ?? ''),
                ];
            }

            $group_members[$group_id] = $members;
        }

        $normalized_member = [];

        foreach ($member_groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $group_id = (int) ($group['id'] ?? 0);
            if ($group_id <= 0) {
                continue;
            }

            $normalized_member[] = [
                'id'        => $group_id,
                'title'     => (string) ($group['title'] ?? ''),
                'subtitle'  => (string) ($group['pool_title'] ?? ''),
                'avatar'    => (string) ($group['icon_url'] ?? '') ?: $group_cover_fallback,
                'owner_id'  => (int) ($group['owner_id'] ?? 0),
            ];
        }

        $default_member_group = $normalized_member[0] ?? null;
        $subscriber_admin     = null;

        if ($default_member_group && ($default_member_group['owner_id'] ?? 0)) {
            $admin_id   = (int) $default_member_group['owner_id'];
            $admin_user = get_user_by('id', $admin_id);

            $subscriber_admin = [
                'id'     => $admin_id,
                'name'   => $admin_user ? (string) $admin_user->display_name : '',
                'avatar' => get_avatar_url($admin_id, ['size' => 64]) ?: $avatar_fallback,
            ];
        }

        $context = [
            'restBase'       => esc_url_raw(rest_url('juntaplay/v1/chat')),
            'nonce'          => wp_create_nonce('wp_rest'),
            'isAdmin'        => $is_admin,
            'currentUser'    => [
                'id'     => $current_id,
                'name'   => (string) $current_user->display_name,
                'avatar' => $header_avatar ?: $avatar_fallback,
            ],
            'groups'         => $normalized_owned,
            'groupMembers'   => $group_members,
            'memberGroups'   => $normalized_member,
            'groupPlaceholder' => $group_cover_fallback,
            'subscriberView' => [
                'group'    => $default_member_group,
                'adminId'  => $default_member_group ? (int) ($default_member_group['owner_id'] ?? 0) : 0,
                'admin'    => $subscriber_admin,
            ],
            'fallbackAvatar' => $avatar_fallback,
            'i18n'           => [
                'emptyMembers'   => __('Nenhum assinante ativo neste grupo.', 'juntaplay'),
                'selectMember'   => __('Selecione um assinante para iniciar o chat.', 'juntaplay'),
                'startChat'      => __('Selecione um grupo ou assinante para começar.', 'juntaplay'),
                'send'           => __('Enviar', 'juntaplay'),
                'typing'         => __('Digite sua mensagem...', 'juntaplay'),
                'noGroups'       => __('Nenhum grupo disponível para chat.', 'juntaplay'),
                'adminHeader'    => __('Central de Mensagens', 'juntaplay'),
                'subscriberHeader' => __('Fale com o Administrador', 'juntaplay'),
            ],
        ];

        ob_start();
        ?>
        <style>
            .jp-chat-shell {
                max-width: 1200px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: 320px 1fr;
                gap: 16px;
                font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }

            .jp-chat-card,
            .jp-chat-panel,
            .jp-chat-sidebar,
            .jp-chat-header,
            .jp-chat-message-input,
            .jp-chat-empty {
                background: rgba(255, 255, 255, 0.18);
                border-radius: 24px;
                box-shadow: 0 8px 32px rgba(17, 24, 39, 0.12);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .jp-chat-sidebar {
                padding: 16px;
                height: 720px;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .jp-chat-sidebar h3 {
                margin: 0;
                font-size: 18px;
                color: #0f172a;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .jp-chat-sidebar .jp-chat-section {
                background: rgba(255, 255, 255, 0.35);
                border-radius: 16px;
                padding: 8px;
            }

            .jp-chat-list {
                list-style: none;
                margin: 0;
                padding: 0;
                max-height: 280px;
                overflow: auto;
            }

            .jp-chat-list li {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 12px;
                border-radius: 14px;
                cursor: pointer;
                transition: all 0.15s ease;
            }

            .jp-chat-list li:hover {
                background: rgba(255, 255, 255, 0.65);
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            }

            .jp-chat-list li.active {
                background: linear-gradient(135deg, rgba(56, 189, 248, 0.2), rgba(14, 165, 233, 0.18));
                border: 1px solid rgba(56, 189, 248, 0.35);
            }

            .jp-avatar-glass {
                width: 64px;
                height: 64px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.35);
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
                border: 1px solid rgba(255,255,255,0.45);
                box-shadow:
                    0 8px 24px rgba(0,0,0,0.12),
                    inset 0 0 0 1px rgba(255,255,255,0.25);
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .jp-avatar-glass img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 50%;
            }

            .juntaplay-group-card_avatar {
                width: 64px;
                height: 64px;
                border-radius: 16px;
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                flex-shrink: 0;
                border: 1px solid rgba(0, 0, 0, 0.05);
            }

            .jp-chat-meta {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            .jp-chat-meta .name {
                font-weight: 600;
                color: #0f172a;
            }

            .jp-chat-meta .subtitle {
                color: #475569;
                font-size: 13px;
            }

            .jp-chat-panel {
                position: relative;
                padding: 0;
                min-height: 720px;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .jp-chat-header {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 16px 20px;
            }

            .jp-chat-header .titles {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .jp-chat-header .titles .primary {
                font-size: 20px;
                margin: 0;
                color: #0f172a;
            }

            .jp-chat-header .titles .secondary {
                margin: 0;
                color: #475569;
                font-size: 13px;
            }

            .jp-chat-body {
                flex: 1;
                padding: 16px 20px;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
                gap: 10px;
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.22), rgba(255, 255, 255, 0.05));
            }

            .jp-chat-bubble {
                max-width: 72%;
                padding: 12px 14px;
                border-radius: 18px;
                position: relative;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            }

            .jp-chat-bubble.mine {
                align-self: flex-end;
                background: linear-gradient(135deg, rgba(59, 130, 246, 0.22), rgba(14, 165, 233, 0.18));
                border: 1px solid rgba(59, 130, 246, 0.3);
                color: #0f172a;
            }

            .jp-chat-bubble.theirs {
                align-self: flex-start;
                background: rgba(255, 255, 255, 0.82);
                border: 1px solid rgba(15, 23, 42, 0.08);
                color: #0f172a;
            }

            .jp-chat-bubble .timestamp {
                display: block;
                margin-top: 6px;
                font-size: 12px;
                color: #475569;
            }

            .jp-chat-message-input {
                margin: 16px;
                padding: 10px;
                display: grid;
                grid-template-columns: 1fr 120px;
                gap: 8px;
                align-items: center;
            }

            .jp-chat-message-input textarea {
                width: 100%;
                border: none;
                border-radius: 14px;
                padding: 12px 14px;
                resize: none;
                min-height: 56px;
                font-size: 15px;
                background: rgba(255, 255, 255, 0.78);
                outline: none;
            }

            .jp-chat-message-input button {
                width: 100%;
                border: none;
                border-radius: 14px;
                padding: 14px 12px;
                font-weight: 700;
                color: #fff;
                cursor: pointer;
                background: linear-gradient(135deg, #3b82f6, #0ea5e9);
                box-shadow: 0 10px 25px rgba(59, 130, 246, 0.35);
                transition: transform 0.1s ease, box-shadow 0.1s ease;
            }

            .jp-chat-message-input button:hover {
                transform: translateY(-1px);
                box-shadow: 0 14px 28px rgba(59, 130, 246, 0.45);
            }

            .jp-chat-empty {
                margin: 0;
                padding: 28px;
                text-align: center;
                color: #334155;
                font-weight: 600;
            }

            .jp-chat-single-column {
                display: block;
                grid-template-columns: 1fr;
            }

            @media (max-width: 960px) {
                .jp-chat-shell {
                    grid-template-columns: 1fr;
                }

                .jp-chat-sidebar {
                    height: auto;
                }

                .jp-chat-panel {
                    min-height: 520px;
                }
            }
        </style>

        <div class="jp-chat-shell" data-jp-chat-shell>
            <?php if ($is_admin) : ?>
                <aside class="jp-chat-sidebar" data-jp-chat-sidebar>
                    <h3><?php echo esc_html($context['i18n']['adminHeader']); ?></h3>
                    <div class="jp-chat-section">
                        <p class="jp-chat-meta subtitle" style="margin: 8px 10px 4px; font-weight:600; color:#0f172a;">
                            <?php esc_html_e('Grupos que você administra', 'juntaplay'); ?>
                        </p>
                        <ul class="jp-chat-list" data-jp-chat-groups></ul>
                    </div>
                    <div class="jp-chat-section">
                        <p class="jp-chat-meta subtitle" style="margin: 8px 10px 4px; font-weight:600; color:#0f172a;">
                            <?php esc_html_e('Assinantes do grupo selecionado', 'juntaplay'); ?>
                        </p>
                        <ul class="jp-chat-list" data-jp-chat-members></ul>
                        <p class="jp-chat-empty" data-jp-chat-members-empty style="display:none;"></p>
                    </div>
                </aside>
            <?php endif; ?>

            <section class="jp-chat-panel" data-jp-chat-panel>
                <header class="jp-chat-header" data-jp-chat-header>
                    <div class="jp-avatar-glass juntaplay-profile_avatar" data-jp-chat-avatar-shell>
                        <img class="juntaplay-profile_avatar-img" data-jp-chat-partner-avatar src="" alt="" />
                    </div>
                    <div class="titles">
                        <p class="primary" data-jp-chat-partner-name><?php echo esc_html($context['i18n']['startChat']); ?></p>
                        <p class="secondary" data-jp-chat-partner-subtitle></p>
                    </div>
                </header>
                <div class="jp-chat-body" data-jp-chat-body>
                    <p class="jp-chat-empty" data-jp-chat-empty><?php echo esc_html($context['i18n']['startChat']); ?></p>
                </div>
                <div class="jp-chat-message-input">
                    <textarea data-jp-chat-input placeholder="<?php echo esc_attr($context['i18n']['typing']); ?>" disabled></textarea>
                    <button type="button" data-jp-chat-send disabled><?php echo esc_html($context['i18n']['send']); ?></button>
                </div>
            </section>
        </div>

        <script type="application/json" id="jp-chat-data">
            <?php echo wp_json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
        </script>

        <script>
            (function () {
                const dataEl = document.getElementById('jp-chat-data');
                if (!dataEl) return;

                const data = JSON.parse(dataEl.textContent || '{}');
                const shell = document.querySelector('[data-jp-chat-shell]');
                const groupsList = document.querySelector('[data-jp-chat-groups]');
                const membersList = document.querySelector('[data-jp-chat-members]');
                const membersEmpty = document.querySelector('[data-jp-chat-members-empty]');
                const chatBody = document.querySelector('[data-jp-chat-body]');
                const chatEmpty = document.querySelector('[data-jp-chat-empty]');
                const headerName = document.querySelector('[data-jp-chat-partner-name]');
                const headerSubtitle = document.querySelector('[data-jp-chat-partner-subtitle]');
                const headerAvatar = document.querySelector('[data-jp-chat-partner-avatar]');
                const input = document.querySelector('[data-jp-chat-input]');
                const sendBtn = document.querySelector('[data-jp-chat-send]');

                if (!shell || !chatBody) return;

                let selectedGroup = null;
                let selectedMember = null;
                let isSending = false;

                const restBase = (data.restBase || '').replace(/\/$/, '');
                const fallbackAvatar = data.fallbackAvatar || 'https://www.gravatar.com/avatar/?d=mp&s=64';

                const formatTime = (dateStr) => {
                    const date = new Date(dateStr.replace(' ', 'T'));
                    return date.toLocaleString([], { hour: '2-digit', minute: '2-digit' });
                };

                const setPartner = (user) => {
                    if (headerName) {
                        headerName.textContent = user?.name || data.i18n.startChat;
                    }

                    if (headerSubtitle) {
                        headerSubtitle.textContent = user?.subtitle || '';
                    }

                    if (headerAvatar) {
                        headerAvatar.src = (user && user.avatar) || fallbackAvatar;
                        headerAvatar.alt = user?.name || '';
                    }
                };

                const renderMessages = (messages) => {
                    chatBody.innerHTML = '';

                    if (!messages || !messages.length) {
                        const p = document.createElement('p');
                        p.className = 'jp-chat-empty';
                        p.textContent = data.i18n.selectMember;
                        chatBody.appendChild(p);
                        return;
                    }

                    messages.forEach((message) => {
                        const bubble = document.createElement('div');
                        bubble.className = 'jp-chat-bubble ' + (message.sender_id === data.currentUser.id ? 'mine' : 'theirs');
                        bubble.textContent = message.message || '';

                        const time = document.createElement('span');
                        time.className = 'timestamp';
                        time.textContent = formatTime(message.created_at);

                        bubble.appendChild(time);
                        chatBody.appendChild(bubble);
                    });

                    chatBody.scrollTop = chatBody.scrollHeight;
                };

                const toggleInput = (enabled) => {
                    input.disabled = !enabled;
                    sendBtn.disabled = !enabled;
                };

                const fetchMessages = async () => {
                    if (!selectedGroup || !selectedMember) return;

                    const params = new URLSearchParams({
                        admin_id: selectedGroup.owner_id || data.subscriberView.adminId,
                        subscriber_id: selectedMember.id,
                        group_id: selectedGroup.id || data.subscriberView.group?.id || 0,
                        mark_read: 'true',
                    });

                    const res = await fetch(`${restBase}/messages?${params.toString()}`, {
                        headers: { 'X-WP-Nonce': data.nonce },
                        credentials: 'same-origin',
                    });

                    if (!res.ok) return;

                    const json = await res.json();
                    renderMessages(json?.messages || []);
                    toggleInput(true);
                };

                const sendMessage = async () => {
                    if (isSending || !selectedGroup || !selectedMember) return;
                    const message = (input.value || '').trim();
                    if (!message) return;

                    isSending = true;
                    sendBtn.classList.add('is-loading');

                    const payload = {
                        admin_id: selectedGroup.owner_id || data.subscriberView.adminId,
                        subscriber_id: selectedMember.id,
                        group_id: selectedGroup.id || data.subscriberView.group?.id || 0,
                        message,
                    };

                    try {
                        const res = await fetch(`${restBase}/send`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': data.nonce,
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(payload),
                        });

                        if (res.ok) {
                            input.value = '';
                            await fetchMessages();
                        }
                    } finally {
                        isSending = false;
                        sendBtn.classList.remove('is-loading');
                    }
                };

                const renderMembers = (groupId) => {
                    if (!membersList || !membersEmpty) return;
                    membersList.innerHTML = '';
                    const members = (data.groupMembers || {})[groupId] || [];

                    if (!members.length) {
                        membersEmpty.textContent = data.i18n.emptyMembers;
                        membersEmpty.style.display = 'block';
                        return;
                    }

                    membersEmpty.style.display = 'none';

                    members.forEach((member) => {
                        const li = document.createElement('li');
                        li.dataset.memberId = member.id;
                        li.dataset.memberName = member.name;
                        li.dataset.memberGroup = member.group;
                        li.dataset.memberAvatar = member.avatar;

                        const avatar = member.avatar || fallbackAvatar;

                        li.innerHTML = `
                            <div class="jp-avatar-glass juntaplay-profile_avatar">
                                <img
                                  class="juntaplay-profile_avatar-img"
                                  src="${avatar}"
                                  alt="${member.name}"
                                />
                            </div>
                            <div class="jp-chat-meta">
                                <span class="name">${member.name}</span>
                                <span class="subtitle">${member.group}</span>
                            </div>
                        `;

                        li.addEventListener('click', () => {
                            membersList.querySelectorAll('li').forEach((node) => node.classList.remove('active'));
                            li.classList.add('active');
                            selectedMember = { id: member.id, name: member.name, avatar, subtitle: member.group };
                            setPartner(selectedMember);
                            fetchMessages();
                        });

                        membersList.appendChild(li);
                    });
                };

                const renderGroups = () => {
                    if (!groupsList) return;
                    groupsList.innerHTML = '';

                    if (!data.groups || !data.groups.length) {
                        const li = document.createElement('li');
                        li.className = 'jp-chat-empty';
                        li.textContent = data.i18n.noGroups;
                        groupsList.appendChild(li);
                        return;
                    }

                    const groupPlaceholder = data.groupPlaceholder || '';

                    data.groups.forEach((group) => {
                        const li = document.createElement('li');
                        li.dataset.groupId = group.id;
                        li.dataset.ownerId = group.owner_id;
                        const avatar = group.avatar || groupPlaceholder;

                        li.innerHTML = `
                            <span
                              class="juntaplay-group-card_avatar juntaplay-service-card_icon has-image"
                              style="background-image: url('${avatar}');"
                            ></span>
                            <div class="jp-chat-meta">
                                <span class="name">${group.title}</span>
                                <span class="subtitle">${group.subtitle || ''}</span>
                            </div>
                        `;

                        li.addEventListener('click', () => {
                            groupsList.querySelectorAll('li').forEach((node) => node.classList.remove('active'));
                            li.classList.add('active');
                            selectedGroup = group;
                            selectedMember = null;
                            setPartner(null);
                            renderMembers(group.id);
                            chatBody.innerHTML = `<p class="jp-chat-empty">${data.i18n.selectMember}</p>`;
                            toggleInput(false);
                        });

                        groupsList.appendChild(li);
                    });
                };

                if (data.isAdmin) {
                    renderGroups();
                } else if (data.subscriberView && data.subscriberView.group) {
                    shell.classList.add('jp-chat-single-column');
                    selectedGroup = {
                        id: data.subscriberView.group.id,
                        owner_id: data.subscriberView.adminId,
                        title: data.subscriberView.group.title,
                        subtitle: data.subscriberView.group.subtitle,
                    };
                    selectedMember = {
                        id: data.currentUser.id,
                        name: data.currentUser.name,
                        avatar: data.currentUser.avatar,
                        subtitle: data.subscriberView.group.title,
                    };

                    const adminPartner = data.subscriberView.admin || {};

                    setPartner({
                        name: adminPartner.name || data.subscriberView.group.title,
                        avatar: adminPartner.avatar || fallbackAvatar,
                        subtitle: data.i18n.subscriberHeader,
                    });

                    fetchMessages();
                    toggleInput(true);
                }

                sendBtn?.addEventListener('click', sendMessage);
                input?.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        sendMessage();
                    }
                });
            })();
        </script>
        <?php

        return (string) ob_get_clean();
    });
});
