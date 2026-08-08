<?php
/**
 * Minimal regression checks that run without a WordPress installation.
 */

function add_action() {}
function apply_filters($hook, $value) {
	unset($hook);
	return $value;
}
function get_bloginfo() {
	return 'UTF-8';
}
function wp_strip_all_tags($text) {
	return strip_tags($text);
}
function remove_accents($text) {
	return strtr($text, array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ç' => 'c'));
}

define('ABSPATH', __DIR__);
require dirname(__DIR__) . '/blog-privilege-ai/blog-privilege-ai.php';

$method = new ReflectionMethod(Blog_Privilege_AI_SEO::class, 'keyphrase');
$cases = array(
	'Google Meu Negócio / Perfil da Empresa: o que realmente gera resultado' => 'Google Meu Negócio',
	'SEO Local Juiz de Fora: 3 decisões que impactam tráfego qualificado' => 'SEO Local Juiz de Fora',
	'9 erros em marketing digital em Juiz de Fora que aumentam a rejeição' => 'marketing digital em Juiz de Fora',
);

foreach ($cases as $title => $expected) {
	$actual = $method->invoke(null, $title);
	if ($expected !== $actual) {
		fwrite(STDERR, sprintf("Expected '%s', got '%s' for '%s'.\n", $expected, $actual, $title));
		exit(1);
	}
}

echo "All keyphrase checks passed.\n";
