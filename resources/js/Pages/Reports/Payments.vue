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
          <h1 class="text-xl font-bold text-gray-900">{{ $t('reports.payments_title') }}</h1>
          <p class="text-sm text-gray-500">{{ $t('reports.payments_desc') }}</p>
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
        <div class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.date_from') }}</label>
          <input v-model="filters.date_from" type="date" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.date_to') }}</label>
          <input v-model="filters.date_to" type="date" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" />
        </div>
        <div v-if="data?.filters?.paymentMethods?.length" class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.payment_method') }}</label>
          <select v-model="filters.payment_method_id" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
            <option value="">{{ $t('reports.all_methods') }}</option>
            <option v-for="pm in data.filters.paymentMethods" :key="pm.id" :value="pm.id">{{ pm.name }}</option>
          </select>
        </div>
        <div v-if="data?.filters?.pointsOfSale?.length" class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.point_of_sale') }}</label>
          <select v-model="filters.point_of_sale_id" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
            <option value="">{{ $t('reports.all_pos') }}</option>
            <option v-for="p in data.filters.pointsOfSale" :key="p.id" :value="p.id">{{ p.name }}</option>
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
          <p class="text-xs text-gray-500">{{ $t('reports.total_amount') }}</p>
          <p class="mt-1 text-2xl font-bold text-emerald-700">{{ fmtMoney(data.kpis.total_amount) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
          <p class="text-xs text-gray-500">{{ $t('reports.payments_count') }}</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ data.kpis.count }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
          <p class="text-xs text-gray-500">{{ $t('reports.avg_amount') }}</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmtMoney(data.kpis.avg_amount) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
          <p class="text-xs text-gray-500">{{ $t('reports.top_method') }}</p>
          <p class="mt-1 text-sm font-bold text-indigo-600">{{ data.kpis.top_method ?? '—' }}</p>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Daily Line -->
        <div class="lg:col-span-2 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
          <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ $t('reports.payments_chart') }}</h2>
          <div class="h-56">
            <Line :data="lineChartData" :options="lineChartOptions" />
          </div>
        </div>

        <!-- Donut by method -->
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
          <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ $t('reports.by_method') }}</h2>
          <div v-if="data.by_method.length" class="flex h-44 items-center justify-center">
            <Doughnut :data="donutData" :options="donutOptions" />
          </div>
          <div v-else class="flex h-44 items-center justify-center text-sm text-gray-400">{{ $t('reports.no_data') }}</div>
          <ul class="mt-3 space-y-1.5">
            <li v-for="(m, i) in data.by_method" :key="m.name" class="flex items-center justify-between text-xs">
              <span class="flex items-center gap-1.5">
                <span class="inline-block h-2 w-2 rounded-full" :style="{ background: donutColors[i % donutColors.length] }" />
                {{ m.name }}
              </span>
              <span class="font-semibold text-gray-700">{{ fmtMoney(m.total) }} <span class="font-normal text-gray-400">({{ m.count }})</span></span>
            </li>
          </ul>
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
                <th class="px-5 py-3 text-left">{{ $t('reports.date') }}</th>
                <th class="px-5 py-3 text-left">{{ $t('reports.payment_method') }}</th>
                <th class="px-5 py-3 text-left">{{ $t('reports.point_of_sale') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.amount') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="(row, i) in data.table" :key="i" class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3 text-gray-500">{{ fmtDateTime(row.date) }}</td>
                <td class="px-5 py-3 font-medium text-gray-900">{{ row.payment_method }}</td>
                <td class="px-5 py-3 text-gray-500">{{ row.point_of_sale }}</td>
                <td class="px-5 py-3 text-right font-semibold text-emerald-700">{{ fmtMoney(row.amount) }}</td>
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
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Tooltip, Filler } from 'chart.js'
import { Line, Doughnut } from 'vue-chartjs'
import { useReport } from '@/composables/useReport'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Tooltip, Filler)

defineOptions({ layout: AppLayout })

const { data, loading, exporting, error, filters, fetchData, exportXlsx, fmtMoney, fmtDateTime } = useReport('payments', { payment_method_id: '', point_of_sale_id: '' })

const donutColors = ['#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#ec4899']

const lineChartData = computed(() => {
  if (!data.value?.chart) return { labels: [], datasets: [] }
  const rows = data.value.chart
  return {
    labels: rows.map(r => { const [, m, d] = r.date.split('-'); return `${d}/${m}` }),
    datasets: [{
      label: 'Cobros',
      data: rows.map(r => r.revenue),
      borderColor: '#10b981',
      backgroundColor: 'rgba(16,185,129,0.07)',
      fill: true,
      tension: 0.4,
      pointRadius: 2,
      pointHoverRadius: 5,
    }],
  }
})

const lineChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' $' + ctx.raw.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) } } },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 15 } },
    y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: v => '$' + v.toLocaleString() } },
  },
}

const donutData = computed(() => {
  if (!data.value?.by_method) return { labels: [], datasets: [] }
  return {
    labels: data.value.by_method.map(m => m.name),
    datasets: [{ data: data.value.by_method.map(m => m.total), backgroundColor: donutColors, borderWidth: 2, borderColor: '#fff', hoverOffset: 4 }],
  }
})

const donutOptions = { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' $' + ctx.raw.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) } } } }

onMounted(fetchData)
</script>
