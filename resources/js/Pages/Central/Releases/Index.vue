<template>
  <CentralLayout>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6 flex items-start justify-between">
        <div>
          <h1 class="text-xl font-bold text-white">{{ $t('releases.title') }}</h1>
          <p class="mt-0.5 text-sm text-gray-400">{{ $t('releases.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
          <span
            v-if="release.is_published"
            class="inline-flex items-center gap-1.5 rounded-full bg-green-900/50 px-3 py-1 text-xs font-medium text-green-300"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-green-400"></span>
            {{ $t('releases.published') }}
          </span>
          <span
            v-else-if="saved"
            class="inline-flex items-center gap-1.5 rounded-full bg-yellow-900/50 px-3 py-1 text-xs font-medium text-yellow-300"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-yellow-400"></span>
            {{ $t('releases.draft') }}
          </span>
          <span
            v-else
            class="inline-flex items-center gap-1.5 rounded-full bg-gray-800 px-3 py-1 text-xs font-medium text-gray-400"
          >
            {{ $t('releases.not_saved') }}
          </span>
        </div>
      </div>

      <!-- Flash -->
      <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg border border-green-700/50 bg-green-900/40 px-4 py-3 text-sm text-green-300">
        {{ $page.props.flash.success }}
      </div>

      <!-- Form -->
      <form @submit.prevent="save" class="space-y-6">
        <!-- Meta -->
        <div class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900">
          <div class="border-b border-gray-800 px-5 py-3.5">
            <h2 class="text-sm font-semibold text-white">{{ $t('releases.meta') }}</h2>
          </div>
          <div class="space-y-4 p-5">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-400">{{ $t('releases.version') }}</label>
                <input
                  v-model="form.version"
                  type="text"
                  readonly
                  class="w-full rounded-lg border border-gray-700 bg-gray-800/60 px-3 py-2 text-sm text-gray-400 outline-none font-mono"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-400">{{ $t('releases.release_title') }} *</label>
                <input
                  v-model="form.title"
                  type="text"
                  required
                  :disabled="release.is_published"
                  class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:opacity-50"
                />
              </div>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-400">{{ $t('releases.summary') }}</label>
              <textarea
                v-model="form.summary"
                rows="3"
                :disabled="release.is_published"
                class="w-full resize-none rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:opacity-50"
              />
            </div>
          </div>
        </div>

        <!-- Items -->
        <div class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900">
          <div class="flex items-center justify-between border-b border-gray-800 px-5 py-3.5">
            <h2 class="text-sm font-semibold text-white">{{ $t('releases.items') }} ({{ form.items.length }})</h2>
            <button
              v-if="!release.is_published"
              type="button"
              @click="addItem"
              class="flex items-center gap-1.5 rounded-lg bg-indigo-600/20 px-3 py-1.5 text-xs font-medium text-indigo-300 transition hover:bg-indigo-600/30"
            >
              <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
              {{ $t('releases.add_item') }}
            </button>
          </div>

          <div v-if="form.items.length === 0" class="px-5 py-10 text-center text-sm text-gray-500">
            {{ $t('releases.no_items') }}
          </div>

          <div v-else class="divide-y divide-gray-800">
            <div
              v-for="(item, index) in form.items"
              :key="index"
              class="flex items-start gap-3 px-5 py-3.5"
            >
              <!-- Type badge -->
              <div class="mt-0.5 shrink-0">
                <select
                  v-if="!release.is_published"
                  v-model="item.type"
                  class="rounded-md border border-gray-700 bg-gray-800 px-2 py-1 text-xs outline-none focus:border-indigo-500"
                  :class="typeClass(item.type)"
                >
                  <option value="feature">{{ $t('releases.types.feature') }}</option>
                  <option value="fix">{{ $t('releases.types.fix') }}</option>
                  <option value="improvement">{{ $t('releases.types.improvement') }}</option>
                  <option value="security">{{ $t('releases.types.security') }}</option>
                  <option value="removal">{{ $t('releases.types.removal') }}</option>
                  <option value="deprecation">{{ $t('releases.types.deprecation') }}</option>
                </select>
                <span
                  v-else
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="typeBadgeClass(item.type)"
                >
                  {{ $t('releases.types.' + item.type) }}
                </span>
              </div>

              <!-- Title -->
              <div class="flex-1">
                <input
                  v-if="!release.is_published"
                  v-model="item.title"
                  type="text"
                  required
                  :placeholder="$t('releases.item_placeholder')"
                  class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-1.5 text-sm text-white outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                />
                <p v-else class="py-1.5 text-sm text-gray-300">{{ item.title }}</p>
              </div>

              <!-- Delete -->
              <button
                v-if="!release.is_published"
                type="button"
                @click="removeItem(index)"
                class="mt-1.5 shrink-0 text-gray-600 transition hover:text-red-400"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div v-if="!release.is_published" class="flex items-center justify-between">
          <button
            type="submit"
            :disabled="saving"
            class="rounded-lg bg-gray-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-gray-600 disabled:opacity-60"
          >
            {{ saving ? '...' : $t('releases.save_draft') }}
          </button>

          <button
            v-if="saved"
            type="button"
            :disabled="publishing"
            @click="publish"
            class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-60"
          >
            {{ publishing ? '...' : $t('releases.publish') }}
          </button>
        </div>

        <div v-else class="flex justify-end">
          <button
            type="button"
            :disabled="publishing"
            @click="unpublish"
            class="rounded-lg border border-gray-700 px-5 py-2 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-white disabled:opacity-60"
          >
            {{ publishing ? '...' : $t('releases.unpublish') }}
          </button>
        </div>
      </form>
    </div>
  </CentralLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import CentralLayout from '@/Layouts/CentralLayout.vue'

const props = defineProps({
  saved: Boolean,
  release: Object,
})

const saving = ref(false)
const publishing = ref(false)

const form = reactive({
  version: props.release.version,
  title: props.release.title,
  summary: props.release.summary ?? '',
  items: (props.release.items ?? []).map((item, i) => ({
    id: item.id ?? null,
    type: item.type,
    title: item.title,
    order: item.order ?? i,
  })),
})

function addItem() {
  form.items.push({ id: null, type: 'feature', title: '', order: form.items.length })
}

function removeItem(index) {
  form.items.splice(index, 1)
  form.items.forEach((item, i) => { item.order = i })
}

function normalizedItems() {
  return form.items.map((item, i) => ({
    type: item.type,
    title: item.title,
    order: i,
  }))
}

function save() {
  saving.value = true
  const data = {
    version: form.version,
    title: form.title,
    summary: form.summary,
    items: normalizedItems(),
  }

  if (props.saved) {
    router.put(`/releases/${props.release.id}`, data, {
      onFinish: () => { saving.value = false },
    })
  } else {
    router.post('/releases', data, {
      onFinish: () => { saving.value = false },
    })
  }
}

function publish() {
  publishing.value = true
  router.post(`/releases/${props.release.id}/publish`, {}, {
    onFinish: () => { publishing.value = false },
  })
}

function unpublish() {
  publishing.value = true
  router.post(`/releases/${props.release.id}/unpublish`, {}, {
    onFinish: () => { publishing.value = false },
  })
}

function typeClass(type) {
  return {
    feature: 'text-indigo-300',
    fix: 'text-red-300',
    improvement: 'text-yellow-300',
    security: 'text-orange-300',
    removal: 'text-rose-300',
    deprecation: 'text-gray-400',
  }[type] ?? 'text-gray-300'
}

function typeBadgeClass(type) {
  return {
    feature: 'bg-indigo-900/50 text-indigo-300',
    fix: 'bg-red-900/50 text-red-300',
    improvement: 'bg-yellow-900/50 text-yellow-300',
    security: 'bg-orange-900/50 text-orange-300',
    removal: 'bg-rose-900/50 text-rose-300',
    deprecation: 'bg-gray-700/50 text-gray-400',
  }[type] ?? 'bg-gray-800 text-gray-400'
}
</script>
