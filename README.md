# 化简 MVP

化简是一个“后台动态管理，前台静态生成”的轻量 SaaS 建站系统。当前 MVP 用 PHP + MySQL 管理内容，再把官网、博客知识库、商品页生成成 SEO 友好的 HTML 静态站。

## 当前已包含

- 演示数据：`demo-data/`
- 第一套模板：`templates/business-clean/`
- 模板中心已内置三套 MVP 模板：`business-clean`、`blog-knowledge`、`product-showcase`
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

新版 Vue 后台入口：
```text
http://127.0.0.1:8000/admin-vue/
```

新版后台源码位于 `admin-vue/`，使用 Vue 3 + Vite + Element Plus，并保留 Art Design Pro 的 MIT License；旧版 `admin.html` 后台继续保留。

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
- 全站浮动咨询支持联系页、电话、邮箱、WhatsApp 和微信入口，适合商品询盘型独立站转化
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
- 站点支付配置支持收款账户和付款指引，前台下单回执/查单页可展示付款说明，后台订单详情显示收款摘要
- 客户可在订单查询页提交付款金额、流水号或截图编号，后台可用“待核款”快捷筛选集中核对
- 支付通道支持通用回调入口，支付平台用 `webhook_secret` 对原始 JSON 请求体做 HMAC-SHA256 签名后，可自动同步订单支付状态
- 查单页会展示配送进度卡，后台订单详情可复制发货通知并查看物流摘要
- 客户可在查单页提交催发货、修改收货信息、售后问题等服务请求，后台可用快捷筛选处理
- 后台订单详情会识别最新服务请求，支持填写客服回复或一键标记已处理，客户查单页可看到服务进度
- 后台新增服务中心，可集中筛选待处理/已处理服务请求，并一键跳转到对应订单处理
- 服务中心支持待处理数量提醒和批量标记已处理，方便客服集中清理请求队列
- 后台概览展示今日访客、访问深度、今日支付金额和待处理订单，前台静态页会自动上报访问统计
- 中台概览新增待办中心，会按订单、询盘、发布、域名、部署和内容完整度自动生成站点级处理队列
- 留言列表会按询盘字段配置显示字段标题，便于客服快速识别客户需求
- 留言支持详情查看、跟进状态、客户等级和跟进备注，适合作为轻量客服线索池
- 站点设置包含 AI 服务配置占位，文章和商品支持 AI 草稿生成入口
- 新版中台把 AI 内容生产和 AI 接口配置拆成独立菜单；支付通道与宝塔部署也分别在左侧“支付”“部署”中维护
- 设置页只维护站点基础信息、SEO、导航和页面结构；AI、支付与宝塔部署属于站点服务能力，统一从左侧独立菜单配置
- AI 菜单新增对话式指令入口，用户可直接输入“给当前站点生成 5 篇行业文章”等自然语言，系统会解析内容类型、数量、站点范围并返回可执行计划；文章/商品指令会自动创建 AI 任务等待确认
- AI 草稿生成已接入后端 `/api/ai/generate`，配置真实模型后可切换到 OpenAI-compatible 远程调用；未配置时自动使用本地草稿
- AI 页面搭建支持根据一句话生成首页草案、查看差异预览，并可一键应用、保存和生成静态站
- AI 页面搭建会先从用户指令中提炼页面主题，避免把“帮我搭一个……”这类操作指令直接写进首页标题或案例标题
- 目标网站转模板草稿会生成可选主题模板，应用后同步更新中台站点模板并自动生成当前站点静态预览
- AI 任务记录支持生成结构化草稿、查看任务结果，并在确认后保存为草稿、直接发布或丢弃
- 文章支持 AI 批量生成草稿，可一次生成多篇不同角度的 SEO 内容
- 商品支持 AI 批量生成草稿，可一次生成多个不同定位的商品页
- 文章、商品、页面和 AI 批量入库都采用“一份内容库，多站点分发”逻辑，可发布到当前站点、全部启用站点或指定站点；生成静态站时每个前台只读取分发给自己的内容
- 文章、商品和页面列表支持按“全部内容库 / 当前站点 / 指定站点”查看，批量分发和 AI 入库沿用同一套站点范围逻辑
- 当选择“指定站点”发布文章、商品或 AI 内容时，必须明确选择目标站点，系统不会静默回退到当前站点
- 文章支持标签维护，发布静态站时会生成 `/tag/{slug}/index.html` 标签聚合页，并同步写入 `sitemap.xml` 和 `search.json`
- 发布静态站时会生成 `rss.xml` 文章订阅源，模板头部会自动写入 RSS 发现链接
- 文章和商品支持 AI 生成封面图，当前 MVP 生成本地 SVG 并自动写入媒体库
- 平台客户套餐开始参与中台实际约束：站点数、AI 生成次数和媒体库容量会按客户配额校验，概览页展示当前用量
- 平台总后台新增系统设置，可维护平台名称、默认域名、新客户默认套餐额度和新站默认语言/模板，新建客户会自动带出默认配额
- 采集中心支持 RSS/指定 URL 采集源管理、立即采集、采集记录去重，并可按采集源入库方式自动转为文章草稿或发布文章；发布后会同步生成对应站点静态页
- 文章和商品支持从媒体库选择封面，媒体卡片也可直接设为文章或商品封面
- 文章和商品保存、AI 批量生成后可自动同步静态站，前台页面立即更新
- 一键生成静态首页、列表页、详情页、联系页、404 页、站点地图和搜索索引
- 站点设置支持支付配置和宝塔部署配置，发布页可检查部署参数、生成发布包或执行本机目录同步
- 发布页支持创建站点静态目录备份、恢复备份和删除备份；恢复前会自动保存当前状态，降低误操作风险
- 部署模式支持手动发布、发布包上传、本机目录同步、宝塔 API 和 FTP/SFTP；当前版本的本机目录同步会复制到 `storage/deploy_targets/`，宝塔 API 与 FTP/SFTP 会生成标准发布包和待执行任务，任务详情可查看检查项、执行步骤、下载发布包并重试
- 域名管理支持单个/批量检查，自动探测 DNS 解析和 HTTPS 可访问状态，检查结果会参与部署计划与 SEO 诊断

