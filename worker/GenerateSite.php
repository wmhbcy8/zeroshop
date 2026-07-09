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
    $siteId = (int)(env_or_null('HJ_SITE_ID') ?: 10001);
    $setting = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'site'")->fetchColumn();
    if (!$setting) {
        throw new RuntimeException('Missing site setting in database.');
    }

    $site = json_decode($setting, true, 512, JSON_THROW_ON_ERROR);
    $hasSiteOverride = false;
    $stmt = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute(['site_' . $siteId]);
    $override = $stmt->fetchColumn();
    if ($override) {
        $overrideData = json_decode((string)$override, true, 512, JSON_THROW_ON_ERROR);
        if (is_array($overrideData)) {
            $site = array_replace_recursive($site, $overrideData);
            $hasSiteOverride = true;
        }
    }
    $site['id'] = $siteId;
    if (!$hasSiteOverride || empty($site['name'])) {
        $site['name'] = env_or_null('HJ_SITE_NAME') ?: ($site['name'] ?? '');
    }
    if (!$hasSiteOverride || empty($site['domain'])) {
        $site['domain'] = env_or_null('HJ_SITE_DOMAIN') ?: ($site['domain'] ?? '');
    }
    if (!$hasSiteOverride || empty($site['language'])) {
        $site['language'] = env_or_null('HJ_SITE_LANGUAGE') ?: ($site['language'] ?? 'zh-CN');
    }
    if (!$hasSiteOverride || empty($site['template_key'])) {
        $site['template_key'] = env_or_null('HJ_TEMPLATE_KEY') ?: ($site['template_key'] ?? 'business-clean');
    }
    $categories = $pdo->query("SELECT id, name, slug, description FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll();
    $productCategories = $pdo->query("SELECT id, name, slug, description FROM product_categories ORDER BY sort_order ASC, id ASC")->fetchAll();
    $articles = $pdo->query("SELECT id, category_id, title, slug, cover, summary, content, seo_title, seo_keywords, seo_description, published_at FROM articles WHERE status = 'published' ORDER BY published_at DESC, id DESC")->fetchAll();
    $products = $pdo->query("SELECT id, category_id, title, slug, sku, cover, summary, description, price, market_price, stock, seo_title, seo_keywords, seo_description FROM products WHERE status = 'published' ORDER BY id DESC")->fetchAll();
    $pages = [];
    try {
        $tableExists = (bool)$pdo->query("SHOW TABLES LIKE 'pages'")->fetchColumn();
        if ($tableExists) {
            $pages = $pdo->query("SELECT id, title, slug, cover, summary, content, seo_title, seo_keywords, seo_description, published_at FROM pages WHERE status = 'published' ORDER BY id DESC")->fetchAll();
        }
    } catch (Throwable $error) {
        $pages = [];
    }
    $articles = filter_distributed_content($pdo, 'article', $articles, $siteId);
    $products = filter_distributed_content($pdo, 'product', $products, $siteId);
    $pages = filter_distributed_content($pdo, 'page', $pages, $siteId);
    $articles = attach_article_tags($pdo, $articles);

    foreach ($products as &$product) {
        $product['price'] = number_format((float)$product['price'], 2, '.', '');
        $product['market_price'] = number_format((float)$product['market_price'], 2, '.', '');
    }
    unset($product);

    return [$site, $categories, $productCategories, $articles, $products, $pages];
}

