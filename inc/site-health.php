<?php
if (!defined('ABSPATH')) exit;

function fc_debug_info() {
  if (!current_user_can('manage_options')) return '';
  $map = fc_get_city_list();
  $templates = fc_get_templates();
  $logs_count = count(fc_get_logs());
  return [
    'total_cities' => array_sum(array_map('count', $map)),
    'states' => array_keys($map),
    'templates_configured' => !empty($templates),
    'logs_count' => $logs_count,
    'version' => FC_VERSION
  ];
}

add_filter('debug_information', function($info) {
  $debug = fc_debug_info();
  if ($debug) {
    $info['forcoder-city-landings'] = [
      'label' => 'Forcoder City Landings',
      'fields' => [
        'total_cities' => ['label'=>'Total de cidades', 'value'=>$debug['total_cities']],
        'states'        => ['label'=>'Estados configurados', 'value'=>implode(', ', $debug['states'])],
        'templates'     => ['label'=>'Templates configurados', 'value'=>$debug['templates_configured'] ? 'Sim' : 'Não'],
        'logs'          => ['label'=>'Logs registrados', 'value'=>$debug['logs_count']],
        'version'       => ['label'=>'Versão', 'value'=>$debug['version']],
      ]
    ];
  }
  return $info;
});
