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
    console.log('Loading student finance for id:', id);
    const sRes = await studentService.show(id);
    console.log('studentService.show response:', sRes);
    const s = sRes.data?.student ?? sRes.data ?? {};
    console.log('Normalized student object:', s);
    student.value = s || null;
    // extraire les champs financiers depuis l'enregistrement student
    const tuition = s.tuition_amount ?? null;
    const paid = s.amount_paid ?? 0;
    finance.value = {
      tuition_amount: tuition,
      amount_paid: paid,
      amount_due: (typeof tuition === 'number' ? Math.max(0, tuition - (paid || 0)) : null),
      // last_payment_date: s.last_payment_date ?? s.last_payment_at ?? null,
    };
    console.log('Computed finance:', finance.value);
  } catch (e) {
    console.error('Failed to load finance', e);
    error.value = 'Failed to load finance';
  } finally {
    loading.value = false;
  }
};

const createPaymentLink = async () => {
  try {
    const amount = finance.value.amount_due ?? finance.value.tuition_amount ?? 0;
    console.log('Requesting payment link for amount:', amount);
    const res = await studentService.createPaymentLink(id, { amount });
    console.log('Payment link response:', res);
    paymentLink.value = res.data?.link ?? res.data?.url ?? res.data?.link_url ?? res.data;
  } catch (e) {
    console.error('Failed to create payment link', e);
    alert('Failed to create payment link');
  }
};

onMounted(load);
</script>

<style scoped></style>
