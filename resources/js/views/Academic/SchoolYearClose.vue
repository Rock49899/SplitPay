<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Clôture de l'année scolaire" />

    <div class="max-w-3xl mx-auto space-y-5">

      <!-- Étape 1 : sélection des années -->
      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
          1. Sélectionner l'année à clôturer
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Année à clôturer *</label>
            <input
              list="close-from-years"
              :value="fromYear"
              @change="fromYear = parseYear($event)"
              @keydown.enter="fromYear = parseYear($event)"
              placeholder="Ex: 2024-2025"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white"
            />
            <datalist id="close-from-years">
              <option v-for="y in allYearOptions" :key="y" :value="y" />
            </datalist>
          </div>
          <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Nouvelle année (promotion vers) *</label>
            <input
              list="close-to-years"
              :value="toYear"
              @change="toYear = parseYear($event)"
              @keydown.enter="toYear = parseYear($event)"
              placeholder="Ex: 2025-2026"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white"
            />
            <datalist id="close-to-years">
              <option v-for="y in allYearOptions" :key="y" :value="y" />
            </datalist>
          </div>
        </div>
        <div v-if="yearsError" class="mt-2 text-sm text-red-500">{{ yearsError }}</div>
        <div class="mt-4">
          <button @click="loadPreview" :disabled="!fromYear || !toYear || loadingPreview"
            class="px-5 py-2 bg-brand-500 text-white rounded-lg text-sm font-medium disabled:opacity-60">
            <span v-if="loadingPreview">Analyse en cours…</span>
            <span v-else>Analyser →</span>
          </button>
        </div>
      </div>

      <!-- Étape 2 : aperçu des résultats -->
      <template v-if="preview">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
            2. Aperçu de la promotion
          </h2>

          <!-- Résumé stats -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ preview.summary.total }}</div>
              <div class="text-xs text-gray-500 mt-0.5">Étudiants concernés</div>
            </div>
            <div class="bg-success-50 dark:bg-success-500/10 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-success-700 dark:text-success-400">{{ preview.summary.promotable }}</div>
              <div class="text-xs text-gray-500 mt-0.5">À promouvoir</div>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-500/10 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-indigo-700 dark:text-indigo-400">{{ preview.summary.terminal }}</div>
              <div class="text-xs text-gray-500 mt-0.5">Diplômés</div>
            </div>
            <div class="bg-orange-50 dark:bg-orange-500/10 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ preview.summary.no_level }}</div>
              <div class="text-xs text-gray-500 mt-0.5">Sans niveau</div>
            </div>
          </div>

          <!-- Alerte tarifs manquants -->
          <div v-if="missingFees.length" class="mb-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg px-4 py-3 text-sm text-orange-700 dark:text-orange-400">
            ⚠ {{ missingFees.length }} étudiant(s) n'ont pas de barème configuré pour <strong>{{ toYear }}</strong>.
            Leur inscription sera créée avec scolarité à 0 FCFA.
          </div>

          <!-- Table détail -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th class="px-3 py-2 text-left text-xs text-gray-500 font-medium uppercase">Étudiant</th>
                  <th class="px-3 py-2 text-left text-xs text-gray-500 font-medium uppercase">Niveau actuel</th>
                  <th class="px-3 py-2 text-left text-xs text-gray-500 font-medium uppercase">Prochain niveau</th>
                  <th class="px-3 py-2 text-left text-xs text-gray-500 font-medium uppercase">Filière</th>
                  <th class="px-3 py-2 text-right text-xs text-gray-500 font-medium uppercase">Frais {{ toYear }}</th>
                  <th class="px-3 py-2 text-center text-xs text-gray-500 font-medium uppercase">Statut</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="d in preview.details" :key="d.student_id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                  <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">
                    {{ d.student_name }}
                    <span class="block text-xs text-gray-400">{{ d.matricule }}</span>
                  </td>
                  <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ d.current_level }}</td>
                  <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ d.next_level ?? '—' }}</td>
                  <td class="px-3 py-2 text-gray-500">{{ d.specialization ?? '—' }}</td>
                  <td class="px-3 py-2 text-right">
                    <span v-if="d.outcome === 'promote' && d.resolved_fee" class="text-success-700 dark:text-success-400 font-medium">
                      {{ fmtAmount(d.resolved_fee) }}
                    </span>
                    <span v-else-if="d.outcome === 'promote' && d.fee_missing" class="text-orange-500">
                      Tarif manquant
                    </span>
                    <span v-else class="text-gray-400">—</span>
                  </td>
                  <td class="px-3 py-2 text-center">
                    <span v-if="d.outcome === 'promote'"
                      class="px-2 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400">
                      Promotion
                    </span>
                    <span v-else-if="d.outcome === 'terminal'"
                      class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-400">
                      Diplômé
                    </span>
                    <span v-else
                      class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                      Sans niveau
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Étape 3 : confirmation -->
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-2">
            3. Confirmer la clôture
          </h2>
          <p class="text-sm text-gray-500 mb-4">
            Cette action est <strong>irréversible</strong> : toutes les inscriptions actives de
            <strong>{{ fromYear }}</strong> seront clôturées et les étudiants promouvables
            obtiendront une nouvelle inscription pour <strong>{{ toYear }}</strong>.
          </p>

          <!-- Résultat post-exécution -->
          <div v-if="result" class="mb-4 bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-lg px-4 py-3 text-sm text-success-700 dark:text-success-400">
            ✓ Clôture exécutée — {{ result.promoted }} promu(s), {{ result.graduated }} diplômé(s)
            <span v-if="result.skipped">, {{ result.skipped }} sans niveau</span>
            <span v-if="result.errors?.length" class="block text-orange-500 mt-1">
              {{ result.errors.length }} erreur(s) — voir la console
            </span>
          </div>
          <div v-if="execError" class="mb-4 text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{{ execError }}</div>

          <div class="flex gap-3">
            <button @click="execute" :disabled="executing || !!result"
              class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium disabled:opacity-60">
              <span v-if="executing">Exécution…</span>
              <span v-else-if="result">Terminé ✓</span>
              <span v-else>Clôturer l'année {{ fromYear }}</span>
            </button>
            <button v-if="result" @click="reset" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-sm">
              Nouvelle clôture
            </button>
          </div>
        </div>
      </template>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import { useSchoolYear } from '@/composables/useSchoolYear'
