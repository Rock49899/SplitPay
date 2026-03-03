<template>
  <div>
    <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-col items-center w-full gap-6 xl:flex-row">
          <div class="relative w-20 h-20 group">
            <AvatarDisplay 
              :src="avatarPreview || form.avatar_url" 
              :label="form.name" 
              :size="80"
              :clickable="!!(avatarPreview || form.avatar_url)"
              @click="showImageModal = true"
            />
            <label class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40 opacity-0 group-hover:opacity-100 cursor-pointer transition">
              <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <input type="file" class="sr-only" accept="image/jpeg,image/jpg,image/png,image/webp" @change="onAvatarChange" />
            </label>
          </div>

          <div class="order-3 xl:order-2">
            <h4 class="mb-2 text-lg font-semibold text-center text-gray-800 dark:text-white/90 xl:text-left">
              {{ form.name ? `${form.name}` : '—' }}
            </h4>
            <div class="flex flex-col items-center gap-1 text-center xl:flex-row xl:gap-3 xl:text-left">
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ roleLabel }}</p>
              <!-- <div class="hidden h-3.5 w-px bg-gray-300 dark:bg-gray-700 xl:block"></div>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ locationLabel }}</p> -->
            </div>
          </div>

          <div class="flex items-center order-2 gap-2 grow xl:order-3 xl:justify-end">
            <!-- social links removed -->
          </div>
        </div>

        <button @click="isProfileInfoModal = true" class="edit-button">Edit</button>
      </div>
    </div>

    <Modal v-if="isProfileInfoModal" @close="isProfileInfoModal = false">
      <template #body>
        <div class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 dark:bg-gray-900 lg:p-11 max-h-[80vh]">
          <button @click="isProfileInfoModal = false" class="absolute right-5 top-5 z-999 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300">
            <svg
              class="fill-current"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M6.04289 16.5418C5.65237 16.9323 5.65237 17.5655 6.04289 17.956C6.43342 18.3465 7.06658 18.3465 7.45711 17.956L11.9987 13.4144L16.5408 17.9565C16.9313 18.347 17.5645 18.347 17.955 17.9565C18.3455 17.566 18.3455 16.9328 17.955 16.5423L13.4129 12.0002L17.955 7.45808C18.3455 7.06756 18.3455 6.43439 17.955 6.04387C17.5645 5.65335 16.9313 5.65335 16.5408 6.04387L11.9987 10.586L7.45711 6.04439C7.06658 5.65386 6.43342 5.65386 6.04289 6.04439C5.65237 6.43491 5.65237 7.06808 6.04289 7.4586L10.5845 12.0002L6.04289 16.5418Z"
                fill=""
              />
            </svg>
          </button>

          <div class="px-2 pr-14">
            <h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Edit Profile</h4>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400 lg:mb-7">Update your details.</p>
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
                      <input type="file" class="sr-only" accept="image/jpeg,image/jpg,image/png,image/webp" @change="onAvatarChangeModal" />
                    </label>
                  </div>
                  <p class="text-xs text-gray-400 dark:text-gray-500">Hover the photo and click to change</p>
                </div>

                <div class="col-span-2">
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Name</label>
                  <input type="text" v-model="form.name" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label>
                  <input type="email" v-model="form.email" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Phone</label>
                  <input type="text" v-model="form.phone" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" />
                </div>

                <div class="lg:col-span-2">
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Bio</label>
                  <input type="text" v-model="form.bio" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 dark:text-white dark:bg-gray-800 dark:border-gray-700" />
                </div>

                <div class="col-span-2">
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Role</label>
                  <p class="h-11 flex items-center px-4 rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">{{ roleLabel }}</p>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-3 px-2 mt-6 lg:justify-end">
              <button @click.prevent="isProfileInfoModal = false" type="button" class="flex w-full justify-center rounded-lg border px-4 py-2.5 text-sm sm:w-auto">Close</button>
              <button type="submit" class="flex w-full justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white sm:w-auto">Save Changes</button>
            </div>
          </form>
        </div>
      </template>
    </Modal>

    <!-- Modal pour agrandir l'avatar -->
    <ImageViewerModal
      v-model="showImageModal"
      :image-src="avatarPreview || form.avatar_url || ''"
      :image-alt="form.name"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import Modal from './Modal.vue'