function attach_article_tags(PDO $pdo, array $articles): array
{
    try {
        if (!$articles) {
            return $articles;
        }
        $tagTableExists = (bool)$pdo->query("SHOW TABLES LIKE 'tags'")->fetchColumn();
        $relTableExists = (bool)$pdo->query("SHOW TABLES LIKE 'article_tags'")->fetchColumn();
        if (!$tagTableExists || !$relTableExists) {
            return array_map(fn($article) => $article + ['tags' => []], $articles);
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', array_column($articles, 'id')), fn($id) => $id > 0)));
        if (!$ids) {
            return array_map(fn($article) => $article + ['tags' => []], $articles);
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT at.article_id, t.id, t.name, t.slug, t.description
            FROM article_tags at
            INNER JOIN tags t ON t.id = at.tag_id
            WHERE at.article_id IN ({$placeholders})
            ORDER BY t.name ASC");
        $stmt->execute($ids);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $articleId = (int)$row['article_id'];
            $map[$articleId] ??= [];
            $map[$articleId][] = [
                'id' => (int)$row['id'],
                'name' => (string)$row['name'],
                'slug' => (string)$row['slug'],
                'description' => (string)($row['description'] ?? ''),
            ];
        }
        return array_map(function (array $article) use ($map) {
            $article['tags'] = $map[(int)$article['id']] ?? [];
            return $article;
        }, $articles);
    } catch (Throwable $error) {
        return array_map(fn($article) => $article + ['tags' => []], $articles);
    }
}

function filter_distributed_content(PDO $pdo, string $type, array $items, int $siteId): array
{
    try {
        $tableExists = (bool)$pdo->query("SHOW TABLES LIKE 'content_site_relations'")->fetchColumn();
        if (!$tableExists || !$items) {
            return $items;
        }
        $hasRelations = (int)$pdo->query("SELECT COUNT(*) FROM content_site_relations WHERE content_type = " . $pdo->quote($type))->fetchColumn();
        if ($hasRelations === 0) {
            return $items;
        }
        $stmt = $pdo->prepare("SELECT content_id FROM content_site_relations WHERE content_type = ? AND site_id = ?");
        $stmt->execute([$type, $siteId]);
        $allowed = array_flip(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
        return array_values(array_filter($items, fn($item) => isset($allowed[(int)$item['id']])));
    } catch (Throwable $error) {
        return $items;
    }
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
    if (!is_dir($src)) {
        return;
    }
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

function template_editable_regions(string $templateRoot): array
{
    $path = $templateRoot . DIRECTORY_SEPARATOR . 'editable-regions.json';
    if (!is_file($path)) {
        return [];
    }
    try {
        $payload = read_json($path);
        $regions = $payload['regions'] ?? $payload;
        return is_array($regions) ? $regions : [];
    } catch (Throwable $error) {
        return [];
    }
}

function css_selector_to_xpath(string $selector): ?string
{
    $selector = trim($selector);
    if ($selector === '' || str_contains($selector, ',') || str_contains($selector, '>')) {
        return null;
    }
    $parts = preg_split('/\s+/', $selector) ?: [];
    $xpath = '';
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        if (preg_match('/^\[class\*=["\']?([^"\']+)["\']?\]$/', $part, $m)) {
            $xpath .= "//*[contains(concat(' ', normalize-space(@class), ' '), '" . htmlspecialchars($m[1], ENT_QUOTES) . "')]";
            continue;
        }
        if (preg_match('/^#([a-zA-Z0-9_-]+)$/', $part, $m)) {
            $xpath .= "//*[@id='" . $m[1] . "']";
            continue;
        }
        if (preg_match('/^\.([a-zA-Z0-9_-]+)$/', $part, $m)) {
            $xpath .= "//*[contains(concat(' ', normalize-space(@class), ' '), ' " . $m[1] . " ')]";
            continue;
        }
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z0-9_-]+)$/', $part, $m)) {
            $xpath .= '//' . strtolower($m[1]) . "[contains(concat(' ', normalize-space(@class), ' '), ' " . $m[2] . " ')]";
            continue;
        }
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $part)) {
            $xpath .= '//' . strtolower($part);
            continue;
        }
        return null;
    }
    return $xpath ?: null;
}

function dom_set_inner_html(DOMDocument $dom, DOMElement $node, string $html): void
{
    while ($node->firstChild) {
        $node->removeChild($node->firstChild);
    }
    if (trim($html) === '') {
        return;
    }
    $fragment = $dom->createDocumentFragment();
    if (@$fragment->appendXML($html)) {
        $node->appendChild($fragment);
        return;
    }
    $node->appendChild($dom->createTextNode($html));
}

