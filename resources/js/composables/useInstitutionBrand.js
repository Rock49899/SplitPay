import { ref, computed } from 'vue'

const brandName = ref('SplitPay')
const brandLogoUrl = ref('')
const brandLoading = ref(false)
const isLoaded = ref(false)

const buildDisplayName = (name = '', maxLength = 22) => {
  if (!name) return 'SplitPay'
  return name.length > maxLength ? `${name.slice(0, maxLength - 1)}…` : name
}

export function useInstitutionBrand() {
  const displayName = computed(() => buildDisplayName(brandName.value))

  const loadBrand = async (force = false) => {
    if (isLoaded.value && !force) return

    brandLoading.value = true
    try {
      const response = await fetch('/api/check-institution')
      const data = await response.json()

      if (data?.exists && data?.institution) {
        brandName.value = data.institution.name || 'SplitPay'
        brandLogoUrl.value = data.institution.logo_url || ''
      }

      isLoaded.value = true
    } catch (error) {
      console.error('[Brand] Unable to fetch institution branding:', error)
      brandName.value = 'SplitPay'
      brandLogoUrl.value = ''
      isLoaded.value = true
    } finally {
      brandLoading.value = false
    }
  }

  return {
    brandName,
    brandLogoUrl,
    displayName,
    brandLoading,
    loadBrand,
  }
}
