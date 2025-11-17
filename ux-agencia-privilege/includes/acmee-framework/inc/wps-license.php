<?php 
//License activation form

// add registration page submenu
add_action( 'admin_menu', 'wps_license_register' );
function wps_license_register() {
    add_submenu_page( 'wpshapere-options', esc_html__( 'WPShapere Registration', 'wps' ), esc_html__( 'Manage/Register license', 'wps' ), 'manage_options', 'register-wpshapere', 'acm_register_license' );
}

function wps_get_host() {
    $parsed_url = parse_url(get_site_url());
    $host = $parsed_url['host'] ?? preg_replace('/^https?:\/\//', '', get_site_url());
    $host = preg_replace('/^www\./', '', $host);
    $host_name = explode('/', $host)[0];
    return $host_name;
}

function acm_register_license() {

    $license_data = get_option( 'wps_purchase_data' );
    $domain_name = wps_get_host();
    ?>
    <div class="acm-license-process-form" style="background-color:#fff;padding: 30px;width:790px;margin:100px auto 0;">
    <?php if ( !empty($license_data) && !empty($license_data['license_key']) ): 
            ?>
            <!-- Display License Details -->

                <div class="wps-license-details">
                    <div class="license-item no-border"><span class="license-active" style="display:flex;"><svg style="margin-right:5px" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
<mask id="mask0_442_433" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="20" height="20">
<rect x="0.371754" y="0.216003" width="19" height="19" fill="#D9D9D9"/>
</mask>
<g mask="url(#mask0_442_433)">
<path d="M8.76342 13.3577L14.3447 7.77646L13.2363 6.66813L8.76342 11.141L6.50717 8.88479L5.39884 9.99313L8.76342 13.3577ZM9.87175 17.6327C8.77661 17.6327 7.74745 17.4249 6.78425 17.0093C5.82106 16.5936 4.98321 16.0296 4.27071 15.3171C3.55821 14.6046 2.99415 13.7667 2.57852 12.8035C2.1629 11.8403 1.95509 10.8112 1.95509 9.71604C1.95509 8.6209 2.1629 7.59174 2.57852 6.62854C2.99415 5.66535 3.55821 4.8275 4.27071 4.115C4.98321 3.4025 5.82106 2.83844 6.78425 2.42281C7.74745 2.00719 8.77661 1.79938 9.87175 1.79938C10.9669 1.79938 11.9961 2.00719 12.9593 2.42281C13.9224 2.83844 14.7603 3.4025 15.4728 4.115C16.1853 4.8275 16.7494 5.66535 17.165 6.62854C17.5806 7.59174 17.7884 8.6209 17.7884 9.71604C17.7884 10.8112 17.5806 11.8403 17.165 12.8035C16.7494 13.7667 16.1853 14.6046 15.4728 15.3171C14.7603 16.0296 13.9224 16.5936 12.9593 17.0093C11.9961 17.4249 10.9669 17.6327 9.87175 17.6327ZM9.87175 16.0494C11.6398 16.0494 13.1374 15.4358 14.3645 14.2088C15.5915 12.9817 16.2051 11.4841 16.2051 9.71604C16.2051 7.94799 15.5915 6.45042 14.3645 5.22334C13.1374 3.99625 11.6398 3.38271 9.87175 3.38271C8.1037 3.38271 6.60613 3.99625 5.37904 5.22334C4.15196 6.45042 3.53842 7.94799 3.53842 9.71604C3.53842 11.4841 4.15196 12.9817 5.37904 14.2088C6.60613 15.4358 8.1037 16.0494 9.87175 16.0494Z" fill="#3C434A"/>
</g>
</svg> <?php echo esc_html__('ACTIVATED', 'wps'); ?> - <?php echo !empty($license_data['license_type']) ? esc_html($license_data['license_type']) : '' ?></span></div>
                    <div class="license-item"><strong><?php echo esc_html__('License Key', 'wps'); ?>:</strong> <span><?php echo esc_html($license_data['license_key']); ?></span>
                    </div>
                    <div class="license-item"><strong><?php echo esc_html__('Registered Domain', 'wps'); ?>:</strong><?php echo esc_html(home_url()); ?></div>
                </div>

            <!-- Deactivate License Button -->
            <form method="post" id="acm-deactivate-form" class="acm-deactivate-form" action="<?php echo esc_url( admin_url('admin.php?page=register-wpshapere') ) ?>" style="display: flex;align-items: center;justify-content: space-around;">
                <?php wp_nonce_field('aof_license_action', 'aof_nonce'); ?>
                <input type="hidden" name="purchase_code" id="license-key" value="<?php echo esc_html( $license_data['license_key'] ); ?>" >
                <input type="hidden" name="domain" id="domain-name" value="<?php echo esc_attr( $domain_name ); ?>">
                <input type="hidden" name="product_code" id="product-code" value="8183353">
                <button type="submit" name="deactivate_license" class="deactivate-license wps-button primary"><svg class="loader" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30">
                    <radialGradient id="a11" cx=".66" fx=".66" cy=".3125" fy=".3125" gradientTransform="scale(1.5)">
                        <stop offset="0" stop-color="#11C4FF"></stop>
                        <stop offset=".3" stop-color="#11C4FF" stop-opacity=".9"></stop>
                        <stop offset=".6" stop-color="#11C4FF" stop-opacity=".6"></stop>
                        <stop offset=".9" stop-color="#11C4FF" stop-opacity=".3"></stop>
                        <stop offset="1" stop-color="#11C4FF" stop-opacity="0"></stop>
                    </radialGradient>
                    <circle transform-origin="center" fill="none" stroke="url(#a11)" stroke-width="2.4" stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0" cx="15" cy="15" r="10.5">
                        <animateTransform type="rotate" attributeName="transform" calcMode="spline" dur="2" values="0;360" keyTimes="0;1" keySplines="0 0 1 1" repeatCount="indefinite"></animateTransform>
                    </circle>
                    <circle transform-origin="center" fill="none" opacity=".2" stroke="#11C4FF" stroke-width="2.4" stroke-linecap="round" cx="15" cy="15" r="10.5"></circle>
                    </svg> <span class="btn-label"><?php echo esc_html__('Detach License', 'wps'); ?></span></button>
                    <a class="wps-button primary" style="margin-top: 20px;text-decoration:none" href="<?php echo esc_url( 'admin.php?page=wpshapere-options' ); ?>"><?php echo esc_html__('Go to Settings', 'wps'); ?></a>
            </form>

        <?php else: 

        $key = null;
        if( !empty($license_data) && is_array($license_data) ) {
            $old_activation = count($license_data) == 4 && strlen($license_data[3][0]) == 8 ? true : false;
            if($old_activation) {
                $key = implode('-', $license_data[3]);
            }
        }
            
            ?>

        <!-- License Activation Form -->
        <form method="post" class="aof-form wps-form" id="acm-license-activation-form" action="<?php echo esc_url( admin_url('admin.php?page=register-wpshapere') ) ?>">
            <?php if( !empty($key)) : ?>
            <div><em><?php echo esc_html__('Your purchase code:', 'wps'); ?> <?php echo esc_html( $key ); ?></em></div>
            <h5><?php echo esc_html__("You have already registered, but we have moved to a new licensing system. Please re-register by copying your purchase code (shown above) and pasting it in the field below to activate.", 'wps'); ?></h5>
            <?php endif; ?>
            <h3 class="form-title"><?php echo esc_html__('Activate Your License', 'wps'); ?></h3>

            <?php wp_nonce_field('aof_license_action', 'aof_nonce'); ?>

            <label for="license_key"><strong><?php echo esc_html__('License Key', 'wps'); ?></strong></label>
            <div class="form-wrap">
                <input type="text" name="purchase_code" id="license-key" class="wps-input" required placeholder="<?php echo esc_html__('Enter your license key', 'wps'); ?>">
                    <input type="hidden" name="domain" id="domain-name" value="<?php echo esc_html( $domain_name ); ?>">
                    <input type="hidden" name="domain" id="product-code" value="8183353">
                    <button type="submit" class="wps-button primary"><svg class="loader" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30">
                    <radialGradient id="a11" cx=".66" fx=".66" cy=".3125" fy=".3125" gradientTransform="scale(1.5)">
                        <stop offset="0" stop-color="#11C4FF"></stop>
                        <stop offset=".3" stop-color="#11C4FF" stop-opacity=".9"></stop>
                        <stop offset=".6" stop-color="#11C4FF" stop-opacity=".6"></stop>
                        <stop offset=".9" stop-color="#11C4FF" stop-opacity=".3"></stop>
                        <stop offset="1" stop-color="#11C4FF" stop-opacity="0"></stop>
                    </radialGradient>
                    <circle transform-origin="center" fill="none" stroke="url(#a11)" stroke-width="2.4" stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0" cx="15" cy="15" r="10.5">
                        <animateTransform type="rotate" attributeName="transform" calcMode="spline" dur="2" values="0;360" keyTimes="0;1" keySplines="0 0 1 1" repeatCount="indefinite"></animateTransform>
                    </circle>
                    <circle transform-origin="center" fill="none" opacity=".2" stroke="#11C4FF" stroke-width="2.4" stroke-linecap="round" cx="15" cy="15" r="10.5"></circle>
                    </svg> <span class="btn-label"><?php echo esc_html__('Activate', 'wps'); ?></span></button>
                </div>
                <div id="api-response" style="margin-top: 20px;"></div>
            </form>
            
        <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank"><?php echo esc_html__('How to find your purchase code?', 'wps'); ?></a> <br><br>
        <?php echo esc_html__('For support, please visit', 'wps');
          echo ' <a target="_blank" style="background:#acf8e4;text-decoration:none;padding:3px 9px;border-radius:3px;border-bottom:2px solid #91dcc8" href="https://acmeedesign.support">https://acmeedesign.support</a>';
    
    endif; ?>
    </div> 
    <?php
}

