<?php
if (!defined('ABSPATH')) exit;

/** ===== Menu ===== */
add_action('admin_menu', function(){
  add_menu_page(
    'City Landings Pro',
    'City Landings Pro',
    'manage_options',
    'fc-city-landings',
    'fc_admin_screen',
    'dashicons-location-alt',
    58
  );

  add_submenu_page(
    'fc-city-landings',
    'Templates',
    'Templates',
    'manage_options',
    'fc-templates',
    'fc_templates_screen'
  );
});

/** ===== Helper: estou na tela do plugin? ===== */
function fc_is_admin_page(): bool {
  return is_admin() && isset($_GET['page']) && in_array($_GET['page'], ['fc-city-landings', 'fc-templates'], true);
}

/** ===== Tela principal ===== */
function fc_admin_screen() {
  if (!current_user_can('manage_options')) return;

  // Processa POST
  if (!empty($_POST['fc_nonce']) && wp_verify_nonce($_POST['fc_nonce'], 'fc_actions')) {
    $action = sanitize_text_field($_POST['fc_action'] ?? '');

    if ($action === 'generate') {
      $states = array_filter(array_map('trim', explode(',', sanitize_text_field($_POST['states'] ?? 'RS,SC'))));
      $count = fc_generate_landings($states);
      add_action('admin_notices', fn()=> print '<div class="notice notice-success is-dismissible"><p>✅ '.$count.' landings geradas/atualizadas!</p></div>');
    }

    if ($action === 'generate_indexes') {
      $states = array_filter(array_map('trim', explode(',', sanitize_text_field($_POST['states_idx'] ?? 'RS,SC'))));
      fc_generate_indexes($states);
      add_action('admin_notices', fn()=> print '<div class="notice notice-success is-dismissible"><p>✅ Páginas-índice criadas/atualizadas!</p></div>');
    }

    if ($action === 'add_city') {
      $city = sanitize_text_field($_POST['city'] ?? '');
      $uf   = strtoupper(sanitize_text_field($_POST['state'] ?? ''));
      $data = fc_get_city_list();
      if ($city && $uf) {
        $data[$uf] = $data[$uf] ?? [];
        if (!in_array($city, $data[$uf], true)) {
          $data[$uf][] = $city;
          fc_set_city_list($data);
          fc_log("Adicionada cidade: {$city}/{$uf}");
          add_action('admin_notices', fn()=> print '<div class="notice notice-success is-dismissible"><p>✅ Cidade '.$city.'/'.$uf.' adicionada!</p></div>');
        }
      }
    }

    if ($action === 'del_city') {
      $city = sanitize_text_field($_POST['city'] ?? '');
      $uf   = strtoupper(sanitize_text_field($_POST['state'] ?? ''));
      $data = fc_get_city_list();
      if ($city && !empty($data[$uf])) {
        $data[$uf] = array_values(array_filter($data[$uf], fn($c)=> $c !== $city));
        fc_set_city_list($data);
        fc_log("Removida cidade: {$city}/{$uf}");
        add_action('admin_notices', fn()=> print '<div class="notice notice-warning is-dismissible"><p>🗑️ Cidade '.$city.'/'.$uf.' removida!</p></div>');
      }
    }

    if ($action === 'bulk_delete') {
      $selected = $_POST['selected_cities'] ?? [];
      $count = 0; $data = fc_get_city_list();
      foreach ($selected as $item) {
        if (strpos($item, '|') !== false) {
          [$city,$uf] = explode('|', $item, 2);
          $city = sanitize_text_field($city);
          $uf   = strtoupper(sanitize_text_field($uf));
          if (!empty($data[$uf])) {
            $data[$uf] = array_values(array_filter($data[$uf], fn($c)=> $c !== $city));
            $count++; fc_log("Removida cidade (bulk): {$city}/{$uf}");
          }
        }
      }
      if ($count>0) {
        fc_set_city_list($data);
        add_action('admin_notices', fn()=> print '<div class="notice notice-warning is-dismissible"><p>🗑️ '.$count.' cidades removidas!</p></div>');
      }
    }

    if ($action === 'import') {
      $bulk  = (string)($_POST['bulk'] ?? '');
      $ufSel = strtoupper(sanitize_text_field($_POST['bulk_state'] ?? ''));
      $data  = fc_get_city_list();
      $lines = preg_split('/\r\n|\r|\n/', $bulk);
      $imported = 0;
      foreach ($lines as $line) {
        $line = trim($line); if ($line==='') continue;
        if (strpos($line, ',') !== false) {
          [$c,$u] = array_map('trim', explode(',', $line, 2));
          $u = strtoupper($u);
        } else {
          $c = $line; $u = $ufSel ?: 'RS';
        }
        $data[$u] = $data[$u] ?? [];
        if (!in_array($c, $data[$u], true)) { $data[$u][] = $c; $imported++; }
      }
      fc_set_city_list($data);
      fc_log("Importação em lote concluída: {$imported} cidades.");
      add_action('admin_notices', fn()=> print '<div class="notice notice-success is-dismissible"><p>📥 '.$imported.' cidades importadas!</p></div>');
    }

    if ($action === 'clear_logs') {
      fc_clear_logs();
      add_action('admin_notices', fn()=> print '<div class="notice notice-warning is-dismissible"><p>🧹 Logs limpos!</p></div>');
    }
  }

  $map = fc_get_city_list();
  $idx = get_option(FC_IDX_KEY, []);
  $counts = [
    'RS_total' => isset($map['RS']) ? count($map['RS']) : 0,
    'SC_total' => isset($map['SC']) ? count($map['SC']) : 0,
  ];
  $totalCidades = $counts['RS_total'] + $counts['SC_total'];

  echo '<div class="wrap fc-wrap">';
  ?>
  <!-- Hero -->
  <div class="fc-hero">
    <div>
      <div class="fc-chips" style="margin-bottom:12px;">
        <span class="fc-chip">🚀 Forcoder City Landings Pro</span>
        <span class="fc-chip">v<?php echo esc_html(FC_VERSION); ?></span>
        <span class="fc-chip">✨ Templates Editáveis</span>
        <span class="fc-chip">⚡ Bulk Actions</span>
        <span class="fc-chip">🎯 Yoast Ready</span>
      </div>
      <h1>Gerador Avançado de Landings</h1>
      <p class="fc-muted">Crie e mantenha páginas locais com templates personalizáveis, ações em lote e interface moderna.</p>
      <div class="fc-actions">
        <a href="#fc-acao-gerar" class="fc-btn fc-primary"><span class="dashicons dashicons-hammer"></span> Gerar/Atualizar Landings</a>
        <a href="<?php echo admin_url('admin.php?page=fc-templates'); ?>" class="fc-btn"><span class="dashicons dashicons-edit"></span> Editar Templates</a>
        <a href="#fc-acao-indices" class="fc-btn"><span class="dashicons dashicons-index-card"></span> Índices por Estado</a>
        <a href="#fc-logs" class="fc-btn"><span class="dashicons dashicons-clipboard"></span> Ver Logs</a>
      </div>
    </div>
    <div class="fc-kpis">
      <div class="fc-kpi"><h3>Total Cidades</h3><strong><?php echo esc_html($totalCidades); ?></strong></div>
      <div class="fc-kpi"><h3>RS</h3><strong><?php echo esc_html($counts['RS_total']); ?></strong></div>
      <div class="fc-kpi"><h3>SC</h3><strong><?php echo esc_html($counts['SC_total']); ?></strong></div>
    </div>
  </div>

  <!-- Grid -->
  <div class="fc-grid">
    <div class="fc-card" id="fc-acao-gerar">
      <h2>🔧 Gerar / Atualizar Landings</h2>
      <form method="post" style="margin:12px 0">
        <?php wp_nonce_field('fc_actions', 'fc_nonce'); ?>
        <input type="hidden" name="fc_action" value="generate">
        <p>
          <label><strong>Estados:</strong></label><br>
          <input type="text" name="states" value="RS,SC" style="width:200px; padding:8px; border-radius:6px;">
          <button class="button button-primary" style="margin-left:8px;"><span class="dashicons dashicons-hammer"></span> Executar</button>
        </p>
        <p class="fc-muted">💡 Cria/atualiza páginas <code>/desenvolvimento-de-sistemas-em-&lt;cidade&gt;</code>.</p>
      </form>

      <hr style="margin:20px 0; opacity:0.3;">

      <h2 id="fc-acao-indices">📋 Páginas-Índice por Estado</h2>
      <form method="post" style="margin:12px 0">
        <?php wp_nonce_field('fc_actions', 'fc_nonce'); ?>
        <input type="hidden" name="fc_action" value="generate_indexes">
        <p>
          <label><strong>Estados:</strong></label><br>
          <input type="text" name="states_idx" value="RS,SC" style="width:200px; padding:8px; border-radius:6px;">
          <button class="button" style="margin-left:8px;"><span class="dashicons dashicons-index-card"></span> Criar/Atualizar</button>
        </p>
        <p class="fc-muted">📝 Gera páginas com links para todas as cidades do estado.</p>
      </form>

      <h3>📊 Status dos Índices</h3>
      <div class="fc-chips">
        <?php foreach (['RS','SC'] as $state): ?>
          <div class="fc-chip <?php echo (!empty($idx[$state]) && get_post_status($idx[$state])) ? 'fc-state-ok' : 'fc-state-missing'; ?>">
            <strong><?php echo $state; ?>:</strong>
            <?php if (!empty($idx[$state]) && get_post_status($idx[$state])): ?>
              <a href="<?php echo esc_url(get_permalink($idx[$state])); ?>" target="_blank">Ver página</a> ·
              <a href="<?php echo esc_url(get_edit_post_link($idx[$state])); ?>">Editar</a>
            <?php else: ?>
              <span class="fc-muted">ainda não criada</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="fc-card">
      <h2>📥 Importar / Adicionar Cidades</h2>

      <details open style="margin-bottom:20px;">
        <summary style="cursor:pointer; font-weight:600; color:var(--fc-indigo);"><strong>📋 Importar em Lote</strong></summary>
        <form method="post" style="margin-top:12px">
          <?php wp_nonce_field('fc_actions', 'fc_nonce'); ?>
          <input type="hidden" name="fc_action" value="import">
          <p>
            <label><strong>UF padrão (se linha não tiver UF):</strong></label><br>
            <select name="bulk_state" style="padding:6px; border-radius:6px;">
              <option value="RS">RS - Rio Grande do Sul</option>
              <option value="SC">SC - Santa Catarina</option>
            </select>
          </p>
          <textarea name="bulk" rows="6" style="width:100%; padding:12px; border-radius:8px; border:2px dashed #ccc;" placeholder="Exemplos:
Porto Alegre,RS
Joinville,SC
Alvorada
Canoas"></textarea>
          <p><button class="button button-primary"><span class="dashicons dashicons-upload"></span> Importar Cidades</button></p>
        </form>
      </details>

      <h3>➕ Adicionar Cidade Individual</h3>
      <form method="post" style="display:flex; gap:8px; align-items:end; flex-wrap:wrap; margin-bottom:16px;">
        <?php wp_nonce_field('fc_actions', 'fc_nonce'); ?>
        <input type="hidden" name="fc_action" value="add_city">
        <div>
          <label style="font-size:12px; display:block; margin-bottom:4px;"><strong>Cidade:</strong></label>
          <input type="text" name="city" placeholder="Nome da cidade" required style="padding:8px; border-radius:6px;">
        </div>
        <div>
          <label style="font-size:12px; display:block; margin-bottom:4px;"><strong>Estado:</strong></label>
          <select name="state" style="padding:8px; border-radius:6px;">
            <option value="RS">RS</option>
            <option value="SC">SC</option>
          </select>
        </div>
        <button class="button button-primary"><span class="dashicons dashicons-plus-alt2"></span> Adicionar</button>
      </form>

      <div style="background:rgba(99,102,241,0.1); padding:12px; border-radius:8px; font-size:14px;">
        📊 <strong>Resumo:</strong> RS: <strong><?php echo esc_html($counts['RS_total']); ?></strong> · SC: <strong><?php echo esc_html($counts['SC_total']); ?></strong>
      </div>
    </div>
  </div>

  <!-- Lista por UF -->
  <div class="fc-card" style="margin-top:24px;">
    <h2>🏙️ Lista de Cidades & Status das Páginas</h2>
    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin:16px 0;">
      <input type="search" id="fc-filter" placeholder="🔍 Filtrar cidade..." style="width:280px; padding:10px; border-radius:8px; border:2px solid #e2e8f0;">
      <span class="fc-muted">💡 Dica: Use as checkboxes para ações em lote</span>
    </div>

    <div class="fc-bulk-actions fc-bulk">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <div><span style="font-weight:600;">🎯 <span id="fc-selected-count">0</span> cidades selecionadas</span></div>
        <div><button type="button" id="fc-bulk-delete" class="fc-btn fc-danger"><span class="dashicons dashicons-trash"></span> Remover Selecionadas</button></div>
      </div>
    </div>

    <?php foreach (['RS','SC'] as $uf): $cities = $map[$uf] ?? []; ?>
      <h3 style="margin-top:24px; color:var(--fc-indigo);">
        🌟 <?php echo $uf === 'RS' ? 'Rio Grande do Sul' : 'Santa Catarina'; ?>
        <span style="font-size:14px; color:var(--fc-muted);">(<?php echo count($cities); ?> cidades)</span>
      </h3>
      <table class="widefat striped fc-table fc-cities">
        <thead>
          <tr>
            <th style="width:40px;"><input type="checkbox" id="fc-select-all"></th>
            <th style="width:30%;">Cidade</th>
            <th>Slug da Página</th>
            <th>Status da Página</th>
            <th style="width:200px;">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$cities): ?>
          <tr><td colspan="5" style="text-align:center; color:var(--fc-muted);"><em>📭 Sem cidades cadastradas em <?php echo $uf; ?></em></td></tr>
        <?php else: foreach ($cities as $city):
          $slug = fc_target_slug($city);
          $page = get_page_by_path($slug, OBJECT, 'page');
          $slugPath = '/'.$slug.'/';
        ?>
          <tr>
            <td><input type="checkbox" class="fc-city-checkbox" value="<?php echo esc_attr($city.'|'.$uf); ?>"></td>
            <td class="fc-city"><strong><?php echo esc_html($city); ?></strong></td>
            <td>
              <code style="font-size:11px;"><?php echo esc_html($slugPath); ?></code>
              <button class="button button-small" data-copy="<?php echo esc_attr($slugPath); ?>" style="margin-left:6px;">📋</button>
            </td>
            <td>
              <?php if ($page): ?>
                <span style="color:var(--fc-emerald);">✅ Ativa</span> ·
                <a href="<?php echo esc_url(get_permalink($page)); ?>" target="_blank">Ver</a> ·
                <a href="<?php echo esc_url(get_edit_post_link($page->ID)); ?>">Editar</a>
              <?php else: ?>
                <span style="color:var(--fc-muted);">⏳ Não criada</span>
              <?php endif; ?>
            </td>
            <td style="display:flex; gap:6px; flex-wrap:wrap;">
              <form method="post" onsubmit="return confirm('Remover <?php echo esc_js($city); ?>/<?php echo $uf; ?> da lista?')" style="display:inline;">
                <?php wp_nonce_field('fc_actions','fc_nonce',true,false); ?>
                <input type="hidden" name="fc_action" value="del_city">
                <input type="hidden" name="city" value="<?php echo esc_attr($city); ?>">
                <input type="hidden" name="state" value="<?php echo esc_attr($uf); ?>">
                <button class="button button-small" style="color:#dc2626;"><span class="dashicons dashicons-trash"></span></button>
              </form>
              <form method="post" style="display:inline;" title="Gerar/atualizar todas as páginas de <?php echo $uf; ?>">
                <?php wp_nonce_field('fc_actions','fc_nonce',true,false); ?>
                <input type="hidden" name="fc_action" value="generate">
                <input type="hidden" name="states" value="<?php echo esc_attr($uf); ?>">
                <button class="button button-small"><span class="dashicons dashicons-update"></span></button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    <?php endforeach; ?>
  </div>

  <!-- Logs -->
  <div class="fc-card" id="fc-logs" style="margin-top:24px;">
    <h2>📋 Logs do Sistema</h2>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
      <p class="fc-muted">Acompanhe todas as ações realizadas pelo plugin.</p>
      <form method="post" style="margin:0;">
        <?php wp_nonce_field('fc_actions', 'fc_nonce'); ?>
        <input type="hidden" name="fc_action" value="clear_logs">
        <button class="button" onclick="return confirm('Limpar todos os logs?')"><span class="dashicons dashicons-trash"></span> Limpar Logs</button>
      </form>
    </div>
    <pre style="max-height:350px; overflow:auto; background:#1e293b; color:#e2e8f0; padding:16px; border-radius:12px; border:2px solid #334155; font-size:12px; line-height:1.4;"><?php
      $logs = fc_get_logs();
      echo esc_html($logs ? implode("\n", array_reverse(array_slice($logs, -50))) : '📝 Nenhum log registrado ainda.');
    ?></pre>
  </div>

  <div class="fc-fixedbar">
    <a href="#fc-acao-gerar" class="fc-btn fc-primary"><span class="dashicons dashicons-hammer"></span> Gerar Landings</a>
    <a href="<?php echo admin_url('admin.php?page=fc-templates'); ?>" class="fc-btn"><span class="dashicons dashicons-edit"></span> Templates</a>
    <a href="#fc-acao-indices" class="fc-btn"><span class="dashicons dashicons-index-card"></span> Índices</a>
    <a href="#fc-logs" class="fc-btn"><span class="dashicons dashicons-clipboard"></span> Logs</a>
    <a href="<?php echo admin_url('plugins.php'); ?>" class="fc-btn"><span class="dashicons dashicons-admin-plugins"></span> Plugins</a>
  </div>

  <script>
  function toggleAllInState(state, checked) {
    const checkboxes = document.querySelectorAll('.fc-city-checkbox[value*="|' + state + '"]');
    checkboxes.forEach(cb => { cb.checked = checked; cb.dispatchEvent(new Event('change')); });
  }
  </script>

  <?php wp_nonce_field('fc_actions', 'fc_nonce'); ?>
  <?php
  echo '</div>';
}

