<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-xl font-semibold text-gray-900">{{ $t('payments.title') }}</h1>
      <p class="mt-0.5 text-sm text-gray-500">{{ $t('payments.subtitle') }}</p>
    </div>

    <!-- Success banner -->
    <div
      v-if="successMessage"
      class="flex items-center gap-2 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 ring-1 ring-green-200"
    >
      <svg class="h-4 w-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
      </svg>
      {{ successMessage }}
    </div>

    <div class="flex gap-6 items-start">
      <!-- Left: pending list -->
      <div class="w-full max-w-lg shrink-0">
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
          <!-- Filters -->
          <div class="border-b border-gray-200 px-4 py-3 space-y-3">
            <SearchInput v-model="search" :placeholder="$t('payments.search_placeholder')" class="w-full" />
            <div class="flex gap-1">
              <button
                v-for="f in typeFilters"
                :key="f.value"
                type="button"
                :class="[
                  'rounded-md px-3 py-1 text-xs font-medium transition',
                  typeFilter === f.value
                    ? 'bg-indigo-600 text-white'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                ]"
                @click="typeFilter = f.value"
              >
                {{ f.label }}
              </button>
            </div>
          </div>

          <!-- Loading skeletons -->
          <div v-if="loadingList" class="divide-y divide-gray-100">
            <div v-for="i in 5" :key="i" class="flex items-center gap-3 px-4 py-3.5 animate-pulse">
              <div class="h-8 w-8 rounded-full bg-gray-200 shrink-0" />
              <div class="flex-1 space-y-1.5">
                <div class="h-3.5 w-40 rounded bg-gray-200" />
                <div class="h-3 w-24 rounded bg-gray-200" />
              </div>
              <div class="h-4 w-16 rounded bg-gray-200" />
            </div>
          </div>

          <!-- Empty -->
          <EmptyState
            v-else-if="pendingItems.length === 0"
            :title="$t('payments.no_pending')"
            :subtitle="$t('payments.no_pending_hint')"
          />

          <!-- List -->
          <ul v-else class="divide-y divide-gray-100 max-h-[580px] overflow-y-auto">
            <li
              v-for="item in pendingItems"
              :key="`${item.type}-${item.id}`"
              :class="[
                'group flex items-center gap-3 px-4 py-3.5 cursor-pointer transition',
                isSelected(item)
                  ? 'bg-indigo-50 ring-inset ring-1 ring-indigo-200'
                  : 'hover:bg-gray-50',
              ]"
              @click="selectItem(item)"
            >
              <!-- Type icon -->
              <div :class="[
                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                item.type === 'sale' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700',
              ]">
                {{ item.type === 'sale' ? 'V' : 'P' }}
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <p class="truncate text-sm font-medium text-gray-900">
                  {{ item.client ?? $t('sales.no_client') }}
                </p>
                <p class="text-xs text-gray-400 truncate">
                  <span :class="item.type === 'sale' ? 'text-indigo-600' : 'text-amber-600'" class="font-medium">
                    {{ item.type === 'sale' ? $t('payments.type_sale') : $t('payments.type_order') }}
                  </span>
                  <template v-if="item.reference"> · {{ item.reference }}</template>
                  · {{ formatDate(item.created_at) }}
                </p>
              </div>

              <!-- Pending amount -->
              <div class="text-right shrink-0">
                <p class="text-xs text-gray-400 tabular-nums">${{ formatNumber(item.total) }}</p>
                <p class="text-sm font-semibold text-red-600 tabular-nums">-${{ formatNumber(item.pending_amount) }}</p>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Right: payment form -->
      <div class="flex-1 min-w-0">
        <!-- Placeholder -->
        <div
          v-if="!selected"
          class="flex h-64 items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-white text-center"
        >
          <div>
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
              <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-500">{{ $t('payments.select_to_pay') }}</p>
          </div>
        </div>

        <!-- Payment form -->
        <div v-else class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
          <!-- Selected summary -->
          <div class="border-b border-gray-200 px-5 py-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <span :class="[
                  'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium mb-1',
                  selected.type === 'sale' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700',
                ]">
                  {{ selected.type === 'sale' ? $t('payments.type_sale') : $t('payments.type_order') }}
                </span>
                <h2 class="text-sm font-semibold text-gray-900">
                  {{ selected.client ?? $t('sales.no_client') }}
                </h2>
                <p class="text-xs text-gray-400">{{ selected.reference }} · {{ formatDate(selected.created_at) }}</p>
              </div>
              <button
                type="button"
                class="shrink-0 rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                @click="selected = null"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Balance cards -->
            <div class="mt-4 grid grid-cols-3 gap-3">
              <div class="rounded-lg bg-gray-50 px-3 py-2.5 text-center">
                <p class="text-xs text-gray-400">{{ $t('payments.total') }}</p>
                <p class="mt-0.5 text-sm font-bold text-gray-900 tabular-nums">${{ formatNumber(selected.total) }}</p>
              </div>
              <div class="rounded-lg bg-green-50 px-3 py-2.5 text-center">
                <p class="text-xs text-green-500">{{ $t('payments.paid') }}</p>
                <p class="mt-0.5 text-sm font-bold text-green-700 tabular-nums">${{ formatNumber(selected.paid_amount) }}</p>
              </div>
              <div class="rounded-lg bg-red-50 px-3 py-2.5 text-center">
                <p class="text-xs text-red-400">{{ $t('payments.pending') }}</p>
                <p class="mt-0.5 text-sm font-bold text-red-600 tabular-nums">${{ formatNumber(selected.pending_amount) }}</p>
              </div>
            </div>
          </div>

          <!-- Fields -->
          <form class="px-5 py-5 space-y-4" @submit.prevent="submitPayment">
            <SelectField
              v-model="form.payment_method_id"
              :label="$t('payments.payment_method')"
              :options="paymentMethodOptions"
              :placeholder="$t('payments.select_payment_method')"
              :error="formErrors.payment_method_id?.[0]"
              required
            />

            <InputField
              v-model="form.amount"
              :label="$t('payments.amount')"
              type="number"
              step="0.01"
              min="0.01"
              :placeholder="$t('payments.amount_placeholder')"
              :error="formErrors.amount?.[0]"
              required
            />

            <!-- Overpayment warning -->
            <div
              v-if="form.amount && parseFloat(form.amount) > selected.pending_amount"
              class="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2.5 text-xs text-amber-700"
            >
              <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
              {{ $t('payments.overpayment_warning', { pending: formatNumber(selected.pending_amount) }) }}
            </div>

            <TextareaField
              v-model="form.notes"
              :label="$t('payments.notes')"
              :rows="2"
              :error="formErrors.notes?.[0]"
            />

            <div class="flex justify-end pt-1">
              <button
                type="submit"
                :disabled="submitting || !form.payment_method_id || !form.amount"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg v-if="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 0 12h4a8 8 0 01-8 8z" />
                </svg>
                {{ submitting ? $t('payments.registering') : $t('payments.register_payment') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchInput from '@/Components/SearchInput.vue'
import EmptyState from '@/Components/EmptyState.vue'
import SelectField from '@/Components/SelectField.vue'
import InputField from '@/Components/InputField.vue'
import TextareaField from '@/Components/TextareaField.vue'
import { useApi } from '@/composables/useApi'
import { useTranslation } from '@/composables/useTranslation'

defineOptions({ layout: AppLayout })

const { t } = useTranslation()

const { loading: loadingList, get: getList } = useApi()
const { loading: submitting, errors: formErrors, post } = useApi()

const pendingItems = ref([])
const paymentMethods = ref([])
const selected = ref(null)
const search = ref('')
const typeFilter = ref('all')
const successMessage = ref('')

const form = ref({
  payment_method_id: null,
  amount: '',
  notes: '',
})

const typeFilters = computed(() => [
  { value: 'all', label: t('payments.filter_all') },
  { value: 'sale', label: t('payments.filter_sale') },
  { value: 'order', label: t('payments.filter_order') },
])

const paymentMethodOptions = computed(() =>
  paymentMethods.value
    .filter((m) => m.is_active)
    .map((m) => ({ value: m.id, label: m.name }))
)

function isSelected(item) {
  return selected.value?.id === item.id && selected.value?.type === item.type
}

async function fetchPending() {
  const params = { type: typeFilter.value }
  if (search.value) params.search = search.value
  const { data } = await getList('/api/payments/pending', params)
  if (data) pendingItems.value = data.data
}

async function fetchPaymentMethods() {
  const { data } = await getList('/api/payment-methods')
  if (data) paymentMethods.value = data.data
}

function selectItem(item) {
  selected.value = item
  successMessage.value = ''
  form.value = {
    payment_method_id: null,
    amount: String(item.pending_amount),
    notes: '',
  }
}

async function submitPayment() {
  successMessage.value = ''
  const { data } = await post('/api/payments', {
    payable_type: selected.value.type,
    payable_id: selected.value.id,
    payment_method_id: form.value.payment_method_id,
    amount: parseFloat(form.value.amount),
    notes: form.value.notes || null,
  })

  if (data) {
    successMessage.value = t('payments.success')
    selected.value = null
    await fetchPending()
  }
}

function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString()
}

function formatNumber(value) {
  const num = parseFloat(value) || 0
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

let searchDebounce
watch(search, () => {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(fetchPending, 300)
})
watch(typeFilter, fetchPending)

onMounted(() => {
  fetchPending()
  fetchPaymentMethods()
})
</script>
