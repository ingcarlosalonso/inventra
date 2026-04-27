<template>
  <div>
    <label v-if="label" :for="id" class="mb-1.5 block text-sm font-medium text-gray-700">
      {{ label }}
    </label>
    <textarea
      :id="id"
      :value="modelValue"
      :rows="rows"
      :placeholder="placeholder"
      :class="[
        'block w-full rounded-lg border px-3 py-2 text-sm text-gray-900 shadow-sm placeholder-gray-400 resize-none transition focus:outline-none focus:ring-1',
        error
          ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
          : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500',
      ]"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { useId } from 'vue'

defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: null },
  placeholder: { type: String, default: null },
  rows: { type: Number, default: 3 },
  error: { type: String, default: null },
})
defineEmits(['update:modelValue'])

const id = useId()
</script>
