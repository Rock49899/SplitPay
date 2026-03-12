<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Notifications" />
    
    <div class="mt-6">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Gérez vos notifications système
        </p>
      </div>

      <div class="flex gap-3">
        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          Tout marquer comme lu
        </button>

        <button
          @click="clearAllRead"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
          </svg>
          Supprimer les lues
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-wrap gap-4 items-center bg-white dark:bg-gray-dark p-4 rounded-lg shadow-sm">
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Afficher :</label>
        <select
          v-model="filters.unread"
          @change="loadNotifications"
          class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
        >
          <option :value="null">Toutes</option>
          <option :value="true">Non lues</option>
          <option :value="false">Lues</option>
        </select>
      </div>

      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Type :</label>
        <select
          v-model="filters.type"
          @change="loadNotifications"
          class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
        >
          <option value="">Tous les types</option>
          <option value="payment_received">Paiements reçus</option>
          <option value="payment_failed">Paiements échoués</option>
          <option value="due_date_approaching">Échéances proches</option>
          <option value="payment_overdue">Retards de paiement</option>
        </select>
      </div>

      <div class="ml-auto text-sm text-gray-600 dark:text-gray-400">
        <span v-if="unreadCount > 0" class="font-semibold text-blue-600 dark:text-blue-400">
          {{ unreadCount }} non lue(s)
        </span>
        <span v-else class="text-gray-500">Aucune notification non lue</span>
      </div>
    </div>

    <!-- Notifications List -->
    <div v-if="loading && notifications.length === 0" class="flex justify-center py-12">
      <svg class="animate-spin h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <div v-else-if="notifications.length === 0" class="bg-white dark:bg-gray-dark rounded-lg shadow-sm p-12 text-center">
      <svg class="w-20 h-20 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
      </svg>
      <p class="text-gray-500 dark:text-gray-400">Aucune notification à afficher</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        :class="[
          'bg-white dark:bg-gray-dark rounded-lg shadow-sm p-4 transition-all hover:shadow-md',
          !notification.is_read ? 'border-l-4 border-l-blue-500 bg-blue-50/50 dark:bg-blue-900/10' : ''
        ]"
      >
        <div class="flex items-start gap-4">
          <div class="flex-shrink-0 text-3xl mt-1">
            {{ getNotificationIcon(notification.type) }}
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-4 mb-2">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ notification.title }}
              </h3>
              <span class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-400">
                {{ formatTime(notification.created_at) }}
              </span>
            </div>

            <p class="text-gray-600 dark:text-gray-400 mb-3">
              {{ notification.message }}
            </p>

            <div class="flex items-center gap-3">
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  getTypeColor(notification.type)
                ]"
              >
                {{ getTypeLabel(notification.type) }}
              </span>

              <button
                v-if="!notification.is_read"
                @click="markAsRead(notification)"
                class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
              >
                Marquer comme lu
              </button>

              <button
                @click="deleteNotification(notification)"
                class="ml-auto text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
              >
                Supprimer
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > 0" class="mt-6 flex items-center justify-between bg-white dark:bg-gray-dark p-4 rounded-lg shadow-sm">
      <div class="text-sm text-gray-600 dark:text-gray-400">
        Affichage de {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} notifications
      </div>

      <div class="flex gap-2">
        <button
          @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page <= 1 || loading"
          class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-700 dark:hover:bg-gray-800"
        >
          Précédent
        </button>
        <button
          @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page >= pagination.last_page || loading"
          class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-700 dark:hover:bg-gray-800"
        >
          Suivant
        </button>
      </div>
    </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import notificationService from '@/services/notificationService'
import Swal from 'sweetalert2'

const notifications = ref([])
const unreadCount = ref(0)
const loading = ref(false)

const filters = ref({
  unread: null,
  type: '',
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
})

