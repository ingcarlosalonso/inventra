<template>
  <span
    :class="[
      'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
      active
        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
        : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200',
    ]"
  >
    <span :class="['mr-1.5 h-1.5 w-1.5 rounded-full', active ? 'bg-emerald-500' : 'bg-gray-400']" />
    {{ active ? $t('common.active') : $t('common.inactive') }}
  </span>
</template>

<script setup>
defineProps({ active: { type: Boolean, required: true } })
</script>
