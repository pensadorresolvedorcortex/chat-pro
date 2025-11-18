<?php
/**
 * Dashboard card: GRUPOS
 * Uses WooCommerce (when available) and basic WP data to surface group approvals,
 * revenue, avatars and recent transactions for Juntaplay.
 */

defined('ABSPATH') or die('Silence is golden :)');

require_once __DIR__ . '/gdw-groups-data.php';

$snapshot = function_exists( 'gdw_get_groups_card_snapshot' ) ? gdw_get_groups_card_snapshot() : array();

$groups_admin_link = isset( $snapshot['links']['admin'] ) ? $snapshot['links']['admin'] : admin_url('admin.php?page=juntaplay-groups');
$badge_url = isset( $snapshot['badge_url'] ) ? $snapshot['badge_url'] : 'https://www.juntaplay.com.br/wp-content/uploads/2025/11/dheniell.svg';

$monthly_total_display = isset( $snapshot['monthly_total_label'] ) ? $snapshot['monthly_total_label'] : 'R$ 0,00';
$monthly_count = isset( $snapshot['monthly_count'] ) ? intval( $snapshot['monthly_count'] ) : 0;
$new_user_cards = isset( $snapshot['new_users'] ) ? (array) $snapshot['new_users'] : array();
$pending_groups = isset( $snapshot['pending_groups'] ) ? (array) $snapshot['pending_groups'] : array();
$recent_orders = isset( $snapshot['recent_orders'] ) ? (array) $snapshot['recent_orders'] : array();

?>
<div class="gdw-group-card">
  <a class="gdw-group-card__link" href="<?php echo esc_url( $groups_admin_link ); ?>">
    <div class="gdw-group-card__badge">
      <img src="<?php echo esc_url( $badge_url ); ?>" alt="<?php esc_attr_e( 'Marca Juntaplay', 'gdwlang' ); ?>" />
    </div>
    <div class="gdw-group-card__header">
      <div>
        <p class="gdw-group-card__eyebrow"><?php echo esc_html__( 'Central de grupos', 'gdwlang' ); ?></p>
        <h3><?php echo esc_html__( 'GRUPOS', 'gdwlang' ); ?></h3>
      </div>
      <div class="gdw-group-card__actions">
        <button type="button" class="gdw-group-card__refresh" aria-label="<?php esc_attr_e( 'Atualizar card', 'gdwlang' ); ?>">
          <?php echo esc_html__( 'Atualizar', 'gdwlang' ); ?>
        </button>
        <span class="gdw-group-card__cta"><?php echo esc_html__( 'Acessar gestão', 'gdwlang' ); ?> →</span>
      </div>
    </div>

    <div class="gdw-group-card__grid">
      <div class="gdw-group-card__metric">
        <p><?php echo esc_html__( 'Valores recebidos no mês', 'gdwlang' ); ?></p>
        <strong data-gdw-groups-total><?php echo wp_kses_post( $monthly_total_display ); ?></strong>
        <small data-gdw-groups-total-count><?php echo esc_html( sprintf( _n( '%d transação no período', '%d transações no período', $monthly_count, 'gdwlang' ), $monthly_count ) ); ?></small>
      </div>

      <div class="gdw-group-card__panel">
        <p class="gdw-group-card__panel-title"><?php echo esc_html__( 'Novos usuários cadastrados', 'gdwlang' ); ?></p>
        <div class="gdw-group-card__avatars" data-gdw-groups-new-users>
          <?php if ( ! empty( $new_user_cards ) ) : ?>
            <?php foreach ( $new_user_cards as $person ) : ?>
              <span class="gdw-group-card__avatar" title="<?php echo esc_attr( $person['name'] ); ?>">
                <img src="<?php echo esc_url( $person['photo'] ); ?>" alt="<?php echo esc_attr( $person['name'] ); ?>" />
              </span>
            <?php endforeach; ?>
          <?php else : ?>
            <span class="gdw-group-card__placeholder"><?php echo esc_html__( 'Sem cadastros recentes', 'gdwlang' ); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="gdw-group-card__panel">
        <p class="gdw-group-card__panel-title"><?php echo esc_html__( 'Grupos pendentes', 'gdwlang' ); ?></p>
        <ul class="gdw-group-card__list" data-gdw-groups-pending>
          <?php if ( ! empty( $pending_groups ) ) : ?>
            <?php foreach ( $pending_groups as $group ) : ?>
              <li>
                <img src="<?php echo esc_url( $group['thumb'] ); ?>" alt="<?php echo esc_attr( $group['title'] ); ?>" />
                <span><?php echo esc_html( $group['title'] ); ?></span>
                <em><?php echo esc_html__( 'Aprovar ou recusar', 'gdwlang' ); ?></em>
              </li>
            <?php endforeach; ?>
          <?php else : ?>
            <li class="gdw-group-card__placeholder"><?php echo esc_html__( 'Nenhum grupo precisa de tratamento', 'gdwlang' ); ?></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="gdw-group-card__panel">
        <p class="gdw-group-card__panel-title"><?php echo esc_html__( 'Transações recentes', 'gdwlang' ); ?></p>
        <ul class="gdw-group-card__list is-compact" data-gdw-groups-recent>
          <?php if ( ! empty( $recent_orders ) ) : ?>
            <?php foreach ( $recent_orders as $order_item ) : ?>
              <li>
                <span class="gdw-group-card__pill"><?php echo esc_html( $order_item['date'] ); ?></span>
                <strong><?php echo wp_kses_post( $order_item['total'] ); ?></strong>
              </li>
            <?php endforeach; ?>
          <?php else : ?>
            <li class="gdw-group-card__placeholder"><?php echo esc_html__( 'Nenhuma transação recente', 'gdwlang' ); ?></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="gdw-group-card__footer">
      <span class="gdw-group-card__pill is-soft" data-gdw-groups-summary="new">
        <?php echo esc_html__( 'Novos usuários no mês', 'gdwlang' ); ?>: <strong><?php echo esc_html( count( $new_user_cards ) ); ?></strong>
      </span>
      <span class="gdw-group-card__pill is-soft" data-gdw-groups-summary="pending">
        <?php echo esc_html__( 'Grupos aguardando ação', 'gdwlang' ); ?>: <strong><?php echo esc_html( count( $pending_groups ) ); ?></strong>
      </span>
      <span class="gdw-group-card__pill is-soft" data-gdw-groups-summary="recent">
        <?php echo esc_html__( 'Transações listadas', 'gdwlang' ); ?>: <strong><?php echo esc_html( count( $recent_orders ) ); ?></strong>
      </span>
    </div>
  </a>
</div>
