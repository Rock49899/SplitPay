<template>
  <ComponentCard title="Liens de paiement et échéances">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:text-gray-400">
            <th class="px-3 py-3">Échéance</th>
            <th class="px-3 py-3">Date limite</th>
            <th class="px-3 py-3 text-right">Montant</th>
            <th class="px-3 py-3 text-right">Payé</th>
            <th class="px-3 py-3 text-right">Reste</th>
            <th class="px-3 py-3">Dernier paiement</th>
            <th class="px-3 py-3">Statut</th>
            <th class="px-3 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="lnk in links" :key="lnk.id" class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.03]">
            <td class="px-3 py-3">
              <div class="max-w-[260px] truncate font-medium text-gray-900 dark:text-white" :title="lnk.description">{{ lnk.description || typeLabel(lnk.type) }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ typeLabel(lnk.type) }}</div>
            </td>
            <td class="px-3 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ formatDate(lnk.due_date) ?? '—' }}</td>
            <td class="px-3 py-3 whitespace-nowrap text-right text-gray-900 dark:text-white">{{ formatCurrency(lnk.amount, lnk.currency) }}</td>
            <td class="px-3 py-3 whitespace-nowrap text-right text-gray-900 dark:text-white">{{ formatCurrency(paidFor(lnk), lnk.currency) }}</td>
            <td class="px-3 py-3 whitespace-nowrap text-right font-semibold" :class="remainingFor(lnk) > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-400'">
              {{ formatCurrency(remainingFor(lnk), lnk.currency) }}
            </td>
            <td class="px-3 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ formatDate(lastPaymentFor(lnk)) ?? '—' }}</td>
            <td class="px-3 py-3 whitespace-nowrap">
              <span :class="['rounded-full px-2.5 py-1 text-xs font-medium', statusOf(lnk).cls]">{{ statusOf(lnk).label }}</span>
            </td>
            <td class="px-3 py-3 text-right">
              <button @click="openActions(lnk)" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/[0.05]">Gérer</button>
            </td>
          </tr>
          <tr v-if="!links.length">
            <td class="px-3 py-8 text-center text-gray-500 dark:text-gray-400" colspan="8">
              {{ loading ? 'Chargement…' : 'Aucun lien de paiement pour cette année.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <LinkActionModal v-if="activeLink" :link="activeLink" @close="activeLink = null" @changed="onChanged" />
  </ComponentCard>
</template>

<script setup>
/* filepath: /home/rock/PIEUVRE/Saas-schooling-project/resources/js/components/payment/PaymentLinksCard.vue */
import { ref, onMounted, watch } from 'vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import paymentLinkService from '@/services/paymentLinkService';
import LinkActionModal from '@/components/payment/LinkActionModal.vue';
import { useActiveYearStore } from '@/stores/useActiveYearStore';

const activeYearStore = useActiveYearStore();

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
    const res = await paymentLinkService.index({
      student_id: props.studentId,
      school_year: activeYearStore.activeYear || undefined,
    });
    const list = res.data?.data ?? res.data ?? [];
    // Ordre des échéances : par date limite (les liens sans date à la fin)
    links.value = [...list].sort((a, b) => {
      const da = a.due_date ? new Date(a.due_date).getTime() : Infinity;
      const db = b.due_date ? new Date(b.due_date).getTime() : Infinity;
      return da - db;
    });
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

const remainingFor = (lnk) => Math.max(0, Number(lnk.amount || 0) - paidFor(lnk));

// formate la monnaie avec Intl ; si la devise n'est pas supportée, affiche montant + code
const formatCurrency = (v, currency) => {
  const code = currency || 'XOF';
  try {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: code, maximumFractionDigits: code === 'XOF' ? 0 : 2 }).format(Number(v ?? 0));
  } catch (e) {
    // si la devise n'est pas supportée, retour simple
    return `${Number(v ?? 0).toLocaleString('fr-FR')} ${code}`;
  }
};

const formatDate = (d) => {
  if (!d) return null;
  const dt = new Date(String(d).length === 10 ? `${d}T00:00:00` : d);
  if (isNaN(dt)) return String(d);
  return new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }).format(dt);
};

const TYPE_LABELS = { tuition: 'Scolarité', registration: 'Inscription', other: 'Autres frais' };
const typeLabel = (t) => TYPE_LABELS[t] ?? 'Autres frais';

// Statut lisible : un lien actif dont la date limite est passée est « En retard »
const statusOf = (lnk) => {
  if (lnk.status === 'used' || remainingFor(lnk) <= 0) {
    return { label: 'Réglé', cls: 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400' };
  }
  if (lnk.status === 'expired') {
    return { label: 'Expiré', cls: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' };
  }
  const overdue = lnk.due_date && new Date(`${String(lnk.due_date).slice(0, 10)}T23:59:59`) < new Date();
  if (overdue) {
    return { label: paidFor(lnk) > 0 ? 'Partiel, en retard' : 'En retard', cls: 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400' };
  }
  return paidFor(lnk) > 0
    ? { label: 'Partiel', cls: 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400' }
    : { label: 'À payer', cls: 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' };
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

watch(() => activeYearStore.activeYear, () => {
  fetchLinks();
});

// exposer la méthode fetchLinks pour le parent
defineExpose({ fetchLinks });
</script>

<style scoped></style>