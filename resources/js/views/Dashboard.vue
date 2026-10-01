<template>
  <admin-layout>
    <div class="space-y-5 md:space-y-6">

      <!-- ── Header bar  -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-sm font-medium text-brand-600 dark:text-brand-300">{{ todayLabel }}</p>
          <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
            Bonjour{{ firstName ? `, ${firstName}` : '' }}
          </h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ isSuperAdminInstitution ? 'Voici où en sont les paiements de votre établissement.' : scopeLabel }}
          </p>
        </div>
        <router-link
          v-if="hasPermission('link.create')"
          to="/finances"
          class="inline-flex items-center justify-center gap-2 self-start rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 sm:self-auto"
        >
          <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
          </svg>
          Suivre les paiements
        </router-link>
      </div>
      <DashboardKpis
        :kpis="kpis"
        :school-year="selectedYear"
        :loading="kpisLoading"
        :show-annexes="isSuperAdminInstitution"
      />

      <div class="grid grid-cols-12 gap-5 md:gap-6">

        <!-- Monthly collections (all roles) -->
        <div :class="isSuperAdminInstitution ? 'col-span-12 xl:col-span-7' : 'col-span-12'">
          <MonthlyCollectionsChart
            :series="monthlySeries"
            :categories="monthlyLabels"
            :school-year="selectedYear"
            :loading="monthlyLoading"
          />
        </div>

        <!-- Annexe stats — super_admin_institution only -->
        <div v-if="isSuperAdminInstitution" class="col-span-12 xl:col-span-5">
          <AnnexeStatsChart
            :labels="annexeLabels"
            :collected="annexeCollected"
            :unpaid="annexeUnpaid"
            :recovery-rate="annexeRecoveryRate"
            :school-year="selectedYear"
            :loading="annexeLoading"
          />
        </div>
      </div>

      <!-- ── Recent payments ─────────────────────────────────────────────────── -->
      <recent-orders />

    </div>
  </admin-layout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import AdminLayout     from '../components/layout/AdminLayout.vue'
import RecentOrders    from '../components/ecommerce/RecentOrders.vue'
import DashboardKpis           from '../components/dashboard/DashboardKpis.vue'
import MonthlyCollectionsChart from '../components/dashboard/MonthlyCollectionsChart.vue'
import AnnexeStatsChart        from '../components/dashboard/AnnexeStatsChart.vue'
import api                     from '@/services/api'
import { usePermissions }      from '@/composables/usePermissions'
import { useActiveYearStore }  from '@/stores/useActiveYearStore'
import { useInstitutionBrand } from '@/composables/useInstitutionBrand'

const { isSuperAdminInstitution, isSuperAdminAnnexe, isComptable, isGestionnaire, currentUser, hasPermission } = usePermissions()
const { loadBrand } = useInstitutionBrand()

const firstName = computed(() => (currentUser.value?.name || '').trim().split(/\s+/)[0] || '')
const todayLabel = computed(() => {
  const label = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date())
  return label.charAt(0).toUpperCase() + label.slice(1)
})

const activeYearStore = useActiveYearStore()

// selectedYear est un computed r/w sur le store (partagé avec tout le reste de l'app)
const selectedYear = computed({
  get: () => activeYearStore.activeYear,
  set: (val) => activeYearStore.setActiveYear(val),
})

// ── Scope label (non-institution roles) ────────────────────────────────────────

const scopeLabel = computed(() => {
  if (isSuperAdminAnnexe.value)  return 'Vue administrateur d\'établissement annexe'
  if (isComptable.value)         return 'Vue comptable'
  if (isGestionnaire.value)      return 'Vue gestionnaire'
  return ''
})

// ── KPIs state ─────────────────────────────────────────────────────────────────

const kpis        = ref({})
const kpisLoading = ref(true)

const loadKpis = async () => {
  kpisLoading.value = true
  try {
    const res = await api.get('admin/dashboard/kpis', { params: { school_year: selectedYear.value } })
    kpis.value = res.data ?? {}
  } catch (e) {
    console.error('[Dashboard] kpis failed', e)
  } finally {
    kpisLoading.value = false
  }
}

// ── Monthly collections state ───────────────────────────────────────────────────

const monthlySeries  = ref([])
const monthlyLabels  = ref([])
const monthlyLoading = ref(true)

const loadMonthly = async () => {
  monthlyLoading.value = true
  try {
    const res = await api.get('admin/dashboard/monthly-collections', { params: { school_year: selectedYear.value } })
    monthlySeries.value = res.data?.series  ?? []
    monthlyLabels.value = res.data?.labels  ?? []
  } catch (e) {
    console.error('[Dashboard] monthly-collections failed', e)
  } finally {
    monthlyLoading.value = false
  }
}

// ── Annexe stats state ──────────────────────────────────────────────────────────

const annexeLabels       = ref([])
const annexeCollected    = ref([])
const annexeUnpaid       = ref([])
const annexeRecoveryRate = ref([])
const annexeLoading      = ref(true)

const loadAnnexeStats = async () => {
  if (!isSuperAdminInstitution.value) {
    annexeLoading.value = false
    return
  }
  annexeLoading.value = true
  try {
    const res = await api.get('admin/dashboard/annexe-stats', { params: { school_year: selectedYear.value } })
    annexeLabels.value       = res.data?.labels        ?? []
    annexeCollected.value    = res.data?.collected     ?? []
    annexeUnpaid.value       = res.data?.unpaid        ?? []
    annexeRecoveryRate.value = res.data?.recovery_rate ?? []
  } catch (e) {
    console.error('[Dashboard] annexe-stats failed', e)
  } finally {
    annexeLoading.value = false
  }
}

// ── Load all on mount / year change (année choisie dans l'en-tête) ─────────────
watch(() => activeYearStore.activeYear, () => loadAll())

const loadAll = () => {
  loadKpis()
  loadMonthly()
  loadAnnexeStats()
}

onMounted(async () => {
  loadBrand(true)
  await activeYearStore.loadAvailableYears()
  loadAll()
})
</script>
