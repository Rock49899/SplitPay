import api from './api';

const index = (params = {}) => api.get('admin/annexes', { params });
const show = (id) => api.get(`admin/annexes/${id}`);
const store = (payload) => api.post('admin/annexes', payload);
const update = (id, payload) => api.put(`admin/annexes/${id}`, payload);
const destroy = (id) => api.delete(`admin/annexes/${id}`);

export default { index, show, store, update, destroy };
