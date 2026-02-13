<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="My profile" />
    <div class="p-6 space-y-6">
      <ProfileCard />
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <PersonalInfoCard />
        <!-- AddressCard removed per spec; do not include it -->
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ProfileCard from '@/components/profile/ProfileCard.vue';
import PersonalInfoCard from '@/components/profile/PersonalInfoCard.vue';
</script>

<style scoped>
/* minimal */
</style>
