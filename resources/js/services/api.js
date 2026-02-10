import axios from 'axios';

const api = axios.create({
	// prefer Vite env variable; fallback to /api
	baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
	headers: {
		Accept: 'application/json',
		'Content-Type': 'application/json',
	},
});

// helper to set/remove Authorization header
function applyToken(token) {
	if (token) {
		api.defaults.headers.common.Authorization = `Bearer ${token}`;
	} else {
		delete api.defaults.headers.common.Authorization;
	}
}

export default api;
export { applyToken };
