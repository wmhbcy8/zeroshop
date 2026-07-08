<?php

declare(strict_types=1);

class HuajianTemplateEngine
{
    public function __construct(private string $templateRoot)
    {
        $this->templateRoot = rtrim($templateRoot, DIRECTORY_SEPARATOR);
    }

    public function renderFile(string $relativePath, array $context): string
    {
        return $this->render(file_get_contents($this->safePath($relativePath)), $context);
    }

    public function render(string $template, array $context): string
    {
        $template = $this->renderIncludes($template, $context);
        $template = $this->renderSeoMeta($template, $context);
        $template = $this->renderAssets($template);
        $template = $this->renderUrls($template);
        $template = $this->renderConditionals($template, $context);
        $template = $this->renderLoops($template, $context);
        $template = $this->renderRawVariables($template, $context);
        $template = $this->renderEscapedVariables($template, $context);
        $template = str_replace('__ASSET_BASE__', $context['asset_base'] ?? '', $template);
        return str_replace('__ROOT_BASE__', $context['root_base'] ?? '', $template);
    }

    private function renderIncludes(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{\s*include\s+"([^"]+)"\s*\}\}/', fn($m) => $this->renderFile($m[1], $context), $template);
    }

    private function renderSeoMeta(string $template, array $context): string
    {
        $seo = $context['seo'] ?? [];
        $site = $context['site'] ?? [];
        $title = $seo['title'] ?? ($site['name'] ?? '');
        $description = $seo['description'] ?? ($site['description'] ?? '');
        $keywords = $seo['keywords'] ?? ($site['keywords'] ?? '');
        $html = '<title>' . htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') . '</title>' . PHP_EOL;
        $html .= '  <meta name="description" content="' . htmlspecialchars((string)$description, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
        $html .= '  <meta name="keywords" content="' . htmlspecialchars((string)$keywords, ENT_QUOTES, 'UTF-8') . '">';
        return str_replace('{{ seo_meta }}', $html, $template);
    }

    private function renderAssets(string $template): string
    {
        return preg_replace('/\{\{\s*asset\s+\'([^\']+)\'\s*\}\}/', '__ASSET_BASE__assets/$1', $template);
    }

    private function renderUrls(string $template): string
    {
        return preg_replace_callback('/\{\{\s*url\s+\'([^\']+)\'\s*\}\}/', fn($m) => $m[1] === 'home' ? '__ROOT_BASE__index.html' : '#', $template);
    }

    private function renderConditionals(string $template, array $context): string
    {
        $pattern = '/\{\{\s*if\s+([^}]+)\s*\}\}(.*?)((\{\{\s*else\s*\}\})(.*?))?\{\{\s*\/if\s*\}\}/s';
        return preg_replace_callback($pattern, function ($m) use ($context) {
            $value = $this->resolve(trim($m[1]), $context);
            return !empty($value) ? $m[2] : ($m[5] ?? '');
        }, $template);
    }

    private function renderLoops(string $template, array $context): string
    {
        $pattern = '/\{\{\s*each\s+([^}]+)\s*\}\}(.*?)\{\{\s*\/each\s*\}\}/s';
        return preg_replace_callback($pattern, function ($m) use ($context) {
            $items = $this->resolve(trim($m[1]), $context);
            if (!is_array($items)) {
                return '';
            }
            $out = '';
            foreach (array_values($items) as $index => $item) {
                $loopContext = $context;
                $loopContext['item'] = $item;
                $loopContext['index'] = $index + 1;
                $out .= $this->render($m[2], $loopContext);
            }
            return $out;
        }, $template);
    }

    private function renderRawVariables(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{\{\s*([^}]+)\s*\}\}\}/', fn($m) => (string)($this->resolve(trim($m[1]), $context) ?? ''), $template);
    }

    private function renderEscapedVariables(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($m) use ($context) {
            return htmlspecialchars((string)($this->resolve(trim($m[1]), $context) ?? ''), ENT_QUOTES, 'UTF-8');
        }, $template);
    }

    private function resolve(string $path, array $context): mixed
    {
        $value = $context;
        foreach (explode('.', $path) as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } else {
                return null;
            }
        }
        return $value;
    }

    private function safePath(string $relativePath): string
    {
        if (str_contains($relativePath, '..')) {
            throw new RuntimeException('Unsafe template path: ' . $relativePath);
        }
        $path = $this->templateRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        if (!is_file($path)) {
            throw new RuntimeException('Template file not found: ' . $relativePath);
        }
        return $path;
    }
}

