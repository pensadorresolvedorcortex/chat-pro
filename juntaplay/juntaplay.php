<?php
/**
 * Plugin Name: JuntaPlay Chat
 * Description: Interface completa do chat JuntaPlay com shortcode [juntaplay_chatonline].
 * Version: 1.1.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('JUNTAPLAY_CHAT_FILE')) {
    define('JUNTAPLAY_CHAT_FILE', __FILE__);
}

require_once __DIR__ . '/chat.php';
