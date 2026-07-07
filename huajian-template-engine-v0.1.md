# 化简模板引擎语法 v0.1

## 1. 设计目标

化简模板语法要服务三类人：

- 会 HTML/CSS/JavaScript 的站长。
- 会复制和修改网页结构的前端编辑者。
- 能通过 AI 自动生成、改写、套用模板的系统。

所以语法必须简单、稳定、可读，不追求复杂编程能力。

核心原则：

- 模板是 HTML 文件，不是 PHP 程序。
- 模板只负责展示，不直接处理业务。
- 数据由系统注入，模板只读取变量。
- 复杂逻辑放在后台、模块配置和生成器里。
- 生成结果必须是普通静态 HTML。

## 2. 基础变量

变量使用双大括号：

```html
{{ site.name }}
{{ page.title }}
{{ article.title }}
{{ product.price }}
```

生成器会把变量替换成真实内容。

示例：

```html
<title>{{ page.seo_title }} - {{ site.name }}</title>
<h1>{{ article.title }}</h1>
<div class="content">{{ article.content }}</div>
```

## 3. 数据对象

### 3.1 site

站点基础信息。

```text
site.id
site.name
site.logo
site.domain
site.slogan
site.description
site.keywords
site.language
site.icp
site.phone
site.email
site.address
site.whatsapp
site.wechat_qrcode
```

### 3.2 page

当前页面信息。

```text
page.id
page.title
page.slug
page.url
page.content
page.seo_title
page.seo_keywords
page.seo_description
page.created_at
page.updated_at
```

### 3.3 article

文章详情页信息。

```text
article.id
article.title
article.slug
article.url
article.cover
article.summary
article.content
article.category
article.tags
article.author
article.published_at
article.seo_title
article.seo_keywords
article.seo_description
```

### 3.4 product

商品详情页信息。

```text
product.id
product.title
product.slug
product.url
product.cover
product.gallery
product.summary
product.description
product.price
product.market_price
product.sku
product.stock
product.category
product.brand
product.attributes
product.seo_title
product.seo_keywords
product.seo_description
```

### 3.5 module

当前模块配置。

```text
module.title
module.subtitle
module.image
module.count
module.link
module.button_text
module.custom
```

## 4. HTML 转义规则

默认变量会进行 HTML 转义，避免破坏页面结构。

```html
{{ article.title }}
```

富文本内容允许输出 HTML：

```html
{{{ article.content }}}
{{{ product.description }}}
```

规则：

- `{{ value }}` 输出安全文本。
- `{{{ value }}}` 输出可信 HTML。
- 后台富文本必须先经过安全过滤。
- 普通客户不能直接写 `<script>`。

## 5. 条件判断

```html
{{ if site.logo }}
  <img src="{{ site.logo }}" alt="{{ site.name }}">
{{ else }}
  <span>{{ site.name }}</span>
{{ /if }}
```

支持简单比较：

```html
{{ if product.stock > 0 }}
  <button data-product-id="{{ product.id }}">加入购物车</button>
{{ else }}
  <span>暂时缺货</span>
{{ /if }}
```

第一版只支持：

```text
存在判断
等于 ==
不等于 !=
大于 >
小于 <
大于等于 >=
小于等于 <=
```

## 6. 循环

```html
{{ each articles }}
  <article>
    <h2><a href="{{ item.url }}">{{ item.title }}</a></h2>
    <p>{{ item.summary }}</p>
  </article>
{{ /each }}
```

循环内默认对象为 `item`。

支持序号：

```html
{{ each products }}
  <div class="product-card product-{{ index }}">
    <img src="{{ item.cover }}" alt="{{ item.title }}">
    <h3>{{ item.title }}</h3>
  </div>
{{ /each }}
```

可循环的数据：

```text
menus
articles
categories
tags
products
product_categories
related_articles
related_products
modules
breadcrumbs
images
```

## 7. 引入局部模板

```html
{{ include "partials/header.html" }}
{{ include "partials/nav.html" }}
{{ include "partials/footer.html" }}
```

限制：

- 只能引用当前模板目录内文件。
- 不允许 `../` 跳出模板目录。
- 不允许引用 PHP、可执行脚本、隐藏文件。

## 8. 模块插槽

页面可以声明一个模块区域：

```html
<main>
  {{ slot "home_main" }}
</main>
```

后台会读取这个页面绑定的模块配置，把模块依次渲染到该插槽。

示例配置：

```json
[
  { "module": "hero", "title": "智能外贸建站" },
  { "module": "product-grid", "title": "热销产品", "count": 8 },
  { "module": "article-list", "title": "行业资讯", "count": 6 }
]
```

## 9. 模块调用

模板可以直接调用固定模块：

```html
{{ module "hero" }}
{{ module "product-grid" count=8 title="推荐商品" }}
```

第一版建议主要使用后台配置的 `slot`，减少模板里的复杂参数。

## 10. 静态资源路径

模板内使用：

```html
<link rel="stylesheet" href="{{ asset 'css/style.css' }}">
<script src="{{ asset 'js/main.js' }}"></script>
<img src="{{ asset 'images/banner.jpg' }}" alt="">
```

生成后变成：

```html
<link rel="stylesheet" href="/assets/css/style.css">
```

## 11. URL 生成

```html
{{ url "home" }}
{{ url "articles" }}
{{ url "products" }}
{{ url "page" slug="about" }}
```

常用输出：

```text
首页 /
文章列表 /news/
商品列表 /products/
普通页面 /about.html
```

## 12. SEO 标签

模板可以直接使用 SEO 组件：

```html
{{ seo_meta }}
```

生成：

```html
<title>页面标题</title>
<meta name="description" content="页面描述">
<meta name="keywords" content="关键词">
<link rel="canonical" href="https://example.com/current.html">
```

也可以手写：

```html
<title>{{ page.seo_title }}</title>
<meta name="description" content="{{ page.seo_description }}">
```

## 13. 商城动态按钮

商品页静态生成，但按钮走 API。

```html
<button
  class="hj-add-cart"
  data-product-id="{{ product.id }}"
  data-api="/api/cart/add">
  加入购物车
</button>

<button
  class="hj-buy-now"
  data-product-id="{{ product.id }}"
  data-api="/api/order/checkout">
  立即购买
</button>
```

前端 JavaScript 负责调用接口。

## 14. 表单模块

表单 HTML 静态生成，提交走 API。

```html
<form class="hj-form" data-api="/api/forms/submit">
  <input type="hidden" name="form_key" value="contact">
  <input type="text" name="name" placeholder="姓名">
  <input type="tel" name="phone" placeholder="电话">
  <textarea name="message" placeholder="留言"></textarea>
  <button type="submit">提交</button>
</form>
```

## 15. AI 可改写区域

为了让 AI 更容易改模板，模板中可以标记可编辑区域：

```html
<!-- hj:editable name="hero_title" type="text" -->
<h1>{{ site.slogan }}</h1>
<!-- /hj:editable -->

<!-- hj:module key="product-grid" -->
{{ module "product-grid" }}
<!-- /hj:module -->
```

AI 改站时优先修改这些区域，而不是盲目改整份 HTML。

## 16. 禁止语法

第一版不支持：

- 模板内写 SQL。
- 模板内写 PHP。
- 模板内执行系统命令。
- 模板内远程加载未知脚本。
- 模板内跨站读取文件。
- 模板内复杂函数和递归。

化简模板要像 HTML 一样容易读，而不是变成另一门复杂语言。

