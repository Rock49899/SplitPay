<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Suivi financier" />
    <div class="space-y-5">
      <!-- En-tête étudiant -->
      <div v-if="student" class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
          <AvatarDisplay :src="student.avatar_url" :label="student.first_name" :size="52" />
          <div>
            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ student.first_name }} {{ student.last_name }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ student.matricule }}
              <template v-if="finance.study_level"> · {{ finance.study_level }}</template>
              <template v-if="student.specialization?.label"> · {{ student.specialization.label }}</template>
              <template v-if="student.annexe?.name"> · {{ student.annexe.name }}</template>
            </p>
          </div>
        </div>
        <button
          @click="openCreateModal"
          :disabled="finance.amount_due <= 0"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
        >
          <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
          </svg>
          Créer un lien de paiement
        </button>
      </div>

      <!-- Situation de l'année -->
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl bg-brand-600 p-6 text-white lg:col-span-1 dark:bg-brand-700">
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-brand-100">Scolarité {{ finance.school_year || '' }}</p>
            <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium">{{ statusLabel }}</span>
          </div>
          <p class="mt-3 text-3xl font-bold tracking-tight">{{ money(finance.amount_paid) }}</p>
          <p class="mt-1 text-sm text-brand-100/80">payés sur {{ money(finance.tuition_amount) }}</p>
          <div class="mt-5 h-2.5 w-full rounded-full bg-white/15">
            <div class="h-2.5 rounded-full bg-white transition-all duration-700" :style="{ width: paidPercent + '%' }"></div>
          </div>
          <p class="mt-2 text-xs text-brand-100/90">{{ paidPercent }} % de la scolarité réglée</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:col-span-2">
          <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Reste à payer</p>
            <p class="mt-3 text-2xl font-bold" :class="finance.amount_due > 0 ? 'text-error-600 dark:text-error-400' : 'text-success-600 dark:text-success-400'">
              {{ money(finance.amount_due) }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ finance.amount_due > 0 ? 'Solde à régler sur l\'année' : 'Scolarité entièrement réglée' }}</p>
          </div>
          <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Dernier paiement</p>
            <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ longDate(finance.last_payment_date) }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Paiement confirmé le plus récent</p>
          </div>
        </div>
      </div>

      <div v-if="createdLinkUrl" class="flex flex-col gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm dark:border-brand-500/30 dark:bg-brand-500/10 sm:flex-row sm:items-center">
        <span class="font-medium text-brand-800 dark:text-brand-200">Lien créé :</span>
        <a :href="createdLinkUrl" target="_blank" class="break-all text-brand-700 underline dark:text-brand-300">{{ createdLinkUrl }}</a>
        <div class="flex gap-2 sm:ml-auto">
          <button @click="copyLink" class="rounded-lg border border-brand-300 bg-white px-3 py-1.5 font-medium text-brand-700 hover:bg-brand-50">Copier</button>
          <button @click="openMailClient" class="rounded-lg bg-brand-500 px-3 py-1.5 font-medium text-white hover:bg-brand-600">Envoyer par e-mail</button>
        </div>
      </div>

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
import { ref, computed, onMounted, watch } from 'vue';
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
import { useActiveYearStore } from '@/stores/useActiveYearStore';

const route = useRoute();
const id = route.params.id;
const activeYearStore = useActiveYearStore();

const student = ref(null);
const currentEnrollmentId = ref(null);
const finance = ref({ tuition_amount: null, amount_paid: 0, amount_due: 0, last_payment_date: null, school_year: null, study_level: null });
const showCreate = ref(false);
const isCreating = ref(false);
const createdLinkUrl = ref(null);
const createdLinkId = ref(null);
const linksCard = ref(null);

const form = ref({ type: 'tuition', amount: null, due_date: null, description: '', currency: 'XOF' });

const money = (v) =>
  v == null ? '—' : new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(Number(v));

const longDate = (d) => {
  if (!d) return 'Aucun';
  const dt = new Date(d);
  return isNaN(dt) ? String(d) : new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }).format(dt);
};

const paidPercent = computed(() => {
  const total = Number(finance.value.tuition_amount || 0);
  if (total <= 0) return 0;
  return Math.min(100, Math.round((Number(finance.value.amount_paid || 0) / total) * 100));
});

const statusLabel = computed(() => {
  if (finance.value.amount_due <= 0 && finance.value.tuition_amount > 0) return 'Soldée';
  if (finance.value.amount_paid > 0) return 'En cours';
  return 'Aucun paiement';
});

const maxAmount = () => (form.value.type === 'tuition' ? Number(finance.value.amount_due ?? 0) : 1e12);

const load = async () => {
  try {
    const sRes = await studentService.show(id);
    const s = sRes.data?.student ?? sRes.data ?? {};
    student.value = s;
    // enrollment courant pour le modal de lien de paiement
    currentEnrollmentId.value = s?.current_enrollment?.id ?? null;

    // Le détail financier (date du dernier paiement incluse) vient de l'endpoint financials
    let fin = null;
    try {
      const fRes = await studentService.financials(id);
      fin = fRes?.data?.data ?? fRes?.data ?? null;
      if (fin && fin.data) fin = fin.data;
    } catch { fin = null; }
    fin = { ...(sRes.data?.finance ?? {}), ...(fin ?? {}) };
    finance.value = {
      tuition_amount: Number(fin?.tuition_amount ?? 0),
      amount_paid:    Number(fin?.amount_paid    ?? 0),
      amount_due:     Number(fin?.amount_due     ?? Math.max(0, (fin?.tuition_amount ?? 0) - (fin?.amount_paid ?? 0))),
      last_payment_date: fin?.last_payment_date ?? null,
      school_year:    fin?.school_year ?? null,
      study_level:    fin?.study_level ?? null,
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
  try { await navigator.clipboard.writeText(createdLinkUrl.value); alert('Lien copié'); }
  catch { alert('Échec de la copie'); }
};

const openMailClient = async () => {
  if (!createdLinkId.value) return alert('Aucun lien créé à envoyer');
  try {
    const email = student.value?.email ?? null;
    if (!email) return alert('L\'étudiant n\'a pas d\'email');
    await paymentLinkService.sendEmail(createdLinkId.value, { email });
    alert('Lien de paiement envoyé à ' + email);
  } catch (e) {
    console.error('Send email failed', e);
    alert(e.response?.data?.message || e.message || 'Échec de l\'envoi');
  }
};

onMounted(load);

watch(() => activeYearStore.activeYear, () => {
  load();
  linksCard.value?.fetchLinks();
});
</script>

<style scoped></style>