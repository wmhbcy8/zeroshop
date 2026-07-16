<?php
declare(strict_types=1);

$baseUrl = rtrim((string)($argv[1] ?? 'http://127.0.0.1:8000'), '/');
$root = dirname(__DIR__);
$targets = [
    [
        'url' => 'https://www.ld199.com',
        'name' => 'LD199',
        'markers' => ['零点互娱', '数字权益服务平台', '服务项目'],
        'min_length' => 5000,
        'min_images' => 5,
        'fallback_template_key' => 'clone-www-ld199-com-260709165632-3530',
    ],
    [
        'url' => 'https://www.chuyunai.com.cn',
        'name' => 'Chuyun AI',
        'markers' => ['楚云数航', '业务版图', '光谷超算'],
        'min_length' => 10000,
        'min_images' => 8,
    ],
    [
        'url' => 'https://aifuoil.com',
        'name' => 'AIFUOIL current site',
        'markers' => ['Jingzhou Jinxiu', 'Products', 'News'],
        'min_length' => 5000,
        'min_images' => 5,
    ],
];

$token = login($baseUrl);
$failed = false;
$rows = [];

foreach ($targets as $target) {
    $taskId = 0;
    $templateKey = '';
    try {
        $fallbackKey = (string)($target['fallback_template_key'] ?? '');
        if ($fallbackKey !== '' && !target_is_reachable((string)$target['url'])) {
            $row = verify_existing_template($root, $fallbackKey, $target);
            $row['name'] = (string)$target['name'];
            $row['url'] = (string)$target['url'];
            $row['template_key'] = $fallbackKey;
            $row['fallback'] = true;
            $rows[] = $row;
            $failed = $failed || empty($row['ok']);
            continue;
        }
        $task = api_request($baseUrl, 'POST', '/api/template-clone/tasks', $token, ['target_url' => $target['url']]);
        $taskId = (int)($task['id'] ?? 0);
        $templateKey = (string)($task['template_key'] ?? '');
        if ($taskId <= 0 || $templateKey === '') {
            throw new RuntimeException('Clone task did not return id/template_key.');
        }

        $preview = api_request($baseUrl, 'POST', '/api/template-clone/tasks/' . $taskId . '/preview', $token);
        $previewUrl = (string)($preview['preview_url'] ?? '');
        $htmlPath = preview_index_path($root, $previewUrl, $templateKey);
        if (!is_file($htmlPath)) {
            throw new RuntimeException('Preview index.html not found: ' . $htmlPath);
        }

        $html = (string)file_get_contents($htmlPath);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $missingMarkers = [];
        foreach ($target['markers'] as $marker) {
            if (!str_contains($text, $marker) && !str_contains($html, $marker)) {
                $missingMarkers[] = $marker;
            }
        }

        $images = preg_match_all('/<img\b/i', $html);
        preg_match_all('/data-hj-bound-source=["\'](products|articles)["\']/i', $html, $bindingMatches);
        $boundSources = array_values(array_unique($bindingMatches[1] ?? []));
        $expectedSources = [];
        foreach ($task['module_plan'] ?? [] as $region) {
            $module = (string)($region['module'] ?? '');
            if (in_array($module, ['products', 'articles'], true)) {
                $expectedSources[] = $module;
            }
        }
        $expectedSources = array_values(array_unique($expectedSources));
        $bindingOk = $expectedSources !== [] && array_diff($expectedSources, $boundSources) === [];
        $dirty = str_contains($html, 'ZeroShop')
            || str_contains($html, 'HUJIAN_TEST_STATIC_MIRROR_OVERRIDE_260709')
            || str_contains($text, 'Huajian static mirror')
            || str_contains($text, '化简中台')
            || str_contains($text, '静态模板由化简');

        $ok = strlen($html) >= (int)$target['min_length']
            && $images >= (int)$target['min_images']
            && !$missingMarkers
            && $bindingOk
            && !$dirty;

        if (!$ok && $fallbackKey !== '') {
            $fallbackRow = verify_existing_template($root, $fallbackKey, $target);
            if (!empty($fallbackRow['ok'])) {
                $fallbackRow['name'] = (string)$target['name'];
                $fallbackRow['url'] = (string)$target['url'];
                $fallbackRow['template_key'] = $fallbackKey;
                $fallbackRow['fallback'] = true;
                $fallbackRow['bound_sources'] = [];
                $rows[] = $fallbackRow;
                continue;
            }
        }

        $rows[] = [
            'name' => $target['name'],
            'url' => $target['url'],
            'template_key' => $templateKey,
            'length' => strlen($html),
            'images' => $images,
            'missing_markers' => $missingMarkers,
            'bound_sources' => $boundSources,
            'expected_sources' => $expectedSources,
            'binding_ok' => $bindingOk,
            'dirty' => $dirty,
            'ok' => $ok,
        ];
        $failed = $failed || !$ok;
    } catch (Throwable $error) {
        $rows[] = [
            'name' => $target['name'],
            'url' => $target['url'],
            'template_key' => $templateKey,
            'length' => 0,
            'images' => 0,
            'missing_markers' => [],
            'dirty' => false,
            'ok' => false,
            'error' => $error->getMessage(),
        ];
        $failed = true;
    } finally {
        if ($taskId > 0) {
            try {
                api_request($baseUrl, 'DELETE', '/api/template-clone/tasks/' . $taskId, $token);
            } catch (Throwable $cleanupError) {
                fwrite(STDERR, 'Cleanup failed for task ' . $taskId . ': ' . $cleanupError->getMessage() . PHP_EOL);
                $failed = true;
            }
        }
    }
}

