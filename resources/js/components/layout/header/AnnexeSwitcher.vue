<template>
  <div v-if="userAnnexes.length > 1" class="relative" ref="dropdownRef">
    <button
      @click="isOpen = !isOpen"
      class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition"
    >
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
      </svg>
      <span class="hidden md:inline">{{ activeAnnexe?.name || 'Sélectionner annexe' }}</span>
      <svg class="w-4 h-4" :class="{ 'rotate-180': isOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 z-50 mt-2 w-72 origin-top-right rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg"
      >
        <div class="p-2">
          <div class="px-3 py-2 mb-1">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
              Mes Annexes
            </p>
          </div>
          
          <button
            v-for="annexe in userAnnexes"
            :key="annexe.id"
            @click="handleSwitchAnnexe(annexe.id)"
            class="w-full px-3 py-2.5 text-left rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            :class="{
              'bg-brand-50 dark:bg-brand-900/20': annexe.id === activeAnnexeId
            }"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                  {{ annexe.name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                  {{ annexe.role?.label || 'Sans rôle' }}
                </p>
              </div>
              
              <div class="flex items-center gap-1.5">
                <span
                  v-if="annexe.is_principal"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400"
                >
                  Principal
                </span>
                
                <svg
                  v-if="annexe.id === activeAnnexeId"
                  class="w-5 h-5 text-brand-600 dark:text-brand-400 flex-shrink-0"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useAnnexeContext } from '@/composables/useAnnexeContext'

const { userAnnexes, activeAnnexe, activeAnnexeId, switchAnnexe } = useAnnexeContext()

const isOpen = ref(false)
const dropdownRef = ref(null)

const handleSwitchAnnexe = async (annexeId) => {
  isOpen.value = false
  
  const success = await switchAnnexe(annexeId)
  if (success) {
    console.log('[AnnexeSwitcher] Annexe switched successfully, permissions updated')
    // Les permissions sont automatiquement mises à jour par switchAnnexe()
    // Les composants réactifs (sidebar, etc.) vont se rafraîchir automatiquement
    // Pas besoin de recharger la page !
  } else {
    console.error('[AnnexeSwitcher] Failed to switch annexe')
  }
}

// Fermer le dropdown si clic à l'extérieur
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.rotate-180 {
  transform: rotate(180deg);
}
</style>
