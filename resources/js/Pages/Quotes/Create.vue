<template>
  <div class="max-w-6xl mx-auto flex flex-col" style="min-height: calc(100vh - 4rem);">

    <!-- Page header + stepper -->
    <div class="space-y-5 pb-6">
      <div class="flex items-center gap-4">
        <Link href="/quotes" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
        </Link>
        <div>
          <h1 class="text-xl font-semibold text-gray-900">{{ $t('quotes.create') }}</h1>
          <p class="mt-0.5 text-sm text-gray-500">{{ $t('quotes.create_subtitle') }}</p>
        </div>
      </div>

      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 px-6 py-4">
        <StepperIndicator :steps="stepLabels" :current="step" @go="goTo" />
      </div>
    </div>

    <!-- Step content -->
    <div class="flex-1">
      <Transition name="step" mode="out-in">

        <!-- Step 0: Info general -->
        <div v-if="step === 0" key="info" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('quotes.header_info') }}</h2>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <SelectField
              v-model="form.client_id"
              :label="$t('quotes.client')"
              :options="clientOptions"
              :placeholder="$t('common.none')"
              :error="formErrors.client_id?.[0]"
            />
            <InputField
              v-model="form.starts_at"
              :label="$t('quotes.starts_at')"
              type="date"
              :error="formErrors.starts_at?.[0]"
            />
            <InputField
              v-model="form.expires_at"
              :label="$t('quotes.expires_at')"
              type="date"
              :error="formErrors.expires_at?.[0]"
            />
            <TextareaField
              v-model="form.notes"
              :label="$t('quotes.notes')"
              :rows="1"
            />
          </div>
        </div>

        <!-- Step 1: Productos -->
        <div v-else-if="step === 1" key="items" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-700">{{ $t('quotes.items_section') }}</h2>
            <span class="text-xs text-gray-400">{{ form.items.length }} {{ $t('quotes.items_added') }}</span>
          </div>

          <!-- Product search -->
          <div class="px-5 py-4 border-b border-gray-100">
            <div class="relative" ref="searchContainer">
              <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ $t('quotes.search_product') }}</label>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                  </svg>
                </div>
                <input
                  v-model="productSearch"
                  type="text"
                  :placeholder="$t('quotes.search_product_placeholder')"
                  class="block w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm text-gray-900 shadow-sm placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  @focus="searchOpen = true"
                  @keydown.escape="searchOpen = false"
                  @keydown.down.prevent="highlightNext"
                  @keydown.up.prevent="highlightPrev"
                  @keydown.enter.prevent="selectHighlighted"
                />
              </div>
              <div
                v-if="searchOpen && filteredOptions.length > 0"
                class="absolute z-20 mt-1 w-full rounded-xl bg-white shadow-lg ring-1 ring-gray-200 overflow-hidden"
              >
                <ul class="max-h-64 overflow-y-auto divide-y divide-gray-100 py-1">
                  <li
                    v-for="(opt, i) in filteredOptions"
                    :key="opt.id"
                    :class="['flex items-center justify-between px-4 py-2.5 cursor-pointer text-sm transition', i === highlighted ? 'bg-indigo-50 text-indigo-900' : 'text-gray-700 hover:bg-gray-50']"
                    @mousedown.prevent="addItem(opt)"
                    @mouseover="highlighted = i"
                  >
                    <div>
                      <span class="font-medium">{{ opt.productName }}</span>
                      <span class="ml-2 text-xs text-gray-500">{{ opt.presentationDisplay }}</span>
                    </div>
                    <div class="ml-4 text-right shrink-0">
                      <p class="text-xs font-medium text-gray-900">${{ formatNumber(opt.price) }}</p>
                      <p class="text-xs text-gray-400">{{ $t('quotes.stock') }}: {{ opt.stock }}</p>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Items table -->
          <div v-if="form.items.length > 0" class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-100 bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                  <th class="px-5 py-3 w-8">#</th>
                  <th class="px-3 py-3">{{ $t('quotes.product_presentation') }}</th>
                  <th class="px-3 py-3 w-52">{{ $t('quotes.description') }}</th>
                  <th class="px-3 py-3 w-28">{{ $t('quotes.quantity') }}</th>
                  <th class="px-3 py-3 w-32">{{ $t('quotes.unit_price') }}</th>
                  <th class="px-3 py-3 w-40">{{ $t('quotes.discount') }}</th>
                  <th class="px-3 py-3 w-28 text-right">{{ $t('quotes.item_total') }}</th>
                  <th class="px-3 py-3 w-10"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(item, index) in form.items" :key="item._key" class="group hover:bg-gray-50 transition">
                  <td class="px-5 py-3 text-xs text-gray-400 tabular-nums">{{ index + 1 }}</td>
                  <td class="px-3 py-3">
                    <p class="font-medium text-gray-900">{{ item.productName }}</p>
                    <p class="text-xs text-gray-400">{{ item.presentationDisplay }}</p>
                  </td>
                  <td class="px-3 py-2">
                    <input v-model="item.description" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                  </td>
                  <td class="px-3 py-2">
                    <input v-model="item.quantity" type="number" step="0.001" min="0.001" class="block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-right tabular-nums focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                  </td>
                  <td class="px-3 py-2">
                    <div class="relative">
                      <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-400">$</span>
                      <input v-model="item.unit_price" type="number" step="0.01" min="0" class="block w-full rounded-lg border border-gray-300 py-1.5 pl-6 pr-3 text-sm text-right tabular-nums focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                    </div>
                  </td>
                  <td class="px-3 py-2">
                    <div class="flex items-center gap-1">
                      <select v-model="item.discount_type" class="rounded-lg border border-gray-300 py-1.5 pl-2 pr-6 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">—</option>
                        <option value="percentage">%</option>
                        <option value="fixed">$</option>
                      </select>
                      <input v-if="item.discount_type" v-model="item.discount_value" type="number" step="0.01" min="0" class="block w-16 rounded-lg border border-gray-300 px-2 py-1.5 text-xs text-right tabular-nums focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                    </div>
                  </td>
                  <td class="px-3 py-3 text-right font-medium text-gray-900 tabular-nums">${{ formatNumber(computeItemTotal(item)) }}</td>
                  <td class="px-3 py-3">
                    <button type="button" class="rounded-md p-1 text-gray-300 hover:bg-gray-100 hover:text-red-500 opacity-0 group-hover:opacity-100 transition" @click="removeItem(index)">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="px-5 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <p class="mt-2 text-sm text-gray-400">{{ $t('quotes.no_items') }}</p>
          </div>

          <!-- Subtotal / discount / total -->
          <div v-if="form.items.length > 0" class="border-t border-gray-200 px-5 py-4 bg-gray-50 rounded-b-xl">
            <div class="flex items-end justify-between gap-6">
              <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500">{{ $t('quotes.discount') }}</span>
                <select v-model="form.discount_type" class="rounded-lg border border-gray-300 py-1.5 pl-2 pr-6 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                  <option value="">—</option>
                  <option value="percentage">{{ $t('quotes.percentage') }}</option>
                  <option value="fixed">{{ $t('quotes.fixed') }}</option>
                </select>
                <input v-if="form.discount_type" v-model="form.discount_value" type="number" step="0.01" min="0" class="w-24 rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-right tabular-nums focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
              </div>
              <div class="text-right space-y-1">
                <div class="flex items-center justify-end gap-6 text-sm text-gray-500">
                  <span>{{ $t('quotes.subtotal') }}</span>
                  <span class="w-28 tabular-nums">${{ formatNumber(subtotal) }}</span>
                </div>
                <div v-if="discountAmount > 0" class="flex items-center justify-end gap-6 text-sm text-red-600">
                  <span>{{ $t('quotes.discount_amount') }}</span>
                  <span class="w-28 tabular-nums">-${{ formatNumber(discountAmount) }}</span>
                </div>
                <div class="flex items-center justify-end gap-6">
                  <span class="text-sm text-gray-500">{{ $t('quotes.total') }}</span>
                  <span class="w-28 text-2xl font-bold text-gray-900 tabular-nums">${{ formatNumber(formTotal) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </Transition>
    </div>

    <!-- Sticky bottom nav -->
    <div class="sticky bottom-0 mt-6 rounded-xl bg-white shadow-lg ring-1 ring-gray-200 px-5 py-3.5 flex items-center justify-between gap-4">
      <!-- Total pill -->
      <div class="flex items-center gap-2 min-w-0">
        <span v-if="formTotal > 0" class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200 tabular-nums">
          ${{ formatNumber(formTotal) }}
        </span>
        <span v-if="stepError" class="text-sm text-red-600">{{ stepError }}</span>
        <span v-if="formError" class="text-sm text-red-600">{{ formError }}</span>
      </div>

      <!-- Nav buttons -->
      <div class="flex items-center gap-2 shrink-0">
        <Link href="/quotes" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
          {{ $t('common.cancel') }}
        </Link>
        <button
          v-if="step > 0"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 transition"
          @click="prev"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
          {{ $t('common.previous') }}
        </button>
        <button
          v-if="step < stepLabels.length - 1"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition"
          @click="next"
        >
          {{ $t('common.next') }}
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
          </svg>
        </button>
        <button
          v-else
          type="button"
          :disabled="saving || form.items.length === 0"
          class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          @click="save"
        >
          <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          {{ $t('quotes.save_quote') }}
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputField from '@/Components/InputField.vue'
import TextareaField from '@/Components/TextareaField.vue'
import SelectField from '@/Components/SelectField.vue'
import StepperIndicator from '@/Components/StepperIndicator.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const { t } = useI18n()
const { loading: saving, errors: formErrors, post: postForm } = useApi()
const { get } = useApi()

// ── Stepper ────────────────────────────────────────────────────────────────
const step = ref(0)
const stepError = ref(null)
const stepLabels = computed(() => [
  t('quotes.header_info'),
  t('quotes.items_section'),
])

function next() {
  stepError.value = null
  step.value++
}

function prev() { step.value-- }
function goTo(i) { step.value = i }

// ── Form state ─────────────────────────────────────────────────────────────
const clients = ref([])
const allProductPresentations = ref([])
const formError = ref(null)
let itemKeyCounter = 0

const form = ref({
  client_id: null,
  notes: '',
  starts_at: new Date().toISOString().split('T')[0],
  expires_at: '',
  discount_type: '',
  discount_value: '',
  items: [],
})

const clientOptions = computed(() => clients.value.map(c => ({ value: c.id, label: c.name })))

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
function selectHighlighted() { if (filteredOptions.value[highlighted.value]) addItem(filteredOptions.value[highlighted.value]) }

function addItem(opt) {
  form.value.items.push({
    _key: ++itemKeyCounter,
    product_presentation_id: opt.id,
    productName: opt.productName,
    presentationDisplay: opt.presentationDisplay,
    description: opt.productName + (opt.presentationDisplay ? ' - ' + opt.presentationDisplay : ''),
    quantity: 1,
    unit_price: opt.price,
    discount_type: '',
    discount_value: '',
  })
  productSearch.value = ''
  searchOpen.value = false
  highlighted.value = 0
}

function removeItem(index) { form.value.items.splice(index, 1) }

// ── Totals ─────────────────────────────────────────────────────────────────
function computeItemTotal(item) {
  const qty = parseFloat(item.quantity) || 0
  const price = parseFloat(item.unit_price) || 0
  let lineTotal = qty * price
  const discVal = parseFloat(item.discount_value) || 0
  if (item.discount_type === 'percentage' && discVal > 0) lineTotal = lineTotal * (1 - discVal / 100)
  else if (item.discount_type === 'fixed' && discVal > 0) lineTotal = Math.max(lineTotal - discVal, 0)
  return lineTotal
}

const subtotal = computed(() => form.value.items.reduce((sum, it) => sum + computeItemTotal(it), 0))
const discountAmount = computed(() => {
  const discVal = parseFloat(form.value.discount_value) || 0
  if (form.value.discount_type === 'percentage' && discVal > 0) return subtotal.value * discVal / 100
  if (form.value.discount_type === 'fixed' && discVal > 0) return Math.min(discVal, subtotal.value)
  return 0
})
const formTotal = computed(() => Math.max(subtotal.value - discountAmount.value, 0))

// ── Save ───────────────────────────────────────────────────────────────────
async function save() {
  formError.value = null
  if (form.value.items.length === 0) {
    stepError.value = t('quotes.no_items')
    return
  }
  const payload = {
    client_id: form.value.client_id || null,
    notes: form.value.notes || null,
    starts_at: form.value.starts_at || null,
    expires_at: form.value.expires_at || null,
    discount_type: form.value.discount_type || null,
    discount_value: form.value.discount_value || null,
    items: form.value.items.map(it => ({
      product_presentation_id: it.product_presentation_id,
      description: it.description,
      quantity: it.quantity,
      unit_price: it.unit_price,
      discount_type: it.discount_type || null,
      discount_value: it.discount_value || null,
    })),
  }
  const result = await postForm('/api/quotes', payload)
  if (result.error) {
    if (!Object.keys(formErrors.value).length) formError.value = result.error
    return
  }
  router.visit('/quotes')
}

// ── Helpers ────────────────────────────────────────────────────────────────
function formatNumber(value) {
  const num = parseFloat(value) || 0
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function handleClickOutside(e) {
  if (searchContainer.value && !searchContainer.value.contains(e.target)) searchOpen.value = false
}

// ── Init ───────────────────────────────────────────────────────────────────
async function fetchOptions() {
  const [clientsRes, productsRes] = await Promise.all([
    get('/api/clients'), get('/api/products?per_page=500'),
  ])
  if (clientsRes.data) clients.value = clientsRes.data.data ?? clientsRes.data
  const products = productsRes.data?.data ?? []
  allProductPresentations.value = products.flatMap(product =>
    (product.presentations ?? [])
      .filter(pp => pp.is_active)
      .map(pp => ({
        id: pp.id,
        productName: product.name,
        presentationDisplay: pp.presentation?.display ?? '',
        price: pp.price ?? 0,
        stock: pp.stock ?? 0,
      }))
  )
}

onMounted(() => { fetchOptions(); document.addEventListener('mousedown', handleClickOutside) })
onBeforeUnmount(() => { document.removeEventListener('mousedown', handleClickOutside) })
</script>

<style scoped>
.step-enter-active, .step-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.step-enter-from { opacity: 0; transform: translateX(12px); }
.step-leave-to { opacity: 0; transform: translateX(-12px); }
</style>