## 前台静态搜索

- 发布静态站时会生成 `search.html` 和 `search.json`
- `search.html` 使用纯 HTML + CSS + JavaScript 读取 `search.json`
- `rss.xml` 会输出最近文章列表，适合博客知识库、新闻动态和 SEO 内容站被订阅工具抓取
- 支持关键词搜索、文章/产品类型筛选和 `?q=关键词` 直达搜索
- 搜索不访问数据库，适合随静态站一起部署到宝塔、Nginx、对象存储或 CDN

## 前台静态下单

- 商品详情页会生成纯静态下单表单，表单通过 JavaScript 提交到 `POST /api/orders`
- 前台静态站新增 `cart.html` 购物车页，商品详情页可加入购物车，浏览器本地保存商品并一次性提交多商品订单
- 下单成功后前台显示订单号，便于客户截图或客服跟进
- 后台订单详情会显示商品明细、客户信息、来源页面和客户备注，适合先做人工确认、线下收款或后续接入支付网关
- 下单时服务端会按后台商品库校准商品标题、SKU 和价格，校验当前站点可售状态并扣减库存；订单关闭、退款或删除时会自动回补库存
- 前台访问统计、留言、下单、查单、付款凭证和服务请求接口已接入数据库限流，避免公开静态页 API 被高频刷写

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
GET              /api/dashboard/metrics
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
POST             /api/ai/chat
POST             /api/ai/generate
POST             /api/ai/page-plan
POST             /api/ai/batch-articles
POST             /api/ai/batch-products
POST             /api/ai/generate-image
POST             /api/site/generate
POST             /api/site/deploy-test
GET              /api/site/publish-versions
GET              /api/orders/export
GET              /api/orders/service-requests
POST             /api/orders/service-requests/resolve
```

前台留言提交保持开放：

```text
POST /api/forms/submit
POST /api/analytics/visit
POST /api/orders
POST /api/orders/lookup
POST /api/orders/customer-note
POST /api/orders/payment-proof
POST /api/orders/service-request
POST /api/payment/webhook
```
这些前台开放接口会按站点、访问 IP、浏览器标识和业务参数写入 `api_rate_limits` 进行频率限制；超过限制时返回 `429 RATE_LIMITED`。

通用支付回调：

```text
POST /api/payment/webhook?channel_id=通道ID
Header: X-HJ-Signature: sha256=<hex hmac sha256>
Body: {"site_id":10001,"channel_id":1,"order_no":"ZS...","status":"paid","amount":19999,"currency":"CNY","transaction_id":"PAY..."}
```

签名密钥来自支付通道接口参数里的 `webhook_secret`。回调会写入 `payment_webhook_events` 做幂等记录，并同步订单 `payment_status`、`paid_at` 和订单时间线。

## 安全提醒

不要把真实数据库密码写进仓库。正式部署时建议通过服务器环境变量、面板配置或独立配置文件注入。

AI 大模型、支付通道和部署节点的敏感密钥会在入库前加密存储，后台接口只返回掩码。正式部署时建议设置稳定的 `HJ_APP_KEY`，或妥善备份本机生成的 `storage/secret.key`，避免更换服务器后无法解密历史配置。

前台公开动态 API 已启用持久化限流；正式上线时仍建议在宝塔/Nginx/CDN 层叠加 IP 访问频率限制和基础 WAF 规则。
