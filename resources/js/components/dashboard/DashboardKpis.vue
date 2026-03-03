<template>
  <!-- Loading skeleton -->
  <div v-if="loading" class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6 md:gap-6">
    <div
      v-for="n in 6" :key="n"
      class="animate-pulse rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"
    >
      <div class="h-10 w-10 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
      <div class="mt-4 h-3 w-20 rounded bg-gray-200 dark:bg-gray-700"></div>
      <div class="mt-2 h-6 w-28 rounded bg-gray-200 dark:bg-gray-700"></div>
    </div>
  </div>

  <!-- KPI cards grid -->
  <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6 md:gap-5">

    <!-- Total collected -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-success-50 dark:bg-success-500/10">
        <svg class="h-5 w-5 text-success-600 dark:text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div class="mt-4">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Collected</p>
        <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90 leading-tight">{{ fmtMoney(kpis.total_collected) }}</p>
        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ schoolYear }}</p>
      </div>
    </div>

    <!-- Total pending -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-warning-50 dark:bg-warning-500/10">
        <svg class="h-5 w-5 text-warning-600 dark:text-warning-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div class="mt-4">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pending</p>
        <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90 leading-tight">{{ fmtMoney(kpis.total_pending) }}</p>
        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">In progress</p>
      </div>
    </div>

    <!-- Total unpaid -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-error-50 dark:bg-error-500/10">
        <svg class="h-5 w-5 text-error-600 dark:text-error-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
      </div>
      <div class="mt-4">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Outstanding</p>
        <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90 leading-tight">{{ fmtMoney(kpis.total_unpaid) }}</p>
        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Not yet paid</p>
      </div>
    </div>

    <!-- Students count -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-500/10">
        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
      </div>
      <div class="mt-4">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Students</p>
        <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90 leading-tight">{{ fmtNum(kpis.students_count) }}</p>
        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Active</p>
      </div>
    </div>

    <!-- Annexes count — only super_admin_institution -->
    <div
      v-if="showAnnexes"
      class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"
    >
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 dark:bg-purple-500/10">
        <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
      </div>
      <div class="mt-4">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Branches</p>
        <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90 leading-tight">{{ kpis.annexes_count ?? '—' }}</p>
        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Active</p>
      </div>
    </div>

    <!-- Recovery rate -->
    <div
      :class="[
        'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]',
        !showAnnexes ? 'sm:col-span-1' : '',
      ]"
    >
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 dark:bg-teal-500/10">
        <svg class="h-5 w-5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
        </svg>
      </div>
      <div class="mt-4">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Recovery Rate</p>
        <p class="mt-1 text-lg font-bold leading-tight" :class="rateColorClass">
          {{ kpis.recovery_rate ?? 0 }}%
        </p>
        <!-- Mini progress bar -->
        <div class="mt-2 h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
          <div
            class="h-1.5 rounded-full transition-all duration-500"
            :class="rateBarClass"
            :style="{ width: clamp(kpis.recovery_rate ?? 0) + '%' }"
          ></div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  kpis:        { type: Object,  default: () => ({}) },
  schoolYear:  { type: String,  default: '' },
  loading:     { type: Boolean, default: false },
  showAnnexes: { type: Boolean, default: true },
})

const fmtMoney = (v) => {
  if (v == null) return '—'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency', currency: 'XAF', maximumFractionDigits: 0,
  }).format(v)
}

const fmtNum = (v) => {
  if (v == null) return '—'
  return new Intl.NumberFormat('fr-FR').format(v)
}

const clamp = (v) => Math.min(100, Math.max(0, v))

const rateColorClass = computed(() => {
  const r = props.kpis.recovery_rate ?? 0
  if (r >= 80) return 'text-success-600 dark:text-success-400'
  if (r >= 50) return 'text-warning-600 dark:text-warning-400'
  return 'text-error-600 dark:text-error-400'
})

const rateBarClass = computed(() => {
  const r = props.kpis.recovery_rate ?? 0
  if (r >= 80) return 'bg-success-500'
  if (r >= 50) return 'bg-warning-500'
  return 'bg-error-500'
})
</script>
