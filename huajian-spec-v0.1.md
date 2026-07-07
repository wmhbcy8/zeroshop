# 化简系统规格 v0.1

## 1. 产品定位

化简是一个面向站群、企业官网、博客知识库、产品展示和独立站商城的轻量 SaaS 建站系统。

核心原则：

- 后台动态管理，前台静态生成。
- 模板使用 HTML、CSS、JavaScript。
- 内容发布后生成真实 `.html` 页面。
- 商城展示静态化，交易流程 API 化。
- 模块固定规范，模板自由组合。
- 多站点统一管理，单站点独立发布、备份、恢复。

一句话定义：

> 化简用 PHP 管理内容，用 HTML 模板生成静态网站，用模块搭建页面，用 API 承载交易。

## 2. 系统边界

### 2.1 化简应该做什么

- 创建和管理多个客户站点。
- 为每个站点选择模板。
- 配置首页、栏目页、文章页、商品页。
- 发布文章、页面、商品、分类。
- 自动生成静态 HTML、站点地图、搜索索引。
- 支持绑定独立域名和平台二级域名。
- 支持宝塔面板一键部署。
- 支持站点备份、恢复、更新。
- 支持商城订单、支付、询盘、客服。

### 2.2 化简暂时不做什么

- 不做 WordPress 那种运行时主题解析。
- 不允许模板直接写业务 PHP。
- 不允许插件随意改核心表结构和核心文件。
- 不做一开始就支持所有外部源码的“万能融合”。
- 不把后台功能堆成复杂 ERP。

## 3. 技术架构

```text
管理后台
  PHP + MySQL
  管理站点、内容、商品、订单、模板、模块、部署任务

静态生成器
  PHP CLI 或 Go Worker
  读取数据库和模板，生成 HTML/CSS/JS/JSON/XML 文件

前台站点
  纯 HTML/CSS/JS
  文章页、商品页、分类页、首页、专题页全部静态化

动态 API
  PHP API
  登录、购物车、订单、支付、库存、客服、表单提交

部署层
  宝塔 API / 服务器 Agent
  创建站点、绑定域名、申请 SSL、同步文件、备份恢复
```

## 4. 目录结构

### 4.1 平台目录

```text
huajian/
  admin/
  api/
  worker/
  templates/
  modules/
  plugins/
  storage/
  sites/
```

### 4.2 单个站点生成目录

```text
sites/site_10001/public/
  index.html
  about.html
  news/
    index.html
    article-title.html
  products/
    index.html
    product-title.html
  category/
    company-news/
      index.html
  product-category/
    electronics/
      index.html
  assets/
    css/
    js/
    images/
  search.json
  sitemap.xml
  robots.txt
  rss.xml
```

## 5. 模板规范

模板是一套可安装、可预览、可生成静态页面的前端文件。

### 5.1 模板目录

```text
templates/business-clean/
  template.json
  preview.png
  pages/
    index.html
    page.html
    article.html
    article-list.html
    product.html
    product-list.html
    search.html
    404.html
  partials/
    header.html
    footer.html
    nav.html
    breadcrumb.html
  modules/
    hero.html
    article-list.html
    product-grid.html
    faq.html
    contact.html
  assets/
    css/style.css
    js/main.js
    images/
```

### 5.2 template.json

```json
{
  "name": "商务官网简洁模板",
  "key": "business-clean",
  "version": "1.0.0",
  "author": "化简",
  "type": ["company", "blog", "shop"],
  "supports": ["page", "article", "product", "seo", "form"],
  "entry": "pages/index.html",
  "preview": "preview.png"
}
```

### 5.3 模板变量

模板只允许简单变量、循环、判断和局部文件引用。

变量：

```html
{{ site.name }}
{{ site.logo }}
{{ page.title }}
{{ article.title }}
{{ article.content }}
{{ product.title }}
{{ product.price }}
```

循环：

```html
{{ each articles }}
  <article>
    <h2><a href="{{ item.url }}">{{ item.title }}</a></h2>
    <p>{{ item.summary }}</p>
  </article>
{{ /each }}
```

判断：

```html
{{ if site.logo }}
  <img src="{{ site.logo }}" alt="{{ site.name }}">
{{ else }}
  <span>{{ site.name }}</span>
{{ /if }}
```

引用局部文件：

```html
{{ include "partials/header.html" }}
{{ include "partials/footer.html" }}
```

### 5.4 模板禁区

- 禁止模板直接连接数据库。
- 禁止模板执行 PHP 业务代码。
- 禁止模板直接操作订单、支付、用户。
- 禁止模板跨站读取文件。
- 禁止模板写入站点目录。

## 6. 模块规范

模块是页面搭建的最小积木。模块由字段配置、HTML 片段、样式和可选脚本组成。

### 6.1 模块目录

```text
modules/product-grid/
  module.json
  view.html
  style.css
  script.js
```

### 6.2 module.json

```json
{
  "name": "商品网格",
  "key": "product-grid",
  "category": "shop",
  "version": "1.0.0",
  "fields": [
    { "name": "title", "label": "标题", "type": "text", "default": "推荐商品" },
    { "name": "category_id", "label": "商品分类", "type": "product_category" },
    { "name": "count", "label": "显示数量", "type": "number", "default": 8 }
  ],
  "dataSource": "products"
}
```

