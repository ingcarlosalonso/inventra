<template>
  <div class="relative" ref="bellRef">
    <!-- Bell button -->
    <button
      type="button"
      class="relative flex items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition"
      @click="toggle"
      :aria-label="$t('notifications.title')"
    >
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
      </svg>
      <span
        v-if="unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-if="open"
        class="absolute right-0 mt-2 w-80 rounded-xl bg-white shadow-xl ring-1 ring-gray-200 z-50 flex flex-col max-h-[480px]"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 shrink-0">
          <h3 class="text-sm font-semibold text-gray-900">{{ $t('notifications.title') }}</h3>
          <button
            v-if="unreadCount > 0"
            type="button"
            class="text-xs font-medium text-indigo-600 hover:text-indigo-700 transition"
            @click="handleMarkAllRead"
          >
            {{ $t('notifications.mark_all_read') }}
          </button>
        </div>

        <!-- List -->
        <div class="overflow-y-auto flex-1">
          <div v-if="loading" class="divide-y divide-gray-50">
            <div v-for="i in 3" :key="i" class="flex gap-3 px-4 py-3 animate-pulse">
              <div class="h-8 w-8 rounded-full bg-gray-200 shrink-0" />
              <div class="flex-1 space-y-1.5">
                <div class="h-3 w-3/4 rounded bg-gray-200" />
                <div class="h-3 w-1/2 rounded bg-gray-200" />
              </div>
            </div>
          </div>

          <p v-else-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
            {{ $t('notifications.empty') }}
          </p>

          <ul v-else class="divide-y divide-gray-50">
            <li
              v-for="n in notifications"
              :key="n.id"
              class="group flex items-start gap-3 px-4 py-3 transition"
              :class="n.read ? 'bg-white' : 'bg-indigo-50/50'"
            >
              <!-- Type icon -->
              <div
                class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                :class="typeConfig(n.type).iconBg"
              >
                <component :is="typeConfig(n.type).icon" class="h-4 w-4" :class="typeConfig(n.type).iconColor" />
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0 cursor-pointer" @click="handleClick(n)">
                <p class="text-sm font-medium text-gray-900 leading-snug">{{ renderTitle(n) }}</p>
                <p class="mt-0.5 text-xs text-gray-500 leading-relaxed">{{ renderBody(n) }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ timeAgo(n.created_at) }}</p>
              </div>

              <!-- Unread dot + remove -->
              <div class="flex flex-col items-center gap-1.5 shrink-0">
                <div v-if="!n.read" class="h-2 w-2 rounded-full bg-indigo-500" />
                <button
                  type="button"
                  class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-red-400 transition"
                  @click.stop="remove(n.id)"
                >
                  <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, h } from 'vue'
import { router } from '@inertiajs/vue3'
import { onClickOutside } from '@vueuse/core'
import { useNotifications } from '@/composables/useNotifications'

const { unreadCount, notifications, loading, fetchAll, markRead, markAllRead, remove } = useNotifications()

const open = ref(false)
const bellRef = ref(null)

onClickOutside(bellRef, () => { open.value = false })

async function toggle() {
    open.value = !open.value
    if (open.value) await fetchAll()
}

async function handleMarkAllRead() {
    await markAllRead()
}

async function handleClick(n) {
    if (!n.read) await markRead(n.id)
    if (n.data?.url) {
        open.value = false
        router.visit(n.data.url)
    }
}

// ── Extensible type registry ──────────────────────────────────────────────────
// To add a new notification type, add an entry here.
// Each entry: { iconBg, iconColor, icon (render fn), title(n), body(n) }

const WarningIcon = () => h('svg', { fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '1.5', stroke: 'currentColor' },
    [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z' })])

const InfoIcon = () => h('svg', { fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '1.5', stroke: 'currentColor' },
    [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z' })])

const typeRegistry = {
    low_stock: {
        iconBg: 'bg-amber-100',
        iconColor: 'text-amber-600',
        icon: WarningIcon,
        title: (n) => n.data.product_name,
        body: (n) => `Stock bajo: ${n.data.stock} (mín. ${n.data.min_stock}) — ${n.data.presentation_name}`,
    },
    // Add future types here:
    // order_assigned: { ... },
    // quote_expiring: { ... },
}

const defaultType = {
    iconBg: 'bg-gray-100',
    iconColor: 'text-gray-500',
    icon: InfoIcon,
    title: () => 'Notificación',
    body: (n) => JSON.stringify(n.data),
}

function typeConfig(type) {
    return typeRegistry[type] ?? defaultType
}

function renderTitle(n) {
    return typeConfig(n.type).title(n)
}

function renderBody(n) {
    return typeConfig(n.type).body(n)
}

function timeAgo(iso) {
    if (!iso) return ''
    const diff = (Date.now() - new Date(iso)) / 1000
    if (diff < 60) return 'Ahora'
    if (diff < 3600) return `Hace ${Math.floor(diff / 60)} min`
    if (diff < 86400) return `Hace ${Math.floor(diff / 3600)} h`
    return `Hace ${Math.floor(diff / 86400)} d`
}
</script>
