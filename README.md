# Forcoder City Landings Pro

Gerador avançado de **landing pages por cidade/estado** para WordPress.
Crie, atualize e mantenha páginas locais em massa, com **templates editáveis**, **páginas-índice por estado**, **importação em lote**, **logs de operações**, **suporte a Yoast SEO** e **atalhos WP-CLI**.

---

## ✨ Principais recursos

* **Landings por cidade**: cria/atualiza páginas no padrão `/desenvolvimento-de-sistemas-em-<cidade>/`.
* **Índices por estado**: gera páginas agregadoras (ex.: RS e SC) com links para as cidades.
* **Templates 100% editáveis** (título, conteúdo, meta title/description) com **placeholders dinâmicos**.
* **Importação em lote** de cidades (cola a lista e pronto).
* **Ações em lote** (remover várias cidades).
* **Painel moderno** no WP-Admin com KPIs e logs.
* **Compatível** com Yoast SEO (preenche metadados), **Hello Elementor** (template `elementor_full_width`) e **Elementor** (marca o post para edição no builder).
* **WP-CLI** para automatizar (CI/CD, cron, etc.).

---

## 📦 Requisitos

* WordPress 6.0+
* PHP 7.4+ (recomendado PHP 8.1+)
* (Opcional) Yoast SEO
* (Opcional) Elementor / Hello Elementor

---

## 🗂️ Estrutura do repositório

```
forcoder-city-landings-pro/
├─ forcoder-city-landings-pro.php        # Bootstrap do plugin (loader)
└─ inc/
   ├─ templates.php                      # Defaults, helpers, templates, placeholders e logs
   ├─ generator.php                      # Criação/atualização de landings e índices (RS/SC)
   ├─ admin-ui.php                       # Telas do WP-Admin (painel e editor de templates)
   ├─ assets.php                         # CSS/JS do admin (enqueued)
   ├─ ajax.php                           # Endpoints Ajax (preview rápido - reservado)
   ├─ cli.php                            # Comandos WP-CLI
   └─ site-health.php                    # Entradas no Site Health
```

---

## 🛠️ Instalação

1. Faça o build/clone do repositório para `wp-content/plugins/forcoder-city-landings-pro`.
2. No WP-Admin, vá em **Plugins → Ativar**.
3. O plugin cria opções iniciais (RS/SC com algumas cidades) e templates padrão.

> Na ativação, as **rewrite rules** são atualizadas e os templates default são gravados.

---

## 🚀 Como usar (WP-Admin)

### 1) Painel principal

**WP-Admin → City Landings Pro**

* **Gerar / Atualizar Landings**
  Informe os estados (padrão `RS,SC`) e clique em **Executar**.
* **Páginas-Índice por Estado**
  Gera/atualiza as páginas agregadoras (ex.: RS e SC) com lista de cidades.
* **Importar em Lote**
  Seção “Importar / Adicionar Cidades” → cole a lista e escolha a UF padrão.
* **Adicionar Cidade Individual**
  Informe nome e UF.
* **Lista de Cidades & Status**
  Filtro, status (ativa/não criada), links **Ver/Editar** e remoção.
* **Logs do Sistema**
  Últimas ações do plugin (com botão “Limpar Logs”).

### 2) Editor de Templates

**WP-Admin → City Landings Pro → Templates**

Edite **Título**, **Conteúdo**, **Meta Title** e **Meta Description** tanto para **Landing** quanto para **Índice**.

**Placeholders disponíveis:**

* `{city}` — Nome da cidade
* `{state}` — UF (RS/SC)
* `{state_full}` — Nome completo do estado
* `{contact_url}` — URL da página de contato (ex.: `/contato`)
* `{site_url}` — URL do site
* `{cities_list}` — Lista de cidades (apenas para templates de **Índice**)

> O preview mostra substituições reais (ex.: “Porto Alegre/RS”).

---

## 🧩 Slugs e páginas geradas

* **Landing por cidade**:
  `/desenvolvimento-de-sistemas-em-<cidade-slug>/`
  Ex.: `/desenvolvimento-de-sistemas-em-porto-alegre/`

* **Índice por estado**:

  * RS → `/desenvolvimento-de-sistemas-no-rio-grande-do-sul/`
  * SC → `/desenvolvimento-de-sistemas-em-santa-catarina/`

