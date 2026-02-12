<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="`Finance — ${student?.first_name || ''} ${student?.last_name || ''}`" />
    <div class="space-y-4">
      <ComponentCard title="Financial Summary">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <div class="text-sm text-gray-500">Tuition Amount</div>
            <div class="text-lg font-semibold">{{ finance.tuition_amount ?? '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Paid</div>
            <div class="text-lg font-semibold">{{ finance.amount_paid ?? '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Due</div>
            <div class="text-lg font-semibold">{{ finance.amount_due ?? '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Last payment</div>
            <div class="text-lg font-semibold">{{ finance.last_payment_date ?? '-' }}</div>
          </div>
        </div>
        <div class="mt-4">
          <button @click="createPaymentLink" class="px-4 py-2 bg-brand-500 text-white rounded">Create payment link</button>
          <div v-if="paymentLink" class="mt-2">
            <a :href="paymentLink" target="_blank" class="text-indigo-600 underline">{{ paymentLink }}</a>
          </div>
        </div>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import api from '@/services/api';
import studentService from '@/services/studentService';

const route = useRoute();
const router = useRouter();
const id = route.params.id;

const student = ref(null);
const finance = ref({});
const paymentLink = ref(null);
const loading = ref(false);
const error = ref(null);

const load = async () => {
  loading.value = true;
  try {
    const [sRes, fRes] = await Promise.all([
      studentService.show(id),
      api.get(`admin/students/${id}/financials`).catch(() => ({ data: {} })),
    ]);
    student.value = (sRes.data?.student ?? sRes.data) || null;
    finance.value = fRes.data?.data ?? fRes.data ?? {};
  } catch (e) {
    console.error(e);
    error.value = 'Failed to load finance';
  } finally {
    loading.value = false;
  }
};

const createPaymentLink = async () => {
  try {
    const res = await api.post(`admin/students/${id}/payment-link`, { amount: finance.value.amount_due ?? finance.value.tuition_amount ?? 0 });
    paymentLink.value = res.data?.link ?? res.data?.url ?? res.data;
  } catch (e) {
    console.error(e);
    alert('Failed to create payment link');
  }
};

onMounted(load);
</script>

<style scoped></style>
