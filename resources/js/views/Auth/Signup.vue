<template>
  <FullScreenLayout>
    <div class="relative p-6 z-1 bg-gray-50 dark:bg-gray-900 sm:p-0">
      <div
        class="relative flex flex-col justify-start lg:justify-center w-full min-h-screen lg:h-screen lg:flex-row bg-gray-50 dark:bg-gray-900 overflow-y-auto"
      >
        <div class="flex flex-col flex-1 w-full lg:w-1/2">
          <!-- Form -->
          <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto px-5 sm:px-6 lg:px-0 py-6 lg:py-0">
            <div class="mb-8 block text-center lg:hidden">
              <router-link to="/" class="inline-block">
                <span class="text-4xl font-extrabold tracking-tight text-brand-600 dark:text-brand-300">SplitPay</span>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">Plateforme de gestion de scolarité multi-établissements</p>
              </router-link>
            </div>
            <div class="mb-5 sm:mb-8">
              <h1
                class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md"
              >
                Inscription
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Créez votre compte institution en quelques étapes.
              </p>
            </div>

            <div v-if="error || fieldErrors.length" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
              <p class="font-semibold">Impossible de continuer :</p>
              <p v-if="error" class="mt-1">{{ error }}</p>
              <ul v-if="fieldErrors.length" class="mt-2 list-disc space-y-1 pl-5">
                <li v-for="(msg, index) in fieldErrors" :key="index">{{ msg }}</li>
              </ul>
            </div>

            <form @submit.prevent="handleSubmit">
              <div class="space-y-5">
                <!-- Step 1: Owner -->
                <div v-if="step === 1" class="space-y-5">
                  <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <!-- First Name -->
                    <div class="sm:col-span-1">
                      <label for="fname" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Prénom<span class="text-error-500">*</span>
                      </label>
                      <input
                        v-model="firstName"
                        type="text"
                        id="fname"
                        placeholder="Entrez votre prénom"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                      />
                    </div>
                    <!-- Last Name -->
                    <div class="sm:col-span-1">
                      <label for="lname" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Nom<span class="text-error-500">*</span>
                      </label>
                      <input
                        v-model="lastName"
                        type="text"
                        id="lname"
                        placeholder="Entrez votre nom"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                      />
                    </div>
                  </div>

                  <!-- Email -->
                  <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                      E-mail<span class="text-error-500">*</span>
                    </label>
                    <input
                      v-model="email"
                      type="email"
                      id="email"
                      placeholder="Entrez votre e-mail"
                      class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                    />
                  </div>

                  <!-- Password -->
                  <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                      Mot de passe<span class="text-error-500">*</span>
                    </label>
                    <div class="relative">
                      <input
                        v-model="password"
                        :type="showPassword ? 'text' : 'password'"
                        id="password"
                        placeholder="Entrez votre mot de passe"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-4 pr-11 text-sm text-gray-900 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                      />
                      <span @click="togglePasswordVisibility" class="absolute z-30 text-gray-500 -translate-y-1/2 cursor-pointer right-4 top-1/2 dark:text-gray-400">
                        <svg v-if="!showPassword" class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z"
                            fill="#98A2B3"
                          />
                        </svg>
                        <svg v-else class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z"
                            fill="#98A2B3"
                          />
                        </svg>
                      </span>
                    </div>
                  </div>

                  <!-- Terms -->
                  <div>
                    <div>
                      <label
                        for="checkboxLabelOne"
                        class="flex items-start text-sm font-normal text-gray-700 cursor-pointer select-none dark:text-gray-400"
                      >
                        <div class="relative">
                          <input v-model="agreeToTerms" type="checkbox" id="checkboxLabelOne" class="sr-only" />
                          <div :class="agreeToTerms ? 'border-brand-500 bg-brand-500' : 'bg-transparent border-gray-300 dark:border-gray-700'" class="mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                            <span :class="agreeToTerms ? '' : 'opacity-0'">
                              <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                <path
                                  d="M11.6666 3.5L5.24992 9.91667L2.33325 7"
                                  stroke="white"
                                  stroke-width="1.94437"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                />
                              </svg>
                            </span>
                          </div>
                        </div>
                        <p class="inline-block font-normal text-gray-500 dark:text-gray-400">En créant un compte, vous acceptez les <span class="text-gray-800 dark:text-white/90">conditions d'utilisation</span> et la <span class="text-gray-800 dark:text-white">politique de confidentialité</span>.</p>
                      </label>
                    </div>
                  </div>

                  <!-- Navigation -->
                  <div class="flex gap-3">
                    <button type="button" @click="validateStep1" :disabled="loading" class="flex-1 px-4 py-3 text-sm font-medium text-white rounded-lg bg-brand-500 hover:bg-brand-600 disabled:opacity-50">
                      Suivant
                    </button>
                  </div>
                </div>

                <!-- Step 2: Institution + Annexe -->
                <div v-if="step === 2" class="space-y-5">
                  <!-- Institution Name -->
                  <div>
                    <label for="institution" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nom de l'établissement<span class="text-error-500">*</span></label>
                    <input
                      v-model="institutionName"
                      type="text"
                      id="institution"
                      placeholder="Entrez le nom de l'établissement"
                      class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                    />
                  </div>

                  <!-- Institution Email (optional) -->
                  <div>
                    <label for="institution_email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">E-mail de l'établissement (optionnel)</label>
                    <input
                      v-model="institutionEmail"
                      type="email"
                      id="institution_email"
                      placeholder="E-mail de contact de l'établissement"
                      class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                    />
                  </div>

                  <!-- Institution Logo -->
                  <div>
                    <label for="logo" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Logo de l'établissement (optionnel)</label>
                    <input
                      @change="handleLogoUpload"
                      type="file"
                      id="logo"
                      accept="image/*"
                      class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 dark:text-white file:mr-4 file:py-1 file:px-4 file:rounded file:border-0 file:text-sm file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PNG, JPG ou JPEG (Max 2MB)</p>
                  </div>

                  <!-- Annexe Name -->
                  <div>
                    <label for="annexe" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nom de l'annexe<span class="text-error-500">*</span></label>
                    <input
                      v-model="annexeName"
                      type="text"
                      id="annexe"
                      placeholder="Entrez le nom de l'annexe"
                      class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                    />
                  </div>

                  <!-- Annexe Contact Details -->
                  <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                      <label for="annexe_email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email de contact<span class="text-error-500">*</span></label>
                      <input
                        v-model="annexeEmail"
                        type="email"
                        id="annexe_email"
                        placeholder="contact@annexe.com"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                      />
                    </div>
                    <div>
                      <label for="annexe_phone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Téléphone<span class="text-error-500">*</span></label>
                      <input
                        v-model="annexePhone"
                        type="tel"
                        id="annexe_phone"
                        placeholder="+242 XX XXX XXXX"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                      />
                    </div>
                  </div>

                  <!-- Navigation -->
                  <div class="flex gap-3">
                    <button type="button" @click="prevStep" class="flex-1 px-4 py-3 text-sm font-medium text-gray-700 rounded-lg border border-gray-300 hover:bg-gray-50">Retour</button>
                    <button type="submit" :disabled="loading" class="flex-1 px-4 py-3 text-sm font-medium text-white rounded-lg bg-brand-500 hover:bg-brand-600 disabled:opacity-50">
                      <span v-if="!loading">Finaliser l'inscription</span>
                      <span v-else>Inscription en cours...</span>
                    </button>
                  </div>
                </div>
              </div>
            </form>
            <div class="mt-5">
              <p
                class="text-sm font-normal text-center text-gray-700 dark:text-gray-400 sm:text-start"
              >
                Vous avez déjà un compte ?
                <router-link
                  to="/signin"
                  class="text-brand-500 hover:text-brand-600 dark:text-brand-400"
                  >Se connecter</router-link
                >
              </p>
            </div>
          </div>
        </div>
        <div
          class="relative items-center hidden w-full h-full lg:w-1/2 bg-brand-950 dark:bg-gray-950 lg:grid"
        >
          <div class="flex items-center justify-center z-1">
            <common-grid-shape />
            <div class="flex flex-col items-center max-w-xs">
              <router-link to="/" class="block mb-4 text-center">
                <span class="text-8xl font-extrabold tracking-tight text-white dark:text-white">SplitPay</span>
                <p class="mt-3 text-sm text-white/80">Plateforme de gestion de scolairité multi-établissements</p>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FullScreenLayout>
