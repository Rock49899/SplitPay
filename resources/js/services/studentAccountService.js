import api from './api';

// Clé localStorage pour le token de session étudiant
const TOKEN_KEY = 'student_token';

export default {
  //Envoi du matricule pr généré et envoie OTP par email 
  requestOtp(matricule) {
    return api.post('students/login', { matricule });
  },

  verifyOtp(matricule, otp) {
    return api.post('students/verify-otp', { matricule, otp });
  },

  saveToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
  },

  getToken() {
    return localStorage.getItem(TOKEN_KEY);
  },

  removeToken() {
    localStorage.removeItem(TOKEN_KEY);
  },

  isAuthenticated() {
    return !!this.getToken();
  },

  authHeaders() {
    const token = this.getToken();
    return token ? { Authorization: `Bearer ${token}` } : {};
  },

  logout() {
    this.removeToken();
  },

  getProfile() {
    return api.get('student/profile', { headers: this.authHeaders() });
  },

  /**
   * Liens de paiement (paginés)
   * @param {{ type?: 'tuition'|'other', status?: string, page?: number }} params
   */
  getPaymentLinks(params = {}) {
    return api.get('student/payment-links', { params, headers: this.authHeaders() });
  },

  /**
   * Historique des paiements confirmés 
   * @param {{ type?: 'tuition'|'other', from?: string, to?: string, method?: string, page?: number }} params
   */
  getPayments(params = {}) {
    return api.get('student/payments', { params, headers: this.authHeaders() });
  },
};
