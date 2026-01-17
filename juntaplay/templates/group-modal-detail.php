<?php
/**
 * Group detail modal content.
 */

declare(strict_types=1);

use JuntaPlay\Assets\ServiceIcons;
use JuntaPlay\Data\Groups;
use JuntaPlay\Data\GroupMembers;

if (!defined('ABSPATH')) {
    exit;
}

$group    = isset($group) && is_array($group) ? $group : [];
$is_owner = !empty($is_owner);

$title            = isset($group['title']) ? (string) $group['title'] : '';
$description      = isset($group['description']) ? (string) $group['description'] : '';
$rules            = isset($group['rules']) ? (string) $group['rules'] : '';
$service_name     = isset($group['service_name']) ? (string) $group['service_name'] : '';
$service_url      = isset($group['service_url']) ? (string) $group['service_url'] : '';
$price_regular    = isset($group['price_regular_display']) ? (string) $group['price_regular_display'] : '';
$price_promo      = isset($group['price_promotional_display']) ? (string) $group['price_promotional_display'] : '';
$member_price     = isset($group['member_price_display']) ? (string) $group['member_price_display'] : '';
$slots_summary    = isset($group['slots_summary']) ? (string) $group['slots_summary'] : '';
$support_channel  = isset($group['support_channel']) ? (string) $group['support_channel'] : '';
$delivery_time    = isset($group['delivery_time']) ? (string) $group['delivery_time'] : '';
$access_method    = isset($group['access_method']) ? (string) $group['access_method'] : '';
$access_login     = isset($group['access_login']) ? (string) $group['access_login'] : '';
$access_password  = isset($group['access_password']) ? (string) $group['access_password'] : '';
$access_notes     = isset($group['access_notes']) ? (string) $group['access_notes'] : '';
$category_label   = isset($group['category_label']) ? (string) $group['category_label'] : '';
$instant_label    = isset($group['instant_access_label']) ? (string) $group['instant_access_label'] : '';
$pool_title       = isset($group['pool_title']) ? (string) $group['pool_title'] : '';
$pool_link        = isset($group['pool_link']) ? (string) $group['pool_link'] : '';
$members_count    = isset($group['members_count']) ? (int) $group['members_count'] : 0;
$availability     = isset($group['availability_label']) ? (string) $group['availability_label'] : '';
$availabilityTone = isset($group['availability_tone']) ? (string) $group['availability_tone'] : '';
$price_highlight  = isset($group['price_highlight']) ? (string) $group['price_highlight'] : '';
$cta_label        = isset($group['cta_label']) ? (string) $group['cta_label'] : '';
$cta_url          = isset($group['cta_url']) ? (string) $group['cta_url'] : '';
$cta_disabled     = !empty($group['cta_disabled']);
$blocked_notice   = isset($group['blocked_notice']) ? (string) $group['blocked_notice'] : '';
$relationship_label = isset($group['relationship_label']) ? (string) $group['relationship_label'] : '';
$support_display  = isset($group['support_channel_display']) ? (string) $group['support_channel_display'] : $support_channel;
$support_type     = isset($group['support_channel_type']) ? (string) $group['support_channel_type'] : '';
$support_masked   = !empty($group['support_channel_masked']);
$support_notice   = isset($group['support_channel_notice']) ? (string) $group['support_channel_notice'] : '';
$support_label    = isset($group['support_channel_label']) ? (string) $group['support_channel_label'] : __('Suporte a Membros', 'juntaplay');
$complaint_url    = isset($group['complaint_url']) ? (string) $group['complaint_url'] : '';
$complaint_label  = isset($group['complaint_label']) ? (string) $group['complaint_label'] : __('Abrir reclamação', 'juntaplay');
$complaint_hint   = isset($group['complaint_hint']) ? (string) $group['complaint_hint'] : __('Problemas com o grupo? Abra uma reclamação ou disputa.', 'juntaplay');

$membership_status = isset($group['membership_status']) ? (string) $group['membership_status'] : 'active';

/**
 * IMPORTANTÍSSIMO:
 * Não usar $is_owner como fallback de papel.
 * O papel DEVE vir do backend (membership_role). Se não vier, assume "member".
 */
$membership_role = isset($group['membership_role']) ? (string) $group['membership_role'] : 'member';
$membership_role_norm = function_exists('sanitize_key')
    ? sanitize_key($membership_role)
    : strtolower($membership_role);

/**
 * Gate administrativo DEFINITIVO:
 * Papel vem do backend via membership_role (owner/manager).
 * NÃO usar CTA label nem flags frágeis como $group['is_admin'] para permissão.
 */
