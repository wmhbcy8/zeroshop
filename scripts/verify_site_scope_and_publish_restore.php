<?php
declare(strict_types=1);

$baseUrl = rtrim((string)($argv[1] ?? 'http://127.0.0.1:8000'), '/');
$root = dirname(__DIR__);
$token = login($baseUrl);
$failed = false;
$rows = [];

try {
    $sites = api_request($baseUrl, 'GET', '/api/sites', $token, null, 10001)['items'] ?? [];
    $siteIds = array_values(array_map(static fn($item): int => (int)($item['id'] ?? 0), $sites));
    $siteIds = array_values(array_filter($siteIds, static fn(int $id): bool => $id > 0));
    if (!$siteIds) {
        throw new RuntimeException('No site is available for verification.');
    }

    $primarySiteId = in_array(10001, $siteIds, true) ? 10001 : $siteIds[0];
    $scopeOk = count($siteIds) >= 2 ? verify_site_scope($baseUrl, $token, $siteIds[0], $siteIds[1]) : null;
    $rows[] = [
        'name' => 'site scoped settings and menus',
        'ok' => $scopeOk !== false,
        'message' => $scopeOk === null ? 'skipped: only one site exists' : ($scopeOk ? 'ok' : 'failed'),
    ];
    $failed = $failed || $scopeOk === false;

    $rows[] = verify_publish_rollback($baseUrl, $token, $root, $primarySiteId);
    $failed = $failed || empty(end($rows)['ok']);

    $rows[] = verify_backup_restore($baseUrl, $token, $root, $primarySiteId);
    $failed = $failed || empty(end($rows)['ok']);
} catch (Throwable $error) {
    $rows[] = [
        'name' => 'verification bootstrap',
        'ok' => false,
        'message' => $error->getMessage(),
    ];
    $failed = true;
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

function verify_site_scope(string $baseUrl, string $token, int $siteA, int $siteB): bool
{
    $settingsA = api_request($baseUrl, 'GET', '/api/site/settings', $token, null, $siteA);
    $settingsB = api_request($baseUrl, 'GET', '/api/site/settings', $token, null, $siteB);
    $menusA = api_request($baseUrl, 'GET', '/api/menus', $token, null, $siteA);
    $menusB = api_request($baseUrl, 'GET', '/api/menus', $token, null, $siteB);
    $primaryA = menu_items($menusA, 'primary');
    $primaryB = menu_items($menusB, 'primary');

    $marker = 'HJ_SCOPE_' . date('YmdHis') . '_' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    $settingsTestA = $settingsA;
    $settingsTestB = $settingsB;
    $settingsTestA['verification_marker'] = $marker . '_A';
    $settingsTestB['verification_marker'] = $marker . '_B';
    $menuTestA = [[
        'id' => 'verify-' . strtolower($marker) . '-a',
        'title' => $marker . '_A',
        'type' => 'custom',
        'url' => '/verify-a.html',
        'target' => '_self',
        'target_blank' => false,
        'children' => [],
    ]];
    $menuTestB = [[
        'id' => 'verify-' . strtolower($marker) . '-b',
        'title' => $marker . '_B',
        'type' => 'custom',
        'url' => '/verify-b.html',
        'target' => '_self',
        'target_blank' => false,
        'children' => [],
    ]];

    try {
        api_request($baseUrl, 'PUT', '/api/site/settings', $token, $settingsTestA, $siteA);
        api_request($baseUrl, 'PUT', '/api/site/settings', $token, $settingsTestB, $siteB);
        api_request($baseUrl, 'PUT', '/api/menus/primary', $token, ['items' => $menuTestA], $siteA);
        api_request($baseUrl, 'PUT', '/api/menus/primary', $token, ['items' => $menuTestB], $siteB);

        $afterSettingsA = api_request($baseUrl, 'GET', '/api/site/settings', $token, null, $siteA);
        $afterSettingsB = api_request($baseUrl, 'GET', '/api/site/settings', $token, null, $siteB);
        $afterMenusA = api_request($baseUrl, 'GET', '/api/menus', $token, null, $siteA);
        $afterMenusB = api_request($baseUrl, 'GET', '/api/menus', $token, null, $siteB);

        return ($afterSettingsA['verification_marker'] ?? '') === $marker . '_A'
            && ($afterSettingsB['verification_marker'] ?? '') === $marker . '_B'
            && menu_has_title($afterMenusA, 'primary', $marker . '_A')
            && menu_has_title($afterMenusB, 'primary', $marker . '_B')
            && !menu_has_title($afterMenusA, 'primary', $marker . '_B')
            && !menu_has_title($afterMenusB, 'primary', $marker . '_A');
    } finally {
        unset($settingsA['verification_marker'], $settingsB['verification_marker']);
        api_request($baseUrl, 'PUT', '/api/site/settings', $token, $settingsA, $siteA);
        api_request($baseUrl, 'PUT', '/api/site/settings', $token, $settingsB, $siteB);
        api_request($baseUrl, 'PUT', '/api/menus/primary', $token, ['items' => $primaryA], $siteA);
        api_request($baseUrl, 'PUT', '/api/menus/primary', $token, ['items' => $primaryB], $siteB);
    }
}

function verify_publish_rollback(string $baseUrl, string $token, string $root, int $siteId): array
{
    $generate = api_request($baseUrl, 'POST', '/api/site/generate', $token, null, $siteId);
    $versionNo = (string)($generate['version_no'] ?? '');
    $versions = api_request($baseUrl, 'GET', '/api/site/publish-versions?page_size=10', $token, null, $siteId)['items'] ?? [];
    $version = find_version($versions, $versionNo);
    if (!$version) {
        throw new RuntimeException('Generated publish version was not listed.');
    }
    $publicIndex = public_index_path($root, $baseUrl, $token, $siteId);
    $before = (string)file_get_contents($publicIndex);
    $marker = 'HJ_ROLLBACK_MUTATION_' . date('YmdHis') . '_' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    file_put_contents($publicIndex, $before . "\n<!-- {$marker} -->\n");
    $mutated = (string)file_get_contents($publicIndex);
    if (!str_contains($mutated, $marker)) {
        throw new RuntimeException('Could not mutate public index for rollback verification.');
    }
    api_request($baseUrl, 'POST', '/api/site/rollback', $token, ['version_id' => (int)$version['id']], $siteId);
    $after = (string)file_get_contents($publicIndex);
    return [
        'name' => 'publish rollback restores public snapshot',
        'ok' => $after === $before && !str_contains($after, $marker),
        'message' => 'version=' . $versionNo,
    ];
}

function verify_backup_restore(string $baseUrl, string $token, string $root, int $siteId): array
{
    $publicIndex = public_index_path($root, $baseUrl, $token, $siteId);
    if (!is_file($publicIndex)) {
        api_request($baseUrl, 'POST', '/api/site/generate', $token, null, $siteId);
    }
    $before = (string)file_get_contents($publicIndex);
    $backup = api_request($baseUrl, 'POST', '/api/site/backups', $token, [
        'backup_type' => 'manual',
        'message' => 'verification backup',
    ], $siteId);
    $backupId = (int)($backup['id'] ?? 0);
    if ($backupId <= 0) {
        throw new RuntimeException('Backup API did not return an id.');
    }
    $marker = 'HJ_RESTORE_MUTATION_' . date('YmdHis') . '_' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    file_put_contents($publicIndex, $before . "\n<!-- {$marker} -->\n");
    api_request($baseUrl, 'POST', '/api/site/backups/' . $backupId . '/restore', $token, null, $siteId);
    $after = (string)file_get_contents($publicIndex);
    return [
        'name' => 'site backup restore restores public snapshot',
        'ok' => $after === $before && !str_contains($after, $marker),
        'message' => 'backup=' . (string)($backup['backup_no'] ?? $backupId),
    ];
}

function find_version(array $versions, string $versionNo): ?array
{
    foreach ($versions as $version) {
        if ((string)($version['version_no'] ?? '') === $versionNo) {
            return $version;
        }
    }
    return $versions[0] ?? null;
}

function public_index_path(string $root, string $baseUrl, string $token, int $siteId): string
{
    $preview = api_request($baseUrl, 'GET', '/api/site/preview', $token, null, $siteId);
    $relative = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)($preview['public_path'] ?? '')), DIRECTORY_SEPARATOR);
    if ($relative === '' || str_contains($relative, '..')) {
        throw new RuntimeException('Invalid public path from preview API.');
    }
    $path = $root . DIRECTORY_SEPARATOR . $relative . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($path)) {
        throw new RuntimeException('Public index does not exist: ' . $path);
    }
    return $path;
}

function menu_items(array $menus, string $key): array
{
    foreach (($menus['items'] ?? []) as $menu) {
        if ((string)($menu['menu_key'] ?? '') === $key) {
            return is_array($menu['items'] ?? null) ? $menu['items'] : [];
        }
    }
    return [];
}

function menu_has_title(array $menus, string $key, string $title): bool
{
    foreach (menu_items($menus, $key) as $item) {
        if ((string)($item['title'] ?? '') === $title) {
            return true;
        }
    }
    return false;
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
