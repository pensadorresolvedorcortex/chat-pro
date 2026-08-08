<?php
/**
 * Plugin Name: Blog Privilege AI — SEO para Yoast
 * Description: Preenche metadados do Yoast SEO e oferece otimização em massa para os artigos gerados pelo Blog Privilege AI.
 * Version: 1.1.1
 * Author: Agência Privilege
 * Text Domain: blog-privilege-ai
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

final class Blog_Privilege_AI_SEO {
	private const NONCE_ACTION = 'bpai_seo_bulk_optimize';
	private const OPTION_NOTICE = 'bpai_seo_bulk_notice_';

	public static function init(): void {
		add_action('save_post_post', array(__CLASS__, 'optimize_on_save'), 30, 3);
		add_action('admin_menu', array(__CLASS__, 'register_tools_page'));
		add_action('admin_post_bpai_seo_bulk_optimize', array(__CLASS__, 'bulk_optimize'));
		add_action('admin_notices', array(__CLASS__, 'show_bulk_notice'));
	}

	/**
	 * Adds Yoast metadata whenever a post is created or updated.
	 */
	public static function optimize_on_save(int $post_id, WP_Post $post, bool $update): void {
		unset($update);

		if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || 'auto-draft' === $post->post_status) {
			return;
		}

		self::optimize_post($post_id);
	}

	/**
	 * Sets values understood natively by Yoast without replacing hand-written ones.
	 */
	private static function optimize_post(int $post_id, bool $overwrite = false): bool {
		$post = get_post($post_id);
		if (!$post instanceof WP_Post || 'post' !== $post->post_type) {
			return false;
		}

		$keyphrase = self::keyphrase($post->post_title);
		if ('' === $keyphrase) {
			return false;
		}

		$description = self::description($post, $keyphrase);
		$seo_title = self::seo_title($post->post_title, $keyphrase);

		self::update_meta($post_id, '_yoast_wpseo_focuskw', $keyphrase, $overwrite);
		self::update_meta($post_id, '_yoast_wpseo_title', $seo_title, $overwrite);
		self::update_meta($post_id, '_yoast_wpseo_metadesc', $description, $overwrite);

		// Gives Yoast a useful social preview instead of an empty card.
		self::update_meta($post_id, '_yoast_wpseo_opengraph-title', $seo_title, $overwrite);
		self::update_meta($post_id, '_yoast_wpseo_opengraph-description', $description, $overwrite);
		self::update_meta($post_id, '_yoast_wpseo_twitter-title', $seo_title, $overwrite);
		self::update_meta($post_id, '_yoast_wpseo_twitter-description', $description, $overwrite);

		self::optimize_featured_image($post_id, $keyphrase);

		/**
		 * Fires after the SEO fields for a post are prepared.
		 *
		 * @param int    $post_id   Post ID.
		 * @param string $keyphrase Focus keyphrase selected from the title.
		 */
		do_action('bpai_seo_post_optimized', $post_id, $keyphrase);

		return true;
	}

	private static function update_meta(int $post_id, string $key, string $value, bool $overwrite): void {
		if ($overwrite || '' === (string) get_post_meta($post_id, $key, true)) {
			update_post_meta($post_id, $key, wp_slash($value));
		}
	}

	/**
	 * Turns a headline into a natural, short focus keyphrase.
	 */
	private static function keyphrase(string $title): string {
		$title = trim(wp_strip_all_tags(html_entity_decode($title, ENT_QUOTES, get_bloginfo('charset'))));
		$title = preg_replace('/^[0-9]+\s+(?:erros?|dicas?|passos?|formas?|motivos?|estrat[eé]gias?)\s+(?:de|para|que|em)?\s*/iu', '', $title);
		$title = preg_replace('/^[0-9]+\s+/', '', $title);
		$title = preg_split('/\s*(?:\/|:|\||–|—)\s*/u', $title, 2)[0] ?? $title;
		$words = preg_split('/\s+/u', $title, -1, PREG_SPLIT_NO_EMPTY);

		if (!$words) {
			return '';
		}

		$keyphrase = implode(' ', array_slice($words, 0, 6));

		/**
		 * Filters the automatically selected focus keyphrase.
		 *
		 * Returning a manual phrase from the AI generator is the most reliable
		 * way to keep the exact phrase consistent in the title and article.
		 */
		return trim((string) apply_filters('bpai_seo_focus_keyphrase', $keyphrase, $title));
	}

	/**
	 * Produces a readable snippet close to Yoast's recommended preview length.
	 */
	private static function description(WP_Post $post, string $keyphrase): string {
		$source = has_excerpt($post) ? $post->post_excerpt : $post->post_content;
		$source = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($source))));

		if ('' === $source) {
			$source = sprintf(__('Descubra como %s pode gerar resultados e veja dicas práticas para aplicar essa estratégia no seu negócio.', 'blog-privilege-ai'), $keyphrase);
		} elseif (!self::contains($source, $keyphrase)) {
			$source = $keyphrase . ': ' . lcfirst($source);
		}

		$description = wp_html_excerpt($source, 155, '…');

		/** This filter allows site-specific copy adjustments. */
		return (string) apply_filters('bpai_seo_meta_description', $description, $post, $keyphrase);
	}

	private static function seo_title(string $title, string $keyphrase): string {
		$title = trim(wp_strip_all_tags($title));
		if (!self::contains($title, $keyphrase)) {
			$title = $keyphrase . ' — ' . $title;
		}

		// Yoast replaces this variable with the configured site name.
		return wp_html_excerpt($title, 52, '') . ' %%sep%% %%sitename%%';
	}

	/**
	 * Case- and accent-insensitive search without requiring the mbstring extension.
	 */
	private static function contains(string $haystack, string $needle): bool {
		return false !== stripos(remove_accents($haystack), remove_accents($needle));
	}

	private static function optimize_featured_image(int $post_id, string $keyphrase): void {
		$thumbnail_id = get_post_thumbnail_id($post_id);
		if (!$thumbnail_id || '' !== trim((string) get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true))) {
			return;
		}

		update_post_meta($thumbnail_id, '_wp_attachment_image_alt', $keyphrase);
	}

	public static function register_tools_page(): void {
		add_management_page(
			__('SEO do Blog Privilege AI', 'blog-privilege-ai'),
			__('SEO do Blog Privilege AI', 'blog-privilege-ai'),
			'edit_others_posts',
			'bpai-seo',
			array(__CLASS__, 'render_tools_page')
		);
	}

	public static function render_tools_page(): void {
		if (!current_user_can('edit_others_posts')) {
			wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'blog-privilege-ai'));
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e('SEO do Blog Privilege AI', 'blog-privilege-ai'); ?></h1>
			<p><?php esc_html_e('Preencha a frase-chave, o título SEO, a meta description, as prévias sociais e o texto alternativo da imagem destacada em todos os posts.', 'blog-privilege-ai'); ?></p>
			<p><strong><?php esc_html_e('Importante:', 'blog-privilege-ai'); ?></strong> <?php esc_html_e('o semáforo também avalia o texto. Para obter verde, mantenha a frase-chave no primeiro parágrafo, em ao menos um H2 e naturalmente ao longo do artigo.', 'blog-privilege-ai'); ?></p>
			<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
				<input type="hidden" name="action" value="bpai_seo_bulk_optimize">
				<?php wp_nonce_field(self::NONCE_ACTION); ?>
				<label><input type="checkbox" name="overwrite" value="1"> <?php esc_html_e('Sobrescrever campos SEO já preenchidos', 'blog-privilege-ai'); ?></label>
				<?php submit_button(__('Otimizar todos os posts', 'blog-privilege-ai')); ?>
			</form>
		</div>
		<?php
	}

	public static function bulk_optimize(): void {
		if (!current_user_can('edit_others_posts')) {
			wp_die(esc_html__('Você não tem permissão para executar esta ação.', 'blog-privilege-ai'));
		}

		check_admin_referer(self::NONCE_ACTION);
		$overwrite = isset($_POST['overwrite']) && '1' === sanitize_text_field(wp_unslash($_POST['overwrite']));
		$post_ids = get_posts(array(
			'post_type'              => 'post',
			'post_status'            => array('publish', 'future', 'draft', 'pending'),
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		));

		$optimized = 0;
		foreach ($post_ids as $post_id) {
			if (self::optimize_post((int) $post_id, $overwrite)) {
				++$optimized;
			}
		}

		set_transient(self::OPTION_NOTICE . get_current_user_id(), $optimized, MINUTE_IN_SECONDS);
		wp_safe_redirect(admin_url('tools.php?page=bpai-seo'));
		exit;
	}

	public static function show_bulk_notice(): void {
		$count = get_transient(self::OPTION_NOTICE . get_current_user_id());
		if (false === $count) {
			return;
		}

		delete_transient(self::OPTION_NOTICE . get_current_user_id());
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(sprintf(_n('%d post otimizado para o Yoast SEO.', '%d posts otimizados para o Yoast SEO.', (int) $count, 'blog-privilege-ai'), (int) $count))
		);
	}
}

Blog_Privilege_AI_SEO::init();
