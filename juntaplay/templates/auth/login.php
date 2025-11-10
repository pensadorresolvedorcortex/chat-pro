<?php
/**
 * JuntaPlay authentication template (login + register).
 *
 * @var string[] $login_errors
 * @var string[] $register_errors
 * @var string   $redirect_to
 * @var string   $active_view
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$plugin_dir = dirname(__DIR__, 2);

$resolve_icon = static function (string $relative_path, string $fallback = '') use ($plugin_dir): string {
    $icon_file = trailingslashit($plugin_dir) . $relative_path;

    if (file_exists($icon_file)) {
        $icon_paths = [];

        if (defined('JP_URL') && JP_URL !== '') {
            $icon_paths[] = trailingslashit(JP_URL) . $relative_path;
        }

        if (function_exists('plugins_url')) {
            $main_file   = trailingslashit($plugin_dir) . 'juntaplay.php';
            $icon_paths[] = plugins_url($relative_path, file_exists($main_file) ? $main_file : __FILE__);
        }

        if (defined('WP_PLUGIN_DIR') && defined('WP_PLUGIN_URL')) {
            $normalize = static function (string $path): string {
                if (function_exists('wp_normalize_path')) {
                    return wp_normalize_path($path);
                }

                return str_replace('\\', '/', $path);
            };

            $normalized_plugin_dir  = $normalize($plugin_dir);
            $normalized_plugins_dir = $normalize(WP_PLUGIN_DIR);

            if ($normalized_plugin_dir !== '' && strpos($normalized_plugin_dir, $normalized_plugins_dir) === 0) {
                $relative_plugin_dir = trim(substr($normalized_plugin_dir, strlen($normalized_plugins_dir)), '/');
                $base_plugin_url     = trailingslashit(WP_PLUGIN_URL);

                if ($relative_plugin_dir !== '') {
                    $base_plugin_url .= trailingslashit($relative_plugin_dir);
                }

                $icon_paths[] = $base_plugin_url . $relative_path;
            }
        }

        foreach (array_filter(array_unique($icon_paths)) as $source) {
            if ($source !== '') {
                return $source;
            }
        }
    }

    return $fallback;
};

$facebook_icon = $resolve_icon('assets/images/facebook.png');
$google_icon   = $resolve_icon(
    'assets/images/google.png',
    'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxOCAxOCI+PHBhdGggZmlsbD0iIzQyODVGNCIgZD0iTTE3LjY0IDkuMjA0NWMwLS42Mzg3LS4wNTczLTEuMjUxOC0uMTYzNi0xLjgzNjRIOXYzLjQ3MjdoNC44NDQ1Yy0uMjA5IDEuMTI1LS44NDM2IDIuMDc4Mi0xLjc5NzMgMi43MTY0djIuMjU4MWgyLjkwOWMxLjcwMy0xLjU2OTggMi42ODM4LTMuODgyNyAyLjY4MzgtNi42MTA4eiIvPjxwYXRoIGZpbGw9IiMzNEE4NTMiIGQ9Ik05IDE4YzIuNDMgMCA0LjQ2Ni0uODA2IDUuOTU1LTIuMTg0bC0yLjkwOS0yLjI1OGMtLjgwNi41NC0xLjgzOC44NTktMy4wNDYuODU5LTIuMzQ0IDAtNC4zMjgtMS41ODQtNS4wMzYtMy43MWgtMy4wMXYyLjMzMUMxLjQ4MiAxNS45MTUgNC45NTEgMTggOSAxOHoiLz48cGF0aCBmaWxsPSIjRkJCQzA1IiBkPSJNMy45NjQgMTAuNzA3Yy0uMTgtLjU0LS4yODItMS4xMTYtLjI4Mi0xLjcwN3MuMTAyLTEuMTY3LjI4Mi0xLjcwN1Y0Ljk2MkguOTU0Qy4zNDcgNi4xNzQgMCA3LjU1NSAwIDlzLjM0NyAyLjgyNi45NTQgNC4wMzhMMy4wMS0yLjMzMXoiLz48cGF0aCBmaWxsPSIjRUE0MzM1IiBkPSJNOSAzLjU3OWMxLjMyMSAwIDIuNTA2LjQ1NCAzLjQzOCAxLjM0NmwyLjU3OS0yLjU3OUMxMy40NjYuODg5IDExLjQzIDAgOSAwIDQuOTUxIDAgMS40ODIgMi4wODUuOTU0IDQuOTYybDMuMDEgMi4zMzFDNC42NzIgNS4xNjMgNi42NTYgMy41NzkgOSAzLjU3OXoiLz48L3N2Zz4='
);

$facebook_icon = apply_filters('juntaplay/login/facebook_icon', $facebook_icon);
$google_icon   = apply_filters('juntaplay/login/google_icon', $google_icon);

$providers = apply_filters(
    'juntaplay/login/providers',
    [
        [
            'key'   => 'facebook',
            'label' => __('Entrar com Facebook', 'juntaplay'),
            'register_label' => __('Criar a conta com Facebook', 'juntaplay'),
            'href'  => '#',
            'icon'  => $facebook_icon,
        ],
        [
            'key'             => 'google',
            'label'           => __('Entrar com Google', 'juntaplay'),
            'register_label'  => __('Criar a conta com Google', 'juntaplay'),
            'href'            => '#',
            'icon'            => $google_icon,
            'popup'           => true,
        ],
    ]
);

$current_url   = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
$redirect_to   = $redirect_to ? esc_url_raw($redirect_to) : '';
$username      = isset($_POST['jp_login_username']) ? sanitize_text_field(wp_unslash($_POST['jp_login_username'])) : '';
$remember      = !empty($_POST['jp_login_remember']);
$register_name = isset($_POST['jp_register_name']) ? sanitize_text_field(wp_unslash($_POST['jp_register_name'])) : '';
$register_mail = isset($_POST['jp_register_email']) ? sanitize_email(wp_unslash($_POST['jp_register_email'])) : '';
$register_accept = !empty($_POST['jp_register_accept']);

$can_register  = (bool) get_option('users_can_register');
$social_hooks  = has_action('wordpress_social_login') || has_action('nextend_social_login_buttons');
$show_register = $can_register || has_action('juntaplay/login/register_alternate');

$render_social_block = static function (array $providers, bool $social_hooks, string $context, string $divider_text): void {
    if (empty($providers) && !$social_hooks) {
        return;
    }
    ?>
    <div class="juntaplay-auth__social">
        <?php if ($social_hooks) : ?>
            <div class="juntaplay-auth__social-integrations">
                <?php if (has_action('wordpress_social_login')) : ?>
                    <?php do_action('wordpress_social_login'); ?>
                <?php endif; ?>
                <?php if (has_action('nextend_social_login_buttons')) : ?>
                    <?php do_action('nextend_social_login_buttons', $context === 'register' ? 'register' : 'login'); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($providers)) : ?>
            <div class="juntaplay-auth__social-list">
                <?php foreach ($providers as $provider) :
                    $href    = isset($provider['href']) ? esc_url($provider['href']) : '#';
                    $key     = isset($provider['key']) ? sanitize_html_class((string) $provider['key']) : 'provider';
                    $icon    = isset($provider['icon']) ? (string) $provider['icon'] : '';
                    $popup   = !empty($provider['popup']);
                    $classes = 'juntaplay-auth__social-btn juntaplay-auth__social-btn--' . $key;
                    $disabled = !empty($provider['disabled']) || $href === '#';
                    $label_key = $context === 'register' && isset($provider['register_label']) ? 'register_label' : 'label';
                    $label   = isset($provider[$label_key]) ? esc_html($provider[$label_key]) : '';
                    $data_attrs = $key !== '' ? ' data-jp-auth-provider="' . esc_attr($key) . '"' : '';
                    $data_attrs .= ' data-jp-auth-context="' . esc_attr($context) . '"';
                    if ($popup) {
                        $data_attrs .= ' data-jp-auth-popup="1"';
                    }
                    $rel = $popup ? ' rel="noopener"' : '';
                    ?>
                    <a class="<?php echo esc_attr($classes); ?>" href="<?php echo $href; ?>"<?php echo $disabled ? ' role="button" aria-disabled="true"' : ''; ?><?php echo $rel; ?><?php echo $data_attrs; ?>>
                        <?php if ($icon !== '') : ?>
                            <span class="juntaplay-auth__social-icon" aria-hidden="true">
                                <img src="<?php echo esc_url($icon); ?>" alt="" loading="lazy">
                            </span>
                        <?php endif; ?>
                        <span class="juntaplay-auth__social-label"><?php echo $label; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="juntaplay-auth__divider" role="presentation">
        <span><?php echo esc_html($divider_text); ?></span>
    </div>
    <?php
};

if (!$show_register) {
    $active_view = 'login';
}

$active_view = $active_view === 'register' && $show_register ? 'register' : 'login';

$terms_id      = (int) get_option('juntaplay_page_regras');
$privacy_url   = function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : '';
$terms_url     = $terms_id ? get_permalink($terms_id) : $privacy_url;
$terms_url     = $terms_url ?: home_url('/regras');
?>
<div class="juntaplay-auth" data-active-view="<?php echo esc_attr($active_view); ?>">
    <div class="juntaplay-auth__container">
        <div class="juntaplay-auth__intro">
            <span class="juntaplay-auth__brand">JuntaPlay</span>
            <h1><?php esc_html_e('Entre, crie. Compartilhe e curta.', 'juntaplay'); ?></h1>
            <p><?php esc_html_e('Acesse sua conta para acompanhar campanhas, reservar cotas e gerenciar seus pedidos com facilidade.', 'juntaplay'); ?></p>
        </div>
        <div class="juntaplay-auth__card" data-has-register="<?php echo $show_register ? '1' : '0'; ?>">
            <div class="juntaplay-auth__header">
                <h2 class="juntaplay-auth__title">
                    <?php esc_html_e('Bem-vindo(a)!', 'juntaplay'); ?>
                </h2>
                <?php if ($show_register) : ?>
                    <div class="juntaplay-auth__switch" role="tablist">
                        <button type="button" class="juntaplay-auth__switch-btn<?php echo $active_view === 'login' ? ' is-active' : ''; ?>" data-target="login" role="tab" aria-selected="<?php echo $active_view === 'login' ? 'true' : 'false'; ?>">
                            <?php esc_html_e('Entrar', 'juntaplay'); ?>
                        </button>
                        <button type="button" class="juntaplay-auth__switch-btn<?php echo $active_view === 'register' ? ' is-active' : ''; ?>" data-target="register" role="tab" aria-selected="<?php echo $active_view === 'register' ? 'true' : 'false'; ?>" <?php disabled(!$can_register); ?>>
                            <?php echo $can_register ? esc_html__('Criar conta', 'juntaplay') : esc_html__('Solicitar acesso', 'juntaplay'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="juntaplay-auth__panes">
                <div class="juntaplay-auth__pane juntaplay-auth__pane--login<?php echo $active_view === 'login' ? ' is-active' : ''; ?>" data-pane="login" role="tabpanel" aria-hidden="<?php echo $active_view === 'login' ? 'false' : 'true'; ?>">
                    <?php
                    $render_social_block(
                        $providers,
                        $social_hooks,
                        'login',
                        __('ou acesse com seu e-mail', 'juntaplay')
                    );
                    ?>

                    <form class="juntaplay-auth__form" method="post" action="<?php echo esc_url($current_url); ?>">
                        <input type="hidden" name="jp_auth_view" value="login">
                        <?php if (!empty($login_errors)) : ?>
                            <div class="juntaplay-auth__alert" role="alert">
                                <ul>
                                    <?php foreach ($login_errors as $message) : ?>
                                        <li><?php echo esc_html($message); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="juntaplay-auth__field">
                            <label for="jp_login_username"><?php esc_html_e('E-mail ou usuário', 'juntaplay'); ?></label>
                            <input type="text" name="jp_login_username" id="jp_login_username" autocomplete="username" placeholder="nome@email.com" value="<?php echo esc_attr($username); ?>" required>
                        </div>

                        <div class="juntaplay-auth__field">
                            <label for="jp_login_password"><?php esc_html_e('Senha', 'juntaplay'); ?></label>
                            <input type="password" name="jp_login_password" id="jp_login_password" autocomplete="current-password" placeholder="••••••••" required>
                        </div>

                        <div class="juntaplay-auth__meta">
                            <label class="juntaplay-auth__remember">
                                <input type="checkbox" name="jp_login_remember" value="1" <?php checked($remember); ?>>
                                <span><?php esc_html_e('Lembrar-me', 'juntaplay'); ?></span>
                            </label>
                            <a class="juntaplay-auth__forgot" href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Esqueci minha senha', 'juntaplay'); ?></a>
                        </div>

                        <input type="hidden" name="jp_login_action" value="1">
                        <?php wp_nonce_field('juntaplay_login', 'jp_login_nonce'); ?>

                        <?php if ($redirect_to) : ?>
                            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
                        <?php endif; ?>

                        <button type="submit" class="juntaplay-button juntaplay-button--primary juntaplay-auth__submit"><?php esc_html_e('Entrar', 'juntaplay'); ?></button>
                    </form>
                </div>

                <?php if ($show_register) : ?>
                    <div class="juntaplay-auth__pane juntaplay-auth__pane--register<?php echo $active_view === 'register' ? ' is-active' : ''; ?>" data-pane="register" role="tabpanel" aria-hidden="<?php echo $active_view === 'register' ? 'false' : 'true'; ?>">
                        <?php
                        $render_social_block(
                            $providers,
                            $social_hooks,
                            'register',
                            __('ou complete seus dados para finalizar', 'juntaplay')
                        );
                        ?>

                        <form class="juntaplay-auth__form" method="post" action="<?php echo esc_url($current_url); ?>">
                            <input type="hidden" name="jp_auth_view" value="register">
                            <?php if (!$can_register) : ?>
                                <div class="juntaplay-auth__alert" role="alert">
                                    <p><?php esc_html_e('Estamos com novas contas fechadas no momento. Entre em contato com nossa equipe para solicitar acesso.', 'juntaplay'); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($register_errors)) : ?>
                                <div class="juntaplay-auth__alert" role="alert">
                                    <ul>
                                        <?php foreach ($register_errors as $message) : ?>
                                            <li><?php echo esc_html($message); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <div class="juntaplay-auth__field">
                                <label for="jp_register_name"><?php esc_html_e('Nome completo', 'juntaplay'); ?></label>
                                <input type="text" name="jp_register_name" id="jp_register_name" autocomplete="name" placeholder="Maria Silva" value="<?php echo esc_attr($register_name); ?>" <?php disabled(!$can_register); ?> required>
                            </div>

                            <div class="juntaplay-auth__field">
                                <label for="jp_register_email"><?php esc_html_e('E-mail', 'juntaplay'); ?></label>
                                <input type="email" name="jp_register_email" id="jp_register_email" autocomplete="email" placeholder="nome@email.com" value="<?php echo esc_attr($register_mail); ?>" <?php disabled(!$can_register); ?> required>
                            </div>

                            <div class="juntaplay-auth__field">
                                <label for="jp_register_password"><?php esc_html_e('Senha', 'juntaplay'); ?></label>
                                <input type="password" name="jp_register_password" id="jp_register_password" autocomplete="new-password" placeholder="••••••••" <?php disabled(!$can_register); ?> required>
                            </div>

                            <div class="juntaplay-auth__field">
                                <label for="jp_register_password_confirm"><?php esc_html_e('Confirmar senha', 'juntaplay'); ?></label>
                                <input type="password" name="jp_register_password_confirm" id="jp_register_password_confirm" autocomplete="new-password" placeholder="••••••••" <?php disabled(!$can_register); ?> required>
                            </div>

                            <div class="juntaplay-auth__meta juntaplay-auth__meta--terms">
                                <label class="juntaplay-auth__remember">
                                    <input type="checkbox" name="jp_register_accept" value="1" <?php checked($register_accept); ?> <?php disabled(!$can_register); ?>>
                                    <span>
                                        <?php
                                        $terms_template = wp_kses(
                                            /* translators: %s: terms link */
                                            __('Li e concordo com os <a href="%s" target="_blank" rel="noopener noreferrer">Termos de uso</a>.', 'juntaplay'),
                                            [
                                                'a' => [
                                                    'href'   => [],
                                                    'target' => [],
                                                    'rel'    => [],
                                                ],
                                            ]
                                        );

                                        echo sprintf($terms_template, esc_url($terms_url)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        ?>
                                    </span>
                                </label>
                            </div>

                            <input type="hidden" name="jp_register_action" value="1">
                            <?php wp_nonce_field('juntaplay_register', 'jp_register_nonce'); ?>

                            <?php if ($redirect_to) : ?>
                                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
                            <?php endif; ?>

                            <?php do_action('register_form'); ?>

                            <button type="submit" class="juntaplay-button juntaplay-button--primary juntaplay-auth__submit" <?php disabled(!$can_register); ?>>
                                <?php echo $can_register ? esc_html__('Criar conta', 'juntaplay') : esc_html__('Solicitar acesso', 'juntaplay'); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$show_register) : ?>
                <p class="juntaplay-auth__footer">
                    <?php esc_html_e('Ainda não possui conta?', 'juntaplay'); ?>
                    <a class="juntaplay-link" href="<?php echo esc_url(wp_login_url()); ?>?action=register"><?php esc_html_e('Solicite acesso', 'juntaplay'); ?></a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