const loadNotifications = async (page = 1) => {
  try {
    loading.value = true

    const params = {
      page,
      limit: 20,
    }

    if (filters.value.unread !== null) {
      params.unread = filters.value.unread
    }

    if (filters.value.type) {
      params.type = filters.value.type
    }

    const [notificationsResponse, countResponse] = await Promise.all([
      notificationService.getAll(params),
      notificationService.getUnreadCount(),
    ])

    notifications.value = notificationsResponse.data.data
    pagination.value = {
      current_page: notificationsResponse.data.current_page,
      last_page: notificationsResponse.data.last_page,
      from: notificationsResponse.data.from || 0,
      to: notificationsResponse.data.to || 0,
      total: notificationsResponse.data.total,
    }

    unreadCount.value = countResponse.data.count
  } catch (error) {
    console.error('Failed to load notifications:', error)
    Swal.fire('Erreur', 'Impossible de charger les notifications', 'error')
  } finally {
    loading.value = false
  }
}

const changePage = (page) => {
  loadNotifications(page)
}

const markAsRead = async (notification) => {
  try {
    await notificationService.markAsRead(notification.id)
    notification.is_read = true
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (error) {
    console.error('Failed to mark as read:', error)
    Swal.fire('Erreur', 'Impossible de marquer comme lu', 'error')
  }
}

const markAllAsRead = async () => {
  try {
    const result = await Swal.fire({
      title: 'Confirmer',
      text: 'Marquer toutes les notifications comme lues ?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Oui',
      cancelButtonText: 'Annuler',
    })

    if (result.isConfirmed) {
      await notificationService.markAllAsRead()
      await loadNotifications(pagination.value.current_page)
      Swal.fire('Succès', 'Toutes les notifications sont marquées comme lues', 'success')
    }
  } catch (error) {
    console.error('Failed to mark all as read:', error)
    Swal.fire('Erreur', 'Impossible de marquer toutes comme lues', 'error')
  }
}

const deleteNotification = async (notification) => {
  try {
    const result = await Swal.fire({
      title: 'Confirmer',
      text: 'Supprimer cette notification ?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Supprimer',
      cancelButtonText: 'Annuler',
    })

    if (result.isConfirmed) {
      await notificationService.delete(notification.id)
      await loadNotifications(pagination.value.current_page)
      Swal.fire('Succès', 'Notification supprimée', 'success')
    }
  } catch (error) {
    console.error('Failed to delete notification:', error)
    Swal.fire('Erreur', 'Impossible de supprimer la notification', 'error')
  }
}

const clearAllRead = async () => {
  try {
    const result = await Swal.fire({
      title: 'Confirmer',
      text: 'Supprimer toutes les notifications lues ?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Supprimer',
      cancelButtonText: 'Annuler',
    })

    if (result.isConfirmed) {
      await notificationService.clearRead()
      await loadNotifications(1)
      Swal.fire('Succès', 'Notifications lues supprimées', 'success')
    }
  } catch (error) {
    console.error('Failed to clear read notifications:', error)
    Swal.fire('Erreur', 'Impossible de supprimer les notifications lues', 'error')
  }
}

const formatTime = (date) => {
  try {
    const now = new Date()
    const then = new Date(date)
    const diffInSeconds = Math.floor((now - then) / 1000)
    
    if (diffInSeconds < 60) return 'à l\'instant'
    if (diffInSeconds < 3600) return `il y a ${Math.floor(diffInSeconds / 60)} min`
    if (diffInSeconds < 86400) return `il y a ${Math.floor(diffInSeconds / 3600)} h`
    if (diffInSeconds < 604800) return `il y a ${Math.floor(diffInSeconds / 86400)} j`
    
    return then.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return 'récemment'
  }
}

const getNotificationIcon = (type) => {
  switch (type) {
    case 'payment_received':
      return '🟢'
    case 'payment_failed':
      return '🔴'
    case 'due_date_approaching':
      return '⏰'
    case 'payment_overdue':
      return '🚨'
    default:
      return 'ℹ️'
  }
}

const getTypeLabel = (type) => {
  switch (type) {
    case 'payment_received':
      return 'Paiement reçu'
    case 'payment_failed':
      return 'Paiement échoué'
    case 'due_date_approaching':
      return 'Échéance proche'
    case 'payment_overdue':
      return 'Paiement en retard'
    default:
      return 'Notification'
  }
}

const getTypeColor = (type) => {
  switch (type) {
    case 'payment_received':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
    case 'payment_failed':
      return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    case 'due_date_approaching':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
    case 'payment_overdue':
      return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
}

onMounted(() => {
  loadNotifications()
})
</script>
