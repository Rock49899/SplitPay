import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import schoolYearService from '@/services/schoolYearService'

function calcCurrentYear() {
  const now = new Date()
  const y   = now.getFullYear()
  return now.getMonth() >= 8 ? `${y}-${y + 1}` : `${y - 1}-${y}`
}

export const useActiveYearStore = defineStore('activeYear', () => {
  // ── État persisté dans localStorage ─────────────────────────────────────
  const activeYear     = ref(localStorage.getItem('active_school_year') || calcCurrentYear())
  const availableYears = ref([])
  const loading        = ref(false)

  const currentCalcYear = computed(() => calcCurrentYear())

  function setActiveYear(year) {
    activeYear.value = year
    localStorage.setItem('active_school_year', year)
  }

  async function loadAvailableYears() {
    loading.value = true
    try {
      const res          = await schoolYearService.index()
      availableYears.value = res.data.years ?? []
      // S'assurer que l'année active est dans la liste
      if (!availableYears.value.includes(activeYear.value)) {
        availableYears.value = [activeYear.value, ...availableYears.value]
      }
    } catch {
      // Fallback : générer 6 années autour de l'année courante
      const now  = new Date()
      const base = now.getMonth() >= 8 ? now.getFullYear() : now.getFullYear() - 1
      availableYears.value = Array.from({ length: 6 }, (_, i) => {
        const s = base + 1 - i
        return `${s}-${s + 1}`
      })
    } finally {
      loading.value = false
    }
  }

  return { activeYear, availableYears, loading, currentCalcYear, setActiveYear, loadAvailableYears }
})
