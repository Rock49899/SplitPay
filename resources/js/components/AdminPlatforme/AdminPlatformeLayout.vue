<template>
  <div class="min-h-screen bg-[#131313] text-[#e5e2e1]">
    <PlatformSidebar />

    <div
      v-if="isMobileOpen"
      class="fixed inset-0 z-40 bg-black/60 lg:hidden"
      @click="toggleMobileSidebar"
    ></div>

    <div class="min-h-screen lg:ml-64">
      <header class="sticky top-0 z-30 border-b border-[#2a2a2a] bg-[#131313]/90 backdrop-blur">
        <div class="flex items-center justify-between gap-3 px-4 py-4 md:px-8">
          <div class="flex items-center gap-3 md:gap-6">
            <button
              type="button"
              class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-[#202020] text-slate-200 hover:bg-[#2a2a2a] lg:hidden"
              @click="toggleMobileSidebar"
            >
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 6H17M3 10H17M3 14H17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </button>
            <h1 class="text-lg font-extrabold tracking-tight text-[#7bd7bd] md:text-2xl">
              {{ topbarTitle }}
            </h1>
          </div>

          <div class="hidden items-center gap-3 md:flex">
            <div class="relative">
              <input
                v-model="search"
                type="text"
                placeholder="Recherche rapide..."
                @focus="isSearchOpen = true"
                @keydown.enter.prevent="goToFirstMatch"
                class="w-64 rounded-full border border-[#3e4945] bg-[#1b1b1c] px-4 py-2 text-sm text-[#e5e2e1] placeholder:text-[#88938e] focus:border-[#7bd7bd] focus:outline-none"
              />

              <div
                v-if="isSearchOpen && search.trim().length > 0"
                class="absolute left-0 right-0 top-12 z-40 overflow-hidden rounded-xl border border-[#2a2a2a] bg-[#1b1b1c] shadow-2xl"
              >
                <button
                  v-for="item in quickResults"
                  :key="item.name"
                  type="button"
                  @click="goTo(item.to)"
                  class="flex w-full items-center justify-between px-4 py-3 text-left text-sm text-[#e5e2e1] transition hover:bg-[#202020]"
                >
                  <span>{{ item.name }}</span>
                  <span class="text-xs text-[#88938e]">{{ item.hint }}</span>
                </button>

                <p v-if="quickResults.length === 0" class="px-4 py-3 text-xs text-[#88938e]">
                  Aucun résultat pour "{{ search }}"
                </p>
              </div>
            </div>
            <button
              @click="goToFirstMatch"
              class="rounded-xl bg-[#0e7c66] px-4 py-2 text-sm font-bold text-white transition hover:brightness-110"
            >
              Action rapide
            </button>
          </div>
        </div>
      </header>

      <main class="p-4 md:p-8">
        <slot></slot>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSidebar } from '@/composables/useSidebar'
import PlatformSidebar from './PlatformSidebar.vue'

const route = useRoute()
const router = useRouter()
const { isMobileOpen, toggleMobileSidebar } = useSidebar()

const search = ref('')
const isSearchOpen = ref(false)

const topbarTitle = computed(() => route.meta?.title || 'Plateforme')

const quickMenu = [
  { name: 'Dashboard plateforme', to: '/platform', hint: 'Vue globale' },
  { name: 'Institutions', to: '/platform/institutions', hint: 'Gestion établissements' },
  { name: 'Annexes', to: '/platform/annexes', hint: 'Gestion annexes' },
  { name: 'Utilisateurs', to: '/platform/users', hint: 'Comptes et rôles' },
  { name: 'Paramètres plateforme', to: '/platform/settings', hint: 'Configuration' },
  { name: 'Profil plateforme', to: '/platform/profile', hint: 'Mon compte' },
]

const quickResults = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return []
  return quickMenu.filter((item) => {
    const haystack = `${item.name} ${item.hint} ${item.to}`.toLowerCase()
    return haystack.includes(q)
  })
})

const goTo = async (path) => {
  isSearchOpen.value = false
  search.value = ''
  await router.push(path)
}

const goToFirstMatch = async () => {
  if (!search.value.trim()) return
  if (quickResults.value.length === 0) return
  await goTo(quickResults.value[0].to)
}

const onDocumentClick = (event) => {
  if (!event.target) return
  const target = event.target
  if (!(target instanceof HTMLElement)) return
  if (!target.closest('header')) {
    isSearchOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
})

onUnmounted(() => {
  document.removeEventListener('click', onDocumentClick)
})
</script>
