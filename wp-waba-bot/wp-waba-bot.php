<?php
/*
Plugin Name: WABA Bot
Description: Integração do bot de atendimento com a API do WhatsApp Business.
Author: Chat-Pro
Version: 1.2
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/Bot.php';
require_once __DIR__ . '/userDatabase.php';

function waba_bot_default_user_name($userId) {
    return getUserName($userId);
}

function waba_bot_send_message($phone, $text) {
    $token = get_option('waba_bot_token');
    $phoneId = get_option('waba_bot_phone_id');
    if (!$token || !$phoneId) {
        return;
    }
    $url = "https://graph.facebook.com/v17.0/{$phoneId}/messages";
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type' => 'text',
        'text' => ['body' => $text]
    ];
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ],
        'body' => wp_json_encode($payload),
        'timeout' => 20,
        'method' => 'POST'
    ];
    wp_remote_post($url, $args);
}

function waba_bot_load_states() {
    $states = get_option('waba_bot_states');
    return is_array($states) ? $states : [];
}

function waba_bot_save_states(array $states) {
    update_option('waba_bot_states', $states);
}


function waba_bot_enqueue_scripts() {
    wp_enqueue_style('waba-bot', plugins_url('waba-bot.css', __FILE__));
    wp_enqueue_script('waba-bot', plugins_url('waba-bot.js', __FILE__), ['jquery'], null, true);
    wp_localize_script('waba-bot', 'wabaBotAjax', ['ajaxurl' => admin_url('admin-ajax.php')]);
}
add_action('wp_enqueue_scripts', 'waba_bot_enqueue_scripts');

function waba_bot_shortcode() {
    ob_start();
    ?>
    <div id="waba-bot-chat">
        <input type="text" id="waba-bot-phone" placeholder="Seu telefone com código do país" />
        <div id="waba-bot-messages" aria-live="polite"></div>
        <div class="waba-bot-input">
            <input type="text" id="waba-bot-input" placeholder="Digite sua mensagem" />
            <button id="waba-bot-send">Enviar</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('waba_bot', 'waba_bot_shortcode');

function waba_bot_ajax() {
    $phone = sanitize_text_field($_POST['phone']);
    $message = sanitize_text_field($_POST['message']);
    $states = waba_bot_load_states();
    $bot = new Bot('waba_bot_default_user_name');
    $bot->setStates($states);
    $reply = $bot->handleMessage($phone, $message);
    waba_bot_save_states($bot->getStates());
    waba_bot_send_message($phone, $reply);
    wp_send_json(['reply' => $reply]);
}
add_action('wp_ajax_waba_bot_send', 'waba_bot_ajax');
add_action('wp_ajax_nopriv_waba_bot_send', 'waba_bot_ajax');

function waba_bot_settings_menu() {
    add_options_page('WABA Bot', 'WABA Bot', 'manage_options', 'waba-bot', 'waba_bot_settings_page');
}
add_action('admin_menu', 'waba_bot_settings_menu');

function waba_bot_settings_page() {
    if (isset($_POST['waba_bot_save'])) {
        check_admin_referer('waba_bot_options');
        update_option('waba_bot_token', sanitize_text_field($_POST['waba_bot_token']));
        update_option('waba_bot_phone_id', sanitize_text_field($_POST['waba_bot_phone_id']));
        update_option('waba_bot_verify_token', sanitize_text_field($_POST['waba_bot_verify_token']));
        echo '<div class="updated"><p>Configurações salvas.</p></div>';
    }
    $token = esc_attr(get_option('waba_bot_token'));
    $phone_id = esc_attr(get_option('waba_bot_phone_id'));
    $verify = esc_attr(get_option('waba_bot_verify_token'));
    ?>
    <div class="wrap">
        <h1>Configurações do WABA Bot</h1>
        <form method="post">
            <?php wp_nonce_field('waba_bot_options'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="waba_bot_token">Token</label></th>
                    <td><input name="waba_bot_token" type="text" id="waba_bot_token" value="<?php echo $token; ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="waba_bot_phone_id">ID do Número</label></th>
                    <td><input name="waba_bot_phone_id" type="text" id="waba_bot_phone_id" value="<?php echo $phone_id; ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="waba_bot_verify_token">Verify Token</label></th>
                    <td><input name="waba_bot_verify_token" type="text" id="waba_bot_verify_token" value="<?php echo $verify; ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button('Salvar', 'primary', 'waba_bot_save'); ?>
        </form>
    </div>
    <?php
}

function waba_bot_register_routes() {
    register_rest_route('waba-bot/v1', '/webhook', [
        'methods' => ['GET', 'POST'],
        'callback' => 'waba_bot_webhook',
        'permission_callback' => '__return_true'
    ]);
}
add_action('rest_api_init', 'waba_bot_register_routes');

function waba_bot_webhook(WP_REST_Request $request) {
    if ($request->get_method() === 'GET') {
        $verify = get_option('waba_bot_verify_token');
        if ($request['hub_verify_token'] === $verify) {
            return new WP_REST_Response($request['hub_challenge'], 200);
        }
        return new WP_REST_Response('Erro de verificação', 403);
    }

    $data = $request->get_json_params();
    if (!isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        return new WP_REST_Response('OK', 200);
    }
    $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
    $from = $message['from'];
    $text = $message['text']['body'] ?? '';

    $states = waba_bot_load_states();
    $bot = new Bot('waba_bot_default_user_name');
    $bot->setStates($states);
    $reply = $bot->handleMessage($from, $text);
    waba_bot_save_states($bot->getStates());
    waba_bot_send_message($from, $reply);
    return new WP_REST_Response('EVENT_RECEIVED', 200);
}
