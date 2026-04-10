<template>
  <div v-if="visible" class="pointer-events-none fixed inset-0 z-[100000]">
    <div
      v-if="hintVisible"
      class="pointer-events-none fixed max-w-xs rounded-xl border border-brand-200 bg-white/95 px-3 py-2 text-xs text-gray-700 shadow-xl dark:border-brand-800 dark:bg-gray-900/95 dark:text-gray-200"
      :style="hintStyle"
    >
      <div class="font-semibold text-brand-600 dark:text-brand-400">{{ currentStep.hintTitle }}</div>
      <div class="mt-1">{{ currentStep.hintText }}</div>
      <span class="tour-arrow" :style="arrowStyle"></span>
    </div>

    <div class="pointer-events-auto fixed bottom-6 right-6 w-[360px] rounded-2xl border border-gray-200 bg-white p-4 shadow-2xl dark:border-gray-700 dark:bg-gray-900">
      <div class="mb-2 flex items-center justify-between">
        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Guide de démarrage</p>
        <span class="text-xs text-gray-500">Étape {{ stepIndex + 1 }} / {{ steps.length }}</span>
      </div>

      <h3 class="text-sm font-semibold text-brand-600 dark:text-brand-400">{{ currentStep.title }}</h3>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ currentStep.description }}</p>

      <div class="mt-3 flex flex-wrap gap-1.5">
        <span
          v-for="(s, idx) in steps"
          :key="s.id"
          class="h-1.5 flex-1 rounded-full"
          :class="idx <= stepIndex ? 'bg-brand-500' : 'bg-gray-200 dark:bg-gray-700'"
        />
      </div>

      <div class="mt-4 flex items-center justify-between gap-2">
        <button
          type="button"
          class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
          @click="skipTour"
        >
          Ignorer le guide
        </button>

        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
            :disabled="stepIndex === 0"
            @click="prevStep"
          >
            Précédent
          </button>

          <button
            v-if="currentStep.route"
            type="button"
            class="rounded-lg border border-brand-300 px-3 py-2 text-xs font-medium text-brand-600 hover:bg-brand-50 dark:border-brand-700 dark:text-brand-300 dark:hover:bg-brand-900/30"
            @click="goToStepRoute"
          >
            Ouvrir la page
          </button>

          <button
            type="button"
            class="rounded-lg bg-brand-500 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-600"
            @click="nextOrFinish"
          >
            {{ stepIndex === steps.length - 1 ? 'Terminer' : 'Passer à l\'étape suivante' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePermissions } from '@/composables/usePermissions'

const route = useRoute()
const router = useRouter()
const { isSuperAdminInstitution, currentUser } = usePermissions()

const steps = [
  {
    id: 'academic-menu',
    title: 'Commencer par le menu Académique',
    description: 'Cliquez sur « Académique » dans la sidebar pour ouvrir les options de paramétrage de base.',
    target: 'academic',
    hintTitle: 'Étape 1',
    hintText: 'Ouvrez le menu Académique.',
    route: null,
    openAcademic: true,
  },
  {
    id: 'specializations',
    title: 'Ajouter les différentes filières',
    description: 'Renseignez les spécialisations/filières de votre annexe principale pour structurer les inscriptions.',
    target: 'specializations',
    hintTitle: 'Étape 2',
    hintText: 'Cliquez sur « Spécialisations ».',
    route: '/admin/specializations',
    openAcademic: true,
  },
  {
    id: 'study-levels',
    title: 'Configurer niveaux d\'étude et scolarité',
    description: 'Ajoutez les niveaux d\'étude et définissez la scolarité associée à chaque niveau.',
    target: 'study-levels',
    hintTitle: 'Étape 3',
    hintText: 'Cliquez sur « Niveaux d\'étude ».',
    route: '/admin/study-levels',
    openAcademic: true,
  },
  {
    id: 'annexes',
    title: 'Créer les annexes secondaires',
    description: 'Ajoutez vos annexes/branches secondaires pour gérer les opérations multi-sites.',
    target: 'annexes',
    hintTitle: 'Étape 4',
    hintText: 'Cliquez sur « Annexes ».',
    route: '/admin/annexes',
  },
  {
    id: 'users',
    title: 'Ajouter les utilisateurs',
    description: 'Créez les comptes des collaborateurs (gestionnaire, comptable, etc.) avec les bons rôles.',
    target: 'users',
    hintTitle: 'Étape 5',
    hintText: 'Cliquez sur « Utilisateurs ».',
    route: '/admin/users',
  },
  {
    id: 'students',
    title: 'Ajouter les étudiants',
    description: 'Terminez par l\'ajout des étudiants pour démarrer la gestion académique et financière.',
    target: 'students',
    hintTitle: 'Étape 6',
    hintText: 'Cliquez sur « Étudiants ».',
    route: '/admin/students',
  },
]

const stepIndex = ref(0)
const hintStyle = ref({ left: '16px', top: '100px' })
const arrowStyle = ref({ left: '-7px', top: '18px' })
const hintVisible = ref(false)
let positionTimer = null

const storageKey = computed(() => `splitpay_admin_onboarding_done_v1_${currentUser.value?.id || 'guest'}`)
const isDone = ref(false)

const visible = computed(() => {
  if (!isSuperAdminInstitution.value) return false
  if (isDone.value) return false
  return true
})

const currentStep = computed(() => steps[stepIndex.value])

function emitFocusTarget() {
  if (!visible.value) return
  const step = currentStep.value
  window.dispatchEvent(new CustomEvent('onboarding:focus-target', {
    detail: {
      target: step.target,
      forceExpand: true,
      openAcademic: !!step.openAcademic,
    },
  }))
}

async function updateHintPosition() {
  if (!visible.value) return
  const target = currentStep.value?.target
  if (!target) return

  const el = document.querySelector(`[data-onboarding-target="${target}"]`)
  if (!el) {
    hintVisible.value = false
    return
  }

  const rect = el.getBoundingClientRect()
  const bubbleWidth = 280
  const top = Math.max(80, rect.top + rect.height / 2 - 34)
  let left = rect.right + 12

  if (left + bubbleWidth > window.innerWidth - 12) {
    left = Math.max(12, rect.left - bubbleWidth - 12)
    arrowStyle.value = { right: '-7px', top: '18px' }
  } else {
    arrowStyle.value = { left: '-7px', top: '18px' }
  }

  hintStyle.value = {
    left: `${Math.round(left)}px`,
    top: `${Math.round(top)}px`,
  }
  hintVisible.value = true
}

function nextOrFinish() {
  if (stepIndex.value >= steps.length - 1) {
    finishTour()
    return
  }
  stepIndex.value += 1
}

function prevStep() {
  if (stepIndex.value > 0) stepIndex.value -= 1
}

function finishTour() {
  localStorage.setItem(storageKey.value, '1')
  isDone.value = true
  clearHighlight()
}

function skipTour() {
  finishTour()
}

function clearHighlight() {
  window.dispatchEvent(new CustomEvent('onboarding:focus-target', {
    detail: {
      target: null,
      forceExpand: false,
      openAcademic: false,
    },
  }))
}

function goToStepRoute() {
  const targetRoute = currentStep.value?.route
  if (!targetRoute) return
  router.push(targetRoute)
}

onMounted(async () => {
  isDone.value = localStorage.getItem(storageKey.value) === '1'
  if (!visible.value) return

  await nextTick()
  emitFocusTarget()
  updateHintPosition()

  positionTimer = window.setInterval(updateHintPosition, 350)
  window.addEventListener('resize', updateHintPosition, { passive: true })
  window.addEventListener('scroll', updateHintPosition, true)
})

onBeforeUnmount(() => {
  if (positionTimer) window.clearInterval(positionTimer)
  window.removeEventListener('resize', updateHintPosition)
  window.removeEventListener('scroll', updateHintPosition, true)
  clearHighlight()
})

watch([stepIndex, visible], async () => {
  if (!visible.value) return
  await nextTick()
  emitFocusTarget()
  updateHintPosition()
})

watch(() => route.fullPath, async () => {
  if (!visible.value) return
  await nextTick()
  updateHintPosition()
})
</script>

<style scoped>
.tour-arrow {
  position: absolute;
  width: 12px;
  height: 12px;
  transform: rotate(45deg);
  background: inherit;
  border-left: 1px solid rgb(191 219 254 / 1);
  border-bottom: 1px solid rgb(191 219 254 / 1);
}
</style>
