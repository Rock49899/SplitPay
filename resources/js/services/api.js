import axios from 'axios';

const api = axios.create({
	// prefer Vite env variable; fallback to /api
	baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
	headers: {
		Accept: 'application/json',
		'Content-Type': 'application/json',
	},
});

// Intercepteur: si FormData, laisser le navigateur gérer Content-Type (multipart/form-data + boundary)
api.interceptors.request.use((config) => {
	if (config.data instanceof FormData) {
		delete config.headers['Content-Type'];
	}
	
	// Ajouter automatiquement l'ID de l'annexe active
	const activeAnnexeId = localStorage.getItem('active_annexe_id');
	if (activeAnnexeId) {
		config.headers['X-Active-Annexe-Id'] = activeAnnexeId;
	}

	// Ajouter automatiquement l'année scolaire active
	const activeSchoolYear = localStorage.getItem('active_school_year');
	if (activeSchoolYear) {
		config.headers['X-Active-School-Year'] = activeSchoolYear;
	}
	
	return config;
});

api.interceptors.response.use(
	(response) => {
		const effectiveYear = response?.headers?.['x-effective-school-year'];
		if (effectiveYear && localStorage.getItem('active_school_year') !== effectiveYear) {
			localStorage.setItem('active_school_year', effectiveYear);
		}

		return response;
	},
	(error) => {
		const effectiveYear = error?.response?.headers?.['x-effective-school-year'];
		if (effectiveYear && localStorage.getItem('active_school_year') !== effectiveYear) {
			localStorage.setItem('active_school_year', effectiveYear);
		}

		return Promise.reject(error);
	}
);

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
