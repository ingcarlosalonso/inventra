<template>
  <div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-4">
      <Link href="/daily-cashes" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </Link>
      <div class="flex-1">
        <div class="flex items-center gap-3">
          <h1 class="text-xl font-semibold text-gray-900">{{ $t('daily_cashes.detail_title') }}</h1>
          <span
            v-if="dailyCash"
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
            :class="dailyCash.is_closed
              ? 'bg-gray-50 text-gray-600 ring-gray-200'
              : 'bg-emerald-50 text-emerald-700 ring-emerald-200'"
          >
            {{ dailyCash.is_closed ? $t('daily_cashes.status_closed') : $t('daily_cashes.status_open') }}
          </span>
        </div>
        <p v-if="dailyCash" class="mt-0.5 text-sm text-gray-500">
          {{ dailyCash.point_of_sale?.name }} · {{ formatDateTime(dailyCash.opened_at) }}
        </p>
      </div>

      <!-- Actions header -->
      <div v-if="dailyCash && !dailyCash.is_closed" class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition"
          @click="openAddMovement"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          {{ $t('daily_cashes.add_movement') }}
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100 transition"
          @click="openClose"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
          </svg>
          {{ $t('daily_cashes.close_action') }}
        </button>
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

    <template v-else-if="dailyCash">
      <!-- Summary cards -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('daily_cashes.point_of_sale') }}</p>
          <p class="mt-1 text-sm font-semibold text-gray-900">{{ dailyCash.point_of_sale?.name }}</p>
        </div>
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('daily_cashes.opened_at') }}</p>
          <p class="mt-1 text-sm font-semibold text-gray-900">{{ formatDateTime(dailyCash.opened_at) }}</p>
        </div>
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('daily_cashes.opening_balance') }}</p>
          <p class="mt-1 text-sm font-semibold text-gray-900 tabular-nums">${{ formatNumber(dailyCash.opening_balance) }}</p>
        </div>
        <div
          class="rounded-xl shadow-sm p-5"
          :class="dailyCash.is_closed ? 'bg-gray-800 ring-1 ring-gray-700' : 'bg-indigo-600'"
        >
          <p class="text-xs font-medium uppercase tracking-wide" :class="dailyCash.is_closed ? 'text-gray-400' : 'text-indigo-200'">
            {{ dailyCash.is_closed ? $t('daily_cashes.closing_balance') : $t('daily_cashes.status_open') }}
          </p>
          <p class="mt-1 text-2xl font-bold tabular-nums text-white">
            ${{ formatNumber(dailyCash.is_closed ? dailyCash.closing_balance : dailyCash.current_balance) }}
          </p>
        </div>
      </div>

      <!-- Unified movements card -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
          <div>
            <h2 class="text-sm font-semibold text-gray-700">{{ $t('daily_cashes.movements_section') }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ allMovements.length }} {{ $t('daily_cashes.movements_count') }}</p>
          </div>
          <button
            v-if="!dailyCash.is_closed"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition"
            @click="openAddMovement"
          >
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            {{ $t('daily_cashes.add_movement') }}
          </button>
        </div>

        <EmptyState
          v-if="allMovements.length === 0"
          :title="$t('daily_cashes.no_movements')"
          :subtitle="dailyCash.is_closed ? null : $t('daily_cashes.no_movements_subtitle')"
        />

        <table v-else class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
              <th class="px-5 py-3">{{ $t('daily_cashes.movement_type') }}</th>
              <th class="px-3 py-3">{{ $t('daily_cashes.movement_notes') }}</th>
              <th class="px-3 py-3">{{ $t('common.date') }}</th>
              <th class="px-3 py-3 text-right">{{ $t('daily_cashes.movement_amount') }}</th>
              <th v-if="!dailyCash.is_closed" class="px-3 py-3 w-10" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="entry in allMovements" :key="entry._key" class="hover:bg-gray-50 transition">
              <td class="px-5 py-3">
                <span
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                  :class="entry.is_income
                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                    : 'bg-rose-50 text-rose-700 ring-rose-200'"
                >
                  {{ entry.label }}
                </span>
                <span v-if="entry.sub_label" class="ml-2 text-xs text-gray-400">{{ entry.sub_label }}</span>
              </td>
              <td class="px-3 py-3 text-gray-500 text-xs max-w-xs truncate">{{ entry.notes ?? '—' }}</td>
              <td class="px-3 py-3 text-xs text-gray-400">{{ formatDateTime(entry.created_at) }}</td>
              <td class="px-3 py-3 text-right tabular-nums font-semibold" :class="entry.is_income ? 'text-emerald-700' : 'text-rose-700'">
                {{ entry.is_income ? '+' : '-' }}${{ formatNumber(entry.amount) }}
              </td>
              <td v-if="!dailyCash.is_closed" class="px-3 py-3">
                <button
                  v-if="entry.deletable"
                  type="button"
                  class="rounded-md p-1 text-gray-300 hover:bg-gray-100 hover:text-red-600 transition"
                  @click="confirmDeleteMovement(entry.raw)"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Notes -->
      <div v-if="dailyCash.notes" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 mb-1">{{ $t('daily_cashes.notes') }}</p>
        <p class="text-sm text-gray-700">{{ dailyCash.notes }}</p>
      </div>
    </template>
  </div>

  <!-- Add Movement SlideOver -->
  <SlideOver v-model="movementSlideOverOpen" :title="$t('daily_cashes.add_movement')">
    <form class="space-y-5" @submit.prevent="saveMovement">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          {{ $t('daily_cashes.movement_type') }} *
        </label>
        <select
          v-model="movementForm.cash_movement_type_id"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="">{{ $t('common.select') }}</option>
          <optgroup :label="$t('cash_movement_types.direction_in')">
            <option v-for="t in incomeTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
          </optgroup>
          <optgroup :label="$t('cash_movement_types.direction_out')">
            <option v-for="t in outcomeTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
          </optgroup>
        </select>
        <p v-if="movementErrors.cash_movement_type_id" class="mt-1 text-xs text-red-600">
          {{ movementErrors.cash_movement_type_id[0] }}
        </p>
      </div>
      <InputField
        v-model.number="movementForm.amount"
        type="number"
        step="0.01"
        min="0.01"
        :label="$t('daily_cashes.movement_amount')"
        :error="movementErrors.amount?.[0]"
        required
      />
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('daily_cashes.movement_notes') }}</label>
        <textarea
          v-model="movementForm.notes"
          rows="3"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        />
      </div>
      <div v-if="movementError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
        {{ movementError }}
      </div>
    </form>
    <template #footer>
      <div class="flex justify-end gap-3">
        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50" @click="movementSlideOverOpen = false">
          {{ $t('common.cancel') }}
        </button>
        <button type="button" :disabled="savingMovement" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition" @click="saveMovement">
          <svg v-if="savingMovement" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          {{ $t('common.save') }}
        </button>
      </div>
    </template>
  </SlideOver>

  <!-- Close Cash SlideOver -->
  <SlideOver v-model="closeSlideOverOpen" :title="$t('daily_cashes.close_title')">
    <form class="space-y-5" @submit.prevent="closeCash">
      <p class="text-sm text-gray-600">{{ $t('daily_cashes.close_confirm') }}</p>
      <InputField
        v-model.number="closeForm.closing_balance"
        type="number"
        step="0.01"
        min="0"
        :label="$t('daily_cashes.closing_balance')"
        :error="closeErrors.closing_balance?.[0]"
        required
      />
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('daily_cashes.notes') }}</label>
        <textarea
          v-model="closeForm.notes"
          rows="3"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        />
      </div>
      <div v-if="closeError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
        {{ closeError }}
      </div>
    </form>
    <template #footer>
      <div class="flex justify-end gap-3">
        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50" @click="closeSlideOverOpen = false">
          {{ $t('common.cancel') }}
        </button>
        <button type="button" :disabled="closingCash" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-60 transition" @click="closeCash">
          <svg v-if="closingCash" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          {{ $t('daily_cashes.close_action') }}
        </button>
      </div>
    </template>
  </SlideOver>

  <ConfirmModal
    v-model="confirmMovementDelete"
    :title="$t('daily_cashes.movement_delete_confirm')"
    @confirm="doDeleteMovement"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTranslation } from '@/composables/useTranslation'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SlideOver from '@/Components/SlideOver.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import EmptyState from '@/Components/EmptyState.vue'
