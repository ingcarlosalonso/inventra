<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-gray-900">{{ $t('products.import_title') }}</h1>
      <p class="mt-0.5 text-sm text-gray-500">{{ $t('products.import_subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Upload card -->
      <div class="lg:col-span-2 rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-6 space-y-5">
        <h2 class="text-sm font-semibold text-gray-700">{{ $t('products.import_file') }}</h2>

        <!-- Drop zone -->
        <div
          class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 px-6 py-10 text-center transition"
          :class="dragOver ? 'border-indigo-400 bg-indigo-50' : 'hover:border-gray-400'"
          @dragover.prevent="dragOver = true"
          @dragleave="dragOver = false"
          @drop.prevent="onDrop"
        >
          <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
          </svg>
          <p class="mt-3 text-sm text-gray-600">
            <label class="cursor-pointer font-semibold text-indigo-600 hover:text-indigo-500">
              {{ $t('products.import_choose') }}
              <input ref="fileInput" type="file" accept=".xlsx,.xls,.csv" class="sr-only" @change="onFileChange" />
            </label>
            {{ $t('products.import_or_drag') }}
          </p>
          <p class="mt-1 text-xs text-gray-400">XLSX, XLS, CSV — máx. 10 MB</p>
        </div>

        <div v-if="selectedFile" class="flex items-center gap-3 rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-gray-200">
          <svg class="h-5 w-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
          <span class="flex-1 text-sm text-gray-700 truncate">{{ selectedFile.name }}</span>
          <button type="button" class="text-gray-400 hover:text-red-500" @click="clearFile">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <button
          type="button"
          :disabled="!selectedFile || importing"
          class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          @click="doImport"
        >
          <svg v-if="importing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          {{ importing ? $t('products.import_processing') : $t('products.import_start') }}
        </button>

        <!-- Result -->
        <div v-if="result" class="rounded-lg bg-green-50 px-4 py-4 ring-1 ring-green-200 space-y-1">
          <p class="text-sm font-semibold text-green-800">{{ $t('products.import_done') }}</p>
          <p class="text-sm text-green-700">{{ $t('products.import_created', { count: result.imported }) }}</p>
          <p class="text-sm text-green-700">{{ $t('products.import_updated', { count: result.updated }) }}</p>
          <div v-if="result.errors.length" class="mt-2 space-y-1">
            <p class="text-xs font-semibold text-red-700">{{ $t('products.import_errors') }}</p>
            <p v-for="(err, i) in result.errors" :key="i" class="text-xs text-red-600">{{ err }}</p>
          </div>
        </div>

        <div v-if="importError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">{{ importError }}</div>
      </div>

      <!-- Instructions card -->
      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-700">{{ $t('products.import_format') }}</h2>
        <p class="text-xs text-gray-500">{{ $t('products.import_format_hint') }}</p>
        <table class="w-full text-xs">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="pb-1.5 text-left text-gray-500">{{ $t('products.import_col_name') }}</th>
              <th class="pb-1.5 text-left text-gray-500">{{ $t('products.import_col_req') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="col in columns" :key="col.name">
              <td class="py-1.5 font-mono text-gray-700">{{ col.name }}</td>
              <td class="py-1.5 text-gray-500">{{ col.required ? '✓' : '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const { loading: importing, post } = useApi()

const fileInput = ref(null)
const selectedFile = ref(null)
const dragOver = ref(false)
const result = ref(null)
const importError = ref(null)

const columns = [
  { name: 'nombre', required: true },
  { name: 'tipo', required: false },
  { name: 'codigo_barras', required: false },
  { name: 'costo', required: false },
  { name: 'precio', required: false },
  { name: 'stock', required: false },
  { name: 'stock_minimo', required: false },
]

function onFileChange(e) {
  selectedFile.value = e.target.files[0] ?? null
  result.value = null; importError.value = null
}

function onDrop(e) {
  dragOver.value = false
  const file = e.dataTransfer.files[0]
  if (file) { selectedFile.value = file; result.value = null; importError.value = null }
}

function clearFile() {
  selectedFile.value = null; result.value = null; importError.value = null
  if (fileInput.value) fileInput.value.value = ''
}

async function doImport() {
  if (!selectedFile.value) return
  importError.value = null; result.value = null
  const formData = new FormData()
  formData.append('file', selectedFile.value)
  const res = await post('/api/products/import', formData)
  if (res.error) { importError.value = res.error; return }
  if (res.data) result.value = res.data
}
</script>
