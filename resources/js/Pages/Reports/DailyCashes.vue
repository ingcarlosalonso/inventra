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
          <h1 class="text-xl font-bold text-gray-900">{{ $t('reports.daily_cashes_title') }}</h1>
          <p class="text-sm text-gray-500">{{ $t('reports.daily_cashes_desc') }}</p>
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
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
        <div v-for="n in 5" :key="n" class="h-24 animate-pulse rounded-xl bg-gray-100" />
      </div>
    </template>

    <div v-else-if="error" class="flex h-32 items-center justify-center rounded-xl bg-red-50 text-sm text-red-600 ring-1 ring-red-200">
      {{ error }}
    </div>

    <template v-else-if="data">
      <!-- KPIs -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
          <p class="text-xs text-gray-500">{{ $t('reports.cashes_count') }}</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ data.kpis.count }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
          <p class="text-xs text-gray-500">{{ $t('reports.closed_count') }}</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ data.kpis.closed_count }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-emerald-200 bg-emerald-50">
          <p class="text-xs text-emerald-700">{{ $t('reports.total_collected') }}</p>
          <p class="mt-1 text-2xl font-bold text-emerald-700">{{ fmtMoney(data.kpis.total_collected) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-indigo-200 bg-indigo-50">
          <p class="text-xs text-indigo-700">{{ $t('reports.total_income_extra') }}</p>
          <p class="mt-1 text-2xl font-bold text-indigo-700">{{ fmtMoney(data.kpis.total_income) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-red-200 bg-red-50">
          <p class="text-xs text-red-700">{{ $t('reports.total_expense_extra') }}</p>
          <p class="mt-1 text-2xl font-bold text-red-700">{{ fmtMoney(data.kpis.total_expense) }}</p>
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
                <th class="px-5 py-3 text-left">{{ $t('reports.point_of_sale') }}</th>
                <th class="px-5 py-3 text-left">{{ $t('reports.user') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.opening_balance') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.collected') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.total_income_extra') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.total_expense_extra') }}</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.closing_balance') }}</th>
                <th class="px-5 py-3 text-center">Estado</th>
                <th class="px-5 py-3 text-right">{{ $t('reports.opened_at') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="row in data.table" :key="row.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3 font-medium text-gray-900">{{ row.point_of_sale }}</td>
                <td class="px-5 py-3 text-gray-500">{{ row.user }}</td>
                <td class="px-5 py-3 text-right text-gray-700">{{ fmtMoney(row.opening_balance) }}</td>
                <td class="px-5 py-3 text-right font-semibold text-emerald-700">{{ fmtMoney(row.collected) }}</td>
                <td class="px-5 py-3 text-right text-indigo-600">{{ fmtMoney(row.income) }}</td>
                <td class="px-5 py-3 text-right text-red-500">{{ row.expense > 0 ? '-' + fmtMoney(row.expense) : '—' }}</td>
                <td class="px-5 py-3 text-right font-bold text-gray-900">{{ row.status === 'closed' ? fmtMoney(row.closing_balance) : '—' }}</td>
                <td class="px-5 py-3 text-center">
                  <span v-if="row.status === 'closed'" class="inline-flex items-center rounded-full bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200">
                    {{ $t('reports.closed') }}
                  </span>
                  <span v-else class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                    {{ $t('reports.open') }}
                  </span>
                </td>
                <td class="px-5 py-3 text-right text-gray-400">{{ fmtDateTime(row.opened_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { useReport } from '@/composables/useReport'

defineOptions({ layout: AppLayout })

const { data, loading, exporting, error, filters, fetchData, exportXlsx, fmtMoney, fmtDateTime } = useReport('daily-cashes', { point_of_sale_id: '' })

onMounted(fetchData)
</script>
