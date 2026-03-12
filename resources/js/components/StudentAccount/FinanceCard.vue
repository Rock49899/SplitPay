<template>
  <div :class="`rounded-xl p-4 ${colors.bg}`">
    <p :class="`text-xs font-medium ${colors.label} mb-1`">{{ label }}</p>
    <p :class="`text-lg font-bold ${colors.text} leading-tight`">{{ fmtVal }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label:    { type: String, required: true },
  value:    { type: [Number, String], default: 0 },
  color:    { type: String, default: 'gray' },
  currency: { type: String, default: 'XOF' },
});

const colorMap = {
  blue:   { bg: 'bg-blue-50',   text: 'text-blue-700',   label: 'text-blue-500'   },
  green:  { bg: 'bg-green-50',  text: 'text-green-700',  label: 'text-green-600'  },
  orange: { bg: 'bg-orange-50', text: 'text-orange-700', label: 'text-orange-600' },
  purple: { bg: 'bg-purple-50', text: 'text-purple-700', label: 'text-purple-600' },
  gray:   { bg: 'bg-gray-50',   text: 'text-gray-700',   label: 'text-gray-500'   },
};

const colors = computed(() => colorMap[props.color] ?? colorMap.gray);

const fmtVal = computed(() => {
  try {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: props.currency ?? 'XOF',
    }).format(Number(props.value ?? 0));
  } catch {
    return `${Number(props.value ?? 0).toLocaleString('fr-FR')} ${props.currency}`;
  }
});
</script>
