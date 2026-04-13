<template>
  <div class="space-y-6">
    <!-- Page header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $t('presentations.title') }}</h1>
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
        {{ $t('presentations.create') }}
      </button>
    </div>

    <!-- Table card -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
      <!-- Toolbar -->
      <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-3.5">
        <SearchInput v-model="search" class="w-64" />
        <p class="text-sm text-gray-500">
          {{ items.length }} {{ $t('common.results') }}
        </p>
      </div>

      <!-- Loading -->
      <div v-if="loadingList" class="divide-y divide-gray-100">
        <div v-for="i in 5" :key="i" class="flex items-center gap-4 px-5 py-4 animate-pulse">
          <div class="h-4 w-24 rounded bg-gray-200" />
          <div class="h-4 w-32 rounded bg-gray-200" />
          <div class="h-5 w-16 rounded-full bg-gray-200 ml-auto" />
          <div class="h-4 w-20 rounded bg-gray-200" />
        </div>
      </div>

      <!-- Empty state -->
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
          {{ $t('presentations.create') }}
        </button>
      </EmptyState>

      <!-- List -->
      <ul v-else class="divide-y divide-gray-100">
        <li
          v-for="item in items"
          :key="item.id"
          class="group flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition"
        >
          <!-- Display: "1 kg", "500 g", "6 un" -->
          <p class="w-24 shrink-0 text-sm font-semibold text-gray-900">{{ item.display }}</p>

          <!-- Type name -->
          <p class="flex-1 truncate text-sm text-gray-500">{{ item.presentation_type?.name }}</p>

          <StatusBadge :active="item.is_active" />

          <!-- Actions -->
          <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
            <button
              type="button"
              class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-indigo-600"
              :title="$t('common.edit')"
              @click="openEdit(item)"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
              </svg>
            </button>
            <button
              type="button"
              class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-600"
              :title="$t('common.delete')"
              @click="confirmDelete(item)"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
              </svg>
            </button>
          </div>
        </li>
      </ul>
    </div>
  </div>

  <!-- Slide-over form -->
  <SlideOver v-model="slideOverOpen" :title="editing ? $t('presentations.edit') : $t('presentations.create')">
    <form class="space-y-5" @submit.prevent="save">
      <SelectField
        v-model="form.presentation_type_id"
        :label="$t('presentations.presentation_type')"
        :options="presentationTypeOptions"
        :error="formErrors.presentation_type_id?.[0]"
        required
      />

      <InputField
        v-model="form.quantity"
        :label="$t('presentations.quantity')"
        :error="formErrors.quantity?.[0]"
        type="number"
        step="0.001"
        min="0.001"
        required
      />

      <ToggleSwitch
        v-model="form.is_active"
        :label="$t('common.active')"
      />

      <div v-if="formError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
        {{ formError }}
      </div>
    </form>

    <template #footer>
      <div class="flex justify-end gap-3">
        <button
          type="button"
          class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50"
          @click="slideOverOpen = false"
        >
          {{ $t('common.cancel') }}
        </button>
        <button
          type="button"
          :disabled="saving"
          class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition"
          @click="save"
        >
          <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          {{ $t('common.save') }}
        </button>
      </div>
    </template>
  </SlideOver>

  <!-- Confirm delete -->
  <ConfirmModal
    v-model="confirmOpen"
    :title="$t('presentations.delete_confirm', { display: deleteTarget?.display })"
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
import InputField from '@/Components/InputField.vue'
import SelectField from '@/Components/SelectField.vue'
import ToggleSwitch from '@/Components/ToggleSwitch.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const { loading: loadingList, get, del } = useApi()
const { loading: saving, errors: formErrors, post: postForm, put: putForm } = useApi()

const items = ref([])
const presentationTypes = ref([])
const search = ref('')
const slideOverOpen = ref(false)
const confirmOpen = ref(false)
const editing = ref(null)
const deleteTarget = ref(null)
const formError = ref(null)

const form = ref({ presentation_type_id: null, quantity: '', is_active: true })

const presentationTypeOptions = computed(() =>
  presentationTypes.value.map(t => ({ value: t.id, label: `${t.name} (${t.abbreviation})` }))
)

async function fetchItems() {
  const params = {}
  if (search.value) params.search = search.value
  const { data } = await get('/api/presentations', params)
  if (data) items.value = data.data
}

async function fetchPresentationTypes() {
  const { data } = await get('/api/presentation-types')
  if (data) presentationTypes.value = data.data
}

function openCreate() {
  editing.value = null
  form.value = { presentation_type_id: null, quantity: '', is_active: true }
  formError.value = null
  slideOverOpen.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    presentation_type_id: item.presentation_type_id ?? null,
    quantity: item.quantity,
    is_active: item.is_active,
  }
  formError.value = null
  slideOverOpen.value = true
}

async function save() {
  formError.value = null
  const payload = {
    presentation_type_id: form.value.presentation_type_id,
    quantity: form.value.quantity,
    is_active: form.value.is_active,
  }

  let result
  if (editing.value) {
    result = await putForm(`/api/presentations/${editing.value.id}`, payload)
  } else {
    result = await postForm('/api/presentations', payload)
  }

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
  await del(`/api/presentations/${deleteTarget.value.id}`)
  await fetchItems()
}

let searchDebounce
watch(search, () => {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(fetchItems, 300)
})

onMounted(() => {
  fetchItems()
  fetchPresentationTypes()
})
</script>
