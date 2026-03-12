import api from './api';

export default {
  /**
   * Récupérer toutes les notifications avec filtres
   * @param {Object} params - Paramètres de filtrage
   * @param {boolean} params.unread - Uniquement les non lues
   * @param {string} params.type - Type de notification
   * @param {number} params.limit - Nombre de résultats par page
   * @param {number} params.page - Numéro de page
   * @returns {Promise}
   */
  getAll(params = {}) {
    return api.get('/admin/notifications', { params });
  },

  /**
   * Récupérer le nombre de notifications non lues
   * @returns {Promise<{count: number}>}
   */
  getUnreadCount() {
    return api.get('/admin/notifications/unread-count');
  },

  /**
   * Récupérer une notification par ID
   * @param {string} id - ID de la notification
   * @returns {Promise}
   */
  getById(id) {
    return api.get(`/admin/notifications/${id}`);
  },

  /**
   * Marquer une notification comme lue
   * @param {string} id - ID de la notification
   * @returns {Promise}
   */
  markAsRead(id) {
    return api.patch(`/admin/notifications/${id}/mark-as-read`);
  },

  /**
   * Marquer toutes les notifications comme lues
   * @returns {Promise}
   */
  markAllAsRead() {
    return api.post('/admin/notifications/mark-all-as-read');
  },

  /**
   * Supprimer une notification
   * @param {string} id - ID de la notification
   * @returns {Promise}
   */
  delete(id) {
    return api.delete(`/admin/notifications/${id}`);
  },

  /**
   * Supprimer toutes les notifications lues
   * @returns {Promise}
   */
  clearRead() {
    return api.delete('/admin/notifications/clear-read');
  },

  /**
   * Récupérer les notifications récentes (non lues, limitées)
   * @param {number} limit - Nombre de notifications à récupérer
   * @returns {Promise}
   */
  getRecent(limit = 10) {
    return api.get('/admin/notifications', {
      params: {
        unread: true,
        limit,
        recent_days: 7,
      },
    });
  },
};
