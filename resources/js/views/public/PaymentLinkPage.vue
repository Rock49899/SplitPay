<template>
  <div class="min-h-screen bg-gray-50 flex items-start justify-center py-10 px-4">
    <div class="w-full max-w-lg">

      <!-- En-tête : établissement -->
      <div class="mb-6 flex items-center gap-3">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl"
          :class="paymentInstitutionLogo ? 'bg-white ring-1 ring-gray-200' : 'bg-brand-500 text-white'">
          <img v-if="paymentInstitutionLogo" :src="paymentInstitutionLogo" :alt="paymentInstitutionName" class="h-full w-full object-contain p-1.5" />
          <span v-else class="text-sm font-bold">{{ institutionInitials }}</span>
        </span>
        <div class="min-w-0">
          <p class="truncate text-base font-bold text-gray-900">{{ paymentInstitutionName }}</p>
          <p class="flex items-center gap-1.5 text-xs text-gray-500">
            <svg class="h-3.5 w-3.5 text-brand-500" viewBox="0 0 20 20" fill="none" aria-hidden="true">
              <path d="M10 2.5 4 5v4.5c0 3.7 2.6 6.6 6 7.5 3.4-.9 6-3.8 6-7.5V5l-6-2.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
              <path d="m7.5 10 1.8 1.8 3.2-3.6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Paiement sécurisé des frais de scolarité
          </p>
        </div>
      </div>

      <!-- Chargement -->
      <div v-if="loading" class="text-center py-12 text-gray-500">
        Chargement du lien de paiement…
      </div>

      <!-- Erreur -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-5 text-center">
        <p class="font-semibold">Lien invalide ou expiré</p>
        <p class="text-sm mt-1">{{ error }}</p>
      </div>

      <!-- État : paiement confirmé -->
      <div v-else-if="paymentInitiated && paymentConfirmed" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <h2 class="text-xl font-semibold text-green-700 mb-2">Paiement confirmé</h2>
        <p class="text-gray-600 text-sm mb-4">Votre paiement a bien été reçu et enregistré.</p>
        <div class="bg-gray-50 rounded-lg p-4 text-left text-sm text-gray-700 space-y-1">
          <div><span class="text-gray-500">Référence :</span> <span class="font-mono font-medium">{{ paymentReference }}</span></div>
          <div><span class="text-gray-500">Montant :</span> <strong>{{ fmt(form.amount, link.currency) }}</strong></div>
        </div>
      </div>

      <!-- État : paiement échoué -->
      <div v-else-if="paymentInitiated && paymentFailed" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>
        <h2 class="text-xl font-semibold text-red-700 mb-2">Paiement échoué</h2>
        <p class="text-gray-600 text-sm mb-4">La transaction a été refusée ou annulée.</p>
        <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600">
          <span class="text-gray-500">Référence :</span> <span class="font-mono">{{ paymentReference }}</span>
        </div>
        <p class="text-xs text-gray-400 mt-3">Contactez votre établissement avec cette référence.</p>
      </div>

      <!-- État : en attente de confirmation (polling en cours) -->
      <div v-else-if="paymentInitiated" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
        <!-- Spinner animé -->
        <div class="w-16 h-16 rounded-full border-4 border-gray-200 border-t-blue-500 animate-spin mx-auto mb-5"></div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">En attente de confirmation…</h2>
        <p class="text-gray-600 text-sm mb-4">
          Une demande a été envoyée à <strong>{{ form.method === 'mtn' ? 'MTN Mobile Money' : 'Moov Money' }}</strong>.<br>
          Acceptez la notification sur votre téléphone (<strong>+{{ fullPhone }}</strong>) et entrez votre code PIN.
        </p>
        <div class="bg-blue-50 rounded-lg p-4 text-left text-sm text-gray-700 space-y-1">
          <div><span class="text-gray-500">Référence :</span> <span class="font-mono font-medium">{{ paymentReference }}</span></div>
          <div><span class="text-gray-500">Montant :</span> <strong>{{ fmt(form.amount, link.currency) }}</strong></div>
        </div>
        <p class="text-xs text-gray-400 mt-4">Cette page se met à jour automatiquement. Ne la fermez pas.</p>
      </div>

      <!-- Formulaire de paiement -->
      <template v-else>

        <!-- Récapitulatif : étudiant + montant restant -->
        <div class="mb-4 overflow-hidden rounded-2xl bg-brand-600 text-white shadow-theme-md">
          <div class="p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="text-xs font-medium text-brand-100">Paiement pour</p>
                <p class="mt-0.5 truncate text-lg font-semibold">{{ studentName }}</p>
                <p class="text-xs text-brand-100/80">Matricule {{ link.student?.matricule ?? '—' }}</p>
              </div>
              <span class="shrink-0 rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium">{{ linkTypeLabel }}</span>
            </div>

            <div class="mt-5">
              <p class="text-xs font-medium text-brand-100">Reste à payer</p>
              <p class="mt-0.5 text-3xl font-bold tracking-tight">{{ fmt(remaining, link.currency) }}</p>
            </div>

            <div class="mt-4">
              <div class="h-2 w-full rounded-full bg-white/15">
                <div class="h-2 rounded-full bg-white transition-all duration-700" :style="{ width: paidPercent + '%' }"></div>
              </div>
              <div class="mt-2 flex justify-between text-xs text-brand-100/90">
                <span>Déjà payé : {{ fmt(paidOnLink, link.currency) }}</span>
                <span>Total : {{ fmt(link.amount, link.currency) }}</span>
              </div>
            </div>
          </div>
          <p v-if="link.description" class="border-t border-white/10 bg-black/10 px-5 py-3 text-xs text-brand-50">
            {{ link.description }}
          </p>
        </div>

        <!-- Formulaire -->
        <div class="bg-white rounded-2xl shadow-theme-sm border border-gray-200 p-5">
          <h2 class="text-base font-semibold text-gray-900 mb-4">Payer avec Mobile Money</h2>

          <!-- Choix réseau -->
          <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-2">Opérateur</label>
            <div class="grid grid-cols-2 gap-3">
              <button
                type="button"
                @click="form.method = 'mtn'"
                :aria-pressed="form.method === 'mtn'"
                :class="[
                  'flex items-center gap-3 rounded-xl border-2 px-3 py-3 text-left text-sm font-semibold transition',
                  form.method === 'mtn'
                    ? 'border-[#ffcb05] bg-[#fffbe6] text-gray-900'
                    : 'border-gray-200 text-gray-600 hover:border-gray-300'
                ]"
              >
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#ffcb05] text-[11px] font-extrabold text-gray-900">MTN</span>
                <span>MTN<br><span class="text-xs font-normal text-gray-500">Mobile Money</span></span>
              </button>
              <button
                type="button"
                @click="form.method = 'moov'"
                :aria-pressed="form.method === 'moov'"
                :class="[
                  'flex items-center gap-3 rounded-xl border-2 px-3 py-3 text-left text-sm font-semibold transition',
                  form.method === 'moov'
                    ? 'border-[#0068b3] bg-[#eef5fb] text-gray-900'
                    : 'border-gray-200 text-gray-600 hover:border-gray-300'
                ]"
              >
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#0068b3] text-[10px] font-extrabold text-white">MOOV</span>
                <span>Moov<br><span class="text-xs font-normal text-gray-500">Money</span></span>
              </button>
            </div>
          </div>

          <!-- Montant -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Montant à payer <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                type="number"
                v-model.number="form.amount"
                :max="remaining"
                min="1"
                step="1"
                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 pr-16 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none"
                :class="{'border-red-400': form.amount > remaining}"
                placeholder="0"
              />
              <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">{{ link.currency ?? 'XOF' }}</span>
            </div>
            <p v-if="form.amount > remaining" class="text-xs text-red-500 mt-1">
              Le montant dépasse le restant dû ({{ fmt(remaining, link.currency) }}).
            </p>
          </div>

          <!-- Numéro de téléphone -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Numéro de téléphone <span class="text-red-500">*</span>
              <span class="font-normal text-gray-400 text-xs ml-1">(numéro {{ form.method === 'mtn' ? 'MTN' : 'Moov' }} utilisé pour le paiement)</span>
            </label>
            <div class="flex gap-2">
              <!-- Sélecteur indicatif pays -->
              <select
                v-model="form.country_code"
                class="border border-gray-200 rounded-lg px-2 py-2.5 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none bg-white"
                style="min-width:120px"
              >
                <option v-for="c in countries" :key="c.code" :value="c.dial">
                  {{ c.flag }} {{ c.dial }}
                </option>
              </select>
              <!-- Numéro local -->
              <input
                type="tel"
                v-model="form.payer_phone_local"
                @input="form.payer_phone_local = form.payer_phone_local.replace(/\D/g, '')"
                :placeholder="form.country_code === '229' ? '0197010101' : '0XXXXXXXXX'"
                :class="[
                  'flex-1 border rounded-lg px-3 py-2.5 text-sm focus:ring-2 outline-none',
                  phoneError
                    ? 'border-red-400 focus:ring-red-200 focus:border-red-400'
                    : 'border-gray-200 focus:ring-blue-200 focus:border-blue-400'
                ]"
              />
            </div>
            <p class="text-xs text-gray-400 mt-1">
              Numéro complet : <span class="font-mono">{{ fullPhone || '—' }}</span>
            </p>
            <p v-if="phoneError" class="text-xs text-red-500 mt-1">{{ phoneError }}</p>
          </div>

          <!-- Email (optionnel) -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Email <span class="text-gray-400 text-xs font-normal">(optionnel)</span>
            </label>
            <input
              type="email"
              v-model="form.payer_email"
              placeholder="votre@email.com"
              class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none"
            />
          </div>

          <!-- Case optionnelle : informations personnelles -->
          <div class="mb-5">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input type="checkbox" v-model="showPersonalInfo" class="w-4 h-4 rounded text-blue-600" />
              <span class="text-sm text-gray-600">Ajouter mes informations personnelles</span>
            </label>

            <div v-if="showPersonalInfo" class="mt-3 grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">Prénom</label>
                <input
                  type="text"
                  v-model="form.payer_first_name"
                  placeholder="Prénom"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Nom</label>
                <input
                  type="text"
                  v-model="form.payer_last_name"
                  placeholder="Nom de famille"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none"
                />
              </div>
            </div>
          </div>

          <!-- Erreur soumission -->
          <div v-if="submitError" class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
            {{ submitError }}
          </div>

          <!-- Bouton de paiement -->
          <button
            @click="submit"
            :disabled="submitting || !canSubmit"
            class="w-full py-3 rounded-xl text-white font-semibold text-sm transition"
            :class="canSubmit && !submitting
              ? 'bg-blue-600 hover:bg-blue-700 active:bg-blue-800'
              : 'bg-gray-300 cursor-not-allowed'"
          >
            <span v-if="submitting" class="flex items-center justify-center gap-2">
              <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              Traitement en cours…
            </span>
            <span v-else>Procéder au paiement</span>
          </button>

          <p class="text-center text-xs text-gray-400 mt-3">
            Paiement sécurisé via PayPlus Africa · {{ form.method === 'mtn' ? 'MTN Mobile Money' : 'Moov Money' }}
          </p>
        </div>

      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import paymentLinkService from '@/services/paymentLinkService';
