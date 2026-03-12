<template>
  <div class="relative" ref="dropdownRef">
    <button
      class="flex items-center text-gray-700 dark:text-gray-400"
      @click.prevent="toggleDropdown"
    >
      <span class="mr-3 relative" @click.stop="() => { if (user.avatar_url) showImageModal = true }">
        <AvatarDisplay 
          :src="user.avatar_url" 
          :label="user.name" 
          :size="44"
          :clickable="!!user.avatar_url"
          @click="showImageModal = true"
        />
      </span>

      <span class="block mr-1 font-medium text-theme-sm">{{ user.name || 'User' }}</span>

      <ChevronDownIcon :class="{ 'rotate-180': dropdownOpen }" />
    </button>

    <!-- Dropdown Start -->
    <div
      v-if="dropdownOpen"
      class="absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark"
    >
      <div>
        <span class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400">
          {{ user.name || '—' }}
        </span>
        <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">
          {{ user.email || '—' }}
        </span>
      </div>

      <ul class="flex flex-col gap-1 pt-4 pb-3 border-b border-gray-200 dark:border-gray-800">
        <li v-for="item in menuItems" :key="item.href">
          <router-link
            :to="item.href"
            class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
            @click="closeDropdown"
          >
            <component
              :is="item.icon"
              class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300"
            />
            {{ item.text }}
          </router-link>
        </li>
      </ul>
      <button
        @click="signOut"
        class="flex items-center gap-3 px-3 py-2 mt-3 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
      >
        <LogoutIcon
          class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300"
        />
        Se déconnecter
      </button>
    </div>
    <!-- Dropdown End -->

    <!-- Modal pour agrandir l'avatar -->
    <ImageViewerModal
      v-model="showImageModal"
      :image-src="user.avatar_url || ''"
      :image-alt="user.name"
    />
  </div>
</template>

<script setup>
import { UserCircleIcon, ChevronDownIcon, LogoutIcon, SettingsIcon, InfoCircleIcon } from '@/icons'
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { usePermissions } from '@/composables/usePermissions'
import { useAnnexeContext } from '@/composables/useAnnexeContext'
import AvatarDisplay from '@/components/shared/AvatarDisplay.vue'
import ImageViewerModal from '@/components/shared/ImageViewerModal.vue'

const router = useRouter()
const dropdownOpen = ref(false)
const dropdownRef = ref(null)
const showImageModal = ref(false)
const { setUser, clearPermissions, hasAnyRole } = usePermissions()
const { initializeContext, resetContext } = useAnnexeContext()

// données de l'utilisateur connecté
const user = ref({ name: '', email: '', avatar_url: '', scope: '', annexe_id: null })

// éléments du menu (calculés dynamiquement selon le rôle)
const menuItems = computed(() => {
  const items = [
    { href: '/profile', icon: UserCircleIcon, text: 'Modifier le profil' },
  ]
  
  // Ajouter "Paramètres annexe" uniquement pour les admins avec annexe_id valide
  if (user.value.annexe_id && hasAnyRole(['super_admin_institution', 'super_admin_annexe', 'admin_annexe'])) {
    items.push({ 
      href: `/admin/annexe/${user.value.annexe_id}/settings`, 
      icon: SettingsIcon, 
      text: 'Paramètres annexe' 
    })
  }
  
  return items
})

const toggleDropdown = () => { dropdownOpen.value = !dropdownOpen.value }
const closeDropdown = () => { dropdownOpen.value = false }

// endpoints candidats pour récupérer l'utilisateur courant
const tryUrls = ['admin/me', 'me', 'user', 'api/user', 'api/admin/me']

// vérifie si l'utilisateur semble authentifié côté client (simple check token)
const hasAuth = () => {
	// adapter si vous stockez le token ailleurs (cookie, vuex, pinia...)
	try {
		return !!localStorage.getItem('api_token')
	} catch {
		return false
	}
}

const fetchCurrentUser = async () => {
  for (const u of tryUrls) {
    try {
      const res = await api.get(u)
      console.log('[UserMenu] tried', u, 'status', res.status)
      if (res && res.status >= 200 && res.status < 300) {
        const payload = res.data?.user ?? res.data ?? res.data?.data ?? {}
        console.log('[UserMenu] user payload', payload)
        user.value.name = payload.name ?? payload.first_name ?? payload.username ?? ''
        user.value.email = payload.email ?? ''
        user.value.avatar_url = payload.avatar_url ?? payload.avatar ?? ''
        user.value.scope = payload.scope ?? ''
        user.value.annexe_id = payload.annexe_id ?? null
        
        // Si super admin institution sans annexe_id, récupérer l'annexe principale
        if (!user.value.annexe_id && payload.scope === 'institution') {
          try {
            const annexesRes = await api.get('/admin/annexes?per_page=1')
            const annexes = annexesRes.data?.data ?? annexesRes.data
            if (annexes && annexes.length > 0) {
              user.value.annexe_id = annexes[0].id
            }
          } catch (err) {
            console.warn('[UserMenu] Failed to fetch principal annexe:', err)
          }
        }
        
        // Initialiser le contexte multi-annexe AVANT setUser
        // pour que les bonnes permissions soient appliquées
        initializeContext(payload)
        
        // Note: setUser est appelé dans initializeContext après avoir appliqué
        // les permissions de l'annexe active, donc on ne l'appelle pas ici
        
        return
      }
    } catch (err) {
      // log léger pour debug (évite spam)
      console.warn('[UserMenu] endpoint failed:', u, err?.response?.status)
    }
  }
}

const signOut = async () => {
  try {
    const endpoints = ['logout', 'admin/logout', 'auth/logout']
    for (const ep of endpoints) {
      try {
        await api.post(ep)
        break
      } catch (err) {
      }
    }
  } catch (e) {
    console.error('Logout failed', e)
  } finally {
    // supprimer token local et permissions
    try { localStorage.removeItem('api_token') } catch {}
    clearPermissions()
    resetContext()
    closeDropdown()
    
    // Redirection intelligente : seulement si on n'est pas déjà sur une page publique
    const currentPath = router.currentRoute.value.path
    const publicRoutes = ['/', '/signin', '/signup']
    
    if (!publicRoutes.includes(currentPath)) {
      router.push({ name: 'Signin' })
    }
  }
}

// fermer le dropdown si clic à l'extérieur
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  // Écouter les mises à jour du profil utilisateur
  window.addEventListener('user-profile-updated', fetchCurrentUser)
  fetchCurrentUser()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('user-profile-updated', fetchCurrentUser)
})

// Exposer fetchCurrentUser pour pouvoir recharger l'avatar depuis d'autres composants
defineExpose({
  refreshUser: fetchCurrentUser
})
</script>

<style scoped>
/* ...existing styles... */
</style>
