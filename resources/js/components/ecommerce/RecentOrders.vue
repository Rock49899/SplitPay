<template>
  <div
    class="overflow-hidden rounded-2xl border border-slate-200 bg-white px-4 pb-3 pt-4 dark:border-slate-700 dark:bg-slate-800 sm:px-6"
  >
    <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Paiements récents</h3>
        <p v-if="scopeLabel" class="mt-0.5 text-theme-xs text-slate-400 dark:text-slate-300">{{ scopeLabel }}</p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Bouton filtres -->
        <button
          @click="showFilter = !showFilter"
          :class="[
            'inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-theme-sm font-medium shadow-theme-xs transition',
            hasActiveFilters
              ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-600 dark:bg-brand-500/10 dark:text-brand-400'
              : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600',
          ]"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
          </svg>
          Filtrer<span v-if="hasActiveFilters" class="ml-1 font-bold">·</span>
        </button>

        <router-link
          to="/finances"
          class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-slate-700 shadow-theme-xs hover:bg-slate-50 hover:text-slate-800 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600 dark:hover:text-white"
        >
          Voir tout
        </router-link>
      </div>
    </div>

    <!-- Panneau filtres -->
    <Transition name="slide-down">
      <div
        v-if="showFilter"
        class="mb-4 grid grid-cols-2 gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-700/40 sm:grid-cols-3 lg:grid-cols-4"
      >
        <!-- Status -->
        <div>
          <label class="mb-1 block text-theme-xs font-medium text-slate-500 dark:text-slate-300">Statut</label>
          <select v-model="filters.status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-theme-sm text-slate-700 focus:border-brand-500 focus:outline-none dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            <option value="">Tous</option>
            <option value="success">Payé</option>
            <option value="pending">En attente</option>
            <option value="failed">Échoué</option>
          </select>
        </div>

        <!-- Method -->
        <div>
          <label class="mb-1 block text-theme-xs font-medium text-slate-500 dark:text-slate-300">Méthode</label>
          <select v-model="filters.method" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-theme-sm text-slate-700 focus:border-brand-500 focus:outline-none dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            <option value="">Toutes</option>
            <option value="mtn">MTN Mobile Money</option>
            <option value="moov">Moov Money</option>
            <option value="cash">Espèces</option>
            <option value="bank_transfer">Virement bancaire</option>
            <option value="card">Carte</option>
            <option value="cheque">Chèque</option>
          </select>
        </div>

        <!-- Type -->
        <div>
          <label class="mb-1 block text-theme-xs font-medium text-slate-500 dark:text-slate-300">Type</label>
          <select v-model="filters.type" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-theme-sm text-slate-700 focus:border-brand-500 focus:outline-none dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            <option value="">Tous</option>
            <option value="tuition">Scolarité</option>
            <option value="registration">Inscription</option>
            <option value="other">Autre</option>
          </select>
        </div>

        <!-- Branch filter (super_admin_institution only) -->
        <div v-if="isSuperAdminInstitution && annexes.length">
          <label class="mb-1 block text-theme-xs font-medium text-slate-500 dark:text-slate-300">Annexe</label>
          <select v-model="filters.annexe_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-theme-sm text-slate-700 focus:border-brand-500 focus:outline-none dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            <option value="">Toutes</option>
            <option v-for="a in annexes" :key="a.id" :value="a.id">{{ a.name }}</option>
          </select>
        </div>

        <!-- Date from -->
        <div>
          <label class="mb-1 block text-theme-xs font-medium text-slate-500 dark:text-slate-300">Du</label>
          <input type="date" v-model="filters.from" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-theme-sm text-slate-700 focus:border-brand-500 focus:outline-none dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
        </div>

        <!-- Date to -->
        <div>
          <label class="mb-1 block text-theme-xs font-medium text-slate-500 dark:text-slate-300">Au</label>
          <input type="date" v-model="filters.to" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-theme-sm text-slate-700 focus:border-brand-500 focus:outline-none dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
        </div>

        <!-- Actions -->
        <div class="col-span-2 flex items-end gap-2 sm:col-span-3 lg:col-span-4">
          <button
            @click="applyFilters"
            class="rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-brand-600 transition"
          >
            Appliquer
          </button>
          <button
            @click="resetFilters"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-theme-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600 transition"
          >
            Réinitialiser
          </button>
        </div>
      </div>
    </Transition>

    <div class="max-w-full overflow-x-auto custom-scrollbar">
      <!-- Spinner -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="h-6 w-6 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
        </svg>
      </div>

      <!-- Empty -->
      <div v-else-if="!payments.length" class="py-8 text-center text-theme-sm text-slate-400 dark:text-slate-300">
        Aucun paiement trouvé.
      </div>

      <table v-else class="min-w-full">
        <thead>
          <tr class="border-t border-slate-100 dark:border-slate-700">
            <th class="py-3 pr-4 text-left">
              <p class="font-medium text-slate-500 text-theme-xs dark:text-slate-300">Étudiant</p>
            </th>
            <th class="py-3 pr-4 text-left">
              <p class="font-medium text-slate-500 text-theme-xs dark:text-slate-300">Date</p>
            </th>
            <th v-if="isSuperAdminInstitution" class="py-3 pr-4 text-left">
              <p class="font-medium text-slate-500 text-theme-xs dark:text-slate-300">Annexe</p>
            </th>
            <th class="py-3 pr-4 text-left">
              <p class="font-medium text-slate-500 text-theme-xs dark:text-slate-300">Type</p>
            </th>
            <th class="py-3 pr-4 text-left">
              <p class="font-medium text-slate-500 text-theme-xs dark:text-slate-300">Méthode</p>
            </th>
            <th class="py-3 pr-4 text-left">
              <p class="font-medium text-slate-500 text-theme-xs dark:text-slate-300">Montant</p>
            </th>
            <th class="py-3 text-left">
              <p class="font-medium text-slate-500 text-theme-xs dark:text-slate-300">Statut</p>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(p, idx) in payments"
            :key="p.id ?? idx"
            class="border-t border-slate-100 transition hover:bg-slate-50/50 dark:border-slate-700 dark:hover:bg-slate-700/40"
          >
            <!-- Élève -->
            <td class="py-3 pr-4 whitespace-nowrap">
              <p class="font-medium text-slate-800 text-theme-sm dark:text-white">{{ getStudentName(p) }}</p>
              <span class="text-slate-400 text-theme-xs dark:text-slate-300">{{ p.student?.email ?? '' }}</span>
            </td>

            <!-- Date -->
            <td class="py-3 pr-4 whitespace-nowrap">
              <p class="text-slate-500 text-theme-sm dark:text-slate-300">{{ formatDate(p.paid_at ?? p.created_at) }}</p>
            </td>

            <!-- Annexe (super_admin_institution) -->
            <td v-if="isSuperAdminInstitution" class="py-3 pr-4 whitespace-nowrap">
              <p class="text-slate-500 text-theme-sm dark:text-slate-300">{{ p.student?.annexe?.name ?? '–' }}</p>
            </td>

            <!-- Type de paiement -->
            <td class="py-3 pr-4 whitespace-nowrap">
              <span :class="typeClass(p.payment_link?.type)">{{ typeLabel(p.payment_link?.type) }}</span>
            </td>

            <!-- Méthode -->
            <td class="py-3 pr-4 whitespace-nowrap">
              <p class="text-slate-500 text-theme-sm dark:text-slate-300">{{ methodLabel(p.method) }}</p>
            </td>

            <!-- Montant -->
            <td class="py-3 pr-4 whitespace-nowrap">
              <p class="font-medium text-slate-800 text-theme-sm dark:text-white">{{ formatAmount(p.amount) }}</p>
            </td>

            <!-- Statut -->
            <td class="py-3 whitespace-nowrap">
              <span :class="statusClass(p.status)">{{ statusLabel(p.status) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { usePermissions } from '@/composables/usePermissions'

const { isSuperAdminInstitution, isSuperAdminAnnexe, isComptable } = usePermissions()

const payments   = ref([])
const annexes    = ref([])
const loading    = ref(false)
const showFilter = ref(false)

const filters = ref({
  status:    '',
  method:    '',
  type:      '',
  annexe_id: '',
  from:      '',
  to:        '',
})

// ─── Rôle
const scopeLabel = computed(() => {
  if (isSuperAdminInstitution.value) return 'All branches — institution-wide'
  if (isSuperAdminAnnexe.value)      return 'Your branch(es)'
  if (isComptable.value)             return 'Your branch'
  return null
})

const hasActiveFilters = computed(() =>
  Object.values(filters.value).some((v) => v !== '')
)

const loadPayments = async () => {
  loading.value = true
  try {
    const params = { limit: 5 }
    Object.entries(filters.value).forEach(([k, v]) => { if (v !== '') params[k] = v })
    const res = await api.get('admin/payments/recent', { params })
    payments.value = res.data?.data ?? res.data ?? []
  } catch (e) {
    console.error('Failed to load recent payments', e)
    payments.value = []
  } finally {
    loading.value = false
  }
}

const loadAnnexes = async () => {
  if (!isSuperAdminInstitution.value) return
  try {
    const res = await api.get('admin/annexes', { params: { per_page: 100 } })
    annexes.value = res.data?.data ?? res.data ?? []
  } catch {
    annexes.value = []
  }
}

const applyFilters = () => { showFilter.value = false; loadPayments() }
const resetFilters = () => {
  filters.value = { status: '', method: '', type: '', annexe_id: '', from: '', to: '' }
  loadPayments()
}

const getStudentName = (p) =>
  p?.student?.name
  ?? (p?.student?.first_name ? `${p.student.first_name} ${p.student.last_name ?? ''}`.trim() : null)
  ?? p?.student_name
  ?? '—'

const formatDate = (d) => {
  if (!d) return '–'
  try {
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    }).format(new Date(d))
  } catch {
    return String(d)
  }
}

