<template>
  <div v-if="!token" class="login-page">
    <el-card class="login-card" shadow="always">
      <div class="login-brand">
        <span>简</span>
        <div>
          <strong>化简新版后台</strong>
          <small>Art Design Pro UI · API 保持不变</small>
        </div>
      </div>
      <el-form :model="loginForm" label-position="top" @submit.prevent="login">
        <el-form-item label="账号"><el-input v-model="loginForm.username" /></el-form-item>
        <el-form-item label="密码"><el-input v-model="loginForm.password" type="password" show-password /></el-form-item>
        <el-button type="primary" size="large" :loading="loading" native-type="submit" class="full-btn">登录后台</el-button>
      </el-form>
    </el-card>
  </div>

  <el-container v-else class="app-shell">
    <el-aside width="248px" class="side">
      <div class="brand">
        <span>简</span>
        <div>
          <strong>化简</strong>
          <small>ZeroShop Admin</small>
        </div>
      </div>
      <el-menu :default-active="view" class="menu" @select="setView">
        <el-menu-item v-for="item in navItems" :key="item.key" :index="item.key">
          <el-icon><component :is="item.icon" /></el-icon>
          <span>{{ item.label }}</span>
          <el-badge v-if="item.key === 'service' && servicePending" :value="servicePending" class="menu-badge" />
        </el-menu-item>
      </el-menu>
    </el-aside>

    <el-container>
      <el-header class="topbar">
        <div>
          <h1>{{ currentNav?.label }}</h1>
          <p>{{ currentNav?.hint }}</p>
          <div class="work-tabs">
            <el-tag effect="plain">{{ currentNav?.label || '工作台' }}</el-tag>
            <el-button link type="primary" @click="refreshCurrentView">刷新当前页</el-button>
            <el-button link @click="openLegacyAdmin">旧版后台</el-button>
          </div>
        </div>
        <div class="top-actions">
          <el-button @click="previewSite">预览站点</el-button>
          <el-button type="primary" :loading="generating" @click="generateSite">生成静态站</el-button>
          <el-button @click="logout">退出</el-button>
        </div>
      </el-header>

      <el-main class="main">
        <section v-if="view === 'dashboard'">
          <div class="metric-grid">
            <MetricCard title="今日访客" :value="metrics.today_visitors || 0" :note="`浏览 ${metrics.today_views || 0} 次`" icon="User" />
            <MetricCard title="访问深度" :value="metrics.visit_depth || 0" note="人均浏览页数" icon="TrendCharts" />
            <MetricCard title="今日支付金额" :value="metrics.today_paid_amount || '0.00'" :suffix="`/ ${metrics.currency || 'CNY'}`" note="已支付订单金额" icon="Money" />
            <MetricCard title="待处理订单" :value="metrics.pending_orders || 0" :note="`待付款 ${metrics.pending_payment_orders || 0} / 待发货 ${metrics.pending_fulfillment_orders || 0}`" icon="Tickets" />
            <MetricCard title="文章" :value="totals.articles" note="SEO 内容库" icon="Document" />
            <MetricCard title="商品" :value="totals.products" note="独立站商品" icon="Goods" />
            <MetricCard title="订单" :value="totals.orders" note="商城订单" icon="ShoppingCart" />
            <MetricCard title="留言" :value="totals.forms" note="询盘线索" icon="ChatLineRound" />
          </div>
          <el-card class="panel" shadow="never">
            <template #header><strong>站点信息</strong></template>
            <el-descriptions :column="2" border>
              <el-descriptions-item label="站点名称">{{ site.name || '-' }}</el-descriptions-item>
              <el-descriptions-item label="域名">{{ site.domain || '-' }}</el-descriptions-item>
              <el-descriptions-item label="电话">{{ site.phone || '-' }}</el-descriptions-item>
              <el-descriptions-item label="邮箱">{{ site.email || '-' }}</el-descriptions-item>
              <el-descriptions-item label="地址" :span="2">{{ site.address || '-' }}</el-descriptions-item>
            </el-descriptions>
          </el-card>
        </section>

        <section v-if="view === 'settings'">
          <el-card class="panel" shadow="never">
            <template #header>
              <div class="card-head"><strong>站点设置</strong><el-button type="primary" @click="saveSettings">保存设置</el-button></div>
            </template>
            <el-form :model="site" label-width="120px" class="wide-form">
              <el-divider content-position="left">基础信息</el-divider>
              <el-row :gutter="16">
                <el-col :span="12"><el-form-item label="站点名称"><el-input v-model="site.name" /></el-form-item></el-col>
                <el-col :span="12"><el-form-item label="域名"><el-input v-model="site.domain" /></el-form-item></el-col>
              </el-row>
              <el-form-item label="品牌标语"><el-input v-model="site.slogan" /></el-form-item>
              <el-form-item label="网站描述"><el-input v-model="site.description" type="textarea" :rows="3" /></el-form-item>
              <el-form-item label="SEO 关键词"><el-input v-model="site.keywords" /></el-form-item>
              <el-row :gutter="16">
                <el-col :span="12"><el-form-item label="电话"><el-input v-model="site.phone" /></el-form-item></el-col>
                <el-col :span="12"><el-form-item label="邮箱"><el-input v-model="site.email" /></el-form-item></el-col>
              </el-row>
              <el-form-item label="地址"><el-input v-model="site.address" /></el-form-item>
              <el-divider content-position="left">AI 与支付</el-divider>
              <el-row :gutter="16">
                <el-col :span="12"><el-form-item label="AI 服务商"><el-input v-model="site.ai.provider" /></el-form-item></el-col>
                <el-col :span="12"><el-form-item label="模型名称"><el-input v-model="site.ai.model" /></el-form-item></el-col>
              </el-row>
              <el-form-item label="AI API 地址"><el-input v-model="site.ai.endpoint" /></el-form-item>
              <el-form-item label="AI API Key"><el-input v-model="site.ai.api_key" type="password" show-password /></el-form-item>
              <el-row :gutter="16">
                <el-col :span="12"><el-form-item label="支付模式"><el-select v-model="site.payment.mode"><el-option label="人工确认" value="manual" /><el-option label="微信支付" value="wechat" /><el-option label="支付宝" value="alipay" /><el-option label="Stripe" value="stripe" /></el-select></el-form-item></el-col>
                <el-col :span="12"><el-form-item label="默认币种"><el-input v-model="site.payment.currency" /></el-form-item></el-col>
              </el-row>
              <el-form-item label="付款说明"><el-input v-model="site.payment.guide" type="textarea" :rows="3" /></el-form-item>
            </el-form>
          </el-card>
        </section>

        <section v-if="view === 'templates'">
          <el-row :gutter="16">
            <el-col :span="9">
              <el-card class="panel" shadow="never">
                <template #header>
                  <div class="card-head">
                    <strong>模板中心</strong>
                    <el-button type="primary" @click="saveTemplateSettings">保存配置</el-button>
                  </div>
                </template>
                <div class="template-grid">
                  <article v-for="item in templates" :key="item.key" class="template-card" :class="{ active: site.template_key === item.key }" @click="site.template_key = item.key">
                    <div class="template-preview">
                      <span>{{ item.name?.slice(0, 2) || '模' }}</span>
                    </div>
                    <strong>{{ item.name }}</strong>
                    <small>{{ item.key }} · {{ item.version || 'v0.1' }}</small>
                    <div class="tag-row">
                      <el-tag v-for="support in item.supports || []" :key="support" size="small" effect="plain">{{ support }}</el-tag>
                    </div>
                    <el-button size="small" :type="site.template_key === item.key ? 'primary' : 'default'">{{ site.template_key === item.key ? '当前模板' : '选择模板' }}</el-button>
                  </article>
                </div>
              </el-card>
            </el-col>
            <el-col :span="15">
              <el-card class="panel" shadow="never">
                <template #header>
                  <div class="card-head">
                    <strong>模块中心</strong>
                    <el-button @click="resetHomeModules">恢复默认模块</el-button>
                  </div>
                </template>
                <el-alert title="模块配置会写入站点设置，生成静态站时按启用状态和排序输出首页模块。" type="info" show-icon class="mb16" />
                <div class="builder-box">
                  <div class="builder-form">
                    <el-input v-model="pageBuilder.prompt" type="textarea" :rows="3" placeholder="告诉 AI：这个站点面向什么行业、卖什么产品、希望首页突出什么" />
                    <div class="builder-actions">
                      <el-button type="primary" :loading="pagePlanLoading" @click="generatePagePlan">AI 生成页面草案</el-button>
                      <el-button :disabled="!pagePlan" @click="applyPagePlan">应用草案</el-button>
                      <el-button :disabled="!pagePlan" :loading="pagePlanSaving" @click="applyPagePlanAndSave">应用并保存</el-button>
                    </div>
                  </div>
                  <div v-if="pagePlan" class="page-plan-preview">
                    <strong>{{ pagePlan.summary || '首页搭建草案' }}</strong>
                    <div class="plan-grid">
                      <article>
                        <span>首屏</span>
                        <b>{{ pagePlan.hero?.title }}</b>
                        <small>{{ pagePlan.hero?.subtitle }}</small>
                      </article>
                      <article>
                        <span>模块顺序</span>
                        <b>{{ (pagePlan.home_modules || []).map((item: any) => item.title || item.key).join(' / ') }}</b>
                      </article>
                      <article>
                        <span>内容草案</span>
                        <b>{{ pagePlan.home_content?.advantages?.length || 0 }} 个优势 / {{ pagePlan.home_content?.cases?.length || 0 }} 个案例</b>
                        <small>{{ pagePlan.home_content?.faqs?.length || 0 }} 个 FAQ</small>
                      </article>
                    </div>
                  </div>
                </div>
                <el-tabs v-model="templateTab">
                  <el-tab-pane label="首页模块" name="home">
                    <el-table :data="site.home_modules" row-key="key" height="470">
                      <el-table-column prop="title" label="模块" min-width="160">
                        <template #default="{ row }"><strong>{{ moduleTitle(row.key) }}</strong><br /><small>{{ moduleDescription(row.key) }}</small></template>
                      </el-table-column>
                      <el-table-column label="启用" width="90"><template #default="{ row }"><el-switch v-model="row.enabled" /></template></el-table-column>
                      <el-table-column label="排序" width="150"><template #default="{ row }"><el-input-number v-model="row.sort_order" :min="0" :step="10" size="small" /></template></el-table-column>
                      <el-table-column label="操作" width="150">
                        <template #default="{ $index }">
                          <el-button link type="primary" @click="moveHomeModule($index, -1)">上移</el-button>
                          <el-button link type="primary" @click="moveHomeModule($index, 1)">下移</el-button>
                        </template>
                      </el-table-column>
                    </el-table>
                  </el-tab-pane>
                  <el-tab-pane label="全站模块" name="global">
                    <div class="global-module-grid">
                      <label v-for="item in globalModuleItems" :key="item.key" class="module-toggle">
                        <el-switch v-model="site.global_modules[item.key]" />
                        <span><strong>{{ item.title }}</strong><small>{{ item.description }}</small></span>
                      </label>
                      <el-form label-width="96px" class="floating-text-form">
                        <el-form-item label="浮动按钮文案"><el-input v-model="site.global_modules.floating_text" /></el-form-item>
                      </el-form>
                    </div>
                  </el-tab-pane>
                  <el-tab-pane label="模块注册表" name="registry">
                    <el-table :data="moduleRegistry.modules || []" height="470">
                      <el-table-column prop="key" label="Key" width="150" />
                      <el-table-column prop="title" label="名称" width="140" />
                      <el-table-column prop="scope" label="范围" width="110" />
                      <el-table-column prop="render_slot" label="插槽" min-width="180" />
                      <el-table-column prop="description" label="说明" min-width="240" />
                    </el-table>
                  </el-tab-pane>
                </el-tabs>
              </el-card>
            </el-col>
          </el-row>
        </section>

        <section v-if="view === 'ai'">
          <el-row :gutter="16">
            <el-col :span="9">
              <el-card class="panel" shadow="never">
                <template #header><strong>AI 内容生产</strong></template>
                <el-form :model="aiForm" label-width="96px">
                  <el-form-item label="内容类型">
                    <el-segmented v-model="aiForm.type" :options="[{ label: '文章', value: 'article' }, { label: '商品', value: 'product' }]" />
                  </el-form-item>
                  <el-form-item label="生成要求">
                    <el-input v-model="aiForm.prompt" type="textarea" :rows="7" placeholder="例如：围绕低空巡检无人机、自主品牌、行业解决方案，生成适合独立站收录的内容" />
                  </el-form-item>
                  <el-form-item label="生成数量">
                    <el-input-number v-model="aiForm.count" :min="1" :max="20" />
                  </el-form-item>
                  <el-form-item label="保存状态">
                    <el-radio-group v-model="aiForm.status">
                      <el-radio-button label="draft">草稿</el-radio-button>
                      <el-radio-button label="published">发布</el-radio-button>
                    </el-radio-group>
                  </el-form-item>
                  <el-form-item>
                    <el-button type="primary" :loading="aiLoading" @click="generateAiPreview">生成预览</el-button>
                    <el-button :loading="aiBatchLoading" @click="batchCreateAiContent">批量入库</el-button>
                  </el-form-item>
                </el-form>
              </el-card>
            </el-col>
            <el-col :span="15">
              <el-card class="panel" shadow="never">
                <template #header>
                  <div class="card-head">
                    <strong>生成结果</strong>
                    <el-button :disabled="!aiDrafts.length" @click="clearAiDrafts">清空</el-button>
                  </div>
                </template>
                <el-empty v-if="!aiDrafts.length" description="输入要求后生成内容预览" />
                <div v-else class="ai-draft-list">
                  <article v-for="(item, index) in aiDrafts" :key="item.local_id" class="ai-draft-card">
                    <div class="ai-draft-head">
                      <div>
                        <el-tag>{{ item.type === 'article' ? '文章' : '商品' }}</el-tag>
                        <strong>{{ item.title }}</strong>
                      </div>
                      <div class="ai-draft-actions">
                        <el-button size="small" :loading="aiCoverLoading === item.local_id" @click="generateAiCover(item)">生成封面</el-button>
                        <el-button size="small" type="primary" @click="saveAiDraft(item, index)">保存草稿</el-button>
                      </div>
                    </div>
                    <img v-if="item.cover" class="ai-cover" :src="item.cover.startsWith('/') ? item.cover : '/' + item.cover" />
                    <p>{{ item.summary }}</p>
                    <small>{{ item.slug }}<span v-if="item.sku"> / {{ item.sku }}</span></small>
                  </article>
                </div>
              </el-card>
            </el-col>
          </el-row>
        </section>

        <section v-if="view === 'articles'">
          <ContentEditor
            type="article"
            :items="articles"
            :form="articleForm"
            :page="articlePager.page"
            :page-size="articlePager.page_size"
            :total="articlePager.total"
            :media="imageMedia"
            @new="newArticle"
            @edit="editArticle"
            @save="saveArticle"
            @delete="deleteArticle"
            @ai="generateArticleDraft"
            @page-change="changeArticlePage"
          />
        </section>

        <section v-if="view === 'products'">
          <ContentEditor
            type="product"
            :items="products"
            :form="productForm"
            :page="productPager.page"
            :page-size="productPager.page_size"
            :total="productPager.total"
            :media="imageMedia"
            @new="newProduct"
            @edit="editProduct"
            @save="saveProduct"
            @delete="deleteProduct"
            @ai="generateProductDraft"
            @page-change="changeProductPage"
          />
        </section>

        <section v-if="view === 'orders'">
          <el-card class="panel" shadow="never">
            <template #header>
              <div class="card-head">
                <strong>订单列表</strong>
                <el-button @click="loadOrders">刷新</el-button>
              </div>
            </template>
            <el-form :inline="true" class="toolbar" @submit.prevent="applyOrderFilters">
              <el-form-item><el-input v-model="orderFilters.keyword" placeholder="订单号/客户/手机号" clearable /></el-form-item>
              <el-form-item><el-select v-model="orderFilters.payment_status" placeholder="支付" clearable><el-option label="待支付" value="pending" /><el-option label="已支付" value="paid" /></el-select></el-form-item>
              <el-form-item><el-select v-model="orderFilters.fulfillment_status" placeholder="履约" clearable><el-option label="新订单" value="new" /><el-option label="已确认" value="confirmed" /><el-option label="已发货" value="shipped" /><el-option label="已完成" value="finished" /></el-select></el-form-item>
              <el-button type="primary" @click="applyOrderFilters">筛选</el-button>
            </el-form>
            <el-table :data="orders" height="560" row-key="id" highlight-current-row @row-click="selectOrder">
              <el-table-column prop="order_no" label="订单号" min-width="170" />
              <el-table-column prop="customer_name" label="客户" min-width="150">
                <template #default="{ row }"><strong>{{ row.customer_name }}</strong><br /><small>{{ row.phone }}</small></template>
              </el-table-column>
              <el-table-column label="金额" width="140"><template #default="{ row }">{{ row.currency }} {{ row.total_amount }}</template></el-table-column>
              <el-table-column label="状态" width="150">
                <template #default="{ row }"><el-tag>{{ paymentLabel(row.payment_status) }}</el-tag><el-tag class="ml6" type="success">{{ fulfillLabel(row.fulfillment_status) }}</el-tag></template>
              </el-table-column>
              <el-table-column label="操作" width="110"><template #default="{ row }"><el-button link type="primary" @click.stop="selectOrder(row)">跟进</el-button></template></el-table-column>
            </el-table>
            <el-pagination class="table-pager" layout="prev, pager, next, total" :current-page="orderPager.page" :page-size="orderPager.page_size" :total="orderPager.total" @current-change="changeOrderPage" />
          </el-card>
          <el-drawer v-model="orderDrawerVisible" size="520px" title="订单跟进">
            <el-empty v-if="!orderDetail.id" description="选择一条订单查看详情" />
            <el-form v-else :model="orderDetail" label-width="92px">
              <el-descriptions :column="1" border class="mb16">
                <el-descriptions-item label="订单号">{{ orderDetail.order_no }}</el-descriptions-item>
                <el-descriptions-item label="客户">{{ orderDetail.customer_name }} / {{ orderDetail.phone }}</el-descriptions-item>
                <el-descriptions-item label="金额">{{ orderDetail.currency }} {{ orderDetail.total_amount }}</el-descriptions-item>
              </el-descriptions>
              <el-form-item label="支付状态"><el-select v-model="orderDetail.payment_status"><el-option label="待支付" value="pending" /><el-option label="已支付" value="paid" /><el-option label="已退款" value="refunded" /></el-select></el-form-item>
              <el-form-item label="履约状态"><el-select v-model="orderDetail.fulfillment_status"><el-option label="新订单" value="new" /><el-option label="已确认" value="confirmed" /><el-option label="已发货" value="shipped" /><el-option label="已完成" value="finished" /><el-option label="已关闭" value="closed" /></el-select></el-form-item>
              <el-form-item label="物流公司"><el-input v-model="orderDetail.tracking_company" /></el-form-item>
              <el-form-item label="物流单号"><el-input v-model="orderDetail.tracking_no" /></el-form-item>
              <el-form-item label="新增跟进"><el-input v-model="orderDetail.followup_note" type="textarea" :rows="3" /></el-form-item>
              <el-form-item label="时间线"><el-input v-model="orderDetail.remark" type="textarea" :rows="6" /></el-form-item>
              <el-button type="primary" @click="saveOrder">保存订单</el-button>
            </el-form>
          </el-drawer>
        </section>

        <section v-if="view === 'service'">
          <el-card class="panel" shadow="never">
            <template #header>
              <div class="card-head"><strong>服务中心</strong><el-button type="primary" @click="resolveSelectedServices">批量标记已处理</el-button></div>
            </template>
            <el-form :inline="true" class="toolbar" @submit.prevent="loadServices">
              <el-form-item><el-input v-model="serviceFilters.keyword" placeholder="搜索订单/客户/请求内容" clearable /></el-form-item>
              <el-form-item><el-select v-model="serviceFilters.status" placeholder="状态" clearable><el-option label="待处理" value="pending" /><el-option label="已处理" value="handled" /></el-select></el-form-item>
              <el-form-item><el-select v-model="serviceFilters.type" placeholder="类型" clearable><el-option label="催发货" value="催发货" /><el-option label="改地址" value="修改收货信息" /><el-option label="售后" value="售后问题" /><el-option label="其他" value="其他服务" /></el-select></el-form-item>
              <el-button type="primary" @click="loadServices">筛选</el-button>
            </el-form>
            <el-table :data="services" height="600" @selection-change="selectedServiceIds = $event.map((item: any) => item.id)">
              <el-table-column type="selection" width="48" :selectable="(row: any) => row.status === 'pending'" />
              <el-table-column prop="type" label="类型" width="130" />
              <el-table-column prop="message" label="请求内容" min-width="260" />
              <el-table-column label="客户" width="170"><template #default="{ row }">{{ row.customer_name }}<br /><small>{{ row.phone }}</small></template></el-table-column>
              <el-table-column prop="order_no" label="订单号" width="170" />
              <el-table-column label="状态" width="100"><template #default="{ row }"><el-tag :type="row.status === 'handled' ? 'success' : 'warning'">{{ row.status === 'handled' ? '已处理' : '待处理' }}</el-tag></template></el-table-column>
              <el-table-column label="操作" width="110"><template #default="{ row }"><el-button link type="primary" @click="openOrder(row.order_id)">去处理</el-button></template></el-table-column>
            </el-table>
            <el-pagination class="table-pager" layout="prev, pager, next, total" :current-page="servicePager.page" :page-size="servicePager.page_size" :total="servicePager.total" @current-change="changeServicePage" />
          </el-card>
        </section>

        <section v-if="view === 'media'">
          <el-card class="panel" shadow="never">
            <template #header>
              <div class="card-head">
                <strong>媒体库</strong>
                <el-button @click="loadMedia">刷新</el-button>
              </div>
            </template>
            <el-upload drag action="/api/media/upload" :headers="authHeaders" name="file" :on-success="loadMedia">
              <el-icon class="upload-icon"><UploadFilled /></el-icon>
              <div>拖拽文件到这里，或点击上传</div>
            </el-upload>
            <el-form :inline="true" class="toolbar media-toolbar" @submit.prevent="applyMediaFilters">
              <el-form-item><el-input v-model="mediaFilters.keyword" placeholder="搜索文件名/路径" clearable /></el-form-item>
              <el-form-item><el-select v-model="mediaFilters.file_type" placeholder="类型" clearable><el-option label="图片" value="image" /><el-option label="文件" value="file" /></el-select></el-form-item>
              <el-button type="primary" @click="applyMediaFilters">筛选</el-button>
            </el-form>
            <div class="media-grid">
              <article v-for="item in media" :key="item.id" class="media-card">
                <img v-if="item.file_type === 'image'" :src="`/${item.file_path}`" />
                <div v-else class="file-tile">FILE</div>
                <strong>{{ item.file_name }}</strong>
                <small>/{{ item.file_path }}</small>
                <el-button size="small" @click="copyMediaPath(item.file_path)">复制路径</el-button>
              </article>
            </div>
            <el-pagination class="table-pager" layout="prev, pager, next, total" :current-page="mediaPager.page" :page-size="mediaPager.page_size" :total="mediaPager.total" @current-change="changeMediaPage" />
          </el-card>
        </section>

        <section v-if="view === 'forms'">
          <el-card class="panel" shadow="never">
            <template #header><strong>留言线索</strong></template>
            <el-table :data="forms" height="650">
              <el-table-column prop="form_key" label="来源" width="140" />
              <el-table-column label="内容" min-width="340"><template #default="{ row }"><pre>{{ pretty(row.data) }}</pre></template></el-table-column>
              <el-table-column prop="status" label="状态" width="120" />
              <el-table-column prop="created_at" label="时间" width="170" />
            </el-table>
            <el-pagination class="table-pager" layout="prev, pager, next, total" :current-page="formPager.page" :page-size="formPager.page_size" :total="formPager.total" @current-change="changeFormPage" />
          </el-card>
        </section>

        <section v-if="view === 'publish'">
          <el-card class="panel" shadow="never">
            <template #header>
              <div class="card-head"><strong>发布中心</strong><el-button type="primary" :loading="generating" @click="generateSite">生成静态站</el-button></div>
            </template>
            <el-alert title="新版后台复用原 PHP 发布接口，不影响旧 admin.html。" type="info" show-icon class="mb16" />
            <el-result v-if="publishResult" class="publish-result" icon="success" title="本次生成完成" :sub-title="publishResult.version_no || publishResult.message || '静态站已生成'">
              <template #extra>
                <el-button type="primary" @click="previewSite">预览站点</el-button>
                <el-button @click="publishResult = null">收起</el-button>
              </template>
            </el-result>
            <el-table :data="versions">
              <el-table-column prop="version_no" label="版本号" min-width="160" />
              <el-table-column prop="publish_type" label="类型" width="120" />
              <el-table-column prop="status" label="状态" width="120">
                <template #default="{ row }"><el-tag :type="row.status === 'success' ? 'success' : 'info'">{{ row.status }}</el-tag></template>
              </el-table-column>
              <el-table-column prop="created_at" label="时间" width="180" />
              <el-table-column label="操作" width="100"><template #default="{ row }"><el-button link type="primary" @click="openPublishVersion(row)">详情</el-button></template></el-table-column>
            </el-table>
          </el-card>
          <el-drawer v-model="publishDrawerVisible" size="520px" title="发布版本详情">
            <el-descriptions :column="1" border>
              <el-descriptions-item label="版本号">{{ publishDetail.version_no || '-' }}</el-descriptions-item>
              <el-descriptions-item label="类型">{{ publishDetail.publish_type || '-' }}</el-descriptions-item>
              <el-descriptions-item label="状态">{{ publishDetail.status || '-' }}</el-descriptions-item>
              <el-descriptions-item label="时间">{{ publishDetail.created_at || '-' }}</el-descriptions-item>
              <el-descriptions-item label="说明">{{ publishDetail.message || publishDetail.remark || '-' }}</el-descriptions-item>
            </el-descriptions>
          </el-drawer>
        </section>
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import axios from 'axios'

