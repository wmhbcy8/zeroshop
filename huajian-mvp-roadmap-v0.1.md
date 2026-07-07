# 化简 MVP 路线图 v0.1

## 1. MVP 总目标

第一版不追求完整商城、不追求复杂插件市场、不追求一次支持几千站。

第一版只验证一件事：

> 客户可以选择模板，通过后台和 AI 填充内容，生成静态网站，并一键发布到服务器。

MVP 成功标准：

- 能创建站点。
- 能选择主题模板。
- 能配置站点基础信息。
- 能发布页面、文章、商品。
- 能用 AI 生成文章和模块文案。
- 能生成静态 HTML。
- 能预览整站。
- 能发布到宝塔站点目录。
- 能回滚上一个版本。

## 2. 第一版角色

### 2.1 平台管理员

负责：

- 管理客户。
- 管理站点。
- 管理模板。
- 管理 AI 配置。
- 管理部署节点。
- 查看发布任务。

### 2.2 客户管理员

负责：

- 设置自己的站点信息。
- 选择模板。
- 编辑导航菜单。
- 发布文章。
- 发布商品。
- 使用 AI 生成内容。
- 预览和发布网站。

### 2.3 访客

访问生成后的静态网站。

可进行：

- 浏览首页。
- 浏览文章。
- 浏览商品。
- 提交联系表单。
- 发起询盘。

第一版可以暂不做会员登录和完整购物车。

## 3. MVP 分期

## 3.1 MVP 0：本地静态生成验证

目标：

在本地跑通“数据 + 模板 = 静态 HTML”。

功能：

- 固定一套演示模板。
- 固定一组站点数据。
- 固定几篇文章和几个商品。
- 生成首页、文章页、商品页、分类页。
- 生成 sitemap.xml、robots.txt、search.json。

验收：

```text
打开 public/index.html 可以看到完整首页。
打开 public/news/demo.html 可以看到文章页。
打开 public/products/demo.html 可以看到商品页。
页面不依赖 PHP 运行。
```

## 3.2 MVP 1：单站点后台

目标：

做出一个客户可以管理单个网站的后台。

后台菜单：

```text
概览
站点设置
模板中心
页面管理
文章管理
文章分类
商品管理
商品分类
媒体库
导航菜单
AI 助手
发布管理
表单留言
```

核心功能：

- 站点名称、Logo、联系方式、SEO 信息设置。
- 模板选择。
- 首页模块配置。
- 页面新增、编辑、发布。
- 文章新增、编辑、发布。
- 商品新增、编辑、发布。
- 图片上传。
- 导航菜单配置。
- 一键生成静态站点。
- 本地预览。

验收：

```text
客户能从后台发布一篇文章。
系统能生成文章 HTML。
首页文章列表自动更新。
sitemap.xml 自动更新。
```

## 3.3 MVP 2：AI 内容助手

目标：

让客户用自然语言生成内容。

后台菜单：

```text
AI 助手
AI 任务记录
AI 配置
```

第一批 AI 指令：

```text
帮我生成 10 篇行业文章。
帮我根据这些商品生成文章。
帮我生成首页 Banner 文案。
帮我生成企业介绍。
帮我生成商品详情描述。
帮我生成文章封面图。
帮我检查全站 SEO。
```

AI 输出必须落到结构化动作：

```json
{
  "actions": [
    {
      "type": "create_article",
      "title": "农业无人机如何提升喷洒效率",
      "status": "draft"
    },
    {
      "type": "generate_image",
      "target": "article_cover"
    },
    {
      "type": "publish_static",
      "scope": "articles"
    }
  ],
  "requires_confirmation": true
}
```

验收：

```text
客户输入一句话。
系统生成文章草稿。
客户确认后发布。
文章静态页生成成功。
```

## 3.4 MVP 3：宝塔部署

目标：

把生成的网站发布到真实服务器目录。

后台菜单：

```text
部署节点
域名管理
发布管理
备份恢复
```

功能：

- 配置宝塔 API。
- 创建网站。
- 绑定域名。
- 申请 SSL。
- 同步静态文件。
- 发布前生成版本。
- 发布失败可回滚。

验收：

```text
点击发布。
宝塔站点目录出现静态文件。
域名可以访问首页。
历史版本可以回滚。
```

## 3.5 MVP 4：目标网站转模板草稿

目标：

输入目标 URL，生成一套化简标准模板草稿。

功能：

