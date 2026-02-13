<template>
  <div class="relative" ref="dropdownRef">
    <button
      class="flex items-center text-gray-700 dark:text-gray-400"
      @click.prevent="toggleDropdown"
    >
      <span class="mr-3 overflow-hidden rounded-full h-11 w-11">
        <img :src="user.avatar_url || '/images/user/owner.jpg'" alt="User" />
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
        Sign out
      </button>
    </div>
    <!-- Dropdown End -->
  </div>
</template>

<script setup>
// filepath: /home/rock/PIEUVRE/Saas-schooling-project/resources/js/components/layout/header/UserMenu.vue
import { UserCircleIcon, ChevronDownIcon, LogoutIcon, SettingsIcon, InfoCircleIcon } from '@/icons'
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const dropdownOpen = ref(false)
const dropdownRef = ref(null)

// données de l'utilisateur connecté
const user = ref({ name: '', email: '', avatar_url: '' })

// éléments du menu
const menuItems = [
  { href: '/profile', icon: UserCircleIcon, text: 'Edit profile' },
  { href: '/chat', icon: SettingsIcon, text: 'Account settings' },
  { href: '/profile', icon: InfoCircleIcon, text: 'Support' },
]

const toggleDropdown = () => { dropdownOpen.value = !dropdownOpen.value }
const closeDropdown = () => { dropdownOpen.value = false }

// essayer plusieurs endpoints pour récupérer l'utilisateur courant
const tryUrls = ['admin/me', 'me', 'user']

// vérifie si l'utilisateur semble authentifié côté client (simple check token)
const hasAuth = () => {
	// adapter si vous stockez le token ailleurs (cookie, vuex, pinia...)
	try {
		return !!localStorage.getItem('token')
	} catch {
		return false
	}
}

const fetchCurrentUser = async () => {
	// ne pas appeler les endpoints si pas authentifié
	if (!hasAuth()) return
	for (const u of tryUrls) {
		try {
			const res = await api.get(u)
			if (res && res.status >= 200 && res.status < 300) {
				const payload = res.data?.user ?? res.data ?? res.data?.data ?? {}
				user.value.name = payload.name ?? payload.first_name ?? payload.username ?? ''
				user.value.email = payload.email ?? ''
				user.value.avatar_url = payload.avatar_url ?? payload.avatar ?? ''
				return
			}
		} catch (err) {
			// essayer le prochain endpoint — on évite d'afficher l'erreur ici
		}
	}
	// si aucun endpoint ne répond, on reste en fallback silencieux
}

// déconnecter l'utilisateur : essayer plusieurs endpoints, puis nettoyer le client
const signOut = async () => {
  try {
    const endpoints = ['logout', 'admin/logout', 'auth/logout']
    for (const ep of endpoints) {
      try {
        await api.post(ep)
        break
      } catch (err) {
        // essayer le suivant
      }
    }
  } catch (e) {
    console.error('Logout failed', e)
  } finally {
    // supprimer token local et rediriger vers signin
    try { localStorage.removeItem('token') } catch {}
    closeDropdown()
    router.push('/signin')
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
  // n'appeler fetchCurrentUser que si on a un token local (évite 401/404 console)
  if (hasAuth()) fetchCurrentUser()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* ...existing styles... */
</style>
