# 化简 API 契约 v0.1

## 1. API 设计原则

化简 API 服务于两个后台：

```text
平台总后台
客户站点后台
```

第一版采用 REST 风格。

核心原则：

- 所有接口返回统一 JSON。
- 所有后台接口必须登录后访问。
- 平台管理员可以访问全部数据。
- 客户管理员只能访问自己的站点。
- 当前站点通过请求头或路径明确指定。
- 列表接口统一分页、搜索、筛选。
- 写操作必须记录操作日志。
- AI、发布、采集等耗时操作全部走任务。

## 2. 基础地址

本地开发：

```text
http://localhost:8000/api
```

生产环境：

```text
https://admin-api.example.com/api
```

## 3. 认证方式

第一版建议使用 Bearer Token。

请求头：

```http
Authorization: Bearer {token}
X-Site-Id: 10001
```

说明：

- `Authorization` 用于识别当前登录用户。
- `X-Site-Id` 用于客户后台指定当前操作站点。
- 平台后台接口可不传 `X-Site-Id`。

## 4. 统一响应格式

### 4.1 成功响应

```json
{
  "success": true,
  "message": "ok",
  "data": {}
}
```

### 4.2 列表响应

```json
{
  "success": true,
  "message": "ok",
  "data": {
    "items": [],
    "pagination": {
      "page": 1,
      "page_size": 20,
      "total": 100,
      "total_pages": 5
    }
  }
}
```

### 4.3 错误响应

```json
{
  "success": false,
  "message": "参数错误",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "title": ["标题不能为空"]
    }
  }
}
```

## 5. 状态码

```text
200 请求成功
201 创建成功
400 参数错误
401 未登录
403 无权限
404 不存在
409 数据冲突
422 表单验证失败
500 服务器错误
```

## 6. 通用分页参数

```text
page        页码，默认 1
page_size   每页数量，默认 20
keyword     搜索关键词
status      状态
sort        排序字段
order       asc / desc
```

示例：

```http
GET /api/articles?page=1&page_size=20&keyword=无人机&status=published
```

## 7. 登录接口

### 7.1 登录

```http
POST /api/auth/login
```

请求：

```json
{
  "account": "admin",
  "password": "123456"
}
```

响应：

```json
{
  "success": true,
  "message": "登录成功",
  "data": {
    "token": "jwt-token",
    "user": {
      "id": 1,
      "name": "管理员",
      "role": "platform_admin"
    }
  }
}
```

### 7.2 当前用户

```http
GET /api/auth/me
```