import InputField from '@/Components/InputField.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const props = defineProps({ uuid: String })

const { t } = useTranslation()

const { loading, get } = useApi()
const { loading: savingMovement, errors: movementErrors, post: postMovement } = useApi()
const { loading: closingCash, errors: closeErrors, post: postClose } = useApi()
const { del } = useApi()

const dailyCash = ref(null)
const movementTypes = ref([])

const movementSlideOverOpen = ref(false)
const closeSlideOverOpen = ref(false)
const confirmMovementDelete = ref(false)
const movementToDelete = ref(null)
const movementError = ref(null)
const closeError = ref(null)

const emptyMovementForm = () => ({ cash_movement_type_id: '', amount: null, notes: '' })
const movementForm = ref(emptyMovementForm())
const closeForm = ref({ closing_balance: null, notes: '' })

// Unified ledger: merges sale payments (always income) + extra movements, sorted by date
const allMovements = computed(() => {
  if (!dailyCash.value) return []

  const payments = (dailyCash.value.payments ?? []).map(p => ({
    _key: `payment-${p.id}`,
    is_income: true,
    label: p.payable_type === 'order' ? t('daily_cashes.order_payment') : t('daily_cashes.sale_payment'),
    sub_label: p.payment_method?.name ?? null,
    notes: p.notes,
    amount: p.amount,
    created_at: p.created_at,
    deletable: false,
    raw: null,
  }))

  const movements = (dailyCash.value.cash_movements ?? []).map(m => ({
    _key: `movement-${m.id}`,
    is_income: m.cash_movement_type?.is_income ?? true,
    label: m.cash_movement_type?.name ?? '—',
    sub_label: null,
    notes: m.notes,
    amount: m.amount,
    created_at: m.created_at,
    deletable: true,
    raw: m,
  }))

  return [...payments, ...movements].sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
})

