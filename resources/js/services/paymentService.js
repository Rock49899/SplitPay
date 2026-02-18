import api from './api';

export default {
  // admin
  index(params = {}) {
    return api.get('admin/payments', { params });
  },
  show(id) {
    return api.get(`admin/payments/${id}`);
  },
  // public creation (used by the public payment page that posts payment_link_id or token-resolved id)
  createPublic(payload) {
    // payload should include payment_link_id, amount, method, metadata...
    return api.post('payments/public', payload);
  },
  // admin create (if needed)
  store(payload) {
    return api.post('admin/payments', payload);
  },
};
