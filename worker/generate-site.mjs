import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const dataRoot = path.join(root, 'demo-data');
const templateRoot = path.join(root, 'templates', 'business-clean');
const publicRoot = path.join(root, 'sites', 'site_10001', 'public');

function readJson(file) {
  return JSON.parse(fs.readFileSync(path.join(dataRoot, file), 'utf8'));
}

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function writeFile(file, content) {
  ensureDir(path.dirname(file));
  fs.writeFileSync(file, content, 'utf8');
}

function copyDir(src, dst) {
  ensureDir(dst);
  for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
    const from = path.join(src, entry.name);
    const to = path.join(dst, entry.name);
    if (entry.isDirectory()) copyDir(from, to);
    else fs.copyFileSync(from, to);
  }
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function resolveValue(expr, context) {
  return expr.trim().split('.').reduce((value, key) => {
    if (value && Object.prototype.hasOwnProperty.call(value, key)) return value[key];
    return undefined;
  }, context);
}

function renderFile(relativePath, context) {
  if (relativePath.includes('..')) throw new Error(`Unsafe template path: ${relativePath}`);
  return render(fs.readFileSync(path.join(templateRoot, relativePath), 'utf8'), context);
}

function seoMeta(context) {
  const site = context.site || {};
  const seo = context.seo || {};
  return [
    `<title>${escapeHtml(seo.title || site.name || '')}</title>`,
    `  <meta name="description" content="${escapeHtml(seo.description || site.description || '')}">`,
    `  <meta name="keywords" content="${escapeHtml(seo.keywords || site.keywords || '')}">`
  ].join('\n');
}

function render(template, context) {
  let output = template;

  output = output.replace(/\{\{\s*include\s+"([^"]+)"\s*\}\}/g, (_, file) => renderFile(file, context));
  output = output.replaceAll('{{ seo_meta }}', seoMeta(context));
  output = output.replace(/\{\{\s*asset\s+'([^']+)'\s*\}\}/g, (_, file) => `${context.asset_base || ''}assets/${file}`);
  output = output.replace(/\{\{\s*url\s+'([^']+)'\s*\}\}/g, (_, name) => (name === 'home' ? `${context.root_base || ''}index.html` : '#'));

  output = output.replace(/\{\{\s*if\s+([^}]+)\s*\}\}([\s\S]*?)(?:\{\{\s*else\s*\}\}([\s\S]*?))?\{\{\s*\/if\s*\}\}/g, (_, expr, truthy, falsy = '') => {
    return resolveValue(expr, context) ? truthy : falsy;
  });

  output = output.replace(/\{\{\s*each\s+([^}]+)\s*\}\}([\s\S]*?)\{\{\s*\/each\s*\}\}/g, (_, expr, body) => {
    const items = resolveValue(expr, context);
    if (!Array.isArray(items)) return '';
    return items.map((item, idx) => render(body, { ...context, item, index: idx + 1 })).join('');
  });

  output = output.replace(/\{\{\{\s*([^}]+)\s*\}\}\}/g, (_, expr) => String(resolveValue(expr, context) ?? ''));
  output = output.replace(/\{\{\s*([^}]+)\s*\}\}/g, (_, expr) => escapeHtml(resolveValue(expr, context)));
  return output;
}

const site = readJson('site.json');
const categories = readJson('categories.json');
const productCategories = readJson('product-categories.json');
const articles = readJson('articles.json');
const products = readJson('products.json');

const categoryMap = new Map(categories.map((category) => [category.id, category]));
for (const article of articles) {
  article.category = categoryMap.get(article.category_id) || null;
}

copyDir(path.join(templateRoot, 'assets'), path.join(publicRoot, 'assets'));

function siteFor(rootBase) {
  return {
    ...site,
    nav: [
      { title: '首页', url: `${rootBase}index.html` },
      { title: '行业资讯', url: `${rootBase}news/index.html` },
      { title: '产品中心', url: `${rootBase}products/index.html` },
      { title: '联系我们', url: `${rootBase}contact.html` }
    ]
  };
}

function withUrls(items, prefix) {
  return items.map((item) => ({ ...item, url: `${prefix}${item.slug}.html` }));
}

function baseContext(rootBase = '', assetBase = '') {
  return {
    site: siteFor(rootBase),
    categories,
    product_categories: productCategories,
    articles: withUrls(articles.slice(0, 6), `${rootBase}news/`),
    products: withUrls(products.slice(0, 6), `${rootBase}products/`),
    root_base: rootBase,
    asset_base: assetBase
  };
}

writeFile(path.join(publicRoot, 'index.html'), renderFile('pages/index.html', {
  ...baseContext('', ''),
  seo: {
    title: `${site.name} - ${site.slogan}`,
    description: site.description,
    keywords: site.keywords
  }
}));

writeFile(path.join(publicRoot, 'contact.html'), renderFile('pages/contact.html', {
  ...baseContext('', ''),
  seo: {
    title: `联系我们 - ${site.name}`,
    description: `${site.name}联系方式和咨询表单。`,
    keywords: site.keywords
  }
}));

writeFile(path.join(publicRoot, 'news', 'index.html'), renderFile('pages/article-list.html', {
  ...baseContext('../', '../'),
  articles: withUrls(articles, ''),
  seo: {
    title: `行业资讯 - ${site.name}`,
    description: '低空经济、无人机和数字化转型行业资讯。',
    keywords: site.keywords
  }
}));

for (const article of articles) {
  writeFile(path.join(publicRoot, 'news', `${article.slug}.html`), renderFile('pages/article.html', {
    ...baseContext('../', '../'),
    article,
    seo: {
      title: article.seo_title,
      description: article.seo_description,
      keywords: article.seo_keywords
    }
  }));
}

writeFile(path.join(publicRoot, 'products', 'index.html'), renderFile('pages/product-list.html', {
  ...baseContext('../', '../'),
  products: withUrls(products, ''),
  seo: {
    title: `产品中心 - ${site.name}`,
    description: '无人机产品和低空经济数字化系统。',
    keywords: site.keywords
  }
}));

for (const product of products) {
  writeFile(path.join(publicRoot, 'products', `${product.slug}.html`), renderFile('pages/product.html', {
    ...baseContext('../', '../'),
    product,
    seo: {
      title: product.seo_title,
      description: product.seo_description,
      keywords: product.seo_keywords
    }
  }));
}

const sitemap = [
  '<?xml version="1.0" encoding="UTF-8"?>',
  '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
  `  <url><loc>https://${site.domain}/index.html</loc></url>`,
  `  <url><loc>https://${site.domain}/contact.html</loc></url>`,
  ...articles.map((article) => `  <url><loc>https://${site.domain}/news/${article.slug}.html</loc></url>`),
  ...products.map((product) => `  <url><loc>https://${site.domain}/products/${product.slug}.html</loc></url>`),
  '</urlset>'
].join('\n');

writeFile(path.join(publicRoot, 'sitemap.xml'), sitemap);
writeFile(path.join(publicRoot, 'robots.txt'), `User-agent: *\nAllow: /\nSitemap: https://${site.domain}/sitemap.xml\n`);

const search = [
  ...articles.map((article) => ({ type: 'article', title: article.title, summary: article.summary, url: `news/${article.slug}.html` })),
  ...products.map((product) => ({ type: 'product', title: product.title, summary: product.summary, url: `products/${product.slug}.html` }))
];
writeFile(path.join(publicRoot, 'search.json'), JSON.stringify(search, null, 2));

console.log(`Generated site: ${publicRoot}`);
