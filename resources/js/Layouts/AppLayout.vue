<template>
  <div class="flex h-screen overflow-hidden bg-gray-50" :style="fontStyle">
    <!-- Sidebar -->
    <Sidebar :open="sidebarOpen" @close="sidebarOpen = false" />

    <!-- Main content -->
    <div class="flex flex-1 flex-col overflow-hidden">
      <TopBar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-8">
        <slot />
      </main>
    </div>

    <AiAssistant />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AiAssistant from '@/Components/AiAssistant.vue'
import Sidebar from '@/Components/Sidebar.vue'
import TopBar from '@/Components/TopBar.vue'
import { applyTheme } from '@/composables/useTheme.js'

const sidebarOpen = ref(false)
const page = usePage()

const customization = computed(() => page.props.customization ?? {})

const fontStyle = computed(() => ({
    fontFamily: customization.value.font_family ?? 'Inter',
}))

// Apply on first load
applyTheme(customization.value.primary_color)

// Re-apply when customization changes (e.g. after saving on the settings page)
watch(
    () => customization.value.primary_color,
    (color) => applyTheme(color),
)
</script>
