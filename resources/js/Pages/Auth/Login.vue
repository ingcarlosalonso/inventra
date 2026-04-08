<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm">
      <!-- Logo -->
      <div class="mb-8 flex flex-col items-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 shadow-lg mb-4">
          <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Inventra</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $t('common.login_subtitle') }}</p>
      </div>

      <!-- Card -->
      <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
        <form @submit.prevent="submit" class="space-y-5">
          <InputField
            v-model="form.email"
            :label="$t('common.email')"
            type="email"
            :placeholder="$t('common.email_placeholder')"
            :error="errors.email?.[0]"
            required
          />

          <InputField
            v-model="form.password"
            :label="$t('common.password')"
            type="password"
            :placeholder="$t('common.password_placeholder')"
            :error="errors.password?.[0]"
            required
          />

          <div v-if="serverError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
            {{ serverError }}
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-60 transition"
          >
            <svg v-if="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            {{ $t('common.login') }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import InputField from '@/Components/InputField.vue'
import { useApi } from '@/composables/useApi'

const { errors, loading, post } = useApi()
const serverError = ref(null)

const form = ref({ email: '', password: '' })

async function submit() {
    serverError.value = null
    const { data, error } = await post('/api/auth/login', form.value)

    if (error) {
        serverError.value = error
        return
    }

    axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`
    localStorage.setItem('token', data.token)
    router.visit('/dashboard')
}
</script>