import paymentService from '@/services/paymentService';
import { useInstitutionBrand } from '@/composables/useInstitutionBrand'

const route = useRoute();
const token = route.params.token;

const loading           = ref(true);
const error             = ref(null);
const submitError       = ref(null);
const link              = ref(null);
const submitting        = ref(false);
const paymentInitiated  = ref(false);
const paymentReference  = ref('');
const paymentConfirmed  = ref(false);  // polling → statut success
const paymentFailed     = ref(false);  // polling → statut failed
const pollTimer         = ref(null);
const { brandName, brandLogoUrl, loadBrand } = useInstitutionBrand()

const POLL_INTERVAL_MS = 5000;   // vérifier toutes les 5 secondes
const POLL_MAX_TRIES   = 24;     // abandon après 2 minutes (24 × 5s)

const startPolling = (reference) => {
  let tries = 0;
  pollTimer.value = setInterval(async () => {
    tries++;
    try {
      const res = await paymentService.checkStatus(reference);
      const status = res.data?.status;
      if (status === 'success') {
        clearInterval(pollTimer.value);
        paymentConfirmed.value = true;
      } else if (status === 'failed') {
        clearInterval(pollTimer.value);
        paymentFailed.value = true;
      }
    } catch (_) { /* silent — on continue */ }
    if (tries >= POLL_MAX_TRIES) {
      clearInterval(pollTimer.value); // délai dépassé, on arrête
    }
  }, POLL_INTERVAL_MS);
};

