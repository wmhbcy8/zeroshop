<template>
  <div>
    <el-card class="panel" shadow="never">
      <template #header>
        <div class="card-head">
          <strong>{{ title }}列表</strong>
          <el-button type="primary" @click="openNew">新建{{ title }}</el-button>
        </div>
      </template>

      <div class="content-scope-summary">
        <article>
          <span>查看范围</span>
          <strong>{{ scopeLabel(listSiteScope) }}</strong>
          <small>列表只筛选已经分发给该范围的内容，不改变内容本身。</small>
        </article>
        <article>
          <span>新建默认</span>
          <strong>{{ scopeLabel(bulkForm.site_scope) }}</strong>
          <small>新建、AI 草稿、批量分发和保存后静态生成都沿用下方发布范围。</small>
        </article>
        <article>
          <span>内容模型</span>
          <strong>一份内容，多站点分发</strong>
          <small>静态生成时，每个前台只读取分发给自己的文章、页面或商品。</small>
        </article>
      </div>

      <div class="content-distribution-bar">
        <div>
          <strong>查看内容</strong>
          <small>先按站点查看内容库，再对选中的内容批量改分发范围。</small>
        </div>
        <el-select
          :model-value="listSiteScope"
          size="small"
          placeholder="查看范围"
          class="bulk-site-select"
          @update:model-value="$emit('scope-change', $event)"
        >
          <el-option label="全部内容库" value="all" />
          <el-option :label="`当前站点：${currentSiteName}`" value="current" />
          <el-option v-for="site in sites" :key="site.id" :label="site.name" :value="String(site.id)" />
        </el-select>
      </div>

      <div class="content-distribution-bar">
        <div>
          <strong>批量发布范围</strong>
          <small>先选择目标站点，再对选中内容批量分发、发布或转草稿。</small>
        </div>
        <el-radio-group v-model="bulkForm.site_scope" size="small" @change="syncBulkScope">
          <el-radio-button value="current">当前站点</el-radio-button>
          <el-radio-button value="all">全部站点</el-radio-button>
          <el-radio-button value="selected">指定站点</el-radio-button>
        </el-radio-group>
        <el-select
          v-if="bulkForm.site_scope === 'selected'"
          v-model="bulkForm.site_ids"
          multiple
          filterable
          collapse-tags
          collapse-tags-tooltip
          placeholder="选择站点"
          class="bulk-site-select"
        >
          <el-option v-for="site in sites" :key="site.id" :label="site.name" :value="site.id" />
        </el-select>
        <el-button type="primary" :disabled="!selectedRows.length" @click="applyBulkDistribution">
          分发到 {{ selectedRows.length }} 条
        </el-button>
        <el-button type="success" :disabled="!selectedRows.length" @click="applyBulkPublish('publish')">
          发布 {{ selectedRows.length }} 条
        </el-button>
        <el-button :disabled="!selectedRows.length" @click="applyBulkPublish('draft')">
          转草稿
        </el-button>
      </div>

      <el-table :data="items" height="650" @selection-change="selectedRows = $event">
        <el-table-column type="selection" width="46" />
        <el-table-column prop="title" label="标题" min-width="260">
          <template #default="{ row }">
            <strong>{{ row.title }}</strong><br />
            <small>{{ row.slug }}</small>
          </template>
        </el-table-column>
        <el-table-column v-if="type === 'product'" prop="sku" label="SKU" width="140" />
        <el-table-column v-if="type === 'product'" label="价格/库存" width="150">
          <template #default="{ row }">{{ row.price || 0 }} / {{ row.stock || 0 }}</template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="110" />
        <el-table-column label="分发站点" min-width="180">
          <template #default="{ row }"><small>{{ siteNames(row) }}</small></template>
        </el-table-column>
        <el-table-column label="操作" width="220">
          <template #default="{ row }">
            <el-button link type="primary" @click="openEdit(row)">编辑</el-button>
            <el-button v-if="row.status === 'published'" link type="warning" @click="$emit('publish-status', row, 'draft')">转草稿</el-button>
            <el-button v-else link type="success" @click="$emit('publish-status', row, 'publish')">发布</el-button>
            <el-button link type="danger" @click="$emit('delete', row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        class="table-pager"
        layout="prev, pager, next, total"
        :current-page="page"
        :page-size="pageSize"
        :total="total"
        @current-change="$emit('page-change', $event)"
      />
    </el-card>

    <el-drawer v-model="drawerVisible" :title="(form.id ? '编辑' : '新建') + title" size="620px">
      <el-form :model="form" label-width="90px">
        <el-alert
          type="info"
          show-icon
          :closable="false"
          class="mb16"
          :title="`这是一份中台内容，保存后会同步到：${siteNames(form)}，并自动重新生成对应静态站。AI 生成草稿也沿用这个发布范围。`"
        />
        <el-form-item label="发布范围">
          <el-radio-group v-model="form.site_scope" @change="syncScope">
            <el-radio-button value="current">当前站点</el-radio-button>
            <el-radio-button value="all">全部站点</el-radio-button>
            <el-radio-button value="selected">指定站点</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="form.site_scope === 'selected'" label="选择站点">
          <el-select v-model="form.site_ids" multiple filterable collapse-tags collapse-tags-tooltip placeholder="选择站点">
            <el-option v-for="site in sites" :key="site.id" :label="site.name" :value="site.id" />
          </el-select>
        </el-form-item>

        <el-form-item label="AI 要求"><el-input v-model="prompt" placeholder="输入生成要求" /></el-form-item>
        <el-form-item><el-button @click="generateDraft">AI 生成草稿</el-button></el-form-item>
        <el-form-item label="标题"><el-input v-model="form.title" /></el-form-item>
        <el-form-item label="Slug"><el-input v-model="form.slug" /></el-form-item>
        <el-form-item v-if="type !== 'page'" label="分类">
          <el-select v-model="form.category_id" clearable filterable placeholder="选择分类">
            <el-option v-for="item in categoryOptions" :key="item.id" :label="item.name" :value="item.id" />
          </el-select>
        </el-form-item>
        <el-form-item v-if="type === 'article'" label="标签">
          <el-select v-model="form.tag_names" multiple filterable allow-create default-first-option collapse-tags collapse-tags-tooltip placeholder="选择或输入文章标签">
            <el-option v-for="item in tags" :key="item.id || item.slug" :label="item.name" :value="item.name" />
          </el-select>
        </el-form-item>
        <el-form-item v-if="type === 'product'" label="SKU"><el-input v-model="form.sku" /></el-form-item>
        <el-form-item label="封面">
          <div class="cover-field">
            <el-input v-model="form.cover" placeholder="选择或输入图片路径" />
            <el-button @click="mediaDrawerVisible = true">选择图片</el-button>
          </div>
          <img v-if="form.cover" class="cover-preview" :src="form.cover.startsWith('/') ? form.cover : '/' + form.cover" />
        </el-form-item>
        <el-form-item label="摘要"><el-input v-model="form.summary" type="textarea" :rows="3" /></el-form-item>
        <el-form-item :label="bodyLabel"><el-input v-model="form[bodyField]" type="textarea" :rows="7" /></el-form-item>
        <el-alert type="info" show-icon :closable="false" class="mb16" :title="`保存后前台可见范围：${siteNames(form)}`" />
        <el-row v-if="type === 'product'" :gutter="12">
          <el-col :span="12"><el-form-item label="价格"><el-input-number v-model="form.price" :min="0" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="库存"><el-input-number v-model="form.stock" :min="0" /></el-form-item></el-col>
        </el-row>
        <el-form-item label="状态">
          <el-select v-model="form.status">
            <el-option label="草稿" value="draft" />
            <el-option label="发布" value="published" />
          </el-select>
        </el-form-item>
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
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'

