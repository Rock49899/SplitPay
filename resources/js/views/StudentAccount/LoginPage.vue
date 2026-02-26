<template>
  <div class="login-page">

    <!-- Blobs décoratifs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="login-wrapper">

      <!-- Bandeau gauche / top -->
      <div class="login-brand">
        <div class="brand-name">SplitPay</div>
        <div class="brand-tagline">Manage your school fees simply and securely</div>

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
            <h1>Sign In</h1>
            <p>Enter your student ID to receive a 6-digit verification code by email.</p>
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
                  placeholder="e.g. STU-2024-0042"
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
              <span>{{ sending ? 'Sending…' : 'Send Code' }}</span>
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
            Back
          </button>

          <div class="card-header">
            <h1>Verification</h1>
            <p>
              A code was sent to the email address linked to
              <strong class="text-gray-800">{{ matricule }}</strong>.
              Valid for <strong>10 min</strong>.
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
              <span>{{ verifying ? 'Verifying…' : 'Verify Code' }}</span>
            </button>

            <p class="resend-line">
              Didn't receive the code?
              <button type="button" @click="resend" :disabled="resendCooldown > 0" class="resend-btn">
                Resend{{ resendCooldown > 0 ? ` (${resendCooldown}s)` : '' }}
              </button>
            </p>
          </form>
        </template>

      </div>
    </div>

    <p class="footer-note">
      This portal is for registered students only · Contact your institution if you need help
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
    otpError.value = e.response?.data?.message || 'Student ID not found or network error.';
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
    studentAccountService.saveToken(res.data.token);
    router.push({ name: 'StudentProfile' });
  } catch (e) {
    verifyError.value = e.response?.data?.message || 'Invalid or expired code.';
    otpDigits.value = ['', '', '', '', '', ''];
    setTimeout(() => otpRefs.value[0]?.focus(), 50);
  } finally {
    verifying.value = false;
  }
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: #0d0f1a;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  position: relative;
  overflow: hidden;
}

.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.35;
  pointer-events: none;
}
.blob-1 {
  width: 420px; height: 420px;
  background: #4f46e5;
  top: -120px; left: -100px;
}
.blob-2 {
  width: 320px; height: 320px;
  background: #7c3aed;
  bottom: -80px; right: -60px;
}
.blob-3 {
  width: 200px; height: 200px;
  background: #06b6d4;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
}

.login-wrapper {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 900px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 40px 80px -20px rgba(0,0,0,.6);
}

@media (max-width: 640px) {
  .login-wrapper { grid-template-columns: 1fr; }
  .login-brand   { display: none; }
}

.login-brand {
  background: linear-gradient(145deg, #3730a3 0%, #4f46e5 50%, #7c3aed 100%);
  padding: 3rem 2.5rem;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  position: relative;
  overflow: hidden;
}
.brand-name {
  font-size: 2.25rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: -1px;
  margin-bottom: .5rem;
}
.brand-tagline {
  font-size: .9rem;
  color: rgba(255,255,255,.65);
  line-height: 1.6;
  max-width: 200px;
}

/* dots decoratifs */
.dots-grid {
  position: absolute;
  top: 1.5rem; right: 1.5rem;
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 10px;
}
.dot {
  width: 4px; height: 4px;
  border-radius: 50%;
  background: rgba(255,255,255,.25);
  display: block;
}

.login-card {
  background: #ffffff;
  padding: 3rem 2.5rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.card-header {
  margin-bottom: 2rem;
}
.card-header h1 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: .4rem;
}
.card-header p {
  font-size: .875rem;
  color: #6b7280;
  line-height: 1.6;
}

.card-form { display: flex; flex-direction: column; gap: 1.25rem; }

.field-group { display: flex; flex-direction: column; gap: .5rem; }
.field-group label {
  font-size: .8125rem;
  font-weight: 600;
  color: #374151;
  letter-spacing: .02em;
}

.input-wrap {
  display: flex;
  align-items: center;
  gap: .75rem;
  border: 1.5px solid #e5e7eb;
  border-radius: 12px;
  padding: 0 1rem;
  background: #f9fafb;
  transition: border-color .2s, box-shadow .2s;
}
.input-wrap:focus-within {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79,70,229,.12);
  background: #fff;
}
.input-wrap.error {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239,68,68,.1);
}
.input-icon {
  width: 17px; height: 17px;
  color: #9ca3af;
  flex-shrink: 0;
}
.input-wrap input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  padding: .75rem 0;
  font-size: .9rem;
  color: #111827;
}
.input-wrap input::placeholder { color: #d1d5db; }

/* ── Alert ──────────────────────────────────────────────── */
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
.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }

/* ── Button ─────────────────────────────────────────────── */
.btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .5rem;
  width: 100%;
  padding: .875rem 1.5rem;
  border-radius: 12px;
  font-size: .9rem;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  border: none;
  cursor: pointer;
  transition: opacity .2s, transform .15s, box-shadow .2s;
  box-shadow: 0 4px 14px rgba(79,70,229,.35);
}
.btn-primary:hover:not(:disabled) {
  opacity: .92;
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(79,70,229,.45);
}
.btn-primary:disabled { opacity: .45; cursor: not-allowed; transform: none; }
.btn-arrow { width: 18px; height: 18px; }

/* ── OTP grid ───────────────────────────────────────────── */
.otp-row {
  display: flex;
  gap: .75rem;
  justify-content: center;
}
.otp-box {
  width: 48px; height: 56px;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 700;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  background: #f9fafb;
  color: #111827;
  outline: none;
  transition: border-color .2s, box-shadow .2s, background .2s;
}
.otp-box:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79,70,229,.15);
  background: #fff;
}
.otp-box.otp-filled {
  border-color: #4f46e5;
  background: #ede9fe;
  color: #4338ca;
}
.otp-box.otp-error {
  border-color: #ef4444;
  background: #fef2f2;
  color: #dc2626;
}

/* ── Resend / Back ──────────────────────────────────────── */
.resend-line {
  text-align: center;
  font-size: .8125rem;
  color: #9ca3af;
}
.resend-btn {
  color: #4f46e5;
  font-weight: 600;
  background: none;
  border: none;
  cursor: pointer;
  transition: color .2s;
}
.resend-btn:hover:not(:disabled) { color: #3730a3; text-decoration: underline; }
.resend-btn:disabled { opacity: .45; cursor: not-allowed; }

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  font-size: .8125rem;
  font-weight: 600;
  color: #4f46e5;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  margin-bottom: 1.5rem;
  transition: color .2s;
}
.back-btn:hover { color: #3730a3; }
.back-btn svg { width: 15px; height: 15px; }

/* ── Spin ───────────────────────────────────────────────── */
.spin-icon {
  width: 16px; height: 16px;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Footer ─────────────────────────────────────────────── */
.footer-note {
  position: relative;
  z-index: 1;
  margin-top: 1.5rem;
  font-size: .75rem;
  color: rgba(255,255,255,.3);
  text-align: center;
}
</style>
