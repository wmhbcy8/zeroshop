# 化简数据库结构 v0.1

## 1. 数据库设计原则

化简第一版使用 MySQL。

建议采用：

- 平台主库：管理客户、站点、模板、插件、部署节点。
- 站点独立库：每个站点一套内容、商品、订单数据。

这样方便备份、迁移、恢复和隔离风险。

```text
huajian_main
huajian_site_10001
huajian_site_10002
huajian_site_10003
```

如果早期想简化，也可以使用同库不同表前缀：

```text
site_10001_articles
site_10002_articles
```

但长期建议独立库。

## 2. 平台主库

### 2.1 customers

客户表。

```sql
CREATE TABLE customers (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(50),
  email VARCHAR(120),
  company VARCHAR(150),
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 2.2 sites

站点表。

```sql
CREATE TABLE sites (
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
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_customer_id (customer_id),
  INDEX idx_domain (domain),
  INDEX idx_status (status)
);
```

### 2.3 site_domains

站点域名表。

```sql
CREATE TABLE site_domains (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  site_id BIGINT UNSIGNED NOT NULL,
  domain VARCHAR(180) NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  ssl_status VARCHAR(30) DEFAULT 'pending',
  verify_status VARCHAR(30) DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uk_domain (domain),
  INDEX idx_site_id (site_id)
);
```

### 2.4 templates

模板表。

```sql
CREATE TABLE templates (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  template_key VARCHAR(100) NOT NULL UNIQUE,
  version VARCHAR(30) NOT NULL,
  type VARCHAR(120),
  author VARCHAR(100),
  preview_image VARCHAR(255),
  package_path VARCHAR(255),
  status VARCHAR(30) NOT NULL DEFAULT 'enabled',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 2.5 modules

模块表。

```sql
CREATE TABLE modules (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  module_key VARCHAR(100) NOT NULL UNIQUE,
  category VARCHAR(60) NOT NULL,
  version VARCHAR(30) NOT NULL,
  config_schema JSON,
  package_path VARCHAR(255),
  status VARCHAR(30) NOT NULL DEFAULT 'enabled',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 2.6 plugins

插件表。

```sql
CREATE TABLE plugins (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  plugin_key VARCHAR(100) NOT NULL UNIQUE,
  version VARCHAR(30) NOT NULL,
  category VARCHAR(60),
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  package_path VARCHAR(255),
  status VARCHAR(30) NOT NULL DEFAULT 'enabled',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 2.7 site_plugins

站点已安装插件。

```sql
CREATE TABLE site_plugins (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  site_id BIGINT UNSIGNED NOT NULL,
  plugin_key VARCHAR(100) NOT NULL,
  version VARCHAR(30) NOT NULL,
  license_key VARCHAR(120),
  status VARCHAR(30) NOT NULL DEFAULT 'enabled',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uk_site_plugin (site_id, plugin_key),
  INDEX idx_site_id (site_id)
);
```

### 2.8 deploy_nodes

部署节点表。

```sql
CREATE TABLE deploy_nodes (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  node_type VARCHAR(30) NOT NULL DEFAULT 'bt',
  api_url VARCHAR(255) NOT NULL,
  api_token_encrypted TEXT,
  server_ip VARCHAR(80),
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 2.9 deploy_tasks

部署任务表。

```sql
CREATE TABLE deploy_tasks (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  site_id BIGINT UNSIGNED NOT NULL,
  node_id BIGINT UNSIGNED,
  task_type VARCHAR(50) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  payload JSON,
  result JSON,
  error_message TEXT,
  started_at DATETIME,
  finished_at DATETIME,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_site_id (site_id),
  INDEX idx_status (status)
);
```

### 2.10 ai_providers

AI 服务商配置。

```sql
CREATE TABLE ai_providers (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  provider_key VARCHAR(80) NOT NULL UNIQUE,
  base_url VARCHAR(255),
  api_key_encrypted TEXT,
  text_model VARCHAR(120),
  image_model VARCHAR(120),
  video_model VARCHAR(120),
  status VARCHAR(30) NOT NULL DEFAULT 'enabled',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

## 3. 站点独立库

以下表存在于每个站点自己的数据库。

### 3.1 site_settings

站点设置。

```sql
CREATE TABLE site_settings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value JSON,
  updated_at DATETIME NOT NULL
);
```

### 3.2 pages

页面表。

```sql
CREATE TABLE pages (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE,
  content MEDIUMTEXT,
  layout VARCHAR(100) DEFAULT 'page',
  module_config JSON,
  seo_title VARCHAR(255),
  seo_keywords VARCHAR(255),
  seo_description VARCHAR(500),
  status VARCHAR(30) NOT NULL DEFAULT 'draft',
  published_at DATETIME,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_status (status)
);
```

### 3.3 articles

文章表。

```sql
CREATE TABLE articles (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  category_id BIGINT UNSIGNED,
  title VARCHAR(220) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  cover VARCHAR(255),
  summary VARCHAR(500),
  content MEDIUMTEXT,
  source_type VARCHAR(30) DEFAULT 'manual',
  source_url VARCHAR(500),
  ai_task_id BIGINT UNSIGNED,
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
);
```

### 3.4 categories

文章分类。

```sql
CREATE TABLE categories (
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
);
```

### 3.5 tags

标签。

```sql
CREATE TABLE tags (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 3.6 article_tags

文章标签关系。

```sql
CREATE TABLE article_tags (
  article_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (article_id, tag_id)
);
```

### 3.7 products

商品表。

```sql
CREATE TABLE products (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  category_id BIGINT UNSIGNED,
  title VARCHAR(220) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  sku VARCHAR(100),
  cover VARCHAR(255),
  gallery JSON,
  summary VARCHAR(500),
  description MEDIUMTEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  market_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock INT NOT NULL DEFAULT 0,
  attributes JSON,
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
);
```

### 3.8 product_categories

商品分类。

```sql
CREATE TABLE product_categories (
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
);
```

### 3.9 media

媒体库。

```sql
CREATE TABLE media (
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
  updated_at DATETIME NOT NULL
);
```

### 3.10 menus

菜单表。

```sql
CREATE TABLE menus (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  menu_key VARCHAR(80) NOT NULL UNIQUE,
  items JSON,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 3.11 forms

表单定义。

```sql
CREATE TABLE forms (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  form_key VARCHAR(80) NOT NULL UNIQUE,
  fields JSON,
  notify_email VARCHAR(180),
  status VARCHAR(30) NOT NULL DEFAULT 'enabled',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 3.12 form_submissions

表单提交。

```sql
CREATE TABLE form_submissions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  form_key VARCHAR(80) NOT NULL,
  data JSON,
  ip_address VARCHAR(80),
  user_agent VARCHAR(255),
  status VARCHAR(30) NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL,
  INDEX idx_form_key (form_key),
  INDEX idx_status (status)
);
```

### 3.13 orders

订单表。

```sql
CREATE TABLE orders (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  order_no VARCHAR(80) NOT NULL UNIQUE,
  customer_name VARCHAR(120),
  customer_email VARCHAR(180),
  customer_phone VARCHAR(80),
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  currency VARCHAR(20) DEFAULT 'CNY',
  payment_status VARCHAR(30) DEFAULT 'unpaid',
  order_status VARCHAR(30) DEFAULT 'pending',
  stock_reserved TINYINT(1) NOT NULL DEFAULT 0,
  shipping_address JSON,
  note TEXT,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_payment_status (payment_status),
  INDEX idx_order_status (order_status)
);
```

### 3.14 order_items

订单商品。

```sql
CREATE TABLE order_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED,
  product_title VARCHAR(220) NOT NULL,
  product_sku VARCHAR(100),
  price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_order_id (order_id)
);
```

### 3.15 publish_versions

发布版本。

```sql
CREATE TABLE publish_versions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  version_no VARCHAR(80) NOT NULL,
  publish_type VARCHAR(50) NOT NULL,
  file_path VARCHAR(255),
  status VARCHAR(30) NOT NULL DEFAULT 'success',
  summary JSON,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uk_version_no (version_no)
);
```

### 3.16 ai_tasks

AI 任务。

```sql
CREATE TABLE ai_tasks (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  task_type VARCHAR(60) NOT NULL,
  provider_key VARCHAR(80),
  prompt MEDIUMTEXT,
  input_data JSON,
  output_data JSON,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  error_message TEXT,
  created_by BIGINT UNSIGNED,
  started_at DATETIME,
  finished_at DATETIME,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_task_type (task_type),
  INDEX idx_status (status)
);
```

### 3.17 collector_sources

采集源。

```sql
CREATE TABLE collector_sources (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  source_type VARCHAR(30) NOT NULL,
  source_url VARCHAR(500) NOT NULL,
  category_id BIGINT UNSIGNED,
  fetch_interval_minutes INT DEFAULT 1440,
  rewrite_with_ai TINYINT(1) NOT NULL DEFAULT 1,
  status VARCHAR(30) NOT NULL DEFAULT 'enabled',
  last_fetched_at DATETIME,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 3.18 collected_items

采集记录。

```sql
CREATE TABLE collected_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  source_id BIGINT UNSIGNED NOT NULL,
  source_url VARCHAR(500) NOT NULL,
  title VARCHAR(255),
  content_hash VARCHAR(80),
  article_id BIGINT UNSIGNED,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uk_source_url (source_url),
  INDEX idx_source_id (source_id),
  INDEX idx_status (status)
);
```

## 4. 数据状态建议

### 4.1 内容状态

```text
draft       草稿
pending     待审核
published   已发布
hidden      隐藏
archived    归档
```

### 4.2 任务状态

```text
pending     等待中
running     运行中
success     成功
failed      失败
canceled    已取消
```

### 4.3 站点状态

```text
active      正常
suspended   暂停
deploying   部署中
error       异常
deleted     已删除
```

## 5. 后续扩展

后续可以增加：

- 多语言表。
- 会员表。
- 优惠券表。
- 商品规格 SKU 表。
- 物流表。
- 评论表。
- 权限角色表。
- API 调用日志表。
- 搜索关键词排名表。