const props = defineProps<{
  type: 'article' | 'product' | 'page'
  items: any[]
  form: any
  page: number
  pageSize: number
  total: number
  media: any[]
  sites: any[]
  categories?: any[]
  productCategories?: any[]
  tags?: any[]
  currentSiteId: number | string
  listSiteScope?: string
}>()

const emit = defineEmits(['new', 'edit', 'save', 'delete', 'ai', 'page-change', 'bulk-distribute', 'bulk-publish', 'publish-status', 'scope-change'])

const prompt = ref('')
const drawerVisible = ref(false)
const mediaDrawerVisible = ref(false)
const selectedRows = ref<any[]>([])
const bulkForm = ref<any>({ site_scope: 'current', site_ids: [] })
const title = computed(() => props.type === 'article' ? '文章' : (props.type === 'product' ? '商品' : '页面'))
const bodyField = computed(() => props.type === 'product' ? 'description' : 'content')
const bodyLabel = computed(() => props.type === 'product' ? '描述' : '正文')
const categoryOptions = computed(() => props.type === 'article' ? (props.categories || []) : (props.productCategories || []))
const currentSiteName = computed(() => (props.sites || []).find((site: any) => String(site.id) === String(props.currentSiteId))?.name || '当前站点')

function openNew() {
  emit('new')
  drawerVisible.value = true
}