响应：

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "管理员",
    "role": "platform_admin",
    "permissions": [],
    "sites": []
  }
}
```

### 7.3 退出登录

```http
POST /api/auth/logout
```

## 8. 平台后台接口

## 8.1 客户管理

### 8.1.1 客户列表

```http
GET /api/platform/customers
```

响应字段：

```json
{
  "id": 1,
  "name": "楚云数航",
  "company": "楚云数航科技有限公司",
  "phone": "13800000000",
  "email": "demo@example.com",
  "site_count": 2,
  "status": "active",
  "created_at": "2026-07-07 10:00:00"
}
```

### 8.1.2 新建客户

```http
POST /api/platform/customers
```

请求：

```json
{
  "name": "楚云数航",
  "company": "楚云数航科技有限公司",
  "phone": "13800000000",
  "email": "demo@example.com",
  "status": "active"
}
```

### 8.1.3 客户详情

```http
GET /api/platform/customers/{id}
```

### 8.1.4 更新客户

```http
PUT /api/platform/customers/{id}
```

### 8.1.5 删除客户

```http
DELETE /api/platform/customers/{id}
```

## 8.2 站点管理

### 8.2.1 站点列表

```http
GET /api/platform/sites
```

筛选参数：

```text
keyword
customer_id
status
template_key
node_id
```

响应字段：

```json
{
  "id": 10001,
  "customer_id": 1,
  "customer_name": "楚云数航",
  "name": "楚云数航官网",
  "site_key": "site_10001",
  "domain": "example.com",
  "subdomain": "site10001.huajian.com",
  "template_key": "business-clean",
  "status": "active",
  "last_published_at": "2026-07-07 12:00:00"
}
```

### 8.2.2 新建站点

```http
POST /api/platform/sites
```

请求：

```json
{
  "customer_id": 1,
  "name": "楚云数航官网",
  "template_key": "business-clean",
  "language": "zh-CN",
  "subdomain": "chuyun.huajian.com",
  "node_id": 1
}
```

响应：

```json
{
  "success": true,
  "data": {
    "id": 10001,
    "site_key": "site_10001",
    "database_name": "huajian_site_10001"
  }
}
```

### 8.2.3 进入客户后台

```http
POST /api/platform/sites/{id}/impersonate
```

说明：

平台管理员临时进入某个客户站点后台。

行为：

```text
仅平台管理员可调用。
系统查找站点所属客户的 customer_admin 账号。
如果客户还没有中台账号，自动创建一个平台代管账号。
签发短期客户中台 Token，并返回当前站点 ID。
前端拿到 Token 后切换到客户中台视图。
```

响应：

```json
{
  "token": "customer-admin-token",
  "expires_at": "2026-07-10 12:00:00",
  "current_site_id": 10001,
  "site": {
    "id": 10001,
    "name": "楚云数航官网",
    "customer_id": 1
  },
  "user": {
    "role": "customer_admin",
    "customer_id": 1,
    "site_scope": "customer",
    "allowed_site_ids": [10001, 10002]
  },
  "impersonated_by": {
    "id": 1,
    "username": "admin"
  }
}
```

## 8.3 模板管理

```http
GET    /api/platform/templates
POST   /api/platform/templates/upload
GET    /api/platform/templates/{key}
POST   /api/platform/templates/{key}/enable
POST   /api/platform/templates/{key}/disable
DELETE /api/platform/templates/{key}
```

模板字段：

```json
{
  "name": "商务官网简洁模板",
  "template_key": "business-clean",
  "version": "1.0.0",
  "type": ["company", "blog", "shop"],
  "author": "化简",
  "preview_image": "/storage/templates/business-clean/preview.png",
  "status": "enabled"
}
```

## 8.4 AI 服务配置

```http
GET    /api/platform/ai-providers
POST   /api/platform/ai-providers
GET    /api/platform/ai-providers/{id}
PUT    /api/platform/ai-providers/{id}
DELETE /api/platform/ai-providers/{id}
POST   /api/platform/ai-providers/{id}/test
```

请求：

```json
{
  "name": "OpenAI",
  "provider_key": "openai",
  "base_url": "https://api.openai.com/v1",
  "api_key": "sk-xxx",
  "text_model": "gpt-4.1",
  "image_model": "gpt-image-1",
  "video_model": "",
  "status": "enabled"
}
```

返回时不返回明文 API Key：

```json
{
  "id": 1,
  "name": "OpenAI",
  "provider_key": "openai",
  "base_url": "https://api.openai.com/v1",
  "api_key_masked": "sk-****abcd",
  "text_model": "gpt-4.1",
  "status": "enabled"
}
```

## 8.5 部署节点

```http
GET    /api/platform/deploy-nodes
POST   /api/platform/deploy-nodes
GET    /api/platform/deploy-nodes/{id}
PUT    /api/platform/deploy-nodes/{id}
DELETE /api/platform/deploy-nodes/{id}
POST   /api/platform/deploy-nodes/{id}/test
```

请求：

```json
{
  "name": "宝塔节点 1",
  "node_type": "bt",
  "api_url": "https://server.example.com:8888",
  "api_token": "encrypted-before-save",
  "server_ip": "1.2.3.4",
  "status": "active"
}
```

## 8.6 发布任务

```http
GET /api/platform/publish-tasks
GET /api/platform/publish-tasks/{id}
```

任务详情响应：

```json
{
  "id": 123,
  "site_id": 10001,
  "task_type": "publish_site",
  "status": "success",
  "steps": [
    { "name": "准备数据", "status": "success" },
    { "name": "渲染页面", "status": "success" },
    { "name": "同步目录", "status": "success" }
  ],
  "logs": []
}
```

## 9. 客户后台接口

## 9.1 站点设置

### 9.1.1 获取站点设置

```http
GET /api/site/settings
```

响应：

```json
{
  "success": true,
  "data": {
    "name": "楚云数航",
    "slogan": "低空经济数字化解决方案",
    "logo": "/uploads/site_10001/logo.png",
    "favicon": "/uploads/site_10001/favicon.ico",
    "language": "zh-CN",
    "phone": "13800000000",
    "email": "demo@example.com",
    "address": "湖北武汉",
    "whatsapp": "",
    "seo_title": "楚云数航 - 低空经济解决方案",
    "seo_keywords": "低空经济,无人机,数字化",
    "seo_description": "楚云数航提供低空经济和无人机数字化解决方案。"
  }
}
```

### 9.1.2 更新站点设置

```http
PUT /api/site/settings
```

## 9.2 页面管理

```http
GET    /api/pages
POST   /api/pages
GET    /api/pages/{id}
PUT    /api/pages/{id}
DELETE /api/pages/{id}
POST   /api/pages/{id}/publish
PUT    /api/pages/{id}/modules
POST   /api/pages/{id}/modules/preview
```

页面字段：

```json
{
  "id": 1,
  "title": "关于我们",
  "slug": "about",
  "layout": "page",
  "module_config": [],
  "content": "",
  "seo_title": "",
  "seo_keywords": "",
  "seo_description": "",
  "status": "draft",
  "published_at": null,
  "created_at": "2026-07-07 10:00:00",
  "updated_at": "2026-07-07 10:00:00"
}
```

模块配置请求：

```json
{
  "modules": [
    {
      "module": "hero",
      "visible": true,
      "settings": {
        "title": "智能建站，从化简开始",
        "subtitle": "静态化、高性能、适合 SEO"
      }
    },
    {
      "module": "article-list",
      "visible": true,
      "settings": {
        "title": "行业资讯",
        "count": 6,
        "category_id": 1
      }
    }
  ]
}
```

## 9.3 文章管理

```http
GET    /api/articles
POST   /api/articles
GET    /api/articles/{id}
PUT    /api/articles/{id}
DELETE /api/articles/{id}
POST   /api/articles/{id}/publish
POST   /api/articles/batch-publish
```

请求：

```json
{
  "title": "农业无人机如何提升喷洒效率",
  "slug": "agricultural-drones-spraying-efficiency",
  "category_id": 1,
  "tags": ["无人机", "农业"],
  "cover": "/uploads/site_10001/article-cover.jpg",
  "summary": "本文介绍农业无人机在喷洒效率方面的优势。",
  "content": "<p>正文内容</p>",
  "seo_title": "农业无人机如何提升喷洒效率",
  "seo_keywords": "农业无人机,喷洒效率",
  "seo_description": "了解农业无人机如何提高喷洒效率。",
  "status": "draft",
  "published_at": null
}
```

响应字段：

```json
{
  "id": 1,
  "title": "农业无人机如何提升喷洒效率",
  "slug": "agricultural-drones-spraying-efficiency",
  "url": "/news/agricultural-drones-spraying-efficiency.html",
  "status": "draft"
}
```

## 9.4 文章分类

```http
GET    /api/categories
POST   /api/categories
GET    /api/categories/{id}
PUT    /api/categories/{id}
DELETE /api/categories/{id}
```

字段：

```json
{
  "id": 1,
  "parent_id": 0,
  "name": "行业资讯",
  "slug": "industry-news",
  "description": "",
  "sort_order": 0,
  "seo_title": "",
  "seo_keywords": "",
  "seo_description": ""
}
```

## 9.5 商品管理

```http
GET    /api/products
POST   /api/products
GET    /api/products/{id}
PUT    /api/products/{id}
DELETE /api/products/{id}
POST   /api/products/{id}/publish
```

请求：

```json
{
  "title": "农业植保无人机 X1",
  "slug": "agricultural-spraying-drone-x1",
  "sku": "DRONE-X1",
  "category_id": 1,
  "cover": "/uploads/site_10001/products/x1.jpg",
  "gallery": [],
  "summary": "适合大面积农田喷洒的智能无人机。",
  "description": "<p>商品详情</p>",
  "price": 6999.00,
  "market_price": 7999.00,
  "stock": 100,
  "attributes": {
    "flight_time": "30min",
    "tank_capacity": "20L"
  },
  "seo_title": "",
  "seo_keywords": "",
  "seo_description": "",
  "status": "draft"
}
```

## 9.6 商品分类

```http
GET    /api/product-categories
POST   /api/product-categories
GET    /api/product-categories/{id}
PUT    /api/product-categories/{id}
DELETE /api/product-categories/{id}
```

## 9.7 媒体库

```http
GET    /api/media
POST   /api/media/upload
PUT    /api/media/{id}
DELETE /api/media/{id}
```

上传使用 `multipart/form-data`。

字段：

```text
file
alt_text
folder
```

响应：

```json
{
  "id": 1,
  "file_name": "banner.jpg",
  "file_path": "/uploads/site_10001/images/banner.jpg",
  "file_type": "image",
  "mime_type": "image/jpeg",
  "file_size": 102400,
  "width": 1200,
  "height": 600,
  "alt_text": "首页横幅"
}
```

## 9.8 导航菜单

```http
GET /api/menus
PUT /api/menus/{menu_key}
```

请求：

```json
{
  "items": [
    {
      "title": "首页",
      "type": "home",
      "url": "/",
      "target": "_self",
      "children": []
    },
    {
      "title": "产品中心",
      "type": "product_category",
      "target_id": 1,
      "url": "/product-category/drones/",
      "children": []
    }
  ]
}
```

## 9.9 AI 助手

### 9.9.1 对话入口

```http
POST /api/ai/chat
```

请求：

```json
{
  "message": "请根据我们的商品生成 5 篇行业文章",
  "context": {
    "target": "articles",
    "category_id": 1,
    "product_ids": [1, 2, 3]
  }
}
```

响应：

```json
{
  "success": true,
  "data": {
    "task_id": 100,
    "message": "已创建 AI 任务，我会生成 5 篇文章草稿。",
    "plan": [
      { "action": "read_products", "count": 3 },
      { "action": "create_article_drafts", "count": 5 },
      { "action": "generate_seo", "count": 5 }
    ],
    "requires_confirmation": false
  }
}
```

### 9.9.2 生成文章

```http
POST /api/ai/articles/generate
```

请求：

```json
{
  "topic": "农业无人机出口和应用",
  "keywords": ["农业无人机", "植保无人机", "低空经济"],
  "count": 5,
  "category_id": 1,
  "language": "zh-CN",
  "generate_cover": true,
  "publish_mode": "draft"
}
```

### 9.9.3 AI 任务列表

```http
GET /api/ai/tasks
```

响应字段：

```json
{
  "id": 100,
  "task_type": "article_generate",
  "status": "success",
  "prompt": "生成 5 篇行业文章",
  "created_at": "2026-07-07 10:00:00",
  "finished_at": "2026-07-07 10:01:30"
}
```

### 9.9.4 确认 AI 任务结果

```http
POST /api/ai/tasks/{id}/confirm
```

请求：

```json
{
  "selected_items": [0, 1, 2],
  "action": "save_draft"
}
```

action 可选：

```text
save_draft
publish
discard
```

## 9.10 发布管理

### 9.10.1 生成静态站

```http
POST /api/site/generate
```

请求：

```json
{
  "scope": "all"
}
```

scope 可选：

```text
all
home
articles
products
pages
single_article
single_product
```

响应：

```json
{
  "task_id": 200,
  "status": "pending"
}
```

### 9.10.2 发布上线

```http
POST /api/site/publish
```

请求：

```json
{
  "version_no": "version_20260707_001",
  "target": "public"
}
```

### 9.10.3 发布版本

```http
GET /api/site/publish-versions
```

响应字段：

```json
{
  "id": 1,
  "version_no": "version_20260707_001",
  "publish_type": "all",
  "file_count": 128,
  "status": "success",
  "created_at": "2026-07-07 10:00:00"
}
```

### 9.10.4 回滚版本

```http
POST /api/site/rollback
```

请求：

```json
{
  "version_no": "version_20260707_001"
}
```

## 9.11 表单留言

### 9.11.1 前台提交表单

```http
POST /api/forms/submit
```

请求：

```json
{
  "form_key": "contact",
  "source_url": "/products/agricultural-spraying-drone-x1.html",
  "data": {
    "name": "张三",
    "phone": "13800000000",
    "email": "demo@example.com",
    "message": "我想了解这个产品"
  }
}
```

### 9.11.2 后台留言列表

```http
GET /api/forms/submissions
```

### 9.11.3 更新留言状态

```http
PUT /api/forms/submissions/{id}
```

请求：

```json
{
  "status": "processed",
  "remark": "已联系客户"
}
```

## 9.12 采集中心

```http
GET    /api/collector/sources
POST   /api/collector/sources
PUT    /api/collector/sources/{id}
DELETE /api/collector/sources/{id}
POST   /api/collector/sources/{id}/run
GET    /api/collector/items
POST   /api/collector/items/{id}/rewrite
POST   /api/collector/items/{id}/publish
```

采集源请求：

```json
{
  "name": "行业新闻 RSS",
  "source_type": "rss",
  "source_url": "https://example.com/rss.xml",
  "category_id": 1,
  "fetch_interval_minutes": 1440,
  "rewrite_with_ai": true,
  "status": "enabled"
}
```

## 9.13 目标网站转模板

### 9.13.1 创建转换任务

```http
POST /api/ai/template/clone
```

请求：

```json
{
  "url": "https://example.com",
  "template_name": "参考官网模板",
  "style_mode": "rebuild",
  "replace_brand": true
}
```

响应：

```json
{
  "task_id": 300,
  "status": "pending"
}
```

### 9.13.2 获取模板转换结果

```http
GET /api/ai/template/clone/{task_id}
```

响应：

```json
{
  "task_id": 300,
  "status": "success",
  "data": {
    "template_key": "custom-300",
    "preview_image": "/storage/templates/custom-300/preview.png",
    "recognized_modules": [
      "header-nav",
      "hero",
      "product-grid",
      "article-list",
      "footer"
    ]
  }
}
```

## 10. 前台动态 API

前台静态页面只允许调用必要动态 API。

第一版：

```text
POST /api/forms/submit
GET  /api/search
GET  /api/site/public-config
```

### 10.1 搜索

```http
GET /api/search?keyword=无人机
```

也可以第一版使用静态 `search.json` 在浏览器本地搜索。

推荐第一版：

```text
小站点用 search.json。
大站点后续再接动态搜索 API。
```

### 10.2 站点公开配置

```http
GET /api/site/public-config?site_id=10001
```

用途：

```text
静态前台读取当前站点可公开展示的轻量配置。
用于支付说明、客服入口、站点联系方式、导航和前台功能开关。
不得返回 AI Key、部署密钥、后台 Token 等敏感信息。
```

响应示例：

```json
{
  "site": {
    "id": 10001,
    "name": "楚云数航",
    "domain": "example.com",
    "phone": "13800000000",
    "email": "demo@example.com"
  },
  "payment": {
    "mode": "manual",
    "currency": "CNY",
    "account": "收款账号",
    "instructions": "付款后请提交流水号"
  },
  "service": {
    "floating_enabled": true,
    "phone": "13800000000",
    "email": "demo@example.com",
    "whatsapp": ""
  },
  "features": {
    "forms": true,
    "search": true,
    "orders": true,
    "payment_proof": true,
    "service_request": true
  }
}
```

## 11. 通用字段规范

### 11.1 时间

统一格式：

```text
YYYY-MM-DD HH:mm:ss
```

### 11.2 状态字段

内容：

```text
draft
pending
published
hidden
archived
```

任务：

```text
pending
running
success
failed
canceled
```

站点：

```text
active
suspended
deploying
error
deleted
```

### 11.3 Slug

规则：

```text
小写英文
数字
中横线
不可重复
不可为空
```

中文标题可自动生成拼音或英文翻译 slug。

## 12. 操作日志规则

这些操作必须写日志：

```text
登录
新建站点
修改站点设置
上传模板
发布网站
回滚版本
删除文章
删除商品
配置 AI Key
配置部署节点
AI 任务确认
```

日志字段：

```json
{
  "user_id": 1,
  "site_id": 10001,
  "action": "publish_site",
  "target_type": "site",
  "target_id": 10001,
  "ip_address": "127.0.0.1",
  "result": "success"
}
```

## 13. 安全约束

### 13.1 上传文件

允许：

```text
jpg
jpeg
png
webp
gif
svg
pdf
doc
docx
xls
xlsx
```

禁止：

```text
php
phtml
phar
exe
bat
cmd
sh
js 上传到可执行目录
```

### 13.2 模板上传

模板包禁止包含：

```text
PHP 文件
隐藏文件
可执行脚本
../ 路径
超大文件
外链恶意脚本
```

### 13.3 API Key

```text
入库前加密
返回前脱敏
操作写日志
只有平台管理员可配置
```

## 14. 前端错误处理

后台前端统一处理：

```text
401 跳转登录
403 显示无权限
404 显示资源不存在
422 显示字段错误
500 显示系统错误和请求 ID
```

## 15. 接口开发优先级

第一批：

```text
auth
site settings
articles
categories
products
product categories
media upload
site generate
publish versions
```

第二批：

```text
ai tasks
ai article generate
forms submit
templates
menus
```

第三批：

```text
deploy nodes
bt publish
collector
template clone
operation logs
```
