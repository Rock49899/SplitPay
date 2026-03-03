<template>
  <admin-layout>
    <div class="space-y-5 md:space-y-6">

      <!-- ── Header bar  -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">Dashboard</h1>
          <p class="mt-0.5 text-sm text-gray-400 dark:text-gray-500">
            {{ isSuperAdminInstitution ? 'Institution-wide overview' : scopeLabel }}
          </p>
        </div>

        <!-- School year selector -->
        <div class="flex items-center gap-2">
          <label class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Academic year</label>
          <input
            list="dashboard-year-list"
            :value="selectedYear"
            @change="onYearInput"
            @keydown.enter="onYearInput"
            placeholder="Ex: 2025-2026"
            class="w-32 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:outline-none focus:border-brand-500"
          />
          <datalist id="dashboard-year-list">
            <option v-for="y in activeYearStore.availableYears" :key="y" :value="y" />
          </datalist>
        </div>
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
import { ref, computed, onMounted } from 'vue'
import AdminLayout     from '../components/layout/AdminLayout.vue'
import RecentOrders    from '../components/ecommerce/RecentOrders.vue'
import DashboardKpis           from '../components/dashboard/DashboardKpis.vue'
import MonthlyCollectionsChart from '../components/dashboard/MonthlyCollectionsChart.vue'
import AnnexeStatsChart        from '../components/dashboard/AnnexeStatsChart.vue'
import api                     from '@/services/api'
import { usePermissions }      from '@/composables/usePermissions'
import { useActiveYearStore }  from '@/stores/useActiveYearStore'

const { isSuperAdminInstitution, isSuperAdminAnnexe, isComptable, isGestionnaire, currentUser } = usePermissions()

const activeYearStore = useActiveYearStore()

// selectedYear est un computed r/w sur le store (partagé avec tout le reste de l'app)
const selectedYear = computed({
  get: () => activeYearStore.activeYear,
  set: (val) => activeYearStore.setActiveYear(val),
})

// ── Scope label (non-institution roles) ────────────────────────────────────────

const scopeLabel = computed(() => {
  if (isSuperAdminAnnexe.value)  return 'Branch administrator view'
  if (isComptable.value)         return 'Accountant view'
  if (isGestionnaire.value)      return 'Manager view'
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

// ── Load all on mount / year change ────────────────────────────────────────────
function onYearInput(e) {
  const val = e.target.value?.trim()
  if (!val || !/^\d{4}-\d{4}$/.test(val)) return
  const [a, b] = val.split('-').map(Number)
  if (b !== a + 1) return
  selectedYear.value = val
  if (!activeYearStore.availableYears.includes(val)) {
    activeYearStore.availableYears.unshift(val)
  }
  loadAll()
}
const loadAll = () => {
  loadKpis()
  loadMonthly()
  loadAnnexeStats()
}

onMounted(async () => {
  await activeYearStore.loadAvailableYears()
  loadAll()
})
</script>
