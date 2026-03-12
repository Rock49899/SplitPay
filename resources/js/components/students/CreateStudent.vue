<template>
  <!-- CreateStudent modal — selects niveau/filière/année, tuition auto-résolu -->
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/50" @click="close"></div>
    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 z-50 w-full max-w-2xl shadow-xl max-h-[88vh] overflow-auto">

      <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Nouvel étudiant</h3>
        <button @click="close" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <!-- Avatar -->
        <div class="sm:col-span-2 flex items-center gap-4">
          <div class="w-14 h-14 rounded-full bg-gray-100 border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
            <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
            <svg v-else class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" />
            </svg>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Photo (optionnel)</label>
            <input type="file" accept="image/jpeg,image/jpg,image/png,image/webp" @change="onAvatarChange"
              class="block text-sm text-gray-500 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100" />
          </div>
        </div>

        <!-- Identité -->
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Prénom *</label>
          <input v-model="form.first_name" placeholder="Prénom"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nom *</label>
          <input v-model="form.last_name" placeholder="Nom de famille"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Email</label>
          <input v-model="form.email" type="email" placeholder="email@exemple.com"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Téléphone</label>
          <input v-model="form.phone" placeholder="+237 6XX XXX XXX"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Matricule *</label>
          <input v-model="form.matricule" placeholder="ex: ETU-2025-001"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
        </div>

        <!-- Annexe -->
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Annexe *</label>
          <select v-model="form.annexe_id"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white">
            <option :value="null">-- Sélectionner une annexe --</option>
            <option v-for="a in annexesLocal" :key="a.id" :value="a.id">{{ a.name }}</option>
          </select>
        </div>

        <!-- Séparateur académique -->
        <div class="sm:col-span-2 border-t border-gray-100 dark:border-gray-800 pt-3">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Informations académiques</p>
        </div>

        <!-- Année scolaire -->
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Année scolaire *</label>
          <select v-model="form.school_year" @change="resolveTuition"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white">
            <option v-for="y in schoolYearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>

        <!-- Filière -->
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Filière</label>
          <select v-model="form.specialization_id" @change="resolveTuition"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white">
            <option :value="null">-- Sélectionner une filière --</option>
            <option v-for="s in specializations" :key="s.id" :value="s.id">{{ s.label }}</option>
          </select>
        </div>

        <!-- Niveau d'études -->
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Niveau d'études</label>
          <select v-model="form.study_level_id" @change="resolveTuition"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white">
            <option :value="null">-- Sélectionner un niveau --</option>
            <option v-for="l in studyLevels" :key="l.id" :value="l.id">{{ l.label }}</option>
          </select>
        </div>

        <!-- Montant scolarité auto-résolu (lecture seule) -->
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Scolarité associée</label>
          <div class="flex items-center gap-2 h-10">
            <svg v-if="tuitionState === 'loading'" class="w-4 h-4 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
            <template v-else-if="resolvedFee">
              <span class="px-3 py-1.5 rounded-lg bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400 text-sm font-semibold">
                {{ fmtAmount(resolvedFee.tuition_amount) }}
              </span>
              <span class="text-xs text-gray-400">{{ resolvedFee.matched_on === 'specific' ? 'tarif filière' : 'tarif générique' }}</span>
            </template>
            <span v-else-if="form.study_level_id && form.school_year" class="text-xs text-orange-500">
              ⚠ Aucun barème configuré
            </span>
            <span v-else class="text-xs text-gray-400">Sélectionner niveau + année</span>
          </div>
        </div>

      </div>

      <!-- Erreur -->
      <div v-if="error" class="mt-3 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2">{{ error }}</div>

      <!-- Actions -->
      <div class="flex gap-2 justify-end mt-5">
        <button @click="close" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-sm">Annuler</button>
        <button @click="submit" :disabled="loading"
          class="px-5 py-2 bg-brand-500 text-white rounded-lg text-sm font-medium disabled:opacity-60">
          <span v-if="!loading">Créer l'étudiant</span>
          <span v-else>Création…</span>
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import studentService from '@/services/studentService'
import studyLevelService from '@/services/studyLevelService'
import specializationService from '@/services/specializationService'
import api from '@/services/api'
import { useAnnexeStore } from '@/stores/useAnnexeStore'
import { useSchoolYear } from '@/composables/useSchoolYear'

