<?php
/**
 * JuntaPlay thank you message for group purchases.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$groups           = isset($groups) && is_array($groups) ? array_values($groups) : [];
$my_groups_url    = isset($my_groups_url) ? (string) $my_groups_url : '';
$help_url         = isset($help_url) ? (string) $help_url : '';
$illustration_url = isset($illustration_url) ? (string) $illustration_url : '';

$groups = array_map(
    static function (array $group): array {
        return [
            'name'   => isset($group['name']) ? (string) $group['name'] : '',
            'quotas' => isset($group['quotas']) ? (string) $group['quotas'] : '',
        ];
    },
    $groups
);
?>
<section class="juntaplay-thankyou" aria-labelledby="juntaplay-thankyou-title">
    <?php if ($illustration_url !== '') : ?>
        <div class="juntaplay-thankyou__media" aria-hidden="true">
            <img src="<?php echo esc_url($illustration_url); ?>" alt="" loading="lazy" />
        </div>
    <?php endif; ?>
    <div class="juntaplay-thankyou__content">
        <header class="juntaplay-thankyou__header">
            <h2 id="juntaplay-thankyou-title"><?php esc_html_e('Pagamento confirmado! Bem-vindo ao seu novo grupo.', 'juntaplay'); ?></h2>
            <p><?php esc_html_e('Estamos preparando o acesso e você receberá todas as orientações do administrador no e-mail cadastrado.', 'juntaplay'); ?></p>
        </header>

        <?php if ($groups) : ?>
            <ul class="juntaplay-thankyou__list" role="list">
                <?php foreach ($groups as $group) :
                    $name   = $group['name'];
                    $quotas = $group['quotas'];
                    ?>
                    <li class="juntaplay-thankyou__item">
                        <span class="juntaplay-thankyou__item-name"><?php echo esc_html($name !== '' ? $name : __('Grupo confirmado', 'juntaplay')); ?></span>
                        <?php if ($quotas !== '') : ?>
                            <span class="juntaplay-thankyou__item-meta"><?php echo esc_html(sprintf(__('Cotas selecionadas: %s', 'juntaplay'), $quotas)); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p class="juntaplay-thankyou__footnote"><?php esc_html_e('Se não encontrar o e-mail em alguns minutos, verifique a caixa de spam ou acesse o painel para acompanhar o andamento.', 'juntaplay'); ?></p>

        <div class="juntaplay-thankyou__actions">
            <?php if ($my_groups_url !== '') : ?>
                <a class="juntaplay-button juntaplay-button--primary" href="<?php echo esc_url($my_groups_url); ?>">
                    <?php esc_html_e('Ir para Meus Grupos', 'juntaplay'); ?>
                </a>
            <?php endif; ?>
            <?php if ($help_url !== '') : ?>
                <a class="juntaplay-button juntaplay-button--ghost" href="<?php echo esc_url($help_url); ?>">
                    <?php esc_html_e('Central de ajuda', 'juntaplay'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php if ($my_groups_url !== '') : ?>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.location.href = <?php echo wp_json_encode($my_groups_url); ?>;
            }, 5000);
        });
    </script>
<?php endif; ?>
