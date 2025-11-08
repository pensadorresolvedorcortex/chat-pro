<?php
/**
 * Profile network connections template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$connections        = isset($connections) && is_array($connections) ? $connections : [];
$connections_total  = isset($connections_total) ? (int) $connections_total : count($connections);
$groups_total       = isset($groups_total) ? (int) $groups_total : 0;
?>
<div class="juntaplay-network" data-profile-network>
    <header class="juntaplay-network__header">
        <div class="juntaplay-network__stat">
            <span class="juntaplay-network__stat-label"><?php esc_html_e('Conexões', 'juntaplay'); ?></span>
            <span class="juntaplay-network__stat-value"><?php echo esc_html(number_format_i18n($connections_total)); ?></span>
        </div>
        <div class="juntaplay-network__stat">
            <span class="juntaplay-network__stat-label"><?php esc_html_e('Grupos em comum', 'juntaplay'); ?></span>
            <span class="juntaplay-network__stat-value"><?php echo esc_html(number_format_i18n($groups_total)); ?></span>
        </div>
    </header>

    <?php if ($connections) : ?>
        <ul class="juntaplay-network__list" role="list">
            <?php foreach ($connections as $connection) :
                $name          = isset($connection['name']) ? (string) $connection['name'] : '';
                $avatar        = isset($connection['avatar']) ? (string) $connection['avatar'] : '';
                $initials      = isset($connection['initials']) ? (string) $connection['initials'] : '';
                $groups        = isset($connection['groups']) && is_array($connection['groups']) ? $connection['groups'] : [];
                $groupsPreview = isset($connection['groups_preview']) && is_array($connection['groups_preview']) ? $connection['groups_preview'] : [];
                $groupsMore    = isset($connection['groups_more']) ? (int) $connection['groups_more'] : 0;
                $groupsCount   = isset($connection['groups_count']) ? (int) $connection['groups_count'] : count($groups);
                ?>
                <li class="juntaplay-network__item">
                    <article class="juntaplay-network-card">
                        <div class="juntaplay-network-card__header">
                            <div class="juntaplay-network-card__avatar">
                                <?php if ($avatar !== '') : ?>
                                    <img src="<?php echo esc_url($avatar); ?>" alt="" loading="lazy" />
                                <?php else : ?>
                                    <span><?php echo esc_html($initials); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="juntaplay-network-card__info">
                                <h3><?php echo esc_html($name); ?></h3>
                                <span class="juntaplay-network-card__count">
                                    <?php
                                    printf(
                                        _n('%d grupo em comum', '%d grupos em comum', $groupsCount, 'juntaplay'),
                                        $groupsCount
                                    );
                                    ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($groupsPreview) : ?>
                            <ul class="juntaplay-network-card__preview" role="list">
                                <?php foreach ($groupsPreview as $preview) : ?>
                                    <li><?php echo esc_html((string) $preview); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ($groupsMore > 0) : ?>
                            <p class="juntaplay-network-card__more">
                                <?php
                                printf(
                                    _n('mais %d grupo em comum', 'mais %d grupos em comum', $groupsMore, 'juntaplay'),
                                    $groupsMore
                                );
                                ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($groups) : ?>
                            <button
                                type="button"
                                class="juntaplay-network-card__toggle"
                                data-network-detail
                                data-default-label="<?php echo esc_attr__('Ver detalhes', 'juntaplay'); ?>"
                                data-open-label="<?php echo esc_attr__('Ocultar detalhes', 'juntaplay'); ?>"
                            >
                                <?php esc_html_e('Ver detalhes', 'juntaplay'); ?>
                            </button>
                            <div class="juntaplay-network-card__drawer" data-network-groups hidden>
                                <ul role="list">
                                    <?php foreach ($groups as $group_meta) :
                                        $group_title = isset($group_meta['title']) ? (string) $group_meta['title'] : '';
                                        $group_role  = isset($group_meta['role']) ? (string) $group_meta['role'] : '';
                                        $group_link  = isset($group_meta['link']) ? (string) $group_meta['link'] : '';
                                        $group_status = isset($group_meta['status']) ? (string) $group_meta['status'] : '';
                                        ?>
                                        <li>
                                            <div class="juntaplay-network-card__drawer-item">
                                                <span class="juntaplay-network-card__drawer-title"><?php echo esc_html($group_title); ?></span>
                                                <?php if ($group_status !== '') : ?>
                                                    <span class="juntaplay-network-card__drawer-status"><?php echo esc_html($group_status); ?></span>
                                                <?php endif; ?>
                                                <span class="juntaplay-network-card__drawer-role"><?php echo esc_html($group_role); ?></span>
                                                <?php if ($group_link !== '') : ?>
                                                    <a class="juntaplay-network-card__drawer-link" href="<?php echo esc_url($group_link); ?>" target="_blank" rel="noopener noreferrer">
                                                        <?php esc_html_e('Ver campanha', 'juntaplay'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="juntaplay-network__empty"><?php esc_html_e('Você ainda não possui conexões. Participe de um grupo para conhecer novos membros.', 'juntaplay'); ?></p>
    <?php endif; ?>
</div>
