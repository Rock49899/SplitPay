/**
 * Retourne les N dernières années scolaires (format "YYYY-YYYY")
 * et l'année courante (mois >= 9 = nouvelle année démarrée).
 */
export function useSchoolYear(count = 4) {
  const now = new Date()
  const year = now.getMonth() >= 8 ? now.getFullYear() : now.getFullYear() - 1
  const current = `${year}-${year + 1}`
  const options = Array.from({ length: count }, (_, i) => {
    const y = year - i
    return `${y}-${y + 1}`
  })
  return { current, options }
}