const formatAmount = (a) =>
  a != null
    ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(a)
    : '–'

const STATUS_MAP = {
  success: { label: 'Paid',    cls: 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' },
  pending: { label: 'Pending', cls: 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400' },
  failed:  { label: 'Failed',  cls: 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500' },
}
const statusLabel = (s) => STATUS_MAP[s?.toLowerCase()]?.label ?? s ?? '–'
const statusClass = (s) => [
  'rounded-full px-2 py-0.5 text-theme-xs font-medium',
  STATUS_MAP[s?.toLowerCase()]?.cls ?? 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
]

const TYPE_MAP = {
  tuition:      { label: 'Tuition',      cls: 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400' },
  registration: { label: 'Registration', cls: 'bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400' },
  other:        { label: 'Other',        cls: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' },
}
const typeLabel = (t) => TYPE_MAP[t?.toLowerCase()]?.label ?? (t ?? 'Other')
const typeClass = (t) => [
  'rounded-full px-2 py-0.5 text-theme-xs font-medium',
  TYPE_MAP[t?.toLowerCase()]?.cls ?? 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
]

const METHOD_MAP = {
  mtn: 'MTN Mobile Money', moov: 'Moov Money',
  cash: 'Cash', bank_transfer: 'Bank Transfer', mobile_money: 'Mobile Money', card: 'Card', cheque: 'Cheque',
}
const methodLabel = (m) => METHOD_MAP[m?.toLowerCase()] ?? m ?? '–'

onMounted(() => { loadPayments(); loadAnnexes() })
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active { transition: all 0.2s ease; }
.slide-down-enter-from,
.slide-down-leave-to { opacity: 0; transform: translateY(-8px); }

select option {
  color: #f8fafc;
  background-color: #334155;
}
</style>
