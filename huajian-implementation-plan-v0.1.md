# 化简实施计划 v0.1

## 1. 实施目标

第一阶段目标不是做完整 SaaS，而是做出一个可演示、可验证、可继续扩展的 MVP。

MVP 主链路：

```text
登录后台
创建站点
配置站点信息
选择模板
发布文章
发布商品
AI 生成内容
生成静态网站
预览网站
发布版本
回滚版本
```

第一阶段完成后，应能演示：

```text
一个客户登录自己的后台。
用 AI 生成几篇文章。
新增几个商品。
点击生成静态网站。
打开生成出来的 index.html、文章页、商品页。
修改内容后重新发布。
回滚到上一版。
```

## 2. 开发阶段总览

```text
第 1 周：项目骨架和数据库
第 2 周：后台基础页面和内容管理
第 3 周：模板引擎和静态生成器
第 4 周：AI 内容生成和媒体库
第 5 周：商品展示、询盘和发布版本
第 6 周：宝塔部署、目标 URL 转模板草稿、采集中心雏形
```

如果一个人开发，6 周是紧凑节奏。

如果多人协作，可以压缩到 3-4 周。

## 3. 第 1 周：项目骨架和数据库

### 3.1 目标

搭好前后端基础工程，能登录后台，能连接数据库，能区分平台后台和客户后台。

### 3.2 任务

```text
初始化 admin-web
清理 Art Design Pro 演示页面
配置化简品牌名称和基础主题
初始化 PHP server
配置路由
配置数据库连接
创建平台主库 migration
创建站点库 migration
实现登录接口
实现用户信息接口
实现站点切换接口
```

### 3.3 页面

```text
登录页
平台后台仪表盘空壳
客户后台概览空壳
站点切换器
```

### 3.4 数据表

优先创建：

```text
customers
sites
site_domains
templates
modules
ai_providers
deploy_tasks
site_settings
pages
articles
categories
products
product_categories
media
publish_versions
ai_tasks
```

### 3.5 验收

```text
能打开后台登录页。
能使用测试账号登录。
平台管理员能看到平台后台。
客户管理员能看到客户后台。
能切换当前站点。
数据库表创建成功。
```

## 4. 第 2 周：后台基础页面和内容管理

### 4.1 目标

客户能在后台维护站点信息、文章、分类、商品、商品分类。

### 4.2 任务

```text
站点设置页面
文章列表
文章编辑
文章分类
商品列表
商品编辑
商品分类
导航菜单基础管理
表单留言列表空壳
发布管理空壳
```

### 4.3 接口

```text
GET    /api/site/settings
PUT    /api/site/settings
GET    /api/articles
POST   /api/articles
GET    /api/articles/{id}
PUT    /api/articles/{id}
DELETE /api/articles/{id}
GET    /api/categories
POST   /api/categories
PUT    /api/categories/{id}
DELETE /api/categories/{id}
GET    /api/products
POST   /api/products
GET    /api/products/{id}
PUT    /api/products/{id}
DELETE /api/products/{id}
GET    /api/product-categories
POST   /api/product-categories
```

### 4.4 表单字段

文章：

```text
标题
Slug
分类
标签
封面
摘要
正文
SEO 标题
SEO 关键词
SEO 描述
状态
发布时间
```

商品：

```text
商品名称
Slug
SKU
分类
封面
相册
摘要
详情
价格
市场价
库存
SEO 信息
询盘按钮设置
```

### 4.5 验收

```text
能保存站点基础信息。
能创建文章分类。
能发布文章草稿。
能创建商品分类。
能发布商品草稿。
列表支持搜索和状态筛选。
```

## 5. 第 3 周：模板引擎和静态生成器

### 5.1 目标

跑通“数据库内容 + 模板 = 静态 HTML”。

### 5.2 任务

```text
实现模板文件读取
实现变量替换
实现 HTML 转义
实现富文本输出
实现 if 判断
实现 each 循环
实现 include
实现 asset 路径
实现 seo_meta
实现首页生成
实现文章页生成
实现文章列表页生成
实现商品页生成
实现商品列表页生成
实现 sitemap.xml
实现 robots.txt
实现 search.json
```

### 5.3 第一套模板

创建：

```text
templates/business-clean/
```

包含：

```text
首页
普通页面
文章列表
文章详情
商品列表
商品详情
搜索页
404 页
```

### 5.4 生成命令

第一版可先用命令验证：

```text
php worker/GenerateSite.php --site=10001
```

### 5.5 验收

```text
生成 public/index.html。
生成 public/news/index.html。
生成 public/news/{slug}.html。
生成 public/products/index.html。
生成 public/products/{slug}.html。
生成 sitemap.xml。
生成 search.json。
直接打开 HTML 可以正常浏览。
```

## 6. 第 4 周：AI 内容生成和媒体库

### 6.1 目标

客户能通过 AI 生成文章、商品描述、首页文案和图片。

### 6.2 任务

```text
AI 服务配置页面
AI 助手页面
AI 任务表
AI 任务创建接口
AI 任务执行器
AI 文章生成
AI 商品描述生成
AI 首页模块文案生成
AI SEO 信息生成
AI 图片生成接口预留
媒体库上传
媒体库选择器
```

### 6.3 接口

```text
POST   /api/ai/chat
POST   /api/ai/articles/generate
POST   /api/ai/products/describe
POST   /api/ai/page/fill
POST   /api/ai/images/generate
GET    /api/ai/tasks
GET    /api/ai/tasks/{id}
POST   /api/ai/tasks/{id}/confirm
GET    /api/media
POST   /api/media/upload
```