function read_json(string $path): array
{
    return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
}

function env_or_null(string $key): ?string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? null : $value;
}

function pdo_site(): ?PDO
{
    $host = env_or_null('HJ_DB_HOST');
    $user = env_or_null('HJ_DB_USERNAME');
    $database = env_or_null('HJ_DB_SITE');
    if (!$host || !$user || !$database) {
        return null;
    }
    $port = env_or_null('HJ_DB_PORT') ?: '3306';
    $password = env_or_null('HJ_DB_PASSWORD') ?: '';
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function load_data_from_mysql(PDO $pdo): array
{
    $setting = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'site'")->fetchColumn();
    if (!$setting) {
        throw new RuntimeException('Missing site setting in database.');
    }

    $site = json_decode($setting, true, 512, JSON_THROW_ON_ERROR);
    $categories = $pdo->query("SELECT id, name, slug, description FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll();
    $productCategories = $pdo->query("SELECT id, name, slug, description FROM product_categories ORDER BY sort_order ASC, id ASC")->fetchAll();
    $articles = $pdo->query("SELECT id, category_id, title, slug, cover, summary, content, seo_title, seo_keywords, seo_description, published_at FROM articles WHERE status = 'published' ORDER BY published_at DESC, id DESC")->fetchAll();
    $products = $pdo->query("SELECT id, category_id, title, slug, sku, cover, summary, description, price, market_price, stock, seo_title, seo_keywords, seo_description FROM products WHERE status = 'published' ORDER BY id DESC")->fetchAll();

    foreach ($products as &$product) {
        $product['price'] = number_format((float)$product['price'], 2, '.', '');
        $product['market_price'] = number_format((float)$product['market_price'], 2, '.', '');
    }
    unset($product);

    return [$site, $categories, $productCategories, $articles, $products];
}

function write_file(string $path, string $content): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($path, $content);
}

function copy_dir(string $src, string $dst): void
{
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        $target = $dst . DIRECTORY_SEPARATOR . $items->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0777, true);
            }
        } else {
            write_file($target, file_get_contents($item->getPathname()));
        }
    }
}

