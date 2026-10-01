<template>
  <AdminPlatformeLayout>
    <section class="space-y-6">
      <div class="rounded-xl bg-[#1b1b1c] p-6 ring-1 ring-[#2a2a2a]">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-xl font-extrabold text-[#e5e2e1]">Vue analytique plateforme</h2>
            <p class="mt-1 text-sm text-[#bdc9c4]">Indicateurs consolidés par année académique.</p>
          </div>

          <select
            v-model="schoolYear"
            @change="loadDashboard"
            class="rounded-full border border-[#3e4945] bg-[#202020] px-4 py-2 text-sm text-[#e5e2e1] focus:border-[#7bd7bd] focus:outline-none"
          >
            <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
        <article
          v-for="kpi in kpis"
          :key="kpi.label"
          class="rounded-xl bg-[#1b1b1c] p-5 ring-1 ring-[#2a2a2a]"
        >
          <p class="text-xs font-semibold uppercase tracking-wider text-[#88938e]">{{ kpi.label }}</p>
          <p class="mt-2 text-3xl font-black text-[#e5e2e1]">{{ kpi.value }}</p>
          <p class="mt-2 text-xs text-[#bdc9c4]">{{ kpi.subtext }}</p>
        </article>
      </div>

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <article class="rounded-xl bg-[#1b1b1c] p-5 ring-1 ring-[#2a2a2a]">
          <h3 class="text-sm font-extrabold uppercase tracking-[0.2em] text-[#7bd7bd]">Recouvrement & montants</h3>
          <VueApexCharts type="line" height="320" :options="recoveryChartOptions" :series="recoverySeries" />
        </article>

        <article class="rounded-xl bg-[#1b1b1c] p-5 ring-1 ring-[#2a2a2a]">
          <h3 class="text-sm font-extrabold uppercase tracking-[0.2em] text-[#7bd7bd]">Liens créés / utilisés</h3>
          <VueApexCharts type="bar" height="320" :options="linksChartOptions" :series="linksSeries" />
        </article>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <router-link
          v-for="card in cards"
          :key="card.title"
          :to="card.to"
          class="group rounded-xl bg-[#1b1b1c] p-6 ring-1 ring-[#2a2a2a] transition hover:ring-[#0e7c66]"
        >
          <h3 class="text-base font-extrabold text-[#e5e2e1] group-hover:text-[#7bd7bd]">{{ card.title }}</h3>
          <p class="mt-2 text-sm leading-relaxed text-[#bdc9c4]">{{ card.description }}</p>
          <span class="mt-4 inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#7bd7bd]">
            Ouvrir
            <span aria-hidden="true">→</span>
          </span>
        </router-link>
      </div>
    </section>
  </AdminPlatformeLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import AdminPlatformeLayout from '@/components/AdminPlatforme/AdminPlatformeLayout.vue'
import VueApexCharts from 'vue3-apexcharts'
import api from '@/services/api'

const loading = ref(false)
const schoolYear = ref('')
const availableYears = ref([])
const dashboard = ref({
  kpis: {},
  charts: {
    labels: [],
    recovery_rate: [],
    links_created: [],
    links_used: [],
    paid: [],
    remaining: [],
  },
})

const formatCurrency = (value) => {
  try {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XOF',
      maximumFractionDigits: 0,
    }).format(Number(value || 0))
  } catch {
    return `${value || 0} FCFA`
  }
}

const formatPercent = (value) => `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 }).format(Number(value || 0))} %`

const formatCompactCurrency = (value) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency', currency: 'XOF', notation: 'compact', maximumFractionDigits: 1,
  }).format(Number(value || 0))

const kpis = computed(() => {
  const values = dashboard.value.kpis || {}
  return [
    {
      label: 'Taux de recouvrement',
      value: formatPercent(values.recovery_rate ?? 0),
      subtext: 'Montant payé / montant attendu',
    },
    {
      label: 'Liens créés',
      value: String(values.links_created ?? 0),
      subtext: 'Total des liens générés',
    },
    {
      label: 'Liens utilisés',
      value: String(values.links_used ?? 0),
      subtext: 'Liens passés au statut utilisé',
    },
    {
      label: 'Montant payé',
      value: formatCurrency(values.total_paid ?? 0),
      subtext: 'Somme encaissée sur l’année',
    },
    {
      label: 'Montant restant',
      value: formatCurrency(values.total_remaining ?? 0),
      subtext: 'Reste à recouvrer',
    },
  ]
})