$is_group_admin = in_array($membership_role_norm, ['owner', 'manager'], true);

/**
 * Cancelamento de participação (usuário) não deve aparecer para papéis administrativos/bloqueados.
 * (Alinhado com o shortcode de cancelamento que bloqueia admin.)
 */
$blocked_member_cancel_roles = ['owner', 'manager', 'staff', 'system'];
$can_member_cancel = !in_array($membership_role_norm, $blocked_member_cancel_roles, true)
    && $membership_status !== 'guest'
    && $membership_status !== 'exit_scheduled';

$group_status_raw = isset($group['status']) ? (string) $group['status'] : '';
$group_status = function_exists('sanitize_key') ? sanitize_key($group_status_raw) : strtolower($group_status_raw);

$group_id = isset($group['id']) ? (int) $group['id'] : 0;

$profile_page_id = (int) get_option('juntaplay_page_perfil');
$messages_base_url = $profile_page_id ? (string) get_permalink($profile_page_id) : '';
if ($messages_base_url === '') {
    $messages_base_url = home_url('/perfil/');
}
if ($messages_base_url !== '') {
    $messages_base_url = add_query_arg('section', 'juntaplay-chat', $messages_base_url);
}

$contact_label = '';
$contact_url   = '';
$contact_hint  = '';

if ($group_id > 0 && $membership_status !== 'guest' && $messages_base_url !== '') {
    $current_user_id = get_current_user_id();
    if ($current_user_id > 0) {
        if ($is_group_admin) {
            $participants = GroupMembers::get_details($group_id, max(1, $members_count), 'active');
            foreach ($participants as $participant) {
                $participant_id = isset($participant['user_id']) ? (int) $participant['user_id'] : 0;
                $participant_role = isset($participant['role']) ? (string) $participant['role'] : '';
                $participant_role = function_exists('sanitize_key') ? sanitize_key($participant_role) : strtolower($participant_role);

                if ($participant_id <= 0 || $participant_id === $current_user_id) {
                    continue;
                }

                if (in_array($participant_role, ['owner', 'manager', 'staff', 'system'], true)) {
                    continue;
                }

                $contact_label = __('Falar com assinante', 'juntaplay');
                $contact_url   = add_query_arg(
                    [
                        'group_id'       => $group_id,
                        'participant_id' => $participant_id,
                    ],
                    $messages_base_url
                );
                $contact_hint = __('Precisa falar com um participante? Use o chat para resolver dúvidas do grupo.', 'juntaplay');
                break;
            }
        } elseif ($membership_role_norm === 'member') {
            $owner_id = isset($group['owner_id']) ? (int) $group['owner_id'] : 0;
            if ($owner_id > 0 && $owner_id !== $current_user_id) {
                $contact_label = __('Falar com administrador', 'juntaplay');
                $contact_url   = add_query_arg('group_id', $group_id, $messages_base_url);
                $contact_hint  = __('Fale com o administrador se precisar de ajuda com o grupo.', 'juntaplay');
            }
        }
    }
}

$should_show_contact = $contact_label !== '' && $contact_url !== '';
$show_complaint_block = $complaint_url !== '' && $membership_status !== 'guest' && $membership_role_norm === 'member';

$exit_effective_display = isset($group['exit_effective_display']) ? (string) $group['exit_effective_display'] : '';
$exit_effective_preview = isset($group['exit_effective_preview']) ? (string) $group['exit_effective_preview'] : '';
$caucao_cycle_end = isset($group['caucao_cycle_end']) ? (string) $group['caucao_cycle_end'] : '';

$pool_slug        = isset($group['pool_slug']) ? (string) $group['pool_slug'] : '';
$group_icon       = isset($group['cover_url']) ? (string) $group['cover_url'] : '';

if ($group_icon === '') {
    $group_icon = $pool_slug !== '' ? ServiceIcons::get($pool_slug) : '';
}
if ($group_icon === '') {
    $group_icon = ServiceIcons::resolve($pool_slug, $service_name !== '' ? $service_name : $title, $service_url);
}
if ($group_icon === '') {
    $group_icon = ServiceIcons::fallback();
}

$group_initial_raw = $title !== '' ? $title : ($service_name !== '' ? $service_name : '');
$group_initial = $group_initial_raw !== ''
    ? (function_exists('mb_substr') ? mb_substr($group_initial_raw, 0, 1) : substr($group_initial_raw, 0, 1))
    : '';

