<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="`Finance — ${student?.first_name || ''} ${student?.last_name || ''}`" />
    <div class="space-y-4">
      <!-- Student mini-header -->
      <div v-if="student" class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3">
        <AvatarDisplay :src="student.avatar_url" :label="student.first_name" :size="44" />
        <div>
          <p class="font-semibold text-gray-900 text-sm">{{ student.first_name }} {{ student.last_name }}</p>
          <p class="text-xs text-gray-500">{{ student.matricule }}</p>
        </div>
      </div>
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
        <div class="mt-4 flex items-center gap-3">
          <button @click="openCreateModal" class="px-4 py-2 bg-brand-500 text-white rounded">Create payment link</button>
          <div v-if="createdLinkUrl" class="flex items-center gap-3">
            <a :href="createdLinkUrl" target="_blank" class="text-indigo-600 underline break-all">{{ createdLinkUrl }}</a>
            <button @click="copyLink" class="px-3 py-1 border rounded">Copy</button>
            <button @click="openMailClient" class="px-3 py-1 border rounded">Send Email</button>
          </div>
        </div>
      </ComponentCard>

      <!-- Payment links list -->
      <PaymentLinksCard :student-id="id" ref="linksCard" />
    </div>
  </AdminLayout>

  <!-- Create Payment Link Modal (moved to component) -->
  <PaymentLinkModal
    v-if="showCreate"
    :student-id="id"
    :remaining="finance.amount_due"
    :tuition-amount="finance.tuition_amount"
    :enrollment-id="currentEnrollmentId"
    :initial-currency="form.currency"
    @created="onLinkCreated"
    @close="showCreate = false"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import paymentLinkService from '@/services/paymentLinkService';
import studentService from '@/services/studentService';
import Modal from '@/components/payment/Modal.vue';
import PaymentLinkModal from '@/components/payment/PaymentLinkModal.vue';
import PaymentLinksCard from '@/components/payment/PaymentLinksCard.vue';
import AvatarDisplay from '@/components/shared/AvatarDisplay.vue';

const route = useRoute();
const id = route.params.id;

const student = ref(null);
const currentEnrollmentId = ref(null);
const finance = ref({ tuition_amount: null, amount_paid: 0, amount_due: 0, last_payment_date: null });
const showCreate = ref(false);
const isCreating = ref(false);
const createdLinkUrl = ref(null);
const createdLinkId = ref(null);
const linksCard = ref(null);

const form = ref({ type: 'tuition', amount: null, due_date: null, description: '', currency: 'USD' });

const maxAmount = () => (form.value.type === 'tuition' ? Number(finance.value.amount_due ?? 0) : 1e12);

const load = async () => {
  try {
    const sRes = await studentService.show(id);
    const s = sRes.data?.student ?? sRes.data ?? {};
    student.value = s;
    // enrollment courant pour le modal de lien de paiement
    currentEnrollmentId.value = s?.current_enrollment?.id ?? null;

    let fin = sRes.data?.finance ?? null;
    if (!fin) {
      try {
        const fRes = await studentService.financials(id);
        fin = fRes?.data?.data ?? fRes?.data ?? null;
        if (fin && fin.data) fin = fin.data;
      } catch { fin = null; }
    }
    finance.value = {
      tuition_amount: Number(fin?.tuition_amount ?? 0),
      amount_paid:    Number(fin?.amount_paid    ?? 0),
      amount_due:     Number(fin?.amount_due     ?? Math.max(0, (fin?.tuition_amount ?? 0) - (fin?.amount_paid ?? 0))),
      last_payment_date: fin?.last_payment_date ?? null,
    };
  } catch (e) {
    console.error('Failed to load finance', e);
  }
};

const openCreateModal = () => {
  form.value.type = 'tuition';
  form.value.amount = Number(finance.value.amount_due ?? finance.value.tuition_amount ?? 0);
  form.value.due_date = null;
  form.value.description = '';
  createdLinkUrl.value = null;
  showCreate.value = true;
};

const onLinkCreated = (linkObj) => {
  createdLinkId.value = linkObj.id ?? linkObj.data?.id ?? null;
  const token = linkObj.token ?? linkObj.data?.token ?? null;
  if (token) createdLinkUrl.value = `${window.location.origin}/payment/${token}`;
  else if (linkObj.link) createdLinkUrl.value = linkObj.link;
  else createdLinkUrl.value = null;
  showCreate.value = false;
  // refresh finances and list
  load();
  linksCard.value?.fetchLinks();
};

const copyLink = async () => {
  if (!createdLinkUrl.value) return;
  try { await navigator.clipboard.writeText(createdLinkUrl.value); alert('Link copied'); }
  catch { alert('Copy failed'); }
};

const openMailClient = async () => {
  if (!createdLinkId.value) return alert('No created link to send');
  try {
    const email = student.value?.email ?? null;
    if (!email) return alert('Student has no email');
    await paymentLinkService.sendEmail(createdLinkId.value, { email });
    alert('Payment link sent to ' + email);
  } catch (e) {
    console.error('Send email failed', e);
    alert(e.response?.data?.message || e.message || 'Send failed');
  }
};

onMounted(load);
</script>

<style scoped></style>