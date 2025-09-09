<?php
if (!defined('ABSPATH')) exit;

/** ===== Defaults na ativação ===== */
function fc_bootstrap_defaults() {
  if (!get_option(FC_OPT_KEY)) {
    update_option(FC_OPT_KEY, [
      'RS' => ['Porto Alegre','Caxias do Sul','Alvorada','Canoas'],
      'SC' => ['Joinville','Florianópolis','Blumenau','Chapecó'],
    ], false);
  }
  if (!get_option(FC_IDX_KEY)) update_option(FC_IDX_KEY, [], false);

  if (!get_option(FC_TPL_KEY)) {
    $defaultTemplates = [
      'landing' => [
        'title' => 'Desenvolvimento de Sistemas em {city} – {state}',
        'content' => '<h1>Desenvolvimento de Sistemas em {city} – {state}</h1>
<p><strong>Forcoder</strong> desenvolve sistemas web e apps com foco em performance, segurança e prazo — atendendo empresas em <strong>{city}/{state}</strong>.</p>

<h2>O que entregamos</h2>
<ul>
  <li>MVP, portais e APIs sob medida</li>
  <li>Integrações (pagamentos, ERPs, CRMs)</li>
  <li>DevOps (Docker, CI/CD, observabilidade)</li>
  <li>Escalabilidade e manutenção contínua</li>
</ul>

<h2>Por que escolher a gente em {city}?</h2>
<p>Equipe sênior, comunicação transparente e foco em resultado. Atuação remota com SLA e contrato.</p>

<p><a class="button button-primary" href="{contact_url}">Fale conosco</a></p>
<hr>
<p><small>Página gerada automaticamente. Conteúdo atualizado periodicamente.</small></p>',
        'meta_title' => 'Desenvolvimento de Sistemas em {city} – {state} | Forcoder',
        'meta_description' => 'Soluções em software sob medida em {city}/{state}. Desenvolvimento web, apps e sistemas personalizados com qualidade e agilidade.'
      ],
      'index' => [
        'title' => 'Desenvolvimento de Sistemas {state_full}',
        'content' => '<h1>Desenvolvimento de Sistemas {state_full}</h1>
<p>Nesta página você encontra as principais cidades atendidas pela Forcoder no estado {state_full}. Escolha sua cidade para conhecer nossos serviços locais.</p>
<h2>Cidades atendidas</h2>
{cities_list}',
        'meta_title' => 'Desenvolvimento de Sistemas {state_full} | Forcoder',
        'meta_description' => 'Cidades atendidas pela Forcoder {state_full}. Desenvolvimento de sistemas personalizados em todo o estado.'
      ]
    ];
    update_option(FC_TPL_KEY, $defaultTemplates, false);
  }
}

/** ===== Logs ===== */
function fc_log($msg) {
  $logs = get_option(FC_LOG_KEY, []);
  $ts = current_time('mysql');
  $logs[] = "[$ts] $msg";
  if (count($logs) > 500) $logs = array_slice($logs, -500);
  update_option(FC_LOG_KEY, $logs, false);
}
function fc_get_logs(){ return get_option(FC_LOG_KEY, []); }
function fc_clear_logs(){ update_option(FC_LOG_KEY, [], false); }

/** ===== Cidades ===== */
function fc_get_city_list(): array {
  $data = get_option(FC_OPT_KEY, []);
  return is_array($data) ? $data : [];
}
function fc_set_city_list(array $data): void {
  $clean = [];
  foreach ($data as $uf => $cities) {
    $uf = strtoupper(sanitize_text_field($uf));
    if (!isset($clean[$uf])) $clean[$uf] = [];
    foreach ((array)$cities as $city) {
      $city = trim((string)$city);
      if ($city === '') continue;
      if (!in_array($city, $clean[$uf], true)) $clean[$uf][] = $city;
    }
    sort($clean[$uf], SORT_NATURAL|SORT_FLAG_CASE);
  }
  update_option(FC_OPT_KEY, $clean, false);
}

/** ===== Templates ===== */
function fc_get_templates(){ return get_option(FC_TPL_KEY, []); }
function fc_update_template($type, $field, $value){
  $templates = fc_get_templates();
  $templates[$type][$field] = $value;
  update_option(FC_TPL_KEY, $templates, false);
}

/** ===== Helpers ===== */
function fc_slugify($str) {
  $str = remove_accents($str);
  $str = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $str));
  return trim($str, '-');
}
function fc_target_slug(string $city): string {
  return 'desenvolvimento-de-sistemas-em-' . fc_slugify($city);
}
function fc_index_slug(string $state): string {
  $label = $state === 'RS' ? 'no-rio-grande-do-sul' : 'em-santa-catarina';
  return 'desenvolvimento-de-sistemas-' . $label;
}
function fc_get_state_name($state){
  return $state === 'RS' ? 'no Rio Grande do Sul' : 'em Santa Catarina';
}
function fc_get_state_full($state){
  return $state === 'RS' ? 'Rio Grande do Sul' : 'Santa Catarina';
}

/** ===== Placeholders ===== */
function fc_replace_placeholders($content, $city, $state, $type='landing'){
  $replacements = [
    '{city}'        => esc_html($city),
    '{state}'       => esc_html($state),
    '{state_full}'  => fc_get_state_full($state),
    '{contact_url}' => esc_url(site_url('/contato')),
    '{site_url}'    => esc_url(site_url()),
    '{home_url}'    => esc_url(home_url()),
  ];

  if ($type === 'index') {
    $map = fc_get_city_list();
    $cities = $map[$state] ?? [];
    $items = '';
    foreach ($cities as $cityItem) {
      $slug = fc_target_slug($cityItem);
      $url  = esc_url(home_url('/'.$slug.'/'));
      $cityEsc = esc_html($cityItem);
      $items .= "<li><a href=\"{$url}\">Desenvolvimento de sistemas em {$cityEsc}</a></li>";
    }
    $replacements['{cities_list}'] = "<ul>{$items}</ul>";
  }

  return str_replace(array_keys($replacements), array_values($replacements), $content);
}
