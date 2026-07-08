<?php

declare(strict_types=1);

function env_value(string $key, ?string $default = null): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        if ($default !== null) {
            return $default;
        }
        throw new RuntimeException("Missing environment variable: {$key}");
    }
    return $value;
}

function pdo_without_db(): PDO
{
    $host = env_value('HJ_DB_HOST');
    $port = env_value('HJ_DB_PORT', '3306');
    $user = env_value('HJ_DB_USERNAME');
    $password = env_value('HJ_DB_PASSWORD');
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function pdo_with_db(string $database): PDO
{
    $host = env_value('HJ_DB_HOST');
    $port = env_value('HJ_DB_PORT', '3306');
    $user = env_value('HJ_DB_USERNAME');
    $password = env_value('HJ_DB_PASSWORD');
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function read_json(string $path): array
{
    return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
}

function exec_all(PDO $pdo, array $statements): void
{
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

$root = dirname(__DIR__);
$mainDb = env_value('HJ_DB_MAIN', 'huajian_main');
$siteDb = env_value('HJ_DB_SITE', 'huajian_site_10001');

$pdo = pdo_without_db();
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$mainDb}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$siteDb}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$main = pdo_with_db($mainDb);
exec_all($main, [
    "CREATE TABLE IF NOT EXISTS customers (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(50),
        email VARCHAR(120),
        company VARCHAR(150),
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS sites (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS batch_tasks (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
]);

$now = date('Y-m-d H:i:s');
$main->exec("INSERT INTO customers (id, name, phone, email, company, status, created_at, updated_at)
    VALUES (1, '楚云数航', '13800000000', 'hello@example.com', '楚云数航科技有限公司', 'active', '{$now}', '{$now}')
    ON DUPLICATE KEY UPDATE name=VALUES(name), updated_at=VALUES(updated_at)");
$main->exec("INSERT INTO sites (id, customer_id, name, site_key, domain, subdomain, language, template_key, database_name, public_path, deploy_config_json, status, created_at, updated_at)
    VALUES (10001, 1, '楚云数航官网', 'site_10001', 'demo.local', 'site10001.huajian.local', 'zh-CN', 'business-clean', '{$siteDb}', 'sites/site_10001/public', '{}', 'active', '{$now}', '{$now}')
    ON DUPLICATE KEY UPDATE name=VALUES(name), template_key=VALUES(template_key), database_name=VALUES(database_name), updated_at=VALUES(updated_at)");

$site = pdo_with_db($siteDb);
exec_all($site, [
    "CREATE TABLE IF NOT EXISTS site_settings (
        setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value TEXT,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS categories (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        parent_id BIGINT UNSIGNED DEFAULT 0,
        name VARCHAR(120) NOT NULL,
        slug VARCHAR(140) NOT NULL UNIQUE,
        description VARCHAR(500),
        sort_order INT NOT NULL DEFAULT 0,
        seo_title VARCHAR(255),
        seo_keywords VARCHAR(255),
        seo_description VARCHAR(500),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS articles (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        category_id BIGINT UNSIGNED,
        title VARCHAR(220) NOT NULL,
        slug VARCHAR(180) NOT NULL UNIQUE,
        cover VARCHAR(255),
        summary VARCHAR(500),
        content MEDIUMTEXT,
        seo_title VARCHAR(255),
        seo_keywords VARCHAR(255),
        seo_description VARCHAR(500),
        status VARCHAR(30) NOT NULL DEFAULT 'draft',
        published_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_category_id (category_id),
        INDEX idx_status (status),
        INDEX idx_published_at (published_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS product_categories (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        parent_id BIGINT UNSIGNED DEFAULT 0,
        name VARCHAR(120) NOT NULL,
        slug VARCHAR(140) NOT NULL UNIQUE,
        cover VARCHAR(255),
        description VARCHAR(500),
        sort_order INT NOT NULL DEFAULT 0,
        seo_title VARCHAR(255),
        seo_keywords VARCHAR(255),
        seo_description VARCHAR(500),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS products (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        category_id BIGINT UNSIGNED,
        title VARCHAR(220) NOT NULL,
        slug VARCHAR(180) NOT NULL UNIQUE,
        sku VARCHAR(100),
        cover VARCHAR(255),
        gallery TEXT,
        summary VARCHAR(500),
        description MEDIUMTEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        market_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        stock INT NOT NULL DEFAULT 0,
        attributes TEXT,
        seo_title VARCHAR(255),
        seo_keywords VARCHAR(255),
        seo_description VARCHAR(500),
        status VARCHAR(30) NOT NULL DEFAULT 'draft',
        published_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_category_id (category_id),
        INDEX idx_status (status),
        INDEX idx_sku (sku)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS media (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        file_name VARCHAR(180) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_type VARCHAR(50),
        mime_type VARCHAR(100),
        file_size BIGINT UNSIGNED DEFAULT 0,
        width INT,
        height INT,
        alt_text VARCHAR(255),
        source_type VARCHAR(30) DEFAULT 'upload',
        source_url VARCHAR(500),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_file_type (file_type),
        INDEX idx_source_type (source_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS publish_versions (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        version_no VARCHAR(80) NOT NULL UNIQUE,
        publish_type VARCHAR(50) NOT NULL,
        file_path VARCHAR(255),
        status VARCHAR(30) NOT NULL DEFAULT 'success',
        summary TEXT,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS form_submissions (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        form_key VARCHAR(80) NOT NULL,
        source_url VARCHAR(255),
        data TEXT,
        ip_address VARCHAR(80),
        user_agent VARCHAR(255),
        status VARCHAR(30) NOT NULL DEFAULT 'new',
        remark VARCHAR(500),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_form_key (form_key),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS orders (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admin_users (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        display_name VARCHAR(100) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'admin',
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        last_login_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admin_sessions (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        ip_address VARCHAR(80),
        user_agent VARCHAR(255),
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
]);

$adminUsername = env_value('HJ_ADMIN_USERNAME', 'admin');
$adminPassword = env_value('HJ_ADMIN_PASSWORD', 'admin123456');
$adminStmt = $site->prepare("INSERT INTO admin_users (id, username, password_hash, display_name, role, status, created_at, updated_at)
    VALUES (1, :username, :password_hash, '化简管理员', 'admin', 'active', :created_at, :updated_at)
    ON DUPLICATE KEY UPDATE username=VALUES(username), password_hash=VALUES(password_hash), updated_at=VALUES(updated_at)");
$adminStmt->execute([
    'username' => $adminUsername,
    'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
    'created_at' => $now,
    'updated_at' => $now,
]);

$siteData = read_json($root . '/demo-data/site.json');
$site->prepare("REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)")
    ->execute(['site', json_encode($siteData, JSON_UNESCAPED_UNICODE), $now]);

$categoryStmt = $site->prepare("INSERT INTO categories (id, parent_id, name, slug, description, sort_order, created_at, updated_at)
    VALUES (:id, 0, :name, :slug, :description, 0, :created_at, :updated_at)
    ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), updated_at=VALUES(updated_at)");
foreach (read_json($root . '/demo-data/categories.json') as $category) {
    $categoryStmt->execute([
        'id' => $category['id'],
        'name' => $category['name'],
        'slug' => $category['slug'],
        'description' => $category['description'],
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

$articleStmt = $site->prepare("INSERT INTO articles (id, category_id, title, slug, cover, summary, content, seo_title, seo_keywords, seo_description, status, published_at, created_at, updated_at)
    VALUES (:id, :category_id, :title, :slug, :cover, :summary, :content, :seo_title, :seo_keywords, :seo_description, 'published', :published_at, :created_at, :updated_at)
    ON DUPLICATE KEY UPDATE title=VALUES(title), cover=VALUES(cover), summary=VALUES(summary), content=VALUES(content), updated_at=VALUES(updated_at)");
foreach (read_json($root . '/demo-data/articles.json') as $article) {
    $articleStmt->execute([
        'id' => $article['id'],
        'category_id' => $article['category_id'],
        'title' => $article['title'],
        'slug' => $article['slug'],
        'cover' => $article['cover'],
        'summary' => $article['summary'],
        'content' => $article['content'],
        'seo_title' => $article['seo_title'],
        'seo_keywords' => $article['seo_keywords'],
        'seo_description' => $article['seo_description'],
        'published_at' => $article['published_at'],
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

$productCategoryStmt = $site->prepare("INSERT INTO product_categories (id, parent_id, name, slug, description, sort_order, created_at, updated_at)
    VALUES (:id, 0, :name, :slug, :description, 0, :created_at, :updated_at)
    ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), updated_at=VALUES(updated_at)");
foreach (read_json($root . '/demo-data/product-categories.json') as $category) {
    $productCategoryStmt->execute([
        'id' => $category['id'],
        'name' => $category['name'],
        'slug' => $category['slug'],
        'description' => $category['description'],
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

$productStmt = $site->prepare("INSERT INTO products (id, category_id, title, slug, sku, cover, gallery, summary, description, price, market_price, stock, attributes, seo_title, seo_keywords, seo_description, status, published_at, created_at, updated_at)
    VALUES (:id, :category_id, :title, :slug, :sku, :cover, :gallery, :summary, :description, :price, :market_price, :stock, :attributes, :seo_title, :seo_keywords, :seo_description, 'published', :published_at, :created_at, :updated_at)
    ON DUPLICATE KEY UPDATE title=VALUES(title), cover=VALUES(cover), summary=VALUES(summary), description=VALUES(description), updated_at=VALUES(updated_at)");
foreach (read_json($root . '/demo-data/products.json') as $product) {
    $productStmt->execute([
        'id' => $product['id'],
        'category_id' => $product['category_id'],
        'title' => $product['title'],
        'slug' => $product['slug'],
        'sku' => $product['sku'],
        'cover' => $product['cover'],
        'gallery' => json_encode([]),
        'summary' => $product['summary'],
        'description' => $product['description'],
        'price' => $product['price'],
        'market_price' => $product['market_price'],
        'stock' => $product['stock'],
        'attributes' => json_encode([]),
        'seo_title' => $product['seo_title'],
        'seo_keywords' => $product['seo_keywords'],
        'seo_description' => $product['seo_description'],
        'published_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

echo "Database bootstrap completed: {$mainDb}, {$siteDb}\n";
