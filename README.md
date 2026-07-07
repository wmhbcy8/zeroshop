# 化简 MVP

化简是一个“后台动态管理，前台静态生成”的轻量 SaaS 建站系统。当前 MVP 用 PHP + MySQL 管理内容，再把官网、博客知识库、商品页生成成 SEO 友好的 HTML 静态站。

## 当前已包含

- 演示数据：`demo-data/`
- 第一套模板：`templates/business-clean/`
- PHP 静态生成器：`worker/GenerateSite.php`
- Node 验证生成器：`worker/generate-site.mjs`
- 最小 API：`server/public/index.php`
- 临时后台：`server/public/admin.html`
- 生成目录：`sites/site_10001/public/`
- 站点配置：企业信息、SEO、首页首屏、导航菜单、首页模块内容、开关和排序
- 首页标准模块：图文介绍、优势卖点、案例展示、产品模块、文章模块、FAQ、询盘表单

## 启动 API 和后台

本地开发目录可以放置便携版 PHP，仓库默认不提交 `tools/` 目录：

```text
tools/php/php.exe
```

如果本机已经安装 PHP，也可以把下面命令里的 `tools/php/php.exe` 换成 `php`。推荐使用启动脚本：

```powershell
scripts/start-api.ps1
```

或手动启动：

```powershell
$env:HJ_DB_HOST="192.168.2.6"
$env:HJ_DB_PORT="3306"
$env:HJ_DB_USERNAME="root"
$env:HJ_DB_PASSWORD="你的数据库密码"
$env:HJ_DB_SITE="huajian_site_10001"
$env:HJ_ADMIN_USERNAME="admin"
$env:HJ_ADMIN_PASSWORD="admin123456"
tools/php/php.exe -S 127.0.0.1:8000 -t server/public server/public/index.php
```

打开后台：

```text
http://127.0.0.1:8000/admin.html
```

默认试用账号：

```text
admin / admin123456
```

## MySQL 初始化

```powershell
$env:HJ_DB_HOST="192.168.2.6"
$env:HJ_DB_PORT="3306"
$env:HJ_DB_USERNAME="root"
$env:HJ_DB_PASSWORD="你的数据库密码"
$env:HJ_DB_MAIN="huajian_main"
$env:HJ_DB_SITE="huajian_site_10001"
$env:HJ_ADMIN_USERNAME="admin"
$env:HJ_ADMIN_PASSWORD="admin123456"
tools/php/php.exe scripts/db_bootstrap.php
```

初始化会创建：

```text
huajian_main
huajian_site_10001
```

并导入演示站点、分类、文章、商品、媒体、留言、发布记录、后台账号和登录会话表。

## 静态站生成

直接运行生成器：

```powershell
tools/php/php.exe worker/GenerateSite.php
```

或在后台点击“生成静态站”。生成后访问：

```text
http://127.0.0.1:8000/
```

## 当前后台能力

