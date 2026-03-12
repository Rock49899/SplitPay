<template>
  <ComponentCard title="Payment Links">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left">
            <th class="px-3 py-2">Type</th>
            <th class="px-3 py-2">Description</th>
            <th class="px-3 py-2">Amount</th>
            <th class="px-3 py-2">Paid</th>
            <th class="px-3 py-2">Due</th>
            <th class="px-3 py-2">Last payment</th>
            <th class="px-3 py-2">Due date</th>
            <th class="px-3 py-2">Status</th>
            <th class="px-3 py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="lnk in links" :key="lnk.id" class="border-t">
            <td class="px-3 py-2">{{ lnk.type ?? 'other' }}</td>
            <td class="px-3 py-2">
              <div class="max-w-[240px] truncate" :title="lnk.description">{{ lnk.description ?? '—' }}</div>
            </td>
            <!-- afficher la devise du lien si présente -->
            <td class="px-3 py-2">{{ formatCurrency(lnk.amount, lnk.currency ?? 'USD') }}</td>
            <td class="px-3 py-2">{{ formatCurrency(paidFor(lnk), lnk.currency ?? 'USD') }}</td>
            <td class="px-3 py-2">{{ formatCurrency((lnk.amount || 0) - paidFor(lnk), lnk.currency ?? 'USD') }}</td>
            <td class="px-3 py-2">{{ formatDate(lastPaymentFor(lnk)) ?? '-' }}</td>
            <td class="px-3 py-2">{{ formatDate(lnk.due_date) ?? '-' }}</td>
            <td class="px-3 py-2">{{ lnk.status ?? '-' }}</td>
            <td class="px-3 py-2">
              <button @click="openActions(lnk)" class="px-2 py-1 border rounded">Actions</button>
            </td>
          </tr>
          <tr v-if="!links.length">
            <td class="px-3 py-6 text-center" colspan="9">No payment links</td>
          </tr>
        </tbody>
      </table>
    </div>

    <LinkActionModal v-if="activeLink" :link="activeLink" @close="activeLink = null" @changed="onChanged" />
  </ComponentCard>
</template>

<script setup>
/* filepath: /home/rock/PIEUVRE/Saas-schooling-project/resources/js/components/payment/PaymentLinksCard.vue */
import { ref, onMounted } from 'vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import paymentLinkService from '@/services/paymentLinkService';
import LinkActionModal from '@/components/payment/LinkActionModal.vue';

const props = defineProps({
  studentId: { type: String, required: true }
});

// définir les events émis (rafraîchir)
const emit = defineEmits(['refresh']);

const links = ref([]);
const loading = ref(false);
const activeLink = ref(null);

const fetchLinks = async () => {
  loading.value = true;
  try {
    const res = await paymentLinkService.index({ student_id: props.studentId });
    links.value = res.data?.data ?? res.data ?? [];
  } catch (e) {
    console.error('Failed to fetch payment links', e);
    links.value = [];
  } finally {
    loading.value = false;
  }
};

// calcule le montant payé pour un lien : privilégie installments → payments → repli
const paidFor = (lnk) => {
  // si la structure installments est fournie
  if (Array.isArray(lnk.installments) && lnk.installments.length) {
    return lnk.installments.reduce((sum, inst) => {
      // le champ amount_paid ou paid_amount peut varier selon l'implémentation
      return sum + (Number(inst.amount_paid ?? inst.paid_amount ?? 0) || 0);
    }, 0);
  }
  // repli : tableau payments
  if (Array.isArray(lnk.payments) && lnk.payments.length) {
    return lnk.payments.reduce((sum, p) => sum + (Number(p.amount ?? 0) || 0), 0);
  }
  // repli final : champ direct sur l'objet
  return Number(lnk.amount_paid ?? 0);
};

// récupère la date du dernier paiement si présente
const lastPaymentFor = (lnk) => {
  // privilégier le tableau payments
  const pays = lnk.payments ?? [];
  if (Array.isArray(pays) && pays.length) {
    const latest = pays.slice().sort((a, b) => new Date(b.paid_at || b.payment_date || b.created_at) - new Date(a.paid_at || a.payment_date || a.created_at))[0];
    return latest?.paid_at ?? latest?.payment_date ?? latest?.created_at ?? null;
  }
  // sinon chercher dans les paiements des échéances
  if (Array.isArray(lnk.installments) && lnk.installments.length) {
    const allPayments = lnk.installments.flatMap(inst => inst.payments ?? []);
    if (allPayments.length) {
      const latest = allPayments.slice().sort((a,b) => new Date(b.paid_at || b.payment_date || b.created_at) - new Date(a.paid_at || a.payment_date || a.created_at))[0];
      return latest?.paid_at ?? latest?.payment_date ?? latest?.created_at ?? null;
    }
  }
  return null;
};

// formate la monnaie avec Intl ; si la devise n'est pas supportée, affiche montant + code
const formatCurrency = (v, currency = 'USD') => {
  try {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(v ?? 0));
  } catch (e) {
    // si la devise n'est pas supportée, retour simple
    return `${Number(v ?? 0).toFixed(2)} ${currency}`;
  }
};

const formatDate = (d) => {
  if (!d) return null;
  try {
    const s = String(d);
    // si format ISO avec T, garder la partie date
    if (s.includes('T')) return s.split('T')[0];
    // si date et heure séparées par un espace
    if (s.includes(' ')) return s.split(' ')[0];
    // sinon essayer de parser et renvoyer YYYY-MM-DD
    const dt = new Date(s);
    if (!isNaN(dt)) {
      return dt.toISOString().slice(0, 10);
    }
    return s;
  } catch {
    return String(d);
  }
};

const openActions = (lnk) => {
  activeLink.value = lnk;
};

const onChanged = () => {
  fetchLinks();
  // informer le parent si nécessaire
  emit('refresh');
};

onMounted(fetchLinks);

// exposer la méthode fetchLinks pour le parent
defineExpose({ fetchLinks });
</script>

<style scoped></style>