const TOKEN_KEY = 'huajian_admin_token'
const token = ref(localStorage.getItem(TOKEN_KEY) || '')
const loading = ref(false)
const generating = ref(false)
const view = ref('dashboard')
const templateTab = ref('home')

const loginForm = reactive({ username: 'admin', password: 'admin123456' })
const site = reactive<any>({ ai: {}, payment: {}, deploy: {}, content: {} })
const metrics = ref<any>({})
const totals = reactive({ articles: 0, products: 0, orders: 0, media: 0, forms: 0 })
const articles = ref<any[]>([])
const products = ref<any[]>([])
const orders = ref<any[]>([])
const services = ref<any[]>([])
const media = ref<any[]>([])
const forms = ref<any[]>([])
const versions = ref<any[]>([])
const templates = ref<any[]>([])
const moduleRegistry = ref<any>({ scopes: [], modules: [] })
const servicePending = ref(0)
const selectedServiceIds = ref<string[]>([])
const orderDrawerVisible = ref(false)
const publishDrawerVisible = ref(false)
const publishResult = ref<any>(null)

const orderFilters = reactive({ keyword: '', payment_status: '', fulfillment_status: '' })
const serviceFilters = reactive({ keyword: '', status: '', type: '' })
const mediaFilters = reactive({ keyword: '', file_type: '' })
const orderDetail = reactive<any>({})
const publishDetail = reactive<any>({})
const articleForm = reactive<any>({})
const productForm = reactive<any>({})
const aiForm = reactive({ type: 'article', prompt: '围绕自主品牌商品、行业解决方案和独立站 SEO 关键词生成内容', count: 5, status: 'draft' })
const pageBuilder = reactive({ prompt: '围绕自主品牌商品、行业解决方案、SEO 内容沉淀和询盘转化，生成一个企业官网 + 博客知识库 + 独立站商城首页方案' })
const articlePager = reactive({ page: 1, page_size: 10, total: 0 })
const productPager = reactive({ page: 1, page_size: 10, total: 0 })
const orderPager = reactive({ page: 1, page_size: 10, total: 0 })
const servicePager = reactive({ page: 1, page_size: 10, total: 0 })
const mediaPager = reactive({ page: 1, page_size: 12, total: 0 })
const formPager = reactive({ page: 1, page_size: 10, total: 0 })
const aiDrafts = ref<any[]>([])
const pagePlan = ref<any>(null)
const aiLoading = ref(false)
const aiBatchLoading = ref(false)
const aiCoverLoading = ref('')
const pagePlanLoading = ref(false)
const pagePlanSaving = ref(false)

