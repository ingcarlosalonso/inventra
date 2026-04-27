<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $t('daily_cashes.title') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ $t('common.manage_subtitle') }}</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition"
        @click="openCreate"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        {{ $t('daily_cashes.create') }}
      </button>
    </div>

    <!-- Status filter tabs -->
    <div class="flex items-center gap-2">
      <button
        type="button"
        class="rounded-full px-3 py-1 text-xs font-medium transition"
        :class="statusFilter === null ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'"
        @click="statusFilter = null"
      >
        {{ $t('daily_cashes.filter_all') }}
      </button>
      <button
        type="button"
        class="rounded-full px-3 py-1 text-xs font-medium transition"
        :class="statusFilter === false ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'"
        @click="statusFilter = false"
      >
        {{ $t('daily_cashes.filter_open') }}
      </button>
      <button
        type="button"
        class="rounded-full px-3 py-1 text-xs font-medium transition"
        :class="statusFilter === true ? 'bg-gray-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'"
        @click="statusFilter = true"
      >
        {{ $t('daily_cashes.filter_closed') }}
      </button>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
      <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-3.5">
        <SearchInput v-model="search" class="w-72" />
        <p class="text-sm text-gray-500">{{ meta.total }} {{ $t('common.results') }}</p>
      </div>

      <div v-if="loadingList" class="divide-y divide-gray-100">
        <div v-for="i in 6" :key="i" class="flex items-center gap-4 px-5 py-4 animate-pulse">
          <div class="h-9 w-9 rounded-full bg-gray-200 shrink-0" />
          <div class="flex-1 space-y-1.5">
            <div class="h-4 w-40 rounded bg-gray-200" />
            <div class="h-3 w-28 rounded bg-gray-200" />
          </div>
          <div class="h-4 w-24 rounded bg-gray-200" />
          <div class="h-6 w-20 rounded-full bg-gray-200" />
        </div>
      </div>

      <EmptyState
        v-else-if="items.length === 0"
        :title="search || statusFilter !== null ? $t('common.no_results') : $t('common.empty')"
        :subtitle="search || statusFilter !== null ? $t('common.try_different_search') : null"
      >
        <button
          v-if="!search && statusFilter === null"
          type="button"
          class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition"
          @click="openCreate"
        >
          {{ $t('daily_cashes.create') }}
        </button>
      </EmptyState>

      <ul v-else class="divide-y divide-gray-100">
        <li
          v-for="item in items"
          :key="item.id"
          class="group flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition cursor-pointer"
          @click="router.visit(`/daily-cashes/${item.id}`)"
        >
          <!-- Icon -->
          <div
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
            :class="item.is_closed ? 'bg-gray-100 text-gray-500' : 'bg-emerald-100 text-emerald-700'"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
            </svg>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="truncate text-sm font-medium text-gray-900">
              {{ item.point_of_sale?.name }}
            </p>
            <div class="flex items-center gap-3 mt-0.5">
              <span class="text-xs text-gray-400">{{ formatDateTime(item.opened_at) }}</span>
              <span v-if="item.is_closed && item.closed_at" class="text-xs text-gray-400">
                → {{ formatDateTime(item.closed_at) }}
              </span>
            </div>
          </div>

          <!-- Opening balance -->
          <div class="text-right shrink-0">
            <p class="text-xs text-gray-400">{{ $t('daily_cashes.opening_balance') }}</p>
            <p class="text-sm font-semibold text-gray-900 tabular-nums">${{ formatNumber(item.opening_balance) }}</p>
          </div>

          <!-- Status badge -->
          <span
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset shrink-0"
            :class="item.is_closed
              ? 'bg-gray-50 text-gray-600 ring-gray-200'
              : 'bg-emerald-50 text-emerald-700 ring-emerald-200'"
          >
            {{ item.is_closed ? $t('daily_cashes.status_closed') : $t('daily_cashes.status_open') }}
          </span>

          <!-- Actions -->
          <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition shrink-0" @click.stop>
            <Link
              :href="`/daily-cashes/${item.id}`"
              class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-indigo-600"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </Link>
            <button
              v-if="!item.is_closed"
              type="button"
              class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-600"
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

  <!-- Open Cash SlideOver -->
  <SlideOver v-model="slideOverOpen" :title="$t('daily_cashes.create')">
    <form class="space-y-5" @submit.prevent="save">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          {{ $t('daily_cashes.point_of_sale') }} *
        </label>
        <select
          v-model="form.point_of_sale_id"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="">{{ $t('common.select') }}</option>
          <option v-for="pos in pointsOfSale" :key="pos.id" :value="pos.id">
            {{ pos.name }}
          </option>
        </select>
        <p v-if="formErrors.point_of_sale_id" class="mt-1 text-xs text-red-600">{{ formErrors.point_of_sale_id[0] }}</p>
      </div>
      <InputField
        v-model.number="form.opening_balance"
        type="number"
        step="0.01"
        min="0"
        :label="$t('daily_cashes.opening_balance')"
        :error="formErrors.opening_balance?.[0]"
        required
      />
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('daily_cashes.notes') }}</label>
        <textarea
          v-model="form.notes"
          rows="3"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        />
      </div>
      <div v-if="formError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
        {{ formError }}
      </div>
    </form>
    <template #footer>
      <div class="flex justify-end gap-3">
        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50" @click="slideOverOpen = false">
          {{ $t('common.cancel') }}
        </button>
        <button type="button" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition" @click="save">
          <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          {{ $t('daily_cashes.create') }}
        </button>
      </div>
    </template>
  </SlideOver>

  <ConfirmModal v-model="confirmOpen" :title="$t('daily_cashes.delete_confirm')" @confirm="doDelete" />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SlideOver from '@/Components/SlideOver.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import SearchInput from '@/Components/SearchInput.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Pagination from '@/Components/Pagination.vue'
