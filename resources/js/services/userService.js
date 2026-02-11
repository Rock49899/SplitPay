import api from './api';


const index = (params = {}) => api.get('admin/users', { params });
const show = (id) => api.get(`admin/users/${id}`);
const store = (payload) => api.post('admin/users', payload);
const update = (id, payload) => api.put(`admin/users/${id}`, payload);
const destroy = (id) => api.delete(`admin/users/${id}`);

export default { index, show, store, update, destroy };
