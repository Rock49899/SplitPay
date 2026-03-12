import api from './api'

export default {
  /**
   * Lister les tarifs (filtrable par study_level_id, school_year, specialization_id)
   */
  index(params = {}) {
    return api.get('admin/level-fees', { params })
  },

  /**
   * Créer un tarif
   */
  store(data) {
    return api.post('admin/level-fees', data)
  },

  /**
   * Mettre à jour un tarif
   */
  update(id, data) {
    return api.patch(`admin/level-fees/${id}`, data)
  },

  /**
   * Supprimer un tarif
   */
  destroy(id) {
    return api.delete(`admin/level-fees/${id}`)
  },

  /**
   * Résoudre le tarif pour une combinaison niveau + filière + année
   */
  resolve(params) {
    return api.get('admin/level-fees/resolve', { params })
  },

  /**
   * Copier tous les barèmes d'une année scolaire vers une autre.
   * Les barèmes déjà présents pour la cible sont ignorés (pas d'écrasement).
   */
  copyYear(fromYear, toYear) {
    return api.post('admin/level-fees/copy-year', { from_year: fromYear, to_year: toYear })
  },
}