</template>

<script setup>
import FullScreenLayout from '@/components/layout/FullScreenLayout.vue'
import CommonGridShape from '@/components/common/CommonGridShape.vue'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const agreeToTerms = ref(false)

// institution/annexe (step 2)
const institutionName = ref('')
const institutionEmail = ref('')
const institutionLogo = ref(null) // File object
const annexeName = ref('')
const annexeEmail = ref('')
const annexePhone = ref('')

// control
const step = ref(1)
const loading = ref(false)
const error = ref(null)
const fieldErrors = ref([])

const router = useRouter()
const auth = useAuthStore()

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const handleLogoUpload = (event) => {
  const file = event.target.files?.[0]
  if (file) {
    // Valider la taille (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
      error.value = 'Le logo ne doit pas dépasser 2MB'
      fieldErrors.value = []
      event.target.value = ''
      return
    }
    // Valider le type
    if (!['image/png', 'image/jpeg', 'image/jpg'].includes(file.type)) {
      error.value = 'Format invalide. Utilisez PNG, JPG ou JPEG'
      fieldErrors.value = []
      event.target.value = ''
      return
    }
    institutionLogo.value = file
    error.value = null
  }
}

// minimal client validation for step1 before moving to step2
const validateStep1 = () => {
  error.value = null
  fieldErrors.value = []
  if (!firstName.value || !lastName.value) {
    error.value = 'Le prénom et le nom sont obligatoires.'
    return
  }
  if (!email.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    error.value = 'Un e-mail valide est requis.'
    return
  }
  if (!password.value || password.value.length < 8) {
    error.value = 'Le mot de passe doit contenir au moins 8 caractères.'
    return
  }
  if (!agreeToTerms.value) {
    error.value = 'Vous devez accepter les conditions d\'utilisation.'
    return
  }
  // pass validation
  step.value = 2
}

