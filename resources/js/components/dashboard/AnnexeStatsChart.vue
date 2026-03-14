<template>
  <div class="rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 overflow-hidden">

    <!-- Tabs header -->
    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 px-5 pt-5 pb-0">
      <div>
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Branches Overview</h3>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 mb-3">{{ schoolYear }}</p>
      </div>
      <div class="flex gap-1 mb-3">
        <button
          @click="activeTab = 'amounts'"
          :class="[
            'rounded-lg px-3 py-1.5 text-xs font-medium transition',
            activeTab === 'amounts'
              ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400'
              : 'text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/[0.04]',
          ]"
        >
          Amounts
        </button>
        <button
          @click="activeTab = 'rate'"
          :class="[
            'rounded-lg px-3 py-1.5 text-xs font-medium transition',
            activeTab === 'rate'
              ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400'
              : 'text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/[0.04]',
          ]"
        >
          Recovery %
        </button>
      </div>
    </div>

    <!-- Spinner -->
    <div v-if="loading" class="flex h-[280px] items-center justify-center">
      <svg class="h-6 w-6 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
      </svg>
    </div>

    <div v-else class="p-4">

      <!-- Tab: Collected vs Outstanding amounts -->
      <div v-if="activeTab === 'amounts'">
        <BarChartOne
          :series="amountSeries"
          :categories="labels"
          :colors="['#465FFF', '#FF6B6B']"
          :y-formatter="yFormatterMoney"
          :height="260"
          column-width="55%"
        />
      </div>

      <!-- Tab: Recovery rate % per annexe (horizontal bars, sorted asc) -->
      <div v-if="activeTab === 'rate'">
        <BarChartOne
          :series="rateSeries"
          :categories="sortedLabels"
          :colors="['#14b8a6']"
          :y-formatter="(v) => v + '%'"
          :height="260"
          :horizontal="true"
          column-width="40%"
        />
      </div>

      <!-- Stat table -->
      <div class="mt-3 divide-y divide-slate-100 dark:divide-slate-700">
        <div
          v-for="(label, i) in labels" :key="label"
          class="flex items-center justify-between py-2 text-xs"
        >
          <span class="font-medium text-gray-700 dark:text-gray-300 truncate max-w-[120px]">{{ label }}</span>
          <div class="flex items-center gap-3">
            <span class="text-gray-500 dark:text-gray-400">{{ fmtShort(collected[i] ?? 0) }}</span>
            <span
              class="min-w-[42px] rounded-full px-2 py-0.5 text-center font-semibold"
              :class="rateClass(recoveryRate[i] ?? 0)"
            >{{ recoveryRate[i] ?? 0 }}%</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import BarChartOne from '@/components/charts/BarChart/BarChartOne.vue'

const props = defineProps({
  labels:       { type: Array,   default: () => [] },
  collected:    { type: Array,   default: () => [] },
  unpaid:       { type: Array,   default: () => [] },
  recoveryRate: { type: Array,   default: () => [] },
  schoolYear:   { type: String,  default: '' },
  loading:      { type: Boolean, default: false },
})

const activeTab = ref('amounts')

// ── Chart series ────────────────────────────────────────────────────────────────

const amountSeries = computed(() => [
  { name: 'Collected',    data: props.collected },
  { name: 'Outstanding',  data: props.unpaid },
])

// Sort by recovery rate ascending (worst first) for the rate chart
const sortedIndices = computed(() =>
  props.recoveryRate
    .map((r, i) => ({ r, i }))
    .sort((a, b) => a.r - b.r)
    .map((x) => x.i)
)
const sortedLabels = computed(() => sortedIndices.value.map((i) => props.labels[i]))
const rateSeries   = computed(() => [
  { name: 'Recovery Rate', data: sortedIndices.value.map((i) => props.recoveryRate[i] ?? 0) },
])

// ── Formatters ──────────────────────────────────────────────────────────────────

const yFormatterMoney = (val) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency', currency: 'XAF', notation: 'compact', maximumFractionDigits: 0,
  }).format(val)

const fmtShort = (v) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency', currency: 'XAF', notation: 'compact', maximumFractionDigits: 0,
  }).format(v)

const rateClass = (r) => {
  if (r >= 80) return 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'
  if (r >= 50) return 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-orange-400'
  return 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400'
}
</script>
