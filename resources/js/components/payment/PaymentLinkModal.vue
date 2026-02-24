<template>
  <Modal @close="close">
    <template #body>
      <div class="w-full max-w-lg p-6 bg-white dark:bg-gray-900 rounded-2xl">
        <h3 class="text-lg font-semibold mb-3">Create Payment Link</h3>

        <div class="space-y-3">

          <!-- Type de lien -->
          <div>
            <label class="block text-sm text-gray-600">Type</label>
            <select v-model="form.type" class="w-full border rounded px-3 py-2">
              <option value="tuition">Tuition</option>
              <option value="other">Other</option>
            </select>
          </div>

          <!-- Tranche number (uniquement pour tuition) -->
          <div v-if="isTuition">
            <label class="block text-sm text-gray-600">Tranche number</label>
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
            <label class="block text-sm text-gray-600">Amount</label>
            <input
              type="number"
              step="0.01"
              v-model.number="form.amount"
              class="w-full border rounded px-3 py-2"
              :max="maxAllowed"
            />
            <div v-if="isTuition" class="text-xs text-gray-500 mt-1">
              Remaining due: {{ remaining }} — Max allowed: {{ maxAllowed }}
            </div>
          </div>

          <!-- Due date -->
          <div>
            <label class="block text-sm text-gray-600">Due date</label>
            <input type="date" v-model="form.due_date" class="w-full border rounded px-3 py-2" />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm text-gray-600">Description</label>
            <input type="text" v-model="form.description" class="w-full border rounded px-3 py-2" />
          </div>

          <!-- Currency -->
          <div>
            <label class="block text-sm text-gray-600">Currency</label>
            <select v-model="form.currency" class="w-full border rounded px-3 py-2">
              <option v-for="c in currencies" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>

          <!-- Boutons -->
          <div class="flex justify-end gap-2 mt-4">
            <button @click="close" class="px-4 py-2 border rounded">Cancel</button>
            <button
              :disabled="loading"
              @click="createLink"
              class="px-4 py-2 bg-brand-500 text-white rounded"
            >
              {{ loading ? 'Creating...' : 'Create Link' }}
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

const props = defineProps({
  studentId: { type: String, required: true },
  remaining: { type: Number, default: 0 },
  initialCurrency: { type: String, default: 'USD' },
})

const emit = defineEmits(['close','created'])

const currencies = ['USD','EUR','XOF']

const form = reactive({
  type: 'tuition',
  amount: Number(props.remaining ?? 0),
  due_date: null,
  description: '',
  currency: props.initialCurrency,
  tranche_number: 1, // valeur par défaut
})

const loading = ref(false)
const isTuition = computed(() => form.type === 'tuition')
const maxAllowed = computed(() =>
  isTuition.value ? Number(props.remaining ?? 0) : 1e12
)

const close = () => emit('close')

const createLink = async () => {
  try {
    loading.value = true

    // Ajustement montant pour tuition
    if (isTuition.value) {
      const remaining = Number(props.remaining ?? 0)
      if (!form.amount || form.amount <= 0) form.amount = remaining
      if (form.amount > remaining) form.amount = remaining
    }

    // Préparer payload
    const payload = {
      student_id: props.studentId,
      type: form.type,
      amount: form.amount,
      description: form.description,
      due_date: form.due_date ?? null,
      currency: form.currency,
      tranche_number: isTuition.value ? form.tranche_number : null, // envoi conditionnel
    }

    // Création du lien
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
