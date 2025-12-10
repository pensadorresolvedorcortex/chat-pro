<?php
/**
 * Frontend chat template for member ↔ admin conversations.
 *
 * Variables from shortcode:
 * @var array $chat_groups
 * @var string $chat_rest
 */

declare(strict_types=1);

use function esc_url;
use function esc_attr;
use function esc_html;

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$current_name = $current_user && $current_user->exists() ? $current_user->display_name : '';
?>
<div class="juntaplay-chat" data-rest="<?php echo esc_attr($chat_rest); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>" data-user="<?php echo esc_attr((string) get_current_user_id()); ?>">
    <aside class="juntaplay-chat__sidebar">
        <div class="juntaplay-chat__sidebar-header">
            <div class="juntaplay-chat__title"><?php esc_html_e('Mensagens do grupo', 'juntaplay'); ?></div>
            <p class="juntaplay-chat__subtitle"><?php esc_html_e('Converse apenas com o administrador de cada grupo.', 'juntaplay'); ?></p>
        </div>
        <div class="juntaplay-chat__threads" data-chat-threads>
            <div class="juntaplay-chat__empty">
                <p><?php esc_html_e('Selecione um grupo para começar.', 'juntaplay'); ?></p>
            </div>
        </div>
    </aside>
    <section class="juntaplay-chat__main">
        <header class="juntaplay-chat__header" data-chat-header>
            <div class="juntaplay-chat__avatar" aria-hidden="true"></div>
            <div>
                <div class="juntaplay-chat__group" data-chat-group><?php esc_html_e('Escolha um grupo', 'juntaplay'); ?></div>
                <div class="juntaplay-chat__admin" data-chat-admin></div>
            </div>
        </header>
        <div class="juntaplay-chat__history" data-chat-history>
            <div class="juntaplay-chat__empty">
                <p><?php esc_html_e('Selecione um chat para ver o histórico.', 'juntaplay'); ?></p>
            </div>
        </div>
        <footer class="juntaplay-chat__composer">
            <div class="juntaplay-chat__composer-actions">
                <button type="button" class="juntaplay-chat__media" data-chat-upload>
                    <span aria-hidden="true">📷</span>
                    <span class="screen-reader-text"><?php esc_html_e('Enviar imagem', 'juntaplay'); ?></span>
                </button>
            </div>
            <div class="juntaplay-chat__composer-form" data-chat-form>
                <input type="hidden" name="chat_id" data-chat-id value="" />
                <input type="hidden" name="attachment_id" data-chat-attachment value="" />
                <input type="text" class="juntaplay-chat__input" placeholder="<?php echo esc_attr__('Digite sua mensagem...', 'juntaplay'); ?>" data-chat-input />
                <button type="button" class="juntaplay-button juntaplay-button--primary" data-chat-send><?php esc_html_e('Enviar', 'juntaplay'); ?></button>
            </div>
        </footer>
    </section>
