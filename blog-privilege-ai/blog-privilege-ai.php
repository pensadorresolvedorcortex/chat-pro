<?php
/**
 * Plugin Name: Blog Privilege AI - SEO
 * Description: Prepara os artigos gerados pelo Blog Privilege AI para a análise do Yoast SEO.
 * Version: 1.2.0
 * Author: Agência Privilege
 * Text Domain: blog-privilege-ai
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/class-bpai-seo-optimizer.php';

add_action(
	'plugins_loaded',
	static function () {
		BPAI_SEO_Optimizer::instance();
	}
);
