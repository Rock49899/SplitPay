<template>
  <!-- Chargement -->
  <div v-if="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-5">
    <div class="animate-pulse rounded-2xl bg-brand-100/60 p-6 sm:col-span-2 xl:row-span-2 dark:bg-brand-500/10">
      <div class="h-3 w-24 rounded bg-brand-200/70 dark:bg-brand-500/20"></div>
      <div class="mt-4 h-9 w-48 rounded bg-brand-200/70 dark:bg-brand-500/20"></div>
    </div>
    <div
      v-for="n in 4" :key="n"
      class="animate-pulse rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900"
    >
      <div class="h-3 w-20 rounded bg-gray-200 dark:bg-gray-700"></div>
      <div class="mt-3 h-6 w-28 rounded bg-gray-200 dark:bg-gray-700"></div>
    </div>
  </div>

  <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-5">
    <!-- Carte principale : encaissé + recouvrement -->
    <div class="relative overflow-hidden rounded-2xl bg-brand-600 p-6 text-white sm:col-span-2 xl:row-span-2 dark:bg-brand-700">
      <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/5"></div>
      <div class="pointer-events-none absolute -right-4 top-24 h-40 w-40 rounded-full bg-white/5"></div>

      <div class="relative flex h-full flex-col justify-between gap-8">
        <div>
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-brand-100">Encaissé cette année</p>
            <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium text-brand-50">{{ schoolYear }}</span>
          </div>
          <p class="mt-3 text-4xl font-bold tracking-tight">{{ fmtMoney(kpis.total_collected) }}</p>
          <p v-if="kpis.total_tuition" class="mt-1 text-sm text-brand-100/80">
            sur {{ fmtMoney(kpis.total_tuition) }} de scolarité attendue
          </p>
        </div>

        <div>
          <div class="flex items-baseline justify-between">
            <p class="text-sm text-brand-100">Taux de recouvrement</p>
            <p class="text-2xl font-bold">{{ fmtRate(kpis.recovery_rate) }}</p>
          </div>
          <div class="mt-3 h-2.5 w-full rounded-full bg-white/15">
            <div
              class="h-2.5 rounded-full bg-white transition-all duration-700"
              :style="{ width: clamp(kpis.recovery_rate ?? 0) + '%' }"
            ></div>
          </div>
          <p class="mt-2 text-xs text-brand-100/80">{{ rateHint }}</p>
        </div>
      </div>
    </div>

    <!-- Reste à encaisser -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
      <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Reste à encaisser</p>
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
          </svg>
        </span>
      </div>
      <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ fmtMoney(kpis.total_unpaid) }}</p>
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Scolarité non encore réglée</p>
    </div>

    <!-- En attente de confirmation -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
      <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">En attente</p>
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
        </span>
      </div>
      <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ fmtMoney(kpis.total_pending) }}</p>
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Paiements lancés, non confirmés</p>
    </div>

    <!-- Étudiants -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
      <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Étudiants inscrits</p>
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
          </svg>
        </span>
      </div>
      <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ fmtNum(kpis.students_count) }}</p>
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pour l'année {{ schoolYear }}</p>
    </div>

    <!-- Annexes (super admin institution) -->
    <div v-if="showAnnexes" class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
      <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Annexes</p>
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
          </svg>
        </span>
      </div>
      <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ kpis.annexes_count ?? '—' }}</p>
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sites de l'établissement</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  kpis:        { type: Object,  default: () => ({}) },
  schoolYear:  { type: String,  default: '' },
  loading:     { type: Boolean, default: false },
  showAnnexes: { type: Boolean, default: true },
})

const fmtMoney = (v) => {
  if (v == null) return '—'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency', currency: 'XOF', maximumFractionDigits: 0,
  }).format(v)
}

const fmtNum = (v) => {
  if (v == null) return '—'
  return new Intl.NumberFormat('fr-FR').format(v)
}

const fmtRate = (v) => `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 }).format(v ?? 0)} %`

const clamp = (v) => Math.min(100, Math.max(0, v))

const rateHint = computed(() => {
  const r = props.kpis.recovery_rate ?? 0
  if (r >= 80) return 'Très bon niveau de recouvrement.'
  if (r >= 50) return 'Plus de la moitié de la scolarité est encaissée.'
  if (r > 0)   return 'Une relance des familles peut être utile.'
  return 'Aucun paiement enregistré pour le moment.'
})
</script>