### 6.4 AI 默认指令

```text
生成 5 篇行业文章
根据商品生成详情描述
生成首页 Banner 文案
生成企业介绍
生成 SEO 标题和描述
生成文章封面图
```

### 6.5 验收

```text
配置 AI API 后能测试连接。
输入“生成 3 篇行业文章”后创建 AI 任务。
AI 返回 3 篇文章草稿。
客户确认后写入 articles 表。
文章可以进入静态生成流程。
```

## 7. 第 5 周：商品展示、询盘和发布版本

### 7.1 目标

把独立站第一版做成“商品展示 + 询盘”。

### 7.2 任务

```text
商品详情页完善
商品分类页完善
询盘按钮
联系表单
表单提交 API
表单留言后台
发布版本目录
发布记录
版本回滚
构建日志
```

### 7.3 接口

```text
POST   /api/forms/submit
GET    /api/forms/submissions
PUT    /api/forms/submissions/{id}
POST   /api/site/publish
GET    /api/site/publish-versions
POST   /api/site/rollback
GET    /api/site/publish-log
```

### 7.4 验收

```text
商品详情页显示询盘按钮。
访客提交询盘后写入 form_submissions。
后台能查看留言。
每次发布生成一个版本。
能回滚到上一版本。
```

## 8. 第 6 周：宝塔部署、URL 转模板、采集中心

### 8.1 目标

把 MVP 从本地静态生成扩展到真实部署和 AI 模板草稿能力。

### 8.2 宝塔部署任务

```text
部署节点页面
宝塔 API 配置
测试连接
创建站点接口
绑定域名接口
同步静态文件
部署日志
```

验收：

```text
后台配置宝塔节点。
点击发布后同步到指定站点目录。
域名能访问生成网站。
```

### 8.3 目标 URL 转模板草稿

第一版任务：

```text
输入 URL
抓取 HTML
保存截图
AI 识别模块结构
生成 template.json
生成 pages/index.html
生成 partials/header.html
生成 partials/footer.html
生成预览
```

验收：

```text
输入一个公开官网地址。
生成一个可启用的模板草稿。
可用该模板生成首页。
```

### 8.4 采集中心雏形

第一版任务：

```text
采集源管理
RSS 采集
指定 URL 采集
手动粘贴改写
AI 改写成文章草稿
发布文章
```

验收：

```text
配置一个 RSS。
采集到文章标题。
AI 改写成草稿。
确认后发布静态页。
```

## 9. 关键里程碑

### 9.1 里程碑 A：可登录后台

```text
登录成功
角色区分
站点切换
基础菜单
```

### 9.2 里程碑 B：可管理内容

```text
文章 CRUD
商品 CRUD
分类 CRUD
站点设置
```

### 9.3 里程碑 C：可生成静态站

```text
首页
文章页
商品页
sitemap
search.json
```

### 9.4 里程碑 D：AI 可用

```text
AI 生成文章
AI 生成商品描述
AI 生成 SEO 信息
AI 结果可确认入库
```

### 9.5 里程碑 E：可发布和回滚

```text
发布版本
构建日志
public 切换
版本回滚
```

### 9.6 里程碑 F：可部署到服务器

```text
宝塔连接
同步文件
域名访问
```

## 10. 每日开发节奏建议

```text
上午：实现一个核心功能
下午：接后台页面和接口
晚上：补文档、修 bug、做一次完整演示
```

每天结束前必须保证：

```text
代码能运行
数据库 migration 可执行
当天功能有最小演示
不留下半截不可启动状态
```

## 11. 开发优先级规则

优先级从高到低：

```text
主链路能跑通
数据结构稳定
生成 HTML 正确
发布流程安全
后台体验顺畅
AI 能力可用
视觉细节优化
高级功能扩展
```

遇到取舍时：

```text
先做能发布网站。
再做 AI 生成内容。
再做漂亮交互。
最后做复杂自动化。
```

## 12. 第一版可以接受的简化

```text
AI 任务可以先轮询，不用实时推送。
发布可以先本机目录复制，不用远程部署。
模板中心可以先固定三套模板，不做市场。
媒体库可以先只支持图片。
页面搭建可以先上移下移，不做拖拽。
采集可以先只支持 RSS 和单 URL。
商品可以先只做询盘，不做支付。
```

## 13. 第一版不能妥协的点

```text
模板不能执行 PHP。
生成过程不能直接覆盖线上 public。
每次发布必须有版本。
AI 生成内容必须可审核。
客户不能访问其他客户站点。
API Key 必须加密保存。
上传文件必须限制类型。
模板路径必须防止跨目录读取。
```

## 14. 演示脚本

MVP 完成后的演示流程：

```text
1. 登录平台后台。
2. 创建客户“楚云数航”。
3. 创建站点“楚云数航官网”。
4. 进入客户后台。
5. 设置 Logo、联系方式、SEO。
6. 选择 business-clean 模板。
7. 用 AI 生成 3 篇低空经济相关文章。
8. 新增 3 个无人机产品。
9. 生成静态网站。
10. 打开首页、文章页、商品页。
11. 提交一次询盘。
12. 后台查看询盘。
13. 修改一篇文章重新发布。
14. 回滚上一版本。
```

这条演示跑通，化简第一阶段就成立了。

