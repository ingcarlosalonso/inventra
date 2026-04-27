<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $t('product_movements.title') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ $t('common.manage_subtitle') }}</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition"
        @click="openCreate"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        {{ $t('product_movements.create') }}
      </button>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
      <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-3.5">
        <SearchInput v-model="search" class="w-72" />
        <p class="text-sm text-gray-500">{{ meta.total }} {{ $t('common.results') }}</p>
      </div>

      <div v-if="loadingList" class="divide-y divide-gray-100">
        <div v-for="i in 5" :key="i" class="flex items-center gap-4 px-5 py-4 animate-pulse">
          <div class="h-4 w-48 rounded bg-gray-200" />
          <div class="h-4 w-24 rounded bg-gray-200 ml-auto" />
        </div>
      </div>

      <EmptyState v-else-if="items.length === 0" :title="search ? $t('common.no_results') : $t('common.empty')" :subtitle="search ? $t('common.try_different_search') : null" />

      <ul v-else class="divide-y divide-gray-100">
        <li
          v-for="item in items"
          :key="item.id"
          class="group flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition"
        >
          <div
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
            :class="item.type?.is_income ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
          >
            {{ item.type?.is_income ? '+' : '−' }}
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ item.product?.name }}</p>
            <p class="text-xs text-gray-400">{{ item.presentation?.name }} · {{ item.type?.name }}</p>
          </div>

          <div class="text-right">
            <p class="text-sm font-semibold" :class="item.type?.is_income ? 'text-green-600' : 'text-red-600'">
              {{ item.type?.is_income ? '+' : '−' }}{{ item.quantity }}
            </p>
            <p class="text-xs text-gray-400">{{ item.user?.name }}</p>
          </div>

          <p class="text-xs text-gray-400 w-28 text-right shrink-0">{{ formatDate(item.created_at) }}</p>

          <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
            <button type="button" class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-600" :title="$t('common.delete')" @click="confirmDelete(item)">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
            </button>
          </div>
        </li>
      </ul>

      <div v-if="meta.last_page > 1" class="px-5 py-4">
        <Pagination :meta="meta" @navigate="navigateTo" />
      </div>
    </div>
  </div>

  <SlideOver v-model="slideOverOpen" :title="$t('product_movements.create')">
    <form class="space-y-5" @submit.prevent="save">
      <!-- Product search -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('product_movements.product') }}</label>
        <div class="relative">
          <input
            v-model="productSearch"
            type="text"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            :placeholder="$t('product_movements.select_product')"
            @input="searchProducts"
          />
          <ul v-if="productResults.length && !form.product" class="absolute z-10 mt-1 w-full rounded-lg bg-white shadow-lg ring-1 ring-gray-200 max-h-48 overflow-y-auto">
            <li
              v-for="p in productResults"
              :key="p.id"
              class="cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50"
              @click="selectProduct(p)"
            >
              {{ p.name }}
            </li>
          </ul>
        </div>
      </div>

      <!-- Presentation select -->
      <div v-if="form.product">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('product_movements.presentation') }}</label>
        <select v-model="form.product_presentation_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
          <option value="">{{ $t('common.select') }}</option>
          <option v-for="pp in form.product.presentations" :key="pp.id" :value="pp.internal_id">
            {{ pp.presentation?.display ?? pp.presentation?.quantity }} — Stock: {{ pp.stock }}
          </option>
        </select>
        <p v-if="formErrors.product_presentation_id?.[0]" class="mt-1 text-xs text-red-600">{{ formErrors.product_presentation_id[0] }}</p>
      </div>

      <!-- Movement type -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('product_movements.type') }}</label>
        <select v-model="form.product_movement_type_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
          <option value="">{{ $t('common.select') }}</option>
          <optgroup :label="$t('common.income')">
            <option v-for="t in movementTypes.filter(t => t.is_income)" :key="t.id" :value="t.internal_id">{{ t.name }}</option>
          </optgroup>
          <optgroup :label="$t('common.expense')">
            <option v-for="t in movementTypes.filter(t => !t.is_income)" :key="t.id" :value="t.internal_id">{{ t.name }}</option>
          </optgroup>
        </select>
        <p v-if="formErrors.product_movement_type_id?.[0]" class="mt-1 text-xs text-red-600">{{ formErrors.product_movement_type_id[0] }}</p>
      </div>

      <InputField v-model="form.quantity" :label="$t('product_movements.quantity')" type="number" step="0.001" min="0.001" :error="formErrors.quantity?.[0]" required />
      <TextareaField v-model="form.notes" :label="$t('product_movements.notes')" :rows="3" />

      <div v-if="formError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">{{ formError }}</div>
    </form>
    <template #footer>
      <div class="flex justify-end gap-3">
        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50" @click="slideOverOpen = false">{{ $t('common.cancel') }}</button>
        <button type="button" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition" @click="save">
          <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          {{ $t('common.save') }}
        </button>
      </div>
    </template>
  </SlideOver>

  <ConfirmModal
    v-model="confirmOpen"
    :title="$t('product_movements.delete_confirm')"
    :subtitle="$t('product_movements.delete_hint')"
    @confirm="doDelete"
  />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import SlideOver from '@/Components/SlideOver.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import SearchInput from '@/Components/SearchInput.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Pagination from '@/Components/Pagination.vue'
