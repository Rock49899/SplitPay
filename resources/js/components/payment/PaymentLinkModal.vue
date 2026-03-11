<template>
  <Modal @close="close">
    <template #body>
      <div class="w-full max-w-lg p-6 bg-white dark:bg-gray-900 rounded-2xl">
        <h3 class="text-lg font-semibold mb-3">Créer un lien de paiement</h3>

        <div class="space-y-3">

          <!-- Type de lien -->
          <div>
            <label class="block text-sm text-gray-600">Type</label>
            <select v-model="form.type" class="w-full border rounded px-3 py-2">
              <option value="tuition">Scolarité</option>
              <option value="other">Autre</option>
            </select>
          </div>

          <!-- Confirmation scolarité avec réduction possible (tuition + barème connu) -->
          <div v-if="isTuition && props.tuitionAmount > 0"
            class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Scolarité de référence
              <span v-if="tuitionChanged" class="text-orange-500 text-xs ml-2">
                ↳ réduction appliquée
              </span>
            </label>
            <input
              type="number"
              v-model.number="confirmedTuition"
              min="0" step="500"
              class="w-full border rounded px-3 py-2 text-sm"
              @input="syncAmount"
            />
            <p class="text-xs text-gray-400 mt-1">
              Montant initial : {{ fmtAmount(props.tuitionAmount) }}
              &nbsp;&mdash;&nbsp;
              Restant effectif : <strong>{{ fmtAmount(effectiveRemaining) }}</strong>
            </p>
          </div>

          <!-- Tranche number (uniquement pour tuition) -->
          <div v-if="isTuition">
            <label class="block text-sm text-gray-600">Numéro de tranche</label>
            <input
              type="number"
              v-model.number="form.tranche_number"
              min="1"
              step="1"
              class="w-full border rounded px-3 py-2"
            />
          </div>

          <!-- Amount -->
          <div>
            <label class="block text-sm text-gray-600">Montant</label>
            <input
              type="number"
              step="0.01"
              v-model.number="form.amount"
              class="w-full border rounded px-3 py-2"
              :max="maxAllowed"
            />
            <div v-if="isTuition" class="text-xs text-gray-500 mt-1">
              Restant dû : {{ fmtAmount(effectiveRemaining) }} &mdash; Max : {{ fmtAmount(maxAllowed) }}
            </div>
          </div>

          <!-- Due date -->
          <div>
            <label class="block text-sm text-gray-600">Échéance</label>
            <input type="date" v-model="form.due_date" class="w-full border rounded px-3 py-2" />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm text-gray-600">Description</label>
            <input type="text" v-model="form.description" class="w-full border rounded px-3 py-2" />
          </div>

          <!-- Currency -->
          <div>
            <label class="block text-sm text-gray-600">Devise</label>
            <select v-model="form.currency" class="w-full border rounded px-3 py-2">
              <option v-for="c in currencies" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>

          <!-- Boutons -->
          <div class="flex justify-end gap-2 mt-4">
            <button @click="close" class="px-4 py-2 border rounded">Annuler</button>
            <button
              :disabled="loading"
              @click="createLink"
              class="px-4 py-2 bg-brand-500 text-white rounded"
            >
              {{ loading ? 'Création...' : 'Créer le lien' }}
            </button>
          </div>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import Modal from '@/components/payment/Modal.vue'
import paymentLinkService from '@/services/paymentLinkService'
import api from '@/services/api'

const props = defineProps({
  studentId:      { type: String, required: true },
  remaining:      { type: Number, default: 0 },
  initialCurrency:{ type: String, default: 'USD' },
  tuitionAmount:  { type: Number, default: 0 },   // tarif configuré dans level_fees
  enrollmentId:   { type: [Number, String], default: null }, // pour PATCH si réduction
})

const emit = defineEmits(['close','created'])

const currencies = ['USD','EUR','XOF']

// ── Scolarité confirmée (admin peut réduire pour accorder une remise) ──────────
const confirmedTuition = ref(Number(props.tuitionAmount ?? 0) || Number(props.remaining ?? 0))

// Montant déjà payé (déduit du tarif initial)
const amountPaid = computed(() => Math.max(0, Number(props.tuitionAmount ?? 0) - Number(props.remaining ?? 0)))
// Restant effectif après application de la réduction
const effectiveRemaining = computed(() => Math.max(0, confirmedTuition.value - amountPaid.value))
// La scolarité a-t-elle été modifiée ?
const tuitionChanged = computed(() => confirmedTuition.value !== Number(props.tuitionAmount ?? 0))

// Si on change la scolarité, recaler le montant du lien si nécessaire
const syncAmount = () => {
  if (form.type === 'tuition' && form.amount > effectiveRemaining.value) {
    form.amount = effectiveRemaining.value
  }
}

const form = reactive({
  type: 'tuition',
  amount: Number(props.remaining ?? 0),
  due_date: null,
  description: '',
  currency: props.initialCurrency,
  tranche_number: 1,
})

const loading = ref(false)
const isTuition = computed(() => form.type === 'tuition')
const maxAllowed = computed(() =>
  isTuition.value ? effectiveRemaining.value : 1e12
)

const fmtAmount = (v) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(v)

const close = () => emit('close')

const createLink = async () => {
  try {
    loading.value = true

    // 1. Si réduction accordée et enrollment connu → mettre à jour la scolarité de l'enrollment
    if (isTuition.value && tuitionChanged.value && props.enrollmentId) {
      await api.patch(`admin/enrollments/${props.enrollmentId}`, {
        tuition_amount: confirmedTuition.value,
      })
    }

    // 2. Ajustement montant lien
    if (isTuition.value) {
      const max = effectiveRemaining.value
      if (!form.amount || form.amount <= 0) form.amount = max
      if (form.amount > max) form.amount = max
    }

    // 3. Créer le lien
    const payload = {
      student_id:     props.studentId,
      type:           form.type,
      amount:         form.amount,
      description:    form.description,
      due_date:       form.due_date ?? null,
      currency:       form.currency,
      tranche_number: isTuition.value ? form.tranche_number : null,
    }

    const res = await paymentLinkService.store(payload)
    const link = res.data?.payment_link ?? res.data ?? res

    emit('created', link)
    close()

  } catch (e) {
    console.error('Payment link creation failed', e)
    alert(e.response?.data?.message || e.message || 'Operation failed')
  } finally {
    loading.value = false
  }
}
</script>