import InputField from '@/Components/InputField.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const { loading: loadingList, get } = useApi()
const { loading: saving, errors: formErrors, post: postForm } = useApi()
const { del } = useApi()

const items = ref([])
const meta = ref({ total: 0, last_page: 1, links: [] })
const search = ref('')
const statusFilter = ref(null)
const slideOverOpen = ref(false)
const confirmOpen = ref(false)
const deleteTarget = ref(null)
const formError = ref(null)
const pointsOfSale = ref([])

const emptyForm = () => ({ point_of_sale_id: '', opening_balance: 0, notes: '' })
const form = ref(emptyForm())

async function fetchItems(url = null) {
  const params = {}
  if (search.value) params.search = search.value
  if (statusFilter.value !== null) params.is_closed = statusFilter.value ? 1 : 0
  const { data } = await get(url ?? '/api/daily-cashes', url ? {} : params)
  if (data) { items.value = data.data; meta.value = data.meta }
}

async function fetchPointsOfSale() {
  const { data } = await get('/api/points-of-sale', { per_page: 100 })
  if (data) pointsOfSale.value = data.data
}

function openCreate() {
  form.value = emptyForm()
  formError.value = null
  slideOverOpen.value = true
}

async function save() {
  formError.value = null
  const result = await postForm('/api/daily-cashes', form.value)
  if (result.error) {
    if (!Object.keys(formErrors.value).length) formError.value = result.error
    return
  }
  slideOverOpen.value = false
  const newId = result.data?.data?.id
  if (newId) router.visit(`/daily-cashes/${newId}`)
  else await fetchItems()
}

function confirmDelete(item) { deleteTarget.value = item; confirmOpen.value = true }

async function doDelete() {
  confirmOpen.value = false
  await del(`/api/daily-cashes/${deleteTarget.value.id}`)
  await fetchItems()
}

function navigateTo(url) { fetchItems(url) }

function formatDateTime(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString()
}

function formatNumber(value) {
  const num = parseFloat(value) || 0
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

let searchDebounce
watch(search, () => { clearTimeout(searchDebounce); searchDebounce = setTimeout(() => fetchItems(), 300) })
watch(statusFilter, () => fetchItems())
onMounted(() => { fetchItems(); fetchPointsOfSale() })
</script>
