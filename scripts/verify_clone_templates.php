<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$php = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
$generator = $root . DIRECTORY_SEPARATOR . 'worker' . DIRECTORY_SEPARATOR . 'GenerateSite.php';
$templates = [
    'clone-www-ld199-com-260709165632-3530' => ['min_length' => 5000, 'min_images' => 5, 'min_pages' => 5],
    'clone-www-chuyunai-com-cn-260709165648-a5d6' => ['min_length' => 12000, 'min_images' => 10, 'min_pages' => 5],
    'clone-aifuoil-com-260709165929-3207' => ['min_length' => 5000, 'min_images' => 5, 'min_pages' => 5],
];

if (!is_file($php) || !is_file($generator)) {
    fwrite(STDERR, "Missing PHP runtime or generator.\n");
    exit(1);
}

$failed = false;
$rows = [];
foreach ($templates as $key => $rules) {
    $out = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'template_previews' . DIRECTORY_SEPARATOR . 'verify_' . $key;
    $templateRoot = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $key;
    remove_path($out);
    putenv('HJ_TEMPLATE_KEY=' . $key);
    putenv('HJ_PUBLIC_PATH=' . $out);
    putenv('HJ_SITE_ID=10001');
    $command = '"' . $php . '" "' . $generator . '"';
    $output = [];
    $code = 0;
    exec($command, $output, $code);
    $index = $out . DIRECTORY_SEPARATOR . 'index.html';
    $html = is_file($index) ? (string)file_get_contents($index) : '';
    $assetCheck = verify_local_assets($out);
    $pageCheck = verify_html_pages($out);
    $regionCheck = verify_editable_regions($templateRoot);
    preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatch);
    $row = [
        'key' => $key,
        'title' => trim(strip_tags($titleMatch[1] ?? '')),
        'length' => strlen($html),
        'images' => preg_match_all('/<img\b/i', $html),
        'html_pages' => $pageCheck['pages'],
        'empty_titles' => count($pageCheck['empty_titles']),
        'dirty_pages' => count($pageCheck['dirty_pages']),
        'editable_regions' => $regionCheck['regions'],
        'unmatched_regions' => count($regionCheck['unmatched']),
        'asset_refs' => $assetCheck['checked'],
        'missing_assets' => count($assetCheck['missing']),
        'external_refs' => count($assetCheck['external']),
        'redirect' => preg_match('/http-equiv=["\']refresh["\']|window\.location\.(replace|href|assign)\s*\(/i', $html) === 1,
        'zeroshop' => str_contains($html, 'ZeroShop'),
        'test_marker' => str_contains($html, 'HUJIAN_TEST_STATIC_MIRROR_OVERRIDE_260709'),
        'code' => $code,
    ];
    $row['ok'] = $code === 0
        && $row['length'] >= (int)$rules['min_length']
        && $row['images'] >= (int)$rules['min_images']
        && $row['html_pages'] >= (int)$rules['min_pages']
        && $row['empty_titles'] === 0
        && $row['dirty_pages'] === 0
        && $row['editable_regions'] > 0
        && $row['unmatched_regions'] === 0
        && $row['missing_assets'] === 0
        && !$row['redirect']
        && !$row['zeroshop']
        && !$row['test_marker'];
    $row['missing_examples'] = array_slice($assetCheck['missing'], 0, 3);
    $row['external_examples'] = array_slice($assetCheck['external'], 0, 3);
    $row['empty_title_examples'] = array_slice($pageCheck['empty_titles'], 0, 3);
    $row['dirty_page_examples'] = array_slice($pageCheck['dirty_pages'], 0, 3);
    $row['unmatched_region_examples'] = array_slice($regionCheck['unmatched'], 0, 3);
    $failed = $failed || !$row['ok'];
    $rows[] = $row;
}
putenv('HJ_TEMPLATE_KEY');
putenv('HJ_PUBLIC_PATH');
putenv('HJ_SITE_ID');

foreach ($rows as $row) {
    echo sprintf(
        "%s\t%s\tlen=%d\timg=%d\tpages=%d\temptyTitle=%d\tdirty=%d\tregions=%d\tunmatched=%d\tassets=%d\tmissing=%d\texternal=%d\tredirect=%s\tzeroshop=%s\tok=%s\n",
        $row['key'],
        $row['title'],
        $row['length'],
        $row['images'],
        $row['html_pages'],
        $row['empty_titles'],
        $row['dirty_pages'],
        $row['editable_regions'],
        $row['unmatched_regions'],
        $row['asset_refs'],
        $row['missing_assets'],
        $row['external_refs'],
        $row['redirect'] ? 'yes' : 'no',
        $row['zeroshop'] ? 'yes' : 'no',
        $row['ok'] ? 'yes' : 'no'
    );
    foreach ($row['missing_examples'] as $missing) {
        echo "  missing: {$missing}\n";
    }
    foreach ($row['external_examples'] as $external) {
        echo "  external: {$external}\n";
    }
    foreach ($row['empty_title_examples'] as $page) {
        echo "  empty-title: {$page}\n";
    }
    foreach ($row['dirty_page_examples'] as $page) {
        echo "  dirty-page: {$page}\n";
    }
    foreach ($row['unmatched_region_examples'] as $region) {
        echo "  unmatched-region: {$region}\n";
    }
}