onUnmounted(() => {
  if (pollTimer.value) clearInterval(pollTimer.value);
});
const showPersonalInfo  = ref(false);

// Liste des pays supportés (extensible)
const countries = [
  { code: 'BJ', flag: '🇧🇯', name: 'Bénin',        dial: '229'  },
  { code: 'TG', flag: '🇹🇬', name: 'Togo',         dial: '228'  },
  { code: 'CI', flag: '🇨🇮', name: 'Côte d\'Ivoire', dial: '225' },
  { code: 'SN', flag: '🇸🇳', name: 'Sénégal',       dial: '221'  },
  { code: 'ML', flag: '🇲🇱', name: 'Mali',          dial: '223'  },
  { code: 'BF', flag: '🇧🇫', name: 'Burkina Faso',  dial: '226'  },
  { code: 'NE', flag: '🇳🇪', name: 'Niger',         dial: '227'  },
  { code: 'GN', flag: '🇬🇳', name: 'Guinée',        dial: '224'  },
  { code: 'CM', flag: '🇨🇲', name: 'Cameroun',      dial: '237'  },
  { code: 'GH', flag: '🇬🇭', name: 'Ghana',         dial: '233'  },
];

const form = ref({
  method:            'mtn',
  amount:            null,
  country_code:      '229',   // indicatif par défaut : Bénin
  payer_phone_local: '',      // numéro local sans indicatif
  payer_email:       '',
  payer_first_name:  '',
  payer_last_name:   '',
});

