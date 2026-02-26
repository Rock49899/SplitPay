import api from './api';

const API_URL = '/admin/study-levels';

export default {
  index(params = {}) {
    return api.get(API_URL, { params });
  },

  show(id) {
    return api.get(`${API_URL}/${id}`);
  },

  store(data) {
    return api.post(API_URL, data);
  },

  update(id, data) {
    return api.patch(`${API_URL}/${id}`, data);
  },

  destroy(id) {
    return api.delete(`${API_URL}/${id}`);
  }
};
