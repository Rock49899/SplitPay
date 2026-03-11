<template>
  <div>
    <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-6">Informations personnelles</h4>

          <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-7 2xl:gap-x-32">
            <div>
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Nom</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ form.name || '—' }}</p>
            </div>

            <div>
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Adresse email</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ form.email || '—' }}</p>
            </div>

            <div>
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Téléphone</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ form.phone || '—' }}</p>
            </div>

            <div>
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Annexe</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ annexeName || '—' }}</p>
            </div>

            <div>
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Rôle</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ roleLabel || '—' }}</p>
            </div>

            <div class="lg:col-span-2">
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Bio</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ form.bio || '—' }}</p>
            </div>
          </div>
        </div>

        <button class="edit-button" @click="isProfileInfoModal = true">Modifier</button>
      </div>
    </div>

    <Modal v-if="isProfileInfoModal" @close="isProfileInfoModal = false">
      <template #body>
        <div class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 dark:bg-gray-900 lg:p-11 max-h-[80vh]">
          <!-- close btn -->
          <button @click="isProfileInfoModal = false" class="absolute right-5 top-5 ...">✕</button>

          <div class="px-2 pr-14">
            <h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Modifier les informations personnelles</h4>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400 lg:mb-7">Mettez à jour vos informations.</p>
          </div>

          <form @submit.prevent="saveProfile" class="flex flex-col">
            <div class="custom-scrollbar overflow-y-auto p-2 max-h-[60vh]">
              <div class="grid grid-cols-1 gap-x-6 gap-y-5 lg:grid-cols-2">

                <!-- Photo de profil -->
                <div class="col-span-2 flex flex-col items-center gap-2">
                  <div class="relative w-24 h-24 group">
                    <AvatarDisplay
                      :src="avatarPreview || form.avatar_url"
                      :label="form.name"
                      :size="96"
                    />
                    <label class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40 opacity-0 group-hover:opacity-100 cursor-pointer transition">
                      <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <input type="file" class="sr-only" accept="image/jpeg,image/jpg,image/png,image/webp" @change="onAvatarChange" />
                    </label>
                  </div>
                  <p class="text-xs text-gray-400 dark:text-gray-500">Survolez la photo et cliquez pour la modifier</p>
                </div>

                <div class="col-span-2">
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nom</label>
                  <input type="text" v-model="form.name" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Adresse email</label>
                  <input type="email" v-model="form.email" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Téléphone</label>
                  <input type="text" v-model="form.phone" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" />
                </div>

                <div class="lg:col-span-2">
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Bio</label>
                  <input type="text" v-model="form.bio" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" />
                </div>
              </div>
            </div>

            <div class="flex items-center gap-3 px-2 mt-6 lg:justify-end">
              <button @click.prevent="isProfileInfoModal = false" type="button" class="flex w-full justify-center rounded-lg border px-4 py-2.5 text-sm sm:w-auto">Fermer</button>
              <button type="submit" class="flex w-full justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white sm:w-auto">Enregistrer</button>
            </div>
          </form>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import Modal from './Modal.vue'
import AvatarDisplay from '@/components/shared/AvatarDisplay.vue'
import api from '@/services/api'
import { useAnnexeStore } from '@/stores/useAnnexeStore'

const annexeStore = useAnnexeStore()
const isProfileInfoModal = ref(false)

// Avatar
const avatarFile = ref(null)
const avatarPreview = ref(null)

const onAvatarChange = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

const form = reactive({
  name: '',
  email: '',
  phone: '',
  bio: '',
  avatar_url: '',
  annexe_id: null,
})
const annexeName = ref('')
const roleLabel = ref('')

const tryUrls = ['admin/me', 'admin/user/me', 'api/admin/me', 'user', 'api/user']

const tryFetchUser = async () => {
  for (const u of tryUrls) {
    try {
      const res = await api.get(u)
      if (res && res.status >= 200 && res.status < 300) return res
    } catch (err) { /* continue */ }
  }
  throw new Error(`No working user endpoint found (tried: ${tryUrls.join(', ')})`)
}

const load = async () => {
  try {
    await annexeStore.fetchAnnexes()
    const res = await tryFetchUser()
    const u = res.data?.user ?? res.data ?? res.data?.data ?? {}
    form.name = u.name ?? ''
    form.email = u.email ?? ''
    form.phone = u.phone ?? ''
    form.bio = u.bio ?? ''
    form.avatar_url = u.avatar_url ?? u.avatar ?? ''
    form.annexe_id = u.annexe_id ?? u.annexe?.id ?? null
    
    // Récupérer le rôle principal (premier rôle ou rôle avec is_principal)
    if (u.roles && Array.isArray(u.roles) && u.roles.length > 0) {
      // Si le rôle a une propriété 'label', l'utiliser
      roleLabel.value = u.roles[0].label ?? u.roles[0].code ?? u.roles[0]
    } else {
      roleLabel.value = u.role?.label ?? u.role?.name ?? u.role_name ?? u.title ?? u.role ?? ''
    }
    
    // Récupérer l'annexe principale
    if (u.annexes && Array.isArray(u.annexes) && u.annexes.length > 0) {
      // Chercher l'annexe principale ou prendre la première
      const principalAnnexe = u.annexes.find(a => a.is_principal) ?? u.annexes[0]
      annexeName.value = principalAnnexe.name ?? ''
      form.annexe_id = principalAnnexe.id
    } else {
      const ann = annexeStore.items?.find(a => String(a.id) === String(form.annexe_id))
      annexeName.value = ann?.name ?? (u.annexe?.name ?? '')
    }
  } catch (e) {
    console.error('Failed to load personal info', e)
  }
}

const saveProfile = async () => {
  try {
    const fd = new FormData()
    fd.append('name', form.name)
    fd.append('email', form.email)
    if (form.phone) fd.append('phone', form.phone)
    if (form.bio) fd.append('bio', form.bio)
    if (avatarFile.value) fd.append('avatar', avatarFile.value)
    fd.append('_method', 'PATCH')

    await api.post('admin/me', fd)

    avatarFile.value = null
    avatarPreview.value = null
    isProfileInfoModal.value = false
    await load()

    // Notify UserMenu and other consumers to refresh
    window.dispatchEvent(new CustomEvent('user-profile-updated'))
  } catch (e) {
    console.error('Failed saving profile info', e)
    alert(e.response?.data?.message || e.message || 'Save failed')
  }
}

onMounted(load)
</script>

<style></style>
