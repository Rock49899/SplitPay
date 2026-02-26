import api from './api';

export default {
  // admin
  index(params = {}) {
    return api.get('admin/payments', { params });
  },
  show(id) {
    return api.get(`admin/payments/${id}`);
  },
  updateStatus(id, payload) {
    return api.patch(`admin/payments/${id}/status`, payload);
  },
  createPublic(payload) {
    return api.post('payments/public', payload);
  },
  // admin create (if needed)
  store(payload) {
    return api.post('admin/payments', payload);
  },
  // create a PayPlus checkout for a public payment link
  createPublicCheckout(payload) {
    return api.post('payments/public/checkout', payload);
  },
  // polling : vérifie le statut d'un paiement via PayPlus confirm()
  checkStatus(reference) {
    return api.get(`payments/check/${reference}`);
  },
};
