<template>
  <aside
    :class="[
      'fixed left-0 top-0 z-50 flex h-screen w-64 flex-col border-r border-[#2a2a2a] bg-[#131313] py-6 transition-transform duration-300',
      isMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
    ]"
  >
    <div class="mb-8 px-6">
      <router-link to="/platform" class="flex items-center gap-3" @click="closeMobileIfNeeded">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0e7c66] text-base font-extrabold text-white">SP</div>
        <div>
          <p class="text-lg font-extrabold tracking-tight text-white">SplitPay</p>
          <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Admin Plateforme</p>
        </div>
      </router-link>
    </div>

    <nav class="flex-1 space-y-1 px-2">
      <router-link
        v-for="item in items"
        :key="item.name"
        :to="item.path"
        @click="closeMobileIfNeeded"
        :class="[
          'mx-1 flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition-all duration-200',
          isActive(item.path)
            ? 'scale-[0.98] bg-[#0E7C66] text-white'
            : 'text-slate-400 hover:bg-[#202020] hover:text-white',
        ]"
      >
        <component :is="item.icon" class="h-5 w-5" />
        <span>{{ item.name }}</span>
      </router-link>
    </nav>

    <div class="space-y-1 px-2">
      <router-link
        to="/platform/profile"
        @click="closeMobileIfNeeded"
        :class="[
          'mx-1 flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition-all duration-200',
          isActive('/platform/profile')
            ? 'scale-[0.98] bg-[#0E7C66] text-white'
            : 'text-slate-400 hover:bg-[#202020] hover:text-white',
        ]"
      >
        <UserCircleIcon class="h-5 w-5" />
        <span>Profil</span>
      </router-link>

      <button
        type="button"
        @click="logout"
        class="mx-1 flex w-[calc(100%-0.5rem)] items-center justify-center rounded-xl bg-[#93000a]/80 px-4 py-3 text-sm font-bold text-white transition hover:bg-[#93000a]"
      >
        Déconnexion
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSidebar } from '@/composables/useSidebar'
import api from '@/services/api'
import { applyToken } from '@/services/api'
import { usePermissions } from '@/composables/usePermissions'
import { useAnnexeContext } from '@/composables/useAnnexeContext'
import { LayoutDashboardIcon, UserCircleIcon, AnnexeIcon, PlugInIcon, UserGroupIcon } from '@/icons'

const route = useRoute()
const router = useRouter()
const { isMobileOpen, toggleMobileSidebar } = useSidebar()
const { clearPermissions } = usePermissions()
const { resetContext } = useAnnexeContext()

const items = computed(() => [
  { icon: LayoutDashboardIcon, name: 'Dashboard', path: '/platform' },
  { icon: AnnexeIcon, name: 'Institutions', path: '/platform/institutions' },
  { icon: AnnexeIcon, name: 'Annexes', path: '/platform/annexes' },
  { icon: UserGroupIcon, name: 'Utilisateurs', path: '/platform/users' },
  { icon: PlugInIcon, name: 'Paramètres', path: '/platform/settings' },
])

const isActive = (path) => route.path === path || route.path.startsWith(`${path}/`)

const closeMobileIfNeeded = () => {
  if (isMobileOpen.value) {
    toggleMobileSidebar()
  }
}

const logout = async () => {
  try {
    const endpoints = ['admin/logout', 'logout', 'auth/logout']
    for (const endpoint of endpoints) {
      try {
        await api.post(endpoint)
        break
      } catch {
      }
    }
  } finally {
    localStorage.removeItem('api_token')
    localStorage.removeItem('user')
    localStorage.removeItem('session_started_at')
    localStorage.removeItem('session_last_activity_at')
    applyToken(null)
    clearPermissions()
    resetContext()
    router.push({ name: 'Signin' })
  }
}
</script>
