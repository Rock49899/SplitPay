import api from './api';

const index = (params = {}) => api.get('admin/students', { params });
const show = (id) => api.get(`admin/students/${id}`);
const store = (payload) => api.post('admin/students', payload);
const update = (id, payload) => api.put(`admin/students/${id}`, payload);
const destroy = (id) => api.delete(`admin/students/${id}`);

// new endpoints
const financials = (id) => api.get(`admin/students/${id}/financials`);
const createPaymentLink = (id, payload) => api.post(`admin/students/${id}/payment-link`, payload);

export default { index, show, store, update, destroy, financials, createPaymentLink };
