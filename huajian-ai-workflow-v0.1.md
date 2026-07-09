# 化简 AI 内容与模板工作流 v0.1

## 1. AI 在化简里的定位

化简不是让客户面对一堆后台表单，而是让客户像聊天一样搭建网站。

AI 的作用：

- 帮客户生成文章、图片、视频。
- 帮客户填充首页模块、产品介绍、企业介绍。
- 帮客户改模板颜色、布局、文案。
- 帮客户从目标网站学习结构，转换成化简标准模板。
- 帮客户采集行业资讯，并改写成适合自己站点的内容。
- 帮客户做 SEO 标题、关键词、描述和内链。

核心体验：

```text
客户说需求
AI 理解站点类型和业务
AI 生成内容和页面配置
系统生成静态 HTML
客户预览
客户确认发布
```

## 2. AI 服务配置

平台后台配置 AI 服务商。

支持能力：

```text
文本生成
图片生成
图片编辑
视频生成
语音生成
网页解析
结构化抽取
翻译
SEO 改写
```

后台配置项：

```text
服务商名称
API Base URL
API Key
文本模型
图片模型
视频模型
默认温度
最大输出长度
每日额度
单站点额度
失败重试次数
```

## 3. AI 对话式建站

### 3.1 首页搭建

客户输入：

```text
帮我做一个无人机外贸独立站，主要卖农业植保无人机，风格要科技感，重点突出出口、售后、工厂实力。
```

AI 输出页面方案：

```json
{
  "page": "home",
  "modules": [
    {
      "module": "hero",
      "title": "Agricultural Drone Solutions for Global Farms",
      "subtitle": "Factory-direct UAV systems for spraying, mapping, and precision agriculture.",
      "button_text": "View Products"
    },
    {
      "module": "advantage-list",
      "title": "Why Choose Us",
      "items": ["Factory Direct", "OEM Support", "Global Shipping", "After-sales Service"]
    },
    {
      "module": "product-grid",
      "title": "Featured Agricultural Drones",
      "count": 6
    },
    {
      "module": "article-list",
      "title": "Drone Farming Insights",
      "count": 6
    },
    {
      "module": "contact-form",
      "title": "Request a Quote"
    }
  ]
}
```

系统把这个方案保存到 `pages.module_config`，然后生成静态首页。

### 3.2 内容模块生成文章

客户输入：

```text
请将内容模块根据我们的自主品牌商品，写十篇文章，主题围绕农业无人机出口、喷洒效率、售后服务、配件维护。
```

AI 任务拆解：

```text
读取站点信息
读取商品列表
读取目标关键词
生成 10 个文章标题
生成文章大纲
生成正文
生成 SEO 标题、关键词、描述
生成配图提示词
调用图片生成接口
保存文章草稿
生成文章静态页
更新栏目页和 sitemap
```

文章生成结果：

```json
{
  "articles": [
    {
      "title": "How Agricultural Drones Improve Spraying Efficiency",
      "slug": "agricultural-drones-spraying-efficiency",
      "category": "industry-news",
      "seo_keywords": "agricultural drone,spraying drone,precision farming",
      "status": "draft"
    }
  ]
}
```

默认建议：

- AI 生成后先进入草稿。
- 客户确认后发布。
- 可选择自动发布，但要有频率限制。

### 3.3 多站点内容分发规则

客户中台的文章库、商品库、页面库都是“中台内容库”，不是某个单站点的私有表。每一份内容只保存一份主体数据，再通过发布范围决定同步到哪些前台站点。

统一范围：

```text
current：当前站点
all：客户名下全部启用站点
selected：指定若干站点
```

统一落库逻辑：

```text
文章 / 商品 / 页面主体数据
  -> 保存到内容库
  -> 写入 content_site_relations
  -> 发布或转草稿
  -> 只重新生成受影响的静态站
```

AI 也使用同一套规则：

```text
AI 预览：只生成临时草稿，不落库。
AI 单条保存：按当前选择范围写入文章库或商品库。
AI 批量入库：按 current / all / selected 批量写入分发关系。
AI 任务确认：可按任务原范围确认，也可按当前新范围重新确认。
```

前台读取规则：

```text
生成 site_10001 时，只读取分发给 10001 的文章、商品、页面。
生成 site_10002 时，只读取分发给 10002 的文章、商品、页面。
一篇文章可以同时属于多个站点，但 URL、模板、导航、站点设置仍由各站点独立决定。
```

这样客户可以在一个中台里统一生成内容，再选择发布到一个站、多个站或全部站点；后续需要单站差异化时，只改该内容的发布范围或复制出一份新内容即可。

## 4. AI 图片和视频

### 4.1 图片生成

适用场景：

- 文章封面图。
- 首页 Banner。
- 产品场景图。
- 案例配图。
- 社媒海报。

生成流程：

```text
读取站点行业
读取文章或商品内容
生成图片提示词
调用图片模型
保存到媒体库
压缩和生成缩略图
绑定到文章、商品或模块
重新生成静态页面
```

### 4.2 视频生成

适用场景：

- 首页短视频背景。
- 产品介绍短片。
- 文章摘要视频。
- 社媒营销视频。

第一版不建议把视频作为核心功能，只做外部接口预留：

```text
生成视频任务
等待外部视频接口返回
保存视频地址
绑定到模块
```

## 5. 新闻采集与发布

### 5.1 采集源类型

```text
RSS
网站列表页
指定文章 URL
关键词搜索结果
公众号导入
手动粘贴内容
```

### 5.2 采集流程