function site_for(array $site, string $rootBase): array
{
    $site['payment'] = array_replace([
        'mode' => 'manual',
        'currency' => 'CNY',
        'merchant_id' => '',
        'webhook_url' => '',
        'account' => '',
        'instructions' => '订单提交后，请根据客服确认的金额完成转账，并在订单查询页提交付款说明或截图编号。',
    ], $site['payment'] ?? []);

    $nav = $site['nav'] ?? [
        ['title' => '首页', 'url' => 'index.html'],
        ['title' => '行业资讯', 'url' => 'news/index.html'],
        ['title' => '产品中心', 'url' => 'products/index.html'],
        ['title' => '联系我们', 'url' => 'contact.html'],
    ];
    $site['nav'] = array_map(function (array $item) use ($rootBase) {
        $url = $item['url'] ?? '#';
        if (!preg_match('#^https?://#', $url) && !str_starts_with($url, $rootBase)) {
            $url = $rootBase . ltrim($url, '/');
        }
        return ['title' => $item['title'] ?? '', 'url' => $url];
    }, $nav);
    $site['hero'] = array_replace([
        'eyebrow' => '化简静态建站 MVP',
        'title' => $site['slogan'] ?? $site['name'] ?? '',
        'subtitle' => $site['description'] ?? '',
        'primary_text' => '查看产品',
        'secondary_text' => '阅读资讯',
        'panel_label' => '静态页面',
        'panel_title' => 'HTML + CSS + JS',
        'panel_description' => '发布时生成，访问时无需数据库。',
    ], $site['hero'] ?? []);
    $site['home_sections'] = array_replace([
        'products_title' => '推荐产品',
        'products_link_text' => '全部产品',
        'articles_title' => '行业资讯',
        'articles_link_text' => '全部文章',
        'about_title' => '关于我们',
        'about_subtitle' => '用轻量化系统连接产品、内容和客户。',
        'about_body' => '通过模块化页面、内容管理和静态化发布，让企业官网、知识库和商品展示更容易长期运营。',
        'advantages_title' => '核心优势',
        'cases_title' => '应用案例',
        'faq_title' => '常见问题',
        'inquiry_title' => '获取方案与报价',
        'inquiry_subtitle' => '留下需求，我们会根据行业场景、产品类型和部署方式给出建站建议。',
    ], $site['home_sections'] ?? []);
    $site['global_modules'] = array_replace([
        'search_nav' => true,
        'breadcrumbs' => true,
        'related' => true,
        'floating_inquiry' => true,
        'floating_text' => '立即咨询',
    ], $site['global_modules'] ?? []);
    $site['payment'] = array_replace([
        'mode' => 'manual',
        'currency' => 'CNY',
        'merchant_id' => '',
        'webhook_url' => '',
    ], $site['payment'] ?? []);
    $site['home_content'] = array_replace([
        'advantages' => [
            ['title' => '静态化发布', 'description' => '前台页面生成 HTML 文件，访问速度快，部署成本低，也更利于搜索引擎抓取。'],
            ['title' => '内容可运营', 'description' => '文章、商品、媒体和留言都在后台统一管理，适合长期做行业关键词沉淀。'],
            ['title' => '模块化搭建', 'description' => '首页结构由模块配置驱动，后续可由 AI 根据客户行业自动组合页面。'],
        ],
        'cases' => [
            ['title' => '农业植保设备展示站', 'description' => '围绕产品参数、应用场景和询盘表单搭建静态独立站，承接搜索流量。', 'tag' => '农业无人机'],
            ['title' => '低空经济解决方案官网', 'description' => '用资讯、案例和产品模块呈现企业能力，适合做行业关键词长期沉淀。', 'tag' => '企业官网'],
            ['title' => '行业知识库内容站', 'description' => '通过文章聚合和搜索索引生成大量静态内容页面，提升收录和转化路径。', 'tag' => 'SEO 内容站'],
        ],
        'inquiry_fields' => [
            ['label' => '姓名', 'name' => 'name', 'type' => 'text', 'placeholder' => '姓名', 'required' => false, 'sort_order' => 10],
            ['label' => '电话', 'name' => 'phone', 'type' => 'tel', 'placeholder' => '电话', 'required' => false, 'sort_order' => 20],
            ['label' => '邮箱', 'name' => 'email', 'type' => 'email', 'placeholder' => '邮箱', 'required' => false, 'sort_order' => 30],
            ['label' => '需求', 'name' => 'message', 'type' => 'textarea', 'placeholder' => '请简单描述你的产品、行业或建站需求', 'required' => false, 'sort_order' => 40],
        ],
        'faqs' => [
            ['question' => '静态站还能管理商品吗？', 'answer' => '可以。商品在后台维护，发布时生成商品列表页和详情页；询盘、订单、支付等动态能力通过 API 承接。'],
            ['question' => '客户修改内容后需要手动改代码吗？', 'answer' => '不需要。客户在后台保存内容后，点击生成静态站即可同步到前台页面。'],
            ['question' => '这种方式适合批量独立站吗？', 'answer' => '适合。每个站点可以独立数据库或独立配置，前台产物是静态文件，便于批量部署和缓存。'],
        ],
    ], $site['home_content'] ?? []);
    $modules = $site['home_modules'] ?? [
        ['key' => 'about', 'title' => '图文介绍', 'enabled' => true, 'sort_order' => 10],
        ['key' => 'advantages', 'title' => '优势卖点', 'enabled' => true, 'sort_order' => 20],
        ['key' => 'cases', 'title' => '案例展示', 'enabled' => true, 'sort_order' => 30],
        ['key' => 'products', 'title' => '产品模块', 'enabled' => true, 'sort_order' => 40],
        ['key' => 'articles', 'title' => '文章模块', 'enabled' => true, 'sort_order' => 50],
        ['key' => 'faq', 'title' => 'FAQ', 'enabled' => true, 'sort_order' => 60],
        ['key' => 'inquiry', 'title' => '询盘表单', 'enabled' => true, 'sort_order' => 70],
    ];
    $modules = array_values(array_filter($modules, fn($item) => !empty($item['enabled'])));
    usort($modules, fn($a, $b) => (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0));
    $site['home_slots'] = array_map(function (array $item) {
        $key = $item['key'] ?? '';
        return [
            'key' => $key,
            'title' => $item['title'] ?? '',
            'sort_order' => (int)($item['sort_order'] ?? 0),
            'is_about' => $key === 'about',
            'is_advantages' => $key === 'advantages',
            'is_cases' => $key === 'cases',
            'is_products' => $key === 'products',
            'is_articles' => $key === 'articles',
            'is_faq' => $key === 'faq',
            'is_inquiry' => $key === 'inquiry',
        ];
    }, $modules);
    return $site;
}

