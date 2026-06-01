<template>
  <Link
    :href="href"
    :class="[
      'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
      sub ? 'ml-4' : '',
      isActive
        ? 'bg-indigo-600 text-white'
        : 'text-gray-400 hover:bg-gray-800 hover:text-white',
    ]"
  >
    <svg
      v-if="icon && !sub"
      class="h-5 w-5 shrink-0"
      fill="none"
      viewBox="0 0 24 24"
      stroke-width="1.5"
      stroke="currentColor"
    >
      <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
    </svg>
    <span
      v-if="sub"
      :class="['h-1.5 w-1.5 shrink-0 rounded-full', isActive ? 'bg-white' : 'bg-gray-600 group-hover:bg-gray-400']"
    />
    {{ label }}
  </Link>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
  href: { type: String, required: true },
  label: { type: String, required: true },
  icon: { type: String, default: null },
  sub: { type: Boolean, default: false },
})

const page = usePage()
const isActive = computed(() => page.url.startsWith(new URL(props.href, window.location.origin).pathname))
</script>
