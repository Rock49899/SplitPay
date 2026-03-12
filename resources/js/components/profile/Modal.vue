<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/50" @click="emitClose"></div>
    <div class="relative z-50 w-full max-w-3xl mx-4">
      <slot name="body">
        <!-- fallback: default slot -->
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-h-[90vh] overflow-auto">
          <slot />
        </div>
      </slot>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from 'vue';
const emit = defineEmits(['close']);

const emitClose = () => emit('close');

const onKey = (e) => {
  if (e.key === 'Escape') emitClose();
};

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<style scoped>
/* minimal */
</style>
