<template>
  <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">{{ $t('product_movement_types.title') }}</h1>
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
          {{ $t('product_movement_types.create') }}
        </button>
      </div>

      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-3.5">
          <SearchInput v-model="search" class="w-64" />
          <p class="text-sm text-gray-500">{{ meta.total }} {{ $t('common.results') }}</p>
        </div>

        <div v-if="loadingList" class="divide-y divide-gray-100">
          <div v-for="i in 5" :key="i" class="flex items-center gap-4 px-5 py-4 animate-pulse">
            <div class="h-4 w-48 rounded bg-gray-200" />
            <div class="h-5 w-16 rounded-full bg-gray-200 ml-2" />
            <div class="h-5 w-16 rounded-full bg-gray-200 ml-auto" />
            <div class="h-4 w-20 rounded bg-gray-200" />
          </div>
        </div>

        <EmptyState
          v-else-if="items.length === 0"
          :title="search ? $t('common.no_results') : $t('common.empty')"
          :subtitle="search ? $t('common.try_different_search') : null"
        >
          <button
            v-if="!search"
            type="button"
            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition"
            @click="openCreate"
          >
            {{ $t('product_movement_types.create') }}
          </button>
        </EmptyState>

        <ul v-else class="divide-y divide-gray-100">
          <li
            v-for="item in items"
            :key="item.id"
            class="group flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition"
          >
            <div class="flex-1 min-w-0">
              <p class="truncate text-sm font-medium text-gray-900">{{ item.name }}</p>
            </div>

            <!-- Direction badge -->
            <span
              :class="[
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1',
                item.is_income
                  ? 'bg-blue-50 text-blue-700 ring-blue-200'
                  : 'bg-amber-50 text-amber-700 ring-amber-200',
              ]"
            >
              {{ item.is_income ? $t('product_movement_types.direction_in') : $t('product_movement_types.direction_out') }}
            </span>

            <StatusBadge :active="item.is_active" />

            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
              <button
                type="button"
                class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-indigo-600"
                @click="openEdit(item)"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
              </button>
              <button
                type="button"
                class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-600"
                @click="confirmDelete(item)"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
              </button>
            </div>
          </li>
        </ul>

        <div v-if="meta.last_page > 1" class="px-5 py-4">
          <Pagination :meta="meta" @navigate="navigateTo" />
        </div>
      </div>
    </div>

    <SlideOver v-model="slideOverOpen" :title="editing ? $t('product_movement_types.edit') : $t('product_movement_types.create')">
      <form class="space-y-5" @submit.prevent="save">
        <InputField
          v-model="form.name"
          :label="$t('common.name')"
          :error="formErrors.name?.[0]"
          required
        />

        <SelectField
          v-model="form.is_income"
          :label="$t('product_movement_types.direction')"
          :options="directionOptions"
          required
        />

        <ToggleSwitch v-model="form.is_active" :label="$t('common.active')" />

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
            <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            {{ $t('common.save') }}
          </button>
        </div>
      </template>
    </SlideOver>

    <ConfirmModal
      v-model="confirmOpen"
      :title="$t('product_movement_types.delete_confirm', { name: deleteTarget?.name })"
      @confirm="doDelete"
    />
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import SlideOver from '@/Components/SlideOver.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import SearchInput from '@/Components/SearchInput.vue'
import EmptyState from '@/Components/EmptyState.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import InputField from '@/Components/InputField.vue'
import SelectField from '@/Components/SelectField.vue'
import ToggleSwitch from '@/Components/ToggleSwitch.vue'
import { useApi } from '@/composables/useApi'
import { getCurrentInstance } from 'vue'

defineOptions({ layout: AppLayout })

const { $t } = getCurrentInstance().appContext.config.globalProperties

const { loading: loadingList, get } = useApi()
const { loading: saving, errors: formErrors, post: postForm, put: putForm } = useApi()
const { del } = useApi()

const items = ref([])
const meta = ref({ total: 0, last_page: 1, links: [] })
const search = ref('')
const slideOverOpen = ref(false)
const confirmOpen = ref(false)
const editing = ref(null)
const deleteTarget = ref(null)
const formError = ref(null)

const form = ref({ name: '', is_income: true, is_active: true })

const directionOptions = computed(() => [
    { value: true, label: $t('product_movement_types.direction_in') },
    { value: false, label: $t('product_movement_types.direction_out') },
])

async function fetchItems(url = null) {
    const params = {}
    if (search.value) params.search = search.value
    const endpoint = url ?? '/api/product-movement-types'
    const { data } = await get(endpoint, url ? {} : params)
    if (data) {
        items.value = data.data
        meta.value = data.meta
    }
}

function openCreate() {
    editing.value = null
    form.value = { name: '', is_income: true, is_active: true }
    formError.value = null
    slideOverOpen.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = { name: item.name, is_income: item.is_income, is_active: item.is_active }
    formError.value = null
    slideOverOpen.value = true
}

async function save() {
    formError.value = null
    const payload = { name: form.value.name, is_income: form.value.is_income, is_active: form.value.is_active }

    const result = editing.value
        ? await putForm(`/api/product-movement-types/${editing.value.id}`, payload)
        : await postForm('/api/product-movement-types', payload)

    if (result.error) {
        if (!Object.keys(formErrors.value).length) formError.value = result.error
        return
    }

    slideOverOpen.value = false
    await fetchItems()
}

function confirmDelete(item) {
    deleteTarget.value = item
    confirmOpen.value = true
}

async function doDelete() {
    confirmOpen.value = false
    await del(`/api/product-movement-types/${deleteTarget.value.id}`)
    await fetchItems()
}

function navigateTo(url) {
    fetchItems(url)
}

let searchDebounce
watch(search, () => {
    clearTimeout(searchDebounce)
    searchDebounce = setTimeout(() => fetchItems(), 300)
})

onMounted(fetchItems)
</script>