const incomeTypes = computed(() => movementTypes.value.filter(t => t.is_income && t.is_active))
const outcomeTypes = computed(() => movementTypes.value.filter(t => !t.is_income && t.is_active))

async function fetchDailyCash() {
  const { data } = await get(`/api/daily-cashes/${props.uuid}`)
  if (data) dailyCash.value = data.data
}

async function fetchMovementTypes() {
  const { data } = await get('/api/cash-movement-types', { per_page: 100 })
  if (data) movementTypes.value = data.data
}

function openAddMovement() {
  movementForm.value = emptyMovementForm()
  movementError.value = null
  movementSlideOverOpen.value = true
}

function openClose() {
  closeForm.value = { closing_balance: parseFloat(dailyCash.value?.opening_balance) || 0, notes: '' }
  closeError.value = null
  closeSlideOverOpen.value = true
}

async function saveMovement() {
  movementError.value = null
  const result = await postMovement(`/api/daily-cashes/${props.uuid}/movements`, movementForm.value)
  if (result.error) {
    if (!Object.keys(movementErrors.value).length) movementError.value = result.error
    return
  }
  movementSlideOverOpen.value = false
  await fetchDailyCash()
}

async function closeCash() {
  closeError.value = null
  const result = await postClose(`/api/daily-cashes/${props.uuid}/close`, closeForm.value)
  if (result.error) {
    if (!Object.keys(closeErrors.value).length) closeError.value = result.error
    return
  }
  closeSlideOverOpen.value = false
  await fetchDailyCash()
}

function confirmDeleteMovement(mv) {
  movementToDelete.value = mv
  confirmMovementDelete.value = true
}

async function doDeleteMovement() {
  confirmMovementDelete.value = false
  await del(`/api/daily-cashes/${props.uuid}/movements/${movementToDelete.value.id}`)
  await fetchDailyCash()
}

function formatDateTime(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString()
}

function formatNumber(value) {
  const num = parseFloat(value) || 0
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

onMounted(() => { fetchDailyCash(); fetchMovementTypes() })
</script>