function openEdit(row: any) {
  emit('edit', row)
  drawerVisible.value = true
}

function saveForm() {
  syncScope()
  emit('save')
}

function generateDraft() {
  syncScope()
  emit('ai', prompt.value)
}

function allSiteIds() {
  return props.sites
    .filter((site: any) => (site.status || 'active') === 'active')
    .map((site: any) => Number(site.id))
    .filter((id: number) => id > 0)
}

function currentSiteIds() {
  return [Number(props.currentSiteId || 10001)]
}

function siteIdsForScope(scope: string, selected: any[] = []) {
  if (scope === 'all') return allSiteIds()
  if (scope === 'selected') {
    const ids = (selected || []).map((id: any) => Number(id)).filter((id: number) => id > 0)
    return ids.length ? ids : currentSiteIds()
  }
  return currentSiteIds()
}

function syncBulkScope() {
  bulkForm.value.site_ids = siteIdsForScope(bulkForm.value.site_scope, bulkForm.value.site_ids)
}

watch(
  () => props.currentSiteId,
  () => {
    if (bulkForm.value.site_scope === 'current') {
      bulkForm.value.site_ids = currentSiteIds()
    }
  },
  { immediate: true }
)

function applyBulkDistribution() {
  if (!ensureBulkScope()) return
  syncBulkScope()
  emit('bulk-distribute', {
    items: selectedRows.value,
    site_scope: bulkForm.value.site_scope,
    site_ids: bulkForm.value.site_ids
  })
}

function applyBulkPublish(action: 'publish' | 'draft') {
  if (!ensureBulkScope()) return
  syncBulkScope()
  emit('bulk-publish', {
    items: selectedRows.value,
    action,
    site_scope: bulkForm.value.site_scope,
    site_ids: bulkForm.value.site_ids
  })
}

function ensureBulkScope() {
  if (!selectedRows.value.length) {
    ElMessage.warning('请先选择需要处理的内容')
    return false
  }
  if (bulkForm.value.site_scope === 'selected' && !(bulkForm.value.site_ids || []).length) {
    ElMessage.warning('请先选择至少一个目标站点')
    return false
  }
  return true
}

function siteNames(row: any) {
  const ids = (row.site_ids || []).map((id: any) => Number(id)).filter((id: number) => id > 0)
  const allIds = allSiteIds()
  if (allIds.length && ids.length === allIds.length && allIds.every((id) => ids.includes(id))) return '全部站点'
  const names = (props.sites || []).filter((site: any) => ids.includes(Number(site.id))).map((site: any) => site.name)
  return names.length ? names.join('、') : '未分发'
}

function scopeLabel(scope: string = 'all') {
  if (scope === 'all') return '全部内容库'
  if (scope === 'current') return currentSiteName.value
  const site = (props.sites || []).find((item: any) => String(item.id) === String(scope))
  return site?.name || scope || '全部内容库'
}

function selectCover(item: any) {
  props.form.cover = item.file_path
  mediaDrawerVisible.value = false
}

function syncScope() {
  if (props.form.site_scope === 'all') {
    props.form.site_ids = allSiteIds()
    return
  }
  if (props.form.site_scope === 'selected') {
    props.form.site_ids = (props.form.site_ids || []).map((id: any) => Number(id)).filter((id: number) => id > 0)
    return
  }
  props.form.site_scope = 'current'
  props.form.site_ids = currentSiteIds()
}
</script>
