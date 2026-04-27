<template>
  <div class="max-w-5xl mx-auto space-y-6">

    <!-- Page header -->
    <div class="flex items-center gap-4">
      <Link href="/receptions" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </Link>
      <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $t('receptions.create') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ $t('receptions.create_subtitle') }}</p>
      </div>
    </div>

    <!-- Header card -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
      <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('receptions.header_info') }}</h2>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <SelectField
          v-model="form.supplier_id"
          :label="$t('receptions.supplier')"
          :options="supplierOptions"
          :placeholder="$t('common.none')"
          :error="formErrors.supplier_id?.[0]"
        />
        <InputField
          v-model="form.received_at"
          :label="$t('receptions.received_at')"
          type="date"
          :error="formErrors.received_at?.[0]"
          required
        />
        <InputField
          v-model="form.supplier_invoice"
          :label="$t('receptions.supplier_invoice')"
          :placeholder="$t('receptions.supplier_invoice_placeholder')"
          :error="formErrors.supplier_invoice?.[0]"
        />
        <TextareaField
          v-model="form.notes"
          :label="$t('receptions.notes')"
          :rows="1"
        />
      </div>
    </div>

    <!-- Items card -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
        <h2 class="text-sm font-semibold text-gray-700">{{ $t('receptions.items') }}</h2>
        <span class="text-xs text-gray-400">{{ form.items.length }} {{ $t('receptions.items_added') }}</span>
      </div>

      <!-- Product search -->
      <div class="px-5 py-4 border-b border-gray-100">
        <div class="relative" ref="searchContainer">
          <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ $t('receptions.search_product') }}</label>
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
              </svg>
            </div>
            <input
              ref="searchInput"
              v-model="productSearch"
              type="text"
              :placeholder="$t('receptions.search_product_placeholder')"
              class="block w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm text-gray-900 shadow-sm placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              @focus="searchOpen = true"
              @keydown.escape="searchOpen = false"
              @keydown.down.prevent="highlightNext"
              @keydown.up.prevent="highlightPrev"
              @keydown.enter.prevent="selectHighlighted"
            />
          </div>

          <!-- Dropdown -->
          <div
            v-if="searchOpen && filteredOptions.length > 0"
            class="absolute z-20 mt-1 w-full rounded-xl bg-white shadow-lg ring-1 ring-gray-200 overflow-hidden"
          >
            <ul class="max-h-64 overflow-y-auto divide-y divide-gray-100 py-1">
              <li
                v-for="(opt, i) in filteredOptions"
                :key="opt.id"
                :class="[
                  'flex items-center justify-between px-4 py-2.5 cursor-pointer text-sm transition',
                  i === highlighted ? 'bg-indigo-50 text-indigo-900' : 'text-gray-700 hover:bg-gray-50',
                ]"
                @mousedown.prevent="addItem(opt)"
                @mouseover="highlighted = i"
              >
                <div>
                  <span class="font-medium">{{ opt.productName }}</span>
                  <span class="ml-2 text-xs text-gray-500">{{ opt.presentationDisplay }}</span>
                </div>
                <span class="ml-4 text-xs text-gray-400 shrink-0">
                  {{ $t('receptions.stock') }}: {{ opt.stock }}
                </span>
              </li>
            </ul>
            <div v-if="filteredOptions.length === 0 && productSearch" class="px-4 py-3 text-sm text-gray-400 text-center">
              {{ $t('common.no_results') }}
            </div>
          </div>

          <p v-if="productSearch && filteredOptions.length === 0 && searchOpen" class="mt-1 text-xs text-gray-400">
            {{ $t('common.no_results') }}
          </p>
        </div>
      </div>

      <!-- Items table -->
      <div v-if="form.items.length > 0" class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
              <th class="px-5 py-3 w-8">#</th>
              <th class="px-3 py-3">{{ $t('receptions.product_presentation') }}</th>
              <th class="px-3 py-3 w-36">{{ $t('receptions.quantity') }}</th>
              <th class="px-3 py-3 w-36">{{ $t('receptions.unit_cost') }}</th>
              <th class="px-3 py-3 w-32 text-right">{{ $t('receptions.item_total') }}</th>
              <th class="px-3 py-3 w-10"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="(item, index) in form.items"
              :key="item._key"
              class="group hover:bg-gray-50 transition"
            >
              <td class="px-5 py-3 text-xs text-gray-400 tabular-nums">{{ index + 1 }}</td>
              <td class="px-3 py-3">
                <div>
                  <p class="font-medium text-gray-900">{{ item.productName }}</p>
                  <p class="text-xs text-gray-400">{{ item.presentationDisplay }}</p>
                </div>
              </td>
              <td class="px-3 py-2">
                <input
                  v-model="item.quantity"
                  type="number"
                  step="0.001"
                  min="0.001"
                  class="block w-full rounded-lg border px-3 py-1.5 text-sm text-right tabular-nums focus:outline-none focus:ring-1 transition"
                  :class="formErrors[`items.${index}.quantity`] ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'"
                />
                <p v-if="formErrors[`items.${index}.quantity`]" class="mt-0.5 text-xs text-red-600">{{ formErrors[`items.${index}.quantity`][0] }}</p>
              </td>
              <td class="px-3 py-2">
                <div class="relative">
                  <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-400">$</span>
                  <input
                    v-model="item.unit_cost"
                    type="number"
                    step="0.01"
                    min="0"
                    class="block w-full rounded-lg border py-1.5 pl-6 pr-3 text-sm text-right tabular-nums focus:outline-none focus:ring-1 transition"
                    :class="formErrors[`items.${index}.unit_cost`] ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'"
                  />
                </div>
                <p v-if="formErrors[`items.${index}.unit_cost`]" class="mt-0.5 text-xs text-red-600">{{ formErrors[`items.${index}.unit_cost`][0] }}</p>
              </td>
              <td class="px-3 py-3 text-right font-medium text-gray-900 tabular-nums">
                ${{ formatNumber(itemTotal(item)) }}
              </td>
              <td class="px-3 py-3">
                <button
                  type="button"
                  class="rounded-md p-1 text-gray-300 hover:bg-gray-100 hover:text-red-500 opacity-0 group-hover:opacity-100 transition"
                  @click="removeItem(index)"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="px-5 py-10 text-center">
        <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <p class="mt-2 text-sm text-gray-400">{{ $t('receptions.no_items') }}</p>
      </div>

      <!-- Footer: errors + total -->
      <div v-if="form.items.length > 0" class="flex items-center justify-between border-t border-gray-200 px-5 py-4 bg-gray-50 rounded-b-xl">
        <span v-if="formErrors.items?.[0]" class="text-sm text-red-600">{{ formErrors.items[0] }}</span>
        <span v-else class="text-sm text-gray-500">{{ form.items.length }} {{ $t('receptions.items_added') }}</span>
        <div class="text-right">
          <p class="text-xs text-gray-500">{{ $t('receptions.total') }}</p>
          <p class="text-2xl font-bold text-gray-900 tabular-nums">${{ formatNumber(formTotal) }}</p>
        </div>
      </div>
    </div>

    <!-- Error banner -->
    <div v-if="formError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
      {{ formError }}
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end gap-3 pb-6">
      <Link href="/receptions" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 transition">
        {{ $t('common.cancel') }}
      </Link>
      <button
        type="button"
        :disabled="saving || form.items.length === 0"
        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
        @click="save"
      >
        <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        {{ $t('receptions.save_reception') }}
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputField from '@/Components/InputField.vue'
import TextareaField from '@/Components/TextareaField.vue'
import SelectField from '@/Components/SelectField.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const { loading: saving, errors: formErrors, post: postForm } = useApi()
const { get } = useApi()

