<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Link href="/reports" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
        </Link>
        <div>
          <h1 class="text-xl font-bold text-gray-900">{{ $t('reports.inventory_title') }}</h1>
          <p class="text-sm text-gray-500">{{ $t('reports.inventory_desc') }}</p>
        </div>
      </div>
      <button @click="exportXlsx" :disabled="exporting || !data" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50 transition">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        {{ exporting ? $t('common.exporting') : $t('reports.export_excel') }}
      </button>
    </div>

    <!-- Filters -->
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
      <div class="flex flex-wrap items-end gap-3">
        <div v-if="data?.filters?.productTypes?.length" class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.product_type') }}</label>
          <select v-model="filters.product_type_id" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
            <option value="">{{ $t('reports.all_categories') }}</option>
            <option v-for="pt in data.filters.productTypes" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
          </select>
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.stock_status') }}</label>
          <select v-model="filters.stock_status" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
            <option value="all">{{ $t('reports.all_stock') }}</option>
            <option value="low">{{ $t('reports.stock_low') }}</option>
            <option value="out">{{ $t('reports.stock_out') }}</option>
            <option value="ok">{{ $t('reports.stock_ok') }}</option>
          </select>
        </div>
        <button @click="fetchData" :disabled="loading" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 transition">
          <svg v-if="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          {{ $t('reports.apply') }}
        </button>
      </div>
    </div>

    <template v-if="loading && !data">
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div v-for="n in 4" :key="n" class="h-24 animate-pulse rounded-xl bg-gray-100" />
      </div>
    </template>

    <div v-else-if="error" class="flex h-32 items-center justify-center rounded-xl bg-red-50 text-sm text-red-600 ring-1 ring-red-200">
      {{ error }}
    </div>

    <template v-else-if="data">
      <!-- KPIs -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
          <p class="text-xs text-gray-500">{{ $t('reports.total_items') }}</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ data.kpis.total_items }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-emerald-200 bg-emerald-50">
          <p class="text-xs text-emerald-700">{{ $t('reports.ok_count') }}</p>
          <p class="mt-1 text-2xl font-bold text-emerald-700">{{ data.kpis.ok_count }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-amber-200 bg-amber-50">
          <p class="text-xs text-amber-700">{{ $t('reports.low_stock_count') }}</p>
          <p class="mt-1 text-2xl font-bold text-amber-700">{{ data.kpis.low_stock_count }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-red-200 bg-red-50">
          <p class="text-xs text-red-700">{{ $t('reports.out_of_stock_count') }}</p>
          <p class="mt-1 text-2xl font-bold text-red-700">{{ data.kpis.out_of_stock_count }}</p>
        </div>
      </div>

      <!-- Table -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5">
          <h2 class="text-sm font-semibold text-gray-900">{{ data.meta.total }} {{ $t('reports.total_records') }}</h2>
          <input v-model="search" type="text" :placeholder="$t('common.search')" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none w-48" />
        </div>
        <div v-if="filteredTable.length === 0" class="flex h-32 items-center justify-center text-sm text-gray-400">
          {{ $t('reports.no_data') }}
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
              <tr>
                <th class="px-5 py-3 text-left">{{ $t('reports.product_name') }}</th>
                <th class="px-5 py-3 text-left">{{ $t('reports.product_type') }}</th>
                <th class="px-5 py-3 text-left">{{ $t('reports.presentation') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.stock') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.min_stock') }}</th>
                <th class="px-5 py-3 text-center">{{ $t('common.state') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="(row, i) in filteredTable" :key="i" class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3 font-medium text-gray-900">{{ row.product_name }}</td>
                <td class="px-5 py-3 text-gray-500">{{ row.product_type ?? '—' }}</td>
                <td class="px-5 py-3 text-gray-500">{{ row.presentation }}</td>
                <td class="px-5 py-3 text-right" :class="row.status === 'out' ? 'font-bold text-red-600' : row.status === 'low' ? 'font-semibold text-amber-600' : 'text-gray-900'">
                  {{ fmtNum(row.stock) }}
                </td>
                <td class="px-5 py-3 text-right text-gray-400">{{ fmtNum(row.min_stock) }}</td>
                <td class="px-5 py-3 text-center">
                  <span v-if="row.status === 'out'" class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-200">
                    {{ $t('reports.stock_out_label') }}
                  </span>
                  <span v-else-if="row.status === 'low'" class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                    {{ $t('reports.stock_low_label') }}
                  </span>
                  <span v-else class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                    {{ $t('reports.stock_ok_label') }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { useReport } from '@/composables/useReport'

defineOptions({ layout: AppLayout })

const { data, loading, exporting, error, filters, fetchData, exportXlsx, fmtNum } = useReport('inventory', { product_type_id: '', stock_status: 'all' })

const search = ref('')

const filteredTable = computed(() => {
  if (!data.value?.table) return []
  const q = search.value.toLowerCase()
  if (!q) return data.value.table
  return data.value.table.filter(r =>
    r.product_name?.toLowerCase().includes(q) ||
    r.product_type?.toLowerCase().includes(q) ||
    r.presentation?.toLowerCase().includes(q),
  )
})

onMounted(fetchData)
</script>
