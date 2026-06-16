<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-4"
    >
      <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-gray-950/80 backdrop-blur-sm" @click="dismiss" />

        <!-- Panel -->
        <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-gray-700/60 bg-gray-900 shadow-2xl">
          <!-- Header -->
          <div class="flex items-start justify-between border-b border-gray-800 px-6 py-5">
            <div>
              <div class="flex items-center gap-2">
                <span class="rounded-full bg-indigo-500/20 px-2.5 py-0.5 text-xs font-semibold text-indigo-300">
                  v{{ release.version }}
                </span>
                <span class="text-xs text-gray-500">{{ formattedDate }}</span>
              </div>
              <h2 class="mt-2 text-lg font-bold text-white">{{ release.title }}</h2>
              <p v-if="release.summary" class="mt-1 text-sm text-gray-400">{{ release.summary }}</p>
            </div>
            <button @click="dismiss" class="ml-4 shrink-0 text-gray-500 transition hover:text-white">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Items grouped by type -->
          <div class="max-h-80 overflow-y-auto px-6 py-5 space-y-4">
            <div v-for="group in groupedItems" :key="group.type">
              <div class="mb-2 flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="badgeClass(group.type)">
                  <component :is="typeIcon(group.type)" class="h-3 w-3" />
                  {{ $t('releases.types.' + group.type) }}
                </span>
              </div>
              <ul class="space-y-1.5 pl-1">
                <li
                  v-for="(item, i) in group.items"
                  :key="i"
                  class="flex items-start gap-2 text-sm text-gray-300"
                >
                  <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full" :class="dotClass(group.type)"></span>
                  {{ item.title }}
                </li>
              </ul>
            </div>
          </div>

          <!-- Footer -->
          <div class="border-t border-gray-800 px-6 py-4">
            <button
              @click="dismiss"
              class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500"
            >
              {{ $t('releases.got_it') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, h } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

const page = usePage()
const visible = ref(false)

const release = computed(() => page.props.unread_release)

// Track which UUID triggered the popup so we don't re-trigger on SPA nav
// while the read record is still being written, but DO re-trigger if the
// release goes null (read confirmed) and comes back (admin re-published).
const shownForUuid = ref(null)

function triggerForRelease(r) {
  if (!r) {
    shownForUuid.value = null
    visible.value = false
    return
  }
  if (shownForUuid.value === r.uuid) return
  shownForUuid.value = r.uuid
  setTimeout(() => { visible.value = true }, 800)
}

watch(release, triggerForRelease, { immediate: true })

const formattedDate = computed(() => {
  if (!release.value?.published_at) return ''
  return new Date(release.value.published_at).toLocaleDateString(undefined, {
    year: 'numeric', month: 'long', day: 'numeric',
  })
})

const groupedItems = computed(() => {
  if (!release.value?.items) return []
  const order = ['feature', 'fix', 'improvement', 'security', 'removal', 'deprecation']
  const groups = {}
  for (const item of release.value.items) {
    if (!groups[item.type]) groups[item.type] = []
    groups[item.type].push(item)
  }
  return order
    .filter(type => groups[type]?.length)
    .map(type => ({ type, items: groups[type] }))
})

function badgeClass(type) {
  return {
    feature: 'bg-indigo-900/60 text-indigo-300',
    fix: 'bg-red-900/60 text-red-300',
    improvement: 'bg-yellow-900/60 text-yellow-300',
    security: 'bg-orange-900/60 text-orange-300',
    removal: 'bg-rose-900/60 text-rose-300',
    deprecation: 'bg-gray-700/60 text-gray-400',
  }[type] ?? 'bg-gray-800 text-gray-400'
}

function dotClass(type) {
  return {
    feature: 'bg-indigo-400',
    fix: 'bg-red-400',
    improvement: 'bg-yellow-400',
    security: 'bg-orange-400',
    removal: 'bg-rose-400',
    deprecation: 'bg-gray-500',
  }[type] ?? 'bg-gray-400'
}

// Simple inline SVG icons via render functions
function typeIcon(type) {
  const paths = {
    feature: 'M12 4.5v15m7.5-7.5h-15',
    fix: 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63',
    improvement: 'M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18',
    security: 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
  }
  return {
    render() {
      return h('svg', { class: 'h-3 w-3', fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '2', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: paths[type] ?? paths.feature }),
      ])
    },
  }
}

function dismiss() {
  visible.value = false
  if (release.value?.uuid) {
    axios.post(`/api/v1/releases/${release.value.uuid}/read`).catch(() => {})
  }
}
</script>