exit($failed ? 1 : 0);

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

function verify_local_assets(string $root): array
{
    $checked = 0;
    $missing = [];
    $external = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($files as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['html', 'css'], true)) {
            continue;
        }
        $content = (string)file_get_contents($file->getPathname());
        $refs = $ext === 'html' ? extract_html_refs($content) : extract_css_refs($content);
        foreach ($refs as $ref) {
            $ref = trim(html_entity_decode($ref, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($ref === '' || preg_match('/^(#|mailto:|tel:|javascript:|data:|blob:)/i', $ref)) {
                continue;
            }
            if (preg_match('#^https?://#i', $ref) || str_starts_with($ref, '//')) {
                $external[] = relative_to_root($root, $file->getPathname()) . ' -> ' . $ref;
                continue;
            }
            $pathOnly = preg_replace('/[?#].*$/', '', $ref) ?? $ref;
            if ($pathOnly === '' || str_ends_with($pathOnly, '.html')) {
                continue;
            }
            $checked++;
            $base = dirname($file->getPathname());
            $target = str_starts_with($pathOnly, '/')
                ? $root . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $pathOnly), DIRECTORY_SEPARATOR)
                : $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pathOnly);
            $realRoot = realpath($root);
            $realTarget = realpath($target);
            if (!$realRoot || !$realTarget || !str_starts_with($realTarget, $realRoot) || !is_file($realTarget)) {
                $missing[] = relative_to_root($root, $file->getPathname()) . ' -> ' . $ref;
            }
        }
    }
    return [
        'checked' => $checked,
        'missing' => array_values(array_unique($missing)),
        'external' => array_values(array_unique($external)),
    ];
}

function verify_html_pages(string $root): array
{
    $pages = 0;
    $emptyTitles = [];
    $dirtyPages = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($files as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'html') {
            continue;
        }
        $pages++;
        $relative = relative_to_root($root, $file->getPathname());
        $html = (string)file_get_contents($file->getPathname());
        preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatch);
        $title = trim(strip_tags($titleMatch[1] ?? ''));
        if ($title === '') {
            $emptyTitles[] = $relative;
        }
        if (str_contains($html, 'ZeroShop') || str_contains($html, 'HUJIAN_TEST_STATIC_MIRROR_OVERRIDE_260709')) {
            $dirtyPages[] = $relative;
        }
    }
    return [
        'pages' => $pages,
        'empty_titles' => $emptyTitles,
        'dirty_pages' => $dirtyPages,
    ];
}

function verify_editable_regions(string $templateRoot): array
{
    $path = $templateRoot . DIRECTORY_SEPARATOR . 'editable-regions.json';
    if (!is_file($path)) {
        return ['regions' => 0, 'unmatched' => ['missing editable-regions.json']];
    }
    $payload = json_decode((string)file_get_contents($path), true);
    if (!is_array($payload)) {
        return ['regions' => 0, 'unmatched' => ['invalid editable-regions.json']];
    }
    $regions = $payload['regions'] ?? $payload;
    if (!is_array($regions) || !$regions) {
        return ['regions' => 0, 'unmatched' => ['empty editable regions']];
    }
    $unmatched = [];
    foreach ($regions as $region) {
        if (!is_array($region)) {
            continue;
        }
        $id = (string)($region['id'] ?? $region['module'] ?? 'region');
        $source = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)($region['source_file'] ?? ''));
        $sourcePath = $templateRoot . DIRECTORY_SEPARATOR . $source;
        if ($source === '' || str_contains($source, '..') || !is_file($sourcePath)) {
            $unmatched[] = $id . ' -> missing source file';
            continue;
        }
        $selectors = $region['selectors'] ?? ($region['selector'] ?? []);
        if (is_string($selectors)) {
            $selectors = [$selectors];
        }
        if (!is_array($selectors) || !$selectors) {
            $unmatched[] = $id . ' -> no selectors';
            continue;
        }
        $html = (string)file_get_contents($sourcePath);
        if (!html_has_matching_selector($html, $selectors)) {
            $unmatched[] = $id . ' -> ' . implode(' | ', array_map('strval', $selectors));
        }
    }
    return [
        'regions' => count($regions),
        'unmatched' => $unmatched,
    ];
}