const successfulPaymentsForLink = computed(() => {
  if (!link.value) return [];

  const byId = new Map();

  (link.value.payments ?? []).forEach((p) => {
    if (p?.status === 'success') {
      byId.set(p.id ?? `${p.reference}-${p.amount}-${p.paid_at}`, p);
    }
  });

  (link.value.installments ?? []).forEach((inst) => {
    (inst?.payments ?? []).forEach((p) => {
      if (p?.status === 'success') {
        byId.set(p.id ?? `${p.reference}-${p.amount}-${p.paid_at}`, p);
      }
    });
  });

  return Array.from(byId.values());
});

const paidOnLink = computed(() => {
  return successfulPaymentsForLink.value.reduce((sum, p) => sum + Number(p?.amount ?? 0), 0);
});

const remaining = computed(() => {
  if (!link.value) return 0;
  const total = Number(link.value.amount ?? 0);
  return Math.max(0, total - paidOnLink.value);
});

const studentName = computed(() => {
  const s = link.value?.student;
  if (!s) return '—';
  return [s.first_name, s.last_name].filter(Boolean).join(' ') || '—';
});

const fullPhone = computed(() => {
  const local = form.value.payer_phone_local.trim();
  if (!local) return '';
  return form.value.country_code + local;
});

const paymentInstitution = computed(() => link.value?.student?.annexe?.institution ?? null)