const navItems = [
  { key: 'dashboard', label: '概览', hint: '查看运营指标、内容数量和站点状态。', icon: 'Odometer' },
  { key: 'settings', label: '站点', hint: '维护企业信息、SEO、AI、支付和发布配置。', icon: 'Setting' },
  { key: 'templates', label: '模板', hint: '选择主题模板，启用首页与全站模块。', icon: 'Grid' },
  { key: 'ai', label: 'AI', hint: '批量生成文章、商品文案和封面素材。', icon: 'MagicStick' },
  { key: 'articles', label: '文章', hint: '管理 SEO 文章和知识库内容。', icon: 'Document' },
  { key: 'products', label: '商品', hint: '管理独立站商品与商城展示内容。', icon: 'Goods' },
  { key: 'orders', label: '订单', hint: '处理支付、发货和订单跟进。', icon: 'ShoppingCart' },
  { key: 'service', label: '服务', hint: '集中处理客户服务请求。', icon: 'Service' },
  { key: 'media', label: '媒体库', hint: '上传并复用图片和文件素材。', icon: 'Picture' },
  { key: 'forms', label: '留言', hint: '处理询盘线索和联系表单。', icon: 'ChatLineRound' },
  { key: 'publish', label: '发布', hint: '生成静态站并查看发布记录。', icon: 'Upload' }
]
const currentNav = computed(() => navItems.find((item) => item.key === view.value))
const authHeaders = computed(() => ({ Authorization: `Bearer ${token.value}` }))
const imageMedia = computed(() => media.value.filter((item) => item.file_type === 'image'))
const homeModuleItems = computed(() => (moduleRegistry.value.modules || []).filter((item: any) => item.scope === 'home'))
const globalModuleItems = computed(() => (moduleRegistry.value.modules || []).filter((item: any) => item.scope === 'global'))

