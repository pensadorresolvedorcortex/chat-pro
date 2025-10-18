<?php
/**
 * Plugin Name: Login Academia da Comunicação
 * Plugin URI:  https://example.com/
 * Description: Área do usuário com design em vidro, notificações e shortcodes personalizados para a Academia da Comunicação.
 * Version:     1.0.0
 * Author:      ChatGPT
 * Text Domain: cpc-login-academia
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CPC_Login_Academia_Plugin')) {
    class CPC_Login_Academia_Plugin
    {
        const META_KEY = '_cpc_orgprompt_notifications';
        private const DEFAULT_NOTIFICATION_LIMIT = 50;
        private const ALLOWED_NOTIFICATION_TYPES = ['info', 'success', 'warning', 'error'];

        private $assets_enqueued = false;
        private $registered_sections = [];

        public function __construct()
        {
            add_action('plugins_loaded', [$this, 'load_textdomain']);
            add_action('init', [$this, 'register_shortcodes']);
            add_action('rest_api_init', [$this, 'register_rest_routes']);
            add_action('cpc_orgprompt_add_notification', [$this, 'handle_external_notification'], 10, 4);
        }

        public function load_textdomain(): void
        {
            load_plugin_textdomain('cpc-login-academia', false, dirname(plugin_basename(__FILE__)) . '/languages');
        }

        public function register_shortcodes(): void
        {
            $sections = $this->get_default_sections();
            $normalized = [];
            foreach ($sections as $section) {
                $slug = sanitize_key($section['slug']);
                $shortcode = !empty($section['shortcode']) ? sanitize_key($section['shortcode']) : 'cpc_' . $slug;
                $normalized_section = array_merge($section, [
                    'slug'      => $slug,
                    'shortcode' => $shortcode,
                ]);
                $this->registered_sections[$slug] = $normalized_section;
                $normalized[] = $normalized_section;
            }
            $sections = $normalized;

            add_shortcode('cpc_user_area', function ($atts, $content = '') use ($sections) {
                return $this->render_user_area($sections);
            });

            add_shortcode('cpc_home_area', function ($atts, $content = '') use ($sections) {
                $atts = is_array($atts) ? $atts : [];
                return $this->render_user_home($sections, $atts);
            });

            foreach ($sections as $section) {
                add_shortcode('cpc_' . $section['slug'], function ($atts = [], $content = '') use ($section) {
                    return $this->render_section($section['slug']);
                });
                if (!empty($section['shortcode']) && 'cpc_' . $section['slug'] !== $section['shortcode']) {
                    add_shortcode($section['shortcode'], function ($atts = [], $content = '') use ($section) {
                        return $this->render_section($section['slug']);
                    });
                }
            }
        }

        public function enqueue_assets(): void
        {
            if ($this->assets_enqueued) {
                return;
            }

            $handle = 'cpc-login-academia';
            wp_register_style(
                $handle,
                false,
                [],
                '1.0.0'
            );

            wp_add_inline_style($handle, $this->get_inline_css());
            wp_enqueue_style($handle);

            wp_register_script(
                $handle,
                '',
                [],
                '1.0.0',
                true
            );

            $localized = [
                'restUrl' => esc_url_raw(rest_url('cpc/v1/notifications')),
                'nonce'   => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
                'i18n'    => [
                    'noNotifications' => __('Nenhuma notificação ainda.', 'cpc-login-academia'),
                ],
            ];

            wp_localize_script($handle, 'CPCLoginAcademia', $localized);
            wp_enqueue_script($handle);

            add_action('wp_footer', [$this, 'print_inline_script']);

            $this->assets_enqueued = true;
        }

        private function get_inline_css(): string
        {
            $primary        = '#6a5ae0';
            $secondary      = '#bf83ff';
            $ink            = '#1f2733';
            $muted          = '#44566c';
            $home_primary   = '#ff3cac';
            $home_secondary = '#562b7c';

            return <<<CSS
/* Login Academia da Comunicação glass theme */
.cpc-user-area{font-family:"Inter",sans-serif;max-width:1100px;margin:0 auto;padding:56px 32px;color:{$ink};position:relative}
.cpc-user-area::before{content:"";position:absolute;inset:0;border-radius:42px;background:linear-gradient(135deg, {$primary} 0%, {$secondary} 100%);opacity:.9;z-index:0;box-shadow:0 30px 70px rgba(106,90,224,.35)}
.cpc-user-area::after{content:"";position:absolute;inset:14px;border-radius:34px;background:rgba(255,255,255,.28);backdrop-filter:blur(22px);border:1px solid rgba(255,255,255,.35);z-index:0}
.cpc-user-area::before,.cpc-user-area::after{pointer-events:none}
.cpc-user-area>*{position:relative;z-index:1}
.cpc-dashboard{display:grid;grid-template-columns:300px 1fr;gap:32px;align-items:start}
@media(max-width:960px){.cpc-dashboard{grid-template-columns:1fr}}
.cpc-card{background:rgba(255,255,255,.78);border-radius:28px;border:1px solid rgba(255,255,255,.55);box-shadow:0 24px 60px rgba(15,23,42,.15);backdrop-filter:blur(20px)}
.cpc-sidebar{padding:32px 26px 36px;display:flex;flex-direction:column;gap:26px}
.cpc-header{display:flex;align-items:center;gap:16px}
.cpc-avatar{width:68px;height:68px;border-radius:22px;overflow:hidden;border:3px solid rgba(255,255,255,.85);box-shadow:0 12px 28px rgba(15,23,42,.18)}
.cpc-avatar img{width:100%;height:100%;object-fit:cover}
.cpc-user-name{margin:0;font-size:1.3rem;font-weight:700;color:{$ink}}
.cpc-user-role{margin-top:4px;font-size:.85rem;font-weight:500;color:rgba(31,39,51,.65)}
.cpc-menu{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px}
.cpc-menu button{width:100%;padding:12px 16px;border-radius:16px;border:1px solid rgba(106,90,224,.18);background:rgba(255,255,255,.35);color:{$ink};font-weight:600;text-align:left;cursor:pointer;transition:background .2s ease,transform .2s ease,box-shadow .2s ease}
.cpc-menu button:hover{background:rgba(255,255,255,.55);transform:translateY(-1px);box-shadow:0 12px 24px rgba(15,23,42,.12)}
.cpc-menu button.is-active{background:linear-gradient(135deg, {$primary} 0%, {$secondary} 100%);color:#fff;box-shadow:0 18px 36px rgba(106,90,224,.35)}
.cpc-content{padding:40px 40px 48px;display:flex;flex-direction:column;gap:32px}
@media(max-width:720px){.cpc-content{padding:32px 28px 36px}}
.cpc-content-header{display:flex;align-items:flex-start;justify-content:space-between;gap:24px;flex-wrap:wrap}
.cpc-intro{max-width:520px}
.cpc-intro-eyebrow{display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;background:rgba(255,255,255,.55);color:rgba(31,39,51,.65);font-size:.75rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase}
.cpc-intro-title{margin:16px 0 0;font-size:2rem;font-weight:700}
.cpc-intro-subtitle{margin:12px 0 0;font-size:1rem;line-height:1.6;color:{$muted}}
.cpc-topbar{display:flex;align-items:center;gap:18px;flex-wrap:wrap}
.cpc-dropdown{position:relative}
.cpc-dropdown>button{display:flex;align-items:center;gap:10px;padding:10px 18px;border-radius:14px;border:1px solid rgba(106,90,224,.25);background:rgba(255,255,255,.65);color:{$ink};font-weight:600;cursor:pointer;transition:background .2s ease,box-shadow .2s ease}
.cpc-dropdown>button:hover{background:#fff;box-shadow:0 16px 28px rgba(15,23,42,.14)}
.cpc-dropdown>button:focus-visible{outline:2px solid {$primary};outline-offset:2px}
.cpc-dropdown-menu{position:absolute;right:0;top:52px;background:#fff;border-radius:18px;padding:10px 0;min-width:220px;border:1px solid rgba(106,90,224,.18);box-shadow:0 26px 60px rgba(15,23,42,.2);display:none;z-index:20}
.cpc-dropdown-menu.is-active{display:block}
.cpc-dropdown-menu a{display:block;padding:12px 20px;font-weight:600;color:{$ink};text-decoration:none;transition:background .2s ease}
.cpc-dropdown-menu a:hover{background:rgba(106,90,224,.1)}
.cpc-dropdown-menu a.is-active{background:linear-gradient(135deg, rgba(106,90,224,.15), rgba(191,131,255,.2))}
.cpc-bell{position:relative;width:48px;height:48px;border-radius:50%;border:1px solid rgba(106,90,224,.25);background:rgba(255,255,255,.7);display:flex;align-items:center;justify-content:center;color:{$primary};cursor:pointer;transition:transform .2s ease,box-shadow .2s ease}
.cpc-bell:hover{transform:translateY(-2px);box-shadow:0 20px 38px rgba(15,23,42,.2)}
.cpc-bell .cpc-badge{position:absolute;top:6px;right:6px;background:#ef4444;color:#fff;font-size:11px;padding:2px 7px;border-radius:999px;font-weight:700;display:none}
.cpc-bell-panel{position:absolute;top:56px;right:0;width:340px;max-height:440px;overflow:auto;background:#fff;border-radius:20px;padding:20px;border:1px solid rgba(106,90,224,.18);box-shadow:0 32px 70px rgba(15,23,42,.25);display:none;z-index:30;color:{$ink}}
.cpc-bell-panel.is-active{display:block}
@media(max-width:640px){.cpc-bell-panel{width:calc(100vw - 64px);right:auto;left:0}}
.cpc-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}
.cpc-panel-head h4{margin:0;font-size:1rem;font-weight:700;color:{$ink}}
.cpc-mark-all{padding:8px 16px;border-radius:14px;border:1px solid rgba(106,90,224,.35);background:rgba(106,90,224,.1);color:{$primary};font-size:.8rem;font-weight:700;cursor:pointer;transition:opacity .2s ease,transform .2s ease}
.cpc-mark-all:hover{transform:translateY(-1px)}
.cpc-mark-all:disabled,.cpc-mark-all.is-disabled{opacity:.45;cursor:not-allowed;transform:none}
.cpc-notifications{display:flex;flex-direction:column;gap:16px}
.cpc-notification{border-radius:16px;padding:16px 18px;background:rgba(245,247,255,.85);border:1px solid rgba(106,90,224,.18);box-shadow:0 14px 28px rgba(15,23,42,.1)}
.cpc-notification .cpc-dot{display:inline-block;margin-right:8px;color:{$primary};font-weight:700}
.cpc-notification h4{margin:0;font-size:1rem;font-weight:700}
.cpc-notification-meta{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;font-size:.82rem;color:rgba(31,39,51,.6)}
.cpc-notification-meta time{font-size:.75rem;color:rgba(31,39,51,.5)}
.cpc-notification .cpc-message{margin:8px 0 0;font-size:.95rem;line-height:1.6;color:{$muted}}
.cpc-notification.is-success{border-color:rgba(34,197,94,.35)}
.cpc-notification.is-warning{border-color:rgba(234,179,8,.35)}
.cpc-notification.is-error{border-color:rgba(239,68,68,.35)}
.cpc-empty{text-align:center;padding:32px 20px;color:rgba(31,39,51,.6);font-weight:500}
.cpc-section{display:none;animation:cpcFadeIn .32s ease forwards}
.cpc-section.is-active{display:block}
.cpc-section-inner{background:rgba(255,255,255,.6);border-radius:24px;padding:28px;border:1px dashed rgba(106,90,224,.25);box-shadow:0 18px 40px rgba(15,23,42,.15)}
.cpc-section-inner h2{margin:0;font-size:1.5rem;color:{$ink}}
.cpc-section-inner p{margin:12px 0 0;font-size:1rem;line-height:1.6;color:{$muted}}
@media(max-width:900px){.cpc-content-header{flex-direction:column;align-items:flex-start}}
@keyframes cpcFadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.cpc-home-area{font-family:"Inter",sans-serif;max-width:1100px;margin:0 auto;padding:72px 28px;color:#fff;position:relative}
.cpc-home-wrap{position:relative;border-radius:40px;background:linear-gradient(140deg, {$home_primary} 0%, {$home_secondary} 100%);padding:20px;border:1px solid rgba(255,255,255,.32);box-shadow:0 40px 90px rgba(255,60,172,.45);overflow:hidden}
.cpc-home-wrap::before{content:"";position:absolute;inset:0;background:radial-gradient(circle at top right, rgba(255,255,255,.4), transparent 60%),radial-gradient(circle at bottom left, rgba(255,130,210,.55), transparent 65%);pointer-events:none}
.cpc-home-panel{position:relative;z-index:1;border-radius:32px;padding:48px;background:linear-gradient(150deg, rgba(255,255,255,.24) 0%, rgba(255,255,255,.08) 100%);border:1px solid rgba(255,255,255,.55);backdrop-filter:blur(32px);display:flex;flex-direction:column;gap:40px;box-shadow:0 30px 70px rgba(42,16,61,.32)}
.cpc-home-header{display:flex;align-items:center;justify-content:space-between;gap:32px;flex-wrap:wrap}
.cpc-home-greeting{display:flex;flex-direction:column;gap:8px}
.cpc-home-welcome{font-size:.85rem;font-weight:700;letter-spacing:.24em;text-transform:uppercase;color:rgba(255,255,255,.82)}
.cpc-home-name{margin:0;font-size:2.4rem;font-weight:700;line-height:1.1;color:#fff}
.cpc-home-subline{margin:0;font-size:1rem;color:rgba(255,255,255,.75);max-width:520px}
.cpc-home-actions{display:flex;align-items:center;gap:18px;flex-wrap:wrap;position:relative}
.cpc-home-actions .cpc-bell{width:52px;height:52px;background:rgba(255,255,255,.22);border:1px solid rgba(255,255,255,.75);color:#fff;box-shadow:0 18px 40px rgba(42,16,61,.42)}
.cpc-home-actions .cpc-bell:hover{transform:translateY(-2px);background:rgba(255,255,255,.32)}
.cpc-home-actions .cpc-bell-panel{background:linear-gradient(160deg, rgba(42,16,61,.95), rgba(86,43,124,.9));border:1px solid rgba(255,255,255,.28);color:#f8f9ff;backdrop-filter:blur(24px)}
.cpc-home-actions .cpc-bell-panel .cpc-notification{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.16);color:#f8f9ff}
.cpc-home-actions .cpc-bell-panel .cpc-notification .cpc-message{color:rgba(248,249,255,.75)}
.cpc-home-actions .cpc-bell-panel .cpc-notification h4{color:#fff}
.cpc-home-actions .cpc-bell-panel .cpc-notification-meta{color:rgba(248,249,255,.7)}
.cpc-home-actions .cpc-bell-panel time{color:rgba(248,249,255,.65)}
.cpc-home-actions .cpc-bell-panel .cpc-empty{color:rgba(248,249,255,.6)}
.cpc-home-user{position:relative}
.cpc-home-user-toggle{display:flex;align-items:center;gap:14px;padding:6px 18px 6px 6px;border-radius:999px;border:1px solid rgba(255,255,255,.75);background:rgba(255,255,255,.24);color:#fff;font-weight:600;cursor:pointer;transition:background .2s ease,box-shadow .2s ease}
.cpc-home-user-toggle:hover,.cpc-home-user-toggle:focus-visible{background:rgba(255,255,255,.34);box-shadow:0 22px 44px rgba(42,16,61,.35);outline:none}
.cpc-home-avatar{width:52px;height:52px;border-radius:20px;overflow:hidden;border:2px solid rgba(255,255,255,.85);box-shadow:0 12px 26px rgba(15,23,42,.25);flex-shrink:0}
.cpc-home-avatar img{width:100%;height:100%;object-fit:cover}
.cpc-home-caret{width:10px;height:10px;border-left:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(-45deg);margin-left:6px;transition:transform .2s ease}
.cpc-home-user-toggle[aria-expanded="true"] .cpc-home-caret{transform:rotate(135deg)}
.cpc-home-menu{position:absolute;top:calc(100% + 14px);right:0;min-width:220px;border-radius:22px;padding:12px 0;background:linear-gradient(180deg, rgba(42,16,61,.95), rgba(86,43,124,.92));border:1px solid rgba(255,255,255,.28);box-shadow:0 34px 70px rgba(42,16,61,.45);backdrop-filter:blur(24px);display:none;z-index:40}
.cpc-home-menu.is-active{display:block}
.cpc-home-menu ul{list-style:none;margin:0;padding:0}
.cpc-home-menu li{margin:0}
.cpc-home-menu a{display:block;padding:12px 20px;color:#f8f9ff;font-weight:600;text-decoration:none;transition:background .2s ease}
.cpc-home-menu a:hover{background:rgba(255,63,172,.2)}
.cpc-home-body{display:grid;grid-template-columns:1fr;gap:28px}
.cpc-home-highlight{display:flex;flex-direction:column;gap:20px;background:rgba(255,63,172,.22);border-radius:28px;padding:28px;border:1px solid rgba(255,255,255,.42);box-shadow:0 24px 58px rgba(42,16,61,.32)}
.cpc-home-highlight-note{margin:0;font-size:.95rem;color:rgba(255,255,255,.84)}
.cpc-home-latest{padding:20px 22px;border-radius:22px;background:rgba(42,16,61,.36);border:1px solid rgba(255,255,255,.4);display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}
.cpc-home-latest.has-unread{border-color:rgba(255,255,255,.55);box-shadow:0 20px 50px rgba(255,60,172,.28)}
.cpc-home-latest-info{display:flex;flex-direction:column;gap:8px;max-width:560px}
.cpc-home-latest-label{font-size:.8rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.7)}
.cpc-home-highlight-title{margin:0;font-size:1.15rem;font-weight:600;color:#fff}
.cpc-home-latest-time{font-size:.85rem;color:rgba(255,255,255,.7)}
.cpc-home-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px}
.cpc-home-card{position:relative;overflow:hidden;border-radius:26px;padding:24px;background:linear-gradient(160deg, rgba(255,255,255,.28) 0%, rgba(255,255,255,.08) 100%);border:1px solid rgba(255,255,255,.55);box-shadow:0 24px 60px rgba(42,16,61,.32);transition:transform .2s ease,box-shadow .2s ease;background-clip:padding-box}
.cpc-home-card::before{content:"";position:absolute;inset:-30% 40% 60% -30%;background:radial-gradient(circle, rgba(255,63,172,.45), transparent 60%);opacity:.9;pointer-events:none;transition:transform .3s ease,opacity .3s ease}
.cpc-home-card a{position:relative;z-index:1;color:#fff;text-decoration:none;display:flex;flex-direction:column;gap:14px;height:100%}
.cpc-home-card:hover{transform:translateY(-6px);box-shadow:0 34px 80px rgba(42,16,61,.45)}
.cpc-home-card:hover::before{transform:translateY(-10px);opacity:1}
.cpc-home-card-label{font-size:1.1rem;font-weight:700;margin:0 0 12px}
.cpc-home-card-action{margin-top:auto;font-weight:600;font-size:.9rem;color:rgba(255,255,255,.88)}
@media(max-width:880px){.cpc-home-panel{padding:36px}.cpc-home-name{font-size:2.1rem}}
@media(max-width:640px){.cpc-home-area{padding:56px 20px}.cpc-home-panel{padding:30px}.cpc-home-actions{width:100%;justify-content:flex-start}.cpc-home-user-toggle{padding:6px 16px 6px 6px}}

CSS;
        }


        public function print_inline_script(): void
        {
            if (!wp_script_is('cpc-login-academia', 'enqueued')) {
                return;
            }
            ?>
            <script>
            (function(){
                function normalizeSectionTarget(rawTarget){
                    if(!rawTarget){
                        return '';
                    }
                    var cleaned = String(rawTarget).trim().replace(/^#/, '');
                    if(cleaned.indexOf('cpc-section-') === 0){
                        return cleaned;
                    }
                    cleaned = cleaned.replace(/^cpc-section-/, '');
                    return cleaned ? 'cpc-section-' + cleaned : '';
                }

                function init(container){
                    if(!container) return;
                    const menuButtons = container.querySelectorAll('[data-cpc-target]');
                    const sections = container.querySelectorAll('.cpc-section');
                    const bell = container.querySelector('.cpc-bell');
                    const panel = container.querySelector('.cpc-bell-panel');
                    const dropdownToggle = container.querySelector('.cpc-dropdown > button');
                    const dropdownMenu = container.querySelector('.cpc-dropdown-menu');
                    const dropdownLinks = dropdownMenu ? Array.from(dropdownMenu.querySelectorAll('a[data-cpc-target]')) : [];
                    const dropdownLabelEl = dropdownToggle ? dropdownToggle.querySelector('[data-cpc-dropdown-label]') : null;

                    function updateDropdownLabel(text){
                        if(!dropdownToggle){
                            return;
                        }
                        const defaultLabel = dropdownToggle.getAttribute('data-default-label') || '';
                        const labelText = (text || '').trim() || defaultLabel;
                        if(dropdownLabelEl){
                            dropdownLabelEl.textContent = labelText;
                        }else{
                            dropdownToggle.textContent = labelText;
                        }
                    }

                    function closeDropdown(){
                        if(dropdownMenu){
                            dropdownMenu.classList.remove('is-active');
                            dropdownMenu.hidden = true;
                        }
                        if(dropdownToggle){
                            dropdownToggle.setAttribute('aria-expanded', 'false');
                        }
                    }

                    function openDropdown(){
                        if(dropdownMenu){
                            dropdownMenu.classList.add('is-active');
                            dropdownMenu.hidden = false;
                        }
                        if(dropdownToggle){
                            dropdownToggle.setAttribute('aria-expanded', 'true');
                        }
                    }

                    function findControlForTarget(target){
                        if(!target){
                            return null;
                        }
                        return container.querySelector('[data-cpc-target="' + target + '"]');
                    }

                    function maybeLoadExternal(target){
                        if(typeof window.CPC_ORGPROMPT_LOAD_SECTION !== 'function'){
                            return;
                        }
                        var clean = target.replace(/^cpc-section-/, '');
                        var targetSection = container.querySelector('#' + target);
                        window.CPC_ORGPROMPT_LOAD_SECTION(clean, function(html){
                            if(targetSection){
                                targetSection.innerHTML = html;
                            }
                        });
                    }

                    function switchToTarget(rawTarget, options){
                        var target = normalizeSectionTarget(rawTarget);
                        if(!target){
                            return false;
                        }
                        var section = container.querySelector('#' + target);
                        if(!section){
                            return false;
                        }
                        var label = options && typeof options.label === 'string' ? options.label : '';
                        var control = findControlForTarget(target);
                        if(!label && control){
                            label = control.getAttribute('data-cpc-label') || control.textContent || '';
                        }
                        menuButtons.forEach(function(btn){
                            btn.classList.toggle('is-active', btn.getAttribute('data-cpc-target') === target);
                        });
                        sections.forEach(function(sectionEl){
                            sectionEl.classList.toggle('is-active', sectionEl.id === target);
                        });
                        if(dropdownMenu){
                            dropdownLinks.forEach(function(link){
                                link.classList.toggle('is-active', link.getAttribute('data-cpc-target') === target);
                            });
                        }
                        updateDropdownLabel(label);
                        closeDropdown();
                        maybeLoadExternal(target);
                        if(options && options.focus && control && typeof control.focus === 'function'){
                            try {
                                control.focus({ preventScroll: true });
                            } catch (err) {
                                control.focus();
                            }
                        }
                        return true;
                    }

                    const interactiveElements = Array.from(menuButtons);
                    dropdownLinks.forEach(function(link){ interactiveElements.push(link); });

                    interactiveElements.forEach(function(element){
                        element.addEventListener('click', function(event){
                            if(event){ event.preventDefault(); }
                            var target = element.getAttribute('data-cpc-target');
                            if(!target && element.hasAttribute('href')){
                                var href = element.getAttribute('href');
                                if(href && href.indexOf('#') !== -1){
                                    target = href.slice(href.indexOf('#'));
                                }
                            }
                            if(!target){
                                return;
                            }
                            var label = element.getAttribute('data-cpc-label') || element.textContent || '';
                            switchToTarget(target, { label: label });
                        });
                    });

                    function openSectionFromHash(hash){
                        if(!hash){
                            return false;
                        }
                        return switchToTarget(hash, {});
                    }

                    if(window.location && window.location.hash){
                        openSectionFromHash(window.location.hash);
                    }

                    window.addEventListener('hashchange', function(){
                        openSectionFromHash(window.location.hash);
                    });

                    if (dropdownToggle && dropdownMenu) {
                        dropdownMenu.hidden = true;
                        dropdownToggle.setAttribute('aria-expanded', 'false');
                        dropdownToggle.addEventListener('click', function(event){
                            if(event){ event.preventDefault(); }
                            const isOpen = dropdownMenu.classList.contains('is-active');
                            if(isOpen){
                                closeDropdown();
                            }else{
                                openDropdown();
                            }
                        });
                        document.addEventListener('click', function(ev){
                            if(!ev) return;
                            const target = ev.target;
                            if(!target || (!dropdownMenu.contains(target) && !dropdownToggle.contains(target))){
                                closeDropdown();
                            }
                        });
                        const activeBtn = container.querySelector('.cpc-menu button.is-active');
                        if(activeBtn){
                            const label = activeBtn.getAttribute('data-cpc-label') || activeBtn.textContent || '';
                            updateDropdownLabel(label);
                        }else{
                            updateDropdownLabel('');
                        }
                        closeDropdown();
                    }
                    bindBell(container, bell, panel);
                }

                function bindBell(container, bell, panel){
                    if(!bell) return;
                    if(panel){
                        updateMarkAllButtonState(panel);
                        bell.addEventListener('click', function(){
                            panel.classList.toggle('is-active');
                            if(panel.classList.contains('is-active')){
                                fetchNotifications(panel, bell);
                            }
                        });

                        panel.addEventListener('click', function(ev){
                            if(!ev || typeof ev.target === 'undefined'){
                                return;
                            }
                            var target = ev.target.nodeType === 1 ? ev.target : ev.target.parentElement;
                            if(!target){
                                return;
                            }
                            while(target && target !== panel){
                                if(elementMatches(target, '[data-cpc-action="mark-all"]')){
                                    break;
                                }
                                target = target.parentElement;
                            }
                            if(!elementMatches(target, '[data-cpc-action="mark-all"]')){
                                return;
                            }
                            var markAllButton = target;
                            ev.preventDefault();
                            var unreadIds = getUnreadIds(panel);
                            if(!unreadIds.length){
                                updateMarkAllButtonState(panel);
                                return;
                            }
                            markAllButton.disabled = true;
                            markNotificationsRead(unreadIds, panel, bell);
                        });
                    }
                    if (!bell.dataset.prefetched) {
                        bell.dataset.prefetched = 'true';
                        fetchNotifications(panel, bell, { markRead: false, renderPanel: false });
                    }
                }

                function initHome(container){
                    if(!container) return;
                    const bell = container.querySelector('.cpc-bell');
                    const panel = container.querySelector('.cpc-bell-panel');
                    const dropdownToggle = container.querySelector('[data-cpc-home-toggle]');
                    const dropdownMenu = container.querySelector('[data-cpc-home-menu]');
                    const interactiveTargets = Array.from(container.querySelectorAll('[data-cpc-target]'));

                    function closeMenu(){
                        if(dropdownMenu){
                            dropdownMenu.classList.remove('is-active');
                            dropdownMenu.hidden = true;
                        }
                        if(dropdownToggle){
                            dropdownToggle.setAttribute('aria-expanded', 'false');
                        }
                    }

                    function openMenu(){
                        if(dropdownMenu){
                            dropdownMenu.classList.add('is-active');
                            dropdownMenu.hidden = false;
                        }
                        if(dropdownToggle){
                            dropdownToggle.setAttribute('aria-expanded', 'true');
                        }
                    }

                    if(dropdownToggle && dropdownMenu){
                        dropdownMenu.hidden = true;
                        dropdownToggle.setAttribute('aria-expanded', 'false');
                        dropdownToggle.addEventListener('click', function(ev){
                            if(ev){ ev.preventDefault(); }
                            const isOpen = dropdownMenu.classList.contains('is-active');
                            if(isOpen){
                                closeMenu();
                            }else{
                                openMenu();
                            }
                        });
                        document.addEventListener('click', function(ev){
                            if(!ev) return;
                            const target = ev.target;
                            if(!target){
                                return;
                            }
                            if(dropdownMenu.contains(target) || dropdownToggle.contains(target)){
                                return;
                            }
                            closeMenu();
                        });
                        Array.from(dropdownMenu.querySelectorAll('a')).forEach(function(link){
                            link.addEventListener('click', function(){
                                closeMenu();
                            });
                        });
                    }

                    bindBell(container, bell, panel);

                    interactiveTargets.forEach(function(element){
                        element.addEventListener('click', function(event){
                            var href = element.getAttribute('href') || '';
                            var targetAttr = element.getAttribute('data-cpc-target') || '';
                            var target = targetAttr;
                            if(!target && href && href.indexOf('#') !== -1){
                                target = href.slice(href.indexOf('#'));
                            }
                            if(!target){
                                return;
                            }
                            var normalized = normalizeSectionTarget(target);
                            if(!normalized){
                                return;
                            }
                            if(href && href.indexOf('#') > 0){
                                closeMenu();
                                return;
                            }
                            event.preventDefault();
                            closeMenu();
                            var hash = '#' + normalized;
                            var userArea = document.querySelector('.cpc-user-area');
                            if(userArea){
                                var control = userArea.querySelector('[data-cpc-target="' + normalized + '"]');
                                if(control && typeof control.click === 'function'){
                                    control.click();
                                }
                            }
                            if(window.location){
                                if(window.location.hash !== hash){
                                    window.location.hash = hash;
                                }else if(typeof window.HashChangeEvent === 'function'){
                                    try {
                                        window.dispatchEvent(new HashChangeEvent('hashchange', { oldURL: window.location.href, newURL: window.location.href }));
                                    } catch (err) {}
                                }
                            }
                        });
                    });
                }

                function fetchNotifications(panelEl, bellEl, options){
                    if(!window.CPCLoginAcademia || !window.CPCLoginAcademia.restUrl) return;
                    const opts = Object.assign({ markRead: true, renderPanel: true }, options || {});
                    fetch(window.CPCLoginAcademia.restUrl, {
                        credentials: 'same-origin'
                    }).then(res => {
                        if(!res.ok) throw new Error('Request failed');
                        return res.json();
                    }).then(data => {
                        const unreadItems = Array.isArray(data) ? data.filter(item => !item.read) : [];
                        if (opts.renderPanel !== false && panelEl) {
                            renderNotifications(panelEl, data);
                        }
                        updateBadge(bellEl, unreadItems.length);
                        if (opts.markRead !== false && unreadItems.length) {
                            markNotificationsRead(unreadItems.map(item => item.id), panelEl, bellEl);
                        }
                    }).catch(() => {
                        if(panelEl && opts.renderPanel !== false){
                            var empty = (window.CPCLoginAcademia && window.CPCLoginAcademia.i18n ? window.CPCLoginAcademia.i18n.noNotifications : 'Nenhuma notificação.');
                            const listEl = panelEl.querySelector('[data-cpc-list]') || panelEl;
                            listEl.innerHTML = '<div class="cpc-empty">'+empty+'</div>';
                            panelEl.dataset.unread = '';
                            panelEl.classList.remove('has-unread');
                            updateMarkAllButtonState(panelEl);
                        }
                    });
                }

                function markNotificationsRead(ids, panelEl, bellEl){
                    if(!Array.isArray(ids) || !ids.length) return;
                    if(!window.CPCLoginAcademia || !window.CPCLoginAcademia.restUrl || !window.CPCLoginAcademia.nonce) return;
                    fetch(window.CPCLoginAcademia.restUrl, {
                        method: 'PATCH',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': window.CPCLoginAcademia.nonce
                        },
                        body: JSON.stringify({ ids: ids })
                    }).then(res => {
                        if(!res.ok) throw new Error('Request failed');
                        return res.json();
                    }).then(data => {
                        if (panelEl) {
                            renderNotifications(panelEl, data);
                        }
                        const unreadItems = Array.isArray(data) ? data.filter(item => !item.read).length : 0;
                        updateBadge(bellEl, unreadItems);
                    }).catch(() => {
                        if(panelEl){
                            updateMarkAllButtonState(panelEl);
                        }
                    });
                }

                function renderNotifications(panelEl, notifications){
                    if(!panelEl) return;
                    const listEl = panelEl.querySelector('[data-cpc-list]') || panelEl;
                    if(!Array.isArray(notifications) || !notifications.length){
                        var empty = (window.CPCLoginAcademia && window.CPCLoginAcademia.i18n ? window.CPCLoginAcademia.i18n.noNotifications : 'Nenhuma notificação ainda.');
                        listEl.innerHTML = '<div class="cpc-empty">'+empty+'</div>';
                        panelEl.dataset.unread = '';
                        panelEl.classList.remove('has-unread');
                        updateMarkAllButtonState(panelEl);
                        return;
                    }

                    const unreadIds = [];
                    const list = notifications.map(item => {
                        const type = item.type ? ' is-'+item.type : '';
                        const href = item.url ? String(item.url) : '';
                        const read = !!item.read;
                        if(!read && item.id){
                            unreadIds.push(item.id);
                        }
                        const title = escapeHTML(item.title || '');
                        const message = item.message || '';
                        const timeLabel = item.created_human ? escapeHTML(item.created_human) : '';
                        const timeAttr = item.created ? String(item.created) : '';
                        const safeHref = href ? href.replace(/"/g, '&quot;') : '';
                        const safeTimeAttr = timeAttr ? timeAttr.replace(/"/g, '&quot;') : '';
                        const linkOpen = href ? '<a class="cpc-notification-link" href="'+safeHref+'" target="_blank" rel="noreferrer noopener">' : '';
                        const linkClose = href ? '</a>' : '';
                        const readBadge = read ? '' : '<span class="cpc-dot" aria-hidden="true">•</span>';
                        const timeHtml = timeLabel ? '<time datetime="'+safeTimeAttr+'">'+timeLabel+'</time>' : '';
                        const idAttr = item.id ? String(item.id).replace(/"/g, '&quot;') : '';
                        return '<article class="cpc-notification'+type+'" data-id="'+idAttr+'" data-read="'+(read ? 'true' : 'false')+'">'
                            + linkOpen
                            + '<div class="cpc-notification-meta">'
                                + '<div class="cpc-notification-title">'+readBadge+'<h4>'+title+'</h4></div>'
                                + timeHtml
                            + '</div>'
                            + '<div class="cpc-message">'+message+'</div>'
                            + linkClose
                        + '</article>';
                    }).join('');
                    listEl.innerHTML = list;
                    panelEl.dataset.unread = unreadIds.join(',');
                    panelEl.classList.toggle('has-unread', unreadIds.length > 0);
                    updateMarkAllButtonState(panelEl);
                }

                function updateBadge(bellEl, unread){
                    if(!bellEl) return;
                    let badge = bellEl.querySelector('.cpc-badge');
                    if(!badge){
                        badge = document.createElement('span');
                        badge.className = 'cpc-badge';
                        bellEl.appendChild(badge);
                    }
                    badge.textContent = unread;
                    badge.style.display = unread > 0 ? 'block' : 'none';
                }

                function getUnreadIds(panelEl){
                    if(!panelEl) return [];
                    const raw = panelEl.dataset.unread || '';
                    if(!raw){
                        return [];
                    }
                    return raw.split(',').map(function(id){ return id.trim(); }).filter(Boolean);
                }

                function updateMarkAllButtonState(panelEl){
                    if(!panelEl) return;
                    const button = panelEl.querySelector('[data-cpc-action="mark-all"]');
                    if(!button) return;
                    const unreadIds = getUnreadIds(panelEl);
                    button.disabled = unreadIds.length === 0;
                    if(unreadIds.length === 0){
                        button.classList.add('is-disabled');
                    }else{
                        button.classList.remove('is-disabled');
                    }
                }

                function escapeHTML(str){
                    if(typeof str !== 'string'){
                        return '';
                    }
                    return str.replace(/[&<>"']/g, function(char){
                        switch(char){
                            case '&': return '&amp;';
                            case '<': return '&lt;';
                            case '>': return '&gt;';
                            case '"': return '&quot;';
                            case "'": return '&#39;';
                            default: return char;
                        }
                    });
                }

                function elementMatches(el, selector){
                    if(!el || el.nodeType !== 1){
                        return false;
                    }
                    var proto = Element.prototype;
                    var func = proto.matches || proto.matchesSelector || proto.msMatchesSelector || proto.webkitMatchesSelector;
                    if(func){
                        return func.call(el, selector);
                    }
                    var nodes = el.parentNode ? el.parentNode.querySelectorAll(selector) : [];
                    var index = 0;
                    while(index < nodes.length){
                        if(nodes[index] === el){
                            return true;
                        }
                        index++;
                    }
                    return false;
                }

                document.addEventListener('DOMContentLoaded', function(){
                    document.querySelectorAll('.cpc-user-area').forEach(init);
                    document.querySelectorAll('.cpc-home-area').forEach(initHome);
                });
            })();
            </script>
            <?php
        }

        private function render_user_area(array $sections): string
        {
            if (!is_user_logged_in()) {
                return '<div class="cpc-user-area"><p>' . esc_html__('Você precisa estar logado para acessar esta área.', 'cpc-login-academia') . '</p></div>';
            }

            $this->enqueue_assets();

            $user = wp_get_current_user();
            $sections = $this->prepare_sections($sections, $user);

            $raw_name = '';
            if (!empty($user->first_name)) {
                $raw_name = $user->first_name;
            } elseif (!empty($user->display_name)) {
                $raw_name = $user->display_name;
            } else {
                $raw_name = $user->user_login;
            }

            $greeting_name = sanitize_text_field($raw_name);

            $initial_label = !empty($sections[0]['label']) ? $sections[0]['label'] : __('Opções', 'cpc-login-academia');

            ob_start();
            ?>
            <div class="cpc-user-area">
                <div class="cpc-dashboard">
                    <aside class="cpc-card cpc-sidebar">
                        <div class="cpc-header">
                            <div class="cpc-avatar">
                                <?php echo get_avatar($user->ID, 64); ?>
                            </div>
                            <div>
                                <div class="cpc-user-name"><?php echo esc_html($user->display_name ?: $user->user_login); ?></div>
                                <div class="cpc-user-role"><?php echo esc_html(implode(', ', $this->map_user_roles($user->roles))); ?></div>
                            </div>
                        </div>
                        <ul class="cpc-menu">
                            <?php foreach ($sections as $index => $section) :
                                $target = 'cpc-section-' . esc_attr($section['slug']);
                                ?>
                                <li>
                                    <button type="button" data-cpc-target="<?php echo esc_attr($target); ?>" data-cpc-label="<?php echo esc_attr($section['label']); ?>" aria-controls="<?php echo esc_attr($target); ?>" class="<?php echo $index === 0 ? 'is-active' : ''; ?>">
                                        <?php echo esc_html($section['label']); ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>
                    <div class="cpc-card cpc-content">
                        <div class="cpc-content-header">
                            <div class="cpc-intro">
                                <span class="cpc-intro-eyebrow"><?php esc_html_e('Bem-vindo de volta', 'cpc-login-academia'); ?></span>
                                <h2 class="cpc-intro-title">
                                    <?php printf(esc_html__('Olá, %s', 'cpc-login-academia'), esc_html($greeting_name)); ?>
                                </h2>
                                <p class="cpc-intro-subtitle"><?php esc_html_e('Escolha um módulo para continuar e acompanhe as novidades em tempo real.', 'cpc-login-academia'); ?></p>
                            </div>
                            <div class="cpc-topbar">
                                <div class="cpc-dropdown">
                                    <button type="button" aria-haspopup="true" aria-expanded="false" data-default-label="<?php echo esc_attr($initial_label); ?>">
                                        <span class="cpc-dropdown-current" data-cpc-dropdown-label><?php echo esc_html($initial_label); ?></span>
                                        <span class="cpc-dropdown-icon" aria-hidden="true">▾</span>
                                    </button>
                                    <div class="cpc-dropdown-menu" hidden>
                                        <?php foreach ($sections as $section) : ?>
                                            <a href="#<?php echo esc_attr($section['slug']); ?>" data-cpc-target="cpc-section-<?php echo esc_attr($section['slug']); ?>" data-cpc-label="<?php echo esc_attr($section['label']); ?>">
                                                <?php echo esc_html($section['label']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button type="button" class="cpc-bell" aria-label="<?php esc_attr_e('Notificações', 'cpc-login-academia'); ?>">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M12 3C8.68629 3 6 5.68629 6 9V11.5858L4.29289 13.2929C3.90143 13.6844 4.14821 14.3613 4.70711 14.3613H19.2929C19.8518 14.3613 20.0986 13.6844 19.7071 13.2929L18 11.5858V9C18 5.68629 15.3137 3 12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M13.7324 19C13.3866 19.5978 12.7373 20 12 20C11.2627 20 10.6134 19.5978 10.2676 19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <div class="cpc-bell-panel" aria-live="polite">
                                    <div class="cpc-panel-head">
                                        <h4><?php esc_html_e('Notificações', 'cpc-login-academia'); ?></h4>
                                        <button type="button" class="cpc-mark-all" data-cpc-action="mark-all" disabled>
                                            <?php esc_html_e('Marcar tudo como lido', 'cpc-login-academia'); ?>
                                        </button>
                                    </div>
                                    <div class="cpc-notifications" data-cpc-list>
                                        <div class="cpc-empty"><?php esc_html_e('Carregando...', 'cpc-login-academia'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php foreach ($sections as $index => $section) :
                            $slug = $section['slug'];
                            $content = $this->render_section_content($slug, $section);
                            ?>
                            <section id="cpc-section-<?php echo esc_attr($slug); ?>" class="cpc-section <?php echo $index === 0 ? 'is-active' : ''; ?>">
                                <?php echo $content; // already escaped ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        private function render_user_home(array $sections, array $atts = []): string
        {
            if (!is_user_logged_in()) {
                return '<div class="cpc-home-area"><p>' . esc_html__('Você precisa estar logado para acessar esta área.', 'cpc-login-academia') . '</p></div>';
            }

            $atts = shortcode_atts([
                'hero_tag'        => '',
                'hero_title'      => '',
                'hero_subtitle'   => '',
                'primary_label'   => '',
                'primary_url'     => '',
                'secondary_label' => '',
                'secondary_url'   => '',
                'user_area_url'   => '',
            ], $atts, 'cpc_home_area');

            $this->enqueue_assets();

            $user = wp_get_current_user();
            $sections = $this->prepare_sections($sections, $user);

            $raw_name = '';
            if (!empty($user->first_name)) {
                $raw_name = $user->first_name;
            } elseif (!empty($user->display_name)) {
                $raw_name = $user->display_name;
            } else {
                $raw_name = $user->user_login;
            }

            $greeting_name = sanitize_text_field($raw_name);

            $notifications = $this->get_user_notifications($user->ID);
            $unread_count = 0;
            $latest_notification = null;

            foreach ($notifications as $notification) {
                if (!$latest_notification) {
                    $latest_notification = $notification;
                }
                if (empty($notification['read'])) {
                    $unread_count++;
                }
            }

            $profile_url = esc_url_raw(apply_filters('cpc_orgprompt_profile_url', '#', $user));
            $user_area_url = '' !== $atts['user_area_url'] ? esc_url_raw($atts['user_area_url']) : '';

            $cards = [];
            foreach ($sections as $section) {
                $default_card_url = $user_area_url ? $this->build_section_anchor($user_area_url, $section['slug']) : '';
                $card_url = apply_filters('cpc_orgprompt_home_card_url', $default_card_url, $section, $user);
                if ('' === $card_url) {
                    $card_url = $default_card_url;
                }
                if ('' === $card_url) {
                    $card_url = $this->build_section_anchor('', $section['slug']);
                }
                $cards[] = [
                    'slug'        => $section['slug'],
                    'label'       => $section['label'],
                    'description' => $this->get_section_summary($section['slug']),
                    'url'         => $card_url,
                ];
            }

            $cards = apply_filters('cpc_orgprompt_home_cards', $cards, $user);

            $sanitized_cards = [];
            foreach ($cards as $card) {
                if (!is_array($card) || empty($card['slug'])) {
                    continue;
                }
                $slug = sanitize_key($card['slug']);
                if ('' === $slug) {
                    continue;
                }
                $label = isset($card['label']) ? sanitize_text_field($card['label']) : '';
                $description = isset($card['description']) ? sanitize_textarea_field($card['description']) : '';
                $default_card_url = $user_area_url ? $this->build_section_anchor($user_area_url, $slug) : '';
                $url = isset($card['url']) && '' !== $card['url'] ? esc_url_raw($card['url']) : $default_card_url;
                if ('' === $url) {
                    $url = $this->build_section_anchor('', $slug);
                }
                $sanitized_cards[] = [
                    'slug'        => $slug,
                    'label'       => $label,
                    'description' => $description,
                    'url'         => $url,
                ];
            }

            $cards = $sanitized_cards;

            $menu_items = [];
            foreach ($sections as $section) {
                if (empty($section['slug']) || empty($section['label'])) {
                    continue;
                }
                $slug = sanitize_key($section['slug']);
                if ('' === $slug) {
                    continue;
                }
                $target = 'cpc-section-' . $slug;
                $href = $user_area_url ? $this->build_section_anchor($user_area_url, $slug) : $this->build_section_anchor('', $slug);
                $menu_items[] = [
                    'label'  => $section['label'],
                    'target' => $target,
                    'href'   => $href,
                ];
            }

            if ('' !== $profile_url && '#' !== $profile_url) {
                $menu_items[] = [
                    'label'  => __('Ver perfil completo', 'cpc-login-academia'),
                    'target' => '',
                    'href'   => $profile_url,
                ];
            }

            $menu_items = apply_filters('cpc_orgprompt_home_menu_items', $menu_items, $user, $sections);

            $sanitized_menu_items = [];
            foreach ($menu_items as $item) {
                if (!is_array($item) || empty($item['label'])) {
                    continue;
                }
                $label = sanitize_text_field($item['label']);
                $target = '';
                if (!empty($item['target'])) {
                    $target_slug = sanitize_key(str_replace('cpc-section-', '', (string) $item['target']));
                    if ('' !== $target_slug) {
                        $target = 'cpc-section-' . $target_slug;
                    }
                }
                $href = '';
                if (!empty($item['href'])) {
                    $href = esc_url_raw($item['href']);
                }
                if ('' === $href && '' !== $target) {
                    $href = '#' . $target;
                }
                if ('' === $href) {
                    $href = '#';
                }
                $sanitized_menu_items[] = [
                    'label'  => $label,
                    'target' => $target,
                    'href'   => $href,
                ];
            }

            $menu_items = $sanitized_menu_items;

            $module_count = count($cards);

            $latest_title = '';
            $latest_time = '';
            if ($latest_notification) {
                $prepared_latest = $this->prepare_notification_for_response($latest_notification, $user->ID);
                $latest_title = sanitize_text_field($prepared_latest['title'] ?? '');
                $latest_time  = sanitize_text_field($prepared_latest['created_human'] ?? '');
            }

            if ($unread_count < 0) {
                $unread_count = 0;
            }

            $notice_label = $unread_count === 0
                ? __('Últimas atualizações', 'cpc-login-academia')
                : _n('Notificação recente', 'Notificações recentes', $unread_count, 'cpc-login-academia');

            $hero_tag = $this->format_home_text($atts['hero_tag'], $greeting_name);
            if ('' === $hero_tag) {
                $hero_tag = sanitize_text_field(__('Bem-vindo de volta', 'cpc-login-academia'));
            }

            $hero_title = $this->format_home_text($atts['hero_title'], $greeting_name);
            if ('' === $hero_title) {
                $hero_title = sanitize_text_field($greeting_name);
            }

            $hero_subtitle = $this->format_home_text($atts['hero_subtitle'], $greeting_name, true);
            if ('' === $hero_subtitle) {
                $hero_subtitle = sanitize_textarea_field(__('Continue a sua jornada com os módulos personalizados e acompanhe seu progresso em tempo real.', 'cpc-login-academia'));
            }

            $module_line = sanitize_text_field(sprintf(_n('Você tem %d módulo ativo disponível.', 'Você tem %d módulos ativos disponíveis.', $module_count, 'cpc-login-academia'), $module_count));

            $highlight_lines = array_filter([
                $hero_subtitle,
                $module_line,
            ], static function ($line) {
                return '' !== trim((string) $line);
            });

            $latest_message = $latest_title;
            if ('' === $latest_message) {
                $latest_message = __('Sem notificações novas no momento.', 'cpc-login-academia');
            }

            $latest_time_label = '';
            if ('' !== $latest_time) {
                $latest_time_label = $latest_time;
            } elseif ($unread_count > 0) {
                $latest_time_label = sprintf(_n('%d alerta pendente', '%d alertas pendentes', $unread_count, 'cpc-login-academia'), $unread_count);
            }

            $latest_message = sanitize_text_field($latest_message);
            if ('' !== $latest_time_label) {
                $latest_time_label = sanitize_text_field($latest_time_label);
            }

            $menu_id = function_exists('wp_unique_id') ? wp_unique_id('cpc-home-menu-') : 'cpc-home-menu-' . uniqid('', false);

            ob_start();
            ?>
            <div class="cpc-home-area" data-cpc-home="true">
                <div class="cpc-home-wrap">
                    <div class="cpc-home-panel">
                        <header class="cpc-home-header">
                            <div class="cpc-home-greeting">
                                <span class="cpc-home-welcome"><?php echo esc_html($hero_tag); ?></span>
                                <h1 class="cpc-home-name"><?php echo esc_html($hero_title); ?></h1>
                            </div>
                            <div class="cpc-home-actions">
                                <button type="button" class="cpc-bell" aria-label="<?php esc_attr_e('Notificações', 'cpc-login-academia'); ?>">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M12 3C8.68629 3 6 5.68629 6 9V11.5858L4.29289 13.2929C3.90143 13.6844 4.14821 14.3613 4.70711 14.3613H19.2929C19.8518 14.3613 20.0986 13.6844 19.7071 13.2929L18 11.5858V9C18 5.68629 15.3137 3 12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M13.7324 19C13.3866 19.5978 12.7373 20 12 20C11.2627 20 10.6134 19.5978 10.2676 19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <div class="cpc-bell-panel" aria-live="polite">
                                    <div class="cpc-panel-head">
                                        <h4><?php esc_html_e('Notificações', 'cpc-login-academia'); ?></h4>
                                        <button type="button" class="cpc-mark-all" data-cpc-action="mark-all" disabled>
                                            <?php esc_html_e('Marcar tudo como lido', 'cpc-login-academia'); ?>
                                        </button>
                                    </div>
                                    <div class="cpc-notifications" data-cpc-list>
                                        <div class="cpc-empty"><?php esc_html_e('Carregando...', 'cpc-login-academia'); ?></div>
                                    </div>
                                </div>
                                <div class="cpc-home-user">
                                    <button type="button" class="cpc-home-user-toggle" data-cpc-home-toggle aria-haspopup="true" aria-expanded="false" aria-controls="<?php echo esc_attr($menu_id); ?>" aria-label="<?php esc_attr_e('Abrir menu do usuário', 'cpc-login-academia'); ?>">
                                        <span class="cpc-home-avatar"><?php echo get_avatar($user->ID, 64); ?></span>
                                        <span class="cpc-home-caret" aria-hidden="true"></span>
                                    </button>
                                    <?php if (!empty($menu_items)) : ?>
                                        <nav id="<?php echo esc_attr($menu_id); ?>" class="cpc-home-menu" data-cpc-home-menu hidden>
                                            <ul>
                                                <?php foreach ($menu_items as $item) : ?>
                                                    <li>
                                                        <a href="<?php echo esc_url($item['href']); ?>"<?php echo $item['target'] ? ' data-cpc-target="' . esc_attr($item['target']) . '"' : ''; ?> data-cpc-label="<?php echo esc_attr($item['label']); ?>">
                                                            <?php echo esc_html($item['label']); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </header>
                        <div class="cpc-home-body">
                            <section class="cpc-home-highlight">
                                <?php foreach ($highlight_lines as $line) : ?>
                                    <p class="cpc-home-highlight-note"><?php echo esc_html($line); ?></p>
                                <?php endforeach; ?>
                                <div class="cpc-home-latest <?php echo $unread_count > 0 ? 'has-unread' : ''; ?>">
                                    <div class="cpc-home-latest-info">
                                        <span class="cpc-home-latest-label"><?php echo esc_html($notice_label); ?></span>
                                        <p class="cpc-home-highlight-title"><?php echo esc_html($latest_message); ?></p>
                                    </div>
                                    <?php if ('' !== $latest_time_label) : ?>
                                        <span class="cpc-home-latest-time"><?php echo esc_html($latest_time_label); ?></span>
                                    <?php endif; ?>
                                </div>
                            </section>
                            <?php if (!empty($cards)) : ?>
                                <section class="cpc-home-grid" aria-label="<?php esc_attr_e('Atalhos dos módulos', 'cpc-login-academia'); ?>">
                                    <?php foreach ($cards as $card) : ?>
                                        <article class="cpc-home-card">
                                            <a href="<?php echo esc_url($card['url']); ?>" data-cpc-target="cpc-section-<?php echo esc_attr($card['slug']); ?>" data-cpc-label="<?php echo esc_attr($card['label']); ?>">
                                                <h3 class="cpc-home-card-label"><?php echo esc_html($card['label']); ?></h3>
                                                <span class="cpc-home-card-action"><?php esc_html_e('Ir agora', 'cpc-login-academia'); ?> →</span>
                                            </a>
                                        </article>
                                    <?php endforeach; ?>
                                </section>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        private function render_section(string $slug): string
        {
            if (!is_user_logged_in()) {
                return '<div class="cpc-user-area"><p>' . esc_html__('Você precisa estar logado para acessar esta área.', 'cpc-login-academia') . '</p></div>';
            }

            $this->enqueue_assets();

            $section = $this->get_section_config($slug);

            return '<div class="cpc-user-area">' . $this->render_section_content($slug, $section) . '</div>';
        }

        private function prepare_sections(array $sections, \WP_User $user): array
        {
            $sections = apply_filters('cpc_orgprompt_menu_items', $sections, $user);
            $prepared = [];

            foreach ($sections as $section) {
                if (!is_array($section) || empty($section['slug'])) {
                    continue;
                }
                $slug = sanitize_key($section['slug']);
                $existing = $this->registered_sections[$slug] ?? [];
                $merged = array_merge($existing, $section);
                $merged['slug'] = $slug;
                if (!empty($merged['shortcode'])) {
                    $merged['shortcode'] = sanitize_key($merged['shortcode']);
                }
                $this->registered_sections[$slug] = $merged;
                $prepared[] = $merged;
            }

            return $prepared;
        }

        private function build_section_anchor(string $base_url, string $slug): string
        {
            $slug = sanitize_key($slug);
            if ('' === $slug) {
                return '#';
            }

            $anchor = 'cpc-section-' . $slug;
            $base_url = esc_url_raw($base_url);

            if ('' === $base_url) {
                return '#' . $anchor;
            }

            $hash_position = strpos($base_url, '#');
            if (false !== $hash_position) {
                $base_url = substr($base_url, 0, $hash_position);
            }

            return rtrim($base_url, '#') . '#' . $anchor;
        }

        private function format_home_text($text, string $name, bool $multiline = false): string
        {
            if (!is_string($text) || '' === $text) {
                return '';
            }

            $text = str_replace(['{{name}}', '%name%'], $name, $text);

            if (false !== strpos($text, '%s')) {
                $text = sprintf($text, $name);
            }

            return $multiline ? sanitize_textarea_field($text) : sanitize_text_field($text);
        }

        private function render_section_content(string $slug, ?array $section = null): string
        {
            $method = 'render_' . $slug . '_section';
            if (method_exists($this, $method)) {
                $content = call_user_func([$this, $method]);
            } else {
                if (null === $section) {
                    $section = $this->get_section_config($slug);
                }

                if ($section && !empty($section['shortcode'])) {
                    $content = do_shortcode('[' . $section['shortcode'] . ']');
                } else {
                    $content = '<div class="cpc-empty">' . esc_html__('Conteúdo em breve.', 'cpc-login-academia') . '</div>';
                }
            }

            $filter = 'cpc_orgprompt_render_' . $slug;
            return apply_filters($filter, $content);
        }

        private function render_pratica_section(): string
        {
            $html = '<div class="cpc-section-inner">'
                . '<h2>' . esc_html__('Prática', 'cpc-login-academia') . '</h2>'
                . '<p>' . esc_html__('Entre em ação com exercícios guiados, desafios semanais e feedback dos treinadores.', 'cpc-login-academia') . '</p>'
                . '<div class="cpc-empty">' . esc_html__('Personalize esta seção com seus próprios blocos.', 'cpc-login-academia') . '</div>'
                . '</div>';
            return $html;
        }

        private function render_meus_conteudos_section(): string
        {
            $html = '<div class="cpc-section-inner">'
                . '<h2>' . esc_html__('Meus conteúdos', 'cpc-login-academia') . '</h2>'
                . '<p>' . esc_html__('Acompanhe materiais salvos, downloads e aulas favoritas.', 'cpc-login-academia') . '</p>'
                . '</div>';
            return $html;
        }

        private function render_treinador_section(): string
        {
            $html = '<div class="cpc-section-inner">'
                . '<h2>' . esc_html__('Treinador', 'cpc-login-academia') . '</h2>'
                . '<p>' . esc_html__('Conecte-se com seu treinador, envie dúvidas e receba direcionamentos personalizados.', 'cpc-login-academia') . '</p>'
                . '</div>';
            return $html;
        }

        private function render_configuracoes_section(): string
        {
            $html = '<div class="cpc-section-inner">'
                . '<h2>' . esc_html__('Configurações', 'cpc-login-academia') . '</h2>'
                . '<p>' . esc_html__('Atualize suas preferências, dados pessoais e opções de privacidade.', 'cpc-login-academia') . '</p>'
                . '</div>';
            return $html;
        }

        private function render_planos_section(): string
        {
            $html = '<div class="cpc-section-inner">'
                . '<h2>' . esc_html__('Planos', 'cpc-login-academia') . '</h2>'
                . '<p>' . esc_html__('Gerencie sua assinatura, faça upgrades ou conheça novos planos disponíveis.', 'cpc-login-academia') . '</p>'
                . '</div>';
            return $html;
        }

        private function render_suporte_section(): string
        {
            $html = '<div class="cpc-section-inner">'
                . '<h2>' . esc_html__('Suporte', 'cpc-login-academia') . '</h2>'
                . '<p>' . esc_html__('Precisa de ajuda? Abra um chamado e acompanhe o status do atendimento.', 'cpc-login-academia') . '</p>'
                . '</div>';
            return $html;
        }

        private function get_section_summary(string $slug): string
        {
            switch ($slug) {
                case 'pratica':
                    return __('Exercícios guiados, desafios e feedback dos treinadores em um só lugar.', 'cpc-login-academia');
                case 'meus_conteudos':
                    return __('Acesse aulas salvas, downloads e materiais favoritos rapidamente.', 'cpc-login-academia');
                case 'treinador':
                    return __('Converse com seu treinador, envie dúvidas e receba orientações.', 'cpc-login-academia');
                case 'configuracoes':
                    return __('Atualize preferências, dados pessoais e privacidade com poucos cliques.', 'cpc-login-academia');
                case 'planos':
                    return __('Compare planos, faça upgrades e acompanhe o status da assinatura.', 'cpc-login-academia');
                case 'suporte':
                    return __('Abra chamados, acompanhe respostas e resolva pendências rapidamente.', 'cpc-login-academia');
                default:
                    return __('Explore o conteúdo e personalize esta área com suas integrações.', 'cpc-login-academia');
            }
        }

        private function get_default_sections(): array
        {
            $sections = [
                ['slug' => 'pratica', 'label' => __('Prática', 'cpc-login-academia'), 'shortcode' => 'cpc_pratica'],
                ['slug' => 'meus_conteudos', 'label' => __('Meus conteúdos', 'cpc-login-academia'), 'shortcode' => 'cpc_meus_conteudos'],
                ['slug' => 'treinador', 'label' => __('Treinador', 'cpc-login-academia'), 'shortcode' => 'cpc_treinador'],
                ['slug' => 'configuracoes', 'label' => __('Configurações', 'cpc-login-academia'), 'shortcode' => 'cpc_configuracoes'],
                ['slug' => 'planos', 'label' => __('Planos', 'cpc-login-academia'), 'shortcode' => 'cpc_planos'],
                ['slug' => 'suporte', 'label' => __('Suporte', 'cpc-login-academia'), 'shortcode' => 'cpc_suporte'],
            ];

            return apply_filters('cpc_orgprompt_sections', $sections);
        }

        private function get_section_config(string $slug)
        {
            return $this->registered_sections[$slug] ?? null;
        }

        public function register_rest_routes(): void
        {
            register_rest_route('cpc/v1', '/notifications', [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'rest_get_notifications'],
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'rest_create_notification'],
                    'permission_callback' => [$this, 'rest_can_create_notification'],
                    'args'                => [
                        'user_id' => [
                            'required' => false,
                            'type'     => 'integer',
                        ],
                        'title' => [
                            'required' => true,
                            'type'     => 'string',
                        ],
                        'message' => [
                            'required' => true,
                            'type'     => 'string',
                        ],
                        'url' => [
                            'required' => false,
                            'type'     => 'string',
                        ],
                        'type' => [
                            'required' => false,
                            'type'     => 'string',
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'rest_mark_read'],
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                    'args'                => [
                        'ids' => [
                            'required' => true,
                            'type'     => 'array',
                        ],
                    ],
                ],
            ]);
        }

        public function rest_can_create_notification(WP_REST_Request $request): bool
        {
            $allowed = current_user_can('edit_posts');

            return (bool) apply_filters('cpc_orgprompt_can_create_notification', $allowed, $request);
        }

        public function rest_get_notifications(WP_REST_Request $request)
        {
            $user_id = get_current_user_id();
            $notifications = $this->get_user_notifications($user_id);

            return rest_ensure_response($this->format_notifications_for_response($notifications, $user_id));
        }

        public function rest_create_notification(WP_REST_Request $request)
        {
            $user_id = (int) $request->get_param('user_id');
            if (!$user_id) {
                $user_id = get_current_user_id();
            }

            if (!$user_id) {
                return new WP_Error('cpc_invalid_user', __('Usuário inválido.', 'cpc-login-academia'), ['status' => 400]);
            }

            $title   = sanitize_text_field($request->get_param('title'));
            $message = wp_kses_post($request->get_param('message'));
            $url     = esc_url_raw($request->get_param('url'));
            $type    = $this->normalize_notification_type($request->get_param('type') ?: 'info');

            if ('' === $title || '' === trim(wp_strip_all_tags($message))) {
                return new WP_Error('cpc_missing_fields', __('Título e mensagem são obrigatórios.', 'cpc-login-academia'), ['status' => 400]);
            }

            $notification = $this->add_user_notification($user_id, $title, $message, $url, $type);

            if (is_wp_error($notification)) {
                return $notification;
            }

            return rest_ensure_response($notification);
        }

        public function rest_mark_read(WP_REST_Request $request)
        {
            $ids = (array) $request->get_param('ids');
            $ids = array_filter(array_map('sanitize_text_field', $ids));

            if (empty($ids)) {
                return new WP_Error('cpc_invalid_ids', __('Nenhuma notificação informada.', 'cpc-login-academia'), ['status' => 400]);
            }

            $user_id = get_current_user_id();
            $notifications = $this->get_user_notifications($user_id);

            foreach ($ids as $id) {
                if (isset($notifications[$id])) {
                    $notifications[$id]['read'] = true;
                }
            }

            update_user_meta($user_id, self::META_KEY, $notifications);

            do_action('cpc_orgprompt_notifications_marked_read', $ids, $user_id);

            return rest_ensure_response($this->format_notifications_for_response($notifications, $user_id));
        }

        public function handle_external_notification($user_id, $message, $url = '', $type = 'info')
        {
            if (!$user_id) {
                return;
            }

            $title   = apply_filters('cpc_orgprompt_default_notification_title', __('Notificação', 'cpc-login-academia'), $user_id, $message);
            $message = wp_kses_post($message);
            $url     = esc_url_raw($url);
            $type    = $this->normalize_notification_type($type);

            $this->add_user_notification($user_id, $title, $message, $url, $type);
        }

        private function add_user_notification(int $user_id, string $title, string $message, string $url = '', string $type = 'info')
        {
            $title   = sanitize_text_field($title);
            $message = wp_kses_post($message);
            $url     = esc_url_raw($url);
            $type    = $this->normalize_notification_type($type);

            $notifications = $this->get_user_notifications($user_id);
            $id = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('cpc_', true);

            $notification = [
                'id'      => $id,
                'title'   => $title,
                'message' => $message,
                'url'     => $url,
                'type'    => $type,
                'read'    => false,
                'created' => current_time('mysql'),
            ];

            $notification = apply_filters('cpc_orgprompt_pre_save_notification', $notification, $user_id);

            $notifications[$id] = $notification;

            $notifications = $this->enforce_notification_limit($notifications);

            $updated = update_user_meta($user_id, self::META_KEY, $notifications);

            if (false === $updated) {
                return new WP_Error('cpc_notification_error', __('Não foi possível salvar a notificação.', 'cpc-login-academia'));
            }

            do_action('cpc_orgprompt_notification_created', $notifications[$id], $user_id);

            return $this->prepare_notification_for_response($notifications[$id], $user_id);
        }

        private function prepare_notification_for_response(array $notification, int $user_id): array
        {
            $notification['title']   = sanitize_text_field($notification['title'] ?? '');
            $notification['message'] = wp_kses_post($notification['message'] ?? '');
            $notification['url']     = esc_url_raw($notification['url'] ?? '');
            $notification['type']    = $this->normalize_notification_type($notification['type'] ?? 'info');
            $notification['read']    = !empty($notification['read']);
            $notification['created'] = $notification['created'] ?? current_time('mysql');
            $created_time = strtotime($notification['created']);
            if (!$created_time) {
                $created_time = current_time('timestamp');
            }
            $notification['created_human'] = sprintf(
                /* translators: %s: relative time (e.g. 5 minutos) */
                __('há %s', 'cpc-login-academia'),
                human_time_diff($created_time, current_time('timestamp'))
            );

            return apply_filters('cpc_orgprompt_notification_response', $notification, $user_id);
        }

        private function enforce_notification_limit(array $notifications): array
        {
            $limit = (int) apply_filters('cpc_orgprompt_notifications_limit', self::DEFAULT_NOTIFICATION_LIMIT);
            if ($limit <= 0) {
                return $notifications;
            }

            uasort($notifications, function ($a, $b) {
                return strtotime($b['created'] ?? 0) <=> strtotime($a['created'] ?? 0);
            });

            if (count($notifications) <= $limit) {
                return $notifications;
            }

            return array_slice($notifications, 0, $limit, true);
        }

        private function format_notifications_for_response(array $notifications, int $user_id): array
        {
            $prepared = [];

            foreach ($notifications as $notification) {
                if (!is_array($notification)) {
                    continue;
                }

                $prepared[] = $this->prepare_notification_for_response($notification, $user_id);
            }

            return $prepared;
        }

        private function get_user_notifications(int $user_id): array
        {
            $notifications = get_user_meta($user_id, self::META_KEY, true);
            if (!is_array($notifications)) {
                $notifications = [];
            }

            uasort($notifications, function ($a, $b) {
                return strtotime($b['created'] ?? 0) <=> strtotime($a['created'] ?? 0);
            });

            return $notifications;
        }

        private function normalize_notification_type(string $type): string
        {
            $type = sanitize_key($type);

            if (!in_array($type, self::ALLOWED_NOTIFICATION_TYPES, true)) {
                $type = 'info';
            }

            return $type;
        }

        private function map_user_roles(array $roles): array
        {
            global $wp_roles;
            if (!isset($wp_roles)) {
                $wp_roles = wp_roles();
            }

            $translated = [];
            foreach ($roles as $role) {
                $translated[] = translate_user_role($wp_roles->roles[$role]['name'] ?? $role);
            }

            return $translated;
        }
    }
}

new CPC_Login_Academia_Plugin();
