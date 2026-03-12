<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Annexe Settings" />
    
    <div class="space-y-6">
      <!-- Loading State -->
      <div v-if="loading" class="p-5 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
        <div class="animate-pulse">
          <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-4"></div>
          <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
      </div>

      <!-- Informations générales Card -->
      <div v-else class="p-5 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-6">Informations générales</h4>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-7 2xl:gap-x-32">
              <div>
                <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Nom de l'annexe</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ annexeData.name || '—' }}</p>
              </div>

              <div>
                <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Ville</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ annexeData.city || '—' }}</p>
              </div>

              <div class="lg:col-span-2">
                <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Adresse</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ annexeData.address || '—' }}</p>
              </div>
            </div>
          </div>

          <button class="edit-button" @click="isGeneralInfoModal = true">Modifier</button>
        </div>
      </div>

      <!-- Coordonnées de contact Card -->
      <div v-if="!loading" class="p-5 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-6">Coordonnées de contact</h4>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-7 2xl:gap-x-32">
              <div>
                <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Email de contact</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ annexeData.email || '—' }}</p>
              </div>

              <div>
                <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Téléphone</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ annexeData.phone || '—' }}</p>
              </div>

              <div>
                <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Fax</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ annexeData.fax || '—' }}</p>
              </div>

              <div>
                <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Site web</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ annexeData.website || '—' }}</p>
              </div>
            </div>
          </div>

          <button class="edit-button" @click="isContactInfoModal = true">Modifier</button>
        </div>
      </div>
    </div>

    <!-- Modal Informations générales -->
    <Modal v-if="isGeneralInfoModal" @close="isGeneralInfoModal = false">
      <template #body>
        <div class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 dark:bg-gray-900 lg:p-11 max-h-[80vh]">
          <button @click="isGeneralInfoModal = false" class="absolute right-5 top-5 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>

          <div class="px-2 pr-14">
            <h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Modifier les informations générales</h4>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400 lg:mb-7">Mettez à jour le nom, l'adresse et la ville de l'annexe.</p>
          </div>

          <form @submit.prevent="saveGeneralInfo" class="flex flex-col">
            <div class="custom-scrollbar overflow-y-auto p-2 max-h-[60vh]">
              <div class="grid grid-cols-1 gap-x-6 gap-y-5">
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nom de l'annexe <span class="text-red-500">*</span></label>
                  <input 
                    type="text" 
                    v-model="form.name" 
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" 
                  />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Ville</label>
                  <input 
                    type="text" 
                    v-model="form.city" 
                    class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" 
                  />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Adresse</label>
                  <textarea 
                    v-model="form.address" 
                    rows="3"
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700"
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-3 px-2 mt-6 lg:justify-end">
              <button @click.prevent="isGeneralInfoModal = false" type="button" class="flex w-full justify-center rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 sm:w-auto hover:bg-gray-50 dark:hover:bg-gray-800">Fermer</button>
              <button type="submit" :disabled="submitting" class="flex w-full justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white sm:w-auto hover:bg-brand-600 disabled:opacity-50">
                <span v-if="!submitting">Enregistrer</span>
                <span v-else>Enregistrement...</span>
              </button>
            </div>
          </form>
        </div>
      </template>
    </Modal>

    <!-- Modal Coordonnées de contact -->
    <Modal v-if="isContactInfoModal" @close="isContactInfoModal = false">
      <template #body>
        <div class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 dark:bg-gray-900 lg:p-11 max-h-[80vh]">
          <button @click="isContactInfoModal = false" class="absolute right-5 top-5 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>

          <div class="px-2 pr-14">
            <h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Modifier les coordonnées</h4>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400 lg:mb-7">Mettez à jour les informations de contact de l'annexe.</p>
          </div>

          <form @submit.prevent="saveContactInfo" class="flex flex-col">
            <div class="custom-scrollbar overflow-y-auto p-2 max-h-[60vh]">
              <div class="grid grid-cols-1 gap-x-6 gap-y-5 lg:grid-cols-2">
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email de contact <span class="text-red-500">*</span></label>
                  <input 
                    type="email" 
                    v-model="form.email" 
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" 
                  />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Téléphone <span class="text-red-500">*</span></label>
                  <input 
                    type="tel" 
                    v-model="form.phone" 
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" 
                  />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Fax</label>
                  <input 
                    type="tel" 
                    v-model="form.fax" 
                    class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" 
                  />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Site web</label>
                  <input 
                    type="url" 
                    v-model="form.website" 
                    class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" 
                    placeholder="https://www.exemple.com"
                  />
                </div>
              </div>
            </div>

            <div class="flex items-center gap-3 px-2 mt-6 lg:justify-end">
              <button @click.prevent="isContactInfoModal = false" type="button" class="flex w-full justify-center rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 sm:w-auto hover:bg-gray-50 dark:hover:bg-gray-800">Fermer</button>
              <button type="submit" :disabled="submitting" class="flex w-full justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white sm:w-auto hover:bg-brand-600 disabled:opacity-50">
                <span v-if="!submitting">Enregistrer</span>
                <span v-else>Enregistrement...</span>
              </button>
            </div>
          </form>
        </div>
      </template>
    </Modal>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import Modal from '@/components/profile/Modal.vue'