function with_urls(array $items, string $prefix): array
{
    return array_map(function ($item) use ($prefix) {
        $item['url'] = $prefix . $item['slug'] . '.html';
        return $item;
    }, $items);
}

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function global_modules(array $site): array
{
    return site_for($site, '')['global_modules'];
}

function floating_actions_html(array $site, string $rootBase): string
{
    $modules = global_modules($site);
    if (empty($modules['floating_inquiry'])) {
        return '';
    }

    $text = (string)($modules['floating_text'] ?? '立即咨询');
    return '<div class="floating-actions"><a href="' . e($rootBase . 'contact.html') . '">' . e($text) . '</a></div>';
}

function breadcrumb_html(array $site, array $items): string
{
    $modules = global_modules($site);
    if (empty($modules['breadcrumbs'])) {
        return '';
    }

    $html = '<nav class="breadcrumb" aria-label="Breadcrumb">';
    foreach ($items as $index => $item) {
        $title = (string)($item['title'] ?? '');
        $url = (string)($item['url'] ?? '');
        if ($url !== '' && $index < count($items) - 1) {
            $html .= '<a href="' . e($url) . '">' . e($title) . '</a>';
        } else {
            $html .= '<span>' . e($title) . '</span>';
        }
    }
    return $html . '</nav>';
}

function related_html(array $site, array $items, string $title): string
{
    $modules = global_modules($site);
    if (empty($modules['related']) || count($items) === 0) {
        return '';
    }

    $html = '<section class="related-section">';
    $html .= '<div class="section-head"><h2>' . e($title) . '</h2></div>';
    $html .= '<div class="related-grid">';
    foreach (array_slice($items, 0, 3) as $item) {
        $html .= '<a class="related-item" href="' . e($item['url'] ?? '#') . '">';
        $html .= '<span>' . e($item['meta'] ?? '') . '</span>';
        $html .= '<strong>' . e($item['title'] ?? '') . '</strong>';
        $html .= '</a>';
    }
    $html .= '</div></section>';
    return $html;
}

function form_field_name(string $name): string
{
    $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);
    return $name !== '' ? $name : 'field';
}

