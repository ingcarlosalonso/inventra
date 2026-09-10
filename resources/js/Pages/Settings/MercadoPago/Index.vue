<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-gray-900">{{ $t('mercadopago.title') }}</h1>
      <p class="mt-0.5 text-sm text-gray-500">{{ $t('mercadopago.subtitle') }}</p>
    </div>

    <div
      v-if="banner"
      :class="[
        'flex items-center gap-2 rounded-lg px-4 py-3 text-sm ring-1',
        banner.type === 'success' ? 'bg-green-50 text-green-700 ring-green-200' : 'bg-red-50 text-red-700 ring-red-200',
      ]"
    >
      {{ banner.message }}
    </div>

    <!-- Connection card -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
      <div v-if="loadingStatus" class="animate-pulse space-y-2">
        <div class="h-4 w-48 rounded bg-gray-200" />
        <div class="h-3 w-32 rounded bg-gray-200" />
      </div>

      <div v-else class="flex items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <StatusBadge :active="status.connected" />
            <h2 class="text-sm font-semibold text-gray-900">
              {{ status.connected ? $t('mercadopago.connected') : $t('mercadopago.not_connected') }}
            </h2>
          </div>
          <p v-if="status.connected" class="mt-1 text-xs text-gray-500">
            {{ $t('mercadopago.connected_since', { date: formatDate(status.connected_at) }) }}
            <span v-if="!status.live_mode"> · {{ $t('mercadopago.test_mode') }}</span>
          </p>
          <p v-else class="mt-1 text-xs text-gray-500">{{ $t('mercadopago.not_connected_hint') }}</p>
        </div>

        <button
          v-if="!status.connected"
          type="button"
          :disabled="connecting"
          class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition disabled:opacity-50"
          @click="connect"
        >
          {{ $t('mercadopago.connect') }}
        </button>
        <button
          v-else
          type="button"
          class="rounded-lg px-4 py-2 text-sm font-medium text-red-600 ring-1 ring-red-200 hover:bg-red-50 transition"
          @click="confirmDisconnectOpen = true"
        >
          {{ $t('mercadopago.disconnect') }}
        </button>
      </div>

      <div v-if="status.connected" class="mt-5 border-t border-gray-100 pt-5">
        <SelectField
          v-model="paymentMethodId"
          :label="$t('mercadopago.payment_method_label')"
          :options="paymentMethodOptions"
          :placeholder="$t('payments.select_payment_method')"
          :error="formErrors.payment_method_id?.[0]"
        />
        <button
          type="button"
          :disabled="!paymentMethodId || savingPaymentMethod"
          class="mt-3 rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition disabled:opacity-50"
          @click="savePaymentMethod"
        >
          {{ $t('common.save') }}
        </button>
      </div>
    </div>

    <!-- Terminal assignment -->
    <div v-if="status.connected" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
      <div class="border-b border-gray-200 px-5 py-3.5">
        <h2 class="text-sm font-semibold text-gray-900">{{ $t('mercadopago.terminals_title') }}</h2>
        <p class="text-xs text-gray-500">{{ $t('mercadopago.terminals_subtitle') }}</p>
      </div>

      <div v-if="loadingPos" class="divide-y divide-gray-100">
        <div v-for="i in 3" :key="i" class="flex items-center gap-4 px-5 py-4 animate-pulse">
          <div class="h-4 w-40 rounded bg-gray-200 flex-1" />
          <div class="h-8 w-48 rounded bg-gray-200" />
        </div>
      </div>

      <EmptyState v-else-if="pointsOfSale.length === 0" :title="$t('common.empty')" />

      <ul v-else class="divide-y divide-gray-100">
        <li v-for="pos in pointsOfSale" :key="pos.id" class="flex items-center gap-4 px-5 py-3.5">
          <p class="flex-1 truncate text-sm font-medium text-gray-900">{{ pos.name }}</p>
          <select
            class="w-56 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :value="pos.mercado_pago_terminal_id ?? ''"
            @change="assignTerminal(pos, $event.target.value)"
          >
            <option value="">{{ $t('mercadopago.no_terminal') }}</option>
            <option v-for="terminal in terminals" :key="terminal.id" :value="terminal.id">
              {{ terminal.external_pos_id ?? terminal.id }}
            </option>
          </select>
        </li>
      </ul>
    </div>
  </div>

  <ConfirmModal
    v-model="confirmDisconnectOpen"
    :title="$t('mercadopago.disconnect_confirm')"
    @confirm="disconnect"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import SelectField from '@/Components/SelectField.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { useApi } from '@/composables/useApi'
import { useTranslation } from '@/composables/useTranslation'

defineOptions({ layout: AppLayout })

const { t } = useTranslation()

const { loading: loadingStatus, get: getStatus } = useApi()
const { loading: connecting, get: getConnectUrl } = useApi()
const { loading: loadingPos, get: getPointsOfSale } = useApi()
const { get: getTerminals } = useApi()
const { get: getPaymentMethods } = useApi()
const { loading: savingPaymentMethod, errors: formErrors, patch: patchSettings } = useApi()
const { del: deleteConnection } = useApi()
const { patch: patchTerminal } = useApi()

const status = ref({ connected: false })
const pointsOfSale = ref([])
const terminals = ref([])
const paymentMethods = ref([])
const paymentMethodId = ref(null)
const confirmDisconnectOpen = ref(false)
const banner = ref(null)

const paymentMethodOptions = computed(() =>
  paymentMethods.value.filter((m) => m.is_active).map((m) => ({ value: m.id, label: m.name }))
)

async function fetchStatus() {
  const { data } = await getStatus('/api/v1/settings/mercado-pago')
  if (data) {
    status.value = data.data
    if (status.value.connected) {
      await Promise.all([fetchPointsOfSale(), fetchTerminals(), fetchPaymentMethods()])
    }
  }
}

async function fetchPointsOfSale() {
  const { data } = await getPointsOfSale('/api/v1/sales/points-of-sale')
  if (data) pointsOfSale.value = data.data
}

async function fetchTerminals() {
  const { data } = await getTerminals('/api/v1/settings/mercado-pago/terminals')
  if (data) terminals.value = data.data
}

async function fetchPaymentMethods() {
  const { data } = await getPaymentMethods('/api/v1/sales/payment-methods')
  if (data) paymentMethods.value = data.data
}

async function connect() {
  const { data } = await getConnectUrl('/api/v1/settings/mercado-pago/connect')
  if (data?.url) window.location.href = data.url
}

async function disconnect() {
  confirmDisconnectOpen.value = false
  await deleteConnection('/api/v1/settings/mercado-pago')
  await fetchStatus()
}

async function savePaymentMethod() {
  const result = await patchSettings('/api/v1/settings/mercado-pago', { payment_method_id: paymentMethodId.value })
  if (!result.error) await fetchStatus()
}

async function assignTerminal(pos, terminalId) {
  await patchTerminal(`/api/v1/sales/points-of-sale/${pos.id}/mercado-pago-terminal`, { terminal_id: terminalId || null })
  await fetchPointsOfSale()
}

function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('es-AR')
}

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  if (params.get('mercadopago_connected')) {
    banner.value = { type: 'success', message: t('mercadopago.connect_success') }
  } else if (params.get('mercadopago_error')) {
    banner.value = { type: 'error', message: t('mercadopago.connect_error') }
  }
  if (banner.value) window.history.replaceState({}, '', window.location.pathname)

  fetchStatus()
})
</script>
