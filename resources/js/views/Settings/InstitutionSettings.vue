<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Paramètres institution" />

    <div class="space-y-6">
      <div v-if="loading" class="p-5 border border-slate-200 bg-white rounded-2xl dark:bg-slate-800 dark:border-slate-700 lg:p-6">
        <div class="animate-pulse space-y-3">
          <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/3"></div>
          <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-2/3"></div>
        </div>
      </div>

      <div v-else class="p-5 border border-slate-200 bg-white rounded-2xl dark:bg-slate-800 dark:border-slate-700 lg:p-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex-1">
            <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-6">Informations institution</h4>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-7 2xl:gap-x-32">
              <div>
                <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Nom</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ institution.name || '—' }}</p>
              </div>

              <div>
                <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Email</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ institution.email || '—' }}</p>
              </div>

              <div>
                <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Téléphone</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ institution.phone || '—' }}</p>
              </div>

              <div class="lg:col-span-2">
                <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Logo</p>
                <div class="flex items-center gap-3">
                  <img
                    v-if="institution.logo_url"
                    :src="institution.logo_url"
                    alt="Logo institution"
                    class="h-14 w-auto max-w-[180px] object-contain rounded-lg border border-slate-200 dark:border-slate-700 p-2 bg-white"
                  />
                  <span v-else class="text-sm font-medium text-gray-500 dark:text-gray-400">Aucun logo</span>
                </div>
              </div>
            </div>
          </div>

          <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600" @click="openEditModal">Modifier</button>
        </div>
      </div>
    </div>

    <Modal v-if="isEditModal" @close="closeEditModal">
      <template #body>
        <div class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 dark:bg-slate-800 border border-transparent dark:border-slate-700 lg:p-11 max-h-[80vh]">
          <button @click="closeEditModal" class="absolute right-5 top-5 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>

          <div class="px-2 pr-14">
            <h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Modifier l'institution</h4>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400 lg:mb-7">Mettez à jour le nom, les coordonnées et le logo de votre institution.</p>
          </div>

          <form @submit.prevent="saveInstitution" class="flex flex-col">
            <div class="custom-scrollbar overflow-y-auto p-2 max-h-[60vh]">
              <div class="grid grid-cols-1 gap-x-6 gap-y-5">
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nom <span class="text-red-500">*</span></label>
                  <input
                    type="text"
                    v-model="form.name"
                    required
                    class="h-11 w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:text-white dark:bg-slate-700 dark:border-slate-600"
                  />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label>
                  <input
                    type="email"
                    v-model="form.email"
                    class="h-11 w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:text-white dark:bg-slate-700 dark:border-slate-600"
                  />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Téléphone</label>
                  <input
                    type="tel"
                    v-model="form.phone"
                    class="h-11 w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:text-white dark:bg-slate-700 dark:border-slate-600"
                  />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Logo (optionnel)</label>
                  <input
                    type="file"
                    accept="image/*"
                    @change="onLogoSelected"
                    class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:text-white hover:file:bg-brand-600"
                  />
                  <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF, SVG, WEBP — max 2MB.</p>
                </div>

                <div class="flex items-center gap-2">
                  <input id="remove-logo" type="checkbox" v-model="form.remove_logo" class="h-4 w-4 rounded border-slate-300" />
                  <label for="remove-logo" class="text-sm text-gray-700 dark:text-gray-300">Supprimer le logo actuel</label>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-3 px-2 mt-6 lg:justify-end">
              <button @click.prevent="closeEditModal" type="button" class="flex w-full justify-center rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 sm:w-auto hover:bg-slate-50 dark:hover:bg-slate-600">Fermer</button>
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
import { ref, reactive, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import Modal from '@/components/profile/Modal.vue'
import api from '@/services/api'
import Swal from 'sweetalert2'
import { useInstitutionBrand } from '@/composables/useInstitutionBrand'

const loading = ref(true)
const submitting = ref(false)
const isEditModal = ref(false)
const institutionId = ref(null)
const selectedLogo = ref(null)
const { loadBrand } = useInstitutionBrand()

const institution = reactive({
  id: '',
  name: '',
  email: '',
  phone: '',
  logo: null,
  logo_url: null,
})

const form = reactive({
  name: '',
  email: '',
  phone: '',
  remove_logo: false,
})

const syncForm = () => {
  form.name = institution.name || ''
  form.email = institution.email || ''
  form.phone = institution.phone || ''
  form.remove_logo = false
  selectedLogo.value = null
}

const fetchInstitution = async () => {
  loading.value = true

  try {
    const meRes = await api.get('/admin/me')
    const me = meRes.data?.user ?? meRes.data
    const principalAnnexe = (me?.annexes ?? []).find(a => a.is_principal) ?? (me?.annexes ?? [])[0]

    if (!principalAnnexe?.id) {
      throw new Error('Annexe introuvable pour cet utilisateur.')
    }

    const annexeRes = await api.get(`/admin/annexes/${principalAnnexe.id}`)
    const annexe = annexeRes.data?.annexe ?? annexeRes.data

    if (!annexe?.institution_id) {
      throw new Error('Institution introuvable pour cette annexe.')
    }

    institutionId.value = annexe.institution_id

    const institutionRes = await api.get(`/admin/institutions/${institutionId.value}`)
    const data = institutionRes.data?.institution ?? institutionRes.data

    institution.id = data.id
    institution.name = data.name ?? ''
    institution.email = data.email ?? ''
    institution.phone = data.phone ?? ''
    institution.logo = data.logo ?? null
    institution.logo_url = data.logo ? `/storage/${data.logo}` : null

    syncForm()
  } catch (err) {
    await Swal.fire({
      icon: 'error',
      title: 'Erreur',
      text: err.response?.data?.message || err.message || 'Erreur lors du chargement de l\'institution',
    })
  } finally {
    loading.value = false
  }
}

const openEditModal = () => {
  syncForm()
  isEditModal.value = true
}

const closeEditModal = () => {
  isEditModal.value = false
}

const onLogoSelected = (event) => {
  const file = event.target?.files?.[0]
  if (!file) {
    selectedLogo.value = null
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    Swal.fire({ icon: 'error', title: 'Fichier trop volumineux', text: 'Le logo ne doit pas dépasser 2MB.' })
    event.target.value = ''
    selectedLogo.value = null
    return
  }

  selectedLogo.value = file
  form.remove_logo = false
}

const saveInstitution = async () => {
  if (!institutionId.value) return

  submitting.value = true
  try {
    const payload = new FormData()
    payload.append('name', form.name)
    payload.append('email', form.email || '')
    payload.append('phone', form.phone || '')
    payload.append('remove_logo', form.remove_logo ? '1' : '0')

    if (selectedLogo.value) {
      payload.append('logo', selectedLogo.value)
    }

    payload.append('_method', 'PATCH')

    await api.post(`/admin/institutions/${institutionId.value}`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    await fetchInstitution()
    await loadBrand(true)
    window.dispatchEvent(new CustomEvent('institution-brand-updated'))
    closeEditModal()

    await Swal.fire({
      icon: 'success',
      title: 'Succès',
      text: 'Institution mise à jour.',
      timer: 2000,
      showConfirmButton: false,
    })
  } catch (err) {
    await Swal.fire({
      icon: 'error',
      title: 'Erreur',
      text: err.response?.data?.message || 'Impossible de mettre à jour l\'institution.',
    })
  } finally {
    submitting.value = false
  }
}

onMounted(fetchInstitution)
</script>

<style scoped></style>