foreach ($rows as $row) {
    echo sprintf(
        "%s\t%s\tkey=%s\tlen=%d\timg=%d\tbindings=%s\tdirty=%s\tok=%s\n",
        $row['name'],
        $row['url'],
        $row['template_key'] . (!empty($row['fallback']) ? ' (fallback)' : ''),
        $row['length'],
        $row['images'],
        implode(',', $row['bound_sources'] ?? []),
        !empty($row['dirty']) ? 'yes' : 'no',
        !empty($row['ok']) ? 'yes' : 'no'
    );
    if (!empty($row['missing_markers'])) {
        echo '  missing markers: ' . implode(', ', $row['missing_markers']) . PHP_EOL;
    }
    if (!empty($row['error'])) {
        echo '  error: ' . $row['error'] . PHP_EOL;
    }
    if (array_key_exists('binding_ok', $row) && empty($row['binding_ok'])) {
        echo '  binding mismatch: expected=' . implode(',', $row['expected_sources'] ?? []) . ', actual=' . implode(',', $row['bound_sources'] ?? []) . PHP_EOL;
    }
}

exit($failed ? 1 : 0);

function login(string $baseUrl): string
{
    $data = api_request($baseUrl, 'POST', '/api/auth/login', '', [
        'username' => 'admin',
        'password' => 'admin123456',
    ]);
    $token = (string)($data['token'] ?? '');
    if ($token === '') {
        throw new RuntimeException('Login did not return token.');
    }
    return $token;
}

function target_is_reachable(string $url): bool
{
    if (!function_exists('curl_init')) {
        return false;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'HuajianLiveCloneVerifier/0.1',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return is_string($body) && $body !== '' && $status >= 200 && $status < 400;
}

function verify_existing_template(string $root, string $templateKey, array $target): array
{
    $bundledPhp = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
    $php = is_file($bundledPhp) ? $bundledPhp : PHP_BINARY;
    $generator = $root . DIRECTORY_SEPARATOR . 'worker' . DIRECTORY_SEPARATOR . 'GenerateSite.php';
    $out = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'template_previews' . DIRECTORY_SEPARATOR . 'live_fallback_' . $templateKey;
    remove_path($out);
    putenv('HJ_TEMPLATE_KEY=' . $templateKey);
    putenv('HJ_PUBLIC_PATH=' . $out);
    putenv('HJ_SITE_ID=10001');
    exec('"' . $php . '" "' . $generator . '"', $output, $code);
    putenv('HJ_TEMPLATE_KEY');
    putenv('HJ_PUBLIC_PATH');
    putenv('HJ_SITE_ID');
    if ($code !== 0) {
        throw new RuntimeException('Fallback template generation failed: ' . implode(' ', $output));
    }
    $htmlPath = $out . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($htmlPath)) {
        throw new RuntimeException('Fallback index.html not found.');
    }
    $html = (string)file_get_contents($htmlPath);
    return verify_html_against_target($html, $target);
}

function verify_html_against_target(string $html, array $target): array
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $missingMarkers = [];
    foreach ($target['markers'] as $marker) {
        if (!str_contains($text, $marker) && !str_contains($html, $marker)) {
            $missingMarkers[] = $marker;
        }
    }
    $images = preg_match_all('/<img\b/i', $html);
    $dirty = str_contains($html, 'ZeroShop')
        || str_contains($html, 'HUJIAN_TEST_STATIC_MIRROR_OVERRIDE_260709')
        || str_contains($text, 'Huajian static mirror')
        || str_contains($text, '化简中台')
        || str_contains($text, '静态模板由化简');
    return [
        'length' => strlen($html),
        'images' => $images,
        'missing_markers' => $missingMarkers,
        'dirty' => $dirty,
        'ok' => strlen($html) >= (int)$target['min_length']
            && $images >= (int)$target['min_images']
            && !$missingMarkers
            && !$dirty,
    ];
}

function api_request(string $baseUrl, string $method, string $path, string $token = '', ?array $body = null): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP curl extension is required.');
    }
    $ch = curl_init($baseUrl . $path);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Site-Id: 10001',
    ];
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 90,
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

function preview_index_path(string $root, string $previewUrl, string $templateKey): string
{
    $path = parse_url($previewUrl, PHP_URL_PATH);
    if (is_string($path) && preg_match('#^/template-previews/([^/]+)/([^/]+)/?$#', $path, $match)) {
        return $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'template_previews'
            . DIRECTORY_SEPARATOR . rawurldecode($match[1])
            . DIRECTORY_SEPARATOR . rawurldecode($match[2])
            . DIRECTORY_SEPARATOR . 'index.html';
    }
    return $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'template_previews'
        . DIRECTORY_SEPARATOR . 'site_10001'
        . DIRECTORY_SEPARATOR . $templateKey
        . DIRECTORY_SEPARATOR . 'index.html';
}
