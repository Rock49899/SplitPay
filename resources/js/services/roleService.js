import api from './api';

const index = (params = {}) => api.get('admin/roles', { params });

export default { index };
