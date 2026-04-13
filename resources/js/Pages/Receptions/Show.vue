<template>
  <div class="max-w-5xl mx-auto space-y-6">

    <!-- Page header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Link href="/receptions" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
        </Link>
        <div>
          <h1 class="text-xl font-semibold text-gray-900">{{ $t('receptions.detail_title') }}</h1>
          <p v-if="reception" class="mt-0.5 text-sm text-gray-500">
            {{ formatDate(reception.received_at) }}
            <span v-if="reception.supplier_invoice"> · {{ reception.supplier_invoice }}</span>
          </p>
        </div>
      </div>

      <button
        v-if="reception"
        type="button"
        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-red-600 ring-1 ring-red-200 hover:bg-red-50 transition"
        @click="confirmOpen = true"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
        </svg>
        {{ $t('common.delete') }}
      </button>
    </div>

    <!-- Loading -->
    <template v-if="loading">
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5 animate-pulse space-y-3">
        <div class="h-4 w-48 rounded bg-gray-200" />
        <div class="grid grid-cols-4 gap-4">
          <div v-for="i in 4" :key="i" class="h-14 rounded-lg bg-gray-100" />
        </div>
      </div>
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 animate-pulse">
        <div class="h-10 border-b border-gray-100 bg-gray-50 rounded-t-xl" />
        <div v-for="i in 4" :key="i" class="flex gap-4 px-5 py-4 border-b border-gray-100">
          <div class="h-4 flex-1 rounded bg-gray-200" />
          <div class="h-4 w-20 rounded bg-gray-200" />
          <div class="h-4 w-20 rounded bg-gray-200" />
          <div class="h-4 w-24 rounded bg-gray-200" />
        </div>
      </div>
    </template>

    <template v-else-if="reception">
      <!-- Header info cards -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $t('receptions.supplier') }}</p>
          <p class="mt-1 text-sm font-semibold text-gray-900 truncate">{{ reception.supplier?.name ?? $t('common.no_supplier') }}</p>
        </div>
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $t('receptions.received_at') }}</p>
          <p class="mt-1 text-sm font-semibold text-gray-900">{{ formatDate(reception.received_at) }}</p>
        </div>
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $t('receptions.supplier_invoice') }}</p>
          <p class="mt-1 text-sm font-semibold text-gray-900 truncate">{{ reception.supplier_invoice || '—' }}</p>
        </div>
        <div class="rounded-xl bg-indigo-600 shadow-sm p-4">
          <p class="text-xs font-medium text-indigo-200 uppercase tracking-wide">{{ $t('receptions.total') }}</p>
          <p class="mt-1 text-xl font-bold text-white tabular-nums">${{ formatNumber(reception.total) }}</p>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="reception.notes" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 px-5 py-4">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">{{ $t('receptions.notes') }}</p>
        <p class="text-sm text-gray-700 whitespace-pre-line">{{ reception.notes }}</p>
      </div>

      <!-- Items table -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('receptions.items') }}</h2>
          <span class="text-xs text-gray-400">{{ reception.items?.length ?? 0 }} {{ $t('receptions.items_added') }}</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                <th class="px-5 py-3 w-8">#</th>
                <th class="px-3 py-3">{{ $t('products.name') }}</th>
                <th class="px-3 py-3">{{ $t('products.presentation') }}</th>
                <th class="px-3 py-3 w-28 text-right">{{ $t('receptions.quantity') }}</th>
                <th class="px-3 py-3 w-32 text-right">{{ $t('receptions.unit_cost') }}</th>
                <th class="px-3 py-3 w-32 text-right">{{ $t('receptions.item_total') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(item, index) in reception.items"
                :key="item.id"
                class="hover:bg-gray-50 transition"
              >
                <td class="px-5 py-3.5 text-xs text-gray-400 tabular-nums">{{ index + 1 }}</td>
                <td class="px-3 py-3.5">
                  <span class="font-medium text-gray-900">{{ item.product_presentation?.product?.name ?? '—' }}</span>
                </td>
                <td class="px-3 py-3.5 text-gray-500">
                  {{ item.product_presentation?.presentation?.display ?? '—' }}
                </td>
                <td class="px-3 py-3.5 text-right tabular-nums text-gray-700">
                  {{ formatQty(item.quantity) }}
                </td>
                <td class="px-3 py-3.5 text-right tabular-nums text-gray-700">
                  ${{ formatNumber(item.unit_cost) }}
                </td>
                <td class="px-3 py-3.5 text-right tabular-nums font-semibold text-gray-900">
                  ${{ formatNumber(item.total) }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="border-t-2 border-gray-200 bg-gray-50">
                <td colspan="5" class="px-5 py-3 text-sm font-medium text-gray-500 text-right">{{ $t('receptions.total') }}</td>
                <td class="px-3 py-3 text-right text-base font-bold text-gray-900 tabular-nums">${{ formatNumber(reception.total) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Meta -->
      <div class="text-xs text-gray-400 text-right pb-4">
        {{ $t('common.created_at') }}: {{ formatDateTime(reception.created_at) }}
        <span v-if="reception.user"> · {{ reception.user.name }}</span>
      </div>
    </template>
  </div>

  <ConfirmModal v-model="confirmOpen" :title="$t('receptions.delete_confirm')" @confirm="doDelete" />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const props = defineProps({
  uuid: { type: String, required: true },
})

const { loading, get } = useApi()
const { del } = useApi()

const reception = ref(null)
const confirmOpen = ref(false)

async function fetchReception() {
  const { data } = await get(`/api/receptions/${props.uuid}`)
  if (data) reception.value = data.data
}

async function doDelete() {
  confirmOpen.value = false
  await del(`/api/receptions/${props.uuid}`)
  router.visit('/receptions')
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr + 'T00:00:00').toLocaleDateString()
}
function formatDateTime(isoStr) {
  if (!isoStr) return ''
  return new Date(isoStr).toLocaleString()
}
function formatNumber(value) {
  const num = parseFloat(value) || 0
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function formatQty(value) {
  const num = parseFloat(value) || 0
  return num % 1 === 0 ? num.toFixed(0) : num.toLocaleString(undefined, { maximumFractionDigits: 3 })
}

onMounted(fetchReception)
</script>