function inquiry_field_html(array $field): string
{
    $name = form_field_name((string)($field['name'] ?? ''));
    $type = in_array(($field['type'] ?? 'text'), ['text', 'tel', 'email', 'textarea'], true) ? $field['type'] : 'text';
    $placeholder = (string)($field['placeholder'] ?? $field['label'] ?? '');
    $required = !empty($field['required']) ? ' required' : '';

    if ($type === 'textarea') {
        return '<textarea name="' . e($name) . '" placeholder="' . e($placeholder) . '"' . $required . '></textarea>';
    }

    return '<input type="' . e($type) . '" name="' . e($name) . '" placeholder="' . e($placeholder) . '"' . $required . '>';
}

function home_modules_html(array $site, array $articles, array $products, string $rootBase): string
{
    $site = site_for($site, $rootBase);
    $sections = $site['home_sections'];
    $articleItems = with_urls(array_slice($articles, 0, 6), $rootBase . 'news/');
    $productItems = with_urls(array_slice($products, 0, 6), $rootBase . 'products/');
    $html = '';

    foreach ($site['home_slots'] as $slot) {
        if (!empty($slot['is_about'])) {
            $html .= '<section class="section about-section">';
            $html .= '<div class="about-copy">';
            $html .= '<span class="eyebrow">About</span>';
            $html .= '<h2>' . e($sections['about_title']) . '</h2>';
            $html .= '<p class="lead">' . e($sections['about_subtitle']) . '</p>';
            $html .= '<p>' . e($sections['about_body']) . '</p>';
            $html .= '</div>';
            $html .= '<div class="about-metrics">';
            $html .= '<div><strong>' . count($productItems) . '</strong><span>产品展示</span></div>';
            $html .= '<div><strong>' . count($articleItems) . '</strong><span>内容页面</span></div>';
            $html .= '<div><strong>HTML</strong><span>静态发布</span></div>';
            $html .= '</div>';
            $html .= '</section>';
        }

        if (!empty($slot['is_advantages'])) {
            $html .= '<section class="section">';
            $html .= '<div class="section-head"><h2>' . e($sections['advantages_title']) . '</h2></div>';
            $html .= '<div class="feature-grid">';
            foreach (($site['home_content']['advantages'] ?? []) as $advantage) {
                $html .= '<article class="feature-card">';
                $html .= '<strong>' . e($advantage['title'] ?? '') . '</strong>';
                $html .= '<p>' . e($advantage['description'] ?? '') . '</p>';
                $html .= '</article>';
            }
            $html .= '</div></section>';
        }

        if (!empty($slot['is_cases'])) {
            $html .= '<section class="section">';
            $html .= '<div class="section-head"><h2>' . e($sections['cases_title']) . '</h2></div>';
            $html .= '<div class="case-grid">';
            foreach (($site['home_content']['cases'] ?? []) as $case) {
                $html .= '<article class="case-card">';
                $html .= '<span>' . e($case['tag'] ?? '') . '</span>';
                $html .= '<strong>' . e($case['title'] ?? '') . '</strong>';
                $html .= '<p>' . e($case['description'] ?? '') . '</p>';
                $html .= '</article>';
            }
            $html .= '</div></section>';
        }

        if (!empty($slot['is_products'])) {
            $html .= '<section class="section">';
            $html .= '<div class="section-head"><h2>' . e($sections['products_title']) . '</h2><a href="' . e($rootBase . 'products/index.html') . '">' . e($sections['products_link_text']) . '</a></div>';
            $html .= '<div class="card-grid">';
            foreach ($productItems as $product) {
                $html .= '<a class="card product-card" href="' . e($product['url']) . '">';
                $html .= '<img src="' . e($product['cover'] ?? '') . '" alt="' . e($product['title'] ?? '') . '">';
                $html .= '<h3>' . e($product['title'] ?? '') . '</h3>';
                $html .= '<p>' . e($product['summary'] ?? '') . '</p>';
                $html .= '<span class="price">￥' . e($product['price'] ?? '') . '</span>';
                $html .= '</a>';
            }
            $html .= '</div></section>';
        }

        if (!empty($slot['is_articles'])) {
            $html .= '<section class="section">';
            $html .= '<div class="section-head"><h2>' . e($sections['articles_title']) . '</h2><a href="' . e($rootBase . 'news/index.html') . '">' . e($sections['articles_link_text']) . '</a></div>';
            $html .= '<div class="article-list">';
            foreach ($articleItems as $article) {
                $html .= '<a class="article-row" href="' . e($article['url']) . '">';
                $html .= '<span>' . e($article['published_at'] ?? '') . '</span>';
                $html .= '<strong>' . e($article['title'] ?? '') . '</strong>';
                $html .= '<p>' . e($article['summary'] ?? '') . '</p>';
                $html .= '</a>';
            }
            $html .= '</div></section>';
        }

        if (!empty($slot['is_faq'])) {
            $html .= '<section class="section">';
            $html .= '<div class="section-head"><h2>' . e($sections['faq_title']) . '</h2></div>';
            $html .= '<div class="faq-list">';
            foreach (($site['home_content']['faqs'] ?? []) as $faq) {
                $html .= '<details class="faq-item">';
                $html .= '<summary>' . e($faq['question'] ?? '') . '</summary>';
                $html .= '<p>' . e($faq['answer'] ?? '') . '</p>';
                $html .= '</details>';
            }
            $html .= '</div></section>';
        }

        if (!empty($slot['is_inquiry'])) {
            $html .= '<section class="section inquiry-section">';
            $html .= '<div class="inquiry-copy">';
            $html .= '<span class="eyebrow">Inquiry</span>';
            $html .= '<h2>' . e($sections['inquiry_title']) . '</h2>';
            $html .= '<p>' . e($sections['inquiry_subtitle']) . '</p>';
            $html .= '<div class="contact-mini">';
            $html .= '<span>电话：' . e($site['phone'] ?? '') . '</span>';
            $html .= '<span>邮箱：' . e($site['email'] ?? '') . '</span>';
            $html .= '</div></div>';
            $html .= '<form class="inquiry-form" data-api="/api/forms/submit">';
            $html .= '<input type="hidden" name="form_key" value="home_inquiry">';
            $fields = $site['home_content']['inquiry_fields'] ?? [];
            usort($fields, fn($a, $b) => (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0));
            foreach ($fields as $field) {
                $html .= inquiry_field_html($field);
            }
            $html .= '<button type="submit">提交需求</button>';
            $html .= '</form></section>';
        }
    }

    return $html;
}