//** 
// verify purchase code
// @since 2.5.0
//  */
function wps_verify_envato_purchase() {

    $api_url = 'https://acmeedesign.com/wp-json/envato/v3/verify/';

    //purchase code
    $purchase_code = wp_unslash(sanitize_text_field( $_POST['purchase_code'] ));

    $hostname = wps_get_host();

    if (strpos($hostname, 'localhost') !== false || $hostname === '127.0.0.1') {
        $license_data = [
            'license_key' => $purchase_code,
            'product_code'  => '8183353',
            'license_type'  => ''
        ];
        update_option( 'wps_purchase_data', $license_data );
        wp_send_json_success(); // Send success response
    }

    $data = [
        'purchase_code' => $purchase_code,
        'domain_name' => $hostname,
    ];

    // Make remote POST request
    $response = wp_remote_post($api_url, array(
        'body' => $data,
        'timeout' => 15,
        'sslverify' => false // Set to true in production
    ));

    // Check for errors in the remote request
    if (is_wp_error($response)) {
        wp_send_json_error('API request failed: ' . $response->get_error_message());
    }

    // Decode API response
    $api_data = json_decode(wp_remote_retrieve_body($response), true);

    // Check if license is valid
    if (isset($api_data['license']) && $api_data['license'] === 'valid') {
        $license_type = !isset($api_data['license_type']) ? $api_data['license_type'] : '';
        //update database
        $license_data = [
            'license_key' => $purchase_code,
            'product_code'  => '8183353',
            'license_type'  => $license_type
        ];

        update_option( 'wps_purchase_data', $license_data );
        wp_send_json_success($api_data); // Send success response

    } elseif (isset($api_data['license']) && $api_data['license'] === 'exhausted') {
        wp_send_json_error( esc_html__( 'All licenses used: The number of licenses you purchased has already been used on other domains. Please purchase a new license to continue using this product.', 'wps' ) );

    } else {
        wp_send_json_error(esc_html__('Invalid license key or it is already registered on another domain.', 'wps'));
    }

}
add_action('wp_ajax_wps_verify_envato_purchase', 'wps_verify_envato_purchase');

