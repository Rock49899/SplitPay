<template>
  <AdminPlatformeLayout>
    <section class="space-y-6">
      <div class="rounded-xl bg-[#1b1b1c] p-6 ring-1 ring-[#2a2a2a]">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-2xl font-extrabold text-[#e5e2e1]">{{ institution.name || 'Institution' }}</h2>
            <p class="mt-2 text-sm text-[#bdc9c4]">{{ institution.email || '—' }} · {{ institution.phone || '—' }}</p>
          </div>
          <div class="flex items-center gap-3">
            <select
              v-model="schoolYear"
              @change="loadDetails"
              class="rounded-full border border-[#3e4945] bg-[#202020] px-4 py-2 text-sm text-[#e5e2e1] focus:border-[#7bd7bd] focus:outline-none"
            >
              <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
            </select>

            <span
              class="inline-flex rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest"
              :class="institution.is_active ? 'bg-[#0e7c66]/20 text-[#7bd7bd] ring-1 ring-[#0e7c66]/30' : 'bg-[#93000a]/20 text-[#ffb4ab] ring-1 ring-[#93000a]/30'"
            >
              {{ institution.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <article class="rounded-xl bg-[#1b1b1c] p-4 ring-1 ring-[#2a2a2a]" v-for="card in statCards" :key="card.label">
          <p class="text-xs uppercase tracking-wider text-[#88938e]">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-black text-[#e5e2e1]">{{ card.value }}</p>
        </article>
      </div>

      <div class="rounded-xl bg-[#1b1b1c] ring-1 ring-[#2a2a2a] overflow-x-auto">
        <table class="min-w-full border-collapse text-left">
          <thead>
            <tr class="border-b border-[#2a2a2a] bg-[#202020]/70 text-[#bdc9c4]">
              <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Annexe</th>
              <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Ville</th>
              <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Responsables</th>
              <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Étudiants</th>
              <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Liens créés</th>
              <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Recouvrement</th>
              <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Paiements collectés</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#2a2a2a]">
            <tr v-for="annexe in annexes" :key="annexe.id" class="transition-colors hover:bg-white/5">
              <td class="px-6 py-4 text-sm font-semibold text-[#e5e2e1]">{{ annexe.name }}</td>
              <td class="px-6 py-4 text-sm text-[#bdc9c4]">{{ annexe.city || '—' }}</td>
              <td class="px-6 py-4 text-sm text-[#bdc9c4]">
                <div v-if="annexe.responsables?.length" class="space-y-1">
                  <div v-for="resp in annexe.responsables" :key="`${annexe.id}-${resp.email}-${resp.role_code}`">
                    <span class="text-[#e5e2e1]">{{ resp.name || resp.email }}</span>
                    <span class="text-[#88938e]"> — {{ resp.role || resp.role_code || 'Responsable' }}</span>
                  </div>
                </div>
                <span v-else>—</span>
              </td>
              <td class="px-6 py-4 text-sm text-[#bdc9c4]">{{ annexe.stats?.students_total ?? 0 }}</td>
              <td class="px-6 py-4 text-sm text-[#bdc9c4]">{{ annexe.stats?.links_created ?? 0 }}</td>
              <td class="px-6 py-4 text-sm text-[#bdc9c4]">{{ formatPercent(annexe.stats?.recovery_rate ?? 0) }}</td>
              <td class="px-6 py-4 text-sm text-[#bdc9c4]">{{ formatCurrency(annexe.stats?.payments_collected ?? 0) }}</td>
            </tr>
            <tr v-if="!loading && annexes.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-sm text-[#bdc9c4]">Aucune annexe trouvée pour cette institution.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </AdminPlatformeLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import AdminPlatformeLayout from '@/components/AdminPlatforme/AdminPlatformeLayout.vue'

const route = useRoute()
const loading = ref(false)

const institution = ref({})
const stats = ref({})
const annexes = ref([])
const schoolYear = ref('')
const availableYears = ref([])

const statCards = computed(() => [
  { label: 'Annexes', value: stats.value.annexes_total ?? 0 },
  { label: 'Annexes actives', value: stats.value.annexes_active ?? 0 },
  { label: 'Étudiants', value: stats.value.students_total ?? 0 },
  { label: 'Liens créés', value: stats.value.links_created ?? 0 },
  { label: 'Taux recouvrement', value: formatPercent(stats.value.recovery_rate ?? 0) },
  { label: 'Paiements collectés', value: formatCurrency(stats.value.payments_collected ?? 0) },
])

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

const formatPercent = (value) => `${Number(value || 0).toFixed(1)}%`

const guessCurrentSchoolYear = () => {
  const now = new Date()
  const year = now.getFullYear()
  const month = now.getMonth() + 1
  return month >= 9 ? `${year}-${year + 1}` : `${year - 1}-${year}`
}

const loadSchoolYears = async () => {
  try {
    const { data } = await api.get('admin/school-years')
    const years = data?.years ?? []
    availableYears.value = years.length ? years : [guessCurrentSchoolYear()]
  } catch {
    availableYears.value = [guessCurrentSchoolYear()]
  }

  if (!schoolYear.value) {
    schoolYear.value = availableYears.value[0]
  }
}

const loadDetails = async () => {
  loading.value = true
  try {
    const { data } = await api.get(`admin/institutions/${route.params.id}`, {
      params: {
        detailed: 1,
        school_year: schoolYear.value || undefined,
      },
    })

    if (Array.isArray(data?.available_years) && data.available_years.length) {
      availableYears.value = data.available_years
    }
    if (data?.school_year) {
      schoolYear.value = data.school_year
    }

    institution.value = data?.institution ?? {}
    stats.value = data?.stats ?? {}
    annexes.value = data?.annexes ?? []
  } catch (error) {
    console.error('Failed loading institution details', error)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadSchoolYears()
  await loadDetails()
})
</script>
