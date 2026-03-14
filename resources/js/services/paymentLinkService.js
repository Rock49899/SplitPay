import api from './api';

export default {
  // admin
  index(params = {}) {
    return api.get('admin/payment-links', { params });
  },
  show(id) {
    return api.get(`admin/payment-links/${id}`);
  },
  store(payload) {
    return api.post('admin/payment-links', payload);
  },
  broadcast(payload) {
    return api.post('admin/payment-links/broadcast', payload);
  },
  update(id, payload) {
    // try patch then put
    return api.patch(`admin/payment-links/${id}`, payload).catch((e) => {
      if (e?.response?.status === 405 || e?.response?.status === 404) {
        return api.put(`admin/payment-links/${id}`, payload);
      }
      throw e;
    });
  },
  destroy(id) {
    return api.delete(`admin/payment-links/${id}`);
  },

  // public
  publicShow(token) {
    return api.get(`payment-links/token/${token}`);
  },

  async sendEmail(id, payload) {
    return api.post(`admin/payment-links/${id}/send`, payload);
  },

  showBySlug(slug) {
    return api.get(`payment-links/token/${slug}`);
  }
};