// go back to step 1
const prevStep = () => {
  error.value = null
  fieldErrors.value = []
  step.value = 1
}

const handleSubmit = async () => {
  error.value = null
  fieldErrors.value = []
  
  // Validation des champs requis de l'annexe
  if (!annexeEmail.value || !annexePhone.value) {
    error.value = 'Les coordonnées de l\'annexe (email et téléphone) sont obligatoires'
    return
  }
  
  loading.value = true

  // Utiliser FormData pour supporter l'upload de logo
  const formData = new FormData()
  
  formData.append('institution_name', institutionName.value)
  formData.append('institution_email', institutionEmail.value || '')
  formData.append('institution_phone', '')
  formData.append('annexe_name', annexeName.value)
  formData.append('owner_name', `${firstName.value} ${lastName.value}`.trim())
  formData.append('owner_email', email.value)
  formData.append('owner_password', password.value)
  
  // Ajouter les détails de l'annexe comme JSON
  const annexeDetails = {
    email: annexeEmail.value,
    phone: annexePhone.value,
  }
  formData.append('annexe_details', JSON.stringify(annexeDetails))
  
  // Ajouter le logo si fourni
  if (institutionLogo.value) {
    formData.append('logo', institutionLogo.value)
  }

  try {
    await auth.register(formData)
    // redirect to signin or dashboard
    router.push('/signin')
  } catch (e) {
    const apiError = e.response?.data
    error.value = apiError?.message || e.message || 'Échec de l\'inscription'
    fieldErrors.value = flattenErrors(apiError?.errors)
  } finally {
    loading.value = false
  }
}

const flattenErrors = (errorsObj) => {
  if (!errorsObj || typeof errorsObj !== 'object') return []
  return Object.values(errorsObj)
    .flat()
    .filter((msg) => typeof msg === 'string' && msg.trim().length > 0)
}
</script>