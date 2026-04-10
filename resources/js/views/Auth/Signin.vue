<template>
  <FullScreenLayout>
    <div class="relative p-6 z-1 bg-gray-50 dark:bg-gray-900 sm:p-0">
      <div
        class="relative flex flex-col justify-start lg:justify-center w-full min-h-screen lg:h-screen lg:flex-row bg-gray-50 dark:bg-gray-900 overflow-y-auto"
      >
        <div class="flex flex-col flex-1 w-full lg:w-1/2">
          <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto px-5 sm:px-6 lg:px-0 py-6 lg:py-0">
            <div class="mb-8 block text-center lg:hidden">
              <router-link to="/" class="inline-block">
                <span class="text-4xl font-extrabold tracking-tight text-brand-600 dark:text-brand-300">SplitPay</span>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">Plateforme de gestion de scolarité multi-établissements</p>
              </router-link>
            </div>
            <div>
              <div class="mb-5 sm:mb-8">
                <h1
                  class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md"
                >
                  Connexion
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Entrez votre email pour recevoir un OTP, ou utilisez votre mot de passe.
                </p>
              </div>

              <div v-if="error || fieldErrors.length" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                <p class="font-semibold">Impossible de continuer :</p>
                <p v-if="error" class="mt-1">{{ error }}</p>
                <ul v-if="fieldErrors.length" class="mt-2 list-disc space-y-1 pl-5">
                  <li v-for="(msg, index) in fieldErrors" :key="index">{{ msg }}</li>
                </ul>
              </div>

              <div v-if="otpNotice" class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ otpNotice }}
              </div>

              <div>
                <form @submit.prevent="handleSubmit">
                  <div class="space-y-5">
                    <!-- Auth mode switch -->
                    <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800">
                      <button
                        type="button"
                        @click="authMode = 'otp'"
                        :class="[
                          'flex-1 rounded-md px-3 py-2 text-xs font-medium transition',
                          authMode === 'otp' ? 'bg-brand-500 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-white/60 dark:hover:bg-gray-700'
                        ]"
                      >
                        Connexion OTP
                      </button>
                      <button
                        type="button"
                        @click="authMode = 'password'; otpStep = false; otp = ''"
                        :class="[
                          'flex-1 rounded-md px-3 py-2 text-xs font-medium transition',
                          authMode === 'password' ? 'bg-brand-500 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-white/60 dark:hover:bg-gray-700'
                        ]"
                      >
                        Mot de passe
                      </button>
                    </div>

                    <!-- Email -->
                    <div>
                      <label
                        for="email"
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                      >
                        E-mail<span class="text-error-500">*</span>
                      </label>
                      <input
                        v-model="email"
                        type="email"
                        id="email"
                        name="email"
                        placeholder="info@gmail.com"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                      />
                    </div>

                    <!-- OTP flow -->
                    <div v-if="authMode === 'otp'" class="space-y-3">
                      <div v-if="otpStep">
                        <label
                          for="otp"
                          class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                        >
                          Code OTP<span class="text-error-500">*</span>
                        </label>
                        <input
                          v-model="otp"
                          type="text"
                          id="otp"
                          maxlength="6"
                          placeholder="Entrez le code reçu par email"
                          class="h-11 w-full rounded-lg border border-gray-300 bg-white py-2.5 px-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Code valide pendant 10 minutes.</p>
                      </div>

                      <div class="flex items-center gap-2">
                        <button
                          v-if="!otpStep"
                          type="button"
                          :disabled="loading || !email"
                          @click="sendOtp"
                          class="flex items-center justify-center w-full px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600 disabled:opacity-50"
                        >
                          <span v-if="!loading">Envoyer OTP</span>
                          <span v-else>Envoi...</span>
                        </button>

                        <template v-else>
                          <button
                            type="button"
                            :disabled="loading || otp.length !== 6"
                            @click="verifyOtp"
                            class="flex items-center justify-center w-full px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600 disabled:opacity-50"
                          >
                            <span v-if="!loading">Vérifier OTP</span>
                            <span v-else>Vérification...</span>
                          </button>
                          <button
                            type="button"
                            :disabled="loading"
                            @click="sendOtp"
                            class="px-4 py-3 text-sm rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                          >
                            Renvoyer
                          </button>
                        </template>
                      </div>
                    </div>

                    <!-- Password -->
                    <div v-if="authMode === 'password'">
                      <label
                        for="password"
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                      >
                        Mot de passe<span class="text-error-500">*</span>
                      </label>
                      <div class="relative">
                        <input
                          v-model="password"
                          :type="showPassword ? 'text' : 'password'"
                          id="password"
                          placeholder="Entrez votre mot de passe"
                          class="h-11 w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-4 pr-11 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                        />
                        <span
                          @click="togglePasswordVisibility"
                          class="absolute z-30 text-gray-500 -translate-y-1/2 cursor-pointer right-4 top-1/2 dark:text-gray-400"
                        >
                          <svg
                            v-if="!showPassword"
                            class="fill-current"
                            width="20"
                            height="20"
                            viewBox="0 0 20 20"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <path
                              fill-rule="evenodd"
                              clip-rule="evenodd"
                              d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z"
                              fill="#98A2B3"
                            />
                          </svg>
                          <svg
                            v-else
                            class="fill-current"
                            width="20"
                            height="20"
                            viewBox="0 0 20 20"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                          >
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
                    <!-- Checkbox -->
                    <div class="flex items-center justify-between">
                      <div>
                        <label
                          for="keepLoggedIn"
                          class="flex items-center text-sm font-normal text-gray-700 cursor-pointer select-none dark:text-gray-400"
                        >
                          <div class="relative">
                            <input
                              v-model="keepLoggedIn"
                              type="checkbox"
                              id="keepLoggedIn"
                              class="sr-only"
                            />
                            <div
                              :class="
                                keepLoggedIn
                                  ? 'border-brand-500 bg-brand-500'
                                  : 'bg-transparent border-gray-300 dark:border-gray-700'
                              "
                              class="mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]"
                            >
                              <span :class="keepLoggedIn ? '' : 'opacity-0'">
                                <svg
                                  width="14"
                                  height="14"
                                  viewBox="0 0 14 14"
                                  fill="none"
                                  xmlns="http://www.w3.org/2000/svg"
                                >
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
                          Rester connecté
                        </label>
                      </div>
                      <router-link
                        to="/reset-password"
                        class="text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400"
                        >Mot de passe oublié ?</router-link
                      >
                    </div>
                    <!-- Button -->
                    <div v-if="authMode === 'password'">
                      <button
                        type="submit"
                        :disabled="loading"
                        class="flex items-center justify-center w-full px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600 disabled:opacity-50"
                      >
                        <span v-if="!loading">Se connecter</span>
                        <span v-else>Connexion...</span>
                      </button>
                    </div>
                  </div>
                </form>
                <div class="mt-5"></div>
              </div>
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
                <span class="text-7xl font-extrabold tracking-tight text-white dark:text-white">SplitPay</span>
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
import { ref } from 'vue'
import CommonGridShape from '@/components/common/CommonGridShape.vue'
import FullScreenLayout from '@/components/layout/FullScreenLayout.vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import authService from '@/services/authService'

