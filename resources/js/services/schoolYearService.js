import api from './api'

export default {
  /** GET admin/school-years → { years: ['2025-2026', ...] } */
  index() {
    return api.get('admin/school-years')
  },
}
