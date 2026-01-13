<?php
/**
 * Central de Mensagens renderizada via shortcode.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="juntaplay-card">
    <?php echo do_shortcode('[juntaplay_chatonline]'); ?>
</div>
