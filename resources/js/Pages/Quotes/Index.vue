<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $t('quotes.title') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ $t('common.manage_subtitle') }}</p>
      </div>
      <Link
        href="/quotes/create"
        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        {{ $t('quotes.create') }}
      </Link>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
      <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-3.5">
        <SearchInput v-model="search" class="w-72" />
        <p class="text-sm text-gray-500">{{ meta.total }} {{ $t('common.results') }}</p>
      </div>

      <div v-if="loadingList" class="divide-y divide-gray-100">
        <div v-for="i in 8" :key="i" class="flex items-center gap-4 px-5 py-4 animate-pulse">
          <div class="h-9 w-9 rounded-full bg-gray-200 shrink-0" />
          <div class="flex-1 space-y-1.5">
            <div class="h-4 w-48 rounded bg-gray-200" />
            <div class="h-3 w-28 rounded bg-gray-200" />
          </div>
          <div class="h-6 w-20 rounded-full bg-gray-200" />
          <div class="h-4 w-24 rounded bg-gray-200" />
        </div>
      </div>

      <EmptyState
        v-else-if="items.length === 0"
        :title="search ? $t('common.no_results') : $t('common.empty')"
        :subtitle="search ? $t('common.try_different_search') : null"
      >
        <Link
          v-if="!search"
          href="/quotes/create"
          class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition"
        >
          {{ $t('quotes.create') }}
        </Link>
      </EmptyState>

      <ul v-else class="divide-y divide-gray-100">
        <li
          v-for="item in items"
          :key="item.id"
          class="group flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition cursor-pointer"
          @click="() => router.visit(`/quotes/${item.id}`)"
        >
          <!-- Icon -->
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <p class="truncate text-sm font-medium text-gray-900">
                {{ item.client?.name ?? $t('quotes.no_client') }}
              </p>
              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                :class="item.is_converted
                  ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                  : 'bg-amber-50 text-amber-700 ring-amber-200'"
              >
                {{ item.is_converted ? $t('quotes.converted') : $t('quotes.not_converted') }}
              </span>
            </div>
            <div class="flex items-center gap-3 mt-0.5">
              <span v-if="item.expires_at" class="text-xs text-gray-500">
                {{ $t('quotes.expires_at') }}: {{ formatDate(item.expires_at) }}
              </span>
              <span class="text-xs text-gray-400">{{ item.user?.name }}</span>
              <span class="text-xs text-gray-400">{{ formatDate(item.created_at) }}</span>
            </div>
          </div>

          <!-- Total -->
          <span class="text-sm font-semibold text-gray-900 tabular-nums">${{ formatNumber(item.total) }}</span>

          <!-- Actions -->
          <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition" @click.stop>
            <Link
              :href="`/quotes/${item.id}`"
              class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-indigo-600"
              :title="$t('common.view')"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </Link>
            <button
              v-if="!item.is_converted"
              type="button"
              class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-600"
              :title="$t('common.delete')"
              @click="confirmDelete(item)"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
            </button>
          </div>
        </li>
      </ul>

      <div v-if="meta.last_page > 1" class="px-5 py-4">
        <Pagination :meta="meta" @navigate="navigateTo" />
      </div>
    </div>
  </div>

  <ConfirmModal v-model="confirmOpen" :title="$t('quotes.delete_confirm')" @confirm="doDelete" />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import SearchInput from '@/Components/SearchInput.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Pagination from '@/Components/Pagination.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const { loading: loadingList, get } = useApi()
const { del } = useApi()

const items = ref([])
const meta = ref({ total: 0, last_page: 1, links: [] })
const search = ref('')
const confirmOpen = ref(false)
const deleteTarget = ref(null)

async function fetchItems(url = null) {
  const params = {}
  if (search.value) params.search = search.value
  const { data } = await get(url ?? '/api/quotes', url ? {} : params)
  if (data) { items.value = data.data; meta.value = data.meta }
}

function confirmDelete(item) { deleteTarget.value = item; confirmOpen.value = true }
async function doDelete() {
  confirmOpen.value = false
  await del(`/api/quotes/${deleteTarget.value.id}`)
  await fetchItems()
}
function navigateTo(url) { fetchItems(url) }

function formatDate(str) {
  if (!str) return ''
  return new Date(str.includes('T') ? str : str + 'T00:00:00').toLocaleDateString()
}
function formatNumber(value) {
  const num = parseFloat(value) || 0
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

let searchDebounce
watch(search, () => { clearTimeout(searchDebounce); searchDebounce = setTimeout(() => fetchItems(), 300) })
onMounted(fetchItems)
</script>
