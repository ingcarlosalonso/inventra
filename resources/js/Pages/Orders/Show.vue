<template>
  <div class="max-w-5xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-4">
      <Link href="/orders" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </Link>
      <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $t('orders.detail_title') }}</h1>
        <p v-if="order" class="mt-0.5 text-sm text-gray-500">{{ formatDate(order.created_at) }} · {{ order.user?.name }}</p>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-6 animate-pulse">
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div v-for="i in 4" :key="i" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <div class="h-3 w-20 rounded bg-gray-200 mb-2" />
          <div class="h-5 w-28 rounded bg-gray-200" />
        </div>
      </div>
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
        <div class="h-4 w-32 rounded bg-gray-200 mb-4" />
        <div class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-10 rounded bg-gray-100" />
        </div>
      </div>
    </div>

    <template v-else-if="order">
      <!-- Summary cards -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('orders.client') }}</p>
          <p class="mt-1 text-sm font-semibold text-gray-900">{{ order.client?.name ?? $t('orders.no_client') }}</p>
        </div>
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('orders.courier') }}</p>
          <p class="mt-1 text-sm font-semibold text-gray-900">{{ order.courier?.name ?? $t('orders.no_courier') }}</p>
        </div>
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('orders.order_state') }}</p>
          <span class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset" :style="stateStyle(order.order_state)">
            {{ order.order_state?.name }}
          </span>
        </div>
        <div class="rounded-xl bg-indigo-600 shadow-sm p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-indigo-200">{{ $t('orders.total') }}</p>
          <p class="mt-1 text-2xl font-bold text-white tabular-nums">${{ formatNumber(order.total) }}</p>
        </div>
      </div>

      <!-- Delivery info (if requires_delivery) -->
      <div v-if="order.requires_delivery || order.address" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('orders.delivery_section') }}</h2>
        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
          <div v-if="order.requires_delivery">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('orders.requires_delivery') }}</dt>
            <dd class="mt-1 text-sm font-semibold text-emerald-600">✓</dd>
          </div>
          <div v-if="order.delivery_date">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('orders.delivery_date') }}</dt>
            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ order.delivery_date }}</dd>
          </div>
          <div v-if="order.address">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('orders.address') }}</dt>
            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ order.address }}</dd>
          </div>
        </dl>
      </div>

      <!-- Change state -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('orders.change_state') }}</h2>
        <div class="flex items-center gap-3">
          <SelectField
            v-model="newStateId"
            :options="orderStateOptions"
            :placeholder="$t('common.select')"
            class="w-56"
          />
          <button
            type="button"
            :disabled="!newStateId || updatingState"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
            @click="updateState"
          >
            <svg v-if="updatingState" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            {{ $t('common.save') }}
          </button>
        </div>
      </div>

      <!-- Items card -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('orders.items_section') }}</h2>
        </div>
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
              <th class="px-5 py-3">#</th>
              <th class="px-3 py-3">{{ $t('orders.description') }}</th>
              <th class="px-3 py-3 text-right">{{ $t('orders.quantity') }}</th>
              <th class="px-3 py-3 text-right">{{ $t('orders.unit_price') }}</th>
              <th class="px-3 py-3 text-right">{{ $t('orders.discount_amount') }}</th>
              <th class="px-3 py-3 text-right">{{ $t('orders.item_total') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(item, i) in order.items" :key="item.id" class="hover:bg-gray-50 transition">
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
            <tr v-if="order.discount_amount > 0">
              <td colspan="5" class="px-3 py-2 text-right text-sm text-gray-500">{{ $t('orders.subtotal') }}</td>
              <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-700">${{ formatNumber(order.subtotal) }}</td>
            </tr>
            <tr v-if="order.discount_amount > 0">
              <td colspan="5" class="px-3 py-2 text-right text-sm text-red-500">{{ $t('orders.discount_amount') }}</td>
              <td class="px-3 py-2 text-right tabular-nums font-medium text-red-600">-${{ formatNumber(order.discount_amount) }}</td>
            </tr>
            <tr>
              <td colspan="5" class="px-3 py-3 text-right text-sm font-semibold text-gray-700">{{ $t('orders.total') }}</td>
              <td class="px-3 py-3 text-right tabular-nums text-lg font-bold text-gray-900">${{ formatNumber(order.total) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Payments card -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('orders.payments_section') }}</h2>
          <div class="flex items-center gap-3 text-sm">
            <span class="text-gray-500">{{ $t('sales.payment_paid') }}: <span class="font-semibold text-green-600">${{ formatNumber(order.paid_amount) }}</span></span>
            <span v-if="pendingAmount > 0" class="text-red-600 font-semibold">{{ $t('sales.payment_remaining') }}: ${{ formatNumber(pendingAmount) }}</span>
            <span v-else class="text-green-600 font-semibold">✓ {{ $t('sales.paid') }}</span>
          </div>
        </div>
        <table v-if="order.payments?.length > 0" class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
              <th class="px-5 py-3">{{ $t('orders.payment_method') }}</th>
              <th class="px-3 py-3 text-right">{{ $t('orders.amount') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="payment in order.payments" :key="payment.id" class="hover:bg-gray-50">
              <td class="px-5 py-3 font-medium text-gray-900">{{ payment.payment_method?.name }}</td>
              <td class="px-3 py-3 text-right tabular-nums font-semibold text-gray-900">${{ formatNumber(payment.amount) }}</td>
            </tr>
          </tbody>
        </table>
        <p v-else class="px-5 py-4 text-sm text-gray-400">{{ $t('sales.no_payments') }}</p>
      </div>

      <!-- Register pending payment -->
      <div v-if="pendingAmount > 0" class="rounded-xl bg-amber-50 shadow-sm ring-1 ring-amber-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-amber-200 flex items-center gap-2">
          <svg class="h-4 w-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
          <h2 class="text-sm font-semibold text-amber-800">{{ $t('payments.title') }} — ${{ formatNumber(pendingAmount) }} {{ $t('payments.pending') }}</h2>
        </div>
        <form class="px-5 py-4 flex flex-wrap items-end gap-4" @submit.prevent="submitPayment">
          <SelectField
            v-model="payForm.payment_method_id"
            :label="$t('payments.payment_method')"
            :options="paymentMethodOptions"
            :placeholder="$t('payments.select_payment_method')"
            :error="payErrors.payment_method_id?.[0]"
            required
            class="w-56"
          />
          <InputField
            v-model="payForm.amount"
            :label="$t('payments.amount')"
            type="number"
            step="0.01"
            min="0.01"
            :error="payErrors.amount?.[0]"
            required
            class="w-40"
          />
          <div class="pb-0.5">
            <button
              type="submit"
              :disabled="paying || !payForm.payment_method_id || !payForm.amount"
              class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              <svg v-if="paying" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 0 12 12h4a8 8 0 01-8 8z" />
              </svg>
              {{ paying ? $t('payments.registering') : $t('payments.register_payment') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Notes -->
      <div v-if="order.notes" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 mb-1">{{ $t('orders.notes') }}</p>
        <p class="text-sm text-gray-700">{{ order.notes }}</p>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pb-6">
        <Link href="/orders" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 transition">
          {{ $t('common.back') }}
        </Link>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-red-50 px-4 py-2 text-sm font-medium text-red-700 ring-1 ring-red-200 hover:bg-red-100 transition"
          @click="confirmOpen = true"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
          {{ $t('common.delete') }}
        </button>
      </div>
    </template>
  </div>

  <ConfirmModal v-model="confirmOpen" :title="$t('orders.delete_confirm')" @confirm="doDelete" />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import SelectField from '@/Components/SelectField.vue'
import InputField from '@/Components/InputField.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const props = defineProps({ uuid: String })

const { loading, get } = useApi()
const { patch: patchForm, loading: updatingState } = useApi()
const { del } = useApi()
const { loading: paying, errors: payErrors, post } = useApi()

const order = ref(null)
const confirmOpen = ref(false)
const orderStates = ref([])
const newStateId = ref(null)
const paymentMethods = ref([])
const payForm = ref({ payment_method_id: null, amount: '' })

const orderStateOptions = computed(() => orderStates.value.map(s => ({ value: s.id, label: s.name })))

const pendingAmount = computed(() => {
  if (!order.value) return 0
  return Math.max(0, Math.round((order.value.total - order.value.paid_amount) * 100) / 100)
})

const paymentMethodOptions = computed(() =>
  paymentMethods.value.filter((m) => m.is_active).map((m) => ({ value: m.id, label: m.name }))
)

async function fetchOrder() {
  const [orderRes, statesRes, methodsRes] = await Promise.all([
    get(`/api/orders/${props.uuid}`),
    get('/api/order-states'),
    get('/api/payment-methods'),
  ])
  if (orderRes.data) {
    order.value = orderRes.data.data
    newStateId.value = order.value.order_state?.id ?? null
    payForm.value.amount = String(Math.max(0, order.value.total - order.value.paid_amount))
  }
  if (statesRes.data) orderStates.value = statesRes.data.data ?? statesRes.data
  if (methodsRes.data) paymentMethods.value = methodsRes.data.data
}

async function submitPayment() {
  const { data } = await post('/api/payments', {
    payable_type: 'order',
    payable_id: props.uuid,
    payment_method_id: payForm.value.payment_method_id,
    amount: parseFloat(payForm.value.amount),
  })
  if (data) {
    payForm.value = { payment_method_id: null, amount: '' }
    await fetchOrder()
  }
}

async function updateState() {
  const result = await patchForm(`/api/orders/${props.uuid}/state`, { order_state_id: newStateId.value })
  if (result.data) {
    order.value.order_state = result.data.data?.order_state
  }
}

async function doDelete() {
  confirmOpen.value = false
  await del(`/api/orders/${props.uuid}`)
  router.visit('/orders')
}

function stateStyle(state) {
  if (state?.color) {
    return { backgroundColor: state.color + '20', color: state.color, borderColor: state.color + '40' }
  }
  return { backgroundColor: '#f3f4f6', color: '#374151', borderColor: '#e5e7eb' }
}

function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString()
}

function formatNumber(value) {
  const num = parseFloat(value) || 0
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

onMounted(fetchOrder)
</script>