- 登录、退出和接口保护
- 站点信息、SEO、首页首屏文案
- 导航菜单新增、删除、排序
- 首页图文介绍、优势卖点、案例展示、产品模块、文章模块、FAQ、询盘表单的内容、开关和排序
- 首页模块支持上移、下移和排序号自动重排
- 首页模块支持移除，并可从模块预设库重新添加
- 优势卖点、案例展示和 FAQ 支持可视化新增、删除、编辑和排序
- 首页询盘表单字段支持可视化配置字段名、类型、占位提示、必填和排序
- 站点设置支持“保存并生成”，修改模块后可直接同步到前台静态页
- 文章、商品、媒体、留言和发布记录管理
- 订单管理支持订单列表、商品明细、支付状态、履约状态、来源页面和后台备注
- 订单列表支持关键词搜索、支付/履约状态筛选和订单概览统计，便于客服处理待支付、待确认和已完成订单
- 订单跟进支持物流公司、物流单号、支付时间、发货时间和跟进时间线，可一键标记已支付或已发货
- 前台静态站生成 `order.html` 订单查询页，客户可用订单号和手机号查询支付、发货和物流状态
- 商品页下单成功后会显示订单回执卡，提供一键查单链接，并在浏览器本地保存最近订单便于再次查询
- 客户可在订单查询页提交付款说明、开票需求、售后说明或补充说明，内容会追加到后台订单时间线
- 后台订单详情支持结构化时间线、客服快捷备注和订单号/手机号/查单链接一键复制
- 后台订单列表支持状态快捷筛选、统计卡筛选和按当前筛选条件导出 CSV
- 留言列表会按询盘字段配置显示字段标题，便于客服快速识别客户需求
- 留言支持详情查看、跟进状态、客户等级和跟进备注，适合作为轻量客服线索池
- 站点设置包含 AI 服务配置占位，文章和商品支持 AI 草稿生成入口
- AI 草稿生成已接入后端 `/api/ai/generate`，配置真实模型后可切换到 OpenAI-compatible 远程调用；未配置时自动使用本地草稿
- AI 页面搭建支持根据一句话生成首页草案、查看差异预览，并可一键应用、保存和生成静态站
- AI 页面搭建会先从用户指令中提炼页面主题，避免把“帮我搭一个……”这类操作指令直接写进首页标题或案例标题
- 文章支持 AI 批量生成草稿，可一次生成多篇不同角度的 SEO 内容
- 商品支持 AI 批量生成草稿，可一次生成多个不同定位的商品页
- 文章和商品支持 AI 生成封面图，当前 MVP 生成本地 SVG 并自动写入媒体库
- 文章和商品支持从媒体库选择封面，媒体卡片也可直接设为文章或商品封面
- 文章和商品保存、AI 批量生成后可自动同步静态站，前台页面立即更新
- 一键生成静态首页、列表页、详情页、联系页、站点地图和搜索索引
- 站点设置支持支付配置和宝塔部署配置占位，发布页可先检查部署参数是否完整

## 前台静态搜索

- 发布静态站时会生成 `search.html` 和 `search.json`
- `search.html` 使用纯 HTML + CSS + JavaScript 读取 `search.json`
- 支持关键词搜索、文章/产品类型筛选和 `?q=关键词` 直达搜索
- 搜索不访问数据库，适合随静态站一起部署到宝塔、Nginx、对象存储或 CDN

## 前台静态下单

- 商品详情页会生成纯静态下单表单，表单通过 JavaScript 提交到 `POST /api/orders`
- 下单成功后前台显示订单号，便于客户截图或客服跟进
- 后台订单详情会显示商品明细、客户信息、来源页面和客户备注，适合先做人工确认、线下收款或后续接入支付网关

## 模块注册表

- 标准模块定义在 `config/module-registry.json`
- 后台通过 `GET /api/site/modules` 读取模块注册表
- 当前包含全站、首页、详情页 3 类模块，共 11 个模块
- 注册表记录 `key`、作用范围、配置路径、渲染插槽和说明，后续可作为 AI 搭积木和模板市场的统一模块字典

## 当前 API

登录：

```text
POST /api/auth/login
GET  /api/auth/me
POST /api/auth/logout
```

内容管理接口需要 `Authorization: Bearer <token>`：

```text
GET/PUT          /api/site/settings
GET              /api/site/modules
GET/POST         /api/articles
GET/PUT/DELETE   /api/articles/{id}
GET/POST         /api/products
GET/PUT/DELETE   /api/products/{id}
GET/PUT/DELETE   /api/orders/{id}
GET              /api/orders
GET/POST         /api/categories
PUT/DELETE       /api/categories/{id}
GET/POST         /api/product-categories
PUT/DELETE       /api/product-categories/{id}
GET              /api/media
POST             /api/media/upload
PUT/DELETE       /api/media/{id}
GET              /api/forms/submissions
GET/PUT/DELETE   /api/forms/submissions/{id}
POST             /api/ai/generate
POST             /api/ai/page-plan
POST             /api/ai/batch-articles
POST             /api/ai/batch-products
POST             /api/ai/generate-image
POST             /api/site/generate
POST             /api/site/deploy-test
GET              /api/site/publish-versions
GET              /api/orders/export
```

前台留言提交保持开放：

```text
POST /api/forms/submit
POST /api/orders
POST /api/orders/lookup
POST /api/orders/customer-note
```

## 安全提醒

不要把真实数据库密码写进仓库。正式部署时建议通过服务器环境变量、面板配置或独立配置文件注入。

AI 大模型 API Key 当前只是 MVP 配置占位。正式上线前建议改为环境变量、加密存储或按租户独立密钥托管，不要在前端输出真实密钥。