const paymentInstitutionName = computed(() => {
  return paymentInstitution.value?.name || brandName.value || 'SplitPay'
})

const paymentInstitutionDisplayName = computed(() => {
  const name = paymentInstitutionName.value || 'SplitPay'
  return name.length > 22 ? `${name.slice(0, 21)}…` : name
})

const paymentInstitutionLogo = computed(() => {
  const logo = paymentInstitution.value?.logo
  if (logo) return `/storage/${logo}`
  return brandLogoUrl.value || ''
})

const institutionInitials = computed(() => {
  const words = (paymentInstitutionName.value || 'SplitPay').trim().split(/\s+/).filter(Boolean)
  return (words.length > 1 ? words[0][0] + words[1][0] : words[0].slice(0, 2)).toUpperCase()
})

const linkTypeLabel = computed(() => ({
  tuition: 'Scolarité',
  registration: 'Inscription',
}[link.value?.type] ?? 'Frais'))

const paidPercent = computed(() => {
  const total = Number(link.value?.amount ?? 0)
  if (total <= 0) return 0
  return Math.min(100, Math.round((paidOnLink.value / total) * 100))
})

// Validation 8 à 15 chiffres au total 
const phoneError = computed(() => {
  const local = form.value.payer_phone_local.trim();
  if (!local) return null;
  if (!/^\d+$/.test(local)) return 'Chiffres uniquement.';
  const full = fullPhone.value;
  if (full.length < 8)  return `Numéro trop court (${full.length} chiffres).`;
  if (full.length > 15) return `Numéro trop long (${full.length} chiffres, max 15).`;
  return null;
});

const canSubmit = computed(() =>
  form.value.amount > 0 &&
  form.value.amount <= remaining.value &&
  fullPhone.value.length >= 8 &&
  phoneError.value === null
);

const fmt = (v, currency = 'XOF') => {
  try {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: currency ?? 'XOF' }).format(Number(v ?? 0));
  } catch {
    return `${Number(v ?? 0).toLocaleString('fr-FR')} ${currency ?? 'XOF'}`;
  }
};

onMounted(async () => {
  loadBrand(true)
  try {
    const res = await paymentLinkService.publicShow(token);
    link.value = res.data ?? res;
    // pré-remplir le montant avec le restant dû (calculé après assignation du link)
    form.value.amount = remaining.value > 0 ? remaining.value : Number(link.value.amount ?? 0);
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Lien de paiement introuvable.';
  } finally {
    loading.value = false;
  }
});

const submit = async () => {
  submitError.value = null;

  if (!form.value.payer_phone_local.trim()) {
    submitError.value = 'Le numéro de téléphone est obligatoire.';
    return;
  }
  if (phoneError.value) {
    submitError.value = phoneError.value;
    return;
  }
  if (!form.value.amount || form.value.amount <= 0) {
    submitError.value = 'Veuillez saisir un montant valide.';
    return;
  }
  if (form.value.amount > remaining.value) {
    submitError.value = `Le montant ne peut pas dépasser le restant dû (${fmt(remaining.value, link.value?.currency)}).`;
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      token,
      amount:           form.value.amount,
      method:           form.value.method,
      payer_phone:      fullPhone.value,   // format E.164 : indicatif + local sans 0
      payer_email:      form.value.payer_email.trim() || undefined,
      payer_first_name: showPersonalInfo.value ? (form.value.payer_first_name.trim() || undefined) : undefined,
      payer_last_name:  showPersonalInfo.value ? (form.value.payer_last_name.trim()  || undefined) : undefined,
    };

    const res = await paymentService.createPublicCheckout(payload);
    paymentReference.value = res.data?.reference ?? '';
    paymentInitiated.value = true;
    // Démarrer le polling pour mettre à jour le statut automatiquement
    startPolling(paymentReference.value);

  } catch (e) {
    submitError.value = e.response?.data?.message || e.message || 'Une erreur est survenue. Réessayez.';
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped></style>
