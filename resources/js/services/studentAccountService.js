import api from './api';

// Clé localStorage pour le token de session étudiant
const TOKEN_KEY = 'student_token';
const TOKEN_EXP_KEY = 'student_token_expires_at';

export default {
  //Envoi du matricule pr généré et envoie OTP par email 
  requestOtp(matricule) {
    return api.post('students/login', { matricule });
  },

  verifyOtp(matricule, otp) {
    return api.post('students/verify-otp', { matricule, otp });
  },

  saveToken(token, expiresInMinutes = 60) {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(TOKEN_EXP_KEY, String(Date.now() + (expiresInMinutes * 60 * 1000)));
  },

  getToken() {
    return localStorage.getItem(TOKEN_KEY);
  },

  removeToken() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(TOKEN_EXP_KEY);
  },

  isAuthenticated() {
    const token = this.getToken();
    if (!token) return false;

    const expiresAt = Number(localStorage.getItem(TOKEN_EXP_KEY) || 0);
    if (expiresAt && Date.now() > expiresAt) {
      this.removeToken();
      return false;
    }

    return true;
  },

  authHeaders() {
    const token = this.getToken();
    return token ? { Authorization: `Bearer ${token}` } : {};
  },

  logout() {
    this.removeToken();
  },

  getProfile(params = {}) {
    return api.get('student/profile', { params, headers: this.authHeaders() });
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
