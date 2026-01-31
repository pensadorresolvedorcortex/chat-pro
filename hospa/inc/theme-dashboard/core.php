<?php
/**
 * Hospa Dashboard Functionalities
 *
 * Handles theme dashboard pages, menu items, scripts, and redirections.
 */

// Load required files
require get_parent_theme_file_path('/inc/theme-dashboard/tgmpa/tgmpa.php');

/**
 * Add Hospa Theme dashboard menu and submenu pages.
 */
function hospa_dashboard_submenu_page() {
    add_menu_page(
        esc_html__('Hospa Theme', 'hospa'),
        esc_html__('Hospa Theme', 'hospa'),
        'manage_options',
        'hospa-dashboard',
        '',
        get_template_directory_uri() . '/inc/theme-dashboard/images/dashboard.svg',
        2
    );

    // Define submenu pages
    $submenus = array(
        'hospa-dashboard'      => array('title' => __('Getting Started', 'hospa'), 'callback' => 'hospa_screen_welcome'),
        'hospa-admin-plugins'  => array('title' => __('Plugins', 'hospa'), 'callback' => 'hospa_screen_plugin'),
        'hospa-activation'     => array('title' => __('Activation', 'hospa'), 'callback' => 'hospa_activation_page'),
        'hospa-demo-import'    => array('title' => __('Demo Import', 'hospa'), 'callback' => 'hospa_demo_page'),
        'hospa_opt'            => array('title' => __('Theme Options', 'hospa'), 'callback' => 'hospa_options_page'),
        'hospa-support'        => array('title' => __('Support + Others', 'hospa'), 'callback' => 'hospa_support_page'),
        'hospa-more-themes'    => array('title' => __('+ More Themes', 'hospa'), 'callback' => 'hospa_more_themes_page'),
    );

    foreach ($submenus as $slug => $menu) {
        add_submenu_page(
            'hospa-dashboard',
            $menu['title'],
            $menu['title'],
            'manage_options',
            $slug,
            $menu['callback']
        );
    }
}
add_action('admin_menu', 'hospa_dashboard_submenu_page');

/**
 * Load the "Getting Started" page.
 */
function hospa_screen_welcome() {
	echo '<div class="wrap" style="height:0;overflow:hidden;"><h2></h2></div>';
    locate_template('/inc/theme-dashboard/welcome.php', true, true);
}

/**
 * Load the "Support" page.
 */
function hospa_support_page() {
	echo '<div class="wrap" style="height:0;overflow:hidden;"><h2></h2></div>';
    locate_template('/inc/theme-dashboard/theme-support.php', true, true);
}

/**
 * Load the "Demo Import" page.
 */
function hospa_demo_page() {
	echo '<div class="wrap" style="height:0;overflow:hidden;"><h2></h2></div>';
    locate_template('/inc/theme-dashboard/theme-demo.php', true, true);
}

/**
 * Load the "More Themes" page.
 */
function hospa_more_themes_page() {
	echo '<div class="wrap" style="height:0;overflow:hidden;"><h2></h2></div>';
    locate_template('/inc/theme-dashboard/theme-more-themes.php', true, true);
}

/**
 * Load the "Plugin Installation" page.
 */
function hospa_screen_plugin() {
	echo '<div class="wrap" style="height:0;overflow:hidden;"><h2></h2></div>';
    locate_template('/inc/theme-dashboard/install-plugins.php', true, true);
}

// Load admin-related files if in the admin dashboard
if (is_admin()) {
    include_once get_template_directory() . '/inc/theme-dashboard/et-admin.php';
}

/**
 * Enqueue admin dashboard styles and scripts.
 */
function hospa_enqueue_scripts() {
    wp_enqueue_style('hospa-admin-styles', HOSPA_THEME_URI . '/inc/theme-dashboard/css/admin-pages.css', array(), wp_get_theme()->get('Version'));
    wp_enqueue_script('hospa-admin', HOSPA_THEME_URI . '/inc/theme-dashboard/js/theme-admin.min.js', array('jquery'), wp_get_theme()->get('Version'), true);
}
add_action('admin_enqueue_scripts', 'hospa_enqueue_scripts');

/**
 * Generate Hospa Admin Navigation Tabs.
 *
 * @param string $active_tab Active tab identifier.
 */
function hospa_admin_navigation_tabs($active_tab) {
    $hospa_my_theme = wp_get_theme();
    $plugin_active = function_exists('hospa_function_pcs');

    if ($hospa_my_theme->parent_theme) {
        $hospa_my_theme = wp_get_theme(basename(get_template_directory()));
    }

    // Determine theme activation status class
    $theme_status = '';
    if ($plugin_active) {
        $theme_status = get_option('hospa_purchase_code_status') === 'valid' ? 'et-valid-nav' : 'et-not-valid-nav';
    }

    ?>
    <div class="et-header">
        <h1><?php echo esc_html__('Welcome to ', 'hospa') . esc_html($hospa_my_theme->Name) . esc_html__(' Theme', 'hospa'); ?></h1>
        <div class="about-text"><?php printf(esc_html__('Version: %s', 'hospa'), esc_html($hospa_my_theme->Version)); ?></div>
        <div class="about-text">
            <?php echo esc_html__('Thank you for choosing ', 'hospa') . esc_html($hospa_my_theme->Name) . esc_html__(' WordPress Theme! Get started building your stunning site by easily importing theme prebuilt demos and customizing the theme options to suit your needs.', 'hospa'); ?>
        </div>
        <img class="welcome-shape" src="<?php echo esc_url(get_template_directory_uri() . '/screenshot.png'); ?>" alt="<?php echo esc_attr__('Banner', 'hospa'); ?>">
        <a href="https://themeforest.net/downloads" target="_blank" class="etd-rating">
            <span class="dashicons dashicons-star-filled"></span>
            <?php echo esc_html__('Rate ', 'hospa') . esc_html($hospa_my_theme->Name) . esc_html__(' Theme on ThemeForest', 'hospa'); ?>
        </a>
    </div>

    <?php
    $tabs = array(
        'dashboard'          => array('title' => __('Getting Started', 'hospa'), 'url' => 'admin.php?page=hospa-dashboard'),
        'hospa-admin-plugins' => array('title' => __('Plugins', 'hospa'), 'url' => 'admin.php?page=hospa-admin-plugins'),
        'hospa-activation'    => array('title' => __('Activation', 'hospa'), 'url' => 'admin.php?page=hospa-activation'),
        'hospa-demo-import'   => array('title' => __('Demo Import', 'hospa'), 'url' => 'admin.php?page=hospa-demo-import'),
        'options'            => array('title' => __('Theme Options', 'hospa'), 'url' => 'admin.php?page=hospa_opt'),
        'hospa-support'      => array('title' => __('Support + Others', 'hospa'), 'url' => 'admin.php?page=hospa-support'),
        'hospa-more-themes'  => array('title' => __('+ More Themes', 'hospa'), 'url' => 'admin.php?page=hospa-more-themes'),
    );

    ?>
    <h2 class="nav-tab-wrapper wp-clearfix <?php echo esc_attr($theme_status); ?>">
        <?php
        foreach ($tabs as $key => $tab) {
            $class = 'et-nav-' . $key . ' nav-tab' . ($active_tab === $key ? ' nav-tab-active' : '');
            $href = esc_url(self_admin_url($tab['url']));

            echo '<a class="' . esc_attr($class) . '" href="' . esc_url($href) . '">';
            echo esc_html($tab['title']);
            echo '</a>';
        }
        ?>
    </h2>
    <?php
}
