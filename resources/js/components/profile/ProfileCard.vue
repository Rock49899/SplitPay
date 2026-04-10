<template>
  <div>
    <div class="p-5 mb-6 border border-slate-200 rounded-2xl bg-white dark:bg-slate-800 dark:border-slate-700 lg:p-6">
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

        <button @click="openUnifiedProfileModal" class="edit-button">Modifier</button>
      </div>
    </div>

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
import AvatarDisplay from '@/components/shared/AvatarDisplay.vue'
import ImageViewerModal from '@/components/shared/ImageViewerModal.vue'
import api from '@/services/api'
import { useAnnexeStore } from '@/stores/useAnnexeStore'

const annexeStore = useAnnexeStore()
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

const openUnifiedProfileModal = () => {
  window.dispatchEvent(new CustomEvent('open-profile-edit-modal'))
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