async function request(path: string, options: any = {}) {
  const response = await axios({
    url: path,
    method: options.method || 'GET',
    data: options.body ? JSON.parse(options.body) : options.data,
    headers: token.value ? { Authorization: `Bearer ${token.value}`, ...(options.headers || {}) } : options.headers
  })
  if (!response.data.success) throw new Error(response.data.message || '请求失败')
  return response.data.data
}

async function login() {
  loading.value = true
  try {
    const data = await request('/api/auth/login', {
      method: 'POST',
      data: { ...loginForm }
    })
    token.value = data.token
    localStorage.setItem(TOKEN_KEY, data.token)
    ElMessage.success('登录成功')
    await loadAll()
  } finally {
    loading.value = false
  }
}

async function logout() {
  try { await request('/api/auth/logout', { method: 'POST' }) } catch {}
  token.value = ''
  localStorage.removeItem(TOKEN_KEY)
}

function setView(key: string) {
  view.value = key
  if (key === 'dashboard') loadDashboard()
  if (key === 'templates') Promise.all([loadTemplates(), loadModuleRegistry()])
  if (key === 'articles') loadArticles()
  if (key === 'products') loadProducts()
  if (key === 'orders') loadOrders()
  if (key === 'service') loadServices()
  if (key === 'media') loadMedia()
  if (key === 'forms') loadForms()
  if (key === 'publish') loadVersions()
}