const suppliers = ref([])
const allProductPresentations = ref([])
const formError = ref(null)
let itemKeyCounter = 0

const form = ref({
  supplier_id: null,
  received_at: new Date().toISOString().split('T')[0],
  supplier_invoice: '',
  notes: '',
  items: [],
})

const supplierOptions = computed(() => suppliers.value.map(s => ({ value: s.id, label: s.name })))

// ── Product search ─────────────────────────────────────────────────────────
const productSearch = ref('')
const searchOpen = ref(false)
const highlighted = ref(0)
const searchContainer = ref(null)

const alreadyAddedIds = computed(() => new Set(form.value.items.map(i => i.product_presentation_id)))

const filteredOptions = computed(() => {
  const q = productSearch.value.trim().toLowerCase()
  return allProductPresentations.value
    .filter(pp => !alreadyAddedIds.value.has(pp.id))
    .filter(pp => !q || pp.productName.toLowerCase().includes(q) || pp.presentationDisplay.toLowerCase().includes(q))
    .slice(0, 30)
})

function highlightNext() { highlighted.value = Math.min(highlighted.value + 1, filteredOptions.value.length - 1) }
function highlightPrev() { highlighted.value = Math.max(highlighted.value - 1, 0) }
function selectHighlighted() {
  if (filteredOptions.value[highlighted.value]) addItem(filteredOptions.value[highlighted.value])
}

function addItem(opt) {
  form.value.items.push({
    _key: ++itemKeyCounter,
    product_presentation_id: opt.id,
    productName: opt.productName,
    presentationDisplay: opt.presentationDisplay,
    quantity: '',
    unit_cost: '',
  })
  productSearch.value = ''
  searchOpen.value = false
  highlighted.value = 0
}

function removeItem(index) {
  form.value.items.splice(index, 1)
}

// Close dropdown when clicking outside
function handleClickOutside(e) {
  if (searchContainer.value && !searchContainer.value.contains(e.target)) {
    searchOpen.value = false
  }
}

// ── Totals ─────────────────────────────────────────────────────────────────
function itemTotal(item) {
  const qty = parseFloat(item.quantity) || 0
  const cost = parseFloat(item.unit_cost) || 0
  return qty * cost
}

const formTotal = computed(() => form.value.items.reduce((sum, it) => sum + itemTotal(it), 0))

// ── Save ───────────────────────────────────────────────────────────────────
async function save() {
  formError.value = null
  const payload = {
    ...form.value,
    items: form.value.items
      .filter(it => it.product_presentation_id)
      .map(it => ({
        product_presentation_id: it.product_presentation_id,
        quantity: it.quantity,
        unit_cost: it.unit_cost,
      })),
  }
  const result = await postForm('/api/receptions', payload)
  if (result.error) {
    if (!Object.keys(formErrors.value).length) formError.value = result.error
    return
  }
  router.visit('/receptions')
}

// ── Init ───────────────────────────────────────────────────────────────────
function formatNumber(value) {
  const num = parseFloat(value) || 0
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function fetchOptions() {
  const [supRes, prodRes] = await Promise.all([
    get('/api/suppliers'),
    get('/api/products?per_page=500'),
  ])
  if (supRes.data) suppliers.value = supRes.data.data ?? supRes.data

  const products = prodRes.data?.data ?? []
  const flat = []
  for (const product of products) {
    for (const pp of (product.presentations ?? [])) {
      if (!pp.is_active) continue
      flat.push({
        id: pp.id,
        productName: product.name,
        presentationDisplay: pp.presentation?.display ?? '',
        stock: pp.stock ?? 0,
      })
    }
  }
  allProductPresentations.value = flat
}

onMounted(() => {
  fetchOptions()
  document.addEventListener('mousedown', handleClickOutside)
})
onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside)
})
</script>