import promotionService from '@/services/promotionService'
import { useActiveYearStore } from '@/stores/useActiveYearStore'

const activeYearStore = useActiveYearStore()
const { options } = useSchoolYear(6)
// Proposer aussi l'année suivante (clôture → promotion vers N+1)
const now       = new Date()
const baseYear  = now.getMonth() >= 8 ? now.getFullYear() : now.getFullYear() - 1
const nextStr   = `${baseYear + 1}-${baseYear + 2}`
// Fusionne les années locales + les années du store (issues de la DB)
const allYearOptions = computed(() => {
  const base = [...options]
  if (!base.includes(nextStr)) base.unshift(nextStr)
  const fromStore = activeYearStore.availableYears.filter(y => !base.includes(y))
  return [...fromStore, ...base].sort((a, b) => b.localeCompare(a))
})

/** Valide et retourne l'année si format YYYY-YYYY avec année+1, sinon null */
function parseYear(e) {
  const val = e.target?.value?.trim() ?? e
  if (!val || !/^\d{4}-\d{4}$/.test(val)) return null
  const [a, b] = val.split('-').map(Number)
  return b === a + 1 ? val : null
}

const fromYear      = ref(null)
const toYear        = ref(null)
const yearsError    = ref(null)
const loadingPreview= ref(false)
const preview       = ref(null)
const executing     = ref(false)
const result        = ref(null)
const execError     = ref(null)

const missingFees = computed(() =>
  (preview.value?.details ?? []).filter(d => d.outcome === 'promote' && d.fee_missing)
)

const fmtAmount = (v) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v)

const loadPreview = async () => {
  yearsError.value = null
  if (fromYear.value === toYear.value) {
    yearsError.value = 'Les deux années doivent être différentes.'
    return
  }
  loadingPreview.value = true
  preview.value = null
  result.value  = null
  try {
    const res = await promotionService.preview(fromYear.value, toYear.value)
    preview.value = res.data
  } catch (e) {
    yearsError.value = e.response?.data?.message || e.message || 'Erreur lors de l\'analyse'
  } finally {
    loadingPreview.value = false
  }
}

const execute = async () => {
  if (!confirm(`Confirmer la clôture de l'année ${fromYear.value} ?`)) return
  executing.value = true
  execError.value = null
  try {
    const res = await promotionService.execute(fromYear.value, toYear.value)
    result.value = res.data?.result ?? res.data
    // Basculer toute l'appli sur la nouvelle année après clôture
    activeYearStore.setActiveYear(toYear.value)
  } catch (e) {
    execError.value = e.response?.data?.message || e.message || 'Erreur lors de l\'exécution'
  } finally {
    executing.value = false
  }
}

const reset = () => {
  fromYear.value = null
  toYear.value   = null
  preview.value  = null
  result.value   = null
  execError.value= null
}
</script>
