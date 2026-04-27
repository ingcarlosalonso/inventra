<template>
  <CentralLayout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-white">{{ $t('central.tenants') }}</h1>
          <p class="mt-0.5 text-sm text-gray-400">{{ tenants.length }} {{ $t('central.tenants').toLowerCase() }}</p>
        </div>
        <button
          @click="openCreate"
          class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          {{ $t('central.new_tenant') }}
        </button>
      </div>

      <!-- Filters -->
      <div class="mb-5 flex flex-wrap gap-3">
        <input
          v-model="search"
          type="text"
          :placeholder="$t('central.search_placeholder')"
          class="w-64 rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
        />
        <select
          v-model="filterStatus"
          class="rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none focus:border-indigo-500"
        >
          <option value="">{{ $t('central.all_statuses') }}</option>
          <option value="trial">{{ $t('central.statuses.trial') }}</option>
          <option value="active">{{ $t('central.statuses.active') }}</option>
          <option value="suspended">{{ $t('central.statuses.suspended') }}</option>
        </select>
      </div>

      <!-- Flash -->
      <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg bg-green-900/40 border border-green-700/50 px-4 py-3 text-sm text-green-300">
        {{ $page.props.flash.success }}
      </div>

      <!-- Table -->
      <div class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-800 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
              <th class="px-5 py-3.5">{{ $t('central.name') }}</th>
              <th class="px-5 py-3.5">{{ $t('central.domain') }}</th>
              <th class="px-5 py-3.5">{{ $t('central.email_label') }}</th>
              <th class="px-5 py-3.5">{{ $t('central.status') }}</th>
              <th class="px-5 py-3.5">{{ $t('central.plan') }}</th>
              <th class="px-5 py-3.5">{{ $t('central.expires_at') }}</th>
              <th class="px-5 py-3.5">{{ $t('central.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-800">
            <tr v-if="filtered.length === 0">
              <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">
                {{ $t('central.empty_state') }}
              </td>
            </tr>
            <tr
              v-for="tenant in filtered"
              :key="tenant.id"
              class="transition hover:bg-gray-800/50"
            >
              <td class="px-5 py-4">
                <div class="font-medium text-white">{{ tenant.name }}</div>
                <div class="text-xs text-gray-500">{{ tenant.contact_name }}</div>
              </td>
              <td class="px-5 py-4 text-gray-300 font-mono text-xs">{{ tenant.domain }}</td>
              <td class="px-5 py-4 text-gray-400">{{ tenant.email || '—' }}</td>
              <td class="px-5 py-4">
                <span :class="statusClass(tenant.status)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                  {{ $t('central.statuses.' + tenant.status) }}
                </span>
              </td>
              <td class="px-5 py-4 text-gray-400">{{ tenant.plan || '—' }}</td>
              <td class="px-5 py-4 text-gray-400">{{ tenant.expires_at || '—' }}</td>
              <td class="px-5 py-4">
                <div class="flex items-center gap-2">
                  <button
                    @click="openEdit(tenant)"
                    class="rounded px-2.5 py-1 text-xs text-gray-400 transition hover:bg-gray-700 hover:text-white"
                  >
                    {{ $t('central.edit') }}
                  </button>
                  <button
                    v-if="tenant.status !== 'suspended'"
                    @click="suspendTenant(tenant)"
                    class="rounded px-2.5 py-1 text-xs text-red-400 transition hover:bg-red-900/40 hover:text-red-300"
                  >
                    {{ $t('central.suspend') }}
                  </button>
                  <button
                    v-else
                    @click="activateTenant(tenant)"
                    class="rounded px-2.5 py-1 text-xs text-green-400 transition hover:bg-green-900/40 hover:text-green-300"
                  >
                    {{ $t('central.activate') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Slide-over create/edit -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="slideOver" class="fixed inset-0 z-50 flex justify-end">
        <div class="fixed inset-0 bg-gray-950/70" @click="closeSlideOver" />
        <div class="relative z-10 flex w-full max-w-md flex-col bg-gray-900 shadow-2xl">
          <div class="flex items-center justify-between border-b border-gray-800 px-6 py-4">
            <h2 class="text-base font-semibold text-white">
              {{ editing ? $t('central.edit_tenant') : $t('central.new_tenant') }}
            </h2>
            <button @click="closeSlideOver" class="text-gray-400 hover:text-white">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 px-6 py-5">
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-400">{{ $t('central.name') }} *</label>
                <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
              </div>

              <div v-if="!editing">
                <label class="mb-1 block text-xs font-medium text-gray-400">{{ $t('central.subdomain') }} *</label>
                <div class="flex items-center gap-1">
                  <span class="text-xs text-gray-500">development.</span>
                  <input v-model="form.subdomain" type="text" required placeholder="mi-cliente" class="field flex-1" />
                  <span class="text-xs text-gray-500">.com</span>
                </div>
                <p v-if="form.errors.subdomain" class="mt-1 text-xs text-red-400">{{ form.errors.subdomain }}</p>
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium text-gray-400">{{ $t('central.email_label') }}</label>
                <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-400">{{ form.errors.email }}</p>
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium text-gray-400">{{ $t('central.contact_name') }}</label>
                <input v-model="form.contact_name" type="text" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium text-gray-400">{{ $t('central.status') }} *</label>
                <select v-model="form.status" required class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                  <option value="trial">{{ $t('central.statuses.trial') }}</option>
                  <option value="active">{{ $t('central.statuses.active') }}</option>
                  <option value="suspended">{{ $t('central.statuses.suspended') }}</option>
                </select>
                <p v-if="form.errors.status" class="mt-1 text-xs text-red-400">{{ form.errors.status }}</p>
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium text-gray-400">{{ $t('central.plan') }}</label>
                <input v-model="form.plan" type="text" placeholder="básico, profesional..." class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium text-gray-400">{{ $t('central.expires_at') }}</label>
                <input v-model="form.expires_at" type="date" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium text-gray-400">{{ $t('central.notes') }}</label>
                <textarea v-model="form.notes" rows="3" class="field resize-none" />
              </div>
            </div>

            <div class="flex gap-3 border-t border-gray-800 px-6 py-4">
              <button type="button" @click="closeSlideOver" class="flex-1 rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-400 transition hover:border-gray-600 hover:text-white">
                {{ $t('central.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="flex-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-60"
              >
                {{ form.processing ? (editing ? '...' : $t('central.provisioning')) : (editing ? $t('central.save') : $t('central.create')) }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </CentralLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import CentralLayout from '@/Layouts/CentralLayout.vue'

const props = defineProps({
  tenants: Array,
})

const search = ref('')
const filterStatus = ref('')
const slideOver = ref(false)
const editing = ref(null)

const filtered = computed(() => {
  return props.tenants.filter((t) => {
    const matchSearch =
      !search.value ||
      t.name.toLowerCase().includes(search.value.toLowerCase()) ||
      (t.email && t.email.toLowerCase().includes(search.value.toLowerCase()))
    const matchStatus = !filterStatus.value || t.status === filterStatus.value
    return matchSearch && matchStatus
  })
})

function statusClass(status) {
  return {
    trial: 'bg-yellow-900/50 text-yellow-300',
    active: 'bg-green-900/50 text-green-300',
    suspended: 'bg-red-900/50 text-red-300',
  }[status]
}

const form = useForm({
  name: '',
  subdomain: '',
  email: '',
  contact_name: '',
  status: 'trial',
  plan: '',
  expires_at: '',
  notes: '',
})

function openCreate() {
  editing.value = null
  form.reset()
  form.status = 'trial'
  slideOver.value = true
}

function openEdit(tenant) {
  editing.value = tenant
  form.name = tenant.name
  form.email = tenant.email || ''
  form.contact_name = tenant.contact_name || ''
  form.status = tenant.status
  form.plan = tenant.plan || ''
  form.expires_at = tenant.expires_at || ''
  form.notes = tenant.notes || ''
  slideOver.value = true
}

function closeSlideOver() {
  slideOver.value = false
  editing.value = null
}

function submitForm() {
  if (editing.value) {
    form.put(`/central-admin/tenants/${editing.value.id}`, {
      onSuccess: () => closeSlideOver(),
    })
  } else {
    form.post('/central-admin/tenants', {
      onSuccess: () => closeSlideOver(),
    })
  }
}

function suspendTenant(tenant) {
  if (!confirm(`¿Suspender acceso de "${tenant.name}"?`)) return
  router.post(`/central-admin/tenants/${tenant.id}/suspend`, {}, { preserveScroll: true })
}

function activateTenant(tenant) {
  if (!confirm(`¿Reactivar acceso de "${tenant.name}"?`)) return
  router.post(`/central-admin/tenants/${tenant.id}/activate`, {}, { preserveScroll: true })
}
</script>