/** ===== Tela de Templates ===== */
function fc_templates_screen() {
  if (!current_user_can('manage_options')) return;

  if (!empty($_POST['fc_nonce']) && wp_verify_nonce($_POST['fc_nonce'], 'fc_template_save')) {
    $type  = sanitize_text_field($_POST['template_type'] ?? '');
    $field = sanitize_text_field($_POST['field'] ?? '');
    $value = wp_kses_post($_POST['value'] ?? '');
    if ($type && $field) {
      fc_update_template($type, $field, $value);
      fc_log("Template atualizado: {$type}.{$field}");
      add_action('admin_notices', fn()=> print '<div class="notice notice-success is-dismissible"><p>✅ Template atualizado com sucesso!</p></div>');
    }
  }

  $templates = fc_get_templates();

  echo '<div class="wrap fc-wrap">';
  ?>
  <div class="fc-hero">
    <div>
      <div class="fc-chips" style="margin-bottom:12px;">
        <span class="fc-chip">🎨 Editor de Templates</span>
        <span class="fc-chip">📝 Conteúdo Personalizável</span>
        <span class="fc-chip">🔄 Placeholders Dinâmicos</span>
      </div>
      <h1>Editor de Templates</h1>
      <p class="fc-muted">Personalize o conteúdo das suas landing pages e páginas-índice.</p>
      <div class="fc-actions">
        <a href="<?php echo admin_url('admin.php?page=fc-city-landings'); ?>" class="fc-btn"><span class="dashicons dashicons-arrow-left-alt"></span> Voltar ao Painel</a>
        <a href="#landing-templates" class="fc-btn fc-primary"><span class="dashicons dashicons-edit"></span> Templates de Landing</a>
        <a href="#index-templates" class="fc-btn"><span class="dashicons dashicons-index-card"></span> Templates de Índice</a>
      </div>
    </div>
    <div>
      <div class="fc-placeholders">
        <h4 style="margin-top:0;">🏷️ Placeholders Disponíveis:</h4>
        <code>{city}</code> · <code>{state}</code> · <code>{state_full}</code> · <code>{contact_url}</code> · <code>{site_url}</code> · <code>{cities_list}</code> (só índices)
      </div>
    </div>
  </div>

  <div class="fc-card" id="landing-templates" style="margin-top:24px;">
    <h2>🎯 Templates de Landing Pages</h2>
    <p class="fc-muted">/desenvolvimento-de-sistemas-em-&lt;cidade&gt;</p>

    <div class="fc-template-editor" style="margin-bottom:24px;">
      <h3>📋 Título da Página</h3>
      <form method="post">
        <?php wp_nonce_field('fc_template_save', 'fc_nonce'); ?>
        <input type="hidden" name="template_type" value="landing">
        <input type="hidden" name="field" value="title">
        <textarea name="value" rows="2" style="width:100%;" class="fc-template-field"><?php echo esc_textarea($templates['landing']['title'] ?? ''); ?></textarea>
        <p><button class="button button-primary">💾 Salvar Título</button></p>
      </form>
    </div>

    <div class="fc-template-editor" style="margin-bottom:24px;">
      <h3>📄 Conteúdo da Página</h3>
      <form method="post">
        <?php wp_nonce_field('fc_template_save', 'fc_nonce'); ?>
        <input type="hidden" name="template_type" value="landing">
        <input type="hidden" name="field" value="content">
        <textarea name="value" rows="15" style="width:100%;" class="fc-template-field"><?php echo esc_textarea($templates['landing']['content'] ?? ''); ?></textarea>
        <p><button class="button button-primary">💾 Salvar Conteúdo</button></p>
      </form>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
      <div class="fc-template-editor">
        <h3>🎯 Meta Title (SEO)</h3>
        <form method="post">
          <?php wp_nonce_field('fc_template_save', 'fc_nonce'); ?>
          <input type="hidden" name="template_type" value="landing">
          <input type="hidden" name="field" value="meta_title">
          <textarea name="value" rows="3" style="width:100%;" class="fc-template-field"><?php echo esc_textarea($templates['landing']['meta_title'] ?? ''); ?></textarea>
          <p><button class="button button-primary">💾 Salvar</button></p>
        </form>
      </div>
      <div class="fc-template-editor">
        <h3>📝 Meta Description (SEO)</h3>
        <form method="post">
          <?php wp_nonce_field('fc_template_save', 'fc_nonce'); ?>
          <input type="hidden" name="template_type" value="landing">
          <input type="hidden" name="field" value="meta_description">
          <textarea name="value" rows="3" style="width:100%;" class="fc-template-field"><?php echo esc_textarea($templates['landing']['meta_description'] ?? ''); ?></textarea>
          <p><button class="button button-primary">💾 Salvar</button></p>
        </form>
      </div>
    </div>
  </div>

  <div class="fc-card" id="index-templates" style="margin-top:24px;">
    <h2>📋 Templates de Páginas-Índice</h2>
    <p class="fc-muted">Ex: /desenvolvimento-de-sistemas-no-rio-grande-do-sul</p>

    <div class="fc-template-editor" style="margin-bottom:24px;">
      <h3>📋 Título da Página-Índice</h3>
      <form method="post">
        <?php wp_nonce_field('fc_template_save', 'fc_nonce'); ?>
        <input type="hidden" name="template_type" value="index">
        <input type="hidden" name="field" value="title">
        <textarea name="value" rows="2" style="width:100%;" class="fc-template-field"><?php echo esc_textarea($templates['index']['title'] ?? ''); ?></textarea>
        <p><button class="button button-primary">💾 Salvar Título</button></p>
      </form>
    </div>

    <div class="fc-template-editor" style="margin-bottom:24px;">
      <h3>📄 Conteúdo da Página-Índice</h3>
      <form method="post">
        <?php wp_nonce_field('fc_template_save', 'fc_nonce'); ?>
        <input type="hidden" name="template_type" value="index">
        <input type="hidden" name="field" value="content">
        <textarea name="value" rows="10" style="width:100%;" class="fc-template-field"><?php echo esc_textarea($templates['index']['content'] ?? ''); ?></textarea>
        <p><button class="button button-primary">💾 Salvar Conteúdo</button></p>
        <p class="fc-muted">💡 Use <code>{cities_list}</code> para inserir automaticamente a lista de cidades do estado.</p>
      </form>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
      <div class="fc-template-editor">
        <h3>🎯 Meta Title - Índice (SEO)</h3>
        <form method="post">
          <?php wp_nonce_field('fc_template_save', 'fc_nonce'); ?>
          <input type="hidden" name="template_type" value="index">
          <input type="hidden" name="field" value="meta_title">
          <textarea name="value" rows="3" style="width:100%;" class="fc-template-field"><?php echo esc_textarea($templates['index']['meta_title'] ?? ''); ?></textarea>
          <p><button class="button button-primary">💾 Salvar</button></p>
        </form>
      </div>
      <div class="fc-template-editor">
        <h3>📝 Meta Description - Índice (SEO)</h3>
        <form method="post">
          <?php wp_nonce_field('fc_template_save', 'fc_nonce'); ?>
          <input type="hidden" name="template_type" value="index">
          <input type="hidden" name="field" value="meta_description">
          <textarea name="value" rows="3" style="width:100%;" class="fc-template-field"><?php echo esc_textarea($templates['index']['meta_description'] ?? ''); ?></textarea>
          <p><button class="button button-primary">💾 Salvar</button></p>
        </form>
      </div>
    </div>
  </div>

  <!-- Preview -->
  <div class="fc-card" style="margin-top:24px;">
    <h2>👁️ Preview dos Templates</h2>
    <p class="fc-muted">Veja como ficará o conteúdo com os placeholders substituídos</p>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
      <div>
        <h3>🎯 Landing Page (ex.: Porto Alegre/RS)</h3>
        <div style="background:#f8fafc; border:2px dashed #cbd5e1; border-radius:8px; padding:16px; font-size:14px;">
          <h4 style="color:var(--fc-indigo); margin-top:0;"><?php echo fc_replace_placeholders($templates['landing']['title'] ?? '', 'Porto Alegre', 'RS'); ?></h4>
          <div style="color:#64748b;"><?php echo wp_kses_post(fc_replace_placeholders($templates['landing']['content'] ?? '', 'Porto Alegre', 'RS')); ?></div>
        </div>
      </div>
      <div>
        <h3>📋 Página-Índice (ex.: RS)</h3>
        <div style="background:#f8fafc; border:2px dashed #cbd5e1; border-radius:8px; padding:16px; font-size:14px;">
          <h4 style="color:var(--fc-indigo); margin-top:0;"><?php echo fc_replace_placeholders($templates['index']['title'] ?? '', '', 'RS', 'index'); ?></h4>
          <div style="color:#64748b;"><?php echo wp_kses_post(fc_replace_placeholders($templates['index']['content'] ?? '', '', 'RS', 'index')); ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="fc-fixedbar">
    <a href="<?php echo admin_url('admin.php?page=fc-city-landings'); ?>" class="fc-btn fc-primary"><span class="dashicons dashicons-arrow-left-alt"></span> Voltar ao Painel</a>
    <a href="#landing-templates" class="fc-btn"><span class="dashicons dashicons-edit"></span> Landing Templates</a>
    <a href="#index-templates" class="fc-btn"><span class="dashicons dashicons-index-card"></span> Index Templates</a>
    <a href="<?php echo admin_url('admin.php?page=fc-city-landings&action=generate'); ?>" class="fc-btn"><span class="dashicons dashicons-hammer"></span> Aplicar Templates</a>
  </div>
  <?php
  echo '</div>';
}