const recoverySeries = computed(() => [
  {
    name: 'Taux de recouvrement (%)',
    data: dashboard.value.charts?.recovery_rate ?? [],
  },
  {
    name: 'Montant payé (cumul)',
    data: dashboard.value.charts?.paid ?? [],
  },
  {
    name: 'Montant restant',
    data: dashboard.value.charts?.remaining ?? [],
  },
])

const linksSeries = computed(() => [
  {
    name: 'Liens créés',
    data: dashboard.value.charts?.links_created ?? [],
  },
  {
    name: 'Liens utilisés',
    data: dashboard.value.charts?.links_used ?? [],
  },
])

const axisLabelStyle = { colors: '#88938e' }

const baseOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    foreColor: '#bdc9c4',
    fontFamily: "'SplitPay Espaces', 'Plus Jakarta Sans', sans-serif",
    animations: { enabled: !loading.value },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: dashboard.value.charts?.labels ?? [],
    labels: { style: axisLabelStyle },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  grid: {
    borderColor: '#2a2a2a',
    strokeDashArray: 4,
  },
  legend: {
    labels: { colors: '#bdc9c4' },
    markers: { radius: 99 },
  },
  tooltip: {
    theme: 'dark',
  },
}))

// Montants (axe de gauche) et taux en % (axe de droite) n'ont pas la même échelle
const recoveryChartOptions = computed(() => ({
  ...baseOptions.value,
  stroke: { width: [3, 3, 3], curve: 'smooth', dashArray: [5, 0, 0] },
  colors: ['#7bd7bd', '#0E7C66', '#f59e0b'],
  yaxis: [
    {
      seriesName: 'Taux de recouvrement (%)',
      opposite: true,
      min: 0,
      max: 100,
      tickAmount: 4,
      labels: { style: axisLabelStyle, formatter: (v) => `${Math.round(v)} %` },
    },
    {
      seriesName: 'Montant payé (cumul)',
      labels: { style: axisLabelStyle, formatter: formatCompactCurrency },
    },
    {
      seriesName: 'Montant payé (cumul)',
      show: false,
      labels: { formatter: formatCompactCurrency },
    },
  ],
  tooltip: {
    theme: 'dark',
    y: {
      formatter: (value, { seriesIndex }) => (seriesIndex === 0 ? formatPercent(value) : formatCurrency(value)),
    },
  },
}))

const linksChartOptions = computed(() => ({
  ...baseOptions.value,
  plotOptions: {
    bar: {
      borderRadius: 6,
      columnWidth: '45%',
    },
  },
  yaxis: {
    labels: { style: axisLabelStyle, formatter: (v) => Math.round(v) },
  },
  colors: ['#0E7C66', '#22d3ee'],
}))

const guessCurrentSchoolYear = () => {
  const now = new Date()
  const year = now.getFullYear()
  const month = now.getMonth() + 1
  return month >= 9 ? `${year}-${year + 1}` : `${year - 1}-${year}`
}

const loadDashboard = async () => {
  loading.value = true
  try {
    const { data } = await api.get('admin/dashboard/platform-overview', {
      params: {
        school_year: schoolYear.value || undefined,
      },
    })

    dashboard.value = {
      kpis: data?.kpis ?? {},
      charts: data?.charts ?? {
        labels: [],
        recovery_rate: [],
        links_created: [],
        links_used: [],
        paid: [],
        remaining: [],
      },
    }

    if (Array.isArray(data?.available_years) && data.available_years.length) {
      availableYears.value = data.available_years
    }

    if (data?.school_year) {
      schoolYear.value = data.school_year
    }
  } catch (error) {
    console.error('Failed to load platform dashboard', error)
  } finally {
    loading.value = false
  }
}

const cards = ref([
  {
    title: 'Institutions',
    description: 'Créer, consulter et piloter toutes les institutions de la plateforme.',
    to: '/platform/institutions',
  },
  {
    title: 'Annexes',
    description: 'Vue globale de toutes les annexes, tous tenants confondus.',
    to: '/platform/annexes',
  },
  {
    title: 'Utilisateurs',
    description: 'Administration des comptes et rôles au niveau plateforme.',
    to: '/platform/users',
  },
  {
    title: 'Paramètres plateforme',
    description: 'Configuration globale et gouvernance de la plateforme.',
    to: '/platform/settings',
  },
])

onMounted(async () => {
  // L'API choisit l'année ouverte la plus récente si aucune n'est précisée
  await loadDashboard()
  if (!availableYears.value.length) {
    availableYears.value = [schoolYear.value || guessCurrentSchoolYear()]
  }
})
</script>