function refreshCurrentView() {
  const loaders: Record<string, () => Promise<void>> = {
    dashboard: loadDashboard,
    settings: loadSettings,
    templates: async () => { await Promise.all([loadTemplates(), loadModuleRegistry(), loadSettings()]) },
    ai: async () => {},
    articles: loadArticles,
    products: loadProducts,
    orders: loadOrders,
    service: loadServices,
    media: loadMedia,
    forms: loadForms,
    publish: loadVersions
  }
  loaders[view.value]?.().then(() => ElMessage.success('当前页面已刷新'))
}

function openLegacyAdmin() {
  window.open('/admin.html', '_blank')
}

async function loadAll() {
  await Promise.all([loadDashboard(), loadSettings(), loadTemplates(), loadModuleRegistry(), loadArticles(), loadProducts(), loadOrders(), loadServices(), loadMedia(), loadForms(), loadVersions()])
}

async function loadDashboard() {
  const [articleData, productData, orderData, mediaData, formData, metricData] = await Promise.all([
    request('/api/articles?page_size=1'),
    request('/api/products?page_size=1'),
    request('/api/orders?page_size=1'),
    request('/api/media?page_size=1'),
    request('/api/forms/submissions?page_size=1'),
    request('/api/dashboard/metrics')
  ])
  totals.articles = articleData.pagination?.total || 0
  totals.products = productData.pagination?.total || 0
  totals.orders = orderData.pagination?.total || 0
  totals.media = mediaData.pagination?.total || 0
  totals.forms = formData.pagination?.total || 0
  metrics.value = metricData
}

