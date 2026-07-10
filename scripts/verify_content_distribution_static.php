<?php
declare(strict_types=1);

$baseUrl = rtrim((string)($argv[1] ?? 'http://127.0.0.1:8000'), '/');
$root = dirname(__DIR__);
$token = login($baseUrl);
$failed = false;
$rows = [];
$created = ['article' => [], 'product' => []];
$changedSiteIds = [];

try {
    $sites = api_request($baseUrl, 'GET', '/api/sites', $token, null, 10001)['items'] ?? [];
    $siteIds = array_values(array_filter(array_map(static fn($item): int => (int)($item['id'] ?? 0), $sites), static fn(int $id): bool => $id > 0));
    if (count($siteIds) < 2) {
        throw new RuntimeException('At least two sites are required for content distribution verification.');
    }
    $siteA = in_array(10001, $siteIds, true) ? 10001 : $siteIds[0];
    $siteB = $siteIds[0] === $siteA ? $siteIds[1] : $siteIds[0];
    $changedSiteIds = [$siteA, $siteB];
    $marker = 'HJ_DIST_' . date('YmdHis') . '_' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

    $articleId = create_article($baseUrl, $token, $siteA, $marker);
    $productId = create_product($baseUrl, $token, $siteA, $marker);
    $created['article'][] = $articleId;
    $created['product'][] = $productId;

    generate_site($baseUrl, $token, $siteA);
    generate_site($baseUrl, $token, $siteB);

    $rows[] = verify_marker_presence($baseUrl, $token, $root, $siteA, $marker, true, 'single-site content is visible on target site');
    $rows[] = verify_marker_presence($baseUrl, $token, $root, $siteB, $marker, false, 'single-site content is hidden from another site');

    api_request($baseUrl, 'POST', '/api/articles/batch-publish', $token, [
        'ids' => [$articleId],
        'action' => 'publish',
        'site_scope' => 'selected',
        'site_ids' => [$siteA, $siteB],
    ], $siteA);
    api_request($baseUrl, 'POST', '/api/products/batch-publish', $token, [
        'ids' => [$productId],
        'action' => 'publish',
        'site_scope' => 'selected',
        'site_ids' => [$siteA, $siteB],
    ], $siteA);

    generate_site($baseUrl, $token, $siteA);
    generate_site($baseUrl, $token, $siteB);

    $rows[] = verify_marker_presence($baseUrl, $token, $root, $siteA, $marker, true, 'multi-site content remains visible on first site');
    $rows[] = verify_marker_presence($baseUrl, $token, $root, $siteB, $marker, true, 'multi-site content becomes visible on second site');

    foreach ($rows as $row) {
        $failed = $failed || empty($row['ok']);
    }
} catch (Throwable $error) {
    $rows[] = [
        'name' => 'content distribution verifier',
        'ok' => false,
        'message' => $error->getMessage(),
    ];
    $failed = true;
} finally {
    foreach ($created['article'] as $id) {
        try {
            api_request($baseUrl, 'DELETE', '/api/articles/' . $id, $token, null, 10001);
        } catch (Throwable $cleanupError) {
            $rows[] = ['name' => 'cleanup article ' . $id, 'ok' => false, 'message' => $cleanupError->getMessage()];
            $failed = true;
        }
    }
    foreach ($created['product'] as $id) {
        try {
            api_request($baseUrl, 'DELETE', '/api/products/' . $id, $token, null, 10001);
        } catch (Throwable $cleanupError) {
            $rows[] = ['name' => 'cleanup product ' . $id, 'ok' => false, 'message' => $cleanupError->getMessage()];
            $failed = true;
        }
    }
    foreach (array_values(array_unique($changedSiteIds)) as $siteId) {
        try {
            generate_site($baseUrl, $token, (int)$siteId);
        } catch (Throwable $cleanupError) {
            $rows[] = ['name' => 'cleanup generate site ' . $siteId, 'ok' => false, 'message' => $cleanupError->getMessage()];
            $failed = true;
        }
    }
}

foreach ($rows as $row) {
    echo sprintf(
        "%s\t%s\t%s\n",
        !empty($row['ok']) ? 'PASS' : 'FAIL',
        $row['name'],
        (string)($row['message'] ?? '')
    );
}

exit($failed ? 1 : 0);

