<?php
/*
Plugin Name: Elfsight WhatsApp Chat CC
Description: Stay always in touch with users through a popular chat on your website
Plugin URI: https://elfsight.com/whatsapp-chat-widget/codecanyon/?utm_source=markets&utm_medium=codecanyon&utm_campaign=whatsapp-chat&utm_content=plugin-site
Version: 1.2.0
Author: Elfsight
Author URI: https://elfsight.com/?utm_source=markets&utm_medium=codecanyon&utm_campaign=whatsapp-chat&utm_content=plugins-list
*/

if (!defined('ABSPATH')) exit;


require_once('core/elfsight-plugin.php');

$elfsight_whatsapp_chat_config_path = plugin_dir_path(__FILE__) . 'config.json';
$elfsight_whatsapp_chat_config = json_decode(file_get_contents($elfsight_whatsapp_chat_config_path), true);

new ElfsightWhatsappChatPlugin(
    array(
        'name' => esc_html__('WhatsApp Chat'),
        'description' => esc_html__('Stay always in touch with users through a popular chat on your website'),
        'slug' => 'elfsight-whatsapp-chat',
        'version' => '1.2.0',
        'text_domain' => 'elfsight-whatsapp-chat',

        'editor_settings' => $elfsight_whatsapp_chat_config['settings'],
        'editor_preferences' => $elfsight_whatsapp_chat_config['preferences'],

        'plugin_name' => esc_html__('Elfsight WhatsApp Chat'),
        'plugin_file' => __FILE__,
        'plugin_slug' => plugin_basename(__FILE__),

        'vc_icon' => 'dashicons-format-chat',
        'menu_icon' => 'dashicons-format-chat',

        'product_url' => esc_url('https://codecanyon.net/item/whatsapp-chat-wordpress-whatsapp-chat-plugin/23890257?ref=Elfsight'),
        'update_url' => esc_url('https://a.elfsight.com/updates/v1/'),
        'helpscout_plugin_id' => 110729
    )
);
