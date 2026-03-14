import api from './api';

/**
 * AuthService
 * - login(credentials) -> POST /api/admin/login
 * - logout() -> POST /api/admin/logout
 * - me() -> GET /api/admin/me
 */
const login = (credentials) => api.post('admin/login', credentials);
const requestOtp = (email) => api.post('admin/request-otp', { email });
const verifyOtp = (email, otp) => api.post('admin/verify-otp', { email, otp });
const logout = () => api.post('admin/logout');
const me = () => api.get('admin/me');

export default { login, requestOtp, verifyOtp, logout, me };
