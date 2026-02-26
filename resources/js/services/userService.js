import api from './api';


const index = (params = {}) => api.get('admin/users', { params });
const show = (id) => api.get(`admin/users/${id}`);
const store = (payload) => api.post('admin/users', payload);
// FormData requires POST + _method spoofing (browsers don't support PUT multipart)
const update = (id, payload) => {
  if (payload instanceof FormData) {
    payload.append('_method', 'PUT');
    return api.post(`admin/users/${id}`, payload);
  }
  return api.put(`admin/users/${id}`, payload);
};
const destroy = (id) => api.delete(`admin/users/${id}`);

export default { index, show, store, update, destroy };
