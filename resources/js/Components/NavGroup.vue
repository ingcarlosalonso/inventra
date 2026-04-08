<template>
  <div>
    <button
      type="button"
      class="group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white transition-colors"
      @click="open = !open"
    >
      <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
      </svg>
      <span class="flex-1 text-left">{{ label }}</span>
      <svg
        :class="['h-4 w-4 transition-transform', open ? 'rotate-90' : '']"
        fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
      </svg>
    </button>

    <div v-if="open" class="mt-1 space-y-1">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  label: { type: String, required: true },
  icon: { type: String, required: true },
})

const open = ref(false)
</script>
