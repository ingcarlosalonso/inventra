<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('common.dashboard') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ $t('common.dashboard_subtitle') }}</p>
      </div>
      <button
        @click="fetchAll"
        :disabled="loading"
        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50 disabled:opacity-50 transition-colors"
      >
        <svg :class="['h-4 w-4 text-gray-400 transition-transform', loading && 'animate-spin']" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        {{ $t('common.refresh') }}
      </button>
    </div>

    <!-- Skeleton loader -->
    <template v-if="loading && !data">
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div v-for="n in 10" :key="n" class="h-24 animate-pulse rounded-xl bg-gray-100"></div>
      </div>
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 h-72 animate-pulse rounded-xl bg-gray-100"></div>
        <div class="h-72 animate-pulse rounded-xl bg-gray-100"></div>
      </div>
    </template>

    <template v-else-if="data">
      <!-- KPI Cards Row 1: Revenue & Sales -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <KpiCard
          :value="fmtMoney(data.kpis.today_revenue)"
          :label="$t('common.today_revenue')"
          icon-path="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"
          bg="bg-indigo-50" icon-color="text-indigo-600"
        />
        <KpiCard
          :value="data.kpis.today_sales_count"
          :label="$t('common.today_sales_count')"
          icon-path="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"
          bg="bg-violet-50" icon-color="text-violet-600"
        />
        <KpiCard
          :value="fmtMoney(data.kpis.today_collected)"
          :label="$t('common.today_collected')"
          icon-path="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          bg="bg-emerald-50" icon-color="text-emerald-600"
        />
        <KpiCard
          :value="fmtMoney(data.kpis.month_revenue)"
          :label="$t('common.month_revenue')"
          icon-path="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"
          bg="bg-amber-50" icon-color="text-amber-600"
        />
        <KpiCard
          :value="data.kpis.month_sales_count"
          :label="$t('common.month_sales_count')"
          icon-path="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"
          bg="bg-sky-50" icon-color="text-sky-600"
        />
      </div>

      <!-- KPI Cards Row 2: Operational -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <KpiCard
          :value="data.kpis.active_orders"
          :label="$t('common.active_orders')"
          icon-path="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"
          bg="bg-orange-50" icon-color="text-orange-600"
        />
        <KpiCard
          :value="data.kpis.pending_quotes"
          :label="$t('common.pending_quotes')"
          icon-path="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
          bg="bg-teal-50" icon-color="text-teal-600"
        />
        <KpiCard
          :value="data.kpis.total_clients"
          :label="$t('common.total_clients')"
          icon-path="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
          bg="bg-pink-50" icon-color="text-pink-600"
        />
        <KpiCard
          :value="data.kpis.low_stock_count"
          :label="$t('common.low_stock_count')"
          icon-path="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
          bg="bg-red-50" icon-color="text-red-500"
          :alert="data.kpis.low_stock_count > 0"
        />
        <KpiCard
          :value="data.kpis.open_cashes_count"
          :label="$t('common.open_cashes_count')"
          icon-path="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"
          bg="bg-cyan-50" icon-color="text-cyan-600"
        />
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Sales Area Chart (2/3 width) -->
        <div class="lg:col-span-2 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">{{ $t('common.sales_chart_title') }}</h2>
            <div class="flex items-center gap-3 text-xs text-gray-500">
              <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-indigo-500"></span>{{ $t('common.revenue') }}</span>
              <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-violet-400"></span>{{ $t('common.sales_count') }}</span>
            </div>
          </div>
          <div class="h-56">
            <Line :data="salesChartData" :options="salesChartOptions" />
          </div>
        </div>

        <!-- Payment Methods Donut -->
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
          <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ $t('common.payment_methods_title') }}</h2>
          <div v-if="data.payment_methods.length" class="flex h-48 items-center justify-center">
            <Doughnut :data="paymentMethodsData" :options="donutOptions" />
          </div>
          <div v-else class="flex h-48 items-center justify-center text-sm text-gray-400">
            {{ $t('common.no_data') }}
          </div>
          <ul class="mt-3 space-y-1.5">
            <li v-for="(pm, i) in data.payment_methods" :key="pm.name" class="flex items-center justify-between text-xs">
              <span class="flex items-center gap-1.5">
                <span class="inline-block h-2 w-2 rounded-full" :style="{ background: donutColors[i % donutColors.length] }"></span>
                {{ pm.name }}
              </span>
              <span class="font-medium text-gray-700">{{ fmtMoney(pm.total) }}</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Weekly + Order States -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Weekly Bar Chart (2/3) -->
        <div class="lg:col-span-2 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
          <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ $t('common.weekly_comparison_title') }}</h2>
          <div class="h-52">
            <Bar :data="weeklyChartData" :options="barOptions" />
          </div>
        </div>

        <!-- Order States -->
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
          <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ $t('common.order_states_title') }}</h2>
          <div v-if="data.order_states.length" class="space-y-3">
            <div v-for="os in data.order_states" :key="os.name">
              <div class="mb-1 flex items-center justify-between text-xs">
                <span class="font-medium text-gray-700">{{ os.name }}</span>
                <span class="text-gray-500">{{ os.count }}</span>
              </div>
              <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                <div
                  class="h-full rounded-full transition-all"
                  :style="{ width: orderStateWidth(os.count) + '%', background: os.color }"
                ></div>
              </div>
            </div>
          </div>
          <div v-else class="flex h-32 items-center justify-center text-sm text-gray-400">
            {{ $t('common.no_data') }}
          </div>
        </div>
      </div>

      <!-- Bottom Row: Top Products + Low Stock + Open Cashes + Recent Sales -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Top Products Bar -->
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
          <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ $t('common.top_products_title') }}</h2>
          <div v-if="data.top_products.length" class="h-52">
            <Bar :data="topProductsData" :options="topProductsOptions" />
          </div>
          <div v-else class="flex h-52 items-center justify-center text-sm text-gray-400">
            {{ $t('common.no_data') }}
          </div>
        </div>

        <!-- Low Stock -->
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">{{ $t('common.low_stock_title') }}</h2>
            <span v-if="data.low_stock.length" class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-200">
              {{ data.low_stock.length }}
            </span>
          </div>
          <div v-if="data.low_stock.length" class="space-y-3">
            <div
              v-for="item in data.low_stock"
              :key="item.product_name + item.presentation"
              class="flex items-center justify-between rounded-lg bg-red-50 px-3 py-2.5"
            >
              <div class="min-w-0">
                <p class="truncate text-xs font-medium text-gray-900">{{ item.product_name }}</p>
                <p class="text-xs text-gray-500">{{ item.presentation }}</p>
              </div>
              <div class="ml-2 shrink-0 text-right">
                <p class="text-sm font-semibold text-red-600">{{ fmtNum(item.stock) }}</p>
                <p class="text-xs text-gray-400">{{ $t('common.min') }} {{ fmtNum(item.min_stock) }}</p>
              </div>
            </div>
          </div>
          <div v-else class="flex h-40 items-center justify-center gap-2 text-sm text-emerald-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $t('common.all_ok') }}
          </div>
        </div>

        <!-- Open Cashes -->
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
          <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ $t('common.open_cashes_title') }}</h2>
          <div v-if="data.open_cashes.length" class="space-y-3">
            <div
              v-for="dc in data.open_cashes"
              :key="dc.id"
              class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3"
            >
              <div class="flex items-start justify-between">
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-gray-900">{{ dc.pos_name }}</p>
                  <p class="mt-0.5 text-xs text-gray-400">{{ $t('common.open_since') }}: {{ formatDate(dc.opened_at) }}</p>
                </div>
                <span class="ml-2 inline-flex shrink-0 items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">{{ $t('common.open') }}</span>
              </div>
              <div class="mt-2.5 grid grid-cols-2 gap-2">
                <div class="rounded-md bg-white p-2 text-center ring-1 ring-gray-100">
                  <p class="text-xs text-gray-400">{{ $t('common.opening_balance') }}</p>
                  <p class="text-sm font-semibold text-gray-700">{{ fmtMoney(dc.opening_balance) }}</p>
                </div>
                <div class="rounded-md bg-emerald-50 p-2 text-center ring-1 ring-emerald-100">
                  <p class="text-xs text-emerald-600">{{ $t('common.current_balance') }}</p>
                  <p class="text-sm font-bold text-emerald-700">{{ fmtMoney(dc.current_balance) }}</p>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="flex h-40 items-center justify-center text-sm text-gray-400">
            {{ $t('common.no_open_cashes') }}
          </div>
        </div>
      </div>

      <!-- Recent Sales Full Width -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
          <h2 class="text-sm font-semibold text-gray-900">{{ $t('common.recent_sales_title') }}</h2>
          <Link href="/sales" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">{{ $t('common.view_all') }} →</Link>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100">
            <thead>
              <tr class="bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wide">
                <th class="px-5 py-3 text-left">{{ $t('common.client') }}</th>
                <th class="px-5 py-3 text-left">{{ $t('common.state') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('common.total') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('common.collected') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('common.date') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="sale in data.recent_sales" :key="sale.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3 text-sm font-medium text-gray-900">{{ sale.client_name }}</td>
                <td class="px-5 py-3">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :style="stateBadgeStyle(sale.state_color)"
                  >{{ sale.state_name }}</span>
                </td>
                <td class="px-5 py-3 text-right text-sm font-semibold text-gray-900">{{ fmtMoney(sale.total) }}</td>
                <td class="px-5 py-3 text-right">
                  <span v-if="sale.paid_amount >= sale.total" class="text-sm font-medium text-emerald-600">{{ fmtMoney(sale.paid_amount) }}</span>
                  <span v-else-if="sale.paid_amount > 0" class="text-sm font-medium text-amber-600">{{ fmtMoney(sale.paid_amount) }}</span>
                  <span v-else class="text-sm text-gray-400">$0</span>
                </td>
                <td class="px-5 py-3 text-right text-xs text-gray-400">{{ formatDate(sale.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import { Line, Bar, Doughnut } from 'vue-chartjs'
import { useApi } from '@/composables/useApi'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Title, Tooltip, Legend, Filler)

defineOptions({ layout: AppLayout })

const { get } = useApi()
const loading = ref(false)
const data = ref(null)
const { proxy } = getCurrentInstance()

// ── KPI Card component (inline) ─────────────────────────────────────────────
const KpiCard = {
  props: {
    value: [String, Number],
    label: String,
    iconPath: String,
    bg: String,
    iconColor: String,
    alert: Boolean,
  },
  template: `
    <div :class="['rounded-xl bg-white p-4 shadow-sm ring-1 transition-shadow hover:shadow-md', alert ? 'ring-red-200' : 'ring-gray-200']">
      <div class="flex items-start gap-3">
        <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', bg]">
          <svg class="h-4.5 w-4.5" :class="iconColor" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath" />
          </svg>
        </div>
        <div class="min-w-0">
          <p class="truncate text-xs text-gray-500">{{ label }}</p>
          <p :class="['mt-0.5 text-xl font-bold tabular-nums leading-tight', alert ? 'text-red-600' : 'text-gray-900']">{{ value }}</p>
        </div>
      </div>
    </div>
  `,
}

// ── Colors ───────────────────────────────────────────────────────────────────
const donutColors = ['#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#ec4899']

// ── Chart Data ────────────────────────────────────────────────────────────────
const salesChartData = computed(() => {
  if (!data.value) return { labels: [], datasets: [] }
  const rows = data.value.sales_chart
  return {
    labels: rows.map((r) => fmtDateShort(r.date)),
    datasets: [
      {
        label: proxy.$t('common.revenue'),
        data: rows.map((r) => r.revenue),
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99,102,241,0.08)',
        fill: true,
        tension: 0.4,
        pointRadius: 2,
        pointHoverRadius: 5,
        yAxisID: 'y',
      },
      {
        label: proxy.$t('common.sales_count'),
        data: rows.map((r) => r.count),
        borderColor: '#a78bfa',
        backgroundColor: 'transparent',
        fill: false,
        tension: 0.4,
        pointRadius: 2,
        pointHoverRadius: 5,
        borderDash: [4, 3],
        yAxisID: 'y1',
      },
    ],
  }
})

const salesChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ctx.datasetIndex === 0 ? ' ' + fmtMoney(ctx.raw) : ' ' + ctx.raw + ' ' + proxy.$t('common.sales') } } },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 10 } },
    y: { position: 'left', grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => '$' + fmtNum(v) } },
    y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { font: { size: 10 }, stepSize: 1 } },
  },
}

