<template>
  <div class="flex h-full gap-0">
    <!-- Left sidebar -->
    <aside class="hidden w-64 shrink-0 border-r border-gray-200 bg-white lg:flex lg:flex-col">
      <div class="border-b border-gray-200 px-4 py-4">
        <h2 class="text-base font-semibold text-gray-900">{{ $t('help.title') }}</h2>
        <p class="mt-0.5 text-xs text-gray-500">{{ $t('help.subtitle') }}</p>
      </div>
      <nav class="flex-1 overflow-y-auto py-3">
        <template v-for="(groupTopics, groupKey) in topicGroupsWithLabels" :key="groupKey">
          <div class="px-4 pb-1 pt-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">
              {{ $t('help.groups.' + groupKey) }}
            </p>
          </div>
          <a
            v-for="topicKey in groupTopics"
            :key="topicKey"
            :href="'/help/' + topicKey"
            @click.prevent="navigate(topicKey)"
            :class="[
              'flex items-center gap-2 px-4 py-2 text-sm transition-colors',
              topic === topicKey
                ? 'bg-indigo-50 font-medium text-indigo-700 border-r-2 border-indigo-600'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
            ]"
          >
            <svg class="h-3.5 w-3.5 shrink-0 opacity-50" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
            {{ $t('help.topics.' + topicKey) }}
          </a>
        </template>
      </nav>
    </aside>

    <!-- Mobile topic selector -->
    <div class="w-full lg:hidden">
      <div class="border-b border-gray-200 bg-white px-4 py-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('help.title') }}</label>
        <select
          :value="topic"
          @change="navigate($event.target.value)"
          class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option v-for="t in topics" :key="t" :value="t">
            {{ $t('help.topics.' + t) }}
          </option>
        </select>
      </div>
    </div>

    <!-- Right content area -->
    <div class="flex-1 overflow-y-auto">
      <div class="mx-auto max-w-3xl px-6 py-8">
        <!-- Breadcrumb -->
        <div class="mb-6 flex items-center gap-2 text-sm text-gray-500">
          <a href="/help" @click.prevent="navigate('dashboard')" class="hover:text-indigo-600 transition-colors">
            {{ $t('help.title') }}
          </a>
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
          </svg>
          <span class="font-medium text-gray-900">{{ $t('help.topics.' + topic) }}</span>
        </div>

        <!-- Rendered markdown content -->
        <div v-html="content" class="help-content" />

        <!-- Bottom navigation -->
        <div class="mt-12 flex items-center justify-between border-t border-gray-200 pt-6">
          <a
            v-if="prevTopic"
            :href="'/help/' + prevTopic"
            @click.prevent="navigate(prevTopic)"
            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 hover:text-gray-900 transition-colors"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            {{ $t('help.topics.' + prevTopic) }}
          </a>
          <div v-else />
          <a
            v-if="nextTopic"
            :href="'/help/' + nextTopic"
            @click.prevent="navigate(nextTopic)"
            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 hover:text-gray-900 transition-colors"
          >
            {{ $t('help.topics.' + nextTopic) }}
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  topic: { type: String, required: true },
  content: { type: String, required: true },
  topics: { type: Array, required: true },
  topicGroups: { type: Object, required: true },
})

const topicGroupsWithLabels = computed(() => props.topicGroups)

const currentIndex = computed(() => props.topics.indexOf(props.topic))
const prevTopic = computed(() => (currentIndex.value > 0 ? props.topics[currentIndex.value - 1] : null))
const nextTopic = computed(() => (currentIndex.value < props.topics.length - 1 ? props.topics[currentIndex.value + 1] : null))

function navigate(topicKey) {
  router.visit(`/help/${topicKey}`, { preserveScroll: false })
}
</script>

<style scoped>
.help-content :deep(h1) {
  font-size: 1.75rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
  line-height: 1.3;
}

.help-content :deep(h2) {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-top: 2rem;
  margin-bottom: 0.75rem;
  padding-top: 0.5rem;
}

.help-content :deep(h3) {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin-top: 1.5rem;
  margin-bottom: 0.5rem;
}

.help-content :deep(p) {
  font-size: 0.9375rem;
  color: #374151;
  line-height: 1.7;
  margin-bottom: 0.875rem;
}

.help-content :deep(ul),
.help-content :deep(ol) {
  padding-left: 1.5rem;
  margin-bottom: 1rem;
  color: #374151;
}

.help-content :deep(ul) {
  list-style-type: disc;
}

.help-content :deep(ol) {
  list-style-type: decimal;
}

.help-content :deep(li) {
  font-size: 0.9375rem;
  line-height: 1.65;
  margin-bottom: 0.375rem;
}

.help-content :deep(strong) {
  font-weight: 600;
  color: #111827;
}

.help-content :deep(code) {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.8125rem;
  background-color: #f3f4f6;
  color: #4f46e5;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  border: 1px solid #e5e7eb;
}

.help-content :deep(pre) {
  background-color: #1f2937;
  color: #f9fafb;
  padding: 1rem 1.25rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin-bottom: 1rem;
  font-size: 0.8125rem;
  line-height: 1.6;
}

.help-content :deep(pre code) {
  background: none;
  color: inherit;
  padding: 0;
  border: none;
  border-radius: 0;
  font-size: inherit;
}

.help-content :deep(blockquote) {
  border-left: 4px solid #6366f1;
  background-color: #eef2ff;
  padding: 0.75rem 1rem;
  border-radius: 0 0.375rem 0.375rem 0;
  margin: 1rem 0;
  font-style: normal;
}

.help-content :deep(blockquote p) {
  margin-bottom: 0;
  color: #3730a3;
  font-size: 0.875rem;
}

.help-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1.25rem;
  font-size: 0.875rem;
}

.help-content :deep(th) {
  background-color: #f9fafb;
  font-weight: 600;
  color: #374151;
  text-align: left;
  padding: 0.625rem 0.875rem;
  border: 1px solid #e5e7eb;
}

.help-content :deep(td) {
  color: #374151;
  padding: 0.5rem 0.875rem;
  border: 1px solid #e5e7eb;
  vertical-align: top;
}

.help-content :deep(tr:nth-child(even) td) {
  background-color: #f9fafb;
}

.help-content :deep(hr) {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 1.5rem 0;
}

.help-content :deep(a) {
  color: #4f46e5;
  text-decoration: underline;
}

.help-content :deep(a:hover) {
  color: #3730a3;
}
</style>
