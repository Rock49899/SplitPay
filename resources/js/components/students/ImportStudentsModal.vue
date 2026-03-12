<template>
  <Modal v-if="isOpen" @close="close">
    <template #body>
      <div class="w-full max-w-3xl bg-white dark:bg-gray-900 rounded-3xl p-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
              Import d'étudiants
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Importer plusieurs étudiants via un fichier Excel
            </p>
          </div>
          <button @click="close" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Étapes -->
        <div class="mb-6">
          <div class="flex items-center space-x-2">
            <div
              v-for="(step, index) in steps"
              :key="index"
              class="flex items-center"
            >
              <div
                :class="[
                  'flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium',
                  currentStep >= index + 1
                    ? 'bg-brand-600 text-white'
                    : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                ]"
              >
                {{ index + 1 }}
              </div>
              <span
                v-if="index < steps.length - 1"
                :class="[
                  'mx-2 h-0.5 w-12',
                  currentStep > index + 1 ? 'bg-brand-600' : 'bg-gray-200 dark:bg-gray-700'
                ]"
              ></span>
            </div>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
            {{ steps[currentStep - 1] }}
          </p>
        </div>

        <!-- Étape 1: Télécharger le template -->
        <div v-if="currentStep === 1" class="space-y-4">
          <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <h3 class="font-medium text-blue-900 dark:text-blue-300 mb-2">
              Téléchargez le template Excel
            </h3>
            <p class="text-sm text-blue-700 dark:text-blue-400 mb-3">
              Le fichier contient les colonnes obligatoires et un exemple de remplissage.
            </p>
            <button
              @click="downloadTemplate"
              :disabled="downloading"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
            >
              {{ downloading ? 'Téléchargement...' : 'Télécharger le template' }}
            </button>
          </div>

          <div class="text-center pt-4">
            <button
              @click="currentStep++"
              class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition"
            >
              Continuer →
            </button>
          </div>
        </div>

        <!-- Étape 2: Upload du fichier -->
        <div v-if="currentStep === 2" class="space-y-4">
          <div
            @dragover.prevent
            @drop.prevent="handleDrop"
            :class="[
              'border-2 border-dashed rounded-lg p-8 text-center transition',
              isDragging
                ? 'border-brand-600 bg-brand-50 dark:bg-brand-900/20'
                : 'border-gray-300 dark:border-gray-700'
            ]"
          >
            <input
              ref="fileInput"
              type="file"
              accept=".xlsx,.xls,.csv"
              @change="handleFileSelect"
              class="hidden"
            />

            <div v-if="!file">
              <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <p class="text-gray-600 dark:text-gray-400 mb-2">
                Glissez-déposez votre fichier ici ou
              </p>
              <button
                @click="$refs.fileInput.click()"
                class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"
              >
                Parcourir les fichiers
              </button>
              <p class="text-xs text-gray-500 mt-2">
                Formats acceptés: .xlsx, .xls, .csv (Max 5MB)
              </p>
            </div>

            <div v-else class="space-y-3">
              <div class="flex items-center justify-center space-x-3">
                <svg class="w-10 h-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ file.name }}</p>
                  <p class="text-sm text-gray-500">{{ formatFileSize(file.size) }}</p>
                </div>
              </div>
              <button
                @click="file = null; preview = null"
                class="text-sm text-red-600 hover:text-red-700"
              >
                Changer de fichier
              </button>
            </div>
          </div>

          <div class="flex justify-between pt-4">
            <button
              @click="currentStep--"
              class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
            >
              ← Retour
            </button>
            <button
              @click="previewFile"
              :disabled="!file || loading"
              class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 transition"
            >
              {{ loading ? 'Analyse...' : 'Analyser le fichier →' }}
            </button>
          </div>
        </div>

        <!-- Étape 3: Preview et validation -->
        <div v-if="currentStep === 3 && preview" class="space-y-4">
          <!-- Statistiques -->
          <div class="grid grid-cols-3 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
              <p class="text-sm text-blue-600 dark:text-blue-400">Total lignes</p>
              <p class="text-2xl font-bold text-blue-900 dark:text-blue-300">
                {{ preview.total_rows }}
              </p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
              <p class="text-sm text-green-600 dark:text-green-400">✓ Valides</p>
              <p class="text-2xl font-bold text-green-900 dark:text-green-300">
                {{ preview.valid_rows }}
              </p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
              <p class="text-sm text-red-600 dark:text-red-400">✗ Erreurs</p>
              <p class="text-2xl font-bold text-red-900 dark:text-red-300">
                {{ preview.invalid_rows }}
              </p>
            </div>
          </div>

          <!-- Erreurs -->
          <div v-if="preview.errors.length > 0" class="max-h-60 overflow-y-auto">
            <h4 class="font-medium text-red-900 dark:text-red-300 mb-2">
              Erreurs détectées :
            </h4>
            <div
              v-for="error in preview.errors"
              :key="error.row"
              class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3 mb-2"
            >
              <p class="font-medium text-red-900 dark:text-red-300">
                Ligne {{ error.row }}
              </p>
              <ul class="text-sm text-red-700 dark:text-red-400 list-disc list-inside">
                <li v-for="(msg, i) in error.errors" :key="i">{{ msg }}</li>
              </ul>
            </div>
          </div>

          <div class="flex justify-between pt-4">
            <button
              @click="currentStep = 2; preview = null"
              class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
            >
              ← Retour
            </button>
            <button
              @click="importData"
              :disabled="preview.valid_rows === 0 || importing"
              class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition"
            >
              {{ importing ? 'Import en cours...' : `Importer ${preview.valid_rows} étudiants` }}
            </button>
          </div>
        </div>

        <!-- Étape 4: Résultat -->
        <div v-if="currentStep === 4 && importResult" class="text-center space-y-4">
          <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
            Import terminé !
          </h3>
          <div class="space-y-2">
            <p class="text-green-600 dark:text-green-400">
              ✓ {{ importResult.imported }} étudiants importés avec succès
            </p>
            <p v-if="importResult.failed > 0" class="text-red-600 dark:text-red-400">
              ✗ {{ importResult.failed }} lignes rejetées
            </p>
          </div>
          <button
            @click="close"
            class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition"
          >
            Fermer
          </button>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import api from '@/services/api'
