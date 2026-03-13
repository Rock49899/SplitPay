<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- Overlay -->
    <div class="fixed inset-0 bg-black/50" @click="emitClose"></div>
    
    <!-- Modal panel -->
    <div class="relative z-50 w-full max-w-3xl mx-4">
      <slot name="body">
        <!-- Default wrapper with padding and scroll -->
        <div class="bg-white dark:bg-slate-800 border border-transparent dark:border-slate-700 rounded-lg shadow-xl overflow-hidden">
          <div class="p-6 max-h-[90vh] overflow-y-auto">
            <slot />
          </div>
          
          <!-- Footer slot if provided -->
          <div v-if="$slots.footer" class="border-t border-slate-200 dark:border-slate-700 p-4">
            <slot name="footer"></slot>
          </div>
        </div>
      </slot>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from 'vue';

const emit = defineEmits(['close']);

const emitClose = () => emit('close');

// Close modal on Escape key
const onKey = (e) => {
  if (e.key === 'Escape') emitClose();
};

onMounted(() => {
  window.addEventListener('keydown', onKey);
  // Prevent body scroll when modal is open
  document.body.style.overflow = 'hidden';
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKey);
  // Restore body scroll
  document.body.style.overflow = '';
});
</script>

<style scoped>
/* Minimal styles - relies on Tailwind classes */
</style>