import InputField from '@/Components/InputField.vue'
import TextareaField from '@/Components/TextareaField.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const { loading: loadingList, get } = useApi()
const { loading: saving, errors: formErrors, post: postForm } = useApi()
const { del } = useApi()
const { get: getProducts } = useApi()

const items = ref([])
const meta = ref({ total: 0, last_page: 1, links: [] })
const movementTypes = ref([])
const search = ref('')
const slideOverOpen = ref(false)
const confirmOpen = ref(false)
const deleteTarget = ref(null)
const formError = ref(null)
const productSearch = ref('')
const productResults = ref([])

const emptyForm = () => ({
  product: null,
  product_presentation_id: '',
  product_movement_type_id: '',
  quantity: '',
  notes: '',
})
const form = ref(emptyForm())

function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function fetchItems(url = null) {
  const params = {}
  if (search.value) params.search = search.value
  const { data } = await get(url ?? '/api/product-movements', url ? {} : params)
  if (data) { items.value = data.data; meta.value = data.meta }
}

async function fetchMovementTypes() {
  const { data } = await get('/api/product-movement-types', { per_page: 100 })
  if (data) movementTypes.value = data.data.map(t => ({ ...t, internal_id: t.id }))
}

let productDebounce
async function searchProducts() {
  form.value.product = null
  clearTimeout(productDebounce)
  if (!productSearch.value) { productResults.value = []; return }
  productDebounce = setTimeout(async () => {
    const { data } = await getProducts('/api/products', { search: productSearch.value, per_page: 10 })
    if (data) productResults.value = data.data
  }, 300)
}

function selectProduct(p) {
  form.value.product = p
  form.value.product_presentation_id = ''
  productSearch.value = p.name
  productResults.value = []
}

function openCreate() {
  form.value = emptyForm(); productSearch.value = ''; productResults.value = []; formError.value = null; slideOverOpen.value = true
}

async function save() {
  formError.value = null
  if (!form.value.product) { formError.value = 'Seleccioná un producto.'; return }
  const payload = {
    product_presentation_id: form.value.product_presentation_id,
    product_movement_type_id: form.value.product_movement_type_id,
    quantity: form.value.quantity,
    notes: form.value.notes,
  }
  const result = await postForm('/api/product-movements', payload)
  if (result.error) { if (!Object.keys(formErrors.value).length) formError.value = result.error; return }
  slideOverOpen.value = false; await fetchItems()
}

function confirmDelete(item) { deleteTarget.value = item; confirmOpen.value = true }
async function doDelete() { confirmOpen.value = false; await del(`/api/product-movements/${deleteTarget.value.id}`); await fetchItems() }
function navigateTo(url) { fetchItems(url) }

let searchDebounce
watch(search, () => { clearTimeout(searchDebounce); searchDebounce = setTimeout(() => fetchItems(), 300) })
onMounted(() => { fetchItems(); fetchMovementTypes() })
</script>
