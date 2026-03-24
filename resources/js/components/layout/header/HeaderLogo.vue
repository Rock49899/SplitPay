<template>
  <router-link to="/" class="lg:hidden inline-flex items-center">
    <img
      v-if="brandLogoUrl"
      :src="brandLogoUrl"
      :alt="brandName"
      class="h-10 w-auto max-w-[180px] object-contain"
    />
    <h3 v-else class="text-black dark:text-white font-semibold">{{ displayName }}</h3>
  </router-link>
</template>

<script setup>
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useInstitutionBrand } from '@/composables/useInstitutionBrand'

const { brandName, brandLogoUrl, displayName, loadBrand } = useInstitutionBrand()

onMounted(() => {
  loadBrand(true)
})
</script>
