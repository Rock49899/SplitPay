<template>
  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <div class="-ml-5 min-w-[650px] xl:min-w-full pl-2">
      <VueApexCharts :type="horizontal ? 'bar' : 'bar'" :height="height" :options="mergedOptions" :series="activeSeries" />
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
  // Override series entirely, e.g. [{ name: 'Collected', data: [...] }]
  series: { type: Array, default: null },
  // Override x-axis categories
  categories: { type: Array, default: null },
  // Color palette
  colors: { type: Array, default: () => ['#0e7c66', '#fdb022', '#f97066'] },
  // Chart height
  height: { type: Number, default: 220 },
  // Horizontal bars
  horizontal: { type: Boolean, default: false },
  // Y-axis formatter (function or null)
  yFormatter: { type: Function, default: null },
  // Column width
  columnWidth: { type: String, default: '55%' },
})

const defaultSeries = [
  { name: 'Sales', data: [168, 385, 201, 298, 187, 195, 291, 110, 215, 390, 280, 112] },
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
  chart: {
    fontFamily: "'SplitPay Espaces', 'Plus Jakarta Sans', sans-serif",
    type: 'bar',
    toolbar: { show: false },
    foreColor: isDark.value ? '#98A39F' : '#68736F',
  },
  plotOptions: {
    bar: {
      horizontal: props.horizontal,
      columnWidth: props.columnWidth,
      borderRadius: 5,
      borderRadiusApplication: 'end',
    },
  },
  dataLabels: { enabled: false },
  stroke: { show: true, width: 4, colors: ['transparent'] },
  xaxis: {
    categories: props.categories ?? defaultCategories,
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: {
      style: {
        fontFamily: "'SplitPay Espaces', 'Plus Jakarta Sans', sans-serif",
        fontSize: '12px',
        colors: isDark.value ? '#98A39F' : '#68736F',
      },
    },
  },
  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'left',
    fontFamily: "'SplitPay Espaces', 'Plus Jakarta Sans', sans-serif",
    markers: { radius: 99 },
    labels: { colors: isDark.value ? '#98A39F' : '#68736F' },
  },
  yaxis: {
    title: false,
    labels: {
      formatter: props.yFormatter ?? ((val) => val.toString()),
    },
  },
  grid: {
    borderColor: isDark.value ? '#1F2624' : '#E1E6E4',
    yaxis: { lines: { show: true } },
  },
  fill: { opacity: 1 },
  tooltip: {
    x: { show: false },
    y: {
      formatter: props.yFormatter ?? ((val) => val.toString()),
    },
  },
}))
</script>