//license activation
function wps_detach_license() {

    if (!isset($_POST['purchase_code'])) {
        wp_send_json_error('Invalid data.');
    }

    $hostname = wps_get_host();

    if (strpos($hostname, 'localhost') !== false || $hostname === '127.0.0.1') {
        delete_option( 'wps_purchase_data' );
        wp_send_json_success(); // Sending success response
    }

    // Get data from AJAX request
    $purchase_code = wp_unslash(sanitize_text_field($_POST['purchase_code']));

    // Prepare data for remote POST request
    $api_url = 'https://acmeedesign.com/wp-json/envato/v3/deactivate/';
    $body = array(
        'purchase_code' => $purchase_code,
        'domain_name' => $hostname
    );

    // Make remote POST request
    $response = wp_remote_post($api_url, array(
        'body' => $body,
        'timeout' => 15,
        'sslverify' => false // Set to true in production
    ));

    // Check for errors in the remote request
    if (is_wp_error($response)) {
        wp_send_json_error('API request failed: ' . $response->get_error_message());
    }

    // Decode API response
    $api_data = json_decode(wp_remote_retrieve_body($response), true);

    // Check if license is valid and detached
    if (isset($api_data['status']) && $api_data['status'] === 'success') {
        //update database and send success response
        delete_option('wps_purchase_data');
        wp_send_json_success($api_data); // Send success response

    } else {
        delete_option('wps_purchase_data'); //delete the license data even if there is no response from API or returned invalid key
        wp_send_json_success('Invalid license key.'); //API success but no action done on remote server
    }
}
add_action('wp_ajax_wps_detach_license', 'wps_detach_license');
