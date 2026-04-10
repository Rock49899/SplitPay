<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Niveaux d'étude &amp; Barèmes de scolarité" />

    <div>
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">

        <!-- Header -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex-1 max-w-md">
              <input v-model="search" type="text" placeholder="Rechercher un niveau d'étude..."
                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                @input="debounceSearch" />
            </div>
            <div class="flex gap-2">
              <button @click="showCopyModal = true"
                class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 text-sm">
              Copier les barèmes
              </button>
              <button @click="openCreateModal"
                class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-lg font-medium">
                + Ajouter un niveau
              </button>
            </div>
          </div>
        </div>

        <!-- Error banner -->
        <div v-if="loadError" class="mx-6 mt-4 mb-2 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
          {{ loadError }}
        </div>

        <!-- Table niveaux -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-700 border-b border-slate-200 dark:border-slate-600">
              <tr>
                <th class="w-8 px-4 py-3"></th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Code</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Libellé</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Annexe</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Ordre</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Description</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-if="loading">
                <td colspan="7" class="px-6 py-12 text-center text-slate-500">Chargement...</td>
              </tr>
              <tr v-else-if="!loading && studyLevels.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-slate-500">Aucun niveau d'étude trouvé</td>
              </tr>

              <template v-if="!loading && studyLevels.length > 0">
              <template v-for="level in studyLevels" :key="level.id">
                <!-- Ligne principale du niveau -->
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                  <!-- Bouton expand / collapse frais -->
                  <td class="px-4 py-4">
                    <button @click="toggleFees(level)"
                      class="w-6 h-6 flex items-center justify-center rounded text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                      :title="expandedId === level.id ? 'Masquer les barèmes' : 'Voir les barèmes'">
                      <svg class="w-4 h-4 transition-transform" :class="expandedId === level.id ? 'rotate-90' : ''"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">{{ level.code }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">{{ level.label }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ level.annexe?.name ?? '—' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ level.order ?? '—' }}</td>
                  <td class="px-6 py-4 text-sm text-slate-500">{{ level.description || '—' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-3">
                    <button @click="openEditModal(level)" class="text-brand-600 hover:text-brand-800 dark:text-brand-400">Modifier</button>
                    <button @click="deleteLevel(level)" class="text-red-600 hover:text-red-800 dark:text-red-400">Supprimer</button>
                  </td>
                </tr>

                <!-- Panneau dépliable : barèmes de scolarité pour ce niveau -->
                <tr v-if="expandedId === level.id">
                  <td colspan="7" class="px-0 py-0 bg-slate-50 dark:bg-slate-900/40">
                    <div class="px-6 py-4 border-t border-dashed border-slate-200 dark:border-slate-700">
                      <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                          Barèmes de scolarité — {{ level.label }}
                        </p>
                        <button @click="openFeeModal(level)"
                          class="px-3 py-1 text-xs bg-brand-500 text-white rounded-lg hover:bg-brand-600">
                          + Ajouter un barème
                        </button>
                      </div>

                      <!-- Chargement des frais -->
                      <div v-if="feesLoading" class="text-xs text-slate-400 py-2">Chargement des frais...</div>

                      <!-- Table des frais -->
                      <template v-else-if="feesMap[level.id]?.length">
                        <table class="w-full text-sm">
                          <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                              <th class="pb-2 text-left text-xs text-slate-500 font-medium">Année scolaire</th>
                              <th class="pb-2 text-left text-xs text-slate-500 font-medium">Spécialisation</th>
                              <th class="pb-2 text-right text-xs text-slate-500 font-medium">Montant</th>
                              <th class="pb-2 text-right text-xs text-slate-500 font-medium">Actions</th>
                            </tr>
                          </thead>
                          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="fee in feesMap[level.id]" :key="fee.id"
                              class="hover:bg-white dark:hover:bg-slate-800">
                              <td class="py-2 text-slate-700 dark:text-slate-300">{{ fee.school_year }}</td>
                              <td class="py-2 text-slate-500">
                                {{ fee.specialization?.label ?? 'Générique (toutes spécialisations)' }}
                              </td>
                              <td class="py-2 text-right font-semibold text-slate-800 dark:text-white">
                                {{ fmtAmount(fee.tuition_amount) }}
                              </td>
                              <td class="py-2 text-right space-x-2">
                                <button @click="openFeeModal(level, fee)" class="text-brand-600 hover:text-brand-800 text-xs">Modifier</button>
                                <button @click="deleteFee(fee, level.id)" class="text-red-500 hover:text-red-700 text-xs">Supprimer</button>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </template>
                      <p v-else class="text-xs text-slate-400 py-2">
                        Aucun barème configuré pour ce niveau.
                        <button @click="openFeeModal(level)" class="text-brand-600 underline ml-1">En ajouter un</button>
                      </p>
                    </div>
                  </td>
                </tr>
              </template>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         Modal : Créer / modifier un niveau
    ═══════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="closeModal">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full">
          <div class="border-b border-slate-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
              {{ editingLevel ? 'Modifier le niveau d\'étude' : 'Nouveau niveau d\'étude' }}
            </h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <form @submit.prevent="saveLevel" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Code *</label>
              <input v-model="levelForm.code" type="text" required placeholder="ex: L1, M2, D3"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Libellé *</label>
              <input v-model="levelForm.label" type="text" required placeholder="ex: Licence 1ère année"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ordre de progression</label>
              <input v-model.number="levelForm.order" type="number" min="1" placeholder="1, 2, 3…"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
              <p class="text-xs text-slate-400 mt-1">Définit la progression académique : ordre 1 → 2 → 3 pilote la promotion automatique en fin d'année.</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
              <textarea v-model="levelForm.description" rows="2"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="closeModal" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm">Annuler</button>
              <button type="submit" :disabled="savingLevel" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-lg text-sm disabled:opacity-50">
                {{ savingLevel ? 'Enregistrement...' : 'Enregistrer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════
         Modal : Créer / modifier un barème
    ═══════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showFeeModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="closeFeeModal">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full">
          <div class="border-b border-slate-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
              {{ editingFee ? 'Modifier le barème' : 'Nouveau barème' }}
              <span class="text-sm font-normal text-slate-500 ml-1">— {{ feeTargetLevel?.label }}</span>
            </h3>
            <button @click="closeFeeModal" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <form @submit.prevent="saveFee" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">School Year *</label>
              <input
                list="fee-year-list"
                :value="feeForm.school_year"
                @change="feeForm.school_year = parseYear($event)"
                @keydown.enter.prevent="feeForm.school_year = parseYear($event)"
                placeholder="Ex: 2025-2026"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
              />
              <datalist id="fee-year-list">
                <option v-for="y in allYearOptions" :key="y" :value="y" />
              </datalist>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Specialization</label>
              <select v-model="feeForm.specialization_id"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <option :value="null">Generic (applies to all specializations)</option>
                <option v-for="s in specializations" :key="s.id" :value="s.id">{{ s.label }}</option>
              </select>
              <p class="text-xs text-slate-400 mt-1">
                If a specialization is selected, this fee takes priority over the generic rate.
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Amount (XAF) *</label>
              <input v-model.number="feeForm.tuition_amount" type="number" min="0" step="500" required placeholder="ex: 450000"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            </div>
            <div v-if="feeError" class="text-sm text-red-500 bg-red-50 rounded-lg px-3 py-2">{{ feeError }}</div>
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="closeFeeModal" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm">Annuler</button>
              <button type="submit" :disabled="savingFee" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-lg text-sm disabled:opacity-50">
                {{ savingFee ? 'Enregistrement...' : 'Enregistrer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═════════════════════════════════════════════════════════
         Modal : Copier les barèmes vers une autre année
    ═════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showCopyModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click.self="showCopyModal = false; copyResult = null; copyError = null">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-sm w-full">
          <div class="border-b border-slate-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Copier les barèmes</h3>
            <button @click="showCopyModal = false; copyResult = null; copyError = null"
              class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <div class="p-6 space-y-4">
            <p class="text-sm text-slate-500">Copie tous les barèmes d'une année vers une autre. Les barèmes déjà configurés pour l'année cible <strong>ne seront pas écrasés</strong>.</p>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Année source</label>
              <input
                list="copy-from-years"
                :value="copyForm.from_year"
                @change="copyForm.from_year = parseYear($event)"
                @keydown.enter.prevent="copyForm.from_year = parseYear($event)"
                placeholder="Ex: 2024-2025"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
              />
              <datalist id="copy-from-years">
                <option v-for="y in allYearOptions" :key="y" :value="y" />
              </datalist>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Année destination</label>
              <input
                list="copy-to-years"
                :value="copyForm.to_year"
                @change="copyForm.to_year = parseYear($event)"
                @keydown.enter.prevent="copyForm.to_year = parseYear($event)"
                placeholder="Ex: 2025-2026"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
              />
              <datalist id="copy-to-years">
                <option v-for="y in allYearOptions" :key="y" :value="y" />
              </datalist>
            </div>
            <div v-if="copyError" class="text-sm text-red-500 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2">{{ copyError }}</div>
            <div v-if="copyResult" class="text-sm text-green-700 bg-green-50 dark:bg-green-900/20 rounded-lg px-3 py-2">
              ✓ {{ copyResult.message }}
              <span class="block text-xs mt-0.5 text-slate-500">{{ copyResult.skipped }} barème(s) ignoré(s) car déjà configuré(s)</span>
            </div>
            <div class="flex justify-end gap-3 pt-2">
              <button @click="showCopyModal = false; copyResult = null; copyError = null"
                class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm">Fermer</button>
              <button @click="submitCopyFees" :disabled="copyingFees || !!copyResult"
                class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-lg text-sm disabled:opacity-50">
                {{ copyingFees ? 'Copie en cours...' : copyResult ? 'Copié ✓' : 'Copier' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import studyLevelService from '@/services/studyLevelService'
import specializationService from '@/services/specializationService'
import levelFeeService from '@/services/levelFeeService'
import { useSchoolYear } from '@/composables/useSchoolYear'
import { useActiveYearStore } from '@/stores/useActiveYearStore'

// ── Niveaux ─────────────────────────────────────────────────────────────────
const studyLevels  = ref([])
const loading      = ref(false)
const loadError    = ref(null)
const search       = ref('')
const searchTimeout= ref(null)
const showModal    = ref(false)
const editingLevel = ref(null)
const savingLevel  = ref(false)
const levelForm    = ref({ code: '', label: '', order: null, description: '' })

// ── Frais dépliables ────────────────────────────────────────────────────────
const expandedId  = ref(null)      // level.id dont le panneau est ouvert
const feesMap     = ref({})        // { [levelId]: LevelFee[] }
const feesLoading = ref(false)

// ── Modal barème ────────────────────────────────────────────────────────────
const showFeeModal    = ref(false)
const editingFee      = ref(null)
const feeTargetLevel  = ref(null)
const savingFee       = ref(false)
const feeError        = ref(null)
const feeForm         = ref({ school_year: null, specialization_id: null, tuition_amount: null })

// ── Copie de barèmes ────────────────────────────────────────────────────────
const showCopyModal = ref(false)
const copyingFees   = ref(false)
const copyError     = ref(null)
const copyResult    = ref(null)
const copyForm      = ref({ from_year: null, to_year: null })

// ── Données de référence ────────────────────────────────────────────────────
const specializations = ref([])
const activeYearStore = useActiveYearStore()
const { current: currentYear, options: schoolYearOptions } = useSchoolYear(5)
// Options fusionnées : années locales + années du store (issues de la DB)
const allYearOptions = computed(() => {
  const base = [...schoolYearOptions]
  const extra = activeYearStore.availableYears.filter(y => !base.includes(y))
  return [...extra, ...base].sort((a, b) => b.localeCompare(a))
})

/** Valide le format YYYY-YYYY (année consécutive), retourne la valeur ou null */
function parseYear(e) {
  const val = (e.target?.value ?? e)?.trim()
  if (!val || !/^\d{4}-\d{4}$/.test(val)) return null
  const [a, b] = val.split('-').map(Number)
  return b === a + 1 ? val : null
}

// ── Helpers ─────────────────────────────────────────────────────────────────
const fmtAmount = (v) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(v)

async function loadStudyLevels() {
  loading.value = true
  try {
    loadError.value = null
    const res = await studyLevelService.index({ search: search.value })
    studyLevels.value = res.data?.data ?? res.data
  } catch (e) {
    console.error(e)
    loadError.value = e.response?.data?.message ?? e.message ?? 'Failed to load study levels'
  } finally {
    loading.value = false
  }
}

function debounceSearch() {
  if (searchTimeout.value) clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(loadStudyLevels, 400)
}

function openCreateModal() {
  editingLevel.value = null
  levelForm.value = { code: '', label: '', order: null, description: '' }
  showModal.value = true
}

function openEditModal(level) {
  editingLevel.value = level
  levelForm.value = { code: level.code, label: level.label, order: level.order ?? null, description: level.description ?? '' }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingLevel.value = null
}

async function saveLevel() {
  savingLevel.value = true
  try {
    if (editingLevel.value) {
      await studyLevelService.update(editingLevel.value.id, levelForm.value)
    } else {
      await studyLevelService.store(levelForm.value)
    }
    await loadStudyLevels()
    closeModal()
  } catch (e) {
    alert(e.response?.data?.message || 'Error')
  } finally {
    savingLevel.value = false
  }
}

async function deleteLevel(level) {
  if (!confirm(`Delete study level "${level.label}"?`)) return
  try {
    await studyLevelService.destroy(level.id)
    await loadStudyLevels()
    if (expandedId.value === level.id) expandedId.value = null
  } catch (e) {
    alert(e.response?.data?.message || 'Error')
  }
}


// Frais dépliables
async function toggleFees(level) {
  if (expandedId.value === level.id) {
    expandedId.value = null
    return
  }
  expandedId.value = level.id
  if (!feesMap.value[level.id]) {
    await loadFees(level.id)
  }
}

async function loadFees(levelId) {
  feesLoading.value = true
  try {
    const res = await levelFeeService.index({ study_level_id: levelId })
    feesMap.value = { ...feesMap.value, [levelId]: res.data?.data ?? [] }
  } catch (e) {
    console.error('loadFees error', e)
    feesMap.value = { ...feesMap.value, [levelId]: [] }
  } finally {
    feesLoading.value = false
  }
}

// Barèmes CRUD
function openFeeModal(level, fee = null) {
  feeTargetLevel.value = level
  editingFee.value = fee
  feeError.value   = null
  feeForm.value = fee
    ? { school_year: fee.school_year, specialization_id: fee.specialization_id ?? null, tuition_amount: fee.tuition_amount }
    : { school_year: currentYear, specialization_id: null, tuition_amount: null }
  showFeeModal.value = true
}

function closeFeeModal() {
  showFeeModal.value = false
  editingFee.value   = null
  feeTargetLevel.value = null
}

async function saveFee() {
  feeError.value = null
  if (!feeForm.value.school_year || !feeForm.value.tuition_amount) {
    feeError.value = 'School year and amount are required.'
    return
  }
  savingFee.value = true
  try {
    const payload = {
      study_level_id:    feeTargetLevel.value.id,
      school_year:       feeForm.value.school_year,
      specialization_id: feeForm.value.specialization_id ?? null,
      tuition_amount:    feeForm.value.tuition_amount,
    }
    if (editingFee.value) {
      await levelFeeService.update(editingFee.value.id, payload)
    } else {
      await levelFeeService.store(payload)
    }
    // Recharger les frais du niveau concerné
    await loadFees(feeTargetLevel.value.id)
    closeFeeModal()
  } catch (e) {
    feeError.value = e.response?.data?.message || 'Failed to save fee schedule'
  } finally {
    savingFee.value = false
  }
}

async function deleteFee(fee, levelId) {
  if (!confirm('Delete this fee schedule entry?')) return
  try {
    await levelFeeService.destroy(fee.id)
    await loadFees(levelId)
  } catch (e) {
    alert(e.response?.data?.message || 'Error')
  }
}

async function submitCopyFees() {
  copyError.value  = null
  copyResult.value = null
  if (!copyForm.value.from_year || !copyForm.value.to_year) {
    copyError.value = 'Sélectionner les deux années.'
    return
  }
  if (copyForm.value.from_year === copyForm.value.to_year) {
    copyError.value = 'Les deux années doivent être différentes.'
    return
  }
  copyingFees.value = true
  try {
    const res        = await levelFeeService.copyYear(copyForm.value.from_year, copyForm.value.to_year)
    copyResult.value = res.data
    if (expandedId.value) await loadFees(expandedId.value)
  } catch (e) {
    copyError.value = e.response?.data?.message || 'Erreur lors de la copie'
  } finally {
    copyingFees.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    loadStudyLevels(),
    specializationService.index().then(r => { specializations.value = r.data?.data ?? r.data ?? [] }).catch(() => {}),
  ])
})
</script>
