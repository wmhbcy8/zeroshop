<?php

declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
if (!str_starts_with($requestPath, '/api')) {
    $serverPublic = __DIR__;
    $serverRelativePath = ltrim($requestPath, '/');
    if ($serverRelativePath !== '' && $serverRelativePath !== 'index.php') {
        $serverTargetPath = realpath($serverPublic . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $serverRelativePath));
        $serverBase = realpath($serverPublic);
        if ($serverTargetPath && $serverBase && str_starts_with($serverTargetPath, $serverBase) && is_dir($serverTargetPath)) {
            $serverIndexPath = realpath($serverTargetPath . DIRECTORY_SEPARATOR . 'index.html');
            if ($serverIndexPath && str_starts_with($serverIndexPath, $serverBase) && is_file($serverIndexPath)) {
                header('Content-Type: text/html; charset=utf-8');
                readfile($serverIndexPath);
                exit;
            }
        }
        if ($serverTargetPath && $serverBase && str_starts_with($serverTargetPath, $serverBase) && is_file($serverTargetPath)) {
            $ext = strtolower(pathinfo($serverTargetPath, PATHINFO_EXTENSION));
            $types = [
                'html' => 'text/html; charset=utf-8',
                'css' => 'text/css; charset=utf-8',
                'js' => 'application/javascript; charset=utf-8',
                'json' => 'application/json; charset=utf-8',
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
            ];
            header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
            readfile($serverTargetPath);
            exit;
        }
    }

    if (preg_match('#^/s/(site_\d+)(?:/(.*))?$#', $requestPath, $sitePreviewMatch)) {
        $siteKey = $sitePreviewMatch[1];
        $siteRelativePath = trim((string)($sitePreviewMatch[2] ?? ''), '/');
        $siteRelativePath = $siteRelativePath === '' ? 'index.html' : $siteRelativePath;
        $siteStaticRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . $siteKey . DIRECTORY_SEPARATOR . 'public';
        $siteTargetPath = realpath($siteStaticRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $siteRelativePath));
        $siteStaticBase = realpath($siteStaticRoot);
        if ($siteTargetPath && $siteStaticBase && str_starts_with($siteTargetPath, $siteStaticBase) && is_file($siteTargetPath)) {
            $ext = strtolower(pathinfo($siteTargetPath, PATHINFO_EXTENSION));
            $types = [
                'html' => 'text/html; charset=utf-8',
                'css' => 'text/css; charset=utf-8',
                'js' => 'application/javascript; charset=utf-8',
                'json' => 'application/json; charset=utf-8',
                'xml' => 'application/xml; charset=utf-8',
                'txt' => 'text/plain; charset=utf-8',
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
            ];
            header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
            readfile($siteTargetPath);
            exit;
        }
        $site404Path = realpath($siteStaticRoot . DIRECTORY_SEPARATOR . '404.html');
        if ($site404Path && $siteStaticBase && str_starts_with($site404Path, $siteStaticBase) && is_file($site404Path)) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            readfile($site404Path);
            exit;
        }
    }

    if (preg_match('#^/template-previews/([a-zA-Z0-9_-]+)/([a-zA-Z0-9_-]+)(?:/(.*))?$#', $requestPath, $templatePreviewMatch)) {
        $siteKey = $templatePreviewMatch[1];
        $templateKey = $templatePreviewMatch[2];
        $previewRelativePath = trim((string)($templatePreviewMatch[3] ?? ''), '/');
        $previewRelativePath = $previewRelativePath === '' ? 'index.html' : $previewRelativePath;
        $previewRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'template_previews' . DIRECTORY_SEPARATOR . $siteKey . DIRECTORY_SEPARATOR . $templateKey;
        $previewTargetPath = realpath($previewRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $previewRelativePath));
        $previewBase = realpath($previewRoot);
        if ($previewTargetPath && $previewBase && str_starts_with($previewTargetPath, $previewBase) && is_file($previewTargetPath)) {
            $ext = strtolower(pathinfo($previewTargetPath, PATHINFO_EXTENSION));
            $types = [
                'html' => 'text/html; charset=utf-8',
                'css' => 'text/css; charset=utf-8',
                'js' => 'application/javascript; charset=utf-8',
                'json' => 'application/json; charset=utf-8',
                'xml' => 'application/xml; charset=utf-8',
                'txt' => 'text/plain; charset=utf-8',
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
            ];
            header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
            readfile($previewTargetPath);
            exit;
        }
    }

    $staticRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'site_10001' . DIRECTORY_SEPARATOR . 'public';
    $relativePath = $requestPath === '/' ? 'index.html' : ltrim($requestPath, '/');
    $targetPath = realpath($staticRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));
    $staticBase = realpath($staticRoot);
    if ($targetPath && $staticBase && str_starts_with($targetPath, $staticBase) && is_file($targetPath)) {
        $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        $types = [
            'html' => 'text/html; charset=utf-8',
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'xml' => 'application/xml; charset=utf-8',
            'txt' => 'text/plain; charset=utf-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        readfile($targetPath);
        exit;
    }
    $static404Path = realpath($staticRoot . DIRECTORY_SEPARATOR . '404.html');
    if ($static404Path && $staticBase && str_starts_with($static404Path, $staticBase) && is_file($static404Path)) {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        readfile($static404Path);
        exit;
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Site-Id');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ok($data = [], string $message = 'ok'): void
{
    auto_operation_log($message, $data);
    json_response(['success' => true, 'message' => $message, 'data' => $data]);
}

function fail(string $message, string $code = 'ERROR', int $status = 400, array $details = []): void
{
    json_response([
        'success' => false,
        'message' => $message,
        'error' => ['code' => $code, 'details' => $details],
    ], $status);
}

function env_value(string $key, ?string $default = null): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        if ($default !== null) {
            return $default;
        }
        fail("Missing environment variable: {$key}", 'CONFIG_ERROR', 500);
    }
    return $value;
}

function site_pdo(): PDO
{
    $host = env_value('HJ_DB_HOST');
    $port = env_value('HJ_DB_PORT', '3306');
    $database = env_value('HJ_DB_SITE', 'huajian_site_10001');
    $user = env_value('HJ_DB_USERNAME');
    $password = env_value('HJ_DB_PASSWORD', '');
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function main_pdo(): PDO
{
    $host = env_value('HJ_DB_HOST');
    $port = env_value('HJ_DB_PORT', '3306');
    $database = env_value('HJ_DB_MAIN', 'huajian_main');
    $user = env_value('HJ_DB_USERNAME');
    $password = env_value('HJ_DB_PASSWORD', '');
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function body_json(): array
{
    $raw = request_raw_body();
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        fail('JSON 格式错误', 'INVALID_JSON', 400);
    }
    return $data;
}

function request_raw_body(): string
{
    static $raw = null;
    if ($raw === null) {
        $value = file_get_contents('php://input');
        $raw = is_string($value) ? $value : '';
    }
    return $raw;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function client_ip(): string
{
    $ip = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
    if (str_contains($ip, ',')) {
        $ip = trim(explode(',', $ip)[0]);
    }
    return text_limit($ip, 64);
}

function read_config_json(string $filename): array
{
    $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($path)) {
        fail("Config file not found: {$filename}", 'CONFIG_ERROR', 500);
    }
    $data = json_decode((string)file_get_contents($path), true);
    if (!is_array($data)) {
        fail("Config file is invalid JSON: {$filename}", 'CONFIG_ERROR', 500);
    }
    return $data;
}

function template_registry(): array
{
    $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'templates';
    $items = [];
    if (!is_dir($root)) {
        return ['items' => []];
    }
    foreach (glob($root . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'template.json') ?: [] as $path) {
        $data = json_decode((string)file_get_contents($path), true);
        if (!is_array($data)) {
            continue;
        }
        $key = (string)($data['key'] ?? basename(dirname($path)));
        $items[] = [
            'key' => $key,
            'name' => (string)($data['name'] ?? $key),
            'version' => (string)($data['version'] ?? ''),
            'author' => (string)($data['author'] ?? ''),
            'type' => $data['type'] ?? [],
            'supports' => $data['supports'] ?? [],
            'entry' => (string)($data['entry'] ?? ''),
            'path' => 'templates/' . basename(dirname($path)),
        ];
    }
    usort($items, fn($a, $b) => strcmp((string)$a['key'], (string)$b['key']));
    return ['items' => $items];
}

function ensure_dir(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function app_secret_key(): string
{
    $envKey = getenv('HJ_APP_KEY');
    if (is_string($envKey) && trim($envKey) !== '') {
        return hash('sha256', trim($envKey), true);
    }
    $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'secret.key';
    ensure_dir(dirname($path));
    if (!is_file($path)) {
        file_put_contents($path, bin2hex(random_bytes(32)));
    }
    return hash('sha256', trim((string)file_get_contents($path)), true);
}

function encrypt_secret(?string $value): string
{
    $plain = (string)$value;
    if ($plain === '' || str_starts_with($plain, 'hjenc:v1:')) {
        return $plain;
    }
    if (!function_exists('openssl_encrypt')) {
        fail('当前 PHP 环境缺少 OpenSSL，无法加密敏感配置', 'CRYPTO_UNAVAILABLE', 500);
    }
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', app_secret_key(), OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) {
        fail('敏感配置加密失败', 'CRYPTO_FAILED', 500);
    }
    $mac = hash_hmac('sha256', $iv . $cipher, app_secret_key(), true);
    return 'hjenc:v1:' . base64_encode($iv . $mac . $cipher);
}

function decrypt_secret(?string $value): string
{
    $value = (string)$value;
    if ($value === '' || !str_starts_with($value, 'hjenc:v1:')) {
        return $value;
    }
    if (!function_exists('openssl_decrypt')) {
        return '';
    }
    $raw = base64_decode(substr($value, 9), true);
    if ($raw === false || strlen($raw) < 49) {
        return '';
    }
    $iv = substr($raw, 0, 16);
    $mac = substr($raw, 16, 32);
    $cipher = substr($raw, 48);
    $calc = hash_hmac('sha256', $iv . $cipher, app_secret_key(), true);
    if (!hash_equals($mac, $calc)) {
        return '';
    }
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', app_secret_key(), OPENSSL_RAW_DATA, $iv);
    return is_string($plain) ? $plain : '';
}

function decrypt_secret_array(array $data, array $keys): array
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $data)) {
            $data[$key] = decrypt_secret((string)$data[$key]);
        }
    }
    return $data;
}

function encrypt_secret_array(array $data, array $keys): array
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $data) && (string)$data[$key] !== '') {
            $data[$key] = encrypt_secret((string)$data[$key]);
        }
    }
    return $data;
}

function sensitive_config_keys(): array
{
    return ['api_key', 'secret', 'secret_key', 'client_secret', 'access_token', 'token', 'private_key', 'webhook_secret', 'merchant_key'];
}

function decrypt_sensitive_config($value)
{
    if (is_array($value)) {
        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = in_array((string)$key, sensitive_config_keys(), true)
                ? decrypt_secret((string)$item)
                : decrypt_sensitive_config($item);
        }
        return $result;
    }
    return $value;
}

function encrypt_sensitive_config($value)
{
    if (is_array($value)) {
        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = in_array((string)$key, sensitive_config_keys(), true)
                ? encrypt_secret((string)$item)
                : encrypt_sensitive_config($item);
        }
        return $result;
    }
    return $value;
}

function mask_sensitive_config($value)
{
    if (is_array($value)) {
        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = in_array((string)$key, sensitive_config_keys(), true)
                ? mask_secret(decrypt_secret((string)$item))
                : mask_sensitive_config($item);
        }
        return $result;
    }
    return $value;
}

function site_public_path(array $site): string
{
    $siteKey = (string)($site['site_key'] ?? 'site_10001');
    $relative = trim((string)($site['public_path'] ?? ''));
    if ($relative === '') {
        $relative = 'sites/' . $siteKey . '/public';
    }
    $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
    if (str_contains($relative, '..')) {
        fail('站点发布目录不安全', 'INVALID_SITE_PATH', 422);
    }
    return $relative;
}

function site_public_root(array $site): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . site_public_path($site);
}

function public_root(?array $site = null): string
{
    return $site ? site_public_root($site) : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'site_10001' . DIRECTORY_SEPARATOR . 'public';
}

function package_root(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'packages';
}

function publish_version_root(array $site): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'publish_versions' . DIRECTORY_SEPARATOR . (string)($site['site_key'] ?? 'site_10001');
}

function site_backup_root(array $site): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'site_backups' . DIRECTORY_SEPARATOR . (string)($site['site_key'] ?? 'site_10001');
}

function deploy_target_root(): string
{
    $root = trim(env_value('HJ_LOCAL_DEPLOY_ROOT', ''));
    if ($root === '') {
        $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'deploy_targets';
    }
    $root = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $root);
    ensure_dir($root);
    assert_workspace_path($root);
    return $root;
}

function deploy_backup_root(array $site): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'deploy_backups' . DIRECTORY_SEPARATOR . (string)($site['site_key'] ?? 'site_10001');
}

function assert_workspace_path(string $path): void
{
    $root = realpath(dirname(__DIR__, 2));
    $target = realpath($path);
    if (!$root || !$target || !str_starts_with($target, $root)) {
        fail('文件路径不安全', 'INVALID_PATH', 422);
    }
}

function remove_dir_contents(string $dir): void
{
    ensure_dir($dir);
    assert_workspace_path($dir);
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
}

function copy_directory(string $src, string $dst): int
{
    $sourceRoot = realpath($src);
    if (!$sourceRoot || !is_dir($sourceRoot)) {
        fail('源目录不存在', 'SOURCE_DIR_MISSING', 404);
    }
    ensure_dir($dst);
    assert_workspace_path($sourceRoot);
    assert_workspace_path($dst);
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    $fileCount = 0;
    foreach ($items as $item) {
        $relative = substr($item->getPathname(), strlen($sourceRoot) + 1);
        $target = $dst . DIRECTORY_SEPARATOR . $relative;
        if ($item->isDir()) {
            ensure_dir($target);
            continue;
        }
        ensure_dir(dirname($target));
        copy($item->getPathname(), $target);
        $fileCount++;
    }
    return $fileCount;
}

function directory_size(string $dir): int
{
    $root = realpath($dir);
    if (!$root || !is_dir($root)) {
        return 0;
    }
    $size = 0;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        if ($item->isFile()) {
            $size += (int)$item->getSize();
        }
    }
    return $size;
}

function remove_directory_tree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    remove_dir_contents($dir);
    assert_workspace_path($dir);
    rmdir($dir);
}

function directory_has_files(string $dir): bool
{
    $root = realpath($dir);
    if (!$root || !is_dir($root)) {
        return false;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        if ($item->isFile()) {
            return true;
        }
    }
    return false;
}

function normalize_local_deploy_relative_path(string $sitePath, array $site): string
{
    $path = trim($sitePath);
    if ($path === '') {
        $path = (string)($site['site_key'] ?? 'site_10001');
    }
    $path = str_replace('\\', '/', $path);
    $path = trim($path, '/');
    if ($path === '' || str_contains($path, '..') || preg_match('/^[a-z]:/i', $path)) {
        fail('本机同步目录必须是部署根目录下的相对路径', 'INVALID_DEPLOY_PATH', 422);
    }
    $segments = array_values(array_filter(explode('/', $path), fn($segment) => $segment !== ''));
    $safeSegments = [];
    foreach ($segments as $segment) {
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $segment);
        $safe = trim((string)$safe, '.-');
        if ($safe === '') {
            fail('本机同步目录包含无效路径片段', 'INVALID_DEPLOY_PATH', 422);
        }
        $safeSegments[] = $safe;
    }
    return implode(DIRECTORY_SEPARATOR, $safeSegments);
}

function local_deploy_target_path(string $sitePath, array $site): string
{
    $root = deploy_target_root();
    $relative = normalize_local_deploy_relative_path($sitePath, $site);
    $target = $root . DIRECTORY_SEPARATOR . $relative;
    ensure_dir($target);
    assert_workspace_path($target);
    $resolvedRoot = realpath($root);
    $resolvedTarget = realpath($target);
    if (!$resolvedRoot || !$resolvedTarget || !str_starts_with($resolvedTarget, $resolvedRoot)) {
        fail('本机同步目标目录不安全', 'INVALID_DEPLOY_PATH', 422);
    }
    return $target;
}

function create_deploy_target_backup(array $site, string $targetPath): ?array
{
    if (!directory_has_files($targetPath)) {
        return null;
    }
    $backupNo = (string)($site['site_key'] ?? 'site') . '_deploy_backup_' . date('Ymd_His') . '_' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    $backupRoot = deploy_backup_root($site) . DIRECTORY_SEPARATOR . $backupNo;
    remove_dir_contents($backupRoot);
    $fileCount = copy_directory($targetPath, $backupRoot);
    return [
        'backup_no' => $backupNo,
        'backup_path' => str_replace(DIRECTORY_SEPARATOR, '/', substr($backupRoot, strlen(dirname(__DIR__, 2)) + 1)),
        'backup_file_count' => $fileCount,
        'backup_file_size' => directory_size($backupRoot),
    ];
}

function sync_static_site_to_local_target(array $site, array $deploy): array
{
    $publicRoot = public_root($site);
    if (!is_dir($publicRoot)) {
        fail('请先生成静态站', 'STATIC_SITE_MISSING', 422);
    }
    $targetPath = local_deploy_target_path((string)($deploy['site_path'] ?? ''), $site);
    $backup = create_deploy_target_backup($site, $targetPath);
    remove_dir_contents($targetPath);
    $fileCount = copy_directory($publicRoot, $targetPath);
    return [
        'deployed' => true,
        'deployed_file_count' => $fileCount,
        'target_path' => str_replace(DIRECTORY_SEPARATOR, '/', $targetPath),
        'target_root' => str_replace(DIRECTORY_SEPARATOR, '/', deploy_target_root()),
    ] + ($backup ?: []);
}

function create_static_package(?array $site = null): array
{
    $siteKey = (string)($site['site_key'] ?? 'site_10001');
    $publicRoot = realpath(public_root($site));
    if (!$publicRoot || !is_dir($publicRoot)) {
        fail('请先生成静态站', 'STATIC_SITE_MISSING', 422);
    }

    if (!class_exists(PharData::class)) {
        fail('当前 PHP 环境不支持 PharData 打包', 'PACKAGE_UNSUPPORTED', 500);
    }

    $packageRoot = package_root();
    ensure_dir($packageRoot);
    $versionNo = $siteKey . '_package_' . date('Ymd_His');
    $tarPath = $packageRoot . DIRECTORY_SEPARATOR . $versionNo . '.tar';
    $gzPath = $tarPath . '.gz';
    if (is_file($tarPath)) {
        unlink($tarPath);
    }
    if (is_file($gzPath)) {
        unlink($gzPath);
    }

    $tar = new PharData($tarPath);
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($publicRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    $fileCount = 0;
    foreach ($files as $file) {
        $pathName = $file->getPathname();
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($pathName, strlen($publicRoot) + 1));
        if ($file->isDir()) {
            $tar->addEmptyDir($relative);
            continue;
        }
        $tar->addFile($pathName, $relative);
        $fileCount++;
    }
    $tar->compress(Phar::GZ);
    unset($tar);
    if (is_file($tarPath)) {
        unlink($tarPath);
    }
    clearstatcache(true, $gzPath);

    return [
        'version_no' => $versionNo,
        'file_path' => 'storage/packages/' . basename($gzPath),
        'absolute_path' => $gzPath,
        'file_count' => $fileCount,
        'file_size' => is_file($gzPath) ? filesize($gzPath) : 0,
    ];
}

function route_param(string $pattern, string $path): ?array
{
    $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
    if (preg_match($regex, $path, $matches)) {
        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }
    return null;
}

function require_fields(array $data, array $fields): void
{
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $errors[$field][] = '不能为空';
        }
    }
    if ($errors) {
        fail('参数错误', 'VALIDATION_ERROR', 422, $errors);
    }
}

function fetch_one(PDO $pdo, string $table, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    return $item ?: null;
}

function paginate(PDO $pdo, string $table, array $where = [], string $order = 'id DESC', string $keywordColumn = 'title', array $extraParams = []): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));

    $clauses = $where;
    $params = $extraParams;
    if ($keyword !== '') {
        $clauses[] = "{$keywordColumn} LIKE :keyword";
        $params['keyword'] = '%' . $keyword . '%';
    }
    if ($status !== '') {
        $clauses[] = 'status = :status';
        $params['status'] = $status;
    }

    $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM {$table}{$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM {$table}{$whereSql} ORDER BY {$order} LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute($params);

    return [
        'items' => $stmt->fetchAll(),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
    $stmt->execute([$column]);
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}

function resolve_request_site_id(array $data = []): int
{
    $candidates = [
        $data['site_id'] ?? null,
        $_GET['site_id'] ?? null,
        $_SERVER['HTTP_X_SITE_ID'] ?? null,
    ];
    foreach ($candidates as $candidate) {
        if (is_numeric($candidate) && (int)$candidate > 0) {
            return (int)$candidate;
        }
    }
    return 10001;
}

function requested_site_filter(): ?int
{
    $raw = $_GET['site_id'] ?? 'all';
    if ($raw === '' || $raw === 'all') {
        return null;
    }
    $siteId = resolve_request_site_id(['site_id' => $raw]);
    assert_site_access($siteId);
    return $siteId;
}

function site_name_map(PDO $main): array
{
    ensure_center_tables($main);
    $rows = $main->query('SELECT id, name FROM sites ORDER BY id ASC')->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $map[(int)$row['id']] = (string)($row['name'] ?? '');
    }
    return $map;
}

function attach_site_names(array $items, ?PDO $main = null): array
{
    $map = [];
    if ($main) {
        try {
            $map = site_name_map($main);
        } catch (Throwable $error) {
            $map = [];
        }
    }
    return array_map(static function (array $item) use ($map) {
        $siteId = (int)($item['site_id'] ?? 10001);
        $item['site_id'] = $siteId;
        $item['site_name'] = $map[$siteId] ?? ('Site ' . $siteId);
        return $item;
    }, $items);
}

function append_order_note(string $remark, string $note): string
{
    $note = trim($note);
    if ($note === '') {
        return $remark;
    }
    $line = '[' . now() . '] ' . $note;
    return trim($remark) === '' ? $line : rtrim($remark) . "\n" . $line;
}

function public_order_timeline(string $remark): array
{
    $items = [];
    foreach (preg_split('/\r?\n/', $remark) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $text = $line;
        $time = '';
        if (preg_match('/^\[(.+?)\]\s*(.+)$/u', $line, $matches)) {
            $time = $matches[1];
            $text = $matches[2];
        }
        $visible = false;
        foreach (['客户服务请求', '客户提交', '客服回复', '客服已处理', '订单标记', '发货', '支付'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $visible = true;
                break;
            }
        }
        if (!$visible) {
            continue;
        }
        $items[] = [
            'time' => $time,
            'text' => $text,
        ];
    }
    return $items;
}

function parse_order_timeline_lines(string $remark): array
{
    $items = [];
    foreach (preg_split('/\r?\n/', $remark) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $text = $line;
        $time = '';
        if (preg_match('/^\[(.+?)\]\s*(.+)$/u', $line, $matches)) {
            $time = $matches[1];
            $text = $matches[2];
        }
        $items[] = [
            'time' => $time,
            'text' => $text,
        ];
    }
    return $items;
}

function service_requests_from_order(array $order): array
{
    $lines = parse_order_timeline_lines((string)($order['remark'] ?? ''));
    $requests = [];
    foreach ($lines as $index => $line) {
        if (!preg_match('/客户服务请求-([^：:]+)[：:](.*)$/u', (string)$line['text'], $matches)) {
            continue;
        }
        $type = trim($matches[1]);
        $message = trim($matches[2]);
        $handled = false;
        for ($i = $index + 1; $i < count($lines); $i++) {
            $text = (string)$lines[$i]['text'];
            if (strpos($text, '客服回复服务请求-' . $type) !== false || strpos($text, '客服已处理服务请求-' . $type) !== false) {
                $handled = true;
                break;
            }
        }
        $requests[] = [
            'id' => (int)($order['id'] ?? 0) . '-' . $index,
            'order_id' => (int)($order['id'] ?? 0),
            'order_no' => $order['order_no'] ?? '',
            'customer_name' => $order['customer_name'] ?? '',
            'phone' => $order['phone'] ?? '',
            'type' => $type,
            'message' => $message,
            'status' => $handled ? 'handled' : 'pending',
            'time' => $line['time'] ?? '',
            'payment_status' => $order['payment_status'] ?? 'pending',
            'fulfillment_status' => $order['fulfillment_status'] ?? 'new',
        ];
    }
    return $requests;
}

function list_order_service_requests(PDO $pdo): array
{
    $status = trim((string)($_GET['status'] ?? ''));
    $type = trim((string)($_GET['type'] ?? ''));
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    $stmt = $pdo->query("SELECT * FROM orders WHERE remark LIKE '%客户服务请求-%' ORDER BY id DESC LIMIT 500");
    $items = [];
    while ($order = $stmt->fetch()) {
        foreach (service_requests_from_order($order) as $request) {
            if ($status !== '' && $request['status'] !== $status) {
                continue;
            }
            if ($type !== '' && $request['type'] !== $type) {
                continue;
            }
            if ($keyword !== '') {
                $haystack = implode(' ', [
                    $request['order_no'],
                    $request['customer_name'],
                    $request['phone'],
                    $request['type'],
                    $request['message'],
                ]);
                if (mb_stripos($haystack, $keyword, 0, 'UTF-8') === false) {
                    continue;
                }
            }
            $items[] = $request;
        }
    }
    usort($items, static fn($a, $b) => strcmp((string)($b['time'] ?? ''), (string)($a['time'] ?? '')));
    return [
        'items' => array_values($items),
        'total' => count($items),
        'pending' => count(array_filter($items, static fn($item) => $item['status'] === 'pending')),
        'handled' => count(array_filter($items, static fn($item) => $item['status'] === 'handled')),
    ];
}

function resolve_order_service_requests(PDO $pdo): array
{
    $data = body_json();
    $ids = $data['ids'] ?? [];
    if (!is_array($ids) || !$ids) {
        fail('请选择需要处理的服务请求', 'VALIDATION_ERROR', 422);
    }
    $handled = 0;
    foreach ($ids as $requestId) {
        $parts = explode('-', (string)$requestId, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $orderId = (int)$parts[0];
        $order = fetch_one($pdo, 'orders', $orderId);
        if (!$order) {
            continue;
        }
        $requests = service_requests_from_order($order);
        $target = null;
        foreach ($requests as $request) {
            if ((string)$request['id'] === (string)$requestId && $request['status'] === 'pending') {
                $target = $request;
                break;
            }
        }
        if (!$target) {
            continue;
        }
        $note = '客服已处理服务请求-' . $target['type'] . '：服务中心批量处理';
        $remark = append_order_note((string)($order['remark'] ?? ''), $note);
        $stmt = $pdo->prepare('UPDATE orders SET remark = :remark, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'id' => $orderId,
            'remark' => $remark,
            'updated_at' => now(),
        ]);
        $handled++;
    }
    return [
        'handled' => $handled,
    ];
}

function list_order_service_requests_scoped(PDO $pdo, ?PDO $main = null): array
{
    $status = trim((string)($_GET['status'] ?? ''));
    $type = trim((string)($_GET['type'] ?? ''));
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    $clauses = ["remark IS NOT NULL", "remark <> ''"];
    $params = [];
    append_site_scope_clause($clauses, $params);
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE ' . implode(' AND ', $clauses) . ' ORDER BY id DESC LIMIT 1000');
    $stmt->execute($params);
    $items = [];
    while ($order = $stmt->fetch()) {
        foreach (service_requests_from_order($order) as $request) {
            if ($status !== '' && $request['status'] !== $status) {
                continue;
            }
            if ($type !== '' && $request['type'] !== $type) {
                continue;
            }
            if ($keyword !== '') {
                $haystack = implode(' ', [
                    $request['order_no'],
                    $request['customer_name'],
                    $request['phone'],
                    $request['type'],
                    $request['message'],
                ]);
                if (mb_stripos($haystack, $keyword, 0, 'UTF-8') === false) {
                    continue;
                }
            }
            $request['site_id'] = (int)($order['site_id'] ?? 10001);
            $items[] = $request;
        }
    }
    usort($items, static fn($a, $b) => strcmp((string)($b['time'] ?? ''), (string)($a['time'] ?? '')));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $total = count($items);
    $pagedItems = array_slice($items, ($page - 1) * $pageSize, $pageSize);
    return [
        'items' => attach_site_names(array_values($pagedItems), $main),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
        'total' => $total,
        'pending' => count(array_filter($items, static fn($item) => $item['status'] === 'pending')),
        'handled' => count(array_filter($items, static fn($item) => $item['status'] === 'handled')),
    ];
}

function list_support_tickets(PDO $pdo, ?PDO $main = null): array
{
    $source = trim((string)($_GET['source'] ?? 'all')) ?: 'all';
    $status = trim((string)($_GET['status'] ?? ''));
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    $items = [];

    if ($source === 'all' || $source === 'order') {
        $originalGet = $_GET;
        $_GET['status'] = $status === 'new' ? 'pending' : $status;
        $_GET['keyword'] = $keyword;
        $_GET['page'] = 1;
        $_GET['page_size'] = 1000;
        $orders = list_order_service_requests_scoped($pdo, $main);
        $_GET = $originalGet;
        foreach ($orders['items'] ?? [] as $item) {
            $items[] = [
                'id' => 'order-' . (string)($item['id'] ?? ''),
                'source' => 'order',
                'source_label' => '订单服务',
                'site_id' => (int)($item['site_id'] ?? 10001),
                'site_name' => $item['site_name'] ?? '',
                'title' => ($item['type'] ?? '服务请求') . ' / ' . ($item['order_no'] ?? ''),
                'customer_name' => $item['customer_name'] ?? '',
                'phone' => $item['phone'] ?? '',
                'message' => $item['message'] ?? '',
                'status' => ($item['status'] ?? '') === 'handled' ? 'handled' : 'new',
                'time' => $item['time'] ?? '',
                'order_id' => (int)($item['order_id'] ?? 0),
                'order_no' => $item['order_no'] ?? '',
                'raw_id' => $item['id'] ?? '',
            ];
        }
    }

    if ($source === 'all' || $source === 'form') {
        $clauses = [];
        $params = [];
        append_site_scope_clause($clauses, $params);
        if ($status !== '') {
            $formStatus = $status === 'handled' ? 'handled' : ($status === 'pending' ? 'new' : $status);
            $clauses[] = 'status = :form_status';
            $params['form_status'] = $formStatus;
        }
        if ($keyword !== '') {
            $clauses[] = '(form_key LIKE :form_keyword OR source_url LIKE :form_keyword OR data LIKE :form_keyword OR remark LIKE :form_keyword)';
            $params['form_keyword'] = '%' . $keyword . '%';
        }
        $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
        $stmt = $pdo->prepare("SELECT * FROM form_submissions{$whereSql} ORDER BY id DESC LIMIT 1000");
        $stmt->execute($params);
        foreach (attach_site_names($stmt->fetchAll(), $main) as $row) {
            $data = json_decode((string)($row['data'] ?? ''), true);
            $data = is_array($data) ? $data : [];
            $items[] = [
                'id' => 'form-' . (int)($row['id'] ?? 0),
                'source' => 'form',
                'source_label' => '表单留言',
                'site_id' => (int)($row['site_id'] ?? 10001),
                'site_name' => $row['site_name'] ?? '',
                'title' => (string)($row['form_key'] ?? 'contact'),
                'customer_name' => (string)($data['name'] ?? $data['customer_name'] ?? ''),
                'phone' => (string)($data['phone'] ?? $data['mobile'] ?? ''),
                'message' => (string)($data['message'] ?? $data['需求'] ?? $data['remark'] ?? $row['remark'] ?? ''),
                'status' => in_array((string)($row['status'] ?? ''), ['handled', 'done', 'closed'], true) ? 'handled' : 'new',
                'time' => $row['created_at'] ?? '',
                'form_id' => (int)($row['id'] ?? 0),
                'source_url' => $row['source_url'] ?? '',
                'raw_id' => (int)($row['id'] ?? 0),
            ];
        }
    }

    usort($items, static fn($a, $b) => strcmp((string)($b['time'] ?? ''), (string)($a['time'] ?? '')));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $total = count($items);
    $pagedItems = array_slice($items, ($page - 1) * $pageSize, $pageSize);
    return [
        'items' => array_values($pagedItems),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
        'pending' => count(array_filter($items, static fn($item) => ($item['status'] ?? '') === 'new')),
        'handled' => count(array_filter($items, static fn($item) => ($item['status'] ?? '') === 'handled')),
    ];
}

function content_distribution_filter_clause(string $type, string $table): array
{
    $siteId = requested_site_filter();
    if ($siteId === null) {
        return ['', []];
    }
    $safeType = str_replace("'", "''", $type);
    $siteId = (int)$siteId;
    return [
        "EXISTS (SELECT 1 FROM content_site_relations cd WHERE cd.content_type = '{$safeType}' AND cd.content_id = {$table}.id AND cd.site_id = {$siteId})",
        [],
    ];
}

function record_site_visit(PDO $pdo): array
{
    $data = body_json();
    $siteId = resolve_request_site_id($data);
    $path = trim((string)($data['path'] ?? ''));
    $title = trim((string)($data['title'] ?? ''));
    $referrer = trim((string)($data['referrer'] ?? ''));
    $sessionId = trim((string)($data['session_id'] ?? ''));
    if ($path === '') {
        $path = '/';
    }
    assert_public_rate_limit($pdo, $siteId, 'analytics.visit', [$path, $sessionId], 120, 60);
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 80);
    $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $visitorSeed = $sessionId !== '' ? $sessionId : $ip . '|' . $userAgent;
    $visitorKey = hash('sha256', $visitorSeed);
    $stmt = $pdo->prepare("INSERT INTO site_visits (site_id, visitor_key, session_id, path, title, referrer, ip_address, user_agent, created_at)
        VALUES (:site_id, :visitor_key, :session_id, :path, :title, :referrer, :ip_address, :user_agent, :created_at)");
    $stmt->execute([
        'site_id' => $siteId,
        'visitor_key' => $visitorKey,
        'session_id' => $sessionId,
        'path' => substr($path, 0, 255),
        'title' => substr($title, 0, 120),
        'referrer' => substr($referrer, 0, 255),
        'ip_address' => $ip,
        'user_agent' => $userAgent,
        'created_at' => now(),
    ]);
    ok(['id' => (int)$pdo->lastInsertId()], '访问已记录');
}

function dashboard_metrics(PDO $pdo): array
{
    $today = date('Y-m-d');
    $scopeParams = [];
    $scopeClauses = [];
    append_site_scope_clause($scopeClauses, $scopeParams);
    $siteClause = $scopeClauses ? ' AND ' . implode(' AND ', $scopeClauses) : '';
    $visitParams = ['today' => $today . ' 00:00:00'];
    $visitParams = array_merge($visitParams, $scopeParams);
    $visitStmt = $pdo->prepare("SELECT COUNT(*) AS views, COUNT(DISTINCT visitor_key) AS visitors FROM site_visits WHERE created_at >= :today{$siteClause}");
    $visitStmt->execute($visitParams);
    $visitStats = $visitStmt->fetch() ?: [];
    $views = (int)($visitStats['views'] ?? 0);
    $visitors = (int)($visitStats['visitors'] ?? 0);

    $orderStmt = $pdo->prepare("SELECT
        COALESCE(SUM(CASE WHEN payment_status = 'paid' AND (paid_at >= :today OR (paid_at IS NULL AND updated_at >= :today)) THEN total_amount ELSE 0 END), 0) AS today_paid_amount,
        SUM(payment_status = 'pending') AS pending_payment_orders,
        SUM(fulfillment_status IN ('new', 'confirmed')) AS pending_orders,
        SUM(payment_status = 'paid' AND fulfillment_status IN ('new', 'confirmed')) AS pending_fulfillment_orders
        FROM orders" . ($scopeClauses ? ' WHERE ' . implode(' AND ', $scopeClauses) : ''));
    $orderParams = ['today' => $today . ' 00:00:00'];
    $orderParams = array_merge($orderParams, $scopeParams);
    $orderStmt->execute($orderParams);
    $orderStats = $orderStmt->fetch() ?: [];

    return [
        'today_visitors' => $visitors,
        'today_views' => $views,
        'visit_depth' => $visitors > 0 ? round($views / $visitors, 2) : 0,
        'today_paid_amount' => number_format((float)($orderStats['today_paid_amount'] ?? 0), 2, '.', ''),
        'pending_orders' => (int)($orderStats['pending_orders'] ?? 0),
        'pending_payment_orders' => (int)($orderStats['pending_payment_orders'] ?? 0),
        'pending_fulfillment_orders' => (int)($orderStats['pending_fulfillment_orders'] ?? 0),
        'currency' => 'CNY',
    ];
}

function dashboard_todos(PDO $pdo, PDO $main): array
{
    $sites = filter_sites_for_user($main, center_site_items($main, $pdo), current_user($pdo));
    $items = [];
    $summary = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'total' => 0];
    $now = time();
    $add = static function (array $todo) use (&$items, &$summary): void {
        $priority = (string)($todo['priority'] ?? 'medium');
        if (!isset($summary[$priority])) {
            $priority = 'medium';
        }
        $todo['priority'] = $priority;
        $todo['id'] = ($todo['type'] ?? 'todo') . '-' . ($todo['site_id'] ?? 0) . '-' . count($items);
        $items[] = $todo;
        $summary[$priority]++;
        $summary['total']++;
    };

    foreach ($sites as $site) {
        $siteId = (int)($site['id'] ?? 0);
        if ($siteId <= 0) {
            continue;
        }
        $settings = site_settings($pdo, $siteId);
        $siteName = (string)($site['name'] ?? ($settings['name'] ?? ('站点 ' . $siteId)));
        $domain = trim((string)($site['domain'] ?? $settings['domain'] ?? ''));
        $subdomain = trim((string)($site['subdomain'] ?? ''));
        $stats = is_array($site['stats'] ?? null) ? $site['stats'] : [];
        $publish = is_array($site['publish'] ?? null) ? $site['publish'] : [];
        $deploy = is_array($site['deploy'] ?? null) ? $site['deploy'] : [];
        $primaryDomain = primary_site_domain($main, $siteId);

        $base = [
            'site_id' => $siteId,
            'site_name' => $siteName,
            'site_key' => (string)($site['site_key'] ?? ''),
        ];
        if (($site['status'] ?? 'active') !== 'active') {
            $add($base + [
                'type' => 'site_status',
                'priority' => 'high',
                'title' => '站点未启用',
                'description' => '站点当前状态为 ' . (string)($site['status'] ?? '-') . '，前台发布和运营可能受影响。',
                'action_view' => 'sites',
                'action_label' => '查看站点',
            ]);
        }
        if ($domain === '' && $subdomain === '') {
            $add($base + [
                'type' => 'domain_missing',
                'priority' => 'high',
                'title' => '未绑定访问域名',
                'description' => '建议先绑定主域名或平台二级域名，便于预览、生成 sitemap 和上线。',
                'action_view' => 'domains',
                'action_label' => '绑定域名',
            ]);
        } elseif ($primaryDomain) {
            $dns = (string)($primaryDomain['dns_status'] ?? 'pending');
            $ssl = (string)($primaryDomain['ssl_status'] ?? 'pending');
            if ($dns !== 'valid' || !in_array($ssl, ['ready', 'pending'], true)) {
                $add($base + [
                    'type' => 'domain_check',
                    'priority' => $dns === 'failed' ? 'high' : 'medium',
                    'title' => '域名解析或 HTTPS 待确认',
                    'description' => '当前 DNS：' . $dns . '，HTTPS：' . $ssl . '。上线前建议完成检查。',
                    'action_view' => 'domains',
                    'action_label' => '检查域名',
                ]);
            }
        }
        if (empty($publish['generated'])) {
            $add($base + [
                'type' => 'publish_missing',
                'priority' => 'high',
                'title' => '尚未生成静态站',
                'description' => '站点内容还没有生成到前台 public 目录，客户无法验收完整页面。',
                'action_view' => 'publish',
                'action_label' => '生成静态站',
            ]);
        } elseif (!empty($publish['last_created_at'])) {
            $age = $now - strtotime((string)$publish['last_created_at']);
            if ($age > 7 * 86400) {
                $add($base + [
                    'type' => 'publish_stale',
                    'priority' => 'low',
                    'title' => '静态站超过 7 天未重新生成',
                    'description' => '如果最近修改过内容、模板或导航，建议重新生成一次静态站。',
                    'action_view' => 'publish',
                    'action_label' => '查看发布',
                ]);
            }
        }
        $mode = (string)($deploy['mode'] ?? 'manual');
        $sitePath = trim((string)($deploy['site_path'] ?? ''));
        $panelUrl = trim((string)($deploy['bt_panel_url'] ?? ''));
        if ($sitePath === '' || ($mode === 'bt-api' && $panelUrl === '')) {
            $add($base + [
                'type' => 'deploy_missing',
                'priority' => 'medium',
                'title' => '部署参数未完善',
                'description' => '当前部署模式为 ' . ($mode ?: 'manual') . '，请补齐站点目录' . ($mode === 'bt-api' ? '和宝塔面板地址。' : '。'),
                'action_view' => 'publish',
                'action_label' => '配置部署',
            ]);
        }
        if ((int)($stats['pending_orders'] ?? 0) > 0) {
            $add($base + [
                'type' => 'pending_orders',
                'priority' => 'critical',
                'title' => '有待处理订单',
                'description' => '当前站点有 ' . (int)$stats['pending_orders'] . ' 个订单需要支付确认或履约处理。',
                'action_view' => 'orders',
                'action_label' => '处理订单',
                'count' => (int)$stats['pending_orders'],
            ]);
        }
        if ((int)($stats['pending_forms'] ?? 0) > 0) {
            $add($base + [
                'type' => 'pending_forms',
                'priority' => 'high',
                'title' => '有待跟进询盘',
                'description' => '当前站点有 ' . (int)$stats['pending_forms'] . ' 条留言线索等待处理。',
                'action_view' => 'forms',
                'action_label' => '处理询盘',
                'count' => (int)$stats['pending_forms'],
            ]);
        }
        if ((int)($stats['articles'] ?? 0) === 0) {
            $add($base + [
                'type' => 'article_empty',
                'priority' => 'medium',
                'title' => '文章内容为空',
                'description' => '建议用 AI 或采集中心先生成一批 SEO 文章，提高搜索收录入口。',
                'action_view' => 'ai',
                'action_label' => '生成文章',
            ]);
        }
        if ((int)($stats['products'] ?? 0) === 0) {
            $add($base + [
                'type' => 'product_empty',
                'priority' => 'medium',
                'title' => '商品库为空',
                'description' => '独立站商城需要至少配置一批商品，前台产品列表和详情页才完整。',
                'action_view' => 'products',
                'action_label' => '添加商品',
            ]);
        }
        $seoDescription = trim((string)($settings['seo_description'] ?? $settings['description'] ?? ''));
        $keywords = trim((string)($settings['keywords'] ?? ''));
        if ($seoDescription === '' || $keywords === '') {
            $add($base + [
                'type' => 'seo_missing',
                'priority' => 'low',
                'title' => 'SEO 基础信息不完整',
                'description' => '建议补齐网站描述和关键词，生成 sitemap/search.json 后更利于搜索引擎理解站点。',
                'action_view' => 'settings',
                'action_label' => '完善 SEO',
            ]);
        }
    }

    $priorityRank = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
    usort($items, static function (array $a, array $b) use ($priorityRank): int {
        $rankA = $priorityRank[$a['priority']] ?? 9;
        $rankB = $priorityRank[$b['priority']] ?? 9;
        if ($rankA !== $rankB) {
            return $rankA <=> $rankB;
        }
        return ((int)($b['count'] ?? 0)) <=> ((int)($a['count'] ?? 0));
    });

    return [
        'summary' => $summary,
        'items' => array_slice($items, 0, 100),
    ];
}

function ensure_center_tables(PDO $main): void
{
    $main->exec("CREATE TABLE IF NOT EXISTS customers (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(50),
        email VARCHAR(120),
        company VARCHAR(150),
        plan_key VARCHAR(60) NOT NULL DEFAULT 'starter',
        max_sites INT UNSIGNED NOT NULL DEFAULT 10,
        ai_quota INT UNSIGNED NOT NULL DEFAULT 1000,
        ai_used INT UNSIGNED NOT NULL DEFAULT 0,
        storage_quota_mb INT UNSIGNED NOT NULL DEFAULT 1024,
        expires_at DATE,
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensure_column($main, 'customers', 'plan_key', "VARCHAR(60) NOT NULL DEFAULT 'starter'");
    ensure_column($main, 'customers', 'max_sites', 'INT UNSIGNED NOT NULL DEFAULT 10');
    ensure_column($main, 'customers', 'ai_quota', 'INT UNSIGNED NOT NULL DEFAULT 1000');
    ensure_column($main, 'customers', 'ai_used', 'INT UNSIGNED NOT NULL DEFAULT 0');
    ensure_column($main, 'customers', 'storage_quota_mb', 'INT UNSIGNED NOT NULL DEFAULT 1024');
    ensure_column($main, 'customers', 'expires_at', 'DATE');
    $main->exec("CREATE TABLE IF NOT EXISTS sites (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        customer_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(120) NOT NULL,
        site_key VARCHAR(80) NOT NULL UNIQUE,
        domain VARCHAR(180),
        subdomain VARCHAR(180),
        language VARCHAR(20) DEFAULT 'zh-CN',
        template_key VARCHAR(100),
        database_name VARCHAR(120),
        public_path VARCHAR(255),
        deploy_config_json TEXT,
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_customer_id (customer_id),
        INDEX idx_domain (domain),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensure_column($main, 'sites', 'deploy_config_json', 'TEXT');
    ensure_column($main, 'sites', 'deploy_node_id', 'BIGINT UNSIGNED');
    $main->exec("CREATE TABLE IF NOT EXISTS deploy_nodes (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(120) NOT NULL,
        node_type VARCHAR(40) NOT NULL DEFAULT 'bt-panel',
        server_ip VARCHAR(80),
        panel_url VARCHAR(255),
        api_key TEXT,
        root_path VARCHAR(255),
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        last_checked_at DATETIME,
        last_result VARCHAR(255),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_node_type (node_type),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $main->exec("CREATE TABLE IF NOT EXISTS ai_providers (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(120) NOT NULL,
        provider VARCHAR(60) NOT NULL DEFAULT 'openai-compatible',
        base_url VARCHAR(255),
        api_key TEXT,
        text_model VARCHAR(120),
        image_model VARCHAR(120),
        video_model VARCHAR(120),
        status VARCHAR(30) NOT NULL DEFAULT 'enabled',
        is_default TINYINT(1) NOT NULL DEFAULT 0,
        last_checked_at DATETIME,
        last_result VARCHAR(255),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_provider (provider),
        INDEX idx_status (status),
        INDEX idx_default (is_default)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensure_column($main, 'ai_providers', 'is_default', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($main, 'ai_providers', 'last_checked_at', 'DATETIME');
    ensure_column($main, 'ai_providers', 'last_result', 'VARCHAR(255)');
    $main->exec("CREATE TABLE IF NOT EXISTS payment_channels (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(120) NOT NULL,
        provider VARCHAR(40) NOT NULL DEFAULT 'manual',
        currency VARCHAR(10) NOT NULL DEFAULT 'CNY',
        account VARCHAR(180),
        instructions TEXT,
        config_json TEXT,
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        is_default TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_provider (provider),
        INDEX idx_status (status),
        INDEX idx_default (is_default)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $main->exec("CREATE TABLE IF NOT EXISTS payment_channel_sites (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        channel_id BIGINT UNSIGNED NOT NULL,
        site_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uk_channel_site (channel_id, site_id),
        INDEX idx_site_id (site_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $main->exec("CREATE TABLE IF NOT EXISTS site_domains (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        site_id BIGINT UNSIGNED NOT NULL,
        domain VARCHAR(180) NOT NULL,
        domain_type VARCHAR(30) NOT NULL DEFAULT 'primary',
        is_primary TINYINT(1) NOT NULL DEFAULT 0,
        dns_status VARCHAR(30) NOT NULL DEFAULT 'pending',
        ssl_status VARCHAR(30) NOT NULL DEFAULT 'pending',
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        last_checked_at DATETIME,
        last_result VARCHAR(255),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uk_site_domain (site_id, domain),
        INDEX idx_domain (domain),
        INDEX idx_site_id (site_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $main->exec("CREATE TABLE IF NOT EXISTS domain_applications (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        customer_id BIGINT UNSIGNED NOT NULL,
        site_id BIGINT UNSIGNED NOT NULL,
        domain VARCHAR(180) NOT NULL,
        years INT UNSIGNED NOT NULL DEFAULT 1,
        usage_type VARCHAR(40) NOT NULL DEFAULT 'primary',
        contact_name VARCHAR(100),
        contact_phone VARCHAR(50),
        contact_email VARCHAR(120),
        status VARCHAR(30) NOT NULL DEFAULT 'submitted',
        applicant_note TEXT,
        admin_note TEXT,
        processed_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_customer_id (customer_id),
        INDEX idx_site_id (site_id),
        INDEX idx_domain (domain),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $main->exec("CREATE TABLE IF NOT EXISTS batch_tasks (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        task_no VARCHAR(80) NOT NULL UNIQUE,
        action VARCHAR(50) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'success',
        total_count INT UNSIGNED NOT NULL DEFAULT 0,
        success_count INT UNSIGNED NOT NULL DEFAULT 0,
        failed_count INT UNSIGNED NOT NULL DEFAULT 0,
        site_ids TEXT,
        summary TEXT,
        started_at DATETIME,
        finished_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_action (action),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $main->exec("CREATE TABLE IF NOT EXISTS deploy_tasks (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        task_no VARCHAR(80) NOT NULL UNIQUE,
        site_id BIGINT UNSIGNED NOT NULL,
        site_key VARCHAR(80),
        site_name VARCHAR(120),
        action VARCHAR(50) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        deploy_mode VARCHAR(40),
        deploy_node_id BIGINT UNSIGNED,
        version_no VARCHAR(120),
        package_path VARCHAR(255),
        target_path VARCHAR(255),
        message VARCHAR(500),
        summary TEXT,
        started_at DATETIME,
        finished_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_site_id (site_id),
        INDEX idx_action (action),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $main->exec("CREATE TABLE IF NOT EXISTS template_clone_tasks (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        task_no VARCHAR(80) NOT NULL UNIQUE,
        target_url VARCHAR(500) NOT NULL,
        template_key VARCHAR(120),
        template_name VARCHAR(160),
        source_title VARCHAR(255),
        status VARCHAR(30) NOT NULL DEFAULT 'success',
        module_plan_json TEXT,
        source_excerpt MEDIUMTEXT,
        message VARCHAR(500),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_status (status),
        INDEX idx_template_key (template_key),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $main->exec("CREATE TABLE IF NOT EXISTS operation_logs (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        user_id BIGINT UNSIGNED,
        username VARCHAR(80),
        site_id BIGINT UNSIGNED,
        method VARCHAR(10) NOT NULL,
        path VARCHAR(255) NOT NULL,
        action VARCHAR(80),
        target_type VARCHAR(80),
        target_id VARCHAR(80),
        message VARCHAR(255),
        summary TEXT,
        ip_address VARCHAR(64),
        user_agent VARCHAR(255),
        created_at DATETIME NOT NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_site_id (site_id),
        INDEX idx_method (method),
        INDEX idx_path (path(120)),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $now = now();
    $main->exec("INSERT INTO customers (id, name, phone, email, company, status, created_at, updated_at)
        VALUES (1, '默认客户', '', '', '', 'active', '{$now}', '{$now}')
        ON DUPLICATE KEY UPDATE updated_at=VALUES(updated_at)");
}

function site_table_count(PDO $pdo, string $table, string $where = ''): int
{
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM {$table}{$where}")->fetchColumn();
    } catch (Throwable $error) {
        return 0;
    }
}

function center_site_items(PDO $main, PDO $sitePdo): array
{
    ensure_center_tables($main);
    ensure_content_distribution_table($sitePdo);
    $settings = site_settings($sitePdo, 10001);
    $now = now();
    $stmt = $main->prepare("INSERT INTO sites (id, customer_id, name, site_key, domain, subdomain, language, template_key, database_name, public_path, status, created_at, updated_at)
        VALUES (10001, 1, :name, 'site_10001', :domain, 'site10001.huajian.local', :language, :template_key, :database_name, 'sites/site_10001/public', 'active', :created_at, :updated_at)
        ON DUPLICATE KEY UPDATE name=VALUES(name), domain=VALUES(domain), language=VALUES(language), template_key=VALUES(template_key), updated_at=VALUES(updated_at)");
    $stmt->execute([
        'name' => (string)($settings['name'] ?? '默认站点'),
        'domain' => (string)($settings['domain'] ?? ''),
        'language' => (string)($settings['language'] ?? 'zh-CN'),
        'template_key' => (string)($settings['template_key'] ?? 'business-clean'),
        'database_name' => env_value('HJ_DB_SITE', 'huajian_site_10001'),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $items = $main->query('SELECT * FROM sites ORDER BY id ASC')->fetchAll();
    $articleCounts = distribution_site_counts($sitePdo, 'article');
    $productCounts = distribution_site_counts($sitePdo, 'product');
    $orderCounts = site_group_counts($sitePdo, 'orders');
    $pendingOrderCounts = site_group_counts($sitePdo, 'orders', "payment_status = 'pending' OR fulfillment_status IN ('new', 'confirmed')");
    $formCounts = site_group_counts($sitePdo, 'form_submissions');
    $pendingFormCounts = site_group_counts($sitePdo, 'form_submissions', "status IN ('new', 'pending')");
    $publishMap = latest_publish_by_site($sitePdo);

    return array_map(function (array $item) use ($articleCounts, $productCounts, $orderCounts, $pendingOrderCounts, $formCounts, $pendingFormCounts, $publishMap, $settings) {
        $siteId = (int)$item['id'];
        $stats = [
            'articles' => $articleCounts[$siteId] ?? 0,
            'products' => $productCounts[$siteId] ?? 0,
            'orders' => $orderCounts[$siteId] ?? 0,
            'pending_orders' => $pendingOrderCounts[$siteId] ?? 0,
            'forms' => $formCounts[$siteId] ?? 0,
            'pending_forms' => $pendingFormCounts[$siteId] ?? 0,
        ];
        return $item + [
            'stats' => $stats,
            'publish' => site_publish_summary($item, $publishMap[$siteId] ?? null),
            'deploy' => site_deploy_config($item, $siteId === 10001 ? $settings : []),
        ];
    }, $items);
}

function latest_publish_by_site(PDO $pdo): array
{
    try {
        ensure_publish_versions_site_column($pdo);
        $rows = $pdo->query('SELECT pv.* FROM publish_versions pv INNER JOIN (SELECT site_id, MAX(id) AS id FROM publish_versions GROUP BY site_id) latest ON latest.id = pv.id')->fetchAll();
        $items = [];
        foreach ($rows as $row) {
            $items[(int)($row['site_id'] ?? 10001)] = $row;
        }
        return $items;
    } catch (Throwable $error) {
        return [];
    }
}

function site_preview_url(array $site): string
{
    $siteId = (int)($site['id'] ?? 10001);
    $siteKey = (string)($site['site_key'] ?? 'site_10001');
    return $siteId === 10001 ? '/' : '/s/' . rawurlencode($siteKey) . '/';
}

function site_publish_summary(array $site, ?array $latest): array
{
    $summary = [];
    if ($latest && !empty($latest['summary'])) {
        $decoded = json_decode((string)$latest['summary'], true);
        if (is_array($decoded)) {
            $summary = $decoded;
        }
    }

    $publicPath = str_replace(DIRECTORY_SEPARATOR, '/', site_public_path($site));
    $publicRoot = site_public_root($site);
    return [
        'preview_url' => site_preview_url($site),
        'public_path' => $publicPath,
        'generated' => is_file($publicRoot . DIRECTORY_SEPARATOR . 'index.html'),
        'last_version' => (string)($latest['version_no'] ?? ''),
        'last_type' => (string)($latest['publish_type'] ?? ''),
        'last_status' => (string)($latest['status'] ?? ''),
        'last_created_at' => (string)($latest['created_at'] ?? ''),
        'file_count' => (int)($summary['file_count'] ?? 0),
        'file_size' => (int)($summary['file_size'] ?? 0),
        'package_path' => (string)($summary['package_path'] ?? ''),
    ];
}

function site_deploy_config(array $site, array $settings = []): array
{
    $deploy = [];
    if (!empty($site['deploy_config_json'])) {
        $decoded = json_decode((string)$site['deploy_config_json'], true);
        if (is_array($decoded)) {
            $deploy = $decoded;
        }
    }
    if (!empty($settings['deploy']) && is_array($settings['deploy'])) {
        $deploy = array_replace($deploy, $settings['deploy']);
    }

    return [
        'bt_panel_url' => (string)($deploy['bt_panel_url'] ?? ''),
        'site_path' => (string)($deploy['site_path'] ?? ''),
        'mode' => (string)($deploy['mode'] ?? 'manual'),
        'after_action' => (string)($deploy['after_action'] ?? ''),
        'note' => (string)($deploy['note'] ?? ''),
    ];
}

function normalize_site_deploy_config(array $data, array $current = []): array
{
    $deploy = is_array($data['deploy'] ?? null) ? $data['deploy'] : [];
    if (!$deploy && !empty($current['deploy_config_json'])) {
        $decoded = json_decode((string)$current['deploy_config_json'], true);
        $deploy = is_array($decoded) ? $decoded : [];
    }
    $mode = trim((string)($deploy['mode'] ?? 'manual'));
    if ($mode === 'bt_api') {
        $mode = 'bt-api';
    }
    if (!in_array($mode, ['manual', 'package', 'local-copy', 'bt-api', 'ftp'], true)) {
        $mode = 'manual';
    }

    return [
        'bt_panel_url' => mb_substr(trim((string)($deploy['bt_panel_url'] ?? '')), 0, 255, 'UTF-8'),
        'site_path' => mb_substr(trim((string)($deploy['site_path'] ?? '')), 0, 255, 'UTF-8'),
        'mode' => $mode,
        'after_action' => mb_substr(trim((string)($deploy['after_action'] ?? '')), 0, 120, 'UTF-8'),
        'note' => mb_substr(trim((string)($deploy['note'] ?? '')), 0, 500, 'UTF-8'),
    ];
}

function normalize_center_site_payload(array $data, array $current = []): array
{
    $name = trim((string)($data['name'] ?? ($current['name'] ?? '')));
    if ($name === '') {
        fail('站点名称不能为空', 'VALIDATION_ERROR', 422);
    }
    $status = trim((string)($data['status'] ?? ($current['status'] ?? 'active')));
    if (!in_array($status, ['active', 'disabled', 'archived'], true)) {
        fail('站点状态不正确', 'VALIDATION_ERROR', 422);
    }

    return [
        'name' => mb_substr($name, 0, 120, 'UTF-8'),
        'deploy_node_id' => max(0, (int)($data['deploy_node_id'] ?? ($current['deploy_node_id'] ?? 0))),
        'domain' => mb_substr(trim((string)($data['domain'] ?? ($current['domain'] ?? ''))), 0, 180, 'UTF-8'),
        'subdomain' => mb_substr(trim((string)($data['subdomain'] ?? ($current['subdomain'] ?? ''))), 0, 180, 'UTF-8'),
        'language' => mb_substr(trim((string)($data['language'] ?? ($current['language'] ?? 'zh-CN'))) ?: 'zh-CN', 0, 20, 'UTF-8'),
        'template_key' => mb_substr(trim((string)($data['template_key'] ?? ($current['template_key'] ?? 'business-clean'))) ?: 'business-clean', 0, 100, 'UTF-8'),
        'status' => $status,
        'deploy' => normalize_site_deploy_config($data, $current),
    ];
}

function update_center_site(PDO $main, int $id, array $data, ?PDO $sitePdo = null): array
{
    ensure_center_tables($main);
    $current = fetch_one($main, 'sites', $id);
    if (!$current) {
        fail('站点不存在', 'NOT_FOUND', 404);
    }
    $payload = normalize_center_site_payload($data, $current);
    $stmt = $main->prepare('UPDATE sites SET name = :name, deploy_node_id = :deploy_node_id, domain = :domain, subdomain = :subdomain, language = :language, template_key = :template_key, deploy_config_json = :deploy_config_json, status = :status, updated_at = :updated_at WHERE id = :id');
    $stmt->execute([
        'name' => $payload['name'],
        'deploy_node_id' => $payload['deploy_node_id'] ?: null,
        'domain' => $payload['domain'],
        'subdomain' => $payload['subdomain'],
        'language' => $payload['language'],
        'template_key' => $payload['template_key'],
        'deploy_config_json' => json_encode($payload['deploy'], JSON_UNESCAPED_UNICODE),
        'status' => $payload['status'],
        'id' => $id,
        'updated_at' => now(),
    ]);
    if ($sitePdo && $id === 10001) {
        $settings = site_settings($sitePdo, 10001);
        foreach (['name', 'domain', 'language', 'template_key'] as $field) {
            $settings[$field] = $payload[$field];
        }
        $settings['deploy'] = $payload['deploy'];
        $settings['updated_at'] = now();
        save_site_settings($sitePdo, $settings, 10001);
    }
    return fetch_one($main, 'sites', $id) ?: ($current + $payload);
}

function site_group_counts(PDO $pdo, string $table, string $where = ''): array
{
    try {
        $whereSql = $where !== '' ? " WHERE {$where}" : '';
        $rows = $pdo->query("SELECT site_id, COUNT(*) AS total FROM {$table}{$whereSql} GROUP BY site_id")->fetchAll();
        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)($row['site_id'] ?? 10001)] = (int)($row['total'] ?? 0);
        }
        return $counts;
    } catch (Throwable $error) {
        return [];
    }
}

function center_overview(array $sites): array
{
    $totals = ['sites' => count($sites), 'articles' => 0, 'products' => 0, 'orders' => 0, 'pending_orders' => 0, 'forms' => 0, 'pending_forms' => 0];
    foreach ($sites as $site) {
        foreach (['articles', 'products', 'orders', 'pending_orders', 'forms', 'pending_forms'] as $key) {
            $totals[$key] += (int)($site['stats'][$key] ?? 0);
        }
    }
    return $totals;
}

function filter_sites_for_user(PDO $main, array $items, ?array $user = null): array
{
    $allowed = allowed_site_ids_for_user($main, $user);
    if ($allowed === null) {
        return $items;
    }
    return array_values(array_filter($items, fn($item) => in_array((int)($item['id'] ?? 0), $allowed, true)));
}

function current_customer_id_for_create(?array $user = null, array $data = []): int
{
    $user = $user ?: auth_user();
    if ($user && !is_platform_admin($user)) {
        $customerId = (int)($user['customer_id'] ?? 0);
        if ($customerId <= 0) {
            fail('客户账号未绑定客户资料，无法创建站点', 'CUSTOMER_NOT_BOUND', 403);
        }
        return $customerId;
    }
    return max(1, (int)($data['customer_id'] ?? 1));
}

function current_customer(PDO $main, ?array $user = null, ?int $customerId = null): ?array
{
    ensure_center_tables($main);
    if ($customerId === null) {
        $user = $user ?: auth_user();
        $customerId = is_platform_admin($user) ? 0 : (int)($user['customer_id'] ?? 0);
    }
    if ($customerId <= 0) {
        return null;
    }
    $customer = fetch_one($main, 'customers', $customerId);
    return $customer ?: null;
}

function customer_site_count(PDO $main, int $customerId): int
{
    ensure_center_tables($main);
    $stmt = $main->prepare("SELECT COUNT(*) FROM sites WHERE customer_id = ? AND status <> 'archived'");
    $stmt->execute([$customerId]);
    return (int)$stmt->fetchColumn();
}

function customer_storage_used_bytes(PDO $sitePdo, PDO $main, int $customerId): int
{
    $allowedSiteIds = allowed_site_ids_for_user($main, ['role' => 'customer_admin', 'customer_id' => $customerId]);
    if (!$allowedSiteIds) {
        return 0;
    }
    ensure_media_site_column($sitePdo);
    $placeholders = implode(',', array_fill(0, count($allowedSiteIds), '?'));
    $stmt = $sitePdo->prepare("SELECT COALESCE(SUM(file_size), 0) FROM media WHERE site_id IN ({$placeholders})");
    $stmt->execute($allowedSiteIds);
    return (int)$stmt->fetchColumn();
}

function customer_quota_summary(PDO $sitePdo, PDO $main, ?array $user = null): array
{
    $customer = current_customer($main, $user);
    if (!$customer) {
        return [
            'is_platform_admin' => true,
            'sites_used' => (int)$main->query("SELECT COUNT(*) FROM sites WHERE status <> 'archived'")->fetchColumn(),
            'sites_limit' => 0,
            'ai_used' => 0,
            'ai_quota' => 0,
            'storage_used_bytes' => 0,
            'storage_quota_mb' => 0,
            'storage_used_mb' => 0,
            'status' => 'unlimited',
            'expires_at' => null,
        ];
    }
    $storageBytes = customer_storage_used_bytes($sitePdo, $main, (int)$customer['id']);
    return [
        'is_platform_admin' => false,
        'customer_id' => (int)$customer['id'],
        'customer_name' => (string)$customer['name'],
        'plan_key' => (string)($customer['plan_key'] ?? 'starter'),
        'sites_used' => customer_site_count($main, (int)$customer['id']),
        'sites_limit' => (int)($customer['max_sites'] ?? 0),
        'ai_used' => (int)($customer['ai_used'] ?? 0),
        'ai_quota' => (int)($customer['ai_quota'] ?? 0),
        'storage_used_bytes' => $storageBytes,
        'storage_used_mb' => round($storageBytes / 1024 / 1024, 2),
        'storage_quota_mb' => (int)($customer['storage_quota_mb'] ?? 0),
        'status' => (string)($customer['status'] ?? 'active'),
        'expires_at' => $customer['expires_at'] ?? null,
    ];
}

function assert_customer_plan_active(array $customer): void
{
    if (($customer['status'] ?? '') !== 'active') {
        fail('客户套餐未启用，请联系平台管理员', 'CUSTOMER_PLAN_INACTIVE', 403);
    }
    $expiresAt = trim((string)($customer['expires_at'] ?? ''));
    if ($expiresAt !== '' && $expiresAt < date('Y-m-d')) {
        fail('客户套餐已过期，请联系平台管理员', 'CUSTOMER_PLAN_EXPIRED', 403);
    }
}

function assert_site_quota(PDO $main, int $customerId): void
{
    $customer = current_customer($main, null, $customerId);
    if (!$customer) {
        fail('客户不存在', 'CUSTOMER_NOT_FOUND', 404);
    }
    assert_customer_plan_active($customer);
    $limit = (int)($customer['max_sites'] ?? 0);
    if ($limit > 0 && customer_site_count($main, $customerId) >= $limit) {
        fail('站点数量已达到套餐上限，请升级套餐或停用旧站点', 'SITE_QUOTA_EXCEEDED', 403, [
            'sites_used' => customer_site_count($main, $customerId),
            'sites_limit' => $limit,
        ]);
    }
}

function consume_ai_quota(PDO $main, ?array $user, int $units = 1): void
{
    if (!$user || is_platform_admin($user)) {
        return;
    }
    $customer = current_customer($main, $user);
    if (!$customer) {
        fail('客户账号未绑定客户资料，无法使用 AI', 'CUSTOMER_NOT_BOUND', 403);
    }
    assert_customer_plan_active($customer);
    $quota = (int)($customer['ai_quota'] ?? 0);
    $used = (int)($customer['ai_used'] ?? 0);
    if ($quota > 0 && $used + $units > $quota) {
        fail('AI 额度不足，请联系平台管理员增加额度', 'AI_QUOTA_EXCEEDED', 403, [
            'ai_used' => $used,
            'ai_quota' => $quota,
            'required' => $units,
        ]);
    }
    $stmt = $main->prepare('UPDATE customers SET ai_used = ai_used + :units, updated_at = :updated_at WHERE id = :id');
    $stmt->execute(['units' => $units, 'updated_at' => now(), 'id' => (int)$customer['id']]);
}

function assert_storage_quota(PDO $sitePdo, PDO $main, ?array $user, int $incomingBytes): void
{
    if (!$user || is_platform_admin($user)) {
        return;
    }
    $customer = current_customer($main, $user);
    if (!$customer) {
        fail('客户账号未绑定客户资料，无法上传文件', 'CUSTOMER_NOT_BOUND', 403);
    }
    assert_customer_plan_active($customer);
    $quotaMb = (int)($customer['storage_quota_mb'] ?? 0);
    if ($quotaMb <= 0) {
        return;
    }
    $used = customer_storage_used_bytes($sitePdo, $main, (int)$customer['id']);
    $limit = $quotaMb * 1024 * 1024;
    if ($used + $incomingBytes > $limit) {
        fail('媒体库容量已达到套餐上限，请删除旧文件或升级套餐', 'STORAGE_QUOTA_EXCEEDED', 403, [
            'storage_used_mb' => round($used / 1024 / 1024, 2),
            'storage_quota_mb' => $quotaMb,
            'incoming_mb' => round($incomingBytes / 1024 / 1024, 2),
        ]);
    }
}

function list_batch_tasks(PDO $main): array
{
    ensure_center_tables($main);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    [$whereSql, $params] = batch_task_filter_sql();

    $countStmt = $main->prepare("SELECT COUNT(*) FROM batch_tasks{$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $stmt = $main->prepare("SELECT * FROM batch_tasks{$whereSql} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute($params);
    $items = array_map('normalize_batch_task_row', $stmt->fetchAll());

    return [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
        'overview' => batch_task_overview($main, $whereSql, $params),
    ];
}

function task_stream_status_tag(string $status): string
{
    return match ($status) {
        'success', 'confirmed', 'approved', 'converted', 'ready' => 'success',
        'failed', 'rejected', 'discarded', 'error' => 'failed',
        'partial' => 'partial',
        'running', 'pending', 'submitted', 'draft', 'rewritten' => 'pending',
        default => $status ?: 'pending',
    };
}

function task_stream_item(string $source, array $row, array $siteMap = []): array
{
    $siteIds = [];
    if (!empty($row['site_ids']) && is_string((string)$row['site_ids'])) {
        $decoded = json_decode((string)$row['site_ids'], true);
        if (is_array($decoded)) {
            $siteIds = array_values(array_filter(array_map('intval', $decoded), fn($id) => $id > 0));
        }
    }
    if (!$siteIds && !empty($row['site_id'])) {
        $siteIds = [(int)$row['site_id']];
    }
    $siteNames = [];
    foreach ($siteIds as $siteId) {
        $siteNames[] = $siteMap[$siteId] ?? ('Site ' . $siteId);
    }
    $status = (string)($row['status'] ?? 'pending');
    $createdAt = (string)($row['created_at'] ?? $row['started_at'] ?? $row['updated_at'] ?? '');
    $updatedAt = (string)($row['updated_at'] ?? $row['finished_at'] ?? $createdAt);
    $base = [
        'source' => $source,
        'source_label' => [
            'batch' => '批量任务',
            'deploy' => '部署任务',
            'ai' => 'AI 任务',
            'template' => '模板任务',
        ][$source] ?? $source,
        'id' => (int)($row['id'] ?? 0),
        'task_no' => (string)($row['task_no'] ?? ($source . '-' . (string)($row['id'] ?? ''))),
        'type' => (string)($row['action'] ?? $row['task_type'] ?? 'task'),
        'status' => $status,
        'status_group' => task_stream_status_tag($status),
        'site_ids' => $siteIds,
        'site_names' => $siteNames,
        'created_at' => $createdAt,
        'updated_at' => $updatedAt,
        'finished_at' => (string)($row['finished_at'] ?? ''),
        'message' => (string)($row['message'] ?? ''),
        'summary' => '',
        'raw' => $row,
    ];
    if ($source === 'batch') {
        $summary = json_decode((string)($row['summary'] ?? ''), true);
        $base['title'] = '批量' . (string)($row['action'] ?? '任务') . '：' . (int)($row['success_count'] ?? 0) . '/' . (int)($row['total_count'] ?? 0) . ' 成功';
        $base['summary'] = (string)($summary['message'] ?? '');
        $base['progress'] = [
            'total' => (int)($row['total_count'] ?? 0),
            'success' => (int)($row['success_count'] ?? 0),
            'failed' => (int)($row['failed_count'] ?? 0),
        ];
    } elseif ($source === 'deploy') {
        $base['title'] = '部署' . (string)($row['action'] ?? '任务') . '：' . (string)($row['site_name'] ?? ($siteNames[0] ?? '-'));
        $base['summary'] = (string)($row['message'] ?? $row['target_path'] ?? '');
        $base['progress'] = ['total' => 1, 'success' => $status === 'success' ? 1 : 0, 'failed' => $status === 'failed' ? 1 : 0];
    } elseif ($source === 'ai') {
        $base['title'] = (string)($row['prompt'] ?? 'AI 任务');
        $base['summary'] = (string)($row['message'] ?? '');
        $base['progress'] = ['total' => (int)($row['success_count'] ?? 0), 'success' => (int)($row['success_count'] ?? 0), 'failed' => $status === 'failed' ? 1 : 0];
    } else {
        $base['title'] = (string)($row['template_name'] ?? $row['source_title'] ?? $row['target_url'] ?? '模板任务');
        $base['summary'] = (string)($row['message'] ?? $row['target_url'] ?? '');
        $base['progress'] = ['total' => 1, 'success' => $status === 'success' ? 1 : 0, 'failed' => $status === 'failed' ? 1 : 0];
    }
    return $base;
}

function list_task_stream(PDO $sitePdo, PDO $main): array
{
    ensure_center_tables($main);
    ensure_ai_task_tables($sitePdo);
    $sourceFilter = trim((string)($_GET['source'] ?? ''));
    $statusFilter = trim((string)($_GET['status'] ?? ''));
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    $siteScope = requested_site_scope($main);
    $siteMap = site_name_map($main);
    $items = [];

    if ($sourceFilter === '' || $sourceFilter === 'batch') {
        [$whereSql, $params] = batch_task_filter_sql();
        $stmt = $main->prepare("SELECT * FROM batch_tasks{$whereSql} ORDER BY id DESC LIMIT 200");
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $row) {
            $items[] = task_stream_item('batch', $row, $siteMap);
        }
    }

    if ($sourceFilter === '' || $sourceFilter === 'deploy') {
        $clauses = [];
        $params = [];
        append_site_scope_clause($clauses, $params, 'site_id', 'stream_deploy_site');
        $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
        $stmt = $main->prepare("SELECT * FROM deploy_tasks{$whereSql} ORDER BY id DESC LIMIT 200");
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $row) {
            $items[] = task_stream_item('deploy', $row, $siteMap);
        }
    }

    if ($sourceFilter === '' || $sourceFilter === 'ai') {
        $stmt = $sitePdo->query('SELECT * FROM ai_tasks ORDER BY id DESC LIMIT 300');
        foreach ($stmt->fetchAll() as $row) {
            $item = task_stream_item('ai', $row, $siteMap);
            if ($siteScope !== null) {
                $matched = false;
                foreach ($item['site_ids'] as $siteId) {
                    if (in_array((int)$siteId, $siteScope, true)) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    continue;
                }
            }
            $items[] = $item;
        }
    }

    if (($sourceFilter === '' || $sourceFilter === 'template') && is_platform_admin(auth_user())) {
        $stmt = $main->query('SELECT * FROM template_clone_tasks ORDER BY id DESC LIMIT 100');
        foreach ($stmt->fetchAll() as $row) {
            $items[] = task_stream_item('template', $row, $siteMap);
        }
    }

    if ($statusFilter !== '') {
        $items = array_values(array_filter($items, static fn($item) => $item['status_group'] === $statusFilter || $item['status'] === $statusFilter));
    }
    if ($keyword !== '') {
        $items = array_values(array_filter($items, static function ($item) use ($keyword) {
            $haystack = implode(' ', [
                $item['task_no'] ?? '',
                $item['title'] ?? '',
                $item['summary'] ?? '',
                $item['message'] ?? '',
                implode(' ', $item['site_names'] ?? []),
            ]);
            return mb_stripos($haystack, $keyword, 0, 'UTF-8') !== false;
        }));
    }

    usort($items, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $total = count($items);
    $paged = array_slice($items, ($page - 1) * $pageSize, $pageSize);
    $overview = [
        'total' => $total,
        'success' => count(array_filter($items, static fn($item) => $item['status_group'] === 'success')),
        'failed' => count(array_filter($items, static fn($item) => $item['status_group'] === 'failed')),
        'pending' => count(array_filter($items, static fn($item) => $item['status_group'] === 'pending')),
        'partial' => count(array_filter($items, static fn($item) => $item['status_group'] === 'partial')),
        'ai' => count(array_filter($items, static fn($item) => $item['source'] === 'ai')),
        'deploy' => count(array_filter($items, static fn($item) => $item['source'] === 'deploy')),
        'batch' => count(array_filter($items, static fn($item) => $item['source'] === 'batch')),
        'template' => count(array_filter($items, static fn($item) => $item['source'] === 'template')),
    ];

    return [
        'items' => $paged,
        'overview' => $overview,
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function operation_log_action(string $method, string $path): array
{
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));
    $targetType = $segments[0] ?? 'system';
    if (($segments[0] ?? '') === 'platform') {
        $targetType = 'platform_' . ($segments[1] ?? 'system');
    } elseif (($segments[0] ?? '') === 'site' && isset($segments[1])) {
        $targetType = 'site_' . $segments[1];
    }
    $targetId = '';
    foreach (array_reverse($segments) as $segment) {
        if (ctype_digit($segment)) {
            $targetId = $segment;
            break;
        }
    }
    $verb = ['POST' => 'create_or_action', 'PUT' => 'update', 'DELETE' => 'delete'][$method] ?? strtolower($method);
    return [$verb, $targetType, $targetId];
}

function summarize_log_data($data): string
{
    $summary = $data;
    if (is_array($summary)) {
        foreach (['api_key', 'password', 'token', 'access_token', 'config'] as $key) {
            if (array_key_exists($key, $summary)) {
                $summary[$key] = '***';
            }
        }
    }
    $encoded = json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return text_limit((string)$encoded, 1200);
}

function auto_operation_log(string $message, $data = []): void
{
    static $recording = false;
    if ($recording) {
        return;
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        return;
    }
    if (empty($GLOBALS['AUTH_USER']) || !is_array($GLOBALS['AUTH_USER'])) {
        return;
    }
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = preg_replace('#^/api#', '', $requestPath);
    if (in_array($path, ['/auth/logout'], true)) {
        return;
    }
    try {
        $recording = true;
        $main = main_pdo();
        ensure_center_tables($main);
        [$action, $targetType, $targetId] = operation_log_action($method, $path);
        $user = $GLOBALS['AUTH_USER'];
        $stmt = $main->prepare("INSERT INTO operation_logs (user_id, username, site_id, method, path, action, target_type, target_id, message, summary, ip_address, user_agent, created_at)
            VALUES (:user_id, :username, :site_id, :method, :path, :action, :target_type, :target_id, :message, :summary, :ip_address, :user_agent, :created_at)");
        $stmt->execute([
            'user_id' => (int)($user['id'] ?? 0) ?: null,
            'username' => (string)($user['username'] ?? $user['name'] ?? ''),
            'site_id' => requested_site_id(),
            'method' => $method,
            'path' => $path,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'message' => text_limit($message, 255),
            'summary' => summarize_log_data($data),
            'ip_address' => client_ip(),
            'user_agent' => text_limit((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 255),
            'created_at' => now(),
        ]);
    } catch (Throwable $error) {
        // Logging must never break the business response.
    } finally {
        $recording = false;
    }
}

function list_operation_logs(PDO $main): array
{
    ensure_center_tables($main);
    $where = [];
    $params = [];
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    if ($keyword !== '') {
        $where[] = '(username LIKE :keyword OR path LIKE :keyword OR message LIKE :keyword OR target_type LIKE :keyword)';
        $params['keyword'] = '%' . $keyword . '%';
    }
    $method = trim((string)($_GET['method'] ?? ''));
    if ($method !== '') {
        $where[] = 'method = :method';
        $params['method'] = strtoupper($method);
    }
    $siteId = trim((string)($_GET['site_id'] ?? ''));
    if ($siteId !== '' && $siteId !== 'all') {
        assert_site_access((int)$siteId, $main);
        $where[] = 'site_id = :site_id';
        $params['site_id'] = (int)$siteId;
    } else {
        append_site_scope_clause($where, $params);
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    $countStmt = $main->prepare("SELECT COUNT(*) FROM operation_logs {$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $stmt = $main->prepare("SELECT * FROM operation_logs {$whereSql} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute($params);
    return [
        'items' => $stmt->fetchAll(),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function platform_customer_payload(array $data, array $current = []): array
{
    $name = trim((string)($data['name'] ?? ($current['name'] ?? '')));
    if ($name === '') {
        fail('客户名称不能为空', 'VALIDATION_ERROR', 422);
    }
    $status = trim((string)($data['status'] ?? ($current['status'] ?? 'active')));
    if (!in_array($status, ['active', 'disabled', 'expired'], true)) {
        $status = 'active';
    }
    $plan = trim((string)($data['plan_key'] ?? ($current['plan_key'] ?? 'starter'))) ?: 'starter';
    return [
        'name' => mb_substr($name, 0, 100, 'UTF-8'),
        'company' => mb_substr(trim((string)($data['company'] ?? ($current['company'] ?? ''))), 0, 150, 'UTF-8'),
        'phone' => mb_substr(trim((string)($data['phone'] ?? ($current['phone'] ?? ''))), 0, 50, 'UTF-8'),
        'email' => mb_substr(trim((string)($data['email'] ?? ($current['email'] ?? ''))), 0, 120, 'UTF-8'),
        'plan_key' => mb_substr($plan, 0, 60, 'UTF-8'),
        'max_sites' => max(1, (int)($data['max_sites'] ?? ($current['max_sites'] ?? 10))),
        'ai_quota' => max(0, (int)($data['ai_quota'] ?? ($current['ai_quota'] ?? 1000))),
        'storage_quota_mb' => max(0, (int)($data['storage_quota_mb'] ?? ($current['storage_quota_mb'] ?? 1024))),
        'expires_at' => trim((string)($data['expires_at'] ?? ($current['expires_at'] ?? ''))) ?: null,
        'status' => $status,
    ];
}

function list_platform_customers(PDO $main): array
{
    ensure_center_tables($main);
    $where = [];
    $params = [];
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    if ($keyword !== '') {
        $where[] = '(c.name LIKE :keyword OR c.company LIKE :keyword OR c.phone LIKE :keyword OR c.email LIKE :keyword)';
        $params['keyword'] = '%' . $keyword . '%';
    }
    $status = trim((string)($_GET['status'] ?? ''));
    if ($status !== '') {
        $where[] = 'c.status = :status';
        $params['status'] = $status;
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    $countStmt = $main->prepare("SELECT COUNT(*) FROM customers c {$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $stmt = $main->prepare("SELECT c.*, COUNT(s.id) AS site_count, SUM(s.status = 'active') AS active_site_count
        FROM customers c
        LEFT JOIN sites s ON s.customer_id = c.id
        {$whereSql}
        GROUP BY c.id
        ORDER BY c.id DESC
        LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute($params);
    return [
        'items' => array_map(function (array $row) {
            $row['site_count'] = (int)($row['site_count'] ?? 0);
            $row['active_site_count'] = (int)($row['active_site_count'] ?? 0);
            return $row;
        }, $stmt->fetchAll()),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function save_platform_customer(PDO $main, array $data, ?int $id = null): array
{
    ensure_center_tables($main);
    $current = $id ? fetch_one($main, 'customers', $id) : [];
    if ($id && !$current) {
        fail('客户不存在', 'NOT_FOUND', 404);
    }
    $payload = platform_customer_payload($data, $current ?: []);
    $now = now();
    if ($id) {
        $stmt = $main->prepare('UPDATE customers SET name=:name, company=:company, phone=:phone, email=:email, plan_key=:plan_key, max_sites=:max_sites, ai_quota=:ai_quota, storage_quota_mb=:storage_quota_mb, expires_at=:expires_at, status=:status, updated_at=:updated_at WHERE id=:id');
        $stmt->execute($payload + ['id' => $id, 'updated_at' => $now]);
    } else {
        $stmt = $main->prepare('INSERT INTO customers (name, company, phone, email, plan_key, max_sites, ai_quota, storage_quota_mb, expires_at, status, created_at, updated_at)
            VALUES (:name, :company, :phone, :email, :plan_key, :max_sites, :ai_quota, :storage_quota_mb, :expires_at, :status, :created_at, :updated_at)');
        $stmt->execute($payload + ['created_at' => $now, 'updated_at' => $now]);
        $id = (int)$main->lastInsertId();
    }
    return fetch_one($main, 'customers', (int)$id) ?: [];
}

function customer_admin_user_payload(array $data): array
{
    $username = trim((string)($data['username'] ?? ''));
    if ($username === '' || !preg_match('/^[a-zA-Z0-9_@.-]{3,80}$/', $username)) {
        fail('客户登录账号只能使用 3-80 位字母、数字、下划线、点、横线或邮箱格式字符', 'VALIDATION_ERROR', 422);
    }
    $password = (string)($data['password'] ?? '');
    if ($password !== '' && strlen($password) < 8) {
        fail('客户登录密码至少 8 位', 'VALIDATION_ERROR', 422);
    }
    return [
        'username' => $username,
        'password' => $password,
        'display_name' => mb_substr(trim((string)($data['display_name'] ?? $username)), 0, 100, 'UTF-8') ?: $username,
        'status' => in_array(($data['status'] ?? 'active'), ['active', 'disabled'], true) ? (string)$data['status'] : 'active',
    ];
}

function save_customer_admin_user(PDO $sitePdo, PDO $main, int $customerId, array $data): array
{
    ensure_center_tables($main);
    ensure_auth_tables($sitePdo);
    $customer = fetch_one($main, 'customers', $customerId);
    if (!$customer) {
        fail('客户不存在', 'NOT_FOUND', 404);
    }
    $payload = customer_admin_user_payload($data);
    $stmt = $sitePdo->prepare("SELECT * FROM admin_users WHERE customer_id = :customer_id AND role = 'customer_admin' ORDER BY id ASC LIMIT 1");
    $stmt->execute(['customer_id' => $customerId]);
    $current = $stmt->fetch();
    $time = now();
    if ($current) {
        $passwordSql = $payload['password'] !== '' ? ', password_hash = :password_hash' : '';
        $update = $sitePdo->prepare("UPDATE admin_users SET username = :username, display_name = :display_name, status = :status{$passwordSql}, updated_at = :updated_at WHERE id = :id");
        $params = [
            'id' => (int)$current['id'],
            'username' => $payload['username'],
            'display_name' => $payload['display_name'],
            'status' => $payload['status'],
            'updated_at' => $time,
        ];
        if ($payload['password'] !== '') {
            $params['password_hash'] = password_hash($payload['password'], PASSWORD_DEFAULT);
        }
        $update->execute($params);
        $id = (int)$current['id'];
    } else {
        if ($payload['password'] === '') {
            fail('新建客户账号时必须设置登录密码', 'VALIDATION_ERROR', 422);
        }
        $insert = $sitePdo->prepare("INSERT INTO admin_users (username, password_hash, display_name, customer_id, role, status, created_at, updated_at)
            VALUES (:username, :password_hash, :display_name, :customer_id, 'customer_admin', :status, :created_at, :updated_at)");
        $insert->execute([
            'username' => $payload['username'],
            'password_hash' => password_hash($payload['password'], PASSWORD_DEFAULT),
            'display_name' => $payload['display_name'],
            'customer_id' => $customerId,
            'status' => $payload['status'],
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        $id = (int)$sitePdo->lastInsertId();
    }
    $item = fetch_one($sitePdo, 'admin_users', $id) ?: [];
    unset($item['password_hash']);
    return $item;
}

function deploy_node_payload(array $data, array $current = []): array
{
    $name = trim((string)($data['name'] ?? ($current['name'] ?? '')));
    if ($name === '') {
        fail('节点名称不能为空', 'VALIDATION_ERROR', 422);
    }
    $status = trim((string)($data['status'] ?? ($current['status'] ?? 'active')));
    if (!in_array($status, ['active', 'disabled'], true)) {
        $status = 'active';
    }
    return [
        'name' => mb_substr($name, 0, 120, 'UTF-8'),
        'node_type' => mb_substr(trim((string)($data['node_type'] ?? ($current['node_type'] ?? 'bt-panel'))) ?: 'bt-panel', 0, 40, 'UTF-8'),
        'server_ip' => mb_substr(trim((string)($data['server_ip'] ?? ($current['server_ip'] ?? ''))), 0, 80, 'UTF-8'),
        'panel_url' => mb_substr(trim((string)($data['panel_url'] ?? ($current['panel_url'] ?? ''))), 0, 255, 'UTF-8'),
        'api_key' => array_key_exists('api_key', $data) && trim((string)$data['api_key']) !== ''
            ? trim((string)$data['api_key'])
            : decrypt_secret((string)($current['api_key'] ?? '')),
        'root_path' => mb_substr(trim((string)($data['root_path'] ?? ($current['root_path'] ?? ''))), 0, 255, 'UTF-8'),
        'status' => $status,
    ];
}

function list_deploy_nodes(PDO $main): array
{
    ensure_center_tables($main);
    $rows = $main->query("SELECT n.*, COUNT(s.id) AS site_count FROM deploy_nodes n LEFT JOIN sites s ON s.deploy_node_id = n.id GROUP BY n.id ORDER BY n.id DESC")->fetchAll();
    return ['items' => array_map(function (array $row) {
        $row['site_count'] = (int)($row['site_count'] ?? 0);
        $row['api_key_masked'] = mask_secret(decrypt_secret((string)($row['api_key'] ?? '')));
        unset($row['api_key']);
        return $row;
    }, $rows)];
}

function save_deploy_node(PDO $main, array $data, ?int $id = null): array
{
    ensure_center_tables($main);
    $current = $id ? fetch_one($main, 'deploy_nodes', $id) : [];
    if ($id && !$current) {
        fail('部署节点不存在', 'NOT_FOUND', 404);
    }
    $payload = deploy_node_payload($data, $current ?: []);
    $payload['api_key'] = encrypt_secret($payload['api_key']);
    $now = now();
    if ($id) {
        $stmt = $main->prepare('UPDATE deploy_nodes SET name=:name, node_type=:node_type, server_ip=:server_ip, panel_url=:panel_url, api_key=:api_key, root_path=:root_path, status=:status, updated_at=:updated_at WHERE id=:id');
        $stmt->execute($payload + ['id' => $id, 'updated_at' => $now]);
    } else {
        $stmt = $main->prepare('INSERT INTO deploy_nodes (name, node_type, server_ip, panel_url, api_key, root_path, status, created_at, updated_at)
            VALUES (:name, :node_type, :server_ip, :panel_url, :api_key, :root_path, :status, :created_at, :updated_at)');
        $stmt->execute($payload + ['created_at' => $now, 'updated_at' => $now]);
        $id = (int)$main->lastInsertId();
    }
    $saved = fetch_one($main, 'deploy_nodes', (int)$id) ?: [];
    $saved['api_key_masked'] = mask_secret(decrypt_secret((string)($saved['api_key'] ?? '')));
    unset($saved['api_key']);
    return $saved;
}

function test_deploy_node(PDO $main, int $id): array
{
    ensure_center_tables($main);
    $node = fetch_one($main, 'deploy_nodes', $id);
    if (!$node) {
        fail('部署节点不存在', 'NOT_FOUND', 404);
    }
    $ok = trim((string)($node['panel_url'] ?? '')) !== '' && trim((string)($node['root_path'] ?? '')) !== '';
    $result = $ok ? '配置完整，等待接入宝塔 API 实测' : '请补全面板地址和服务器根目录';
    $main->prepare('UPDATE deploy_nodes SET last_checked_at=:last_checked_at, last_result=:last_result, updated_at=:updated_at WHERE id=:id')
        ->execute(['id' => $id, 'last_checked_at' => now(), 'last_result' => $result, 'updated_at' => now()]);
    $saved = fetch_one($main, 'deploy_nodes', $id) ?: [];
    $saved['api_key_masked'] = mask_secret(decrypt_secret((string)($saved['api_key'] ?? '')));
    unset($saved['api_key']);
    return $saved;
}

function mask_secret(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    $tail = mb_substr($value, -4, null, 'UTF-8');
    return '****' . $tail;
}

function normalize_ai_base_url(string $baseUrl): string
{
    $baseUrl = rtrim(trim($baseUrl), '/');
    if ($baseUrl === '') {
        return '';
    }
    if (str_ends_with($baseUrl, '/chat/completions')) {
        return $baseUrl;
    }
    return $baseUrl . '/chat/completions';
}

function hydrate_ai_provider(array $row, bool $includeSecret = false): array
{
    $row['id'] = (int)$row['id'];
    $row['is_default'] = (int)($row['is_default'] ?? 0) === 1;
    $row['api_key'] = decrypt_secret((string)($row['api_key'] ?? ''));
    $row['api_key_masked'] = mask_secret($row['api_key'] ?? '');
    if (!$includeSecret) {
        unset($row['api_key']);
    }
    return $row;
}

function list_ai_providers(PDO $main): array
{
    ensure_center_tables($main);
    $rows = $main->query('SELECT * FROM ai_providers ORDER BY is_default DESC, id DESC')->fetchAll();
    return ['items' => array_map('hydrate_ai_provider', $rows)];
}

function ai_provider_payload(array $data, array $current = []): array
{
    $name = mb_substr(trim((string)($data['name'] ?? ($current['name'] ?? ''))), 0, 120, 'UTF-8');
    if ($name === '') {
        fail('AI 服务名称不能为空', 'VALIDATION_ERROR', 422);
    }
    $provider = mb_substr(trim((string)($data['provider'] ?? ($current['provider'] ?? 'openai-compatible'))), 0, 60, 'UTF-8') ?: 'openai-compatible';
    $baseUrl = mb_substr(trim((string)($data['base_url'] ?? ($current['base_url'] ?? ''))), 0, 255, 'UTF-8');
    $apiKey = array_key_exists('api_key', $data) && trim((string)$data['api_key']) !== ''
        ? trim((string)$data['api_key'])
        : decrypt_secret((string)($current['api_key'] ?? ''));
    return [
        'name' => $name,
        'provider' => $provider,
        'base_url' => $baseUrl,
        'api_key' => $apiKey,
        'text_model' => mb_substr(trim((string)($data['text_model'] ?? ($current['text_model'] ?? ''))), 0, 120, 'UTF-8'),
        'image_model' => mb_substr(trim((string)($data['image_model'] ?? ($current['image_model'] ?? ''))), 0, 120, 'UTF-8'),
        'video_model' => mb_substr(trim((string)($data['video_model'] ?? ($current['video_model'] ?? ''))), 0, 120, 'UTF-8'),
        'status' => in_array(($data['status'] ?? ($current['status'] ?? 'enabled')), ['enabled', 'disabled'], true) ? (string)($data['status'] ?? ($current['status'] ?? 'enabled')) : 'enabled',
        'is_default' => !empty($data['is_default']) ? 1 : 0,
    ];
}

function save_ai_provider(PDO $main, array $data, ?int $id = null): array
{
    ensure_center_tables($main);
    $current = $id ? fetch_one($main, 'ai_providers', $id) : [];
    if ($id && !$current) {
        fail('AI 服务不存在', 'NOT_FOUND', 404);
    }
    $payload = ai_provider_payload($data, $current ?: []);
    $payload['api_key'] = encrypt_secret($payload['api_key']);
    $now = now();
    if (!empty($payload['is_default'])) {
        $main->exec('UPDATE ai_providers SET is_default = 0');
    }
    if ($id) {
        $stmt = $main->prepare('UPDATE ai_providers SET name=:name, provider=:provider, base_url=:base_url, api_key=:api_key, text_model=:text_model, image_model=:image_model, video_model=:video_model, status=:status, is_default=:is_default, updated_at=:updated_at WHERE id=:id');
        $stmt->execute($payload + ['id' => $id, 'updated_at' => $now]);
    } else {
        $stmt = $main->prepare('INSERT INTO ai_providers (name, provider, base_url, api_key, text_model, image_model, video_model, status, is_default, created_at, updated_at)
            VALUES (:name, :provider, :base_url, :api_key, :text_model, :image_model, :video_model, :status, :is_default, :created_at, :updated_at)');
        $stmt->execute($payload + ['created_at' => $now, 'updated_at' => $now]);
        $id = (int)$main->lastInsertId();
    }
    return hydrate_ai_provider(fetch_one($main, 'ai_providers', (int)$id) ?: []);
}

function test_ai_provider(PDO $main, int $id): array
{
    ensure_center_tables($main);
    $item = fetch_one($main, 'ai_providers', $id);
    if (!$item) {
        fail('AI 服务不存在', 'NOT_FOUND', 404);
    }
    $ready = trim((string)($item['base_url'] ?? '')) !== ''
        && trim(decrypt_secret((string)($item['api_key'] ?? ''))) !== ''
        && trim((string)($item['text_model'] ?? '')) !== '';
    $result = $ready ? '配置完整，可用于 OpenAI 兼容文本生成接口。' : '请补齐 API 地址、API Key 和文本模型。';
    $main->prepare('UPDATE ai_providers SET last_checked_at=:last_checked_at, last_result=:last_result, updated_at=:updated_at WHERE id=:id')
        ->execute(['id' => $id, 'last_checked_at' => now(), 'last_result' => $result, 'updated_at' => now()]);
    return hydrate_ai_provider(fetch_one($main, 'ai_providers', $id) ?: []);
}

function apply_ai_provider_to_site(PDO $main, PDO $sitePdo, int $providerId): array
{
    ensure_center_tables($main);
    $provider = fetch_one($main, 'ai_providers', $providerId);
    if (!$provider) {
        fail('AI 服务不存在', 'NOT_FOUND', 404);
    }
    if (($provider['status'] ?? '') !== 'enabled') {
        fail('AI 服务未启用', 'VALIDATION_ERROR', 422);
    }
    $settings = site_settings($sitePdo);
    $settings['ai'] = array_replace((array)($settings['ai'] ?? []), [
        'provider_id' => (int)$provider['id'],
        'provider' => (string)$provider['provider'],
        'name' => (string)$provider['name'],
        'endpoint' => normalize_ai_base_url((string)($provider['base_url'] ?? '')),
        'api_key' => decrypt_secret((string)($provider['api_key'] ?? '')),
        'model' => (string)($provider['text_model'] ?? ''),
        'image_model' => (string)($provider['image_model'] ?? ''),
        'video_model' => (string)($provider['video_model'] ?? ''),
    ]);
    return ['provider' => hydrate_ai_provider($provider), 'site' => save_site_settings($sitePdo, $settings)];
}

function normalize_domain_name(string $domain): string
{
    $domain = strtolower(trim($domain));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#/.*$#', '', $domain);
    return mb_substr($domain, 0, 180, 'UTF-8');
}

function domain_payload(array $data, array $current = []): array
{
    $domain = normalize_domain_name((string)($data['domain'] ?? ($current['domain'] ?? '')));
    if ($domain === '' || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
        fail('域名格式不正确', 'VALIDATION_ERROR', 422);
    }
    $type = trim((string)($data['domain_type'] ?? ($current['domain_type'] ?? 'primary')));
    if (!in_array($type, ['primary', 'alias', 'subdomain'], true)) {
        $type = 'alias';
    }
    $status = trim((string)($data['status'] ?? ($current['status'] ?? 'active')));
    if (!in_array($status, ['active', 'disabled'], true)) {
        $status = 'active';
    }
    return [
        'domain' => $domain,
        'domain_type' => $type,
        'is_primary' => !empty($data['is_primary']) || (($current['is_primary'] ?? 0) && !array_key_exists('is_primary', $data)) ? 1 : 0,
        'dns_status' => trim((string)($data['dns_status'] ?? ($current['dns_status'] ?? 'pending'))) ?: 'pending',
        'ssl_status' => trim((string)($data['ssl_status'] ?? ($current['ssl_status'] ?? 'pending'))) ?: 'pending',
        'status' => $status,
    ];
}

function sync_primary_domain(PDO $main, int $siteId, int $domainId, string $domain): void
{
    $main->prepare('UPDATE site_domains SET is_primary = 0, domain_type = IF(domain_type = "primary", "alias", domain_type), updated_at = :updated_at WHERE site_id = :site_id AND id <> :id')
        ->execute(['site_id' => $siteId, 'id' => $domainId, 'updated_at' => now()]);
    $main->prepare('UPDATE site_domains SET is_primary = 1, domain_type = "primary", updated_at = :updated_at WHERE id = :id')
        ->execute(['id' => $domainId, 'updated_at' => now()]);
    $main->prepare('UPDATE sites SET domain = :domain, updated_at = :updated_at WHERE id = :site_id')
        ->execute(['site_id' => $siteId, 'domain' => $domain, 'updated_at' => now()]);
}

function list_site_domains(PDO $main, int $siteId): array
{
    ensure_center_tables($main);
    $stmt = $main->prepare('SELECT * FROM site_domains WHERE site_id = ? ORDER BY is_primary DESC, id DESC');
    $stmt->execute([$siteId]);
    return ['items' => $stmt->fetchAll()];
}

function primary_site_domain(PDO $main, int $siteId): array
{
    ensure_center_tables($main);
    $stmt = $main->prepare('SELECT * FROM site_domains WHERE site_id = ? AND status = "active" ORDER BY is_primary DESC, id DESC LIMIT 1');
    $stmt->execute([$siteId]);
    return $stmt->fetch() ?: [];
}

function probe_domain_dns(string $domain): array
{
    $records = [];
    if (function_exists('dns_get_record')) {
        $types = DNS_A | DNS_AAAA | DNS_CNAME;
        $dnsRecords = @dns_get_record($domain, $types);
        if (is_array($dnsRecords)) {
            foreach ($dnsRecords as $record) {
                $value = (string)($record['ip'] ?? $record['ipv6'] ?? $record['target'] ?? '');
                if ($value !== '') {
                    $records[] = $value;
                }
            }
        }
    }
    if (!$records) {
        $ips = @gethostbynamel($domain);
        if (is_array($ips)) {
            $records = array_merge($records, array_filter(array_map('strval', $ips)));
        }
    }
    $records = array_values(array_unique($records));
    return [
        'ok' => count($records) > 0,
        'records' => $records,
        'message' => $records ? 'DNS 已解析：' . implode(', ', array_slice($records, 0, 3)) : 'DNS 未解析或暂不可达',
    ];
}

function probe_domain_ssl(string $domain, bool $dnsOk): array
{
    if (!$dnsOk) {
        return ['ok' => false, 'status' => 'pending', 'message' => 'DNS 未就绪，暂不检查 HTTPS'];
    }
    $url = 'https://' . $domain . '/';
    if (!function_exists('curl_init')) {
        $headers = @get_headers($url);
        $ok = is_array($headers) && preg_match('/^HTTP\/\S+\s+[23]\d\d/i', (string)($headers[0] ?? ''));
        return [
            'ok' => $ok,
            'status' => $ok ? 'ready' : 'failed',
            'message' => $ok ? 'HTTPS 可访问' : 'HTTPS 暂不可访问',
        ];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'HuajianDomainChecker/0.1',
    ]);
    curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $ok = $errno === 0 && $httpCode >= 200 && $httpCode < 500;
    $message = $ok ? ('HTTPS 可访问，HTTP ' . $httpCode) : ('HTTPS 检查失败' . ($error ? '：' . $error : ''));
    return [
        'ok' => $ok,
        'status' => $ok ? 'ready' : 'failed',
        'message' => $message,
        'http_code' => $httpCode,
    ];
}

function save_site_domain(PDO $main, int $siteId, array $data, ?int $id = null): array
{
    ensure_center_tables($main);
    $site = fetch_one($main, 'sites', $siteId);
    if (!$site) {
        fail('站点不存在', 'NOT_FOUND', 404);
    }
    $current = $id ? fetch_one($main, 'site_domains', $id) : [];
    if ($id && (!$current || (int)$current['site_id'] !== $siteId)) {
        fail('域名不存在', 'NOT_FOUND', 404);
    }
    $payload = domain_payload($data, $current ?: []);
    $now = now();
    try {
        if ($id) {
            $stmt = $main->prepare('UPDATE site_domains SET domain=:domain, domain_type=:domain_type, is_primary=:is_primary, dns_status=:dns_status, ssl_status=:ssl_status, status=:status, updated_at=:updated_at WHERE id=:id');
            $stmt->execute($payload + ['id' => $id, 'updated_at' => $now]);
        } else {
            $stmt = $main->prepare('INSERT INTO site_domains (site_id, domain, domain_type, is_primary, dns_status, ssl_status, status, created_at, updated_at)
                VALUES (:site_id, :domain, :domain_type, :is_primary, :dns_status, :ssl_status, :status, :created_at, :updated_at)');
            $stmt->execute($payload + ['site_id' => $siteId, 'created_at' => $now, 'updated_at' => $now]);
            $id = (int)$main->lastInsertId();
        }
    } catch (PDOException $error) {
        fail('该站点已绑定此域名', 'DOMAIN_EXISTS', 409);
    }
    if (!empty($payload['is_primary'])) {
        sync_primary_domain($main, $siteId, (int)$id, $payload['domain']);
    }
    return fetch_one($main, 'site_domains', (int)$id) ?: [];
}

function check_site_domain(PDO $main, int $siteId, int $id): array
{
    ensure_center_tables($main);
    $item = fetch_one($main, 'site_domains', $id);
    if (!$item || (int)$item['site_id'] !== $siteId) {
        fail('域名不存在', 'NOT_FOUND', 404);
    }
    $domain = (string)$item['domain'];
    $dns = probe_domain_dns($domain);
    $ssl = probe_domain_ssl($domain, (bool)$dns['ok']);
    $result = $dns['message'] . '；' . $ssl['message'];
    $summary = [
        'domain' => $domain,
        'dns' => $dns,
        'ssl' => $ssl,
        'checked_at' => now(),
    ];
    $main->prepare('UPDATE site_domains SET dns_status=:dns_status, ssl_status=:ssl_status, last_checked_at=:last_checked_at, last_result=:last_result, updated_at=:updated_at WHERE id=:id')
        ->execute([
            'id' => $id,
            'dns_status' => $dns['ok'] ? 'valid' : 'failed',
            'ssl_status' => $ssl['status'],
            'last_checked_at' => now(),
            'last_result' => mb_substr($result, 0, 255, 'UTF-8'),
            'updated_at' => now(),
        ]);
    $saved = fetch_one($main, 'site_domains', $id) ?: [];
    $saved['check_summary'] = $summary;
    return $saved;
}

function check_all_site_domains(PDO $main, int $siteId): array
{
    ensure_center_tables($main);
    $stmt = $main->prepare('SELECT id FROM site_domains WHERE site_id = ? AND status = "active" ORDER BY is_primary DESC, id DESC');
    $stmt->execute([$siteId]);
    $items = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $items[] = check_site_domain($main, $siteId, (int)$id);
    }
    $valid = count(array_filter($items, fn($item) => ($item['dns_status'] ?? '') === 'valid'));
    $sslReady = count(array_filter($items, fn($item) => ($item['ssl_status'] ?? '') === 'ready'));
    return [
        'items' => $items,
        'total' => count($items),
        'valid_dns' => $valid,
        'ssl_ready' => $sslReady,
        'message' => '已检查 ' . count($items) . ' 个启用域名，DNS 有效 ' . $valid . ' 个，HTTPS 就绪 ' . $sslReady . ' 个',
    ];
}

function domain_application_payload(array $data, array $current = []): array
{
    $domain = normalize_domain_name((string)($data['domain'] ?? ($current['domain'] ?? '')));
    if ($domain === '' || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
        fail('申请域名格式不正确', 'VALIDATION_ERROR', 422);
    }
    $usageType = trim((string)($data['usage_type'] ?? ($current['usage_type'] ?? 'primary')));
    if (!in_array($usageType, ['primary', 'alias', 'subdomain', 'brand-protect'], true)) {
        $usageType = 'primary';
    }
    $status = trim((string)($data['status'] ?? ($current['status'] ?? 'submitted')));
    if (!in_array($status, ['submitted', 'checking', 'approved', 'rejected', 'purchased', 'bound', 'cancelled'], true)) {
        $status = 'submitted';
    }
    return [
        'domain' => $domain,
        'years' => min(10, max(1, (int)($data['years'] ?? ($current['years'] ?? 1)))),
        'usage_type' => $usageType,
        'contact_name' => mb_substr(trim((string)($data['contact_name'] ?? ($current['contact_name'] ?? ''))), 0, 100, 'UTF-8'),
        'contact_phone' => mb_substr(trim((string)($data['contact_phone'] ?? ($current['contact_phone'] ?? ''))), 0, 50, 'UTF-8'),
        'contact_email' => mb_substr(trim((string)($data['contact_email'] ?? ($current['contact_email'] ?? ''))), 0, 120, 'UTF-8'),
        'status' => $status,
        'applicant_note' => trim((string)($data['applicant_note'] ?? ($current['applicant_note'] ?? ''))),
        'admin_note' => trim((string)($data['admin_note'] ?? ($current['admin_note'] ?? ''))),
    ];
}

function hydrate_domain_application(array $row): array
{
    $row['id'] = (int)($row['id'] ?? 0);
    $row['customer_id'] = (int)($row['customer_id'] ?? 0);
    $row['site_id'] = (int)($row['site_id'] ?? 0);
    $row['years'] = (int)($row['years'] ?? 1);
    return $row;
}

function list_domain_applications(PDO $main, ?array $user = null, bool $platform = false): array
{
    ensure_center_tables($main);
    $where = [];
    $params = [];
    if (!$platform) {
        $scope = requested_site_scope();
        if ($scope !== null) {
            if (!$scope) {
                $where[] = '1 = 0';
            } else {
                $placeholders = [];
                foreach (array_values($scope) as $index => $siteId) {
                    $key = 'site_id_' . $index;
                    $placeholders[] = ':' . $key;
                    $params[$key] = (int)$siteId;
                }
                $where[] = 'da.site_id IN (' . implode(',', $placeholders) . ')';
            }
        }
        if ($user && !is_platform_admin($user)) {
            $where[] = 'da.customer_id = :customer_id';
            $params['customer_id'] = (int)($user['customer_id'] ?? 0);
        }
    }
    $status = trim((string)($_GET['status'] ?? ''));
    if ($status !== '') {
        $where[] = 'da.status = :status';
        $params['status'] = $status;
    }
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    if ($keyword !== '') {
        $where[] = '(da.domain LIKE :keyword OR s.name LIKE :keyword OR c.name LIKE :keyword)';
        $params['keyword'] = '%' . $keyword . '%';
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    $countStmt = $main->prepare("SELECT COUNT(*) FROM domain_applications da LEFT JOIN sites s ON s.id = da.site_id LEFT JOIN customers c ON c.id = da.customer_id {$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $stmt = $main->prepare("SELECT da.*, s.name AS site_name, s.site_key, c.name AS customer_name
        FROM domain_applications da
        LEFT JOIN sites s ON s.id = da.site_id
        LEFT JOIN customers c ON c.id = da.customer_id
        {$whereSql}
        ORDER BY da.id DESC
        LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute($params);
    return [
        'items' => array_map('hydrate_domain_application', $stmt->fetchAll()),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function create_domain_application(PDO $main, int $siteId, array $data, ?array $user = null): array
{
    ensure_center_tables($main);
    assert_site_access($siteId);
    $site = fetch_one($main, 'sites', $siteId);
    if (!$site) {
        fail('站点不存在', 'NOT_FOUND', 404);
    }
    if ($user && !is_platform_admin($user) && (int)($site['customer_id'] ?? 0) !== (int)($user['customer_id'] ?? 0)) {
        fail('无权为该站点申请域名', 'FORBIDDEN', 403);
    }
    $payload = domain_application_payload($data);
    $now = now();
    $stmt = $main->prepare('INSERT INTO domain_applications (customer_id, site_id, domain, years, usage_type, contact_name, contact_phone, contact_email, status, applicant_note, admin_note, created_at, updated_at)
        VALUES (:customer_id, :site_id, :domain, :years, :usage_type, :contact_name, :contact_phone, :contact_email, "submitted", :applicant_note, "", :created_at, :updated_at)');
    $stmt->execute([
        'customer_id' => (int)$site['customer_id'],
        'site_id' => $siteId,
        'domain' => $payload['domain'],
        'years' => $payload['years'],
        'usage_type' => $payload['usage_type'],
        'contact_name' => $payload['contact_name'],
        'contact_phone' => $payload['contact_phone'],
        'contact_email' => $payload['contact_email'],
        'applicant_note' => $payload['applicant_note'],
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    return hydrate_domain_application(fetch_one($main, 'domain_applications', (int)$main->lastInsertId()) ?: []);
}

function update_domain_application(PDO $main, int $id, array $data): array
{
    ensure_center_tables($main);
    $current = fetch_one($main, 'domain_applications', $id);
    if (!$current) {
        fail('域名申请不存在', 'NOT_FOUND', 404);
    }
    $payload = domain_application_payload($data, $current);
    $statusChanged = ($payload['status'] ?? '') !== ($current['status'] ?? '');
    $now = now();
    $stmt = $main->prepare('UPDATE domain_applications SET domain=:domain, years=:years, usage_type=:usage_type, contact_name=:contact_name, contact_phone=:contact_phone, contact_email=:contact_email, status=:status, applicant_note=:applicant_note, admin_note=:admin_note, processed_at=:processed_at, updated_at=:updated_at WHERE id=:id');
    $stmt->execute($payload + [
        'id' => $id,
        'processed_at' => $statusChanged && in_array($payload['status'], ['approved', 'rejected', 'purchased', 'bound', 'cancelled'], true) ? $now : ($current['processed_at'] ?? null),
        'updated_at' => $now,
    ]);
    if (!empty($data['bind_to_site']) && in_array($payload['status'], ['approved', 'purchased', 'bound'], true)) {
        $domain = save_site_domain($main, (int)$current['site_id'], [
            'domain' => $payload['domain'],
            'domain_type' => $payload['usage_type'] === 'alias' ? 'alias' : 'primary',
            'is_primary' => $payload['usage_type'] !== 'alias',
            'dns_status' => 'pending',
            'ssl_status' => 'pending',
            'status' => 'active',
        ]);
        $main->prepare('UPDATE domain_applications SET status = "bound", processed_at = :processed_at, updated_at = :updated_at WHERE id = :id')
            ->execute(['id' => $id, 'processed_at' => $now, 'updated_at' => $now]);
        $saved = hydrate_domain_application(fetch_one($main, 'domain_applications', $id) ?: []);
        $saved['bound_domain'] = $domain;
        return $saved;
    }
    return hydrate_domain_application(fetch_one($main, 'domain_applications', $id) ?: []);
}

function batch_task_filter_sql(): array
{
    $action = trim((string)($_GET['action'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $date = trim((string)($_GET['date'] ?? ''));

    $clauses = [];
    $params = [];
    if ($action !== '' && in_array($action, ['generate', 'deploy-check', 'package'], true)) {
        $clauses[] = 'action = :action';
        $params['action'] = $action;
    }
    if ($status !== '' && in_array($status, ['success', 'partial', 'failed'], true)) {
        $clauses[] = 'status = :status';
        $params['status'] = $status;
    }
    if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $clauses[] = 'DATE(created_at) = :date';
        $params['date'] = $date;
    }
    $scope = requested_site_scope();
    if ($scope !== null) {
        if (!$scope) {
            $clauses[] = '1 = 0';
        } else {
            $clauses[] = 'site_ids REGEXP :site_ids_regexp';
            $params['site_ids_regexp'] = '(^|[^0-9])(' . implode('|', array_map('intval', $scope)) . ')([^0-9]|$)';
        }
    }
    $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
    return [$whereSql, $params];
}

function normalize_batch_task_row(array $item): array
{
    $summary = json_decode((string)($item['summary'] ?? ''), true);
    $siteIds = json_decode((string)($item['site_ids'] ?? '[]'), true);
    $item['summary_data'] = is_array($summary) ? $summary : [];
    $item['site_id_list'] = is_array($siteIds) ? $siteIds : [];
    return $item;
}

function batch_task_overview(PDO $main, string $whereSql = '', array $params = []): array
{
    $stmt = $main->prepare("SELECT
        COUNT(*) AS total,
        SUM(status = 'success') AS success_tasks,
        SUM(status = 'partial') AS partial_tasks,
        SUM(status = 'failed') AS failed_tasks,
        COALESCE(SUM(total_count), 0) AS site_runs,
        COALESCE(SUM(success_count), 0) AS success_runs,
        COALESCE(SUM(failed_count), 0) AS failed_runs,
        MAX(finished_at) AS last_finished_at
        FROM batch_tasks{$whereSql}");
    $stmt->execute($params);
    $row = $stmt->fetch() ?: [];
    $siteRuns = (int)($row['site_runs'] ?? 0);
    $successRuns = (int)($row['success_runs'] ?? 0);
    return [
        'total' => (int)($row['total'] ?? 0),
        'success_tasks' => (int)($row['success_tasks'] ?? 0),
        'partial_tasks' => (int)($row['partial_tasks'] ?? 0),
        'failed_tasks' => (int)($row['failed_tasks'] ?? 0),
        'site_runs' => $siteRuns,
        'success_runs' => $successRuns,
        'failed_runs' => (int)($row['failed_runs'] ?? 0),
        'success_rate' => $siteRuns > 0 ? round($successRuns * 100 / $siteRuns, 1) : 0,
        'last_finished_at' => (string)($row['last_finished_at'] ?? ''),
    ];
}

function export_batch_tasks_csv(PDO $main): void
{
    ensure_center_tables($main);
    [$whereSql, $params] = batch_task_filter_sql();
    $stmt = $main->prepare("SELECT * FROM batch_tasks{$whereSql} ORDER BY id DESC LIMIT 5000");
    $stmt->execute($params);

    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="batch-tasks-' . date('Ymd-His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['任务号', '任务类型', '任务状态', '总站点数', '成功数', '失败数', '站点ID', '站点名称', '站点结果', '结果说明', '开始时间', '完成时间', '创建时间']);
    while ($row = $stmt->fetch()) {
        $item = normalize_batch_task_row($row);
        $results = $item['summary_data']['results'] ?? [];
        if (!$results) {
            fputcsv($out, [
                $item['task_no'] ?? '',
                $item['action'] ?? '',
                $item['status'] ?? '',
                $item['total_count'] ?? 0,
                $item['success_count'] ?? 0,
                $item['failed_count'] ?? 0,
                '',
                '',
                '',
                '',
                $item['started_at'] ?? '',
                $item['finished_at'] ?? '',
                $item['created_at'] ?? '',
            ]);
            continue;
        }
        foreach ($results as $result) {
            fputcsv($out, [
                $item['task_no'] ?? '',
                $item['action'] ?? '',
                $item['status'] ?? '',
                $item['total_count'] ?? 0,
                $item['success_count'] ?? 0,
                $item['failed_count'] ?? 0,
                $result['site_id'] ?? '',
                $result['site_name'] ?? '',
                !empty($result['ok']) ? 'success' : 'failed',
                preg_replace('/\s+/', ' ', (string)($result['message'] ?? '')),
                $item['started_at'] ?? '',
                $item['finished_at'] ?? '',
                $item['created_at'] ?? '',
            ]);
        }
    }
    fclose($out);
    exit;
}

function normalize_template_clone_url(string $url): string
{
    $url = trim($url);
    assert_collect_url($url);
    return mb_substr($url, 0, 500, 'UTF-8');
}

function template_clone_key(string $url): string
{
    $host = (string)(parse_url($url, PHP_URL_HOST) ?: 'site');
    $base = preg_replace('/[^a-z0-9]+/i', '-', strtolower($host));
    $base = trim((string)$base, '-') ?: 'site';
    $base = mb_substr($base, 0, 48, 'UTF-8');
    return 'clone-' . $base . '-' . date('ymdHis') . '-' . strtolower(substr(bin2hex(random_bytes(2)), 0, 4));
}

function extract_html_title(string $html, string $fallback): string
{
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match)) {
        $title = html_entity_decode(trim(strip_tags($match[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($title !== '') {
            return mb_substr($title, 0, 120, 'UTF-8');
        }
    }
    return mb_substr($fallback, 0, 120, 'UTF-8');
}

function fetch_template_clone_html(string $url): array
{
    assert_collect_url($url);
    if (!function_exists('curl_init')) {
        $context = stream_context_create(['http' => ['timeout' => 8, 'user_agent' => 'HuajianTemplateClone/0.1']]);
        $body = @file_get_contents($url, false, $context);
        return is_string($body) && $body !== ''
            ? ['html' => $body, 'message' => '已读取目标首页 HTML。']
            : ['html' => '', 'message' => '目标网页暂未读取成功，已先按 URL 生成标准模板草稿。'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_USERAGENT => 'HuajianTemplateClone/0.1',
    ]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if (is_string($body) && $body !== '' && $status >= 200 && $status < 400) {
        return ['html' => $body, 'message' => '已读取目标首页 HTML。'];
    }
    $suffix = $error ? '原因：' . $error : ($status ? 'HTTP 状态：' . $status : '');
    return ['html' => '', 'message' => trim('目标网页暂未读取成功，已先按 URL 生成标准模板草稿。' . $suffix)];
}

function template_clone_module_plan(string $title, string $url, bool $fetched): array
{
    return [
        ['module' => 'header', 'title' => '导航菜单', 'description' => '保留品牌、导航、咨询入口的标准头部。'],
        ['module' => 'hero', 'title' => $title ?: '品牌首页首屏', 'description' => '将目标网站首屏改造成可编辑标题、副标题、按钮和背景图。'],
        ['module' => 'advantages', 'title' => '核心优势', 'description' => '用卡片模块承载服务优势、产品卖点和企业能力。'],
        ['module' => 'products', 'title' => '产品展示', 'description' => '对接中台商品库，自动输出商品列表和详情页。'],
        ['module' => 'articles', 'title' => '内容与新闻', 'description' => '对接文章库和采集中心，沉淀 SEO 内容。'],
        ['module' => 'contact', 'title' => '询盘转化', 'description' => '保留联系表单、悬浮咨询和订单/询盘入口。'],
        ['module' => 'footer', 'title' => '页脚信息', 'description' => '沉淀联系方式、备案/版权、友情链接和站点地图入口。'],
        ['module' => 'source', 'title' => $fetched ? '已读取目标 HTML' : '未读取目标 HTML', 'description' => $url],
    ];
}

function write_template_clone_metadata(string $templateDir, string $key, string $name, string $url, array $modulePlan): void
{
    $meta = [
        'name' => $name,
        'key' => $key,
        'version' => '0.1.0',
        'author' => '化简模板克隆',
        'type' => ['company', 'blog', 'shop'],
        'supports' => ['page', 'article', 'product', 'seo', 'form', 'clone-draft'],
        'entry' => 'pages/index.html',
        'source_url' => $url,
        'module_plan' => $modulePlan,
    ];
    file_put_contents($templateDir . DIRECTORY_SEPARATOR . 'template.json', json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $readme = "# {$name}\n\n来源：{$url}\n\n这是化简根据目标 URL 生成的标准化模板草稿，已转换为可编辑的 header、hero、内容模块、商品、文章、表单和 footer 结构。\n";
    file_put_contents($templateDir . DIRECTORY_SEPARATOR . 'CLONE_SOURCE.md', $readme);
}

function create_template_clone_task(PDO $main, array $data): array
{
    ensure_center_tables($main);
    require_fields($data, ['target_url']);
    $url = normalize_template_clone_url((string)$data['target_url']);
    $fetch = fetch_template_clone_html($url);
    $html = (string)($fetch['html'] ?? '');
    $fetched = $html !== '';
    $message = ($fetch['message'] ?? '') . ' 模板草稿已生成。';
    $host = (string)(parse_url($url, PHP_URL_HOST) ?: '目标网站');
    $sourceTitle = extract_html_title($html, $host);
    $key = template_clone_key($url);
    $name = '克隆草稿 - ' . $sourceTitle;
    $root = dirname(__DIR__, 2);
    $baseTemplate = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'business-clean';
    $templateDir = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $key;
    if (!is_dir($baseTemplate)) {
        fail('基础模板不存在，无法生成草稿', 'TEMPLATE_BASE_MISSING', 500);
    }
    copy_directory($baseTemplate, $templateDir);
    $modulePlan = template_clone_module_plan($sourceTitle, $url, $fetched);
    write_template_clone_metadata($templateDir, $key, $name, $url, $modulePlan);
    $now = now();
    $taskNo = 'TC' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $stmt = $main->prepare("INSERT INTO template_clone_tasks (task_no, target_url, template_key, template_name, source_title, status, module_plan_json, source_excerpt, message, created_at, updated_at)
        VALUES (:task_no, :target_url, :template_key, :template_name, :source_title, 'success', :module_plan_json, :source_excerpt, :message, :created_at, :updated_at)");
    $stmt->execute([
        'task_no' => $taskNo,
        'target_url' => $url,
        'template_key' => $key,
        'template_name' => $name,
        'source_title' => $sourceTitle,
        'module_plan_json' => json_encode($modulePlan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'source_excerpt' => mb_substr($html, 0, 20000, 'UTF-8'),
        'message' => $message,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    return normalize_template_clone_task(fetch_one($main, 'template_clone_tasks', (int)$main->lastInsertId()) ?: []);
}

function normalize_template_clone_task(array $row): array
{
    $row['id'] = (int)($row['id'] ?? 0);
    $plan = json_decode((string)($row['module_plan_json'] ?? ''), true);
    $row['module_plan'] = is_array($plan) ? $plan : [];
    unset($row['module_plan_json']);
    return $row;
}

function list_template_clone_tasks(PDO $main): array
{
    ensure_center_tables($main);
    $result = paginate($main, 'template_clone_tasks', [], 'id DESC', 'target_url');
    $result['items'] = array_map('normalize_template_clone_task', $result['items']);
    return $result;
}

function apply_template_clone_task(PDO $main, PDO $sitePdo, int $taskId): array
{
    ensure_center_tables($main);
    $task = fetch_one($main, 'template_clone_tasks', $taskId);
    if (!$task) {
        fail('模板克隆任务不存在', 'NOT_FOUND', 404);
    }
    $task = normalize_template_clone_task($task);
    $key = (string)($task['template_key'] ?? '');
    if ($key === '' || !is_dir(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $key)) {
        fail('模板草稿目录不存在', 'TEMPLATE_NOT_FOUND', 404);
    }
    $settings = site_settings($sitePdo);
    $settings['template_key'] = $key;
    $settings['template_clone'] = [
        'task_id' => (int)$task['id'],
        'target_url' => (string)$task['target_url'],
        'template_key' => $key,
        'applied_at' => now(),
    ];
    $siteId = requested_site_id();
    $main->prepare('UPDATE sites SET template_key = :template_key, updated_at = :updated_at WHERE id = :id')
        ->execute(['id' => $siteId, 'template_key' => $key, 'updated_at' => now()]);
    return [
        'task' => $task,
        'site_id' => $siteId,
        'template_key' => $key,
        'preview_url' => '/s/site_' . $siteId . '/',
        'site' => save_site_settings($sitePdo, $settings),
    ];
}

function preview_template_clone_task(PDO $main, PDO $sitePdo, int $taskId): array
{
    ensure_center_tables($main);
    $task = fetch_one($main, 'template_clone_tasks', $taskId);
    if (!$task) {
        fail('模板克隆任务不存在', 'NOT_FOUND', 404);
    }
    $task = normalize_template_clone_task($task);
    $key = (string)($task['template_key'] ?? '');
    $root = dirname(__DIR__, 2);
    $templateDir = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $key;
    if ($key === '' || !is_dir($templateDir) || !is_file($templateDir . DIRECTORY_SEPARATOR . 'template.json')) {
        fail('模板草稿目录不存在', 'TEMPLATE_NOT_FOUND', 404);
    }
    $siteId = requested_site_id();
    $site = current_site($main, $sitePdo);
    $siteKey = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($site['site_key'] ?? ('site_' . $siteId)));
    $previewRoot = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'template_previews' . DIRECTORY_SEPARATOR . $siteKey . DIRECTORY_SEPARATOR . $key;
    ensure_dir($previewRoot);
    $php = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
    $script = $root . DIRECTORY_SEPARATOR . 'worker' . DIRECTORY_SEPARATOR . 'GenerateSite.php';
    putenv('HJ_SITE_KEY=' . $siteKey);
    putenv('HJ_SITE_ID=' . (string)$siteId);
    putenv('HJ_SITE_NAME=' . (string)($site['name'] ?? '模板预览站点'));
    putenv('HJ_SITE_DOMAIN=' . (string)($site['domain'] ?: ($site['subdomain'] ?? '')));
    putenv('HJ_SITE_LANGUAGE=' . (string)($site['language'] ?: 'zh-CN'));
    putenv('HJ_TEMPLATE_KEY=' . $key);
    putenv('HJ_PUBLIC_PATH=' . $previewRoot);
    $command = '"' . $php . '" "' . $script . '"';
    $output = [];
    $code = 0;
    exec($command, $output, $code);
    if ($code !== 0) {
        fail('模板预览生成失败', 'TEMPLATE_PREVIEW_FAILED', 500, ['output' => $output]);
    }
    $main->prepare('UPDATE template_clone_tasks SET message = :message, updated_at = :updated_at WHERE id = :id')
        ->execute([
            'id' => $taskId,
            'message' => '模板草稿预览已生成',
            'updated_at' => now(),
        ]);
    return [
        'task' => normalize_template_clone_task(fetch_one($main, 'template_clone_tasks', $taskId) ?: $task),
        'site_id' => $siteId,
        'site_key' => $siteKey,
        'template_key' => $key,
        'preview_url' => '/template-previews/' . rawurlencode($siteKey) . '/' . rawurlencode($key) . '/',
        'output' => $output,
    ];
}

function delete_template_clone_task(PDO $main, int $taskId): void
{
    ensure_center_tables($main);
    $task = fetch_one($main, 'template_clone_tasks', $taskId);
    if (!$task) {
        fail('模板克隆任务不存在', 'NOT_FOUND', 404);
    }
    $key = (string)($task['template_key'] ?? '');
    if (str_starts_with($key, 'clone-')) {
        $root = dirname(__DIR__, 2);
        $dir = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $key;
        if (is_dir($dir)) {
            remove_dir_contents($dir);
            rmdir($dir);
        }
        $previewRoot = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'template_previews';
        if (is_dir($previewRoot)) {
            foreach (new DirectoryIterator($previewRoot) as $siteDir) {
                if ($siteDir->isDot() || !$siteDir->isDir()) {
                    continue;
                }
                $previewDir = $siteDir->getPathname() . DIRECTORY_SEPARATOR . $key;
                if (is_dir($previewDir)) {
                    remove_dir_contents($previewDir);
                    rmdir($previewDir);
                }
            }
        }
    }
    $main->prepare('DELETE FROM template_clone_tasks WHERE id = ?')->execute([$taskId]);
}

function save_batch_task(PDO $main, array $data): array
{
    ensure_center_tables($main);
    $action = trim((string)($data['action'] ?? ''));
    if (!in_array($action, ['generate', 'deploy-check', 'package'], true)) {
        fail('任务类型不支持', 'VALIDATION_ERROR', 422);
    }
    $results = $data['results'] ?? [];
    if (!is_array($results)) {
        $results = [];
    }
    $siteIds = [];
    $cleanResults = [];
    foreach ($results as $result) {
        if (!is_array($result)) {
            continue;
        }
        $siteId = (int)($result['site_id'] ?? 0);
        if ($siteId > 0) {
            $siteIds[] = $siteId;
        }
        $cleanResults[] = [
            'site_id' => $siteId,
            'site_name' => mb_substr((string)($result['site_name'] ?? ''), 0, 120, 'UTF-8'),
            'ok' => !empty($result['ok']),
            'message' => mb_substr((string)($result['message'] ?? ''), 0, 500, 'UTF-8'),
        ];
    }
    $total = count($cleanResults);
    $success = count(array_filter($cleanResults, static fn($item) => !empty($item['ok'])));
    $failed = max(0, $total - $success);
    $status = $failed === 0 ? 'success' : ($success > 0 ? 'partial' : 'failed');
    $now = now();
    $taskNo = 'BT' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $summary = [
        'message' => (string)($data['message'] ?? ''),
        'results' => $cleanResults,
    ];

    $stmt = $main->prepare("INSERT INTO batch_tasks (task_no, action, status, total_count, success_count, failed_count, site_ids, summary, started_at, finished_at, created_at, updated_at)
        VALUES (:task_no, :action, :status, :total_count, :success_count, :failed_count, :site_ids, :summary, :started_at, :finished_at, :created_at, :updated_at)");
    $stmt->execute([
        'task_no' => $taskNo,
        'action' => $action,
        'status' => $status,
        'total_count' => $total,
        'success_count' => $success,
        'failed_count' => $failed,
        'site_ids' => json_encode(array_values(array_unique($siteIds)), JSON_UNESCAPED_UNICODE),
        'summary' => json_encode($summary, JSON_UNESCAPED_UNICODE),
        'started_at' => (string)($data['started_at'] ?? $now),
        'finished_at' => (string)($data['finished_at'] ?? $now),
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    return fetch_one($main, 'batch_tasks', (int)$main->lastInsertId()) ?: [];
}

function save_deploy_task(PDO $main, array $site, string $action, string $status, array $summary): array
{
    ensure_center_tables($main);
    $now = now();
    $taskNo = 'DT' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $stmt = $main->prepare("INSERT INTO deploy_tasks (task_no, site_id, site_key, site_name, action, status, deploy_mode, deploy_node_id, version_no, package_path, target_path, message, summary, started_at, finished_at, created_at, updated_at)
        VALUES (:task_no, :site_id, :site_key, :site_name, :action, :status, :deploy_mode, :deploy_node_id, :version_no, :package_path, :target_path, :message, :summary, :started_at, :finished_at, :created_at, :updated_at)");
    $stmt->execute([
        'task_no' => $taskNo,
        'site_id' => (int)($site['id'] ?? $summary['site_id'] ?? 10001),
        'site_key' => (string)($site['site_key'] ?? $summary['site_key'] ?? ''),
        'site_name' => mb_substr((string)($site['name'] ?? $summary['site_name'] ?? ''), 0, 120, 'UTF-8'),
        'action' => $action,
        'status' => $status,
        'deploy_mode' => (string)($summary['mode'] ?? $summary['deploy_mode'] ?? ''),
        'deploy_node_id' => !empty($site['deploy_node_id']) ? (int)$site['deploy_node_id'] : null,
        'version_no' => (string)($summary['version_no'] ?? ''),
        'package_path' => (string)($summary['package_path'] ?? ''),
        'target_path' => (string)($summary['site_path'] ?? $summary['target_path'] ?? ''),
        'message' => mb_substr((string)($summary['message'] ?? ''), 0, 500, 'UTF-8'),
        'summary' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'started_at' => (string)($summary['started_at'] ?? $now),
        'finished_at' => (string)($summary['finished_at'] ?? $now),
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    return fetch_one($main, 'deploy_tasks', (int)$main->lastInsertId()) ?: [];
}

function normalize_deploy_task(array $row): array
{
    $summary = json_decode((string)($row['summary'] ?? ''), true);
    $row['summary_json'] = is_array($summary) ? $summary : [];
    $plan = $row['summary_json']['plan'] ?? [];
    $row['steps'] = is_array($plan) && is_array($plan['steps'] ?? null) ? $plan['steps'] : [];
    $row['checks'] = is_array($plan) && is_array($plan['checks'] ?? null) ? $plan['checks'] : [];
    $row['package_file'] = basename((string)($row['package_path'] ?? ($row['summary_json']['package_path'] ?? '')));
    $row['can_retry'] = in_array((string)($row['action'] ?? ''), ['deploy', 'package'], true)
        && in_array((string)($row['status'] ?? ''), ['pending', 'failed'], true);
    return $row;
}

function list_deploy_tasks(PDO $main): array
{
    ensure_center_tables($main);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    $clauses = [];
    $params = [];
    $siteId = trim((string)($_GET['site_id'] ?? ''));
    if ($siteId !== '' && $siteId !== 'all') {
        assert_site_access((int)$siteId, $main);
        $clauses[] = 'site_id = :site_id';
        $params['site_id'] = (int)$siteId;
    } else {
        append_site_scope_clause($clauses, $params);
    }
    foreach (['action', 'status'] as $field) {
        $value = trim((string)($_GET[$field] ?? ''));
        if ($value !== '') {
            $clauses[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
    }
    $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
    $countStmt = $main->prepare("SELECT COUNT(*) FROM deploy_tasks{$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $stmt = $main->prepare("SELECT * FROM deploy_tasks{$whereSql} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute($params);
    return [
        'items' => array_map('normalize_deploy_task', $stmt->fetchAll()),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function get_deploy_task(PDO $main, int $id): array
{
    ensure_center_tables($main);
    $task = fetch_one($main, 'deploy_tasks', $id);
    if (!$task) {
        fail('部署任务不存在', 'NOT_FOUND', 404);
    }
    assert_site_access((int)($task['site_id'] ?? 0), $main);
    return normalize_deploy_task($task);
}

function retry_deploy_task(PDO $main, PDO $pdo, array $currentSite, int $id): array
{
    $task = get_deploy_task($main, $id);
    if ((int)$task['site_id'] !== (int)$currentSite['id']) {
        fail('请先切换到该任务所属站点后再重试', 'SITE_CONTEXT_MISMATCH', 409, [
            'task_site_id' => (int)$task['site_id'],
            'current_site_id' => (int)$currentSite['id'],
        ]);
    }
    $action = (string)($task['action'] ?? '');
    if (!in_array($action, ['deploy', 'package'], true)) {
        fail('该任务类型暂不支持重试', 'TASK_RETRY_UNSUPPORTED', 422);
    }
    if ($action === 'package') {
        $package = create_static_package($currentSite);
        $summary = [
            'site_id' => (int)$currentSite['id'],
            'site_key' => $currentSite['site_key'],
            'site_name' => $currentSite['name'],
            'file_count' => $package['file_count'],
            'file_size' => $package['file_size'],
            'package_path' => $package['file_path'],
            'message' => '发布包重试生成成功，可下载后上传到目标站点目录。',
            'retry_from_task_no' => (string)($task['task_no'] ?? ''),
        ];
        ensure_publish_versions_site_column($pdo);
        $stmt = $pdo->prepare("INSERT INTO publish_versions (site_id, version_no, publish_type, file_path, status, summary, created_at)
            VALUES (:site_id, :version_no, 'package', :file_path, 'success', :summary, :created_at)");
        $stmt->execute([
            'site_id' => (int)$currentSite['id'],
            'version_no' => $package['version_no'],
            'file_path' => $package['file_path'],
            'summary' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);
        $newTask = save_deploy_task($main, $currentSite, 'package', 'success', $summary + ['version_no' => $package['version_no']]);
        return $summary + ['version_no' => $package['version_no'], 'task' => $newTask, 'task_no' => $newTask['task_no'] ?? ''];
    }
    $result = execute_site_deploy($main, $pdo, $currentSite);
    $result['retry_from_task_no'] = (string)($task['task_no'] ?? '');
    return $result;
}

function deploy_config_status(array $site, array $settings): array
{
    $deploy = site_deploy_config($site, (int)($site['id'] ?? 0) === 10001 ? $settings : []);
    $mode = (string)($deploy['mode'] ?? 'manual');
    $sitePath = trim((string)($deploy['site_path'] ?? ''));
    $panelUrl = trim((string)($deploy['bt_panel_url'] ?? ''));
    $configured = $mode === 'local-copy' ? $sitePath !== '' : $sitePath !== '';
    if ($mode === 'bt-api') {
        $configured = $configured && $panelUrl !== '';
    }
    return [$deploy, $configured];
}

function build_deploy_plan(PDO $main, array $site, array $deploy, bool $configured): array
{
    ensure_center_tables($main);
    $mode = (string)($deploy['mode'] ?? 'manual');
    $node = [];
    if (!empty($site['deploy_node_id'])) {
        $node = fetch_one($main, 'deploy_nodes', (int)$site['deploy_node_id']) ?: [];
    }
    $siteKey = (string)($site['site_key'] ?? ('site_' . (int)($site['id'] ?? 10001)));
    $sitePath = trim((string)($deploy['site_path'] ?? ''));
    $panelUrl = trim((string)($deploy['bt_panel_url'] ?? ($node['panel_url'] ?? '')));
    $rootPath = trim((string)($node['root_path'] ?? ''));
    $domain = trim((string)($site['domain'] ?? $site['subdomain'] ?? ''));
    $primaryDomain = primary_site_domain($main, (int)($site['id'] ?? 0));
    $dnsStatus = (string)($primaryDomain['dns_status'] ?? ($domain ? 'pending' : ''));
    $sslStatus = (string)($primaryDomain['ssl_status'] ?? ($domain ? 'pending' : ''));
    $steps = [
        ['key' => 'generate', 'title' => '生成静态站', 'status' => 'ready', 'description' => '从当前站点内容、模板、模块配置生成 HTML/CSS/JS、sitemap 和 search.json。'],
        ['key' => 'snapshot', 'title' => '创建发布版本', 'status' => 'ready', 'description' => '记录发布包、文件数量、发布模式和任务摘要，便于回滚与审计。'],
    ];
    if ($mode === 'local-copy') {
        $steps[] = ['key' => 'backup-target', 'title' => '备份目标目录', 'status' => $sitePath ? 'ready' : 'pending', 'description' => '同步前自动备份 storage/deploy_targets 下的旧静态文件。'];
        $steps[] = ['key' => 'sync-files', 'title' => '复制静态文件', 'status' => $sitePath ? 'ready' : 'pending', 'description' => '把当前 public 静态站复制到本机部署目标目录。'];
    } elseif ($mode === 'bt-api') {
        $steps[] = ['key' => 'bt-connect', 'title' => '连接宝塔面板', 'status' => $panelUrl ? 'ready' : 'pending', 'description' => '使用面板地址和 API Key 连接部署节点。'];
        $steps[] = ['key' => 'bt-site', 'title' => '确认站点目录', 'status' => $sitePath ? 'ready' : 'pending', 'description' => '确认宝塔站点目录存在，后续上传静态文件到该目录。'];
        $steps[] = ['key' => 'bt-upload', 'title' => '上传发布包并解压', 'status' => 'pending', 'description' => '当前版本先生成发布包并记录任务，下一步接入宝塔文件接口自动上传解压。'];
        $steps[] = ['key' => 'bt-ssl', 'title' => 'SSL 与域名检查', 'status' => $domain ? 'pending' : 'blocked', 'description' => '域名绑定后可接入宝塔 SSL 申请与续期接口。'];
    } elseif ($mode === 'ftp') {
        $steps[] = ['key' => 'ftp-package', 'title' => '生成上传包', 'status' => 'ready', 'description' => '生成 tar.gz 发布包，供 FTP/SFTP 执行器上传。'];
        $steps[] = ['key' => 'ftp-sync', 'title' => '执行远程同步', 'status' => 'pending', 'description' => '当前版本记录待执行任务，后续接入 SFTP/Agent 后自动同步。'];
    } else {
        $steps[] = ['key' => 'download', 'title' => '下载发布包', 'status' => 'ready', 'description' => '生成发布包后可人工上传到宝塔、Nginx 或对象存储。'];
        $steps[] = ['key' => 'manual-deploy', 'title' => '人工解压部署', 'status' => $sitePath ? 'ready' : 'pending', 'description' => '在目标站点目录解压发布包并确认首页可访问。'];
    }
    $checks = [
        ['label' => '部署模式', 'ok' => $mode !== '', 'value' => $mode ?: 'manual'],
        ['label' => '站点目录', 'ok' => $sitePath !== '', 'value' => $sitePath ?: '未填写'],
        ['label' => '面板地址', 'ok' => $mode !== 'bt-api' || $panelUrl !== '', 'value' => $panelUrl ?: '未填写'],
        ['label' => '部署节点', 'ok' => empty($site['deploy_node_id']) || !empty($node), 'value' => $node['name'] ?? '未绑定平台节点'],
        ['label' => '根目录', 'ok' => $mode !== 'bt-api' || $rootPath !== '' || $sitePath !== '', 'value' => $rootPath ?: '可由站点目录指定'],
        ['label' => '域名', 'ok' => $domain !== '', 'value' => $domain ?: '未绑定'],
        ['label' => 'DNS', 'ok' => $dnsStatus === 'valid', 'value' => $dnsStatus ?: '未检查'],
        ['label' => 'HTTPS', 'ok' => $sslStatus === 'ready', 'value' => $sslStatus ?: '未检查'],
    ];
    return [
        'mode' => $mode,
        'configured' => $configured,
        'site_path' => $sitePath,
        'panel_url' => $panelUrl,
        'domain' => $domain,
        'domain_status' => [
            'dns_status' => $dnsStatus,
            'ssl_status' => $sslStatus,
            'last_checked_at' => (string)($primaryDomain['last_checked_at'] ?? ''),
            'last_result' => (string)($primaryDomain['last_result'] ?? ''),
        ],
        'node' => $node ? [
            'id' => (int)$node['id'],
            'name' => (string)$node['name'],
            'node_type' => (string)$node['node_type'],
            'server_ip' => (string)($node['server_ip'] ?? ''),
            'root_path' => (string)($node['root_path'] ?? ''),
            'status' => (string)$node['status'],
        ] : null,
        'package_name_hint' => $siteKey . '_package_YYYYMMDD_HHMMSS.tar.gz',
        'checks' => $checks,
        'steps' => $steps,
    ];
}

function execute_site_deploy(PDO $main, PDO $pdo, array $site): array
{
    $settings = site_settings($pdo);
    [$deploy, $configured] = deploy_config_status($site, $settings);
    $plan = build_deploy_plan($main, $site, $deploy, $configured);
    $package = create_static_package($site);
    $mode = (string)($deploy['mode'] ?? 'manual');
    $deployResult = [];
    $status = 'pending';
    $message = '发布包已生成，但部署目标未配置完整，请补齐站点目录和必要的面板地址。';
    if ($configured && $mode === 'local-copy') {
        $deployResult = sync_static_site_to_local_target($site, $deploy);
        $status = 'success';
        $message = '静态站已同步到本机部署目录。';
    } elseif ($configured) {
        $status = 'pending';
        $message = '发布包已生成，当前部署模式需要宝塔 API、FTP/SFTP 或人工上传执行器继续处理。';
    }
    $summary = [
        'site_id' => (int)$site['id'],
        'site_key' => $site['site_key'],
        'site_name' => $site['name'],
        'version_no' => $package['version_no'],
        'file_count' => $package['file_count'],
        'file_size' => $package['file_size'],
        'package_path' => $package['file_path'],
        'configured' => $configured,
        'mode' => $mode,
        'panel_url' => $deploy['bt_panel_url'] ?? '',
        'site_path' => $deploy['site_path'] ?? '',
        'after_action' => $deploy['after_action'] ?? '',
        'message' => $message,
        'plan' => $plan,
    ] + $deployResult;
    ensure_publish_versions_site_column($pdo);
    $stmt = $pdo->prepare("INSERT INTO publish_versions (site_id, version_no, publish_type, file_path, status, summary, created_at)
        VALUES (:site_id, :version_no, 'deploy', :file_path, :status, :summary, :created_at)");
    $stmt->execute([
        'site_id' => (int)$site['id'],
        'version_no' => $package['version_no'],
        'file_path' => $package['file_path'],
        'status' => $status,
        'summary' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_at' => now(),
    ]);
    $task = save_deploy_task($main, $site, 'deploy', $status, $summary);
    return $summary + ['task' => $task, 'task_no' => $task['task_no'] ?? ''];
}

function decode_payment_config(?string $value): array
{
    if (!$value) {
        return [];
    }
    $data = json_decode($value, true);
    return is_array($data) ? decrypt_sensitive_config($data) : [];
}

function payment_channel_site_ids(PDO $main, int $channelId): array
{
    $stmt = $main->prepare('SELECT site_id FROM payment_channel_sites WHERE channel_id = ? ORDER BY site_id ASC');
    $stmt->execute([$channelId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function hydrate_payment_channel(PDO $main, array $row): array
{
    $row['id'] = (int)$row['id'];
    $row['is_default'] = (int)($row['is_default'] ?? 0) === 1;
    $row['config'] = mask_sensitive_config(json_decode((string)($row['config_json'] ?? '{}'), true) ?: []);
    unset($row['config_json']);
    $siteIds = payment_channel_site_ids($main, (int)$row['id']);
    $row['site_ids'] = $siteIds;
    $row['scope'] = $siteIds ? 'selected' : 'all';
    return $row;
}

function list_payment_channels(PDO $main): array
{
    ensure_center_tables($main);
    $rows = $main->query('SELECT * FROM payment_channels ORDER BY is_default DESC, id DESC')->fetchAll();
    return [
        'items' => array_map(fn($row) => hydrate_payment_channel($main, $row), $rows),
    ];
}

function normalize_payment_channel_payload(array $data): array
{
    $name = trim((string)($data['name'] ?? ''));
    if ($name === '') {
        fail('请填写支付通道名称', 'VALIDATION_ERROR', 422);
    }
    $provider = trim((string)($data['provider'] ?? 'manual')) ?: 'manual';
    if (!in_array($provider, ['manual', 'wechat', 'alipay', 'stripe', 'paypal', 'bank'], true)) {
        $provider = 'manual';
    }
    $siteIds = $data['site_ids'] ?? [];
    if (!is_array($siteIds)) {
        $siteIds = [];
    }
    $siteIds = array_values(array_unique(array_filter(array_map('intval', $siteIds), fn($id) => $id > 0)));
    return [
        'name' => $name,
        'provider' => $provider,
        'currency' => strtoupper(trim((string)($data['currency'] ?? 'CNY')) ?: 'CNY'),
        'account' => trim((string)($data['account'] ?? '')),
        'instructions' => trim((string)($data['instructions'] ?? '')),
        'config' => is_array($data['config'] ?? null) ? $data['config'] : [],
        'status' => in_array(($data['status'] ?? 'active'), ['active', 'disabled'], true) ? $data['status'] : 'active',
        'is_default' => !empty($data['is_default']) ? 1 : 0,
        'scope' => ($data['scope'] ?? 'all') === 'selected' ? 'selected' : 'all',
        'site_ids' => $siteIds,
    ];
}

function preserve_masked_sensitive_config($incoming, $current)
{
    if (!is_array($incoming)) {
        return $incoming;
    }
    $result = [];
    foreach ($incoming as $key => $value) {
        if (in_array((string)$key, sensitive_config_keys(), true)) {
            $text = trim((string)$value);
            if ($text === '' || str_starts_with($text, '****')) {
                $result[$key] = is_array($current) && array_key_exists($key, $current) ? $current[$key] : '';
            } else {
                $result[$key] = $text;
            }
            continue;
        }
        $nextCurrent = is_array($current) && is_array($current[$key] ?? null) ? $current[$key] : [];
        $result[$key] = is_array($value)
            ? preserve_masked_sensitive_config($value, $nextCurrent)
            : $value;
    }
    return $result;
}

function sync_payment_channel_sites(PDO $main, int $channelId, string $scope, array $siteIds): void
{
    $main->prepare('DELETE FROM payment_channel_sites WHERE channel_id = ?')->execute([$channelId]);
    if ($scope !== 'selected') {
        return;
    }
    $stmt = $main->prepare('INSERT IGNORE INTO payment_channel_sites (channel_id, site_id, created_at) VALUES (:channel_id, :site_id, :created_at)');
    foreach ($siteIds as $siteId) {
        $stmt->execute([
            'channel_id' => $channelId,
            'site_id' => (int)$siteId,
            'created_at' => now(),
        ]);
    }
}

function save_payment_channel(PDO $main, array $data, ?int $id = null): array
{
    ensure_center_tables($main);
    $payload = normalize_payment_channel_payload($data);
    $current = $id ? fetch_one($main, 'payment_channels', $id) : [];
    $payload['config'] = preserve_masked_sensitive_config($payload['config'], decode_payment_config($current['config_json'] ?? ''));
    $encryptedConfig = encrypt_sensitive_config($payload['config']);
    $time = now();
    if ($payload['is_default']) {
        $main->exec('UPDATE payment_channels SET is_default = 0');
    }
    if ($id) {
        $stmt = $main->prepare("UPDATE payment_channels SET name=:name, provider=:provider, currency=:currency, account=:account, instructions=:instructions, config_json=:config_json, status=:status, is_default=:is_default, updated_at=:updated_at WHERE id=:id");
        $stmt->execute([
            'id' => $id,
            'name' => $payload['name'],
            'provider' => $payload['provider'],
            'currency' => $payload['currency'],
            'account' => $payload['account'],
            'instructions' => $payload['instructions'],
            'config_json' => json_encode($encryptedConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => $payload['status'],
            'is_default' => $payload['is_default'],
            'updated_at' => $time,
        ]);
    } else {
        $stmt = $main->prepare("INSERT INTO payment_channels (name, provider, currency, account, instructions, config_json, status, is_default, created_at, updated_at)
            VALUES (:name, :provider, :currency, :account, :instructions, :config_json, :status, :is_default, :created_at, :updated_at)");
        $stmt->execute([
            'name' => $payload['name'],
            'provider' => $payload['provider'],
            'currency' => $payload['currency'],
            'account' => $payload['account'],
            'instructions' => $payload['instructions'],
            'config_json' => json_encode($encryptedConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => $payload['status'],
            'is_default' => $payload['is_default'],
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        $id = (int)$main->lastInsertId();
    }
    sync_payment_channel_sites($main, (int)$id, $payload['scope'], $payload['site_ids']);
    $item = fetch_one($main, 'payment_channels', (int)$id);
    return $item ? hydrate_payment_channel($main, $item) : [];
}

function apply_payment_channel_to_site(PDO $main, PDO $sitePdo, int $channelId): array
{
    ensure_center_tables($main);
    $item = fetch_one($main, 'payment_channels', $channelId);
    if (!$item || ($item['status'] ?? '') !== 'active') {
        fail('支付通道不存在或已停用', 'NOT_FOUND', 404);
    }
    $channel = hydrate_payment_channel($main, $item);
    $site = site_settings($sitePdo);
    $site['payment'] = array_replace($site['payment'] ?? [], [
        'mode' => $channel['provider'],
        'currency' => $channel['currency'],
        'account' => $channel['account'] ?? '',
        'instructions' => $channel['instructions'] ?? '',
        'guide' => $channel['instructions'] ?? '',
        'channel_id' => $channel['id'],
        'channel_name' => $channel['name'],
        'provider' => $channel['provider'],
    ]);
    return ['channel' => $channel, 'site' => save_site_settings($sitePdo, $site)];
}

function payment_webhook_signature(): string
{
    $headers = [
        $_SERVER['HTTP_X_HJ_SIGNATURE'] ?? '',
        $_SERVER['HTTP_X_PAYMENT_SIGNATURE'] ?? '',
        $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '',
    ];
    foreach ($headers as $header) {
        $signature = trim((string)$header);
        if ($signature !== '') {
            return str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;
        }
    }
    return '';
}

function assert_payment_webhook_signature(array $channel, array $config, string $rawBody): void
{
    $secret = trim((string)($config['webhook_secret'] ?? $config['secret'] ?? ''));
    if ($secret === '') {
        fail('支付通道未配置 webhook_secret，无法接收回调', 'WEBHOOK_SECRET_MISSING', 422);
    }
    $signature = payment_webhook_signature();
    if ($signature === '') {
        fail('缺少支付回调签名', 'WEBHOOK_SIGNATURE_MISSING', 401);
    }
    $expected = hash_hmac('sha256', $rawBody, $secret);
    if (!hash_equals(strtolower($expected), strtolower($signature))) {
        fail('支付回调签名无效', 'WEBHOOK_SIGNATURE_INVALID', 401);
    }
}

function normalize_payment_webhook_status(string $value): string
{
    $value = strtolower(trim($value));
    $map = [
        'success' => 'paid',
        'succeeded' => 'paid',
        'complete' => 'paid',
        'completed' => 'paid',
        'paid' => 'paid',
        'refund' => 'refunded',
        'refunded' => 'refunded',
        'fail' => 'failed',
        'failed' => 'failed',
        'cancel' => 'failed',
        'canceled' => 'failed',
        'pending' => 'pending',
    ];
    return $map[$value] ?? 'pending';
}

function load_payment_webhook_channel(PDO $main, array $data): array
{
    ensure_center_tables($main);
    $channelId = (int)($data['channel_id'] ?? $_GET['channel_id'] ?? 0);
    if ($channelId <= 0) {
        fail('请提供支付通道 channel_id', 'VALIDATION_ERROR', 422);
    }
    $item = fetch_one($main, 'payment_channels', $channelId);
    if (!$item || ($item['status'] ?? '') !== 'active') {
        fail('支付通道不存在或已停用', 'NOT_FOUND', 404);
    }
    $config = decode_payment_config((string)($item['config_json'] ?? ''));
    return [$item, $config];
}

function payment_webhook_event_key(array $channel, array $data, string $rawBody): string
{
    $provider = (string)($channel['provider'] ?? 'manual');
    $transactionId = trim((string)($data['transaction_id'] ?? $data['trade_no'] ?? $data['reference'] ?? ''));
    if ($transactionId !== '') {
        return $provider . ':' . $transactionId;
    }
    return $provider . ':' . trim((string)($data['order_no'] ?? '')) . ':' . substr(hash('sha256', $rawBody), 0, 32);
}

function handle_payment_webhook(PDO $pdo, PDO $main): array
{
    $rawBody = request_raw_body();
    $data = json_decode($rawBody, true);
    if (!is_array($data)) {
        fail('JSON 格式错误', 'INVALID_JSON', 400);
    }
    [$channel, $config] = load_payment_webhook_channel($main, $data);
    assert_payment_webhook_signature($channel, $config, $rawBody);

    $siteId = resolve_request_site_id($data);
    $allowedSiteIds = payment_channel_site_ids($main, (int)$channel['id']);
    if ($allowedSiteIds && !in_array($siteId, $allowedSiteIds, true)) {
        fail('该支付通道未分配给当前站点', 'PAYMENT_CHANNEL_SITE_FORBIDDEN', 403);
    }
    require_fields($data, ['order_no', 'status']);
    assert_public_rate_limit($pdo, $siteId, 'payment.webhook', [(string)$channel['id']], 60, 60);

    $orderNo = trim((string)$data['order_no']);
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_no = :order_no AND site_id = :site_id LIMIT 1');
    $stmt->execute(['order_no' => $orderNo, 'site_id' => $siteId]);
    $order = $stmt->fetch();
    if (!$order) {
        fail('订单不存在', 'ORDER_NOT_FOUND', 404);
    }

    $status = normalize_payment_webhook_status((string)$data['status']);
    $amount = isset($data['amount']) && is_numeric($data['amount']) ? (float)$data['amount'] : (float)($order['total_amount'] ?? 0);
    $currency = strtoupper(trim((string)($data['currency'] ?? ($order['currency'] ?? 'CNY')))) ?: 'CNY';
    if ($amount < 0) {
        fail('支付金额不正确', 'VALIDATION_ERROR', 422);
    }
    if ($status === 'paid' && $amount > 0 && abs($amount - (float)$order['total_amount']) > 0.01) {
        fail('支付金额与订单金额不一致', 'PAYMENT_AMOUNT_MISMATCH', 422);
    }
    if ($currency !== strtoupper((string)($order['currency'] ?? 'CNY'))) {
        fail('支付币种与订单币种不一致', 'PAYMENT_CURRENCY_MISMATCH', 422);
    }

    $transactionId = mb_substr(trim((string)($data['transaction_id'] ?? $data['trade_no'] ?? $data['reference'] ?? '')), 0, 120, 'UTF-8');
    $eventKey = payment_webhook_event_key($channel, $data, $rawBody);
    $existing = null;
    try {
        $insert = $pdo->prepare("INSERT INTO payment_webhook_events (site_id, channel_id, provider, event_key, order_no, transaction_id, payment_status, amount, currency, payload, created_at)
            VALUES (:site_id, :channel_id, :provider, :event_key, :order_no, :transaction_id, :payment_status, :amount, :currency, :payload, :created_at)");
        $insert->execute([
            'site_id' => $siteId,
            'channel_id' => (int)$channel['id'],
            'provider' => (string)$channel['provider'],
            'event_key' => $eventKey,
            'order_no' => $orderNo,
            'transaction_id' => $transactionId,
            'payment_status' => $status,
            'amount' => round($amount, 2),
            'currency' => $currency,
            'payload' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);
        $eventId = (int)$pdo->lastInsertId();
    } catch (Throwable $error) {
        $check = $pdo->prepare('SELECT * FROM payment_webhook_events WHERE event_key = ? LIMIT 1');
        $check->execute([$eventKey]);
        $existing = $check->fetch();
        if (!$existing) {
            throw $error;
        }
        return [
            'duplicate' => true,
            'event_id' => (int)$existing['id'],
            'order' => public_order_view($order),
            'message' => '支付回调已处理，重复事件已忽略',
        ];
    }

    $remark = (string)($order['remark'] ?? '');
    $paidAt = $order['paid_at'] ?? null;
    if ($status === 'paid') {
        $paidAt = $paidAt ?: (trim((string)($data['paid_at'] ?? '')) ?: now());
        $remark = append_order_note($remark, '支付回调确认已支付：' . (string)$channel['name'] . ($transactionId ? '，交易号：' . $transactionId : ''));
    } elseif ($status === 'refunded') {
        if ((int)($order['stock_reserved'] ?? 0) === 1 && restore_order_stock($pdo, $order)) {
            $remark = append_order_note($remark, '支付回调确认退款，系统已回补商品库存');
            $order['stock_reserved'] = 0;
        }
        $remark = append_order_note($remark, '支付回调确认退款：' . (string)$channel['name']);
    } elseif ($status === 'failed') {
        $remark = append_order_note($remark, '支付回调确认支付失败：' . (string)$channel['name']);
    }

    $update = $pdo->prepare('UPDATE orders SET payment_status=:payment_status, paid_at=:paid_at, remark=:remark, stock_reserved=:stock_reserved, updated_at=:updated_at WHERE id=:id');
    $update->execute([
        'id' => (int)$order['id'],
        'payment_status' => $status,
        'paid_at' => $paidAt ?: null,
        'remark' => $remark,
        'stock_reserved' => (int)($order['stock_reserved'] ?? 0),
        'updated_at' => now(),
    ]);
    $pdo->prepare('UPDATE payment_webhook_events SET processed_at = :processed_at WHERE id = :id')
        ->execute(['id' => $eventId, 'processed_at' => now()]);
    $updatedOrder = fetch_one($pdo, 'orders', (int)$order['id']) ?: $order;
    return [
        'duplicate' => false,
        'event_id' => $eventId,
        'order' => public_order_view($updatedOrder),
        'message' => '支付回调已处理',
    ];
}

function list_payment_webhook_events(PDO $pdo, ?PDO $main = null): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    $clauses = [];
    $params = [];
    append_site_scope_clause($clauses, $params, 'site_id', 'pay_event_site');
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    if ($keyword !== '') {
        $clauses[] = '(order_no LIKE :keyword OR transaction_id LIKE :keyword OR event_key LIKE :keyword OR payload LIKE :keyword)';
        $params['keyword'] = '%' . $keyword . '%';
    }
    $status = trim((string)($_GET['payment_status'] ?? ''));
    if ($status !== '') {
        $clauses[] = 'payment_status = :payment_status';
        $params['payment_status'] = $status;
    }
    $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM payment_webhook_events{$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM payment_webhook_events{$whereSql} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute($params);
    $items = array_map(function (array $row) {
        $row['payload_json'] = json_decode((string)($row['payload'] ?? ''), true) ?: null;
        return $row;
    }, $stmt->fetchAll());
    return [
        'items' => attach_site_names($items, $main),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function list_payment_proofs(PDO $pdo, ?PDO $main = null): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    $clauses = [];
    $params = [];
    append_site_scope_clause($clauses, $params, 'site_id', 'pay_proof_site');
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    if ($keyword !== '') {
        $clauses[] = '(order_no LIKE :keyword OR reference LIKE :keyword OR phone LIKE :keyword OR note LIKE :keyword)';
        $params['keyword'] = '%' . $keyword . '%';
    }
    $status = trim((string)($_GET['status'] ?? ''));
    if ($status !== '') {
        $clauses[] = 'status = :status';
        $params['status'] = $status;
    }
    $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM payment_proofs{$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM payment_proofs{$whereSql} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute($params);
    return [
        'items' => attach_site_names($stmt->fetchAll(), $main),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function handle_payment_proof(PDO $pdo, int $id, array $data): array
{
    $proof = fetch_one($pdo, 'payment_proofs', $id);
    if (!$proof) {
        fail('付款凭证不存在', 'NOT_FOUND', 404);
    }
    assert_site_access((int)($proof['site_id'] ?? 10001), main_pdo());
    $action = (string)($data['action'] ?? 'approve');
    if (!in_array($action, ['approve', 'reject'], true)) {
        fail('处理动作不支持', 'VALIDATION_ERROR', 422);
    }
    $adminNote = trim((string)($data['admin_note'] ?? ''));
    $time = now();
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $pdo->prepare('UPDATE payment_proofs SET status=:status, handled_by=:handled_by, handled_at=:handled_at, admin_note=:admin_note, updated_at=:updated_at WHERE id=:id')
        ->execute([
            'id' => $id,
            'status' => $status,
            'handled_by' => (int)(auth_user()['id'] ?? 0) ?: null,
            'handled_at' => $time,
            'admin_note' => $adminNote,
            'updated_at' => $time,
        ]);

    $order = fetch_one($pdo, 'orders', (int)($proof['order_id'] ?? 0));
    if ($order) {
        $message = $action === 'approve'
            ? '后台已审核付款凭证并确认收款：金额 ' . number_format((float)$proof['amount'], 2, '.', '') . '，凭证 ' . (string)$proof['reference']
            : '后台已驳回付款凭证：' . (string)$proof['reference'];
        if ($adminNote !== '') {
            $message .= '；备注 ' . $adminNote;
        }
        $remark = append_order_note((string)($order['remark'] ?? ''), $message);
        if ($action === 'approve') {
            $pdo->prepare("UPDATE orders SET payment_status='paid', paid_at=:paid_at, remark=:remark, updated_at=:updated_at WHERE id=:id")
                ->execute([
                    'id' => (int)$order['id'],
                    'paid_at' => $order['paid_at'] ?: $time,
                    'remark' => $remark,
                    'updated_at' => $time,
                ]);
        } else {
            $pdo->prepare('UPDATE orders SET remark=:remark, updated_at=:updated_at WHERE id=:id')
                ->execute([
                    'id' => (int)$order['id'],
                    'remark' => $remark,
                    'updated_at' => $time,
                ]);
        }
    }
    return [
        'proof' => fetch_one($pdo, 'payment_proofs', $id),
        'order' => $order ? public_order_view(fetch_one($pdo, 'orders', (int)$order['id']) ?: $order) : null,
    ];
}

function ensure_content_distribution_table(PDO $pdo): void
{
    ensure_pages_table($pdo);
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_site_relations (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        content_type VARCHAR(30) NOT NULL,
        content_id BIGINT UNSIGNED NOT NULL,
        site_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uk_content_site (content_type, content_id, site_id),
        INDEX idx_site_id (site_id),
        INDEX idx_content (content_type, content_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    seed_content_distribution($pdo, 'article', 'articles');
    seed_content_distribution($pdo, 'product', 'products');
    seed_content_distribution($pdo, 'page', 'pages');
}

function ensure_pages_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(180) NOT NULL,
        slug VARCHAR(180) NOT NULL UNIQUE,
        cover VARCHAR(255),
        summary TEXT,
        content MEDIUMTEXT,
        seo_title VARCHAR(180),
        seo_keywords VARCHAR(255),
        seo_description TEXT,
        status VARCHAR(30) NOT NULL DEFAULT 'draft',
        published_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_status (status),
        INDEX idx_published_at (published_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function reserved_page_slugs(): array
{
    return ['index', 'contact', 'search', 'order', 'cart', 'news', 'products', 'category', 'product-category', 'assets', 'api', 'admin', 'admin-vue', 's'];
}

function assert_page_slug_available(PDO $pdo, string $slug, ?int $ignoreId = null): void
{
    $slug = trim($slug);
    if ($slug === '' || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/', $slug)) {
        fail('页面 slug 只能使用字母、数字、中划线和下划线', 'VALIDATION_ERROR', 422);
    }
    if (in_array(strtolower($slug), reserved_page_slugs(), true)) {
        fail('页面 slug 与系统页面冲突', 'VALIDATION_ERROR', 422);
    }
    $stmt = $pdo->prepare('SELECT id FROM pages WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $id = (int)($stmt->fetchColumn() ?: 0);
    if ($id > 0 && (!$ignoreId || $id !== $ignoreId)) {
        fail('页面 slug 已存在', 'VALIDATION_ERROR', 422);
    }
}

function seed_content_distribution(PDO $pdo, string $type, string $table): void
{
    $exists = (int)$pdo->query("SELECT COUNT(*) FROM content_site_relations WHERE content_type = " . $pdo->quote($type))->fetchColumn();
    if ($exists > 0) {
        return;
    }
    $ids = $pdo->query("SELECT id FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
    if (!$ids) {
        return;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO content_site_relations (content_type, content_id, site_id, created_at)
        VALUES (:content_type, :content_id, 10001, :created_at)");
    foreach ($ids as $id) {
        $stmt->execute([
            'content_type' => $type,
            'content_id' => (int)$id,
            'created_at' => now(),
        ]);
    }
}

function normalize_site_ids(array $data): array
{
    $scope = (string)($data['site_scope'] ?? $data['distribution_scope'] ?? '');
    if ($scope === 'all') {
        try {
            $main = main_pdo();
            ensure_center_tables($main);
            $allowed = allowed_site_ids_for_user($main);
            if ($allowed !== null) {
                $allowed = array_values(array_unique(array_filter(array_map('intval', $allowed), fn($id) => $id > 0)));
                if ($allowed) {
                    $placeholders = implode(',', array_fill(0, count($allowed), '?'));
                    $stmt = $main->prepare("SELECT id FROM sites WHERE status = 'active' AND id IN ({$placeholders}) ORDER BY id ASC");
                    $stmt->execute($allowed);
                    $activeAllowed = array_values(array_unique(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)), fn($id) => $id > 0)));
                    return $activeAllowed ?: [requested_site_id()];
                }
                return [requested_site_id()];
            }
            $ids = $main->query("SELECT id FROM sites WHERE status = 'active' ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
            if ($ids) {
                return $ids;
            }
        } catch (Throwable $error) {
            return [requested_site_id()];
        }
    }
    if ($scope === 'current') {
        return [requested_site_id()];
    }
    $ids = $data['site_ids'] ?? $data['distribution_site_ids'] ?? [requested_site_id()];
    if (!is_array($ids)) {
        $ids = [$ids];
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
    if ($scope === 'selected' && !$ids) {
        fail('指定站点发布时，请至少选择一个目标站点', 'SITE_SCOPE_EMPTY', 422);
    }
    foreach ($ids as $siteId) {
        assert_site_access((int)$siteId);
    }
    return $ids ?: [requested_site_id()];
}

function sync_content_distribution(PDO $pdo, string $type, int $contentId, array $siteIds): void
{
    ensure_content_distribution_table($pdo);
    $pdo->prepare('DELETE FROM content_site_relations WHERE content_type = ? AND content_id = ?')->execute([$type, $contentId]);
    $stmt = $pdo->prepare("INSERT IGNORE INTO content_site_relations (content_type, content_id, site_id, created_at)
        VALUES (:content_type, :content_id, :site_id, :created_at)");
    foreach ($siteIds as $siteId) {
        $stmt->execute([
            'content_type' => $type,
            'content_id' => $contentId,
            'site_id' => (int)$siteId,
            'created_at' => now(),
        ]);
    }
}

function distribution_map(PDO $pdo, string $type, array $ids): array
{
    ensure_content_distribution_table($pdo);
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
    if (!$ids) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT content_id, site_id FROM content_site_relations WHERE content_type = ? AND content_id IN ({$placeholders}) ORDER BY site_id ASC");
    $stmt->execute(array_merge([$type], $ids));
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $contentId = (int)$row['content_id'];
        $map[$contentId] ??= [];
        $map[$contentId][] = (int)$row['site_id'];
    }
    return $map;
}

function attach_distribution(PDO $pdo, string $type, array $items): array
{
    $map = distribution_map($pdo, $type, array_column($items, 'id'));
    return array_map(function (array $item) use ($map) {
        $item['site_ids'] = $map[(int)$item['id']] ?? [10001];
        return $item;
    }, $items);
}

function ensure_article_tag_tables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS tags (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(80) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uk_tag_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS article_tags (
        article_id BIGINT UNSIGNED NOT NULL,
        tag_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (article_id, tag_id),
        INDEX idx_tag_id (tag_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function normalize_tag_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/\s+/u', '-', $slug);
    $slug = preg_replace('/[^\p{L}\p{N}_-]+/u', '-', (string)$slug);
    $slug = trim((string)$slug, '-_');
    if ($slug === '') {
        $slug = 'tag-' . substr(md5($value), 0, 8);
    }
    return mb_substr($slug, 0, 100, 'UTF-8');
}

function normalize_tag_names(array|string|null $tags): array
{
    if (is_string($tags)) {
        $tags = preg_split('/[,，\n\r]+/u', $tags) ?: [];
    }
    if (!is_array($tags)) {
        return [];
    }
    $names = [];
    foreach ($tags as $tag) {
        $name = trim((string)$tag);
        if ($name === '') {
            continue;
        }
        $name = mb_substr($name, 0, 80, 'UTF-8');
        $names[$name] = $name;
    }
    return array_values($names);
}

function sync_article_tags(PDO $pdo, int $articleId, array|string|null $tags): void
{
    ensure_article_tag_tables($pdo);
    $names = normalize_tag_names($tags);
    $pdo->prepare('DELETE FROM article_tags WHERE article_id = ?')->execute([$articleId]);
    if (!$names) {
        return;
    }
    $time = now();
    $select = $pdo->prepare('SELECT id FROM tags WHERE slug = ? OR name = ? LIMIT 1');
    $insertTag = $pdo->prepare("INSERT INTO tags (name, slug, description, created_at, updated_at)
        VALUES (:name, :slug, '', :created_at, :updated_at)");
    $insertRel = $pdo->prepare("INSERT IGNORE INTO article_tags (article_id, tag_id, created_at)
        VALUES (:article_id, :tag_id, :created_at)");
    foreach ($names as $name) {
        $slug = normalize_tag_slug($name);
        $select->execute([$slug, $name]);
        $tagId = (int)($select->fetchColumn() ?: 0);
        if (!$tagId) {
            $insertTag->execute([
                'name' => $name,
                'slug' => $slug,
                'created_at' => $time,
                'updated_at' => $time,
            ]);
            $tagId = (int)$pdo->lastInsertId();
        }
        $insertRel->execute([
            'article_id' => $articleId,
            'tag_id' => $tagId,
            'created_at' => $time,
        ]);
    }
}

function article_tag_map(PDO $pdo, array $ids): array
{
    ensure_article_tag_tables($pdo);
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
    if (!$ids) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT at.article_id, t.id, t.name, t.slug
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
        ];
    }
    return $map;
}

function attach_article_tags(PDO $pdo, array $items): array
{
    $map = article_tag_map($pdo, array_column($items, 'id'));
    return array_map(function (array $item) use ($map) {
        $tags = $map[(int)$item['id']] ?? [];
        $item['tags'] = $tags;
        $item['tag_names'] = array_map(fn($tag) => $tag['name'], $tags);
        return $item;
    }, $items);
}

function distribution_site_counts(PDO $pdo, string $type): array
{
    try {
        $stmt = $pdo->prepare('SELECT site_id, COUNT(*) AS total FROM content_site_relations WHERE content_type = ? GROUP BY site_id');
        $stmt->execute([$type]);
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int)$row['site_id']] = (int)$row['total'];
        }
        return $counts;
    } catch (Throwable $error) {
        return [];
    }
}

function requested_site_id(): int
{
    $siteId = (int)($_SERVER['HTTP_X_SITE_ID'] ?? 10001);
    assert_site_access($siteId);
    return $siteId > 0 ? $siteId : 10001;
}

function current_site(PDO $main, PDO $sitePdo): array
{
    center_site_items($main, $sitePdo);
    $stmt = $main->prepare('SELECT * FROM sites WHERE id = ? LIMIT 1');
    $stmt->execute([requested_site_id()]);
    $site = $stmt->fetch();
    if ($site) {
        return $site;
    }

    $stmt = $main->prepare('SELECT * FROM sites WHERE id = 10001 LIMIT 1');
    $stmt->execute();
    $site = $stmt->fetch();
    if ($site) {
        return $site;
    }
    fail('站点不存在', 'SITE_NOT_FOUND', 404);
}

function ensure_publish_versions_site_column(PDO $pdo): void
{
    ensure_column($pdo, 'publish_versions', 'site_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 10001');
}

function ensure_media_site_column(PDO $pdo): void
{
    ensure_column($pdo, 'media', 'site_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 10001');
}

function list_publish_versions(PDO $pdo, int $siteId): array
{
    ensure_publish_versions_site_column($pdo);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM publish_versions WHERE site_id = ?');
    $countStmt->execute([$siteId]);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM publish_versions WHERE site_id = ? ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute([$siteId]);

    return [
        'items' => $stmt->fetchAll(),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function create_publish_snapshot(PDO $pdo, array $site, string $versionNo, string $publicPath, array $output): array
{
    $snapshotRoot = publish_version_root($site) . DIRECTORY_SEPARATOR . $versionNo;
    remove_dir_contents($snapshotRoot);
    $fileCount = copy_directory($publicPath, $snapshotRoot);
    $summary = [
        'site_id' => (int)$site['id'],
        'site_key' => $site['site_key'],
        'site_name' => $site['name'],
        'file_count' => $fileCount,
        'snapshot_path' => str_replace(DIRECTORY_SEPARATOR, '/', substr($snapshotRoot, strlen(dirname(__DIR__, 2)) + 1)),
        'output' => $output,
    ];
    ensure_publish_versions_site_column($pdo);
    $stmt = $pdo->prepare("INSERT INTO publish_versions (site_id, version_no, publish_type, file_path, status, summary, created_at)
        VALUES (:site_id, :version_no, 'generate', :file_path, 'success', :summary, :created_at)");
    $stmt->execute([
        'site_id' => (int)$site['id'],
        'version_no' => $versionNo,
        'file_path' => str_replace(DIRECTORY_SEPARATOR, '/', site_public_path($site)),
        'summary' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_at' => now(),
    ]);
    return $summary;
}

function rollback_publish_version(PDO $pdo, array $site, int $versionId): array
{
    ensure_publish_versions_site_column($pdo);
    $stmt = $pdo->prepare("SELECT * FROM publish_versions WHERE id = ? AND site_id = ? AND publish_type = 'generate' AND status = 'success' LIMIT 1");
    $stmt->execute([$versionId, (int)$site['id']]);
    $version = $stmt->fetch();
    if (!$version) {
        fail('可回滚版本不存在', 'VERSION_NOT_FOUND', 404);
    }
    $summary = json_decode((string)($version['summary'] ?? '{}'), true);
    $snapshotRelative = (string)($summary['snapshot_path'] ?? '');
    if ($snapshotRelative === '' || str_contains($snapshotRelative, '..')) {
        fail('版本快照不存在', 'SNAPSHOT_MISSING', 404);
    }
    $snapshotRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $snapshotRelative);
    $publicRoot = site_public_root($site);
    if (!is_dir($snapshotRoot)) {
        fail('版本快照不存在', 'SNAPSHOT_MISSING', 404);
    }
    remove_dir_contents($publicRoot);
    $fileCount = copy_directory($snapshotRoot, $publicRoot);
    $rollbackNo = (string)$site['site_key'] . '_rollback_' . date('Ymd_His');
    $rollbackSummary = [
        'site_id' => (int)$site['id'],
        'site_key' => $site['site_key'],
        'site_name' => $site['name'],
        'rollback_from' => $version['version_no'],
        'rollback_version_id' => (int)$version['id'],
        'file_count' => $fileCount,
        'snapshot_path' => $snapshotRelative,
        'message' => '已回滚到版本 ' . $version['version_no'],
    ];
    $insert = $pdo->prepare("INSERT INTO publish_versions (site_id, version_no, publish_type, file_path, status, summary, created_at)
        VALUES (:site_id, :version_no, 'rollback', :file_path, 'success', :summary, :created_at)");
    $insert->execute([
        'site_id' => (int)$site['id'],
        'version_no' => $rollbackNo,
        'file_path' => str_replace(DIRECTORY_SEPARATOR, '/', site_public_path($site)),
        'summary' => json_encode($rollbackSummary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_at' => now(),
    ]);
    return $rollbackSummary + ['version_no' => $rollbackNo];
}

function ensure_site_backups_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_backups (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 10001,
        backup_no VARCHAR(120) NOT NULL,
        backup_type VARCHAR(40) NOT NULL DEFAULT 'manual',
        snapshot_path VARCHAR(255) NOT NULL,
        file_count INT UNSIGNED NOT NULL DEFAULT 0,
        file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
        status VARCHAR(30) NOT NULL DEFAULT 'success',
        summary TEXT,
        created_at DATETIME NOT NULL,
        restored_at DATETIME,
        UNIQUE KEY uk_backup_no (backup_no),
        INDEX idx_site_id (site_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensure_column($pdo, 'site_backups', 'site_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 10001');
    ensure_column($pdo, 'site_backups', 'backup_type', "VARCHAR(40) NOT NULL DEFAULT 'manual'");
    ensure_column($pdo, 'site_backups', 'file_size', 'BIGINT UNSIGNED NOT NULL DEFAULT 0');
    ensure_column($pdo, 'site_backups', 'restored_at', 'DATETIME');
}

function normalize_site_backup(array $row): array
{
    $row['id'] = (int)($row['id'] ?? 0);
    $row['site_id'] = (int)($row['site_id'] ?? 10001);
    $row['file_count'] = (int)($row['file_count'] ?? 0);
    $row['file_size'] = (int)($row['file_size'] ?? 0);
    $summary = json_decode((string)($row['summary'] ?? ''), true);
    $row['summary_json'] = is_array($summary) ? $summary : [];
    return $row;
}

function list_site_backups(PDO $pdo, int $siteId): array
{
    ensure_site_backups_table($pdo);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    $count = $pdo->prepare('SELECT COUNT(*) FROM site_backups WHERE site_id = ?');
    $count->execute([$siteId]);
    $total = (int)$count->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM site_backups WHERE site_id = ? ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute([$siteId]);
    return [
        'items' => array_map('normalize_site_backup', $stmt->fetchAll()),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function create_site_backup(PDO $pdo, array $site, string $backupType = 'manual', string $message = ''): array
{
    ensure_site_backups_table($pdo);
    $publicRoot = site_public_root($site);
    if (!is_dir($publicRoot)) {
        fail('请先生成静态站，再创建备份', 'STATIC_SITE_MISSING', 422);
    }
    $backupNo = (string)$site['site_key'] . '_backup_' . date('Ymd_His') . '_' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    $snapshotRoot = site_backup_root($site) . DIRECTORY_SEPARATOR . $backupNo;
    remove_dir_contents($snapshotRoot);
    $fileCount = copy_directory($publicRoot, $snapshotRoot);
    $fileSize = directory_size($snapshotRoot);
    $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($snapshotRoot, strlen(dirname(__DIR__, 2)) + 1));
    $summary = [
        'site_id' => (int)$site['id'],
        'site_key' => (string)$site['site_key'],
        'site_name' => (string)$site['name'],
        'backup_type' => $backupType,
        'file_count' => $fileCount,
        'file_size' => $fileSize,
        'snapshot_path' => $relative,
        'message' => $message ?: '站点静态文件备份已创建',
    ];
    $stmt = $pdo->prepare("INSERT INTO site_backups (site_id, backup_no, backup_type, snapshot_path, file_count, file_size, status, summary, created_at)
        VALUES (:site_id, :backup_no, :backup_type, :snapshot_path, :file_count, :file_size, 'success', :summary, :created_at)");
    $stmt->execute([
        'site_id' => (int)$site['id'],
        'backup_no' => $backupNo,
        'backup_type' => $backupType,
        'snapshot_path' => $relative,
        'file_count' => $fileCount,
        'file_size' => $fileSize,
        'summary' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_at' => now(),
    ]);
    return normalize_site_backup(fetch_one($pdo, 'site_backups', (int)$pdo->lastInsertId()) ?: []);
}

function restore_site_backup(PDO $pdo, array $site, int $backupId): array
{
    ensure_site_backups_table($pdo);
    $stmt = $pdo->prepare("SELECT * FROM site_backups WHERE id = ? AND site_id = ? AND status = 'success' LIMIT 1");
    $stmt->execute([$backupId, (int)$site['id']]);
    $backup = $stmt->fetch();
    if (!$backup) {
        fail('备份不存在或不可恢复', 'BACKUP_NOT_FOUND', 404);
    }
    $snapshotRelative = (string)($backup['snapshot_path'] ?? '');
    if ($snapshotRelative === '' || str_contains($snapshotRelative, '..')) {
        fail('备份快照路径不安全', 'INVALID_BACKUP_PATH', 422);
    }
    $snapshotRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $snapshotRelative);
    if (!is_dir($snapshotRoot)) {
        fail('备份快照不存在', 'BACKUP_SNAPSHOT_MISSING', 404);
    }
    $preRestore = create_site_backup($pdo, $site, 'before-restore', '恢复备份前自动保存当前站点状态');
    $publicRoot = site_public_root($site);
    remove_dir_contents($publicRoot);
    $fileCount = copy_directory($snapshotRoot, $publicRoot);
    $fileSize = directory_size($publicRoot);
    $pdo->prepare('UPDATE site_backups SET restored_at = :restored_at WHERE id = :id')
        ->execute(['id' => $backupId, 'restored_at' => now()]);
    $restoreNo = (string)$site['site_key'] . '_restore_' . date('Ymd_His');
    $summary = [
        'site_id' => (int)$site['id'],
        'site_key' => (string)$site['site_key'],
        'site_name' => (string)$site['name'],
        'restore_from' => (string)$backup['backup_no'],
        'restore_backup_id' => (int)$backup['id'],
        'pre_restore_backup_no' => $preRestore['backup_no'] ?? '',
        'file_count' => $fileCount,
        'file_size' => $fileSize,
        'message' => '已恢复站点备份 ' . $backup['backup_no'],
    ];
    ensure_publish_versions_site_column($pdo);
    $insert = $pdo->prepare("INSERT INTO publish_versions (site_id, version_no, publish_type, file_path, status, summary, created_at)
        VALUES (:site_id, :version_no, 'restore', :file_path, 'success', :summary, :created_at)");
    $insert->execute([
        'site_id' => (int)$site['id'],
        'version_no' => $restoreNo,
        'file_path' => str_replace(DIRECTORY_SEPARATOR, '/', site_public_path($site)),
        'summary' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_at' => now(),
    ]);
    return $summary + ['version_no' => $restoreNo, 'pre_restore_backup' => $preRestore];
}

function delete_site_backup(PDO $pdo, array $site, int $backupId): array
{
    ensure_site_backups_table($pdo);
    $stmt = $pdo->prepare('SELECT * FROM site_backups WHERE id = ? AND site_id = ? LIMIT 1');
    $stmt->execute([$backupId, (int)$site['id']]);
    $backup = $stmt->fetch();
    if (!$backup) {
        fail('备份不存在', 'BACKUP_NOT_FOUND', 404);
    }
    $snapshotRelative = (string)($backup['snapshot_path'] ?? '');
    if ($snapshotRelative !== '' && !str_contains($snapshotRelative, '..')) {
        $snapshotRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $snapshotRelative);
        remove_directory_tree($snapshotRoot);
    }
    $pdo->prepare('DELETE FROM site_backups WHERE id = ? AND site_id = ?')->execute([$backupId, (int)$site['id']]);
    return ['id' => $backupId];
}

function public_order_view(array $order): array
{
    $items = [];
    try {
        $items = json_decode((string)($order['items'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $error) {
        $items = [];
    }

    return [
        'order_no' => $order['order_no'] ?? '',
        'customer_name' => $order['customer_name'] ?? '',
        'items' => is_array($items) ? $items : [],
        'total_amount' => $order['total_amount'] ?? '0.00',
        'currency' => $order['currency'] ?? 'CNY',
        'payment_status' => $order['payment_status'] ?? 'pending',
        'fulfillment_status' => $order['fulfillment_status'] ?? 'new',
        'tracking_company' => $order['tracking_company'] ?? '',
        'tracking_no' => $order['tracking_no'] ?? '',
        'paid_at' => $order['paid_at'] ?? '',
        'shipped_at' => $order['shipped_at'] ?? '',
        'service_timeline' => public_order_timeline((string)($order['remark'] ?? '')),
        'created_at' => $order['created_at'] ?? '',
        'updated_at' => $order['updated_at'] ?? '',
    ];
}

function rate_limit_key(int $siteId, string $action, array $parts = []): string
{
    $ip = client_ip();
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 120);
    $payload = json_encode([$siteId, $action, $ip, $ua, $parts], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return hash('sha256', (string)$payload);
}

function assert_public_rate_limit(PDO $pdo, int $siteId, string $action, array $parts = [], int $limit = 10, int $windowSeconds = 60): void
{
    $key = rate_limit_key($siteId, $action, $parts);
    $nowTs = time();
    $windowStartTs = $nowTs - $windowSeconds;
    $now = date('Y-m-d H:i:s', $nowTs);
    $pdo->prepare('DELETE FROM api_rate_limits WHERE updated_at < ?')->execute([date('Y-m-d H:i:s', $nowTs - 86400)]);
    $stmt = $pdo->prepare('SELECT * FROM api_rate_limits WHERE rate_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) {
        $insert = $pdo->prepare("INSERT INTO api_rate_limits (rate_key, site_id, action, counter, window_started_at, updated_at)
            VALUES (:rate_key, :site_id, :action, 1, :window_started_at, :updated_at)");
        $insert->execute([
            'rate_key' => $key,
            'site_id' => $siteId,
            'action' => $action,
            'window_started_at' => $now,
            'updated_at' => $now,
        ]);
        return;
    }
    $started = strtotime((string)($row['window_started_at'] ?? '')) ?: 0;
    $counter = (int)($row['counter'] ?? 0);
    if ($started < $windowStartTs) {
        $update = $pdo->prepare('UPDATE api_rate_limits SET counter = 1, window_started_at = :window_started_at, updated_at = :updated_at WHERE rate_key = :rate_key');
        $update->execute(['rate_key' => $key, 'window_started_at' => $now, 'updated_at' => $now]);
        return;
    }
    if ($counter >= $limit) {
        $retryAfter = max(1, $windowSeconds - max(0, $nowTs - $started));
        header('Retry-After: ' . $retryAfter);
        fail('操作过于频繁，请稍后再试', 'RATE_LIMITED', 429, ['retry_after' => $retryAfter]);
    }
    $update = $pdo->prepare('UPDATE api_rate_limits SET counter = counter + 1, updated_at = :updated_at WHERE rate_key = :rate_key');
    $update->execute(['rate_key' => $key, 'updated_at' => $now]);
}

function list_orders(PDO $pdo, ?PDO $main = null): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    $paymentStatus = trim((string)($_GET['payment_status'] ?? ''));
    $fulfillmentStatus = trim((string)($_GET['fulfillment_status'] ?? ''));

    $keywordClause = '';
    $keywordParams = [];
    if ($keyword !== '') {
        $keywordClause = '(order_no LIKE :keyword OR customer_name LIKE :keyword OR phone LIKE :keyword OR email LIKE :keyword OR tracking_company LIKE :keyword OR tracking_no LIKE :keyword OR source_url LIKE :keyword OR remark LIKE :keyword OR items LIKE :keyword)';
        $keywordParams['keyword'] = '%' . $keyword . '%';
    }

    $clauses = [];
    $params = [];
    if ($keywordClause !== '') {
        $clauses[] = $keywordClause;
        $params = array_merge($params, $keywordParams);
    }
    if ($paymentStatus !== '') {
        $clauses[] = 'payment_status = :payment_status';
        $params['payment_status'] = $paymentStatus;
    }
    if ($fulfillmentStatus !== '') {
        $clauses[] = 'fulfillment_status = :fulfillment_status';
        $params['fulfillment_status'] = $fulfillmentStatus;
    }
    append_site_scope_clause($clauses, $params);

    $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders{$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM orders{$whereSql} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute($params);

    $statsStmt = $pdo->prepare("SELECT
        COUNT(*) AS total,
        SUM(payment_status = 'pending') AS pending_payment,
        SUM(payment_status = 'paid') AS paid,
        SUM(fulfillment_status = 'new') AS new_orders,
        SUM(fulfillment_status IN ('new', 'confirmed')) AS open_orders,
        SUM(fulfillment_status = 'finished') AS finished,
        SUM(total_amount) AS total_amount
        FROM orders{$whereSql}");
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch() ?: [];

    return [
        'items' => attach_site_names($stmt->fetchAll(), $main),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
        'stats' => [
            'total' => (int)($stats['total'] ?? 0),
            'pending_payment' => (int)($stats['pending_payment'] ?? 0),
            'paid' => (int)($stats['paid'] ?? 0),
            'new_orders' => (int)($stats['new_orders'] ?? 0),
            'open_orders' => (int)($stats['open_orders'] ?? 0),
            'finished' => (int)($stats['finished'] ?? 0),
            'total_amount' => (float)($stats['total_amount'] ?? 0),
        ],
    ];
}

function list_form_submissions(PDO $pdo, ?PDO $main = null): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $clauses = [];
    $params = [];
    if ($keyword !== '') {
        $clauses[] = '(form_key LIKE :keyword OR source_url LIKE :keyword OR data LIKE :keyword OR remark LIKE :keyword)';
        $params['keyword'] = '%' . $keyword . '%';
    }
    if ($status !== '') {
        $clauses[] = 'status = :status';
        $params['status'] = $status;
    }
    append_site_scope_clause($clauses, $params);
    $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM form_submissions{$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM form_submissions{$whereSql} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute($params);
    return [
        'items' => attach_site_names($stmt->fetchAll(), $main),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function export_orders_csv(PDO $pdo): void
{
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    $paymentStatus = trim((string)($_GET['payment_status'] ?? ''));
    $fulfillmentStatus = trim((string)($_GET['fulfillment_status'] ?? ''));
    $siteId = requested_site_filter();

    $clauses = [];
    $params = [];
    if ($keyword !== '') {
        $clauses[] = '(order_no LIKE :keyword OR customer_name LIKE :keyword OR phone LIKE :keyword OR email LIKE :keyword OR tracking_company LIKE :keyword OR tracking_no LIKE :keyword OR source_url LIKE :keyword OR remark LIKE :keyword OR items LIKE :keyword)';
        $params['keyword'] = '%' . $keyword . '%';
    }
    if ($paymentStatus !== '') {
        $clauses[] = 'payment_status = :payment_status';
        $params['payment_status'] = $paymentStatus;
    }
    if ($fulfillmentStatus !== '') {
        $clauses[] = 'fulfillment_status = :fulfillment_status';
        $params['fulfillment_status'] = $fulfillmentStatus;
    }
    if ($siteId) {
        $clauses[] = 'site_id = :site_id';
        $params['site_id'] = $siteId;
    }

    $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
    $stmt = $pdo->prepare("SELECT * FROM orders{$whereSql} ORDER BY id DESC LIMIT 1000");
    $stmt->execute($params);

    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders-' . date('Ymd-His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['订单号', '客户', '手机', '邮箱', '金额', '币种', '支付状态', '履约状态', '物流公司', '物流单号', '地址', '来源', '创建时间', '更新时间', '备注']);
    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['order_no'] ?? '',
            $row['customer_name'] ?? '',
            $row['phone'] ?? '',
            $row['email'] ?? '',
            $row['total_amount'] ?? '',
            $row['currency'] ?? '',
            $row['payment_status'] ?? '',
            $row['fulfillment_status'] ?? '',
            $row['tracking_company'] ?? '',
            $row['tracking_no'] ?? '',
            $row['address'] ?? '',
            $row['source_url'] ?? '',
            $row['created_at'] ?? '',
            $row['updated_at'] ?? '',
            preg_replace('/\s+/', ' ', (string)($row['remark'] ?? '')),
        ]);
    }
    fclose($out);
    exit;
}

function read_site_settings_key(PDO $pdo, string $key): array
{
    $stmt = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    if (!$value) {
        return [];
    }
    $data = json_decode((string)$value, true);
    if (!is_array($data)) {
        return [];
    }
    if (isset($data['ai']) && is_array($data['ai'])) {
        $data['ai'] = decrypt_secret_array($data['ai'], ['api_key']);
    }
    return $data;
}

function site_settings_key(int $siteId): string
{
    return $siteId === 10001 ? 'site' : 'site_' . $siteId;
}

function site_settings(PDO $pdo, ?int $siteId = null): array
{
    $siteId = $siteId ?: requested_site_id();
    $base = read_site_settings_key($pdo, 'site');
    $settings = $base;
    if ($siteId !== 10001) {
        $override = read_site_settings_key($pdo, site_settings_key($siteId));
        if ($override) {
            $settings = array_replace_recursive($base, $override);
        }
    }
    $settings['site_id'] = $siteId;
    $settings['settings_scope'] = $siteId === 10001 ? 'default' : 'site';
    $settings['has_site_override'] = $siteId === 10001 ? true : (bool)read_site_settings_key($pdo, site_settings_key($siteId));
    return $settings;
}

function save_site_settings(PDO $pdo, array $settings, ?int $siteId = null): array
{
    $siteId = $siteId ?: requested_site_id();
    unset($settings['settings_scope'], $settings['has_site_override'], $settings['_preserve_service_configs']);
    $current = site_settings($pdo, $siteId);
    if (isset($settings['ai']) && is_array($settings['ai'])) {
        if (array_key_exists('api_key', $settings['ai'])) {
            $incomingKey = trim((string)$settings['ai']['api_key']);
            if ($incomingKey === '' && !empty($current['ai']['api_key'])) {
                $settings['ai']['api_key'] = (string)$current['ai']['api_key'];
            }
        } elseif (!empty($current['ai']['api_key'])) {
            $settings['ai']['api_key'] = (string)$current['ai']['api_key'];
        }
        $settings['ai'] = encrypt_secret_array($settings['ai'], ['api_key']);
    }
    $settings['site_id'] = $siteId;
    $settings['updated_at'] = now();
    $stmt = $pdo->prepare("REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES (:key, :value, :updated_at)");
    $stmt->execute([
        'key' => site_settings_key($siteId),
        'value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'updated_at' => $settings['updated_at'],
    ]);
    return site_settings($pdo, $siteId);
}

function sanitize_site_settings_for_response(array $settings): array
{
    if (isset($settings['ai']) && is_array($settings['ai'])) {
        $settings['ai']['api_key_masked'] = mask_secret((string)($settings['ai']['api_key'] ?? ''));
        unset($settings['ai']['api_key']);
    }
    return $settings;
}

function preserve_service_configs(array $incoming, array $current): array
{
    unset($incoming['_preserve_service_configs']);
    foreach (['ai', 'payment', 'deploy'] as $key) {
        if (array_key_exists($key, $current)) {
            $incoming[$key] = $current[$key];
        } else {
            unset($incoming[$key]);
        }
    }
    return $incoming;
}

function apply_site_settings_to_all(PDO $main, PDO $sitePdo, array $settings): array
{
    ensure_center_tables($main);
    $preserveServiceConfigs = !empty($settings['_preserve_service_configs']);
    unset($settings['_preserve_service_configs']);
    $sites = $main->query('SELECT id FROM sites ORDER BY id ASC')->fetchAll();
    $count = 0;
    foreach ($sites as $site) {
        $siteId = (int)$site['id'];
        $payload = $preserveServiceConfigs ? preserve_service_configs($settings, site_settings($sitePdo, $siteId)) : $settings;
        save_site_settings($sitePdo, $payload, $siteId);
        $count++;
    }
    return ['count' => $count, 'site_ids' => array_map(fn($site) => (int)$site['id'], $sites)];
}

function site_static_pages(PDO $pdo): array
{
    ensure_pages_table($pdo);
    $siteId = requested_site_id();
    $items = [
        ['type' => 'system', 'title' => '首页', 'url' => 'index.html'],
        ['type' => 'system', 'title' => '行业资讯', 'url' => 'news/index.html'],
        ['type' => 'system', 'title' => '产品中心', 'url' => 'products/index.html'],
        ['type' => 'system', 'title' => '联系我们', 'url' => 'contact.html'],
        ['type' => 'system', 'title' => '搜索', 'url' => 'search.html'],
        ['type' => 'system', 'title' => '查订单', 'url' => 'order.html'],
        ['type' => 'system', 'title' => '购物车', 'url' => 'cart.html'],
    ];

    $relationExists = (bool)$pdo->query("SHOW TABLES LIKE 'content_site_relations'")->fetchColumn();
    $articleSql = "SELECT id, title, slug FROM articles WHERE status = 'published'";
    $productSql = "SELECT id, title, slug FROM products WHERE status = 'published'";
    $pageSql = "SELECT id, title, slug FROM pages WHERE status = 'published'";
    $params = [];

    if ($relationExists) {
        $articleHasRelations = (int)$pdo->query("SELECT COUNT(*) FROM content_site_relations WHERE content_type = 'article'")->fetchColumn();
        if ($articleHasRelations > 0) {
            $articleSql .= " AND id IN (SELECT content_id FROM content_site_relations WHERE content_type = 'article' AND site_id = :article_site_id)";
            $params['article_site_id'] = $siteId;
        }
        $productHasRelations = (int)$pdo->query("SELECT COUNT(*) FROM content_site_relations WHERE content_type = 'product'")->fetchColumn();
        if ($productHasRelations > 0) {
            $productSql .= " AND id IN (SELECT content_id FROM content_site_relations WHERE content_type = 'product' AND site_id = :product_site_id)";
            $params['product_site_id'] = $siteId;
        }
        $pageHasRelations = (int)$pdo->query("SELECT COUNT(*) FROM content_site_relations WHERE content_type = 'page'")->fetchColumn();
        if ($pageHasRelations > 0) {
            $pageSql .= " AND id IN (SELECT content_id FROM content_site_relations WHERE content_type = 'page' AND site_id = :page_site_id)";
            $params['page_site_id'] = $siteId;
        }
    }

    $pageSql .= ' ORDER BY id DESC LIMIT 200';
    $pageStmt = $pdo->prepare($pageSql);
    $pageStmt->execute(array_filter($params, fn($key) => $key === 'page_site_id', ARRAY_FILTER_USE_KEY));
    foreach ($pageStmt->fetchAll() as $row) {
        $items[] = ['type' => 'custom', 'title' => $row['title'], 'url' => $row['slug'] . '.html'];
    }

    $categoryRows = $pdo->query('SELECT id, name, slug FROM categories ORDER BY sort_order ASC, id ASC LIMIT 200')->fetchAll();
    foreach ($categoryRows as $row) {
        $items[] = ['type' => 'article_category', 'title' => $row['name'], 'url' => 'category/' . $row['slug'] . '/index.html'];
    }

    $articleSql .= ' ORDER BY published_at DESC, id DESC LIMIT 200';
    $articleStmt = $pdo->prepare($articleSql);
    $articleStmt->execute(array_filter($params, fn($key) => $key === 'article_site_id', ARRAY_FILTER_USE_KEY));
    foreach ($articleStmt->fetchAll() as $row) {
        $items[] = ['type' => 'article', 'title' => $row['title'], 'url' => 'news/' . $row['slug'] . '.html'];
    }

    $productCategoryRows = $pdo->query('SELECT id, name, slug FROM product_categories ORDER BY sort_order ASC, id ASC LIMIT 200')->fetchAll();
    foreach ($productCategoryRows as $row) {
        $items[] = ['type' => 'product_category', 'title' => $row['name'], 'url' => 'product-category/' . $row['slug'] . '/index.html'];
    }

    $productSql .= ' ORDER BY id DESC LIMIT 200';
    $productStmt = $pdo->prepare($productSql);
    $productStmt->execute(array_filter($params, fn($key) => $key === 'product_site_id', ARRAY_FILTER_USE_KEY));
    foreach ($productStmt->fetchAll() as $row) {
        $items[] = ['type' => 'product', 'title' => $row['title'], 'url' => 'products/' . $row['slug'] . '.html'];
    }

    return ['site_id' => $siteId, 'items' => $items];
}

function seo_text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen(trim($value), 'UTF-8') : strlen(trim($value));
}

function seo_plain_text(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', strip_tags($value)));
}

function seo_slug_ok(string $slug): bool
{
    return (bool)preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$/', $slug) || (bool)preg_match('/^[a-z0-9]$/', $slug);
}

function seo_site_filtered_rows(PDO $pdo, string $type, string $table, string $columns, int $siteId): array
{
    $sql = "SELECT {$columns} FROM {$table} WHERE status = 'published'";
    $params = [];
    try {
        $relationExists = (bool)$pdo->query("SHOW TABLES LIKE 'content_site_relations'")->fetchColumn();
        if ($relationExists) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM content_site_relations WHERE content_type = ?');
            $stmt->execute([$type]);
            if ((int)$stmt->fetchColumn() > 0) {
                $sql .= " AND id IN (SELECT content_id FROM content_site_relations WHERE content_type = :content_type AND site_id = :site_id)";
                $params = ['content_type' => $type, 'site_id' => $siteId];
            }
        }
    } catch (Throwable $error) {
        $params = [];
    }
    $sql .= ' ORDER BY id DESC LIMIT 500';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function seo_audit_item(array $row, string $type, string $urlPrefix, string $bodyField): array
{
    $issues = [];
    $title = trim((string)($row['title'] ?? ''));
    $slug = trim((string)($row['slug'] ?? ''));
    $summary = trim((string)($row['summary'] ?? ''));
    $seoTitle = trim((string)($row['seo_title'] ?? ''));
    $seoKeywords = trim((string)($row['seo_keywords'] ?? ''));
    $seoDescription = trim((string)($row['seo_description'] ?? ''));
    $body = seo_plain_text((string)($row[$bodyField] ?? ''));

    if ($seoTitle === '' || seo_text_length($seoTitle) < 8) {
        $issues[] = ['level' => 'warning', 'field' => 'seo_title', 'message' => 'SEO 标题缺失或过短'];
    }
    if ($seoDescription === '' || seo_text_length($seoDescription) < 30) {
        $issues[] = ['level' => 'warning', 'field' => 'seo_description', 'message' => 'SEO 描述缺失或过短'];
    }
    if ($seoKeywords === '') {
        $issues[] = ['level' => 'info', 'field' => 'seo_keywords', 'message' => 'SEO 关键词为空'];
    }
    if ($slug === '' || !seo_slug_ok($slug)) {
        $issues[] = ['level' => 'warning', 'field' => 'slug', 'message' => 'Slug 建议使用英文小写、数字和连字符'];
    }
    if ($summary === '' || seo_text_length($summary) < 20) {
        $issues[] = ['level' => 'info', 'field' => 'summary', 'message' => '摘要偏短，不利于列表页和搜索摘要'];
    }
    if (seo_text_length($body) < 120) {
        $issues[] = ['level' => 'info', 'field' => $bodyField, 'message' => '正文内容偏短，建议补充问题、方案、案例和参数'];
    }

    $score = max(0, 100 - count(array_filter($issues, fn($item) => $item['level'] === 'warning')) * 18 - count(array_filter($issues, fn($item) => $item['level'] === 'info')) * 8);
    return [
        'id' => (int)($row['id'] ?? 0),
        'type' => $type,
        'title' => $title,
        'slug' => $slug,
        'url' => $urlPrefix . $slug . '.html',
        'score' => $score,
        'issues' => $issues,
        'issue_count' => count($issues),
    ];
}

function seo_audit(PDO $pdo, PDO $main): array
{
    ensure_content_distribution_table($pdo);
    $siteId = requested_site_id();
    $site = site_settings($pdo, $siteId);
    $current = current_site($main, $pdo);
    $primaryDomain = primary_site_domain($main, $siteId);
    $issues = [];

    if (trim((string)($site['name'] ?? '')) === '') {
        $issues[] = ['level' => 'error', 'scope' => 'site', 'message' => '站点名称为空'];
    }
    if (trim((string)($site['domain'] ?? $current['domain'] ?? '')) === '') {
        $issues[] = ['level' => 'warning', 'scope' => 'site', 'message' => '未设置主域名，sitemap 会缺少正式域名'];
    }
    if ($primaryDomain) {
        if (($primaryDomain['dns_status'] ?? '') !== 'valid') {
            $issues[] = ['level' => 'warning', 'scope' => 'domain', 'message' => '主域名 DNS 尚未检查通过'];
        }
        if (($primaryDomain['ssl_status'] ?? '') !== 'ready') {
            $issues[] = ['level' => 'info', 'scope' => 'domain', 'message' => '主域名 HTTPS 尚未就绪'];
        }
    }
    if (trim((string)($site['description'] ?? '')) === '') {
        $issues[] = ['level' => 'warning', 'scope' => 'site', 'message' => '站点描述为空'];
    }
    if (trim((string)($site['keywords'] ?? '')) === '') {
        $issues[] = ['level' => 'info', 'scope' => 'site', 'message' => '站点关键词为空'];
    }
    if (empty($site['nav']) || !is_array($site['nav'])) {
        $issues[] = ['level' => 'warning', 'scope' => 'site', 'message' => '导航菜单为空'];
    }

    $articles = seo_site_filtered_rows($pdo, 'article', 'articles', 'id,title,slug,summary,content,seo_title,seo_keywords,seo_description', $siteId);
    $products = seo_site_filtered_rows($pdo, 'product', 'products', 'id,title,slug,summary,description,seo_title,seo_keywords,seo_description', $siteId);
    $pages = seo_site_filtered_rows($pdo, 'page', 'pages', 'id,title,slug,summary,content,seo_title,seo_keywords,seo_description', $siteId);

    if (!$articles) {
        $issues[] = ['level' => 'info', 'scope' => 'content', 'message' => '当前站点还没有已发布文章'];
    }
    if (!$products) {
        $issues[] = ['level' => 'info', 'scope' => 'content', 'message' => '当前站点还没有已发布商品'];
    }

    $items = [];
    foreach ($pages as $row) {
        $items[] = seo_audit_item($row, 'page', '', 'content');
    }
    foreach ($articles as $row) {
        $items[] = seo_audit_item($row, 'article', 'news/', 'content');
    }
    foreach ($products as $row) {
        $items[] = seo_audit_item($row, 'product', 'products/', 'description');
    }

    $itemIssues = array_sum(array_map(fn($item) => (int)$item['issue_count'], $items));
    $sitePenalty = count(array_filter($issues, fn($item) => $item['level'] === 'error')) * 25
        + count(array_filter($issues, fn($item) => $item['level'] === 'warning')) * 12
        + count(array_filter($issues, fn($item) => $item['level'] === 'info')) * 5;
    $score = max(0, min(100, 100 - $sitePenalty - min(45, $itemIssues * 3)));
    usort($items, fn($a, $b) => ($a['score'] <=> $b['score']) ?: ($b['issue_count'] <=> $a['issue_count']));

    return [
        'site_id' => $siteId,
        'site_name' => $site['name'] ?? $current['name'] ?? '',
        'domain' => $site['domain'] ?? $current['domain'] ?? '',
        'domain_status' => $primaryDomain ? [
            'dns_status' => (string)($primaryDomain['dns_status'] ?? ''),
            'ssl_status' => (string)($primaryDomain['ssl_status'] ?? ''),
            'last_checked_at' => (string)($primaryDomain['last_checked_at'] ?? ''),
            'last_result' => (string)($primaryDomain['last_result'] ?? ''),
        ] : null,
        'score' => $score,
        'grade' => $score >= 85 ? 'A' : ($score >= 70 ? 'B' : ($score >= 55 ? 'C' : 'D')),
        'issues' => $issues,
        'items' => $items,
        'summary' => [
            'pages' => count($pages),
            'articles' => count($articles),
            'products' => count($products),
            'checked_items' => count($items),
            'item_issues' => $itemIssues,
            'sitemap_ready' => trim((string)($site['domain'] ?? $current['domain'] ?? '')) !== '',
            'search_index_ready' => (count($articles) + count($products) + count($pages)) > 0,
        ],
    ];
}

function seo_keywords_from_text(string $text, array $site): string
{
    $seed = array_filter(array_map('trim', explode(',', (string)($site['keywords'] ?? ''))));
    $industry = site_industry($site);
    $base = array_values(array_unique(array_filter(array_merge($seed, [$industry, '独立站', '企业官网', '行业方案', '产品服务']))));
    return implode(',', array_slice($base, 0, 8));
}

function seo_description_from_row(array $row, string $bodyField, array $site): string
{
    $summary = trim((string)($row['summary'] ?? ''));
    $body = seo_plain_text((string)($row[$bodyField] ?? ''));
    $source = $summary !== '' ? $summary : $body;
    if ($source === '') {
        $source = '围绕' . ($row['title'] ?? '') . '介绍核心方案、应用价值和服务能力，帮助客户快速了解产品与企业优势。';
    }
    $description = text_limit($source, 150);
    if (seo_text_length($description) < 30) {
        $description .= '，适合用于官网展示、搜索引擎收录和客户询盘转化。';
    }
    return text_limit($description, 180);
}

function seo_title_from_row(array $row, string $type, array $site): string
{
    $title = trim((string)($row['title'] ?? ''));
    $siteName = trim((string)($site['name'] ?? ''));
    $suffix = $type === 'product' ? '产品方案' : ($type === 'page' ? '企业介绍' : '行业知识');
    $seoTitle = $title !== '' ? $title : $suffix;
    if ($siteName !== '' && !str_contains($seoTitle, $siteName) && seo_text_length($seoTitle) < 45) {
        $seoTitle .= ' - ' . $siteName;
    }
    return text_limit($seoTitle, 80);
}

function seo_fix_table(PDO $pdo, string $type, string $table, string $bodyField, array $site, array $ids = []): array
{
    $columns = 'id,title,slug,summary,' . $bodyField . ',seo_title,seo_keywords,seo_description';
    $sql = "SELECT {$columns} FROM {$table} WHERE status = 'published'";
    $params = [];
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql .= " AND id IN ({$placeholders})";
        $params = $ids;
    }
    $sql .= ' ORDER BY id DESC LIMIT 200';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $updated = 0;
    $items = [];
    $update = $pdo->prepare("UPDATE {$table} SET seo_title=:seo_title, seo_keywords=:seo_keywords, seo_description=:seo_description, updated_at=:updated_at WHERE id=:id");
    foreach ($rows as $row) {
        $seoTitle = trim((string)($row['seo_title'] ?? ''));
        $seoKeywords = trim((string)($row['seo_keywords'] ?? ''));
        $seoDescription = trim((string)($row['seo_description'] ?? ''));
        $nextTitle = seo_text_length($seoTitle) >= 8 ? $seoTitle : seo_title_from_row($row, $type, $site);
        $nextKeywords = $seoKeywords !== '' ? $seoKeywords : seo_keywords_from_text(($row['title'] ?? '') . ' ' . ($row['summary'] ?? ''), $site);
        $nextDescription = seo_text_length($seoDescription) >= 30 ? $seoDescription : seo_description_from_row($row, $bodyField, $site);
        if ($nextTitle !== $seoTitle || $nextKeywords !== $seoKeywords || $nextDescription !== $seoDescription) {
            $update->execute([
                'id' => (int)$row['id'],
                'seo_title' => $nextTitle,
                'seo_keywords' => $nextKeywords,
                'seo_description' => $nextDescription,
                'updated_at' => now(),
            ]);
            $updated++;
            $items[] = ['type' => $type, 'id' => (int)$row['id'], 'title' => $row['title'], 'seo_title' => $nextTitle];
        }
    }
    return ['checked' => count($rows), 'updated' => $updated, 'items' => $items];
}

function seo_fix(PDO $pdo, PDO $main, array $data): array
{
    ensure_content_distribution_table($pdo);
    $site = site_settings($pdo, requested_site_id());
    $types = $data['types'] ?? ['page', 'article', 'product'];
    if (!is_array($types)) {
        $types = ['page', 'article', 'product'];
    }
    $types = array_values(array_intersect($types, ['page', 'article', 'product']));
    $ids = $data['ids'] ?? [];
    $ids = is_array($ids) ? array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0)) : [];
    $result = ['checked' => 0, 'updated' => 0, 'items' => []];
    if (in_array('page', $types, true)) {
        $part = seo_fix_table($pdo, 'page', 'pages', 'content', $site, $ids);
        $result['checked'] += $part['checked'];
        $result['updated'] += $part['updated'];
        $result['items'] = array_merge($result['items'], $part['items']);
    }
    if (in_array('article', $types, true)) {
        $part = seo_fix_table($pdo, 'article', 'articles', 'content', $site, $ids);
        $result['checked'] += $part['checked'];
        $result['updated'] += $part['updated'];
        $result['items'] = array_merge($result['items'], $part['items']);
    }
    if (in_array('product', $types, true)) {
        $part = seo_fix_table($pdo, 'product', 'products', 'description', $site, $ids);
        $result['checked'] += $part['checked'];
        $result['updated'] += $part['updated'];
        $result['items'] = array_merge($result['items'], $part['items']);
    }
    $result['audit'] = seo_audit($pdo, $main);
    return $result;
}

function text_limit(string $value, int $length): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $length, 'UTF-8');
    }
    return substr($value, 0, $length);
}

function draft_slug(string $value, string $prefix): string
{
    $slug = strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
    $slug = trim($slug, '-');
    if (strlen($slug) >= 8) {
        return substr($slug, 0, 80);
    }
    if ($slug !== '') {
        return substr($prefix . '-' . $slug . '-' . time(), 0, 80);
    }
    return $prefix . '-' . time();
}

function site_industry(array $site): string
{
    return (string)($site['slogan'] ?? $site['description'] ?? $site['name'] ?? '企业独立站');
}

function page_plan_topic(string $prompt, string $fallback): string
{
    $topic = trim($prompt);
    if ($topic === '') {
        return $fallback;
    }

    $parts = preg_split('/[，,。；;\n\r]/u', $topic);
    $topic = trim((string)($parts[0] ?? $topic));
    $replacements = [
        '请帮我' => '',
        '帮我' => '',
        '请' => '',
        '生成一个' => '',
        '生成' => '',
        '创建一个' => '',
        '创建' => '',
        '做一个' => '',
        '搭建一个' => '',
        '搭建' => '',
        '搭一个' => '',
        '搭' => '',
        '一个' => '',
        '一套' => '',
    ];
    $topic = str_replace(array_keys($replacements), array_values($replacements), $topic);
    $topic = str_replace(['品牌官网首页', '官网首页', '网站首页', '首页'], ['品牌官网', '官网', '网站', ''], $topic);
    $topic = preg_replace('/\s+/u', '', trim((string)$topic));

    return $topic !== '' ? text_limit($topic, 36) : $fallback;
}

function local_ai_draft(string $type, string $prompt, array $site): array
{
    $industry = site_industry($site);
    if ($type === 'product') {
        $title = str_contains($prompt, '商品') || str_contains($prompt, '产品') ? text_limit($prompt, 30) : text_limit($prompt, 24) . '方案';
        return [
            'type' => 'product',
            'title' => $title,
            'slug' => draft_slug($title, 'product'),
            'sku' => 'HJ-' . strtoupper(substr(base_convert((string)time(), 10, 36), -6)),
            'cover' => 'assets/images/product-1.svg',
            'summary' => "{$title}面向{$industry}场景，适合用于独立站商品展示、方案介绍和客户询盘转化。",
            'description' => implode("\n", [
                "<p>{$title}是一款围绕{$prompt}设计的产品方案，适合在企业官网、行业知识库和独立站商城中进行展示。</p>",
                '<h2>核心卖点</h2>',
                '<ul><li>信息结构清晰，便于客户快速理解产品价值。</li><li>支持与文章、案例和询盘表单联动，提升转化路径完整度。</li><li>适合静态化发布，访问速度快，利于 SEO 收录。</li></ul>',
                '<h2>适用场景</h2>',
                "<p>适用于{$industry}相关的产品展示、解决方案页面、渠道招商页面和搜索投放承接页。</p>",
                '<h2>咨询建议</h2>',
                '<p>客户可通过首页询盘表单提交需求，后台客服再根据客户等级和跟进备注持续推进。</p>',
            ]),
            'price' => 0,
            'stock' => 999,
            'status' => 'draft',
        ];
    }

    $title = text_limit($prompt, 28);
    $finalTitle = str_contains($title, '如何') || str_contains($title, '为什么') ? $title : "{$industry}：{$title}";
    return [
        'type' => 'article',
        'title' => $finalTitle,
        'slug' => draft_slug($finalTitle, 'article'),
        'summary' => "本文围绕{$prompt}展开，适合用于官网资讯、知识库和搜索引擎关键词沉淀。",
        'content' => implode("\n", [
            "<p>{$prompt}是企业独立站建设中值得长期投入的主题。通过稳定的内容结构、清晰的产品表达和静态化页面，可以让搜索引擎更容易抓取页面，也让客户更快理解企业能力。</p>",
            '<h2>一、为什么适合做成独立站内容</h2>',
            "<p>{$industry}相关客户通常会通过搜索、社媒和行业渠道了解供应商。把问题、方案、案例和产品资料沉淀为文章，可以不断扩大关键词覆盖面。</p>",
            '<h2>二、页面应该包含哪些信息</h2>',
            '<p>建议包含行业痛点、解决方案、产品能力、应用场景、成功案例和咨询入口。文章内容不只服务阅读，也要服务后续询盘转化。</p>',
            '<h2>三、如何和商品及询盘联动</h2>',
            '<p>文章可以链接到相关商品详情页、案例模块和首页询盘表单，让客户从阅读自然进入咨询流程。后台保存后再生成静态页，即可同步到前台。</p>',
        ]),
        'seo_keywords' => implode(',', [$industry, '独立站', 'SEO', '静态网站', '企业官网']),
        'status' => 'draft',
    ];
}

function local_page_plan(string $prompt, array $site, array $registry): array
{
    $industry = site_industry($site);
    $brand = (string)($site['name'] ?? '化简站点');
    $topic = page_plan_topic($prompt, $industry);
    $homeModules = array_values(array_filter($registry['modules'] ?? [], fn($item) => ($item['scope'] ?? '') === 'home'));
    $moduleOrder = ['about', 'advantages', 'cases', 'products', 'articles', 'faq', 'inquiry'];
    $moduleTitles = [];
    foreach ($homeModules as $module) {
        $moduleTitles[$module['key']] = $module['title'] ?? $module['key'];
    }

    return [
        'source' => 'local',
        'prompt' => $topic,
        'summary' => "围绕“{$topic}”生成首页搭建草案，优先使用模块注册表中的标准首页模块。",
        'hero' => [
            'eyebrow' => 'AI 生成首页方案',
            'title' => "{$brand} · {$topic}",
            'subtitle' => "用静态化官网、内容知识库和产品展示页面承接搜索流量，让客户更快理解{$industry}的服务能力。",
            'primary_text' => '查看产品',
            'secondary_text' => '阅读资讯',
            'panel_label' => '模块化建站',
            'panel_title' => 'HTML 静态发布',
            'panel_description' => '后台维护内容，前台生成静态页面，适合 SEO、批量部署和长期运营。',
        ],
        'home_sections' => [
            'products_title' => '推荐产品与方案',
            'products_link_text' => '查看全部产品',
            'articles_title' => '行业知识与动态',
            'articles_link_text' => '阅读全部文章',
            'about_title' => '关于' . $brand,
            'about_subtitle' => "专注{$industry}的官网、内容和商品展示数字化。",
            'about_body' => "这套页面建议以“品牌可信度 + 产品展示 + 行业内容 + 询盘转化”为主线，让客户从搜索进入网站后，能够快速看到业务定位、核心卖点、应用案例和联系入口。",
            'advantages_title' => '核心优势',
            'cases_title' => '应用场景',
            'faq_title' => '常见问题',
            'inquiry_title' => '获取专属建站与产品方案',
            'inquiry_subtitle' => '留下需求，我们会根据行业、产品、关键词和部署方式给出页面结构与内容建议。',
        ],
        'home_content' => [
            'advantages' => [
                ['title' => '静态化更利于 SEO', 'description' => '前台生成 HTML 文件，访问速度快，部署简单，适合搜索引擎抓取和批量站点缓存。', 'sort_order' => 10],
                ['title' => '内容和商品统一管理', 'description' => '文章、产品、媒体和询盘都在后台维护，发布后同步到前台静态页面。', 'sort_order' => 20],
                ['title' => 'AI 辅助搭积木', 'description' => '根据行业和目标网站，自动建议模块顺序、标题文案和可复用内容结构。', 'sort_order' => 30],
            ],
            'cases' => [
                ['tag' => '企业官网', 'title' => "{$industry}品牌展示站", 'description' => '用首页、产品、文章和询盘表单构成轻量企业官网，承接自然搜索和客户咨询。', 'sort_order' => 10],
                ['tag' => '知识库', 'title' => "{$topic}内容沉淀站", 'description' => '围绕行业关键词持续发布文章，形成可被搜索引擎长期抓取的内容资产。', 'sort_order' => 20],
                ['tag' => '独立站商城', 'title' => '产品展示与询盘转化站', 'description' => '用商品详情、相关推荐和浮动询盘入口提升浏览路径和线索转化。', 'sort_order' => 30],
            ],
            'faqs' => [
                ['question' => '这套页面适合纯静态部署吗？', 'answer' => '适合。前台页面、搜索索引、站点地图都可以生成静态文件，后台只负责内容管理和发布。', 'sort_order' => 10],
                ['question' => '客户能自己调整模块顺序吗？', 'answer' => '可以。首页模块已经支持启用、移除和排序，后续可以继续扩展更多模块类型。', 'sort_order' => 20],
                ['question' => 'AI 生成的内容会直接发布吗？', 'answer' => '当前草案只填充到表单，客户确认后再保存和生成静态站，避免误发布。', 'sort_order' => 30],
            ],
        ],
        'home_modules' => array_map(fn($key, $index) => [
            'key' => $key,
            'title' => $moduleTitles[$key] ?? $key,
            'enabled' => true,
            'sort_order' => ($index + 1) * 10,
        ], $moduleOrder, array_keys($moduleOrder)),
        'used_modules' => array_map(fn($key) => [
            'key' => $key,
            'title' => $moduleTitles[$key] ?? $key,
        ], $moduleOrder),
    ];
}

function insert_article(PDO $pdo, array $data): int
{
    require_fields($data, ['title', 'slug']);
    $stmt = $pdo->prepare("INSERT INTO articles (category_id, title, slug, cover, summary, content, seo_title, seo_keywords, seo_description, status, published_at, created_at, updated_at)
        VALUES (:category_id, :title, :slug, :cover, :summary, :content, :seo_title, :seo_keywords, :seo_description, :status, :published_at, :created_at, :updated_at)");
    $time = now();
    $stmt->execute([
        'category_id' => $data['category_id'] ?? null,
        'title' => $data['title'],
        'slug' => $data['slug'],
        'cover' => $data['cover'] ?? '',
        'summary' => $data['summary'] ?? '',
        'content' => $data['content'] ?? '',
        'seo_title' => $data['seo_title'] ?? $data['title'],
        'seo_keywords' => $data['seo_keywords'] ?? '',
        'seo_description' => $data['seo_description'] ?? ($data['summary'] ?? ''),
        'status' => $data['status'] ?? 'draft',
        'published_at' => $data['published_at'] ?? null,
        'created_at' => $time,
        'updated_at' => $time,
    ]);
    return (int)$pdo->lastInsertId();
}

function insert_product(PDO $pdo, array $data): int
{
    require_fields($data, ['title', 'slug']);
    $stmt = $pdo->prepare("INSERT INTO products (category_id, title, slug, sku, cover, gallery, summary, description, price, market_price, stock, attributes, seo_title, seo_keywords, seo_description, status, published_at, created_at, updated_at)
        VALUES (:category_id, :title, :slug, :sku, :cover, :gallery, :summary, :description, :price, :market_price, :stock, :attributes, :seo_title, :seo_keywords, :seo_description, :status, :published_at, :created_at, :updated_at)");
    $time = now();
    $stmt->execute([
        'category_id' => $data['category_id'] ?? null,
        'title' => $data['title'],
        'slug' => $data['slug'],
        'sku' => $data['sku'] ?? '',
        'cover' => $data['cover'] ?? '',
        'gallery' => json_encode($data['gallery'] ?? []),
        'summary' => $data['summary'] ?? '',
        'description' => $data['description'] ?? '',
        'price' => $data['price'] ?? 0,
        'market_price' => $data['market_price'] ?? 0,
        'stock' => $data['stock'] ?? 0,
        'attributes' => json_encode($data['attributes'] ?? []),
        'seo_title' => $data['seo_title'] ?? $data['title'],
        'seo_keywords' => $data['seo_keywords'] ?? '',
        'seo_description' => $data['seo_description'] ?? ($data['summary'] ?? ''),
        'status' => $data['status'] ?? 'draft',
        'published_at' => $data['published_at'] ?? null,
        'created_at' => $time,
        'updated_at' => $time,
    ]);
    return (int)$pdo->lastInsertId();
}

function insert_page(PDO $pdo, array $data): int
{
    ensure_pages_table($pdo);
    require_fields($data, ['title', 'slug']);
    assert_page_slug_available($pdo, (string)$data['slug']);
    $stmt = $pdo->prepare("INSERT INTO pages (title, slug, cover, summary, content, seo_title, seo_keywords, seo_description, status, published_at, created_at, updated_at)
        VALUES (:title, :slug, :cover, :summary, :content, :seo_title, :seo_keywords, :seo_description, :status, :published_at, :created_at, :updated_at)");
    $time = now();
    $stmt->execute([
        'title' => $data['title'],
        'slug' => $data['slug'],
        'cover' => $data['cover'] ?? '',
        'summary' => $data['summary'] ?? '',
        'content' => $data['content'] ?? '',
        'seo_title' => $data['seo_title'] ?? $data['title'],
        'seo_keywords' => $data['seo_keywords'] ?? '',
        'seo_description' => $data['seo_description'] ?? ($data['summary'] ?? ''),
        'status' => $data['status'] ?? 'draft',
        'published_at' => $data['published_at'] ?? null,
        'created_at' => $time,
        'updated_at' => $time,
    ]);
    $id = (int)$pdo->lastInsertId();
    sync_article_tags($pdo, $id, $data['tags'] ?? $data['tag_names'] ?? '');
    return $id;
}

function publish_content_item(PDO $pdo, string $type, int $id, array $data = []): array
{
    $map = [
        'page' => ['table' => 'pages', 'label' => '页面'],
        'article' => ['table' => 'articles', 'label' => '文章'],
        'product' => ['table' => 'products', 'label' => '商品'],
    ];
    if (!isset($map[$type])) {
        fail('内容类型不支持', 'VALIDATION_ERROR', 422);
    }
    if ($type === 'page') {
        ensure_pages_table($pdo);
    }
    $table = $map[$type]['table'];
    $item = fetch_one($pdo, $table, $id);
    if (!$item) {
        fail($map[$type]['label'] . '不存在', 'NOT_FOUND', 404);
    }
    $action = (string)($data['action'] ?? $data['status'] ?? 'publish');
    $status = in_array($action, ['draft', 'unpublish'], true) ? 'draft' : 'published';
    $publishedAt = $status === 'published' ? ((string)($item['published_at'] ?? '') ?: now()) : null;
    $stmt = $pdo->prepare("UPDATE {$table} SET status = :status, published_at = :published_at, updated_at = :updated_at WHERE id = :id");
    $stmt->execute([
        'id' => $id,
        'status' => $status,
        'published_at' => $publishedAt,
        'updated_at' => now(),
    ]);
    if (isset($data['site_scope']) || isset($data['site_ids']) || isset($data['distribution_site_ids'])) {
        sync_content_distribution($pdo, $type, $id, normalize_site_ids($data));
    }
    $item = attach_distribution($pdo, $type, [fetch_one($pdo, $table, $id)])[0];
    if ($type === 'article') {
        $item = attach_article_tags($pdo, [$item])[0];
    }
    return $item;
}

function ensure_collector_tables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS collector_sources (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 10001,
        name VARCHAR(160) NOT NULL,
        source_type VARCHAR(30) NOT NULL DEFAULT 'rss',
        url VARCHAR(500) NOT NULL,
        category_id BIGINT UNSIGNED,
        rewrite_mode VARCHAR(30) NOT NULL DEFAULT 'draft',
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        last_run_at DATETIME,
        last_result VARCHAR(255),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_site_id (site_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensure_column($pdo, 'collector_sources', 'site_ids', 'TEXT');

    $pdo->exec("CREATE TABLE IF NOT EXISTS collector_records (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 10001,
        source_id BIGINT UNSIGNED,
        source_type VARCHAR(30) NOT NULL DEFAULT 'rss',
        source_url VARCHAR(500) NOT NULL,
        title VARCHAR(255) NOT NULL,
        summary TEXT,
        content MEDIUMTEXT,
        article_id BIGINT UNSIGNED,
        status VARCHAR(30) NOT NULL DEFAULT 'draft',
        error_message TEXT,
        collected_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uk_site_source_url (site_id, source_url(120)),
        INDEX idx_site_id (site_id),
        INDEX idx_source_id (source_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function is_private_host(string $host): bool
{
    $host = strtolower(trim($host));
    if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return true;
    }
    $ip = gethostbyname($host);
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }
    return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

function assert_collect_url(string $url): void
{
    $parts = parse_url($url);
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $host = (string)($parts['host'] ?? '');
    if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
        fail('采集地址必须是 http 或 https URL', 'VALIDATION_ERROR', 422);
    }
    if (is_private_host($host)) {
        fail('采集地址不能指向本机或内网地址', 'VALIDATION_ERROR', 422);
    }
}

function fetch_collect_url(string $url): string
{
    assert_collect_url($url);
    if (!function_exists('curl_init')) {
        $context = stream_context_create(['http' => ['timeout' => 12, 'user_agent' => 'HuajianCollector/0.1']]);
        $body = @file_get_contents($url, false, $context);
        if (!is_string($body) || $body === '') {
            fail('采集源读取失败', 'COLLECT_FETCH_FAILED', 422);
        }
        return $body;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'HuajianCollector/0.1',
    ]);
    if (env_value('HJ_COLLECTOR_INSECURE_SSL', '0') === '1') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if (!is_string($body) || $body === '' || $status < 200 || $status >= 400) {
        fail('采集源读取失败', 'COLLECT_FETCH_FAILED', 422, ['status' => $status, 'curl_error' => $error]);
    }
    return $body;
}

function text_excerpt(string $value, int $length = 180): string
{
    $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)));
    return text_limit($value, $length);
}

function collect_html_text(string $html): string
{
    $html = preg_replace('#<(script|style|noscript)[^>]*>.*?</\1>#is', '', $html) ?? $html;
    if (preg_match('#<article[^>]*>(.*?)</article>#is', $html, $match)) {
        $html = $match[1];
    } elseif (preg_match('#<body[^>]*>(.*?)</body>#is', $html, $match)) {
        $html = $match[1];
    }
    return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function parse_collected_items(string $type, string $url, string $body): array
{
    $items = [];
    if ($type === 'rss') {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        if ($xml) {
            $nodes = $xml->channel->item ?? $xml->entry ?? [];
            foreach ($nodes as $node) {
                $link = (string)($node->link ?? '');
                if ($link === '' && isset($node->link['href'])) {
                    $link = (string)$node->link['href'];
                }
                $description = (string)($node->description ?? $node->summary ?? $node->content ?? '');
                $items[] = [
                    'title' => text_limit(trim((string)($node->title ?? '')), 180),
                    'source_url' => $link ?: $url,
                    'summary' => text_excerpt($description),
                    'content' => '<p>' . htmlspecialchars(text_excerpt($description, 1200), ENT_QUOTES, 'UTF-8') . '</p>',
                ];
            }
        }
    } else {
        preg_match('#<title[^>]*>(.*?)</title>#is', $body, $titleMatch);
        $title = html_entity_decode(trim(strip_tags($titleMatch[1] ?? '采集页面')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = collect_html_text($body);
        $items[] = [
            'title' => text_limit($title, 180),
            'source_url' => $url,
            'summary' => text_excerpt($text),
            'content' => '<p>' . htmlspecialchars(text_excerpt($text, 1800), ENT_QUOTES, 'UTF-8') . '</p>',
        ];
    }
    return array_values(array_filter($items, fn($item) => trim((string)($item['title'] ?? '')) !== ''));
}

function normalize_collector_source(array $data, array $current = []): array
{
    $type = in_array(($data['source_type'] ?? $current['source_type'] ?? 'rss'), ['rss', 'url'], true) ? ($data['source_type'] ?? $current['source_type'] ?? 'rss') : 'rss';
    $url = trim((string)($data['url'] ?? ($current['url'] ?? '')));
    assert_collect_url($url);
    if (!isset($data['site_scope']) && !isset($data['site_ids']) && !isset($data['distribution_site_ids']) && !empty($current)) {
        $siteIds = collector_source_site_ids($current);
    } else {
        $siteIds = normalize_site_ids($data ?: $current);
    }
    $primarySiteId = (int)($siteIds[0] ?? ($data['site_id'] ?? $current['site_id'] ?? requested_site_id()));
    return [
        'site_id' => $primarySiteId,
        'site_ids' => json_encode($siteIds ?: [$primarySiteId], JSON_UNESCAPED_UNICODE),
        'name' => mb_substr(trim((string)($data['name'] ?? ($current['name'] ?? ''))), 0, 160, 'UTF-8'),
        'source_type' => $type,
        'url' => mb_substr($url, 0, 500, 'UTF-8'),
        'category_id' => (int)($data['category_id'] ?? $current['category_id'] ?? 0) ?: null,
        'rewrite_mode' => in_array(($data['rewrite_mode'] ?? $current['rewrite_mode'] ?? 'draft'), ['draft', 'published'], true) ? ($data['rewrite_mode'] ?? $current['rewrite_mode'] ?? 'draft') : 'draft',
        'status' => in_array(($data['status'] ?? $current['status'] ?? 'active'), ['active', 'disabled'], true) ? ($data['status'] ?? $current['status'] ?? 'active') : 'active',
    ];
}

function collector_source_site_ids(array $source): array
{
    $ids = [];
    if (!empty($source['site_ids'])) {
        $decoded = json_decode((string)$source['site_ids'], true);
        if (is_array($decoded)) {
            $ids = array_map('intval', $decoded);
        }
    }
    $ids = array_values(array_unique(array_filter($ids, fn($id) => $id > 0)));
    return $ids ?: [(int)($source['site_id'] ?? requested_site_id())];
}

function list_collector_sources(PDO $pdo, ?PDO $main = null): array
{
    ensure_collector_tables($pdo);
    $siteWhere = site_scope_where_sql();
    $where = $siteWhere !== '' ? [$siteWhere] : [];
    $result = paginate($pdo, 'collector_sources', $where, 'id DESC', 'name');
    $result['items'] = attach_site_names($result['items'], $main);
    $result['items'] = array_map(function (array $item) {
        $item['site_ids'] = collector_source_site_ids($item);
        return $item;
    }, $result['items']);
    return $result;
}

function list_collector_records(PDO $pdo, ?PDO $main = null): array
{
    ensure_collector_tables($pdo);
    $siteWhere = site_scope_where_sql();
    $where = $siteWhere !== '' ? [$siteWhere] : [];
    $result = paginate($pdo, 'collector_records', $where, 'id DESC', 'title');
    $result['items'] = attach_site_names($result['items'], $main);
    return $result;
}

function run_collector_source(PDO $pdo, int $sourceId): array
{
    ensure_collector_tables($pdo);
    $source = fetch_one($pdo, 'collector_sources', $sourceId);
    if (!$source || ($source['status'] ?? '') !== 'active') {
        fail('采集源不存在或已停用', 'NOT_FOUND', 404);
    }
    $siteIds = collector_source_site_ids($source);
    $body = fetch_collect_url((string)$source['url']);
    $items = array_slice(parse_collected_items((string)$source['source_type'], (string)$source['url'], $body), 0, 10);
    $created = [];
    $time = now();
    $stmt = $pdo->prepare("INSERT IGNORE INTO collector_records (site_id, source_id, source_type, source_url, title, summary, content, status, collected_at, created_at, updated_at)
        VALUES (:site_id, :source_id, :source_type, :source_url, :title, :summary, :content, 'draft', :collected_at, :created_at, :updated_at)");
    foreach ($items as $item) {
        $stmt->execute([
            'site_id' => (int)$source['site_id'],
            'source_id' => (int)$source['id'],
            'source_type' => $source['source_type'],
            'source_url' => mb_substr((string)$item['source_url'], 0, 500, 'UTF-8'),
            'title' => mb_substr((string)$item['title'], 0, 255, 'UTF-8'),
            'summary' => $item['summary'] ?? '',
            'content' => $item['content'] ?? '',
            'collected_at' => $time,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        if ($pdo->lastInsertId()) {
            $created[] = fetch_one($pdo, 'collector_records', (int)$pdo->lastInsertId());
        }
    }
    $result = '采集到 ' . count($items) . ' 条，新增 ' . count($created) . ' 条';
    $update = $pdo->prepare('UPDATE collector_sources SET last_run_at = :last_run_at, last_result = :last_result, updated_at = :updated_at WHERE id = :id');
    $update->execute(['id' => $sourceId, 'last_run_at' => $time, 'last_result' => $result, 'updated_at' => $time]);
    $createdArticles = [];
    $rewriteMode = in_array(($source['rewrite_mode'] ?? 'draft'), ['draft', 'published'], true) ? (string)$source['rewrite_mode'] : 'draft';
    foreach ($created as $record) {
        if (!empty($record['id'])) {
            $published = publish_collector_record($pdo, (int)$record['id'], $rewriteMode, $siteIds);
            if (!empty($published['article'])) {
                $createdArticles[] = $published['article'];
            }
        }
    }
    $message = $result;
    if ($createdArticles) {
        $message .= '，已转文章 ' . count($createdArticles) . ' 条';
        if ($rewriteMode === 'published') {
            $message .= '并发布';
        }
    }
    if ($message !== $result) {
        $update->execute(['id' => $sourceId, 'last_run_at' => $time, 'last_result' => $message, 'updated_at' => now()]);
    }
    return [
        'source' => fetch_one($pdo, 'collector_sources', $sourceId),
        'items' => $created,
        'articles' => $createdArticles,
        'article_count' => count($createdArticles),
        'published_count' => $rewriteMode === 'published' ? count($createdArticles) : 0,
        'site_ids' => $siteIds,
        'message' => $message,
    ];
}

function publish_collector_record(PDO $pdo, int $recordId, string $status = 'draft', ?array $siteIds = null): array
{
    ensure_collector_tables($pdo);
    $record = fetch_one($pdo, 'collector_records', $recordId);
    if (!$record) {
        fail('采集记录不存在', 'NOT_FOUND', 404);
    }
    if (!empty($record['article_id'])) {
        $article = fetch_one($pdo, 'articles', (int)$record['article_id']);
        return ['article' => $article ? attach_distribution($pdo, 'article', [$article])[0] : null, 'record' => $record];
    }
    $slug = draft_slug((string)$record['title'], 'collected') . '-' . (int)$record['id'];
    $source = !empty($record['source_id']) ? fetch_one($pdo, 'collector_sources', (int)$record['source_id']) : null;
    $siteIds = array_values(array_unique(array_filter(array_map('intval', $siteIds ?: ($source ? collector_source_site_ids($source) : [(int)$record['site_id']])), fn($id) => $id > 0)));
    $siteIds = $siteIds ?: [(int)$record['site_id']];
    $articleId = insert_article($pdo, [
        'category_id' => $source['category_id'] ?? null,
        'title' => $record['title'],
        'slug' => $slug,
        'summary' => $record['summary'] ?? '',
        'content' => ($record['content'] ?? '') . '<p>来源：' . htmlspecialchars((string)$record['source_url'], ENT_QUOTES, 'UTF-8') . '</p>',
        'seo_title' => $record['title'],
        'seo_description' => $record['summary'] ?? '',
        'status' => in_array($status, ['draft', 'published'], true) ? $status : 'draft',
        'published_at' => $status === 'published' ? now() : null,
    ]);
    sync_content_distribution($pdo, 'article', $articleId, $siteIds);
    $update = $pdo->prepare("UPDATE collector_records SET article_id = :article_id, status = 'converted', updated_at = :updated_at WHERE id = :id");
    $update->execute(['id' => $recordId, 'article_id' => $articleId, 'updated_at' => now()]);
    return ['article' => attach_distribution($pdo, 'article', [fetch_one($pdo, 'articles', $articleId)])[0], 'record' => fetch_one($pdo, 'collector_records', $recordId)];
}

function create_manual_collector_record(PDO $pdo, array $data, ?array $draft = null): array
{
    ensure_collector_tables($pdo);
    require_fields($data, ['title', 'content']);
    $siteIds = normalize_site_ids($data);
    $siteId = (int)($siteIds[0] ?? requested_site_id());
    $title = mb_substr(trim((string)($draft['title'] ?? $data['title'])), 0, 255, 'UTF-8');
    if ($title === '') {
        fail('标题不能为空', 'VALIDATION_ERROR', 422);
    }
    $rawContent = trim((string)($data['content'] ?? $data['summary'] ?? ''));
    $summary = trim((string)($draft['summary'] ?? $data['summary'] ?? text_excerpt($rawContent, 180)));
    $content = trim((string)($draft['content'] ?? $data['content'] ?? ''));
    if ($content === '') {
        $content = '<p>' . htmlspecialchars(text_excerpt($rawContent, 1800), ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $sourceUrl = trim((string)($data['source_url'] ?? ''));
    if ($sourceUrl === '') {
        $sourceUrl = 'manual://' . date('YmdHis') . '-' . substr(md5($title . $rawContent . random_bytes(4)), 0, 10);
    }
    $time = now();
    $stmt = $pdo->prepare("INSERT INTO collector_records (site_id, source_id, source_type, source_url, title, summary, content, status, collected_at, created_at, updated_at)
        VALUES (:site_id, NULL, :source_type, :source_url, :title, :summary, :content, :status, :collected_at, :created_at, :updated_at)");
    $stmt->execute([
        'site_id' => $siteId,
        'source_type' => (string)($draft ? 'ai-rewrite' : 'manual'),
        'source_url' => mb_substr($sourceUrl, 0, 500, 'UTF-8'),
        'title' => $title,
        'summary' => $summary,
        'content' => $content,
        'status' => $draft ? 'rewritten' : 'draft',
        'collected_at' => $time,
        'created_at' => $time,
        'updated_at' => $time,
    ]);
    $record = fetch_one($pdo, 'collector_records', (int)$pdo->lastInsertId()) ?: [];
    $record['site_ids'] = $siteIds;
    return $record;
}

function rewrite_manual_collector_record(PDO $pdo, array $data): array
{
    require_fields($data, ['title', 'content']);
    $title = trim((string)$data['title']);
    $content = trim((string)$data['content']);
    if ($content === '') {
        fail('请粘贴需要改写的正文', 'VALIDATION_ERROR', 422);
    }
    consume_ai_quota(main_pdo(), auth_user(), 1);
    $site = site_settings($pdo);
    $prompt = implode("\n", [
        '请把下面采集或粘贴的资料改写成适合企业独立站 SEO 收录的原创文章草稿。',
        '要求：保留事实信息，重写标题、摘要、正文结构，避免低质量伪原创；正文使用 p、h2、ul、li HTML。',
        '原始标题：' . $title,
        '补充要求：' . trim((string)($data['prompt'] ?? '')),
        '原文：' . text_limit(strip_tags($content), 2400),
    ]);
    $fallback = local_ai_draft('article', $prompt, $site);
    $remote = remote_ai_draft('article', $prompt, $site);
    $draft = $remote ? array_replace($fallback, $remote) : $fallback;
    $record = create_manual_collector_record($pdo, $data, $draft);
    return [
        'source' => $remote ? 'remote' : 'local',
        'draft' => $draft,
        'record' => $record,
    ];
}

function ensure_ai_task_tables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS ai_tasks (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 10001,
        task_type VARCHAR(50) NOT NULL,
        prompt TEXT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'success',
        site_ids TEXT,
        result_json MEDIUMTEXT,
        message VARCHAR(255),
        success_count INT UNSIGNED NOT NULL DEFAULT 0,
        created_article_ids TEXT,
        created_product_ids TEXT,
        started_at DATETIME,
        finished_at DATETIME,
        confirmed_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_site_id (site_id),
        INDEX idx_task_type (task_type),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function normalize_ai_task_row(array $row): array
{
    foreach (['site_ids', 'result_json', 'created_article_ids', 'created_product_ids'] as $field) {
        $decoded = json_decode((string)($row[$field] ?? ''), true);
        $row[$field] = is_array($decoded) ? $decoded : [];
    }
    return $row;
}

function list_ai_tasks(PDO $pdo, ?PDO $main = null): array
{
    ensure_ai_task_tables($pdo);
    $siteScope = requested_site_scope($main);
    if ($siteScope === null) {
        $result = paginate($pdo, 'ai_tasks', [], 'id DESC', 'prompt');
        $result['items'] = array_map('normalize_ai_task_row', attach_site_names($result['items'], $main));
        return $result;
    }

    $keyword = trim((string)($_GET['keyword'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $clauses = [];
    $params = [];
    if ($keyword !== '') {
        $clauses[] = 'prompt LIKE :keyword';
        $params['keyword'] = '%' . $keyword . '%';
    }
    if ($status !== '') {
        $clauses[] = 'status = :status';
        $params['status'] = $status;
    }
    $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
    $stmt = $pdo->prepare("SELECT * FROM ai_tasks{$whereSql} ORDER BY id DESC");
    $stmt->execute($params);
    $rows = array_map('normalize_ai_task_row', $stmt->fetchAll());
    $rows = array_values(array_filter($rows, static function (array $row) use ($siteScope) {
        $ids = array_values(array_filter(array_map('intval', $row['site_ids'] ?? []), fn($id) => $id > 0));
        foreach ($siteScope as $siteId) {
            if ((int)($row['site_id'] ?? 0) === (int)$siteId || in_array((int)$siteId, $ids, true)) {
                return true;
            }
        }
        return false;
    }));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $total = count($rows);
    $items = array_slice($rows, ($page - 1) * $pageSize, $pageSize);
    return [
        'items' => attach_site_names($items, $main),
        'pagination' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int)ceil($total / $pageSize),
        ],
    ];
}

function create_ai_task(PDO $pdo, array $data): array
{
    ensure_ai_task_tables($pdo);
    require_fields($data, ['type', 'prompt']);
    $type = in_array(($data['type'] ?? 'article'), ['article', 'product'], true) ? (string)$data['type'] : 'article';
    $count = min(20, max(1, (int)($data['count'] ?? 3)));
    $siteIds = normalize_site_ids($data);
    consume_ai_quota(main_pdo(), auth_user(), $count);
    $site = site_settings($pdo);
    $items = [];
    for ($i = 1; $i <= $count; $i++) {
        $itemPrompt = trim((string)$data['prompt']) . "（第 {$i} 条，避免重复）";
        $fallback = local_ai_draft($type, $itemPrompt, $site);
        $remote = remote_ai_draft($type, $itemPrompt, $site);
        $draft = $remote ? array_replace($fallback, $remote) : $fallback;
        $draft['type'] = $type;
        $draft['title'] = text_limit((string)($draft['title'] ?? $itemPrompt), 120);
        $draft['slug'] = substr(draft_slug((string)($draft['slug'] ?? $draft['title']), $type) . '-' . date('His') . '-' . $i, 0, 180);
        if ($type === 'product') {
            $draft['sku'] = (string)($draft['sku'] ?? ('HJ-AI-' . date('His') . '-' . $i));
        }
        $items[] = $draft;
    }
    $time = now();
    $taskType = $type === 'article' ? 'article_generate' : 'product_generate';
    $stmt = $pdo->prepare("INSERT INTO ai_tasks (site_id, task_type, prompt, status, site_ids, result_json, message, success_count, started_at, finished_at, created_at, updated_at)
        VALUES (:site_id, :task_type, :prompt, 'success', :site_ids, :result_json, :message, :success_count, :started_at, :finished_at, :created_at, :updated_at)");
    $stmt->execute([
        'site_id' => requested_site_id(),
        'task_type' => $taskType,
        'prompt' => trim((string)$data['prompt']),
        'site_ids' => json_encode($siteIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'result_json' => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'message' => '已生成 ' . count($items) . ' 条草稿，等待确认',
        'success_count' => count($items),
        'started_at' => $time,
        'finished_at' => $time,
        'created_at' => $time,
        'updated_at' => $time,
    ]);
    return normalize_ai_task_row(fetch_one($pdo, 'ai_tasks', (int)$pdo->lastInsertId()) ?: []);
}

function confirm_ai_task(PDO $pdo, int $taskId, array $data): array
{
    ensure_ai_task_tables($pdo);
    $task = fetch_one($pdo, 'ai_tasks', $taskId);
    if (!$task) {
        fail('AI 任务不存在', 'NOT_FOUND', 404);
    }
    $task = normalize_ai_task_row($task);
    $action = in_array(($data['action'] ?? 'save_draft'), ['save_draft', 'publish', 'discard'], true) ? (string)$data['action'] : 'save_draft';
    if ($action === 'discard') {
        $stmt = $pdo->prepare("UPDATE ai_tasks SET status='discarded', message=:message, confirmed_at=:confirmed_at, updated_at=:updated_at WHERE id=:id");
        $stmt->execute(['id' => $taskId, 'message' => '任务结果已丢弃', 'confirmed_at' => now(), 'updated_at' => now()]);
        return normalize_ai_task_row(fetch_one($pdo, 'ai_tasks', $taskId) ?: []);
    }
    $selected = $data['selected_items'] ?? [];
    if (!is_array($selected) || !$selected) {
        $selected = array_keys($task['result_json']);
    }
    $selected = array_values(array_unique(array_map('intval', $selected)));
    $contentStatus = $action === 'publish' ? 'published' : 'draft';
    $siteIds = (isset($data['site_scope']) || isset($data['site_ids']) || isset($data['distribution_site_ids']))
        ? normalize_site_ids($data)
        : (array_values(array_filter(array_map('intval', $task['site_ids'] ?? []))) ?: [requested_site_id()]);
    $articleIds = [];
    $productIds = [];
    foreach ($selected as $index) {
        if (!isset($task['result_json'][$index]) || !is_array($task['result_json'][$index])) {
            continue;
        }
        $draft = $task['result_json'][$index];
        $draft['status'] = $contentStatus;
        $draft['published_at'] = $contentStatus === 'published' ? now() : null;
        if (($draft['type'] ?? '') === 'product' || $task['task_type'] === 'product_generate') {
            $id = insert_product($pdo, $draft);
            sync_content_distribution($pdo, 'product', $id, $siteIds);
            $productIds[] = $id;
        } else {
            $id = insert_article($pdo, $draft);
            sync_content_distribution($pdo, 'article', $id, $siteIds);
            $articleIds[] = $id;
        }
    }
    $message = '已确认入库：文章 ' . count($articleIds) . ' 条，商品 ' . count($productIds) . ' 条';
    $stmt = $pdo->prepare("UPDATE ai_tasks SET status='confirmed', message=:message, created_article_ids=:created_article_ids, created_product_ids=:created_product_ids, confirmed_at=:confirmed_at, updated_at=:updated_at WHERE id=:id");
    $stmt->execute([
        'id' => $taskId,
        'message' => $message,
        'created_article_ids' => json_encode($articleIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_product_ids' => json_encode($productIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'confirmed_at' => now(),
        'updated_at' => now(),
    ]);
    return normalize_ai_task_row(fetch_one($pdo, 'ai_tasks', $taskId) ?: []);
}

function record_ai_batch_content_task(PDO $pdo, string $type, string $prompt, array $siteIds, array $createdIds): array
{
    ensure_ai_task_tables($pdo);
    $time = now();
    $isProduct = $type === 'product';
    $stmt = $pdo->prepare("INSERT INTO ai_tasks (site_id, task_type, prompt, status, site_ids, result_json, message, success_count, created_article_ids, created_product_ids, started_at, finished_at, confirmed_at, created_at, updated_at)
        VALUES (:site_id, :task_type, :prompt, 'confirmed', :site_ids, :result_json, :message, :success_count, :created_article_ids, :created_product_ids, :started_at, :finished_at, :confirmed_at, :created_at, :updated_at)");
    $stmt->execute([
        'site_id' => requested_site_id(),
        'task_type' => $isProduct ? 'product_batch_publish' : 'article_batch_publish',
        'prompt' => $prompt,
        'site_ids' => json_encode(array_values(array_unique(array_map('intval', $siteIds))), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'result_json' => json_encode(['created_ids' => $createdIds, 'content_type' => $type], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'message' => 'AI 批量入库完成：' . ($isProduct ? '商品 ' : '文章 ') . count($createdIds) . ' 条',
        'success_count' => count($createdIds),
        'created_article_ids' => json_encode($isProduct ? [] : $createdIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_product_ids' => json_encode($isProduct ? $createdIds : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'started_at' => $time,
        'finished_at' => $time,
        'confirmed_at' => $time,
        'created_at' => $time,
        'updated_at' => $time,
    ]);
    return normalize_ai_task_row(fetch_one($pdo, 'ai_tasks', (int)$pdo->lastInsertId()) ?: []);
}

function svg_text(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function svg_lines(string $value, int $length = 18): array
{
    $value = trim(preg_replace('/\s+/', ' ', $value));
    if ($value === '') {
        return ['AI 封面'];
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        $lines = [];
        $total = mb_strlen($value, 'UTF-8');
        for ($i = 0; $i < $total; $i += $length) {
            $lines[] = mb_substr($value, $i, $length, 'UTF-8');
        }
        return array_slice($lines, 0, 3);
    }
    return str_split(substr($value, 0, $length * 3), $length);
}

function insert_media(PDO $pdo, array $data): int
{
    ensure_media_site_column($pdo);
    $stmt = $pdo->prepare("INSERT INTO media (site_id, file_name, file_path, file_type, mime_type, file_size, width, height, alt_text, source_type, created_at, updated_at)
        VALUES (:site_id, :file_name, :file_path, :file_type, :mime_type, :file_size, :width, :height, :alt_text, :source_type, :created_at, :updated_at)");
    $time = now();
    $stmt->execute([
        'site_id' => (int)($data['site_id'] ?? requested_site_id()),
        'file_name' => $data['file_name'],
        'file_path' => $data['file_path'],
        'file_type' => $data['file_type'] ?? 'image',
        'mime_type' => $data['mime_type'] ?? 'image/svg+xml',
        'file_size' => $data['file_size'] ?? 0,
        'width' => $data['width'] ?? 1200,
        'height' => $data['height'] ?? 675,
        'alt_text' => $data['alt_text'] ?? '',
        'source_type' => $data['source_type'] ?? 'ai',
        'created_at' => $time,
        'updated_at' => $time,
    ]);
    return (int)$pdo->lastInsertId();
}

function generate_cover_svg(PDO $pdo, string $type, string $title, string $prompt): array
{
    $width = 1200;
    $height = 675;
    $label = $type === 'product' ? 'Product Cover' : 'Article Cover';
    $colors = $type === 'product'
        ? ['#0f766e', '#155e75', '#f8fafc', '#ccfbf1']
        : ['#1d4ed8', '#4338ca', '#f8fafc', '#dbeafe'];
    $displayTitle = $title !== '' ? $title : $prompt;
    $lines = svg_lines($displayTitle, 18);
    $tspans = '';
    foreach ($lines as $index => $line) {
        $dy = $index === 0 ? 0 : 64;
        $tspans .= '<tspan x="92" dy="' . $dy . '">' . svg_text($line) . '</tspan>';
    }
    $subtitle = text_limit($prompt, 42);
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$colors[0]}"/>
      <stop offset="100%" stop-color="{$colors[1]}"/>
    </linearGradient>
  </defs>
  <rect width="{$width}" height="{$height}" rx="0" fill="url(#bg)"/>
  <rect x="64" y="64" width="1072" height="547" rx="28" fill="rgba(255,255,255,.10)" stroke="rgba(255,255,255,.28)"/>
  <circle cx="980" cy="170" r="96" fill="rgba(255,255,255,.13)"/>
  <circle cx="1060" cy="270" r="44" fill="rgba(255,255,255,.18)"/>
  <text x="92" y="132" fill="{$colors[3]}" font-family="Arial, Microsoft YaHei, sans-serif" font-size="28" font-weight="700" letter-spacing="2">{$label}</text>
  <text y="282" fill="{$colors[2]}" font-family="Arial, Microsoft YaHei, sans-serif" font-size="56" font-weight="800">{$tspans}</text>
  <text x="92" y="530" fill="rgba(248,250,252,.82)" font-family="Arial, Microsoft YaHei, sans-serif" font-size="28">{$subtitle}</text>
  <rect x="92" y="560" width="160" height="8" rx="4" fill="{$colors[3]}"/>
</svg>
SVG;
    assert_storage_quota($pdo, main_pdo(), auth_user(), strlen($svg));

    $relativeDir = 'assets/images';
    $targetDir = public_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    ensure_dir($targetDir);
    $fileName = 'ai-cover-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.svg';
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
    file_put_contents($targetPath, $svg);
    $filePath = $relativeDir . '/' . $fileName;
    $id = insert_media($pdo, [
        'file_name' => $fileName,
        'file_path' => $filePath,
        'file_size' => filesize($targetPath) ?: strlen($svg),
        'width' => $width,
        'height' => $height,
        'alt_text' => $displayTitle,
        'source_type' => 'ai',
    ]);
    return fetch_one($pdo, 'media', $id) ?? ['file_path' => $filePath];
}

function extract_json_object(string $text): ?array
{
    $data = json_decode($text, true);
    if (is_array($data)) {
        return $data;
    }
    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start === false || $end === false || $end <= $start) {
        return null;
    }
    $json = substr($text, $start, $end - $start + 1);
    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

function remote_ai_draft(string $type, string $prompt, array $site): ?array
{
    $config = $site['ai'] ?? [];
    $endpoint = trim((string)($config['endpoint'] ?? ''));
    $apiKey = trim((string)($config['api_key'] ?? ''));
    $model = trim((string)($config['model'] ?? ''));
    if ($endpoint === '' || $apiKey === '' || $model === '' || str_contains($endpoint, 'example.com') || str_contains($model, 'placeholder')) {
        return null;
    }
    if (!function_exists('curl_init')) {
        return null;
    }

    $fields = $type === 'product'
        ? 'title, slug, sku, cover, summary, description, price, stock, status'
        : 'title, slug, summary, content, seo_keywords, status';
    $system = "你是化简 SaaS 建站系统的内容助理。请只返回 JSON 对象，不要 Markdown。返回字段：{$fields}。HTML 正文字段允许使用 p、h2、ul、li。";
    $user = "站点： " . site_industry($site) . "\n类型：{$type}\n需求：{$prompt}";
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ],
        'temperature' => 0.7,
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($response) || $status < 200 || $status >= 300) {
        return null;
    }
    $data = json_decode($response, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    if (!is_string($content) || $content === '') {
        return null;
    }
    $draft = extract_json_object($content);
    return is_array($draft) ? $draft : null;
}

function ensure_auth_tables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        display_name VARCHAR(100) NOT NULL,
        customer_id BIGINT UNSIGNED,
        role VARCHAR(50) NOT NULL DEFAULT 'admin',
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        last_login_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensure_column($pdo, 'admin_users', 'customer_id', 'BIGINT UNSIGNED');

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_sessions (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        ip_address VARCHAR(80),
        user_agent VARCHAR(255),
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        order_no VARCHAR(40) NOT NULL UNIQUE,
        customer_name VARCHAR(100) NOT NULL,
        phone VARCHAR(60) NOT NULL,
        email VARCHAR(120),
        address VARCHAR(255),
        items TEXT,
        total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency VARCHAR(10) NOT NULL DEFAULT 'CNY',
        payment_method VARCHAR(50) NOT NULL DEFAULT 'manual',
        payment_status VARCHAR(30) NOT NULL DEFAULT 'pending',
        fulfillment_status VARCHAR(30) NOT NULL DEFAULT 'new',
        tracking_company VARCHAR(100),
        tracking_no VARCHAR(100),
        paid_at DATETIME,
        shipped_at DATETIME,
        remark TEXT,
        source_url VARCHAR(255),
        ip_address VARCHAR(80),
        user_agent VARCHAR(255),
        stock_reserved TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_order_no (order_no),
        INDEX idx_payment_status (payment_status),
        INDEX idx_fulfillment_status (fulfillment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    ensure_column($pdo, 'orders', 'tracking_company', 'VARCHAR(100)');
    ensure_column($pdo, 'orders', 'tracking_no', 'VARCHAR(100)');
    ensure_column($pdo, 'orders', 'paid_at', 'DATETIME');
    ensure_column($pdo, 'orders', 'shipped_at', 'DATETIME');
    ensure_column($pdo, 'orders', 'site_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 10001');
    ensure_column($pdo, 'orders', 'stock_reserved', 'TINYINT(1) NOT NULL DEFAULT 0');

    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_webhook_events (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 10001,
        channel_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        provider VARCHAR(40) NOT NULL DEFAULT 'manual',
        event_key VARCHAR(180) NOT NULL,
        order_no VARCHAR(40) NOT NULL,
        transaction_id VARCHAR(120),
        payment_status VARCHAR(30) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency VARCHAR(10) NOT NULL DEFAULT 'CNY',
        payload TEXT,
        processed_at DATETIME,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uk_event_key (event_key),
        INDEX idx_site_id (site_id),
        INDEX idx_order_no (order_no),
        INDEX idx_transaction_id (transaction_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensure_column($pdo, 'payment_webhook_events', 'site_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 10001');
    ensure_column($pdo, 'payment_webhook_events', 'channel_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 0');
    ensure_column($pdo, 'payment_webhook_events', 'provider', "VARCHAR(40) NOT NULL DEFAULT 'manual'");
    ensure_column($pdo, 'payment_webhook_events', 'event_key', 'VARCHAR(180) NOT NULL');

    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_proofs (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 10001,
        order_id BIGINT UNSIGNED NOT NULL,
        order_no VARCHAR(40) NOT NULL,
        phone VARCHAR(60) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency VARCHAR(10) NOT NULL DEFAULT 'CNY',
        reference VARCHAR(120) NOT NULL,
        note TEXT,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        handled_by BIGINT UNSIGNED,
        handled_at DATETIME,
        admin_note TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_site_id (site_id),
        INDEX idx_order_no (order_no),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensure_column($pdo, 'payment_proofs', 'site_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 10001');
    ensure_column($pdo, 'payment_proofs', 'status', "VARCHAR(30) NOT NULL DEFAULT 'pending'");

    $pdo->exec("CREATE TABLE IF NOT EXISTS form_submissions (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 10001,
        form_key VARCHAR(80) NOT NULL,
        source_url VARCHAR(255),
        data TEXT,
        ip_address VARCHAR(80),
        user_agent VARCHAR(255),
        status VARCHAR(30) NOT NULL DEFAULT 'new',
        remark TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_site_id (site_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensure_column($pdo, 'form_submissions', 'site_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 10001');

    $pdo->exec("CREATE TABLE IF NOT EXISTS site_visits (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 10001,
        visitor_key CHAR(64) NOT NULL,
        session_id VARCHAR(80),
        path VARCHAR(255),
        title VARCHAR(120),
        referrer VARCHAR(255),
        ip_address VARCHAR(80),
        user_agent VARCHAR(255),
        created_at DATETIME NOT NULL,
        INDEX idx_created_at (created_at),
        INDEX idx_visitor_key (visitor_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensure_column($pdo, 'site_visits', 'site_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 10001');

    $pdo->exec("CREATE TABLE IF NOT EXISTS api_rate_limits (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        rate_key CHAR(64) NOT NULL UNIQUE,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 10001,
        action VARCHAR(80) NOT NULL,
        counter INT UNSIGNED NOT NULL DEFAULT 0,
        window_started_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_site_action (site_id, action),
        INDEX idx_updated_at (updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    ensure_collector_tables($pdo);
    ensure_ai_task_tables($pdo);

    $exists = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    if ($exists === 0) {
        $username = env_value('HJ_ADMIN_USERNAME', 'admin');
        $password = env_value('HJ_ADMIN_PASSWORD', 'admin123456');
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, display_name, role, status, created_at, updated_at)
            VALUES (:username, :password_hash, '化简管理员', 'admin', 'active', :created_at, :updated_at)");
        $time = now();
        $stmt->execute([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }
}

function create_order_no(): string
{
    return 'ZS' . date('YmdHis') . strtoupper(bin2hex(random_bytes(3)));
}

function normalize_order_items(array $items): array
{
    $normalized = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $quantity = max(1, (int)($item['quantity'] ?? 1));
        $price = max(0, (float)($item['price'] ?? 0));
        $normalized[] = [
            'product_id' => (int)($item['product_id'] ?? 0),
            'title' => trim((string)($item['title'] ?? '')),
            'sku' => trim((string)($item['sku'] ?? '')),
            'quantity' => $quantity,
            'price' => $price,
            'amount' => round($quantity * $price, 2),
        ];
    }
    return $normalized;
}

function content_relation_exists(PDO $pdo, string $type, int $contentId, int $siteId): bool
{
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM content_site_relations WHERE content_type = ? AND content_id = ? AND site_id = ?');
        $stmt->execute([$type, $contentId, $siteId]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $error) {
        return $siteId === 10001;
    }
}

function reserve_order_stock(PDO $pdo, int $siteId, array $items): array
{
    $normalized = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $quantity = max(1, (int)($item['quantity'] ?? 1));
        $productId = (int)($item['product_id'] ?? 0);
        if ($productId <= 0) {
            $price = max(0, (float)($item['price'] ?? 0));
            $normalized[] = [
                'product_id' => 0,
                'title' => trim((string)($item['title'] ?? '')),
                'sku' => trim((string)($item['sku'] ?? '')),
                'quantity' => $quantity,
                'price' => $price,
                'amount' => round($quantity * $price, 2),
                'stock_reserved' => false,
            ];
            continue;
        }
        $stmt = $pdo->prepare('SELECT id, title, sku, price, stock, status FROM products WHERE id = ? LIMIT 1 FOR UPDATE');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product || (string)($product['status'] ?? '') !== 'published' || !content_relation_exists($pdo, 'product', $productId, $siteId)) {
            fail('商品不存在或当前站点不可售', 'PRODUCT_UNAVAILABLE', 422);
        }
        $stock = (int)($product['stock'] ?? 0);
        if ($stock < $quantity) {
            fail('商品库存不足', 'OUT_OF_STOCK', 422, [
                'product_id' => $productId,
                'stock' => $stock,
                'requested' => $quantity,
            ]);
        }
        $update = $pdo->prepare('UPDATE products SET stock = stock - :quantity, updated_at = :updated_at WHERE id = :id AND stock >= :quantity');
        $update->execute([
            'id' => $productId,
            'quantity' => $quantity,
            'updated_at' => now(),
        ]);
        if ($update->rowCount() !== 1) {
            fail('商品库存不足', 'OUT_OF_STOCK', 422, ['product_id' => $productId]);
        }
        $price = max(0, (float)($product['price'] ?? 0));
        $normalized[] = [
            'product_id' => $productId,
            'title' => (string)($product['title'] ?? ''),
            'sku' => (string)($product['sku'] ?? ''),
            'quantity' => $quantity,
            'price' => $price,
            'amount' => round($quantity * $price, 2),
            'stock_reserved' => true,
        ];
    }
    return $normalized;
}

function restore_order_stock(PDO $pdo, array $order): bool
{
    if ((int)($order['stock_reserved'] ?? 0) !== 1) {
        return false;
    }
    $items = [];
    try {
        $items = json_decode((string)($order['items'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $error) {
        $items = [];
    }
    $restored = false;
    foreach (is_array($items) ? $items : [] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $productId = (int)($item['product_id'] ?? 0);
        $quantity = max(0, (int)($item['quantity'] ?? 0));
        if ($productId <= 0 || $quantity <= 0 || empty($item['stock_reserved'])) {
            continue;
        }
        $stmt = $pdo->prepare('UPDATE products SET stock = stock + :quantity, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'id' => $productId,
            'quantity' => $quantity,
            'updated_at' => now(),
        ]);
        $restored = true;
    }
    if ($restored) {
        $pdo->prepare('UPDATE orders SET stock_reserved = 0, updated_at = :updated_at WHERE id = :id')->execute([
            'id' => (int)$order['id'],
            'updated_at' => now(),
        ]);
    }
    return $restored;
}

function bearer_token(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
        return trim($matches[1]);
    }
    return '';
}

function current_user(PDO $pdo): ?array
{
    $token = bearer_token();
    if ($token === '') {
        return null;
    }
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.display_name, u.role, u.customer_id
        FROM admin_sessions s
        JOIN admin_users u ON u.id = s.user_id
        WHERE s.token_hash = :token_hash AND s.expires_at > :now AND u.status = 'active'
        LIMIT 1");
    $stmt->execute([
        'token_hash' => hash('sha256', $token),
        'now' => now(),
    ]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_login(PDO $pdo): array
{
    $user = current_user($pdo);
    if (!$user) {
        fail('请先登录', 'UNAUTHORIZED', 401);
    }
    $GLOBALS['AUTH_USER'] = $user;
    return $user;
}

function auth_user(): ?array
{
    return $GLOBALS['AUTH_USER'] ?? null;
}

function is_platform_admin(?array $user = null): bool
{
    $user = $user ?: auth_user();
    return in_array((string)($user['role'] ?? ''), ['admin', 'platform_admin', 'super_admin'], true);
}

function require_platform_admin(PDO $pdo): array
{
    $user = require_login($pdo);
    if (!is_platform_admin($user)) {
        fail('当前账号无权访问平台后台', 'FORBIDDEN', 403);
    }
    return $user;
}

function allowed_site_ids_for_user(PDO $main, ?array $user = null): ?array
{
    $user = $user ?: auth_user();
    if (!$user || is_platform_admin($user)) {
        return null;
    }
    $customerId = (int)($user['customer_id'] ?? 0);
    if ($customerId <= 0) {
        return [];
    }
    ensure_center_tables($main);
    $stmt = $main->prepare('SELECT id FROM sites WHERE customer_id = ? ORDER BY id ASC');
    $stmt->execute([$customerId]);
    return array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)), fn($id) => $id > 0));
}

function auth_context_payload(PDO $sitePdo, PDO $main, array $user): array
{
    ensure_center_tables($main);
    $platformAdmin = is_platform_admin($user);
    $allowedSiteIds = allowed_site_ids_for_user($main, $user);
    $customer = current_customer($main, $user);
    $quota = customer_quota_summary($sitePdo, $main, $user);
    $siteRows = filter_sites_for_user($main, center_site_items($main, $sitePdo), $user);
    $sites = array_map(static function (array $site): array {
        return [
            'id' => (int)($site['id'] ?? 0),
            'name' => (string)($site['name'] ?? ''),
            'site_key' => (string)($site['site_key'] ?? ''),
            'domain' => (string)($site['domain'] ?? ''),
            'subdomain' => (string)($site['subdomain'] ?? ''),
            'status' => (string)($site['status'] ?? ''),
        ];
    }, array_slice($siteRows, 0, 100));
    return [
        'id' => (int)$user['id'],
        'username' => (string)$user['username'],
        'display_name' => (string)($user['display_name'] ?? $user['username']),
        'role' => (string)$user['role'],
        'role_label' => $platformAdmin ? '平台管理员' : '客户管理员',
        'customer_id' => (int)($user['customer_id'] ?? 0),
        'customer' => $customer ? [
            'id' => (int)$customer['id'],
            'name' => (string)$customer['name'],
            'company' => (string)($customer['company'] ?? ''),
            'plan_key' => (string)($customer['plan_key'] ?? 'starter'),
            'status' => (string)($customer['status'] ?? ''),
            'expires_at' => (string)($customer['expires_at'] ?? ''),
        ] : null,
        'permissions' => $platformAdmin
            ? ['platform:*', 'sites:*', 'content:*', 'orders:*', 'deploy:*']
            : ['sites:own', 'content:own', 'orders:own', 'deploy:own'],
        'site_scope' => $platformAdmin ? 'all' : 'customer',
        'allowed_site_ids' => $allowedSiteIds,
        'sites' => $sites,
        'site_count' => count($siteRows),
        'quota' => $quota,
    ];
}

function assert_site_access(int $siteId, ?PDO $main = null): void
{
    $user = auth_user();
    if (!$user || is_platform_admin($user)) {
        return;
    }
    $allowed = allowed_site_ids_for_user($main ?: main_pdo(), $user);
    if (!in_array($siteId, $allowed ?? [], true)) {
        fail('当前账号无权访问该站点', 'FORBIDDEN_SITE', 403);
    }
}

function requested_site_scope(?PDO $main = null): ?array
{
    $raw = $_GET['site_id'] ?? 'all';
    if ($raw === '' || $raw === 'all') {
        return allowed_site_ids_for_user($main ?: main_pdo());
    }
    $siteId = resolve_request_site_id(['site_id' => $raw]);
    assert_site_access($siteId, $main);
    return [$siteId];
}

function append_site_scope_clause(array &$clauses, array &$params, string $column = 'site_id', string $prefix = 'scope_site'): void
{
    $scope = requested_site_scope();
    if ($scope === null) {
        return;
    }
    if (!$scope) {
        $clauses[] = '1 = 0';
        return;
    }
    $placeholders = [];
    foreach (array_values($scope) as $index => $siteId) {
        $key = $prefix . $index;
        $placeholders[] = ':' . $key;
        $params[$key] = (int)$siteId;
    }
    $clauses[] = $column . ' IN (' . implode(',', $placeholders) . ')';
}

function site_scope_where_sql(string $column = 'site_id'): string
{
    $scope = requested_site_scope();
    if ($scope === null) {
        return '';
    }
    if (!$scope) {
        return '1 = 0';
    }
    return $column . ' IN (' . implode(',', array_map('intval', $scope)) . ')';
}

$method = $_SERVER['REQUEST_METHOD'];
$path = preg_replace('#^/api#', '', $requestPath);

try {
    $pdo = site_pdo();
    ensure_auth_tables($pdo);

    if ($method === 'POST' && $path === '/auth/login') {
        $data = body_json();
        require_fields($data, ['username', 'password']);
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$data['username']]);
        $user = $stmt->fetch();
        if (!$user || !password_verify((string)$data['password'], (string)$user['password_hash'])) {
            fail('账号或密码错误', 'INVALID_CREDENTIALS', 401);
        }
        $token = bin2hex(random_bytes(32));
        $time = now();
        $expiresAt = date('Y-m-d H:i:s', time() + 86400 * 7);
        $sessionStmt = $pdo->prepare("INSERT INTO admin_sessions (user_id, token_hash, ip_address, user_agent, expires_at, created_at)
            VALUES (:user_id, :token_hash, :ip_address, :user_agent, :expires_at, :created_at)");
        $sessionStmt->execute([
            'user_id' => $user['id'],
            'token_hash' => hash('sha256', $token),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'expires_at' => $expiresAt,
            'created_at' => $time,
        ]);
        $pdo->prepare('UPDATE admin_users SET last_login_at = ?, updated_at = ? WHERE id = ?')->execute([$time, $time, $user['id']]);
        ok([
            'token' => $token,
            'expires_at' => $expiresAt,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'role' => $user['role'],
                'customer_id' => (int)($user['customer_id'] ?? 0),
            ],
        ], '登录成功');
    }

    if ($method === 'POST' && $path === '/auth/logout') {
        $token = bearer_token();
        if ($token !== '') {
            $stmt = $pdo->prepare('DELETE FROM admin_sessions WHERE token_hash = ?');
            $stmt->execute([hash('sha256', $token)]);
        }
        ok([], '已退出');
    }

    if ($method === 'GET' && $path === '/auth/me') {
        ok(auth_context_payload($pdo, main_pdo(), require_login($pdo)));
    }

    if ($method === 'POST' && $path === '/analytics/visit') {
        record_site_visit($pdo);
    }

    if ($method === 'POST' && $path === '/forms/submit') {
        $data = body_json();
        $siteId = resolve_request_site_id($data);
        require_fields($data, ['form_key', 'data']);
        if (!is_array($data['data'])) {
            fail('表单数据格式错误', 'VALIDATION_ERROR', 422);
        }
        assert_public_rate_limit($pdo, $siteId, 'forms.submit', [(string)$data['form_key']], 5, 300);
        $stmt = $pdo->prepare("INSERT INTO form_submissions (site_id, form_key, source_url, data, ip_address, user_agent, status, created_at, updated_at)
            VALUES (:site_id, :form_key, :source_url, :data, :ip_address, :user_agent, 'new', :created_at, :updated_at)");
        $time = now();
        $stmt->execute([
            'site_id' => $siteId,
            'form_key' => $data['form_key'],
            'source_url' => $data['source_url'] ?? '',
            'data' => json_encode($data['data'], JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        ok(['id' => (int)$pdo->lastInsertId()], '提交成功');
    }

    if ($method === 'POST' && $path === '/orders') {
        $data = body_json();
        $siteId = resolve_request_site_id($data);
        require_fields($data, ['customer_name', 'phone', 'items']);
        if (!is_array($data['items'])) {
            fail('订单商品格式错误', 'VALIDATION_ERROR', 422);
        }
        assert_public_rate_limit($pdo, $siteId, 'orders.create', [trim((string)$data['phone'])], 3, 300);
        ensure_content_distribution_table($pdo);
        $pdo->beginTransaction();
        try {
            $items = reserve_order_stock($pdo, $siteId, $data['items']);
            if (!$items) {
                $pdo->rollBack();
                fail('订单至少需要一个商品', 'VALIDATION_ERROR', 422);
            }
            $total = array_reduce($items, fn($sum, $item) => $sum + (float)$item['amount'], 0.0);
            $stockReserved = array_reduce($items, fn($reserved, $item) => $reserved || !empty($item['stock_reserved']), false) ? 1 : 0;
            $time = now();
            $stmt = $pdo->prepare("INSERT INTO orders (site_id, order_no, customer_name, phone, email, address, items, total_amount, currency, payment_method, payment_status, fulfillment_status, remark, source_url, ip_address, user_agent, stock_reserved, created_at, updated_at)
                VALUES (:site_id, :order_no, :customer_name, :phone, :email, :address, :items, :total_amount, :currency, :payment_method, 'pending', 'new', :remark, :source_url, :ip_address, :user_agent, :stock_reserved, :created_at, :updated_at)");
            $stmt->execute([
                'site_id' => $siteId,
                'order_no' => create_order_no(),
                'customer_name' => trim((string)$data['customer_name']),
                'phone' => trim((string)$data['phone']),
                'email' => trim((string)($data['email'] ?? '')),
                'address' => trim((string)($data['address'] ?? '')),
                'items' => json_encode($items, JSON_UNESCAPED_UNICODE),
                'total_amount' => round($total, 2),
                'currency' => trim((string)($data['currency'] ?? 'CNY')) ?: 'CNY',
                'payment_method' => trim((string)($data['payment_method'] ?? 'manual')) ?: 'manual',
                'remark' => trim((string)($data['remark'] ?? '')),
                'source_url' => trim((string)($data['source_url'] ?? '')),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'stock_reserved' => $stockReserved,
                'created_at' => $time,
                'updated_at' => $time,
            ]);
            $orderId = (int)$pdo->lastInsertId();
            $pdo->commit();
            ok(fetch_one($pdo, 'orders', $orderId), '订单已创建');
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    if ($method === 'POST' && $path === '/orders/lookup') {
        $data = body_json();
        $siteId = resolve_request_site_id($data);
        require_fields($data, ['order_no', 'phone']);
        assert_public_rate_limit($pdo, $siteId, 'orders.lookup', [trim((string)$data['phone'])], 20, 60);
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_no = :order_no AND phone = :phone LIMIT 1');
        $stmt->execute([
            'order_no' => trim((string)$data['order_no']),
            'phone' => trim((string)$data['phone']),
        ]);
        $order = $stmt->fetch();
        $order ? ok(public_order_view($order), '查询成功') : fail('未找到匹配订单，请检查订单号和手机号', 'NOT_FOUND', 404);
    }

    if ($method === 'POST' && $path === '/orders/customer-note') {
        $data = body_json();
        $siteId = resolve_request_site_id($data);
        require_fields($data, ['order_no', 'phone', 'note']);
        assert_public_rate_limit($pdo, $siteId, 'orders.customer_note.lookup', [trim((string)$data['phone'])], 20, 60);
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_no = :order_no AND phone = :phone LIMIT 1');
        $stmt->execute([
            'order_no' => trim((string)$data['order_no']),
            'phone' => trim((string)$data['phone']),
        ]);
        $order = $stmt->fetch();
        if (!$order) {
            fail('未找到匹配订单，请检查订单号和手机号', 'NOT_FOUND', 404);
        }
        assert_public_rate_limit($pdo, (int)($order['site_id'] ?? $siteId), 'orders.customer_note', [trim((string)$data['order_no']), trim((string)$data['phone'])], 5, 300);
        $type = trim((string)($data['type'] ?? '补充说明'));
        $allowedTypes = ['付款说明', '开票需求', '售后说明', '补充说明'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = '补充说明';
        }
        $note = trim((string)$data['note']);
        if ($note === '') {
            fail('请填写说明内容', 'VALIDATION_ERROR', 422);
        }
        if (mb_strlen($note, 'UTF-8') > 500) {
            fail('说明内容不能超过 500 个字', 'VALIDATION_ERROR', 422);
        }
        $remark = append_order_note((string)($order['remark'] ?? ''), '客户提交' . $type . '：' . $note);
        $update = $pdo->prepare('UPDATE orders SET remark = :remark, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            'id' => (int)$order['id'],
            'remark' => $remark,
            'updated_at' => now(),
        ]);
        ok(public_order_view(fetch_one($pdo, 'orders', (int)$order['id']) ?: $order), '说明已提交');
    }

    if ($method === 'POST' && $path === '/orders/payment-proof') {
        $data = body_json();
        $siteId = resolve_request_site_id($data);
        require_fields($data, ['order_no', 'phone', 'amount', 'reference']);
        assert_public_rate_limit($pdo, $siteId, 'orders.payment_proof.lookup', [trim((string)$data['phone'])], 20, 60);
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_no = :order_no AND phone = :phone LIMIT 1');
        $stmt->execute([
            'order_no' => trim((string)$data['order_no']),
            'phone' => trim((string)$data['phone']),
        ]);
        $order = $stmt->fetch();
        if (!$order) {
            fail('未找到匹配订单，请检查订单号和手机号', 'NOT_FOUND', 404);
        }
        assert_public_rate_limit($pdo, (int)($order['site_id'] ?? $siteId), 'orders.payment_proof', [trim((string)$data['order_no']), trim((string)$data['phone'])], 5, 300);
        $amount = trim((string)$data['amount']);
        $reference = trim((string)$data['reference']);
        $note = trim((string)($data['note'] ?? ''));
        if ($amount === '' || !is_numeric($amount) || (float)$amount <= 0) {
            fail('请填写有效付款金额', 'VALIDATION_ERROR', 422);
        }
        if ($reference === '') {
            fail('请填写付款流水号或截图编号', 'VALIDATION_ERROR', 422);
        }
        if (mb_strlen($reference, 'UTF-8') > 120 || mb_strlen($note, 'UTF-8') > 500) {
            fail('付款凭证内容过长', 'VALIDATION_ERROR', 422);
        }
        $proofText = '客户提交付款凭证：金额 ' . number_format((float)$amount, 2, '.', '') . '；流水号/截图编号 ' . $reference;
        if ($note !== '') {
            $proofText .= '；说明 ' . $note;
        }
        $time = now();
        $insertProof = $pdo->prepare("INSERT INTO payment_proofs (site_id, order_id, order_no, phone, amount, currency, reference, note, status, created_at, updated_at)
            VALUES (:site_id, :order_id, :order_no, :phone, :amount, :currency, :reference, :note, 'pending', :created_at, :updated_at)");
        $insertProof->execute([
            'site_id' => (int)($order['site_id'] ?? $siteId),
            'order_id' => (int)$order['id'],
            'order_no' => (string)$order['order_no'],
            'phone' => trim((string)$data['phone']),
            'amount' => round((float)$amount, 2),
            'currency' => (string)($order['currency'] ?? 'CNY'),
            'reference' => $reference,
            'note' => $note,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        $proofId = (int)$pdo->lastInsertId();
        $remark = append_order_note((string)($order['remark'] ?? ''), $proofText);
        $update = $pdo->prepare('UPDATE orders SET remark = :remark, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            'id' => (int)$order['id'],
            'remark' => $remark,
            'updated_at' => $time,
        ]);
        ok([
            'order' => public_order_view(fetch_one($pdo, 'orders', (int)$order['id']) ?: $order),
            'proof' => fetch_one($pdo, 'payment_proofs', $proofId),
        ], '付款凭证已提交');
    }

    if ($method === 'POST' && $path === '/orders/service-request') {
        $data = body_json();
        $siteId = resolve_request_site_id($data);
        require_fields($data, ['order_no', 'phone', 'type', 'message']);
        assert_public_rate_limit($pdo, $siteId, 'orders.service_request.lookup', [trim((string)$data['phone'])], 20, 60);
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_no = :order_no AND phone = :phone LIMIT 1');
        $stmt->execute([
            'order_no' => trim((string)$data['order_no']),
            'phone' => trim((string)$data['phone']),
        ]);
        $order = $stmt->fetch();
        if (!$order) {
            fail('未找到匹配订单，请检查订单号和手机号', 'NOT_FOUND', 404);
        }
        assert_public_rate_limit($pdo, (int)($order['site_id'] ?? $siteId), 'orders.service_request', [trim((string)$data['order_no']), trim((string)$data['phone'])], 5, 300);
        $type = trim((string)$data['type']);
        $allowedTypes = ['催发货', '修改收货信息', '售后问题', '其他服务'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = '其他服务';
        }
        $message = trim((string)$data['message']);
        if ($message === '') {
            fail('请填写服务请求内容', 'VALIDATION_ERROR', 422);
        }
        if (mb_strlen($message, 'UTF-8') > 500) {
            fail('服务请求内容不能超过 500 个字', 'VALIDATION_ERROR', 422);
        }
        $remark = append_order_note((string)($order['remark'] ?? ''), '客户服务请求-' . $type . '：' . $message);
        $update = $pdo->prepare('UPDATE orders SET remark = :remark, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            'id' => (int)$order['id'],
            'remark' => $remark,
            'updated_at' => now(),
        ]);
        ok(public_order_view(fetch_one($pdo, 'orders', (int)$order['id']) ?: $order), '服务请求已提交');
    }

    if ($method === 'POST' && $path === '/payment/webhook') {
        ok(handle_payment_webhook($pdo, main_pdo()), '支付回调已处理');
    }

    $user = require_login($pdo);

    if (str_starts_with($path, '/platform/')) {
        require_platform_admin($pdo);
    }

    if ($method === 'GET' && $path === '/dashboard/metrics') {
        ok(dashboard_metrics($pdo));
    }

    if ($method === 'GET' && $path === '/dashboard/todos') {
        ok(dashboard_todos($pdo, main_pdo()));
    }

    if ($method === 'GET' && $path === '/operation-logs') {
        ok(list_operation_logs(main_pdo()));
    }

    if ($method === 'GET' && $path === '/platform/overview') {
        $main = main_pdo();
        ensure_center_tables($main);
        ok([
            'customers' => (int)$main->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
            'active_customers' => (int)$main->query("SELECT COUNT(*) FROM customers WHERE status = 'active'")->fetchColumn(),
            'sites' => (int)$main->query('SELECT COUNT(*) FROM sites')->fetchColumn(),
            'active_sites' => (int)$main->query("SELECT COUNT(*) FROM sites WHERE status = 'active'")->fetchColumn(),
            'deploy_nodes' => (int)$main->query('SELECT COUNT(*) FROM deploy_nodes')->fetchColumn(),
            'active_deploy_nodes' => (int)$main->query("SELECT COUNT(*) FROM deploy_nodes WHERE status = 'active'")->fetchColumn(),
            'ai_providers' => (int)$main->query('SELECT COUNT(*) FROM ai_providers')->fetchColumn(),
            'active_ai_providers' => (int)$main->query("SELECT COUNT(*) FROM ai_providers WHERE status = 'enabled'")->fetchColumn(),
        ]);
    }

    if ($method === 'GET' && $path === '/platform/customers') {
        ok(list_platform_customers(main_pdo()));
    }

    if ($method === 'POST' && $path === '/platform/customers') {
        ok(save_platform_customer(main_pdo(), body_json()), '客户已保存');
    }

    if ($params = route_param('/platform/customers/{id}', $path)) {
        $id = (int)$params['id'];
        $main = main_pdo();
        ensure_center_tables($main);
        if ($method === 'GET') {
            $item = fetch_one($main, 'customers', $id);
            if (!$item) {
                fail('客户不存在', 'NOT_FOUND', 404);
            }
            ok($item);
        }
        if ($method === 'PUT') {
            ok(save_platform_customer($main, body_json(), $id), '客户已保存');
        }
        if ($method === 'DELETE') {
            $stmt = $main->prepare('SELECT COUNT(*) FROM sites WHERE customer_id = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                fail('客户名下还有站点，不能删除', 'CUSTOMER_HAS_SITES', 409);
            }
            $main->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
            ok([], '客户已删除');
        }
    }

    if ($params = route_param('/platform/customers/{id}/admin-user', $path)) {
        if ($method === 'POST' || $method === 'PUT') {
            ok(save_customer_admin_user($pdo, main_pdo(), (int)$params['id'], body_json()), '客户中台账号已保存');
        }
    }

    if ($method === 'GET' && $path === '/platform/sites') {
        $main = main_pdo();
        ensure_center_tables($main);
        $stmt = $main->query("SELECT s.*, c.name AS customer_name, n.name AS deploy_node_name
            FROM sites s
            LEFT JOIN customers c ON c.id = s.customer_id
            LEFT JOIN deploy_nodes n ON n.id = s.deploy_node_id
            ORDER BY s.id DESC");
        ok(['items' => $stmt->fetchAll()]);
    }

    if ($method === 'GET' && $path === '/platform/deploy-tasks') {
        ok(list_deploy_tasks(main_pdo()));
    }

    if ($method === 'GET' && $path === '/platform/domain-applications') {
        ok(list_domain_applications(main_pdo(), $user, true));
    }

    if ($params = route_param('/platform/domain-applications/{id}', $path)) {
        if ($method === 'PUT') {
            ok(update_domain_application(main_pdo(), (int)$params['id'], body_json()), '域名申请已处理');
        }
    }

    if ($method === 'GET' && $path === '/platform/deploy-nodes') {
        ok(list_deploy_nodes(main_pdo()));
    }

    if ($method === 'GET' && $path === '/platform/ai-providers') {
        ok(list_ai_providers(main_pdo()));
    }

    if ($method === 'POST' && $path === '/platform/ai-providers') {
        ok(save_ai_provider(main_pdo(), body_json()), 'AI 服务已保存');
    }

    if ($params = route_param('/platform/ai-providers/{id}/test', $path)) {
        if ($method === 'POST') {
            ok(test_ai_provider(main_pdo(), (int)$params['id']), 'AI 服务检查完成');
        }
    }

    if ($params = route_param('/platform/ai-providers/{id}/apply', $path)) {
        if ($method === 'POST') {
            $result = apply_ai_provider_to_site(main_pdo(), $pdo, (int)$params['id']);
            if (isset($result['site']) && is_array($result['site'])) {
                $result['site'] = sanitize_site_settings_for_response($result['site']);
            }
            ok($result, 'AI 服务已应用到当前站点');
        }
    }

    if ($params = route_param('/platform/ai-providers/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'PUT') {
            ok(save_ai_provider(main_pdo(), body_json(), $id), 'AI 服务已保存');
        }
        if ($method === 'DELETE') {
            $main = main_pdo();
            ensure_center_tables($main);
            $main->prepare('DELETE FROM ai_providers WHERE id = ?')->execute([$id]);
            ok([], 'AI 服务已删除');
        }
    }

    if ($method === 'POST' && $path === '/platform/deploy-nodes') {
        ok(save_deploy_node(main_pdo(), body_json()), '部署节点已保存');
    }

    if ($params = route_param('/platform/deploy-nodes/{id}/test', $path)) {
        if ($method === 'POST') {
            ok(test_deploy_node(main_pdo(), (int)$params['id']), '部署节点检查完成');
        }
    }

    if ($params = route_param('/platform/deploy-nodes/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'PUT') {
            ok(save_deploy_node(main_pdo(), body_json(), $id), '部署节点已保存');
        }
        if ($method === 'DELETE') {
            $main = main_pdo();
            ensure_center_tables($main);
            $main->prepare('UPDATE sites SET deploy_node_id = NULL WHERE deploy_node_id = ?')->execute([$id]);
            $main->prepare('DELETE FROM deploy_nodes WHERE id = ?')->execute([$id]);
            ok([], '部署节点已删除');
        }
    }

    if ($method === 'GET' && $path === '/sites') {
        $main = main_pdo();
        $items = filter_sites_for_user($main, center_site_items($main, $pdo), $user);
        $currentSiteId = (int)($_SERVER['HTTP_X_SITE_ID'] ?? 0);
        if (!$currentSiteId || !in_array($currentSiteId, array_map(fn($item) => (int)$item['id'], $items), true)) {
            $currentSiteId = (int)($items[0]['id'] ?? 10001);
        }
        ok([
            'items' => $items,
            'overview' => center_overview($items),
            'quota' => customer_quota_summary($pdo, $main, $user),
            'current_site_id' => $currentSiteId,
        ]);
    }

    if ($method === 'POST' && $path === '/sites') {
        $data = body_json();
        require_fields($data, ['name']);
        $main = main_pdo();
        ensure_center_tables($main);
        $payload = normalize_center_site_payload($data);
        $customerId = current_customer_id_for_create($user, $data);
        assert_site_quota($main, $customerId);
        $now = now();
        $stmt = $main->prepare("INSERT INTO sites (customer_id, name, site_key, deploy_node_id, domain, subdomain, language, template_key, database_name, public_path, deploy_config_json, status, created_at, updated_at)
            VALUES (:customer_id, :name, '', :deploy_node_id, :domain, :subdomain, :language, :template_key, :database_name, '', :deploy_config_json, :status, :created_at, :updated_at)");
        $stmt->execute([
            'customer_id' => $customerId,
            'name' => $payload['name'],
            'deploy_node_id' => $payload['deploy_node_id'] ?: null,
            'domain' => $payload['domain'],
            'subdomain' => $payload['subdomain'],
            'language' => $payload['language'],
            'template_key' => $payload['template_key'],
            'deploy_config_json' => json_encode($payload['deploy'], JSON_UNESCAPED_UNICODE),
            'status' => $payload['status'],
            'database_name' => env_value('HJ_DB_SITE', 'huajian_site_10001'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int)$main->lastInsertId();
        $siteKey = 'site_' . $id;
        $publicPath = 'sites/' . $siteKey . '/public';
        ensure_dir(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . $siteKey . DIRECTORY_SEPARATOR . 'public');
        $update = $main->prepare('UPDATE sites SET site_key = :site_key, subdomain = :subdomain, public_path = :public_path WHERE id = :id');
        $update->execute([
            'id' => $id,
            'site_key' => $siteKey,
            'subdomain' => $siteKey . '.huajian.local',
            'public_path' => $publicPath,
        ]);
        $items = filter_sites_for_user($main, center_site_items($main, $pdo), $user);
        ok([
            'site' => fetch_one($main, 'sites', $id),
            'items' => $items,
            'overview' => center_overview($items),
        ], '站点已创建');
    }

    if ($params = route_param('/sites/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'PUT') {
            $main = main_pdo();
            assert_site_access($id, $main);
            $siteItem = update_center_site($main, $id, body_json(), $pdo);
            $items = filter_sites_for_user($main, center_site_items($main, $pdo), $user);
            ok([
                'site' => $siteItem,
                'items' => $items,
                'overview' => center_overview($items),
            ], '站点已保存');
        }
    }

    if ($method === 'GET' && $path === '/site/settings') {
        ok(sanitize_site_settings_for_response(site_settings($pdo)));
    }

    if ($method === 'GET' && $path === '/site/pages') {
        ok(site_static_pages($pdo));
    }

    if ($method === 'GET' && $path === '/site/modules') {
        ok(read_config_json('module-registry.json'));
    }

    if ($method === 'GET' && $path === '/site/templates') {
        ok(template_registry());
    }

    if ($method === 'GET' && $path === '/template-clone/tasks') {
        ok(list_template_clone_tasks(main_pdo()));
    }

    if ($method === 'POST' && $path === '/template-clone/tasks') {
        ok(create_template_clone_task(main_pdo(), body_json()), '模板草稿已生成');
    }

    if ($params = route_param('/template-clone/tasks/{id}/apply', $path)) {
        if ($method === 'POST') {
            ok(apply_template_clone_task(main_pdo(), $pdo, (int)$params['id']), '模板草稿已应用到当前站点');
        }
    }

    if ($params = route_param('/template-clone/tasks/{id}/preview', $path)) {
        if ($method === 'POST') {
            ok(preview_template_clone_task(main_pdo(), $pdo, (int)$params['id']), '模板草稿预览已生成');
        }
    }

    if ($params = route_param('/template-clone/tasks/{id}', $path)) {
        if ($method === 'DELETE') {
            delete_template_clone_task(main_pdo(), (int)$params['id']);
            ok([], '模板克隆任务已删除');
        }
    }

    if ($method === 'GET' && $path === '/seo/audit') {
        ok(seo_audit($pdo, main_pdo()));
    }

    if ($method === 'GET' && $path === '/tasks/stream') {
        ok(list_task_stream($pdo, main_pdo()));
    }

    if ($method === 'POST' && $path === '/seo/fix') {
        ok(seo_fix($pdo, main_pdo(), body_json()), 'SEO 修复已完成');
    }

    if ($method === 'GET' && $path === '/payment/channels') {
        ok(list_payment_channels(main_pdo()));
    }

    if ($method === 'GET' && $path === '/payment/events') {
        ok(list_payment_webhook_events($pdo, main_pdo()));
    }

    if ($method === 'GET' && $path === '/payment/proofs') {
        ok(list_payment_proofs($pdo, main_pdo()));
    }

    if ($method === 'POST' && preg_match('#^/payment/proofs/(\d+)/handle$#', $path, $matches)) {
        ok(handle_payment_proof($pdo, (int)$matches[1], body_json()), '付款凭证已处理');
    }

    if ($method === 'GET' && $path === '/site/domains') {
        ok(list_site_domains(main_pdo(), requested_site_id()));
    }

    if ($method === 'GET' && $path === '/domain-applications') {
        ok(list_domain_applications(main_pdo(), $user, false));
    }

    if ($method === 'POST' && $path === '/domain-applications') {
        ok(create_domain_application(main_pdo(), requested_site_id(), body_json(), $user), '域名申请已提交');
    }

    if ($method === 'POST' && $path === '/site/domains') {
        $item = save_site_domain(main_pdo(), requested_site_id(), body_json());
        if (!empty($item['is_primary'])) {
            $settings = site_settings($pdo);
            $settings['domain'] = (string)$item['domain'];
            save_site_settings($pdo, $settings);
        }
        ok($item, '域名已保存');
    }

    if ($method === 'POST' && $path === '/site/domains/check-all') {
        ok(check_all_site_domains(main_pdo(), requested_site_id()), '域名批量检查完成');
    }

    if ($params = route_param('/site/domains/{id}/check', $path)) {
        if ($method === 'POST') {
            ok(check_site_domain(main_pdo(), requested_site_id(), (int)$params['id']), '域名检查完成');
        }
    }

    if ($params = route_param('/site/domains/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'PUT') {
            $item = save_site_domain(main_pdo(), requested_site_id(), body_json(), $id);
            if (!empty($item['is_primary'])) {
                $settings = site_settings($pdo);
                $settings['domain'] = (string)$item['domain'];
                save_site_settings($pdo, $settings);
            }
            ok($item, '域名已保存');
        }
        if ($method === 'DELETE') {
            $main = main_pdo();
            ensure_center_tables($main);
            $item = fetch_one($main, 'site_domains', $id);
            if (!$item || (int)$item['site_id'] !== requested_site_id()) {
                fail('域名不存在', 'NOT_FOUND', 404);
            }
            $main->prepare('DELETE FROM site_domains WHERE id = ?')->execute([$id]);
            if (!empty($item['is_primary'])) {
                $fallback = $main->prepare('SELECT * FROM site_domains WHERE site_id = ? ORDER BY id DESC LIMIT 1');
                $fallback->execute([requested_site_id()]);
                $next = $fallback->fetch();
                if ($next) {
                    sync_primary_domain($main, requested_site_id(), (int)$next['id'], (string)$next['domain']);
                    $settings = site_settings($pdo);
                    $settings['domain'] = (string)$next['domain'];
                    save_site_settings($pdo, $settings);
                } else {
                    $main->prepare('UPDATE sites SET domain = "", updated_at = :updated_at WHERE id = :site_id')->execute(['site_id' => requested_site_id(), 'updated_at' => now()]);
                    $settings = site_settings($pdo);
                    $settings['domain'] = '';
                    save_site_settings($pdo, $settings);
                }
            }
            ok([], '域名已删除');
        }
    }

    if ($method === 'GET' && $path === '/batch/tasks') {
        ok(list_batch_tasks(main_pdo()));
    }

    if ($method === 'GET' && $path === '/batch/tasks/export') {
        export_batch_tasks_csv(main_pdo());
    }

    if ($method === 'POST' && $path === '/batch/tasks') {
        ok(save_batch_task(main_pdo(), body_json()), '批量任务已记录');
    }

    if ($method === 'POST' && $path === '/payment/channels') {
        ok(save_payment_channel(main_pdo(), body_json()), '支付通道已保存');
    }

    if ($params = route_param('/payment/channels/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'PUT') {
            ok(save_payment_channel(main_pdo(), body_json(), $id), '支付通道已保存');
        }
        if ($method === 'DELETE') {
            $main = main_pdo();
            ensure_center_tables($main);
            $main->prepare('DELETE FROM payment_channel_sites WHERE channel_id = ?')->execute([$id]);
            $main->prepare('DELETE FROM payment_channels WHERE id = ?')->execute([$id]);
            ok([], '支付通道已删除');
        }
    }

    if ($method === 'POST' && $path === '/payment/channels/apply') {
        $data = body_json();
        $channelId = (int)($data['channel_id'] ?? 0);
        if ($channelId <= 0) {
            fail('请选择支付通道', 'VALIDATION_ERROR', 422);
        }
        $result = apply_payment_channel_to_site(main_pdo(), $pdo, $channelId);
        if (isset($result['site']) && is_array($result['site'])) {
            $result['site'] = sanitize_site_settings_for_response($result['site']);
        }
        ok($result, '支付通道已应用到当前站点');
    }

    if ($method === 'PUT' && $path === '/site/settings') {
        $data = body_json();
        if (!empty($data['_preserve_service_configs'])) {
            $data = preserve_service_configs($data, site_settings($pdo, requested_site_id()));
        }
        ok(sanitize_site_settings_for_response(save_site_settings($pdo, $data)), '保存成功');
    }

    if ($method === 'PUT' && $path === '/site/settings-default') {
        $data = body_json();
        if (!empty($data['_preserve_service_configs'])) {
            $data = preserve_service_configs($data, site_settings($pdo, 10001));
        }
        ok(sanitize_site_settings_for_response(save_site_settings($pdo, $data, 10001)), '公共默认设置已保存');
    }

    if ($method === 'POST' && $path === '/site/settings/apply-all') {
        $data = body_json();
        ok(apply_site_settings_to_all(main_pdo(), $pdo, $data), '站点设置已应用到全部站点');
    }

    if ($method === 'GET' && $path === '/ai/tasks') {
        ok(list_ai_tasks($pdo, main_pdo()));
    }

    if ($method === 'POST' && $path === '/ai/tasks') {
        ok(create_ai_task($pdo, body_json()), 'AI 任务已创建');
    }

    if ($params = route_param('/ai/tasks/{id}/confirm', $path)) {
        if ($method === 'POST') {
            ok(confirm_ai_task($pdo, (int)$params['id'], body_json()), 'AI 任务已确认');
        }
    }

    if ($params = route_param('/ai/tasks/{id}', $path)) {
        if ($method === 'DELETE') {
            ensure_ai_task_tables($pdo);
            $pdo->prepare('DELETE FROM ai_tasks WHERE id = ?')->execute([(int)$params['id']]);
            ok([], 'AI 任务已删除');
        }
    }

    if ($method === 'POST' && $path === '/ai/generate') {
        $data = body_json();
        require_fields($data, ['type', 'prompt']);
        $type = (string)$data['type'];
        if (!in_array($type, ['article', 'product'], true)) {
            fail('生成类型不支持', 'VALIDATION_ERROR', 422);
        }
        $prompt = trim((string)$data['prompt']);
        consume_ai_quota(main_pdo(), $user, 1);
        $site = site_settings($pdo);
        $fallback = local_ai_draft($type, $prompt, $site);
        $remote = remote_ai_draft($type, $prompt, $site);
        if ($remote) {
            ok(['source' => 'remote', 'draft' => array_replace($fallback, $remote)], '生成成功');
        }
        ok(['source' => 'local', 'draft' => $fallback], '生成成功');
    }

    if ($method === 'POST' && $path === '/ai/page-plan') {
        $data = body_json();
        require_fields($data, ['prompt']);
        $site = site_settings($pdo);
        $registry = read_config_json('module-registry.json');
        ok(local_page_plan((string)$data['prompt'], $site, $registry), '生成成功');
    }

    if ($method === 'POST' && $path === '/ai/batch-articles') {
        $data = body_json();
        require_fields($data, ['prompt']);
        $prompt = trim((string)$data['prompt']);
        $count = min(20, max(1, (int)($data['count'] ?? 5)));
        $siteIds = normalize_site_ids($data);
        consume_ai_quota(main_pdo(), $user, $count);
        $status = in_array(($data['status'] ?? 'draft'), ['draft', 'published'], true) ? $data['status'] : 'draft';
        $site = site_settings($pdo);
        $created = [];
        $createdIds = [];
        $angles = ['行业趋势', '选型指南', '应用案例', 'SEO 获客', '产品卖点', '客户痛点', '解决方案', '常见问题', '运营方法', '转化路径'];
        for ($i = 1; $i <= $count; $i++) {
            $angle = $angles[($i - 1) % count($angles)];
            $itemPrompt = "{$angle}：{$prompt}（第 {$i} 篇，避免重复）";
            $fallback = local_ai_draft('article', $itemPrompt, $site);
            $remote = remote_ai_draft('article', $itemPrompt, $site);
            $draft = $remote ? array_replace($fallback, $remote) : $fallback;
            $draft['title'] = text_limit((string)($draft['title'] ?? "{$prompt} {$i}"), 120);
            $draft['slug'] = substr(draft_slug((string)($draft['slug'] ?? $draft['title']), 'article') . '-' . date('His') . '-' . $i, 0, 180);
            $draft['status'] = $status;
            $draft['published_at'] = $status === 'published' ? now() : null;
            $id = insert_article($pdo, $draft);
            $createdIds[] = $id;
            sync_content_distribution($pdo, 'article', $id, $siteIds);
            $created[] = attach_distribution($pdo, 'article', [fetch_one($pdo, 'articles', $id)])[0];
        }
        $task = record_ai_batch_content_task($pdo, 'article', $prompt, $siteIds, $createdIds);
        ok(['items' => $created, 'count' => count($created), 'task' => $task], '批量生成成功');
    }

    if ($method === 'POST' && $path === '/ai/batch-products') {
        $data = body_json();
        require_fields($data, ['prompt']);
        $prompt = trim((string)$data['prompt']);
        $count = min(20, max(1, (int)($data['count'] ?? 5)));
        $siteIds = normalize_site_ids($data);
        consume_ai_quota(main_pdo(), $user, $count);
        $status = in_array(($data['status'] ?? 'draft'), ['draft', 'published'], true) ? $data['status'] : 'draft';
        $site = site_settings($pdo);
        $created = [];
        $createdIds = [];
        $angles = ['标准款', '专业款', '旗舰款', '入门套装', '行业方案', '高续航版', '轻量版', '企业定制版', '巡检版', '营销组合'];
        for ($i = 1; $i <= $count; $i++) {
            $angle = $angles[($i - 1) % count($angles)];
            $itemPrompt = "{$angle}：{$prompt}（第 {$i} 个商品，避免重复）";
            $fallback = local_ai_draft('product', $itemPrompt, $site);
            $remote = remote_ai_draft('product', $itemPrompt, $site);
            $draft = $remote ? array_replace($fallback, $remote) : $fallback;
            $draft['title'] = text_limit((string)($draft['title'] ?? "{$prompt} {$i}"), 120);
            $draft['slug'] = substr(draft_slug((string)($draft['slug'] ?? $draft['title']), 'product') . '-' . date('His') . '-' . $i, 0, 180);
            $draft['sku'] = (string)($draft['sku'] ?? ('HJ-' . date('His') . '-' . $i));
            $draft['status'] = $status;
            $draft['published_at'] = $status === 'published' ? now() : null;
            $id = insert_product($pdo, $draft);
            $createdIds[] = $id;
            sync_content_distribution($pdo, 'product', $id, $siteIds);
            $created[] = attach_distribution($pdo, 'product', [fetch_one($pdo, 'products', $id)])[0];
        }
        $task = record_ai_batch_content_task($pdo, 'product', $prompt, $siteIds, $createdIds);
        ok(['items' => $created, 'count' => count($created), 'task' => $task], '批量生成成功');
    }

    if ($method === 'POST' && $path === '/ai/generate-image') {
        $data = body_json();
        require_fields($data, ['type', 'prompt']);
        consume_ai_quota(main_pdo(), $user, 1);
        $type = in_array(($data['type'] ?? 'article'), ['article', 'product'], true) ? $data['type'] : 'article';
        $prompt = trim((string)$data['prompt']);
        $title = trim((string)($data['title'] ?? ''));
        $media = generate_cover_svg($pdo, $type, $title, $prompt);
        ok(['media' => $media, 'path' => $media['file_path'] ?? ''], '封面生成成功');
    }

    if ($method === 'GET' && $path === '/categories') {
        ok(['items' => $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll()]);
    }

    if ($method === 'POST' && $path === '/categories') {
        $data = body_json();
        require_fields($data, ['name', 'slug']);
        $stmt = $pdo->prepare("INSERT INTO categories (parent_id, name, slug, description, sort_order, seo_title, seo_keywords, seo_description, created_at, updated_at)
            VALUES (:parent_id, :name, :slug, :description, :sort_order, :seo_title, :seo_keywords, :seo_description, :created_at, :updated_at)");
        $time = now();
        $stmt->execute([
            'parent_id' => $data['parent_id'] ?? 0,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
            'seo_title' => $data['seo_title'] ?? '',
            'seo_keywords' => $data['seo_keywords'] ?? '',
            'seo_description' => $data['seo_description'] ?? '',
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        ok(['id' => (int)$pdo->lastInsertId()], '创建成功');
    }

    if ($params = route_param('/categories/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'PUT') {
            $data = body_json();
            require_fields($data, ['name', 'slug']);
            $stmt = $pdo->prepare("UPDATE categories SET parent_id=:parent_id, name=:name, slug=:slug, description=:description, sort_order=:sort_order, seo_title=:seo_title, seo_keywords=:seo_keywords, seo_description=:seo_description, updated_at=:updated_at WHERE id=:id");
            $stmt->execute([
                'id' => $id,
                'parent_id' => $data['parent_id'] ?? 0,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? '',
                'sort_order' => $data['sort_order'] ?? 0,
                'seo_title' => $data['seo_title'] ?? '',
                'seo_keywords' => $data['seo_keywords'] ?? '',
                'seo_description' => $data['seo_description'] ?? '',
                'updated_at' => now(),
            ]);
            ok(fetch_one($pdo, 'categories', $id), '保存成功');
        }
        if ($method === 'DELETE') {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            ok([], '删除成功');
        }
    }

    if ($method === 'GET' && $path === '/product-categories') {
        ok(['items' => $pdo->query('SELECT * FROM product_categories ORDER BY sort_order ASC, id ASC')->fetchAll()]);
    }

    if ($method === 'POST' && $path === '/product-categories') {
        $data = body_json();
        require_fields($data, ['name', 'slug']);
        $stmt = $pdo->prepare("INSERT INTO product_categories (parent_id, name, slug, cover, description, sort_order, seo_title, seo_keywords, seo_description, created_at, updated_at)
            VALUES (:parent_id, :name, :slug, :cover, :description, :sort_order, :seo_title, :seo_keywords, :seo_description, :created_at, :updated_at)");
        $time = now();
        $stmt->execute([
            'parent_id' => $data['parent_id'] ?? 0,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'cover' => $data['cover'] ?? '',
            'description' => $data['description'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
            'seo_title' => $data['seo_title'] ?? '',
            'seo_keywords' => $data['seo_keywords'] ?? '',
            'seo_description' => $data['seo_description'] ?? '',
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        ok(['id' => (int)$pdo->lastInsertId()], '创建成功');
    }

    if ($params = route_param('/product-categories/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'PUT') {
            $data = body_json();
            require_fields($data, ['name', 'slug']);
            $stmt = $pdo->prepare("UPDATE product_categories SET parent_id=:parent_id, name=:name, slug=:slug, cover=:cover, description=:description, sort_order=:sort_order, seo_title=:seo_title, seo_keywords=:seo_keywords, seo_description=:seo_description, updated_at=:updated_at WHERE id=:id");
            $stmt->execute([
                'id' => $id,
                'parent_id' => $data['parent_id'] ?? 0,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'cover' => $data['cover'] ?? '',
                'description' => $data['description'] ?? '',
                'sort_order' => $data['sort_order'] ?? 0,
                'seo_title' => $data['seo_title'] ?? '',
                'seo_keywords' => $data['seo_keywords'] ?? '',
                'seo_description' => $data['seo_description'] ?? '',
                'updated_at' => now(),
            ]);
            ok(fetch_one($pdo, 'product_categories', $id), '保存成功');
        }
        if ($method === 'DELETE') {
            $pdo->prepare('DELETE FROM product_categories WHERE id = ?')->execute([$id]);
            ok([], '删除成功');
        }
    }

    if ($method === 'GET' && $path === '/tags') {
        ensure_article_tag_tables($pdo);
        $items = $pdo->query("SELECT t.*, COUNT(at.article_id) AS article_count
            FROM tags t
            LEFT JOIN article_tags at ON at.tag_id = t.id
            GROUP BY t.id
            ORDER BY article_count DESC, t.name ASC")->fetchAll();
        ok(['items' => $items]);
    }

    if ($method === 'POST' && $path === '/tags') {
        ensure_article_tag_tables($pdo);
        $data = body_json();
        require_fields($data, ['name']);
        $name = mb_substr(trim((string)$data['name']), 0, 80, 'UTF-8');
        if ($name === '') {
            fail('标签名称不能为空', 'VALIDATION_ERROR', 422);
        }
        $slug = trim((string)($data['slug'] ?? '')) ?: normalize_tag_slug($name);
        $slug = normalize_tag_slug($slug);
        $time = now();
        $stmt = $pdo->prepare("INSERT INTO tags (name, slug, description, created_at, updated_at)
            VALUES (:name, :slug, :description, :created_at, :updated_at)");
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        ok(fetch_one($pdo, 'tags', (int)$pdo->lastInsertId()), '创建成功');
    }

    if ($params = route_param('/tags/{id}', $path)) {
        ensure_article_tag_tables($pdo);
        $id = (int)$params['id'];
        if ($method === 'PUT') {
            $data = body_json();
            require_fields($data, ['name']);
            $name = mb_substr(trim((string)$data['name']), 0, 80, 'UTF-8');
            if ($name === '') {
                fail('标签名称不能为空', 'VALIDATION_ERROR', 422);
            }
            $slug = trim((string)($data['slug'] ?? '')) ?: normalize_tag_slug($name);
            $slug = normalize_tag_slug($slug);
            $stmt = $pdo->prepare("UPDATE tags SET name=:name, slug=:slug, description=:description, updated_at=:updated_at WHERE id=:id");
            $stmt->execute([
                'id' => $id,
                'name' => $name,
                'slug' => $slug,
                'description' => $data['description'] ?? '',
                'updated_at' => now(),
            ]);
            ok(fetch_one($pdo, 'tags', $id), '保存成功');
        }
        if ($method === 'DELETE') {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM article_tags WHERE tag_id = ?');
            $countStmt->execute([$id]);
            if ((int)$countStmt->fetchColumn() > 0) {
                fail('标签已被文章使用，请先从文章中移除后再删除', 'TAG_IN_USE', 422);
            }
            $pdo->prepare('DELETE FROM tags WHERE id = ?')->execute([$id]);
            ok([], '删除成功');
        }
    }

    if ($method === 'GET' && $path === '/pages') {
        ensure_pages_table($pdo);
        ensure_content_distribution_table($pdo);
        [$distributionWhere, $distributionParams] = content_distribution_filter_clause('page', 'pages');
        $where = $distributionWhere ? [$distributionWhere] : [];
        $result = paginate($pdo, 'pages', $where, 'id DESC', 'title', $distributionParams);
        $result['items'] = attach_distribution($pdo, 'page', $result['items']);
        ok($result);
    }

    if ($method === 'POST' && $path === '/pages') {
        $data = body_json();
        $id = insert_page($pdo, $data);
        sync_content_distribution($pdo, 'page', $id, normalize_site_ids($data));
        ok(['id' => $id], '创建成功');
    }

    if ($params = route_param('/pages/{id}/publish', $path)) {
        if ($method === 'POST') {
            $item = publish_content_item($pdo, 'page', (int)$params['id'], body_json());
            ok($item, $item['status'] === 'published' ? '页面已发布' : '页面已转为草稿');
        }
    }

    if ($params = route_param('/pages/{id}', $path)) {
        ensure_pages_table($pdo);
        $id = (int)$params['id'];
        if ($method === 'GET') {
            $item = fetch_one($pdo, 'pages', $id);
            if ($item) {
                $item = attach_distribution($pdo, 'page', [$item])[0];
            }
            $item ? ok($item) : fail('页面不存在', 'NOT_FOUND', 404);
        }
        if ($method === 'PUT') {
            $data = body_json();
            require_fields($data, ['title', 'slug']);
            assert_page_slug_available($pdo, (string)$data['slug'], $id);
            $stmt = $pdo->prepare("UPDATE pages SET title=:title, slug=:slug, cover=:cover, summary=:summary, content=:content, seo_title=:seo_title, seo_keywords=:seo_keywords, seo_description=:seo_description, status=:status, published_at=:published_at, updated_at=:updated_at WHERE id=:id");
            $stmt->execute([
                'id' => $id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'cover' => $data['cover'] ?? '',
                'summary' => $data['summary'] ?? '',
                'content' => $data['content'] ?? '',
                'seo_title' => $data['seo_title'] ?? $data['title'],
                'seo_keywords' => $data['seo_keywords'] ?? '',
                'seo_description' => $data['seo_description'] ?? ($data['summary'] ?? ''),
                'status' => $data['status'] ?? 'draft',
                'published_at' => $data['published_at'] ?? null,
                'updated_at' => now(),
            ]);
            sync_content_distribution($pdo, 'page', $id, normalize_site_ids($data));
            $item = attach_distribution($pdo, 'page', [fetch_one($pdo, 'pages', $id)])[0];
            ok($item, '保存成功');
        }
        if ($method === 'DELETE') {
            ensure_content_distribution_table($pdo);
            $pdo->prepare("DELETE FROM content_site_relations WHERE content_type = 'page' AND content_id = ?")->execute([$id]);
            $pdo->prepare('DELETE FROM pages WHERE id = ?')->execute([$id]);
            ok([], '删除成功');
        }
    }

    if ($method === 'GET' && $path === '/articles') {
        ensure_content_distribution_table($pdo);
        [$distributionWhere, $distributionParams] = content_distribution_filter_clause('article', 'articles');
        $where = $distributionWhere ? [$distributionWhere] : [];
        $result = paginate($pdo, 'articles', $where, 'published_at DESC, id DESC', 'title', $distributionParams);
        $result['items'] = attach_distribution($pdo, 'article', $result['items']);
        $result['items'] = attach_article_tags($pdo, $result['items']);
        ok($result);
    }

    if ($method === 'POST' && $path === '/articles') {
        $data = body_json();
        $id = insert_article($pdo, $data);
        sync_content_distribution($pdo, 'article', $id, normalize_site_ids($data));
        ok(['id' => $id], '创建成功');
    }

    if ($params = route_param('/articles/{id}/publish', $path)) {
        if ($method === 'POST') {
            $item = publish_content_item($pdo, 'article', (int)$params['id'], body_json());
            ok($item, $item['status'] === 'published' ? '文章已发布' : '文章已转为草稿');
        }
    }

    if ($params = route_param('/articles/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'GET') {
            $item = fetch_one($pdo, 'articles', $id);
            if ($item) {
                $item = attach_distribution($pdo, 'article', [$item])[0];
                $item = attach_article_tags($pdo, [$item])[0];
            }
            $item ? ok($item) : fail('文章不存在', 'NOT_FOUND', 404);
        }
        if ($method === 'PUT') {
            $data = body_json();
            require_fields($data, ['title', 'slug']);
            $stmt = $pdo->prepare("UPDATE articles SET category_id=:category_id, title=:title, slug=:slug, cover=:cover, summary=:summary, content=:content, seo_title=:seo_title, seo_keywords=:seo_keywords, seo_description=:seo_description, status=:status, published_at=:published_at, updated_at=:updated_at WHERE id=:id");
            $stmt->execute([
                'id' => $id,
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'cover' => $data['cover'] ?? '',
                'summary' => $data['summary'] ?? '',
                'content' => $data['content'] ?? '',
                'seo_title' => $data['seo_title'] ?? $data['title'],
                'seo_keywords' => $data['seo_keywords'] ?? '',
                'seo_description' => $data['seo_description'] ?? ($data['summary'] ?? ''),
                'status' => $data['status'] ?? 'draft',
                'published_at' => $data['published_at'] ?? null,
                'updated_at' => now(),
            ]);
            sync_content_distribution($pdo, 'article', $id, normalize_site_ids($data));
            sync_article_tags($pdo, $id, $data['tags'] ?? $data['tag_names'] ?? '');
            $item = attach_distribution($pdo, 'article', [fetch_one($pdo, 'articles', $id)])[0];
            $item = attach_article_tags($pdo, [$item])[0];
            ok($item, '保存成功');
        }
        if ($method === 'DELETE') {
            ensure_content_distribution_table($pdo);
            $pdo->prepare("DELETE FROM content_site_relations WHERE content_type = 'article' AND content_id = ?")->execute([$id]);
            ensure_article_tag_tables($pdo);
            $pdo->prepare('DELETE FROM article_tags WHERE article_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
            ok([], '删除成功');
        }
    }

    if ($method === 'GET' && $path === '/products') {
        ensure_content_distribution_table($pdo);
        [$distributionWhere, $distributionParams] = content_distribution_filter_clause('product', 'products');
        $where = $distributionWhere ? [$distributionWhere] : [];
        $result = paginate($pdo, 'products', $where, 'id DESC', 'title', $distributionParams);
        $result['items'] = attach_distribution($pdo, 'product', $result['items']);
        ok($result);
    }

    if ($method === 'POST' && $path === '/products') {
        $data = body_json();
        $id = insert_product($pdo, $data);
        sync_content_distribution($pdo, 'product', $id, normalize_site_ids($data));
        ok(['id' => $id], '创建成功');
    }

    if ($params = route_param('/products/{id}/publish', $path)) {
        if ($method === 'POST') {
            $item = publish_content_item($pdo, 'product', (int)$params['id'], body_json());
            ok($item, $item['status'] === 'published' ? '商品已发布' : '商品已转为草稿');
        }
    }

    if ($params = route_param('/products/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'GET') {
            $item = fetch_one($pdo, 'products', $id);
            if ($item) {
                $item = attach_distribution($pdo, 'product', [$item])[0];
            }
            $item ? ok($item) : fail('商品不存在', 'NOT_FOUND', 404);
        }
        if ($method === 'PUT') {
            $data = body_json();
            require_fields($data, ['title', 'slug']);
            $stmt = $pdo->prepare("UPDATE products SET category_id=:category_id, title=:title, slug=:slug, sku=:sku, cover=:cover, gallery=:gallery, summary=:summary, description=:description, price=:price, market_price=:market_price, stock=:stock, attributes=:attributes, seo_title=:seo_title, seo_keywords=:seo_keywords, seo_description=:seo_description, status=:status, published_at=:published_at, updated_at=:updated_at WHERE id=:id");
            $stmt->execute([
                'id' => $id,
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'sku' => $data['sku'] ?? '',
                'cover' => $data['cover'] ?? '',
                'gallery' => json_encode($data['gallery'] ?? []),
                'summary' => $data['summary'] ?? '',
                'description' => $data['description'] ?? '',
                'price' => $data['price'] ?? 0,
                'market_price' => $data['market_price'] ?? 0,
                'stock' => $data['stock'] ?? 0,
                'attributes' => json_encode($data['attributes'] ?? []),
                'seo_title' => $data['seo_title'] ?? $data['title'],
                'seo_keywords' => $data['seo_keywords'] ?? '',
                'seo_description' => $data['seo_description'] ?? ($data['summary'] ?? ''),
                'status' => $data['status'] ?? 'draft',
                'published_at' => $data['published_at'] ?? null,
                'updated_at' => now(),
            ]);
            sync_content_distribution($pdo, 'product', $id, normalize_site_ids($data));
            $item = attach_distribution($pdo, 'product', [fetch_one($pdo, 'products', $id)])[0];
            ok($item, '保存成功');
        }
        if ($method === 'DELETE') {
            ensure_content_distribution_table($pdo);
            $pdo->prepare("DELETE FROM content_site_relations WHERE content_type = 'product' AND content_id = ?")->execute([$id]);
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            ok([], '删除成功');
        }
    }

    if ($method === 'GET' && $path === '/orders') {
        ok(list_orders($pdo, main_pdo()));
    }

    if ($method === 'GET' && $path === '/orders/export') {
        export_orders_csv($pdo);
    }

    if ($method === 'GET' && $path === '/orders/service-requests') {
        ok(list_order_service_requests_scoped($pdo, main_pdo()));
    }

    if ($method === 'GET' && $path === '/support/tickets') {
        ok(list_support_tickets($pdo, main_pdo()));
    }

    if ($method === 'POST' && $path === '/orders/service-requests/resolve') {
        ok(resolve_order_service_requests($pdo), '服务请求已处理');
    }

    if ($params = route_param('/orders/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'GET') {
            $item = fetch_one($pdo, 'orders', $id);
            $item ? ok($item) : fail('订单不存在', 'NOT_FOUND', 404);
        }
        if ($method === 'PUT') {
            $data = body_json();
            $current = fetch_one($pdo, 'orders', $id);
            if (!$current) {
                fail('订单不存在', 'NOT_FOUND', 404);
            }
            $paymentStatus = $data['payment_status'] ?? ($current['payment_status'] ?? 'pending');
            $fulfillmentStatus = $data['fulfillment_status'] ?? ($current['fulfillment_status'] ?? 'new');
            $trackingCompany = trim((string)($data['tracking_company'] ?? ($current['tracking_company'] ?? '')));
            $trackingNo = trim((string)($data['tracking_no'] ?? ($current['tracking_no'] ?? '')));
            $followupNote = trim((string)($data['followup_note'] ?? ''));
            $remark = array_key_exists('remark', $data) ? (string)$data['remark'] : (string)($current['remark'] ?? '');
            if ($followupNote !== '' && trim($remark) === '' && trim((string)($current['remark'] ?? '')) !== '') {
                $remark = (string)$current['remark'];
            }
            if ($followupNote !== '') {
                $remark = append_order_note($remark, $followupNote);
            }
            $paidAt = $current['paid_at'] ?? null;
            if ($paymentStatus === 'paid' && empty($paidAt)) {
                $paidAt = now();
                $remark = append_order_note($remark, '订单标记为已支付');
            }
            $shippedAt = $current['shipped_at'] ?? null;
            if ($fulfillmentStatus === 'shipped' && empty($shippedAt)) {
                $shippedAt = now();
                $shipmentText = $trackingNo ? "订单标记为已发货，物流单号：{$trackingNo}" : '订单标记为已发货';
                $remark = append_order_note($remark, $shipmentText);
            }
            if (($paymentStatus === 'refunded' || $fulfillmentStatus === 'closed') && (int)($current['stock_reserved'] ?? 0) === 1) {
                if (restore_order_stock($pdo, $current)) {
                    $remark = append_order_note($remark, '订单关闭或退款，系统已回补商品库存');
                }
            }
            $stmt = $pdo->prepare("UPDATE orders SET payment_status=:payment_status, fulfillment_status=:fulfillment_status, tracking_company=:tracking_company, tracking_no=:tracking_no, paid_at=:paid_at, shipped_at=:shipped_at, remark=:remark, updated_at=:updated_at WHERE id=:id");
            $stmt->execute([
                'id' => $id,
                'payment_status' => $paymentStatus,
                'fulfillment_status' => $fulfillmentStatus,
                'tracking_company' => $trackingCompany,
                'tracking_no' => $trackingNo,
                'paid_at' => $paidAt ?: null,
                'shipped_at' => $shippedAt ?: null,
                'remark' => $remark,
                'updated_at' => now(),
            ]);
            ok(fetch_one($pdo, 'orders', $id), '订单已更新');
        }
        if ($method === 'DELETE') {
            $current = fetch_one($pdo, 'orders', $id);
            if ($current) {
                restore_order_stock($pdo, $current);
                $pdo->prepare('DELETE FROM payment_proofs WHERE order_id = ?')->execute([$id]);
            }
            $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
            ok([], '订单已删除');
        }
    }

    if ($method === 'GET' && $path === '/media') {
        ensure_media_site_column($pdo);
        $fileType = trim((string)($_GET['file_type'] ?? ''));
        $where = ['site_id = :site_id'];
        $params = ['site_id' => requested_site_id()];
        if ($fileType !== '') {
            $where[] = 'file_type = :file_type';
            $params['file_type'] = $fileType;
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 24)));
        $offset = ($page - 1) * $pageSize;
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM media{$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM media{$whereSql} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
        $stmt->execute($params);
        ok([
            'items' => $stmt->fetchAll(),
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'total_pages' => (int)ceil($total / $pageSize),
            ],
        ]);
    }

    if ($method === 'POST' && $path === '/media/upload') {
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            fail('请选择上传文件', 'NO_UPLOAD_FILE', 422);
        }
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            fail('上传失败', 'UPLOAD_ERROR', 422, ['error' => $file['error']]);
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            fail('文件不能超过 10MB', 'FILE_TOO_LARGE', 422);
        }
        assert_storage_quota($pdo, main_pdo(), $user, (int)$file['size']);
        $originalName = basename((string)$file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        if (!in_array($ext, $allowed, true)) {
            fail('不允许上传该文件类型', 'FILE_TYPE_NOT_ALLOWED', 422);
        }
        $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $fileType = in_array($ext, $imageExts, true) ? 'image' : 'file';
        $folder = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_POST['folder'] ?? 'images')) ?: 'images';
        $relativeDir = 'uploads/' . $folder . '/' . date('Ym');
        $targetDir = public_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        ensure_dir($targetDir);
        $safeName = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            fail('保存上传文件失败', 'UPLOAD_SAVE_FAILED', 500);
        }
        $relativePath = $relativeDir . '/' . $safeName;
        $width = null;
        $height = null;
        if ($fileType === 'image' && $ext !== 'svg') {
            $size = @getimagesize($targetPath);
            if ($size) {
                $width = $size[0];
                $height = $size[1];
            }
        }
        ensure_media_site_column($pdo);
        $stmt = $pdo->prepare("INSERT INTO media (site_id, file_name, file_path, file_type, mime_type, file_size, width, height, alt_text, source_type, created_at, updated_at)
            VALUES (:site_id, :file_name, :file_path, :file_type, :mime_type, :file_size, :width, :height, :alt_text, 'upload', :created_at, :updated_at)");
        $time = now();
        $stmt->execute([
            'site_id' => requested_site_id(),
            'file_name' => $originalName,
            'file_path' => $relativePath,
            'file_type' => $fileType,
            'mime_type' => $file['type'] ?? '',
            'file_size' => $file['size'],
            'width' => $width,
            'height' => $height,
            'alt_text' => $_POST['alt_text'] ?? '',
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        ok(fetch_one($pdo, 'media', (int)$pdo->lastInsertId()), '上传成功');
    }

    if ($params = route_param('/media/{id}', $path)) {
        $id = (int)$params['id'];
        ensure_media_site_column($pdo);
        $item = fetch_one($pdo, 'media', $id);
        if ($item) {
            assert_site_access((int)($item['site_id'] ?? 10001), main_pdo());
        }
        if ($method === 'PUT') {
            $data = body_json();
            $stmt = $pdo->prepare("UPDATE media SET alt_text=:alt_text, updated_at=:updated_at WHERE id=:id");
            $stmt->execute([
                'id' => $id,
                'alt_text' => $data['alt_text'] ?? '',
                'updated_at' => now(),
            ]);
            ok(fetch_one($pdo, 'media', $id), '保存成功');
        }
        if ($method === 'DELETE') {
            if ($item) {
                $filePath = public_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $item['file_path']);
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }
            $pdo->prepare('DELETE FROM media WHERE id = ?')->execute([$id]);
            ok([], '删除成功');
        }
    }

    if ($method === 'GET' && $path === '/forms/submissions') {
        ok(list_form_submissions($pdo, main_pdo()));
    }

    if ($params = route_param('/forms/submissions/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'GET') {
            $item = fetch_one($pdo, 'form_submissions', $id);
            $item ? ok($item) : fail('留言不存在', 'NOT_FOUND', 404);
        }
        if ($method === 'PUT') {
            $data = body_json();
            $stmt = $pdo->prepare("UPDATE form_submissions SET status=:status, remark=:remark, updated_at=:updated_at WHERE id=:id");
            $stmt->execute([
                'id' => $id,
                'status' => $data['status'] ?? 'new',
                'remark' => $data['remark'] ?? '',
                'updated_at' => now(),
            ]);
            ok(fetch_one($pdo, 'form_submissions', $id), '保存成功');
        }
        if ($method === 'DELETE') {
            $pdo->prepare('DELETE FROM form_submissions WHERE id = ?')->execute([$id]);
            ok([], '删除成功');
        }
    }

    if ($method === 'GET' && $path === '/collector/sources') {
        ok(list_collector_sources($pdo, main_pdo()));
    }

    if ($method === 'POST' && $path === '/collector/sources') {
        ensure_collector_tables($pdo);
        $data = body_json();
        require_fields($data, ['name', 'url']);
        $payload = normalize_collector_source($data);
        $stmt = $pdo->prepare("INSERT INTO collector_sources (site_id, site_ids, name, source_type, url, category_id, rewrite_mode, status, created_at, updated_at)
            VALUES (:site_id, :site_ids, :name, :source_type, :url, :category_id, :rewrite_mode, :status, :created_at, :updated_at)");
        $time = now();
        $stmt->execute($payload + ['created_at' => $time, 'updated_at' => $time]);
        ok(fetch_one($pdo, 'collector_sources', (int)$pdo->lastInsertId()), '采集源已创建');
    }

    if ($params = route_param('/collector/sources/{id}', $path)) {
        ensure_collector_tables($pdo);
        $id = (int)$params['id'];
        if ($method === 'PUT') {
            $current = fetch_one($pdo, 'collector_sources', $id);
            if (!$current) {
                fail('采集源不存在', 'NOT_FOUND', 404);
            }
            $payload = normalize_collector_source(body_json(), $current);
            $stmt = $pdo->prepare("UPDATE collector_sources SET site_id=:site_id, site_ids=:site_ids, name=:name, source_type=:source_type, url=:url, category_id=:category_id, rewrite_mode=:rewrite_mode, status=:status, updated_at=:updated_at WHERE id=:id");
            $stmt->execute($payload + ['id' => $id, 'updated_at' => now()]);
            ok(fetch_one($pdo, 'collector_sources', $id), '采集源已保存');
        }
        if ($method === 'DELETE') {
            $pdo->prepare('DELETE FROM collector_sources WHERE id = ?')->execute([$id]);
            ok([], '采集源已删除');
        }
    }

    if ($params = route_param('/collector/sources/{id}/run', $path)) {
        if ($method === 'POST') {
            ok(run_collector_source($pdo, (int)$params['id']), '采集完成');
        }
    }

    if ($method === 'GET' && $path === '/collector/records') {
        ok(list_collector_records($pdo, main_pdo()));
    }

    if ($method === 'POST' && $path === '/collector/records/manual') {
        ok(create_manual_collector_record($pdo, body_json()), '粘贴内容已进入采集记录');
    }

    if ($method === 'POST' && $path === '/collector/records/rewrite') {
        ok(rewrite_manual_collector_record($pdo, body_json()), 'AI 改写已生成待审核记录');
    }

    if ($params = route_param('/collector/records/{id}', $path)) {
        if ($method === 'DELETE') {
            ensure_collector_tables($pdo);
            $pdo->prepare('DELETE FROM collector_records WHERE id = ?')->execute([(int)$params['id']]);
            ok([], '采集记录已删除');
        }
    }

    if ($params = route_param('/collector/records/{id}/publish', $path)) {
        if ($method === 'POST') {
            $data = body_json();
            ok(publish_collector_record($pdo, (int)$params['id'], (string)($data['status'] ?? 'draft'), normalize_site_ids($data)), '已转为文章');
        }
    }

    if ($method === 'POST' && $path === '/site/generate') {
        $main = main_pdo();
        $currentSite = current_site($main, $pdo);
        $root = dirname(__DIR__, 2);
        $php = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
        $script = $root . DIRECTORY_SEPARATOR . 'worker' . DIRECTORY_SEPARATOR . 'GenerateSite.php';
        $publicPath = site_public_root($currentSite);
        ensure_dir($publicPath);
        putenv('HJ_SITE_KEY=' . (string)$currentSite['site_key']);
        putenv('HJ_SITE_ID=' . (string)$currentSite['id']);
        putenv('HJ_SITE_NAME=' . (string)$currentSite['name']);
        putenv('HJ_SITE_DOMAIN=' . (string)($currentSite['domain'] ?: $currentSite['subdomain']));
        putenv('HJ_SITE_LANGUAGE=' . (string)($currentSite['language'] ?: 'zh-CN'));
        putenv('HJ_TEMPLATE_KEY=' . (string)($currentSite['template_key'] ?: 'business-clean'));
        putenv('HJ_PUBLIC_PATH=' . $publicPath);
        $command = '"' . $php . '" "' . $script . '"';
        $output = [];
        $code = 0;
        exec($command, $output, $code);
        if ($code !== 0) {
            fail('生成失败', 'GENERATE_FAILED', 500, ['output' => $output]);
        }
        $versionNo = (string)$currentSite['site_key'] . '_version_' . date('Ymd_His');
        $summary = create_publish_snapshot($pdo, $currentSite, $versionNo, $publicPath, $output);
        ok($summary + ['version_no' => $versionNo], '生成成功');
    }

    if ($method === 'POST' && $path === '/site/deploy-test') {
        $main = main_pdo();
        $currentSite = current_site($main, $pdo);
        $site = site_settings($pdo);
        [$deploy, $configured] = deploy_config_status($currentSite, $site);
        $plan = build_deploy_plan($main, $currentSite, $deploy, $configured);
        $status = $configured ? 'ready' : 'pending';
        $mode = (string)($deploy['mode'] ?? 'manual');
        $message = '请先填写站点目录。';
        if ($configured && $mode === 'local-copy') {
            $message = '本机目录同步已配置，发布上线时会复制静态站并自动备份旧目录。';
        } elseif ($configured && $mode === 'bt-api') {
            $message = '宝塔 API 参数已填写，当前版本会生成发布包并记录待执行任务。';
        } elseif ($configured) {
            $message = '部署参数已填写，当前模式会生成发布包并记录待上传任务。';
        } elseif ($mode === 'bt-api') {
            $message = '请填写宝塔面板地址和站点目录。';
        }
        $summary = [
            'site_id' => (int)$currentSite['id'],
            'site_key' => $currentSite['site_key'],
            'site_name' => $currentSite['name'],
            'configured' => $configured,
            'panel_url' => $deploy['bt_panel_url'] ?? '',
            'site_path' => $deploy['site_path'] ?? '',
            'mode' => $mode,
            'message' => $message,
            'plan' => $plan,
        ];
        save_deploy_task($main, $currentSite, 'deploy-check', $status, $summary);
        ensure_publish_versions_site_column($pdo);
        $stmt = $pdo->prepare("INSERT INTO publish_versions (site_id, version_no, publish_type, file_path, status, summary, created_at)
            VALUES (:site_id, :version_no, 'deploy-check', :file_path, :status, :summary, :created_at)");
        $stmt->execute([
            'site_id' => (int)$currentSite['id'],
            'version_no' => (string)$currentSite['site_key'] . '_deploy_' . date('Ymd_His'),
            'file_path' => str_replace(DIRECTORY_SEPARATOR, '/', site_public_path($currentSite)),
            'status' => $status,
            'summary' => json_encode($summary, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
        ok($summary, '部署配置检查完成');
    }

    if ($method === 'GET' && $path === '/site/deploy-plan') {
        $main = main_pdo();
        $currentSite = current_site($main, $pdo);
        $site = site_settings($pdo);
        [$deploy, $configured] = deploy_config_status($currentSite, $site);
        ok(build_deploy_plan($main, $currentSite, $deploy, $configured), '部署计划已生成');
    }

    if ($method === 'POST' && $path === '/site/package') {
        $main = main_pdo();
        $currentSite = current_site($main, $pdo);
        $package = create_static_package($currentSite);
        $summary = [
            'site_id' => (int)$currentSite['id'],
            'site_key' => $currentSite['site_key'],
            'site_name' => $currentSite['name'],
            'file_count' => $package['file_count'],
            'file_size' => $package['file_size'],
            'package_path' => $package['file_path'],
            'message' => '发布包已生成，可下载后上传到宝塔站点目录解压。',
        ];
        ensure_publish_versions_site_column($pdo);
        $stmt = $pdo->prepare("INSERT INTO publish_versions (site_id, version_no, publish_type, file_path, status, summary, created_at)
            VALUES (:site_id, :version_no, 'package', :file_path, 'success', :summary, :created_at)");
        $stmt->execute([
            'site_id' => (int)$currentSite['id'],
            'version_no' => $package['version_no'],
            'file_path' => $package['file_path'],
            'summary' => json_encode($summary, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
        save_deploy_task(main_pdo(), $currentSite, 'package', 'success', $summary + ['version_no' => $package['version_no']]);
        ok($summary + ['version_no' => $package['version_no']], '发布包已生成');
    }

    if ($method === 'POST' && $path === '/site/deploy') {
        $main = main_pdo();
        $currentSite = current_site($main, $pdo);
        ok(execute_site_deploy($main, $pdo, $currentSite), '部署任务已创建');
    }

    if ($method === 'GET' && $path === '/deploy/tasks') {
        ok(list_deploy_tasks(main_pdo()));
    }

    if ($params = route_param('/deploy/tasks/{id}/retry', $path)) {
        if ($method === 'POST') {
            $main = main_pdo();
            $currentSite = current_site($main, $pdo);
            ok(retry_deploy_task($main, $pdo, $currentSite, (int)$params['id']), '部署任务已重试');
        }
    }

    if ($params = route_param('/deploy/tasks/{id}', $path)) {
        if ($method === 'GET') {
            ok(get_deploy_task(main_pdo(), (int)$params['id']));
        }
    }

    if ($method === 'GET' && $path === '/site/package-download') {
        $file = basename((string)($_GET['file'] ?? ''));
        if ($file === '' || !preg_match('/^(site_\d+_)?package_\d{8}_\d{6}\.tar\.gz$/', $file)) {
            fail('发布包不存在', 'PACKAGE_NOT_FOUND', 404);
        }
        $packageBase = realpath(package_root());
        $packagePath = realpath(package_root() . DIRECTORY_SEPARATOR . $file);
        if (!$packageBase || !$packagePath || !str_starts_with($packagePath, $packageBase) || !is_file($packagePath)) {
            fail('发布包不存在', 'PACKAGE_NOT_FOUND', 404);
        }
        header_remove('Content-Type');
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($packagePath));
        readfile($packagePath);
        exit;
    }

    if ($method === 'GET' && $path === '/site/publish-versions') {
        $main = main_pdo();
        $currentSite = current_site($main, $pdo);
        ok(list_publish_versions($pdo, (int)$currentSite['id']));
    }

    if ($method === 'GET' && $path === '/site/backups') {
        $main = main_pdo();
        $currentSite = current_site($main, $pdo);
        ok(list_site_backups($pdo, (int)$currentSite['id']));
    }

    if ($method === 'POST' && $path === '/site/backups') {
        $main = main_pdo();
        $currentSite = current_site($main, $pdo);
        $data = body_json();
        $backupType = in_array(($data['backup_type'] ?? 'manual'), ['manual', 'before-deploy', 'before-restore'], true) ? (string)($data['backup_type'] ?? 'manual') : 'manual';
        ok(create_site_backup($pdo, $currentSite, $backupType, (string)($data['message'] ?? '')), '站点备份已创建');
    }

    if ($params = route_param('/site/backups/{id}/restore', $path)) {
        if ($method === 'POST') {
            $main = main_pdo();
            $currentSite = current_site($main, $pdo);
            ok(restore_site_backup($pdo, $currentSite, (int)$params['id']), '站点备份已恢复');
        }
    }

    if ($params = route_param('/site/backups/{id}', $path)) {
        if ($method === 'DELETE') {
            $main = main_pdo();
            $currentSite = current_site($main, $pdo);
            ok(delete_site_backup($pdo, $currentSite, (int)$params['id']), '站点备份已删除');
        }
    }

    if ($method === 'POST' && $path === '/site/rollback') {
        $data = body_json();
        $versionId = (int)($data['version_id'] ?? 0);
        if ($versionId <= 0) {
            fail('请选择要回滚的版本', 'VALIDATION_ERROR', 422);
        }
        $main = main_pdo();
        $currentSite = current_site($main, $pdo);
        ok(rollback_publish_version($pdo, $currentSite, $versionId), '回滚成功');
    }

    fail('接口不存在', 'NOT_FOUND', 404);
} catch (Throwable $e) {
    fail($e->getMessage(), 'SERVER_ERROR', 500);
}