function base_context(array $site, array $categories, array $productCategories, array $articles, array $products, string $rootBase = '', string $assetBase = ''): array
{
    return [
        'site' => site_for($site, $rootBase),
        'articles' => with_urls(array_slice($articles, 0, 6), $rootBase . 'news/'),
        'products' => with_urls(array_slice($products, 0, 6), $rootBase . 'products/'),
        'home_modules' => home_modules_html($site, $articles, $products, $rootBase),
        'floating_actions' => floating_actions_html($site, $rootBase),
        'breadcrumb_html' => '',
        'related_html' => '',
        'categories' => $categories,
        'product_categories' => $productCategories,
        'root_base' => $rootBase,
        'asset_base' => $assetBase,
    ];
}

$root = dirname(__DIR__);
$dataRoot = $root . DIRECTORY_SEPARATOR . 'demo-data';
$templateRoot = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'business-clean';
$publicRoot = $root . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'site_10001' . DIRECTORY_SEPARATOR . 'public';

$pdo = pdo_site();
if ($pdo) {
    [$site, $categories, $productCategories, $articles, $products] = load_data_from_mysql($pdo);
    $dataSource = 'mysql';
} else {
    $site = read_json($dataRoot . DIRECTORY_SEPARATOR . 'site.json');
    $categories = read_json($dataRoot . DIRECTORY_SEPARATOR . 'categories.json');
    $productCategories = read_json($dataRoot . DIRECTORY_SEPARATOR . 'product-categories.json');
    $articles = read_json($dataRoot . DIRECTORY_SEPARATOR . 'articles.json');
    $products = read_json($dataRoot . DIRECTORY_SEPARATOR . 'products.json');
    $dataSource = 'json';
}

