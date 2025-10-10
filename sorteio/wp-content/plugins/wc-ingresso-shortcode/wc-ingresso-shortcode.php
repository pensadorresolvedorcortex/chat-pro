<?php
/**
 * Plugin Name:       WC Ingresso Shortcode
 * Description:       Fornece um shortcode para exibir um cartão estilizado de ingresso de um produto WooCommerce.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            ChatGPT
 * Text Domain:       wc-ingresso-shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Ingresso_Shortcode {
    private static $instance = null;
    private $assets_enqueued = false;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'wc_ingresso', [ $this, 'render_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
    }

    public function register_assets() {
        $plugin_url = plugin_dir_url( __FILE__ );

        wp_register_style(
            'wc-ingresso-shortcode',
            $plugin_url . 'assets/css/ingresso-shortcode.css',
            [],
            filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/ingresso-shortcode.css' )
        );

        wp_register_script(
            'wc-ingresso-shortcode',
            $plugin_url . 'assets/js/ingresso-shortcode.js',
            [ 'jquery' ],
            filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/ingresso-shortcode.js' ),
            true
        );
    }

    private function enqueue_assets() {
        if ( $this->assets_enqueued ) {
            return;
        }

        wp_enqueue_style( 'wc-ingresso-shortcode' );
        wp_enqueue_script( 'wc-ingresso-shortcode' );

        $this->assets_enqueued = true;
    }

    public function render_shortcode( $atts ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return '<p class="wc-ingresso-error">' . esc_html__( 'WooCommerce precisa estar ativo para utilizar este shortcode.', 'wc-ingresso-shortcode' ) . '</p>';
        }

        $atts = shortcode_atts(
            [
                'product_link' => '',
                'button_label' => __( 'Selecione um Ingresso', 'wc-ingresso-shortcode' ),
                'show_description' => 'yes',
                'installments' => 12,
            ],
            $atts,
            'wc_ingresso'
        );

        $product_id = 0;

        if ( ! empty( $atts['product_link'] ) ) {
            $product_id = url_to_postid( esc_url_raw( $atts['product_link'] ) );
        }

        if ( ! $product_id ) {
            return '<p class="wc-ingresso-error">' . esc_html__( 'Não foi possível identificar o produto. Verifique o link informado.', 'wc-ingresso-shortcode' ) . '</p>';
        }

        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return '<p class="wc-ingresso-error">' . esc_html__( 'Produto inválido.', 'wc-ingresso-shortcode' ) . '</p>';
        }

        $this->enqueue_assets();

        $price_html       = $product->get_price() ? $product->get_price_html() : __( 'Grátis', 'wc-ingresso-shortcode' );
        $description      = $product->get_short_description();
        $product_title    = $product->get_name();
        $installments     = absint( $atts['installments'] );
        $regular_price    = (float) $product->get_price();
        $per_installment  = $installments > 1 && $regular_price > 0 ? $regular_price / $installments : 0;
        $installment_html = '';

        $primary_category = '';
        $product_terms    = get_the_terms( $product_id, 'product_cat' );

        if ( $product_terms && ! is_wp_error( $product_terms ) ) {
            $primary_category = $product_terms[0]->name;
        }

        $image_id   = $product->get_image_id();
        $image_html = '';

        if ( $image_id ) {
            $image_html = wp_get_attachment_image(
                $image_id,
                'medium_large',
                false,
                [
                    'class' => 'wc-ingresso-product-image',
                    'alt'   => sanitize_text_field( $product_title ),
                ]
            );
        }

        if ( $per_installment > 0 ) {
            /* translators: %1$s installment count, %2$s installment price. */
            $installment_html = sprintf(
                esc_html__( 'em até %1$sx %2$s', 'wc-ingresso-shortcode' ),
                number_format_i18n( $installments ),
                wp_strip_all_tags( wc_price( $per_installment ) )
            );
        }

        $add_to_cart_url = esc_url( add_query_arg( [ 'add-to-cart' => $product_id ], $product->get_permalink() ) );

        $body_classes = $image_html ? 'wc-ingresso-body has-image' : 'wc-ingresso-body';

        ob_start();
        ?>
        <div class="wc-ingresso-card" data-product-id="<?php echo esc_attr( $product_id ); ?>">
            <span class="wc-ingresso-glow wc-ingresso-glow--pink" aria-hidden="true"></span>
            <span class="wc-ingresso-glow wc-ingresso-glow--peach" aria-hidden="true"></span>
            <div class="wc-ingresso-surface">
                <div class="wc-ingresso-header">
                    <div class="wc-ingresso-tag-group">
                        <span class="wc-ingresso-badge"><?php echo esc_html__( 'Ingressos', 'wc-ingresso-shortcode' ); ?></span>
                        <?php if ( $primary_category ) : ?>
                            <span class="wc-ingresso-chip"><?php echo esc_html( $primary_category ); ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="wc-ingresso-holo" aria-hidden="true"></span>
                </div>
                <div class="<?php echo esc_attr( $body_classes ); ?>">
                    <?php if ( $image_html ) : ?>
                        <figure class="wc-ingresso-figure">
                            <?php echo wp_kses_post( $image_html ); ?>
                        </figure>
                    <?php endif; ?>
                    <div class="wc-ingresso-info">
                        <h3 class="wc-ingresso-title"><?php echo esc_html( $product_title ); ?></h3>
                        <div class="wc-ingresso-price">
                            <?php echo wp_kses_post( $price_html ); ?>
                        </div>
                        <?php if ( $installment_html ) : ?>
                            <span class="wc-ingresso-installments"><?php echo esc_html( $installment_html ); ?></span>
                        <?php endif; ?>
                        <?php if ( 'yes' === strtolower( $atts['show_description'] ) && ! empty( $description ) ) : ?>
                            <div class="wc-ingresso-description"><?php echo wp_kses_post( $description ); ?></div>
                        <?php endif; ?>
                        <div class="wc-ingresso-divider" aria-hidden="true"></div>
                        <div class="wc-ingresso-meta">
                            <span class="wc-ingresso-meta-item">
                                <span class="wc-ingresso-dot" aria-hidden="true"></span>
                                <?php echo esc_html__( 'Acesso digital instantâneo', 'wc-ingresso-shortcode' ); ?>
                            </span>
                            <span class="wc-ingresso-meta-item">
                                <span class="wc-ingresso-dot" aria-hidden="true"></span>
                                <?php echo esc_html__( 'Cancelamento fácil pelo app', 'wc-ingresso-shortcode' ); ?>
                            </span>
                        </div>
                    </div>
                    <div class="wc-ingresso-quantity" data-add-to-cart="<?php echo esc_attr( $add_to_cart_url ); ?>">
                        <button class="wc-ingresso-qty-btn" data-action="decrease" aria-label="<?php esc_attr_e( 'Diminuir quantidade', 'wc-ingresso-shortcode' ); ?>">&minus;</button>
                        <input type="number" name="quantity" min="1" value="1" class="wc-ingresso-qty" aria-label="<?php esc_attr_e( 'Quantidade', 'wc-ingresso-shortcode' ); ?>" />
                        <button class="wc-ingresso-qty-btn" data-action="increase" aria-label="<?php esc_attr_e( 'Aumentar quantidade', 'wc-ingresso-shortcode' ); ?>">+</button>
                    </div>
                </div>
                <div class="wc-ingresso-footer">
                    <a class="wc-ingresso-button" href="<?php echo esc_url( $add_to_cart_url ); ?>">
                        <?php echo esc_html( $atts['button_label'] ); ?>
                    </a>
                    <a class="wc-ingresso-fee" href="<?php echo esc_url( $product->get_permalink() ); ?>#taxas">
                        <?php echo esc_html__( 'Entenda nossa taxa', 'wc-ingresso-shortcode' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

WC_Ingresso_Shortcode::get_instance();
