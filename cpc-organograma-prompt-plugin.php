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
            $primary   = '#6a5ae0';
            $secondary = '#bf83ff';
            $ink       = '#1f2733';
            $muted     = '#44566c';

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

                    const interactiveElements = Array.from(menuButtons);
                    dropdownLinks.forEach(link => interactiveElements.push(link));

                    interactiveElements.forEach(element => {
                        element.addEventListener('click', function(event){
                            if(event){ event.preventDefault(); }
                            const target = element.getAttribute('data-cpc-target');
                            if(!target) return;
                            const label = element.getAttribute('data-cpc-label') || element.textContent || '';
                            menuButtons.forEach(btn => {
                                btn.classList.toggle('is-active', btn.getAttribute('data-cpc-target') === target);
                            });
                            sections.forEach(section => section.classList.toggle('is-active', section.id === target));
                            if (dropdownMenu) {
                                dropdownLinks.forEach(link => {
                                    link.classList.toggle('is-active', link.getAttribute('data-cpc-target') === target);
                                });
                            }
                            updateDropdownLabel(label);
                            closeDropdown();
                            if (typeof window.CPC_ORGPROMPT_LOAD_SECTION === 'function') {
                                window.CPC_ORGPROMPT_LOAD_SECTION(target.replace('cpc-section-',''), function(html){
                                    const targetSection = container.querySelector('#'+target);
                                    if(targetSection){ targetSection.innerHTML = html; }
                                });
                            }
                        });
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

                    if (bell) {
                        if (panel) {
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
            $sections = apply_filters('cpc_orgprompt_menu_items', $sections, $user);
            foreach ($sections as $section) {
                $slug = sanitize_key($section['slug']);
                $existing = $this->registered_sections[$slug] ?? [];
                $merged = array_merge($existing, $section);
                $merged['slug'] = $slug;
                if (!empty($merged['shortcode'])) {
                    $merged['shortcode'] = sanitize_key($merged['shortcode']);
                }
                $this->registered_sections[$slug] = $merged;
            }

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

        private function render_section(string $slug): string
        {
            if (!is_user_logged_in()) {
                return '<div class="cpc-user-area"><p>' . esc_html__('Você precisa estar logado para acessar esta área.', 'cpc-login-academia') . '</p></div>';
            }

            $this->enqueue_assets();

            $section = $this->get_section_config($slug);

            return '<div class="cpc-user-area">' . $this->render_section_content($slug, $section) . '</div>';
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
