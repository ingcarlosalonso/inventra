<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ label }}</label>
    <div class="flex items-center gap-2">
      <div
        class="relative h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-gray-300 cursor-pointer shadow-sm"
        :style="{ backgroundColor: modelValue }"
      >
        <input
          type="color"
          :value="modelValue"
          class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
          @input="$emit('update:modelValue', $event.target.value)"
        />
      </div>
      <input
        type="text"
        :value="modelValue"
        maxlength="7"
        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @input="onTextInput"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: String, required: true },
  label: { type: String, required: true },
})

const emit = defineEmits(['update:modelValue'])

function onTextInput(e) {
  const val = e.target.value
  if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
    emit('update:modelValue', val)
  }
}
</script>
