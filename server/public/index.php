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
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        fail('JSON 格式错误', 'INVALID_JSON', 400);
    }
    return $data;
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

function paginate(PDO $pdo, string $table, array $where = [], string $order = 'id DESC', string $keywordColumn = 'title'): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));

    $clauses = $where;
    $params = [];
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
        storage_quota_mb INT UNSIGNED NOT NULL DEFAULT 1024,
        expires_at DATE,
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensure_column($main, 'customers', 'plan_key', "VARCHAR(60) NOT NULL DEFAULT 'starter'");
    ensure_column($main, 'customers', 'max_sites', 'INT UNSIGNED NOT NULL DEFAULT 10');
    ensure_column($main, 'customers', 'ai_quota', 'INT UNSIGNED NOT NULL DEFAULT 1000');
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
    if (!$deploy && !empty($settings['deploy']) && is_array($settings['deploy'])) {
        $deploy = $settings['deploy'];
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
    if (!in_array($mode, ['manual', 'package', 'bt-api', 'ftp'], true)) {
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
    $dnsOk = $domain !== '' && strpos($domain, '.') !== false;
    $sslOk = $dnsOk && (str_starts_with($domain, 'www.') || !str_contains($domain, 'huajian.local'));
    $result = $dnsOk ? '域名格式有效，等待接入 DNS/SSL 自动检测' : '域名格式异常';
    $main->prepare('UPDATE site_domains SET dns_status=:dns_status, ssl_status=:ssl_status, last_checked_at=:last_checked_at, last_result=:last_result, updated_at=:updated_at WHERE id=:id')
        ->execute([
            'id' => $id,
            'dns_status' => $dnsOk ? 'valid' : 'failed',
            'ssl_status' => $sslOk ? 'ready' : 'pending',
            'last_checked_at' => now(),
            'last_result' => $result,
            'updated_at' => now(),
        ]);
    return fetch_one($main, 'site_domains', $id) ?: [];
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
    return ['task' => $task, 'site' => save_site_settings($sitePdo, $settings)];
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
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $key;
        if (is_dir($dir)) {
            remove_dir_contents($dir);
            rmdir($dir);
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

function deploy_config_status(array $site, array $settings): array
{
    $deploy = site_deploy_config($site, (int)($site['id'] ?? 0) === 10001 ? $settings : []);
    $mode = (string)($deploy['mode'] ?? 'manual');
    $sitePath = trim((string)($deploy['site_path'] ?? ''));
    $panelUrl = trim((string)($deploy['bt_panel_url'] ?? ''));
    $configured = $sitePath !== '';
    if ($mode === 'bt-api') {
        $configured = $configured && $panelUrl !== '';
    }
    return [$deploy, $configured];
}

function execute_site_deploy(PDO $main, PDO $pdo, array $site): array
{
    $settings = site_settings($pdo);
    [$deploy, $configured] = deploy_config_status($site, $settings);
    $package = create_static_package($site);
    $status = $configured ? 'success' : 'pending';
    $message = $configured
        ? '部署任务已记录，发布包已生成，可按当前模式同步到目标目录。'
        : '发布包已生成，但部署目标未配置完整，请补齐站点目录和必要的面板地址。';
    $summary = [
        'site_id' => (int)$site['id'],
        'site_key' => $site['site_key'],
        'site_name' => $site['name'],
        'version_no' => $package['version_no'],
        'file_count' => $package['file_count'],
        'file_size' => $package['file_size'],
        'package_path' => $package['file_path'],
        'configured' => $configured,
        'mode' => $deploy['mode'] ?? 'manual',
        'panel_url' => $deploy['bt_panel_url'] ?? '',
        'site_path' => $deploy['site_path'] ?? '',
        'after_action' => $deploy['after_action'] ?? '',
        'message' => $message,
    ];
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
    return ['index', 'contact', 'search', 'order', 'news', 'products', 'category', 'product-category', 'assets', 'api', 'admin', 'admin-vue', 's'];
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
                return $allowed ?: [requested_site_id()];
            }
            $ids = $main->query('SELECT id FROM sites ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);
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
    $issues = [];

    if (trim((string)($site['name'] ?? '')) === '') {
        $issues[] = ['level' => 'error', 'scope' => 'site', 'message' => '站点名称为空'];
    }
    if (trim((string)($site['domain'] ?? $current['domain'] ?? '')) === '') {
        $issues[] = ['level' => 'warning', 'scope' => 'site', 'message' => '未设置主域名，sitemap 会缺少正式域名'];
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
    return (int)$pdo->lastInsertId();
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
    return [
        'site_id' => (int)($data['site_id'] ?? $current['site_id'] ?? requested_site_id()),
        'name' => mb_substr(trim((string)($data['name'] ?? ($current['name'] ?? ''))), 0, 160, 'UTF-8'),
        'source_type' => $type,
        'url' => mb_substr($url, 0, 500, 'UTF-8'),
        'category_id' => (int)($data['category_id'] ?? $current['category_id'] ?? 0) ?: null,
        'rewrite_mode' => in_array(($data['rewrite_mode'] ?? $current['rewrite_mode'] ?? 'draft'), ['draft', 'published'], true) ? ($data['rewrite_mode'] ?? $current['rewrite_mode'] ?? 'draft') : 'draft',
        'status' => in_array(($data['status'] ?? $current['status'] ?? 'active'), ['active', 'disabled'], true) ? ($data['status'] ?? $current['status'] ?? 'active') : 'active',
    ];
}

function list_collector_sources(PDO $pdo, ?PDO $main = null): array
{
    ensure_collector_tables($pdo);
    $siteWhere = site_scope_where_sql();
    $where = $siteWhere !== '' ? [$siteWhere] : [];
    $result = paginate($pdo, 'collector_sources', $where, 'id DESC', 'name');
    $result['items'] = attach_site_names($result['items'], $main);
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
    return ['source' => fetch_one($pdo, 'collector_sources', $sourceId), 'items' => $created, 'message' => $result];
}

function publish_collector_record(PDO $pdo, int $recordId, string $status = 'draft'): array
{
    ensure_collector_tables($pdo);
    $record = fetch_one($pdo, 'collector_records', $recordId);
    if (!$record) {
        fail('采集记录不存在', 'NOT_FOUND', 404);
    }
    if (!empty($record['article_id'])) {
        return ['article' => fetch_one($pdo, 'articles', (int)$record['article_id']), 'record' => $record];
    }
    $slug = draft_slug((string)$record['title'], 'collected') . '-' . (int)$record['id'];
    $source = !empty($record['source_id']) ? fetch_one($pdo, 'collector_sources', (int)$record['source_id']) : null;
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
    sync_content_distribution($pdo, 'article', $articleId, [(int)$record['site_id']]);
    $update = $pdo->prepare("UPDATE collector_records SET article_id = :article_id, status = 'converted', updated_at = :updated_at WHERE id = :id");
    $update->execute(['id' => $recordId, 'article_id' => $articleId, 'updated_at' => now()]);
    return ['article' => attach_distribution($pdo, 'article', [fetch_one($pdo, 'articles', $articleId)])[0], 'record' => fetch_one($pdo, 'collector_records', $recordId)];
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
    $site = site_settings($pdo);
    $siteIds = normalize_site_ids($data);
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
    $stmt = $pdo->prepare("INSERT INTO media (file_name, file_path, file_type, mime_type, file_size, width, height, alt_text, source_type, created_at, updated_at)
        VALUES (:file_name, :file_path, :file_type, :mime_type, :file_size, :width, :height, :alt_text, :source_type, :created_at, :updated_at)");
    $time = now();
    $stmt->execute([
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
        ok(require_login($pdo));
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
        $items = normalize_order_items($data['items']);
        if (!$items) {
            fail('订单至少需要一个商品', 'VALIDATION_ERROR', 422);
        }
        $total = array_reduce($items, fn($sum, $item) => $sum + (float)$item['amount'], 0.0);
        $time = now();
        $stmt = $pdo->prepare("INSERT INTO orders (site_id, order_no, customer_name, phone, email, address, items, total_amount, currency, payment_method, payment_status, fulfillment_status, remark, source_url, ip_address, user_agent, created_at, updated_at)
            VALUES (:site_id, :order_no, :customer_name, :phone, :email, :address, :items, :total_amount, :currency, :payment_method, 'pending', 'new', :remark, :source_url, :ip_address, :user_agent, :created_at, :updated_at)");
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
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        ok(fetch_one($pdo, 'orders', (int)$pdo->lastInsertId()), '订单已创建');
    }

    if ($method === 'POST' && $path === '/orders/lookup') {
        $data = body_json();
        require_fields($data, ['order_no', 'phone']);
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
        require_fields($data, ['order_no', 'phone', 'note']);
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_no = :order_no AND phone = :phone LIMIT 1');
        $stmt->execute([
            'order_no' => trim((string)$data['order_no']),
            'phone' => trim((string)$data['phone']),
        ]);
        $order = $stmt->fetch();
        if (!$order) {
            fail('未找到匹配订单，请检查订单号和手机号', 'NOT_FOUND', 404);
        }
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
        require_fields($data, ['order_no', 'phone', 'amount', 'reference']);
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_no = :order_no AND phone = :phone LIMIT 1');
        $stmt->execute([
            'order_no' => trim((string)$data['order_no']),
            'phone' => trim((string)$data['phone']),
        ]);
        $order = $stmt->fetch();
        if (!$order) {
            fail('未找到匹配订单，请检查订单号和手机号', 'NOT_FOUND', 404);
        }
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
        $remark = append_order_note((string)($order['remark'] ?? ''), $proofText);
        $update = $pdo->prepare('UPDATE orders SET remark = :remark, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            'id' => (int)$order['id'],
            'remark' => $remark,
            'updated_at' => now(),
        ]);
        ok(public_order_view(fetch_one($pdo, 'orders', (int)$order['id']) ?: $order), '付款凭证已提交');
    }

    if ($method === 'POST' && $path === '/orders/service-request') {
        $data = body_json();
        require_fields($data, ['order_no', 'phone', 'type', 'message']);
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_no = :order_no AND phone = :phone LIMIT 1');
        $stmt->execute([
            'order_no' => trim((string)$data['order_no']),
            'phone' => trim((string)$data['phone']),
        ]);
        $order = $stmt->fetch();
        if (!$order) {
            fail('未找到匹配订单，请检查订单号和手机号', 'NOT_FOUND', 404);
        }
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

    $user = require_login($pdo);

    if (str_starts_with($path, '/platform/')) {
        require_platform_admin($pdo);
    }

    if ($method === 'GET' && $path === '/dashboard/metrics') {
        ok(dashboard_metrics($pdo));
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

    if ($params = route_param('/template-clone/tasks/{id}', $path)) {
        if ($method === 'DELETE') {
            delete_template_clone_task(main_pdo(), (int)$params['id']);
            ok([], '模板克隆任务已删除');
        }
    }

    if ($method === 'GET' && $path === '/seo/audit') {
        ok(seo_audit($pdo, main_pdo()));
    }

    if ($method === 'POST' && $path === '/seo/fix') {
        ok(seo_fix($pdo, main_pdo(), body_json()), 'SEO 修复已完成');
    }

    if ($method === 'GET' && $path === '/payment/channels') {
        ok(list_payment_channels(main_pdo()));
    }

    if ($method === 'GET' && $path === '/site/domains') {
        ok(list_site_domains(main_pdo(), requested_site_id()));
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
        $status = in_array(($data['status'] ?? 'draft'), ['draft', 'published'], true) ? $data['status'] : 'draft';
        $site = site_settings($pdo);
        $created = [];
        $siteIds = normalize_site_ids($data);
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
            sync_content_distribution($pdo, 'article', $id, $siteIds);
            $created[] = attach_distribution($pdo, 'article', [fetch_one($pdo, 'articles', $id)])[0];
        }
        ok(['items' => $created, 'count' => count($created)], '批量生成成功');
    }

    if ($method === 'POST' && $path === '/ai/batch-products') {
        $data = body_json();
        require_fields($data, ['prompt']);
        $prompt = trim((string)$data['prompt']);
        $count = min(20, max(1, (int)($data['count'] ?? 5)));
        $status = in_array(($data['status'] ?? 'draft'), ['draft', 'published'], true) ? $data['status'] : 'draft';
        $site = site_settings($pdo);
        $created = [];
        $siteIds = normalize_site_ids($data);
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
            sync_content_distribution($pdo, 'product', $id, $siteIds);
            $created[] = attach_distribution($pdo, 'product', [fetch_one($pdo, 'products', $id)])[0];
        }
        ok(['items' => $created, 'count' => count($created)], '批量生成成功');
    }

    if ($method === 'POST' && $path === '/ai/generate-image') {
        $data = body_json();
        require_fields($data, ['type', 'prompt']);
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

    if ($method === 'GET' && $path === '/pages') {
        ensure_pages_table($pdo);
        $result = paginate($pdo, 'pages', [], 'id DESC');
        $result['items'] = attach_distribution($pdo, 'page', $result['items']);
        ok($result);
    }

    if ($method === 'POST' && $path === '/pages') {
        $data = body_json();
        $id = insert_page($pdo, $data);
        sync_content_distribution($pdo, 'page', $id, normalize_site_ids($data));
        ok(['id' => $id], '创建成功');
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
        $result = paginate($pdo, 'articles', [], 'published_at DESC, id DESC');
        $result['items'] = attach_distribution($pdo, 'article', $result['items']);
        ok($result);
    }

    if ($method === 'POST' && $path === '/articles') {
        $data = body_json();
        $id = insert_article($pdo, $data);
        sync_content_distribution($pdo, 'article', $id, normalize_site_ids($data));
        ok(['id' => $id], '创建成功');
    }

    if ($params = route_param('/articles/{id}', $path)) {
        $id = (int)$params['id'];
        if ($method === 'GET') {
            $item = fetch_one($pdo, 'articles', $id);
            if ($item) {
                $item = attach_distribution($pdo, 'article', [$item])[0];
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
            $item = attach_distribution($pdo, 'article', [fetch_one($pdo, 'articles', $id)])[0];
            ok($item, '保存成功');
        }
        if ($method === 'DELETE') {
            ensure_content_distribution_table($pdo);
            $pdo->prepare("DELETE FROM content_site_relations WHERE content_type = 'article' AND content_id = ?")->execute([$id]);
            $pdo->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
            ok([], '删除成功');
        }
    }

    if ($method === 'GET' && $path === '/products') {
        $result = paginate($pdo, 'products', [], 'id DESC');
        $result['items'] = attach_distribution($pdo, 'product', $result['items']);
        ok($result);
    }

    if ($method === 'POST' && $path === '/products') {
        $data = body_json();
        $id = insert_product($pdo, $data);
        sync_content_distribution($pdo, 'product', $id, normalize_site_ids($data));
        ok(['id' => $id], '创建成功');
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
            $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
            ok([], '订单已删除');
        }
    }

    if ($method === 'GET' && $path === '/media') {
        $fileType = trim((string)($_GET['file_type'] ?? ''));
        $where = [];
        $params = [];
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
        $stmt = $pdo->prepare("INSERT INTO media (file_name, file_path, file_type, mime_type, file_size, width, height, alt_text, source_type, created_at, updated_at)
            VALUES (:file_name, :file_path, :file_type, :mime_type, :file_size, :width, :height, :alt_text, 'upload', :created_at, :updated_at)");
        $time = now();
        $stmt->execute([
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
            $item = fetch_one($pdo, 'media', $id);
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
        $stmt = $pdo->prepare("INSERT INTO collector_sources (site_id, name, source_type, url, category_id, rewrite_mode, status, created_at, updated_at)
            VALUES (:site_id, :name, :source_type, :url, :category_id, :rewrite_mode, :status, :created_at, :updated_at)");
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
            $stmt = $pdo->prepare("UPDATE collector_sources SET site_id=:site_id, name=:name, source_type=:source_type, url=:url, category_id=:category_id, rewrite_mode=:rewrite_mode, status=:status, updated_at=:updated_at WHERE id=:id");
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
            ok(publish_collector_record($pdo, (int)$params['id'], (string)($data['status'] ?? 'draft')), '已转为文章');
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
        $status = $configured ? 'ready' : 'pending';
        $summary = [
            'site_id' => (int)$currentSite['id'],
            'site_key' => $currentSite['site_key'],
            'site_name' => $currentSite['name'],
            'configured' => $configured,
            'panel_url' => $deploy['bt_panel_url'] ?? '',
            'site_path' => $deploy['site_path'] ?? '',
            'mode' => $deploy['mode'] ?? 'manual',
            'message' => $configured ? '部署参数已填写，后续可接入宝塔 API 执行上传发布。' : '请先填写宝塔面板地址和站点目录。',
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
