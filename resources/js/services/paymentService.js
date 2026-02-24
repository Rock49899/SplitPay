import api from './api';

export default {
  // admin
  index(params = {}) {
    return api.get('admin/payments', { params });
  },
  show(id) {
    return api.get(`admin/payments/${id}`);
  },
  createPublic(payload) {
    // payload should include payment_link_id, amount, method, metadata...
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
};
