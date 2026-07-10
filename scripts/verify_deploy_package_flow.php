<?php
declare(strict_types=1);

$baseUrl = rtrim((string)($argv[1] ?? 'http://127.0.0.1:8000'), '/');
$root = dirname(__DIR__);
$token = login($baseUrl);
$failed = false;
$rows = [];

try {
    $sites = api_request($baseUrl, 'GET', '/api/sites', $token, null, 10001);
    $items = is_array($sites['items'] ?? null) ? $sites['items'] : [];
    $siteId = 10001;
    if (!array_filter($items, static fn($item): bool => (int)($item['id'] ?? 0) === 10001)) {
        $siteId = (int)($items[0]['id'] ?? 10001);
    }

    $readiness = api_request($baseUrl, 'GET', '/api/site/publish-readiness', $token, null, $siteId);
    $rows[] = [
        'name' => 'publish readiness response',
        'ok' => array_key_exists('checks', $readiness) && array_key_exists('public_path', $readiness),
        'message' => 'score=' . (string)($readiness['score'] ?? '-'),
    ];

    $plan = api_request($baseUrl, 'GET', '/api/site/deploy-plan', $token, null, $siteId);
    $rows[] = [
        'name' => 'deploy plan response',
        'ok' => is_array($plan['steps'] ?? null) && is_array($plan['checks'] ?? null),
        'message' => 'mode=' . (string)($plan['mode'] ?? '-'),
    ];

    $deployCheck = api_request($baseUrl, 'POST', '/api/site/deploy-test', $token, null, $siteId);
    $rows[] = [
        'name' => 'deploy check records task',
        'ok' => array_key_exists('configured', $deployCheck) && is_array($deployCheck['plan'] ?? null),
        'message' => 'configured=' . (!empty($deployCheck['configured']) ? 'yes' : 'no'),
    ];

    $package = api_request($baseUrl, 'POST', '/api/site/package', $token, null, $siteId);
    $packagePath = (string)($package['package_path'] ?? '');
    $versionNo = (string)($package['version_no'] ?? '');
    $absolutePackage = absolute_package_path($root, $packagePath);
    $rows[] = [
        'name' => 'package file exists',
        'ok' => $packagePath !== '' && is_file($absolutePackage) && filesize($absolutePackage) > 0,
        'message' => basename($packagePath) . ' size=' . (is_file($absolutePackage) ? filesize($absolutePackage) : 0),
    ];

    $archive = inspect_package_archive($absolutePackage);
    $rows[] = [
        'name' => 'package archive contains static site',
        'ok' => $archive['ok'],
        'message' => 'files=' . $archive['file_count'] . ', required=' . implode(',', $archive['found_required']),
    ];

    $download = download_package($baseUrl, $token, basename($packagePath), $siteId);
    $rows[] = [
        'name' => 'package download endpoint',
        'ok' => $download['ok'] && $download['bytes'] === filesize($absolutePackage),
        'message' => 'downloaded=' . $download['bytes'],
    ];

    $versions = api_request($baseUrl, 'GET', '/api/site/publish-versions?page_size=10', $token, null, $siteId);
    $versionRow = find_by_version($versions['items'] ?? [], $versionNo);
    $rows[] = [
        'name' => 'package publish version recorded',
        'ok' => $versionRow !== null && (string)($versionRow['publish_type'] ?? '') === 'package',
        'message' => $versionNo,
    ];

    $tasks = api_request($baseUrl, 'GET', '/api/deploy/tasks?page_size=20', $token, null, $siteId);
    $taskRow = find_task_by_version($tasks['items'] ?? [], $versionNo);
    $rows[] = [
        'name' => 'package deploy task recorded',
        'ok' => $taskRow !== null && (string)($taskRow['action'] ?? '') === 'package',
        'message' => (string)($taskRow['task_no'] ?? ''),
    ];
} catch (Throwable $error) {
    $rows[] = [
        'name' => 'deploy package verifier',
        'ok' => false,
        'message' => $error->getMessage(),
    ];
    $failed = true;
}

foreach ($rows as $row) {
    $failed = $failed || empty($row['ok']);
    echo sprintf(
        "%s\t%s\t%s\n",
        !empty($row['ok']) ? 'PASS' : 'FAIL',
        $row['name'],
        (string)($row['message'] ?? '')
    );
}

exit($failed ? 1 : 0);

function absolute_package_path(string $root, string $packagePath): string
{
    $relative = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $packagePath), DIRECTORY_SEPARATOR);
    if ($relative === '' || str_contains($relative, '..')) {
        throw new RuntimeException('Invalid package path from API.');
    }
    $path = $root . DIRECTORY_SEPARATOR . $relative;
    $base = realpath($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'packages');
    $real = realpath($path);
    if (!$base || !$real || !str_starts_with($real, $base)) {
        throw new RuntimeException('Package path is outside storage/packages.');
    }
    return $real;
}

function inspect_package_archive(string $path): array
{
    $required = ['index.html', 'contact.html', 'search.html', 'order.html', 'cart.html', 'sitemap.xml', 'search.json'];
    $found = [];
    $fileCount = 0;
    try {
        $archive = new PharData($path);
        $iterator = new RecursiveIteratorIterator($archive);
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $fileCount++;
            $relative = str_replace('\\', '/', (string)$iterator->getSubPathName());
            if (in_array($relative, $required, true)) {
                $found[] = $relative;
            }
        }
    } catch (Throwable $error) {
        return [
            'ok' => false,
            'file_count' => $fileCount,
            'found_required' => $found,
            'error' => $error->getMessage(),
        ];
    }
    sort($found);
    $missing = array_values(array_diff($required, $found));
    return [
        'ok' => $fileCount > 0 && !$missing,
        'file_count' => $fileCount,
        'found_required' => $found,
        'missing' => $missing,
    ];
}

function download_package(string $baseUrl, string $token, string $file, int $siteId): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP curl extension is required.');
    }
    $url = $baseUrl . '/api/site/package-download?file=' . rawurlencode($file);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/gzip',
            'Authorization: Bearer ' . $token,
            'X-Site-Id: ' . $siteId,
        ],
        CURLOPT_TIMEOUT => 120,
    ]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if (!is_string($body)) {
        throw new RuntimeException('Package download failed: ' . ($error ?: 'HTTP ' . $status));
    }
    return [
        'ok' => $status >= 200 && $status < 300 && strlen($body) > 0 && str_starts_with($body, "\x1f\x8b"),
        'bytes' => strlen($body),
        'status' => $status,
    ];
}

function find_by_version(array $items, string $versionNo): ?array
{
    foreach ($items as $item) {
        if ((string)($item['version_no'] ?? '') === $versionNo) {
            return $item;
        }
    }
    return null;
}

function find_task_by_version(array $items, string $versionNo): ?array
{
    foreach ($items as $item) {
        if ((string)($item['version_no'] ?? '') === $versionNo) {
            return $item;
        }
    }
    return null;
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
