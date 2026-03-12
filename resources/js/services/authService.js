import api from './api';

/**
 * AuthService
 * - login(credentials) -> POST /api/admin/login
 * - logout() -> POST /api/admin/logout
 * - me() -> GET /api/admin/me
 */
const login = (credentials) => api.post('admin/login', credentials);
const logout = () => api.post('admin/logout');
const me = () => api.get('admin/me');

export default { login, logout, me };
