<template>
  <header class="flex h-16 shrink-0 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6">
    <button
      type="button"
      class="rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden"
      @click="$emit('toggle-sidebar')"
    >
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
      </svg>
    </button>

    <div class="flex flex-1 items-center justify-end gap-2">
      <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="flash.success" class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
          {{ flash.success }}
        </div>
      </transition>

      <!-- Notification bell -->
      <NotificationBell />

      <!-- Language picker -->
      <div class="relative" ref="langRef">
        <button type="button" class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 transition" @click="langOpen = !langOpen">
          <span class="text-base leading-none">{{ currentLocale === 'es' ? '🇦🇷' : '🇺🇸' }}</span>
          <span class="uppercase text-xs tracking-wide">{{ currentLocale }}</span>
          <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
        </button>
        <div v-if="langOpen" class="absolute right-0 mt-1 w-32 rounded-xl bg-white shadow-lg ring-1 ring-gray-200 py-1 z-50">
          <button v-for="lang in locales" :key="lang.code" type="button" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition" :class="{ 'font-semibold text-indigo-600': currentLocale === lang.code }" @click="switchLocale(lang.code)">
            <span>{{ lang.flag }}</span>{{ lang.label }}
          </button>
        </div>
      </div>

      <!-- User menu -->
      <div class="relative" ref="userRef">
        <button type="button" class="flex items-center gap-2.5 rounded-lg px-2.5 py-1.5 hover:bg-gray-100 transition" @click="userOpen = !userOpen">
          <div class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white uppercase">{{ userInitials }}</div>
          <span class="hidden sm:block text-sm font-medium text-gray-700 max-w-[120px] truncate">{{ user?.name }}</span>
          <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
        </button>
        <div v-if="userOpen" class="absolute right-0 mt-1 w-48 rounded-xl bg-white shadow-lg ring-1 ring-gray-200 py-1 z-50">
          <div class="border-b border-gray-100 px-3 py-2 mb-1">
            <p class="text-sm font-medium text-gray-900 truncate">{{ user?.name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ user?.email }}</p>
          </div>
          <button type="button" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition" @click="logout">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
            {{ $t('common.logout') }}
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import axios from 'axios'
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { onClickOutside } from '@vueuse/core'
import NotificationBell from '@/Components/NotificationBell.vue'

defineEmits(['toggle-sidebar'])

const page = usePage()
const user = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash ?? {})
const currentLocale = computed(() => page.props.locale ?? 'es')
const userInitials = computed(() => (user.value?.name ?? '').split(' ').map(w => w[0]).slice(0, 2).join(''))

const langOpen = ref(false)
const userOpen = ref(false)
const langRef = ref(null)
const userRef = ref(null)

onClickOutside(langRef, () => { langOpen.value = false })
onClickOutside(userRef, () => { userOpen.value = false })

const locales = [
  { code: 'es', label: 'Español', flag: '🇦🇷' },
  { code: 'en', label: 'English', flag: '🇺🇸' },
]

async function switchLocale(code) {
  langOpen.value = false
  await axios.post('/locale', { locale: code })
  router.reload({ only: [] })
}

function logout() {
  userOpen.value = false
  router.post('/logout', {}, {
    onFinish: () => router.visit('/login'),
  })
}
</script>