async function loadSettings() {
  const data = await request('/api/site/settings')
  Object.assign(site, normalizeSite(data))
}

function normalizeSite(data: any = {}) {
  const normalized = {
    ...data,
    template_key: data.template_key || 'business-clean',
    ai: data.ai || {},
    payment: data.payment || {},
    deploy: data.deploy || {},
    content: data.content || {},
    hero: data.hero || {},
    home_sections: data.home_sections || {},
    home_content: data.home_content || {},
    global_modules: {
      search_nav: data.global_modules?.search_nav ?? true,
      breadcrumbs: data.global_modules?.breadcrumbs ?? true,
      related: data.global_modules?.related ?? true,
      floating_inquiry: data.global_modules?.floating_inquiry ?? true,
      floating_text: data.global_modules?.floating_text || '立即咨询'
    },
    home_modules: Array.isArray(data.home_modules) && data.home_modules.length ? data.home_modules : defaultHomeModules()
  }
  return normalized
}

async function saveSettings() {
  const data = await request('/api/site/settings', { method: 'PUT', data: site })
  Object.assign(site, normalizeSite(data))
  ElMessage.success('站点设置已保存')
}

async function loadTemplates() {
  const data = await request('/api/site/templates')
  templates.value = data.items || []
}

async function loadModuleRegistry() {
  const data = await request('/api/site/modules')
  moduleRegistry.value = data || { scopes: [], modules: [] }
  if (!site.home_modules?.length) {
    site.home_modules = defaultHomeModules()
  }
}

function defaultHomeModules() {
  const modules = (moduleRegistry.value.modules || []).filter((item: any) => item.scope === 'home')
  const fallback = modules.length ? modules : [
    { key: 'about', title: '图文介绍' },
    { key: 'advantages', title: '优势卖点' },
    { key: 'cases', title: '案例展示' },
    { key: 'products', title: '产品模块' },
    { key: 'articles', title: '文章模块' },
    { key: 'faq', title: 'FAQ' },
    { key: 'inquiry', title: '询盘表单' }
  ]
  return fallback.map((item: any, index: number) => ({
    key: item.key,
    title: item.title || item.key,
    enabled: item.enabled_by_default ?? true,
    sort_order: (index + 1) * 10
  }))
}

function moduleMeta(key: string) {
  return (moduleRegistry.value.modules || []).find((item: any) => item.key === key) || {}
}

function moduleTitle(key: string) {
  const meta = moduleMeta(key)
  return meta.title || site.home_modules?.find((item: any) => item.key === key)?.title || key
}

function moduleDescription(key: string) {
  return moduleMeta(key).description || '-'
}

function resetHomeModules() {
  site.home_modules = defaultHomeModules()
}

function moveHomeModule(index: number, direction: number) {
  const next = index + direction
  if (next < 0 || next >= site.home_modules.length) return
  const items = [...site.home_modules]
  const current = items[index]
  items[index] = items[next]
  items[next] = current
  site.home_modules = items.map((item, itemIndex) => ({ ...item, sort_order: (itemIndex + 1) * 10 }))
}

async function saveTemplateSettings() {
  site.home_modules = [...site.home_modules]
    .sort((a: any, b: any) => Number(a.sort_order || 0) - Number(b.sort_order || 0))
    .map((item: any, index: number) => ({
      key: item.key,
      title: moduleTitle(item.key),
      enabled: Boolean(item.enabled),
      sort_order: Number(item.sort_order || (index + 1) * 10)
    }))
  const data = await request('/api/site/settings', { method: 'PUT', data: site })
  Object.assign(site, normalizeSite(data))
  ElMessage.success('模板与模块配置已保存')
}

async function generatePagePlan() {
  pagePlanLoading.value = true
  try {
    pagePlan.value = await request('/api/ai/page-plan', { method: 'POST', data: { prompt: pageBuilder.prompt } })
    ElMessage.success('页面草案已生成')
  } finally {
    pagePlanLoading.value = false
  }
}

function applyPagePlan() {
  if (!pagePlan.value) return ElMessage.warning('请先生成页面草案')
  const plan = pagePlan.value
  site.hero = { ...(site.hero || {}), ...(plan.hero || {}) }
  site.home_sections = { ...(site.home_sections || {}), ...(plan.home_sections || {}) }
  site.home_content = {
    ...(site.home_content || {}),
    ...(plan.home_content || {})
  }
  if (Array.isArray(plan.home_modules) && plan.home_modules.length) {
    site.home_modules = plan.home_modules.map((item: any, index: number) => ({
      key: item.key,
      title: item.title || moduleTitle(item.key),
      enabled: item.enabled ?? true,
      sort_order: Number(item.sort_order || (index + 1) * 10)
    }))
  }
  templateTab.value = 'home'
  ElMessage.success('页面草案已应用')
}

async function applyPagePlanAndSave() {
  pagePlanSaving.value = true
  try {
    applyPagePlan()
    await saveTemplateSettings()
  } finally {
    pagePlanSaving.value = false
  }
}

async function loadArticles() {
  const data = await request(`/api/articles?page=${articlePager.page}&page_size=${articlePager.page_size}`)
  articles.value = data.items || []
  articlePager.total = data.pagination?.total || articles.value.length
}
function changeArticlePage(page: number) {
  articlePager.page = page
  loadArticles()
}

async function loadProducts() {
  const data = await request(`/api/products?page=${productPager.page}&page_size=${productPager.page_size}`)
  products.value = data.items || []
  productPager.total = data.pagination?.total || products.value.length
}
function changeProductPage(page: number) {
  productPager.page = page
  loadProducts()
}

function newArticle() { Object.assign(articleForm, { id: '', title: '', slug: '', cover: '', summary: '', content: '', seo_keywords: '', status: 'draft' }) }
function editArticle(item: any) { Object.assign(articleForm, item) }
async function saveArticle() {
  const method = articleForm.id ? 'PUT' : 'POST'
  const path = articleForm.id ? `/api/articles/${articleForm.id}` : '/api/articles'
  await request(path, { method, data: { ...articleForm } })
  ElMessage.success('文章已保存')
  articlePager.page = articleForm.id ? articlePager.page : 1
  await loadArticles()
}
async function deleteArticle(item: any) {
  await ElMessageBox.confirm(`确定删除文章「${item.title}」？`)
  await request(`/api/articles/${item.id}`, { method: 'DELETE' })
  await loadArticles()
}
async function generateArticleDraft(prompt: string) {
  const data = await request('/api/ai/generate', { method: 'POST', data: { type: 'article', prompt } })
  Object.assign(articleForm, data.draft || data)
}