const props = defineProps({
  annexes: { type: Array, default: () => [] },
})
const emit = defineEmits(['created', 'close'])

// ── Données de référence ───────────────────────────────────────────────────────
const annexeStore     = useAnnexeStore()
const annexesLocal    = ref(props.annexes ?? [])
const studyLevels     = ref([])
const specializations = ref([])

const { current: currentYear, options: schoolYearOptions } = useSchoolYear(4)

// ── Avatar ─────────────────────────────────────────────────────────────────────
const avatarFile    = ref(null)
const avatarPreview = ref(null)
const onAvatarChange = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  avatarFile.value    = file
  avatarPreview.value = URL.createObjectURL(file)
}

// ── Formulaire ─────────────────────────────────────────────────────────────────
const form = ref({
  first_name:        '',
  last_name:         '',
  email:             '',
  phone:             '',
  matricule:         '',
  annexe_id:         null,
  school_year:       currentYear,
  study_level_id:    null,
  specialization_id: null,
})

// ── Auto-résolution du tarif ───────────────────────────────────────────────────
const resolvedFee  = ref(null)   // { tuition_amount, matched_on }
const tuitionState = ref('idle') // idle | loading | done | none

const resolveTuition = async () => {
  if (!form.value.study_level_id || !form.value.school_year) {
    resolvedFee.value  = null
    tuitionState.value = 'idle'
    return
  }
  tuitionState.value = 'loading'
  try {
    const params = {
      study_level_id: form.value.study_level_id,
      school_year:    form.value.school_year,
    }
    if (form.value.specialization_id) params.specialization_id = form.value.specialization_id
    const res = await api.get('admin/level-fees/resolve', { params })
    resolvedFee.value  = res.data?.data ?? null
    tuitionState.value = resolvedFee.value ? 'done' : 'none'
  } catch {
    resolvedFee.value  = null
    tuitionState.value = 'none'
  }
}

// ── État ───────────────────────────────────────────────────────────────────────
const loading = ref(false)
const error   = ref(null)
const close   = () => emit('close')

// ── Soumission ─────────────────────────────────────────────────────────────────
const submit = async () => {
  error.value = null
  if (!form.value.first_name || !form.value.last_name) { error.value = 'Prénom et nom requis'; return }
  if (!form.value.matricule)  { error.value = 'Le matricule est requis'; return }
  if (!form.value.annexe_id)  { error.value = 'Sélectionner une annexe'; return }
  if (!form.value.school_year){ error.value = "L'année scolaire est requise"; return }

  loading.value = true
  try {
    const fd = new FormData()
    fd.append('first_name',  form.value.first_name)
    fd.append('last_name',   form.value.last_name)
    fd.append('matricule',   form.value.matricule)
    fd.append('annexe_id',   form.value.annexe_id)
    fd.append('school_year', form.value.school_year)
    if (form.value.email)             fd.append('email',             form.value.email)
    if (form.value.phone)             fd.append('phone',             form.value.phone)
    if (form.value.study_level_id)    fd.append('study_level_id',    form.value.study_level_id)
    if (form.value.specialization_id) fd.append('specialization_id', form.value.specialization_id)
    if (avatarFile.value)             fd.append('avatar',            avatarFile.value)

    const res = await studentService.store(fd)
    emit('created', res.data?.student ?? res.data)
    close()
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Échec de la création'
  } finally {
    loading.value = false
  }
}

// ── Formatage ──────────────────────────────────────────────────────────────────
const fmtAmount = (v) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(v)

// ── Initialisation ─────────────────────────────────────────────────────────────
onMounted(async () => {
  if (!annexesLocal.value.length) {
    await annexeStore.fetchAnnexes()
    annexesLocal.value = annexeStore.items
  }
  const [lvlRes, specRes] = await Promise.all([
    studyLevelService.index().catch(() => null),
    specializationService.index().catch(() => null),
  ])
  studyLevels.value     = lvlRes?.data?.data  ?? lvlRes?.data  ?? []
  specializations.value = specRes?.data?.data ?? specRes?.data ?? []
})
</script>

<style scoped></style>
