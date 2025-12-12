<?php
/**
 * Admin message center within the profile.
 *
 * Expected context variables:
 * @var string $base_url
 * @var array<int, array<string, mixed>> $groups
 * @var array<string, mixed>|null $selected_group
 * @var array<int, array<string, mixed>> $participants
 * @var array<string, mixed>|null $selected_participant
 * @var int $chat_id
 * @var string $rest_base
 * @var string $rest_nonce
 * @var int $current_user_id
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$base_url             = isset($base_url) ? (string) $base_url : '';
$groups               = isset($groups) && is_array($groups) ? $groups : [];
$selected_group       = isset($selected_group) && is_array($selected_group) ? $selected_group : null;
$participants         = isset($participants) && is_array($participants) ? $participants : [];
$selected_participant = isset($selected_participant) && is_array($selected_participant) ? $selected_participant : null;
$chat_id              = isset($chat_id) ? (int) $chat_id : 0;
$rest_base            = isset($rest_base) ? (string) $rest_base : '';
$rest_nonce           = isset($rest_nonce) ? (string) $rest_nonce : '';
$current_user_id      = isset($current_user_id) ? (int) $current_user_id : 0;
$section_key          = isset($section_key) ? (string) $section_key : 'juntaplay-chat';
$is_admin_view        = !empty($is_admin_view);

$group_selector_label = esc_html__('Selecione um grupo que você administra', 'juntaplay');
$subscriber_label     = esc_html__('Escolha um assinante ativo para abrir o chat.', 'juntaplay');

if (!function_exists('juntaplay_messages_link')) {
    function juntaplay_messages_link(string $base, array $args): string
    {
        return $base !== '' ? add_query_arg($args, $base) : '';
    }
}

?>
<div class="juntaplay-card">
    <?php if ($is_admin_view) : ?>
        <form method="get" class="juntaplay-form" style="margin-bottom:16px;">
            <input type="hidden" name="section" value="<?php echo esc_attr($section_key); ?>" />
            <label class="juntaplay-profile__label" for="juntaplay-messages-group"><?php echo $group_selector_label; ?></label>
            <div style="display:flex; gap:8px; align-items:center;">
                <select id="juntaplay-messages-group" name="group_id" style="flex:1;">
                    <option value=""><?php esc_html_e('Selecione', 'juntaplay'); ?></option>
                    <?php foreach ($groups as $group) :
                        $group_id    = isset($group['id']) ? (int) $group['id'] : 0;
                        $is_selected = $selected_group && $group_id === (int) ($selected_group['id'] ?? 0);
                        ?>
                        <option value="<?php echo esc_attr((string) $group_id); ?>" <?php selected($is_selected); ?>>
                            <?php echo esc_html($group['title'] ?? __('Grupo', 'juntaplay')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="juntaplay-button juntaplay-button--primary"><?php esc_html_e('Abrir', 'juntaplay'); ?></button>
            </div>
        </form>

        <?php if (empty($groups)) : ?>
            <p class="juntaplay-profile__description"><?php esc_html_e('Nenhum grupo encontrado onde você seja o administrador.', 'juntaplay'); ?></p>
        <?php endif; ?>

        <?php if ($selected_group) : ?>
            <div class="juntaplay-card" style="margin-bottom:16px;">
                <div class="juntaplay-profile__label" style="margin-bottom:8px;">
                    <?php echo esc_html($subscriber_label); ?>
                </div>
                <?php if ($participants) : ?>
                    <ul class="juntaplay-profile__list" role="list">
                        <?php foreach ($participants as $participant) :
                            $participant_id = isset($participant['user_id']) ? (int) $participant['user_id'] : 0;
                            $participant_name = $participant['name'] ?? ($participant['display_name'] ?? __('Assinante', 'juntaplay'));
                            $is_active = $selected_participant && $participant_id === (int) ($selected_participant['user_id'] ?? 0);
                            $participant_url = juntaplay_messages_link($base_url, [
                                'section'        => $section_key,
                                'group_id'       => $selected_group['id'] ?? 0,
                                'participant_id' => $participant_id,
                            ]);
                            ?>
                            <li class="juntaplay-profile__row juntaplay-profile__row--custom<?php echo $is_active ? ' is-active' : ''; ?>">
                                <div class="juntaplay-profile__content">
                                    <div class="juntaplay-profile__label"><?php echo esc_html($participant_name); ?></div>
                                    <p class="juntaplay-profile__description">
                                        <?php esc_html_e('Assinante ativo', 'juntaplay'); ?>
                                    </p>
                                </div>
                                <div class="juntaplay-profile__custom">
                                    <?php if ($participant_url !== '') : ?>
                                        <a class="juntaplay-button" href="<?php echo esc_url($participant_url); ?>"><?php esc_html_e('Abrir chat', 'juntaplay'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="juntaplay-profile__description"><?php esc_html_e('Nenhum assinante ativo disponível neste grupo.', 'juntaplay'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($chat_id > 0 && $selected_group && $selected_participant) :
        $group_title       = $selected_group['title'] ?? __('Grupo', 'juntaplay');
        $participant_title = $selected_participant['name'] ?? $selected_participant['display_name'] ?? __('Assinante', 'juntaplay');
        ?>
        <div class="juntaplay-chat" data-admin-chat data-rest="<?php echo esc_attr($rest_base); ?>" data-nonce="<?php echo esc_attr($rest_nonce); ?>" data-chat-id="<?php echo esc_attr((string) $chat_id); ?>" data-user-id="<?php echo esc_attr((string) $current_user_id); ?>">
            <section class="juntaplay-chat__main" style="grid-column: 1 / -1;">
                <header class="juntaplay-chat__header">
                    <div>
                        <div class="juntaplay-chat__group"><?php echo esc_html($group_title); ?></div>
                        <div class="juntaplay-chat__admin"><?php echo esc_html($participant_title); ?></div>
                    </div>
                </header>
                <div class="juntaplay-chat__history" data-admin-chat-history>
                    <div class="juntaplay-chat__empty">
                        <p><?php esc_html_e('Envie uma mensagem para iniciar a conversa.', 'juntaplay'); ?></p>
                    </div>
                </div>
                <footer class="juntaplay-chat__composer">
                    <div class="juntaplay-chat__composer-form">
                        <input type="text" class="juntaplay-chat__input" placeholder="<?php echo esc_attr__('Digite sua mensagem...', 'juntaplay'); ?>" data-admin-chat-input />
                        <button type="button" class="juntaplay-button juntaplay-button--primary" data-admin-chat-send><?php esc_html_e('Enviar', 'juntaplay'); ?></button>
                    </div>
                </footer>
            </section>
        </div>
        <script>
        (() => {
            const container = document.querySelector('[data-admin-chat]');
            if (!container) return;

            const restBase = container.dataset.rest || '';
            const nonce = container.dataset.nonce || '';
            const chatId = parseInt(container.dataset.chatId || '0', 10);
            const userId = parseInt(container.dataset.userId || '0', 10);
            const historyEl = container.querySelector('[data-admin-chat-history]');
            const inputEl = container.querySelector('[data-admin-chat-input]');
            const sendBtn = container.querySelector('[data-admin-chat-send]');
            let timer = null;

            if (!restBase || !nonce || !chatId) {
                return;
            }

            const api = (path, options = {}) => {
                const headers = Object.assign({ 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' }, options.headers || {});
                return fetch(restBase.replace(/\/$/, '') + '/' + path.replace(/^\//, ''), Object.assign({}, options, { headers, credentials: 'include' }))
                    .then((response) => response.json());
            };

            const renderMessages = (items) => {
                if (!historyEl) return;
                historyEl.innerHTML = '';

                if (!items.length) {
                    const empty = document.createElement('div');
                    empty.className = 'juntaplay-chat__empty';
                    empty.innerHTML = '<p><?php echo esc_html__('Envie a primeira mensagem.', 'juntaplay'); ?></p>';
                    historyEl.appendChild(empty);
                    return;
                }

                const list = document.createElement('div');
                list.className = 'juntaplay-chat__bubble-list';

                items.forEach((msg) => {
                    const bubble = document.createElement('div');
                    bubble.className = 'juntaplay-chat__bubble';
                    if (msg.sender_id === userId) {
                        bubble.classList.add('is-self');
                    }

                    const meta = document.createElement('div');
                    meta.className = 'juntaplay-chat__bubble-meta';
                    meta.textContent = msg.sender_name || '';
                    bubble.appendChild(meta);

                    if (msg.message) {
                        const text = document.createElement('div');
                        text.className = 'juntaplay-chat__bubble-text';
                        text.textContent = msg.message;
                        bubble.appendChild(text);
                    }

                    list.appendChild(bubble);
                });

                historyEl.appendChild(list);
                historyEl.scrollTop = historyEl.scrollHeight;
            };

            const fetchMessages = () => {
                api(`chats/${chatId}/messages`).then((response) => {
                    renderMessages(response.messages || []);
                }).finally(() => {
                    if (timer) {
                        clearTimeout(timer);
                    }
                    timer = window.setTimeout(fetchMessages, 8000);
                });
            };

            const sendMessage = () => {
                if (!inputEl) return;
                const message = inputEl.value.trim();
                if (!message) {
                    inputEl.focus();
                    return;
                }

                api(`chats/${chatId}/messages`, {
                    method: 'POST',
                    body: JSON.stringify({ message }),
                }).then((response) => {
                    inputEl.value = '';
                    renderMessages(response.messages || []);
                });
            };

            if (sendBtn) {
                sendBtn.addEventListener('click', sendMessage);
            }

            if (inputEl) {
                inputEl.addEventListener('keyup', (event) => {
                    if (event.key === 'Enter') {
                        sendMessage();
                    }
                });
            }

            fetchMessages();
        })();
        </script>
    <?php elseif ($selected_group && $is_admin_view) : ?>
        <p class="juntaplay-profile__description"><?php esc_html_e('Selecione um assinante ativo para abrir o chat.', 'juntaplay'); ?></p>
    <?php endif; ?>
</div>
