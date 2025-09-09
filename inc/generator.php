<?php
if (!defined('ABSPATH')) exit;

/** ===== Conteúdo em blocos (Gutenberg) ===== */
function fc_blockify_landing($city, $state, $tpl) {
  $title   = fc_replace_placeholders($tpl['title'] ?? '',  $city, $state, 'landing');
  $content = fc_replace_placeholders($tpl['content'] ?? '', $city, $state, 'landing');

  $out  = "<!-- wp:heading {\"level\":1} -->\n<h1>".esc_html($title)."</h1>\n<!-- /wp:heading -->\n";
  $out .= "<!-- wp:paragraph -->\n<p>Atendemos <strong>".esc_html($city)."/".esc_html($state)."</strong> com soluções sob medida.</p>\n<!-- /wp:paragraph -->\n";
  $out .= "<!-- wp:group --><div class=\"wp-block-group\">".wp_kses_post($content)."</div><!-- /wp:group -->\n";
  return $out;
}

/** ===== Template de página (Hello Elementor) ===== */
function fc_apply_page_template($post_id) {
  // Se o tema tiver os templates do Hello Elementor, aplicamos "elementor_full_width"
  update_post_meta($post_id, '_wp_page_template', 'elementor_full_width');
}

/** ===== Preparar para Elementor (opcional) ===== */
function fc_prepare_for_elementor($post_id) {
  if (class_exists('\Elementor\Plugin')) {
    update_post_meta($post_id, '_elementor_edit_mode', 'builder');
    update_post_meta($post_id, '_elementor_template_type', 'wp-page');
    // Você pode setar _elementor_data se quiser começar com um layout específico.
  }
}

/** ===== Create/Update Landing ===== */
function fc_create_or_update_page(string $city, string $state) {
  $templates = fc_get_templates();
  $landingTpl = $templates['landing'] ?? [];

  $slug  = fc_target_slug($city);
  $title = fc_replace_placeholders($landingTpl['title'] ?? "Desenvolvimento de Sistemas em {city} – {state}", $city, $state);

  // Gera conteúdo em BLOCOS (Gutenberg)
  $content_blocks = fc_blockify_landing($city, $state, $landingTpl);

  $existing = get_page_by_path($slug, OBJECT, 'page');
  $postarr = [
    'post_title'   => $title,
    'post_name'    => $slug,
    'post_type'    => 'page',
    'post_status'  => 'publish',
    'post_content' => $content_blocks,
  ];

  if ($existing) {
    $postarr['ID'] = $existing->ID;
    $post_id = wp_update_post($postarr, true);
    fc_log("Atualizada landing: {$city}/{$state} (ID {$post_id})");
  } else {
    $post_id = wp_insert_post($postarr, true);
    fc_log("Criada landing: {$city}/{$state} (ID {$post_id})");
  }

  if (is_wp_error($post_id)) {
    fc_log('ERRO landing '.$city.'/'.$state.': '.$post_id->get_error_message());
    return $post_id;
  }

  update_post_meta($post_id, '_fc_city',  $city);
  update_post_meta($post_id, '_fc_state', $state);

  // Template compatível com Hello Elementor
  fc_apply_page_template($post_id);
  // Se Elementor estiver ativo, marca a página para ser editada por ele
  fc_prepare_for_elementor($post_id);

  // Yoast SEO
  if (defined('WPSEO_VERSION')) {
    $metaTitle = fc_replace_placeholders($landingTpl['meta_title'] ?? '', $city, $state);
    $metaDesc  = fc_replace_placeholders($landingTpl['meta_description'] ?? '', $city, $state);
    if ($metaTitle) update_post_meta($post_id, '_yoast_wpseo_title',   $metaTitle);
    if ($metaDesc)  update_post_meta($post_id, '_yoast_wpseo_metadesc', $metaDesc);
  }

  return $post_id;
}

/** ===== Geração em lote ===== */
function fc_generate_landings(array $states = ['RS','SC']): int {
  $map = fc_get_city_list();
  $count = 0;
  foreach ($states as $uf) {
    $uf = strtoupper(trim($uf));
    if (empty($map[$uf])) continue;
    foreach ($map[$uf] as $city) {
      $res = fc_create_or_update_page($city, $uf);
      if (!is_wp_error($res)) $count++;
    }
  }
  flush_rewrite_rules(false);
  fc_log("Conclusão: geradas/atualizadas {$count} landings para UF(s): ".implode(',', $states));
  return $count;
}

/** ===== Índice por estado ===== */
function fc_create_or_update_index(string $state) {
  $templates = fc_get_templates();
  $indexTpl = $templates['index'] ?? [];

  $slug    = fc_index_slug($state);
  $title   = fc_replace_placeholders($indexTpl['title'] ?? '', '', $state, 'index');
  $content = fc_replace_placeholders($indexTpl['content'] ?? '', '', $state, 'index');

  $existing = get_page_by_path($slug, OBJECT, 'page');
  $postarr = [
    'post_title'   => $title,
    'post_name'    => $slug,
    'post_type'    => 'page',
    'post_status'  => 'publish',
    'post_content' => wp_kses_post($content),
  ];

  if ($existing) {
    $postarr['ID'] = $existing->ID;
    $post_id = wp_update_post($postarr, true);
    fc_log("Atualizada página-índice do estado {$state} (ID {$post_id})");
  } else {
    $post_id = wp_insert_post($postarr, true);
    fc_log("Criada página-índice do estado {$state} (ID {$post_id})");
  }

  if (is_wp_error($post_id)) {
    fc_log('ERRO index '.$state.': '.$post_id->get_error_message());
    return $post_id;
  }

  // Yoast
  if (defined('WPSEO_VERSION')) {
    $metaTitle = fc_replace_placeholders($indexTpl['meta_title'] ?? '', '', $state, 'index');
    $metaDesc  = fc_replace_placeholders($indexTpl['meta_description'] ?? '', '', $state, 'index');
    if ($metaTitle) update_post_meta($post_id, '_yoast_wpseo_title',   $metaTitle);
    if ($metaDesc)  update_post_meta($post_id, '_yoast_wpseo_metadesc', $metaDesc);
  }

  // Guarda ID do índice
  $idx = get_option(FC_IDX_KEY, []);
  $idx[$state] = $post_id;
  update_option(FC_IDX_KEY, $idx, false);

  return $post_id;
}

function fc_generate_indexes(array $states = ['RS','SC']): array {
  $ids = [];
  foreach ($states as $uf) {
    $res = fc_create_or_update_index($uf);
    if (!is_wp_error($res)) $ids[$uf] = $res;
  }
  flush_rewrite_rules(false);
  return $ids;
}
