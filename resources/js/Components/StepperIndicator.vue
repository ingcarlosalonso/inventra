<template>
  <div class="flex items-center">
    <template v-for="(step, i) in steps" :key="i">
      <button
        type="button"
        :disabled="i >= current"
        class="flex items-center gap-2.5 min-w-0"
        :class="i < current ? 'cursor-pointer' : 'cursor-default'"
        @click="i < current && $emit('go', i)"
      >
        <span
          class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-all duration-200"
          :class="circleClass(i)"
        >
          <svg v-if="i < current" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
          <span v-else>{{ i + 1 }}</span>
        </span>
        <span
          class="text-sm font-medium hidden sm:block whitespace-nowrap transition-colors duration-200"
          :class="labelClass(i)"
        >
          {{ step }}
        </span>
      </button>

      <div
        v-if="i < steps.length - 1"
        class="mx-3 h-px flex-1 min-w-4 transition-colors duration-300"
        :class="i < current ? 'bg-indigo-500' : 'bg-gray-200'"
      />
    </template>
  </div>
</template>

<script setup>
const props = defineProps({
  steps: Array,
  current: Number,
})

defineEmits(['go'])

function circleClass(i) {
  if (i < props.current) return 'bg-indigo-600 text-white'
  if (i === props.current) return 'bg-indigo-600 text-white shadow-md shadow-indigo-200 ring-4 ring-indigo-100'
  return 'bg-white text-gray-400 ring-1 ring-gray-300'
}

function labelClass(i) {
  if (i < props.current) return 'text-indigo-600'
  if (i === props.current) return 'text-gray-900'
  return 'text-gray-400'
}
</script>
