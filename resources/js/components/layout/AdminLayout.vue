<template>
  <div class="min-h-screen xl:flex bg-slate-50 dark:bg-slate-900">
    <app-sidebar />
    <Backdrop />
    <div
      class="flex-1 transition-all duration-300 ease-in-out bg-slate-50 dark:bg-slate-900"
      :class="[isExpanded || isHovered ? 'lg:ml-[290px]' : 'lg:ml-[90px]']"
    >
      <app-header />
      <AdminOnboardingTour />
      <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6 bg-slate-50 dark:bg-slate-900 min-h-[calc(100vh-64px)]">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import { useSidebar } from '@/composables/useSidebar'
import Backdrop from './Backdrop.vue'
import AdminOnboardingTour from '@/components/onboarding/AdminOnboardingTour.vue'
const { isExpanded, isHovered } = useSidebar()
</script>
