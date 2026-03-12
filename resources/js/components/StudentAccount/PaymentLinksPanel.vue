<template>
  <div>
    <h2 class="text-base font-bold text-gray-800 mb-4">{{ title }}</h2>

    <!-- Filtre statut + compteur -->
    <div class="flex items-center gap-3 mb-4">
      <select
        v-model="filterStatus"
        @change="loadLinks(1)"
        class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 bg-white"
      >
        <option value="">Tous les statuts</option>
        <option value="active">Actifs</option>
        <option value="used">Soldés</option>
        <option value="expired">Expirés</option>
      </select>
      <span class="text-sm text-gray-400 ml-auto">
        {{ pagination ? `${pagination.total ?? links.length} lien(s)` : '' }}
      </span>
    </div>

    <!-- Chargement -->
    <div v-if="loadingLinks" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-gray-200 border-t-blue-500 rounded-full animate-spin"></div>
    </div>

    <!-- Vide -->
    <div v-else-if="links.length === 0" class="text-center py-12 text-gray-400 text-sm">
      Aucun lien de paiement.
    </div>

    <!-- Liste -->
    <div v-else class="space-y-3">
      <div v-for="link in links" :key="link.id" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">

        <!-- En-tête -->
        <div class="flex items-start justify-between mb-3">
          <div>
            <p class="font-semibold text-gray-800 text-sm">
              {{ link.description || (type === 'tuition' ? 'Scolarité' : 'Autres frais') }}
            </p>
            <p v-if="link.due_date" class="text-xs text-gray-400 mt-0.5">
              Échéance : {{ fmtDate(link.due_date) }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <span :class="['text-xs px-2.5 py-1 rounded-full font-medium', statusLabel[link.status]?.cls ?? 'bg-gray-100 text-gray-500']">
              {{ statusLabel[link.status]?.text ?? link.status }}
            </span>
            <!-- Copier le lien -->
            <button
              @click="copyLink(link)"
              :title="copiedId === link.id ? 'Copié !' : 'Copier le lien'"
              class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-blue-600 transition"
            >
              <svg v-if="copiedId !== link.id" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-4 12h6a2 2 0 002-2v-8a2 2 0 00-2-2h-6a2 2 0 00-2 2v8a2 2 0 002 2z"/>
              </svg>
              <svg v-else class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </button>
            <!-- Partager -->
            <button
              v-if="canShare"
              @click="shareLink(link)"
              title="Partager"
              class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-blue-600 transition"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Montants -->
        <div class="grid grid-cols-3 gap-3 mb-3">
          <div class="bg-gray-50 rounded-lg p-2.5">
            <p class="text-xs text-gray-400 mb-0.5">Total</p>
            <p class="text-sm font-bold text-gray-700">{{ fmt(link.amount) }}</p>
          </div>
          <div class="bg-green-50 rounded-lg p-2.5">
            <p class="text-xs text-green-500 mb-0.5">Payé</p>
            <p class="text-sm font-bold text-green-700">{{ fmt(link.amount_paid_success) }}</p>
          </div>
          <div class="bg-orange-50 rounded-lg p-2.5">
            <p class="text-xs text-orange-500 mb-0.5">Restant</p>
            <p class="text-sm font-bold text-orange-700">{{ fmt(link.remaining_success) }}</p>
          </div>
        </div>

        <!-- Barre de progression -->
        <div class="mb-3">
          <div class="flex justify-between text-xs text-gray-400 mb-1">
            <span>Progression</span>
            <span>{{ pct(link) }}%</span>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-2">
            <div
              :class="['h-2 rounded-full transition-all', pct(link) === 100 ? 'bg-green-500' : 'bg-blue-500']"
              :style="{ width: `${pct(link)}%` }"
            ></div>
          </div>
        </div>

        <!-- Tranches -->
        <div v-if="link.installments?.length" class="border-t border-gray-100 pt-3">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
            {{ link.installments.length }} tranche(s)
          </p>
          <div class="space-y-1.5">
            <div v-for="inst in link.installments" :key="inst.id" class="flex items-center justify-between text-xs">
              <span class="text-gray-600">
                T{{ inst.tranche_number }}{{ inst.description ? ` — ${inst.description}` : '' }}
              </span>
              <div class="flex items-center gap-2">
                <span class="text-gray-700 font-medium">{{ fmt(inst.amount) }}</span>
                <span :class="['px-2 py-0.5 rounded-full', instPaid(inst) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                  {{ instPaid(inst) ? '✓' : 'En attente' }}
                </span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2 mt-4">
      <button
        @click="loadLinks(currentPage - 1)"
        :disabled="currentPage <= 1"
        class="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40 hover:bg-gray-50"
      >← Préc.</button>
      <span class="px-3 py-1.5 text-sm text-gray-500">
        Page {{ pagination.current_page }} / {{ pagination.last_page }}
      </span>
      <button
        @click="loadLinks(currentPage + 1)"
        :disabled="currentPage >= pagination.last_page"
        class="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40 hover:bg-gray-50"
      >Suiv. →</button>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import studentAccountService from '../../services/studentAccountService';

const props = defineProps({
  type:     { type: String, required: true },
  title:    { type: String, required: true },
  currency: { type: String, default: 'XOF' },
});

const links        = ref([]);
const loadingLinks = ref(true);
const pagination   = ref(null);
const currentPage  = ref(1);
const filterStatus = ref('');
const copiedId     = ref(null);
const canShare     = !!navigator.share;

const copyLink = async (link) => {
  const url = link.payment_url;
  try {
    await navigator.clipboard.writeText(url);
  } catch {
    const el = document.createElement('textarea');
    el.value = url;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
  }
  copiedId.value = link.id;
  setTimeout(() => { copiedId.value = null; }, 2000);
};

const shareLink = async (link) => {
  try {
    await navigator.share({
      title: link.description || 'Lien de paiement',
      text: `Voici mon lien de paiement SplitPay`,
      url: link.payment_url,
    });
  } catch { /* annulé par l'utilisateur */ }
};

const statusLabel = {
  active:  { text: 'Actif',       cls: 'bg-blue-100 text-blue-700'   },
  used:    { text: 'Soldé',       cls: 'bg-green-100 text-green-700' },
  expired: { text: 'Expiré',      cls: 'bg-red-100 text-red-700'    },
  pending: { text: 'En attente',  cls: 'bg-yellow-100 text-yellow-700' },
};

const fmt = (v) => {
  try {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: props.currency }).format(Number(v ?? 0));
  } catch {
    return `${Number(v ?? 0).toLocaleString('fr-FR')} ${props.currency}`;
  }
};

const fmtDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '';

const pct = (link) =>
  link.amount > 0 ? Math.min(100, Math.round((link.amount_paid_success / link.amount) * 100)) : 0;

const instPaid = (inst) => {
  const paid = inst.payments?.reduce((s, p) => s + Number(p.amount), 0) ?? 0;
  return inst.status === 'paid' || paid >= Number(inst.amount);
};

const loadLinks = async (page = 1) => {
  loadingLinks.value = true;
  try {
    const res = await studentAccountService.getPaymentLinks({
      type: props.type,
      status: filterStatus.value || undefined,
      page,
    });
    links.value      = res.data.data;
    pagination.value = res.data.meta ?? res.data;
    currentPage.value = page;
  } catch (e) {
    console.error(e);
  } finally {
    loadingLinks.value = false;
  }
};

onMounted(() => loadLinks(1));
</script>
