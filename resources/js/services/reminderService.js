import api from './api';

export default {
  /**
   * Récupérer tous les rappels
   * @param {Object} params - Paramètres de filtrage
   * @param {boolean} params.active_only - Uniquement les rappels actifs
   * @returns {Promise}
   */
  getAll(params = {}) {
    return api.get('/admin/reminders', { params });
  },

  /**
   * Récupérer un rappel par ID
   * @param {string} id - ID du rappel
   * @returns {Promise}
   */
  getById(id) {
    return api.get(`/admin/reminders/${id}`);
  },

  /**
   * Créer un nouveau rappel
   * @param {Object} data - Données du rappel
   * @param {string} data.annexe_id - ID de l'annexe
   * @param {number} data.days_before - Nombre de jours avant échéance
   * @param {string} data.message - Message du rappel
   * @param {boolean} data.is_active - Statut actif/inactif
   * @returns {Promise}
   */
  create(data) {
    return api.post('/admin/reminders', data);
  },

  /**
   * Mettre à jour un rappel
   * @param {string} id - ID du rappel
   * @param {Object} data - Données à mettre à jour
   * @returns {Promise}
   */
  update(id, data) {
    return api.patch(`/admin/reminders/${id}`, data);
  },

  /**
   * Activer un rappel
   * @param {string} id - ID du rappel
   * @returns {Promise}
   */
  activate(id) {
    return api.patch(`/admin/reminders/${id}/activate`);
  },

  /**
   * Désactiver un rappel
   * @param {string} id - ID du rappel
   * @returns {Promise}
   */
  deactivate(id) {
    return api.patch(`/admin/reminders/${id}/deactivate`);
  },

  /**
   * Supprimer un rappel
   * @param {string} id - ID du rappel
   * @returns {Promise}
   */
  delete(id) {
    return api.delete(`/admin/reminders/${id}`);
  },

  /**
   * Prévisualiser les étudiants concernés par un rappel
   * @param {string} id - ID du rappel
   * @returns {Promise}
   */
  preview(id) {
    return api.get(`/admin/reminders/${id}/preview`);
  },

  /**
   * Envoyer le rappel immédiatement
   * @param {string} id - ID du rappel
   * @returns {Promise}
   */
  sendNow(id) {
    return api.post(`/admin/reminders/${id}/send-now`);
  },
};