$exit_effective_copy = $exit_effective_display !== '' ? $exit_effective_display : $exit_effective_preview;

$exit_notice_within_15 = false;
$exit_notice_known = false;
if ($caucao_cycle_end !== '') {
    $cycle_end_ts = strtotime($caucao_cycle_end);
    if ($cycle_end_ts) {
        $exit_notice_known = true;
        $exit_notice_within_15 = ($cycle_end_ts - current_time('timestamp')) < (15 * DAY_IN_SECONDS);
    }
}

$exit_notice_label = $exit_notice_known
    ? ($exit_notice_within_15 ? __('Sim', 'juntaplay') : __('Não', 'juntaplay'))
    : __('Não identificado', 'juntaplay');

/**
 * Permissão de cancelar grupo (admin):
 * Deve seguir o backend: somente admin (owner/manager) e status aprovado.
 */
$approved_status = defined('\\JuntaPlay\\Data\\Groups::STATUS_APPROVED') ? (string) Groups::STATUS_APPROVED : 'approved';
$approved_status = function_exists('sanitize_key') ? sanitize_key($approved_status) : strtolower($approved_status);

$admin_cancel_allowed = $is_group_admin && $group_id > 0 && $group_status === $approved_status;
$admin_cancel_modal_id = $admin_cancel_allowed ? 'jp-group-admin-cancel-detail-' . $group_id : '';
$credentials_allowed = $is_group_admin && $group_id > 0;
$credentials_modal_id = $credentials_allowed ? 'jp-group-credentials-modal-' . $group_id : '';
$credentials_available = $credentials_allowed && ($access_login !== '' || $access_password !== '' || $access_notes !== '');

$current_user = wp_get_current_user();
$current_user_name = ($current_user && $current_user->exists())
    ? ($current_user->display_name !== '' ? $current_user->display_name : $current_user->user_login)
    : __('Assinante', 'juntaplay');

$support_icon_svg = '';
switch ($support_type) {
    case 'whatsapp':
        $support_icon_svg = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.66 15.21L2 22l4.93-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.18-1.14l-.3-.18-2.93.78.79-2.86-.19-.3A8.2 8.2 0 1 1 12 20.2Zm4.6-6.1c-.25-.13-1.47-.72-1.7-.8s-.4-.12-.57.13-.65.8-.8.97-.3.19-.55.06a6.7 6.7 0 0 1-2-1.24 7.48 7.48 0 0 1-1.4-1.74c-.15-.26 0-.4.11-.53.11-.11.26-.3.38-.45a1.74 1.74 0 0 0 .25-.42.48.48 0 0 0 0-.45c0-.13-.57-1.37-.78-1.88s-.41-.44-.57-.45h-.49a.94.94 0 0 0-.68.32A2.85 2.85 0 0 0 6 8.7a5 5 0 0 0 1.05 2.65 11.32 11.32 0 0 0 4.32 3.61c.43.19.76.3 1 .38a2.39 2.39 0 0 0 1.1.07 1.84 1.84 0 0 0 1.2-.86 1.5 1.5 0 0 0 .1-.86c-.04-.07-.22-.13-.37-.2Z"/></svg>';
        break;
    case 'email':
        $support_icon_svg = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 2v.2l8 4.8 8-4.8V7H4Zm16 10V9.62l-7.37 4.42a1 1 0 0 1-1.26 0L4 9.62V17h16Z"/></svg>';
        break;
    default:
        $support_icon_svg = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 3a9 9 0 1 1-4.5 16.8L3 21l1.3-4.5A9 9 0 0 1 12 3Zm0 2a7 7 0 1 0 0 14 7 7 0 0 0 0-14Zm0 3a1 1 0 0 1 1 1v3h2a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/></svg>';
        break;
}