---

## 🧾 Formato de importação em lote

Cole no textarea (um por linha). Aceita dois formatos:

```
Porto Alegre,RS
Joinville,SC
Alvorada
Canoas
```

* Quando **não** houver UF na linha, a UF **padrão escolhida** no formulário será usada.

---

## ⚙️ WP-CLI

Todos os comandos começam com `wp forcoder ...`.

### Gerar/atualizar landings

```bash
wp forcoder landings generate --states=RS,SC
```

### Gerar/atualizar índices

```bash
wp forcoder indexes generate --states=RS,SC
```

### Importar cidades de um CSV

```bash
wp forcoder cities import ./cidades.csv
# Espera linhas no formato: "Cidade,UF" ou "Cidade" (usa RS por default)
```

### Listar cidades (com status e slug)

```bash
wp forcoder cities list --format=table
# Campos: city | state | slug | status
```

### Atualizar rapidamente algum template

```bash
wp forcoder template update landing title "Desenvolvimento de Sistemas em {city} – {state}"
# Campos permitidos: title | content | meta_title | meta_description
# Tipos: landing | index
```

---

## 🔧 Integrações e comportamento

* **Yoast SEO**
  Se o Yoast estiver ativo, o plugin preenche `_yoast_wpseo_title` e `_yoast_wpseo_metadesc` com base nos templates.

* **Hello Elementor / Elementor**

  * Seta o **template de página** para `elementor_full_width` (Hello Elementor) se existir.
  * Marca a página para edição no Elementor (metadados `_elementor_*`).
  * O conteúdo é gerado em **blocos Gutenberg**; você pode editar livremente no Elementor se preferir.

* **Site Health**
  Mostra um painel “Forcoder City Landings” com resumo de cidades, estados, templates e logs.

---

## 🔍 Debug & Logs

* Logs armazenados em `option` (`FC_LOG_KEY`), visíveis no painel.
* Limite de \~500 entradas (rotaciona os mais antigos).
* A função `fc_log($mensagem)` está disponível para extensões.

---

## 🧱 Desenvolvimento

* **Constantes**

  * `FC_VERSION` – versão do plugin
  * `FC_OPT_KEY`, `FC_LOG_KEY`, `FC_IDX_KEY`, `FC_TPL_KEY` – opções no banco

* **Helpers principais** (em `inc/templates.php`)

  * `fc_get_city_list()`, `fc_set_city_list( array $data )`
  * `fc_get_templates()`, `fc_update_template( $type, $field, $value )`
  * `fc_replace_placeholders( $content, $city, $state, $type )`
  * `fc_target_slug( $city )`, `fc_index_slug( $state )`
  * `fc_log( $msg )`, `fc_get_logs()`, `fc_clear_logs()`

* **Geradores** (em `inc/generator.php`)

  * `fc_create_or_update_page( $city, $state )`
  * `fc_generate_landings( array $states )`
  * `fc_create_or_update_index( $state )`
  * `fc_generate_indexes( array $states )`

---

## 🐞 Problemas comuns

* **Página não abre / 404**
  Vá em **Configurações → Links permanentes** e salve (ou reative o plugin) para “flush” nas regras.

* **Yoast não aplicou meta**
  Confira se os campos de template (`meta_title`, `meta_description`) não estão vazios e se o Yoast está ativo.

* **Tema não tem `elementor_full_width`**
  O plugin ainda cria as páginas normalmente; apenas ignora o template se não existir no tema.

---

## 🗺️ Roadmap (ideias)

* Placeholders adicionais (ex.: `{state_name}` estilo “no Rio Grande do Sul”).
* Suporte a **mais estados** e **taxonomias** personalizadas.
* Agendamentos (cron) para atualização periódica.
* Export/Import de **templates** via JSON (UI).
* Hooks/filters públicos para extender a geração.

---

## 📄 Licença

Este projeto é disponibilizado sob a licença que você preferir definir para o repositório (ex.: MIT).
Inclua o arquivo `LICENSE` na raiz do plugin.

---

## 🖼️ Screenshots (opcional)

* **Painel** – KPIs, geração e listagem
* **Editor de templates** – campos e preview
* **Página-índice** – lista de cidades

> Adicione capturas em `assets/` e referencie-as aqui.
