<?php
/**
 * Lightweight regression checks that do not require a WordPress installation.
 */

define( 'ABSPATH', __DIR__ . '/' );

class WP_Post {
	public $post_title;
	public $post_content;
}

function add_action() {}
function add_filter() {}
function apply_filters( $name, $value ) { return $value; }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $value ) { return $value; }
function home_url() { return 'https://example.test/'; }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function strip_shortcodes( $value ) { return $value; }
function wp_html_excerpt( $value, $length, $more ) { return mb_substr( $value, 0, $length ) . ( mb_strlen( $value ) > $length ? $more : '' ); }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }

require dirname( __DIR__ ) . '/blog-privilege-ai/includes/class-bpai-seo-optimizer.php';

$optimizer = BPAI_SEO_Optimizer::instance();
$invoke    = static function ( $method, ...$arguments ) use ( $optimizer ) {
	$reflection = new ReflectionMethod( $optimizer, $method );
	return $reflection->invoke( $optimizer, ...$arguments );
};

$keyword = 'marketing digital';
$body    = str_repeat( '<p>Planejamento, conteúdo e mensuração melhoram os resultados da estratégia.</p>', 45 );
$body    = '<img src="hero.jpg">' . $body;
$content = $invoke( 'improve_content', $body, $keyword, 'Marketing digital para empresas' );

assert( false !== mb_stripos( mb_substr( wp_strip_all_tags( $content ), 0, 250 ), $keyword ) );
assert( 1 === preg_match( '/<h2[^>]*>[^<]*marketing digital/iu', $content ) );
assert( 1 === preg_match( '/<img[^>]+alt="marketing digital"/iu', $content ) );
assert( 1 === preg_match( '/<a[^>]+href="https:\/\/example.test\//iu', $content ) );
assert( $invoke( 'word_count', 'ação, conteúdo e otimização' ) === 4 );

$post               = new WP_Post();
$post->post_title    = 'Marketing digital: guia para empresas';
$post->post_content  = $content . '<p>Marketing digital exige acompanhamento contínuo.</p>';
$score              = $invoke( 'calculate_score', $post, $keyword );

assert( $score >= 75, 'A complete generated article should qualify for a green score.' );
fwrite( STDOUT, "SEO optimizer checks passed (score: {$score}).\n" );

