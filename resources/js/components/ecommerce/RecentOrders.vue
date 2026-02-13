<template>
  <div
    class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6"
  >
    <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Recent Payments</h3>
      </div>

      <div class="flex items-center gap-3">
        <button
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
        >
          Filter
        </button>

        <button
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
        >
          See all
        </button>
      </div>
    </div>

    <div class="max-w-full overflow-x-auto custom-scrollbar">
      <table class="min-w-full">
        <thead>
          <tr class="border-t border-gray-100 dark:border-gray-800">
            <th class="py-3 text-left">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Student</p>
            </th>
            <th class="py-3 text-left">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Date</p>
            </th>
            <th class="py-3 text-left">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Method</p>
            </th>
            <th class="py-3 text-left">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Amount</p>
            </th>
            <th class="py-3 text-left">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(p, idx) in payments"
            :key="p.id ?? idx"
            class="border-t border-gray-100 dark:border-gray-800"
          >
            <td class="py-3 whitespace-nowrap">
              <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                {{ getStudentName(p) }}
              </p>
              <span class="text-gray-500 text-theme-xs dark:text-gray-400">{{ p.student?.email ?? p.payer_email ?? '' }}</span>
            </td>
            <td class="py-3 whitespace-nowrap">
              <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ formatDate(p.paid_at ?? p.created_at ?? p.date) }}</p>
            </td>
            <td class="py-3 whitespace-nowrap">
              <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ p.method ?? p.gateway ?? p.payment_method ?? '-' }}</p>
            </td>
            <td class="py-3 whitespace-nowrap">
              <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ p.amount ?? p.total ?? '-' }}</p>
            </td>
            <td class="py-3 whitespace-nowrap">
              <span
                :class="{
                  'rounded-full px-2 py-0.5 text-theme-xs font-medium': true,
                  'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500': (p.status ?? '').toLowerCase() === 'paid',
                  'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400': (p.status ?? '').toLowerCase() === 'pending',
                  'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500': (p.status ?? '').toLowerCase() === 'failed' || (p.status ?? '').toLowerCase() === 'canceled',
                }"
              >
                {{ p.status ?? (p.paid ? 'Paid' : 'Pending') }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

// état des paiements récents
const payments = ref([])

// charger les derniers paiements (essayer admin/payments puis payments)
const loadPayments = async () => {
  try {
    let res = null
    try {
      res = await api.get('admin/payments', { params: { per_page: 5 } })
    } catch (e) {
      // fallback si endpoint différent
      res = await api.get('payments', { params: { per_page: 5 } })
    }
    // normaliser la réponse (différents backends)
    payments.value = res.data?.data ?? res.data ?? res
  } catch (e) {
    // log en console pour debug (ne pas spammer)
    console.error('Impossible de charger les paiements récents', e)
    payments.value = []
  }
}

// helper pour afficher nom étudiant
const getStudentName = (p) => {
  if (!p) return '-'
  return p.student?.name
    ?? (p.student?.first_name ? `${p.student.first_name} ${p.student.last_name ?? ''}` : null)
    ?? p.student_name
    ?? p.payer_name
    ?? '—'
}

// format simple de date (affichage terre-à-terre)
const formatDate = (d) => {
  if (!d) return '-'
  try {
    const dt = new Date(d)
    return dt.toLocaleDateString() + ' ' + dt.toLocaleTimeString()
  } catch {
    return String(d)
  }
}

onMounted(loadPayments)
</script>
<style scoped></style>