function newProduct() { Object.assign(productForm, { id: '', title: '', slug: '', sku: '', cover: '', summary: '', description: '', price: 0, stock: 0, status: 'draft' }) }
function editProduct(item: any) { Object.assign(productForm, item) }
async function saveProduct() {
  const method = productForm.id ? 'PUT' : 'POST'
  const path = productForm.id ? `/api/products/${productForm.id}` : '/api/products'
  await request(path, { method, data: { ...productForm } })
  ElMessage.success('商品已保存')
  productPager.page = productForm.id ? productPager.page : 1
  await loadProducts()
}
async function deleteProduct(item: any) {
  await ElMessageBox.confirm(`确定删除商品「${item.title}」？`)
  await request(`/api/products/${item.id}`, { method: 'DELETE' })
  await loadProducts()
}
async function generateProductDraft(prompt: string) {
  const data = await request('/api/ai/generate', { method: 'POST', data: { type: 'product', prompt } })
  Object.assign(productForm, data.draft || data)
}

function aiPromptFor(index = 1) {
  return `${aiForm.prompt}（第 ${index} 条，避免重复）`
}

function normalizeAiDraft(type: string, draft: any, index = 1) {
  const title = draft.title || `${aiForm.prompt.slice(0, 24)} ${index}`
  return {
    ...draft,
    local_id: `${Date.now()}-${index}-${Math.random().toString(36).slice(2, 7)}`,
    type,
    title,
    slug: draft.slug || `${type}-${Date.now()}-${index}`,
    sku: type === 'product' ? (draft.sku || `HJ-${Date.now().toString(36).toUpperCase().slice(-6)}-${index}`) : draft.sku,
    cover: draft.cover || '',
    summary: draft.summary || '',
    content: draft.content || '',
    description: draft.description || '',
    price: draft.price ?? 0,
    stock: draft.stock ?? 999,
    seo_keywords: draft.seo_keywords || '',
    status: aiForm.status
  }
}

async function generateAiPreview() {
  aiLoading.value = true
  try {
    const items = []
    for (let index = 1; index <= Math.min(3, aiForm.count); index++) {
      const data = await request('/api/ai/generate', { method: 'POST', data: { type: aiForm.type, prompt: aiPromptFor(index) } })
      items.push(normalizeAiDraft(aiForm.type, data.draft || data, index))
    }
    aiDrafts.value = items
    ElMessage.success(`已生成 ${items.length} 条预览`)
  } finally {
    aiLoading.value = false
  }
}

async function saveAiDraft(item: any, index: number) {
  if (item.type === 'article') {
    await request('/api/articles', { method: 'POST', data: { ...item, status: 'draft' } })
    await loadArticles()
  } else {
    await request('/api/products', { method: 'POST', data: { ...item, status: 'draft' } })
    await loadProducts()
  }
  aiDrafts.value.splice(index, 1)
  await loadDashboard()
  ElMessage.success('已保存为草稿')
}

async function batchCreateAiContent() {
  aiBatchLoading.value = true
  try {
    const path = aiForm.type === 'article' ? '/api/ai/batch-articles' : '/api/ai/batch-products'
    const data = await request(path, { method: 'POST', data: { prompt: aiForm.prompt, count: aiForm.count, status: aiForm.status } })
    ElMessage.success(`已批量生成 ${data.count || 0} 条内容`)
    await Promise.all([loadArticles(), loadProducts(), loadDashboard()])
  } finally {
    aiBatchLoading.value = false
  }
}

async function generateAiCover(item: any) {
  aiCoverLoading.value = item.local_id
  try {
    const data = await request('/api/ai/generate-image', { method: 'POST', data: { type: item.type, title: item.title, prompt: item.summary || aiForm.prompt } })
    item.cover = data.path || item.cover
    await loadMedia()
    ElMessage.success('封面已生成')
  } finally {
    aiCoverLoading.value = ''
  }
}

function clearAiDrafts() {
  aiDrafts.value = []
}

async function loadOrders() {
  const params = new URLSearchParams()
  params.set('page', String(orderPager.page))
  params.set('page_size', String(orderPager.page_size))
  Object.entries(orderFilters).forEach(([key, value]) => value && params.set(key, value))
  const data = await request(`/api/orders?${params.toString()}`)
  orders.value = data.items || []
  orderPager.total = data.pagination?.total || orders.value.length
}
function applyOrderFilters() {
  orderPager.page = 1
  loadOrders()
}
function changeOrderPage(page: number) {
  orderPager.page = page
  loadOrders()
}
function selectOrder(row: any) {
  Object.assign(orderDetail, row, { followup_note: '' })
  orderDrawerVisible.value = true
}
async function saveOrder() {
  await request(`/api/orders/${orderDetail.id}`, {
    method: 'PUT',
    data: {
      payment_status: orderDetail.payment_status,
      fulfillment_status: orderDetail.fulfillment_status,
      tracking_company: orderDetail.tracking_company,
      tracking_no: orderDetail.tracking_no,
      followup_note: orderDetail.followup_note,
      remark: orderDetail.remark
    }
  })
  ElMessage.success('订单已保存')
  orderDrawerVisible.value = false
  await Promise.all([loadOrders(), loadServices(), loadDashboard()])
}
async function openOrder(id: number) {
  const data = await request(`/api/orders/${id}`)
  Object.assign(orderDetail, data, { followup_note: '' })
  view.value = 'orders'
  orderDrawerVisible.value = true
}

async function loadServices() {
  const params = new URLSearchParams()
  params.set('page', String(servicePager.page))
  params.set('page_size', String(servicePager.page_size))
  Object.entries(serviceFilters).forEach(([key, value]) => value && params.set(key, value))
  const data = await request(`/api/orders/service-requests?${params.toString()}`)
  services.value = data.items || []
  servicePager.total = data.pagination?.total || services.value.length
  servicePending.value = data.pending || 0
}
function changeServicePage(page: number) {
  servicePager.page = page
  loadServices()
}
async function resolveSelectedServices() {
  if (!selectedServiceIds.value.length) return ElMessage.warning('请选择待处理服务请求')
  const data = await request('/api/orders/service-requests/resolve', { method: 'POST', data: { ids: selectedServiceIds.value } })
  ElMessage.success(`已处理 ${data.handled || 0} 条服务请求`)
  await loadServices()
}

