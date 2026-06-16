<template>
  <div class="mx-auto max-w-2xl space-y-8">
    <!-- Header -->
    <div>
      <h1 class="text-xl font-semibold text-gray-900">{{ $t('profile.title') }}</h1>
      <p class="mt-0.5 text-sm text-gray-500">{{ $t('profile.subtitle') }}</p>
    </div>

    <!-- Change password -->
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
      <h2 class="text-base font-semibold text-gray-900">{{ $t('profile.change_password') }}</h2>
      <p class="mt-0.5 text-sm text-gray-500">{{ $t('profile.change_password_subtitle') }}</p>

      <form class="mt-6 space-y-4" @submit.prevent="submitPassword">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('profile.current_password') }}</label>
          <input
            v-model="form.current_password"
            type="password"
            :placeholder="$t('profile.current_password_placeholder')"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            :class="{ 'border-red-400': errors.current_password }"
            autocomplete="current-password"
          />
          <p v-if="errors.current_password" class="mt-1 text-xs text-red-500">{{ errors.current_password[0] }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('profile.new_password') }}</label>
          <input
            v-model="form.password"
            type="password"
            :placeholder="$t('profile.new_password_placeholder')"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            :class="{ 'border-red-400': errors.password }"
            autocomplete="new-password"
          />
          <p v-if="errors.password" class="mt-1 text-xs text-red-500">{{ errors.password[0] }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('profile.confirm_password') }}</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            :placeholder="$t('profile.confirm_password_placeholder')"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            autocomplete="new-password"
          />
        </div>

        <div v-if="successMessage" class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
          <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
          {{ successMessage }}
        </div>

        <div v-if="errorMessage" class="flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm font-medium text-red-700 ring-1 ring-red-200">
          <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
          {{ errorMessage }}
        </div>

        <div class="flex justify-end pt-2">
          <button
            type="submit"
            :disabled="loading"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
          >
            {{ loading ? $t('common.saving') : $t('profile.save_password') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useApi } from '@/composables/useApi.js'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { put, errors, loading } = useApi()

const form = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const successMessage = ref('')
const errorMessage = ref('')

async function submitPassword() {
    successMessage.value = ''
    errorMessage.value = ''

    const { data, error } = await put('/api/v1/profile/password', form.value)

    if (error) {
        errorMessage.value = error
        return
    }

    if (data) {
        successMessage.value = data.message
        form.value = { current_password: '', password: '', password_confirmation: '' }
    }
}
</script>
