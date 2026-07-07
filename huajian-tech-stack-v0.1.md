# 化简技术栈与工程结构 v0.1

## 1. 技术选型总览

化简采用三层技术路线：

```text
后台管理端
  Art Design Pro / Vue3 / TypeScript / Vite / Element Plus / Tailwind CSS

业务服务端
  PHP / MySQL / REST API / 队列任务

生成网站前台
  HTML / CSS / JavaScript / 静态文件
```

核心思路：

- Vue 后台负责现代化管理体验。
- PHP 后端负责业务数据、权限、AI、发布和部署。
- 静态前台负责 SEO、访问速度和站群承载。

## 2. 推荐仓库结构

```text
huajian/
  admin-web/
  server/
  worker/
  templates/
  modules/
  plugins/
  storage/
  sites/
  docs/
  scripts/
  README.md
```

目录说明：

```text
admin-web   后台前端，基于 Art Design Pro 改造。
server      PHP 后端 API 和后台业务。
worker      静态生成、AI 任务、采集任务、发布任务。
templates   化简前台主题模板。
modules     化简标准模块。
plugins     后续插件包。
storage     上传文件、构建缓存、发布版本、日志。
sites       各站点生成后的 public 目录。
docs        产品和技术文档。
scripts     安装、升级、备份、部署脚本。
```

## 3. admin-web 结构

基于 Art Design Pro 改造。

```text
admin-web/
  src/
    api/
    assets/
    components/
    config/
    hooks/
    layouts/
    router/
    stores/
    styles/
    utils/
    views/
      platform/
      site/
      auth/
      error/
  package.json
  vite.config.ts
```

### 3.1 页面目录

```text
views/platform/
  dashboard/
  customers/
  sites/
  templates/
  modules/
  ai-providers/
  deploy-nodes/
  publish-tasks/
  logs/
  settings/

views/site/
  dashboard/
  ai-assistant/
  settings/
  pages/
  templates/
  articles/
  categories/
  products/
  product-categories/
  media/
  menus/
  forms/
  collector/
  seo/
  publish/
```

### 3.2 API 分组

```text
src/api/platform.ts
src/api/site.ts
src/api/pages.ts
src/api/articles.ts
src/api/products.ts
src/api/templates.ts
src/api/modules.ts
src/api/ai.ts
src/api/publish.ts
src/api/collector.ts
src/api/media.ts
```

### 3.3 前端状态

建议保留这些状态：

```text
用户信息
当前站点
权限菜单
主题设置
打开的标签页
AI 任务状态
发布任务状态
```

## 4. server 结构

PHP 后端推荐先用轻量 MVC 结构，第一版不用过度框架化。

```text
server/
  public/
    index.php
  app/
    Controllers/
    Services/
    Models/
    Middleware/
    Validators/
    Support/
  config/
    app.php
    database.php
    ai.php
    storage.php
  routes/
    api.php
  database/
    migrations/
    seeds/
  storage/
    logs/
  composer.json
```

如果后续采用框架，可以选 Laravel 或 ThinkPHP。

早期建议：

```text
熟悉 Laravel 就用 Laravel。
熟悉 ThinkPHP 就用 ThinkPHP。
想保持轻量就自建小型 MVC。
```

化简的关键不在 PHP 框架，而在数据模型、模板规范和生成流程。

## 5. worker 结构

worker 负责耗时任务，不直接阻塞后台请求。

```text
worker/
  GenerateSite.php
  GenerateArticle.php
  GenerateProduct.php
  PublishSite.php
  RollbackSite.php
  AiTaskRunner.php
  CollectorRunner.php
  TemplateCloneRunner.php
```

任务类型：

```text
静态生成
AI 文章生成
AI 图片生成
目标网站转模板
新闻采集
站点发布
版本回滚
宝塔部署
```

第一版可以用数据库任务表轮询。

后续再接入：

```text
Redis 队列
Supervisor
定时任务
消息队列
```

## 6. templates 结构

前台主题模板。

```text
templates/
  business-clean/
    template.json
    preview.png
    pages/
    partials/
    modules/
    assets/
  blog-knowledge/
  product-showcase/
```

模板只允许：

```text
HTML
CSS
JavaScript
图片
字体
JSON 配置
```

模板不允许：

```text
PHP
可执行脚本
数据库连接
跨目录读取
```

## 7. modules 结构

标准模块库。

```text
modules/
  hero/
    module.json
    view.html
    style.css
    script.js
  article-list/
  product-grid/
  contact-form/
```

模块可以被多个模板复用。

模板也可以有自己的局部模块：

```text
templates/business-clean/modules/
```

优先级：

```text
模板内模块 > 全局标准模块
```

## 8. storage 结构

```text
storage/
  uploads/
    site_10001/
      images/
      files/
  build/
    site_10001/
      build_20260707_001/
  publish_versions/
    site_10001/
      version_20260707_001/
  logs/
    app.log
    publish.log
    ai.log
    collector.log
  cache/
```

原则：

- 上传文件不要直接放模板目录。
- 生成文件先进入 build。
- 发布版本单独保存。
- public 目录只放当前线上文件。

## 9. sites 结构

```text
sites/
  site_10001/
    public/
      index.html
      news/
      products/
      assets/
      sitemap.xml
      robots.txt
      search.json
  site_10002/
    public/
```

宝塔站点目录可以直接指向：

```text
sites/site_10001/public/
```

也可以发布时同步到宝塔指定目录：

```text
/www/wwwroot/customer-domain.com/
```

## 10. 数据库连接策略

### 10.1 平台主库

保存：