?>
<div class="juntaplay-group-modal__detail">
    <header class="juntaplay-group-modal__header">
        <div class="juntaplay-group-modal__media">
            <span
                class="juntaplay-group-modal__icon<?php echo $group_icon !== '' ? ' has-image' : ''; ?>"
                <?php echo $group_icon !== '' ? ' style="background-image: url(' . esc_url($group_icon) . ')"' : ''; ?>
                aria-hidden="true"
            ><?php echo $group_icon === '' ? esc_html($group_initial) : ''; ?></span>
        </div>
        <div class="juntaplay-group-modal__headline">
            <h3 class="juntaplay-group-modal__title"><?php echo esc_html($title !== '' ? $title : esc_html__('Grupo sem nome', 'juntaplay')); ?></h3>
            <?php if ($availability !== '') : ?>
                <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($availabilityTone !== '' ? $availabilityTone : 'info'); ?>"><?php echo esc_html($availability); ?></span>
            <?php endif; ?>
            <?php if ($members_count > 0) : ?>
                <span class="juntaplay-group-modal__meta"><?php echo esc_html(sprintf(_n('%d participante', '%d participantes', $members_count, 'juntaplay'), $members_count)); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <div class="juntaplay-group-modal__body">
        <?php if ($price_highlight !== '' || $cta_label !== '') : ?>
            <div class="juntaplay-group-modal__cta">
                <?php if ($price_highlight !== '') : ?>
                    <span class="juntaplay-group-modal__cta-price"><?php echo esc_html($price_highlight); ?></span>
                <?php endif; ?>
                <?php if ($cta_label !== '') : ?>
                    <?php if ($cta_url !== '' && !$cta_disabled) : ?>
                        <a class="juntaplay-button juntaplay-button--primary" href="<?php echo esc_url($cta_url); ?>" rel="nofollow noopener"><?php echo esc_html($cta_label); ?></a>
                    <?php else : ?>
                        <button type="button" class="juntaplay-button juntaplay-button--primary" disabled><?php echo esc_html($cta_label); ?></button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($blocked_notice !== '') : ?>
            <p class="juntaplay-group-modal__notice"><?php echo esc_html($blocked_notice); ?></p>
        <?php endif; ?>

        <?php if ($show_complaint_block || $should_show_contact) : ?>
            <div class="juntaplay-group-modal__notice juntaplay-group-modal__notice--highlight">
                <strong><?php esc_html_e('Problemas com o grupo?', 'juntaplay'); ?></strong>
                <?php if ($show_complaint_block) : ?>
                    <p><?php echo esc_html($complaint_hint); ?></p>
                <?php elseif ($contact_hint !== '') : ?>
                    <p><?php echo esc_html($contact_hint); ?></p>
                <?php endif; ?>
                <div class="juntaplay-group-complaint__actions">
                    <?php if ($show_complaint_block) : ?>
                        <a class="juntaplay-button juntaplay-button--ghost" href="<?php echo esc_url($complaint_url); ?>"><?php echo esc_html($complaint_label); ?></a>
                        <?php if ($can_member_cancel) : ?>
                            <a
                                class="juntaplay-button juntaplay-button--ghost"
                                href="<?php echo esc_url(add_query_arg('group_id', (int) $group_id, site_url('/cancelamento'))); ?>"
                            >
                                <?php echo esc_html__('Cancelar minha participação', 'juntaplay'); ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($should_show_contact) : ?>
                        <a class="juntaplay-button juntaplay-button--primary juntaplay-button--glass" href="<?php echo esc_url($contact_url); ?>" data-group-contact-link><?php echo esc_html($contact_label); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($credentials_available) : ?>
            <div class="juntaplay-group-modal__notice">
                <strong><?php esc_html_e('Credenciais do grupo', 'juntaplay'); ?></strong>
                <p><?php esc_html_e('Visualize os dados de acesso utilizados pelos participantes.', 'juntaplay'); ?></p>
                <div class="juntaplay-group-complaint__actions">
                    <button
                        type="button"
                        class="juntaplay-button juntaplay-button--ghost"
                        data-group-credentials-open
                        data-modal-id="<?php echo esc_attr($credentials_modal_id); ?>"
                    >
                        <?php echo esc_html__('Credenciais do grupo', 'juntaplay'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($can_member_cancel) : ?>
            <div class="juntaplay-modal juntaplay-modal--compact" id="jp-group-exit-modal-detail-<?php echo esc_attr((string) $group_id); ?>" data-group-exit-modal hidden aria-hidden="true">
                <div class="juntaplay-modal__overlay" data-modal-close></div>
                <div class="juntaplay-modal__dialog" role="dialog" aria-modal="true">
                    <button type="button" class="juntaplay-modal__close" data-modal-close aria-label="<?php echo esc_attr__('Fechar', 'juntaplay'); ?>">&times;</button>
                    <div class="juntaplay-modal__content">
                        <h3 class="juntaplay-modal__title"><?php echo esc_html__('Confirmar cancelamento', 'juntaplay'); ?></h3>
                        <p class="juntaplay-modal__text"><?php echo esc_html__('Antes de prosseguir com seu cancelamento precisamos que você saiba sobre algumas informações importantes:', 'juntaplay'); ?></p>
                        <div class="juntaplay-group-cancel__illustration" aria-hidden="true"></div>

                        <p class="juntaplay-modal__text">
                            <?php
                            if ($exit_effective_copy !== '') {
                                if ($exit_notice_known && $exit_notice_within_15) {
                                    echo esc_html(sprintf(
                                        __('%1$s, você está solicitando o cancelamento com menos de 15 dias de antecedência da data de vencimento. Sua saída será agendada para o dia %2$s e o crédito caução será utilizado para quitar a última fatura do grupo.', 'juntaplay'),
                                        $current_user_name,
                                        $exit_effective_copy
                                    ));
                                } elseif ($exit_notice_known && !$exit_notice_within_15) {
                                    echo esc_html(sprintf(
                                        __('%1$s, você está solicitando o cancelamento com pelo menos 15 dias de antecedência da data de vencimento. Sua saída será agendada para o dia %2$s e o crédito caução será devolvido após a conclusão do ciclo.', 'juntaplay'),
                                        $current_user_name,
                                        $exit_effective_copy
                                    ));
                                } else {
                                    echo esc_html(sprintf(
                                        __('%1$s, sua saída será agendada para %2$s. O crédito caução seguirá as regras de devolução da plataforma.', 'juntaplay'),
                                        $current_user_name,
                                        $exit_effective_copy
                                    ));
                                }
                            } else {
                                echo esc_html__('Não foi possível calcular a data efetiva de saída no momento.', 'juntaplay');
                            }
                            ?>
                        </p>

                        <div class="juntaplay-alert juntaplay-alert--danger">
                            <span class="juntaplay-alert__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                    <path fill="currentColor" d="M11 7h2v6h-2V7Zm1 10a1.25 1.25 0 1 1 0-2.5A1.25 1.25 0 0 1 12 17Zm-.18-14.73a2 2 0 0 1 3.36 0l7.37 12.62A2 2 0 0 1 20.37 18H3.63a2 2 0 0 1-1.99-3.11l7.37-12.62Z"/>
                                </svg>
                            </span>
                            <p>
                                <?php
                                echo esc_html__(
                                    'Você pagou os créditos de assinatura (caução) quando se inscreveu no grupo. Para receber o crédito caução de volta, é necessário solicitar o cancelamento com pelo menos 15 dias de antecedência da data de vencimento e não podem haver faturas em aberto ou reclamações no período.',
                                    'juntaplay'
                                );
                                ?>
                            </p>
                        </div>

                        <form class="juntaplay-group-cancel__form" method="post" action="">
                            <input type="hidden" name="jp_profile_action" value="1" />
                            <input type="hidden" name="jp_profile_section" value="group_cancel" />
                            <input type="hidden" name="jp_profile_group_cancel" value="<?php echo esc_attr((string) $group_id); ?>" />
                            <?php
                            $cancel_profile_nonce = wp_nonce_field(
                                'juntaplay_profile_update',
                                'jp_profile_nonce',
                                true,
                                false
                            );
                            $cancel_profile_nonce = preg_replace(
                                '/id="jp_profile_nonce"/',
                                'id="' . esc_attr(wp_unique_id('jp_profile_nonce_')) . '"',
                                $cancel_profile_nonce
                            );
                            echo $cancel_profile_nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

                            $cancel_action_nonce = wp_nonce_field(
                                'jp_profile_group_cancel',
                                'jp_profile_group_cancel_nonce',
                                true,
                                false
                            );
                            $cancel_action_nonce = preg_replace(
                                '/id="jp_profile_group_cancel_nonce"/',
                                'id="' . esc_attr(wp_unique_id('jp_profile_group_cancel_nonce_')) . '"',
                                $cancel_action_nonce
                            );
                            echo $cancel_action_nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ?>
                            <div class="juntaplay-form__group">
                                <label for="jp-group-cancel-reason-modal-<?php echo esc_attr((string) $group_id); ?>"><?php echo esc_html__('Descreva o motivo da sua saída', 'juntaplay'); ?></label>
                                <textarea id="jp-group-cancel-reason-modal-<?php echo esc_attr((string) $group_id); ?>" name="jp_profile_group_cancel_reason" class="juntaplay-form__input" rows="3" minlength="10" required placeholder="<?php echo esc_attr__('Explique o que aconteceu para que possamos orientar o administrador.', 'juntaplay'); ?>"></textarea>
                            </div>
                            <div class="juntaplay-group-complaint__actions">
                                <button type="button" class="juntaplay-button juntaplay-button--primary" data-group-cancel-submit><?php echo esc_html__('Prosseguir', 'juntaplay'); ?></button>
                                <button type="button" class="juntaplay-button juntaplay-button--ghost" data-modal-close><?php echo esc_html__('Cancelar', 'juntaplay'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($admin_cancel_allowed) : ?>
            <div class="juntaplay-modal juntaplay-modal--compact" id="<?php echo esc_attr($admin_cancel_modal_id); ?>" hidden aria-hidden="true">
                <div class="juntaplay-modal__overlay" data-modal-close></div>
                <div class="juntaplay-modal__dialog" role="dialog" aria-modal="true">
                    <button type="button" class="juntaplay-modal__close" data-modal-close aria-label="<?php echo esc_attr__('Fechar', 'juntaplay'); ?>">&times;</button>
                    <div class="juntaplay-modal__content">
                        <h3 class="juntaplay-modal__title"><?php echo esc_html__('Cancelar Grupo', 'juntaplay'); ?></h3>
                        <p class="juntaplay-modal__text">
                            <?php echo esc_html__('Cancelar este grupo encerrará definitivamente o grupo. Os participantes manterão acesso até o fim do período já pago. Caso algum usuário tenha calção a ser devolvido, a plataforma o fará, conforme as regras. Esta ação não pode ser desfeita.', 'juntaplay'); ?>
                        </p>
                        <form class="juntaplay-group-admin-cancel__form" method="post">
                            <input type="hidden" name="jp_profile_action" value="1" />
                            <input type="hidden" name="jp_profile_section" value="group_admin_cancel" />
                            <input type="hidden" name="jp_profile_group_admin_cancel" value="<?php echo esc_attr((string) $group_id); ?>" />
                            <?php
                            $admin_cancel_profile_nonce = wp_nonce_field(
                                'juntaplay_profile_update',
                                'jp_profile_nonce',
                                true,
                                false
                            );
                            $admin_cancel_profile_nonce = preg_replace(
                                '/id="jp_profile_nonce"/',
                                'id="' . esc_attr(wp_unique_id('jp_profile_nonce_')) . '"',
                                $admin_cancel_profile_nonce
                            );
                            echo $admin_cancel_profile_nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

                            $admin_cancel_action_nonce = wp_nonce_field(
                                'jp_profile_group_admin_cancel',
                                'jp_profile_group_admin_cancel_nonce',
                                true,
                                false
                            );
                            $admin_cancel_action_nonce = preg_replace(
                                '/id="jp_profile_group_admin_cancel_nonce"/',
                                'id="' . esc_attr(wp_unique_id('jp_profile_group_admin_cancel_nonce_')) . '"',
                                $admin_cancel_action_nonce
                            );
                            echo $admin_cancel_action_nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ?>
                            <div class="juntaplay-group-complaint__actions">
                                <button type="submit" class="juntaplay-button juntaplay-button--danger"><?php echo esc_html__('Confirmar cancelamento', 'juntaplay'); ?></button>
                                <button type="button" class="juntaplay-button juntaplay-button--ghost" data-modal-close><?php echo esc_html__('Voltar', 'juntaplay'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($credentials_available) : ?>
            <div class="juntaplay-modal juntaplay-modal--compact" id="<?php echo esc_attr($credentials_modal_id); ?>" hidden aria-hidden="true">
                <div class="juntaplay-modal__overlay" data-modal-close></div>
                <div class="juntaplay-modal__dialog" role="dialog" aria-modal="true">
                    <button type="button" class="juntaplay-modal__close" data-modal-close aria-label="<?php echo esc_attr__('Fechar', 'juntaplay'); ?>">&times;</button>
                    <div class="juntaplay-modal__content">
                        <h3 class="juntaplay-modal__title"><?php echo esc_html__('Credenciais de acesso', 'juntaplay'); ?></h3>
                        <p class="juntaplay-modal__text"><?php echo esc_html__('Estas credenciais são compartilhadas com os participantes do grupo.', 'juntaplay'); ?></p>
                        <div class="juntaplay-form__group">
                            <label for="jp-group-credentials-login-<?php echo esc_attr((string) $group_id); ?>"><?php esc_html_e('Login', 'juntaplay'); ?></label>
                            <input
                                id="jp-group-credentials-login-<?php echo esc_attr((string) $group_id); ?>"
                                type="text"
                                class="juntaplay-form__input"
                                value="<?php echo esc_attr($access_login); ?>"
                                readonly
                            />
                        </div>
                        <div class="juntaplay-form__group">
                            <label for="jp-group-credentials-password-<?php echo esc_attr((string) $group_id); ?>"><?php esc_html_e('Senha', 'juntaplay'); ?></label>
                            <div class="juntaplay-input-group" style="display:flex; gap:8px; align-items:center;">
                                <input
                                    id="jp-group-credentials-password-<?php echo esc_attr((string) $group_id); ?>"
                                    type="password"
                                    class="juntaplay-form__input"
                                    value="<?php echo esc_attr($access_password); ?>"
                                    readonly
                                    data-group-credentials-password
                                />
                                <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-credentials-toggle>
                                    <?php esc_html_e('Mostrar', 'juntaplay'); ?>
                                </button>
                            </div>
                        </div>
                        <?php if ($access_notes !== '') : ?>
                            <div class="juntaplay-form__group">
                                <label for="jp-group-credentials-notes-<?php echo esc_attr((string) $group_id); ?>"><?php esc_html_e('Observações', 'juntaplay'); ?></label>
                                <textarea
                                    id="jp-group-credentials-notes-<?php echo esc_attr((string) $group_id); ?>"
                                    class="juntaplay-form__input"
                                    rows="3"
                                    readonly
                                ><?php echo esc_textarea($access_notes); ?></textarea>
                            </div>
                        <?php endif; ?>
                        <div class="juntaplay-group-complaint__actions">
                            <button type="button" class="juntaplay-button juntaplay-button--ghost" data-modal-close><?php echo esc_html__('Fechar', 'juntaplay'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <dl class="juntaplay-group-modal__info">
            <?php if ($service_name !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Serviço', 'juntaplay'); ?></dt>
                    <dd>
                        <?php if ($service_url !== '') : ?>
                            <a href="<?php echo esc_url($service_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($service_name); ?></a>
                        <?php else : ?>
                            <?php echo esc_html($service_name); ?>
                        <?php endif; ?>
                    </dd>
                </div>
            <?php endif; ?>
            <?php if ($category_label !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Categoria', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($category_label); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($price_regular !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Valor do serviço', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($price_regular); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($price_promo !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Oferta promocional', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($price_promo); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($member_price !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Valor por participante', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($member_price); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($slots_summary !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Vagas', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($slots_summary); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($support_display !== '') : ?>
                <div>
                    <dt><?php echo esc_html($support_label); ?></dt>
                    <dd class="juntaplay-group-modal__support">
                        <span class="juntaplay-group-modal__support-line">
                            <?php if ($support_icon_svg !== '') : ?>
                                <span class="juntaplay-group-modal__support-icon" aria-hidden="true">
                                    <?php echo $support_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </span>
                            <?php endif; ?>
                            <span class="juntaplay-group-modal__support-value"><?php echo esc_html($support_display); ?></span>
                        </span>
                        <?php if ($support_masked && $support_notice !== '') : ?>
                            <span class="juntaplay-group-modal__support-hint"><?php echo esc_html($support_notice); ?></span>
                        <?php endif; ?>
                    </dd>
                </div>
            <?php endif; ?>
            <?php if ($relationship_label !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Relação com administrador', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($relationship_label); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($delivery_time !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Prazo de entrega', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($delivery_time); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($access_method !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Forma de acesso', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($access_method); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($instant_label !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Acesso instantâneo', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($instant_label); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($pool_title !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Grupo vinculado', 'juntaplay'); ?></dt>
                    <dd>
                        <?php if ($pool_link !== '') : ?>
                            <a href="<?php echo esc_url($pool_link); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($pool_title); ?></a>
                        <?php else : ?>
                            <?php echo esc_html($pool_title); ?>
                        <?php endif; ?>
                    </dd>
                </div>
            <?php endif; ?>
        </dl>

        <?php if ($description !== '') : ?>
            <section class="juntaplay-group-modal__section">
                <h4><?php esc_html_e('Descrição do grupo', 'juntaplay'); ?></h4>
                <?php echo wp_kses_post(wpautop($description)); ?>
            </section>
        <?php endif; ?>

        <?php if ($rules !== '') : ?>
            <section class="juntaplay-group-modal__section">
                <h4><?php esc_html_e('Regras para participantes', 'juntaplay'); ?></h4>
                <?php echo wp_kses_post(wpautop($rules)); ?>
            </section>
        <?php endif; ?>

        <?php if ($admin_cancel_allowed) : ?>
            <div class="juntaplay-group-modal__section" style="margin-top: 16px;">
                <button
                    type="button"
                    class="juntaplay-button juntaplay-button--danger juntaplay-group-cancel"
                    data-group-admin-cancel-open
                    data-modal-id="<?php echo esc_attr($admin_cancel_modal_id); ?>"
                >
                    <?php echo esc_html__('Cancelar Grupo', 'juntaplay'); ?>
                </button>
                <p style="margin: 8px 0 0; font-size: 12px; opacity: 0.75;">
                    <?php echo esc_html__('Ação administrativa. Os participantes manterão acesso até o fim do período pago. Esta ação não pode ser desfeita.', 'juntaplay'); ?>
                </p>
            </div>
        <?php endif; ?>

    </div>
</div>
<script>
    (() => {
        const cancelamentoBase = <?php echo wp_json_encode(site_url('/cancelamento')); ?>;

        const closeModal = (modal) => {
            if (!modal) {
                return;
            }
            modal.setAttribute('aria-hidden', 'true');
            modal.setAttribute('hidden', 'hidden');
            modal.classList.remove('is-open');

            const openModals = document.querySelectorAll('.juntaplay-modal.is-open');
            if (!openModals.length) {
                document.body.classList.remove('juntaplay-modal-open');
            }
        };

        const openModal = (modal) => {
            if (!modal) {
                return;
            }
            modal.removeAttribute('hidden');
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.add('is-open');
            document.body.classList.add('juntaplay-modal-open');
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-group-exit-trigger]');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const openModals = document.querySelectorAll('.juntaplay-modal.is-open');
            openModals.forEach((modal) => closeModal(modal));

            const groupId =
                trigger.dataset.groupId ||
                trigger.getAttribute('data-group-id') ||
                trigger.getAttribute('data-group') ||
                trigger.closest('[data-group-id]')?.getAttribute('data-group-id') ||
                trigger.closest('[data-group]')?.getAttribute('data-group');

            if (!groupId || Number.isNaN(Number(groupId))) {
                console.warn('JuntaPlay: group_id inválido para cancelamento.', {
                    groupId,
                    trigger,
                });
                return;
            }

            window.location.href = `${cancelamentoBase}?group_id=${encodeURIComponent(groupId)}`;
        });

        document.addEventListener('click', (event) => {
            const adminCancelTrigger = event.target.closest('[data-group-admin-cancel-open]');
            if (adminCancelTrigger) {
                event.preventDefault();
                const modalId = adminCancelTrigger.getAttribute('data-modal-id');
                if (!modalId) {
                    return;
                }
                const modal = document.getElementById(modalId);
                if (!modal) {
                    return;
                }
                closeModal(adminCancelTrigger.closest('.juntaplay-modal'));
                openModal(modal);
                return;
            }

            const credentialsTrigger = event.target.closest('[data-group-credentials-open]');
            if (credentialsTrigger) {
                event.preventDefault();
                const modalId = credentialsTrigger.getAttribute('data-modal-id');
                if (!modalId) {
                    return;
                }
                const modal = document.getElementById(modalId);
                if (!modal) {
                    return;
                }
                closeModal(credentialsTrigger.closest('.juntaplay-modal'));
                openModal(modal);
                return;
            }

            const toggleButton = event.target.closest('[data-group-credentials-toggle]');
            if (toggleButton) {
                event.preventDefault();
                const modal = toggleButton.closest('.juntaplay-modal');
                if (!modal) {
                    return;
                }
                const input = modal.querySelector('[data-group-credentials-password]');
                if (!input) {
                    return;
                }
                if (input.type === 'password') {
                    input.type = 'text';
                    toggleButton.textContent = <?php echo wp_json_encode(__('Ocultar', 'juntaplay')); ?>;
                } else {
                    input.type = 'password';
                    toggleButton.textContent = <?php echo wp_json_encode(__('Mostrar', 'juntaplay')); ?>;
                }
                return;
            }

            const contactLink = event.target.closest('[data-group-contact-link]');
            if (contactLink) {
                event.preventDefault();
                event.stopPropagation();
                if (typeof event.stopImmediatePropagation === 'function') {
                    event.stopImmediatePropagation();
                }
                const destination = contactLink.getAttribute('href');
                if (destination) {
                    window.location.href = destination;
                }
                return;
            }

            const submitButton = event.target.closest('[data-group-cancel-submit]');
            if (!submitButton) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const form = submitButton.closest('form');
            if (!form) {
                return;
            }

            if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                return;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }

            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            if (form.dispatchEvent(submitEvent)) {
                form.submit();
            }
        });
    })();
</script>
