<?php
declare(strict_types=1);

$baseUrl = rtrim((string)($argv[1] ?? 'http://127.0.0.1:8000'), '/');
$root = dirname(__DIR__);
$token = login($baseUrl);
$failed = false;
$rows = [];
$created = ['article' => [], 'product' => [], 'collector_record' => []];
$changedSiteIds = [];

try {
    $sites = api_request($baseUrl, 'GET', '/api/sites', $token, null, 10001)['items'] ?? [];
    $siteIds = array_values(array_filter(array_map(static fn($item): int => (int)($item['id'] ?? 0), $sites), static fn(int $id): bool => $id > 0));
    if (count($siteIds) < 2) {
        throw new RuntimeException('At least two sites are required for AI and collector distribution verification.');
    }
    $siteA = in_array(10001, $siteIds, true) ? 10001 : $siteIds[0];
    $siteB = $siteIds[0] === $siteA ? $siteIds[1] : $siteIds[0];
    $changedSiteIds = [$siteA, $siteB];
    $marker = 'HJ_AI_COLLECTOR_' . date('YmdHis') . '_' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

    $batchArticle = api_request($baseUrl, 'POST', '/api/ai/batch-articles', $token, [
        'prompt' => $marker . ' batch article',
        'count' => 1,
        'status' => 'published',
        'site_scope' => 'selected',
        'site_ids' => [$siteA],
    ], $siteA);
    $article = first_item($batchArticle, 'AI batch article');
    $created['article'][] = (int)$article['id'];
    $rows[] = distribution_row('AI batch article distribution', $article, [$siteA]);

    $batchProduct = api_request($baseUrl, 'POST', '/api/ai/batch-products', $token, [
        'prompt' => $marker . ' batch product',
        'count' => 1,
        'status' => 'published',
        'site_scope' => 'selected',
        'site_ids' => [$siteA, $siteB],
    ], $siteA);
    $product = first_item($batchProduct, 'AI batch product');
    $created['product'][] = (int)$product['id'];
    $rows[] = distribution_row('AI batch product distribution', $product, [$siteA, $siteB]);

    $task = api_request($baseUrl, 'POST', '/api/ai/tasks', $token, [
        'type' => 'article',
        'prompt' => $marker . ' confirm task article',
        'count' => 1,
        'status' => 'draft',
        'site_scope' => 'selected',
        'site_ids' => [$siteB],
    ], $siteA);
    $confirmed = api_request($baseUrl, 'POST', '/api/ai/tasks/' . (int)$task['id'] . '/confirm', $token, [
        'action' => 'publish',
        'site_scope' => 'selected',
        'site_ids' => [$siteB],
    ], $siteA);
    $confirmedIds = array_values(array_filter(array_map('intval', $confirmed['created_article_ids'] ?? []), static fn(int $id): bool => $id > 0));
    foreach ($confirmedIds as $id) {
        $created['article'][] = $id;
    }
    $confirmedArticle = $confirmedIds ? api_request($baseUrl, 'GET', '/api/articles/' . $confirmedIds[0], $token, null, $siteA) : [];
    $rows[] = distribution_row('AI task confirm distribution', $confirmedArticle, [$siteB]);

    $record = api_request($baseUrl, 'POST', '/api/collector/records/manual', $token, [
        'title' => $marker . ' collector record',
        'summary' => $marker . ' collector summary',
        'content' => '<p>' . $marker . ' collector content</p>',
        'site_scope' => 'selected',
        'site_ids' => [$siteA, $siteB],
    ], $siteA);
    $created['collector_record'][] = (int)$record['id'];
    $published = api_request($baseUrl, 'POST', '/api/collector/records/' . (int)$record['id'] . '/publish', $token, [
        'status' => 'published',
        'site_scope' => 'selected',
        'site_ids' => [$siteA, $siteB],
    ], $siteA);
    $collectorArticle = $published['article'] ?? [];
    if (!empty($collectorArticle['id'])) {
        $created['article'][] = (int)$collectorArticle['id'];
    }
    $rows[] = distribution_row('collector publish distribution', $collectorArticle, [$siteA, $siteB]);

    generate_site($baseUrl, $token, $siteA);
    generate_site($baseUrl, $token, $siteB);
    $rows[] = marker_row($baseUrl, $token, $root, $siteA, $marker, true, 'AI and collector content visible on selected first site');
    $rows[] = marker_row($baseUrl, $token, $root, $siteB, $marker, true, 'AI and collector content visible on selected second site');

    foreach ($rows as $row) {
        $failed = $failed || empty($row['ok']);
    }
} catch (Throwable $error) {
    $rows[] = [
        'name' => 'AI and collector distribution verifier',
        'ok' => false,
        'message' => $error->getMessage(),
    ];
    $failed = true;
} finally {
    foreach (array_reverse($created['article']) as $id) {
        try {
            api_request($baseUrl, 'DELETE', '/api/articles/' . $id, $token, null, 10001);
        } catch (Throwable $cleanupError) {
            $rows[] = ['name' => 'cleanup article ' . $id, 'ok' => false, 'message' => $cleanupError->getMessage()];
            $failed = true;
        }
    }
    foreach (array_reverse($created['product']) as $id) {
        try {
            api_request($baseUrl, 'DELETE', '/api/products/' . $id, $token, null, 10001);
        } catch (Throwable $cleanupError) {
            $rows[] = ['name' => 'cleanup product ' . $id, 'ok' => false, 'message' => $cleanupError->getMessage()];
            $failed = true;
        }
    }
    foreach (array_reverse($created['collector_record']) as $id) {
        try {
            api_request($baseUrl, 'DELETE', '/api/collector/records/' . $id, $token, null, 10001);
        } catch (Throwable $cleanupError) {
            $rows[] = ['name' => 'cleanup collector record ' . $id, 'ok' => false, 'message' => $cleanupError->getMessage()];
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

function first_item(array $payload, string $label): array
{
    $items = $payload['items'] ?? [];
    if (!is_array($items) || empty($items[0]) || !is_array($items[0])) {
        throw new RuntimeException($label . ' API did not return an item.');
    }
    return $items[0];
}

function distribution_row(string $name, array $item, array $expectedSiteIds): array
{
    $actual = array_values(array_unique(array_filter(array_map('intval', $item['site_ids'] ?? []), static fn(int $id): bool => $id > 0)));
    sort($actual);
    $expected = array_values(array_unique(array_map('intval', $expectedSiteIds)));
    sort($expected);
    return [
        'name' => $name,
        'ok' => $actual === $expected,
        'message' => 'expected=' . implode(',', $expected) . ', actual=' . implode(',', $actual),
    ];
}

function marker_row(string $baseUrl, string $token, string $root, int $siteId, string $marker, bool $expected, string $name): array
{
    $publicRoot = public_root_path($baseUrl, $token, $root, $siteId);
    $found = false;
    foreach (['index.html', 'news/index.html', 'products/index.html', 'search.json'] as $relative) {
        $path = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($path) && str_contains((string)file_get_contents($path), $marker)) {
            $found = true;
            break;
        }
    }
    return [
        'name' => $name,
        'ok' => $found === $expected,
        'message' => 'site=' . $siteId . ', expected=' . ($expected ? 'visible' : 'hidden') . ', actual=' . ($found ? 'visible' : 'hidden'),
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