$categoryMap = [];
foreach ($categories as $category) {
    $categoryMap[$category['id']] = $category;
}
foreach ($articles as &$article) {
    $article['category'] = $categoryMap[$article['category_id']] ?? null;
}
unset($article);

$engine = new HuajianTemplateEngine($templateRoot);
copy_dir($templateRoot . DIRECTORY_SEPARATOR . 'assets', $publicRoot . DIRECTORY_SEPARATOR . 'assets');

write_file($publicRoot . DIRECTORY_SEPARATOR . 'index.html', $engine->renderFile('pages/index.html', base_context($site, $categories, $productCategories, $articles, $products) + [
    'seo' => [
        'title' => ($site['name'] ?? '') . ' - ' . ($site['slogan'] ?? ''),
        'description' => $site['description'] ?? '',
        'keywords' => $site['keywords'] ?? '',
    ],
]));

write_file($publicRoot . DIRECTORY_SEPARATOR . 'contact.html', $engine->renderFile('pages/contact.html', base_context($site, $categories, $productCategories, $articles, $products) + [
    'seo' => [
        'title' => '联系我们 - ' . ($site['name'] ?? ''),
        'description' => ($site['name'] ?? '') . '联系方式和咨询表单。',
        'keywords' => $site['keywords'] ?? '',
    ],
]));

write_file($publicRoot . DIRECTORY_SEPARATOR . 'search.html', $engine->renderFile('pages/search.html', base_context($site, $categories, $productCategories, $articles, $products) + [
    'seo' => [
        'title' => '站内搜索 - ' . ($site['name'] ?? ''),
        'description' => '搜索' . ($site['name'] ?? '') . '的文章、产品和页面摘要。',
        'keywords' => $site['keywords'] ?? '',
    ],
]));

write_file($publicRoot . DIRECTORY_SEPARATOR . 'order.html', $engine->renderFile('pages/order.html', base_context($site, $categories, $productCategories, $articles, $products) + [
    'seo' => [
        'title' => '订单查询 - ' . ($site['name'] ?? ''),
        'description' => '查询' . ($site['name'] ?? '') . '商城订单的支付、发货和物流状态。',
        'keywords' => $site['keywords'] ?? '',
    ],
]));

write_file($publicRoot . DIRECTORY_SEPARATOR . 'news' . DIRECTORY_SEPARATOR . 'index.html', $engine->renderFile('pages/article-list.html', base_context($site, $categories, $productCategories, $articles, $products, '../', '../') + [
    'articles' => with_urls($articles, ''),
    'seo' => [
        'title' => '行业资讯 - ' . ($site['name'] ?? ''),
        'description' => '低空经济、无人机和数字化转型行业资讯。',
        'keywords' => $site['keywords'] ?? '',
    ],
]));

foreach ($articles as $article) {
    $relatedArticles = array_values(array_filter($articles, fn($item) => (int)$item['id'] !== (int)$article['id']));
    $relatedArticles = array_map(fn($item) => [
        'title' => $item['title'] ?? '',
        'url' => $item['slug'] . '.html',
        'meta' => $item['published_at'] ?? '文章',
    ], $relatedArticles);
    write_file($publicRoot . DIRECTORY_SEPARATOR . 'news' . DIRECTORY_SEPARATOR . $article['slug'] . '.html', $engine->renderFile('pages/article.html', array_replace(base_context($site, $categories, $productCategories, $articles, $products, '../', '../'), [
        'article' => $article,
        'breadcrumb_html' => breadcrumb_html($site, [
            ['title' => '首页', 'url' => '../index.html'],
            ['title' => '行业资讯', 'url' => 'index.html'],
            ['title' => $article['title'] ?? '', 'url' => ''],
        ]),
        'related_html' => related_html($site, $relatedArticles, '相关阅读'),
        'seo' => [
            'title' => $article['seo_title'] ?? $article['title'],
            'description' => $article['seo_description'] ?? $article['summary'],
            'keywords' => $article['seo_keywords'] ?? '',
        ],
    ])));
}

