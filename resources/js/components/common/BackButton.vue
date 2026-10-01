<template>
  <button
    @click="goBack"
    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 dark:hover:text-white transition-colors group"
    :class="customClass"
  >
    <svg 
      class="w-4 h-4 transition-transform group-hover:-translate-x-1" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
    </svg>
    <span v-if="!hideLabel">{{ label }}</span>
  </button>
</template>

<script setup>
import { useRouter } from 'vue-router';

const props = defineProps({
  to: {
    type: [String, Object],
    default: null
  },
  label: {
    type: String,
    default: 'Retour'
  },
  hideLabel: {
    type: Boolean,
    default: false
  },
  customClass: {
    type: String,
    default: ''
  }
});

const router = useRouter();

const goBack = () => {
  if (props.to) {
    // Si une destination spécifique est fournie
    if (typeof props.to === 'string') {
      router.push(props.to);
    } else {
      router.push(props.to);
    }
  } else {
    // Sinon, retour arrière dans l'historique
    router.back();
  }
};
</script>