const paymentMethodsData = computed(() => {
  if (!data.value) return { labels: [], datasets: [] }
  return {
    labels: data.value.payment_methods.map((pm) => pm.name),
    datasets: [{
      data: data.value.payment_methods.map((pm) => pm.total),
      backgroundColor: donutColors,
      borderWidth: 2,
      borderColor: '#fff',
      hoverOffset: 4,
    }],
  }
})

const donutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '68%',
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ' ' + fmtMoney(ctx.raw) } } },
}

const weeklyChartData = computed(() => {
  if (!data.value) return { labels: [], datasets: [] }
  return {
    labels: data.value.weekly_comparison.map((r) => r.day),
    datasets: [{
      label: proxy.$t('common.revenue'),
      data: data.value.weekly_comparison.map((r) => r.revenue),
      backgroundColor: 'rgba(99,102,241,0.75)',
      borderRadius: 6,
    }],
  }
})

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ' ' + fmtMoney(ctx.raw) } } },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
    y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => '$' + fmtNum(v) } },
  },
}

const topProductsData = computed(() => {
  if (!data.value) return { labels: [], datasets: [] }
  const products = [...data.value.top_products].reverse()
  return {
    labels: products.map((p) => p.product_name),
    datasets: [{
      label: proxy.$t('common.revenue'),
      data: products.map((p) => p.revenue),
      backgroundColor: ['#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b'],
      borderRadius: 4,
    }],
  }
})

const topProductsOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ' ' + fmtMoney(ctx.raw) } } },
  scales: {
    x: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => '$' + fmtNum(v) } },
    y: { grid: { display: false }, ticks: { font: { size: 11 } } },
  },
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function fmtMoney(value) {
  const n = parseFloat(value) || 0
  return '$' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtNum(value) {
  const n = parseFloat(value) || 0
  return n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString(undefined, { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function fmtDateShort(dateStr) {
  if (!dateStr) return ''
  const [, m, d] = dateStr.split('-')
  return `${d}/${m}`
}

function stateBadgeStyle(color) {
  if (!color) return {}
  return { backgroundColor: color + '20', color, borderColor: color + '40', border: '1px solid' }
}

function orderStateWidth(count) {
  if (!data.value?.order_states?.length) return 0
  const max = Math.max(...data.value.order_states.map((s) => s.count))
  return max ? Math.round((count / max) * 100) : 0
}

// ── Data Fetch ────────────────────────────────────────────────────────────────
async function fetchAll() {
  loading.value = true
  const { data: res } = await get('/api/dashboard')
  if (res) data.value = res
  loading.value = false
}

onMounted(fetchAll)
</script>
