<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <!-- Header -->
    <div class="flex flex-col gap-1 mb-5 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Monthly Collections</h3>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Collected vs. pending — {{ schoolYear }}</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
          <span class="inline-block h-2 w-2 rounded-full bg-brand-500"></span>
          Collected
        </span>
        <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
          <span class="inline-block h-2 w-2 rounded-full bg-blue-300"></span>
          Pending
        </span>
      </div>
    </div>

    <!-- Spinner -->
    <div v-if="loading" class="flex h-[280px] items-center justify-center">
      <svg class="h-6 w-6 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
      </svg>
    </div>

    <!-- Chart -->
    <LineChartOne
      v-else
      :series="series"
      :categories="categories"
      :colors="['#465FFF', '#9CB9FF']"
      :height="280"
      :y-formatter="yFormatter"
    />
  </div>
</template>

<script setup>
import LineChartOne from '@/components/charts/LineChart/LineChartOne.vue'

const props = defineProps({
  series:     { type: Array,   default: () => [] },
  categories: { type: Array,   default: () => [] },
  schoolYear: { type: String,  default: '' },
  loading:    { type: Boolean, default: false },
})

const yFormatter = (val) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency', currency: 'XAF', notation: 'compact', maximumFractionDigits: 0,
  }).format(val)
</script>
