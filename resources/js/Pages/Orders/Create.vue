<template>
  <div class="max-w-6xl mx-auto flex flex-col" style="min-height: calc(100vh - 4rem);">

    <!-- Page header + stepper -->
    <div class="space-y-5 pb-6">
      <div class="flex items-center gap-4">
        <Link href="/orders" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
        </Link>
        <div>
          <h1 class="text-xl font-semibold text-gray-900">{{ $t('orders.create') }}</h1>
          <p class="mt-0.5 text-sm text-gray-500">{{ $t('orders.create_subtitle') }}</p>
        </div>
      </div>

      <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 px-6 py-4">
        <StepperIndicator :steps="stepLabels" :current="step" @go="goTo" />
      </div>
    </div>

    <!-- Step content -->
    <div class="flex-1">
      <Transition name="step" mode="out-in">

        <!-- Step 0: Info general -->
        <div v-if="step === 0" key="info" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
          <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('orders.header_info') }}</h2>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <SelectField
              v-model="form.client_id"
              :label="$t('orders.client')"
              :options="clientOptions"
              :placeholder="$t('common.none')"
              :error="formErrors.client_id?.[0]"
            />
            <SelectField
              v-model="form.courier_id"
              :label="$t('orders.courier')"
              :options="courierOptions"
              :placeholder="$t('common.none')"
              :error="formErrors.courier_id?.[0]"
            />
            <SelectField
              v-model="form.order_state_id"
              :label="$t('orders.order_state')"
              :options="stateOptions"
              :placeholder="$t('common.default')"
              :error="formErrors.order_state_id?.[0]"
            />
            <InputField
              v-model="form.address"
              :label="$t('orders.address')"
              :error="formErrors.address?.[0]"
            />
            <TextareaField
              v-model="form.notes"
              :label="$t('orders.notes')"
              :rows="1"
            />
          </div>
        </div>

        <!-- Step 1: Productos -->
        <div v-else-if="step === 1" key="items" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-700">{{ $t('orders.items_section') }}</h2>
            <span class="text-xs text-gray-400">{{ form.items.length }} {{ $t('orders.items_added') }}</span>
          </div>

          <!-- Product search -->
          <div class="px-5 py-4 border-b border-gray-100">
            <div class="relative" ref="searchContainer">
              <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ $t('orders.search_product') }}</label>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                  </svg>
                </div>
                <input
                  v-model="productSearch"
                  type="text"
                  :placeholder="$t('orders.search_product_placeholder')"
                  class="block w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm text-gray-900 shadow-sm placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  @focus="searchOpen = true"
                  @keydown.escape="searchOpen = false"
                  @keydown.down.prevent="highlightNext"
                  @keydown.up.prevent="highlightPrev"
                  @keydown.enter.prevent="selectHighlighted"
                />
              </div>
              <div
                v-if="searchOpen && filteredOptions.length > 0"
                class="absolute z-20 mt-1 w-full rounded-xl bg-white shadow-lg ring-1 ring-gray-200 overflow-hidden"
              >
                <ul class="max-h-64 overflow-y-auto divide-y divide-gray-100 py-1">
                  <li
                    v-for="(opt, i) in filteredOptions"
                    :key="opt.id"
                    :class="['flex items-center justify-between px-4 py-2.5 cursor-pointer text-sm transition', i === highlighted ? 'bg-indigo-50 text-indigo-900' : 'text-gray-700 hover:bg-gray-50']"
                    @mousedown.prevent="addItem(opt)"
                    @mouseover="highlighted = i"
                  >
                    <div>
                      <span class="font-medium">{{ opt.productName }}</span>
                      <span class="ml-2 text-xs text-gray-500">{{ opt.presentationDisplay }}</span>
                    </div>
                    <div class="ml-4 text-right shrink-0">
                      <p class="text-xs font-medium text-gray-900">${{ formatNumber(opt.price) }}</p>
                      <p class="text-xs text-gray-400">{{ $t('orders.stock') }}: {{ opt.stock }}</p>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Items table -->
          <div v-if="form.items.length > 0" class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-100 bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                  <th class="px-5 py-3 w-8">#</th>
                  <th class="px-3 py-3">{{ $t('orders.product_presentation') }}</th>
                  <th class="px-3 py-3 w-52">{{ $t('orders.description') }}</th>
                  <th class="px-3 py-3 w-28">{{ $t('orders.quantity') }}</th>
                  <th class="px-3 py-3 w-32">{{ $t('orders.unit_price') }}</th>
                  <th class="px-3 py-3 w-40">{{ $t('orders.discount') }}</th>
                  <th class="px-3 py-3 w-28 text-right">{{ $t('orders.item_total') }}</th>
                  <th class="px-3 py-3 w-10"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(item, index) in form.items" :key="item._key" class="group hover:bg-gray-50 transition">
                  <td class="px-5 py-3 text-xs text-gray-400 tabular-nums">{{ index + 1 }}</td>
                  <td class="px-3 py-3">
                    <p class="font-medium text-gray-900">{{ item.productName }}</p>
                    <p class="text-xs text-gray-400">{{ item.presentationDisplay }}</p>
                  </td>
                  <td class="px-3 py-2">
                    <input v-model="item.description" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                  </td>
                  <td class="px-3 py-2">
                    <input v-model="item.quantity" type="number" step="0.001" min="0.001" class="block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-right tabular-nums focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                  </td>
                  <td class="px-3 py-2">
                    <div class="relative">
                      <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-400">$</span>
                      <input v-model="item.unit_price" type="number" step="0.01" min="0" class="block w-full rounded-lg border border-gray-300 py-1.5 pl-6 pr-3 text-sm text-right tabular-nums focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                    </div>
                  </td>
                  <td class="px-3 py-2">
                    <div class="flex items-center gap-1">
                      <select v-model="item.discount_type" class="rounded-lg border border-gray-300 py-1.5 pl-2 pr-6 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">—</option>
                        <option value="percentage">%</option>
                        <option value="fixed">$</option>
                      </select>
                      <input v-if="item.discount_type" v-model="item.discount_value" type="number" step="0.01" min="0" class="block w-16 rounded-lg border border-gray-300 px-2 py-1.5 text-xs text-right tabular-nums focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                    </div>
                  </td>
                  <td class="px-3 py-3 text-right font-medium text-gray-900 tabular-nums">${{ formatNumber(computeItemTotal(item)) }}</td>
                  <td class="px-3 py-3">
                    <button type="button" class="rounded-md p-1 text-gray-300 hover:bg-gray-100 hover:text-red-500 opacity-0 group-hover:opacity-100 transition" @click="removeItem(index)">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="px-5 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p class="mt-2 text-sm text-gray-400">{{ $t('orders.no_items') }}</p>
          </div>

          <!-- Subtotal / discount / total -->
          <div v-if="form.items.length > 0" class="border-t border-gray-200 px-5 py-4 bg-gray-50 rounded-b-xl">
            <div class="flex items-end justify-between gap-6">
              <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500">{{ $t('orders.discount') }}</span>
                <select v-model="form.discount_type" class="rounded-lg border border-gray-300 py-1.5 pl-2 pr-6 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                  <option value="">—</option>
                  <option value="percentage">{{ $t('orders.percentage') }}</option>
                  <option value="fixed">{{ $t('orders.fixed') }}</option>
                </select>
                <input v-if="form.discount_type" v-model="form.discount_value" type="number" step="0.01" min="0" class="w-24 rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-right tabular-nums focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
              </div>
              <div class="text-right space-y-1">
                <div class="flex items-center justify-end gap-6 text-sm text-gray-500">
                  <span>{{ $t('orders.subtotal') }}</span>
                  <span class="w-28 tabular-nums">${{ formatNumber(subtotal) }}</span>
                </div>
                <div v-if="orderDiscountAmount > 0" class="flex items-center justify-end gap-6 text-sm text-red-600">
                  <span>{{ $t('orders.discount_amount') }}</span>
                  <span class="w-28 tabular-nums">-${{ formatNumber(orderDiscountAmount) }}</span>
                </div>
                <div class="flex items-center justify-end gap-6">
                  <span class="text-sm text-gray-500">{{ $t('orders.total') }}</span>
                  <span class="w-28 text-2xl font-bold text-gray-900 tabular-nums">${{ formatNumber(formTotal) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Envío & Pagos -->
        <div v-else-if="step === 2" key="shipping" class="space-y-5">

          <!-- Delivery section -->
          <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('orders.delivery_section') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <div class="flex items-center gap-3">
                <ToggleSwitch v-model="form.requires_delivery" :label="$t('orders.requires_delivery')" />
              </div>
              <InputField
                v-if="form.requires_delivery"
                v-model="form.delivery_date"
                type="date"
                :label="$t('orders.delivery_date')"
                :error="formErrors.delivery_date?.[0]"
              />
              <InputField
                v-model="form.scheduled_at"
                type="datetime-local"
                :label="$t('orders.scheduled_at')"
                :error="formErrors.scheduled_at?.[0]"
              />
            </div>
          </div>

          <!-- Payments section -->
          <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
              <h2 class="text-sm font-semibold text-gray-700">{{ $t('orders.payments_section') }}</h2>
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition"
                @click="addPayment"
              >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ $t('orders.add_payment') }}
              </button>
            </div>

            <div v-if="form.payments.length > 0" class="divide-y divide-gray-100">
              <div
                v-for="(payment, index) in form.payments"
                :key="payment._key"
                class="group flex items-center gap-4 px-5 py-3.5"
              >
                <SelectField
                  v-model="payment.payment_method_id"
                  :label="index === 0 ? $t('orders.payment_method') : ''"
                  :options="paymentMethodOptions"
                  :placeholder="$t('common.select')"
                  class="w-56"
                />
                <div class="flex-1">
                  <label v-if="index === 0" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $t('orders.amount') }}</label>
                  <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-400">$</span>
                    <input
                      v-model="payment.amount"
                      type="number"
                      step="0.01"
                      min="0"
                      class="block w-full rounded-lg border border-gray-300 py-2 pl-7 pr-3 text-sm text-right tabular-nums focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                  </div>
                </div>
                <button
                  type="button"
                  :class="['rounded-md p-1.5 text-gray-300 hover:bg-gray-100 hover:text-red-500 opacity-0 group-hover:opacity-100 transition', index === 0 ? 'mt-5' : '']"
                  @click="removePayment(index)"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>
            </div>

            <div v-else class="px-5 py-10 text-center">
              <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
              </svg>
              <p class="mt-2 text-sm text-gray-400">{{ $t('orders.no_payments') }}</p>
              <button
                type="button"
                class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition"
                @click="addPayment"
              >
                {{ $t('orders.add_payment') }}
              </button>
            </div>

            <div v-if="form.payments.length > 0 && paymentsTotal > 0" class="border-t border-gray-200 px-5 py-4 bg-gray-50 rounded-b-xl">
              <div class="flex items-center justify-end gap-6">
                <div class="text-right">
                  <p class="text-xs text-gray-400 mb-0.5">{{ $t('orders.total') }}</p>
                  <p class="text-base font-semibold text-gray-700 tabular-nums">${{ formatNumber(formTotal) }}</p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-gray-400 mb-0.5">{{ $t('orders.payment_paid') }}</p>
                  <p class="text-base font-semibold tabular-nums" :class="paymentDiff === 0 ? 'text-emerald-600' : paymentDiff > 0 ? 'text-blue-600' : 'text-gray-900'">
                    ${{ formatNumber(paymentsTotal) }}
                  </p>
                </div>
                <div v-if="paymentDiff < 0" class="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 ring-1 ring-amber-200">
                  <svg class="h-4 w-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                  </svg>
                  <div>
                    <p class="text-xs text-amber-600">{{ $t('orders.payment_remaining') }}</p>
                    <p class="text-sm font-bold text-amber-700 tabular-nums">${{ formatNumber(-paymentDiff) }}</p>
                  </div>
                </div>
                <div v-else-if="paymentDiff === 0" class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 ring-1 ring-emerald-200">
                  <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <p class="text-sm font-bold text-emerald-700">{{ $t('orders.payment_exact') }}</p>
                </div>
                <div v-else class="flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 ring-1 ring-blue-200">
                  <svg class="h-4 w-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                  </svg>
                  <div>
                    <p class="text-xs text-blue-600">{{ $t('orders.payment_change') }}</p>
                    <p class="text-sm font-bold text-blue-700 tabular-nums">${{ formatNumber(paymentDiff) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

      </Transition>
    </div>

    <!-- Sticky bottom nav -->
    <div class="sticky bottom-0 mt-6 rounded-xl bg-white shadow-lg ring-1 ring-gray-200 px-5 py-3.5 flex items-center justify-between gap-4">
      <!-- Total pill -->
      <div class="flex items-center gap-2 min-w-0">
        <span v-if="formTotal > 0" class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200 tabular-nums">
          ${{ formatNumber(formTotal) }}
        </span>
        <span v-if="stepError" class="text-sm text-red-600">{{ stepError }}</span>
        <span v-if="formError" class="text-sm text-red-600">{{ formError }}</span>
      </div>

      <!-- Nav buttons -->
      <div class="flex items-center gap-2 shrink-0">
        <Link href="/orders" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
          {{ $t('common.cancel') }}
        </Link>
        <button
          v-if="step > 0"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 transition"
          @click="prev"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
          {{ $t('common.previous') }}
        </button>
        <button
          v-if="step < stepLabels.length - 1"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition"
          @click="next"
        >
          {{ $t('common.next') }}
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
          </svg>
        </button>
        <button
          v-else
          type="button"
          :disabled="saving || form.items.length === 0"
          class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          @click="save"
        >
          <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          {{ $t('orders.save_order') }}
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useTranslation } from '@/composables/useTranslation'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputField from '@/Components/InputField.vue'
import TextareaField from '@/Components/TextareaField.vue'
import SelectField from '@/Components/SelectField.vue'
import ToggleSwitch from '@/Components/ToggleSwitch.vue'
import StepperIndicator from '@/Components/StepperIndicator.vue'
import { useApi } from '@/composables/useApi'

defineOptions({ layout: AppLayout })

const { t } = useTranslation()
const { loading: saving, errors: formErrors, post: postForm } = useApi()
const { get } = useApi()

// ── Stepper ────────────────────────────────────────────────────────────────
const step = ref(0)
const stepError = ref(null)
const stepLabels = computed(() => [
  t('orders.header_info'),
  t('orders.items_section'),
  t('orders.shipping_and_payments'),
])

function next() {
  stepError.value = null
  if (step.value === 1 && form.value.items.length === 0) {
    stepError.value = t('orders.no_items')
    return
  }
  step.value++
}

function prev() { step.value-- }
function goTo(i) { step.value = i }

// ── Form state ─────────────────────────────────────────────────────────────
const clients = ref([])
const couriers = ref([])
const orderStates = ref([])
const paymentMethods = ref([])
const allProductPresentations = ref([])
const formError = ref(null)
let itemKeyCounter = 0
let paymentKeyCounter = 0

const form = ref({
  client_id: null,
  courier_id: null,
  order_state_id: null,
  address: '',
  notes: '',
  requires_delivery: false,
  delivery_date: '',
  scheduled_at: '',
  discount_type: '',
  discount_value: '',
  items: [],
  payments: [],
})

const clientOptions = computed(() => clients.value.map(c => ({ value: c.id, label: c.name })))
const courierOptions = computed(() => couriers.value.filter(c => c.is_active).map(c => ({ value: c.id, label: c.name })))
const stateOptions = computed(() => orderStates.value.map(s => ({ value: s.id, label: s.name })))
const paymentMethodOptions = computed(() => paymentMethods.value.map(m => ({ value: m.id, label: m.name })))

// ── Product search ─────────────────────────────────────────────────────────
const productSearch = ref('')
const searchOpen = ref(false)
const highlighted = ref(0)
const searchContainer = ref(null)

const alreadyAddedIds = computed(() => new Set(form.value.items.map(i => i.product_presentation_id)))
const filteredOptions = computed(() => {
  const q = productSearch.value.trim().toLowerCase()
  return allProductPresentations.value
    .filter(pp => !alreadyAddedIds.value.has(pp.id))
    .filter(pp => !q || pp.productName.toLowerCase().includes(q) || pp.presentationDisplay.toLowerCase().includes(q))
    .slice(0, 30)
})

function highlightNext() { highlighted.value = Math.min(highlighted.value + 1, filteredOptions.value.length - 1) }
function highlightPrev() { highlighted.value = Math.max(highlighted.value - 1, 0) }
function selectHighlighted() { if (filteredOptions.value[highlighted.value]) addItem(filteredOptions.value[highlighted.value]) }

function addItem(opt) {
  form.value.items.push({
    _key: ++itemKeyCounter,
    product_presentation_id: opt.id,
    productName: opt.productName,
    presentationDisplay: opt.presentationDisplay,
    description: opt.productName + (opt.presentationDisplay ? ' - ' + opt.presentationDisplay : ''),
    quantity: 1,
    unit_price: opt.price,
    discount_type: '',
    discount_value: '',
  })
  productSearch.value = ''
  searchOpen.value = false
  highlighted.value = 0
}

function removeItem(index) { form.value.items.splice(index, 1) }

// ── Payments ──────────────────────────────────────────────────────────────
function addPayment() {
  form.value.payments.push({ _key: ++paymentKeyCounter, payment_method_id: null, amount: '' })
}
function removePayment(index) { form.value.payments.splice(index, 1) }

// ── Totals ─────────────────────────────────────────────────────────────────
function computeItemTotal(item) {
  const qty = parseFloat(item.quantity) || 0
  const price = parseFloat(item.unit_price) || 0
  let lineTotal = qty * price
  const discVal = parseFloat(item.discount_value) || 0
  if (item.discount_type === 'percentage' && discVal > 0) lineTotal = lineTotal * (1 - discVal / 100)
  else if (item.discount_type === 'fixed' && discVal > 0) lineTotal = Math.max(lineTotal - discVal, 0)
  return lineTotal
}

const subtotal = computed(() => form.value.items.reduce((sum, it) => sum + computeItemTotal(it), 0))
const orderDiscountAmount = computed(() => {
  const discVal = parseFloat(form.value.discount_value) || 0
  if (form.value.discount_type === 'percentage' && discVal > 0) return subtotal.value * discVal / 100
  if (form.value.discount_type === 'fixed' && discVal > 0) return Math.min(discVal, subtotal.value)
  return 0
})
const formTotal = computed(() => Math.max(subtotal.value - orderDiscountAmount.value, 0))
const paymentsTotal = computed(() => form.value.payments.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0))
const paymentDiff = computed(() => Math.round((paymentsTotal.value - formTotal.value) * 100) / 100)

