<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-gray-900">{{ $t('bulk_price.title') }}</h1>
      <p class="mt-0.5 text-sm text-gray-500">{{ $t('bulk_price.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Controls -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-6 space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('bulk_price.filter_type') }}</label>
          <select v-model="filters.product_type_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" @change="fetchPreview">
            <option value="">{{ $t('bulk_price.filter_all') }}</option>
            <option v-for="pt in productTypes" :key="pt.id" :value="pt.internal_id">{{ pt.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('common.search') }}</label>
          <input v-model="filters.search" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" @input="debouncedPreview" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('bulk_price.update_type') }}</label>
          <div class="flex rounded-lg overflow-hidden ring-1 ring-gray-300">
            <button
              type="button"
              class="flex-1 py-2 text-sm font-medium transition"
              :class="adjustType === 'percentage' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
              @click="adjustType = 'percentage'"
            >{{ $t('bulk_price.percentage') }}</button>
            <button
              type="button"
              class="flex-1 py-2 text-sm font-medium transition border-l border-gray-300"
              :class="adjustType === 'fixed' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
              @click="adjustType = 'fixed'"
            >{{ $t('bulk_price.fixed') }}</button>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('bulk_price.value') }}</label>
          <input
            v-model="adjustValue"
            type="number"
            step="0.01"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
          <p class="mt-1 text-xs text-gray-400">{{ adjustType === 'percentage' ? $t('bulk_price.value_hint_pct') : $t('bulk_price.value_hint_fixed') }}</p>
        </div>

        <div v-if="successMessage" class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 ring-1 ring-green-200">{{ successMessage }}</div>

        <button
          type="button"
          :disabled="applying || !adjustValue || !previewItems.length"
          class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          @click="applyUpdate"
        >
          <svg v-if="applying" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          {{ applying ? $t('bulk_price.applying') : $t('bulk_price.apply') }}
        </button>
      </div>

      <!-- Preview -->
      <div class="lg:col-span-2 rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-5 py-3.5 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('bulk_price.preview') }}</h2>
          <p class="text-sm text-gray-500">{{ previewItems.length }} {{ $t('common.results') }}</p>
        </div>

        <div v-if="loadingPreview" class="divide-y divide-gray-100">
          <div v-for="i in 4" :key="i" class="flex items-center gap-4 px-5 py-4 animate-pulse">
            <div class="h-4 w-40 rounded bg-gray-200 flex-1" />
            <div class="h-4 w-20 rounded bg-gray-200" />
            <div class="h-4 w-20 rounded bg-gray-200" />
          </div>
        </div>

        <EmptyState v-else-if="!previewItems.length" :title="$t('bulk_price.no_products')" />

        <ul v-else class="divide-y divide-gray-100 max-h-[calc(100vh-280px)] overflow-y-auto">
          <li v-for="item in previewItems" :key="item.id" class="flex items-center gap-4 px-5 py-3">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ item.name }}</p>
              <p class="text-xs text-gray-400">{{ item.product_type?.name }}</p>
            </div>
            <div v-for="pp in item.presentations" :key="pp.id" class="flex items-center gap-2 text-sm">
              <span class="text-xs text-gray-400">{{ pp.presentation?.display }}</span>
              <span class="font-medium text-gray-700">${{ pp.price }}</span>
              <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
              <span class="font-semibold" :class="computedPrice(pp) > pp.price ? 'text-green-600' : computedPrice(pp) < pp.price ? 'text-red-600' : 'text-gray-700'">${{ computedPrice(pp).toFixed(2) }}</span>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const { loading: loadingPreview, get } = useApi()
const { loading: applying, post } = useApi()
const { get: getTypes } = useApi()

const productTypes = ref([])
const previewItems = ref([])
const adjustType = ref('percentage')
const adjustValue = ref('')
const successMessage = ref('')
const filters = ref({ product_type_id: '', search: '' })

async function fetchProductTypes() {
  const { data } = await getTypes('/api/product-types', { per_page: 200 })
  if (data) productTypes.value = data.data.map(t => ({ ...t, internal_id: t.id }))
}

async function fetchPreview() {
  successMessage.value = ''
  const params = {}
  if (filters.value.product_type_id) params.product_type_id = filters.value.product_type_id
  if (filters.value.search) params.search = filters.value.search
  const { data } = await get('/api/bulk-price/preview', params)
  if (data) previewItems.value = data.data
}

function computedPrice(pp) {
  const v = parseFloat(adjustValue.value) || 0
  const price = parseFloat(pp.price) || 0
  if (adjustType.value === 'percentage') return Math.max(0, Math.round(price * (1 + v / 100) * 100) / 100)
  return Math.max(0, Math.round((price + v) * 100) / 100)
}

async function applyUpdate() {
  successMessage.value = ''
  const payload = {
    type: adjustType.value,
    value: parseFloat(adjustValue.value),
    product_type_id: filters.value.product_type_id || null,
    only_active: true,
  }
  const { data } = await post('/api/bulk-price', payload)
  if (data) {
    successMessage.value = `${data.updated} presentaciones actualizadas.`
    adjustValue.value = ''
    await fetchPreview()
  }
}

let previewDebounce
function debouncedPreview() {
  clearTimeout(previewDebounce)
  previewDebounce = setTimeout(fetchPreview, 300)
}

onMounted(() => { fetchProductTypes(); fetchPreview() })
</script>
