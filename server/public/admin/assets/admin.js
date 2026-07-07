const api = '';
const tokenKey = 'huajian_admin_token';

const state = {
  site: {},
  articles: [],
  products: [],
  orders: [],
  categories: [],
  productCategories: [],
  media: [],
  forms: [],
  versions: [],
  moduleRegistry: { scopes: [], modules: [] },
  pagePlan: null,
  selectedFormId: null,
  selectedOrderId: null,
  orderFilters: { keyword: '', payment_status: '', fulfillment_status: '' },
  orderFilterTimer: null
};

const titles = {
  dashboard: ['概览', '查看站点内容、商品、媒体、留言和发布状态。'],
  settings: ['站点', '维护企业信息、SEO、首页首屏和首页模块文案。'],
  articles: ['文章', '发布行业资讯、知识库文章和 SEO 内容。'],
  products: ['商品', '维护产品展示和询盘型独立站内容。'],
  orders: ['订单', '处理独立站商城订单、支付状态和履约跟进。'],
  media: ['媒体库', '上传图片和文件，供文章、商品、页面使用。'],
  forms: ['留言', '处理前台联系表单和商品询盘。'],
  publish: ['发布', '生成静态网站并查看发布版本。']
};

const modulePresets = [
  { key: 'about', title: '图文介绍', description: '展示企业简介、业务定位和关键数据。' },
  { key: 'advantages', title: '优势卖点', description: '用卡片形式展示服务优势、技术优势或交付能力。' },
  { key: 'cases', title: '案例展示', description: '展示客户案例、应用场景或解决方案成果。' },
  { key: 'products', title: '产品模块', description: '展示已发布商品，适合独立站和产品型官网。' },
  { key: 'articles', title: '文章模块', description: '展示行业资讯和知识库文章，承接 SEO 内容。' },
  { key: 'faq', title: 'FAQ', description: '展示常见问题，降低咨询成本并补充长尾关键词。' },
  { key: 'inquiry', title: '询盘表单', description: '在首页收集客户姓名、电话、邮箱和需求。' }
];

function $(selector) {
  return document.querySelector(selector);
}

function $all(selector) {
  return [...document.querySelectorAll(selector)];
}

function token() {
  return localStorage.getItem(tokenKey) || '';
}

function setToken(value) {
  if (value) localStorage.setItem(tokenKey, value);
  else localStorage.removeItem(tokenKey);
}

function toast(message) {
  const el = $('#toast');
  el.textContent = message;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 2200);
}

function showLogin() {
  $('#loginScreen').classList.remove('hidden');
  $('#appShell').classList.add('hidden');
}

function showApp() {
  $('#loginScreen').classList.add('hidden');
  $('#appShell').classList.remove('hidden');
}