```text
读取采集源
抓取文章列表
去重
提取标题、正文、图片、发布时间、来源
AI 判断是否相关
AI 改写标题和正文
AI 生成 SEO 信息
保存为草稿或自动发布
生成静态页
更新 sitemap 和 search.json
```

### 5.3 采集底线

采集功能必须有边界：

- 不直接照搬原文发布。
- 不采集禁止转载的网站内容。
- 保留来源字段。
- 优先采集可授权、RSS、公共新闻、企业自身资料。
- AI 改写后仍需避免侵权和虚假信息。

### 5.4 采集后的内容形态

建议发布成：

```text
行业资讯
技术知识
产品百科
采购指南
常见问题
案例分析
```

不要只做低质量伪原创。化简要做的是“搜索引擎能收录、客户也愿意看”的内容。

## 6. 目标网站转模板

### 6.1 功能目标

客户可以输入一个目标网站地址：

```text
https://example.com
```

系统分析它的前端结构，把它转换成化简标准模板。

最终输出：

```text
template.json
pages/index.html
pages/page.html
partials/header.html
partials/footer.html
modules/hero.html
modules/product-grid.html
assets/css/style.css
assets/js/main.js
preview.png
```

### 6.2 重要边界

这个功能应该叫“结构学习与模板重建”，不要叫“整站盗用”。

允许：

- 学习布局结构。
- 提取页面区块类型。
- 识别导航、Banner、产品列表、文章列表、页脚。
- 重新生成相似但不照搬的 HTML/CSS。
- 用客户自己的品牌、图片、文案替换。

不建议：

- 直接复制对方商标。
- 直接复制对方图片。
- 直接复制对方文案。
- 直接复制对方完整 CSS 和 JS。
- 绕过登录、付费墙、反爬保护。

系统应默认下载结构，不默认商用别人的素材。

### 6.3 转换流程

```text
客户输入 URL
系统抓取页面 HTML
系统截图
AI 识别页面模块
抽取导航结构
抽取颜色、字体、间距、布局特征
识别可替换内容
生成化简模块配置
生成标准模板文件
替换成客户品牌占位内容
生成预览图
客户确认保存为主题模板
```

### 6.4 AI 识别模块

AI 需要把页面拆成标准模块：

```json
[
  { "type": "header-nav", "confidence": 0.98 },
  { "type": "hero", "confidence": 0.94 },
  { "type": "advantage-list", "confidence": 0.87 },
  { "type": "product-grid", "confidence": 0.91 },
  { "type": "article-list", "confidence": 0.82 },
  { "type": "footer", "confidence": 0.96 }
]
```

### 6.5 模板重建策略

优先生成标准化结构：

```html
{{ include "partials/header.html" }}

<main>
  {{ slot "home_main" }}
</main>

{{ include "partials/footer.html" }}
```

而不是保留原站混乱结构。

AI 需要把原页面改造成化简模块：

```text
原站 section.hero       -> 化简 hero 模块
原站 product cards      -> 化简 product-grid 模块
原站 blog list          -> 化简 article-list 模块
原站 footer columns     -> 化简 footer 模块
```

## 7. AI 替换内容

客户可以说：

```text
把这个模板改成我的品牌，品牌名是楚云数航，主营无人机低空经济解决方案，色调用蓝色和白色。
```

AI 执行：

```text
修改 site 设置
修改首页模块文案
替换导航菜单
生成企业介绍
生成服务项目
生成行业文章
生成图片提示词
生成或替换图片
重新生成静态页
```

## 8. AI 任务类型

```text
site_plan               站点规划
page_build              页面搭建
module_fill             模块填充
article_generate        文章生成
article_rewrite         文章改写
product_description     商品描述生成
seo_generate            SEO 信息生成
image_generate          图片生成
video_generate          视频生成
news_collect            新闻采集
template_clone          目标网站结构学习
template_convert        转换为化简模板
template_restyle        模板换色和改风格
menu_generate           菜单生成
faq_generate            FAQ 生成
```

## 9. AI 发布安全

AI 生成内容不能无条件直接上线。

建议三种模式：

```text
草稿模式      AI 生成后进入草稿，人工确认发布
半自动模式    低风险内容自动发布，高风险内容待审核
自动模式      白名单站点按频率自动发布
```

高风险内容包括：

- 医疗、金融、法律建议。
- 品牌侵权。
- 虚假功效。
- 夸大宣传。
- 采集原文相似度过高。
- 含联系方式、价格、承诺类敏感信息。

## 10. 后台交互形态

后台应该有一个 AI 助手面板。

常用指令：

```text
帮我生成 10 篇行业文章
帮我把首页改得更像外贸官网
帮我把这个模板换成科技蓝
帮我采集今天的行业新闻并改写成三篇文章
帮我把这些商品生成英文详情页
帮我根据这个网站生成一个类似结构的主题模板
帮我检查全站 SEO 缺失项
帮我生成 FAQ 和联系方式模块
```

AI 返回的不是纯聊天文本，而是可执行计划：

```json
{
  "plan": [
    { "action": "update_site_setting", "target": "site.name" },
    { "action": "create_articles", "count": 10 },
    { "action": "generate_images", "count": 10 },
    { "action": "publish_pages", "scope": "articles" }
  ],
  "requires_confirmation": true
}
```

用户点击确认后，系统执行。

## 11. 推荐 MVP

第一阶段只做 5 个 AI 能力：

```text
AI 生成文章
AI 生成文章封面图
AI 填充首页模块文案
AI 生成商品描述
AI 从目标 URL 学习结构并生成模板草稿
```

第二阶段再做：

```text
新闻采集自动发布
视频生成
批量多语言翻译
整站 SEO 自动优化
自动内链
竞品站点结构分析
```
