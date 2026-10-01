<template>
  <div class="login-page">

    <div class="login-wrapper">

      <!-- Bandeau gauche / top -->
      <div class="login-brand">
        <div class="brand-mark">SP</div>
        <div>
          <div class="brand-name">Espace étudiant</div>
          <div class="brand-tagline">Consultez votre scolarité, vos paiements et ce qu'il reste à régler.</div>
        </div>

        <!-- Decorative dots grid -->
        <div class="dots-grid" aria-hidden="true">
          <span v-for="n in 30" :key="n" class="dot" />
        </div>
      </div>

      <!-- Carte formulaire -->
      <div class="login-card">

        <!-- ÉTAPE 1 -->
        <template v-if="step === 1">
          <div class="card-header">
            <h1>Connexion</h1>
            <p>Saisissez votre matricule. Vous recevrez un code à 6 chiffres sur l'adresse e-mail enregistrée par votre établissement.</p>
          </div>

          <form @submit.prevent="sendOtp" class="card-form">
            <div class="field-group">
              <label>Matricule</label>
              <div class="input-wrap" :class="{ error: otpError }">
                <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <input
                  v-model="matricule"
                  type="text"
                  placeholder="Ex. : ETU-2025-0042"
                  autocomplete="username"
                />
              </div>
            </div>

            <div v-if="otpError" class="alert alert-error">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
              </svg>
              {{ otpError }}
            </div>

            <button type="submit" :disabled="sending || !matricule.trim()" class="btn-primary">
              <svg v-if="sending" class="spin-icon" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              <span>{{ sending ? 'Envoi…' : 'Recevoir mon code' }}</span>
              <svg v-if="!sending" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="btn-arrow">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
              </svg>
            </button>
          </form>
        </template>

        <!-- ÉTAPE 2 -->
        <template v-else-if="step === 2">
          <button @click="step = 1; otpError = null" class="back-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour
          </button>

          <div class="card-header">
            <h1>Vérification</h1>
            <p>
              Un code a été envoyé à l'adresse email associée à
              <strong class="text-gray-800">{{ matricule }}</strong>.
              Valide pendant <strong>10 min</strong>.
            </p>
          </div>

          <form @submit.prevent="verifyOtp" class="card-form">
            <div class="otp-row">
              <input
                v-for="(_, i) in otpDigits"
                :key="i"
                :ref="el => otpRefs[i] = el"
                v-model="otpDigits[i]"
                @input="onOtpInput(i)"
                @keydown.backspace="onOtpBackspace(i)"
                @paste.prevent="onOtpPaste($event)"
                type="text"
                inputmode="numeric"
                maxlength="1"
                :class="['otp-box', { 'otp-filled': otpDigits[i], 'otp-error': verifyError }]"
              />
            </div>

            <div v-if="verifyError" class="alert alert-error">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
              </svg>
              {{ verifyError }}
            </div>

            <button type="submit" :disabled="verifying || otpCode.length < 6" class="btn-primary">
              <svg v-if="verifying" class="spin-icon" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              <span>{{ verifying ? 'Vérification…' : 'Se connecter' }}</span>
            </button>

            <p class="resend-line">
              Code non reçu ?
              <button type="button" @click="resend" :disabled="resendCooldown > 0" class="resend-btn">
                Renvoyer{{ resendCooldown > 0 ? ` (${resendCooldown} s)` : '' }}
              </button>
            </p>
          </form>
        </template>

      </div>
    </div>

    <p class="footer-note">
      Réservé aux étudiants inscrits · En cas de problème, contactez votre établissement.
      <router-link to="/signin" class="footer-link">Accès personnel de l'établissement</router-link>
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import studentAccountService from '../../services/studentAccountService';

const router  = useRouter();

const step       = ref(1);
const matricule  = ref('');
const sending    = ref(false);
const verifying  = ref(false);
const otpError   = ref(null);
const verifyError = ref(null);

const otpDigits = ref(['', '', '', '', '', '']);
const otpRefs   = ref([]);

const otpCode = computed(() => otpDigits.value.join(''));

const resendCooldown = ref(0);
let cooldownTimer = null;