const email = ref('')
const password = ref('')
const otp = ref('')
const authMode = ref('otp')
const otpStep = ref(false)
const showPassword = ref(false)
const keepLoggedIn = ref(false)

const loading = ref(false)
const error = ref(null)
const otpNotice = ref('')
const fieldErrors = ref([])

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const handleSubmit = async () => {
  if (authMode.value === 'otp') {
    if (!otpStep.value) return sendOtp()
    return verifyOtp()
  }

  error.value = null
  otpNotice.value = ''
  fieldErrors.value = []
  if (!password.value) {
    error.value = 'Veuillez saisir un mot de passe ou utiliser la connexion OTP.'
    return
  }
  loading.value = true
  try {
    const res = await auth.login({ email: email.value, password: password.value })
    // optional: fetch user profile
    try { await auth.fetchMe() } catch (_) {}
    // redirect to original destination or dashboard
    const redirect = route.query.redirect
    router.push(redirect && redirect !== '/signin' ? redirect : '/')
  } catch (e) {
    const apiError = e.response?.data
    error.value = apiError?.message || e.message || 'Échec de connexion'
    fieldErrors.value = flattenErrors(apiError?.errors)
  } finally {
    loading.value = false
  }
}

const sendOtp = async () => {
  error.value = null
  otpNotice.value = ''
  fieldErrors.value = []
  loading.value = true
  try {
    const res = await authService.requestOtp(email.value)
    const apiMessage = res?.data?.message
    otpStep.value = true
    otpNotice.value = apiMessage || 'Code OTP envoyé à votre email.'
  } catch (e) {
    const apiError = e.response?.data
    error.value = apiError?.message || e.message || 'Échec envoi OTP'
    fieldErrors.value = flattenErrors(apiError?.errors)
  } finally {
    loading.value = false
  }
}

const verifyOtp = async () => {
  error.value = null
  otpNotice.value = ''
  fieldErrors.value = []
  loading.value = true
  try {
    const res = await authService.verifyOtp(email.value, otp.value)
    const token = res.data?.token
    if (token) auth.setToken(token)
    if (res.data?.user) auth.user = res.data.user
    try { await auth.fetchMe() } catch (_) {}
    const redirect = route.query.redirect
    router.push(redirect && redirect !== '/signin' ? redirect : '/')
  } catch (e) {
    const apiError = e.response?.data
    error.value = apiError?.message || e.message || 'OTP invalide ou expiré'
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