function create_article(string $baseUrl, string $token, int $siteId, string $marker): int
{
    $slug = strtolower(str_replace('_', '-', $marker)) . '-article';
    $data = api_request($baseUrl, 'POST', '/api/articles', $token, [
        'title' => $marker . ' Article',
        'slug' => $slug,
        'summary' => $marker . ' article summary',
        'content' => '<p>' . $marker . ' article body for static distribution verification.</p>',
        'seo_title' => $marker . ' Article',
        'seo_keywords' => $marker,
        'seo_description' => $marker . ' article seo',
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s'),
        'site_scope' => 'selected',
        'site_ids' => [$siteId],
    ], $siteId);
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        throw new RuntimeException('Article create API did not return an id.');
    }
    return $id;
}

function create_product(string $baseUrl, string $token, int $siteId, string $marker): int
{
    $slug = strtolower(str_replace('_', '-', $marker)) . '-product';
    $data = api_request($baseUrl, 'POST', '/api/products', $token, [
        'title' => $marker . ' Product',
        'slug' => $slug,
        'sku' => $marker . '-SKU',
        'summary' => $marker . ' product summary',
        'description' => '<p>' . $marker . ' product body for static distribution verification.</p>',
        'price' => 199,
        'market_price' => 299,
        'stock' => 10,
        'seo_title' => $marker . ' Product',
        'seo_keywords' => $marker,
        'seo_description' => $marker . ' product seo',
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s'),
        'site_scope' => 'selected',
        'site_ids' => [$siteId],
    ], $siteId);
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        throw new RuntimeException('Product create API did not return an id.');
    }
    return $id;
}

function verify_marker_presence(string $baseUrl, string $token, string $root, int $siteId, string $marker, bool $expected, string $name): array
{
    $publicRoot = public_root_path($baseUrl, $token, $root, $siteId);
    $paths = [
        $publicRoot . DIRECTORY_SEPARATOR . 'index.html',
        $publicRoot . DIRECTORY_SEPARATOR . 'news' . DIRECTORY_SEPARATOR . 'index.html',
        $publicRoot . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . 'index.html',
    ];
    $found = false;
    foreach ($paths as $path) {
        if (is_file($path) && str_contains((string)file_get_contents($path), $marker)) {
            $found = true;
            break;
        }
    }
    $articleFileFound = count(glob($publicRoot . DIRECTORY_SEPARATOR . 'news' . DIRECTORY_SEPARATOR . strtolower(str_replace('_', '-', $marker)) . '-article.html') ?: []) > 0;
    $productFileFound = count(glob($publicRoot . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . strtolower(str_replace('_', '-', $marker)) . '-product.html') ?: []) > 0;
    $visible = $found || $articleFileFound || $productFileFound;
    return [
        'name' => $name,
        'ok' => $visible === $expected,
        'message' => 'site=' . $siteId . ', expected=' . ($expected ? 'visible' : 'hidden') . ', actual=' . ($visible ? 'visible' : 'hidden'),
    ];
}

function generate_site(string $baseUrl, string $token, int $siteId): void
{
    api_request($baseUrl, 'POST', '/api/site/generate', $token, null, $siteId);
}

function public_root_path(string $baseUrl, string $token, string $root, int $siteId): string
{
    $preview = api_request($baseUrl, 'GET', '/api/site/preview', $token, null, $siteId);
    $relative = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)($preview['public_path'] ?? '')), DIRECTORY_SEPARATOR);
    if ($relative === '' || str_contains($relative, '..')) {
        throw new RuntimeException('Invalid public path from preview API.');
    }
    $path = $root . DIRECTORY_SEPARATOR . $relative;
    if (!is_dir($path)) {
        throw new RuntimeException('Public root does not exist: ' . $path);
    }
    return $path;
}

function login(string $baseUrl): string
{
    $data = api_request($baseUrl, 'POST', '/api/auth/login', '', [
        'username' => 'admin',
        'password' => 'admin123456',
    ], 10001);
    $token = (string)($data['token'] ?? '');
    if ($token === '') {
        throw new RuntimeException('Login did not return token.');
    }
    return $token;
}

function api_request(string $baseUrl, string $method, string $path, string $token = '', ?array $body = null, int $siteId = 10001): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP curl extension is required.');
    }
    $ch = curl_init($baseUrl . $path);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Site-Id: ' . $siteId,
    ];
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 120,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if (!is_string($raw) || $raw === '') {
        throw new RuntimeException('Empty API response: ' . ($error ?: 'HTTP ' . $status));
    }
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON API response: ' . mb_substr($raw, 0, 200, 'UTF-8'));
    }
    if ($status >= 400 || empty($payload['success'])) {
        throw new RuntimeException((string)($payload['message'] ?? ('HTTP ' . $status)));
    }
    return is_array($payload['data'] ?? null) ? $payload['data'] : [];
}