import AvatarDisplay from '@/components/shared/AvatarDisplay.vue'
import ImageViewerModal from '@/components/shared/ImageViewerModal.vue'
import api from '@/services/api'
import { useAnnexeStore } from '@/stores/useAnnexeStore'

const annexeStore = useAnnexeStore()
const isProfileInfoModal = ref(false)
const showImageModal = ref(false)

// Avatar
const avatarFile = ref(null)
const avatarPreview = ref(null)

// Hover overlay outside modal → auto-save immediately
const onAvatarChange = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
  saveAvatar(file)
}

// Picker inside the Edit modal → only preview, saved on "Save Changes"
const onAvatarChangeModal = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

// form uses single name field (backend has "name")
const form = reactive({
  name: '',
  email: '',
  phone: '',
  bio: '',
  avatar_url: '',
  city: '',
  state: '',
  annexe_id: null,
})

// computed read-only labels
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
    // ensure annexes available to resolve annexe_id -> name
    await annexeStore.fetchAnnexes()
    const res = await tryFetchUser()
    const u = res.data?.user ?? res.data ?? res.data?.data ?? {}

    // core fields
    form.name = u.name ?? ''
    form.email = u.email ?? ''
    form.phone = u.phone ?? ''
    form.bio = u.bio ?? ''
    form.avatar_url = u.avatar_url ?? u.avatar ?? ''
    form.city = u.city ?? ''
    form.state = u.state ?? ''
    form.annexe_id = u.annexe_id ?? u.annexe?.id ?? null

    // annexe name: prefer relation, else lookup by annexe_id
    annexeName.value = u.annexe?.name ?? (annexeStore.items?.find(a => String(a.id) === String(form.annexe_id))?.name ?? '')

    // role label: prefer user pivots (user_annexes) primary role, else roles array, else u.role
    let role = ''
    if (Array.isArray(u.user_annexes) && u.user_annexes.length) {
      const primary = u.user_annexes.find(x => x.is_primary) || u.user_annexes[0]
      role = primary?.role?.name ?? primary?.role_name ?? ''
    }
    if (!role && Array.isArray(u.roles) && u.roles.length) {
      role = u.roles[0]?.name ?? u.roles[0]?.title ?? ''
    }
    if (!role) {
      role = u.role?.name ?? u.role_name ?? u.title ?? ''
    }
    roleLabel.value = role || '—'
  } catch (e) {
    console.error('Failed to load profile', e)
  }
}

const saveProfile = async () => {
  try {
    const fd = new FormData()
    fd.append('name', form.name)
    fd.append('email', form.email)
    if (form.phone) fd.append('phone', form.phone)
    if (form.bio) fd.append('bio', form.bio)
    if (form.city) fd.append('city', form.city)
    if (form.state) fd.append('state', form.state)
    if (avatarFile.value) fd.append('avatar', avatarFile.value)

    // POST with _method=PATCH for multipart
    fd.append('_method', 'PATCH')
    await api.post('admin/me', fd)

    avatarFile.value = null
    avatarPreview.value = null
    isProfileInfoModal.value = false
    await load()
    
    // Notifier UserMenu de se rafraîchir
    window.dispatchEvent(new CustomEvent('user-profile-updated'))
  } catch (e) {
    console.error('Failed saving profile', e)
    alert(e.response?.data?.message || e.message || 'Save failed')
  }
}

const saveAvatar = async (file) => {
  try {
    const fd = new FormData()
    fd.append('avatar', file)
    fd.append('_method', 'PATCH')
    await api.post('admin/me', fd)
    await load()
    
    // Notifier UserMenu de se rafraîchir
    window.dispatchEvent(new CustomEvent('user-profile-updated'))
  } catch (e) {
    console.error('Avatar upload failed', e)
  }
}

onMounted(load)
</script>

<style scoped>
/* keep existing styles or minimal */
</style>