### 6.3 模块视图

```html
<section class="hj-product-grid">
  <h2>{{ module.title }}</h2>
  <div class="hj-grid">
    {{ each products }}
      <a class="hj-product-card" href="{{ item.url }}">
        <img src="{{ item.cover }}" alt="{{ item.title }}">
        <h3>{{ item.title }}</h3>
        <p>{{ item.price }}</p>
      </a>
    {{ /each }}
  </div>
</section>
```

### 6.4 模块字段类型

```text
text              单行文本
textarea          多行文本
richtext          富文本
image             图片
gallery           图片组
number            数字
switch            开关
select            下拉选择
color             颜色
url               链接
article_category  文章分类
product_category  商品分类
article_list      文章列表
product_list      商品列表
menu              菜单
form              表单
```

## 7. 第一批核心模块

### 7.1 基础模块

```text
header-nav        顶部导航
footer            底部信息
breadcrumb        面包屑
search-box        搜索框
language-switch   语言切换
friend-links      友情链接
```

### 7.2 官网模块

```text
hero              首屏横幅
about             企业介绍
service-list      服务项目
advantage-list    优势卖点
case-list         案例展示
team-list         团队介绍
contact-form      联系表单
map               地图位置
```

### 7.3 内容模块

```text
article-list      文章列表
article-detail    文章详情
category-list     分类列表
tag-list          标签列表
related-articles  相关文章
knowledge-tree    知识库目录
faq               常见问题
```

### 7.4 商城模块

```text
product-category  商品分类
product-grid      商品网格
product-detail    商品详情
product-recommend 商品推荐
cart-button       购物车按钮
buy-button        立即购买按钮
inquiry-button    询盘按钮
order-query       订单查询入口
```

### 7.5 营销模块

```text
popup-form        弹窗表单
floating-service  浮动客服
whatsapp-button   WhatsApp 按钮
wechat-button     微信按钮
email-button      邮件按钮
promo-banner      优惠横幅
countdown         倒计时
testimonial-list  客户评价
```

## 8. 内容模型

第一版只保留必要模型。

```text
Site              站点
Template          模板
Module            模块
Page              页面
Article           文章
Category          文章分类
Tag               标签
Product           商品
ProductCategory   商品分类
Media             媒体
Menu              菜单
Customer          客户
Order             订单
Payment           支付
DeployTask        部署任务
PublishVersion    发布版本
```

## 9. 静态生成规则

### 9.1 文章发布

发布文章后生成：

```text
/news/article-slug.html
/news/index.html
/category/category-slug/index.html
/tag/tag-slug/index.html
/sitemap.xml
/rss.xml
/search.json
```

### 9.2 商品发布

发布商品后生成：

```text
/products/product-slug.html
/products/index.html
/product-category/category-slug/index.html
/sitemap.xml
/search.json
```

### 9.3 页面发布

发布普通页面后生成：

```text
/about.html
/contact.html
/custom-page.html
/sitemap.xml
```

### 9.4 全站发布

全站发布生成：

```text
首页
全部页面
全部文章详情页
全部文章分类页
全部商品详情页
全部商品分类页
搜索索引
站点地图
RSS
robots.txt
静态资源
```

## 10. 商城静态化边界

可以静态化：

- 首页
- 商品列表
- 商品详情
- 商品分类
- 品牌页
- 活动页
- 帮助中心
- SEO 聚合页

必须动态化：

- 登录注册
- 购物车
- 实时库存
- 订单创建
- 支付发起
- 支付回调
- 订单状态
- 售后
- 客服会话

推荐模式：

```text
静态 HTML 负责展示和 SEO。
JavaScript 调用 API 完成交易。
PHP API 处理订单、支付和用户。
```

## 11. 发布和回滚

每次发布都生成一个版本。

```text
storage/publish_versions/site_10001/
  20260707_001/
  20260707_002/
  20260707_003/
```

发布流程：

```text
读取内容数据
读取模板文件
生成临时静态目录
校验必要文件
生成发布版本
同步到站点 public 目录
记录发布日志
```

回滚流程：

```text
选择历史版本
复制版本文件到 public
刷新缓存
记录回滚日志
```

## 12. 开发优先级

### MVP 1：内容静态站

- 站点管理
- 模板安装
- 页面管理
- 文章管理
- 分类管理
- 媒体库
- 静态生成
- sitemap
- robots
- 宝塔发布

### MVP 2：模块化搭建

- 模块注册
- 首页模块配置
- 页面模块配置
- 模块字段表单
- 模块预览
- 模板切换

### MVP 3：商品和询盘

- 商品管理
- 商品分类
- 商品静态页
- 询盘表单
- 客服按钮
- 邮件通知

### MVP 4：订单和支付

- 购物车
- 下单 API
- 支付插件
- 订单后台
- 支付回调
- 订单状态查询

### MVP 5：多站点 SaaS

- 客户管理
- 套餐管理
- 分站创建
- 二级域名
- 独立域名绑定
- SSL
- 备份恢复
- 多服务器节点

