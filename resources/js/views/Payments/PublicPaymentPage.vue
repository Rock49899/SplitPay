<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 p-6">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow p-6">

      <h2 class="text-xl font-semibold mb-4">
        Payment
      </h2>

      <div v-if="link">
        <p class="mb-2 text-gray-600">
          Description: {{ link.description }}
        </p>

        <p class="mb-4 text-gray-600">
          Amount: {{ link.amount }} {{ link.currency }}
        </p>

        <div class="space-y-3">

          <input
            v-model="form.payer_name"
            placeholder="Payer name"
            class="w-full border rounded px-3 py-2"
          />

          <input
            v-model="form.payer_email"
            placeholder="Email"
            class="w-full border rounded px-3 py-2"
          />

          <input
            v-model="form.payer_phone"
            placeholder="Phone"
            class="w-full border rounded px-3 py-2"
          />

          <input
            v-model="form.matricule"
            placeholder="Student matricule"
            class="w-full border rounded px-3 py-2"
          />

          <input
            type="number"
            v-model.number="form.amount"
            class="w-full border rounded px-3 py-2"
          />

          <button
            @click="pay"
            :disabled="loading"
            class="w-full bg-brand-500 text-white py-2 rounded mt-3"
          >
            {{ loading ? 'Redirecting...' : 'Pay Now' }}
          </button>

        </div>
      </div>

      <div v-else>
        Loading...
      </div>

    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import paymentLinkService from '@/services/paymentLinkService'
import paymentService from '@/services/paymentService'

const route = useRoute()
const slug = route.params.slug

const link = ref(null)
const loading = ref(false)

const form = ref({
  payer_name: '',
  payer_email: '',
  payer_phone: '',
  matricule: '',
  amount: null,
})

onMounted(async () => {
  const res = await paymentLinkService.showBySlug(slug)
  link.value = res.data
  form.value.amount = link.value.amount
})

const pay = async () => {
  try {
    loading.value = true

    const checkoutPayload = {
      payment_link_id: link.value.id,
      amount: form.value.amount,
      method: 'payplus',
      payer_name: form.value.payer_name,
      payer_email: form.value.payer_email,
      payer_phone: form.value.payer_phone,
      metadata: {
        matricule: form.value.matricule
      }
    }

    const res = await paymentService.createPublicCheckout(checkoutPayload)

    const checkoutUrl = res.data?.checkout_url
    if (!checkoutUrl) throw new Error('Checkout URL missing')

    window.location.href = checkoutUrl

  } catch (e) {
    alert(e.response?.data?.message || e.message || 'Payment failed')
  } finally {
    loading.value = false
  }
}
</script>
