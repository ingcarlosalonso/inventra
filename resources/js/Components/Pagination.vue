<template>
  <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-gray-200 px-1 pt-4">
    <p class="text-sm text-gray-500">
      {{ $t('common.showing') }}
      <span class="font-medium">{{ meta.from }}</span>–<span class="font-medium">{{ meta.to }}</span>
      {{ $t('common.of') }}
      <span class="font-medium">{{ meta.total }}</span>
    </p>
    <div class="flex gap-1">
      <button
        v-for="link in meta.links"
        :key="link.label"
        :disabled="!link.url || link.active"
        :class="[
          'rounded px-3 py-1.5 text-sm font-medium transition-colors',
          link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100',
          !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
        ]"
        v-html="link.label"
        @click="link.url && $emit('navigate', link.url)"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({ meta: { type: Object, required: true } })
defineEmits(['navigate'])
</script>
