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
    return resolve_request_site_id(['site_id' => $raw]);
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
    $siteId = requested_site_filter();
    $clauses = ["remark IS NOT NULL", "remark <> ''"];
    $params = [];
    if ($siteId) {
        $clauses[] = 'site_id = :site_id';
        $params['site_id'] = $siteId;
    }
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
    $siteId = requested_site_filter();
    $siteClause = $siteId ? ' AND site_id = :site_id' : '';
    $visitParams = ['today' => $today . ' 00:00:00'];
    if ($siteId) {
        $visitParams['site_id'] = $siteId;
    }
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
        FROM orders" . ($siteId ? ' WHERE site_id = :site_id' : ''));
    $orderParams = ['today' => $today . ' 00:00:00'];
    if ($siteId) {
        $orderParams['site_id'] = $siteId;
    }
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
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
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
    $settings = site_settings($sitePdo);
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
    if (!in_array($mode, ['manual', 'package', 'bt-api'], true)) {
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
    $stmt = $main->prepare('UPDATE sites SET name = :name, domain = :domain, subdomain = :subdomain, language = :language, template_key = :template_key, deploy_config_json = :deploy_config_json, status = :status, updated_at = :updated_at WHERE id = :id');
    $stmt->execute([
        'name' => $payload['name'],
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
        $settings = site_settings($sitePdo);
        foreach (['name', 'domain', 'language', 'template_key'] as $field) {
            $settings[$field] = $payload[$field];
        }
        $settings['deploy'] = $payload['deploy'];
        $settings['updated_at'] = now();
        $settingsStmt = $sitePdo->prepare("REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES ('site', :value, :updated_at)");
        $settingsStmt->execute(['value' => json_encode($settings, JSON_UNESCAPED_UNICODE), 'updated_at' => $settings['updated_at']]);
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

function list_batch_tasks(PDO $main): array
{
    ensure_center_tables($main);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
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
    $whereSql = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';

    $countStmt = $main->prepare("SELECT COUNT(*) FROM batch_tasks{$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $stmt = $main->prepare("SELECT * FROM batch_tasks{$whereSql} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
    $stmt->execute($params);
    $items = array_map(function (array $item) {
        $summary = json_decode((string)($item['summary'] ?? ''), true);
        $siteIds = json_decode((string)($item['site_ids'] ?? '[]'), true);
        $item['summary_data'] = is_array($summary) ? $summary : [];
        $item['site_id_list'] = is_array($siteIds) ? $siteIds : [];
        return $item;
    }, $stmt->fetchAll());

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

function decode_payment_config(?string $value): array
{
    if (!$value) {
        return [];
    }
    $data = json_decode($value, true);
    return is_array($data) ? $data : [];
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
    $row['config'] = decode_payment_config($row['config_json'] ?? '');
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
            'config_json' => json_encode($payload['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
            'config_json' => json_encode($payload['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
    $stmt = $sitePdo->prepare("REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES ('site', :value, :updated_at)");
    $stmt->execute([
        'value' => json_encode($site, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'updated_at' => now(),
    ]);
    return ['channel' => $channel, 'site' => $site];
}

function ensure_content_distribution_table(PDO $pdo): void
{
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
    $ids = $data['site_ids'] ?? $data['distribution_site_ids'] ?? [requested_site_id()];
    if (!is_array($ids)) {
        $ids = [$ids];
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
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
    $siteId = requested_site_filter();

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
    if ($siteId) {
        $clauses[] = 'site_id = :site_id';
        $params['site_id'] = $siteId;
    }

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
    $siteId = requested_site_filter();
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
    if ($siteId) {
        $clauses[] = 'site_id = :site_id';
        $params['site_id'] = $siteId;
    }
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

function site_settings(PDO $pdo): array
{
    $value = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'site'")->fetchColumn();
    if (!$value) {
        return [];
    }
    $data = json_decode((string)$value, true);
    return is_array($data) ? $data : [];
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
        role VARCHAR(50) NOT NULL DEFAULT 'admin',
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        last_login_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.display_name, u.role
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
    return $user;
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

    require_login($pdo);

    if ($method === 'GET' && $path === '/dashboard/metrics') {
        ok(dashboard_metrics($pdo));
    }

    if ($method === 'GET' && $path === '/sites') {
        $main = main_pdo();
        $items = center_site_items($main, $pdo);
        ok([
            'items' => $items,
            'overview' => center_overview($items),
            'current_site_id' => 10001,
        ]);
    }

    if ($method === 'POST' && $path === '/sites') {
        $data = body_json();
        require_fields($data, ['name']);
        $main = main_pdo();
        ensure_center_tables($main);
        $payload = normalize_center_site_payload($data);
        $now = now();
        $stmt = $main->prepare("INSERT INTO sites (customer_id, name, site_key, domain, subdomain, language, template_key, database_name, public_path, deploy_config_json, status, created_at, updated_at)
            VALUES (1, :name, '', :domain, :subdomain, :language, :template_key, :database_name, '', :deploy_config_json, :status, :created_at, :updated_at)");
        $stmt->execute([
            'name' => $payload['name'],
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
        $items = center_site_items($main, $pdo);
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
            $siteItem = update_center_site($main, $id, body_json(), $pdo);
            $items = center_site_items($main, $pdo);
            ok([
                'site' => $siteItem,
                'items' => $items,
                'overview' => center_overview($items),
            ], '站点已保存');
        }
    }

    if ($method === 'GET' && $path === '/site/settings') {
        ok(site_settings($pdo));
    }

    if ($method === 'GET' && $path === '/site/modules') {
        ok(read_config_json('module-registry.json'));
    }

    if ($method === 'GET' && $path === '/site/templates') {
        ok(template_registry());
    }

    if ($method === 'GET' && $path === '/payment/channels') {
        ok(list_payment_channels(main_pdo()));
    }

    if ($method === 'GET' && $path === '/batch/tasks') {
        ok(list_batch_tasks(main_pdo()));
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
        ok(apply_payment_channel_to_site(main_pdo(), $pdo, $channelId), '支付通道已应用到当前站点');
    }

    if ($method === 'PUT' && $path === '/site/settings') {
        $data = body_json();
        $stmt = $pdo->prepare("REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES ('site', :value, :updated_at)");
        $stmt->execute(['value' => json_encode($data, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        ok($data, '保存成功');
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
        putenv('HJ_PUBLIC_PATH=' . $publicPath);
        $command = '"' . $php . '" "' . $script . '"';
        $output = [];
        $code = 0;
        exec($command, $output, $code);
        if ($code !== 0) {
            fail('生成失败', 'GENERATE_FAILED', 500, ['output' => $output]);
        }
        $versionNo = (string)$currentSite['site_key'] . '_version_' . date('Ymd_His');
        $fileCount = count(iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($publicPath, FilesystemIterator::SKIP_DOTS))));
        ensure_publish_versions_site_column($pdo);
        $stmt = $pdo->prepare("INSERT INTO publish_versions (site_id, version_no, publish_type, file_path, status, summary, created_at)
            VALUES (:site_id, :version_no, 'generate', :file_path, 'success', :summary, :created_at)");
        $stmt->execute([
            'site_id' => (int)$currentSite['id'],
            'version_no' => $versionNo,
            'file_path' => str_replace(DIRECTORY_SEPARATOR, '/', site_public_path($currentSite)),
            'summary' => json_encode(['site_id' => (int)$currentSite['id'], 'site_key' => $currentSite['site_key'], 'site_name' => $currentSite['name'], 'file_count' => $fileCount, 'output' => $output], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
        ok(['site_id' => (int)$currentSite['id'], 'site_key' => $currentSite['site_key'], 'site_name' => $currentSite['name'], 'version_no' => $versionNo, 'file_count' => $fileCount, 'output' => $output], '生成成功');
    }

    if ($method === 'POST' && $path === '/site/deploy-test') {
        $main = main_pdo();
        $currentSite = current_site($main, $pdo);
        $site = site_settings($pdo);
        $deploy = site_deploy_config($currentSite, (int)$currentSite['id'] === 10001 ? $site : []);
        $configured = !empty($deploy['bt_panel_url']) && !empty($deploy['site_path']);
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
        ok($summary + ['version_no' => $package['version_no']], '发布包已生成');
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

    fail('接口不存在', 'NOT_FOUND', 404);
} catch (Throwable $e) {
    fail($e->getMessage(), 'SERVER_ERROR', 500);
}
