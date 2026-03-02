<?php
/**
 * Exemplo de integração do kit visual RMA no tema WordPress.
 *
 * Copie este trecho para o functions.php do tema (ou plugin de tema) e ajuste
 * caminhos/markup conforme necessário.
 *
 * Observação: o script do wizard (`rma-glass-theme.js`) também é enfileirado.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', static function (): void {
    $handle = 'rma-glass-theme';
    $src = get_stylesheet_directory_uri() . '/ui/rma-glass-theme.css';

    wp_register_style($handle, $src, [], '1.0.0');
    wp_enqueue_style($handle);

    wp_enqueue_script(
        'rma-glass-theme-js',
        get_stylesheet_directory_uri() . '/ui/rma-glass-theme.js',
        [],
        '1.0.0',
        true
    );

    // Garante disponibilidade da fonte Federo.
    wp_enqueue_style(
        'rma-federo-font',
        'https://fonts.googleapis.com/css2?family=Federo&display=swap',
        [],
        null
    );
});

add_shortcode('rma_glass_card_demo', static function (): string {
    ob_start();
    ?>
    <section class="rma-glass-card" style="margin: 20px 0;">
      <span class="rma-badge">RMA • Glasmorphism Ultra White</span>
      <h2 class="rma-glass-title">Card de demonstração</h2>
      <p class="rma-glass-subtitle">
        Este bloco usa Federo, #7bad39 e #37302c com acabamento translúcido branco.
      </p>
      <div class="rma-actions">
        <button class="rma-button" type="button">Ação principal</button>
      </div>
    </section>
    <?php
    return (string) ob_get_clean();
});
