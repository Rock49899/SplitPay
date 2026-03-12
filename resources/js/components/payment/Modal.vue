<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- overlay -->
    <div class="absolute inset-0 bg-black/50" @click.self="emitClose"></div>

    <!-- modal panel -->
    <div class="relative z-10 w-full max-w-3xl mx-4">
      <div class="rounded-2xl bg-white dark:bg-gray-900 shadow-lg overflow-hidden">
        <div class="p-4">
          <slot name="body"></slot>
        </div>
        <div v-if="$slots.footer" class="p-4 border-t border-gray-100 dark:border-gray-800">
          <slot name="footer"></slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const emit = defineEmits(['close']);
const emitClose = () => emit('close');
</script>

<style scoped>
/* minimal styling; adapt to theme/tailwind in project */
</style>
