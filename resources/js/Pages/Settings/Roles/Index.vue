<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $t('roles.title') }}</h1>
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
        {{ $t('roles.create') }}
      </button>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
      <div v-if="loadingList" class="divide-y divide-gray-100">
        <div v-for="i in 3" :key="i" class="flex items-center gap-4 px-5 py-4 animate-pulse">
          <div class="h-4 w-32 rounded bg-gray-200" />
          <div class="h-4 w-24 rounded bg-gray-200 ml-auto" />
        </div>
      </div>

      <EmptyState
        v-else-if="items.length === 0"
        :title="$t('common.empty')"
      >
        <button type="button" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition" @click="openCreate">
          {{ $t('roles.create') }}
        </button>
      </EmptyState>

      <ul v-else class="divide-y divide-gray-100">
        <li
          v-for="item in items"
          :key="item.id"
          class="group flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition"
        >
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-100">
            <svg class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900">{{ item.name }}</p>
          </div>

          <span class="text-xs text-gray-400">
            {{ item.permissions_count }}
            {{ item.permissions_count === 1 ? $t('roles.permissions').slice(0, -1) : $t('roles.permissions') }}
          </span>

          <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
            <button type="button" class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-indigo-600" :title="$t('common.edit')" @click="openEdit(item)">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
            </button>
            <button type="button" class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-600" :title="$t('common.delete')" @click="confirmDelete(item)">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
            </button>
          </div>
        </li>
      </ul>
    </div>
  </div>

  <SlideOver v-model="slideOverOpen" :title="editing ? $t('roles.edit') : $t('roles.create')" wide>
    <form class="space-y-6" @submit.prevent="save">
      <InputField v-model="form.name" :label="$t('roles.name')" :error="formErrors.name?.[0]" required />

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">{{ $t('roles.permissions') }}</label>
        <div v-if="loadingPermissions" class="space-y-2">
          <div v-for="i in 4" :key="i" class="h-4 w-3/4 rounded bg-gray-200 animate-pulse" />
        </div>
        <div v-else class="space-y-5">
          <div v-for="(perms, group) in groupedPermissions" :key="group">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ groupLabel(group) }}</p>
            <div class="grid grid-cols-1 gap-1.5">
              <label
                v-for="perm in perms"
                :key="perm.id"
                class="flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2 text-sm transition"
                :class="form.permissions.includes(perm.id) ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-gray-100 bg-gray-50 text-gray-700 hover:border-gray-300'"
              >
                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600" :value="perm.id" v-model="form.permissions" />
                <span>{{ perm.name }}</span>
              </label>
            </div>
          </div>
        </div>
        <p v-if="formErrors.permissions?.[0]" class="mt-1 text-xs text-red-600">{{ formErrors.permissions[0] }}</p>
      </div>

      <div v-if="formError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">{{ formError }}</div>
    </form>
    <template #footer>
      <div class="flex justify-end gap-3">
        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50" @click="slideOverOpen = false">{{ $t('common.cancel') }}</button>
        <button type="button" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition" @click="save">
          <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          {{ $t('common.save') }}
        </button>
      </div>
    </template>
  </SlideOver>

  <ConfirmModal v-model="confirmOpen" :title="$t('roles.delete_confirm', { name: deleteTarget?.name })" @confirm="doDelete" />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import SlideOver from '@/Components/SlideOver.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import EmptyState from '@/Components/EmptyState.vue'
import InputField from '@/Components/InputField.vue'
import { useApi } from '@/composables/useApi'
import { usePage } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

const { loading: loadingList, get } = useApi()
const { loading: loadingPermissions, get: getPerms } = useApi()
const { loading: saving, errors: formErrors, post: postForm, put: putForm } = useApi()
const { del } = useApi()

const page = usePage()
const locale = computed(() => page.props.locale ?? 'es')

const items = ref([])
const allPermissions = ref([])
const slideOverOpen = ref(false)
const confirmOpen = ref(false)
const editing = ref(null)
const deleteTarget = ref(null)
const formError = ref(null)

const emptyForm = () => ({ name: '', permissions: [] })
const form = ref(emptyForm())

const permissionGroupLabels = {
  users: { es: 'Usuarios', en: 'Users' },
  roles: { es: 'Roles', en: 'Roles' },
  clients: { es: 'Clientes', en: 'Clients' },
  suppliers: { es: 'Proveedores', en: 'Suppliers' },
  products: { es: 'Productos', en: 'Products' },
  receptions: { es: 'Recepciones', en: 'Receptions' },
  sales: { es: 'Ventas', en: 'Sales' },
  quotes: { es: 'Presupuestos', en: 'Quotes' },
  orders: { es: 'Pedidos', en: 'Orders' },
  daily_cashes: { es: 'Cajas', en: 'Daily Cashes' },
  reports: { es: 'Reportes', en: 'Reports' },
  currencies: { es: 'Monedas', en: 'Currencies' },
  other: { es: 'Otros', en: 'Other' },
}

function permissionGroup(name) {
  const known = ['users', 'roles', 'clients', 'suppliers', 'products', 'receptions', 'sales', 'quotes', 'orders', 'daily_cashes', 'reports', 'currencies']
  for (const group of known) {
    if (name.includes(group)) return group
  }
  return 'other'
}

function groupLabel(group) {
  const lang = locale.value === 'en' ? 'en' : 'es'
  return permissionGroupLabels[group]?.[lang] ?? group
}

const groupedPermissions = computed(() => {
  const groups = {}
  for (const perm of allPermissions.value) {
    const group = permissionGroup(perm.name)
    if (!groups[group]) groups[group] = []
    groups[group].push(perm)
  }
  return groups
})

async function fetchItems() {
  const { data } = await get('/api/roles')
  if (data) items.value = data.data
}

async function fetchPermissions() {
  const { data } = await getPerms('/api/permissions')
  if (data) allPermissions.value = data.data
}

function openCreate() {
  editing.value = null; form.value = emptyForm(); formError.value = null; slideOverOpen.value = true
}

async function openEdit(item) {
  editing.value = item
  const { data } = await get(`/api/roles/${item.id}`)
  const permissions = data?.data?.permissions?.map(p => p.id) ?? []
  form.value = { name: item.name, permissions }
  formError.value = null; slideOverOpen.value = true
}

async function save() {
  formError.value = null
  const result = editing.value
    ? await putForm(`/api/roles/${editing.value.id}`, form.value)
    : await postForm('/api/roles', form.value)
  if (result.error) { if (!Object.keys(formErrors.value).length) formError.value = result.error; return }
  slideOverOpen.value = false; await fetchItems()
}

function confirmDelete(item) { deleteTarget.value = item; confirmOpen.value = true }
async function doDelete() { confirmOpen.value = false; await del(`/api/roles/${deleteTarget.value.id}`); await fetchItems() }

onMounted(() => { fetchItems(); fetchPermissions() })
</script>
