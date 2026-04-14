<template>
  <!-- Mobile overlay -->
  <transition
    enter-active-class="transition-opacity ease-linear duration-300"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-opacity ease-linear duration-300"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="open" class="fixed inset-0 z-40 bg-gray-900/60 lg:hidden" @click="$emit('close')" />
  </transition>

  <!-- Sidebar panel -->
  <aside
    :class="[
      'fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-gray-900 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0',
      open ? 'translate-x-0' : '-translate-x-full',
    ]"
  >
    <!-- Logo -->
    <div class="flex h-16 shrink-0 items-center gap-2 border-b border-gray-800 px-6">
      <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600">
        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
      </div>
      <span class="text-lg font-bold text-white">Inventra</span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
      <NavItem href="/dashboard" :label="$t('common.dashboard')" :icon="icons.dashboard" />

      <NavItem href="/suppliers" :label="$t('suppliers.title')" :icon="icons.suppliers" />
      <NavItem href="/clients" :label="$t('clients.title')" :icon="icons.clients" />
      <NavItem href="/receptions" :label="$t('receptions.title')" :icon="icons.receptions" />

      <NavGroup :label="$t('sales.title')" :icon="icons.sales" :matches="['/sales', '/settings/points-of-sale', '/settings/sale-states', '/settings/payment-methods']">
        <NavItem href="/sales" :label="$t('sales.title')" sub />
        <NavItem href="/settings/points-of-sale" :label="$t('points_of_sale.title')" sub />
        <NavItem href="/settings/sale-states" :label="$t('sale_states.title')" sub />
        <NavItem href="/settings/payment-methods" :label="$t('payment_methods.title')" sub />
      </NavGroup>

      <NavGroup :label="$t('common.products')" :icon="icons.products" :matches="['/products', '/settings/product-types', '/settings/presentation-types', '/settings/presentations']">
        <NavItem href="/products" :label="$t('products.title')" sub />
        <NavItem href="/settings/product-types" :label="$t('product_types.title')" sub />
        <NavItem href="/settings/presentation-types" :label="$t('presentation_types.title')" sub />
        <NavItem href="/settings/presentations" :label="$t('presentations.title')" sub />
      </NavGroup>

      <NavGroup
        :label="$t('common.settings')"
        :icon="icons.settings"
        :matches="['/settings/product-movement-types', '/settings/cash-movement-types', '/settings/currencies']"
      >
        <NavItem href="/settings/product-movement-types" :label="$t('product_movement_types.title')" sub />
        <NavItem href="/settings/cash-movement-types" :label="$t('cash_movement_types.title')" sub />
        <NavItem href="/settings/currencies" :label="$t('currencies.title')" sub />
      </NavGroup>
    </nav>

    <!-- User -->
    <div class="border-t border-gray-800 px-4 py-3">
      <div class="flex items-center gap-3">
        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white uppercase">
          {{ userInitials }}
        </div>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium text-white">{{ user?.name }}</p>
          <p class="truncate text-xs text-gray-400">{{ user?.email }}</p>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import NavItem from '@/Components/NavItem.vue'
import NavGroup from '@/Components/NavGroup.vue'

defineProps({ open: Boolean })
defineEmits(['close'])

const page = usePage()
const user = computed(() => page.props.auth?.user)
const userInitials = computed(() => {
  const name = user.value?.name ?? ''
  return name.split(' ').map(w => w[0]).slice(0, 2).join('')
})

const icons = {
  dashboard: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
  suppliers: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
  clients: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
  products: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
  receptions: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
  sales: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
  settings: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
}
</script>