async function loadMedia() {
  const params = new URLSearchParams()
  params.set('page', String(mediaPager.page))
  params.set('page_size', String(mediaPager.page_size))
  Object.entries(mediaFilters).forEach(([key, value]) => value && params.set(key, value))
  const data = await request(`/api/media?${params.toString()}`)
  media.value = data.items || []
  mediaPager.total = data.pagination?.total || media.value.length
}
function applyMediaFilters() {
  mediaPager.page = 1
  loadMedia()
}
function changeMediaPage(page: number) {
  mediaPager.page = page
  loadMedia()
}
async function copyMediaPath(path: string) {
  const value = `/${path}`
  try {
    await navigator.clipboard.writeText(value)
    ElMessage.success('路径已复制')
  } catch {
    ElMessage.info(value)
  }
}
async function loadForms() {
  const data = await request(`/api/forms/submissions?page=${formPager.page}&page_size=${formPager.page_size}`)
  forms.value = data.items || []
  formPager.total = data.pagination?.total || forms.value.length
}
function changeFormPage(page: number) {
  formPager.page = page
  loadForms()
}
async function loadVersions() {
  const data = await request('/api/site/publish-versions')
  versions.value = data.items || data || []
}
async function generateSite() {
  generating.value = true
  try {
    const data = await request('/api/site/generate', { method: 'POST' })
    publishResult.value = data || { message: '静态站已生成' }
    ElMessage.success('静态站已生成')
    await loadVersions()
  } finally {
    generating.value = false
  }
}
function openPublishVersion(row: any) {
  Object.assign(publishDetail, row)
  publishDrawerVisible.value = true
}
function previewSite() {
  window.open('/', '_blank')
}

function paymentLabel(value: string) {
  return ({ pending: '待支付', paid: '已支付', refunded: '已退款', failed: '失败' } as any)[value] || value || '-'
}
function fulfillLabel(value: string) {
  return ({ new: '新订单', confirmed: '已确认', shipped: '已发货', finished: '已完成', closed: '已关闭' } as any)[value] || value || '-'
}
function pretty(value: string) {
  try { return JSON.stringify(JSON.parse(value || '{}'), null, 2) } catch { return value || '-' }
}

onMounted(() => {
  if (token.value) loadAll().catch((error) => ElMessage.error(error.message))
})
</script>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  components: {
    MetricCard: {
      props: ['title', 'value', 'note', 'icon', 'suffix'],
      template: `
        <el-card class="metric-card" shadow="never">
          <div class="metric-top">
            <span>{{ title }} <em v-if="suffix">{{ suffix }}</em></span>
            <el-icon><component :is="icon" /></el-icon>
          </div>
          <strong>{{ value }}</strong>
          <small>{{ note }}</small>
        </el-card>
      `
    },
    ContentEditor: {
      props: ['type', 'items', 'form', 'page', 'pageSize', 'total', 'media'],
      emits: ['new', 'edit', 'save', 'delete', 'ai', 'page-change'],
      data() {
        return { prompt: '', drawerVisible: false, mediaDrawerVisible: false }
      },
      computed: {
        title() { return this.type === 'article' ? '文章' : '商品' },
        bodyField() { return this.type === 'article' ? 'content' : 'description' }
      },
      methods: {
        openNew() {
          this.$emit('new')
          this.drawerVisible = true
        },
        openEdit(row: any) {
          this.$emit('edit', row)
          this.drawerVisible = true
        },
        saveForm() {
          this.$emit('save')
        },
        selectCover(item: any) {
          this.form.cover = item.file_path
          this.mediaDrawerVisible = false
        }
      },
      template: `
        <div>
          <el-card class="panel" shadow="never">
            <template #header><div class="card-head"><strong>{{ title }}列表</strong><el-button type="primary" @click="openNew">新建{{ title }}</el-button></div></template>
            <el-table :data="items" height="650">
              <el-table-column prop="title" label="标题" min-width="260">
                <template #default="{ row }"><strong>{{ row.title }}</strong><br /><small>{{ row.slug }}</small></template>
              </el-table-column>
              <el-table-column v-if="type === 'product'" prop="sku" label="SKU" width="140" />
              <el-table-column v-if="type === 'product'" label="价格/库存" width="150">
                <template #default="{ row }">{{ row.price || 0 }} / {{ row.stock || 0 }}</template>
              </el-table-column>
              <el-table-column prop="status" label="状态" width="110" />
              <el-table-column label="操作" width="160">
                <template #default="{ row }">
                  <el-button link type="primary" @click="openEdit(row)">编辑</el-button>
                  <el-button link type="danger" @click="$emit('delete', row)">删除</el-button>
                </template>
              </el-table-column>
            </el-table>
            <el-pagination class="table-pager" layout="prev, pager, next, total" :current-page="page" :page-size="pageSize" :total="total" @current-change="$emit('page-change', $event)" />
          </el-card>
          <el-drawer v-model="drawerVisible" :title="(form.id ? '编辑' : '新建') + title" size="620px">
              <el-form :model="form" label-width="90px">
                <el-alert type="info" show-icon :closable="false" class="mb16" title="AI 生成入口保留，内容会填充到当前表单。" />
                <el-form-item label="AI 要求"><el-input v-model="prompt" placeholder="输入生成要求" /></el-form-item>
                <el-form-item><el-button @click="$emit('ai', prompt)">AI 生成草稿</el-button></el-form-item>
                <el-form-item label="标题"><el-input v-model="form.title" /></el-form-item>
                <el-form-item label="Slug"><el-input v-model="form.slug" /></el-form-item>
                <el-form-item v-if="type === 'product'" label="SKU"><el-input v-model="form.sku" /></el-form-item>
                <el-form-item label="封面">
                  <div class="cover-field">
                    <el-input v-model="form.cover" placeholder="选择或输入图片路径" />
                    <el-button @click="mediaDrawerVisible = true">选择图片</el-button>
                  </div>
                  <img v-if="form.cover" class="cover-preview" :src="form.cover.startsWith('/') ? form.cover : '/' + form.cover" />
                </el-form-item>
                <el-form-item label="摘要"><el-input v-model="form.summary" type="textarea" :rows="3" /></el-form-item>
                <el-form-item :label="type === 'article' ? '正文' : '描述'"><el-input v-model="form[bodyField]" type="textarea" :rows="7" /></el-form-item>
                <el-row v-if="type === 'product'" :gutter="12">
                  <el-col :span="12"><el-form-item label="价格"><el-input-number v-model="form.price" :min="0" /></el-form-item></el-col>
                  <el-col :span="12"><el-form-item label="库存"><el-input-number v-model="form.stock" :min="0" /></el-form-item></el-col>
                </el-row>
                <el-form-item label="状态"><el-select v-model="form.status"><el-option label="草稿" value="draft" /><el-option label="发布" value="published" /></el-select></el-form-item>
                <el-button type="primary" @click="saveForm">保存{{ title }}</el-button>
              </el-form>
          </el-drawer>
          <el-drawer v-model="mediaDrawerVisible" title="选择封面图片" size="520px">
            <div class="picker-grid">
              <button v-for="item in media" :key="item.id" type="button" class="picker-card" @click="selectCover(item)">
                <img :src="'/' + item.file_path" />
                <span>{{ item.file_name }}</span>
              </button>
            </div>
            <el-empty v-if="!media || !media.length" description="媒体库暂无图片" />
          </el-drawer>
        </div>
      `
    }
  }
})
</script>
