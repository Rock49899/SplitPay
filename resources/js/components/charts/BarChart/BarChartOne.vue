<template>
  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <div class="-ml-5 min-w-[650px] xl:min-w-full pl-2">
      <VueApexCharts :type="horizontal ? 'bar' : 'bar'" :height="height" :options="mergedOptions" :series="activeSeries" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
  // Override series entirely, e.g. [{ name: 'Collected', data: [...] }]
  series: { type: Array, default: null },
  // Override x-axis categories
  categories: { type: Array, default: null },
  // Color palette
  colors: { type: Array, default: () => ['#465fff', '#9CB9FF', '#FF6B6B'] },
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

const mergedOptions = computed(() => ({
  colors: props.colors,
  chart: {
    fontFamily: 'Outfit, sans-serif',
    type: 'bar',
    toolbar: { show: false },
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
      style: { fontFamily: 'Outfit, sans-serif', fontSize: '12px' },
    },
  },
  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'left',
    fontFamily: 'Outfit',
    markers: { radius: 99 },
  },
  yaxis: {
    title: false,
    labels: {
      formatter: props.yFormatter ?? ((val) => val.toString()),
    },
  },
  grid: { yaxis: { lines: { show: true } } },
  fill: { opacity: 1 },
  tooltip: {
    x: { show: false },
    y: {
      formatter: props.yFormatter ?? ((val) => val.toString()),
    },
  },
}))
</script>
