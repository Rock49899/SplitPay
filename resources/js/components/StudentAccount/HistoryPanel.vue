<template>
  <div>
    <h2 class="text-base font-bold text-gray-800 mb-4">Historique des paiements</h2>

    <!-- Filtres -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
      <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Filtres</p>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <select v-model="filters.type" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 bg-white">
          <option value="">Toutes catégories</option>
          <option value="tuition">Scolarité</option>
          <option value="other">Autres</option>
        </select>
        <select v-model="filters.method" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 bg-white">
          <option value="">Tous opérateurs</option>
          <option value="mtn">MTN MoMo</option>
          <option value="moov">Moov Money</option>
        </select>
        <input type="date" v-model="filters.from" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 bg-white" placeholder="Du" />
        <input type="date" v-model="filters.to"   class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 bg-white" placeholder="Au" />
      </div>
      <div class="flex gap-2 mt-3">
        <button @click="applyFilters" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Filtrer</button>
        <button @click="resetFilters" class="border border-gray-200 text-gray-500 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Réinitialiser</button>
        <span v-if="pagination" class="ml-auto text-sm text-gray-400 self-center">
          {{ pagination.total ?? payments.length }} paiement(s)
        </span>
      </div>
    </div>

    <!-- Chargement -->
    <div v-if="loadingPay" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-gray-200 border-t-blue-500 rounded-full animate-spin"></div>
    </div>

    <!-- Vide -->
    <div v-else-if="payments.length === 0" class="text-center py-12 text-gray-400 text-sm bg-white rounded-2xl border border-gray-100">
      Aucun paiement confirmé.
    </div>

    <!-- Liste -->
    <div v-else class="space-y-2">
      <div
        v-for="pay in payments" :key="pay.id"
        class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4 flex items-center gap-4"
      >
        <!-- Icône opérateur -->
        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0', pay.method === 'mtn' ? 'bg-yellow-50' : 'bg-blue-50']">
          {{ pay.method === 'mtn' ? '🟡' : '🔵' }}
        </div>

        <!-- Détails -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-800 truncate">
            {{ pay.payment_link?.description || typeLabel[pay.payment_link?.type] || '—' }}
          </p>
          <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-400 mt-0.5">
            <span>Réf. {{ pay.reference }}</span>
            <span v-if="pay.installment">· T{{ pay.installment.tranche_number }}</span>
            <span>· {{ methodLabel[pay.method] ?? pay.method }}</span>
          </div>
        </div>

        <!-- Montant + date -->
        <div class="text-right shrink-0">
          <p class="text-sm font-bold text-gray-800">{{ fmt(pay.amount) }}</p>
          <p class="text-xs text-gray-400 mt-0.5">{{ fmtDate(pay.paid_at) }}</p>
        </div>

        <!-- Badge -->
        <span class="bg-green-100 text-green-700 text-xs px-2.5 py-1 rounded-full font-medium shrink-0">✓</span>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="!loadingPay && pagination && pagination.last_page > 1" class="flex justify-center gap-2 mt-4">
      <button @click="goPage(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40 hover:bg-gray-50">← Préc.</button>
      <span class="px-3 py-1.5 text-sm text-gray-500">Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
      <button @click="goPage(currentPage + 1)" :disabled="currentPage >= pagination.last_page" class="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40 hover:bg-gray-50">Suiv. →</button>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import studentAccountService from '../../services/studentAccountService';

const props = defineProps({
  currency: { type: String, default: 'XOF' },
});

const payments    = ref([]);
const loadingPay  = ref(true);
const pagination  = ref(null);
const currentPage = ref(1);
const filters     = ref({ type: '', from: '', to: '', method: '' });

const methodLabel = { mtn: 'MTN MoMo', moov: 'Moov Money' };
const typeLabel   = { tuition: 'Scolarité', other: 'Autre' };

const fmt = (v) => {
  try {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: props.currency }).format(Number(v ?? 0));
  } catch {
    return `${Number(v ?? 0).toLocaleString('fr-FR')} ${props.currency}`;
  }
};

const fmtDate = (d) => d
  ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  : '—';

const loadPayments = async (page = 1) => {
  loadingPay.value = true;
  try {
    const params = { page, ...filters.value };
    Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
    const res = await studentAccountService.getPayments(params);
    payments.value    = res.data.data;
    pagination.value  = res.data.meta ?? res.data;
    currentPage.value = page;
  } catch (e) {
    console.error(e);
  } finally {
    loadingPay.value = false;
  }
};

const applyFilters = () => loadPayments(1);
const resetFilters = () => { filters.value = { type: '', from: '', to: '', method: '' }; loadPayments(1); };
const goPage = (p) => { if (p >= 1) loadPayments(p); };

onMounted(() => loadPayments(1));
</script>

<style scoped></style>
