<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="title" />
    <div class="p-6">
      <h2 class="text-lg font-semibold mb-4">{{ title || 'Placeholder' }}</h2>
      <p class="text-sm text-gray-500">This is a placeholder page. Replace with real implementation.</p>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useRoute } from 'vue-router';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';

const route = useRoute();
const title = route.meta?.title || route.name || '';
</script>