const startCooldown = () => {
  resendCooldown.value = 60;
  cooldownTimer = setInterval(() => {
    resendCooldown.value--;
    if (resendCooldown.value <= 0) clearInterval(cooldownTimer);
  }, 1000);
};

onUnmounted(() => { if (cooldownTimer) clearInterval(cooldownTimer); });

const onOtpInput = (i) => {
  otpDigits.value[i] = otpDigits.value[i].replace(/\D/, '');
  if (otpDigits.value[i] && i < 5) otpRefs.value[i + 1]?.focus();
  verifyError.value = null;
};

const onOtpBackspace = (i) => {
  if (!otpDigits.value[i] && i > 0) {
    otpDigits.value[i - 1] = '';
    otpRefs.value[i - 1]?.focus();
  }
};

const onOtpPaste = (e) => {
  const text = (e.clipboardData?.getData('text') ?? '').replace(/\D/g, '').slice(0, 6);
  text.split('').forEach((c, i) => { otpDigits.value[i] = c; });
  otpRefs.value[Math.min(text.length, 5)]?.focus();
};

const sendOtp = async () => {
  if (!matricule.value.trim()) return;
  otpError.value = null;
  sending.value  = true;
  try {
    await studentAccountService.requestOtp(matricule.value.trim());
    step.value = 2;
    startCooldown();
    // Focus premier champ OTP
    setTimeout(() => otpRefs.value[0]?.focus(), 100);
  } catch (e) {
    otpError.value = e.response?.data?.message || 'Matricule introuvable ou problème de connexion.';
  } finally {
    sending.value = false;
  }
};

const resend = async () => {
  if (resendCooldown.value > 0) return;
  verifyError.value = null;
  otpDigits.value = ['', '', '', '', '', ''];
  await sendOtp();
};

const verifyOtp = async () => {
  if (otpCode.value.length < 6) return;
  verifyError.value = null;
  verifying.value   = true;
  try {
    const res = await studentAccountService.verifyOtp(matricule.value.trim(), otpCode.value);
    studentAccountService.saveToken(res.data.token, res.data.expires_in_minutes || 60);
    router.push({ name: 'StudentProfile' });
  } catch (e) {
    verifyError.value = e.response?.data?.message || 'Code invalide ou expiré.';
    otpDigits.value = ['', '', '', '', '', ''];
    setTimeout(() => otpRefs.value[0]?.focus(), 50);
  } finally {
    verifying.value = false;
  }
};
</script>

<style scoped>
/* Couleurs alignées sur le thème SplitPay (voir resources/assets/main.css) */
.login-page {
  --brand: #0e7c66;
  --brand-dark: #0b5849;
  --brand-soft: #e7f5f1;
  --ink: #131816;
  --muted: #68736f;
  --line: #e1e6e4;

  min-height: 100vh;
  background: #f6f8f7;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  font-family: 'SplitPay Espaces', 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
}

.login-wrapper {
  width: 100%;
  max-width: 880px;
  display: grid;
  grid-template-columns: 1fr 1.1fr;
  border-radius: 24px;
  overflow: hidden;
  background: #fff;
  border: 1px solid var(--line);
  box-shadow: 0 24px 48px -24px rgba(19, 24, 22, .18);
}

@media (max-width: 640px) {
  .login-wrapper { grid-template-columns: 1fr; }
  .login-brand   { display: none; }
}

.login-brand {
  background: var(--brand-dark);
  padding: 2.75rem 2.5rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 3rem;
  position: relative;
  overflow: hidden;
  color: #fff;
}
.brand-mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255, 255, 255, .12);
  font-size: .85rem;
  font-weight: 700;
}
.brand-name {
  font-size: 1.75rem;
  font-weight: 700;
  letter-spacing: -.02em;
  margin-bottom: .5rem;
}
.brand-tagline {
  font-size: .9rem;
  color: rgba(255, 255, 255, .72);
  line-height: 1.6;
  max-width: 260px;
}

/* points décoratifs */
.dots-grid {
  position: absolute;
  top: 1.75rem; right: 1.75rem;
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 10px;
}
.dot {
  width: 4px; height: 4px;
  border-radius: 50%;
  background: rgba(255, 255, 255, .18);
  display: block;
}

