<template>
  <!-- Avatar with photo or fallback icon -->
  <div
    :style="{ width: sizePx, height: sizePx }"
    class="shrink-0 rounded-full overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center"
  >
    <img
      v-if="src"
      :src="src"
      :alt="label"
      class="w-full h-full object-cover"
      @error="onImgError"
    />
    <svg
      v-else
      :style="{ width: iconSizePx, height: iconSizePx }"
      class="text-gray-400"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
      stroke-width="1.5"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
      />
    </svg>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  /** Public URL of the avatar image (null/undefined = show icon) */
  src: {
    type: String,
    default: null,
  },
  /** Alt text / label */
  label: {
    type: String,
    default: 'avatar',
  },
  /** Size in pixels (applies to both width and height) */
  size: {
    type: Number,
    default: 40,
  },
});

// Reset on src change so error doesn't persist for a new src
const hasError = ref(false);
watch(() => props.src, () => { hasError.value = false; });

const onImgError = () => { hasError.value = true; };

const sizePx = computed(() => `${props.size}px`);
const iconSizePx = computed(() => `${Math.round(props.size * 0.55)}px`);
</script>