function dom_first_descendant(DOMElement $node, string $tag): ?DOMElement
{
    if (strtolower($node->tagName) === strtolower($tag)) {
        return $node;
    }
    foreach ($node->getElementsByTagName($tag) as $child) {
        if ($child instanceof DOMElement) {
            return $child;
        }
    }
    return null;
}

function apply_static_mirror_region_overrides(string $templateRoot, string $publicRoot, string $templateKey, array $site): void
{
    if (!class_exists('DOMDocument')) {
        return;
    }
    $allOverrides = is_array($site['template_region_overrides'] ?? null) ? $site['template_region_overrides'] : [];
    $overrides = is_array($allOverrides[$templateKey] ?? null) ? $allOverrides[$templateKey] : [];
    if (!$overrides) {
        return;
    }
    $regions = template_editable_regions($templateRoot);
    if (!$regions) {
        return;
    }
    $htmlFiles = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($publicRoot, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'html') {
            $htmlFiles[] = $file->getPathname();
        }
    }
    foreach ($regions as $region) {
        if (!is_array($region)) {
            continue;
        }
        $regionId = (string)($region['id'] ?? $region['selector'] ?? '');
        $override = is_array($overrides[$regionId] ?? null) ? $overrides[$regionId] : [];
        $text = trim((string)($override['text'] ?? ''));
        $html = trim((string)($override['html'] ?? ''));
        $image = trim((string)($override['image'] ?? ''));
        $link = trim((string)($override['link'] ?? ''));
        if ($regionId === '' || ($text === '' && $html === '' && $image === '' && $link === '')) {
            continue;
        }
        $source = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)($region['source_file'] ?? ''));
        $source = preg_replace('/^mirror[\/\\\\]/', '', $source);
        $candidateFiles = $source ? [$publicRoot . DIRECTORY_SEPARATOR . $source] : $htmlFiles;
        $selectors = $region['selectors'] ?? ($region['selector'] ?? []);
        if (is_string($selectors)) {
            $selectors = [$selectors];
        }
        foreach ($candidateFiles as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }
            $content = file_get_contents($filePath);
            if ($content === false || trim($content) === '') {
                continue;
            }
            $dom = new DOMDocument('1.0', 'UTF-8');
            libxml_use_internal_errors(true);
            $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            if (!$loaded) {
                continue;
            }
            $xpath = new DOMXPath($dom);
            $changed = false;
            foreach ($selectors as $selector) {
                $query = css_selector_to_xpath((string)$selector);
                if (!$query) {
                    continue;
                }
                $nodes = $xpath->query($query);
                if (!$nodes || !$nodes->length) {
                    continue;
                }
                $node = $nodes->item(0);
                if (!$node instanceof DOMElement) {
                    continue;
                }
                if ($html !== '') {
                    dom_set_inner_html($dom, $node, $html);
                    $changed = true;
                } elseif ($text !== '') {
                    while ($node->firstChild) {
                        $node->removeChild($node->firstChild);
                    }
                    $node->appendChild($dom->createTextNode($text));
                    $changed = true;
                }
                if ($image !== '' && ($img = dom_first_descendant($node, 'img'))) {
                    $img->setAttribute('src', $image);
                    $changed = true;
                }
                if ($link !== '' && ($anchor = dom_first_descendant($node, 'a'))) {
                    $anchor->setAttribute('href', $link);
                    $changed = true;
                }
                break;
            }
            if ($changed) {
                $output = $dom->saveHTML();
                $output = preg_replace('/^<\?xml encoding="UTF-8"\?>\s*/', '', (string)$output);
                write_file($filePath, $output);
            }
        }
    }
}

