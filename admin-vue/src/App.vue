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
          <small>客户中台</small>
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
            <el-select v-model="currentSiteId" class="site-switcher" placeholder="选择站点" @change="switchSite">
              <el-option v-for="item in sites" :key="item.id" :label="item.name" :value="item.id">
                <span>{{ item.name }}</span>
                <small class="option-domain">{{ item.domain || item.subdomain || item.site_key }}</small>
              </el-option>
            </el-select>
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
            <MetricCard title="站点总数" :value="centerOverview.sites || sites.length" note="客户名下前台站点" icon="Grid" />
            <MetricCard title="今日访客" :value="metrics.today_visitors || 0" :note="`浏览 ${metrics.today_views || 0} 次`" icon="User" />
            <MetricCard title="访问深度" :value="metrics.visit_depth || 0" note="人均浏览页数" icon="TrendCharts" />
            <MetricCard title="今日支付金额" :value="metrics.today_paid_amount || '0.00'" :suffix="`/ ${metrics.currency || 'CNY'}`" note="已支付订单金额" icon="Money" />
            <MetricCard title="待处理订单" :value="centerOverview.pending_orders || metrics.pending_orders || 0" :note="`全部订单 ${centerOverview.orders || totals.orders || 0}`" icon="Tickets" />
            <MetricCard title="待处理询盘" :value="centerOverview.pending_forms || 0" :note="`全部留言 ${centerOverview.forms || totals.forms || 0}`" icon="ChatLineRound" />
            <MetricCard title="内容库文章" :value="centerOverview.articles || totals.articles" note="可分发到多个站点" icon="Document" />
            <MetricCard title="商品库商品" :value="centerOverview.products || totals.products" note="可发布到多个前台" icon="Goods" />
          </div>
          <el-card class="panel" shadow="never">
            <template #header><strong>全局任务池</strong></template>
            <el-table :data="sites" row-key="id">
              <el-table-column label="站点" min-width="220">
                <template #default="{ row }">
                  <strong>{{ row.name }}</strong><br />
                  <small>{{ row.domain || row.subdomain || row.site_key }}</small>
                </template>
              </el-table-column>
              <el-table-column label="文章" width="90"><template #default="{ row }">{{ row.stats?.articles || 0 }}</template></el-table-column>
              <el-table-column label="商品" width="90"><template #default="{ row }">{{ row.stats?.products || 0 }}</template></el-table-column>
              <el-table-column label="订单" width="90"><template #default="{ row }">{{ row.stats?.orders || 0 }}</template></el-table-column>
              <el-table-column label="待处理" width="110"><template #default="{ row }">{{ row.stats?.pending_orders || 0 }}</template></el-table-column>
              <el-table-column label="询盘" width="90"><template #default="{ row }">{{ row.stats?.forms || 0 }}</template></el-table-column>
              <el-table-column label="最近发布" min-width="180">
                <template #default="{ row }">
                  <span>{{ row.publish?.last_created_at || '尚未生成' }}</span><br />
                  <small>{{ row.publish?.last_version || row.publish?.public_path || '-' }}</small>
                </template>
              </el-table-column>
              <el-table-column label="状态" width="110"><template #default="{ row }"><el-tag :type="row.status === 'active' ? 'success' : 'info'">{{ row.status }}</el-tag></template></el-table-column>
              <el-table-column label="操作" width="260">
                <template #default="{ row }">
                  <el-button link type="primary" @click="openSite(row)">进入</el-button>
                  <el-button link type="primary" @click="previewSite(row)">预览</el-button>
                  <el-button link type="primary" @click="editSite(row)">编辑</el-button>
                  <el-button link :type="row.status === 'active' ? 'warning' : 'success'" @click="toggleSiteStatus(row)">
                    {{ row.status === 'active' ? '停用' : '恢复' }}
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </section>

        <section v-if="view === 'sites'">
          <el-row :gutter="16">
            <el-col :span="16">
              <el-card class="panel" shadow="never">
                <template #header>
                  <div class="card-head">
                    <strong>站点工作台</strong>
                    <div class="head-actions">
                      <el-button :loading="siteBatchRunning" @click="selectAllActiveSites">选择启用站点</el-button>
                      <el-button :loading="siteBatchRunning" @click="runSiteBatch('deploy-check')">批量检查部署</el-button>
                      <el-button :loading="siteBatchRunning" @click="runSiteBatch('package')">批量发布包</el-button>
                      <el-button type="primary" :loading="siteBatchRunning" @click="runSiteBatch('generate')">批量生成</el-button>
                      <el-button type="primary" @click="newSite">新建站点</el-button>
                    </div>
                  </div>
                </template>
                <div class="site-batchbar">
                  <el-checkbox v-model="allVisibleSitesSelected" :indeterminate="someVisibleSitesSelected">全选当前站点</el-checkbox>
                  <span>已选择 {{ selectedSiteIds.length }} 个站点</span>
                  <el-button v-if="selectedSiteIds.length" link type="primary" @click="selectedSiteIds = []">清空</el-button>
                </div>
                <el-alert v-if="siteBatchResults.length" class="mb16" type="info" show-icon :title="siteBatchSummary">
                  <template #default>
                    <div class="batch-result-list">
                      <span v-for="item in siteBatchResults" :key="`${item.site_id}-${item.action}`">
                        {{ item.site_name }}：{{ item.ok ? '完成' : '失败' }}{{ item.message ? ` - ${item.message}` : '' }}
                      </span>
                    </div>
                  </template>
                </el-alert>
                <div class="site-grid">
                  <article v-for="item in sites" :key="item.id" class="site-card" :class="{ active: item.id === currentSiteId }" @click="openSite(item)">
                    <div class="site-card-head">
                      <div>
                        <el-checkbox v-model="selectedSiteIds" :label="item.id" @click.stop>{{ item.name }}</el-checkbox>
                        <small>{{ item.domain || item.subdomain || item.site_key }}</small>
                      </div>
                      <el-tag size="small" :type="item.status === 'active' ? 'success' : 'info'">{{ item.status }}</el-tag>
                    </div>
                    <div class="site-stats">
                      <span>文章 {{ item.stats?.articles || 0 }}</span>
                      <span>商品 {{ item.stats?.products || 0 }}</span>
                      <span>订单 {{ item.stats?.orders || 0 }}</span>
                      <span>待处理 {{ item.stats?.pending_orders || 0 }}</span>
                    </div>
                    <div class="site-publish">
                      <span>{{ item.publish?.generated ? '已生成静态站' : '待生成静态站' }} · {{ deployReady(item) ? '部署已配置' : '部署待配置' }}</span>
                      <small>{{ item.publish?.last_created_at || item.publish?.public_path || '-' }}</small>
                    </div>
                    <div class="site-actions" @click.stop>
                      <el-button size="small" type="primary" plain @click="openSite(item)">进入</el-button>
                      <el-button size="small" :loading="generating && String(currentSiteId) === String(item.id)" @click="generateSiteFor(item)">生成</el-button>
                      <el-button size="small" @click="previewSite(item)">预览</el-button>
                      <el-button size="small" @click="editSite(item)">编辑</el-button>
                      <el-button size="small" :type="item.status === 'active' ? 'warning' : 'success'" plain @click="toggleSiteStatus(item)">
                        {{ item.status === 'active' ? '停用' : '恢复' }}
                      </el-button>
                    </div>
                  </article>
                </div>
              </el-card>
            </el-col>
            <el-col :span="8">
              <el-card class="panel" shadow="never">
                <template #header><strong>中台能力规划</strong></template>
                <div class="capability-list">
                  <span>统一订单中心</span>
                  <span>统一询盘处理</span>
                  <span>内容库多站点分发</span>
                  <span>商品库多站点上架</span>
                  <span>全局支付通道</span>
                  <span>批量生成与部署</span>
                </div>
              </el-card>
            </el-col>
          </el-row>
          <el-drawer v-model="siteDrawerVisible" size="480px" :title="siteEditingId ? '编辑站点' : '新建站点'">
            <el-form :model="siteForm" label-width="92px">
              <el-form-item label="站点名称"><el-input v-model="siteForm.name" placeholder="例如：农业无人机英文站" /></el-form-item>
              <el-form-item label="绑定域名"><el-input v-model="siteForm.domain" placeholder="例如：www.example.com" /></el-form-item>
              <el-form-item label="备用域名"><el-input v-model="siteForm.subdomain" placeholder="例如：site10002.huajian.local" /></el-form-item>
              <el-form-item label="站点语言">
                <el-select v-model="siteForm.language">
                  <el-option label="中文" value="zh-CN" />
                  <el-option label="英文" value="en-US" />
                </el-select>
              </el-form-item>
              <el-form-item label="模板">
                <el-select v-model="siteForm.template_key">
                  <el-option v-for="item in templates" :key="item.key" :label="item.name" :value="item.key" />
                </el-select>
              </el-form-item>
              <el-form-item label="站点状态">
                <el-select v-model="siteForm.status">
                  <el-option label="启用" value="active" />
                  <el-option label="停用" value="disabled" />
                  <el-option label="归档" value="archived" />
                </el-select>
              </el-form-item>
              <el-divider content-position="left">部署配置</el-divider>
              <el-form-item label="面板地址"><el-input v-model="siteForm.deploy.bt_panel_url" placeholder="https://server:8888" /></el-form-item>
              <el-form-item label="站点目录"><el-input v-model="siteForm.deploy.site_path" placeholder="/www/wwwroot/example.com" /></el-form-item>
              <el-form-item label="部署模式">
                <el-select v-model="siteForm.deploy.mode">
                  <el-option label="手动发布" value="manual" />
                  <el-option label="发布包上传" value="package" />
                  <el-option label="宝塔 API" value="bt-api" />
                </el-select>
              </el-form-item>
              <el-form-item label="发布后动作"><el-input v-model="siteForm.deploy.after_action" placeholder="reload_nginx" /></el-form-item>
              <el-form-item label="部署备注"><el-input v-model="siteForm.deploy.note" type="textarea" :rows="2" placeholder="记录服务器、目录、证书和备份策略" /></el-form-item>
              <el-button type="primary" :loading="siteCreating" @click="saveSite">{{ siteEditingId ? '保存站点' : '创建站点' }}</el-button>
            </el-form>
          </el-drawer>
        </section>

        <section v-if="view === 'settings'">
          <el-card class="panel" shadow="never">
            <template #header>
              <div class="card-head">
                <strong>站点设置</strong>
                <div class="head-actions">
                  <el-button @click="loadStaticPages">刷新页面列表</el-button>
                  <el-button @click="saveSettingsAsDefault">保存为公共默认</el-button>
                  <el-button @click="applySettingsToAll">应用到全部站点</el-button>
                  <el-button @click="saveSettings">保存当前站点</el-button>
                  <el-button type="primary" :loading="generating" @click="saveSettingsAndGenerate">保存并生成静态站</el-button>
                </div>
              </div>
            </template>
            <el-form :model="site" label-width="120px" class="wide-form">
              <el-alert
                class="mb16"
                type="info"
                show-icon
                :closable="false"
                :title="`当前编辑：${currentSite?.name || site.name || '默认站点'}，${settingsScopeText}。普通保存只影响当前站点；需要统一菜单时再使用“应用到全部站点”。`"
              />
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
              <el-divider content-position="left">导航菜单</el-divider>
              <el-table :data="site.nav" row-key="id" class="mb16">
                <el-table-column label="标题" min-width="160">
                  <template #default="{ row }"><el-input v-model="row.title" placeholder="例如：首页" /></template>
                </el-table-column>
                <el-table-column label="链接页面" min-width="300">
                  <template #default="{ row }">
                    <el-select
                      v-model="row.url"
                      filterable
                      allow-create
                      clearable
                      default-first-option
                      placeholder="选择静态页面或输入外链"
                      class="nav-page-select"
                      @change="onNavUrlChange(row)"
                    >
                      <el-option-group
                        v-for="group in staticPageGroups"
                        :key="group.type"
                        :label="group.label"
                      >
                        <el-option
                          v-for="page in group.items"
                          :key="`${group.type}-${page.url}`"
                          :label="page.title"
                          :value="page.url"
                        >
                          <div class="page-option">
                            <span>{{ page.title }}</span>
                            <small>{{ page.url }}</small>
                          </div>
                        </el-option>
                      </el-option-group>
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column label="新窗口" width="90">
                  <template #default="{ row }"><el-switch v-model="row.target_blank" /></template>
                </el-table-column>
                <el-table-column label="操作" width="170">
                  <template #default="{ $index }">
                    <el-button link type="primary" @click="moveNavItem($index, -1)">上移</el-button>
                    <el-button link type="primary" @click="moveNavItem($index, 1)">下移</el-button>
                    <el-button link type="danger" @click="removeNavItem($index)">删除</el-button>
                  </template>
                </el-table-column>
              </el-table>
              <el-button @click="addNavItem">新增导航</el-button>
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
                      <el-button :disabled="!pagePlan" :loading="pagePlanPublishing" @click="applyPagePlanSaveGeneratePreview">保存并生成预览</el-button>
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
                    <div class="builder-screen">
                      <div class="builder-screen-hero">
                        <span>{{ site.brand || 'ZeroShop' }}</span>
                        <h3>{{ pagePlan.hero?.title || site.hero?.title || '品牌独立站首页' }}</h3>
                        <p>{{ pagePlan.hero?.subtitle || site.hero?.subtitle || pagePlan.summary }}</p>
                      </div>
                      <div class="builder-screen-modules">
                        <article v-for="item in (pagePlan.home_modules || []).filter((module: any) => module.enabled !== false).slice(0, 5)" :key="item.key">
                          <span>{{ item.title || moduleTitle(item.key) }}</span>
                        </article>
                      </div>
                      <div class="builder-screen-content">
                        <small v-for="item in (pagePlan.home_content?.advantages || []).slice(0, 3)" :key="item.title || item">{{ item.title || item }}</small>
                      </div>
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
                  <el-form-item label="发布范围">
                    <el-radio-group v-model="aiForm.site_scope" @change="syncAiSiteScope">
                      <el-radio-button label="current">当前站点</el-radio-button>
                      <el-radio-button label="all">全部站点</el-radio-button>
                      <el-radio-button label="selected">指定站点</el-radio-button>
                    </el-radio-group>
                  </el-form-item>
                  <el-form-item v-if="aiForm.site_scope === 'selected'" label="选择站点">
                    <el-select v-model="aiForm.site_ids" multiple filterable collapse-tags collapse-tags-tooltip placeholder="选择要发布到的站点">
                      <el-option v-for="item in sites" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                  </el-form-item>
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
                    <el-button :loading="aiTaskLoading" @click="createAiTask">生成任务</el-button>
                    <el-button :loading="aiBatchLoading" @click="batchCreateAiContent">批量入库</el-button>
                  </el-form-item>
                </el-form>
              </el-card>
              <el-card class="panel mt16" shadow="never">
                <template #header><strong>AI 接口配置</strong></template>
                <el-form :model="site.ai" label-width="96px">
                  <el-form-item label="服务商"><el-input v-model="site.ai.provider" placeholder="OpenAI / DeepSeek / 通义千问" /></el-form-item>
                  <el-form-item label="模型名称"><el-input v-model="site.ai.model" placeholder="例如：gpt-4.1-mini" /></el-form-item>
                  <el-form-item label="API 地址"><el-input v-model="site.ai.endpoint" placeholder="https://api.example.com/v1/chat/completions" /></el-form-item>
                  <el-form-item label="API Key"><el-input v-model="site.ai.api_key" type="password" show-password /></el-form-item>
                  <div class="form-actions">
                    <el-button @click="saveSettings">保存当前站点 AI</el-button>
                    <el-button @click="saveSettingsAsDefault">保存为公共默认</el-button>
                  </div>
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
              <el-card class="panel mt16" shadow="never">
                <template #header>
                  <div class="card-head">
                    <strong>AI 任务记录</strong>
                    <el-button @click="loadAiTasks">刷新任务</el-button>
                  </div>
                </template>
                <el-table :data="aiTasks" height="320" row-key="id">
                  <el-table-column label="任务" min-width="260">
                    <template #default="{ row }">
                      <strong>{{ aiTaskTypeLabel(row.task_type) }}</strong><br />
                      <small>{{ row.prompt }}</small>
                    </template>
                  </el-table-column>
                  <el-table-column prop="status" label="状态" width="110" />
                  <el-table-column label="结果" width="100">
                    <template #default="{ row }">{{ row.result_json?.length || row.success_count || 0 }} 条</template>
                  </el-table-column>
                  <el-table-column prop="created_at" label="时间" width="170" />
                  <el-table-column label="操作" width="250">
                    <template #default="{ row }">
                      <el-button link type="primary" :disabled="row.status !== 'success'" @click="confirmAiTask(row, 'save_draft')">存草稿</el-button>
                      <el-button link type="success" :disabled="row.status !== 'success'" @click="confirmAiTask(row, 'publish')">发布</el-button>
                      <el-button link type="danger" :disabled="row.status !== 'success'" @click="confirmAiTask(row, 'discard')">丢弃</el-button>
                      <el-button link type="danger" @click="deleteAiTask(row)">删除</el-button>
                    </template>
                  </el-table-column>
                </el-table>
                <el-pagination class="table-pager" layout="prev, pager, next, total" :current-page="aiTaskPager.page" :page-size="aiTaskPager.page_size" :total="aiTaskPager.total" @current-change="changeAiTaskPage" />
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
            :sites="sites"
            :categories="categories"
            :product-categories="productCategories"
            :current-site-id="currentSiteId"
            @new="newArticle"
            @edit="editArticle"
            @save="saveArticle"
            @delete="deleteArticle"
            @ai="generateArticleDraft"
            @page-change="changeArticlePage"
          />
        </section>

        <section v-if="view === 'pages'">
          <ContentEditor
            type="page"
            :items="pages"
            :form="pageForm"
            :page="pagePager.page"
            :page-size="pagePager.page_size"
            :total="pagePager.total"
            :media="imageMedia"
            :sites="sites"
            :categories="categories"
            :product-categories="productCategories"
            :current-site-id="currentSiteId"
            @new="newPage"
            @edit="editPage"
            @save="savePage"
            @delete="deletePage"
            @ai="generatePageDraft"
            @page-change="changePagePage"
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
            :sites="sites"
            :categories="categories"
            :product-categories="productCategories"
            :current-site-id="currentSiteId"
            @new="newProduct"
            @edit="editProduct"
            @save="saveProduct"
            @delete="deleteProduct"
            @ai="generateProductDraft"
            @page-change="changeProductPage"
          />
        </section>

        <section v-if="view === 'categories'">
          <el-row :gutter="16">
            <el-col :span="12">
              <el-card class="panel" shadow="never">
                <template #header>
                  <div class="card-head">
                    <strong>文章分类</strong>
                    <el-button type="primary" @click="newCategory('article')">新建分类</el-button>
                  </div>
                </template>
                <el-table :data="categories" height="620" row-key="id">
                  <el-table-column prop="name" label="分类名称" min-width="160">
                    <template #default="{ row }"><strong>{{ row.name }}</strong><br /><small>{{ row.slug }}</small></template>
                  </el-table-column>
                  <el-table-column prop="sort_order" label="排序" width="90" />
                  <el-table-column prop="description" label="说明" min-width="180" />
                  <el-table-column label="操作" width="140">
                    <template #default="{ row }">
                      <el-button link type="primary" @click="editCategory('article', row)">编辑</el-button>
                      <el-button link type="danger" @click="deleteCategory('article', row)">删除</el-button>
                    </template>
                  </el-table-column>
                </el-table>
              </el-card>
            </el-col>
            <el-col :span="12">
              <el-card class="panel" shadow="never">
                <template #header>
                  <div class="card-head">
                    <strong>商品分类</strong>
                    <el-button type="primary" @click="newCategory('product')">新建分类</el-button>
                  </div>
                </template>
                <el-table :data="productCategories" height="620" row-key="id">
                  <el-table-column prop="name" label="分类名称" min-width="160">
                    <template #default="{ row }"><strong>{{ row.name }}</strong><br /><small>{{ row.slug }}</small></template>
                  </el-table-column>
                  <el-table-column prop="sort_order" label="排序" width="90" />
                  <el-table-column prop="description" label="说明" min-width="180" />
                  <el-table-column label="操作" width="140">
                    <template #default="{ row }">
                      <el-button link type="primary" @click="editCategory('product', row)">编辑</el-button>
                      <el-button link type="danger" @click="deleteCategory('product', row)">删除</el-button>
                    </template>
                  </el-table-column>
                </el-table>
              </el-card>
            </el-col>
          </el-row>
          <el-drawer v-model="categoryDrawerVisible" size="560px" :title="(categoryForm.id ? '编辑' : '新建') + (categoryType === 'article' ? '文章分类' : '商品分类')">
            <el-form :model="categoryForm" label-width="96px">
              <el-form-item label="分类名称"><el-input v-model="categoryForm.name" /></el-form-item>
              <el-form-item label="Slug"><el-input v-model="categoryForm.slug" /></el-form-item>
              <el-form-item label="上级分类">
                <el-select v-model="categoryForm.parent_id" clearable filterable placeholder="无上级分类">
                  <el-option label="无上级分类" :value="0" />
                  <el-option v-for="item in currentCategoryOptions" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
              </el-form-item>
              <el-form-item v-if="categoryType === 'product'" label="封面图"><el-input v-model="categoryForm.cover" /></el-form-item>
              <el-form-item label="排序"><el-input-number v-model="categoryForm.sort_order" :min="0" /></el-form-item>
              <el-form-item label="说明"><el-input v-model="categoryForm.description" type="textarea" :rows="3" /></el-form-item>
              <el-form-item label="SEO 标题"><el-input v-model="categoryForm.seo_title" /></el-form-item>
              <el-form-item label="SEO 关键词"><el-input v-model="categoryForm.seo_keywords" /></el-form-item>
              <el-form-item label="SEO 描述"><el-input v-model="categoryForm.seo_description" type="textarea" :rows="3" /></el-form-item>
              <div class="drawer-actions">
                <el-button @click="categoryDrawerVisible = false">取消</el-button>
                <el-button type="primary" @click="saveCategory">保存分类</el-button>
              </div>
            </el-form>
          </el-drawer>
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
              <el-form-item><el-select v-model="operationSiteScope" placeholder="站点范围" style="width: 180px" @change="applyOperationSiteScope"><el-option label="全部站点" value="all" /><el-option v-for="item in sites" :key="item.id" :label="item.name" :value="String(item.id)" /></el-select></el-form-item>
              <el-form-item><el-input v-model="orderFilters.keyword" placeholder="订单号/客户/手机号" clearable /></el-form-item>
              <el-form-item><el-select v-model="orderFilters.payment_status" placeholder="支付" clearable><el-option label="待支付" value="pending" /><el-option label="已支付" value="paid" /></el-select></el-form-item>
              <el-form-item><el-select v-model="orderFilters.fulfillment_status" placeholder="履约" clearable><el-option label="新订单" value="new" /><el-option label="已确认" value="confirmed" /><el-option label="已发货" value="shipped" /><el-option label="已完成" value="finished" /></el-select></el-form-item>
              <el-button type="primary" @click="applyOrderFilters">筛选</el-button>
            </el-form>
            <el-table :data="orders" height="560" row-key="id" highlight-current-row @row-click="selectOrder">
              <el-table-column prop="site_name" label="站点" width="150" />
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
              <el-form-item><el-select v-model="operationSiteScope" placeholder="站点范围" style="width: 180px" @change="applyOperationSiteScope"><el-option label="全部站点" value="all" /><el-option v-for="item in sites" :key="item.id" :label="item.name" :value="String(item.id)" /></el-select></el-form-item>
              <el-form-item><el-input v-model="serviceFilters.keyword" placeholder="搜索订单/客户/请求内容" clearable /></el-form-item>
              <el-form-item><el-select v-model="serviceFilters.status" placeholder="状态" clearable><el-option label="待处理" value="pending" /><el-option label="已处理" value="handled" /></el-select></el-form-item>
              <el-form-item><el-select v-model="serviceFilters.type" placeholder="类型" clearable><el-option label="催发货" value="催发货" /><el-option label="改地址" value="修改收货信息" /><el-option label="售后" value="售后问题" /><el-option label="其他" value="其他服务" /></el-select></el-form-item>
              <el-button type="primary" @click="loadServices">筛选</el-button>
            </el-form>
            <el-table :data="services" height="600" @selection-change="selectedServiceIds = $event.map((item: any) => item.id)">
              <el-table-column type="selection" width="48" :selectable="(row: any) => row.status === 'pending'" />
              <el-table-column prop="site_name" label="站点" width="150" />
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
            <el-form :inline="true" class="toolbar" @submit.prevent="applyFormFilters">
              <el-form-item><el-select v-model="operationSiteScope" placeholder="站点范围" style="width: 180px" @change="applyOperationSiteScope"><el-option label="全部站点" value="all" /><el-option v-for="item in sites" :key="item.id" :label="item.name" :value="String(item.id)" /></el-select></el-form-item>
              <el-form-item><el-input v-model="formFilters.keyword" placeholder="搜索来源/内容" clearable /></el-form-item>
              <el-form-item><el-select v-model="formFilters.status" placeholder="状态" clearable><el-option label="新线索" value="new" /><el-option label="待处理" value="pending" /><el-option label="已处理" value="handled" /></el-select></el-form-item>
              <el-button type="primary" @click="applyFormFilters">筛选</el-button>
            </el-form>
            <el-table :data="forms" height="650" row-key="id" highlight-current-row @row-click="openFormSubmission">
              <el-table-column prop="site_name" label="站点" width="150" />
              <el-table-column prop="form_key" label="来源" width="140" />
              <el-table-column label="内容" min-width="340"><template #default="{ row }"><pre>{{ pretty(row.data) }}</pre></template></el-table-column>
              <el-table-column prop="status" label="状态" width="120" />
              <el-table-column prop="created_at" label="时间" width="170" />
              <el-table-column label="Action" width="110"><template #default="{ row }"><el-button link type="primary" @click.stop="openFormSubmission(row)">Handle</el-button></template></el-table-column>
            </el-table>
            <el-pagination class="table-pager" layout="prev, pager, next, total" :current-page="formPager.page" :page-size="formPager.page_size" :total="formPager.total" @current-change="changeFormPage" />
          </el-card>
          <el-drawer v-model="formDrawerVisible" size="560px" title="Inquiry Detail">
            <el-empty v-if="!formDetail.id" description="Select one inquiry" />
            <el-form v-else :model="formDetail" label-width="92px">
              <el-descriptions :column="1" border class="mb16">
                <el-descriptions-item label="Site">{{ formDetail.site_name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="Source">{{ formDetail.form_key || '-' }}</el-descriptions-item>
                <el-descriptions-item label="Page">{{ formDetail.source_url || '-' }}</el-descriptions-item>
                <el-descriptions-item label="Time">{{ formDetail.created_at || '-' }}</el-descriptions-item>
              </el-descriptions>
              <el-form-item label="Content"><pre class="form-detail-json">{{ pretty(formDetail.data) }}</pre></el-form-item>
              <el-form-item label="Status">
                <el-select v-model="formDetail.status">
                  <el-option label="New" value="new" />
                  <el-option label="Pending" value="pending" />
                  <el-option label="Contacted" value="contacted" />
                  <el-option label="Handled" value="handled" />
                  <el-option label="Invalid" value="invalid" />
                </el-select>
              </el-form-item>
              <el-form-item label="Remark"><el-input v-model="formDetail.remark" type="textarea" :rows="4" placeholder="Record follow-up notes, quote status, customer needs" /></el-form-item>
              <div class="drawer-actions">
                <el-button type="primary" @click="saveFormSubmission">Save</el-button>
                <el-button type="danger" plain @click="deleteFormSubmission">Delete</el-button>
              </div>
            </el-form>
          </el-drawer>
        </section>

        <section v-if="view === 'collector'">
          <el-row :gutter="16">
            <el-col :span="10">
              <el-card class="panel" shadow="never">
                <template #header>
                  <div class="card-head">
                    <strong>采集源</strong>
                    <el-button type="primary" @click="newCollectorSource">新增采集源</el-button>
                  </div>
                </template>
                <el-form :inline="true" class="toolbar" @submit.prevent="loadCollectorSources">
                  <el-form-item>
                    <el-select v-model="operationSiteScope" placeholder="站点范围" style="width: 180px" @change="loadCollector">
                      <el-option label="全部站点" value="all" />
                      <el-option v-for="item in sites" :key="item.id" :label="item.name" :value="String(item.id)" />
                    </el-select>
                  </el-form-item>
                  <el-form-item><el-input v-model="collectorFilters.keyword" placeholder="搜索采集源" clearable /></el-form-item>
                  <el-button type="primary" @click="loadCollectorSources">筛选</el-button>
                </el-form>
                <el-table :data="collectorSources" height="620" row-key="id">
                  <el-table-column label="采集源" min-width="220">
                    <template #default="{ row }">
                      <strong>{{ row.name }}</strong><br />
                      <small>{{ row.source_type?.toUpperCase() }} / {{ row.site_name || row.site_id }}</small>
                    </template>
                  </el-table-column>
                  <el-table-column prop="last_result" label="最近结果" min-width="160" />
                  <el-table-column label="状态" width="90">
                    <template #default="{ row }"><el-tag :type="row.status === 'active' ? 'success' : 'info'">{{ row.status }}</el-tag></template>
                  </el-table-column>
                  <el-table-column label="操作" width="210">
                    <template #default="{ row }">
                      <el-button link type="primary" :loading="collectorRunningId === row.id" @click="runCollectorSource(row)">采集</el-button>
                      <el-button link type="primary" @click="editCollectorSource(row)">编辑</el-button>
                      <el-button link type="danger" @click="deleteCollectorSource(row)">删除</el-button>
                    </template>
                  </el-table-column>
                </el-table>
                <el-pagination class="table-pager" layout="prev, pager, next, total" :current-page="collectorSourcePager.page" :page-size="collectorSourcePager.page_size" :total="collectorSourcePager.total" @current-change="changeCollectorSourcePage" />
              </el-card>
            </el-col>
            <el-col :span="14">
              <el-card class="panel" shadow="never">
                <template #header>
                  <div class="card-head">
                    <strong>采集记录</strong>
                    <el-button @click="loadCollectorRecords">刷新记录</el-button>
                  </div>
                </template>
                <el-form :inline="true" class="toolbar" @submit.prevent="loadCollectorRecords">
                  <el-form-item><el-input v-model="collectorFilters.keyword" placeholder="搜索标题/摘要" clearable /></el-form-item>
                  <el-form-item>
                    <el-select v-model="collectorFilters.status" placeholder="状态" clearable>
                      <el-option label="草稿" value="draft" />
                      <el-option label="已转文章" value="converted" />
                    </el-select>
                  </el-form-item>
                  <el-button type="primary" @click="loadCollectorRecords">筛选</el-button>
                </el-form>
                <el-table :data="collectorRecords" height="620" row-key="id">
                  <el-table-column label="标题" min-width="260">
                    <template #default="{ row }">
                      <strong>{{ row.title }}</strong><br />
                      <small>{{ row.source_url }}</small>
                    </template>
                  </el-table-column>
                  <el-table-column prop="site_name" label="站点" width="130" />
                  <el-table-column prop="status" label="状态" width="100" />
                  <el-table-column prop="collected_at" label="采集时间" width="170" />
                  <el-table-column label="操作" width="220">
                    <template #default="{ row }">
                      <el-button link type="primary" :disabled="!!row.article_id" @click="publishCollectorRecord(row, 'draft')">转草稿</el-button>
                      <el-button link type="success" :disabled="!!row.article_id" @click="publishCollectorRecord(row, 'published')">发布</el-button>
                      <el-button link type="danger" @click="deleteCollectorRecord(row)">删除</el-button>
                    </template>
                  </el-table-column>
                </el-table>
                <el-pagination class="table-pager" layout="prev, pager, next, total" :current-page="collectorRecordPager.page" :page-size="collectorRecordPager.page_size" :total="collectorRecordPager.total" @current-change="changeCollectorRecordPage" />
              </el-card>
            </el-col>
          </el-row>
          <el-drawer v-model="collectorDrawerVisible" size="560px" title="采集源配置">
            <el-form :model="collectorForm" label-width="96px">
              <el-form-item label="采集源名称"><el-input v-model="collectorForm.name" placeholder="例如：行业协会 RSS" /></el-form-item>
              <el-form-item label="所属站点">
                <el-select v-model="collectorForm.site_id" filterable>
                  <el-option v-for="item in sites" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
              </el-form-item>
              <el-form-item label="采集类型">
                <el-radio-group v-model="collectorForm.source_type">
                  <el-radio-button label="rss">RSS</el-radio-button>
                  <el-radio-button label="url">指定 URL</el-radio-button>
                </el-radio-group>
              </el-form-item>
              <el-form-item label="采集地址"><el-input v-model="collectorForm.url" placeholder="https://example.com/feed.xml" /></el-form-item>
              <el-form-item label="文章分类">
                <el-select v-model="collectorForm.category_id" clearable filterable placeholder="可选">
                  <el-option v-for="item in categories" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
              </el-form-item>
              <el-form-item label="入库方式">
                <el-radio-group v-model="collectorForm.rewrite_mode">
                  <el-radio-button label="draft">转草稿</el-radio-button>
                  <el-radio-button label="published">直接发布</el-radio-button>
                </el-radio-group>
              </el-form-item>
              <el-form-item label="状态">
                <el-select v-model="collectorForm.status">
                  <el-option label="启用" value="active" />
                  <el-option label="停用" value="disabled" />
                </el-select>
              </el-form-item>
              <div class="drawer-actions">
                <el-button @click="collectorDrawerVisible = false">取消</el-button>
                <el-button type="primary" @click="saveCollectorSource">保存采集源</el-button>
              </div>
            </el-form>
          </el-drawer>
        </section>

        <section v-if="view === 'payments'">
          <el-card class="panel mb16" shadow="never">
            <template #header>
              <div class="card-head">
                <strong>当前站点支付配置</strong>
                <div class="head-actions">
                  <el-button @click="saveSettings">保存当前站点支付</el-button>
                  <el-button @click="saveSettingsAsDefault">保存为公共默认</el-button>
                </div>
              </div>
            </template>
            <el-form :model="site.payment" label-width="100px" class="wide-form">
              <el-row :gutter="16">
                <el-col :span="8">
                  <el-form-item label="支付模式">
                    <el-select v-model="site.payment.mode">
                      <el-option label="人工确认" value="manual" />
                      <el-option label="微信支付" value="wechat" />
                      <el-option label="支付宝" value="alipay" />
                      <el-option label="Stripe" value="stripe" />
                      <el-option label="PayPal" value="paypal" />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :span="8"><el-form-item label="默认币种"><el-input v-model="site.payment.currency" /></el-form-item></el-col>
                <el-col :span="8"><el-form-item label="收款账号"><el-input v-model="site.payment.account" /></el-form-item></el-col>
              </el-row>
              <el-form-item label="付款说明"><el-input v-model="site.payment.guide" type="textarea" :rows="3" placeholder="展示给前台订单页的付款方式、备注格式和客服确认流程" /></el-form-item>
            </el-form>
          </el-card>
          <el-card class="panel" shadow="never">
            <template #header>
              <div class="card-head">
                <strong>支付通道</strong>
                <el-button type="primary" @click="newPaymentChannel">新建通道</el-button>
              </div>
            </template>
            <el-alert title="支付通道在客户中台统一维护，可分配给全部站点或指定站点；应用到当前站点后，前台订单页会展示对应付款说明。" type="info" show-icon class="mb16" />
            <el-table :data="paymentChannels" height="620">
              <el-table-column prop="name" label="通道名称" min-width="180">
                <template #default="{ row }"><strong>{{ row.name }}</strong><br /><small>{{ providerLabel(row.provider) }} / {{ row.currency }}</small></template>
              </el-table-column>
              <el-table-column label="适用站点" min-width="220">
                <template #default="{ row }"><small>{{ channelSiteLabel(row) }}</small></template>
              </el-table-column>
              <el-table-column prop="account" label="收款账号" min-width="180" />
              <el-table-column label="状态" width="130">
                <template #default="{ row }"><el-tag :type="row.status === 'active' ? 'success' : 'info'">{{ row.status === 'active' ? '启用' : '停用' }}</el-tag><el-tag v-if="row.is_default" class="ml6">默认</el-tag></template>
              </el-table-column>
              <el-table-column label="操作" width="230">
                <template #default="{ row }">
                  <el-button link type="primary" @click="editPaymentChannel(row)">编辑</el-button>
                  <el-button link type="primary" @click="applyPaymentChannel(row)">应用到当前站</el-button>
                  <el-button link type="danger" @click="deletePaymentChannel(row)">删除</el-button>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
          <el-drawer v-model="paymentDrawerVisible" size="620px" title="支付通道配置">
            <el-form :model="paymentForm" label-width="100px">
              <el-form-item label="通道名称"><el-input v-model="paymentForm.name" placeholder="例如：默认银行转账 / 支付宝收款" /></el-form-item>
              <el-row :gutter="12">
                <el-col :span="12"><el-form-item label="通道类型"><el-select v-model="paymentForm.provider"><el-option label="人工确认" value="manual" /><el-option label="银行转账" value="bank" /><el-option label="微信支付" value="wechat" /><el-option label="支付宝" value="alipay" /><el-option label="Stripe" value="stripe" /><el-option label="PayPal" value="paypal" /></el-select></el-form-item></el-col>
                <el-col :span="12"><el-form-item label="币种"><el-input v-model="paymentForm.currency" /></el-form-item></el-col>
              </el-row>
              <el-form-item label="收款账号"><el-input v-model="paymentForm.account" placeholder="银行账号、支付宝账号、Stripe account id 等" /></el-form-item>
              <el-form-item label="付款说明"><el-input v-model="paymentForm.instructions" type="textarea" :rows="4" placeholder="展示给前台客户的付款步骤、备注格式、客服确认方式" /></el-form-item>
              <el-row :gutter="12">
                <el-col :span="12"><el-form-item label="状态"><el-select v-model="paymentForm.status"><el-option label="启用" value="active" /><el-option label="停用" value="disabled" /></el-select></el-form-item></el-col>
                <el-col :span="12"><el-form-item label="默认通道"><el-switch v-model="paymentForm.is_default" /></el-form-item></el-col>
              </el-row>
              <el-form-item label="适用范围">
                <el-radio-group v-model="paymentForm.scope">
                  <el-radio-button label="all">全部站点</el-radio-button>
                  <el-radio-button label="selected">指定站点</el-radio-button>
                </el-radio-group>
              </el-form-item>
              <el-form-item v-if="paymentForm.scope === 'selected'" label="选择站点">
                <el-select v-model="paymentForm.site_ids" multiple filterable collapse-tags collapse-tags-tooltip placeholder="选择可使用该通道的站点">
                  <el-option v-for="item in sites" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
              </el-form-item>
              <el-form-item label="接口参数">
                <el-input v-model="paymentConfigText" type="textarea" :rows="5" placeholder='{"merchant_id":"","api_key":"","webhook_url":""}' />
              </el-form-item>
              <el-button type="primary" @click="savePaymentChannel">保存通道</el-button>
            </el-form>
          </el-drawer>
        </section>

        <section v-if="view === 'publish'">
          <el-card class="panel mb16" shadow="never">
            <template #header>
              <div class="card-head">
                <strong>当前站点部署配置</strong>
                <div class="head-actions">
                  <el-button @click="saveSettings">保存部署配置</el-button>
                  <el-button @click="saveSettingsAsDefault">保存为公共默认</el-button>
                </div>
              </div>
            </template>
            <el-form :model="site.deploy" label-width="100px" class="wide-form">
              <el-row :gutter="16">
                <el-col :span="12"><el-form-item label="面板地址"><el-input v-model="site.deploy.bt_panel_url" placeholder="https://server:8888" /></el-form-item></el-col>
                <el-col :span="12"><el-form-item label="站点目录"><el-input v-model="site.deploy.site_path" placeholder="/www/wwwroot/example.com" /></el-form-item></el-col>
              </el-row>
              <el-row :gutter="16">
                <el-col :span="12">
                  <el-form-item label="部署模式">
                    <el-select v-model="site.deploy.mode">
                      <el-option label="手动发布" value="manual" />
                      <el-option label="发布包上传" value="package" />
                      <el-option label="宝塔 API" value="bt-api" />
                      <el-option label="FTP/SFTP" value="ftp" />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :span="12"><el-form-item label="发布后动作"><el-input v-model="site.deploy.after_action" placeholder="reload_nginx" /></el-form-item></el-col>
              </el-row>
              <el-form-item label="部署备注"><el-input v-model="site.deploy.note" type="textarea" :rows="2" placeholder="记录服务器、站点、证书和备份策略" /></el-form-item>
            </el-form>
          </el-card>
          <el-card class="panel" shadow="never">
            <template #header>
              <div class="card-head">
                <strong>发布中心</strong>
                <div class="head-actions">
                  <el-button :loading="deployTesting" @click="checkDeployConfig">检查部署配置</el-button>
                  <el-button :loading="packaging" @click="createPackage">生成发布包</el-button>
                  <el-button type="primary" :loading="generating" @click="generateSite">生成静态站</el-button>
                </div>
              </div>
            </template>
            <el-alert :title="`当前发布站点：${currentSite?.name || '默认站点'}，生成、部署检查和发布包都会写入该站点目录。`" type="info" show-icon class="mb16" />
            <el-result v-if="publishResult" class="publish-result" :icon="publishResult.configured === false ? 'warning' : 'success'" :title="publishResultTitle" :sub-title="publishResultSubtitle">
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
              <el-descriptions-item label="文件路径">{{ publishDetail.file_path || '-' }}</el-descriptions-item>
              <el-descriptions-item label="生成文件数">{{ publishSummary.file_count ?? '-' }}</el-descriptions-item>
              <el-descriptions-item label="发布包大小">{{ publishSummary.file_size ? formatFileSize(publishSummary.file_size) : '-' }}</el-descriptions-item>
              <el-descriptions-item label="面板地址">{{ publishSummary.panel_url || '-' }}</el-descriptions-item>
              <el-descriptions-item label="站点目录">{{ publishSummary.site_path || '-' }}</el-descriptions-item>
              <el-descriptions-item label="说明">{{ publishSummary.message || publishDetail.message || publishDetail.remark || '-' }}</el-descriptions-item>
            </el-descriptions>
            <div v-if="publishDetail.publish_type === 'package'" class="drawer-actions">
              <el-button type="primary" @click="downloadPackage(publishDetail.file_path || publishSummary.package_path)">下载发布包</el-button>
            </div>
            <div v-if="publishDetail.publish_type === 'generate' && publishDetail.status === 'success'" class="drawer-actions">
              <el-button type="warning" @click="rollbackVersion(publishDetail)">回滚到此版本</el-button>
            </div>
            <el-alert v-if="publishSummary.output?.length" class="mt16" type="info" show-icon title="生成输出">
              <pre class="output-log">{{ publishSummary.output.join('\n') }}</pre>
            </el-alert>
          </el-drawer>
        </section>

        <section v-if="view === 'tasks'">
          <el-card class="panel" shadow="never">
            <template #header>
              <div class="card-head">
                <strong>任务记录中心</strong>
                <div class="head-actions">
                  <el-button @click="exportBatchTasks">导出 CSV</el-button>
                  <el-button @click="loadBatchTasks">刷新任务</el-button>
                </div>
              </div>
            </template>
            <div class="metric-grid mb16">
              <MetricCard title="任务总数" :value="batchTaskOverview.total || 0" note="当前筛选范围" icon="List" />
              <MetricCard title="站点执行次数" :value="batchTaskOverview.site_runs || 0" note="所有任务站点累计" icon="Grid" />
              <MetricCard title="执行成功率" :value="batchTaskOverview.success_rate || 0" suffix="%" note="按站点执行次数计算" icon="TrendCharts" />
              <MetricCard title="失败站点数" :value="batchTaskOverview.failed_runs || 0" note="可进入详情重试" icon="Warning" />
              <MetricCard title="部分成功任务" :value="batchTaskOverview.partial_tasks || 0" note="需要关注的任务" icon="Tickets" />
              <MetricCard title="最近执行" :value="batchTaskOverview.last_finished_at || '-'" note="最近完成时间" icon="Clock" />
            </div>
            <div class="task-toolbar">
              <el-select v-model="taskFilters.action" clearable placeholder="任务类型" @change="applyTaskFilters">
                <el-option label="生成静态站" value="generate" />
                <el-option label="部署检查" value="deploy-check" />
                <el-option label="生成发布包" value="package" />
              </el-select>
              <el-select v-model="taskFilters.status" clearable placeholder="任务状态" @change="applyTaskFilters">
                <el-option label="成功" value="success" />
                <el-option label="部分成功" value="partial" />
                <el-option label="失败" value="failed" />
              </el-select>
              <el-date-picker v-model="taskFilters.date" type="date" value-format="YYYY-MM-DD" placeholder="执行日期" @change="applyTaskFilters" />
              <el-button @click="resetTaskFilters">重置</el-button>
            </div>
            <el-table :data="batchTasks">
              <el-table-column prop="task_no" label="任务号" min-width="180" />
              <el-table-column label="类型" width="120"><template #default="{ row }">{{ batchActionLabel(row.action) }}</template></el-table-column>
              <el-table-column label="状态" width="110">
                <template #default="{ row }">
                  <el-tag :type="row.status === 'success' ? 'success' : row.status === 'partial' ? 'warning' : 'danger'">{{ row.status }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column label="结果" width="150">
                <template #default="{ row }">{{ row.success_count }}/{{ row.total_count }} 成功</template>
              </el-table-column>
              <el-table-column prop="finished_at" label="完成时间" width="180" />
              <el-table-column label="操作" width="180">
                <template #default="{ row }">
                  <el-button link type="primary" @click="openBatchTask(row)">详情</el-button>
                  <el-button v-if="row.failed_count > 0" link type="warning" :loading="siteBatchRunning" @click="retryFailedTask(row)">重试失败</el-button>
                </template>
              </el-table-column>
            </el-table>
            <el-pagination
              class="table-pager"
              layout="sizes, prev, pager, next, total"
              :current-page="taskPager.page"
              :page-size="taskPager.page_size"
              :page-sizes="[10, 20, 50]"
              :total="taskPager.total"
              @current-change="changeTaskPage"
              @size-change="changeTaskPageSize"
            />
          </el-card>
          <el-drawer v-model="batchTaskDrawerVisible" size="560px" title="任务详情">
            <el-descriptions :column="1" border>
              <el-descriptions-item label="任务号">{{ batchTaskDetail.task_no || '-' }}</el-descriptions-item>
              <el-descriptions-item label="类型">{{ batchActionLabel(batchTaskDetail.action) }}</el-descriptions-item>
              <el-descriptions-item label="状态">{{ batchTaskDetail.status || '-' }}</el-descriptions-item>
              <el-descriptions-item label="站点数量">{{ batchTaskDetail.total_count || 0 }}</el-descriptions-item>
              <el-descriptions-item label="成功/失败">{{ batchTaskDetail.success_count || 0 }} / {{ batchTaskDetail.failed_count || 0 }}</el-descriptions-item>
              <el-descriptions-item label="开始时间">{{ batchTaskDetail.started_at || '-' }}</el-descriptions-item>
              <el-descriptions-item label="完成时间">{{ batchTaskDetail.finished_at || '-' }}</el-descriptions-item>
            </el-descriptions>
            <div class="drawer-actions">
              <el-button v-if="failedBatchTaskResults(batchTaskDetail).length" type="warning" :loading="siteBatchRunning" @click="retryFailedTask(batchTaskDetail)">重试失败站点</el-button>
            </div>
            <div class="batch-detail-list">
              <div class="batch-detail-head">
                <strong>执行结果</strong>
                <el-radio-group v-model="batchTaskResultFilter" size="small">
                  <el-radio-button label="all">全部</el-radio-button>
                  <el-radio-button label="failed">失败</el-radio-button>
                  <el-radio-button label="success">成功</el-radio-button>
                </el-radio-group>
              </div>
              <span v-for="item in filteredBatchTaskResults(batchTaskDetail)" :key="`${item.site_id}-${item.site_name}`" :class="{ failed: !item.ok }">
                {{ item.site_name || item.site_id }}：{{ item.ok ? '完成' : '失败' }}{{ item.message ? ` - ${item.message}` : '' }}
              </span>
              <el-empty v-if="!filteredBatchTaskResults(batchTaskDetail).length" description="当前筛选没有结果" />
            </div>
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
import ContentEditor from './components/ContentEditor.vue'

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
const pages = ref<any[]>([])
const articles = ref<any[]>([])
const products = ref<any[]>([])
const categories = ref<any[]>([])
const productCategories = ref<any[]>([])
const orders = ref<any[]>([])
const services = ref<any[]>([])
const media = ref<any[]>([])
const forms = ref<any[]>([])
const collectorSources = ref<any[]>([])
const collectorRecords = ref<any[]>([])
const paymentChannels = ref<any[]>([])
const versions = ref<any[]>([])
const batchTasks = ref<any[]>([])
const batchTaskOverview = ref<any>({})
const sites = ref<any[]>([])
const centerOverview = ref<any>({})
const currentSiteId = ref<number | string>(10001)
const operationSiteScope = ref('all')
const templates = ref<any[]>([])
const moduleRegistry = ref<any>({ scopes: [], modules: [] })
const staticPages = ref<any[]>([])
const servicePending = ref(0)
const selectedServiceIds = ref<string[]>([])
const orderDrawerVisible = ref(false)
const paymentDrawerVisible = ref(false)
const formDrawerVisible = ref(false)
const collectorDrawerVisible = ref(false)
const publishDrawerVisible = ref(false)
const siteDrawerVisible = ref(false)
const batchTaskDrawerVisible = ref(false)
const categoryDrawerVisible = ref(false)
const publishResult = ref<any>(null)
const deployTesting = ref(false)
const packaging = ref(false)
const siteBatchRunning = ref(false)
const selectedSiteIds = ref<Array<number | string>>([])
const siteBatchResults = ref<any[]>([])
const batchTaskResultFilter = ref('all')

const orderFilters = reactive({ keyword: '', payment_status: '', fulfillment_status: '' })
const serviceFilters = reactive({ keyword: '', status: '', type: '' })
const formFilters = reactive({ keyword: '', status: '' })
const collectorFilters = reactive({ keyword: '', status: '' })
const mediaFilters = reactive({ keyword: '', file_type: '' })
const taskFilters = reactive({ action: '', status: '', date: '' })
const orderDetail = reactive<any>({})
const formDetail = reactive<any>({})
const collectorForm = reactive<any>({})
const paymentForm = reactive<any>({})
const paymentConfigText = ref('')
const publishDetail = reactive<any>({})
const batchTaskDetail = reactive<any>({})
const categoryForm = reactive<any>({})
const categoryType = ref<'article' | 'product'>('article')
const publishSummary = computed(() => parseSummary(publishDetail.summary))
const publishResultTitle = computed(() => {
  if (!publishResult.value) return ''
  if (publishResult.value.configured === false) return '部署配置待完善'
  if (publishResult.value.configured === true) return '部署配置已就绪'
  return '本次生成完成'
})
const publishResultSubtitle = computed(() => {
  if (!publishResult.value) return ''
  return publishResult.value.message || publishResult.value.version_no || (publishResult.value.file_count ? `已生成 ${publishResult.value.file_count} 个文件` : '静态站已生成')
})
const articleForm = reactive<any>({})
const productForm = reactive<any>({})
const pageForm = reactive<any>({})
const siteEditingId = ref<number | string>('')
const emptyDeploy = () => ({ bt_panel_url: '', site_path: '', mode: 'manual', after_action: '', note: '' })
const siteForm = reactive<any>({ name: '', domain: '', subdomain: '', language: 'zh-CN', template_key: 'business-clean', status: 'active', deploy: emptyDeploy() })
const aiForm = reactive<any>({ type: 'article', prompt: '围绕自主品牌商品、行业解决方案和独立站 SEO 关键词生成内容', count: 5, status: 'draft', site_scope: 'current', site_ids: [] })
const pageBuilder = reactive({ prompt: '围绕自主品牌商品、行业解决方案、SEO 内容沉淀和询盘转化，生成一个企业官网 + 博客知识库 + 独立站商城首页方案' })
const articlePager = reactive({ page: 1, page_size: 10, total: 0 })
const productPager = reactive({ page: 1, page_size: 10, total: 0 })
const pagePager = reactive({ page: 1, page_size: 10, total: 0 })
const orderPager = reactive({ page: 1, page_size: 10, total: 0 })
const servicePager = reactive({ page: 1, page_size: 10, total: 0 })
const mediaPager = reactive({ page: 1, page_size: 12, total: 0 })
const formPager = reactive({ page: 1, page_size: 10, total: 0 })
const collectorSourcePager = reactive({ page: 1, page_size: 10, total: 0 })
const collectorRecordPager = reactive({ page: 1, page_size: 10, total: 0 })
const aiTaskPager = reactive({ page: 1, page_size: 10, total: 0 })
const taskPager = reactive({ page: 1, page_size: 10, total: 0 })
const aiDrafts = ref<any[]>([])
const aiTasks = ref<any[]>([])
const pagePlan = ref<any>(null)
const aiLoading = ref(false)
const aiBatchLoading = ref(false)
const aiTaskLoading = ref(false)
const aiCoverLoading = ref('')
const pagePlanLoading = ref(false)
const pagePlanSaving = ref(false)
const pagePlanPublishing = ref(false)
const siteCreating = ref(false)
const collectorRunningId = ref<number | string>('')

const navItems = [
  { key: 'dashboard', label: '概览', hint: '查看运营指标、内容数量和站点状态。', icon: 'Odometer' },
  { key: 'sites', label: '站点', hint: '管理客户名下所有前台站点。', icon: 'Grid' },
  { key: 'settings', label: '设置', hint: '维护当前站点基础信息、SEO、导航和全站页面结构。', icon: 'Setting' },
  { key: 'templates', label: '模板', hint: '选择主题模板，启用首页与全站模块。', icon: 'Grid' },
  { key: 'pages', label: '页面', hint: '管理关于我们、服务介绍、专题落地页等普通静态页面。', icon: 'Files' },
  { key: 'ai', label: 'AI', hint: '批量生成文章、商品文案和封面素材。', icon: 'MagicStick' },
  { key: 'articles', label: '文章', hint: '管理 SEO 文章和知识库内容。', icon: 'Document' },
  { key: 'products', label: '商品', hint: '管理独立站商品与商城展示内容。', icon: 'Goods' },
  { key: 'categories', label: '分类', hint: '管理文章分类和商品分类，生成 SEO 分类聚合页。', icon: 'FolderOpened' },
  { key: 'orders', label: '订单', hint: '处理支付、发货和订单跟进。', icon: 'ShoppingCart' },
  { key: 'service', label: '服务', hint: '集中处理客户服务请求。', icon: 'Service' },
  { key: 'payments', label: '支付', hint: '统一配置收款通道并分配到各站点。', icon: 'Money' },
  { key: 'tasks', label: '任务', hint: '查看批量生成、发布和部署检查的执行记录。', icon: 'List' },
  { key: 'media', label: '媒体库', hint: '上传并复用图片和文件素材。', icon: 'Picture' },
  { key: 'forms', label: '留言', hint: '处理询盘线索和联系表单。', icon: 'ChatLineRound' },
  { key: 'collector', label: '采集', hint: '管理 RSS 和指定 URL 采集，沉淀 SEO 文章草稿。', icon: 'Connection' },
  { key: 'publish', label: '发布', hint: '生成静态站并查看发布记录。', icon: 'Upload' }
]
const currentNav = computed(() => navItems.find((item) => item.key === view.value))
const currentSite = computed(() => sites.value.find((item: any) => String(item.id) === String(currentSiteId.value)))
const settingsScopeText = computed(() => {
  if (String(currentSiteId.value) === '10001') return '正在编辑公共默认配置'
  return site.has_site_override ? '当前站点已有独立配置' : '当前站点正在继承公共默认配置'
})
const staticPageGroups = computed(() => {
  const labels: any = { system: '系统页面', article_category: '文章分类页', article: '文章页面', product_category: '商品分类页', product: '商品页面', custom: '自定义页面' }
  return ['system', 'article_category', 'article', 'product_category', 'product', 'custom']
    .map((type) => ({
      type,
      label: labels[type] || type,
      items: staticPages.value.filter((item: any) => item.type === type)
    }))
    .filter((group) => group.items.length)
})
const authHeaders = computed(() => ({ Authorization: `Bearer ${token.value}` }))
const imageMedia = computed(() => media.value.filter((item) => item.file_type === 'image'))
const homeModuleItems = computed(() => (moduleRegistry.value.modules || []).filter((item: any) => item.scope === 'home'))
const globalModuleItems = computed(() => (moduleRegistry.value.modules || []).filter((item: any) => item.scope === 'global'))
const visibleSiteIds = computed(() => sites.value.map((item: any) => item.id))
const selectedVisibleSiteCount = computed(() => visibleSiteIds.value.filter((id: any) => selectedSiteIds.value.some((selected) => String(selected) === String(id))).length)
const someVisibleSitesSelected = computed(() => selectedVisibleSiteCount.value > 0 && selectedVisibleSiteCount.value < visibleSiteIds.value.length)
const allVisibleSitesSelected = computed({
  get: () => visibleSiteIds.value.length > 0 && selectedVisibleSiteCount.value === visibleSiteIds.value.length,
  set: (checked: boolean) => {
    selectedSiteIds.value = checked ? [...visibleSiteIds.value] : []
  }
})
const siteBatchSummary = computed(() => {
  const total = siteBatchResults.value.length
  const success = siteBatchResults.value.filter((item) => item.ok).length
  const failed = total - success
  return total ? `批量任务完成：成功 ${success} 个，失败 ${failed} 个` : ''
})

function batchActionLabel(action: string) {
  return ({ generate: '生成静态站', 'deploy-check': '部署检查', package: '生成发布包' } as any)[action] || action || '-'
}

async function request(path: string, options: any = {}) {
  const headers = {
    ...(token.value ? { Authorization: `Bearer ${token.value}`, 'X-Site-Id': String(currentSiteId.value || 10001) } : {}),
    ...(options.headers || {})
  }
  const response = await axios({
    url: path,
    method: options.method || 'GET',
    data: options.body ? JSON.parse(options.body) : options.data,
    headers
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
  if (key === 'sites') loadSites()
  if (key === 'templates') Promise.all([loadTemplates(), loadModuleRegistry()])
  if (key === 'pages') loadPages()
  if (key === 'ai') loadAiTasks()
  if (key === 'articles') loadArticles()
  if (key === 'products') loadProducts()
  if (key === 'categories') loadCategories()
  if (key === 'orders') loadOrders()
  if (key === 'service') loadServices()
  if (key === 'payments') loadPaymentChannels()
  if (key === 'tasks') loadBatchTasks()
  if (key === 'media') loadMedia()
  if (key === 'forms') loadForms()
  if (key === 'collector') loadCollector()
  if (key === 'publish') loadVersions()
}

function refreshCurrentView() {
  const loaders: Record<string, () => Promise<void>> = {
    dashboard: loadDashboard,
    sites: loadSites,
    settings: async () => { await Promise.all([loadSettings(), loadStaticPages()]) },
    templates: async () => { await Promise.all([loadTemplates(), loadModuleRegistry(), loadSettings(), loadStaticPages()]) },
    ai: loadAiTasks,
    pages: loadPages,
    articles: async () => { await Promise.all([loadArticles(), loadCategories()]) },
    products: async () => { await Promise.all([loadProducts(), loadCategories()]) },
    categories: loadCategories,
    orders: loadOrders,
    service: loadServices,
    payments: loadPaymentChannels,
    tasks: loadBatchTasks,
    media: loadMedia,
    forms: loadForms,
    collector: loadCollector,
    publish: loadVersions
  }
  loaders[view.value]?.().then(() => ElMessage.success('当前页面已刷新'))
}

function openLegacyAdmin() {
  window.open('/admin.html', '_blank')
}

async function loadAll() {
  await Promise.all([loadSites(), loadDashboard(), loadSettings(), loadStaticPages(), loadTemplates(), loadModuleRegistry(), loadCategories(), loadPages(), loadArticles(), loadProducts(), loadOrders(), loadServices(), loadPaymentChannels(), loadBatchTasks(), loadMedia(), loadForms(), loadCollector(), loadAiTasks(), loadVersions()])
}

async function loadDashboard() {
  const [siteData, articleData, productData, orderData, mediaData, formData, metricData] = await Promise.all([
    request('/api/sites'),
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
  sites.value = siteData.items || []
  centerOverview.value = siteData.overview || {}
  if (!currentSiteId.value) currentSiteId.value = siteData.current_site_id || sites.value[0]?.id || 10001
}

async function loadSites() {
  const data = await request('/api/sites')
  sites.value = data.items || []
  centerOverview.value = data.overview || {}
  if (!currentSiteId.value) currentSiteId.value = data.current_site_id || sites.value[0]?.id || 10001
}

function switchSite() {
  ElMessage.success('已切换当前站点')
  publishResult.value = null
  Promise.all([loadStaticPages(), refreshCurrentView()])
}

function openSite(item: any) {
  currentSiteId.value = item.id
  view.value = 'settings'
  ElMessage.success(`已进入：${item.name}`)
  Promise.all([loadSettings(), loadStaticPages()])
}

function resetSiteForm() {
  Object.assign(siteForm, { name: '', domain: '', subdomain: '', language: 'zh-CN', template_key: 'business-clean', status: 'active', deploy: emptyDeploy() })
}

function newSite() {
  siteEditingId.value = ''
  resetSiteForm()
  siteDrawerVisible.value = true
}

function editSite(item: any) {
  siteEditingId.value = item.id
  Object.assign(siteForm, {
    name: item.name || '',
    domain: item.domain || '',
    subdomain: item.subdomain || '',
    language: item.language || 'zh-CN',
    template_key: item.template_key || 'business-clean',
    status: item.status || 'active',
    deploy: { ...emptyDeploy(), ...(item.deploy || {}) }
  })
  siteDrawerVisible.value = true
}

function deployReady(item: any) {
  return !!(item?.deploy?.bt_panel_url && item?.deploy?.site_path)
}

async function saveSite() {
  siteCreating.value = true
  try {
    const editing = !!siteEditingId.value
    const data = editing
      ? await request(`/api/sites/${siteEditingId.value}`, { method: 'PUT', data: siteForm })
      : await request('/api/sites', { method: 'POST', data: siteForm })
    sites.value = data.items || sites.value
    centerOverview.value = data.overview || centerOverview.value
    if (data.site?.id) currentSiteId.value = data.site.id
    resetSiteForm()
    siteEditingId.value = ''
    siteDrawerVisible.value = false
    ElMessage.success(editing ? '站点已保存' : '站点已创建')
    await loadDashboard()
  } finally {
    siteCreating.value = false
  }
}

async function toggleSiteStatus(item: any) {
  const nextStatus = item.status === 'active' ? 'disabled' : 'active'
  await request(`/api/sites/${item.id}`, {
    method: 'PUT',
    data: { ...item, status: nextStatus }
  })
  ElMessage.success(nextStatus === 'active' ? '站点已恢复' : '站点已停用')
  await Promise.all([loadSites(), loadDashboard()])
}

async function loadSettings() {
  const data = await request('/api/site/settings')
  Object.assign(site, normalizeSite(data))
}

async function loadStaticPages() {
  const data = await request('/api/site/pages')
  staticPages.value = data.items || []
}

function normalizeSite(data: any = {}) {
  const normalized = {
    ...data,
    template_key: data.template_key || 'business-clean',
    ai: data.ai || {},
    payment: data.payment || {},
    deploy: {
      bt_panel_url: data.deploy?.bt_panel_url || '',
      site_path: data.deploy?.site_path || '',
      mode: data.deploy?.mode || 'manual',
      after_action: data.deploy?.after_action || '',
      note: data.deploy?.note || ''
    },
    content: data.content || {},
    hero: data.hero || {},
    home_sections: data.home_sections || {},
    home_content: data.home_content || {},
    nav: normalizeNavItems(data.nav),
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

function defaultNavItems() {
  return [
    { title: '首页', url: 'index.html' },
    { title: '行业资讯', url: 'news/index.html' },
    { title: '产品中心', url: 'products/index.html' },
    { title: '联系我们', url: 'contact.html' },
    { title: '搜索', url: 'search.html' },
    { title: '查订单', url: 'order.html' }
  ]
}

function normalizeNavItems(items: any[] = []) {
  const source = Array.isArray(items) && items.length ? items : defaultNavItems()
  return source.map((item: any, index: number) => ({
    id: item.id || `nav-${Date.now()}-${index}`,
    title: item.title || '',
    url: item.url || '#',
    target_blank: Boolean(item.target_blank)
  }))
}

function cleanNavItems(items: any[] = []) {
  return items
    .filter((item: any) => String(item.title || '').trim() !== '')
    .map((item: any) => ({
      title: String(item.title || '').trim(),
      url: String(item.url || '#').trim() || '#',
      target_blank: Boolean(item.target_blank)
    }))
}

async function saveSettings() {
  site.nav = cleanNavItems(site.nav)
  const data = await request('/api/site/settings', { method: 'PUT', data: site })
  Object.assign(site, normalizeSite(data))
  ElMessage.success('当前站点设置已保存，生成静态站后前台生效')
}

async function saveSettingsAsDefault() {
  site.nav = cleanNavItems(site.nav)
  const data = await request('/api/site/settings-default', { method: 'PUT', data: site })
  Object.assign(site, normalizeSite(data))
  ElMessage.success('公共默认设置已保存，新站点会优先继承这套配置')
}

async function applySettingsToAll() {
  await ElMessageBox.confirm('确定把当前站点设置应用到全部站点吗？已有独立菜单、SEO、AI、支付等设置会被覆盖。')
  site.nav = cleanNavItems(site.nav)
  const data = await request('/api/site/settings/apply-all', { method: 'POST', data: site })
  ElMessage.success(`已应用到 ${data.count || 0} 个站点`)
  await Promise.all([loadSites(), loadSettings()])
}

async function saveSettingsAndGenerate() {
  await saveSettings()
  await generateSite()
  await loadStaticPages()
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

function addNavItem() {
  site.nav = [...(site.nav || []), { id: `nav-${Date.now()}`, title: '新导航', url: '', target_blank: false }]
}

function onNavUrlChange(row: any) {
  const page = staticPages.value.find((item: any) => item.url === row.url)
  if (!page) return
  const title = String(row.title || '').trim()
  if (!title || title === '新导航') {
    row.title = page.title
  }
}

function removeNavItem(index: number) {
  site.nav = (site.nav || []).filter((_: any, itemIndex: number) => itemIndex !== index)
}

function moveNavItem(index: number, direction: number) {
  const next = index + direction
  if (!site.nav || next < 0 || next >= site.nav.length) return
  const items = [...site.nav]
  const current = items[index]
  items[index] = items[next]
  items[next] = current
  site.nav = items
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

async function applyPagePlanSaveGeneratePreview() {
  pagePlanPublishing.value = true
  try {
    applyPagePlan()
    await saveTemplateSettings()
    await generateSite()
    previewSite()
  } finally {
    pagePlanPublishing.value = false
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

async function loadPages() {
  const data = await request(`/api/pages?page=${pagePager.page}&page_size=${pagePager.page_size}`)
  pages.value = data.items || []
  pagePager.total = data.pagination?.total || pages.value.length
}
function changePagePage(page: number) {
  pagePager.page = page
  loadPages()
}

async function loadCategories() {
  const [articleData, productData] = await Promise.all([
    request('/api/categories'),
    request('/api/product-categories')
  ])
  categories.value = articleData.items || []
  productCategories.value = productData.items || []
}

const currentCategoryOptions = computed(() => {
  const items = categoryType.value === 'article' ? categories.value : productCategories.value
  return items.filter((item: any) => Number(item.id) !== Number(categoryForm.id || 0))
})

function resetCategoryForm() {
  Object.assign(categoryForm, {
    id: '',
    parent_id: 0,
    name: '',
    slug: '',
    cover: '',
    description: '',
    sort_order: 0,
    seo_title: '',
    seo_keywords: '',
    seo_description: ''
  })
}

function categoryEndpoint(type: 'article' | 'product') {
  return type === 'article' ? '/api/categories' : '/api/product-categories'
}

function newCategory(type: 'article' | 'product') {
  categoryType.value = type
  resetCategoryForm()
  categoryDrawerVisible.value = true
}

function editCategory(type: 'article' | 'product', item: any) {
  categoryType.value = type
  resetCategoryForm()
  Object.assign(categoryForm, { ...item, parent_id: Number(item.parent_id || 0), sort_order: Number(item.sort_order || 0) })
  categoryDrawerVisible.value = true
}

async function saveCategory() {
  const endpoint = categoryEndpoint(categoryType.value)
  const method = categoryForm.id ? 'PUT' : 'POST'
  const path = categoryForm.id ? `${endpoint}/${categoryForm.id}` : endpoint
  await request(path, { method, data: { ...categoryForm } })
  categoryDrawerVisible.value = false
  ElMessage.success('分类已保存')
  await loadCategories()
}

async function deleteCategory(type: 'article' | 'product', item: any) {
  await ElMessageBox.confirm(`确定删除分类“${item.name}”？`)
  await request(`${categoryEndpoint(type)}/${item.id}`, { method: 'DELETE' })
  ElMessage.success('分类已删除')
  await loadCategories()
}

function allSiteIds() {
  return sites.value.map((item: any) => Number(item.id)).filter((id) => id > 0)
}

function currentSiteIds() {
  return [Number(currentSiteId.value || 10001)]
}

function siteIdsForScope(scope: string, selected: any[] = []) {
  if (scope === 'all') return allSiteIds()
  if (scope === 'selected') {
    const ids = (selected || []).map((id: any) => Number(id)).filter((id) => id > 0)
    return ids.length ? ids : currentSiteIds()
  }
  return currentSiteIds()
}

function inferSiteScope(siteIds: any[] = []) {
  const ids = (siteIds || []).map((id: any) => Number(id)).filter((id) => id > 0)
  const allIds = allSiteIds()
  if (allIds.length && ids.length === allIds.length && allIds.every((id) => ids.includes(id))) return 'all'
  if (ids.length === 1 && ids[0] === Number(currentSiteId.value || 10001)) return 'current'
  return 'selected'
}

function normalizeDistributionPayload(form: any) {
  const site_scope = form.site_scope || inferSiteScope(form.site_ids || [])
  return {
    ...form,
    site_scope,
    site_ids: siteIdsForScope(site_scope, form.site_ids)
  }
}

function syncAiSiteScope() {
  aiForm.site_ids = siteIdsForScope(aiForm.site_scope, aiForm.site_ids)
}

function newPage() { Object.assign(pageForm, { id: '', title: '', slug: '', cover: '', summary: '', content: '', seo_keywords: '', status: 'draft', site_scope: 'current', site_ids: currentSiteIds() }) }
function editPage(item: any) { Object.assign(pageForm, { ...item, site_scope: inferSiteScope(item.site_ids || []), site_ids: item.site_ids?.length ? item.site_ids : currentSiteIds() }) }
async function savePage() {
  const method = pageForm.id ? 'PUT' : 'POST'
  const path = pageForm.id ? `/api/pages/${pageForm.id}` : '/api/pages'
  await request(path, { method, data: normalizeDistributionPayload(pageForm) })
  ElMessage.success('页面已保存')
  pagePager.page = pageForm.id ? pagePager.page : 1
  await Promise.all([loadPages(), loadStaticPages()])
}
async function deletePage(item: any) {
  await ElMessageBox.confirm(`确定删除页面「${item.title}」？`)
  await request(`/api/pages/${item.id}`, { method: 'DELETE' })
  await Promise.all([loadPages(), loadStaticPages()])
}
async function generatePageDraft(prompt: string) {
  const data = await request('/api/ai/generate', { method: 'POST', data: { type: 'article', prompt } })
  Object.assign(pageForm, { ...(data.draft || data), content: (data.draft || data).content || '' })
}

function newArticle() { Object.assign(articleForm, { id: '', title: '', slug: '', cover: '', summary: '', content: '', seo_keywords: '', status: 'draft', site_scope: 'current', site_ids: currentSiteIds() }) }
function editArticle(item: any) { Object.assign(articleForm, { ...item, site_scope: inferSiteScope(item.site_ids || []), site_ids: item.site_ids?.length ? item.site_ids : currentSiteIds() }) }
async function saveArticle() {
  const method = articleForm.id ? 'PUT' : 'POST'
  const path = articleForm.id ? `/api/articles/${articleForm.id}` : '/api/articles'
  await request(path, { method, data: normalizeDistributionPayload(articleForm) })
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

function newProduct() { Object.assign(productForm, { id: '', title: '', slug: '', sku: '', cover: '', summary: '', description: '', price: 0, stock: 0, status: 'draft', site_scope: 'current', site_ids: currentSiteIds() }) }
function editProduct(item: any) { Object.assign(productForm, { ...item, site_scope: inferSiteScope(item.site_ids || []), site_ids: item.site_ids?.length ? item.site_ids : currentSiteIds() }) }
async function saveProduct() {
  const method = productForm.id ? 'PUT' : 'POST'
  const path = productForm.id ? `/api/products/${productForm.id}` : '/api/products'
  await request(path, { method, data: normalizeDistributionPayload(productForm) })
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

async function loadAiTasks() {
  const params = new URLSearchParams()
  params.set('page', String(aiTaskPager.page))
  params.set('page_size', String(aiTaskPager.page_size))
  params.set('site_id', operationSiteScope.value)
  const data = await request(`/api/ai/tasks?${params.toString()}`)
  aiTasks.value = data.items || []
  aiTaskPager.total = data.pagination?.total || aiTasks.value.length
}

async function createAiTask() {
  aiTaskLoading.value = true
  try {
    const task = await request('/api/ai/tasks', {
      method: 'POST',
      data: {
        type: aiForm.type,
        prompt: aiForm.prompt,
        count: aiForm.count,
        site_scope: aiForm.site_scope,
        site_ids: siteIdsForScope(aiForm.site_scope, aiForm.site_ids)
      }
    })
    ElMessage.success(task.message || 'AI 任务已创建')
    await loadAiTasks()
  } finally {
    aiTaskLoading.value = false
  }
}

async function confirmAiTask(row: any, action: 'save_draft' | 'publish' | 'discard') {
  const text = action === 'discard' ? '确定丢弃这个 AI 任务结果？' : (action === 'publish' ? '确定把这个 AI 任务结果直接发布？' : '确定把这个 AI 任务结果保存为草稿？')
  await ElMessageBox.confirm(text)
  await request(`/api/ai/tasks/${row.id}/confirm`, { method: 'POST', data: { action } })
  ElMessage.success('AI 任务已处理')
  await Promise.all([loadAiTasks(), loadArticles(), loadProducts(), loadDashboard()])
}

async function deleteAiTask(row: any) {
  await ElMessageBox.confirm(`确定删除 AI 任务「${row.prompt}」？`)
  await request(`/api/ai/tasks/${row.id}`, { method: 'DELETE' })
  ElMessage.success('AI 任务已删除')
  await loadAiTasks()
}

function changeAiTaskPage(page: number) {
  aiTaskPager.page = page
  loadAiTasks()
}

function aiTaskTypeLabel(value: string) {
  return ({ article_generate: '文章生成', product_generate: '商品生成', page_build: '页面搭建' } as any)[value] || value || '-'
}

async function saveAiDraft(item: any, index: number) {
  const site_ids = siteIdsForScope(aiForm.site_scope, aiForm.site_ids)
  if (item.type === 'article') {
    await request('/api/articles', { method: 'POST', data: { ...item, status: 'draft', site_scope: aiForm.site_scope, site_ids } })
    await loadArticles()
  } else {
    await request('/api/products', { method: 'POST', data: { ...item, status: 'draft', site_scope: aiForm.site_scope, site_ids } })
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
    const data = await request(path, { method: 'POST', data: { prompt: aiForm.prompt, count: aiForm.count, status: aiForm.status, site_scope: aiForm.site_scope, site_ids: siteIdsForScope(aiForm.site_scope, aiForm.site_ids) } })
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
  params.set('site_id', operationSiteScope.value)
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
function applyOperationSiteScope() {
  orderPager.page = 1
  servicePager.page = 1
  formPager.page = 1
  if (view.value === 'orders') loadOrders()
  if (view.value === 'service') loadServices()
  if (view.value === 'forms') loadForms()
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
  params.set('site_id', operationSiteScope.value)
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

async function loadPaymentChannels() {
  const data = await request('/api/payment/channels')
  paymentChannels.value = data.items || []
}
function newPaymentChannel() {
  Object.assign(paymentForm, {
    id: '',
    name: '',
    provider: 'manual',
    currency: 'CNY',
    account: '',
    instructions: '',
    status: 'active',
    is_default: false,
    scope: 'all',
    site_ids: [],
    config: {}
  })
  paymentConfigText.value = ''
  paymentDrawerVisible.value = true
}
function editPaymentChannel(item: any) {
  Object.assign(paymentForm, {
    ...item,
    is_default: !!item.is_default,
    scope: item.scope || (item.site_ids?.length ? 'selected' : 'all'),
    site_ids: item.site_ids || [],
    config: item.config || {}
  })
  paymentConfigText.value = JSON.stringify(paymentForm.config || {}, null, 2)
  paymentDrawerVisible.value = true
}
async function savePaymentChannel() {
  let config = {}
  if (paymentConfigText.value.trim()) {
    try {
      config = JSON.parse(paymentConfigText.value)
    } catch {
      return ElMessage.error('接口参数必须是 JSON 格式')
    }
  }
  const payload = { ...paymentForm, config }
  if (payload.scope !== 'selected') payload.site_ids = []
  const method = paymentForm.id ? 'PUT' : 'POST'
  const path = paymentForm.id ? `/api/payment/channels/${paymentForm.id}` : '/api/payment/channels'
  await request(path, { method, data: payload })
  paymentDrawerVisible.value = false
  ElMessage.success('支付通道已保存')
  await loadPaymentChannels()
}
async function deletePaymentChannel(item: any) {
  await ElMessageBox.confirm(`确定删除支付通道「${item.name}」？`)
  await request(`/api/payment/channels/${item.id}`, { method: 'DELETE' })
  ElMessage.success('支付通道已删除')
  await loadPaymentChannels()
}
async function applyPaymentChannel(item: any) {
  await request('/api/payment/channels/apply', { method: 'POST', data: { channel_id: item.id } })
  await loadSettings()
  ElMessage.success(`已应用到当前站点：${currentSite.value?.name || '默认站点'}`)
}
function providerLabel(value: string) {
  return { manual: '人工确认', bank: '银行转账', wechat: '微信支付', alipay: '支付宝', stripe: 'Stripe', paypal: 'PayPal' }[value] || value || '-'
}
function channelSiteLabel(item: any) {
  if (!item.site_ids?.length) return '全部站点'
  const names = sites.value.filter((site: any) => item.site_ids.includes(Number(site.id))).map((site: any) => site.name)
  return names.length ? names.join('、') : item.site_ids.join('、')
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
  const params = new URLSearchParams()
  params.set('page', String(formPager.page))
  params.set('page_size', String(formPager.page_size))
  params.set('site_id', operationSiteScope.value)
  Object.entries(formFilters).forEach(([key, value]) => value && params.set(key, value))
  const data = await request(`/api/forms/submissions?${params.toString()}`)
  forms.value = data.items || []
  formPager.total = data.pagination?.total || forms.value.length
}
function openFormSubmission(row: any) {
  Object.assign(formDetail, row)
  formDrawerVisible.value = true
}
async function saveFormSubmission() {
  await request(`/api/forms/submissions/${formDetail.id}`, {
    method: 'PUT',
    data: {
      status: formDetail.status || 'new',
      remark: formDetail.remark || ''
    }
  })
  ElMessage.success('Inquiry saved')
  formDrawerVisible.value = false
  await Promise.all([loadForms(), loadDashboard(), loadSites()])
}
async function deleteFormSubmission() {
  await ElMessageBox.confirm('Delete this inquiry?')
  await request(`/api/forms/submissions/${formDetail.id}`, { method: 'DELETE' })
  ElMessage.success('Inquiry deleted')
  formDrawerVisible.value = false
  await Promise.all([loadForms(), loadDashboard(), loadSites()])
}
function applyFormFilters() {
  formPager.page = 1
  loadForms()
}
function changeFormPage(page: number) {
  formPager.page = page
  loadForms()
}

async function loadCollector() {
  await Promise.all([loadCollectorSources(), loadCollectorRecords(), loadCategories()])
}

async function loadCollectorSources() {
  const params = new URLSearchParams()
  params.set('page', String(collectorSourcePager.page))
  params.set('page_size', String(collectorSourcePager.page_size))
  params.set('site_id', operationSiteScope.value)
  if (collectorFilters.keyword) params.set('keyword', collectorFilters.keyword)
  const data = await request(`/api/collector/sources?${params.toString()}`)
  collectorSources.value = data.items || []
  collectorSourcePager.total = data.pagination?.total || collectorSources.value.length
}

async function loadCollectorRecords() {
  const params = new URLSearchParams()
  params.set('page', String(collectorRecordPager.page))
  params.set('page_size', String(collectorRecordPager.page_size))
  params.set('site_id', operationSiteScope.value)
  if (collectorFilters.keyword) params.set('keyword', collectorFilters.keyword)
  if (collectorFilters.status) params.set('status', collectorFilters.status)
  const data = await request(`/api/collector/records?${params.toString()}`)
  collectorRecords.value = data.items || []
  collectorRecordPager.total = data.pagination?.total || collectorRecords.value.length
}

function resetCollectorForm() {
  Object.assign(collectorForm, {
    id: '',
    site_id: Number(currentSiteId.value || 10001),
    name: '',
    source_type: 'rss',
    url: '',
    category_id: '',
    rewrite_mode: 'draft',
    status: 'active'
  })
}

function newCollectorSource() {
  resetCollectorForm()
  collectorDrawerVisible.value = true
}

function editCollectorSource(row: any) {
  resetCollectorForm()
  Object.assign(collectorForm, { ...row, category_id: row.category_id || '' })
  collectorDrawerVisible.value = true
}

async function saveCollectorSource() {
  const method = collectorForm.id ? 'PUT' : 'POST'
  const path = collectorForm.id ? `/api/collector/sources/${collectorForm.id}` : '/api/collector/sources'
  await request(path, { method, data: { ...collectorForm, site_id: Number(collectorForm.site_id || currentSiteId.value || 10001) } })
  collectorDrawerVisible.value = false
  ElMessage.success('采集源已保存')
  await loadCollectorSources()
}

async function deleteCollectorSource(row: any) {
  await ElMessageBox.confirm(`确定删除采集源「${row.name}」？`)
  await request(`/api/collector/sources/${row.id}`, { method: 'DELETE' })
  ElMessage.success('采集源已删除')
  await loadCollectorSources()
}

async function runCollectorSource(row: any) {
  collectorRunningId.value = row.id
  try {
    const data = await request(`/api/collector/sources/${row.id}/run`, { method: 'POST' })
    ElMessage.success(data.message || '采集完成')
    await Promise.all([loadCollectorSources(), loadCollectorRecords()])
  } finally {
    collectorRunningId.value = ''
  }
}

async function publishCollectorRecord(row: any, status: 'draft' | 'published') {
  await request(`/api/collector/records/${row.id}/publish`, { method: 'POST', data: { status } })
  ElMessage.success(status === 'published' ? '已转为发布文章' : '已转为文章草稿')
  await Promise.all([loadCollectorRecords(), loadArticles(), loadDashboard()])
}

async function deleteCollectorRecord(row: any) {
  await ElMessageBox.confirm(`确定删除采集记录「${row.title}」？`)
  await request(`/api/collector/records/${row.id}`, { method: 'DELETE' })
  ElMessage.success('采集记录已删除')
  await loadCollectorRecords()
}

function changeCollectorSourcePage(page: number) {
  collectorSourcePager.page = page
  loadCollectorSources()
}

function changeCollectorRecordPage(page: number) {
  collectorRecordPager.page = page
  loadCollectorRecords()
}

async function loadVersions() {
  const data = await request('/api/site/publish-versions')
  versions.value = data.items || data || []
}

async function loadBatchTasks() {
  const query = batchTaskQuery()
  const data = await request(`/api/batch/tasks${query.toString() ? `?${query.toString()}` : ''}`)
  batchTasks.value = data.items || data || []
  batchTaskOverview.value = data.overview || {}
  taskPager.total = data.pagination?.total || batchTasks.value.length
}

function batchTaskQuery(includePagination = true) {
  const query = new URLSearchParams()
  if (includePagination) {
    query.set('page', String(taskPager.page))
    query.set('page_size', String(taskPager.page_size))
  }
  Object.entries(taskFilters).forEach(([key, value]) => {
    if (value) query.set(key, String(value))
  })
  return query
}

function applyTaskFilters() {
  taskPager.page = 1
  loadBatchTasks()
}

function resetTaskFilters() {
  Object.assign(taskFilters, { action: '', status: '', date: '' })
  taskPager.page = 1
  loadBatchTasks()
}

function changeTaskPage(page: number) {
  taskPager.page = page
  loadBatchTasks()
}

function changeTaskPageSize(size: number) {
  taskPager.page_size = size
  taskPager.page = 1
  loadBatchTasks()
}

async function exportBatchTasks() {
  const query = batchTaskQuery(false)
  const response = await axios({
    url: `/api/batch/tasks/export${query.toString() ? `?${query.toString()}` : ''}`,
    method: 'GET',
    responseType: 'blob',
    headers: token.value ? { Authorization: `Bearer ${token.value}`, 'X-Site-Id': String(currentSiteId.value || 10001) } : undefined
  })
  const blobUrl = URL.createObjectURL(response.data)
  const link = document.createElement('a')
  link.href = blobUrl
  link.download = `batch-tasks-${new Date().toISOString().slice(0, 19).replace(/[:T]/g, '')}.csv`
  link.click()
  URL.revokeObjectURL(blobUrl)
}

function parseSummary(value: any) {
  if (!value) return {}
  if (typeof value === 'object') return value
  try {
    return JSON.parse(value)
  } catch {
    return { message: value }
  }
}

async function generateSite() {
  generating.value = true
  try {
    const data = await request('/api/site/generate', { method: 'POST' })
    publishResult.value = data || { message: '静态站已生成' }
    ElMessage.success('静态站已生成')
    await Promise.all([loadVersions(), loadSites(), loadDashboard()])
    return data
  } finally {
    generating.value = false
  }
}

async function generateSiteFor(item: any) {
  currentSiteId.value = item.id
  publishResult.value = null
  return generateSite()
}

function selectAllActiveSites() {
  selectedSiteIds.value = sites.value.filter((item: any) => item.status === 'active').map((item: any) => item.id)
}

async function runSiteBatch(action: 'generate' | 'deploy-check' | 'package') {
  if (!selectedSiteIds.value.length) {
    ElMessage.warning('请先选择站点')
    return
  }
  const targets = sites.value.filter((item: any) => selectedSiteIds.value.some((id) => String(id) === String(item.id)))
  await executeSiteBatch(action, targets)
}

async function executeSiteBatch(action: 'generate' | 'deploy-check' | 'package', targets: any[], messagePrefix = '') {
  if (!targets.length) {
    ElMessage.warning('没有可执行的站点')
    return
  }
  const originalSiteId = currentSiteId.value
  siteBatchRunning.value = true
  siteBatchResults.value = []
  publishResult.value = null
  try {
    for (const item of targets) {
      currentSiteId.value = item.id
      try {
        const endpoint = action === 'generate' ? '/api/site/generate' : action === 'deploy-check' ? '/api/site/deploy-test' : '/api/site/package'
        const data = await request(endpoint, { method: 'POST' })
        siteBatchResults.value.push({
          site_id: item.id,
          site_name: item.name,
          action,
          ok: true,
          message: data?.message || data?.version_no || batchActionLabel(action)
        })
      } catch (error: any) {
        siteBatchResults.value.push({
          site_id: item.id,
          site_name: item.name,
          action,
          ok: false,
          message: error?.message || '执行失败'
        })
      }
    }
    currentSiteId.value = originalSiteId
    await saveBatchTaskRecord(action, siteBatchResults.value, messagePrefix)
    await Promise.all([loadSites(), loadDashboard(), loadVersions(), loadBatchTasks()])
    ElMessage.success(siteBatchSummary.value || '批量任务完成')
  } finally {
    currentSiteId.value = originalSiteId
    siteBatchRunning.value = false
  }
}

async function saveBatchTaskRecord(action: string, results: any[], messagePrefix = '') {
  if (!results.length) return
  const message = messagePrefix ? `${messagePrefix}；${siteBatchSummary.value}` : siteBatchSummary.value
  try {
    await request('/api/batch/tasks', {
      method: 'POST',
      data: {
        action,
        results,
        message,
        started_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
        finished_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
      }
    })
  } catch (error: any) {
    ElMessage.warning(error?.message || '批量任务记录保存失败')
  }
}

async function checkDeployConfig() {
  deployTesting.value = true
  try {
    const data = await request('/api/site/deploy-test', { method: 'POST' })
    publishResult.value = data || { message: '部署配置检查完成' }
    ElMessage.success(data?.message || '部署配置检查完成')
    await loadVersions()
  } finally {
    deployTesting.value = false
  }
}

async function createPackage() {
  packaging.value = true
  try {
    const data = await request('/api/site/package', { method: 'POST' })
    publishResult.value = data || { message: '发布包已生成' }
    ElMessage.success(data?.message || '发布包已生成')
    await loadVersions()
  } finally {
    packaging.value = false
  }
}

async function downloadPackage(path: string) {
  const file = String(path || '').split('/').pop()
  if (!file) return ElMessage.warning('发布包路径为空')
  const response = await axios({
    url: `/api/site/package-download?file=${encodeURIComponent(file)}`,
    method: 'GET',
    responseType: 'blob',
    headers: token.value ? { Authorization: `Bearer ${token.value}`, 'X-Site-Id': String(currentSiteId.value || 10001) } : undefined
  })
  const blobUrl = URL.createObjectURL(response.data)
  const link = document.createElement('a')
  link.href = blobUrl
  link.download = file
  link.click()
  URL.revokeObjectURL(blobUrl)
}

function openPublishVersion(row: any) {
  Object.assign(publishDetail, row)
  publishDrawerVisible.value = true
}

async function rollbackVersion(row: any) {
  await ElMessageBox.confirm(`确定回滚到版本 ${row.version_no} 吗？当前 public 目录会被该版本快照覆盖。`)
  const data = await request('/api/site/rollback', { method: 'POST', data: { version_id: row.id } })
  publishResult.value = data || { message: '回滚成功' }
  publishDrawerVisible.value = false
  ElMessage.success('已回滚到所选版本')
  await Promise.all([loadVersions(), loadSites(), loadDashboard()])
}

function openBatchTask(row: any) {
  Object.assign(batchTaskDetail, row)
  batchTaskResultFilter.value = 'all'
  batchTaskDrawerVisible.value = true
}

function batchTaskResults(row: any) {
  return row?.summary_data?.results || parseSummary(row?.summary)?.results || []
}

function filteredBatchTaskResults(row: any) {
  const results = batchTaskResults(row)
  if (batchTaskResultFilter.value === 'failed') return results.filter((item: any) => !item.ok)
  if (batchTaskResultFilter.value === 'success') return results.filter((item: any) => item.ok)
  return results
}

function failedBatchTaskResults(row: any) {
  return batchTaskResults(row).filter((item: any) => !item.ok)
}

async function retryFailedTask(row: any) {
  const failedItems = failedBatchTaskResults(row)
  if (!failedItems.length) {
    ElMessage.warning('没有失败站点可重试')
    return
  }
  const targets = failedItems.map((failed: any) => {
    const existing = sites.value.find((item: any) => String(item.id) === String(failed.site_id))
    return existing || { id: failed.site_id, name: failed.site_name || failed.site_id }
  })
  batchTaskDrawerVisible.value = false
  await executeSiteBatch(row.action, targets, `重试任务 ${row.task_no}`)
}

function previewSite(item: any = currentSite.value) {
  const url = item?.publish?.preview_url || (String(item?.id || currentSiteId.value) === '10001' ? '/' : `/s/${item?.site_key || 'site_10001'}/`)
  window.open(url, '_blank')
}

function formatFileSize(value: number) {
  if (!value) return '0 B'
  if (value < 1024) return `${value} B`
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`
  return `${(value / 1024 / 1024).toFixed(2)} MB`
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
    }
  }
})
</script>
