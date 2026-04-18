<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Link href="/reports" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
        </Link>
        <div>
          <h1 class="text-xl font-bold text-gray-900">{{ $t('reports.sales_title') }}</h1>
          <p class="text-sm text-gray-500">{{ $t('reports.sales_desc') }}</p>
        </div>
      </div>
      <button
        @click="exportXlsx"
        :disabled="exporting || !data"
        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50 transition"
      >
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
        <div v-if="data?.filters?.clients?.length" class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.client') }}</label>
          <select v-model="filters.client_id" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
            <option value="">{{ $t('reports.all_clients') }}</option>
            <option v-for="c in data.filters.clients" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div v-if="data?.filters?.pointsOfSale?.length" class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.point_of_sale') }}</label>
          <select v-model="filters.point_of_sale_id" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
            <option value="">{{ $t('reports.all_pos') }}</option>
            <option v-for="p in data.filters.pointsOfSale" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>
        <div v-if="data?.filters?.saleStates?.length" class="flex flex-col gap-1">
          <label class="text-xs font-medium text-gray-600">{{ $t('reports.state') }}</label>
          <select v-model="filters.sale_state_id" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
            <option value="">{{ $t('reports.all_states') }}</option>
            <option v-for="s in data.filters.saleStates" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <button @click="fetchData" :disabled="loading" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 transition">
          <svg v-if="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          {{ $t('reports.apply') }}
        </button>
        <button @click="clearFilters" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 transition">
          {{ $t('reports.clear') }}
        </button>
      </div>
    </div>

    <!-- Skeleton -->
    <template v-if="loading && !data">
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div v-for="n in 5" :key="n" class="h-24 animate-pulse rounded-xl bg-gray-100" />
      </div>
      <div class="h-72 animate-pulse rounded-xl bg-gray-100" />
    </template>

    <div v-else-if="error" class="flex h-32 items-center justify-center rounded-xl bg-red-50 text-sm text-red-600 ring-1 ring-red-200">
      {{ error }}
    </div>

    <template v-else-if="data">
      <!-- KPIs -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <KpiCard :value="fmtMoney(data.kpis.total_revenue)" :label="$t('reports.total_revenue')" color="bg-indigo-50" icon-color="text-indigo-600" icon="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
        <KpiCard :value="fmtMoney(data.kpis.total_collected)" :label="$t('reports.total_collected')" color="bg-emerald-50" icon-color="text-emerald-600" icon="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        <KpiCard :value="data.kpis.count" :label="$t('reports.sales_count')" color="bg-violet-50" icon-color="text-violet-600" icon="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
        <KpiCard :value="fmtMoney(data.kpis.avg_ticket)" :label="$t('reports.avg_ticket')" color="bg-amber-50" icon-color="text-amber-600" icon="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
        <KpiCard :value="fmtMoney(data.kpis.total_discounts)" :label="$t('reports.total_discounts')" color="bg-red-50" icon-color="text-red-500" icon="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z" />
      </div>

      <!-- Chart -->
      <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
        <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ $t('reports.revenue_chart') }}</h2>
        <div class="h-64">
          <Line :data="chartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Table -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5">
          <h2 class="text-sm font-semibold text-gray-900">
            {{ data.meta.total }} {{ $t('reports.total_records') }}
          </h2>
          <input v-model="search" type="text" placeholder="Buscar..." class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none w-48" />
        </div>
        <div v-if="filteredTable.length === 0" class="flex h-32 items-center justify-center text-sm text-gray-400">
          {{ $t('reports.no_data') }}
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
              <tr>
                <th class="px-5 py-3 text-left">{{ $t('reports.date') }}</th>
                <th class="px-5 py-3 text-left">{{ $t('reports.client') }}</th>
                <th class="px-5 py-3 text-left">{{ $t('reports.point_of_sale') }}</th>
                <th class="px-5 py-3 text-left">{{ $t('reports.state') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.subtotal') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.discount') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.total') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.total_collected') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="row in filteredTable" :key="row.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3 text-gray-500">{{ fmtDateTime(row.created_at) }}</td>
                <td class="px-5 py-3 font-medium text-gray-900">{{ row.client }}</td>
                <td class="px-5 py-3 text-gray-500">{{ row.point_of_sale ?? '—' }}</td>
                <td class="px-5 py-3">
                  <span v-if="row.state" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :style="stateBadgeStyle(row.state_color)">
                    {{ row.state }}
                  </span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-5 py-3 text-right text-gray-700">{{ fmtMoney(row.subtotal) }}</td>
                <td class="px-5 py-3 text-right text-red-500">{{ row.discount_amount > 0 ? '-' + fmtMoney(row.discount_amount) : '—' }}</td>
                <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ fmtMoney(row.total) }}</td>
                <td class="px-5 py-3 text-right">
                  <span v-if="row.collected >= row.total" class="font-medium text-emerald-600">{{ fmtMoney(row.collected) }}</span>
                  <span v-else-if="row.collected > 0" class="font-medium text-amber-600">{{ fmtMoney(row.collected) }}</span>
                  <span v-else class="text-gray-400">$0</span>
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
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Filler } from 'chart.js'
import { Line } from 'vue-chartjs'
import { useReport } from '@/composables/useReport'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Filler)

defineOptions({ layout: AppLayout })

const { data, loading, exporting, error, filters, fetchData, exportXlsx, fmtMoney, fmtDateTime, stateBadgeStyle } = useReport('sales', {
  client_id: '',
  point_of_sale_id: '',
  sale_state_id: '',
})

const search = ref('')

function clearFilters() {
  const today = new Date().toISOString().split('T')[0]
  const thirty = new Date(Date.now() - 29 * 86400000).toISOString().split('T')[0]
  filters.date_from = thirty
  filters.date_to = today
  filters.client_id = ''
  filters.point_of_sale_id = ''
  filters.sale_state_id = ''
  fetchData()
}

const filteredTable = computed(() => {
  if (!data.value?.table) return []
  const q = search.value.toLowerCase()
  if (!q) return data.value.table
  return data.value.table.filter(r =>
    r.client?.toLowerCase().includes(q) ||
    r.point_of_sale?.toLowerCase().includes(q) ||
    r.state?.toLowerCase().includes(q),
  )
})

const chartData = computed(() => {
  if (!data.value?.chart) return { labels: [], datasets: [] }
  const rows = data.value.chart
  return {
    labels: rows.map(r => {
      const [, m, d] = r.date.split('-')
      return `${d}/${m}`
    }),
    datasets: [
      {
        label: 'Facturación',
        data: rows.map(r => r.revenue),
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99,102,241,0.08)',
        fill: true,
        tension: 0.4,
        pointRadius: 2,
        pointHoverRadius: 5,
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: ctx => ' $' + ctx.raw.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) } },
  },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 15 } },
    y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: v => '$' + v.toLocaleString() } },
  },
}

// Inline KPI Card
const KpiCard = {
  props: { value: [String, Number], label: String, color: String, iconColor: String, icon: String },
  template: `
    <div :class="['rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200']">
      <div class="flex items-start gap-3">
        <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', color]">
          <svg class="h-4.5 w-4.5" :class="iconColor" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
          </svg>
        </div>
        <div class="min-w-0">
          <p class="truncate text-xs text-gray-500">{{ label }}</p>
          <p class="mt-0.5 text-xl font-bold tabular-nums leading-tight text-gray-900">{{ value }}</p>
        </div>
      </div>
    </div>
  `,
}

onMounted(fetchData)
</script>