write_file($publicRoot . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . 'index.html', $engine->renderFile('pages/product-list.html', base_context($site, $categories, $productCategories, $articles, $products, '../', '../') + [
    'products' => with_urls($products, ''),
    'seo' => [
        'title' => '产品中心 - ' . ($site['name'] ?? ''),
        'description' => '无人机产品和低空经济数字化系统。',
        'keywords' => $site['keywords'] ?? '',
    ],
]));

foreach ($products as $product) {
    $relatedProducts = array_values(array_filter($products, fn($item) => (int)$item['id'] !== (int)$product['id']));
    $relatedProducts = array_map(fn($item) => [
        'title' => $item['title'] ?? '',
        'url' => $item['slug'] . '.html',
        'meta' => isset($item['price']) ? ('￥' . $item['price']) : '产品',
    ], $relatedProducts);
    write_file($publicRoot . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . $product['slug'] . '.html', $engine->renderFile('pages/product.html', array_replace(base_context($site, $categories, $productCategories, $articles, $products, '../', '../'), [
        'product' => $product,
        'breadcrumb_html' => breadcrumb_html($site, [
            ['title' => '首页', 'url' => '../index.html'],
            ['title' => '产品中心', 'url' => 'index.html'],
            ['title' => $product['title'] ?? '', 'url' => ''],
        ]),
        'related_html' => related_html($site, $relatedProducts, '相关产品'),
        'seo' => [
            'title' => $product['seo_title'] ?? $product['title'],
            'description' => $product['seo_description'] ?? $product['summary'],
            'keywords' => $product['seo_keywords'] ?? '',
        ],
    ])));
}

$domain = $site['domain'] ?? 'demo.local';
$sitemap = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
$sitemap[] = '  <url><loc>https://' . $domain . '/index.html</loc></url>';
$sitemap[] = '  <url><loc>https://' . $domain . '/contact.html</loc></url>';
$sitemap[] = '  <url><loc>https://' . $domain . '/search.html</loc></url>';
$sitemap[] = '  <url><loc>https://' . $domain . '/order.html</loc></url>';
foreach ($articles as $article) {
    $sitemap[] = '  <url><loc>https://' . $domain . '/news/' . $article['slug'] . '.html</loc></url>';
}
foreach ($products as $product) {
    $sitemap[] = '  <url><loc>https://' . $domain . '/products/' . $product['slug'] . '.html</loc></url>';
}
$sitemap[] = '</urlset>';
write_file($publicRoot . DIRECTORY_SEPARATOR . 'sitemap.xml', implode(PHP_EOL, $sitemap));
write_file($publicRoot . DIRECTORY_SEPARATOR . 'robots.txt', "User-agent: *\nAllow: /\nSitemap: https://{$domain}/sitemap.xml\n");

$search = [];
foreach ($articles as $article) {
    $search[] = ['type' => 'article', 'title' => $article['title'], 'summary' => $article['summary'], 'url' => 'news/' . $article['slug'] . '.html'];
}
foreach ($products as $product) {
    $search[] = ['type' => 'product', 'title' => $product['title'], 'summary' => $product['summary'], 'url' => 'products/' . $product['slug'] . '.html'];
}
write_file($publicRoot . DIRECTORY_SEPARATOR . 'search.json', json_encode($search, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "Generated site from {$dataSource}: {$publicRoot}\n";
