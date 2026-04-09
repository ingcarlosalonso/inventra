<template>
  <div>
    <label v-if="label" :for="id" class="mb-1.5 block text-sm font-medium text-gray-700">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-0.5">*</span>
    </label>
    <select
      :id="id"
      :value="modelValue"
      :disabled="disabled"
      :class="[
        'block w-full rounded-lg border px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:outline-none focus:ring-1',
        error
          ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
          : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500',
        disabled ? 'bg-gray-50 cursor-not-allowed' : 'bg-white',
      ]"
      @change="$emit('update:modelValue', options.find(o => String(o.value) === $event.target.value)?.value ?? ($event.target.value || null))"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { useId } from 'vue'

defineProps({
  modelValue: { type: [String, Number, Boolean], default: null },
  label: { type: String, default: null },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: null },
  error: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])

const id = useId()
</script>