- 输入目标网站 URL。
- 抓取首页 HTML。
- 截图。
- AI 识别模块结构。
- 转换成化简模块配置。
- 生成模板目录。
- 用占位品牌替换原站品牌。
- 生成预览。

验收：

```text
输入一个公开官网 URL。
系统生成一个模板草稿。
模板包含 header、hero、content、footer。
可以被站点选择并生成首页。
```

## 3.6 MVP 5：商品询盘型独立站

目标：

先不做完整支付，把独立站商城先做成产品展示 + 询盘。

功能：

- 商品列表。
- 商品详情。
- 商品分类。
- 询盘按钮。
- 联系表单。
- WhatsApp / 邮箱 / 微信客服按钮。
- 表单留言后台。

验收：

```text
客户发布商品后生成商品详情页。
访客可以提交询盘。
后台可以看到询盘记录。
```

## 4. 第一版后台菜单设计

### 4.1 平台后台

```text
仪表盘
客户管理
站点管理
模板管理
模块管理
AI 服务配置
部署节点
发布任务
操作日志
系统设置
```

### 4.2 客户后台

```text
概览
AI 助手
站点设置
页面搭建
模板中心
内容管理
  文章列表
  文章分类
  标签管理
商品管理
  商品列表
  商品分类
媒体库
导航菜单
表单留言
发布管理
  预览网站
  生成静态
  发布上线
  发布记录
  回滚版本
SEO 工具
  sitemap
  robots
  搜索索引
采集中心
  采集源
  采集记录
```

## 5. 第一版接口

接口风格使用普通 REST API。

### 5.1 站点设置

```text
GET    /api/site/settings
PUT    /api/site/settings
GET    /api/site/preview
POST   /api/site/publish
POST   /api/site/rollback
```

### 5.2 页面

```text
GET    /api/pages
POST   /api/pages
GET    /api/pages/{id}
PUT    /api/pages/{id}
DELETE /api/pages/{id}
POST   /api/pages/{id}/publish
```

### 5.3 文章

```text
GET    /api/articles
POST   /api/articles
GET    /api/articles/{id}
PUT    /api/articles/{id}
DELETE /api/articles/{id}
POST   /api/articles/{id}/publish
```

### 5.4 商品

```text
GET    /api/products
POST   /api/products
GET    /api/products/{id}
PUT    /api/products/{id}
DELETE /api/products/{id}
POST   /api/products/{id}/publish
```

### 5.5 模板

```text
GET    /api/templates
POST   /api/templates/install
POST   /api/templates/{key}/activate
GET    /api/templates/{key}/preview
```

### 5.6 模块

```text
GET    /api/modules
GET    /api/modules/{key}
PUT    /api/pages/{id}/modules
POST   /api/pages/{id}/modules/preview
```

### 5.7 AI

```text
POST   /api/ai/chat
POST   /api/ai/articles/generate
POST   /api/ai/images/generate
POST   /api/ai/products/describe
POST   /api/ai/page/fill
POST   /api/ai/template/clone
GET    /api/ai/tasks
GET    /api/ai/tasks/{id}
POST   /api/ai/tasks/{id}/confirm
```

### 5.8 采集

```text
GET    /api/collector/sources
POST   /api/collector/sources
PUT    /api/collector/sources/{id}
DELETE /api/collector/sources/{id}
POST   /api/collector/sources/{id}/run
GET    /api/collector/items
POST   /api/collector/items/{id}/rewrite
POST   /api/collector/items/{id}/publish
```

### 5.9 表单

前台提交：

```text
POST   /api/forms/submit
```

后台查看：

```text
GET    /api/forms/submissions
PUT    /api/forms/submissions/{id}
```

## 6. 静态生成流程

### 6.1 单篇文章发布

```text
保存文章
生成文章 slug
生成 SEO 信息
渲染 article.html
写入 /news/{slug}.html
更新 /news/index.html
更新分类页
更新 sitemap.xml
更新 rss.xml
更新 search.json
记录发布日志
```

### 6.2 商品发布

```text
保存商品
生成商品 slug
渲染 product.html
写入 /products/{slug}.html
更新 /products/index.html
更新商品分类页
更新 sitemap.xml
更新 search.json
记录发布日志
```

### 6.3 全站发布

