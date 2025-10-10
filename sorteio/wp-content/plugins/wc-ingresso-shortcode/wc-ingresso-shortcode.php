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
    private $option_name      = 'wc_ingresso_shortcode_options';

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'wc_ingresso', [ $this, 'render_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );

        if ( is_admin() ) {
            add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
        }
    }

    public function register_settings() {
        register_setting(
            'wc_ingresso_shortcode_settings',
            $this->option_name,
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this, 'sanitize_settings' ],
                'default'           => [],
            ]
        );

        add_settings_section(
            'wc_ingresso_shortcode_main',
            __( 'Configurações do Ingresso', 'wc-ingresso-shortcode' ),
            function () {
                echo '<p>' . esc_html__( 'Defina o link do produto WooCommerce que será utilizado pelo shortcode para montar o cartão de ingresso.', 'wc-ingresso-shortcode' ) . '</p>';
            },
            'wc_ingresso_shortcode_settings'
        );

        add_settings_field(
            'wc_ingresso_shortcode_product_link',
            __( 'Link do produto', 'wc-ingresso-shortcode' ),
            [ $this, 'render_product_link_field' ],
            'wc_ingresso_shortcode_settings',
            'wc_ingresso_shortcode_main'
        );
    }

    public function sanitize_settings( $input ) {
        $sanitized = [];

        if ( isset( $input['product_link'] ) ) {
            $sanitized['product_link'] = esc_url_raw( $input['product_link'] );
        }

        return $sanitized;
    }

    public function render_product_link_field() {
        $options     = get_option( $this->option_name, [] );
        $product_link = isset( $options['product_link'] ) ? $options['product_link'] : '';

        printf(
            '<input type="url" id="wc_ingresso_shortcode_product_link" name="%1$s[product_link]" value="%2$s" class="regular-text" placeholder="https://">',
            esc_attr( $this->option_name ),
            esc_attr( $product_link )
        );

        echo '<p class="description">' . esc_html__( 'Cole aqui o link permanente do produto WooCommerce que você deseja destacar.', 'wc-ingresso-shortcode' ) . '</p>';
    }

    public function register_admin_page() {
        add_menu_page(
            __( 'WC Ingresso', 'wc-ingresso-shortcode' ),
            __( 'WC Ingresso', 'wc-ingresso-shortcode' ),
            'manage_options',
            'wc-ingresso-shortcode',
            [ $this, 'render_admin_page' ],
            'dashicons-tickets',
            56
        );
    }

    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap wc-ingresso-admin">
            <h1><?php esc_html_e( 'WC Ingresso – Dashboard', 'wc-ingresso-shortcode' ); ?></h1>
            <div class="wc-ingresso-admin-card">
                <div class="wc-ingresso-admin-card__body">
                    <h2><?php esc_html_e( 'Produto em Destaque', 'wc-ingresso-shortcode' ); ?></h2>
                    <p><?php esc_html_e( 'Selecione o link do produto que será utilizado como base para o cartão gerado pelo shortcode [wc_ingresso].', 'wc-ingresso-shortcode' ); ?></p>
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'wc_ingresso_shortcode_settings' );
                        do_settings_sections( 'wc_ingresso_shortcode_settings' );
                        submit_button( __( 'Salvar link do produto', 'wc-ingresso-shortcode' ) );
                        ?>
                    </form>
                </div>
                <div class="wc-ingresso-admin-card__aside">
                    <span class="wc-ingresso-admin-glow wc-ingresso-admin-glow--pink"></span>
                    <span class="wc-ingresso-admin-glow wc-ingresso-admin-glow--peach"></span>
                    <div class="wc-ingresso-admin-meta">
                        <h3><?php esc_html_e( 'Como usar', 'wc-ingresso-shortcode' ); ?></h3>
                        <ol>
                            <li><?php esc_html_e( 'Salve o link do produto acima.', 'wc-ingresso-shortcode' ); ?></li>
                            <li><?php esc_html_e( 'Insira o shortcode [wc_ingresso] em qualquer página ou post.', 'wc-ingresso-shortcode' ); ?></li>
                            <li><?php esc_html_e( 'Opcionalmente, forneça outro link via atributo product_link para sobrepor este padrão.', 'wc-ingresso-shortcode' ); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .wc-ingresso-admin-card {
                position: relative;
                display: grid;
                gap: 2rem;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                align-items: stretch;
                padding: 2.5rem;
                margin-top: 2rem;
                border-radius: 24px;
                background: rgba(255, 255, 255, 0.05);
                box-shadow: 0 30px 80px rgba(15, 6, 31, 0.25);
                backdrop-filter: blur(18px);
                overflow: hidden;
            }

            .wc-ingresso-admin-card::before {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(135deg, rgba(255, 10, 182, 0.35), rgba(255, 170, 131, 0.25));
                opacity: 0.85;
                z-index: -2;
            }

            .wc-ingresso-admin-card__body {
                position: relative;
                z-index: 2;
                padding: 1rem;
                color: #1f0933;
            }

            .wc-ingresso-admin-card h2,
            .wc-ingresso-admin-card h3 {
                color: #1f0933;
            }

            .wc-ingresso-admin-card p,
            .wc-ingresso-admin-card ol,
            .wc-ingresso-admin-card li,
            .wc-ingresso-admin-card label {
                color: #36124d;
                font-size: 15px;
                line-height: 1.6;
            }

            .wc-ingresso-admin-card .button-primary {
                background: linear-gradient(135deg, #ff0ab6, #ffaa83);
                border: none;
                box-shadow: 0 12px 30px rgba(255, 10, 182, 0.35);
                text-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            }

            .wc-ingresso-admin-card .button-primary:hover,
            .wc-ingresso-admin-card .button-primary:focus {
                filter: brightness(1.05);
            }

            .wc-ingresso-admin-card__aside {
                position: relative;
                z-index: 2;
                padding: 1rem 1.5rem;
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.35);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
            }

            .wc-ingresso-admin-meta {
                position: relative;
                z-index: 3;
            }

            .wc-ingresso-admin-meta ol {
                margin: 0;
                padding-left: 1.25rem;
            }

            .wc-ingresso-admin-glow {
                position: absolute;
                border-radius: 999px;
                filter: blur(50px);
                opacity: 0.7;
                z-index: 1;
            }

            .wc-ingresso-admin-glow--pink {
                width: 240px;
                height: 240px;
                top: -80px;
                right: 20px;
                background: #ff0ab6;
            }

            .wc-ingresso-admin-glow--peach {
                width: 180px;
                height: 180px;
                bottom: -60px;
                left: 10px;
                background: #ffaa83;
            }

            @media (max-width: 782px) {
                .wc-ingresso-admin-card {
                    padding: 2rem;
                }
            }
        </style>
        <?php
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

        $default_options = get_option( $this->option_name, [] );
        $product_link    = $atts['product_link'];

        if ( empty( $product_link ) && isset( $default_options['product_link'] ) ) {
            $product_link = $default_options['product_link'];
        }

        $product_id = 0;

        if ( ! empty( $product_link ) ) {
            $product_id = url_to_postid( esc_url_raw( $product_link ) );
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
