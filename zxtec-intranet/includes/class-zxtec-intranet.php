<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZXTEC_Intranet {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Plugin URL for enqueuing assets
     * @var string
     */
    private $url;

    /**
     * Get singleton instance
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->url = plugin_dir_url( dirname( __FILE__ ) );
        $this->includes();
        $this->hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once plugin_dir_path( __FILE__ ) . 'class-zxtec-financial.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-zxtec-expense.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-zxtec-analytics.php';
    }

    /**
     * Hooks
     */
    private function hooks() {
        register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
        register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate_plugin' ) );
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_post_meta' ) );
        // Load styles with very high priority so ours override the WordPress defaults
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 999 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_scripts' ) );
        add_shortcode( 'zxtec_colaborador_dashboard', array( $this, 'dashboard_shortcode' ) );
        add_shortcode( 'zxtec_colaborador_contas', array( $this, 'colaborador_contas_shortcode' ) );
        add_action( 'admin_post_zxtec_confirm_order', array( $this, 'handle_confirm_order' ) );
        add_action( 'admin_post_zxtec_decline_order', array( $this, 'handle_decline_order' ) );
        add_action( 'admin_post_zxtec_finalize_order', array( $this, 'handle_finalize_order' ) );
        add_action( 'admin_post_zxtec_user_financial_csv', array( $this, 'download_user_financial_csv' ) );
        add_action( 'admin_post_zxtec_user_financial_pdf', array( $this, 'download_user_financial_pdf' ) );
        add_action( 'admin_post_zxtec_history_csv', array( $this, 'download_history_csv' ) );
        add_action( 'admin_post_zxtec_expense_csv', array( $this, 'download_expense_csv' ) );
        add_action( 'admin_post_zxtec_accountability_csv', array( $this, 'download_accountability_csv' ) );
        add_action( 'admin_post_zxtec_clear_notification', array( $this, 'handle_clear_notification' ) );
        add_action( 'show_user_profile', array( $this, 'user_location_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'user_location_fields' ) );
        add_action( 'personal_options_update', array( $this, 'save_user_location_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_location_fields' ) );
        add_action( 'show_user_profile', array( $this, 'user_commission_field' ) );
        add_action( 'edit_user_profile', array( $this, 'user_commission_field' ) );
        add_action( 'personal_options_update', array( $this, 'save_user_commission_field' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_commission_field' ) );
        add_action( 'show_user_profile', array( $this, 'user_account_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'user_account_fields' ) );
        add_action( 'personal_options_update', array( $this, 'save_user_account_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_account_fields' ) );
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
    }

    /**
     * Plugin activation
     */
    public function activate_plugin() {
        $this->register_post_types();
        add_role( 'zxtec_colaborador', 'Colaborador ZX Tec', array( 'read' => true ) );
        if ( get_option( 'zxtec_commission', null ) === null ) {
            add_option( 'zxtec_commission', 10 );
        }
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate_plugin() {
        remove_role( 'zxtec_colaborador' );
        flush_rewrite_rules();
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        register_post_type( 'zxtec_client', array(
            'labels' => array(
                'name' => __( 'Clientes', 'zxtec' ),
                'singular_name' => __( 'Cliente', 'zxtec' ),
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array( 'title', 'editor', 'custom-fields' ),
        ) );

        register_post_type( 'zxtec_service', array(
            'labels' => array(
                'name' => __( 'Servicos', 'zxtec' ),
                'singular_name' => __( 'Servico', 'zxtec' ),
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array( 'title', 'editor', 'custom-fields' ),
        ) );

        register_post_type( 'zxtec_order', array(
            'labels' => array(
                'name' => __( 'Ordens de Servico', 'zxtec' ),
                'singular_name' => __( 'Ordem de Servico', 'zxtec' ),
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array( 'title', 'editor', 'custom-fields' ),
        ) );

        register_post_type( 'zxtec_contract', array(
            'labels' => array(
                'name' => __( 'Contratos', 'zxtec' ),
                'singular_name' => __( 'Contrato', 'zxtec' ),
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array( 'title', 'editor', 'custom-fields' ),
        ) );

        register_post_type( 'zxtec_expense', array(
            'labels' => array(
                'name' => __( 'Despesas', 'zxtec' ),
                'singular_name' => __( 'Despesa', 'zxtec' ),
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array( 'title', 'editor', 'custom-fields' ),
        ) );

        register_post_type( 'zxtec_accountability', array(
            'labels' => array(
                'name' => __( 'Prestacao de Contas', 'zxtec' ),
                'singular_name' => __( 'Prestacao de Contas', 'zxtec' ),
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array( 'title', 'editor', 'custom-fields' ),
        ) );
    }

    /**
     * Register meta boxes for custom fields
     */
    public function register_meta_boxes() {
        add_meta_box( 'zxtec_client_meta', __( 'Dados do Cliente', 'zxtec' ), array( $this, 'client_meta_box' ), 'zxtec_client', 'normal', 'default' );
        add_meta_box( 'zxtec_service_meta', __( 'Detalhes do Servico', 'zxtec' ), array( $this, 'service_meta_box' ), 'zxtec_service', 'normal', 'default' );
        add_meta_box( 'zxtec_order_meta', __( 'Detalhes da Ordem', 'zxtec' ), array( $this, 'order_meta_box' ), 'zxtec_order', 'normal', 'default' );
        add_meta_box( 'zxtec_contract_meta', __( 'Detalhes do Contrato', 'zxtec' ), array( $this, 'contract_meta_box' ), 'zxtec_contract', 'normal', 'default' );
        add_meta_box( 'zxtec_expense_meta', __( 'Detalhes da Despesa', 'zxtec' ), array( $this, 'expense_meta_box' ), 'zxtec_expense', 'normal', 'default' );
        add_meta_box( 'zxtec_accountability_meta', __( 'Detalhes da Prestacao', 'zxtec' ), array( $this, 'accountability_meta_box' ), 'zxtec_accountability', 'normal', 'default' );
    }

    /**
     * Render client meta box
     */
    public function client_meta_box( $post ) {
        $cpf   = get_post_meta( $post->ID, '_zxtec_cpf', true );
        $phone = get_post_meta( $post->ID, '_zxtec_phone', true );
        $email = get_post_meta( $post->ID, '_zxtec_email', true );
        $zip   = get_post_meta( $post->ID, '_zxtec_zip', true );
        $street = get_post_meta( $post->ID, '_zxtec_street', true );
        $number = get_post_meta( $post->ID, '_zxtec_number', true );
        $neigh  = get_post_meta( $post->ID, '_zxtec_neighborhood', true );
        $compl  = get_post_meta( $post->ID, '_zxtec_complement', true );
        $city   = get_post_meta( $post->ID, '_zxtec_city', true );
        $state  = get_post_meta( $post->ID, '_zxtec_state', true );
        $country = get_post_meta( $post->ID, '_zxtec_country', true );
        wp_nonce_field( 'zxtec_save_meta', 'zxtec_nonce' );
        ?>
        <p><label><?php _e( 'CPF/CNPJ', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_cpf" value="<?php echo esc_attr( $cpf ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Telefone', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_phone" value="<?php echo esc_attr( $phone ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Email', 'zxtec' ); ?><br/>
        <input type="email" name="zxtec_email" value="<?php echo esc_attr( $email ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'CEP', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_zip" value="<?php echo esc_attr( $zip ); ?>" class="widefat zxtec-cep" data-street="zxtec_street" data-number="zxtec_number" data-neighborhood="zxtec_neighborhood" data-complement="zxtec_complement" data-city="zxtec_city" data-state="zxtec_state" data-country="zxtec_country" /></label></p>
        <p><label><?php _e( 'Rua', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_street" value="<?php echo esc_attr( $street ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Numero', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_number" value="<?php echo esc_attr( $number ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Bairro', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_neighborhood" value="<?php echo esc_attr( $neigh ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Complemento', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_complement" value="<?php echo esc_attr( $compl ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Cidade', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_city" value="<?php echo esc_attr( $city ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Estado', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_state" value="<?php echo esc_attr( $state ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Pais', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_country" value="<?php echo esc_attr( $country ); ?>" class="widefat" /></label></p>
        <?php
    }

    /**
     * Render service meta box
     */
    public function service_meta_box( $post ) {
        $price = get_post_meta( $post->ID, '_zxtec_price', true );
        $specialty = get_post_meta( $post->ID, '_zxtec_specialty', true );
        wp_nonce_field( 'zxtec_save_meta', 'zxtec_nonce' );
        ?>
        <p><label><?php _e( 'Preco (R$)', 'zxtec' ); ?><br/>
        <input type="number" step="0.01" name="zxtec_price" value="<?php echo esc_attr( $price ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Especialidade', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_specialty" value="<?php echo esc_attr( $specialty ); ?>" class="widefat" /></label></p>
        <?php
    }

    /**
     * Render order meta box
     */
    public function order_meta_box( $post ) {
        $client = get_post_meta( $post->ID, '_zxtec_client', true );
        $service = get_post_meta( $post->ID, '_zxtec_service', true );
        $technician = get_post_meta( $post->ID, '_zxtec_technician', true );
        $date = get_post_meta( $post->ID, '_zxtec_date', true );
        $status  = get_post_meta( $post->ID, '_zxtec_status', true );
        $lat     = get_post_meta( $post->ID, '_zxtec_lat', true );
        $lng     = get_post_meta( $post->ID, '_zxtec_lng', true );
        $addr    = get_post_meta( $post->ID, '_zxtec_order_address', true );
        $order_total = get_post_meta( $post->ID, '_zxtec_order_total', true );
        $collab_value = get_post_meta( $post->ID, '_zxtec_order_collab_value', true );
        $paid_value = get_post_meta( $post->ID, '_zxtec_order_paid', true );
        $payment_date = get_post_meta( $post->ID, '_zxtec_order_payment_date', true );
        $payment_method = get_post_meta( $post->ID, '_zxtec_order_payment_method', true );
        $admin_notes = get_post_meta( $post->ID, '_zxtec_order_admin_notes', true );
        $zip     = get_post_meta( $post->ID, '_zxtec_order_zip', true );
        $street  = get_post_meta( $post->ID, '_zxtec_order_street', true );
        $number  = get_post_meta( $post->ID, '_zxtec_order_number', true );
        $neigh   = get_post_meta( $post->ID, '_zxtec_order_neighborhood', true );
        $compl   = get_post_meta( $post->ID, '_zxtec_order_complement', true );
        $city    = get_post_meta( $post->ID, '_zxtec_order_city', true );
        $state   = get_post_meta( $post->ID, '_zxtec_order_state', true );
        $country = get_post_meta( $post->ID, '_zxtec_order_country', true );

        $clients = get_posts( array( 'post_type' => 'zxtec_client', 'numberposts' => -1 ) );
        $services = get_posts( array( 'post_type' => 'zxtec_service', 'numberposts' => -1 ) );

        wp_nonce_field( 'zxtec_save_meta', 'zxtec_nonce' );
        ?>
        <p><label><?php _e( 'Cliente', 'zxtec' ); ?><br/>
        <select name="zxtec_client" class="widefat">
            <option value="">--</option>
            <?php foreach ( $clients as $c ) : ?>
                <option value="<?php echo $c->ID; ?>" <?php selected( $client, $c->ID ); ?>><?php echo esc_html( $c->post_title ); ?></option>
            <?php endforeach; ?>
        </select></label></p>
        <p><label><?php _e( 'Servico', 'zxtec' ); ?><br/>
        <select name="zxtec_service" class="widefat">
            <option value="">--</option>
            <?php foreach ( $services as $s ) : ?>
                <option value="<?php echo $s->ID; ?>" <?php selected( $service, $s->ID ); ?>><?php echo esc_html( $s->post_title ); ?></option>
            <?php endforeach; ?>
        </select></label></p>
        <p><label><?php _e( 'Tecnico (user ID)', 'zxtec' ); ?><br/>
        <input type="number" name="zxtec_technician" value="<?php echo esc_attr( $technician ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Data', 'zxtec' ); ?><br/>
        <input type="date" name="zxtec_date" value="<?php echo esc_attr( $date ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Status', 'zxtec' ); ?><br/>
        <select name="zxtec_status" class="widefat">
            <option value="pendente" <?php selected( $status, 'pendente' ); ?>><?php _e( 'A Iniciar', 'zxtec' ); ?></option>
            <option value="confirmado" <?php selected( $status, 'confirmado' ); ?>><?php _e( 'Em execucao', 'zxtec' ); ?></option>
            <option value="recusado" <?php selected( $status, 'recusado' ); ?>><?php _e( 'Pausado', 'zxtec' ); ?></option>
            <option value="concluido" <?php selected( $status, 'concluido' ); ?>><?php _e( 'Finalizado', 'zxtec' ); ?></option>
        </select></label></p>
        <p><label><?php _e( 'Valor total da ordem (R$)', 'zxtec' ); ?><br/>
        <input type="number" step="0.01" name="zxtec_order_total" value="<?php echo esc_attr( $order_total ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Valor do colaborador (R$)', 'zxtec' ); ?><br/>
        <input type="number" step="0.01" name="zxtec_order_collab_value" value="<?php echo esc_attr( $collab_value ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Valor recebido (R$)', 'zxtec' ); ?><br/>
        <input type="number" step="0.01" name="zxtec_order_paid" value="<?php echo esc_attr( $paid_value ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Data de pagamento', 'zxtec' ); ?><br/>
        <input type="date" name="zxtec_order_payment_date" value="<?php echo esc_attr( $payment_date ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Forma de pagamento', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_order_payment_method" value="<?php echo esc_attr( $payment_method ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Observacoes do admin', 'zxtec' ); ?><br/>
        <textarea name="zxtec_order_admin_notes" class="widefat" rows="3"><?php echo esc_textarea( $admin_notes ); ?></textarea></label></p>
        <p><label><?php _e( 'CEP', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_order_zip" value="<?php echo esc_attr( $zip ); ?>" class="widefat zxtec-cep" data-street="zxtec_order_street" data-number="zxtec_order_number" data-neighborhood="zxtec_order_neighborhood" data-complement="zxtec_order_complement" data-city="zxtec_order_city" data-state="zxtec_order_state" data-country="zxtec_order_country" /></label></p>
        <p><label><?php _e( 'Rua', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_order_street" value="<?php echo esc_attr( $street ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Numero', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_order_number" value="<?php echo esc_attr( $number ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Bairro', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_order_neighborhood" value="<?php echo esc_attr( $neigh ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Complemento', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_order_complement" value="<?php echo esc_attr( $compl ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Cidade', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_order_city" value="<?php echo esc_attr( $city ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Estado', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_order_state" value="<?php echo esc_attr( $state ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Pais', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_order_country" value="<?php echo esc_attr( $country ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Latitude', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_lat" value="<?php echo esc_attr( $lat ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Longitude', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_lng" value="<?php echo esc_attr( $lng ); ?>" class="widefat" /></label></p>
        <div id="zxtec_map" style="height:300px;"></div>
        <?php
    }

    /**
     * Render contract meta box
     */
    public function contract_meta_box( $post ) {
        $client = get_post_meta( $post->ID, '_zxtec_client', true );
        $plan   = get_post_meta( $post->ID, '_zxtec_plan', true );
        $start  = get_post_meta( $post->ID, '_zxtec_start', true );
        $end    = get_post_meta( $post->ID, '_zxtec_end', true );
        $status = get_post_meta( $post->ID, '_zxtec_status', true );

        $clients = get_posts( array( 'post_type' => 'zxtec_client', 'numberposts' => -1 ) );

        wp_nonce_field( 'zxtec_save_meta', 'zxtec_nonce' );
        ?>
        <p><label><?php _e( 'Cliente', 'zxtec' ); ?><br/>
        <select name="zxtec_client" class="widefat">
            <option value="">--</option>
            <?php foreach ( $clients as $c ) : ?>
                <option value="<?php echo $c->ID; ?>" <?php selected( $client, $c->ID ); ?>><?php echo esc_html( $c->post_title ); ?></option>
            <?php endforeach; ?>
        </select></label></p>
        <p><label><?php _e( 'Plano', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_plan" value="<?php echo esc_attr( $plan ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Inicio', 'zxtec' ); ?><br/>
        <input type="date" name="zxtec_start" value="<?php echo esc_attr( $start ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Fim', 'zxtec' ); ?><br/>
        <input type="date" name="zxtec_end" value="<?php echo esc_attr( $end ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Status', 'zxtec' ); ?><br/>
        <select name="zxtec_status" class="widefat">
            <option value="ativo" <?php selected( $status, 'ativo' ); ?>><?php _e( 'Ativo', 'zxtec' ); ?></option>
            <option value="encerrado" <?php selected( $status, 'encerrado' ); ?>><?php _e( 'Encerrado', 'zxtec' ); ?></option>
        </select></label></p>
        <?php
    }

    /**
     * Render expense meta box
     */
    public function expense_meta_box( $post ) {
        $tech  = get_post_meta( $post->ID, '_zxtec_technician', true );
        $amount = get_post_meta( $post->ID, '_zxtec_amount', true );
        $date   = get_post_meta( $post->ID, '_zxtec_date', true );
        $users = get_users( array( 'role' => 'zxtec_colaborador' ) );
        wp_nonce_field( 'zxtec_save_meta', 'zxtec_nonce' );
        ?>
        <p><label><?php _e( 'Tecnico', 'zxtec' ); ?><br/>
        <select name="zxtec_technician" class="widefat">
            <option value="">--</option>
            <?php foreach ( $users as $u ) : ?>
                <option value="<?php echo esc_attr( $u->ID ); ?>" <?php selected( $tech, $u->ID ); ?>><?php echo esc_html( $u->display_name ); ?></option>
            <?php endforeach; ?>
        </select></label></p>
        <p><label><?php _e( 'Valor', 'zxtec' ); ?><br/>
        <input type="number" step="0.01" name="zxtec_amount" value="<?php echo esc_attr( $amount ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Data', 'zxtec' ); ?><br/>
        <input type="date" name="zxtec_date" value="<?php echo esc_attr( $date ); ?>" class="widefat" /></label></p>
        <?php
    }

    /**
     * Render accountability meta box
     */
    public function accountability_meta_box( $post ) {
        $user_id = get_post_meta( $post->ID, '_zxtec_account_user', true );
        $type = get_post_meta( $post->ID, '_zxtec_account_type', true );
        $amount = get_post_meta( $post->ID, '_zxtec_account_amount', true );
        $status = get_post_meta( $post->ID, '_zxtec_account_status', true );
        $date = get_post_meta( $post->ID, '_zxtec_account_date', true );
        $method = get_post_meta( $post->ID, '_zxtec_account_payment_method', true );
        $notes = get_post_meta( $post->ID, '_zxtec_account_notes', true );
        $receipt = get_post_meta( $post->ID, '_zxtec_account_receipt', true );
        $users = get_users( array( 'role' => 'zxtec_colaborador' ) );
        wp_nonce_field( 'zxtec_save_meta', 'zxtec_nonce' );
        ?>
        <p><label><?php _e( 'Colaborador', 'zxtec' ); ?><br/>
        <select name="zxtec_account_user" class="widefat">
            <option value="">--</option>
            <?php foreach ( $users as $u ) : ?>
                <option value="<?php echo esc_attr( $u->ID ); ?>" <?php selected( $user_id, $u->ID ); ?>><?php echo esc_html( $u->display_name ); ?></option>
            <?php endforeach; ?>
        </select></label></p>
        <p><label><?php _e( 'Tipo', 'zxtec' ); ?><br/>
        <select name="zxtec_account_type" class="widefat">
            <option value="receita" <?php selected( $type, 'receita' ); ?>><?php _e( 'Receita', 'zxtec' ); ?></option>
            <option value="despesa" <?php selected( $type, 'despesa' ); ?>><?php _e( 'Despesa', 'zxtec' ); ?></option>
            <option value="ajuste" <?php selected( $type, 'ajuste' ); ?>><?php _e( 'Ajuste', 'zxtec' ); ?></option>
        </select></label></p>
        <p><label><?php _e( 'Valor (R$)', 'zxtec' ); ?><br/>
        <input type="number" step="0.01" name="zxtec_account_amount" value="<?php echo esc_attr( $amount ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Status', 'zxtec' ); ?><br/>
        <select name="zxtec_account_status" class="widefat">
            <option value="pendente" <?php selected( $status, 'pendente' ); ?>><?php _e( 'Pendente', 'zxtec' ); ?></option>
            <option value="aprovado" <?php selected( $status, 'aprovado' ); ?>><?php _e( 'Aprovado', 'zxtec' ); ?></option>
            <option value="recusado" <?php selected( $status, 'recusado' ); ?>><?php _e( 'Recusado', 'zxtec' ); ?></option>
        </select></label></p>
        <p><label><?php _e( 'Data', 'zxtec' ); ?><br/>
        <input type="date" name="zxtec_account_date" value="<?php echo esc_attr( $date ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Forma de pagamento', 'zxtec' ); ?><br/>
        <input type="text" name="zxtec_account_payment_method" value="<?php echo esc_attr( $method ); ?>" class="widefat" /></label></p>
        <p><label><?php _e( 'Observacoes', 'zxtec' ); ?><br/>
        <textarea name="zxtec_account_notes" class="widefat" rows="3"><?php echo esc_textarea( $notes ); ?></textarea></label></p>
        <p><label><?php _e( 'Comprovante (URL)', 'zxtec' ); ?><br/>
        <input type="url" name="zxtec_account_receipt" value="<?php echo esc_attr( $receipt ); ?>" class="widefat" /></label></p>
        <?php
    }

    /**
     * Save post meta
     */
    public function save_post_meta( $post_id ) {
        if ( ! isset( $_POST['zxtec_nonce'] ) || ! wp_verify_nonce( $_POST['zxtec_nonce'], 'zxtec_save_meta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        $post_type = get_post_type( $post_id );

        switch ( $post_type ) {
            case 'zxtec_client':
                update_post_meta( $post_id, '_zxtec_cpf', sanitize_text_field( $_POST['zxtec_cpf'] ?? '' ) );
                update_post_meta( $post_id, '_zxtec_phone', sanitize_text_field( $_POST['zxtec_phone'] ?? '' ) );
                update_post_meta( $post_id, '_zxtec_email', sanitize_email( $_POST['zxtec_email'] ?? '' ) );
                $street  = sanitize_text_field( $_POST['zxtec_street'] ?? '' );
                $number  = sanitize_text_field( $_POST['zxtec_number'] ?? '' );
                $neigh   = sanitize_text_field( $_POST['zxtec_neighborhood'] ?? '' );
                $compl   = sanitize_text_field( $_POST['zxtec_complement'] ?? '' );
                $city    = sanitize_text_field( $_POST['zxtec_city'] ?? '' );
                $state   = sanitize_text_field( $_POST['zxtec_state'] ?? '' );
                $country = sanitize_text_field( $_POST['zxtec_country'] ?? '' );
                $zip     = sanitize_text_field( $_POST['zxtec_zip'] ?? '' );
                $full    = trim( $street . ' ' . $number . ' ' . $compl . ', ' . $neigh . ', ' . $city . ' - ' . $state . ', ' . $country );
                update_post_meta( $post_id, '_zxtec_street', $street );
                update_post_meta( $post_id, '_zxtec_number', $number );
                update_post_meta( $post_id, '_zxtec_neighborhood', $neigh );
                update_post_meta( $post_id, '_zxtec_complement', $compl );
                update_post_meta( $post_id, '_zxtec_city', $city );
                update_post_meta( $post_id, '_zxtec_state', $state );
                update_post_meta( $post_id, '_zxtec_country', $country );
                update_post_meta( $post_id, '_zxtec_zip', $zip );
                update_post_meta( $post_id, '_zxtec_address', $full );
                break;
            case 'zxtec_service':
                update_post_meta( $post_id, '_zxtec_price', floatval( $_POST['zxtec_price'] ?? 0 ) );
                update_post_meta( $post_id, '_zxtec_specialty', sanitize_text_field( $_POST['zxtec_specialty'] ?? '' ) );
                break;
           case 'zxtec_order':
                $old_tech   = get_post_meta( $post_id, '_zxtec_technician', true );
                $old_status = get_post_meta( $post_id, '_zxtec_status', true );

                $new_client     = absint( $_POST['zxtec_client'] ?? 0 );
                $new_service    = absint( $_POST['zxtec_service'] ?? 0 );
                $new_technician = absint( $_POST['zxtec_technician'] ?? 0 );
                $new_date       = sanitize_text_field( $_POST['zxtec_date'] ?? '' );
                $new_status     = sanitize_text_field( $_POST['zxtec_status'] ?? '' );
                $new_lat        = sanitize_text_field( $_POST['zxtec_lat'] ?? '' );
                $new_lng        = sanitize_text_field( $_POST['zxtec_lng'] ?? '' );
                $order_total    = floatval( $_POST['zxtec_order_total'] ?? 0 );
                $collab_value   = floatval( $_POST['zxtec_order_collab_value'] ?? 0 );
                $paid_value     = floatval( $_POST['zxtec_order_paid'] ?? 0 );
                $payment_date   = sanitize_text_field( $_POST['zxtec_order_payment_date'] ?? '' );
                $payment_method = sanitize_text_field( $_POST['zxtec_order_payment_method'] ?? '' );
                $admin_notes    = sanitize_text_field( $_POST['zxtec_order_admin_notes'] ?? '' );
                $new_street     = sanitize_text_field( $_POST['zxtec_order_street'] ?? '' );
                $new_number     = sanitize_text_field( $_POST['zxtec_order_number'] ?? '' );
                $new_neigh      = sanitize_text_field( $_POST['zxtec_order_neighborhood'] ?? '' );
                $new_compl      = sanitize_text_field( $_POST['zxtec_order_complement'] ?? '' );
                $new_city       = sanitize_text_field( $_POST['zxtec_order_city'] ?? '' );
                $new_state      = sanitize_text_field( $_POST['zxtec_order_state'] ?? '' );
                $new_country    = sanitize_text_field( $_POST['zxtec_order_country'] ?? '' );
                $new_zip        = sanitize_text_field( $_POST['zxtec_order_zip'] ?? '' );
                $new_addr       = trim( $new_street . ' ' . $new_number . ' ' . $new_compl . ', ' . $new_neigh . ', ' . $new_city . ' - ' . $new_state . ', ' . $new_country );

                update_post_meta( $post_id, '_zxtec_client', $new_client );
                update_post_meta( $post_id, '_zxtec_service', $new_service );
                update_post_meta( $post_id, '_zxtec_technician', $new_technician );
                update_post_meta( $post_id, '_zxtec_date', $new_date );
                update_post_meta( $post_id, '_zxtec_status', $new_status );
                update_post_meta( $post_id, '_zxtec_lat', $new_lat );
                update_post_meta( $post_id, '_zxtec_lng', $new_lng );
                update_post_meta( $post_id, '_zxtec_order_total', $order_total );
                update_post_meta( $post_id, '_zxtec_order_collab_value', $collab_value );
                update_post_meta( $post_id, '_zxtec_order_paid', $paid_value );
                update_post_meta( $post_id, '_zxtec_order_payment_date', $payment_date );
                update_post_meta( $post_id, '_zxtec_order_payment_method', $payment_method );
                update_post_meta( $post_id, '_zxtec_order_admin_notes', $admin_notes );
                update_post_meta( $post_id, '_zxtec_order_street', $new_street );
                update_post_meta( $post_id, '_zxtec_order_number', $new_number );
                update_post_meta( $post_id, '_zxtec_order_neighborhood', $new_neigh );
                update_post_meta( $post_id, '_zxtec_order_complement', $new_compl );
                update_post_meta( $post_id, '_zxtec_order_city', $new_city );
                update_post_meta( $post_id, '_zxtec_order_state', $new_state );
                update_post_meta( $post_id, '_zxtec_order_country', $new_country );
                update_post_meta( $post_id, '_zxtec_order_address', $new_addr );
                update_post_meta( $post_id, '_zxtec_order_zip', $new_zip );

                if ( ! $new_technician ) {
                    $this->assign_nearest_technician( $post_id );
                    $new_technician = get_post_meta( $post_id, '_zxtec_technician', true );
                }

                if ( $new_technician && $new_technician !== (int) $old_tech ) {
                    $user = get_userdata( $new_technician );
                    if ( $user ) {
                        wp_mail( $user->user_email, __( 'Nova ordem atribuida', 'zxtec' ), sprintf( __( 'Voce recebeu a ordem "%s".', 'zxtec' ), get_the_title( $post_id ) ) );
                        $this->add_notification( $new_technician, sprintf( __( 'Nova ordem: %s', 'zxtec' ), get_the_title( $post_id ) ) );
                    }
                }

                if ( $new_status !== $old_status ) {
                    $tech = $new_technician ? get_userdata( $new_technician ) : null;
                    if ( $tech ) {
                        wp_mail( $tech->user_email, __( 'Status atualizado', 'zxtec' ), sprintf( __( 'A ordem "%s" agora esta "%s".', 'zxtec' ), get_the_title( $post_id ), $new_status ) );
                        $this->add_notification( $tech->ID, sprintf( __( 'Status da ordem "%s": %s', 'zxtec' ), get_the_title( $post_id ), $new_status ) );
                    }
                }
                break;
            case 'zxtec_contract':
                update_post_meta( $post_id, '_zxtec_client', absint( $_POST['zxtec_client'] ?? 0 ) );
                update_post_meta( $post_id, '_zxtec_plan', sanitize_text_field( $_POST['zxtec_plan'] ?? '' ) );
                update_post_meta( $post_id, '_zxtec_start', sanitize_text_field( $_POST['zxtec_start'] ?? '' ) );
                update_post_meta( $post_id, '_zxtec_end', sanitize_text_field( $_POST['zxtec_end'] ?? '' ) );
                update_post_meta( $post_id, '_zxtec_status', sanitize_text_field( $_POST['zxtec_status'] ?? '' ) );
                break;
            case 'zxtec_expense':
                update_post_meta( $post_id, '_zxtec_technician', absint( $_POST['zxtec_technician'] ?? 0 ) );
                update_post_meta( $post_id, '_zxtec_amount', floatval( $_POST['zxtec_amount'] ?? 0 ) );
                update_post_meta( $post_id, '_zxtec_date', sanitize_text_field( $_POST['zxtec_date'] ?? '' ) );
                break;
            case 'zxtec_accountability':
                update_post_meta( $post_id, '_zxtec_account_user', absint( $_POST['zxtec_account_user'] ?? 0 ) );
                update_post_meta( $post_id, '_zxtec_account_type', sanitize_text_field( $_POST['zxtec_account_type'] ?? '' ) );
                update_post_meta( $post_id, '_zxtec_account_amount', floatval( $_POST['zxtec_account_amount'] ?? 0 ) );
                update_post_meta( $post_id, '_zxtec_account_status', sanitize_text_field( $_POST['zxtec_account_status'] ?? '' ) );
                update_post_meta( $post_id, '_zxtec_account_date', sanitize_text_field( $_POST['zxtec_account_date'] ?? '' ) );
                update_post_meta( $post_id, '_zxtec_account_payment_method', sanitize_text_field( $_POST['zxtec_account_payment_method'] ?? '' ) );
                update_post_meta( $post_id, '_zxtec_account_notes', sanitize_text_field( $_POST['zxtec_account_notes'] ?? '' ) );
                update_post_meta( $post_id, '_zxtec_account_receipt', esc_url_raw( $_POST['zxtec_account_receipt'] ?? '' ) );
                break;
        }
    }

    /**
     * Enqueue scripts for admin
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
            $screen = get_current_screen();
            if ( 'zxtec_order' === $screen->post_type ) {
                wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' );
                wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), null, true );
                wp_add_inline_script( 'leaflet', $this->order_map_script() );
            }
        }
        if ( 'zxtec_admin_panel_page_zxtec_tech_map' === $hook ) {
            wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' );
            wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), null, true );
        }
        if ( 'zxtec_admin_panel_page_zxtec_analytics' === $hook ) {
            wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true );
        }
        $screen = get_current_screen();
        $post_types = array( 'zxtec_client', 'zxtec_service', 'zxtec_order', 'zxtec_contract', 'zxtec_expense' );
        if ( strpos( $hook, 'zxtec_admin_panel' ) !== false || ( isset( $screen->post_type ) && in_array( $screen->post_type, $post_types, true ) ) ) {
            wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/flatly/bootstrap.min.css' );
            wp_enqueue_style( 'zxtec-admin', $this->url . 'css/zxtec-admin.css' );
            wp_enqueue_style( 'zxtec-toolbar', $this->url . 'css/zxtec-toolbar.css' );
            wp_enqueue_script( 'zxtec-admin', $this->url . 'js/zxtec-admin.js', array(), null, true );
        }
    }

    /**
     * Enqueue styles for frontend dashboard
     */
    public function enqueue_public_scripts() {
        if ( is_singular() ) {
            global $post;
            if ( has_shortcode( $post->post_content, 'zxtec_colaborador_dashboard' ) || has_shortcode( $post->post_content, 'zxtec_colaborador_contas' ) ) {
                wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/flatly/bootstrap.min.css' );
                wp_enqueue_style( 'zxtec-dashboard', $this->url . 'css/zxtec-dashboard.css' );
                wp_enqueue_style( 'zxtec-toolbar', $this->url . 'css/zxtec-toolbar.css' );
                wp_enqueue_style( 'zxtec-glass', $this->url . 'css/zxtec-glass.css' );
                wp_enqueue_script( 'zxtec-glass', $this->url . 'js/zxtec-glass.js', array(), null, true );
            }
        }
    }

    /**
     * JS used in order meta box
     */
    private function order_map_script() {
        return <<<JS
document.addEventListener('DOMContentLoaded',function(){
    var latInput=document.querySelector('input[name="zxtec_lat"]');
    var lngInput=document.querySelector('input[name="zxtec_lng"]');
    if(!latInput||!lngInput) return;
    var lat=parseFloat(latInput.value)||-15;
    var lng=parseFloat(lngInput.value)||-55;
    var map=L.map('zxtec_map').setView([lat,lng],5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(map);
    var marker=L.marker([lat,lng],{draggable:true}).addTo(map);
    marker.on('dragend',function(e){
        var c=e.target.getLatLng();
        latInput.value=c.lat.toFixed(6);
        lngInput.value=c.lng.toFixed(6);
    });
});
JS;
    }

    /**
     * Register admin pages
     */
    public function register_admin_pages() {
        add_menu_page( __( 'ZX Tec', 'zxtec' ), __( 'ZX Tec', 'zxtec' ), 'manage_options', 'zxtec_admin_panel', array( $this, 'admin_page' ), 'dashicons-admin-generic', 26 );
        add_submenu_page( 'zxtec_admin_panel', __( 'Colaboradores', 'zxtec' ), __( 'Painel do Colaborador', 'zxtec' ), 'read', 'zxtec_colaborador_dashboard', array( $this, 'dashboard_page' ) );
        add_submenu_page( 'zxtec_admin_panel', __( 'Financeiro', 'zxtec' ), __( 'Relatorio Financeiro', 'zxtec' ), 'manage_options', 'zxtec_financial_report', array( $this, 'financial_page' ) );
        add_submenu_page( 'zxtec_admin_panel', __( 'Historico', 'zxtec' ), __( 'Historico de Servicos', 'zxtec' ), 'manage_options', 'zxtec_order_history', array( $this, 'history_page' ) );
        add_submenu_page( 'zxtec_admin_panel', __( 'Contratos Ativos', 'zxtec' ), __( 'Contratos Ativos', 'zxtec' ), 'manage_options', 'zxtec_active_contracts', array( $this, 'contracts_page' ) );
        add_submenu_page( 'zxtec_admin_panel', __( 'Agenda', 'zxtec' ), __( 'Agenda', 'zxtec' ), 'read', 'zxtec_schedule', array( $this, 'schedule_page' ) );
        add_submenu_page( 'zxtec_admin_panel', __( 'Mapa de Tecnicos', 'zxtec' ), __( 'Mapa de Tecnicos', 'zxtec' ), 'manage_options', 'zxtec_tech_map', array( $this, 'technician_map_page' ) );
        add_submenu_page( 'zxtec_admin_panel', __( 'Despesas', 'zxtec' ), __( 'Relatorio de Despesas', 'zxtec' ), 'manage_options', 'zxtec_expense_report', array( $this, 'expense_page' ) );
        add_submenu_page( 'zxtec_admin_panel', __( 'Notificacoes', 'zxtec' ), __( 'Notificacoes', 'zxtec' ), 'manage_options', 'zxtec_notifications', array( $this, 'notifications_page' ) );
        add_submenu_page( 'zxtec_admin_panel', __( 'Analiticos', 'zxtec' ), __( 'Relatorio Grafico', 'zxtec' ), 'manage_options', 'zxtec_analytics', array( $this, 'analytics_page' ) );
        add_submenu_page( 'zxtec_admin_panel', __( 'Configuracoes', 'zxtec' ), __( 'Configuracoes', 'zxtec' ), 'manage_options', 'zxtec_settings', array( $this, 'settings_page' ) );
    }

    /**
     * Render admin page
     */
    public function admin_page() {
        echo '<div class="zxtec-container"><div class="zxtec-card"><h1 class="mb-4">ZX Tec</h1><p>Bem-vindo ao painel administrativo.</p></div></div>';
    }

    /**
     * Financial report page
     */
    public function financial_page() {
        $start = isset( $_GET['start'] ) ? sanitize_text_field( $_GET['start'] ) : '';
        $end   = isset( $_GET['end'] ) ? sanitize_text_field( $_GET['end'] ) : '';
        if ( isset( $_GET['download'] ) ) {
            if ( 'csv' === $_GET['download'] ) {
                ZXTEC_Financial::download_csv( $start, $end );
            } elseif ( 'pdf' === $_GET['download'] ) {
                ZXTEC_Financial::download_pdf( $start, $end );
            } elseif ( 'xls' === $_GET['download'] ) {
                ZXTEC_Financial::download_xls( $start, $end );
            }
            exit;
        }

        echo '<div class="zxtec-container"><div class="zxtec-card">';
        echo '<h1 class="mb-4">' . esc_html__( 'Relatorio Financeiro', 'zxtec' ) . '</h1>';
        echo '<form method="get" class="mb-3">';
        echo '<input type="hidden" name="page" value="zxtec_financial_report" />';
        echo '<label>' . esc_html__( 'De', 'zxtec' ) . ' <input type="date" name="start" value="' . esc_attr( $start ) . '" /></label> ';
        echo '<label>' . esc_html__( 'Ate', 'zxtec' ) . ' <input type="date" name="end" value="' . esc_attr( $end ) . '" /></label> ';
        submit_button( __( 'Filtrar', 'zxtec' ), 'secondary', '', false );
        echo '</form>';
        echo ZXTEC_Financial::get_report_html( $start, $end );
        echo '</div></div>';
    }

    /**
     * Expense report page
     */
    public function expense_page() {
        if ( isset( $_GET['download'] ) && 'csv' === $_GET['download'] ) {
            ZXTEC_Expense::download_csv();
            exit;
        }
        echo '<div class="zxtec-container"><div class="zxtec-card">';
        echo '<h1 class="mb-4">' . esc_html__( 'Relatorio de Despesas', 'zxtec' ) . '</h1>';
        echo ZXTEC_Expense::get_report_html();
        echo '</div></div>';
    }

    /**
     * Orders history page
     */
    public function history_page() {
        if ( isset( $_GET['download'] ) && 'csv' === $_GET['download'] ) {
            $this->download_history_csv();
            exit;
        }
        echo '<div class="zxtec-container"><div class="zxtec-card">';
        echo '<h1 class="mb-4">' . esc_html__( 'Historico de Servicos', 'zxtec' ) . '</h1>';
        echo $this->get_history_html();
        echo '</div></div>';
    }

    /**
     * Active contracts page
     */
    public function contracts_page() {
        echo '<div class="zxtec-container"><div class="zxtec-card">';
        echo '<h1 class="mb-4">' . esc_html__( 'Contratos Ativos', 'zxtec' ) . '</h1>';
        echo $this->get_contracts_html();
        echo '</div></div>';
    }

    /**
     * Schedule page for technicians
     */
    public function schedule_page() {
        echo '<div class="zxtec-container"><div class="zxtec-card">';
        echo '<h1 class="mb-4">' . esc_html__( 'Agenda', 'zxtec' ) . '</h1>';
        echo $this->get_schedule_html();
        echo '</div></div>';
    }

    /**
     * Notifications list for administrators
     */
    public function notifications_page() {
        echo '<div class="zxtec-container"><div class="zxtec-card">';
        echo '<h1 class="mb-4">' . esc_html__( 'Notificacoes', 'zxtec' ) . '</h1>';
        $selected = absint( $_GET['user'] ?? 0 );
        echo '<form method="get"><input type="hidden" name="page" value="zxtec_notifications" />';
        echo '<p><label>' . esc_html__( 'Colaborador:', 'zxtec' ) . ' <select name="user"><option value="">--</option>';
        foreach ( get_users( array( 'role' => 'zxtec_colaborador' ) ) as $u ) {
            echo '<option value="' . esc_attr( $u->ID ) . '" ' . selected( $selected, $u->ID, false ) . '>' . esc_html( $u->display_name ) . '</option>';
        }
        echo '</select></label> <input type="submit" class="button" value="' . esc_attr__( 'Filtrar', 'zxtec' ) . '" /></p></form>';

        $users = $selected ? array( get_userdata( $selected ) ) : get_users( array( 'role' => 'zxtec_colaborador' ) );
        echo '<table class="table table-striped zxtec-table"><thead><tr><th>' . esc_html__( 'Colaborador', 'zxtec' ) . '</th><th>' . esc_html__( 'Mensagem', 'zxtec' ) . '</th><th>' . esc_html__( 'Data', 'zxtec' ) . '</th><th></th></tr></thead><tbody>';
        foreach ( $users as $user ) {
            if ( ! $user ) {
                continue;
            }
            $notes = $this->get_notifications( $user->ID );
            foreach ( $notes as $i => $n ) {
                $url = wp_nonce_url( admin_url( 'admin-post.php?action=zxtec_clear_notification&n=' . $i ), 'zxtec_clear_note_' . $user->ID );
                echo '<tr><td>' . esc_html( $user->display_name ) . '</td><td>' . esc_html( $n['message'] ) . '</td><td>' . esc_html( $n['time'] ) . '</td><td><a href="' . esc_url( $url ) . '">x</a></td></tr>';
            }
        }
        echo '</tbody></table>';
        echo '</div></div>';
    }

    /**
     * Analytics charts page
     */
    public function analytics_page() {
        echo ZXTEC_Analytics::page_html();
    }

    /**
     * Render collaborator dashboard page
     */
    public function dashboard_page() {
        echo '<div class="zxtec-container"><div class="zxtec-card">';
        echo $this->get_dashboard_html();
        echo '</div></div>';
    }

    /**
     * Shortcode handler
     */
    public function dashboard_shortcode() {
        return $this->get_dashboard_html();
    }

    /**
     * Shortcode handler for collaborator accounts dashboard
     */
    public function colaborador_contas_shortcode() {
        return $this->get_colaborador_contas_html();
    }

    /**
     * Dashboard HTML
     */
    private function get_dashboard_html() {
        if ( ! is_user_logged_in() ) {
            return __( 'Necessario fazer login.', 'zxtec' );
        }

        $user_id = get_current_user_id();
        $orders = get_posts( array(
            'post_type'  => 'zxtec_order',
            'meta_key'   => '_zxtec_technician',
            'meta_value' => $user_id,
            'numberposts' => -1,
        ) );
        $tech_lat = get_user_meta( $user_id, 'zxtec_lat', true );
        $tech_lng = get_user_meta( $user_id, 'zxtec_lng', true );

        ob_start();
        ?>
        <h1><?php _e( 'Dashboard do Colaborador', 'zxtec' ); ?></h1>
        <?php
        $notes = $this->get_notifications( $user_id );
        if ( ! empty( $notes ) ) : ?>
            <h2><?php _e( 'Notificacoes', 'zxtec' ); ?></h2>
            <ul>
                <?php foreach ( $notes as $i => $n ) : ?>
                    <li><?php echo esc_html( $n['message'] ); ?>
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=zxtec_clear_notification&n=' . $i ), 'zxtec_clear_note_' . $user_id ) ); ?>">x</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ( ! empty( $orders ) ) : ?>
            <table class="table table-striped zxtec-table">
                <thead><tr><th><?php _e( 'Ordem', 'zxtec' ); ?></th><th><?php _e( 'Data', 'zxtec' ); ?></th><th><?php _e( 'Status', 'zxtec' ); ?></th><th><?php _e( 'Acao', 'zxtec' ); ?></th></tr></thead>
                <tbody>
                <?php foreach ( $orders as $order ) : ?>
                    <?php
                        $status = get_post_meta( $order->ID, '_zxtec_status', true );
                        $lat = get_post_meta( $order->ID, '_zxtec_lat', true );
                        $lng = get_post_meta( $order->ID, '_zxtec_lng', true );
                        if ( $tech_lat && $tech_lng && $lat && $lng ) {
                            $map_url = sprintf( 'https://www.google.com/maps/dir/?api=1&origin=%s,%s&destination=%s,%s', $tech_lat, $tech_lng, $lat, $lng );
                        } elseif ( $lat && $lng ) {
                            $map_url = 'https://www.google.com/maps/?q=' . $lat . ',' . $lng;
                        } else {
                            $map_url = '';
                        }
                        $nonce = wp_create_nonce( 'zxtec_order_action_' . $order->ID );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $order->post_title ); ?></td>
                        <td><?php echo esc_html( get_post_meta( $order->ID, '_zxtec_date', true ) ); ?></td>
                        <td><?php echo esc_html( $this->human_status( $status ) ); ?></td>
                        <td>
                            <?php if ( $map_url ) : ?>
                                <a href="<?php echo esc_url( $map_url ); ?>" target="_blank">GPS</a>
                                |
                            <?php endif; ?>
                            <?php if ( 'confirmado' !== $status && 'concluido' !== $status ) : ?>
                                <a href="<?php echo admin_url( 'admin-post.php?action=zxtec_confirm_order&order=' . $order->ID . '&_wpnonce=' . $nonce ); ?>">Confirmar</a>
                                |
                                <a href="#" onclick="var r=prompt('Justificativa:');if(r===null){return false;}window.location.href='<?php echo admin_url( 'admin-post.php?action=zxtec_decline_order&order=' . $order->ID . '&_wpnonce=' . $nonce . '&reason=' ); ?>'+encodeURIComponent(r);return false;">Recusar</a>
                            <?php elseif ( 'confirmado' === $status ) : ?>
                                <a href="<?php echo admin_url( 'admin-post.php?action=zxtec_finalize_order&order=' . $order->ID . '&_wpnonce=' . $nonce ); ?>">Finalizar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php _e( 'Nenhuma ordem designada.', 'zxtec' ); ?></p>
        <?php endif; ?>
        <?php echo $this->get_user_financial_html(); ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Collaborator accounts dashboard HTML
     */
    private function get_colaborador_contas_html() {
        if ( ! is_user_logged_in() ) {
            return __( 'Necessario fazer login.', 'zxtec' );
        }

        $user_id = get_current_user_id();
        $profile = $this->get_colaborador_profile_data( $user_id );
        $filters = $this->get_colaborador_orders_filters();
        $orders  = $this->get_colaborador_orders( $user_id, $filters );
        $totals  = $this->calculate_colaborador_totals( $orders, $user_id );
        $accountability_filters = $this->get_accountability_filters();
        $accountability_entries = $this->get_accountability_entries( $user_id, $accountability_filters );
        $accountability_totals = $this->calculate_accountability_totals( $accountability_entries );

        ob_start();
        ?>
        <div class="zxtec-container zxtec-glass-dashboard">
            <div class="zxtec-card zxtec-glass-card">
                <div class="zxtec-glass-header">
                    <h1><?php esc_html_e( 'Contas do Colaborador', 'zxtec' ); ?></h1>
                    <p><?php esc_html_e( 'Visao financeira e operacional do colaborador logado.', 'zxtec' ); ?></p>
                </div>

                <div class="zxtec-glass-summary">
                    <div class="zxtec-glass-card zxtec-glass-summary-card">
                        <span class="zxtec-glass-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="presentation">
                                <path d="M4 12h16M12 4v16" />
                            </svg>
                        </span>
                        <span class="zxtec-glass-label"><?php esc_html_e( 'Total faturado', 'zxtec' ); ?></span>
                        <strong class="zxtec-glass-value"><?php echo esc_html( number_format_i18n( $totals['billed'], 2 ) ); ?></strong>
                    </div>
                    <div class="zxtec-glass-card zxtec-glass-summary-card">
                        <span class="zxtec-glass-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="presentation">
                                <path d="M4 12h16M4 12l6-6M4 12l6 6" />
                            </svg>
                        </span>
                        <span class="zxtec-glass-label"><?php esc_html_e( 'Total recebido', 'zxtec' ); ?></span>
                        <strong class="zxtec-glass-value"><?php echo esc_html( number_format_i18n( $totals['received'], 2 ) ); ?></strong>
                    </div>
                    <div class="zxtec-glass-card zxtec-glass-summary-card">
                        <span class="zxtec-glass-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="presentation">
                                <path d="M4 12h10M14 8l4 4-4 4" />
                            </svg>
                        </span>
                        <span class="zxtec-glass-label"><?php esc_html_e( 'Total pendente', 'zxtec' ); ?></span>
                        <strong class="zxtec-glass-value"><?php echo esc_html( number_format_i18n( $totals['pending'], 2 ) ); ?></strong>
                    </div>
                    <div class="zxtec-glass-card zxtec-glass-summary-card">
                        <span class="zxtec-glass-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="presentation">
                                <path d="M5 12l4 4L19 6" />
                            </svg>
                        </span>
                        <span class="zxtec-glass-label"><?php esc_html_e( 'Saldo aprovado', 'zxtec' ); ?></span>
                        <strong class="zxtec-glass-value"><?php echo esc_html( number_format_i18n( $accountability_totals['saldo_aprovado'], 2 ) ); ?></strong>
                    </div>
                </div>

                <div class="zxtec-glass-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Secoes do dashboard', 'zxtec' ); ?>">
                    <button class="zxtec-glass-tab is-active" data-tab="perfil" type="button" role="tab" aria-selected="true" aria-controls="zxtec-tab-perfil"><?php esc_html_e( 'Perfil', 'zxtec' ); ?></button>
                    <button class="zxtec-glass-tab" data-tab="ordens" type="button" role="tab" aria-selected="false" aria-controls="zxtec-tab-ordens"><?php esc_html_e( 'Ordens', 'zxtec' ); ?></button>
                    <button class="zxtec-glass-tab" data-tab="prestacao" type="button" role="tab" aria-selected="false" aria-controls="zxtec-tab-prestacao"><?php esc_html_e( 'Prestacao de contas', 'zxtec' ); ?></button>
                    <button class="zxtec-glass-tab" data-tab="extrato" type="button" role="tab" aria-selected="false" aria-controls="zxtec-tab-extrato"><?php esc_html_e( 'Extrato', 'zxtec' ); ?></button>
                    <button class="zxtec-glass-tab" data-tab="metas" type="button" role="tab" aria-selected="false" aria-controls="zxtec-tab-metas"><?php esc_html_e( 'Metas', 'zxtec' ); ?></button>
                </div>

                <div class="zxtec-glass-section is-active" data-tab-panel="perfil" id="zxtec-tab-perfil" role="tabpanel">
                    <h2><?php esc_html_e( 'Perfil', 'zxtec' ); ?></h2>
                    <table class="table table-striped zxtec-table zxtec-glass-table">
                        <tbody>
                            <tr><th><?php esc_html_e( 'Nome completo', 'zxtec' ); ?></th><td><?php echo esc_html( $profile['name'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'CPF/Documento', 'zxtec' ); ?></th><td><?php echo esc_html( $profile['document'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'E-mail', 'zxtec' ); ?></th><td><?php echo esc_html( $profile['email'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Telefone', 'zxtec' ); ?></th><td><?php echo esc_html( $profile['phone'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Endereco', 'zxtec' ); ?></th><td><?php echo esc_html( $profile['address'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Cargo/Especialidade', 'zxtec' ); ?></th><td><?php echo esc_html( $profile['role'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Valor por km (R$)', 'zxtec' ); ?></th><td><?php echo esc_html( $profile['cost_km'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Tipo de contrato', 'zxtec' ); ?></th><td><?php echo esc_html( $profile['contract_type'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Regra de remuneracao', 'zxtec' ); ?></th><td><?php echo esc_html( $profile['remuneration'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Status', 'zxtec' ); ?></th><td><?php echo esc_html( $profile['status'] ); ?></td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="zxtec-glass-section" data-tab-panel="ordens" id="zxtec-tab-ordens" role="tabpanel">
                    <h2><?php esc_html_e( 'Ordens de Servico', 'zxtec' ); ?></h2>
                    <form method="get" class="mb-3 zxtec-glass-filters">
                        <label><?php esc_html_e( 'De', 'zxtec' ); ?>
                            <input type="date" name="start" value="<?php echo esc_attr( $filters['start'] ); ?>" class="zxtec-glass-input" />
                        </label>
                        <label><?php esc_html_e( 'Ate', 'zxtec' ); ?>
                            <input type="date" name="end" value="<?php echo esc_attr( $filters['end'] ); ?>" class="zxtec-glass-input" />
                        </label>
                        <label><?php esc_html_e( 'Status', 'zxtec' ); ?>
                            <select name="status" class="zxtec-glass-input">
                                <option value=""><?php esc_html_e( 'Todos', 'zxtec' ); ?></option>
                                <option value="pendente" <?php selected( $filters['status'], 'pendente' ); ?>><?php esc_html_e( 'A Iniciar', 'zxtec' ); ?></option>
                                <option value="confirmado" <?php selected( $filters['status'], 'confirmado' ); ?>><?php esc_html_e( 'Em execucao', 'zxtec' ); ?></option>
                                <option value="recusado" <?php selected( $filters['status'], 'recusado' ); ?>><?php esc_html_e( 'Pausado', 'zxtec' ); ?></option>
                                <option value="concluido" <?php selected( $filters['status'], 'concluido' ); ?>><?php esc_html_e( 'Finalizado', 'zxtec' ); ?></option>
                            </select>
                        </label>
                        <label><?php esc_html_e( 'Cliente', 'zxtec' ); ?>
                            <select name="client" class="zxtec-glass-input">
                                <option value=""><?php esc_html_e( 'Todos', 'zxtec' ); ?></option>
                                <?php foreach ( $this->get_clients_options() as $client_id => $client_name ) : ?>
                                    <option value="<?php echo esc_attr( $client_id ); ?>" <?php selected( $filters['client'], $client_id ); ?>>
                                        <?php echo esc_html( $client_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button class="button zxtec-glass-button" type="submit"><?php esc_html_e( 'Filtrar', 'zxtec' ); ?></button>
                    </form>

                    <?php if ( empty( $orders ) ) : ?>
                        <p><?php esc_html_e( 'Nenhuma ordem encontrada.', 'zxtec' ); ?></p>
                    <?php else : ?>
                        <table class="table table-striped zxtec-table zxtec-glass-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'ID', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Cliente', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Servico', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Data', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Valor total', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Valor do colaborador', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Valor recebido', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Valor pendente', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Data de pagamento', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Forma de pagamento', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Observacoes', 'zxtec' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $orders as $order ) : ?>
                                    <?php $order_meta = $this->get_order_financial_data( $order->ID, $user_id ); ?>
                                    <tr>
                                        <td><?php echo esc_html( $order->ID ); ?></td>
                                        <td><?php echo esc_html( $order_meta['client'] ); ?></td>
                                        <td><?php echo esc_html( $order_meta['service'] ); ?></td>
                                        <td><?php echo esc_html( $order_meta['date'] ); ?></td>
                                        <td><span class="zxtec-glass-badge status-<?php echo esc_attr( $order_meta['status'] ); ?>"><?php echo esc_html( $this->human_status( $order_meta['status'] ) ); ?></span></td>
                                        <td><?php echo esc_html( number_format_i18n( $order_meta['total'], 2 ) ); ?></td>
                                        <td><?php echo esc_html( number_format_i18n( $order_meta['collab_value'], 2 ) ); ?></td>
                                        <td><?php echo esc_html( number_format_i18n( $order_meta['paid'], 2 ) ); ?></td>
                                        <td><?php echo esc_html( number_format_i18n( $order_meta['pending'], 2 ) ); ?></td>
                                        <td><?php echo esc_html( $order_meta['payment_date'] ); ?></td>
                                        <td><?php echo esc_html( $order_meta['payment_method'] ); ?></td>
                                        <td><?php echo esc_html( $order_meta['admin_notes'] ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="zxtec-glass-section" data-tab-panel="prestacao" id="zxtec-tab-prestacao" role="tabpanel">
                    <h2><?php esc_html_e( 'Prestacao de Contas', 'zxtec' ); ?></h2>
                    <form method="get" class="mb-3 zxtec-glass-filters">
                        <label><?php esc_html_e( 'De', 'zxtec' ); ?>
                            <input type="date" name="account_start" value="<?php echo esc_attr( $accountability_filters['start'] ); ?>" class="zxtec-glass-input" />
                        </label>
                        <label><?php esc_html_e( 'Ate', 'zxtec' ); ?>
                            <input type="date" name="account_end" value="<?php echo esc_attr( $accountability_filters['end'] ); ?>" class="zxtec-glass-input" />
                        </label>
                        <label><?php esc_html_e( 'Tipo', 'zxtec' ); ?>
                            <select name="account_type" class="zxtec-glass-input">
                                <option value=""><?php esc_html_e( 'Todos', 'zxtec' ); ?></option>
                                <option value="receita" <?php selected( $accountability_filters['type'], 'receita' ); ?>><?php esc_html_e( 'Receita', 'zxtec' ); ?></option>
                                <option value="despesa" <?php selected( $accountability_filters['type'], 'despesa' ); ?>><?php esc_html_e( 'Despesa', 'zxtec' ); ?></option>
                                <option value="ajuste" <?php selected( $accountability_filters['type'], 'ajuste' ); ?>><?php esc_html_e( 'Ajuste', 'zxtec' ); ?></option>
                            </select>
                        </label>
                        <label><?php esc_html_e( 'Status', 'zxtec' ); ?>
                            <select name="account_status" class="zxtec-glass-input">
                                <option value=""><?php esc_html_e( 'Todos', 'zxtec' ); ?></option>
                                <option value="pendente" <?php selected( $accountability_filters['status'], 'pendente' ); ?>><?php esc_html_e( 'Pendente', 'zxtec' ); ?></option>
                                <option value="aprovado" <?php selected( $accountability_filters['status'], 'aprovado' ); ?>><?php esc_html_e( 'Aprovado', 'zxtec' ); ?></option>
                                <option value="recusado" <?php selected( $accountability_filters['status'], 'recusado' ); ?>><?php esc_html_e( 'Recusado', 'zxtec' ); ?></option>
                            </select>
                        </label>
                        <button class="button zxtec-glass-button" type="submit"><?php esc_html_e( 'Filtrar', 'zxtec' ); ?></button>
                        <?php
                        $export_url = add_query_arg(
                            array(
                                'action' => 'zxtec_accountability_csv',
                                'account_start' => $accountability_filters['start'],
                                'account_end' => $accountability_filters['end'],
                                'account_type' => $accountability_filters['type'],
                                'account_status' => $accountability_filters['status'],
                            ),
                            admin_url( 'admin-post.php' )
                        );
                        $export_url = wp_nonce_url( $export_url, 'zxtec_accountability_export_' . $user_id );
                        ?>
                        <a class="button zxtec-glass-button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Exportar CSV', 'zxtec' ); ?></a>
                    </form>

                    <div class="zxtec-card zxtec-glass-card">
                        <p><strong><?php esc_html_e( 'Total de receitas', 'zxtec' ); ?>:</strong> <?php echo esc_html( number_format_i18n( $accountability_totals['receita'], 2 ) ); ?></p>
                        <p><strong><?php esc_html_e( 'Total de despesas', 'zxtec' ); ?>:</strong> <?php echo esc_html( number_format_i18n( $accountability_totals['despesa'], 2 ) ); ?></p>
                        <p><strong><?php esc_html_e( 'Saldo', 'zxtec' ); ?>:</strong> <?php echo esc_html( number_format_i18n( $accountability_totals['saldo'], 2 ) ); ?></p>
                        <p><strong><?php esc_html_e( 'Saldo aprovado', 'zxtec' ); ?>:</strong> <?php echo esc_html( number_format_i18n( $accountability_totals['saldo_aprovado'], 2 ) ); ?></p>
                        <p><strong><?php esc_html_e( 'Receitas pendentes', 'zxtec' ); ?>:</strong> <?php echo esc_html( number_format_i18n( $accountability_totals['pendente_receita'], 2 ) ); ?></p>
                        <p><strong><?php esc_html_e( 'Despesas pendentes', 'zxtec' ); ?>:</strong> <?php echo esc_html( number_format_i18n( $accountability_totals['pendente_despesa'], 2 ) ); ?></p>
                        <p><strong><?php esc_html_e( 'Saldo pendente', 'zxtec' ); ?>:</strong> <?php echo esc_html( number_format_i18n( $accountability_totals['saldo_pendente'], 2 ) ); ?></p>
                    </div>

                    <?php if ( empty( $accountability_entries ) ) : ?>
                        <p><?php esc_html_e( 'Nenhum registro de prestacao de contas.', 'zxtec' ); ?></p>
                    <?php else : ?>
                        <table class="table table-striped zxtec-table zxtec-glass-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Data', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Descricao', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Tipo', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Valor', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Forma de pagamento', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Observacoes', 'zxtec' ); ?></th>
                                    <th><?php esc_html_e( 'Comprovante', 'zxtec' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $accountability_entries as $entry ) : ?>
                                    <?php $entry_data = $this->get_accountability_entry_data( $entry->ID ); ?>
                                    <tr>
                                        <td><?php echo esc_html( $entry_data['date'] ); ?></td>
                                        <td><?php echo esc_html( $entry_data['title'] ); ?></td>
                                        <td><span class="zxtec-glass-badge type-<?php echo esc_attr( $entry_data['type'] ); ?>"><?php echo esc_html( $this->human_accountability_type( $entry_data['type'] ) ); ?></span></td>
                                        <td><span class="zxtec-glass-badge status-<?php echo esc_attr( $entry_data['status'] ); ?>"><?php echo esc_html( $this->human_accountability_status( $entry_data['status'] ) ); ?></span></td>
                                        <td><?php echo esc_html( number_format_i18n( $entry_data['amount'], 2 ) ); ?></td>
                                        <td><?php echo esc_html( $entry_data['payment_method'] ); ?></td>
                                        <td><?php echo esc_html( $entry_data['notes'] ); ?></td>
                                        <td>
                                            <?php if ( $entry_data['receipt'] ) : ?>
                                                <a class="zxtec-glass-link" href="<?php echo esc_url( $entry_data['receipt'] ); ?>" target="_blank"><?php esc_html_e( 'Ver', 'zxtec' ); ?></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="zxtec-glass-section" data-tab-panel="extrato" id="zxtec-tab-extrato" role="tabpanel">
                    <h2><?php esc_html_e( 'Extrato', 'zxtec' ); ?></h2>
                    <p class="zxtec-glass-muted"><?php esc_html_e( 'Em breve: consolidado financeiro por periodo.', 'zxtec' ); ?></p>
                </div>

                <div class="zxtec-glass-section" data-tab-panel="metas" id="zxtec-tab-metas" role="tabpanel">
                    <h2><?php esc_html_e( 'Metas', 'zxtec' ); ?></h2>
                    <p class="zxtec-glass-muted"><?php esc_html_e( 'Em breve: metas e indicadores do colaborador.', 'zxtec' ); ?></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Build collaborator profile data from user meta
     */
    private function get_colaborador_profile_data( $user_id ) {
        $user = get_userdata( $user_id );
        $specialty = get_user_meta( $user_id, 'zxtec_specialty', true );
        $role_title = get_user_meta( $user_id, 'zxtec_role_title', true );
        $cost_km = get_user_meta( $user_id, 'zxtec_cost_km', true );
        return array(
            'name' => $user ? $user->display_name : '',
            'document' => get_user_meta( $user_id, 'zxtec_document', true ),
            'email' => $user ? $user->user_email : '',
            'phone' => get_user_meta( $user_id, 'zxtec_phone', true ),
            'address' => get_user_meta( $user_id, 'zxtec_address', true ),
            'role' => $role_title ?: $specialty,
            'cost_km' => $cost_km,
            'contract_type' => get_user_meta( $user_id, 'zxtec_contract_type', true ),
            'remuneration' => get_user_meta( $user_id, 'zxtec_remuneration_rule', true ),
            'status' => get_user_meta( $user_id, 'zxtec_status', true ),
        );
    }

    /**
     * Read filters for collaborator orders
     */
    private function get_colaborador_orders_filters() {
        return array(
            'start' => isset( $_GET['start'] ) ? sanitize_text_field( $_GET['start'] ) : '',
            'end' => isset( $_GET['end'] ) ? sanitize_text_field( $_GET['end'] ) : '',
            'status' => isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '',
            'client' => isset( $_GET['client'] ) ? absint( $_GET['client'] ) : 0,
        );
    }

    /**
     * Retrieve collaborator orders based on filters
     */
    private function get_colaborador_orders( $user_id, array $filters ) {
        $meta = array(
            array(
                'key' => '_zxtec_technician',
                'value' => $user_id,
            ),
        );
        if ( $filters['status'] ) {
            $meta[] = array( 'key' => '_zxtec_status', 'value' => $filters['status'] );
        }
        if ( $filters['client'] ) {
            $meta[] = array( 'key' => '_zxtec_client', 'value' => $filters['client'] );
        }
        if ( $filters['start'] ) {
            $meta[] = array( 'key' => '_zxtec_date', 'value' => $filters['start'], 'compare' => '>=' );
        }
        if ( $filters['end'] ) {
            $meta[] = array( 'key' => '_zxtec_date', 'value' => $filters['end'], 'compare' => '<=' );
        }

        return get_posts( array(
            'post_type'   => 'zxtec_order',
            'numberposts' => -1,
            'meta_key'    => '_zxtec_date',
            'orderby'     => 'meta_value',
            'order'       => 'ASC',
            'meta_query'  => $meta,
        ) );
    }

    /**
     * Build options list for clients
     */
    private function get_clients_options() {
        $clients = get_posts( array( 'post_type' => 'zxtec_client', 'numberposts' => -1 ) );
        $options = array();
        foreach ( $clients as $client ) {
            $options[ $client->ID ] = $client->post_title;
        }
        return $options;
    }

    /**
     * Retrieve financial data for a single order
     */
    private function get_order_financial_data( $order_id, $user_id ) {
        $client_id = get_post_meta( $order_id, '_zxtec_client', true );
        $service_id = get_post_meta( $order_id, '_zxtec_service', true );
        $client = $client_id ? get_post( $client_id ) : null;
        $service = $service_id ? get_post( $service_id ) : null;
        $order_total = floatval( get_post_meta( $order_id, '_zxtec_order_total', true ) );
        if ( ! $order_total ) {
            $order_total = $service_id ? floatval( get_post_meta( $service_id, '_zxtec_price', true ) ) : 0;
        }
        $collab_value = get_post_meta( $order_id, '_zxtec_order_collab_value', true );
        if ( $collab_value === '' ) {
            $collab_value = $order_total * $this->get_commission_rate( $user_id );
        } else {
            $collab_value = floatval( $collab_value );
        }
        $paid = floatval( get_post_meta( $order_id, '_zxtec_order_paid', true ) );
        $pending = max( 0, $collab_value - $paid );
        return array(
            'client' => $client ? $client->post_title : '',
            'service' => $service ? $service->post_title : '',
            'date' => get_post_meta( $order_id, '_zxtec_date', true ),
            'status' => get_post_meta( $order_id, '_zxtec_status', true ),
            'total' => $order_total,
            'collab_value' => $collab_value,
            'paid' => $paid,
            'pending' => $pending,
            'payment_date' => get_post_meta( $order_id, '_zxtec_order_payment_date', true ),
            'payment_method' => get_post_meta( $order_id, '_zxtec_order_payment_method', true ),
            'admin_notes' => get_post_meta( $order_id, '_zxtec_order_admin_notes', true ),
        );
    }

    /**
     * Calculate totals for collaborator orders
     */
    private function calculate_colaborador_totals( array $orders, $user_id ) {
        $billed = 0;
        $received = 0;
        $pending = 0;
        foreach ( $orders as $order ) {
            $data = $this->get_order_financial_data( $order->ID, $user_id );
            $billed += $data['collab_value'];
            $received += $data['paid'];
            $pending += $data['pending'];
        }
        return array(
            'billed' => $billed,
            'received' => $received,
            'pending' => $pending,
        );
    }

    /**
     * Read filters for accountability entries
     */
    private function get_accountability_filters() {
        return array(
            'start' => isset( $_GET['account_start'] ) ? sanitize_text_field( $_GET['account_start'] ) : '',
            'end' => isset( $_GET['account_end'] ) ? sanitize_text_field( $_GET['account_end'] ) : '',
            'type' => isset( $_GET['account_type'] ) ? sanitize_text_field( $_GET['account_type'] ) : '',
            'status' => isset( $_GET['account_status'] ) ? sanitize_text_field( $_GET['account_status'] ) : '',
        );
    }

    /**
     * Retrieve accountability entries for collaborator
     */
    private function get_accountability_entries( $user_id, array $filters ) {
        $meta = array(
            array(
                'key' => '_zxtec_account_user',
                'value' => $user_id,
            ),
        );
        if ( $filters['status'] ) {
            $meta[] = array( 'key' => '_zxtec_account_status', 'value' => $filters['status'] );
        }
        if ( $filters['type'] ) {
            $meta[] = array( 'key' => '_zxtec_account_type', 'value' => $filters['type'] );
        }
        if ( $filters['start'] ) {
            $meta[] = array( 'key' => '_zxtec_account_date', 'value' => $filters['start'], 'compare' => '>=' );
        }
        if ( $filters['end'] ) {
            $meta[] = array( 'key' => '_zxtec_account_date', 'value' => $filters['end'], 'compare' => '<=' );
        }

        return get_posts( array(
            'post_type'   => 'zxtec_accountability',
            'numberposts' => -1,
            'meta_key'    => '_zxtec_account_date',
            'orderby'     => 'meta_value',
            'order'       => 'ASC',
            'meta_query'  => $meta,
        ) );
    }

    /**
     * Retrieve accountability entry data
     */
    private function get_accountability_entry_data( $entry_id ) {
        $entry = get_post( $entry_id );
        return array(
            'title' => $entry ? $entry->post_title : '',
            'type' => get_post_meta( $entry_id, '_zxtec_account_type', true ),
            'amount' => floatval( get_post_meta( $entry_id, '_zxtec_account_amount', true ) ),
            'status' => get_post_meta( $entry_id, '_zxtec_account_status', true ),
            'date' => get_post_meta( $entry_id, '_zxtec_account_date', true ),
            'payment_method' => get_post_meta( $entry_id, '_zxtec_account_payment_method', true ),
            'notes' => get_post_meta( $entry_id, '_zxtec_account_notes', true ),
            'receipt' => get_post_meta( $entry_id, '_zxtec_account_receipt', true ),
        );
    }

    /**
     * Calculate totals for accountability entries
     */
    private function calculate_accountability_totals( array $entries ) {
        $receita = 0;
        $despesa = 0;
        $receita_aprovada = 0;
        $despesa_aprovada = 0;
        $pendente_receita = 0;
        $pendente_despesa = 0;
        foreach ( $entries as $entry ) {
            $data = $this->get_accountability_entry_data( $entry->ID );
            if ( 'recusado' === $data['status'] ) {
                continue;
            }
            if ( 'despesa' === $data['type'] ) {
                $despesa += $data['amount'];
            } else {
                $receita += $data['amount'];
            }
            if ( 'aprovado' === $data['status'] ) {
                if ( 'despesa' === $data['type'] ) {
                    $despesa_aprovada += $data['amount'];
                } else {
                    $receita_aprovada += $data['amount'];
                }
            } else {
                if ( 'despesa' === $data['type'] ) {
                    $pendente_despesa += $data['amount'];
                } else {
                    $pendente_receita += $data['amount'];
                }
            }
        }
        return array(
            'receita' => $receita,
            'despesa' => $despesa,
            'saldo' => $receita - $despesa,
            'saldo_aprovado' => $receita_aprovada - $despesa_aprovada,
            'pendente_receita' => $pendente_receita,
            'pendente_despesa' => $pendente_despesa,
            'saldo_pendente' => $pendente_receita - $pendente_despesa,
        );
    }

    /**
     * Download accountability CSV for current user
     */
    public function download_accountability_csv() {
        if ( ! is_user_logged_in() ) {
            wp_die( __( 'Acesso negado', 'zxtec' ) );
        }
        $user_id = get_current_user_id();
        check_admin_referer( 'zxtec_accountability_export_' . $user_id );

        $filters = $this->get_accountability_filters();
        $entries = $this->get_accountability_entries( $user_id, $filters );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=prestacao_contas.csv' );
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Data', 'Descricao', 'Tipo', 'Status', 'Valor', 'Forma de pagamento', 'Observacoes', 'Comprovante' ) );
        foreach ( $entries as $entry ) {
            $data = $this->get_accountability_entry_data( $entry->ID );
            fputcsv( $output, array(
                $data['date'],
                $data['title'],
                $this->human_accountability_type( $data['type'] ),
                $this->human_accountability_status( $data['status'] ),
                number_format( $data['amount'], 2, ',', '' ),
                $data['payment_method'],
                $data['notes'],
                $data['receipt'],
            ) );
        }
        fclose( $output );
        exit;
    }

    /**
     * Translate accountability type to human labels
     */
    private function human_accountability_type( $type ) {
        switch ( $type ) {
            case 'receita':
                return __( 'Receita', 'zxtec' );
            case 'despesa':
                return __( 'Despesa', 'zxtec' );
            case 'ajuste':
                return __( 'Ajuste', 'zxtec' );
            default:
                return $type;
        }
    }

    /**
     * Translate accountability status to human labels
     */
    private function human_accountability_status( $status ) {
        switch ( $status ) {
            case 'pendente':
                return __( 'Pendente', 'zxtec' );
            case 'aprovado':
                return __( 'Aprovado', 'zxtec' );
            case 'recusado':
                return __( 'Recusado', 'zxtec' );
            default:
                return $status;
        }
    }

    /**
     * History HTML
     */
    private function get_history_html() {
        $tech  = absint( $_GET['tech'] ?? 0 );
        $start = isset( $_GET['start'] ) ? sanitize_text_field( $_GET['start'] ) : '';
        $end   = isset( $_GET['end'] ) ? sanitize_text_field( $_GET['end'] ) : '';

        $meta = array( array( 'key' => '_zxtec_status', 'value' => 'concluido' ) );
        if ( $tech ) {
            $meta[] = array( 'key' => '_zxtec_technician', 'value' => $tech );
        }
        if ( $start ) {
            $meta[] = array( 'key' => '_zxtec_date', 'value' => $start, 'compare' => '>=' );
        }
        if ( $end ) {
            $meta[] = array( 'key' => '_zxtec_date', 'value' => $end, 'compare' => '<=' );
        }

        $orders = get_posts( array(
            'post_type'   => 'zxtec_order',
            'numberposts' => -1,
            'meta_key'    => '_zxtec_date',
            'orderby'     => 'meta_value',
            'order'       => 'ASC',
            'meta_query'  => $meta,
        ) );

        ob_start();
        echo '<form method="get"><input type="hidden" name="page" value="zxtec_order_history" />';
        echo '<p>';
        echo '<label>' . esc_html__( 'Tecnico:', 'zxtec' ) . ' <select name="tech"><option value="">--</option>';
        foreach ( get_users( array( 'role' => 'zxtec_colaborador' ) ) as $u ) {
            echo '<option value="' . esc_attr( $u->ID ) . '" ' . selected( $tech, $u->ID, false ) . '>' . esc_html( $u->display_name ) . '</option>';
        }
        echo '</select></label> ';
        echo '<label>' . esc_html__( 'Inicio:', 'zxtec' ) . ' <input type="date" name="start" value="' . esc_attr( $start ) . '" /></label> ';
        echo '<label>' . esc_html__( 'Fim:', 'zxtec' ) . ' <input type="date" name="end" value="' . esc_attr( $end ) . '" /></label> ';
        echo '<input type="submit" class="button" value="' . esc_attr__( 'Filtrar', 'zxtec' ) . '" /> ';
        $export_url = add_query_arg( array( 'page' => 'zxtec_order_history', 'download' => 'csv', 'tech' => $tech, 'start' => $start, 'end' => $end ) );
        echo '<a class="button" href="' . esc_url( $export_url ) . '">' . esc_html__( 'Exportar CSV', 'zxtec' ) . '</a>';
        echo '</p></form>';

        if ( empty( $orders ) ) {
            echo '<p>' . esc_html__( 'Nenhum servico concluido.', 'zxtec' ) . '</p>';
            return ob_get_clean();
        }

        echo '<table class="table table-striped zxtec-table">';
        echo '<thead><tr><th>' . esc_html__( 'Ordem', 'zxtec' ) . '</th><th>' . esc_html__( 'Tecnico', 'zxtec' ) . '</th><th>' . esc_html__( 'Data', 'zxtec' ) . '</th></tr></thead><tbody>';
        foreach ( $orders as $order ) {
            $tech_id = get_post_meta( $order->ID, '_zxtec_technician', true );
            $user    = $tech_id ? get_userdata( $tech_id ) : null;
            echo '<tr><td>' . esc_html( $order->post_title ) . '</td><td>' . esc_html( $user ? $user->display_name : '' ) . '</td><td>' . esc_html( get_post_meta( $order->ID, '_zxtec_date', true ) ) . '</td></tr>';
        }
        echo '</tbody></table>';
        return ob_get_clean();
    }

    /**
     * Active contracts table HTML
     */
    private function get_contracts_html() {
        $contracts = get_posts( array(
            'post_type'   => 'zxtec_contract',
            'meta_key'    => '_zxtec_status',
            'meta_value'  => 'ativo',
            'numberposts' => -1,
        ) );

        if ( empty( $contracts ) ) {
            return '<p>' . esc_html__( 'Nenhum contrato ativo.', 'zxtec' ) . '</p>';
        }

        ob_start();
        echo '<table class="table table-striped zxtec-table">';
        echo '<thead><tr><th>' . esc_html__( 'Cliente', 'zxtec' ) . '</th><th>' . esc_html__( 'Plano', 'zxtec' ) . '</th><th>' . esc_html__( 'Inicio', 'zxtec' ) . '</th><th>' . esc_html__( 'Fim', 'zxtec' ) . '</th></tr></thead><tbody>';
        foreach ( $contracts as $contract ) {
            $client_id = get_post_meta( $contract->ID, '_zxtec_client', true );
            $client = $client_id ? get_post( $client_id ) : null;
            echo '<tr><td>' . esc_html( $client ? $client->post_title : '' ) . '</td><td>' . esc_html( get_post_meta( $contract->ID, '_zxtec_plan', true ) ) . '</td><td>' . esc_html( get_post_meta( $contract->ID, '_zxtec_start', true ) ) . '</td><td>' . esc_html( get_post_meta( $contract->ID, '_zxtec_end', true ) ) . '</td></tr>';
        }
        echo '</tbody></table>';
        return ob_get_clean();
    }

    /**
     * Technician schedule table HTML
     */
    private function get_schedule_html() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Necessario fazer login.', 'zxtec' ) . '</p>';
        }

        $user_id = get_current_user_id();
        $orders = get_posts( array(
            'post_type'  => 'zxtec_order',
            'meta_query' => array(
                array(
                    'key'   => '_zxtec_technician',
                    'value' => $user_id,
                ),
                array(
                    'key'   => '_zxtec_status',
                    'value' => 'confirmado',
                ),
            ),
            'meta_key'   => '_zxtec_date',
            'orderby'    => 'meta_value',
            'order'      => 'ASC',
            'numberposts' => -1,
        ) );

        if ( empty( $orders ) ) {
            return '<p>' . esc_html__( 'Nenhum agendamento.', 'zxtec' ) . '</p>';
        }

        ob_start();
        echo '<table class="table table-striped zxtec-table">';
        echo '<thead><tr><th>' . esc_html__( 'Ordem', 'zxtec' ) . '</th><th>' . esc_html__( 'Data', 'zxtec' ) . '</th><th>' . esc_html__( 'Cliente', 'zxtec' ) . '</th></tr></thead><tbody>';
        foreach ( $orders as $order ) {
            $client_id = get_post_meta( $order->ID, '_zxtec_client', true );
            $client = $client_id ? get_post( $client_id ) : null;
            echo '<tr><td>' . esc_html( $order->post_title ) . '</td><td>' . esc_html( get_post_meta( $order->ID, '_zxtec_date', true ) ) . '</td><td>' . esc_html( $client ? $client->post_title : '' ) . '</td></tr>';
        }
        echo '</tbody></table>';
        return ob_get_clean();
    }

    /**
     * Financial summary for logged in user
     */
    private function get_user_financial_html() {
        if ( ! is_user_logged_in() ) {
            return '';
        }

        $user_id = get_current_user_id();
        $orders = get_posts( array(
            'post_type'  => 'zxtec_order',
            'meta_query' => array(
                array( 'key' => '_zxtec_technician', 'value' => $user_id ),
                array( 'key' => '_zxtec_status', 'value' => 'concluido' ),
            ),
            'numberposts' => -1,
        ) );

        if ( empty( $orders ) ) {
            return '';
        }

        $total = 0;
        $expense_total = 0;
        ob_start();
        echo '<h2>' . esc_html__( 'Financeiro', 'zxtec' ) . '</h2>';
        $url  = wp_nonce_url( admin_url( 'admin-post.php?action=zxtec_user_financial_csv' ), 'zxtec_user_financial_' . $user_id );
        $urlp = wp_nonce_url( admin_url( 'admin-post.php?action=zxtec_user_financial_pdf' ), 'zxtec_user_financial_' . $user_id );
        echo '<p><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Exportar CSV', 'zxtec' ) . '</a> ';
        echo '<a class="button" href="' . esc_url( $urlp ) . '">' . esc_html__( 'Exportar PDF', 'zxtec' ) . '</a></p>';
        echo '<table class="table table-striped zxtec-table">';
        echo '<thead><tr><th>' . esc_html__( 'Servico', 'zxtec' ) . '</th><th>' . esc_html__( 'Preco', 'zxtec' ) . '</th><th>' . esc_html__( 'Comissao', 'zxtec' ) . '</th></tr></thead><tbody>';
        foreach ( $orders as $order ) {
            $service_id = get_post_meta( $order->ID, '_zxtec_service', true );
            $price = floatval( get_post_meta( $service_id, '_zxtec_price', true ) );
            $commission = $price * $this->get_commission_rate( $user_id );
            $total += $commission;
            echo '<tr><td>' . esc_html( $order->post_title ) . '</td><td>' . number_format_i18n( $price, 2 ) . '</td><td>' . number_format_i18n( $commission, 2 ) . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '<p><strong>' . sprintf( esc_html__( 'Total de comissoes: %s', 'zxtec' ), number_format_i18n( $total, 2 ) ) . '</strong></p>';

        $expenses = get_posts( array(
            'post_type'  => 'zxtec_expense',
            'meta_key'   => '_zxtec_technician',
            'meta_value' => $user_id,
            'numberposts' => -1,
        ) );
        foreach ( $expenses as $exp ) {
            $expense_total += floatval( get_post_meta( $exp->ID, '_zxtec_amount', true ) );
        }
        if ( $expense_total > 0 ) {
            echo '<p>' . sprintf( esc_html__( 'Total de despesas: %s', 'zxtec' ), number_format_i18n( $expense_total, 2 ) ) . '</p>';
        }
        $net = $total - $expense_total;
        echo '<p><strong>' . sprintf( esc_html__( 'Saldo liquido: %s', 'zxtec' ), number_format_i18n( $net, 2 ) ) . '</strong></p>';
        return ob_get_clean();
    }

    /**
     * Handle order confirmation
     */
    public function handle_confirm_order() {
        $order_id = absint( $_GET['order'] ?? 0 );
        if ( ! $order_id || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'zxtec_order_action_' . $order_id ) ) {
            wp_die( __( 'Acesso negado', 'zxtec' ) );
        }
        update_post_meta( $order_id, '_zxtec_status', 'confirmado' );
        $this->notify_admins( sprintf( __( 'Ordem %s confirmada', 'zxtec' ), get_the_title( $order_id ) ) );
        wp_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=zxtec_colaborador_dashboard' ) );
        exit;
    }

    /**
     * Handle order decline
     */
    public function handle_decline_order() {
        $order_id = absint( $_GET['order'] ?? 0 );
        if ( ! $order_id || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'zxtec_order_action_' . $order_id ) ) {
            wp_die( __( 'Acesso negado', 'zxtec' ) );
        }
        $reason = isset( $_GET['reason'] ) ? sanitize_text_field( wp_unslash( $_GET['reason'] ) ) : '';
        update_post_meta( $order_id, '_zxtec_status', 'recusado' );
        if ( $reason ) {
            update_post_meta( $order_id, '_zxtec_decline_reason', $reason );
        }
        $this->notify_admins( sprintf( __( 'Ordem %s recusada', 'zxtec' ), get_the_title( $order_id ) ) );
        wp_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=zxtec_colaborador_dashboard' ) );
        exit;
    }

    /**
     * Handle order finalize
     */
    public function handle_finalize_order() {
        $order_id = absint( $_GET['order'] ?? 0 );
        if ( ! $order_id || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'zxtec_order_action_' . $order_id ) ) {
            wp_die( __( 'Acesso negado', 'zxtec' ) );
        }
        update_post_meta( $order_id, '_zxtec_status', 'concluido' );
        $this->notify_admins( sprintf( __( 'Ordem %s finalizada', 'zxtec' ), get_the_title( $order_id ) ) );
        wp_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=zxtec_colaborador_dashboard' ) );
        exit;
    }

    /**
     * Technician map admin page
     */
    public function technician_map_page() {
        echo '<div class="zxtec-container"><div class="zxtec-card">';
        echo '<h1 class="mb-4">' . esc_html__( 'Mapa de Tecnicos', 'zxtec' ) . '</h1>';
        echo $this->get_technician_map_html();
        echo '</div></div>';
    }

    /**
     * Render technicians map HTML
     */
    private function get_technician_map_html() {
        $users = get_users( array( 'role' => 'zxtec_colaborador' ) );
        $markers = array();
        foreach ( $users as $user ) {
            $lat = get_user_meta( $user->ID, 'zxtec_lat', true );
            $lng = get_user_meta( $user->ID, 'zxtec_lng', true );
            if ( $lat && $lng ) {
                $markers[] = array( 'name' => $user->display_name, 'lat' => $lat, 'lng' => $lng );
            }
        }
        ob_start();
        ?>
        <div id="zxtec_tech_map" style="height:400px;"></div>
        <script>
        document.addEventListener('DOMContentLoaded',function(){
            var map=L.map('zxtec_tech_map').setView([0,0],2);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(map);
            var data=<?php echo json_encode( $markers ); ?>;
            data.forEach(function(m){L.marker([m.lat,m.lng]).addTo(map).bindPopup(m.name);});
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Output user location fields
     */
    public function user_location_fields( $user ) {
        $lat = get_user_meta( $user->ID, 'zxtec_lat', true );
        $lng = get_user_meta( $user->ID, 'zxtec_lng', true );
        $specialty = get_user_meta( $user->ID, 'zxtec_specialty', true );
        $cost_km  = get_user_meta( $user->ID, 'zxtec_cost_km', true );
        ?>
        <h2><?php esc_html_e( 'Localizacao ZX Tec', 'zxtec' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="zxtec_lat"><?php esc_html_e( 'Latitude', 'zxtec' ); ?></label></th>
                <td><input type="text" name="zxtec_lat" id="zxtec_lat" value="<?php echo esc_attr( $lat ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="zxtec_lng"><?php esc_html_e( 'Longitude', 'zxtec' ); ?></label></th>
                <td><input type="text" name="zxtec_lng" id="zxtec_lng" value="<?php echo esc_attr( $lng ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="zxtec_specialty"><?php esc_html_e( 'Especialidade', 'zxtec' ); ?></label></th>
                <td><input type="text" name="zxtec_specialty" id="zxtec_specialty" value="<?php echo esc_attr( $specialty ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="zxtec_cost_km"><?php esc_html_e( 'Custo por Km (R$)', 'zxtec' ); ?></label></th>
                <td><input type="number" step="0.01" name="zxtec_cost_km" id="zxtec_cost_km" value="<?php echo esc_attr( $cost_km ); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save user location
     */
    public function save_user_location_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }
        update_user_meta( $user_id, 'zxtec_lat', sanitize_text_field( $_POST['zxtec_lat'] ?? '' ) );
        update_user_meta( $user_id, 'zxtec_lng', sanitize_text_field( $_POST['zxtec_lng'] ?? '' ) );
        update_user_meta( $user_id, 'zxtec_specialty', sanitize_text_field( $_POST['zxtec_specialty'] ?? '' ) );
        update_user_meta( $user_id, 'zxtec_cost_km', floatval( $_POST['zxtec_cost_km'] ?? 0 ) );
    }

    /**
     * Output commission field for user
     */
    public function user_commission_field( $user ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $comm = get_user_meta( $user->ID, 'zxtec_commission', true );
        ?>
        <h2><?php esc_html_e( 'Comissao ZX Tec', 'zxtec' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="zxtec_commission_user"><?php esc_html_e( 'Percentual de Comissao', 'zxtec' ); ?></label></th>
                <td><input type="number" step="0.01" name="zxtec_commission_user" id="zxtec_commission_user" value="<?php echo esc_attr( $comm ); ?>" class="regular-text" /> %<p class="description"><?php esc_html_e( 'Deixe em branco para usar o valor padrao.', 'zxtec' ); ?></p></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save user commission field
     */
    public function save_user_commission_field( $user_id ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( isset( $_POST['zxtec_commission_user'] ) ) {
            $val = sanitize_text_field( $_POST['zxtec_commission_user'] );
            if ( $val === '' ) {
                delete_user_meta( $user_id, 'zxtec_commission' );
            } else {
                update_user_meta( $user_id, 'zxtec_commission', floatval( $val ) );
            }
        }
    }

    /**
     * Output collaborator account fields for administrators
     */
    public function user_account_fields( $user ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $document = get_user_meta( $user->ID, 'zxtec_document', true );
        $phone = get_user_meta( $user->ID, 'zxtec_phone', true );
        $address = get_user_meta( $user->ID, 'zxtec_address', true );
        $role_title = get_user_meta( $user->ID, 'zxtec_role_title', true );
        $contract_type = get_user_meta( $user->ID, 'zxtec_contract_type', true );
        $remuneration_rule = get_user_meta( $user->ID, 'zxtec_remuneration_rule', true );
        $status = get_user_meta( $user->ID, 'zxtec_status', true );
        ?>
        <h2><?php esc_html_e( 'Conta do Colaborador (ZX Tec)', 'zxtec' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="zxtec_document"><?php esc_html_e( 'CPF/Documento', 'zxtec' ); ?></label></th>
                <td><input type="text" name="zxtec_document" id="zxtec_document" value="<?php echo esc_attr( $document ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="zxtec_phone"><?php esc_html_e( 'Telefone', 'zxtec' ); ?></label></th>
                <td><input type="text" name="zxtec_phone" id="zxtec_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="zxtec_address"><?php esc_html_e( 'Endereco', 'zxtec' ); ?></label></th>
                <td><input type="text" name="zxtec_address" id="zxtec_address" value="<?php echo esc_attr( $address ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="zxtec_role_title"><?php esc_html_e( 'Cargo/Funcao', 'zxtec' ); ?></label></th>
                <td><input type="text" name="zxtec_role_title" id="zxtec_role_title" value="<?php echo esc_attr( $role_title ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="zxtec_contract_type"><?php esc_html_e( 'Tipo de contrato', 'zxtec' ); ?></label></th>
                <td><input type="text" name="zxtec_contract_type" id="zxtec_contract_type" value="<?php echo esc_attr( $contract_type ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'fixo, comissao, hibrido', 'zxtec' ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="zxtec_remuneration_rule"><?php esc_html_e( 'Regra de remuneracao', 'zxtec' ); ?></label></th>
                <td><input type="text" name="zxtec_remuneration_rule" id="zxtec_remuneration_rule" value="<?php echo esc_attr( $remuneration_rule ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="zxtec_status"><?php esc_html_e( 'Status', 'zxtec' ); ?></label></th>
                <td>
                    <select name="zxtec_status" id="zxtec_status">
                        <option value="ativo" <?php selected( $status, 'ativo' ); ?>><?php esc_html_e( 'Ativo', 'zxtec' ); ?></option>
                        <option value="inativo" <?php selected( $status, 'inativo' ); ?>><?php esc_html_e( 'Inativo', 'zxtec' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save collaborator account fields for administrators
     */
    public function save_user_account_fields( $user_id ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        update_user_meta( $user_id, 'zxtec_document', sanitize_text_field( $_POST['zxtec_document'] ?? '' ) );
        update_user_meta( $user_id, 'zxtec_phone', sanitize_text_field( $_POST['zxtec_phone'] ?? '' ) );
        update_user_meta( $user_id, 'zxtec_address', sanitize_text_field( $_POST['zxtec_address'] ?? '' ) );
        update_user_meta( $user_id, 'zxtec_role_title', sanitize_text_field( $_POST['zxtec_role_title'] ?? '' ) );
        update_user_meta( $user_id, 'zxtec_contract_type', sanitize_text_field( $_POST['zxtec_contract_type'] ?? '' ) );
        update_user_meta( $user_id, 'zxtec_remuneration_rule', sanitize_text_field( $_POST['zxtec_remuneration_rule'] ?? '' ) );
        update_user_meta( $user_id, 'zxtec_status', sanitize_text_field( $_POST['zxtec_status'] ?? '' ) );
    }

    /**
     * Assign the nearest technician to an order
     */
    private function assign_nearest_technician( $order_id ) {
        $lat = get_post_meta( $order_id, '_zxtec_lat', true );
        $lng = get_post_meta( $order_id, '_zxtec_lng', true );
        if ( ! $lat || ! $lng ) {
            return;
        }

        $service_id = get_post_meta( $order_id, '_zxtec_service', true );
        $specialty  = $service_id ? get_post_meta( $service_id, '_zxtec_specialty', true ) : '';

        $args = array( 'role' => 'zxtec_colaborador' );
        if ( $specialty ) {
            $args['meta_query'] = array(
                array(
                    'key'   => 'zxtec_specialty',
                    'value' => $specialty,
                ),
            );
        }

        $users = get_users( $args );
        $closest = 0;
        $best_cost = PHP_FLOAT_MAX;
        $best_distance = PHP_FLOAT_MAX;
        foreach ( $users as $user ) {
            $t_lat = get_user_meta( $user->ID, 'zxtec_lat', true );
            $t_lng = get_user_meta( $user->ID, 'zxtec_lng', true );
            if ( $t_lat && $t_lng ) {
                $d = $this->haversine_distance( $lat, $lng, $t_lat, $t_lng );
                $cost_km = floatval( get_user_meta( $user->ID, 'zxtec_cost_km', true ) );
                $est_cost = $d * ( $cost_km ?: 1 );
                if ( $est_cost < $best_cost || ( $est_cost === $best_cost && $d < $best_distance ) ) {
                    $best_cost = $est_cost;
                    $best_distance = $d;
                    $closest = $user->ID;
                }
            }
        }
        if ( $closest ) {
            update_post_meta( $order_id, '_zxtec_technician', $closest );
            $this->auto_schedule_order( $order_id, $closest );
        }
    }

    /**
     * Schedule order if empty date
     */
    private function auto_schedule_order( $order_id, $tech_id ) {
        $current = get_post_meta( $order_id, '_zxtec_date', true );
        if ( $current ) {
            return;
        }
        $orders = get_posts( array(
            'post_type'  => 'zxtec_order',
            'meta_key'   => '_zxtec_technician',
            'meta_value' => $tech_id,
            'numberposts' => -1,
            'post_status' => 'publish',
        ) );
        $taken = array();
        foreach ( $orders as $o ) {
            $d = get_post_meta( $o->ID, '_zxtec_date', true );
            if ( $d ) {
                $taken[ $d ] = true;
            }
        }
        $date = strtotime( 'tomorrow' );
        for ( $i = 0; $i < 30; $i++ ) {
            $check = date( 'Y-m-d', $date + DAY_IN_SECONDS * $i );
            if ( empty( $taken[ $check ] ) ) {
                update_post_meta( $order_id, '_zxtec_date', $check );
                break;
            }
        }
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function haversine_distance( $lat1, $lon1, $lat2, $lon2 ) {
        $earth = 6371; // km
        $dLat = deg2rad( $lat2 - $lat1 );
        $dLon = deg2rad( $lon2 - $lon1 );
        $a = sin( $dLat / 2 ) * sin( $dLat / 2 ) + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dLon / 2 ) * sin( $dLon / 2 );
        $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
        return $earth * $c;
    }

    /**
     * Download financial CSV for current user
     */
    public function download_user_financial_csv() {
        if ( ! is_user_logged_in() ) {
            wp_die( __( 'Acesso negado', 'zxtec' ) );
        }
        $user_id = get_current_user_id();
        check_admin_referer( 'zxtec_user_financial_' . $user_id );

        $orders = get_posts( array(
            'post_type'  => 'zxtec_order',
            'meta_query' => array(
                array( 'key' => '_zxtec_technician', 'value' => $user_id ),
                array( 'key' => '_zxtec_status', 'value' => 'concluido' ),
            ),
            'numberposts' => -1,
        ) );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=financeiro_usuario.csv' );
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Servico', 'Preco', 'Comissao' ) );
        foreach ( $orders as $order ) {
            $service_id = get_post_meta( $order->ID, '_zxtec_service', true );
            $price = floatval( get_post_meta( $service_id, '_zxtec_price', true ) );
            $commission = $price * $this->get_commission_rate( $user_id );
            fputcsv( $output, array( $order->post_title, number_format( $price, 2, ',', '' ), number_format( $commission, 2, ',', '' ) ) );
        }
        fclose( $output );
        exit;
    }

    /**
     * Download financial PDF for current user
     */
    public function download_user_financial_pdf() {
        if ( ! is_user_logged_in() ) {
            wp_die( __( 'Acesso negado', 'zxtec' ) );
        }
        $user_id = get_current_user_id();
        check_admin_referer( 'zxtec_user_financial_' . $user_id );

        $orders = get_posts( array(
            'post_type'  => 'zxtec_order',
            'meta_query' => array(
                array( 'key' => '_zxtec_technician', 'value' => $user_id ),
                array( 'key' => '_zxtec_status', 'value' => 'concluido' ),
            ),
            'numberposts' => -1,
        ) );

        require_once dirname( dirname( __FILE__ ) ) . '/lib/fpdf.php';
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont( 'Arial', '', 12 );
        $pdf->Cell( 0, 10, __( 'Relatorio Financeiro', 'zxtec' ), 0, 1 );
        $total = 0;
        foreach ( $orders as $order ) {
            $service_id = get_post_meta( $order->ID, '_zxtec_service', true );
            $price = floatval( get_post_meta( $service_id, '_zxtec_price', true ) );
            $commission = $price * $this->get_commission_rate( $user_id );
            $total += $commission;
            $line = sprintf( '%s - %s', $order->post_title, number_format_i18n( $commission, 2 ) );
            $pdf->Cell( 0, 8, $line, 0, 1 );
        }
        $pdf->Cell( 0, 10, sprintf( __( 'Total de comissoes: %s', 'zxtec' ), number_format_i18n( $total, 2 ) ), 0, 1 );
        $expenses = get_posts( array(
            'post_type'  => 'zxtec_expense',
            'meta_key'   => '_zxtec_technician',
            'meta_value' => $user_id,
            'numberposts' => -1,
        ) );
        $expense_total = 0;
        foreach ( $expenses as $exp ) {
            $expense_total += floatval( get_post_meta( $exp->ID, '_zxtec_amount', true ) );
        }
        if ( $expense_total > 0 ) {
            $pdf->Cell( 0, 8, sprintf( __( 'Total de despesas: %s', 'zxtec' ), number_format_i18n( $expense_total, 2 ) ), 0, 1 );
        }
        $net = $total - $expense_total;
        $pdf->Cell( 0, 8, sprintf( __( 'Saldo liquido: %s', 'zxtec' ), number_format_i18n( $net, 2 ) ), 0, 1 );
        $pdf->Output( 'D', 'financeiro_usuario.pdf' );
        exit;
    }

    /**
     * Download filtered history as CSV
     */
    public function download_history_csv() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Acesso negado', 'zxtec' ) );
        }

        $tech  = absint( $_GET['tech'] ?? 0 );
        $start = isset( $_GET['start'] ) ? sanitize_text_field( $_GET['start'] ) : '';
        $end   = isset( $_GET['end'] ) ? sanitize_text_field( $_GET['end'] ) : '';

        $meta = array( array( 'key' => '_zxtec_status', 'value' => 'concluido' ) );
        if ( $tech ) {
            $meta[] = array( 'key' => '_zxtec_technician', 'value' => $tech );
        }
        if ( $start ) {
            $meta[] = array( 'key' => '_zxtec_date', 'value' => $start, 'compare' => '>=' );
        }
        if ( $end ) {
            $meta[] = array( 'key' => '_zxtec_date', 'value' => $end, 'compare' => '<=' );
        }

        $orders = get_posts( array(
            'post_type'   => 'zxtec_order',
            'numberposts' => -1,
            'meta_query'  => $meta,
        ) );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=historico.csv' );
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, array( 'Ordem', 'Tecnico', 'Data' ) );
        foreach ( $orders as $order ) {
            $tech_id = get_post_meta( $order->ID, '_zxtec_technician', true );
            $user    = $tech_id ? get_userdata( $tech_id ) : null;
            fputcsv( $out, array( $order->post_title, $user ? $user->display_name : '', get_post_meta( $order->ID, '_zxtec_date', true ) ) );
        }
        fclose( $out );
        exit;
    }

    /**
     * Download expenses CSV
     */
    public function download_expense_csv() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Acesso negado', 'zxtec' ) );
        }
        ZXTEC_Expense::download_csv();
        exit;
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting( 'zxtec_settings', 'zxtec_commission', array(
            'type' => 'number',
            'sanitize_callback' => 'floatval',
            'default' => 10,
        ) );
    }

    /**
     * Settings page
     */
    public function settings_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Configuracoes', 'zxtec' ) . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'zxtec_settings' );
        echo '<table class="form-table">';
        echo '<tr><th scope="row">' . esc_html__( 'Percentual de Comissao', 'zxtec' ) . '</th>';
        echo '<td><input name="zxtec_commission" type="number" step="0.01" value="' . esc_attr( get_option( 'zxtec_commission', 10 ) ) . '" /> %</td></tr>';
        echo '</table>';
        submit_button();
        echo '</form></div>';
    }

    /**
     * Get commission rate as decimal
     */
    private function get_commission_rate( $user_id = 0 ) {
        $rate = floatval( get_option( 'zxtec_commission', 10 ) );
        if ( $user_id ) {
            $user_rate = get_user_meta( $user_id, 'zxtec_commission', true );
            if ( $user_rate !== '' && $user_rate !== false ) {
                $rate = floatval( $user_rate );
            }
        }
        return $rate / 100;
    }

    /**
     * Register dashboard widget
     */
    public function add_dashboard_widget() {
        if ( current_user_can( 'manage_options' ) ) {
            wp_add_dashboard_widget( 'zxtec_overview', __( 'ZX Tec - Visao Geral', 'zxtec' ), array( $this, 'render_dashboard_widget' ) );
        }
    }

    /**
     * Render dashboard widget content
     */
    public function render_dashboard_widget() {
        $pending    = $this->count_orders_by_status( 'pendente' );
        $confirmed  = $this->count_orders_by_status( 'confirmado' );
        $completed  = $this->count_orders_by_status( 'concluido' );
        echo '<ul>';
        echo '<li>' . esc_html__( 'Pendentes:', 'zxtec' ) . ' ' . intval( $pending ) . '</li>';
        echo '<li>' . esc_html__( 'Confirmadas:', 'zxtec' ) . ' ' . intval( $confirmed ) . '</li>';
        echo '<li>' . esc_html__( 'Concluidas:', 'zxtec' ) . ' ' . intval( $completed ) . '</li>';
        echo '</ul>';
    }

    /**
     * Count orders by custom status
     */
    private function count_orders_by_status( $status ) {
        return count( get_posts( array(
            'post_type'   => 'zxtec_order',
            'numberposts' => -1,
            'meta_key'    => '_zxtec_status',
            'meta_value'  => $status,
            'post_status' => 'publish',
        ) ) );
    }

    /**
     * Translate internal status codes to human labels
     */
    private function human_status( $status ) {
        switch ( $status ) {
            case 'pendente':
                return __( 'A Iniciar', 'zxtec' );
            case 'confirmado':
                return __( 'Em execucao', 'zxtec' );
            case 'recusado':
                return __( 'Pausado', 'zxtec' );
            case 'concluido':
                return __( 'Finalizado', 'zxtec' );
            default:
                return $status;
        }
    }

    /**
     * Store a notification for a user
     */
    private function add_notification( $user_id, $message ) {
        $notes   = (array) get_user_meta( $user_id, 'zxtec_notifications', true );
        $notes[] = array(
            'message' => sanitize_text_field( $message ),
            'time'    => current_time( 'mysql' ),
        );
        update_user_meta( $user_id, 'zxtec_notifications', $notes );
    }

    /**
     * Retrieve notifications for a user
     */
    private function get_notifications( $user_id ) {
        return (array) get_user_meta( $user_id, 'zxtec_notifications', true );
    }

    /**
     * Notify all administrators
     */
    private function notify_admins( $message ) {
        $admins = get_users( array( 'role' => 'administrator' ) );
        foreach ( $admins as $admin ) {
            $this->add_notification( $admin->ID, $message );
        }
    }

    /**
     * Clear a single notification
     */
    public function handle_clear_notification() {
        if ( ! is_user_logged_in() ) {
            wp_die( __( 'Acesso negado', 'zxtec' ) );
        }
        $user_id = get_current_user_id();
        check_admin_referer( 'zxtec_clear_note_' . $user_id );
        $index   = absint( $_GET['n'] ?? -1 );
        $notes   = (array) get_user_meta( $user_id, 'zxtec_notifications', true );
        if ( $index >= 0 && isset( $notes[ $index ] ) ) {
            unset( $notes[ $index ] );
            update_user_meta( $user_id, 'zxtec_notifications', array_values( $notes ) );
        }
        wp_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=zxtec_colaborador_dashboard' ) );
        exit;
    }
}

