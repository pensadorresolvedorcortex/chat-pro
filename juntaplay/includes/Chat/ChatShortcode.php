<?php

declare(strict_types=1);

namespace JuntaPlay\Chat;

defined('ABSPATH') || exit;

class ChatShortcode
{
    public static function init(): void
    {
        add_shortcode('juntaplay_chat', [self::class, 'render_chat']);

        add_filter('woocommerce_account_menu_items', [self::class, 'add_account_menu']);
        add_action('woocommerce_account_juntaplay-chat_endpoint', [self::class, 'render_account_endpoint']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function render_chat(): string
    {
        if (!is_user_logged_in()) {
            return '<p>É necessário estar logado para acessar suas mensagens.</p>';
        }

        $html = '
        <div id="jp-chat-wrapper">
            <div id="jp-chat-header">
                <img src="" class="jp-chat-avatar" alt="Admin">
                <h3>Chat com o administrador</h3>
            </div>
            <div id="jp-chat-messages">
                <div class="jp-chat-bubble jp-chat-bubble-admin">Mensagem exemplo do admin</div>
                <div class="jp-chat-bubble jp-chat-bubble-user">Mensagem exemplo do usuário</div>
            </div>
            <div id="jp-chat-preview">Pré-visualização da imagem (aguardando upload)</div>
            <div id="jp-chat-input">
                <button id="jp-chat-upload-btn">📎</button>
                <input type="text" placeholder="Digite sua mensagem..." id="jp-chat-text">
                <button id="jp-chat-send-btn">Enviar</button>
            </div>
        </div>
        ';

        return $html;
    }

    public static function add_account_menu(array $items): array
    {
        $items['juntaplay-chat'] = 'Mensagens';
        return $items;
    }

    public static function render_account_endpoint(): void
    {
        echo do_shortcode('[juntaplay_chat]');
    }

    public static function enqueue_assets(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        if (!isset($_GET['section']) || $_GET['section'] !== 'juntaplay-chat') {
            return;
        }

        $group_id = isset($_GET['group_id']) ? absint($_GET['group_id']) : 0;

        wp_enqueue_style(
            'juntaplay-chat-css',
            JP_URL . 'assets/css/chat.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'juntaplay-chat-js',
            plugins_url('/assets/js/chat.js', __FILE__),
            [],
            '1.0.0',
            true
        );

        wp_localize_script('juntaplay-chat-js', 'jpChatData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('juntaplay_chat_nonce'),
            'group_id' => $group_id,
        ]);
    }
}