import api from '@/services/api'
import Swal from 'sweetalert2'

const route = useRoute()
const annexeId = computed(() => route.params.id)

const loading = ref(true)
const submitting = ref(false)
const isGeneralInfoModal = ref(false)
const isContactInfoModal = ref(false)

// Données affichées (lecture seule)
const annexeData = reactive({
  name: '',
  address: '',
  city: '',
  email: '',
  phone: '',
  fax: '',
  website: '',
})

// Formulaire d'édition
const form = reactive({
  name: '',
  address: '',
  city: '',
  email: '',
  phone: '',
  fax: '',
  website: '',
})

// Charger les données de l'annexe
const fetchAnnexe = async () => {
  loading.value = true
  
  try {
    const response = await api.get(`/admin/annexes/${annexeId.value}`)
    const annexe = response.data?.annexe ?? response.data
    
    // Remplir les données d'affichage
    annexeData.name = annexe.name ?? ''
    annexeData.address = annexe.address ?? ''
    annexeData.city = annexe.city ?? ''
    
    // Récupérer les détails depuis annexe_details
    const details = annexe.annexe_details ?? {}
    annexeData.email = details.email ?? ''
    annexeData.phone = details.phone ?? ''
    annexeData.fax = details.fax ?? ''
    annexeData.website = details.website ?? ''
    
    // Copier dans le formulaire
    copyToForm()
  } catch (err) {
    console.error('Error fetching annexe:', err)
    await Swal.fire({
      icon: 'error',
      title: 'Erreur',
      text: err.response?.data?.message || 'Erreur lors du chargement des données'
    })
  } finally {
    loading.value = false
  }
}

// Copier les données vers le formulaire
const copyToForm = () => {
  form.name = annexeData.name
  form.address = annexeData.address
  form.city = annexeData.city
  form.email = annexeData.email
  form.phone = annexeData.phone
  form.fax = annexeData.fax
  form.website = annexeData.website
}

// Sauvegarder les informations générales
const saveGeneralInfo = async () => {
  submitting.value = true
  
  try {
    // Construire annexe_details avec les coordonnées actuelles
    const annexeDetails = {
      email: annexeData.email,
      phone: annexeData.phone,
      fax: annexeData.fax || null,
      website: annexeData.website || null,
    }
    
    const payload = {
      name: form.name,
      address: form.address || null,
      city: form.city || null,
      annexe_details: annexeDetails,
    }
    
    await api.patch(`/admin/annexes/${annexeId.value}`, payload)
    
    // Mettre à jour les données affichées
    annexeData.name = form.name
    annexeData.address = form.address
    annexeData.city = form.city
    
    isGeneralInfoModal.value = false
    
    await Swal.fire({
      icon: 'success',
      title: 'Succès',
      text: 'Informations générales mises à jour',
      showConfirmButton: false,
      timer: 2000
    })
  } catch (err) {
    console.error('Error updating annexe:', err)
    await Swal.fire({
      icon: 'error',
      title: 'Erreur',
      text: err.response?.data?.message || 'Erreur lors de la sauvegarde'
    })
  } finally {
    submitting.value = false
  }
}

// Sauvegarder les coordonnées de contact
const saveContactInfo = async () => {
  submitting.value = true
  
  try {
    // Construire annexe_details
    const annexeDetails = {
      email: form.email,
      phone: form.phone,
      fax: form.fax || null,
      website: form.website || null,
    }
    
    const payload = {
      name: annexeData.name,
      address: annexeData.address || null,
      city: annexeData.city || null,
      annexe_details: annexeDetails,
    }
    
    await api.patch(`/admin/annexes/${annexeId.value}`, payload)
    
    // Mettre à jour les données affichées
    annexeData.email = form.email
    annexeData.phone = form.phone
    annexeData.fax = form.fax
    annexeData.website = form.website
    
    isContactInfoModal.value = false
    
    await Swal.fire({
      icon: 'success',
      title: 'Succès',
      text: 'Coordonnées mises à jour',
      showConfirmButton: false,
      timer: 2000
    })
  } catch (err) {
    console.error('Error updating annexe:', err)
    await Swal.fire({
      icon: 'error',
      title: 'Erreur',
      text: err.response?.data?.message || 'Erreur lors de la sauvegarde'
    })
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchAnnexe()
})
</script>
