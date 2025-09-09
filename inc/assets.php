<?php
if (!defined('ABSPATH')) exit;

add_action('admin_enqueue_scripts', function(){
  if (!fc_is_admin_page()) return;

  // CSS
  wp_register_style('fc-admin', false, [], FC_VERSION);
  wp_enqueue_style('fc-admin');
  $css = <<<CSS
/* ===== Acessibilidade/Contraste melhorado ===== */
:root{
  /* cores mais escuras para texto/borda e fundo de cartões sólido */
  --fc-text:#0f172a;    /* slate-900 */
  --fc-muted:#374151;   /* gray-700 */
  --fc-border:#cbd5e1;  /* slate-300 */
  --fc-card:#ffffff;    /* branco sólido */

  --fc-indigo:#4f46e5;  /* indigo-600 */
  --fc-emerald:#059669; /* emerald-600 */
  --fc-cyan:#0891b2;    /* cyan-600 */
  --fc-amber:#f59e0b;   /* amber-500 */
  --fc-rose:#e11d48;    /* rose-600 */

  /* gradient do background do admin; pode virar sólido se quiser */
  --fc-bg: linear-gradient(135deg, #eef2ff 0%, #e9d5ff 100%);

  --fc-shadow: 0 8px 20px rgba(0,0,0,.06);
  --fc-shadow-lg: 0 10px 24px rgba(0,0,0,.08);
}

@media (prefers-color-scheme: dark){
  :root{
    --fc-bg: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    --fc-card:#111827;
    --fc-border:#334155;  /* slate-700 */
    --fc-text:#f8fafc;
    --fc-muted:#e5e7eb;
  }
}

/* tipografia levemente maior e mais espaçada */
body.admin_page_fc-city-landings,
body.admin_page_fc-templates{
  background: var(--fc-bg) !important;
  min-height: 100vh;
  font-size: 15px;
  line-height: 1.6;
}

.fc-wrap{ background: transparent; padding: 20px; margin: 20px 20px 0 0; border-radius: 20px; }

/* HERO e CARDS agora com fundo sólido (sem blur/transparência) */
.fc-hero{
  position:relative; overflow:hidden; border-radius:20px; padding:40px;
  background:var(--fc-card);
  border:1px solid var(--fc-border);
  box-shadow: var(--fc-shadow-lg);
  display:grid; grid-template-columns:1.2fr .8fr; gap:32px; margin-bottom:24px;
}
.fc-hero::before{ content:''; position:absolute; inset:0; pointer-events:none; }

.fc-hero h1{
  margin:0 0 8px; font-size:30px; color:var(--fc-text); font-weight:800;
  /* remove o texto em gradiente para legibilidade */
  -webkit-text-fill-color:initial; background:none;
}
.fc-hero p{ margin:0; color:var(--fc-muted); font-size:16px; }

.fc-kpis{ display:grid; grid-template-columns: repeat(3,1fr); gap:16px; }
.fc-kpi{
  background:var(--fc-card);
  border:1px solid var(--fc-border);
  border-radius:16px; padding:20px; box-shadow:var(--fc-shadow);
}
.fc-kpi h3{ margin:0; font-size:12px; color:var(--fc-muted); font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.fc-kpi strong{ display:block; font-size:32px; margin-top:8px; color:var(--fc-indigo); font-weight:800; }

.fc-actions{ display:flex; gap:12px; flex-wrap:wrap; margin-top:20px; }

.fc-btn{
  display:inline-flex; gap:8px; align-items:center; border-radius:12px;
  border:1px solid var(--fc-border);
  padding:12px 20px; background:var(--fc-card); color:var(--fc-text);
  text-decoration:none; cursor:pointer; transition:.2s ease;
  font-weight:600; box-shadow:var(--fc-shadow);
}
.fc-btn:hover{ transform: translateY(-1px); box-shadow:var(--fc-shadow-lg); }
.fc-btn.fc-primary{ background:linear-gradient(135deg,var(--fc-indigo), #2563eb); color:#fff; border-color:transparent; }
.fc-btn.fc-danger{ background:linear-gradient(135deg,var(--fc-rose), #b91c1c); color:#fff; border-color:transparent; }

.fc-grid{ display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:24px; }
.fc-card{
  background:var(--fc-card);
  border:1px solid var(--fc-border);
  border-radius:16px; padding:24px; box-shadow:var(--fc-shadow);
}
.fc-card h2{ margin-top:0; color:var(--fc-indigo); font-size:20px; font-weight:700; }
.fc-muted{ color:var(--fc-muted); }

.fc-chips{ display:flex; gap:8px; flex-wrap:wrap; }
.fc-chip{
  padding:6px 12px; border-radius:20px; font-size:12px;
  border:1px solid var(--fc-border);
  background:#a3a3a3; color:var(--fc-text); font-weight:600;
}

/* Tabela mais contrastada */
.fc-table{ border-radius:12px; overflow:hidden; box-shadow:var(--fc-shadow); background:var(--fc-card); }
.fc-table th{
  background:#f1f5f9;  /* slate-100 */
  font-weight:700; color:var(--fc-text);
}
.fc-table td, .fc-table th{ vertical-align:middle; padding:12px; border-bottom:1px solid var(--fc-border); }

/* Slug/código com fonte maior e cor escura */
.fc-table code{
  background:#e2e8f0; border:1px solid #cbd5e1; padding:4px 8px; border-radius:6px;
  font-size:13px; color:#0f172a;
}

/* Barra de Ações em Lote */
.fc-bulk{ background: rgba(16,185,129,.1); border: 2px dashed var(--fc-emerald); border-radius:12px; padding:16px; margin:16px 0; display:none; }
.fc-bulk.active{ display:block; animation: fcIn .3s ease; }
@keyframes fcIn{ from{ opacity:0; transform: scale(.97);} to{ opacity:1; transform: scale(1);} }

/* Estados */
.fc-state-ok{ background: rgba(16,185,129,.15); border-color: rgba(16,185,129,.3); }
.fc-state-missing{ background: rgba(244,63,94,.15); border-color: rgba(244,63,94,.3); }

/* Barra fixa com fundo sólido */
.fc-fixedbar{
  position:sticky; bottom:20px; display:flex; gap:12px; padding:16px; z-index:10;
  background:var(--fc-card); border:1px solid var(--fc-border); border-radius:16px; margin-top:24px; box-shadow:var(--fc-shadow-lg);
}

.notice-success.fc-flash{ border-left-color: var(--fc-emerald)!important; background: rgba(16,185,129,.1)!important; }

/* Textareas do editor com foco visível */
.fc-template-editor textarea{
  font-family: 'Monaco','Menlo','Ubuntu Mono',monospace;
  border-radius:8px; border:2px solid var(--fc-border); padding:16px; transition: border-color .2s ease, box-shadow .2s ease;
  color:var(--fc-text); background:var(--fc-card);
}
.fc-template-editor textarea:focus{ border-color: var(--fc-indigo); box-shadow:0 0 0 3px rgba(79,70,229,.15); }

/* Caixa de placeholders */
.fc-placeholders{ background:#eef2ff; border:1px solid #c7d2fe; border-radius:8px; padding:12px; font-size:12px; color:var(--fc-text); }

/* Inputs do formulário com borda legível */
input[type="text"], input[type="search"], select, textarea{
  border:1px solid var(--fc-border);
}
input[type="text"]:focus, input[type="search"]:focus, select:focus, textarea:focus{
  border-color: var(--fc-indigo);
  box-shadow: 0 0 0 3px rgba(79,70,229,.15);
  outline: none;
}

/* Área de logs com contraste alto */
#fc-logs pre{
  font-size:13px !important;
  background:#0b1220 !important;
  color:#e2e8f0 !important;
  border:2px solid #1e293b !important;
  border-radius:12px;
  padding:16px;
}

/* Responsivo */
@media (max-width:1100px){
  .fc-grid{ grid-template-columns:1fr; }
  .fc-hero{ grid-template-columns:1fr; }
  .fc-kpis{ grid-template-columns:1fr; }
}
CSS;
  wp_add_inline_style('fc-admin', $css);

  // JS
  wp_register_script('fc-admin', false, [], FC_VERSION, true);
  wp_enqueue_script('fc-admin');
  $js = <<<JS
(function(){
  // Filtro de cidade
  const input = document.getElementById('fc-filter');
  if (input) {
    input.addEventListener('input', function(){
      const q = this.value.toLowerCase();
      document.querySelectorAll('.fc-table .fc-city').forEach(td=>{
        const tr = td.closest('tr');
        tr.style.display = td.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // Copiar slug
  document.querySelectorAll('[data-copy]').forEach(btn=>{
    btn.addEventListener('click', e=>{
      e.preventDefault();
      const text = btn.getAttribute('data-copy') || '';
      navigator.clipboard.writeText(text).then(()=>{
        const original = btn.innerText;
        btn.innerText = '✓ Copiado!';
        btn.style.background = 'var(--fc-emerald)';
        btn.style.color = 'white';
        setTimeout(()=>{
          btn.innerText = original;
          btn.style.background = '';
          btn.style.color = '';
        }, 1500);
      });
    });
  });

  // Bulk Actions
  let selectedCities = new Set();

  const selectAllCheckbox = document.getElementById('fc-select-all');
  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.fc-city-checkbox');
      checkboxes.forEach(cb => {
        cb.checked = this.checked;
        if (this.checked) selectedCities.add(cb.value); else selectedCities.delete(cb.value);
      });
      updateBulkActions();
    });
  }

  document.querySelectorAll('.fc-city-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      if (this.checked) selectedCities.add(this.value); else selectedCities.delete(this.value);
      updateBulkActions();
      const total = document.querySelectorAll('.fc-city-checkbox').length;
      const checked = document.querySelectorAll('.fc-city-checkbox:checked').length;
      if (selectAllCheckbox) {
        selectAllCheckbox.indeterminate = checked>0 && checked<total;
        selectAllCheckbox.checked = checked===total;
      }
    });
  });

  function updateBulkActions() {
    const bulkDiv = document.querySelector('.fc-bulk');
    const count = document.getElementById('fc-selected-count');
    if (!bulkDiv || !count) return;
    if (selectedCities.size > 0) {
      bulkDiv.classList.add('active');
      count.textContent = selectedCities.size;
    } else {
      bulkDiv.classList.remove('active');
      count.textContent = '0';
    }
  }

  // Bulk delete
  const bulkBtn = document.getElementById('fc-bulk-delete');
  if (bulkBtn) {
    bulkBtn.addEventListener('click', function() {
      if (selectedCities.size === 0) return;
      const cities = Array.from(selectedCities).map(val => {
        const [city, state] = val.split('|');
        return city+' ('+state+')';
      }).join(', ');
      if (confirm('Tem certeza que deseja remover '+selectedCities.size+' cidade(s)?\\n\\n'+cities)) {
        const form = document.createElement('form');
        form.method = 'POST'; form.style.display = 'none';
        const nonce = document.querySelector('[name="fc_nonce"]');
        if (nonce) {
          const n = document.createElement('input'); n.type='hidden'; n.name='fc_nonce'; n.value=nonce.value; form.appendChild(n);
        }
        const action = document.createElement('input'); action.type='hidden'; action.name='fc_action'; action.value='bulk_delete'; form.appendChild(action);
        selectedCities.forEach(val => {
          const input = document.createElement('input'); input.type='hidden'; input.name='selected_cities[]'; input.value=val; form.appendChild(input);
        });
        document.body.appendChild(form); form.submit();
      }
    });
  }

  // Animação pequena nos botões ao enviar formulário
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
      const btn = this.querySelector('button[type="submit"], input[type="submit"]');
      if (btn) {
        btn.style.transform='scale(0.95)'; btn.style.opacity='0.7';
        setTimeout(()=>{ btn.style.transform=''; btn.style.opacity=''; }, 200);
      }
    });
  });

  // Auto-save templates (apenas visual)
  let saveTimeout;
  document.querySelectorAll('.fc-template-field').forEach(field => {
    field.addEventListener('input', function() {
      clearTimeout(saveTimeout);
      saveTimeout = setTimeout(()=>{ console.log('Template field updated:', this.name); }, 1000);
    });
  });

})();
JS;
  wp_add_inline_script('fc-admin', $js, 'after');
});
