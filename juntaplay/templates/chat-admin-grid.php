<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="jp-admin-grid">
   <?php foreach ($members as $m): ?>
      <div class="jp-admin-card">
         <img class="avatar" src="<?php echo esc_url($m['avatar']); ?>" />
         <div class="name"><?php echo esc_html($m['name']); ?></div>
         <div class="group"><?php echo esc_html($m['group']); ?></div>
         <a class="open" href="/perfil/?section=juntaplay-chat&group_id=<?php echo $group_id; ?>&participant_id=<?php echo $m['id']; ?>">
            Abrir conversa
         </a>
      </div>
   <?php endforeach; ?>
</div>
