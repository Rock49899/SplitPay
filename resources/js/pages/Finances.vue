<template>
  <AppLayout>
    <div class="p-4 sm:p-6 space-y-6">

      <!-- Titre -->
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Finances — Historique</h1>
        <p v-if="scopeLabel" class="mt-0.5 text-sm text-gray-400 dark:text-gray-500">{{ scopeLabel }}</p>
      </div>

      <!-- Filtres -->
      <div class="grid grid-cols-2 gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:grid-cols-3 lg:grid-cols-5">
        <div>
          <label class="filter-label">Statut</label>
          <select v-model="filters.status" class="filter-select">
            <option value="">Tous</option>
            <option value="success">Payé</option>
            <option value="pending">En attente</option>
            <option value="failed">Échoué</option>
          </select>
        </div>
        <div>
          <label class="filter-label">Méthode</label>
          <select v-model="filters.method" class="filter-select">
            <option value="">Toutes</option>
            <option value="mtn">MTN Mobile Money</option>
            <option value="moov">Moov Money</option>
            <option value="cash">Espèces</option>
            <option value="bank_transfer">Virement</option>
            <option value="card">Carte</option>
          </select>
        </div>
        <div>
          <label class="filter-label">Type</label>
          <select v-model="filters.type" class="filter-select">
            <option value="">Tous</option>
            <option value="tuition">Scolarité</option>
            <option value="registration">Inscription</option>
            <option value="other">Autre</option>
          </select>
        </div>
        <div v-if="isSuperAdminInstitution && annexes.length">
          <label class="filter-label">Annexe</label>
          <select v-model="filters.annexe_id" class="filter-select">
            <option value="">Toutes</option>
            <option v-for="a in annexes" :key="a.id" :value="a.id">{{ a.name }}</option>
          </select>
        </div>
        <div>
          <label class="filter-label">Du</label>
          <input type="date" v-model="filters.from" class="filter-select" />
        </div>
        <div>
          <label class="filter-label">Au</label>
          <input type="date" v-model="filters.to" class="filter-select" />
        </div>
        <div>
          <label class="filter-label">Recherche</label>
          <input type="text" v-model="filters.q" placeholder="Nom, réf…" class="filter-select" />
        </div>
        <div class="col-span-2 flex items-end gap-2 sm:col-span-3 lg:col-span-5">
          <button @click="applyFilters" class="btn-primary">Appliquer</button>
          <button @click="resetFilters" class="btn-secondary">Réinitialiser</button>
        </div>
      </div>

      <!-- Tableau -->
      <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">

          <!-- Chargement -->
          <div v-if="loading" class="flex justify-center py-12">
            <svg class="h-7 w-7 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
          </div>

          <table v-else class="min-w-full">
            <thead class="bg-gray-50 dark:bg-white/[0.02]">
              <tr>
                <th class="px-4 py-3 text-left text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Élève</th>
                <th class="px-4 py-3 text-left text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Date</th>
                <th v-if="isSuperAdminInstitution" class="px-4 py-3 text-left text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Annexe</th>
                <th class="px-4 py-3 text-left text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Type</th>
                <th class="px-4 py-3 text-left text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Méthode</th>
                <th class="px-4 py-3 text-left text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Montant</th>
                <th class="px-4 py-3 text-left text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Réf.</th>
                <th class="px-4 py-3 text-left text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!payments.length">
                <td :colspan="isSuperAdminInstitution ? 8 : 7" class="py-10 text-center text-gray-400 dark:text-gray-500 text-theme-sm">
                  Aucun paiement trouvé.
                </td>
              </tr>
              <tr
                v-for="(p, idx) in payments"
                :key="p.id ?? idx"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition"
              >
                <td class="px-4 py-3 whitespace-nowrap">
                  <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ getStudentName(p) }}</p>
                  <span class="text-gray-400 text-theme-xs">{{ p.student?.email ?? '' }}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-theme-sm dark:text-gray-400">
                  {{ formatDate(p.paid_at ?? p.created_at) }}
                </td>
                <td v-if="isSuperAdminInstitution" class="px-4 py-3 whitespace-nowrap text-gray-500 text-theme-sm dark:text-gray-400">
                  {{ p.student?.annexe?.name ?? '–' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span :class="typeClass(p.payment_link?.type)">{{ typeLabel(p.payment_link?.type) }}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-theme-sm dark:text-gray-400">
                  {{ methodLabel(p.method) }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-800 text-theme-sm dark:text-white/80">
                  {{ formatAmount(p.amount) }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-400 text-theme-xs font-mono">
                  {{ p.reference ?? '–' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span :class="statusClass(p.status)">{{ statusLabel(p.status) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="meta && meta.last_page > 1"
          class="flex items-center justify-between border-t border-gray-100 px-4 py-3 dark:border-gray-800"
        >
          <p class="text-theme-xs text-gray-400">
            Page {{ meta.current_page }} / {{ meta.last_page }} — {{ meta.total }} paiement(s)
          </p>
          <div class="flex gap-2">
            <button
              :disabled="meta.current_page === 1"
              @click="goToPage(meta.current_page - 1)"
              class="btn-secondary disabled:opacity-40"
            >← Précédent</button>
            <button
              :disabled="meta.current_page === meta.last_page"
              @click="goToPage(meta.current_page + 1)"
              class="btn-secondary disabled:opacity-40"
            >Suivant →</button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import api from '@/services/api'
import { usePermissions } from '@/composables/usePermissions'

const { isSuperAdminInstitution, isSuperAdminAnnexe, isComptable } = usePermissions()

const payments = ref([])
const annexes  = ref([])
const loading  = ref(false)
const meta     = ref(null)
const page     = ref(1)

const filters = ref({
  status: '', method: '', type: '', annexe_id: '', from: '', to: '', q: '',
})

const scopeLabel = computed(() => {
  if (isSuperAdminInstitution.value) return 'All payments — institution-wide'
  if (isSuperAdminAnnexe.value)      return 'Payments from your branch(es)'
  if (isComptable.value)             return 'Payments from your branch'
  return ''
})

const loadPayments = async () => {
  loading.value = true
  try {
    const params = { per_page: 20, page: page.value }
    Object.entries(filters.value).forEach(([k, v]) => { if (v !== '') params[k] = v })
    const res = await api.get('admin/payments', { params })
    payments.value = res.data?.data ?? []
    meta.value     = res.data?.meta ?? null
  } catch (e) {
    console.error(e)
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
  } catch { annexes.value = [] }
}

const applyFilters = () => { page.value = 1; loadPayments() }
const resetFilters = () => {
  filters.value = { status: '', method: '', type: '', annexe_id: '', from: '', to: '', q: '' }
  page.value = 1
  loadPayments()
}
const goToPage = (p) => { page.value = p; loadPayments() }

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getStudentName = (p) =>
  p?.student?.name
  ?? (p?.student?.first_name ? `${p.student.first_name} ${p.student.last_name ?? ''}`.trim() : null)
  ?? p?.payer_name
  ?? '—'

const formatDate = (d) => {
  if (!d) return '–'
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  }).format(new Date(d))
}
const formatAmount = (a) =>
  a != null
    ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(a)
    : '–'

const STATUS_MAP = {
  success: { label: 'Payé',       cls: 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' },
  pending: { label: 'En attente', cls: 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400' },
  failed:  { label: 'Échoué',     cls: 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500' },
}
const statusLabel = (s) => STATUS_MAP[s?.toLowerCase()]?.label ?? s ?? '–'
const statusClass = (s) => [
  'rounded-full px-2 py-0.5 text-theme-xs font-medium',
  STATUS_MAP[s?.toLowerCase()]?.cls ?? 'bg-gray-100 text-gray-500',
]

const TYPE_MAP = {
  tuition:      { label: 'Scolarité',   cls: 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400' },
  registration: { label: 'Inscription', cls: 'bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400' },
  other:        { label: 'Autre',       cls: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' },
}
const typeLabel = (t) => TYPE_MAP[t?.toLowerCase()]?.label ?? (t ?? 'Autre')
const typeClass = (t) => [
  'rounded-full px-2 py-0.5 text-theme-xs font-medium',
  TYPE_MAP[t?.toLowerCase()]?.cls ?? 'bg-gray-100 text-gray-500',
]

const METHOD_MAP = {
  mtn: 'MTN Mobile Money', moov: 'Moov Money',
  cash: 'Espèces', bank_transfer: 'Virement', card: 'Carte',
}
const methodLabel = (m) => METHOD_MAP[m?.toLowerCase()] ?? m ?? '–'

onMounted(() => { loadPayments(); loadAnnexes() })
</script>

<style scoped>
.filter-label {
  @apply mb-1 block text-theme-xs font-medium text-gray-500 dark:text-gray-400;
}
.filter-select {
  @apply w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-theme-sm text-gray-700
         focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300;
}
.btn-primary {
  @apply rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-brand-600 transition;
}
.btn-secondary {
  @apply rounded-lg border border-gray-300 bg-white px-4 py-2 text-theme-sm font-medium text-gray-600
         hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 transition;
}
</style>
