<template>
  <div class="max-w-5xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-4">
      <Link href="/quotes" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </Link>
      <div class="flex-1 min-w-0">
        <h1 class="text-xl font-semibold text-gray-900">{{ $t('quotes.detail_title') }}</h1>
        <p v-if="quote" class="mt-0.5 text-sm text-gray-500">{{ formatDate(quote.created_at) }} · {{ quote.user?.name }}</p>
      </div>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-6 animate-pulse">
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div v-for="i in 4" :key="i" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <div class="h-3 w-20 rounded bg-gray-200 mb-2" />
          <div class="h-5 w-28 rounded bg-gray-200" />
        </div>
      </div>
    </div>

    <template v-else-if="quote">
      <!-- Summary cards -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('quotes.client') }}</p>
          <p class="mt-1 text-sm font-semibold text-gray-900">{{ quote.client?.name ?? $t('quotes.no_client') }}</p>
        </div>
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('quotes.expires_at') }}</p>
          <p class="mt-1 text-sm font-semibold text-gray-900">{{ quote.expires_at ? formatDate(quote.expires_at) : '—' }}</p>
        </div>
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('quotes.not_converted') }}</p>
          <span
            class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
            :class="quote.is_converted
              ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
              : 'bg-amber-50 text-amber-700 ring-amber-200'"
          >
            {{ quote.is_converted ? $t('quotes.converted') : $t('quotes.not_converted') }}
          </span>
        </div>
        <div class="rounded-xl bg-violet-600 shadow-sm p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-violet-200">{{ $t('quotes.total') }}</p>
          <p class="mt-1 text-2xl font-bold text-white tabular-nums">${{ formatNumber(quote.total) }}</p>
        </div>
      </div>

      <!-- Converted banner -->
      <div v-if="quote.is_converted" class="rounded-xl bg-emerald-50 ring-1 ring-emerald-200 px-5 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-sm font-medium text-emerald-800">{{ $t('quotes.already_converted') }}</p>
        </div>
        <Link
          v-if="quote.sale?.id"
          :href="`/sales/${quote.sale.id}`"
          class="text-sm font-semibold text-emerald-700 hover:text-emerald-900 underline"
        >
          {{ $t('quotes.sale_link') }}
        </Link>
      </div>

      <!-- Items card -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('quotes.items_section') }}</h2>
        </div>
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
              <th class="px-5 py-3">#</th>
              <th class="px-3 py-3">{{ $t('quotes.description') }}</th>
              <th class="px-3 py-3 text-right">{{ $t('quotes.quantity') }}</th>
              <th class="px-3 py-3 text-right">{{ $t('quotes.unit_price') }}</th>
              <th class="px-3 py-3 text-right">{{ $t('quotes.discount_amount') }}</th>
              <th class="px-3 py-3 text-right">{{ $t('quotes.item_total') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(item, i) in quote.items" :key="item.id" class="hover:bg-gray-50 transition">
              <td class="px-5 py-3 text-xs text-gray-400">{{ i + 1 }}</td>
              <td class="px-3 py-3">
                <p class="font-medium text-gray-900">{{ item.description }}</p>
                <p class="text-xs text-gray-400">{{ item.product_presentation?.product?.name }} · {{ item.product_presentation?.presentation?.name }}</p>
              </td>
              <td class="px-3 py-3 text-right tabular-nums text-gray-600">{{ item.quantity }}</td>
              <td class="px-3 py-3 text-right tabular-nums text-gray-600">${{ formatNumber(item.unit_price) }}</td>
              <td class="px-3 py-3 text-right tabular-nums" :class="item.discount_amount > 0 ? 'text-red-600' : 'text-gray-400'">
                {{ item.discount_amount > 0 ? '-$' + formatNumber(item.discount_amount) : '—' }}
              </td>
              <td class="px-3 py-3 text-right tabular-nums font-semibold text-gray-900">${{ formatNumber(item.total) }}</td>
            </tr>
          </tbody>
          <tfoot class="border-t border-gray-200 bg-gray-50">
            <tr v-if="quote.discount_amount > 0">
              <td colspan="5" class="px-3 py-2 text-right text-sm text-gray-500">{{ $t('quotes.subtotal') }}</td>
              <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-700">${{ formatNumber(quote.subtotal) }}</td>
            </tr>
            <tr v-if="quote.discount_amount > 0">
              <td colspan="5" class="px-3 py-2 text-right text-sm text-red-500">{{ $t('quotes.discount_amount') }}</td>
              <td class="px-3 py-2 text-right tabular-nums font-medium text-red-600">-${{ formatNumber(quote.discount_amount) }}</td>
            </tr>
            <tr>
              <td colspan="5" class="px-3 py-3 text-right text-sm font-semibold text-gray-700">{{ $t('quotes.total') }}</td>
              <td class="px-3 py-3 text-right tabular-nums text-lg font-bold text-gray-900">${{ formatNumber(quote.total) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Notes -->
      <div v-if="quote.notes" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 mb-1">{{ $t('quotes.notes') }}</p>
        <p class="text-sm text-gray-700">{{ quote.notes }}</p>
      </div>

      <!-- Convert modal -->
      <div v-if="showConvert" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5 space-y-4">
        <h2 class="text-sm font-semibold text-gray-700">{{ $t('quotes.convert_title') }}</h2>
        <p class="text-sm text-gray-500">{{ $t('quotes.convert_subtitle') }}</p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <SelectField
            v-model="convertForm.point_of_sale_id"
            :label="$t('quotes.point_of_sale')"
            :options="posOptions"
            :placeholder="$t('common.select')"
            :error="convertErrors.point_of_sale_id?.[0]"
            required
          />
        </div>

        <!-- Payments -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">{{ $t('quotes.payments_section') }}</span>
            <button type="button" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium" @click="addPayment">
              + {{ $t('quotes.add_payment') }}
            </button>
          </div>
          <div class="space-y-2">
            <div v-for="(payment, idx) in convertForm.payments" :key="payment._key" class="flex items-center gap-3">
              <SelectField
                v-model="payment.payment_method_id"
                :label="''"
                :options="paymentMethodOptions"
                :placeholder="$t('common.select')"
                class="flex-1"
              />
              <div class="relative w-36">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-400">$</span>
                <input v-model="payment.amount" type="number" step="0.01" min="0" class="block w-full rounded-lg border border-gray-300 py-2 pl-7 pr-3 text-sm text-right tabular-nums focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
              </div>
              <button type="button" class="text-gray-300 hover:text-red-500 transition" @click="convertForm.payments.splice(idx, 1)">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
          </div>
          <p v-if="convertErrors['payments']?.[0]" class="mt-1 text-xs text-red-600">{{ convertErrors['payments'][0] }}</p>
        </div>

        <div v-if="convertError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
          {{ convertError }}
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 transition" @click="showConvert = false">
            {{ $t('common.cancel') }}
          </button>
          <button
            type="button"
            :disabled="converting"
            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50 transition"
            @click="doConvert"
          >
            <svg v-if="converting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ $t('quotes.convert_to_sale') }}
          </button>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pb-6">
        <Link href="/quotes" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 transition">
          {{ $t('common.back') }}
        </Link>
        <div class="flex items-center gap-3">
          <button
            v-if="!quote.is_converted"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition"
            @click="openConvert"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            {{ $t('quotes.convert_to_sale') }}
          </button>
          <button
            v-if="!quote.is_converted"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-red-50 px-4 py-2 text-sm font-medium text-red-700 ring-1 ring-red-200 hover:bg-red-100 transition"
            @click="confirmOpen = true"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
            {{ $t('common.delete') }}
          </button>
        </div>
      </div>
    </template>
  </div>

  <ConfirmModal v-model="confirmOpen" :title="$t('quotes.delete_confirm')" @confirm="doDelete" />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import SelectField from '@/Components/SelectField.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const props = defineProps({ uuid: String })

const { loading, get } = useApi()
const { del } = useApi()
const { loading: converting, errors: convertErrors, post: postConvert } = useApi()

const quote = ref(null)
const confirmOpen = ref(false)
const showConvert = ref(false)
const convertError = ref(null)

const pointsOfSale = ref([])
const paymentMethods = ref([])
let paymentKeyCounter = 0

const convertForm = ref({ point_of_sale_id: null, payments: [] })

const posOptions = computed(() => pointsOfSale.value.map(p => ({ value: p.id, label: p.name })))
const paymentMethodOptions = computed(() => paymentMethods.value.map(m => ({ value: m.id, label: m.name })))

async function fetchQuote() {
  const { data } = await get(`/api/quotes/${props.uuid}`)
  if (data) quote.value = data.data
}

async function fetchConvertOptions() {
  const [posRes, pmRes] = await Promise.all([get('/api/points-of-sale'), get('/api/payment-methods')])
  if (posRes.data) pointsOfSale.value = posRes.data.data ?? posRes.data
  if (pmRes.data) paymentMethods.value = pmRes.data.data ?? pmRes.data
}

function openConvert() {
  convertForm.value = { point_of_sale_id: pointsOfSale.value[0]?.id ?? null, payments: [] }
  addPayment()
  showConvert.value = true
  window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' })
}

function addPayment() {
  convertForm.value.payments.push({
    _key: ++paymentKeyCounter,
    payment_method_id: paymentMethods.value[0]?.id ?? null,
    amount: quote.value?.total ?? '',
  })
}

async function doConvert() {
  convertError.value = null
  const payload = {
    point_of_sale_id: convertForm.value.point_of_sale_id,
    payments: convertForm.value.payments.map(p => ({
      payment_method_id: p.payment_method_id,
      amount: p.amount,
    })),
  }
  const result = await postConvert(`/api/quotes/${props.uuid}/convert`, payload)
  if (result.error) {
    if (!Object.keys(convertErrors.value).length) convertError.value = result.error
    return
  }
  router.visit(`/sales/${result.data?.data?.id}`)
}

async function doDelete() {
  confirmOpen.value = false
  await del(`/api/quotes/${props.uuid}`)
  router.visit('/quotes')
}

function formatDate(str) {
  if (!str) return ''
  return new Date(str.includes('T') ? str : str + 'T00:00:00').toLocaleDateString()
}
function formatNumber(value) {
  const num = parseFloat(value) || 0
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

onMounted(async () => {
  await fetchQuote()
  await fetchConvertOptions()
})
</script>
