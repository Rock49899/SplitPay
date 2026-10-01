<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Tableau de bord financier" />
    
    <div class=" space-y-6">
      <!-- KPIs -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm border border-slate-200 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total paiements</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ kpis.total }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm border border-slate-200 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Encaissé</p>
              <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ formatMoney(kpis.total_success) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm border border-slate-200 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">En attente</p>
              <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ kpis.pending }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm border border-slate-200 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Échoué</p>
              <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ kpis.failed }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters & Actions -->
      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="flex flex-col lg:flex-row gap-4 items-end">
          <div class="flex-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Rechercher</label>
              <input
                v-model="filters.q"
                type="text"
                placeholder="Référence, nom, téléphone..."
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                @input="debounceSearch"
              />
            </div>

            <!-- Annexe -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Annexe</label>
              <select
                v-model="filters.annexe_id"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                @change="loadPayments"
              >
                <option value="">Toutes</option>
                <option v-for="annexe in annexes" :key="annexe.id" :value="annexe.id">
                  {{ annexe.name }}
                </option>
              </select>
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Statut</label>
              <select
                v-model="filters.status"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                @change="loadPayments"
              >
                <option value="">Tous</option>
                <option value="success">Payé</option>
                <option value="pending">En attente</option>
                <option value="failed">Échoué</option>
              </select>
            </div>

            <!-- Date Range -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Période</label>
              <div class="flex gap-2">
                <input
                  v-model="filters.from"
                  type="date"
                  class="flex-1 px-2 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"
                  @change="loadPayments"
                />
                <input
                  v-model="filters.to"
                  type="date"
                  class="flex-1 px-2 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"
                  @change="loadPayments"
                />
              </div>
            </div>
          </div>

          <!-- Broadcast Button -->
            <button
              @click="showBroadcast = true"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors whitespace-nowrap"
            >
              <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
              Créer un lien général
            </button>
        </div>
      </div>

      <!-- Payments Table -->
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-500">
          <svg class="animate-spin h-8 w-8 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Chargement...
        </div>

        <div v-else-if="payments.length === 0" class="p-12 text-center text-slate-500">
          Aucun paiement trouvé
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-700 border-b border-slate-200 dark:border-slate-600">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Étudiant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Payeur</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Annexe</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Montant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Méthode</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
              <tr
                v-for="payment in payments"
                :key="payment.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors"
                @click="viewDetails(payment)"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <AvatarDisplay
                      :src="payment.student?.avatar_url"
                      :label="`${payment.student?.first_name} ${payment.student?.last_name}`"
                      :size="40"
                      class="mr-3"
                    />
                    <div>
                      <div class="text-sm font-medium text-slate-900 dark:text-white">
                        {{ payment.student?.first_name }} {{ payment.student?.last_name }}
                      </div>
                      <div class="text-sm text-slate-500 dark:text-slate-400">
                        {{ payment.student?.matricule }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                  {{ payment.payer_name || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 capitalize">
                    {{ getPaymentTypeLabel(payment.payment_link?.type) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                  {{ payment.student?.annexe?.name || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                  {{ formatMoney(payment.amount) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 uppercase">
                    {{ payment.method }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusClass(payment.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getStatusLabel(payment.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                  {{ formatDate(payment.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button
                    @click.stop="viewDetails(payment)"
                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                  >
                    Détails
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > pagination.per_page" class="bg-slate-50 dark:bg-slate-700 px-4 py-3 flex items-center justify-between border-t border-slate-200 dark:border-slate-600">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="relative inline-flex items-center px-4 py-2 border border-slate-300 dark:border-slate-600 text-sm font-medium rounded-md text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50"
            >
              Précédent
            </button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-slate-300 dark:border-slate-600 text-sm font-medium rounded-md text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50"
            >
              Suivant
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-slate-700 dark:text-slate-300">
                Affichage de
                <span class="font-medium">{{ pagination.from }}</span>
                à
                <span class="font-medium">{{ pagination.to }}</span>
                sur
                <span class="font-medium">{{ pagination.total }}</span>
                résultats
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <button
                  @click="changePage(pagination.current_page - 1)"
                  :disabled="pagination.current_page === 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm font-medium text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50"
                >
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
                <button
                  v-for="page in visiblePages"
                  :key="page"
                  @click="changePage(page)"
                  :class="[
                    page === pagination.current_page
                      ? 'z-10 bg-indigo-50 dark:bg-indigo-900/30 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                      : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700',
                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                  ]"
                >
                  {{ page }}
                </button>
                <button
                  @click="changePage(pagination.current_page + 1)"
                  :disabled="pagination.current_page === pagination.last_page"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm font-medium text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50"
                >
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Detail Modal -->
    <Teleport to="body">
      <div
        v-if="showDetail"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click.self="showDetail = false"
      >
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-auto">
          <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Détails du paiement</h3>
            <button @click="showDetail = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div v-if="selectedPayment" class="p-6 space-y-6">
            <!-- Student Info -->
            <div>
              <h4 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-3">ÉTUDIANT</h4>
              <div class="flex items-center space-x-4">
                <AvatarDisplay
                  :src="selectedPayment.student?.avatar_url"
                  :label="`${selectedPayment.student?.first_name} ${selectedPayment.student?.last_name}`"
                  :size="60"
                />
                <div>
                  <p class="text-lg font-medium text-slate-900 dark:text-white">
                    {{ selectedPayment.student?.first_name }} {{ selectedPayment.student?.last_name }}
                  </p>
                  <p class="text-sm text-slate-500 dark:text-slate-400">{{ selectedPayment.student?.matricule }}</p>
                  <p class="text-sm text-slate-500 dark:text-slate-400">{{ selectedPayment.student?.email }}</p>
                </div>
              </div>
            </div>

            <!-- Payment Info -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Montant</p>
                <p class="text-lg font-bold text-slate-900 dark:text-white">{{ formatMoney(selectedPayment.amount) }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Statut</p>
                <span :class="getStatusClass(selectedPayment.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1">
                  {{ getStatusLabel(selectedPayment.status) }}
                </span>
              </div>
              <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Type de paiement</p>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 capitalize mt-1">
                  {{ getPaymentTypeLabel(selectedPayment.payment_link?.type) }}
                </span>
              </div>
              <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Méthode</p>
                <p class="text-sm text-slate-900 dark:text-white uppercase">{{ selectedPayment.method }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Référence</p>
                <p class="text-sm text-slate-900 dark:text-white font-mono">{{ selectedPayment.reference }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Payeur</p>
                <p class="text-sm text-slate-900 dark:text-white">{{ selectedPayment.payer_name || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Téléphone</p>
                <p class="text-sm text-slate-900 dark:text-white">{{ selectedPayment.payer_phone || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Email</p>
                <p class="text-sm text-slate-900 dark:text-white">{{ selectedPayment.payer_email || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Date</p>
                <p class="text-sm text-slate-900 dark:text-white">{{ formatDate(selectedPayment.created_at) }}</p>
              </div>
              <div v-if="selectedPayment.payplus_transaction_id" class="col-span-2">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">PayPlus Transaction ID</p>
                <p class="text-sm text-slate-900 dark:text-white font-mono">{{ selectedPayment.payplus_transaction_id }}</p>
              </div>
            </div>

            <!-- Update Status -->
            <div v-if="selectedPayment.status !== 'success' && hasPermission('payment.manage')" class="border-t border-slate-200 dark:border-slate-700 pt-6">
              <h4 class="text-sm font-medium text-slate-900 dark:text-white mb-4">Modifier le statut</h4>
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nouveau statut</label>
                  <select
                    v-model="updateForm.status"
                    class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                  >
                    <option value="success">Payé</option>
                    <option value="failed">Échoué</option>
                    <option value="pending">En attente</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Note (optionnelle)</label>
                  <textarea
                    v-model="updateForm.note"
                    rows="3"
                    placeholder="Raison de la modification..."
                    class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                  ></textarea>
                </div>
                <button
                  @click="updatePaymentStatus"
                  :disabled="updating"
                  class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                  <span v-if="updating">Mise à jour...</span>
                  <span v-else>Mettre à jour le statut</span>
                </button>
              </div>
            </div>

            <!-- Metadata -->
            <div v-if="selectedPayment.metadata && Object.keys(selectedPayment.metadata).length > 0" class="border-t border-slate-200 dark:border-slate-700 pt-6">
              <h4 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-3">METADATA</h4>
              <pre class="bg-slate-50 dark:bg-slate-900 p-4 rounded-lg text-xs text-slate-700 dark:text-slate-300 overflow-auto">{{ JSON.stringify(selectedPayment.metadata, null, 2) }}</pre>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Broadcast Modal -->
    <Teleport to="body">
      <div
        v-if="showBroadcast"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click.self="showBroadcast = false"
      >
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-2xl w-full">
          <div class="border-b border-slate-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Créer un lien de paiement général</h3>
            <button @click="showBroadcast = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cible</label>
              <select
                v-model="broadcastForm.target"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
              >
                <option value="all">Tous les étudiants</option>
                <option value="annexe">Étudiants d'une annexe</option>
                <option value="class">Étudiants d'une classe</option>
              </select>
            </div>

            <div v-if="broadcastForm.target === 'annexe' || broadcastForm.target === 'class'">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Annexe</label>
              <select
                v-model="broadcastForm.annexe_id"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
              >
                <option value="">Sélectionner une annexe</option>
                <option v-for="annexe in annexes" :key="annexe.id" :value="annexe.id">
                  {{ annexe.name }}
                </option>
              </select>
            </div>

            <div v-if="broadcastForm.target === 'class'">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Classe</label>
              <input
                v-model="broadcastForm.class"
                type="text"
                placeholder="Ex: Licence 1 Informatique"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Amount (XOF)</label>
                <input
                  v-model.number="broadcastForm.amount"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type</label>
                <select
                  v-model="broadcastForm.type"
                  class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
                  <option value="tuition">Scolarité</option>
                  <option value="registration">Inscription</option>
                  <option value="other">Autre</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
              <textarea
                v-model="broadcastForm.description"
                rows="3"
                placeholder="Motif du paiement..."
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date d'échéance</label>
                <input
                  v-model="broadcastForm.due_date"
                  type="date"
                  class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Expiration</label>
                <input
                  v-model="broadcastForm.expire_at"
                  type="date"
                  class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                />
              </div>
            </div>

            <div class="flex items-center">
              <input
                v-model="broadcastForm.send_email"
                id="send-email"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded"
              />
              <label for="send-email" class="ml-2 block text-sm text-slate-700 dark:text-slate-300">
                Send automatically by email
              </label>
            </div>

            <div class="flex gap-3 pt-4">
              <button
                @click="showBroadcast = false"
                class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
              >
                Cancel
              </button>
              <button
                @click="createBroadcast"
                :disabled="broadcasting"
                class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
              >
                <span v-if="broadcasting">Creating...</span>
                <span v-else>Create links</span>
              </button>
            </div>

            <div v-if="broadcastResult" class="mt-4 p-4 rounded-lg" :class="broadcastResult.errors?.length > 0 ? 'bg-orange-50 dark:bg-orange-900/20' : 'bg-green-50 dark:bg-green-900/20'">
              <p class="text-sm font-medium" :class="broadcastResult.errors?.length > 0 ? 'text-orange-800 dark:text-orange-200' : 'text-green-800 dark:text-green-200'">
                {{ broadcastResult.message }}
              </p>
              <p class="text-xs mt-1" :class="broadcastResult.errors?.length > 0 ? 'text-orange-600 dark:text-orange-300' : 'text-green-600 dark:text-green-300'">
                {{ broadcastResult.created }} liens créés • {{ broadcastResult.sent }} emails envoyés
              </p>
              <div v-if="broadcastResult.errors?.length > 0" class="mt-2 text-xs text-orange-600 dark:text-orange-300">
                <details>
                  <summary class="cursor-pointer">{{ broadcastResult.errors.length }} erreur(s)</summary>
                  <ul class="list-disc list-inside mt-1 space-y-1">
                    <li v-for="(error, idx) in broadcastResult.errors" :key="idx">{{ error }}</li>
                  </ul>
                </details>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import AvatarDisplay from '@/components/shared/AvatarDisplay.vue';
import paymentService from '@/services/paymentService';
import paymentLinkService from '@/services/paymentLinkService';
import annexeService from '@/services/annexeService';
import { useActiveYearStore } from '@/stores/useActiveYearStore';
import { usePermissions } from '@/composables/usePermissions';

const { hasPermission } = usePermissions();

const activeYearStore = useActiveYearStore();

const loading = ref(false);
const payments = ref([]);
const annexes = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  from: 0,
  to: 0,
});

const filters = ref({
  q: '',
  annexe_id: '',
  status: '',
  school_year: activeYearStore.activeYear || '',
  from: '',
  to: '',
});

const kpis = ref({
  total: 0,
  total_success: 0,
  pending: 0,
  failed: 0,
});

const searchTimeout = ref(null);

const showDetail = ref(false);
const selectedPayment = ref(null);
const updating = ref(false);
const updateForm = ref({
  status: 'success',
  note: '',
});

const showBroadcast = ref(false);
const broadcasting = ref(false);
const broadcastResult = ref(null);
const broadcastForm = ref({
  target: 'all',
  annexe_id: '',
  class: '',
  school_year: activeYearStore.activeYear || '',
  amount: 0,
  type: 'tuition',
  description: '',
  due_date: '',
  expire_at: '',
  send_email: false,
});

const visiblePages = computed(() => {
  const pages = [];
  const total = pagination.value.last_page;
  const current = pagination.value.current_page;
  
  let start = Math.max(1, current - 2);
  let end = Math.min(total, current + 2);
  
  if (end - start < 4) {
    if (start === 1) {
      end = Math.min(total, start + 4);
    } else {
      start = Math.max(1, end - 4);
    }
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  return pages;
});

async function loadPayments() {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
      ...filters.value,
    };
    
    const response = await paymentService.index(params);
    payments.value = response.data.data;
    
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      per_page: response.data.per_page,
      total: response.data.total,
      from: response.data.from || 0,
      to: response.data.to || 0,
    };
    
    calculateKPIs();
  } catch (error) {
    console.error('Error loading payments:', error);
  } finally {
    loading.value = false;
  }
}

async function loadAnnexes() {
  try {
    const response = await annexeService.index();
    annexes.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error loading annexes:', error);
  }
}

function calculateKPIs() {
  kpis.value.total = pagination.value.total;
  kpis.value.total_success = payments.value
    .filter(p => p.status === 'success')
    .reduce((sum, p) => sum + parseFloat(p.amount), 0);
  kpis.value.pending = payments.value.filter(p => p.status === 'pending').length;
  kpis.value.failed = payments.value.filter(p => p.status === 'failed').length;
}

function debounceSearch() {
  if (searchTimeout.value) clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(() => {
    pagination.value.current_page = 1;
    loadPayments();
  }, 500);
}

function changePage(page) {
  if (page >= 1 && page <= pagination.value.last_page) {
    pagination.value.current_page = page;
    loadPayments();
  }
}

function viewDetails(payment) {
  selectedPayment.value = payment;
  updateForm.value.status = payment.status === 'success' ? 'success' : 'success';
  updateForm.value.note = '';
  showDetail.value = true;
}

async function updatePaymentStatus() {
  if (!selectedPayment.value) return;
  
  updating.value = true;
  try {
    const response = await paymentService.updateStatus(selectedPayment.value.id, updateForm.value);
    
    const index = payments.value.findIndex(p => p.id === selectedPayment.value.id);
    if (index !== -1) {
      payments.value[index] = response.data.payment;
    }
    selectedPayment.value = response.data.payment;
    
    calculateKPIs();
    
    alert('Statut mis à jour avec succès');
  } catch (error) {
    console.error('Error updating payment status:', error);
    alert('Erreur lors de la mise à jour du statut');
  } finally {
    updating.value = false;
  }
}

async function createBroadcast() {
  broadcasting.value = true;
  broadcastResult.value = null;
  
  try {
    const response = await paymentLinkService.broadcast(broadcastForm.value);
    broadcastResult.value = response.data;
    
    setTimeout(() => {
      broadcastResult.value = null;
      if (response.data.created > 0) {
        showBroadcast.value = false;
        resetBroadcastForm();
      }
    }, 5000);
  } catch (error) {
    console.error('Error creating broadcast:', error);
    alert('Erreur lors de la création des liens');
  } finally {
    broadcasting.value = false;
  }
}

function resetBroadcastForm() {
  broadcastForm.value = {
    target: 'all',
    annexe_id: '',
    class: '',
    school_year: activeYearStore.activeYear || '',
    amount: 0,
    type: 'tuition',
    description: '',
    due_date: '',
    expire_at: '',
    send_email: false,
  };
}

function getStatusClass(status) {
  const classes = {
    success: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
    pending: 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300',
    failed: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
  };
  return classes[status] || 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200';
}

function getStatusLabel(status) {
  const labels = {
    success: 'Réussi',
    pending: 'En attente',
    failed: 'Échoué',
  };
  return labels[status] || status;
}

function formatMoney(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0,
  }).format(amount);
}

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function getPaymentTypeLabel(type) {
  const labels = {
    tuition: 'Scolarité',
    registration: 'Inscription',
    exam: 'Examen',
    uniform: 'Uniforme',
    books: 'Livres',
    transport: 'Transport',
    canteen: 'Cantine',
    other: 'Autres',
  };
  return labels[type] || type || 'N/A';
}

onMounted(() => {
  activeYearStore.loadAvailableYears();
  filters.value.school_year = activeYearStore.activeYear || '';
  broadcastForm.value.school_year = activeYearStore.activeYear || '';
  loadPayments();
  loadAnnexes();
});

watch(() => activeYearStore.activeYear, (year) => {
  filters.value.school_year = year || '';
  broadcastForm.value.school_year = year || '';
  pagination.value.current_page = 1;
  loadPayments();
});
</script>