</div>
<script>
(() => {
    const root = document.querySelector('.juntaplay-chat');
    if (!root) return;

    const restBase = root.dataset.rest;
    const nonce = root.dataset.nonce;
    const userId = parseInt(root.dataset.user || '0', 10);
    const threadsEl = root.querySelector('[data-chat-threads]');
    const historyEl = root.querySelector('[data-chat-history]');
    const groupEl = root.querySelector('[data-chat-group]');
    const adminEl = root.querySelector('[data-chat-admin]');
    const inputEl = root.querySelector('[data-chat-input]');
    const sendBtn = root.querySelector('[data-chat-send]');
    const chatIdInput = root.querySelector('[data-chat-id]');
    const attachmentInput = root.querySelector('[data-chat-attachment]');
    const uploadBtn = root.querySelector('[data-chat-upload]');
    let activeChat = null;
    let lastTimestamp = '';
    let refreshTimer = null;

    function api(path, options = {}) {
        const headers = Object.assign({ 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' }, options.headers || {});
        return fetch(restBase + path, Object.assign({}, options, { headers, credentials: 'include' })).then((response) => response.json());
    }

    function renderThreads(items) {
        if (!items.length) {
            threadsEl.innerHTML = '<div class="juntaplay-chat__empty"><p><?php echo esc_html__('Nenhum chat disponível ainda.', 'juntaplay'); ?></p></div>';
            return;
        }

        const list = document.createElement('ul');
        list.className = 'juntaplay-chat__thread-list';

        items.forEach((item) => {
            const li = document.createElement('li');
            li.className = 'juntaplay-chat__thread';
            li.dataset.chatId = item.id;
            li.innerHTML = `
                <div class="juntaplay-chat__thread-title">${item.group_title || '<?php echo esc_html__('Grupo', 'juntaplay'); ?>'}</div>
                <div class="juntaplay-chat__thread-meta">${item.member_name || ''}</div>
                <div class="juntaplay-chat__thread-last">${item.last_type === 'image' ? '<?php echo esc_html__('📷 Imagem', 'juntaplay'); ?>' : (item.last_message || '')}</div>
            `;
            li.addEventListener('click', () => {
                threadsEl.querySelectorAll('.is-active').forEach((el) => el.classList.remove('is-active'));
                li.classList.add('is-active');
                loadChat(item.id, item);
            });
            list.appendChild(li);
        });

        threadsEl.innerHTML = '';
        threadsEl.appendChild(list);

        autoSelectThread(items);
    }

    function autoSelectThread(items) {
        const params = new URLSearchParams(window.location.search);
        const targetGroupId = parseInt(params.get('group_id') || '0', 10);
        const targetParticipantId = parseInt(params.get('participant_id') || '0', 10);

        let target = null;

        if (targetGroupId > 0 && targetParticipantId > 0) {
            target = items.find((item) => item.group_id === targetGroupId && (item.member_id === targetParticipantId || item.admin_id === targetParticipantId));
        }

        if (!target && targetGroupId > 0) {
            const groupMatches = items.filter((item) => item.group_id === targetGroupId);
            if (groupMatches.length === 1) {
                target = groupMatches[0];
            }
        }

        if (target) {
            const targetEl = threadsEl.querySelector(`[data-chat-id="${target.id}"]`);
            if (targetEl) {
                targetEl.click();
            }
        }
    }

    function renderMessages(messages) {
        historyEl.innerHTML = '';
        const list = document.createElement('div');
        list.className = 'juntaplay-chat__bubble-list';

        messages.forEach((msg) => {
            const bubble = document.createElement('div');
            bubble.className = 'juntaplay-chat__bubble';
            if (msg.sender_id === userId) {
                bubble.classList.add('is-self');
            }
            const header = document.createElement('div');
            header.className = 'juntaplay-chat__bubble-meta';
            header.textContent = msg.sender_name || '';
            bubble.appendChild(header);

            if (msg.type === 'image' && msg.attachment_id) {
                const img = document.createElement('img');
                img.src = wp?.media?.attachment(msg.attachment_id)?.get('url') || '';
                img.alt = msg.sender_name || '';
                img.className = 'juntaplay-chat__image';
                bubble.appendChild(img);
            }

            if (msg.message) {
                const body = document.createElement('div');
                body.className = 'juntaplay-chat__bubble-text';
                body.textContent = msg.message;
                bubble.appendChild(body);
            }

            list.appendChild(bubble);
            lastTimestamp = msg.created_at;
        });

        historyEl.appendChild(list);
        historyEl.scrollTop = historyEl.scrollHeight;
    }

    function loadThreads() {
        api('chats').then((response) => {
            renderThreads(response.items || []);
        });
    }

    function loadChat(chatId, context) {
        activeChat = chatId;
        chatIdInput.value = chatId;
        lastTimestamp = '';
        groupEl.textContent = context.group_title || '<?php echo esc_html__('Grupo', 'juntaplay'); ?>';
        adminEl.textContent = context.role === 'member'
            ? '<?php echo esc_html__('Administrador', 'juntaplay'); ?>'
            : (context.member_name || '');
        fetchMessages();
    }

    function fetchMessages() {
        if (!activeChat) return;
        api(`chats/${activeChat}/messages`, { headers: { 'X-WP-Nonce': nonce } }).then((response) => {
            renderMessages(response.messages || []);
        });

        if (refreshTimer) {
            clearTimeout(refreshTimer);
        }
        refreshTimer = window.setTimeout(fetchMessages, 8000);
    }

    function sendMessage() {
        if (!activeChat) return;
        const text = inputEl.value.trim();
        const attachmentId = parseInt(attachmentInput.value || '0', 10);
        if (!text && !attachmentId) {
            inputEl.focus();
            return;
        }

        api(`chats/${activeChat}/messages`, {
            method: 'POST',
            body: JSON.stringify({ message: text, attachment_id: attachmentId }),
        }).then((response) => {
            inputEl.value = '';
            attachmentInput.value = '';
            renderMessages(response.messages || []);
        });
    }

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

    if (uploadBtn && window.wp && wp.media) {
        let frame = null;
        uploadBtn.addEventListener('click', () => {
            if (!frame) {
                frame = wp.media({ title: '<?php echo esc_js(__('Selecionar imagem', 'juntaplay')); ?>', multiple: false, library: { type: 'image' } });
                frame.on('select', () => {
                    const attachment = frame.state().get('selection').first().toJSON();
                    attachmentInput.value = attachment.id;
                });
            }
            frame.open();
        });
    }

    loadThreads();
})();
</script>