import Modal from '@/components/shared/Modal.vue'

const props = defineProps({
  modelValue: Boolean,
})

const emit = defineEmits(['update:modelValue', 'imported'])

const isOpen = ref(props.modelValue)
const currentStep = ref(1)
const file = ref(null)
const preview = ref(null)
const importResult = ref(null)
const downloading = ref(false)
const loading = ref(false)
const importing = ref(false)
const isDragging = ref(false)
const fileInput = ref(null)

// Synchroniser isOpen avec modelValue
watch(() => props.modelValue, (newValue) => {
  isOpen.value = newValue
})

const steps = [
  'Télécharger le template',
  'Uploader le fichier',
  'Vérifier les données',
  'Confirmation'
]

const close = () => {
  emit('update:modelValue', false)
  isOpen.value = false
  // Reset
  currentStep.value = 1
  file.value = null
  preview.value = null
  importResult.value = null
}

const downloadTemplate = async () => {
  try {
    downloading.value = true
    
    console.log('Téléchargement du template...')
    console.log('Token présent:', !!api.defaults.headers.common.Authorization)
    
    const response = await api.get('/admin/students/import/template', {
      responseType: 'blob'
    })
    
    console.log('✓ Template reçu:', response.data.size, 'bytes')
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `modele_import_etudiants_${new Date().toISOString().split('T')[0]}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    console.log('✓ Téléchargement terminé')
  } catch (error) {
    console.error('Error downloading template:', error)
    console.error('Response:', error.response)
    
    let errorMessage = 'Erreur lors du téléchargement du template'
    
    if (error.response) {
      if (error.response.status === 401) {
        errorMessage = 'Vous n\'êtes pas authentifié. Veuillez vous reconnecter.'
      } else if (error.response.status === 403) {
        errorMessage = 'Vous n\'avez pas la permission de créer des étudiants.'
      } else if (error.response.data?.message) {
        errorMessage = error.response.data.message
      }
    }
    
    alert(errorMessage)
  } finally {
    downloading.value = false
  }
}

const handleDrop = (e) => {
  isDragging.value = false
  const files = e.dataTransfer.files
  if (files.length > 0) {
    file.value = files[0]
  }
}

const handleFileSelect = (e) => {
  const files = e.target.files
  if (files.length > 0) {
    file.value = files[0]
  }
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const previewFile = async () => {
  if (!file.value) return
  
  try {
    loading.value = true
    const formData = new FormData()
    formData.append('file', file.value)
    
    const response = await api.post('/admin/students/import/preview', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    preview.value = response.data
    currentStep.value = 3
  } catch (error) {
    console.error('Error previewing file:', error)
    alert('Erreur lors de l\'analyse du fichier')
  } finally {
    loading.value = false
  }
}

const importData = async () => {
  if (!file.value) return
  
  try {
    importing.value = true
    const formData = new FormData()
    formData.append('file', file.value)
    
    const response = await api.post('/admin/students/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    importResult.value = response.data
    currentStep.value = 4
    emit('imported', response.data)
  } catch (error) {
    console.error('Error importing data:', error)
    alert('Erreur lors de l\'import')
  } finally {
    importing.value = false
  }
}
</script>
