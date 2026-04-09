<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-gray-900">{{ $t('common.dashboard') }}</h1>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="card in cards"
        :key="card.key"
        class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
      >
        <div class="flex items-center gap-3">
          <div :class="['flex h-10 w-10 items-center justify-center rounded-lg', card.bg]">
            <svg class="h-5 w-5" :class="card.iconColor" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
            </svg>
          </div>
          <div>
            <p class="text-sm text-gray-500">{{ $t(card.labelKey) }}</p>
            <p class="text-2xl font-semibold text-gray-900">{{ counts[card.key] ?? '—' }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

defineProps({
  counts: { type: Object, default: () => ({}) },
})

const cards = [
  {
    key: 'suppliers',
    labelKey: 'suppliers.title',
    icon: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
    bg: 'bg-indigo-50',
    iconColor: 'text-indigo-600',
  },
  {
    key: 'clients',
    labelKey: 'clients.title',
    icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    bg: 'bg-violet-50',
    iconColor: 'text-violet-600',
  },
  {
    key: 'product_types',
    labelKey: 'product_types.title',
    icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    bg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
  },
  {
    key: 'currencies',
    labelKey: 'currencies.title',
    icon: 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
    bg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
]
</script>