.login-card {
  padding: 3rem 2.5rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.card-header { margin-bottom: 2rem; }
.card-header h1 {
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--ink);
  letter-spacing: -.015em;
  margin-bottom: .4rem;
}
.card-header p {
  font-size: .875rem;
  color: var(--muted);
  line-height: 1.6;
}

.card-form { display: flex; flex-direction: column; gap: 1.25rem; }

.field-group { display: flex; flex-direction: column; gap: .5rem; }
.field-group label {
  font-size: .8125rem;
  font-weight: 600;
  color: #353d3a;
}

.input-wrap {
  display: flex;
  align-items: center;
  gap: .75rem;
  border: 1.5px solid var(--line);
  border-radius: 12px;
  padding: 0 1rem;
  background: #fff;
  transition: border-color .2s, box-shadow .2s;
}
.input-wrap:focus-within {
  border-color: var(--brand);
  box-shadow: 0 0 0 4px rgba(14, 124, 102, .12);
}
.input-wrap.error {
  border-color: #f04438;
  box-shadow: 0 0 0 4px rgba(240, 68, 56, .1);
}
.input-icon {
  width: 17px; height: 17px;
  color: #98a39f;
  flex-shrink: 0;
}
.input-wrap input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  padding: .8rem 0;
  font-size: .9rem;
  color: var(--ink);
}
.input-wrap input::placeholder { color: #b5bdba; }

/* ── Alerte ─────────────────────────────────────────────── */
.alert {
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  border-radius: 10px;
  padding: .75rem 1rem;
  font-size: .8125rem;
  line-height: 1.5;
}
.alert svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }
.alert-error { background: #fef3f2; border: 1px solid #fecdca; color: #b42318; }

/* ── Bouton ─────────────────────────────────────────────── */
.btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .5rem;
  width: 100%;
  padding: .85rem 1.5rem;
  border-radius: 12px;
  font-size: .9rem;
  font-weight: 600;
  color: #fff;
  background: var(--brand);
  border: none;
  cursor: pointer;
  transition: background .2s;
}
.btn-primary:hover:not(:disabled) { background: #0b6b58; }
.btn-primary:disabled { opacity: .45; cursor: not-allowed; }
.btn-arrow { width: 18px; height: 18px; }

/* ── Code OTP ───────────────────────────────────────────── */
.otp-row {
  display: flex;
  gap: .6rem;
  justify-content: center;
}
.otp-box {
  width: 48px; height: 56px;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 700;
  border: 1.5px solid var(--line);
  border-radius: 12px;
  background: #fff;
  color: var(--ink);
  outline: none;
  transition: border-color .2s, box-shadow .2s, background .2s;
}
.otp-box:focus {
  border-color: var(--brand);
  box-shadow: 0 0 0 4px rgba(14, 124, 102, .12);
}
.otp-box.otp-filled {
  border-color: var(--brand);
  background: var(--brand-soft);
  color: var(--brand-dark);
}
.otp-box.otp-error {
  border-color: #f04438;
  background: #fef3f2;
  color: #b42318;
}

/* ── Renvoyer / Retour ──────────────────────────────────── */
.resend-line {
  text-align: center;
  font-size: .8125rem;
  color: var(--muted);
}
.resend-btn,
.back-btn {
  color: var(--brand);
  font-weight: 600;
  background: none;
  border: none;
  cursor: pointer;
  transition: color .2s;
}
.resend-btn:hover:not(:disabled) { color: var(--brand-dark); text-decoration: underline; }
.resend-btn:disabled { opacity: .45; cursor: not-allowed; }

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  font-size: .8125rem;
  padding: 0;
  margin-bottom: 1.5rem;
}
.back-btn:hover { color: var(--brand-dark); }
.back-btn svg { width: 15px; height: 15px; }

/* ── Chargement ─────────────────────────────────────────── */
.spin-icon {
  width: 16px; height: 16px;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Pied de page ───────────────────────────────────────── */
.footer-note {
  margin-top: 1.5rem;
  font-size: .75rem;
  color: var(--muted);
  text-align: center;
  line-height: 1.8;
}
.footer-link {
  display: block;
  color: var(--brand);
  font-weight: 600;
}
.footer-link:hover { text-decoration: underline; }
</style>
