<template>
  <div
    :class="[
      'rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800',
      className,
    ]"
  >
    <!-- Card Header -->
    <div class="px-6 py-5">
      <h3 class="text-base font-medium text-slate-800 dark:text-white">
        {{ title }}
      </h3>
      <p v-if="desc" class="mt-1 text-sm text-slate-500 dark:text-slate-300">
        {{ desc }}
      </p>
    </div>

    <!-- Card Body -->
    <div class="p-4 border-t border-slate-100 dark:border-slate-700 sm:p-6">
      <div class="space-y-5">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  className: {
    type: String,
    default: ''
  },
  desc: {
    type: String,
    default: ''
  }
})
</script>