```text
创建临时构建目录
复制模板静态资源
生成首页
生成全部页面
生成全部文章
生成全部文章列表和分类
生成全部商品
生成全部商品列表和分类
生成 sitemap.xml
生成 robots.txt
生成 rss.xml
生成 search.json
生成发布版本
切换 public 目录
记录发布日志
```

## 7. 文件发布策略

### 7.1 本地构建目录

```text
storage/build/site_10001/{build_id}/
```

### 7.2 发布版本目录

```text
storage/publish_versions/site_10001/{version_no}/
```

### 7.3 当前访问目录

```text
sites/site_10001/public/
```

发布时不要直接在 `public` 里边生成边覆盖。

正确流程：

```text
先生成 build
校验 build
复制 build 到 publish_versions
同步 publish_versions 到 public
```

这样失败时不会破坏线上站点。

## 8. 模板中心第一版

第一版模板不要多，先做 3 套。

```text
business-clean       企业官网
blog-knowledge       博客知识库
product-showcase     产品展示独立站
```

每套模板必须包含：

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

## 9. 页面搭建第一版

第一版先不做复杂拖拽。

采用：

```text
模块列表 + 上移下移 + 字段表单
```

页面配置示例：

```json
[
  {
    "module": "hero",
    "settings": {
      "title": "智能建站，从化简开始",
      "subtitle": "静态化、高性能、适合 SEO 的 SaaS 建站系统"
    }
  },
  {
    "module": "article-list",
    "settings": {
      "title": "行业资讯",
      "count": 6
    }
  }
]
```

这样比可视化拖拽更快落地，也更稳。

## 10. AI 助手第一版界面

AI 助手页面分三块：

```text
左侧：快捷指令
中间：对话窗口
右侧：执行计划和预览
```

快捷指令：

```text
生成行业文章
生成商品描述
生成首页文案
生成 SEO 信息
生成文章配图
根据网址生成模板
采集行业新闻
```

AI 回复必须包含：

```text
自然语言说明
可执行计划
需要用户确认的按钮
```

## 11. 采集中心第一版

第一版只支持：

```text
RSS 采集
指定 URL 采集
手动粘贴文章改写
```

暂不支持：

```text
全网搜索采集
复杂反爬网站
登录后内容
无限深度爬取
```

采集后状态：

```text
待处理
已改写
待审核
已发布
已忽略
```

## 12. 目标网站转模板第一版

第一版不要追求 100% 还原。

目标是：

```text
识别结构
重建模板
生成可编辑模块
替换成客户占位内容
```

流程：

```text
输入 URL
抓取首页
生成截图
AI 分析模块
生成模块配置
生成模板草稿
客户预览
客户保存为模板
```

第一版只处理首页，后续再处理内页。

## 13. 安全与权限

第一版必须做：

- 登录鉴权。
- 客户只能访问自己的站点。
- 上传文件类型限制。
- 模板路径限制。
- API Key 加密存储。
- 发布任务日志。
- AI 任务确认机制。
- 表单提交频率限制。

第一版暂不做：

- 复杂角色权限。
- 多级审批。
- 企业组织架构。

## 14. 最小验收清单

### 14.1 内容站验收

```text
创建站点
设置站点名称和 Logo
选择模板
创建文章分类
发布 3 篇文章
生成首页、文章页、分类页
生成 sitemap.xml
本地预览正常
```

### 14.2 AI 验收

```text
配置 AI API
输入“生成 5 篇行业文章”
生成 5 篇草稿
确认发布其中 1 篇
静态页生成成功
```

### 14.3 商品询盘验收

```text
创建商品分类
发布 3 个商品
生成商品列表和详情页
前台提交询盘
后台看到询盘记录
```

### 14.4 发布验收

```text
生成发布版本
同步到 public
访问首页正常
修改文章后重新发布
可以回滚上一版
```

## 15. 建议开发顺序

```text
1. 数据库和基础后台
2. 模板引擎
3. 静态生成器
4. 页面、文章、分类
5. 媒体库
6. sitemap、robots、search.json
7. 模板中心
8. 模块配置
9. AI 文章生成
10. 商品展示和询盘
11. 宝塔发布
12. 目标网站转模板草稿
13. 新闻采集
```

## 16. 第一版不碰的复杂点

为了快速成型，第一版先不做：

- 完整购物车。
- 在线支付。
- 多语言自动同步。
- 复杂会员系统。
- 可视化自由拖拽。
- 插件市场交易。
- 多服务器自动调度。
- 几千站并发发布。

这些都留到第二阶段。

