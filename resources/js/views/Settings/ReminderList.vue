<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Rappels Automatiques" />
    
    <div class="mt-6">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Configurez les rappels de paiement envoyés automatiquement aux parents et étudiants
        </p>
      </div>

      <button
        @click="openCreateModal"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Nouveau Rappel
      </button>
    </div>

    <!-- Info Box -->
    <!-- <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
      <div class="flex gap-3">
        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="text-sm text-blue-800 dark:text-blue-300">
          <strong>Comment ça fonctionne :</strong> Les rappels sont envoyés automatiquement tous les jours à <strong>8h00</strong>.
          Vous pouvez configurer plusieurs rappels (ex: 7 jours avant, 3 jours avant, jour J).
          Utilisez les variables <code>{student_name}</code>, <code>{amount}</code> et <code>{due_date}</code> dans vos messages.
        </div>
      </div>
    </div> -->

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <!-- Reminders List -->
    <div v-else-if="reminders.length === 0" class="bg-white dark:bg-gray-dark rounded-lg shadow-sm p-12 text-center">
      <svg class="w-20 h-20 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <p class="text-gray-500 dark:text-gray-400 mb-4">Aucun rappel configuré</p>
      <!-- <button
        @click="openCreateModal"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Créer votre premier rappel
      </button> -->
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="reminder in reminders"
        :key="reminder.id"
        class="bg-white dark:bg-gray-dark rounded-lg shadow-sm p-6 transition-all hover:shadow-md"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-3">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Rappel : {{ reminder.days_before }} jour(s) avant échéance
              </h3>
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  reminder.is_active
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                ]"
              >
                {{ reminder.is_active ? '✓ Actif' : '✗ Inactif' }}
              </span>
            </div>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-4">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message :</p>
              <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ reminder.message }}</p>
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400">
              Créé le {{ formatDate(reminder.created_at) }}
            </div>
          </div>

          <!-- Actions Menu -->
          <div class="relative">
            <button
              @click="toggleActionsMenu(reminder.id)"
              class="px-3 py-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
              title="Actions"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
              </svg>
            </button>
            
            <!-- Dropdown Menu -->
            <div v-if="activeMenuId === reminder.id" 
                 class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-10"
                 @click.stop>
              <div class="py-2">
                <!-- Activer/Désactiver -->
                <button
                  v-if="reminder.is_active"
                  @click="deactivateReminder(reminder); activeMenuId = null"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                  </svg>
                  Désactiver
                </button>
                <button
                  v-else
                  @click="activateReminder(reminder); activeMenuId = null"
                  class="w-full text-left px-4 py-2 text-sm text-green-600 dark:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  Activer
                </button>
                
                <!-- Modifier -->
                <button
                  @click="editReminder(reminder); activeMenuId = null"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                  Modifier
                </button>
                
                <!-- Aperçu -->
                <button
                  @click="previewReminder(reminder); activeMenuId = null"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                  Aperçu
                </button>
                
                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                
                <!-- Envoyer maintenant -->
                <button
                  @click="sendNowReminder(reminder); activeMenuId = null"
                  class="w-full text-left px-4 py-2 text-sm text-orange-600 dark:text-orange-400 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                  </svg>
                  Envoyer maintenant
                </button>
                
                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                
                <!-- Supprimer -->
                <button
                  @click="deleteReminder(reminder); activeMenuId = null"
                  class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                  Supprimer
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal || editingReminder" 
           class="fixed inset-0 bg-black/30 dark:bg-black/60 flex items-center justify-center z-[9999] p-4" 
           @click.self="cancelEdit">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto" 
             @click.stop>
          <div class="p-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            {{ editingReminder ? 'Modifier le rappel' : 'Créer un rappel' }}
          </h2>

          <form @submit.prevent="saveReminder" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Annexe
              </label>
              <select
                v-model="form.annexe_id"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
              >
                <option value="">Sélectionner une annexe</option>
                <template v-if="Array.isArray(annexes)">
                  <option v-for="annexe in annexes" :key="annexe?.id || annexe" :value="annexe?.id">
                    {{ annexe?.name || 'Sans nom' }}
                  </option>
                </template>
                <option v-else disabled>Chargement des annexes...</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Envoyer le rappel (jours avant échéance)
              </label>
              <input
                v-model.number="form.days_before"
                type="number"
                min="0"
                max="30"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                placeholder="Ex: 7 (pour 7 jours avant)"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                0 = jour de l'échéance, 3 = 3 jours avant, etc.
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Message du rappel
              </label>
              
              <!-- Variable insertion buttons -->
              <div class="mb-2 flex flex-wrap gap-2">
                <p class="w-full text-xs text-gray-600 dark:text-gray-400 mb-1">Insérer une variable dans le message :</p>
                <button
                  v-for="variable in availableVariables"
                  :key="variable.code"
                  type="button"
                  @click="insertVariable(variable.code)"
                  class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-md bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                  </svg>
                  {{ variable.label }}
                </button>
              </div>
              
              <textarea
                ref="messageTextarea"
                v-model="form.message"
                rows="5"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                placeholder="Écrivez votre message ici, puis cliquez sur les boutons ci-dessus pour insérer les informations dynamiques..."
              ></textarea>
              
              <!-- Live preview -->
              <div v-if="form.message" class="mt-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">📄 Aperçu du message :</p>
                <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ getPreviewMessage() }}</p>
              </div>
              
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Astuce : Positionnez le curseur où vous voulez insérer une variable, puis cliquez sur le bouton correspondant.
              </p>
            </div>

            <div class="flex items-center gap-2">
              <input
                v-model="form.is_active"
                type="checkbox"
                id="is_active"
                class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
              />
              <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">
                Activer immédiatement ce rappel
              </label>
            </div>

            <div class="flex gap-3 pt-4">
              <button
                type="submit"
                :disabled="submitting"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
              </button>
              <button
                type="button"
                @click="cancelEdit"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
              >
                Annuler
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    </Teleport>

    <!-- Preview Modal -->
    <Teleport to="body">
    <div v-if="previewData" class="fixed inset-0 bg-black/30 dark:bg-black/60 flex items-center justify-center z-[9999] p-4" @click.self="previewData = null">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
              Aperçu du rappel ({{ previewData.count }} étudiant(s) concerné(s))
            </h2>
            <button
              @click="previewData = null"
              class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <div v-if="previewData.students.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            Aucun étudiant concerné actuellement
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="student in previewData.students"
              :key="student.student_id"
              class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4"
            >
              <div class="flex items-start justify-between mb-2">
                <div>
                  <h4 class="font-semibold text-gray-900 dark:text-white">{{ student.student_name }}</h4>
                  <p class="text-sm text-gray-600 dark:text-gray-400">{{ student.student_email || student.parent_email }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ student.remaining }} FCFA</p>
                  <p class="text-xs text-gray-500">Échéance: {{ student.due_date }}</p>
                </div>
              </div>

              <div class="mt-3 bg-white dark:bg-gray-900 rounded p-3 text-sm text-gray-600 dark:text-gray-400">
                {{ student.message_preview }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import reminderService from '@/services/reminderService'
import annexeService from '@/services/annexeService'
import { useActiveYearStore } from '@/stores/useActiveYearStore'
import Swal from 'sweetalert2'

const activeYearStore = useActiveYearStore()

const reminders = ref([])
const annexes = ref([])
const loading = ref(false)
const submitting = ref(false)
const showCreateModal = ref(false)
const editingReminder = ref(null)
const previewData = ref(null)

const form = ref({
  annexe_id: '',
  days_before: 7,
  message: '',
  is_active: true,
})

const messageTextarea = ref(null)
const activeMenuId = ref(null)

const availableVariables = [
  { code: '{student_name}', label: 'Nom de l\'étudiant', example: 'Jean Dupont' },
  { code: '{amount}', label: 'Montant', example: '50 000 FCFA' },
  { code: '{due_date}', label: 'Date d\'échéance', example: '15/03/2026' },
  { code: '{payment_link}', label: 'Lien de paiement', example: 'https://votresite.com/payment/abc123' },
]

const insertVariable = (variableCode) => {
  const textarea = messageTextarea.value
  if (!textarea) return
  
  const start = textarea.selectionStart
  const end = textarea.selectionEnd
  const text = form.value.message
  
  // Insert variable at cursor position
  form.value.message = text.substring(0, start) + variableCode + text.substring(end)
  
  // Move cursor after inserted variable
  textarea.focus()
  setTimeout(() => {
    textarea.selectionStart = textarea.selectionEnd = start + variableCode.length
  }, 0)
}

const getPreviewMessage = () => {
  let preview = form.value.message
  availableVariables.forEach(variable => {
    preview = preview.replaceAll(variable.code, variable.example)
  })
  return preview
}

const toggleActionsMenu = (reminderId) => {
  activeMenuId.value = activeMenuId.value === reminderId ? null : reminderId
}

// Fermer le menu si on clique en dehors
const handleClickOutside = (event) => {
  if (activeMenuId.value && !event.target.closest('.relative')) {
    activeMenuId.value = null
  }
}

if (typeof window !== 'undefined') {
  window.addEventListener('click', handleClickOutside)
}

const openCreateModal = async () => {
  console.log('Opening create reminder modal...')
  console.log('showCreateModal before:', showCreateModal.value)
  console.log('annexes:', annexes.value)
  
  // Load annexes if not already loaded
  if (!Array.isArray(annexes.value) || annexes.value.length === 0) {
    console.log('Annexes not loaded, loading now...')
    await loadAnnexes()
  }
  
  showCreateModal.value = true
  console.log('showCreateModal after:', showCreateModal.value)
  
  // Force re-render (sometimes needed)
  await new Promise(resolve => setTimeout(resolve, 100))
  console.log('Modal should be visible now')
}

const loadReminders = async () => {
  try {
    loading.value = true
    const response = await reminderService.getAll({
      school_year: activeYearStore.activeYear || undefined,
    })
    reminders.value = response.data
  } catch (error) {
    console.error('Failed to load reminders:', error)
    Swal.fire('Erreur', 'Impossible de charger les rappels', 'error')
  } finally {
    loading.value = false
  }
}

const loadAnnexes = async () => {
  try {
    console.log('Loading annexes...')
    const response = await annexeService.index({ per_page: 999 })
    console.log('Annexes response:', response.data)
    
    // Handle paginated response
    if (response.data.data) {
      annexes.value = response.data.data
      console.log('Annexes loaded:', annexes.value.length)
    } else {
      annexes.value = response.data
      console.log('Annexes loaded (non-paginated):', annexes.value.length)
    }
  } catch (error) {
    console.error('Failed to load annexes:', error)
    annexes.value = []
  }
}

const saveReminder = async () => {
  try {
    submitting.value = true

    if (editingReminder.value) {
      await reminderService.update(editingReminder.value.id, form.value)
      Swal.fire('Succès', 'Rappel modifié avec succès', 'success')
    } else {
      await reminderService.create(form.value)
      Swal.fire('Succès', 'Rappel créé avec succès', 'success')
    }

    cancelEdit()
    await loadReminders()
  } catch (error) {
    console.error('Failed to save reminder:', error)
    Swal.fire('Erreur', error.response?.data?.message || 'Impossible d\'enregistrer le rappel', 'error')
  } finally {
    submitting.value = false
  }
}

const editReminder = (reminder) => {
  editingReminder.value = reminder
  form.value = {
    annexe_id: reminder.annexe_id,
    days_before: reminder.days_before,
    message: reminder.message,
    is_active: reminder.is_active,
  }
}

const cancelEdit = () => {
  showCreateModal.value = false
  editingReminder.value = null
  form.value = {
    annexe_id: '',
    days_before: 7,
    message: '',
    is_active: true,
  }
}

const activateReminder = async (reminder) => {
  try {
    await reminderService.activate(reminder.id)
    reminder.is_active = true
    Swal.fire('Succès', 'Rappel activé', 'success')
  } catch (error) {
    console.error('Failed to activate reminder:', error)
    Swal.fire('Erreur', 'Impossible d\'activer le rappel', 'error')
  }
}

const deactivateReminder = async (reminder) => {
  try {
    await reminderService.deactivate(reminder.id)
    reminder.is_active = false
    Swal.fire('Succès', 'Rappel désactivé', 'success')
  } catch (error) {
    console.error('Failed to deactivate reminder:', error)
    Swal.fire('Erreur', 'Impossible de désactiver le rappel', 'error')
  }
}

const previewReminder = async (reminder) => {
  try {
    const response = await reminderService.preview(reminder.id, {
      school_year: activeYearStore.activeYear || undefined,
    })
    previewData.value = response.data
  } catch (error) {
    console.error('Failed to preview reminder:', error)
    Swal.fire('Erreur', 'Impossible de générer l\'aperçu', 'error')
  }
}

const sendNowReminder = async (reminder) => {
  try {
    // Confirmation avant envoi
    const result = await Swal.fire({
      title: 'Envoyer maintenant ?',
      html: `
        <p>Ce rappel sera envoyé <strong>immédiatement</strong> à tous les étudiants concernés,</p>
        <p>même si la date programmée (${reminder.days_before} jours avant échéance) n'est pas encore arrivée.</p>
        <br>
        <p class="text-sm text-gray-600">Les emails seront mis en file d'attente et envoyés dans quelques instants.</p>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#f97316',
      confirmButtonText: 'Envoyer maintenant',
      cancelButtonText: 'Annuler',
    })

    if (result.isConfirmed) {
      const response = await reminderService.sendNow(reminder.id, {
        school_year: activeYearStore.activeYear || undefined,
      })
      
      Swal.fire({
        title: 'Envoi programmé !',
        text: response.data.message || `Rappel envoyé à ${response.data.count} étudiant(s)`,
        icon: 'success',
        confirmButtonText: 'OK',
      })
    }
  } catch (error) {
    console.error('Failed to send reminder now:', error)
    Swal.fire(
      'Erreur',
      error.response?.data?.message || 'Impossible d\'envoyer le rappel',
      'error'
    )
  }
}

const deleteReminder = async (reminder) => {
  try {
    const result = await Swal.fire({
      title: 'Confirmer',
      text: 'Supprimer ce rappel ?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Supprimer',
      cancelButtonText: 'Annuler',
    })

    if (result.isConfirmed) {
      await reminderService.delete(reminder.id)
      await loadReminders()
      Swal.fire('Succès', 'Rappel supprimé', 'success')
    }
  } catch (error) {
    console.error('Failed to delete reminder:', error)
    Swal.fire('Erreur', 'Impossible de supprimer le rappel', 'error')
  }
}

const formatDate = (dateString) => {
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return dateString
  }
}

onMounted(() => {
  loadReminders()
  loadAnnexes()
})

watch(() => activeYearStore.activeYear, () => {
  loadReminders()
})
</script>