// ── Save ───────────────────────────────────────────────────────────────────
async function save() {
  formError.value = null
  const payload = {
    client_id: form.value.client_id || null,
    courier_id: form.value.courier_id || null,
    order_state_id: form.value.order_state_id || null,
    address: form.value.address || null,
    notes: form.value.notes || null,
    requires_delivery: form.value.requires_delivery,
    delivery_date: form.value.delivery_date || null,
    scheduled_at: form.value.scheduled_at || null,
    discount_type: form.value.discount_type || null,
    discount_value: form.value.discount_value || null,
    items: form.value.items.map(it => ({
      product_presentation_id: it.product_presentation_id,
      description: it.description,
      quantity: it.quantity,
      unit_price: it.unit_price,
      discount_type: it.discount_type || null,
      discount_value: it.discount_value || null,
    })),
    payments: form.value.payments
      .filter(p => p.payment_method_id && p.amount)
      .map(p => ({ payment_method_id: p.payment_method_id, amount: p.amount })),
  }
  const result = await postForm('/api/orders', payload)
  if (result.error) {
    if (!Object.keys(formErrors.value).length) formError.value = result.error
    return
  }
  router.visit('/orders')
}

// ── Helpers ────────────────────────────────────────────────────────────────
function formatNumber(value) {
  const num = parseFloat(value) || 0
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function handleClickOutside(e) {
  if (searchContainer.value && !searchContainer.value.contains(e.target)) searchOpen.value = false
}

// ── Init ───────────────────────────────────────────────────────────────────
async function fetchOptions() {
  const [clientsRes, couriersRes, statesRes, pmRes, productsRes] = await Promise.all([
    get('/api/clients'), get('/api/couriers'), get('/api/order-states'),
    get('/api/payment-methods'), get('/api/products?per_page=500'),
  ])
  if (clientsRes.data) clients.value = clientsRes.data.data ?? clientsRes.data
  if (couriersRes.data) couriers.value = couriersRes.data.data ?? couriersRes.data
  if (statesRes.data) orderStates.value = statesRes.data.data ?? statesRes.data
  if (pmRes.data) paymentMethods.value = pmRes.data.data ?? pmRes.data

  const products = productsRes.data?.data ?? []
  allProductPresentations.value = products.flatMap(product =>
    (product.presentations ?? [])
      .filter(pp => pp.is_active)
      .map(pp => ({
        id: pp.id,
        productName: product.name,
        presentationDisplay: pp.presentation?.display ?? '',
        price: pp.price ?? 0,
        stock: pp.stock ?? 0,
      }))
  )

  const defaultState = orderStates.value.find(s => s.is_default)
  if (defaultState && !form.value.order_state_id) form.value.order_state_id = defaultState.id
}

onMounted(() => { fetchOptions(); document.addEventListener('mousedown', handleClickOutside) })
onBeforeUnmount(() => { document.removeEventListener('mousedown', handleClickOutside) })
</script>

<style scoped>
.step-enter-active, .step-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.step-enter-from { opacity: 0; transform: translateX(12px); }
.step-leave-to { opacity: 0; transform: translateX(-12px); }
</style>