async function request(path, options = {}) {
  const headers = options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' };
  if (token()) headers.Authorization = `Bearer ${token()}`;

  const response = await fetch(api + path, { headers, ...options });
  const result = await response.json();
  if (!result.success) {
    if (response.status === 401) {
      setToken('');
      showLogin();
    }
    throw new Error(result.message || '请求失败');
  }
  return result.data;
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function slugify(value, prefix = 'draft') {
  const ascii = String(value || '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
  if (ascii.length >= 8) return ascii.slice(0, 80);
  if (ascii) return `${prefix}-${ascii}-${Date.now().toString(36)}`.slice(0, 80);
  return `${prefix}-${Date.now().toString(36)}`;
}

function cleanPrompt(value, fallback) {
  return String(value || '').trim() || fallback;
}

function siteIndustryText() {
  const site = getDefaultSite(state.site);
  return site.slogan || site.description || site.name || '企业独立站';
}

function formToObject(form) {
  return Object.fromEntries(new FormData(form).entries());
}

function setView(view) {
  $all('.nav-item').forEach((item) => item.classList.toggle('active', item.dataset.view === view));
  $all('.view').forEach((item) => item.classList.toggle('active', item.id === `view-${view}`));
  $('#pageTitle').textContent = titles[view][0];
  $('#pageHint').textContent = titles[view][1];
}

function getDefaultSite(site = {}) {
  return {
    id: site.id || 10001,
    name: site.name || '楚云数航',
    slogan: site.slogan || '低空经济数字化解决方案',
    domain: site.domain || 'demo.local',
    language: site.language || 'zh-CN',
    logo: site.logo || '',
    description: site.description || '',
    keywords: site.keywords || '',
    phone: site.phone || '',
    email: site.email || '',
    address: site.address || '',
    whatsapp: site.whatsapp || '',
    wechat_qrcode: site.wechat_qrcode || '',
    ai: {
      provider: site.ai?.provider || '',
      model: site.ai?.model || '',
      endpoint: site.ai?.endpoint || '',
      api_key: site.ai?.api_key || ''
    },
    payment: {
      mode: site.payment?.mode || 'manual',
      currency: site.payment?.currency || 'CNY',
      merchant_id: site.payment?.merchant_id || '',
      webhook_url: site.payment?.webhook_url || ''
    },
    deploy: {
      bt_panel_url: site.deploy?.bt_panel_url || '',
      site_path: site.deploy?.site_path || '',
      mode: site.deploy?.mode || 'manual',
      after_action: site.deploy?.after_action || '',
      note: site.deploy?.note || ''
    },
    global_modules: {
      search_nav: site.global_modules?.search_nav ?? true,
      breadcrumbs: site.global_modules?.breadcrumbs ?? true,
      related: site.global_modules?.related ?? true,
      floating_inquiry: site.global_modules?.floating_inquiry ?? true,
      floating_text: site.global_modules?.floating_text || '立即咨询'
    },
    nav: site.nav || [
      { title: '首页', url: 'index.html' },
      { title: '行业资讯', url: 'news/index.html' },
      { title: '产品中心', url: 'products/index.html' },
      { title: '联系我们', url: 'contact.html' }
    ],
    hero: {
      eyebrow: site.hero?.eyebrow || '化简静态建站 MVP',
      title: site.hero?.title || site.slogan || '低空经济数字化解决方案',
      subtitle: site.hero?.subtitle || site.description || '',
      primary_text: site.hero?.primary_text || '查看产品',
      secondary_text: site.hero?.secondary_text || '阅读资讯',
      panel_label: site.hero?.panel_label || '静态页面',
      panel_title: site.hero?.panel_title || 'HTML + CSS + JS',
      panel_description: site.hero?.panel_description || '发布时生成，访问时无需数据库。'
    },
    home_sections: {
      products_title: site.home_sections?.products_title || '推荐产品',
      products_link_text: site.home_sections?.products_link_text || '全部产品',
      articles_title: site.home_sections?.articles_title || '行业资讯',
      articles_link_text: site.home_sections?.articles_link_text || '全部文章',
      about_title: site.home_sections?.about_title || '关于我们',
      about_subtitle: site.home_sections?.about_subtitle || '用轻量化系统连接产品、内容和客户。',
      about_body: site.home_sections?.about_body || '通过模块化页面、内容管理和静态化发布，让企业官网、知识库和商品展示更容易长期运营。',
      advantages_title: site.home_sections?.advantages_title || '核心优势',
      cases_title: site.home_sections?.cases_title || '应用案例',
      faq_title: site.home_sections?.faq_title || '常见问题',
      inquiry_title: site.home_sections?.inquiry_title || '获取方案与报价',
      inquiry_subtitle: site.home_sections?.inquiry_subtitle || '留下需求，我们会根据行业场景、产品类型和部署方式给出建站建议。'
    },
    home_content: {
      advantages: site.home_content?.advantages || [
        { title: '静态化发布', description: '前台页面生成 HTML 文件，访问速度快，部署成本低，也更利于搜索引擎抓取。' },
        { title: '内容可运营', description: '文章、商品、媒体和留言都在后台统一管理，适合长期做行业关键词沉淀。' },
        { title: '模块化搭建', description: '首页结构由模块配置驱动，后续可由 AI 根据客户行业自动组合页面。' }
      ],
      cases: site.home_content?.cases || [
        { title: '农业植保设备展示站', description: '围绕产品参数、应用场景和询盘表单搭建静态独立站，承接搜索流量。', tag: '农业无人机' },
        { title: '低空经济解决方案官网', description: '用资讯、案例和产品模块呈现企业能力，适合做行业关键词长期沉淀。', tag: '企业官网' },
        { title: '行业知识库内容站', description: '通过文章聚合和搜索索引生成大量静态内容页面，提升收录和转化路径。', tag: 'SEO 内容站' }
      ],
      inquiry_fields: site.home_content?.inquiry_fields || [
        { label: '姓名', name: 'name', type: 'text', placeholder: '姓名', required: false, sort_order: 10 },
        { label: '电话', name: 'phone', type: 'tel', placeholder: '电话', required: false, sort_order: 20 },
        { label: '邮箱', name: 'email', type: 'email', placeholder: '邮箱', required: false, sort_order: 30 },
        { label: '需求', name: 'message', type: 'textarea', placeholder: '请简单描述你的产品、行业或建站需求', required: false, sort_order: 40 }
      ],
      faqs: site.home_content?.faqs || [
        { question: '静态站还能管理商品吗？', answer: '可以。商品在后台维护，发布时生成商品列表页和详情页；询盘、订单、支付等动态能力通过 API 承接。' },
        { question: '客户修改内容后需要手动改代码吗？', answer: '不需要。客户在后台保存内容后，点击生成静态站即可同步到前台页面。' },
        { question: '这种方式适合批量独立站吗？', answer: '适合。每个站点可以独立数据库或独立配置，前台产物是静态文件，便于批量部署和缓存。' }
      ]
    },
    home_modules: site.home_modules || modulePresets.map((item, index) => ({
      key: item.key,
      title: item.title,
      enabled: true,
      sort_order: (index + 1) * 10
    }))
  };
}

function renderNavRows(nav) {
  $('#navRows').innerHTML = nav.map((item, index) => `
    <div class="config-row" data-nav-index="${index}">
      <label>名称<input data-nav-title value="${escapeHtml(item.title || '')}"></label>
      <label>链接<input data-nav-url value="${escapeHtml(item.url || '')}"></label>
      <label>排序<input data-nav-order type="number" value="${Number(item.sort_order ?? (index + 1) * 10)}"></label>
      <button class="danger-btn" type="button" data-delete-nav="${index}">删除</button>
    </div>
  `).join('');
}

function renderModuleRows(modules) {
  const labels = Object.fromEntries(modulePresets.map((item) => [item.key, item.title]));
  const normalized = [...modules].sort((a, b) => Number(a.sort_order ?? 0) - Number(b.sort_order ?? 0));
  $('#homeModuleRows').innerHTML = normalized.map((item, index) => `
    <div class="config-row module-row" data-module-key="${escapeHtml(item.key)}" data-module-title="${escapeHtml(labels[item.key] || item.title || item.key)}">
      <strong>${escapeHtml(labels[item.key] || item.title || item.key)}</strong>
      <label class="check-label"><input data-module-enabled type="checkbox" ${item.enabled ? 'checked' : ''}> 启用</label>
      <label>排序<input data-module-order type="number" value="${Number(item.sort_order ?? (index + 1) * 10)}"></label>
      <div class="row-actions module-actions">
        <button class="text-btn" type="button" data-move-module="up">上移</button>
        <button class="text-btn" type="button" data-move-module="down">下移</button>
      </div>
      <button class="danger-btn" type="button" data-remove-module="${escapeHtml(item.key)}">移除</button>
    </div>
  `).join('');
  refreshModuleMoveButtons();
  renderModulePresetRows();
}

function collectNavRows() {
  return $all('[data-nav-index]').map((row, index) => ({
    title: row.querySelector('[data-nav-title]').value.trim(),
    url: row.querySelector('[data-nav-url]').value.trim(),
    sort_order: Number(row.querySelector('[data-nav-order]').value || (index + 1) * 10)
  })).filter((item) => item.title && item.url)
    .sort((a, b) => a.sort_order - b.sort_order);
}

function collectModuleRows() {
  normalizeModuleOrders();
  return $all('[data-module-key]').map((row) => ({
    key: row.dataset.moduleKey,
    title: row.dataset.moduleTitle || row.querySelector('strong').textContent.trim(),
    enabled: row.querySelector('[data-module-enabled]').checked,
    sort_order: Number(row.querySelector('[data-module-order]').value || 0)
  })).sort((a, b) => a.sort_order - b.sort_order);
}

function currentModuleKeys() {
  return new Set($all('[data-module-key]').map((row) => row.dataset.moduleKey));
}

function renderModulePresetRows() {
  const existing = currentModuleKeys();
  const available = modulePresets.filter((item) => !existing.has(item.key));
  const container = $('#modulePresetRows');
  if (!container) return;
  container.innerHTML = available.length ? available.map((item) => `
    <article class="preset-card">
      <strong>${escapeHtml(item.title)}</strong>
      <p>${escapeHtml(item.description)}</p>
      <button class="text-btn" type="button" data-add-module="${escapeHtml(item.key)}">添加模块</button>
    </article>
  `).join('') : '<div class="empty-hint">所有标准模块都已加入首页。</div>';
}

function renderModuleRegistry(registry = state.moduleRegistry) {
  const target = $('#moduleRegistryRows');
  if (!target) return;
  const scopes = registry.scopes || [];
  const modules = registry.modules || [];
  if (!modules.length) {
    target.innerHTML = '<div class="empty-hint">暂无模块注册信息</div>';
    return;
  }

  target.innerHTML = scopes.map((scope) => {
    const items = modules.filter((item) => item.scope === scope.key);
    if (!items.length) return '';
    return `
      <section class="registry-group">
        <h3>${escapeHtml(scope.title)} <span class="muted">${escapeHtml(scope.description || '')}</span></h3>
        <div class="registry-items">
          ${items.map((item) => `
            <article class="registry-card">
              <header>
                <strong>${escapeHtml(item.title)}</strong>
                <span class="tag">${item.enabled_by_default ? '默认开' : '默认关'}</span>
              </header>
              <code>${escapeHtml(item.key)}</code>
              <p>${escapeHtml(item.description || '')}</p>
              <div class="registry-meta">
                <span>插槽：${escapeHtml(item.render_slot || '-')}</span>
                <span>配置：${escapeHtml(item.config_path || '-')}</span>
              </div>
            </article>
          `).join('')}
        </div>
      </section>
    `;
  }).join('');
}

function renderPagePlan(plan) {
  const target = $('#pagePlanPreview');
  if (!target) return;
  if (!plan) {
    target.textContent = '等待生成页面草案';
    $('#applyPagePlanBtn').disabled = true;
    $('#applyGeneratePagePlanBtn').disabled = true;
    return;
  }

  const modules = plan.used_modules || plan.home_modules || [];
  const advantages = plan.home_content?.advantages || [];
  const cases = plan.home_content?.cases || [];
  const faqs = plan.home_content?.faqs || [];
  const changes = pagePlanChanges(plan);
  target.innerHTML = `
    <div><strong>${escapeHtml(plan.summary || '首页搭建草案')}</strong></div>
    <div class="page-plan-grid">
      <article class="page-plan-card">
        <span>首屏</span>
        <strong>${escapeHtml(plan.hero?.title || '')}</strong>
        <p>${escapeHtml(plan.hero?.subtitle || '')}</p>
      </article>
      <article class="page-plan-card">
        <span>模块顺序</span>
        <strong>${modules.map((item) => escapeHtml(item.title || item.key)).join(' / ')}</strong>
      </article>
      <article class="page-plan-card">
        <span>优势</span>
        <strong>${advantages.length} 条</strong>
        <p>${advantages.map((item) => escapeHtml(item.title || '')).join('、')}</p>
      </article>
      <article class="page-plan-card">
        <span>案例与 FAQ</span>
        <strong>${cases.length} 个案例 / ${faqs.length} 个问题</strong>
      </article>
    </div>
    <div class="page-plan-diff">
      <strong>差异预览：${changes.length ? `将修改 ${changes.length} 项` : '当前表单已与草案一致'}</strong>
      ${changes.length ? `<ul>${changes.map((item) => `
        <li><b>${escapeHtml(item.label)}</b>：${escapeHtml(item.before || '空')} → ${escapeHtml(item.after || '空')}</li>
      `).join('')}</ul>` : ''}
    </div>
  `;
  $('#applyPagePlanBtn').disabled = false;
  $('#applyGeneratePagePlanBtn').disabled = false;
}

function pagePlanChanges(plan) {
  const form = $('#siteSettingsForm');
  if (!form || !plan) return [];
  const changes = [];
  const fields = [
    ['hero_eyebrow', '首屏眉标', plan.hero?.eyebrow],
    ['hero_title', '首屏主标题', plan.hero?.title],
    ['hero_subtitle', '首屏副标题', plan.hero?.subtitle],
    ['hero_primary_text', '首屏主按钮', plan.hero?.primary_text],
    ['hero_secondary_text', '首屏副按钮', plan.hero?.secondary_text],
    ['hero_panel_label', '首屏右侧标签', plan.hero?.panel_label],
    ['hero_panel_title', '首屏右侧标题', plan.hero?.panel_title],
    ['hero_panel_description', '首屏右侧说明', plan.hero?.panel_description],
    ['products_title', '产品区标题', plan.home_sections?.products_title],
    ['products_link_text', '产品区链接文案', plan.home_sections?.products_link_text],
    ['articles_title', '文章区标题', plan.home_sections?.articles_title],
    ['articles_link_text', '文章区链接文案', plan.home_sections?.articles_link_text],
    ['about_title', '关于模块标题', plan.home_sections?.about_title],
    ['about_subtitle', '关于模块副标题', plan.home_sections?.about_subtitle],
    ['about_body', '关于模块正文', plan.home_sections?.about_body],
    ['advantages_title', '优势模块标题', plan.home_sections?.advantages_title],
    ['cases_title', '案例模块标题', plan.home_sections?.cases_title],
    ['faq_title', 'FAQ 模块标题', plan.home_sections?.faq_title],
    ['inquiry_title', '询盘模块标题', plan.home_sections?.inquiry_title],
    ['inquiry_subtitle', '询盘模块副标题', plan.home_sections?.inquiry_subtitle]
  ];

  fields.forEach(([name, label, next]) => {
    if (!next || !form[name]) return;
    const before = form[name].value.trim();
    const after = String(next).trim();
    if (before !== after) changes.push({ label, before, after });
  });

  const currentModules = collectModuleRows().map((item) => item.key).join(' / ');
  const nextModules = (plan.home_modules || []).map((item) => item.key).join(' / ');
  if (nextModules && currentModules !== nextModules) {
    changes.push({ label: '首页模块顺序', before: currentModules, after: nextModules });
  }

  [
    ['优势列表', collectAdvantageRows(), plan.home_content?.advantages],
    ['案例列表', collectCaseRows(), plan.home_content?.cases],
    ['FAQ 列表', collectFaqRows(), plan.home_content?.faqs]
  ].forEach(([label, current, next]) => {
    if (!Array.isArray(next)) return;
    const before = `${current.length} 条`;
    const after = `${next.length} 条`;
    const currentTitles = current.map((item) => item.title || item.question || '').join(' / ');
    const nextTitles = next.map((item) => item.title || item.question || '').join(' / ');
    if (before !== after || currentTitles !== nextTitles) {
      changes.push({ label, before, after });
    }
  });

  return changes;
}

function applyPagePlan(plan = state.pagePlan) {
  if (!plan) {
    toast('请先生成页面草案');
    return;
  }
  const form = $('#siteSettingsForm');
  const hero = plan.hero || {};
  const sections = plan.home_sections || {};
  form.hero_eyebrow.value = hero.eyebrow || form.hero_eyebrow.value;
  form.hero_title.value = hero.title || form.hero_title.value;
  form.hero_subtitle.value = hero.subtitle || form.hero_subtitle.value;
  form.hero_primary_text.value = hero.primary_text || form.hero_primary_text.value;
  form.hero_secondary_text.value = hero.secondary_text || form.hero_secondary_text.value;
  form.hero_panel_label.value = hero.panel_label || form.hero_panel_label.value;
  form.hero_panel_title.value = hero.panel_title || form.hero_panel_title.value;
  form.hero_panel_description.value = hero.panel_description || form.hero_panel_description.value;

  Object.entries({
    products_title: 'products_title',
    products_link_text: 'products_link_text',
    articles_title: 'articles_title',
    articles_link_text: 'articles_link_text',
    about_title: 'about_title',
    about_subtitle: 'about_subtitle',
    about_body: 'about_body',
    advantages_title: 'advantages_title',
    cases_title: 'cases_title',
    faq_title: 'faq_title',
    inquiry_title: 'inquiry_title',
    inquiry_subtitle: 'inquiry_subtitle'
  }).forEach(([field, name]) => {
    if (sections[field] && form[name]) form[name].value = sections[field];
  });

  if (plan.home_content?.advantages) renderAdvantageRows(plan.home_content.advantages);
  if (plan.home_content?.cases) renderCaseRows(plan.home_content.cases);
  if (plan.home_content?.faqs) renderFaqRows(plan.home_content.faqs);
  if (plan.home_modules) renderModuleRows(plan.home_modules);
  renderPagePlan(plan);
  toast('页面草案已应用到表单，请确认后保存');
}

async function applyPagePlanAndGenerate() {
  if (!state.pagePlan) {
    toast('请先生成页面草案');
    return;
  }
  $('#applyGeneratePagePlanBtn').disabled = true;
  try {
    applyPagePlan(state.pagePlan);
    await saveSiteSettings(null, { generate: true });
    toast('页面草案已保存并生成静态站');
  } finally {
    $('#applyGeneratePagePlanBtn').disabled = false;
  }
}

function addModuleFromPreset(key) {
  if (currentModuleKeys().has(key)) return;
  const preset = modulePresets.find((item) => item.key === key);
  if (!preset) return;
  const modules = collectModuleRows();
  modules.push({
    key: preset.key,
    title: preset.title,
    enabled: true,
    sort_order: (modules.length + 1) * 10
  });
  renderModuleRows(modules);
}

function normalizeModuleOrders() {
  $all('[data-module-key]').forEach((row, index) => {
    row.querySelector('[data-module-order]').value = String((index + 1) * 10);
  });
  refreshModuleMoveButtons();
}

function refreshModuleMoveButtons() {
  const rows = $all('[data-module-key]');
  rows.forEach((row, index) => {
    const up = row.querySelector('[data-move-module="up"]');
    const down = row.querySelector('[data-move-module="down"]');
    if (up) up.disabled = index === 0;
    if (down) down.disabled = index === rows.length - 1;
  });
}

function moveModuleRow(row, direction) {
  if (!row) return;
  if (direction === 'up' && row.previousElementSibling) {
    row.parentElement.insertBefore(row, row.previousElementSibling);
  }
  if (direction === 'down' && row.nextElementSibling) {
    row.parentElement.insertBefore(row.nextElementSibling, row);
  }
  normalizeModuleOrders();
}

function renderAdvantageRows(items) {
  $('#advantageRows').innerHTML = items.map((item, index) => `
    <div class="content-row" data-advantage-index="${index}">
      <label>排序<input data-advantage-order type="number" value="${Number(item.sort_order ?? (index + 1) * 10)}"></label>
      <div class="content-row-fields">
        <label>标题<input data-advantage-title value="${escapeHtml(item.title || '')}"></label>
        <label>说明<textarea data-advantage-description rows="3">${escapeHtml(item.description || '')}</textarea></label>
      </div>
      <button class="danger-btn" type="button" data-delete-advantage="${index}">删除</button>
    </div>
  `).join('');
}

function renderCaseRows(items) {
  $('#caseRows').innerHTML = items.map((item, index) => `
    <div class="content-row" data-case-index="${index}">
      <label>排序<input data-case-order type="number" value="${Number(item.sort_order ?? (index + 1) * 10)}"></label>
      <div class="content-row-fields">
        <label>标签<input data-case-tag value="${escapeHtml(item.tag || '')}"></label>
        <label>标题<input data-case-title value="${escapeHtml(item.title || '')}"></label>
        <label>说明<textarea data-case-description rows="3">${escapeHtml(item.description || '')}</textarea></label>
      </div>
      <button class="danger-btn" type="button" data-delete-case="${index}">删除</button>
    </div>
  `).join('');
}

function renderInquiryFieldRows(items) {
  $('#inquiryFieldRows').innerHTML = items.map((item, index) => `
    <div class="content-row field-row" data-inquiry-field-index="${index}">
      <label>排序<input data-inquiry-field-order type="number" value="${Number(item.sort_order ?? (index + 1) * 10)}"></label>
      <div class="content-row-fields">
        <div class="form-grid">
          <label>字段标题<input data-inquiry-field-label value="${escapeHtml(item.label || '')}"></label>
          <label>字段名<input data-inquiry-field-name value="${escapeHtml(item.name || '')}" placeholder="name"></label>
        </div>
        <div class="form-grid">
          <label>类型<select data-inquiry-field-type>
            <option value="text" ${item.type === 'text' ? 'selected' : ''}>文本</option>
            <option value="tel" ${item.type === 'tel' ? 'selected' : ''}>电话</option>
            <option value="email" ${item.type === 'email' ? 'selected' : ''}>邮箱</option>
            <option value="textarea" ${item.type === 'textarea' ? 'selected' : ''}>多行文本</option>
          </select></label>
          <label class="check-label"><input data-inquiry-field-required type="checkbox" ${item.required ? 'checked' : ''}> 必填</label>
        </div>
        <label>占位提示<input data-inquiry-field-placeholder value="${escapeHtml(item.placeholder || '')}"></label>
      </div>
      <button class="danger-btn" type="button" data-delete-inquiry-field="${index}">删除</button>
    </div>
  `).join('');
}

function renderFaqRows(items) {
  $('#faqRows').innerHTML = items.map((item, index) => `
    <div class="content-row" data-faq-index="${index}">
      <label>排序<input data-faq-order type="number" value="${Number(item.sort_order ?? (index + 1) * 10)}"></label>
      <div class="content-row-fields">
        <label>问题<input data-faq-question value="${escapeHtml(item.question || '')}"></label>
        <label>答案<textarea data-faq-answer rows="3">${escapeHtml(item.answer || '')}</textarea></label>
      </div>
      <button class="danger-btn" type="button" data-delete-faq="${index}">删除</button>
    </div>
  `).join('');
}

function collectAdvantageRows() {
  return $all('[data-advantage-index]').map((row, index) => ({
    title: row.querySelector('[data-advantage-title]').value.trim(),
    description: row.querySelector('[data-advantage-description]').value.trim(),
    sort_order: Number(row.querySelector('[data-advantage-order]').value || (index + 1) * 10)
  })).filter((item) => item.title || item.description)
    .sort((a, b) => a.sort_order - b.sort_order);
}

function collectCaseRows() {
  return $all('[data-case-index]').map((row, index) => ({
    tag: row.querySelector('[data-case-tag]').value.trim(),
    title: row.querySelector('[data-case-title]').value.trim(),
    description: row.querySelector('[data-case-description]').value.trim(),
    sort_order: Number(row.querySelector('[data-case-order]').value || (index + 1) * 10)
  })).filter((item) => item.tag || item.title || item.description)
    .sort((a, b) => a.sort_order - b.sort_order);
}

function collectInquiryFieldRows() {
  return $all('[data-inquiry-field-index]').map((row, index) => ({
    label: row.querySelector('[data-inquiry-field-label]').value.trim(),
    name: row.querySelector('[data-inquiry-field-name]').value.trim(),
    type: row.querySelector('[data-inquiry-field-type]').value,
    placeholder: row.querySelector('[data-inquiry-field-placeholder]').value.trim(),
    required: row.querySelector('[data-inquiry-field-required]').checked,
    sort_order: Number(row.querySelector('[data-inquiry-field-order]').value || (index + 1) * 10)
  })).filter((item) => item.label && item.name)
    .sort((a, b) => a.sort_order - b.sort_order);
}

function collectFaqRows() {
  return $all('[data-faq-index]').map((row, index) => ({
    question: row.querySelector('[data-faq-question]').value.trim(),
    answer: row.querySelector('[data-faq-answer]').value.trim(),
    sort_order: Number(row.querySelector('[data-faq-order]').value || (index + 1) * 10)
  })).filter((item) => item.question || item.answer)
    .sort((a, b) => a.sort_order - b.sort_order);
}

function fillSiteForm(site) {
  const form = $('#siteSettingsForm');
  const data = getDefaultSite(site);
  form.name.value = data.name;
  form.slogan.value = data.slogan;
  form.domain.value = data.domain;
  form.language.value = data.language;
  form.description.value = data.description;
  form.keywords.value = data.keywords;
  form.phone.value = data.phone;
  form.email.value = data.email;
  form.address.value = data.address;
  form.ai_provider.value = data.ai.provider;
  form.ai_model.value = data.ai.model;
  form.ai_endpoint.value = data.ai.endpoint;
  form.ai_api_key.value = data.ai.api_key;
  form.payment_mode.value = data.payment.mode;
  form.payment_currency.value = data.payment.currency;
  form.payment_merchant_id.value = data.payment.merchant_id;
  form.payment_webhook_url.value = data.payment.webhook_url;
  form.deploy_bt_panel_url.value = data.deploy.bt_panel_url;
  form.deploy_site_path.value = data.deploy.site_path;
  form.deploy_mode.value = data.deploy.mode;
  form.deploy_after_action.value = data.deploy.after_action;
  form.deploy_note.value = data.deploy.note;
  form.global_search_nav.checked = Boolean(data.global_modules.search_nav);
  form.global_breadcrumbs.checked = Boolean(data.global_modules.breadcrumbs);
  form.global_related.checked = Boolean(data.global_modules.related);
  form.global_floating_inquiry.checked = Boolean(data.global_modules.floating_inquiry);
  form.global_floating_text.value = data.global_modules.floating_text;
  form.hero_eyebrow.value = data.hero.eyebrow;
  form.hero_title.value = data.hero.title;
  form.hero_subtitle.value = data.hero.subtitle;
  form.hero_primary_text.value = data.hero.primary_text;
  form.hero_secondary_text.value = data.hero.secondary_text;
  form.hero_panel_label.value = data.hero.panel_label;
  form.hero_panel_title.value = data.hero.panel_title;
  form.hero_panel_description.value = data.hero.panel_description;
  form.products_title.value = data.home_sections.products_title;
  form.products_link_text.value = data.home_sections.products_link_text;
  form.articles_title.value = data.home_sections.articles_title;
  form.articles_link_text.value = data.home_sections.articles_link_text;
  form.about_title.value = data.home_sections.about_title;
  form.about_subtitle.value = data.home_sections.about_subtitle;
  form.about_body.value = data.home_sections.about_body;
  form.advantages_title.value = data.home_sections.advantages_title;
  form.cases_title.value = data.home_sections.cases_title;
  form.faq_title.value = data.home_sections.faq_title;
  form.inquiry_title.value = data.home_sections.inquiry_title;
  form.inquiry_subtitle.value = data.home_sections.inquiry_subtitle;
  renderAdvantageRows(data.home_content.advantages);
  renderCaseRows(data.home_content.cases);
  renderInquiryFieldRows(data.home_content.inquiry_fields);
  renderFaqRows(data.home_content.faqs);
  renderModuleRows(data.home_modules);
  renderNavRows(data.nav);
}

function collectSiteForm() {
  const form = $('#siteSettingsForm');
  const current = getDefaultSite(state.site);
  return {
    ...current,
    name: form.name.value.trim(),
    slogan: form.slogan.value.trim(),
    domain: form.domain.value.trim(),
    language: form.language.value.trim() || 'zh-CN',
    description: form.description.value.trim(),
    keywords: form.keywords.value.trim(),
    phone: form.phone.value.trim(),
    email: form.email.value.trim(),
    address: form.address.value.trim(),
    ai: {
      provider: form.ai_provider.value.trim(),
      model: form.ai_model.value.trim(),
      endpoint: form.ai_endpoint.value.trim(),
      api_key: form.ai_api_key.value.trim()
    },
    payment: {
      mode: form.payment_mode.value,
      currency: form.payment_currency.value.trim() || 'CNY',
      merchant_id: form.payment_merchant_id.value.trim(),
      webhook_url: form.payment_webhook_url.value.trim()
    },
    deploy: {
      bt_panel_url: form.deploy_bt_panel_url.value.trim(),
      site_path: form.deploy_site_path.value.trim(),
      mode: form.deploy_mode.value,
      after_action: form.deploy_after_action.value.trim(),
      note: form.deploy_note.value.trim()
    },
    global_modules: {
      search_nav: form.global_search_nav.checked,
      breadcrumbs: form.global_breadcrumbs.checked,
      related: form.global_related.checked,
      floating_inquiry: form.global_floating_inquiry.checked,
      floating_text: form.global_floating_text.value.trim() || '立即咨询'
    },
    hero: {
      eyebrow: form.hero_eyebrow.value.trim(),
      title: form.hero_title.value.trim(),
      subtitle: form.hero_subtitle.value.trim(),
      primary_text: form.hero_primary_text.value.trim(),
      secondary_text: form.hero_secondary_text.value.trim(),
      panel_label: form.hero_panel_label.value.trim(),
      panel_title: form.hero_panel_title.value.trim(),
      panel_description: form.hero_panel_description.value.trim()
    },
    home_sections: {
      products_title: form.products_title.value.trim(),
      products_link_text: form.products_link_text.value.trim(),
      articles_title: form.articles_title.value.trim(),
      articles_link_text: form.articles_link_text.value.trim(),
      about_title: form.about_title.value.trim(),
      about_subtitle: form.about_subtitle.value.trim(),
      about_body: form.about_body.value.trim(),
      advantages_title: form.advantages_title.value.trim(),
      cases_title: form.cases_title.value.trim(),
      faq_title: form.faq_title.value.trim(),
      inquiry_title: form.inquiry_title.value.trim(),
      inquiry_subtitle: form.inquiry_subtitle.value.trim()
    },
    home_content: {
      advantages: collectAdvantageRows(),
      cases: collectCaseRows(),
      inquiry_fields: collectInquiryFieldRows(),
      faqs: collectFaqRows()
    },
    home_modules: collectModuleRows(),
    nav: collectNavRows()
  };
}

async function loadSiteSettings() {
  state.site = getDefaultSite(await request('/api/site/settings'));
  fillSiteForm(state.site);
  return state.site;
}

async function loadModuleRegistry() {
  state.moduleRegistry = await request('/api/site/modules');
  renderModuleRegistry(state.moduleRegistry);
  return state.moduleRegistry;
}

async function saveSiteSettings(event, options = {}) {
  if (event) event.preventDefault();
  const data = collectSiteForm();
  state.site = await request('/api/site/settings', {
    method: 'PUT',
    body: JSON.stringify(data)
  });
  fillSiteForm(state.site);
  toast('站点设置已保存');
  await loadDashboard();
  if (options.generate) {
    await generateSite();
  }
}

async function loadDashboard() {
  const [site, articles, products, orders, media, forms] = await Promise.all([
    request('/api/site/settings'),
    request('/api/articles?page_size=1'),
    request('/api/products?page_size=1'),
    request('/api/orders?page_size=1'),
    request('/api/media?page_size=1'),
    request('/api/forms/submissions?page_size=1')
  ]);
  state.site = getDefaultSite(site);
  $('#statArticles').textContent = articles.pagination.total;
  $('#statProducts').textContent = products.pagination.total;
  $('#statOrders').textContent = orders.pagination.total;
  $('#statMedia').textContent = media.pagination.total;
  $('#statForms').textContent = forms.pagination.total;
  $('#siteInfo').innerHTML = [
    ['站点名称', state.site.name],
    ['标语', state.site.slogan],
    ['域名', state.site.domain],
    ['电话', state.site.phone],
    ['邮箱', state.site.email],
    ['地址', state.site.address]
  ].map(([key, value]) => `<dt>${key}</dt><dd>${escapeHtml(value || '-')}</dd>`).join('');
}

async function loadCategories() {
  const [categories, productCategories] = await Promise.all([
    request('/api/categories'),
    request('/api/product-categories')
  ]);
  state.categories = categories.items;
  state.productCategories = productCategories.items;
  $('#articleCategorySelect').innerHTML = `<option value="">不选择</option>` + state.categories.map((item) => `<option value="${item.id}">${escapeHtml(item.name)}</option>`).join('');
  $('#productCategorySelect').innerHTML = `<option value="">不选择</option>` + state.productCategories.map((item) => `<option value="${item.id}">${escapeHtml(item.name)}</option>`).join('');
}

async function loadArticles() {
  const data = await request('/api/articles?page_size=50');
  state.articles = data.items;
  $('#articleRows').innerHTML = data.items.map((item) => `
    <tr>
      <td><strong>${escapeHtml(item.title)}</strong><br><small>${escapeHtml(item.slug)}</small></td>
      <td><span class="tag ${item.status}">${item.status}</span></td>
      <td>${escapeHtml(item.published_at || '-')}</td>
      <td><div class="row-actions">
        <button class="text-btn" data-edit-article="${item.id}">编辑</button>
        <button class="danger-btn" data-delete-article="${item.id}">删除</button>
      </div></td>
    </tr>
  `).join('');
}

async function loadProducts() {
  const data = await request('/api/products?page_size=50');
  state.products = data.items;
  $('#productRows').innerHTML = data.items.map((item) => `
    <tr>
      <td><strong>${escapeHtml(item.title)}</strong><br><small>${escapeHtml(item.slug)}</small></td>
      <td>￥${escapeHtml(item.price)}</td>
      <td>${escapeHtml(item.stock)}</td>
      <td><div class="row-actions">
        <button class="text-btn" data-edit-product="${item.id}">编辑</button>
        <button class="danger-btn" data-delete-product="${item.id}">删除</button>
      </div></td>
    </tr>
  `).join('');
}

function paymentStatusLabel(status) {
  return {
    pending: '待支付',
    paid: '已支付',
    failed: '支付失败',
    refunded: '已退款'
  }[status] || status || '待支付';
}

function fulfillmentStatusLabel(status) {
  return {
    new: '新订单',
    confirmed: '已确认',
    shipped: '已发货',
    finished: '已完成',
    closed: '已关闭'
  }[status] || status || '新订单';
}

function buildOrderQuery() {
  const params = new URLSearchParams({ page_size: '50' });
  Object.entries(state.orderFilters).forEach(([key, value]) => {
    if (value) params.set(key, value);
  });
  return params.toString();
}

function renderOrderStats(stats = {}) {
  const amount = Number(stats.total_amount || 0).toFixed(2);
  $('#orderStats').innerHTML = [
    ['订单总数', stats.total || 0],
    ['待支付', stats.pending_payment || 0],
    ['待处理', stats.open_orders || 0],
    ['已完成', stats.finished || 0],
    ['累计金额', `CNY ${amount}`]
  ].map(([label, value]) => `
    <div class="mini-stat">
      <span>${escapeHtml(label)}</span>
      <strong>${escapeHtml(value)}</strong>
    </div>
  `).join('');
}

async function applyOrderFilters(form = $('#orderFilterForm')) {
  const data = formToObject(form);
  state.orderFilters = {
    keyword: data.keyword || '',
    payment_status: data.payment_status || '',
    fulfillment_status: data.fulfillment_status || ''
  };
  await loadOrders();
}

function scheduleOrderFilters() {
  clearTimeout(state.orderFilterTimer);
  state.orderFilterTimer = setTimeout(() => {
    applyOrderFilters().catch((error) => toast(error.message));
  }, 300);
}

function syncOrderFilterForm() {
  const form = $('#orderFilterForm');
  form.keyword.value = state.orderFilters.keyword;
  form.payment_status.value = state.orderFilters.payment_status;
  form.fulfillment_status.value = state.orderFilters.fulfillment_status;
}

async function resetOrderFilters() {
  state.orderFilters = { keyword: '', payment_status: '', fulfillment_status: '' };
  syncOrderFilterForm();
  await loadOrders();
}

async function updateSelectedOrder(patch, message) {
  const form = $('#orderDetailForm');
  const id = form.elements.id.value;
  if (!id) {
    toast('请先选择一条订单');
    return;
  }
  const data = formToObject(form);
  await request(`/api/orders/${id}`, {
    method: 'PUT',
    body: JSON.stringify({
      payment_status: data.payment_status || 'pending',
      fulfillment_status: data.fulfillment_status || 'new',
      tracking_company: data.tracking_company || '',
      tracking_no: data.tracking_no || '',
      remark: data.remark || '',
      ...patch
    })
  });
  toast(message);
  await Promise.all([loadOrders(), loadDashboard()]);
}

function renderOrderItems(items) {
  if (!Array.isArray(items) || !items.length) {
    return '<span class="muted">暂无商品明细</span>';
  }
  return items.map((item) => `
    <div class="form-detail-item">
      <small>${escapeHtml(item.sku || item.product_id || '商品')}</small>
      <span>${escapeHtml(item.title || '未命名商品')} × ${escapeHtml(item.quantity || 1)}，${escapeHtml(item.price || 0)}</span>
    </div>
  `).join('');
}

function fillOrderDetail(item) {
  const form = $('#orderDetailForm');
  const detailBox = $('#orderDetailBox');
  if (!item) {
    state.selectedOrderId = null;
    form.reset();
    form.elements.id.value = '';
    detailBox.innerHTML = '选择一条订单查看明细。';
    return;
  }

  let items = [];
  try { items = JSON.parse(item.items || '[]'); } catch {}
  state.selectedOrderId = item.id;
  form.elements.id.value = item.id;
  form.payment_status.value = item.payment_status || 'pending';
  form.fulfillment_status.value = item.fulfillment_status || 'new';
  form.tracking_company.value = item.tracking_company || '';
  form.tracking_no.value = item.tracking_no || '';
  form.followup_note.value = '';
  form.remark.value = item.remark || '';
  detailBox.innerHTML = `
    <div class="form-detail-meta">
      <div><strong>${escapeHtml(item.order_no)}</strong><br><small>${escapeHtml(item.customer_name)} / ${escapeHtml(item.phone)}</small></div>
      <span>${escapeHtml(item.created_at || '')}</span>
    </div>
    <div class="form-detail-list">
      ${renderOrderItems(items)}
      <div class="form-detail-item"><small>金额</small><span>${escapeHtml(item.currency || 'CNY')} ${escapeHtml(item.total_amount || 0)}</span></div>
      <div class="form-detail-item"><small>支付时间</small><span>${escapeHtml(item.paid_at || '-')}</span></div>
      <div class="form-detail-item"><small>发货时间</small><span>${escapeHtml(item.shipped_at || '-')}</span></div>
      <div class="form-detail-item"><small>物流</small><span>${escapeHtml([item.tracking_company, item.tracking_no].filter(Boolean).join(' / ') || '-')}</span></div>
      <div class="form-detail-item"><small>地址</small><span>${escapeHtml(item.address || '-')}</span></div>
      <div class="form-detail-item"><small>邮箱</small><span>${escapeHtml(item.email || '-')}</span></div>
      <div class="form-detail-item"><small>来源</small><span>${escapeHtml(item.source_url || '-')}</span></div>
      <div class="form-detail-item"><small>时间线</small><span class="timeline-text">${escapeHtml(item.remark || '-')}</span></div>
    </div>
  `;
}

async function loadOrders() {
  const data = await request(`/api/orders?${buildOrderQuery()}`);
  state.orders = data.items;
  renderOrderStats(data.stats || {});
  $('#orderRows').innerHTML = data.items.length ? data.items.map((item) => `
    <tr class="${String(state.selectedOrderId) === String(item.id) ? 'selected-row' : ''}">
      <td><strong>${escapeHtml(item.order_no)}</strong><br><small>${escapeHtml(item.payment_method || 'manual')}</small></td>
      <td>${escapeHtml(item.customer_name)}<br><small>${escapeHtml(item.phone)}</small></td>
      <td>${escapeHtml(item.currency || 'CNY')} ${escapeHtml(item.total_amount || 0)}</td>
      <td><span class="tag ${item.payment_status}">${paymentStatusLabel(item.payment_status)}</span><br><span class="tag ${item.fulfillment_status}">${fulfillmentStatusLabel(item.fulfillment_status)}</span></td>
      <td>${escapeHtml(item.created_at)}</td>
      <td><div class="row-actions">
        <button class="text-btn" data-view-order="${item.id}">详情</button>
        <button class="text-btn" data-confirm-order="${item.id}">确认</button>
        <button class="danger-btn" data-delete-order="${item.id}">删除</button>
      </div></td>
    </tr>
  `).join('') : '<tr><td colspan="6" class="empty-cell">暂无匹配订单</td></tr>';
  if (state.selectedOrderId) {
    const selected = state.orders.find((item) => String(item.id) === String(state.selectedOrderId));
    fillOrderDetail(selected || null);
  }
}

async function loadMedia() {
  const data = await request('/api/media?page_size=50');
  state.media = data.items;
  $('#mediaGrid').innerHTML = data.items.map((item) => `
    <article class="media-card">
      ${item.file_type === 'image' ? `<img src="/${escapeHtml(item.file_path)}" alt="${escapeHtml(item.alt_text || item.file_name)}">` : ''}
      <div>
        <strong>${escapeHtml(item.file_name)}</strong>
        <small>/${escapeHtml(item.file_path)}</small>
        <div class="row-actions" style="margin-top:10px">
          ${item.file_type === 'image' ? `<button class="text-btn" data-use-media-cover="article" data-media-path="${escapeHtml(item.file_path)}">设为文章封面</button>` : ''}
          ${item.file_type === 'image' ? `<button class="text-btn" data-use-media-cover="product" data-media-path="${escapeHtml(item.file_path)}">设为商品封面</button>` : ''}
          <button class="danger-btn" data-delete-media="${item.id}">删除</button>
        </div>
      </div>
    </article>
  `).join('');
}

function setCoverFromMedia(type, path) {
  const form = type === 'product' ? $('#productForm') : $('#articleForm');
  form.cover.value = path;
  setView(type === 'product' ? 'products' : 'articles');
  toast(type === 'product' ? '已设为商品封面' : '已设为文章封面');
}

async function chooseLatestMediaCover(type) {
  if (!state.media.length) {
    await loadMedia();
  }
  const item = state.media.find((row) => row.file_type === 'image');
  if (!item) {
    toast('媒体库暂无图片，请先上传或生成封面');
    return;
  }
  setCoverFromMedia(type, item.file_path);
}

function formFieldLabels() {
  const fallback = {
    name: '姓名',
    phone: '电话',
    email: '邮箱',
    message: '需求'
  };
  const fields = state.site?.home_content?.inquiry_fields || [];
  return {
    ...fallback,
    ...Object.fromEntries(fields.map((field) => {
      const label = String(field.label || '').trim();
      const brokenLabel = label !== '' && /^[?\s]+$/.test(label);
      return [field.name, brokenLabel ? (fallback[field.name] || field.name) : (label || fallback[field.name] || field.name)];
    }))
  };
}

function renderFormDetail(detail) {
  const labels = formFieldLabels();
  const entries = Object.entries(detail || {}).filter(([, value]) => String(value ?? '').trim() !== '');
  if (!entries.length) {
    return '<span class="muted">暂无内容</span>';
  }
  return entries.map(([key, value]) => `
    <div class="form-detail-item">
      <small>${escapeHtml(labels[key] || key)}</small>
      <span>${escapeHtml(value)}</span>
    </div>
  `).join('');
}

function formSourceLabel(item) {
  const labels = {
    home_inquiry: '首页询盘',
    contact: '联系表单'
  };
  const formKey = item.form_key || '';
  const label = labels[formKey] || formKey || '表单';
  const source = item.source_url || '-';
  return `<strong>${escapeHtml(label)}</strong><br><small>${escapeHtml(source)}</small>`;
}

function parseFormRemark(value) {
  if (!value) return { level: 'normal', note: '' };
  try {
    const data = JSON.parse(value);
    return {
      level: data.level || 'normal',
      note: data.note || ''
    };
  } catch {
    return { level: 'normal', note: value };
  }
}

function stringifyFormRemark(data) {
  return JSON.stringify({
    level: data.level || 'normal',
    note: data.note || ''
  });
}

function statusLabel(status) {
  return {
    new: '新留言',
    contacted: '已联系',
    processed: '已处理',
    closed: '已关闭'
  }[status] || status || '新留言';
}

function levelLabel(level) {
  return {
    normal: '普通',
    important: '重点',
    high: '高意向',
    low: '低意向'
  }[level] || '普通';
}

function fillFormDetail(item) {
  const form = $('#formDetailForm');
  const detailBox = $('#formDetailBox');
  if (!item) {
    state.selectedFormId = null;
    form.reset();
    form.elements.id.value = '';
    detailBox.innerHTML = '选择一条留言查看详情。';
    return;
  }

  let detail = {};
  try { detail = JSON.parse(item.data || '{}'); } catch {}
  const remark = parseFormRemark(item.remark || '');
  state.selectedFormId = item.id;
  form.elements.id.value = item.id;
  form.status.value = item.status || 'new';
  form.level.value = remark.level || 'normal';
  form.note.value = remark.note || '';
  detailBox.innerHTML = `
    <div class="form-detail-meta">
      <div>${formSourceLabel(item)}</div>
      <span>${escapeHtml(item.created_at || '')}</span>
    </div>
    <div class="form-detail-list">${renderFormDetail(detail)}</div>
  `;
}

async function loadForms() {
  const data = await request('/api/forms/submissions?page_size=50');
  state.forms = data.items;
  $('#formRows').innerHTML = data.items.map((item) => {
    let detail = {};
    try { detail = JSON.parse(item.data || '{}'); } catch {}
    const remark = parseFormRemark(item.remark || '');
    return `
      <tr class="${String(state.selectedFormId) === String(item.id) ? 'selected-row' : ''}">
        <td>${formSourceLabel(item)}</td>
        <td><div class="form-detail-list">${renderFormDetail(detail)}</div></td>
        <td><span class="tag ${item.status}">${statusLabel(item.status)}</span><br><small>${levelLabel(remark.level)}</small></td>
        <td>${escapeHtml(item.created_at)}</td>
        <td><div class="row-actions">
          <button class="text-btn" data-view-form="${item.id}">详情</button>
          <button class="text-btn" data-process-form="${item.id}">标记处理</button>
          <button class="danger-btn" data-delete-form="${item.id}">删除</button>
        </div></td>
      </tr>
    `;
  }).join('');
  if (state.selectedFormId) {
    const selected = state.forms.find((item) => String(item.id) === String(state.selectedFormId));
    fillFormDetail(selected || null);
  }
}

async function loadVersions() {
  const data = await request('/api/site/publish-versions?page_size=20');
  state.versions = data.items;
  $('#versionRows').innerHTML = data.items.map((item) => `
    <tr>
      <td>${escapeHtml(item.version_no)}</td>
      <td>${escapeHtml(item.publish_type)}</td>
      <td><span class="tag published">${escapeHtml(item.status)}</span></td>
      <td>${escapeHtml(item.created_at)}</td>
    </tr>
  `).join('');
}

async function loadAll() {
  await Promise.all([loadSiteSettings(), loadCategories(), loadModuleRegistry()]);
  await Promise.all([loadDashboard(), loadArticles(), loadProducts(), loadOrders(), loadMedia(), loadForms(), loadVersions()]);
}

function resetArticleForm() {
  $('#articleForm').reset();
  $('#articleForm').elements.id.value = '';
  $('#articleForm').elements.auto_publish.checked = true;
  $('#articleFormTitle').textContent = '新建文章';
}

function resetProductForm() {
  $('#productForm').reset();
  $('#productForm').elements.id.value = '';
  $('#productForm').elements.auto_publish.checked = true;
  $('#productFormTitle').textContent = '新建商品';
}

function localArticleDraft(prompt) {
  const industry = siteIndustryText();
  const title = prompt.length > 28 ? prompt.slice(0, 28) : prompt;
  const finalTitle = title.includes('如何') || title.includes('为什么') ? title : `${industry}：${title}`;
  const keywords = [industry, '独立站', 'SEO', '静态网站', '企业官网'].join(',');
  return {
    title: finalTitle,
    slug: slugify(finalTitle, 'article'),
    summary: `本文围绕${prompt}展开，适合用于官网资讯、知识库和搜索引擎关键词沉淀。`,
    content: [
      `<p>${prompt}是企业独立站建设中值得长期投入的主题。通过稳定的内容结构、清晰的产品表达和静态化页面，可以让搜索引擎更容易抓取页面，也让客户更快理解企业能力。</p>`,
      '<h2>一、为什么适合做成独立站内容</h2>',
      `<p>${industry}相关客户通常会通过搜索、社媒和行业渠道了解供应商。把问题、方案、案例和产品资料沉淀为文章，可以不断扩大关键词覆盖面。</p>`,
      '<h2>二、页面应该包含哪些信息</h2>',
      '<p>建议包含行业痛点、解决方案、产品能力、应用场景、成功案例和咨询入口。文章内容不只服务阅读，也要服务后续询盘转化。</p>',
      '<h2>三、如何和商品及询盘联动</h2>',
      '<p>文章可以链接到相关商品详情页、案例模块和首页询盘表单，让客户从阅读自然进入咨询流程。后台保存后再生成静态页，即可同步到前台。</p>'
    ].join('\n'),
    seo_keywords: keywords,
    status: 'draft'
  };
}

function applyArticleDraft(draft) {
  const form = $('#articleForm');
  form.title.value = draft.title || '';
  form.slug.value = draft.slug || slugify(draft.title || 'article', 'article');
  form.cover.value = draft.cover || form.cover.value || '';
  form.summary.value = draft.summary || '';
  form.content.value = draft.content || '';
  form.seo_keywords.value = draft.seo_keywords || '';
  form.status.value = 'draft';
}

async function generateArticleDraft() {
  const form = $('#articleForm');
  const prompt = cleanPrompt(form.ai_prompt.value, '围绕企业官网、独立站和行业关键词写一篇 SEO 文章');
  try {
    const data = await request('/api/ai/generate', {
      method: 'POST',
      body: JSON.stringify({ type: 'article', prompt })
    });
    applyArticleDraft(data.draft || localArticleDraft(prompt));
    toast(data.source === 'remote' ? 'AI 文章草稿已生成' : '本地文章草稿已生成');
  } catch (error) {
    applyArticleDraft(localArticleDraft(prompt));
    toast('后端 AI 暂不可用，已生成本地草稿');
  }
}

async function batchGenerateArticles() {
  const prompt = cleanPrompt($('#batchArticlePrompt').value, '围绕企业官网、独立站和行业关键词生成 SEO 文章');
  const count = Math.min(20, Math.max(1, Number($('#batchArticleCount').value || 5)));
  const status = $('#batchArticleStatus').value || 'draft';
  const autoPublish = $('#batchArticleAutoPublish').checked;
  const data = await request('/api/ai/batch-articles', {
    method: 'POST',
    body: JSON.stringify({ prompt, count, status })
  });
  toast(`已生成 ${data.count || 0} 篇文章草稿`);
  await Promise.all([loadArticles(), loadDashboard()]);
  await generateSiteIfNeeded(autoPublish);
}

async function generateCover(type) {
  const form = type === 'product' ? $('#productForm') : $('#articleForm');
  const title = form.title.value.trim();
  const prompt = cleanPrompt(form.ai_prompt?.value || form.summary?.value || title, type === 'product' ? '商品封面' : '文章封面');
  const data = await request('/api/ai/generate-image', {
    method: 'POST',
    body: JSON.stringify({ type, title, prompt })
  });
  if (data.path) {
    form.cover.value = data.path;
  }
  toast('AI 封面已生成并写入媒体库');
  await Promise.all([loadMedia(), loadDashboard()]);
}

function localProductDraft(prompt) {
  const industry = siteIndustryText();
  const title = prompt.includes('商品') || prompt.includes('产品') ? prompt.slice(0, 30) : `${prompt.slice(0, 24)}方案`;
  return {
    title,
    slug: slugify(title, 'product'),
    sku: `HJ-${Date.now().toString(36).toUpperCase().slice(-6)}`,
    cover: 'assets/images/product-1.svg',
    summary: `${title}面向${industry}场景，适合用于独立站商品展示、方案介绍和客户询盘转化。`,
    description: [
      `<p>${title}是一款围绕${prompt}设计的产品方案，适合在企业官网、行业知识库和独立站商城中进行展示。</p>`,
      '<h2>核心卖点</h2>',
      '<ul><li>信息结构清晰，便于客户快速理解产品价值。</li><li>支持与文章、案例和询盘表单联动，提升转化路径完整度。</li><li>适合静态化发布，访问速度快，利于 SEO 收录。</li></ul>',
      '<h2>适用场景</h2>',
      `<p>适用于${industry}相关的产品展示、解决方案页面、渠道招商页面和搜索投放承接页。</p>`,
      '<h2>咨询建议</h2>',
      '<p>客户可通过首页询盘表单提交需求，后台客服再根据客户等级和跟进备注持续推进。</p>'
    ].join('\n'),
    price: 0,
    stock: 999,
    status: 'draft'
  };
}

function applyProductDraft(draft) {
  const form = $('#productForm');
  form.title.value = draft.title || '';
  form.slug.value = draft.slug || slugify(draft.title || 'product', 'product');
  form.sku.value = draft.sku || `HJ-${Date.now().toString(36).toUpperCase().slice(-6)}`;
  form.cover.value = draft.cover || form.cover.value || 'assets/images/product-1.svg';
  form.summary.value = draft.summary || '';
  form.description.value = draft.description || '';
  form.price.value = draft.price ?? 0;
  form.stock.value = draft.stock ?? 999;
  form.status.value = 'draft';
}

async function generateProductDraft() {
  const form = $('#productForm');
  const prompt = cleanPrompt(form.ai_prompt.value, '适合企业独立站展示的行业产品');
  try {
    const data = await request('/api/ai/generate', {
      method: 'POST',
      body: JSON.stringify({ type: 'product', prompt })
    });
    applyProductDraft(data.draft || localProductDraft(prompt));
    toast(data.source === 'remote' ? 'AI 商品草稿已生成' : '本地商品草稿已生成');
  } catch (error) {
    applyProductDraft(localProductDraft(prompt));
    toast('后端 AI 暂不可用，已生成本地草稿');
  }
}

async function batchGenerateProducts() {
  const prompt = cleanPrompt($('#batchProductPrompt').value, '围绕企业独立站生成商品展示草稿');
  const count = Math.min(20, Math.max(1, Number($('#batchProductCount').value || 5)));
  const status = $('#batchProductStatus').value || 'draft';
  const autoPublish = $('#batchProductAutoPublish').checked;
  const data = await request('/api/ai/batch-products', {
    method: 'POST',
    body: JSON.stringify({ prompt, count, status })
  });
  toast(`已生成 ${data.count || 0} 个商品草稿`);
  await Promise.all([loadProducts(), loadDashboard()]);
  await generateSiteIfNeeded(autoPublish);
}

async function saveArticle(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const data = formToObject(form);
  const id = data.id;
  const autoPublish = form.auto_publish.checked;
  delete data.id;
  delete data.ai_prompt;
  delete data.auto_publish;
  data.category_id = data.category_id || null;
  data.published_at = data.status === 'published' ? new Date().toISOString().slice(0, 19).replace('T', ' ') : null;
  await request(id ? `/api/articles/${id}` : '/api/articles', {
    method: id ? 'PUT' : 'POST',
    body: JSON.stringify(data)
  });
  toast('文章已保存');
  resetArticleForm();
  await Promise.all([loadArticles(), loadDashboard()]);
  await generateSiteIfNeeded(autoPublish);
}

async function saveProduct(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const data = formToObject(form);
  const id = data.id;
  const autoPublish = form.auto_publish.checked;
  delete data.id;
  delete data.ai_prompt;
  delete data.auto_publish;
  data.category_id = data.category_id || null;
  data.price = Number(data.price || 0);
  data.stock = Number(data.stock || 0);
  data.published_at = data.status === 'published' ? new Date().toISOString().slice(0, 19).replace('T', ' ') : null;
  await request(id ? `/api/products/${id}` : '/api/products', {
    method: id ? 'PUT' : 'POST',
    body: JSON.stringify(data)
  });
  toast('商品已保存');
  resetProductForm();
  await Promise.all([loadProducts(), loadDashboard()]);
  await generateSiteIfNeeded(autoPublish);
}

async function generateSite() {
  $('#publishStatus').textContent = '正在生成静态站...';
  const data = await request('/api/site/generate', { method: 'POST' });
  $('#publishStatus').textContent = `生成成功：${data.version_no}，文件数 ${data.file_count}`;
  toast('静态站已生成');
  await Promise.all([loadVersions(), loadDashboard()]);
}

async function generateSiteIfNeeded(enabled) {
  if (!enabled) return;
  await generateSite();
}

async function generatePagePlan() {
  const prompt = cleanPrompt($('#pagePlanPrompt').value, '帮我生成一个适合企业官网、博客知识库和独立站商品展示的首页');
  $('#generatePagePlanBtn').disabled = true;
  $('#pagePlanPreview').textContent = '正在生成页面草案...';
  try {
    const data = await request('/api/ai/page-plan', {
      method: 'POST',
      body: JSON.stringify({ prompt })
    });
    state.pagePlan = data;
    renderPagePlan(state.pagePlan);
    toast('首页搭建草案已生成');
  } finally {
    $('#generatePagePlanBtn').disabled = false;
  }
}

async function login(event) {
  event.preventDefault();
  const data = formToObject(event.currentTarget);
  const result = await request('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify(data)
  });
  setToken(result.token);
  showApp();
  toast('登录成功');
  await loadAll();
}

async function logout() {
  try {
    await request('/api/auth/logout', { method: 'POST' });
  } catch {}
  setToken('');
  showLogin();
}

document.addEventListener('click', async (event) => {
  const target = event.target;
  const nav = target.closest('.nav-item');
  if (nav) setView(nav.dataset.view);

  if (target.matches('[data-refresh="articles"]')) loadArticles().catch((error) => toast(error.message));
  if (target.matches('[data-refresh="products"]')) loadProducts().catch((error) => toast(error.message));
  if (target.matches('[data-refresh="orders"]')) loadOrders().catch((error) => toast(error.message));
  if (target.matches('[data-refresh="media"]')) loadMedia().catch((error) => toast(error.message));
  if (target.matches('[data-refresh="forms"]')) loadForms().catch((error) => toast(error.message));
  if (target.matches('[data-refresh="versions"]')) loadVersions().catch((error) => toast(error.message));

  if (target.closest('[data-apply-order-filter]')) {
    event.preventDefault();
    await applyOrderFilters();
  }

  const navDelete = target.closest('[data-delete-nav]');
  if (navDelete) {
    navDelete.closest('[data-nav-index]')?.remove();
  }

  const advantageDelete = target.closest('[data-delete-advantage]');
  if (advantageDelete) {
    advantageDelete.closest('[data-advantage-index]')?.remove();
  }

  const caseDelete = target.closest('[data-delete-case]');
  if (caseDelete) {
    caseDelete.closest('[data-case-index]')?.remove();
  }

  const inquiryFieldDelete = target.closest('[data-delete-inquiry-field]');
  if (inquiryFieldDelete) {
    inquiryFieldDelete.closest('[data-inquiry-field-index]')?.remove();
  }

  const faqDelete = target.closest('[data-delete-faq]');
  if (faqDelete) {
    faqDelete.closest('[data-faq-index]')?.remove();
  }

  const moduleMove = target.closest('[data-move-module]');
  if (moduleMove) {
    moveModuleRow(moduleMove.closest('[data-module-key]'), moduleMove.dataset.moveModule);
  }

  const moduleRemove = target.closest('[data-remove-module]');
  if (moduleRemove) {
    moduleRemove.closest('[data-module-key]')?.remove();
    normalizeModuleOrders();
    renderModulePresetRows();
  }

  const moduleAdd = target.closest('[data-add-module]');
  if (moduleAdd) {
    addModuleFromPreset(moduleAdd.dataset.addModule);
  }

  const articleEdit = target.closest('[data-edit-article]');
  if (articleEdit) {
    const item = state.articles.find((row) => String(row.id) === articleEdit.dataset.editArticle);
    if (!item) return;
    const form = $('#articleForm');
    form.elements.id.value = item.id;
    form.title.value = item.title || '';
    form.slug.value = item.slug || '';
    form.cover.value = item.cover || '';
    form.category_id.value = item.category_id || '';
    form.summary.value = item.summary || '';
    form.content.value = item.content || '';
    form.seo_keywords.value = item.seo_keywords || '';
    form.status.value = item.status || 'draft';
    $('#articleFormTitle').textContent = '编辑文章';
  }

  const productEdit = target.closest('[data-edit-product]');
  if (productEdit) {
    const item = state.products.find((row) => String(row.id) === productEdit.dataset.editProduct);
    if (!item) return;
    const form = $('#productForm');
    form.elements.id.value = item.id;
    form.title.value = item.title || '';
    form.slug.value = item.slug || '';
    form.sku.value = item.sku || '';
    form.category_id.value = item.category_id || '';
    form.cover.value = item.cover || '';
    form.summary.value = item.summary || '';
    form.description.value = item.description || '';
    form.price.value = item.price || 0;
    form.stock.value = item.stock || 0;
    form.status.value = item.status || 'draft';
    $('#productFormTitle').textContent = '编辑商品';
  }

  const articleDelete = target.closest('[data-delete-article]');
  if (articleDelete && confirm('确定删除这篇文章？')) {
    await request(`/api/articles/${articleDelete.dataset.deleteArticle}`, { method: 'DELETE' });
    toast('文章已删除');
    await Promise.all([loadArticles(), loadDashboard()]);
  }

  const productDelete = target.closest('[data-delete-product]');
  if (productDelete && confirm('确定删除这个商品？')) {
    await request(`/api/products/${productDelete.dataset.deleteProduct}`, { method: 'DELETE' });
    toast('商品已删除');
    await Promise.all([loadProducts(), loadDashboard()]);
  }

  const orderView = target.closest('[data-view-order]');
  if (orderView) {
    const item = state.orders.find((row) => String(row.id) === String(orderView.dataset.viewOrder));
    fillOrderDetail(item || null);
    await loadOrders();
  }

  const orderConfirm = target.closest('[data-confirm-order]');
  if (orderConfirm) {
    const current = state.orders.find((row) => String(row.id) === String(orderConfirm.dataset.confirmOrder));
    await request(`/api/orders/${orderConfirm.dataset.confirmOrder}`, {
      method: 'PUT',
      body: JSON.stringify({
        payment_status: current?.payment_status || 'pending',
        fulfillment_status: 'confirmed',
        remark: current?.remark || '后台已确认订单'
      })
    });
    toast('订单已确认');
    await Promise.all([loadOrders(), loadDashboard()]);
  }

  const orderDelete = target.closest('[data-delete-order]');
  if (orderDelete && confirm('确定删除这条订单？')) {
    await request(`/api/orders/${orderDelete.dataset.deleteOrder}`, { method: 'DELETE' });
    toast('订单已删除');
    if (String(state.selectedOrderId) === String(orderDelete.dataset.deleteOrder)) {
      fillOrderDetail(null);
    }
    await Promise.all([loadOrders(), loadDashboard()]);
  }

  const mediaDelete = target.closest('[data-delete-media]');
  if (mediaDelete && confirm('确定删除这个媒体文件？')) {
    await request(`/api/media/${mediaDelete.dataset.deleteMedia}`, { method: 'DELETE' });
    toast('媒体已删除');
    await Promise.all([loadMedia(), loadDashboard()]);
  }

  const useMediaCover = target.closest('[data-use-media-cover]');
  if (useMediaCover) {
    setCoverFromMedia(useMediaCover.dataset.useMediaCover, useMediaCover.dataset.mediaPath);
  }

  const formProcess = target.closest('[data-process-form]');
  if (formProcess) {
    const current = state.forms.find((row) => String(row.id) === String(formProcess.dataset.processForm));
    await request(`/api/forms/submissions/${formProcess.dataset.processForm}`, {
      method: 'PUT',
      body: JSON.stringify({ status: 'processed', remark: current?.remark || stringifyFormRemark({ level: 'normal', note: '后台已处理' }) })
    });
    toast('留言已处理');
    await Promise.all([loadForms(), loadDashboard()]);
  }

  const formView = target.closest('[data-view-form]');
  if (formView) {
    const item = state.forms.find((row) => String(row.id) === String(formView.dataset.viewForm));
    fillFormDetail(item || null);
    await loadForms();
  }

  const formDelete = target.closest('[data-delete-form]');
  if (formDelete && confirm('确定删除这条留言？')) {
    await request(`/api/forms/submissions/${formDelete.dataset.deleteForm}`, { method: 'DELETE' });
    toast('留言已删除');
    if (String(state.selectedFormId) === String(formDelete.dataset.deleteForm)) {
      fillFormDetail(null);
    }
    await Promise.all([loadForms(), loadDashboard()]);
  }
});

document.addEventListener('pointerdown', (event) => {
  const target = event.target;
  if (target.closest('[data-apply-order-filter]')) {
    event.preventDefault();
    applyOrderFilters().catch((error) => toast(error.message));
  }
  if (target.closest('#resetOrderFilterBtn')) {
    event.preventDefault();
    resetOrderFilters().catch((error) => toast(error.message));
  }
});

$('#loginForm').addEventListener('submit', (event) => login(event).catch((error) => toast(error.message)));
$('#logoutBtn').addEventListener('click', logout);
$('#addNavItemBtn').addEventListener('click', () => {
  const rows = collectNavRows();
  rows.push({ title: '新菜单', url: '#', sort_order: (rows.length + 1) * 10 });
  renderNavRows(rows);
});
$('#addAdvantageBtn').addEventListener('click', () => {
  const rows = collectAdvantageRows();
  rows.push({ title: '新优势', description: '填写这条优势的说明。', sort_order: (rows.length + 1) * 10 });
  renderAdvantageRows(rows);
});
$('#addCaseBtn').addEventListener('click', () => {
  const rows = collectCaseRows();
  rows.push({ tag: '新案例', title: '新案例标题', description: '填写这个案例的说明。', sort_order: (rows.length + 1) * 10 });
  renderCaseRows(rows);
});
$('#addInquiryFieldBtn').addEventListener('click', () => {
  const rows = collectInquiryFieldRows();
  rows.push({
    label: '新字段',
    name: `field_${rows.length + 1}`,
    type: 'text',
    placeholder: '请输入内容',
    required: false,
    sort_order: (rows.length + 1) * 10
  });
  renderInquiryFieldRows(rows);
});
$('#addFaqBtn').addEventListener('click', () => {
  const rows = collectFaqRows();
  rows.push({ question: '新问题', answer: '填写这个问题的答案。', sort_order: (rows.length + 1) * 10 });
  renderFaqRows(rows);
});
$('#siteSettingsForm').addEventListener('submit', (event) => saveSiteSettings(event).catch((error) => toast(error.message)));
$('#saveGenerateBtn').addEventListener('click', () => saveSiteSettings(null, { generate: true }).catch((error) => toast(error.message)));
$('#generatePagePlanBtn').addEventListener('click', () => generatePagePlan().catch((error) => {
  renderPagePlan(null);
  toast(error.message);
}));
$('#applyPagePlanBtn').addEventListener('click', () => applyPagePlan());
$('#applyGeneratePagePlanBtn').addEventListener('click', () => applyPagePlanAndGenerate().catch((error) => toast(error.message)));
$('#generateArticleDraftBtn').addEventListener('click', generateArticleDraft);
$('#batchGenerateArticlesBtn').addEventListener('click', () => batchGenerateArticles().catch((error) => toast(error.message)));
$('#chooseArticleCoverBtn').addEventListener('click', () => chooseLatestMediaCover('article').catch((error) => toast(error.message)));
$('#generateArticleCoverBtn').addEventListener('click', () => generateCover('article').catch((error) => toast(error.message)));
$('#generateProductDraftBtn').addEventListener('click', generateProductDraft);
$('#batchGenerateProductsBtn').addEventListener('click', () => batchGenerateProducts().catch((error) => toast(error.message)));
$('#chooseProductCoverBtn').addEventListener('click', () => chooseLatestMediaCover('product').catch((error) => toast(error.message)));
$('#generateProductCoverBtn').addEventListener('click', () => generateCover('product').catch((error) => toast(error.message)));
$('#articleForm').addEventListener('submit', (event) => saveArticle(event).catch((error) => toast(error.message)));
$('#productForm').addEventListener('submit', (event) => saveProduct(event).catch((error) => toast(error.message)));
$('#orderDetailForm').addEventListener('submit', async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const id = form.elements.id.value;
  if (!id) {
    toast('请先选择一条订单');
    return;
  }
  const data = formToObject(form);
  await request(`/api/orders/${id}`, {
    method: 'PUT',
    body: JSON.stringify({
      payment_status: data.payment_status || 'pending',
      fulfillment_status: data.fulfillment_status || 'new',
      tracking_company: data.tracking_company || '',
      tracking_no: data.tracking_no || '',
      followup_note: data.followup_note || '',
      remark: data.remark || ''
    })
  });
  toast('订单已保存');
  await Promise.all([loadOrders(), loadDashboard()]);
});
$('#clearOrderDetailBtn').addEventListener('click', () => fillOrderDetail(null));
$('#markOrderPaidBtn').addEventListener('click', () => updateSelectedOrder({
  payment_status: 'paid',
  followup_note: '客服标记订单已支付'
}, '订单已标记为已支付').catch((error) => toast(error.message)));
$('#markOrderShippedBtn').addEventListener('click', () => updateSelectedOrder({
  fulfillment_status: 'shipped',
  followup_note: '客服标记订单已发货'
}, '订单已标记为已发货').catch((error) => toast(error.message)));
$('#orderFilterForm').addEventListener('submit', async (event) => {
  event.preventDefault();
  await applyOrderFilters(event.currentTarget);
});
$('#orderFilterForm').addEventListener('input', scheduleOrderFilters);
$('#orderFilterForm').addEventListener('change', scheduleOrderFilters);
$('#resetOrderFilterBtn').addEventListener('click', async () => {
  await resetOrderFilters();
});
$('#mediaForm').addEventListener('submit', async (event) => {
  event.preventDefault();
  try {
    await request('/api/media/upload', { method: 'POST', body: new FormData(event.currentTarget) });
    event.currentTarget.reset();
    toast('媒体已上传');
    await Promise.all([loadMedia(), loadDashboard()]);
  } catch (error) {
    toast(error.message);
  }
});
$('#formDetailForm').addEventListener('submit', async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const id = form.elements.id.value;
  if (!id) {
    toast('请先选择一条留言');
    return;
  }
  const item = state.forms.find((row) => String(row.id) === String(id));
  const data = formToObject(form);
  await request(`/api/forms/submissions/${id}`, {
    method: 'PUT',
    body: JSON.stringify({
      status: data.status || item?.status || 'new',
      remark: stringifyFormRemark({ level: data.level, note: data.note })
    })
  });
  toast('跟进记录已保存');
  await Promise.all([loadForms(), loadDashboard()]);
});
$('#clearFormDetailBtn').addEventListener('click', () => fillFormDetail(null));
$('#resetArticleBtn').addEventListener('click', resetArticleForm);
$('#resetProductBtn').addEventListener('click', resetProductForm);
$('#generateBtn').addEventListener('click', () => generateSite().catch((error) => toast(error.message)));
$('#generateTopBtn').addEventListener('click', () => generateSite().catch((error) => toast(error.message)));
$('#deployCheckBtn').addEventListener('click', async () => {
  try {
    $('#publishStatus').textContent = '正在检查部署配置...';
    const data = await request('/api/site/deploy-test', { method: 'POST' });
    $('#publishStatus').textContent = data.message || '部署配置检查完成';
    toast('部署配置检查完成');
    await Promise.all([loadVersions(), loadDashboard()]);
  } catch (error) {
    $('#publishStatus').textContent = error.message;
    toast(error.message);
  }
});
$('#reloadDashboardBtn').addEventListener('click', () => loadDashboard().catch((error) => toast(error.message)));

(async function boot() {
  if (!token()) {
    showLogin();
    return;
  }
  try {
    await request('/api/auth/me');
    showApp();
    await loadAll();
  } catch (error) {
    toast(error.message);
  }
})();