```text
客户
站点
域名
模板
模块
插件
AI 服务商
部署节点
发布任务
操作日志
```

### 10.2 站点独立库

保存：

```text
页面
文章
分类
标签
商品
商品分类
媒体
菜单
表单
订单
AI 任务
采集记录
发布版本
```

### 10.3 连接选择

后台进入某个站点后：

```text
根据 site_id 查询 database_name
切换到该站点数据库
执行站点业务查询
```

## 11. 接口认证

第一版建议：

```text
后台登录使用 Session 或 JWT。
API 请求带 Authorization。
客户只能访问自己的 site_id。
平台管理员可访问所有 site_id。
```

建议权限模型：

```text
platform_admin
customer_admin
site_editor
site_viewer
```

第一版可以只做：

```text
platform_admin
customer_admin
```

## 12. 静态生成器设计

核心输入：

```text
站点设置
模板文件
页面配置
文章数据
商品数据
媒体资源
导航菜单
模块配置
```

核心输出：

```text
HTML 文件
CSS/JS/图片资源
sitemap.xml
robots.txt
rss.xml
search.json
```

生成器流程：

```text
加载站点
加载模板
加载模块
构建渲染上下文
渲染页面
写入 build 目录
校验文件
保存发布版本
```

## 13. 模板渲染核心

第一版模板引擎只做：

```text
变量替换
HTML 安全转义
富文本输出
if 判断
each 循环
include 引用
asset 路径
slot 模块插槽
seo_meta 输出
```

不做：

```text
复杂函数
递归
模板内数据库查询
模板内 PHP 执行
```

## 14. AI 接入方式

AI 服务统一走后端。

前端不直接持有 API Key。

流程：

```text
admin-web 发起 AI 请求
server 创建 ai_tasks
worker 执行 AI 调用
结果写入数据库
admin-web 轮询或订阅任务状态
用户确认
server 执行写入文章、图片、模块配置等动作
```

AI 输出必须结构化：

```json
{
  "message": "已为你规划 5 篇文章",
  "actions": [],
  "requires_confirmation": true
}
```

## 15. 采集接入方式

采集任务也走 worker。

流程：

```text
客户配置采集源
server 创建采集任务
worker 抓取内容
AI 判断和改写
保存为草稿
客户审核
发布静态页
```

第一版支持：

```text
RSS
指定 URL
手动粘贴
```

## 16. 目标网站转模板工程流程

流程：

```text
输入 URL
worker 抓取 HTML
生成截图
抽取 CSS 和结构
AI 识别模块
AI 生成化简模板
保存到 templates/custom-{id}
生成 preview.png
客户预览
启用模板
```

注意：

- 默认不保留对方品牌。
- 默认不保留对方商标图片。
- 默认不照搬原文。
- 用占位图和客户资料替换。

## 17. 宝塔部署

部署方式：

```text
server 调用宝塔 API
创建站点
绑定域名
申请 SSL
同步静态文件
```

同步文件方式：

第一版：

```text
PHP 直接复制到本机目录
或通过宝塔文件接口上传
```

后续：

```text
rsync
SFTP
自研 Agent
对象存储 + CDN
```

## 18. 日志设计

必须记录：

```text
登录日志
操作日志
AI 任务日志
发布日志
部署日志
采集日志
错误日志
```

日志要能关联：

```text
user_id
customer_id
site_id
task_id
ip_address
created_at
```

## 19. 配置文件

`.env` 示例：

```text
APP_ENV=local
APP_URL=http://localhost

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=huajian_main
DB_USERNAME=root
DB_PASSWORD=

STORAGE_PATH=../storage
SITES_PATH=../sites
TEMPLATES_PATH=../templates

AI_DEFAULT_PROVIDER=openai

BT_API_URL=
BT_API_TOKEN=
```

## 20. 本地开发启动

推荐：

```text
admin-web 使用 npm run dev
server 使用 PHP 内置服务或本地 Nginx
MySQL 本地启动
worker 使用 PHP CLI 手动执行
```

本地访问：

```text
后台前端：http://localhost:5173
后端 API：http://localhost:8000
生成站点：http://localhost:8080/site_10001
```

## 21. 生产部署建议

初期单机：

```text
Nginx
PHP-FPM
MySQL
Node 只用于构建 admin-web
Supervisor 跑 worker
宝塔面板管理站点
```

中期多机：

```text
平台主服务一台
MySQL 独立
多个静态站点节点
对象存储
CDN
队列服务
```

## 22. MVP 开发顺序

```text
1. 初始化 admin-web
2. 初始化 PHP server
3. 建平台主库和站点库
4. 实现登录和站点切换
5. 实现站点设置
6. 实现文章和分类
7. 实现模板引擎
8. 实现静态生成
9. 实现发布版本
10. 实现 AI 文章生成
11. 实现商品和询盘
12. 实现宝塔发布
13. 实现目标 URL 转模板草稿
14. 实现采集中心
```

## 23. 关键决策

### 23.1 为什么后台用 Vue

后台需要大量表格、表单、弹窗、抽屉、任务状态和实时反馈。

Vue + Art Design Pro 可以节省大量界面工程。

### 23.2 为什么后端用 PHP

PHP 适合 Web 后台、MySQL、宝塔部署，也符合你熟悉的技术路径。

### 23.3 为什么前台不用 Vue

生成网站前台要服务 SEO 和站群性能。

静态 HTML 比前端 SPA 更适合搜索引擎、缓存、迁移和批量部署。

### 23.4 为什么商城先做询盘

完整电商涉及购物车、支付、库存、物流、售后，复杂度高。

先做商品展示 + 询盘，可以最快验证独立站价值。

