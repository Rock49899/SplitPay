import api from './api';

const index = (params = {}) => api.get('admin/annexes', { params });

export default { index };
