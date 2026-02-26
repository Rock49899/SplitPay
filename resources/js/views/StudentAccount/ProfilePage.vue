<template>
  <div class="min-h-screen bg-gray-50">

    <!-- nav bar étudiant -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-10">
      <div class="max-w-5xl mx-auto px-4 flex items-center justify-between h-14">
        <div class="flex items-center gap-2.5">
          <!-- <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 14l9-5-9-5-9 5 9 5zm0 7l-9-5 9-5 9 5-9 5z"/>
            </svg>
          </div> -->
          <span class="font-bold text-gray-800 text-sm">SplitPay</span>
          <span class="text-gray-300 text-sm">|</span>
          <span class="text-sm text-gray-500">Espace Étudiant</span>
        </div>
        <button @click="logout" class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-red-600 transition">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          Déconnexion
        </button>
      </div>
    </nav>

    <!-- Contenu -->
    <div class="max-w-5xl mx-auto px-4 py-6">

      <!-- Chargement -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-gray-400">
        <div class="w-10 h-10 border-4 border-gray-200 border-t-blue-500 rounded-full animate-spin mb-4"></div>
        Chargement de votre profil…
      </div>

      <!-- Erreur session -->
      <div v-else-if="sessionError" class="max-w-md mx-auto text-center py-16">
        <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
          </svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Session expirée</h2>
        <p class="text-sm text-gray-500 mb-5">{{ sessionError }}</p>
        <button @click="goLogin" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700">
          Se reconnecter
        </button>
      </div>

      <template v-else-if="student">

        <!-- En-tête profil -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-5 flex flex-col sm:flex-row sm:items-center gap-4">
          <!-- Avatar or initials fallback -->
          <div class="w-14 h-14 rounded-2xl overflow-hidden shrink-0 bg-blue-600 flex items-center justify-center">
            <img
              v-if="student.avatar_url"
              :src="student.avatar_url"
              :alt="studentName"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-white font-bold text-xl">{{ initials }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <h1 class="text-lg font-bold text-gray-800 truncate">{{ studentName }}</h1>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500 mt-0.5">
              <span>{{ student.matricule }}</span>
              <span v-if="student.class">· {{ student.class }}</span>
              <span v-if="student.school_year">· {{ student.school_year }}</span>
              <span v-if="student.annexe?.name">· {{ student.annexe.name }}</span>
            </div>
          </div>
          <div class="shrink-0">
            <span :class="['px-3 py-1 rounded-full text-xs font-semibold', student.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
              {{ student.status === 'active' ? 'Actif' : student.status }}
            </span>
          </div>
        </div>

        <!-- Onglets navigation -->
        <div class="flex gap-1 bg-white border border-gray-100 rounded-2xl p-1 mb-5 shadow-sm overflow-x-auto">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition whitespace-nowrap',
              activeTab === tab.id
                ? 'bg-blue-600 text-white shadow'
                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>

        <div v-if="activeTab === 'overview'">

          <!-- Cartes financières -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <FinanceCard label="Total scolarité"  :value="summary.tuition_total"     color="blue"   :currency="currency" />
            <FinanceCard label="Scolarité payée"  :value="summary.tuition_paid"      color="green"  :currency="currency" />
            <FinanceCard label="Restant scolarité" :value="summary.tuition_remaining" color="orange" :currency="currency" />
            <FinanceCard label="Total payé (tout)" :value="summary.total_paid"        color="purple" :currency="currency" />
          </div>

          <div v-if="summary.other_total > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
            <FinanceCard label="Autres frais (total)"  :value="summary.other_total"     color="gray"   :currency="currency" />
            <FinanceCard label="Autres frais (payé)"   :value="summary.other_paid"      color="green"  :currency="currency" />
            <FinanceCard label="Autres frais (restant)" :value="summary.other_remaining" color="orange" :currency="currency" />
          </div>

          <!-- Infos personnelles -->
          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Informations personnelles</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <InfoRow label="Prénom"         :value="student.first_name" />
              <InfoRow label="Nom"            :value="student.last_name" />
              <InfoRow label="Matricule"      :value="student.matricule" />
              <InfoRow label="Email"          :value="student.email" />
              <InfoRow label="Téléphone"      :value="student.phone" />
              <InfoRow label="Classe"         :value="student.class" />
              <InfoRow label="Année scolaire" :value="student.school_year" />
              <InfoRow label="Établissement"  :value="student.annexe?.institution?.name" />
              <InfoRow label="Annexe"         :value="student.annexe?.name" />
            </div>
          </div>
        </div>

        <!-- tab scolari -->
        <div v-else-if="activeTab === 'tuition'">
          <PaymentLinksPanel
            type="tuition"
            title="Liens de scolarité"
            :currency="currency"
          />
        </div>


        <!-- tab autres frais -->
        <div v-else-if="activeTab === 'other'">
          <PaymentLinksPanel
            type="other"
            title="Autres frais"
            :currency="currency"
          />
        </div>

        <!-- tab historique -->
        <div v-else-if="activeTab === 'history'">
          <HistoryPanel :currency="currency" />
        </div>

      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import studentAccountService from '../../services/studentAccountService';
import FinanceCard       from '../../components/StudentAccount/FinanceCard.vue';
import InfoRow           from '../../components/StudentAccount/InfoRow.vue';
import PaymentLinksPanel from '../../components/StudentAccount/PaymentLinksPanel.vue';
import HistoryPanel      from '../../components/StudentAccount/HistoryPanel.vue';

const router = useRouter();

const loading      = ref(true);
const sessionError = ref(null);
const student      = ref(null);
const summary      = ref({});
const activeTab    = ref('overview');

const tabs = [
  { id: 'overview', label: 'Aperçu' },
  { id: 'tuition',  label: 'Scolarité'},
  { id: 'other',    label: 'Autres frais'},
  { id: 'history',  label: 'Historique'},
];

const studentName = computed(() =>
  [student.value?.first_name, student.value?.last_name].filter(Boolean).join(' ')
  || student.value?.matricule || '—'
);

const initials = computed(() => {
  const f = student.value?.first_name?.[0] ?? '';
  const l = student.value?.last_name?.[0] ?? '';
  return (f + l).toUpperCase() || '?';
});

const currency = computed(() => student.value?.currency ?? 'XOF');

const goLogin = () => {
  studentAccountService.removeToken();
  router.push({ name: 'StudentLogin' });
};

const logout = () => goLogin();

onMounted(async () => {
  if (!studentAccountService.isAuthenticated()) {
    goLogin();
    return;
  }
  try {
    const res     = await studentAccountService.getProfile();
    student.value = res.data.student;
    summary.value = res.data.summary;
  } catch (e) {
    sessionError.value = e.response?.status === 401
      ? 'Votre session a expiré. Veuillez vous reconnecter.'
      : (e.response?.data?.message || 'Impossible de charger le profil.');
  } finally {
    loading.value = false;
  }
});
</script>
