<template>
  <div>
    <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-6">Personal Information</h4>

          <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-7 2xl:gap-x-32">
            <div>
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Name</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ form.name || '—' }}</p>
            </div>

            <div>
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Email address</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ form.email || '—' }}</p>
            </div>

            <div>
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Phone</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ form.phone || '—' }}</p>
            </div>

            <div>
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Annexe</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ annexeName || '—' }}</p>
            </div>

            <div>
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Role</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ roleLabel || '—' }}</p>
            </div>

            <div class="lg:col-span-2">
              <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Bio</p>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ form.bio || '—' }}</p>
            </div>
          </div>
        </div>

        <button class="edit-button" @click="isProfileInfoModal = true">Edit</button>
      </div>
    </div>

    <Modal v-if="isProfileInfoModal" @close="isProfileInfoModal = false">
      <template #body>
        <div class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 dark:bg-gray-900 lg:p-11 max-h-[80vh]">
          <!-- close btn -->
          <button @click="isProfileInfoModal = false" class="absolute right-5 top-5 ...">✕</button>

          <div class="px-2 pr-14">
            <h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Edit Personal Information</h4>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400 lg:mb-7">Update your details.</p>
          </div>

          <form @submit.prevent="saveProfile" class="flex flex-col">
            <div class="custom-scrollbar overflow-y-auto p-2 max-h-[60vh]">
              <div class="grid grid-cols-1 gap-x-6 gap-y-5 lg:grid-cols-2">
                <div class="col-span-2">
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Name</label>
                  <input type="text" v-model="form.name" class="h-11 w-full rounded-lg border px-4 py-2.5 text-sm dark:bg-gray-900" />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email Address</label>
                  <input type="email" v-model="form.email" class="h-11 w-full rounded-lg border px-4 py-2.5 text-sm dark:bg-gray-900" />
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Phone</label>
                  <input type="text" v-model="form.phone" class="h-11 w-full rounded-lg border px-4 py-2.5 text-sm dark:bg-gray-900" />
                </div>

                <div class="lg:col-span-2">
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Bio</label>
                  <input type="text" v-model="form.bio" class="h-11 w-full rounded-lg border px-4 py-2.5 text-sm dark:bg-gray-900" />
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
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import Modal from './Modal.vue'
import api from '@/services/api'
import { useAnnexeStore } from '@/stores/useAnnexeStore'

const annexeStore = useAnnexeStore()
const isProfileInfoModal = ref(false)
const form = reactive({
  name: '',
  email: '',
  phone: '',
  bio: '',
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
    form.annexe_id = u.annexe_id ?? u.annexe?.id ?? null
    roleLabel.value = u.role?.name ?? u.role_name ?? u.title ?? u.role ?? ''
    const ann = annexeStore.items?.find(a => String(a.id) === String(form.annexe_id))
    annexeName.value = ann?.name ?? (u.annexe?.name ?? '')
  } catch (e) {
    console.error('Failed to load personal info', e)
  }
}

const saveProfile = async () => {
  try {
    await api.put('admin/me', {
      name: form.name,
      email: form.email,
      phone: form.phone,
      bio: form.bio,
    })
    isProfileInfoModal.value = false
    await load()
  } catch (e) {
    console.error('Failed saving profile info', e)
    alert(e.response?.data?.message || 'Save failed')
  }
}

onMounted(load)
</script>

<style></style>
