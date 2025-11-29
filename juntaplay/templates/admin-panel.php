<?php
declare(strict_types=1);
?>
<section class="juntaplay-admin-panel">
    <h2><?php esc_html_e('Painel Operacional', 'juntaplay'); ?></h2>
    <ul>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=juntaplay')); ?>"><?php esc_html_e('Dashboard', 'juntaplay'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=juntaplay-import')); ?>"><?php esc_html_e('Importar Campanhas', 'juntaplay'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=juntaplay-settings')); ?>"><?php esc_html_e('Configurações', 'juntaplay'); ?></a></li>
    </ul>
</section>