function html_has_matching_selector(string $html, array $selectors): bool
{
    if (!class_exists('DOMDocument')) {
        return true;
    }
    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    if (!$loaded) {
        return false;
    }
    $xpath = new DOMXPath($dom);
    foreach ($selectors as $selector) {
        $query = css_selector_to_xpath((string)$selector);
        if (!$query) {
            continue;
        }
        $nodes = $xpath->query($query);
        if ($nodes && $nodes->length > 0) {
            return true;
        }
    }
    return false;
}

function css_selector_to_xpath(string $selector): ?string
{
    $selector = trim($selector);
    if ($selector === '' || str_contains($selector, ',') || str_contains($selector, '>')) {
        return null;
    }
    $parts = preg_split('/\s+/', $selector) ?: [];
    $query = '';
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $step = selector_part_to_xpath($part);
        if ($step === null) {
            return null;
        }
        $query .= '//' . $step;
    }
    return $query ?: null;
}

function selector_part_to_xpath(string $part): ?string
{
    if (preg_match('/^\[([a-zA-Z0-9_-]+)\*=["\']?([^"\']+)["\']?\]$/', $part, $match)) {
        return '*[contains(@' . strtolower($match[1]) . ', "' . xpath_literal($match[2]) . '")]';
    }
    if (preg_match('/^#([a-zA-Z0-9_-]+)$/', $part, $match)) {
        return '*[@id="' . xpath_literal($match[1]) . '"]';
    }
    if (preg_match('/^([a-zA-Z][a-zA-Z0-9_-]*)?((?:\.[a-zA-Z0-9_-]+)+)$/', $part, $match)) {
        $tag = $match[1] !== '' ? strtolower($match[1]) : '*';
        preg_match_all('/\.([a-zA-Z0-9_-]+)/', $match[2], $classes);
        $conditions = [];
        foreach ($classes[1] as $className) {
            $conditions[] = 'contains(concat(" ", normalize-space(@class), " "), " ' . xpath_literal($className) . ' ")';
        }
        return $tag . '[' . implode(' and ', $conditions) . ']';
    }
    if (preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $part)) {
        return strtolower($part);
    }
    return null;
}

function xpath_literal(string $value): string
{
    return str_replace('"', '\"', $value);
}

function extract_html_refs(string $html): array
{
    $refs = [];
    if (preg_match_all('/<(?:img|script|source|video|audio|iframe)\b[^>]*\s(?:src|poster|data-src)=["\']([^"\']+)["\']/i', $html, $matches)) {
        array_push($refs, ...$matches[1]);
    }
    if (preg_match_all('/<link\b[^>]*\shref=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
        foreach ($matches[0] as $index => $tag) {
            if (preg_match('/\srel=["\']([^"\']+)["\']/i', $tag, $rel) && preg_match('/\b(stylesheet|icon|preload|modulepreload)\b/i', $rel[1])) {
                $refs[] = $matches[1][$index];
            }
        }
    }
    if (preg_match_all('/<(?:img|source)\b[^>]*\ssrcset=["\']([^"\']+)["\']/i', $html, $matches)) {
        foreach ($matches[1] as $srcset) {
            foreach (explode(',', $srcset) as $part) {
                $bits = preg_split('/\s+/', trim($part));
                if (!empty($bits[0])) {
                    $refs[] = $bits[0];
                }
            }
        }
    }
    if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $styleBlocks)) {
        foreach ($styleBlocks[1] as $css) {
            array_push($refs, ...extract_css_refs($css));
        }
    }
    if (preg_match_all('/\sstyle=["\']([^"\']+)["\']/is', $html, $styleAttrs)) {
        foreach ($styleAttrs[1] as $css) {
            array_push($refs, ...extract_css_refs($css));
        }
    }
    return $refs;
}

function extract_css_refs(string $css): array
{
    $refs = [];
    if (preg_match_all('/url\(([^)]+)\)/i', $css, $matches)) {
        array_push($refs, ...array_map(static fn($value) => trim($value, " \t\n\r\0\x0B'\""), $matches[1]));
    }
    if (preg_match_all('/@import\s+["\']([^"\']+)["\']/i', $css, $matches)) {
        array_push($refs, ...$matches[1]);
    }
    return $refs;
}

function relative_to_root(string $root, string $path): string
{
    return str_replace('\\', '/', ltrim(substr($path, strlen($root)), DIRECTORY_SEPARATOR));
}
