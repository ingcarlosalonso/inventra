<template>
  <div class="mx-auto max-w-3xl space-y-8">
    <!-- Header -->
    <div>
      <h1 class="text-xl font-semibold text-gray-900">{{ $t('customization.title') }}</h1>
      <p class="mt-0.5 text-sm text-gray-500">{{ $t('customization.subtitle') }}</p>
    </div>

    <form class="space-y-6" @submit.prevent="submit">
      <!-- Logo -->
      <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('customization.logo') }}</h2>

        <div class="flex items-center gap-5">
          <!-- Preview -->
          <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 overflow-hidden">
            <img v-if="logoPreview" :src="logoPreview" class="h-full w-full object-contain" alt="logo" />
            <svg v-else class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M6.75 6.75h.008v.008H6.75V6.75z" />
            </svg>
          </div>

          <div class="flex flex-col gap-2">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
              </svg>
              {{ logoPreview ? $t('customization.logo_change') : $t('customization.logo_upload') }}
              <input ref="logoInput" type="file" accept="image/png,image/jpeg,image/jpg,image/svg+xml" class="sr-only" @change="onLogoChange" />
            </label>

            <button
              v-if="logoPreview"
              type="button"
              class="inline-flex items-center gap-1.5 text-sm text-red-500 hover:text-red-700 transition"
              @click="removeLogo"
            >
              <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
              {{ $t('customization.logo_remove') }}
            </button>

            <p class="text-xs text-gray-400">{{ $t('customization.logo_help') }}</p>
          </div>
        </div>
      </section>

      <!-- Colors -->
      <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('customization.colors') }}</h2>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
          <ColorPicker v-model="form.primary_color" :label="$t('customization.primary_color')" />
          <ColorPicker v-model="form.secondary_color" :label="$t('customization.secondary_color')" />
          <ColorPicker v-model="form.accent_color" :label="$t('customization.accent_color')" />
        </div>
      </section>

      <!-- Typography -->
      <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('customization.typography') }}</h2>

        <div class="max-w-xs">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('customization.font_family') }}</label>
          <select
            v-model="form.font_family"
            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option v-for="font in fonts" :key="font" :value="font" :style="{ fontFamily: font }">
              {{ font }}
            </option>
          </select>
        </div>

        <p class="mt-4 text-sm text-gray-500" :style="{ fontFamily: form.font_family }">
          {{ $t('customization.preview') }} — The quick brown fox jumps over the lazy dog.
        </p>
      </section>

      <!-- Actions -->
      <div class="flex items-center justify-between">
        <button
          type="button"
          class="text-sm text-gray-400 hover:text-gray-600 transition"
          @click="resetDefaults"
        >
          {{ $t('customization.reset_defaults') }}
        </button>

        <button
          type="submit"
          :disabled="saving"
          class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition"
        >
          <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
          {{ $t('customization.save') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import ColorPicker from '@/Components/ColorPicker.vue'
import { applyTheme } from '@/composables/useTheme.js'

defineOptions({ layout: AppLayout })

const FONTS = ['Inter', 'Roboto', 'Lato', 'Montserrat', 'Open Sans', 'Nunito', 'Poppins']

const fonts = FONTS
const saving = ref(false)
const logoInput = ref(null)
const logoPreview = ref(null)
const logoFile = ref(null)
const removingLogo = ref(false)

const form = reactive({
  primary_color: '#3B82F6',
  secondary_color: '#1E40AF',
  accent_color: '#F59E0B',
  font_family: 'Inter',
})

onMounted(async () => {
  const { data } = await axios.get('/api/customization')
  form.primary_color = data.data.primary_color
  form.secondary_color = data.data.secondary_color
  form.accent_color = data.data.accent_color
  form.font_family = data.data.font_family
  logoPreview.value = data.data.logo_url ?? null
  loadGoogleFont(form.font_family)
})

function onLogoChange(e) {
  const file = e.target.files[0]
  if (!file) { return }
  logoFile.value = file
  removingLogo.value = false
  const reader = new FileReader()
  reader.onload = (ev) => { logoPreview.value = ev.target.result }
  reader.readAsDataURL(file)
}

function removeLogo() {
  logoPreview.value = null
  logoFile.value = null
  removingLogo.value = true
  if (logoInput.value) { logoInput.value.value = '' }
}

const DEFAULTS = {
  primary_color: '#4F46E5',
  secondary_color: '#111827',
  accent_color: '#ffffff',
  font_family: 'Inter',
}

function resetDefaults() {
  Object.assign(form, DEFAULTS)
  logoPreview.value = null
  logoFile.value = null
  removingLogo.value = true
}

function loadGoogleFont(family) {
  const id = `gfont-${family.replace(/\s+/g, '-')}`
  if (document.getElementById(id)) { return }
  const link = document.createElement('link')
  link.id = id
  link.rel = 'stylesheet'
  link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(family)}:wght@400;500;600;700&display=swap`
  document.head.appendChild(link)
}

async function submit() {
  saving.value = true

  try {
    const payload = new FormData()
    payload.append('primary_color', form.primary_color)
    payload.append('secondary_color', form.secondary_color)
    payload.append('accent_color', form.accent_color)
    payload.append('font_family', form.font_family)

    if (logoFile.value) {
      payload.append('logo', logoFile.value)
    }
    if (removingLogo.value) {
      payload.append('remove_logo', '1')
    }

    const { data } = await axios.post('/api/customization', payload)

    logoPreview.value = data.data.logo_url ?? null
    logoFile.value = null
    removingLogo.value = false

    loadGoogleFont(form.font_family)
    applyTheme(form.primary_color)
  } finally {
    saving.value = false
  }
}
</script>
