<?php
if (!defined('ABSPATH')) exit;

// Quick preview dos templates com placeholders
add_action('wp_ajax_fc_quick_preview', function() {
  check_ajax_referer('fc_ajax_nonce', 'nonce');

  $city  = sanitize_text_field($_POST['city'] ?? '');
  $state = sanitize_text_field($_POST['state'] ?? '');
  $type  = sanitize_text_field($_POST['type'] ?? 'landing');

  if (!$city || !$state) wp_die('Parâmetros inválidos');

  $templates = fc_get_templates();
  $template  = $templates[$type] ?? [];

  $content = [
    'title'   => fc_replace_placeholders($template['title'] ?? '',   $city, $state, $type),
    'content' => fc_replace_placeholders($template['content'] ?? '', $city, $state, $type),
  ];

  wp_send_json_success($content);
});

// Injeta nonce para AJAX
add_action('admin_footer', function(){
  if (fc_is_admin_page()) {
    echo '<script>window.fc_ajax_nonce = "'. esc_js( wp_create_nonce('fc_ajax_nonce') ) .'";</script>';
  }
});
