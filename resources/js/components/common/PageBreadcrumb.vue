<template>
  <div class="mb-6">
    <!-- Back Button (if enabled) -->
    <div v-if="showBackButton" class="mb-3">
      <BackButton :to="backTo" :label="backLabel" />
    </div>

    <!-- Breadcrumb -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100" x-text="pageTitle">
        {{ pageTitle }}
      </h2>
      <nav>
        <ol class="flex items-center gap-1.5">
          <li>
            <router-link
              class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-800 dark:text-slate-300 dark:hover:text-slate-100 transition-colors"
              to="/"
            >
              Accueil
              <svg
                class="stroke-current"
                width="17"
                height="16"
                viewBox="0 0 17 16"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                  stroke=""
                  stroke-width="1.2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </router-link>
          </li>
          <li class="text-sm font-medium text-slate-900 dark:text-slate-100">
            {{ pageTitle }}
          </li>
        </ol>
      </nav>
    </div>
  </div>
</template>

<script setup>
import BackButton from './BackButton.vue';

const props = defineProps({
  pageTitle: {
    type: String,
    required: true
  },
  showBackButton: {
    type: Boolean,
    default: true  // Now enabled by default everywhere
  },
  backTo: {
    type: [String, Object],
    default: null
  },
  backLabel: {
    type: String,
    default: 'Back'  // English by default
  }
})
</script>
