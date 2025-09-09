<?php
/**
 * Plugin Name: Forcoder City Landings Pro
 * Description: Gerador avançado de landings por cidade com templates editáveis, bulk actions e interface moderna.
 * Version: 2.0.0
 * Author: Forcoder
 */

if (!defined('ABSPATH')) exit;

define('FC_VERSION', '2.0.0');
define('FC_OPT_KEY', 'fc_city_list_opt');
define('FC_LOG_KEY', 'fc_city_landings_log');
define('FC_IDX_KEY', 'fc_city_landings_indexes');
define('FC_TPL_KEY', 'fc_city_templates');

// === Includes (ordem importa) ===
require_once plugin_dir_path(__FILE__) . 'inc/templates.php';     // helpers + defaults + placeholders
require_once plugin_dir_path(__FILE__) . 'inc/generator.php';     // criação/atualização de páginas
require_once plugin_dir_path(__FILE__) . 'inc/admin-ui.php';      // telas do admin (painel + templates)
require_once plugin_dir_path(__FILE__) . 'inc/assets.php';        // CSS/JS do admin
require_once plugin_dir_path(__FILE__) . 'inc/ajax.php';          // ajax (quick preview)
require_once plugin_dir_path(__FILE__) . 'inc/cli.php';           // wp-cli
require_once plugin_dir_path(__FILE__) . 'inc/site-health.php';   // site health

// === Ativação/Desativação ===
register_activation_hook(__FILE__, function () {
  fc_bootstrap_defaults();
  flush_rewrite_rules(false);
  fc_log('Plugin ativado');
});
register_deactivation_hook(__FILE__, function(){
  flush_rewrite_rules();
  fc_log("Plugin desativado - rewrite rules atualizadas");
});

// === Link "Configurar" na lista de plugins ===
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
  $url = admin_url('admin.php?page=fc-city-landings');
  array_unshift($links, '<a href="'.esc_url($url).'">Configurar</a>');
  return $links;
});
