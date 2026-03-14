<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/50" @click="$emit('close')"></div>
    <div class="bg-white dark:bg-slate-800 border border-transparent dark:border-slate-700 rounded-lg p-6 z-50 w-full max-w-md">
      <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Select columns to display</h3>
      <div class="space-y-2">
        <div v-for="col in columns" :key="col.key" class="flex items-center gap-3">
          <input type="checkbox" :id="col.key" v-model="localSelected" :value="col.key" />
          <label :for="col.key" class="text-sm text-slate-700 dark:text-slate-200">{{ col.label }}</label>
        </div>
      </div>
      <div class="mt-4 flex justify-end gap-2">
        <button @click="$emit('close')" class="px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-100 rounded hover:bg-slate-50 dark:hover:bg-slate-600">Cancel</button>
        <button @click="apply" class="px-4 py-2 bg-brand-500 text-white rounded">Apply</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
const props = defineProps({
  columns: { type: Array, required: true }, // [{ key, label }]
  value: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:value', 'close']);
const localSelected = ref([...props.value]);
watch(() => props.value, v => localSelected.value = [...v]);
const apply = () => {
  emit('update:value', [...localSelected.value]);
  emit('close');
};
</script>

<style scoped>
/* minimal */
</style>
