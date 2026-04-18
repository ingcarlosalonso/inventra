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
          <h1 class="text-xl font-bold text-gray-900">{{ $t('reports.products_title') }}</h1>
          <p class="text-sm text-gray-500">{{ $t('reports.products_desc') }}</p>
        </div>
      </div>
      <button @click="exportXlsx" :disabled="exporting || !data" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50 transition">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        {{ exporting ? 'Exportando...' : $t('reports.export_excel') }}
      </button>
    </div>

    <!-- Filters -->
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
      <div class="flex flex-wrap items-end gap-3">
        <div class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.date_from') }}</label>
          <input v-model="filters.date_from" type="date" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.date_to') }}</label>
          <input v-model="filters.date_to" type="date" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" />
        </div>
        <div v-if="data?.filters?.productTypes?.length" class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.product_type') }}</label>
          <select v-model="filters.product_type_id" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
            <option value="">{{ $t('reports.all_categories') }}</option>
            <option v-for="pt in data.filters.productTypes" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
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
      <div class="h-72 animate-pulse rounded-xl bg-gray-100" />
    </template>

    <div v-else-if="error" class="flex h-32 items-center justify-center rounded-xl bg-red-50 text-sm text-red-600 ring-1 ring-red-200">
      {{ error }}
    </div>

    <template v-else-if="data">
      <!-- KPIs -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
          <p class="text-xs text-gray-500">{{ $t('reports.total_revenue') }}</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmtMoney(data.kpis.total_revenue) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
          <p class="text-xs text-gray-500">{{ $t('reports.total_units') }}</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmtNum(data.kpis.total_units, 0) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
          <p class="text-xs text-gray-500">{{ $t('reports.unique_products') }}</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ data.kpis.unique_products }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
          <p class="text-xs text-gray-500">{{ $t('reports.top_product') }}</p>
          <p class="mt-1 text-sm font-bold text-indigo-600 truncate">{{ data.kpis.top_product ?? '—' }}</p>
        </div>
      </div>

      <!-- Chart -->
      <div v-if="data.chart.length" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
        <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ $t('reports.top_products_chart') }}</h2>
        <div class="h-64">
          <Bar :data="chartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Table -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-100 px-5 py-3.5">
          <h2 class="text-sm font-semibold text-gray-900">{{ data.meta.total }} {{ $t('reports.total_records') }}</h2>
        </div>
        <div v-if="data.table.length === 0" class="flex h-32 items-center justify-center text-sm text-gray-400">
          {{ $t('reports.no_data') }}
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
              <tr>
                <th class="px-5 py-3 text-left">{{ $t('reports.product_name') }}</th>
                <th class="px-5 py-3 text-left">{{ $t('reports.product_type') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.units_sold') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.total_revenue') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.avg_price') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.sale_count') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="(row, i) in data.table" :key="i" class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3 font-medium text-gray-900">{{ row.product_name }}</td>
                <td class="px-5 py-3 text-gray-500">{{ row.product_type ?? '—' }}</td>
                <td class="px-5 py-3 text-right text-gray-700">{{ fmtNum(row.units_sold, 0) }}</td>
                <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ fmtMoney(row.revenue) }}</td>
                <td class="px-5 py-3 text-right text-gray-500">{{ fmtMoney(row.avg_price) }}</td>
                <td class="px-5 py-3 text-right text-gray-500">{{ row.sale_count }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip } from 'chart.js'
import { Bar } from 'vue-chartjs'
import { useReport } from '@/composables/useReport'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip)

defineOptions({ layout: AppLayout })

const { data, loading, exporting, error, filters, fetchData, exportXlsx, fmtMoney, fmtNum } = useReport('products', { product_type_id: '' })

const chartData = computed(() => {
  if (!data.value?.chart) return { labels: [], datasets: [] }
  const rows = [...data.value.chart].reverse()
  return {
    labels: rows.map(r => r.name),
    datasets: [{
      label: 'Ingresos',
      data: rows.map(r => r.revenue),
      backgroundColor: ['#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#3b82f6', '#84cc16', '#f97316'],
      borderRadius: 5,
    }],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: ctx => ' $' + ctx.raw.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) } },
  },
  scales: {
    x: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: v => '$' + v.toLocaleString() } },
    y: { grid: { display: false }, ticks: { font: { size: 11 } } },
  },
}

onMounted(fetchData)
</script>
