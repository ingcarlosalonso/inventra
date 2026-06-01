<template>
  <div class="min-h-screen bg-gray-950 text-white">
    <!-- Top bar -->
    <header class="fixed inset-x-0 top-0 z-40 flex h-14 items-center justify-between border-b border-gray-800 bg-gray-900 px-6">
      <div class="flex items-center gap-3">
        <div class="flex h-7 w-7 items-center justify-center rounded-md bg-indigo-600">
          <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
          </svg>
        </div>
        <span class="text-sm font-bold tracking-wide text-white">In-ventra</span>
        <span class="rounded bg-indigo-900/60 px-1.5 py-0.5 text-xs font-medium text-indigo-300">Super Admin</span>
      </div>

      <form @submit.prevent="logout" class="flex items-center gap-2">
        <span class="text-xs text-gray-400">{{ auth.user?.name }}</span>
        <button
          type="submit"
          class="rounded px-3 py-1.5 text-xs text-gray-400 transition hover:bg-gray-800 hover:text-white"
        >
          {{ $t('central.logout') }}
        </button>
      </form>
    </header>

    <!-- Content -->
    <main class="pt-14">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { usePage, router } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const auth = computed(() => page.props.auth)

function logout() {
  router.post('/central-admin/logout')
}
</script>