function remove_path(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    if (is_file($path) || is_link($path)) {
        unlink($path);
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($path);
}

function reset_generated_output(string $publicRoot): void
{
    $generatedPaths = [
        'assets',
        'news',
        'products',
        'index.html',
        'contact.html',
        'search.html',
        'order.html',
        'cart.html',
        '404.html',
        'sitemap.xml',
        'robots.txt',
        'rss.xml',
        'search.json',
    ];
    foreach ($generatedPaths as $relativePath) {
        remove_path($publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    }
}

function site_for(array $site, string $rootBase): array
{
    $apiBase = trim((string)($site['api_base'] ?? ($site['api']['base_url'] ?? (env_or_null('HJ_PUBLIC_API_BASE') ?: ''))));
    $apiBase = rtrim($apiBase, '/');
    $site['api_base'] = $apiBase;
    $site['api_base_json'] = json_encode($apiBase, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

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
        ['title' => '搜索', 'url' => 'search.html'],
        ['title' => '查订单', 'url' => 'order.html'],
        ['title' => '购物车', 'url' => 'cart.html'],
    ];
    $normalizeMenu = function (array $items) use ($rootBase): array {
        return array_map(function (array $item) use ($rootBase) {
        $url = $item['url'] ?? '#';
        if (!preg_match('#^https?://#', $url) && !str_starts_with($url, $rootBase)) {
            $url = $rootBase . ltrim($url, '/');
        }
        return ['title' => $item['title'] ?? '', 'url' => $url, 'target_blank' => !empty($item['target_blank'])];
        }, $items);
    };
    $site['nav'] = $normalizeMenu($nav);
    $footerNav = $site['footer_nav'] ?? ($site['menus']['footer'] ?? $nav);
    $site['footer_nav'] = $normalizeMenu(is_array($footerNav) ? $footerNav : []);
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
        'floating_phone' => '',
        'floating_email' => '',
        'floating_whatsapp' => '',
        'floating_wechat' => '',
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
            ['question' => '这种方式适合批量独立站吗？', 'answer' => '适合。每个站点可以独立配置，前台产物是静态文件，便于批量部署和缓存。'],
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

function site_origin(array $site): string
{
    $domain = trim((string)($site['domain'] ?? ''));
    if ($domain === '') {
        $domain = 'demo.local';
    }
    if (preg_match('#^https?://#i', $domain)) {
        return rtrim($domain, '/');
    }
    return 'https://' . trim($domain, '/');
}

function absolute_site_url(array $site, string $path = ''): string
{
    return site_origin($site) . '/' . ltrim($path, '/');
}

function rss_date(?string $value): string
{
    try {
        if ($value) {
            return (new DateTimeImmutable($value))->format(DATE_RSS);
        }
    } catch (Throwable $error) {
    }
    return (new DateTimeImmutable())->format(DATE_RSS);
}

function build_rss_xml(array $site, array $articles): string
{
    $title = (string)($site['name'] ?? 'Huajian Site');
    $description = (string)($site['description'] ?? $title);
    $rows = [
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">',
        '  <channel>',
        '    <title>' . e($title) . '</title>',
        '    <link>' . e(absolute_site_url($site, 'index.html')) . '</link>',
        '    <atom:link href="' . e(absolute_site_url($site, 'rss.xml')) . '" rel="self" type="application/rss+xml" />',
        '    <description>' . e($description) . '</description>',
        '    <language>' . e((string)($site['language'] ?? 'zh-CN')) . '</language>',
        '    <lastBuildDate>' . rss_date(null) . '</lastBuildDate>',
    ];
    foreach (array_slice($articles, 0, 50) as $article) {
        $url = absolute_site_url($site, 'news/' . ($article['slug'] ?? '') . '.html');
        $summary = strip_tags((string)($article['summary'] ?? $article['content'] ?? ''));
        $rows[] = '    <item>';
        $rows[] = '      <title>' . e((string)($article['title'] ?? '')) . '</title>';
        $rows[] = '      <link>' . e($url) . '</link>';
        $rows[] = '      <guid isPermaLink="true">' . e($url) . '</guid>';
        $rows[] = '      <description>' . e($summary) . '</description>';
        $rows[] = '      <pubDate>' . rss_date((string)($article['published_at'] ?? '')) . '</pubDate>';
        $rows[] = '    </item>';
    }
    $rows[] = '  </channel>';
    $rows[] = '</rss>';
    return implode(PHP_EOL, $rows) . PHP_EOL;
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

    $actions = [];
    $text = trim((string)($modules['floating_text'] ?? '绔嬪嵆鍜ㄨ')) ?: '绔嬪嵆鍜ㄨ';
    $actions[] = ['label' => $text, 'href' => $rootBase . 'contact.html', 'class' => 'primary'];
    $phone = trim((string)($modules['floating_phone'] ?? ($site['phone'] ?? '')));
    if ($phone !== '') {
        $actions[] = ['label' => '鐢佃瘽', 'href' => 'tel:' . preg_replace('/\s+/', '', $phone), 'class' => 'phone'];
    }
    $email = trim((string)($modules['floating_email'] ?? ($site['email'] ?? '')));
    if ($email !== '') {
        $actions[] = ['label' => '閭', 'href' => 'mailto:' . $email, 'class' => 'email'];
    }
    $whatsapp = trim((string)($modules['floating_whatsapp'] ?? ''));
    if ($whatsapp !== '') {
        $digits = preg_replace('/[^\d+]/', '', $whatsapp);
        $digits = ltrim((string)$digits, '+');
        if ($digits !== '') {
            $actions[] = ['label' => 'WhatsApp', 'href' => 'https://wa.me/' . $digits, 'class' => 'whatsapp', 'target' => '_blank'];
        }
    }
    $wechat = trim((string)($modules['floating_wechat'] ?? ''));
    if ($wechat !== '') {
        $actions[] = ['label' => '微信', 'href' => $rootBase . 'contact.html#wechat', 'class' => 'wechat', 'title' => '微信：' . $wechat];
    }
    $html = '<div class="floating-actions" aria-label="快捷咨询">';
    foreach ($actions as $action) {
        $target = !empty($action['target']) ? ' target="' . e($action['target']) . '" rel="noopener"' : '';
        $title = !empty($action['title']) ? ' title="' . e($action['title']) . '"' : '';
        $html .= '<a class="' . e($action['class']) . '" href="' . e($action['href']) . '"' . $target . $title . '>' . e($action['label']) . '</a>';
    }
    return $html . '</div>';
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

function category_items(array $items, int $categoryId): array
{
    return array_values(array_filter($items, fn($item) => (int)($item['category_id'] ?? 0) === $categoryId));
}

function article_tags(array $articles): array
{
    $tags = [];
    foreach ($articles as $article) {
        foreach (($article['tags'] ?? []) as $tag) {
            $slug = (string)($tag['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $tags[$slug] = [
                'name' => (string)($tag['name'] ?? $slug),
                'slug' => $slug,
                'description' => (string)($tag['description'] ?? ''),
            ];
        }
    }
    uasort($tags, fn($a, $b) => strcmp($a['name'], $b['name']));
    return array_values($tags);
}

function tag_items(array $articles, string $slug): array
{
    return array_values(array_filter($articles, function (array $article) use ($slug) {
        foreach (($article['tags'] ?? []) as $tag) {
            if ((string)($tag['slug'] ?? '') === $slug) {
                return true;
            }
        }
        return false;
    }));
}

function article_tags_html(array $article, string $rootBase): string
{
    $tags = $article['tags'] ?? [];
    if (!$tags) {
        return '';
    }
    $html = '<div class="tag-list">';
    foreach ($tags as $tag) {
        $html .= '<a href="' . e($rootBase . 'tag/' . ($tag['slug'] ?? '') . '/index.html') . '">' . e($tag['name'] ?? '') . '</a>';
    }
    return $html . '</div>';
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
            $html .= '<div><strong>' . count($productItems) . '</strong><span>浜у搧灞曠ず</span></div>';
            $html .= '<div><strong>' . count($articleItems) . '</strong><span>鍐呭椤甸潰</span></div>';
            $html .= '<div><strong>HTML</strong><span>闈欐€佸彂甯?/span></div>';
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
                $html .= '<span class="price">¥' . e($product['price'] ?? '') . '</span>';
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
$publicRoot = env_or_null('HJ_PUBLIC_PATH') ?: $root . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'site_10001' . DIRECTORY_SEPARATOR . 'public';

$pdo = pdo_site();
if ($pdo) {
    [$site, $categories, $productCategories, $articles, $products, $pages] = load_data_from_mysql($pdo);
    $dataSource = 'mysql';
} else {
    $site = read_json($dataRoot . DIRECTORY_SEPARATOR . 'site.json');
    $categories = read_json($dataRoot . DIRECTORY_SEPARATOR . 'categories.json');
    $productCategories = read_json($dataRoot . DIRECTORY_SEPARATOR . 'product-categories.json');
    $articles = read_json($dataRoot . DIRECTORY_SEPARATOR . 'articles.json');
    $products = read_json($dataRoot . DIRECTORY_SEPARATOR . 'products.json');
    $pages = [];
    $dataSource = 'json';
}
$site['id'] = (int)($site['id'] ?? (env_or_null('HJ_SITE_ID') ?: 10001));
if (env_or_null('HJ_TEMPLATE_KEY')) {
    $site['template_key'] = env_or_null('HJ_TEMPLATE_KEY');
}
$templateKey = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($site['template_key'] ?? 'business-clean')) ?: 'business-clean';
$templateRoot = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $templateKey;
if (!is_dir($templateRoot) || !is_file($templateRoot . DIRECTORY_SEPARATOR . 'template.json')) {
    $templateKey = 'business-clean';
    $templateRoot = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $templateKey;
}
$templateMeta = read_json($templateRoot . DIRECTORY_SEPARATOR . 'template.json');
if (($templateMeta['clone_mode'] ?? '') === 'static_mirror' && is_dir($templateRoot . DIRECTORY_SEPARATOR . 'mirror')) {
    reset_generated_output($publicRoot);
    copy_dir($templateRoot . DIRECTORY_SEPARATOR . 'mirror', $publicRoot);
    apply_static_mirror_region_overrides($templateRoot, $publicRoot, $templateKey, $site);
    echo "Generated static mirror template {$templateKey}: {$publicRoot}\n";
    exit;
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
reset_generated_output($publicRoot);
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

write_file($publicRoot . DIRECTORY_SEPARATOR . 'cart.html', $engine->renderFile('pages/cart.html', base_context($site, $categories, $productCategories, $articles, $products) + [
    'seo' => [
        'title' => '购物车 - ' . ($site['name'] ?? ''),
        'description' => '核对' . ($site['name'] ?? '') . '商城商品并提交多商品订单。',
        'keywords' => $site['keywords'] ?? '',
    ],
]));

write_file($publicRoot . DIRECTORY_SEPARATOR . '404.html', $engine->renderFile('pages/404.html', base_context($site, $categories, $productCategories, $articles, $products) + [
    'seo' => [
        'title' => '页面不存在 - ' . ($site['name'] ?? ''),
        'description' => '你访问的页面不存在或已经移动。',
        'keywords' => $site['keywords'] ?? '',
    ],
]));

write_file($publicRoot . DIRECTORY_SEPARATOR . 'news' . DIRECTORY_SEPARATOR . 'index.html', $engine->renderFile('pages/article-list.html', base_context($site, $categories, $productCategories, $articles, $products, '../', '../') + [
    'articles' => with_urls($articles, ''),
    'seo' => [
        'title' => '行业资讯 - ' . ($site['name'] ?? ''),
        'description' => '行业资讯、产品知识和解决方案内容。',
        'keywords' => $site['keywords'] ?? '',
    ],
]));

foreach ($categories as $category) {
    $categoryArticles = category_items($articles, (int)$category['id']);
    if (!$categoryArticles) {
        continue;
    }
    write_file($publicRoot . DIRECTORY_SEPARATOR . 'category' . DIRECTORY_SEPARATOR . $category['slug'] . DIRECTORY_SEPARATOR . 'index.html', $engine->renderFile('pages/article-list.html', base_context($site, $categories, $productCategories, $categoryArticles, $products, '../../', '../../') + [
        'articles' => with_urls($categoryArticles, '../../news/'),
        'seo' => [
            'title' => ($category['seo_title'] ?? $category['name']) . ' - ' . ($site['name'] ?? ''),
            'description' => $category['seo_description'] ?? ($category['description'] ?? ''),
            'keywords' => $category['seo_keywords'] ?? ($site['keywords'] ?? ''),
        ],
    ]));
}

$tags = article_tags($articles);
foreach ($tags as $tag) {
    $tagArticles = tag_items($articles, (string)$tag['slug']);
    if (!$tagArticles) {
        continue;
    }
    write_file($publicRoot . DIRECTORY_SEPARATOR . 'tag' . DIRECTORY_SEPARATOR . $tag['slug'] . DIRECTORY_SEPARATOR . 'index.html', $engine->renderFile('pages/article-list.html', base_context($site, $categories, $productCategories, $tagArticles, $products, '../../', '../../') + [
        'articles' => with_urls($tagArticles, '../../news/'),
        'seo' => [
            'title' => $tag['name'] . ' - 文章标签 - ' . ($site['name'] ?? ''),
            'description' => $tag['description'] ?: ('浏览标签“' . $tag['name'] . '”下的文章内容。'),
            'keywords' => $tag['name'] . ',' . ($site['keywords'] ?? ''),
        ],
    ]));
}

foreach ($articles as $article) {
    $relatedArticles = array_values(array_filter($articles, fn($item) => (int)$item['id'] !== (int)$article['id']));
    $relatedArticles = array_map(fn($item) => [
        'title' => $item['title'] ?? '',
        'url' => $item['slug'] . '.html',
        'meta' => $item['published_at'] ?? '鏂囩珷',
    ], $relatedArticles);
    write_file($publicRoot . DIRECTORY_SEPARATOR . 'news' . DIRECTORY_SEPARATOR . $article['slug'] . '.html', $engine->renderFile('pages/article.html', array_replace(base_context($site, $categories, $productCategories, $articles, $products, '../', '../'), [
        'article' => $article,
        'article_tags_html' => article_tags_html($article, '../'),
        'breadcrumb_html' => breadcrumb_html($site, [
            ['title' => '棣栭〉', 'url' => '../index.html'],
            ['title' => '琛屼笟璧勮', 'url' => 'index.html'],
            ['title' => $article['title'] ?? '', 'url' => ''],
        ]),
        'related_html' => related_html($site, $relatedArticles, '鐩稿叧闃呰'),
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
        'description' => '产品中心、商品展示和采购下单入口。',
        'keywords' => $site['keywords'] ?? '',
    ],
]));

foreach ($productCategories as $category) {
    $categoryProducts = category_items($products, (int)$category['id']);
    if (!$categoryProducts) {
        continue;
    }
    write_file($publicRoot . DIRECTORY_SEPARATOR . 'product-category' . DIRECTORY_SEPARATOR . $category['slug'] . DIRECTORY_SEPARATOR . 'index.html', $engine->renderFile('pages/product-list.html', base_context($site, $categories, $productCategories, $articles, $categoryProducts, '../../', '../../') + [
        'products' => with_urls($categoryProducts, '../../products/'),
        'seo' => [
            'title' => ($category['seo_title'] ?? $category['name']) . ' - ' . ($site['name'] ?? ''),
            'description' => $category['seo_description'] ?? ($category['description'] ?? ''),
            'keywords' => $category['seo_keywords'] ?? ($site['keywords'] ?? ''),
        ],
    ]));
}

foreach ($products as $product) {
    $relatedProducts = array_values(array_filter($products, fn($item) => (int)$item['id'] !== (int)$product['id']));
    $relatedProducts = array_map(fn($item) => [
        'title' => $item['title'] ?? '',
        'url' => $item['slug'] . '.html',
        'meta' => isset($item['price']) ? ('¥' . $item['price']) : '产品',
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

foreach ($pages as $page) {
    write_file($publicRoot . DIRECTORY_SEPARATOR . $page['slug'] . '.html', $engine->renderFile('pages/page.html', array_replace(base_context($site, $categories, $productCategories, $articles, $products), [
        'page' => $page,
        'breadcrumb_html' => breadcrumb_html($site, [
            ['title' => '首页', 'url' => 'index.html'],
            ['title' => $page['title'] ?? '', 'url' => ''],
        ]),
        'seo' => [
            'title' => $page['seo_title'] ?? $page['title'],
            'description' => $page['seo_description'] ?? $page['summary'],
            'keywords' => $page['seo_keywords'] ?? '',
        ],
    ])));
}

$origin = site_origin($site);
$sitemap = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
$sitemap[] = '  <url><loc>' . $origin . '/index.html</loc></url>';
$sitemap[] = '  <url><loc>' . $origin . '/contact.html</loc></url>';
$sitemap[] = '  <url><loc>' . $origin . '/search.html</loc></url>';
$sitemap[] = '  <url><loc>' . $origin . '/order.html</loc></url>';
$sitemap[] = '  <url><loc>' . $origin . '/cart.html</loc></url>';
foreach ($categories as $category) {
    if (category_items($articles, (int)$category['id'])) {
        $sitemap[] = '  <url><loc>' . $origin . '/category/' . $category['slug'] . '/index.html</loc></url>';
    }
}
foreach ($articles as $article) {
    $sitemap[] = '  <url><loc>' . $origin . '/news/' . $article['slug'] . '.html</loc></url>';
}
foreach ($tags as $tag) {
    if (tag_items($articles, (string)$tag['slug'])) {
        $sitemap[] = '  <url><loc>' . $origin . '/tag/' . $tag['slug'] . '/index.html</loc></url>';
    }
}
foreach ($productCategories as $category) {
    if (category_items($products, (int)$category['id'])) {
        $sitemap[] = '  <url><loc>' . $origin . '/product-category/' . $category['slug'] . '/index.html</loc></url>';
    }
}
foreach ($products as $product) {
    $sitemap[] = '  <url><loc>' . $origin . '/products/' . $product['slug'] . '.html</loc></url>';
}
foreach ($pages as $page) {
    $sitemap[] = '  <url><loc>' . $origin . '/' . $page['slug'] . '.html</loc></url>';
}
$sitemap[] = '</urlset>';
write_file($publicRoot . DIRECTORY_SEPARATOR . 'sitemap.xml', implode(PHP_EOL, $sitemap));
write_file($publicRoot . DIRECTORY_SEPARATOR . 'robots.txt', "User-agent: *\nAllow: /\nSitemap: {$origin}/sitemap.xml\n");
write_file($publicRoot . DIRECTORY_SEPARATOR . 'rss.xml', build_rss_xml($site, $articles));

$search = [];
foreach ($categories as $category) {
    if (category_items($articles, (int)$category['id'])) {
        $search[] = ['type' => 'category', 'title' => $category['name'], 'summary' => $category['description'] ?? '', 'url' => 'category/' . $category['slug'] . '/index.html'];
    }
}
foreach ($articles as $article) {
    $search[] = ['type' => 'article', 'title' => $article['title'], 'summary' => $article['summary'], 'url' => 'news/' . $article['slug'] . '.html'];
}
foreach ($tags as $tag) {
    if (tag_items($articles, (string)$tag['slug'])) {
        $search[] = ['type' => 'tag', 'title' => $tag['name'], 'summary' => $tag['description'] ?: ('文章标签：' . $tag['name']), 'url' => 'tag/' . $tag['slug'] . '/index.html'];
    }
}
foreach ($productCategories as $category) {
    if (category_items($products, (int)$category['id'])) {
        $search[] = ['type' => 'product_category', 'title' => $category['name'], 'summary' => $category['description'] ?? '', 'url' => 'product-category/' . $category['slug'] . '/index.html'];
    }
}
foreach ($products as $product) {
    $search[] = ['type' => 'product', 'title' => $product['title'], 'summary' => $product['summary'], 'url' => 'products/' . $product['slug'] . '.html'];
}
foreach ($pages as $page) {
    $search[] = ['type' => 'page', 'title' => $page['title'], 'summary' => $page['summary'], 'url' => $page['slug'] . '.html'];
}
write_file($publicRoot . DIRECTORY_SEPARATOR . 'search.json', json_encode($search, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "Generated site from {$dataSource}: {$publicRoot}\n";
