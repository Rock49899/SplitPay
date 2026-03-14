<template>
  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <div class="-ml-4 min-w-[650px] xl:min-w-full pl-2">
      <VueApexCharts type="area" :height="height" :options="mergedOptions" :series="activeSeries" />
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
  // Override series, e.g. [{ name: 'Collected', data: [...] }]
  series: { type: Array, default: null },
  // Override x-axis categories
  categories: { type: Array, default: null },
  // Color palette
  colors: { type: Array, default: () => ['#465FFF', '#9CB9FF'] },
  // Chart height
  height: { type: Number, default: 280 },
  // Y-axis value formatter
  yFormatter: { type: Function, default: null },
})

const defaultSeries = [
  { name: 'Collected', data: [180, 190, 170, 160, 175, 165, 170, 205, 230, 210, 240, 235] },
  { name: 'Pending',   data: [40,  30,  50,  40,  55,  40,  70,  100, 110, 120, 150, 140] },
]
const defaultCategories = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

const activeSeries = computed(() => props.series ?? defaultSeries)
const isDark = ref(false)
let classObserver = null

const syncTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

onMounted(() => {
  syncTheme()
  classObserver = new MutationObserver(syncTheme)
  classObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

onBeforeUnmount(() => {
  classObserver?.disconnect()
})

const mergedOptions = computed(() => ({
  colors: props.colors,
  theme: { mode: isDark.value ? 'dark' : 'light' },
  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'left',
    fontFamily: 'Outfit',
    markers: { radius: 99 },
    labels: { colors: isDark.value ? '#9CA3AF' : '#6B7280' },
  },
  chart: {
    fontFamily: 'Outfit, sans-serif',
    type: 'area',
    toolbar: { show: false },
    foreColor: isDark.value ? '#9CA3AF' : '#6B7280',
  },
  fill: {
    type: 'gradient',
    gradient: { enabled: true, opacityFrom: 0.45, opacityTo: 0 },
  },
  stroke: { curve: 'smooth', width: [2, 2] },
  markers: { size: 0 },
  dataLabels: { enabled: false },
  grid: {
    borderColor: isDark.value ? '#1F2937' : '#E5E7EB',
    xaxis: { lines: { show: false } },
    yaxis: { lines: { show: true } },
  },
  tooltip: { x: { show: true } },
  xaxis: {
    type: 'category',
    categories: props.categories ?? defaultCategories,
    axisBorder: { show: false },
    axisTicks: { show: false },
    tooltip: { enabled: false },
    labels: {
      style: {
        fontFamily: 'Outfit, sans-serif',
        fontSize: '12px',
        colors: isDark.value ? '#9CA3AF' : '#6B7280',
      },
    },
  },
  yaxis: {
    labels: {
      formatter: props.yFormatter ?? ((val) => val.toString()),
      style: { fontFamily: 'Outfit, sans-serif', colors: isDark.value ? '#9CA3AF' : '#6B7280' },
    },
  },
}))
</script>
