<?php
if (!defined('ABSPATH')) exit;

if (defined('WP_CLI') && WP_CLI) {

  WP_CLI::add_command('forcoder landings generate', function($args, $assoc_args) {
    $states = isset($assoc_args['states']) ? explode(',', $assoc_args['states']) : ['RS','SC'];
    $count = fc_generate_landings($states);
    WP_CLI::success("Geradas/atualizadas {$count} páginas de landing.");
    fc_log("WP-CLI: Geradas {$count} landings para estados: " . implode(',', $states));
  });

  WP_CLI::add_command('forcoder indexes generate', function($args, $assoc_args) {
    $states = isset($assoc_args['states']) ? explode(',', $assoc_args['states']) : ['RS','SC'];
    $ids = fc_generate_indexes($states);
    WP_CLI::success("Páginas-índice criadas/atualizadas para: " . implode(', ', array_keys($ids)));
    fc_log("WP-CLI: Índices gerados para estados: " . implode(',', $states));
  });

  WP_CLI::add_command('forcoder cities import', function($args, $assoc_args) {
    if (empty($args[0])) { WP_CLI::error("Informe o arquivo CSV para importação."); return; }
    $file = $args[0];
    if (!file_exists($file)) { WP_CLI::error("Arquivo não encontrado: {$file}"); return; }

    $content = file_get_contents($file);
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $data = fc_get_city_list(); $imported = 0;

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || strpos($line, 'cidade') === 0) continue;
      if (strpos($line, ',') !== false) {
        [$city,$state] = array_map('trim', explode(',', $line, 2));
        $state = strtoupper($state);
      } else { $city = $line; $state = 'RS'; }
      $data[$state] = $data[$state] ?? [];
      if (!in_array($city, $data[$state], true)) { $data[$state][] = $city; $imported++; }
    }

    fc_set_city_list($data);
    WP_CLI::success("Importadas {$imported} cidades do arquivo {$file}");
    fc_log("WP-CLI: Importadas {$imported} cidades de arquivo CSV");
  });

  WP_CLI::add_command('forcoder cities list', function($args, $assoc_args) {
    $map = fc_get_city_list();
    $format = $assoc_args['format'] ?? 'table';
    $items = [];
    foreach ($map as $state => $cities) {
      foreach ($cities as $city) {
        $slug = fc_target_slug($city);
        $page = get_page_by_path($slug, OBJECT, 'page');
        $items[] = ['city'=>$city,'state'=>$state,'slug'=>$slug,'status'=>$page?'Criada':'Não criada'];
      }
    }
    WP_CLI\Utils\format_items($format, $items, ['city','state','slug','status']);
  });

  WP_CLI::add_command('forcoder template update', function($args, $assoc_args) {
    if (count($args) < 3) {
      WP_CLI::error("Uso: wp forcoder template update <tipo> <campo> <valor>");
      WP_CLI::line("Tipos: landing, index"); WP_CLI::line("Campos: title, content, meta_title, meta_description");
      return;
    }
    [$type, $field, $value] = $args;
    if (!in_array($type, ['landing','index'], true)) { WP_CLI::error("Tipo deve ser 'landing' ou 'index'"); return; }
    if (!in_array($field, ['title','content','meta_title','meta_description'], true)) { WP_CLI::error("Campo inválido"); return; }
    fc_update_template($type, $field, $value);
    WP_CLI::success("Template {$type}.{$field} atualizado");
    fc_log("WP-CLI: Template atualizado - {$type}.{$field}");
  });

}
