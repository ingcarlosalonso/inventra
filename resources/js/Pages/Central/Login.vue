<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-950 px-4">
    <div class="w-full max-w-sm">
      <!-- Logo -->
      <div class="mb-8 flex flex-col items-center gap-2">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600">
          <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
          </svg>
        </div>
        <div class="text-center">
          <h1 class="text-lg font-bold text-white">In-ventra</h1>
          <p class="text-xs text-gray-500">{{ $t('central.title') }}</p>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="space-y-4">
        <div v-if="form.errors.email" class="rounded-lg bg-red-900/40 border border-red-700/50 px-4 py-3 text-sm text-red-300">
          {{ form.errors.email }}
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-medium text-gray-400">{{ $t('central.email') }}</label>
          <input
            v-model="form.email"
            type="email"
            autocomplete="email"
            required
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
          />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-medium text-gray-400">{{ $t('central.password') }}</label>
          <input
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            required
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
          />
        </div>

        <div class="flex items-center gap-2">
          <input
            id="remember"
            v-model="form.remember"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-600 bg-gray-800 text-indigo-600 focus:ring-indigo-500"
          />
          <label for="remember" class="text-xs text-gray-400">{{ $t('central.remember_me') }}</label>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-60"
        >
          {{ form.processing ? '...' : $t('central.login') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post(route('central.login.post'), {
    onFinish: () => form.reset('password'),
  })
}
</script>
