import api from './api'

export default {
  /**
   * Prévisualiser la promotion : retourne le nombre d'étudiants concernés,
   * les niveaux suivants, les tarifs associés.
   */
  preview(fromYear, toYear) {
    return api.get('admin/promotions/preview', {
      params: { from_year: fromYear, to_year: toYear },
    })
  },

  /**
   * Exécuter la promotion d'une année scolaire.
   * studentIds (optionnel) : limiter à certains étudiants
   */
  execute(fromYear, toYear, studentIds = null) {
    const payload = { from_year: fromYear, to_year: toYear }
    if (studentIds?.length) payload.student_ids = studentIds
    return api.post('admin/promotions', payload)
  },
}
