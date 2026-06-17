<template>
  <div>
    <!-- Floating button -->
    <button
      v-if="!open"
      @click="open = true"
      class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-white shadow-lg transition hover:bg-indigo-700 focus:outline-none"
      :title="$t('assistant.title')"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.32 2.798H4.12c-1.35 0-2.32-1.798-1.32-2.798L4.2 15.3" />
      </svg>
    </button>

    <!-- Backdrop (mobile only) -->
    <div
      v-if="open"
      class="fixed inset-0 z-[60] bg-black/60 sm:hidden"
      @click="open = false"
    />

    <!-- Chat panel -->
    <div
      v-if="open"
      class="fixed inset-x-0 bottom-0 z-[70] flex flex-col rounded-t-2xl bg-white shadow-2xl sm:inset-auto sm:bottom-6 sm:right-6 sm:h-[560px] sm:w-96 sm:rounded-2xl sm:shadow-2xl sm:ring-1 sm:ring-gray-200"
      style="max-height: 88dvh;"
    >
      <!-- Drag handle (mobile) -->
      <div class="flex justify-center pt-3 pb-1 sm:hidden">
        <div class="h-1 w-10 rounded-full bg-gray-300" />
      </div>

      <!-- Header -->
      <div class="flex items-center justify-between bg-indigo-600 px-4 py-3 sm:rounded-t-2xl">
        <div class="flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.32 2.798H4.12c-1.35 0-2.32-1.798-1.32-2.798L4.2 15.3" />
            </svg>
          </div>
          <div>
            <p class="text-sm font-semibold text-white">{{ $t('assistant.title') }}</p>
            <p class="text-xs text-indigo-200">{{ $t('assistant.subtitle') }}</p>
          </div>
        </div>
        <button @click="open = false" class="flex h-8 w-8 items-center justify-center rounded-lg text-indigo-200 hover:bg-white/10 hover:text-white transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Messages -->
      <div ref="messagesContainer" class="flex-1 overflow-y-auto bg-gray-50 px-4 py-4 space-y-3">
        <!-- Welcome state -->
        <div v-if="messages.length === 0" class="flex flex-col gap-4 pt-4">
          <div class="flex items-center gap-3 rounded-2xl bg-white px-4 py-4 shadow-sm ring-1 ring-gray-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-800">{{ $t('assistant.welcome_title') }}</p>
              <p class="mt-0.5 text-xs text-gray-500">{{ $t('assistant.welcome_subtitle') }}</p>
            </div>
          </div>

          <p class="text-xs font-medium text-gray-400 px-1">{{ $t('assistant.suggestions_label') ?? 'Sugerencias' }}</p>

          <div class="flex flex-col gap-2">
            <button
              v-for="key in suggestionKeys"
              :key="key"
              @click="sendSuggestion($t(`assistant.${key}`))"
              class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 text-left text-sm text-gray-700 shadow-sm ring-1 ring-gray-100 hover:bg-indigo-50 hover:text-indigo-700 hover:ring-indigo-200 transition"
            >
              <svg class="h-4 w-4 shrink-0 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
              </svg>
              {{ $t(`assistant.${key}`) }}
            </button>
          </div>
        </div>

        <!-- Chat messages -->
        <template v-else>
          <div
            v-for="(msg, index) in messages"
            :key="index"
            class="flex"
            :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-[82%] rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed"
              :class="msg.role === 'user'
                ? 'bg-indigo-600 text-white rounded-br-sm'
                : 'bg-white text-gray-800 rounded-bl-sm shadow-sm ring-1 ring-gray-100'"
            >
              <span style="white-space: pre-wrap;">{{ msg.content }}</span>
            </div>
          </div>

          <!-- Typing indicator -->
          <div v-if="loading" class="flex justify-start">
            <div class="rounded-2xl rounded-bl-sm bg-white px-4 py-3 shadow-sm ring-1 ring-gray-100">
              <div class="flex gap-1.5">
                <span class="h-2 w-2 rounded-full bg-indigo-300 animate-bounce" style="animation-delay: 0ms"></span>
                <span class="h-2 w-2 rounded-full bg-indigo-300 animate-bounce" style="animation-delay: 150ms"></span>
                <span class="h-2 w-2 rounded-full bg-indigo-300 animate-bounce" style="animation-delay: 300ms"></span>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Input -->
      <div class="border-t border-gray-100 bg-white px-4 py-3" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
        <div v-if="error" class="mb-2 rounded-lg bg-red-50 px-3 py-1.5 text-xs text-red-600">{{ error }}</div>
        <form @submit.prevent="sendMessage" class="flex items-end gap-2">
          <textarea
            ref="inputRef"
            v-model="input"
            @keydown.enter.exact.prevent="sendMessage"
            :placeholder="$t('assistant.placeholder')"
            :disabled="loading"
            rows="1"
            class="flex-1 resize-none rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-400 disabled:opacity-50"
            style="max-height: 96px; overflow-y: auto;"
            @input="autoResize"
          ></textarea>
          <button
            type="submit"
            :disabled="loading || !input.trim()"
            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white transition hover:bg-indigo-700 disabled:opacity-40"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </form>
        <p class="mt-1.5 text-center text-[10px] text-gray-400">{{ $t('assistant.powered_by') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import axios from 'axios'

const open = ref(false)
const input = ref('')
const messages = ref([])
const loading = ref(false)
const error = ref(null)
const messagesContainer = ref(null)
const inputRef = ref(null)

const suggestionKeys = [
  'suggestion_low_stock',
  'suggestion_sales_today',
  'suggestion_recent_orders',
  'suggestion_cash_open',
]

function autoResize(e) {
  e.target.style.height = 'auto'
  e.target.style.height = Math.min(e.target.scrollHeight, 96) + 'px'
}

async function scrollToBottom() {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

function sendSuggestion(text) {
  input.value = text
  sendMessage()
}

async function sendMessage() {
  const text = input.value.trim()
  if (!text || loading.value) return

  error.value = null
  messages.value.push({ role: 'user', content: text })
  input.value = ''

  if (inputRef.value) {
    inputRef.value.style.height = 'auto'
  }

  await scrollToBottom()

  loading.value = true

  try {
    const { data } = await axios.post('/api/v1/assistant/chat', { messages: messages.value })
    messages.value.push({ role: 'assistant', content: data.message })
  } catch (err) {
    const status = err.response?.status
    const msg = err.response?.data?.message
    error.value = `HTTP ${status}: ${msg || err.message}`
    messages.value.pop()
  } finally {
    loading.value = false
    await scrollToBottom()
  }
}

</script>
