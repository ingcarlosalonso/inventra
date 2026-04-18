<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('reports.title') }}</h1>
      <p class="mt-0.5 text-sm text-gray-500">{{ $t('reports.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <a
        v-for="report in reports"
        :key="report.href"
        :href="report.href"
        class="group relative flex flex-col overflow-hidden rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md hover:-translate-y-0.5"
        @click.prevent="router.visit(report.href)"
      >
        <div class="flex items-start gap-4">
          <div :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-xl', report.color]">
            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" :d="report.icon" />
            </svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">{{ report.title }}</p>
            <p class="mt-1 text-sm text-gray-500 leading-snug">{{ report.desc }}</p>
          </div>
        </div>
        <div class="mt-4 flex items-center text-xs font-medium text-indigo-600 group-hover:text-indigo-700">
          {{ $t('reports.view_report') }}
          <svg class="ml-1 h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
          </svg>
        </div>
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

const page = usePage()
const t = (key) => {
  const parts = key.split('.')
  return page.props.translations?.[parts[0]]?.[parts[1]] ?? key
}

const reports = computed(() => [
  {
    title: t('reports.sales_title'),
    desc: t('reports.sales_desc'),
    href: '/reports/sales',
    color: 'bg-indigo-600',
    icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
  },
  {
    title: t('reports.products_title'),
    desc: t('reports.products_desc'),
    href: '/reports/products',
    color: 'bg-violet-600',
    icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
  },
  {
    title: t('reports.payments_title'),
    desc: t('reports.payments_desc'),
    href: '/reports/payments',
    color: 'bg-emerald-600',
    icon: 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  },
  {
    title: t('reports.inventory_title'),
    desc: t('reports.inventory_desc'),
    href: '/reports/inventory',
    color: 'bg-amber-500',
    icon: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
  },
  {
    title: t('reports.daily_cashes_title'),
    desc: t('reports.daily_cashes_desc'),
    href: '/reports/daily-cashes',
    color: 'bg-cyan-600',
    icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
  },
  {
    title: t('reports.orders_title'),
    desc: t('reports.orders_desc'),
    href: '/reports/orders',
    color: 'bg-orange-500',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
  },
  {
    title: t('reports.clients_title'),
    desc: t('reports.clients_desc'),
    href: '/reports/clients',
    color: 'bg-pink-600',
    icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
  },
  {
    title: t('reports.purchases_title'),
    desc: t('reports.purchases_desc'),
    href: '/reports/purchases',
    color: 'bg-teal-600',
    icon: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
  },
])
</script>
