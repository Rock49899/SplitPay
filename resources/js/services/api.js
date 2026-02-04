import axios from 'axios';

// Configuration de base pour Axios
const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Intercepteur pour ajouter le token a chaque requete
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        
        console.log('Requete API:', config.method.toUpperCase(), config.url);
        
        return config;
    },
    (error) => {
        console.error('Erreur requete:', error);
        return Promise.reject(error);
    }
);

// Intercepteur pour gerer les erreurs de reponse
api.interceptors.response.use(
    (response) => {
        console.log('Reponse API:', response.status, response.config.url);
        return response;
    },
    (error) => {
        console.error('Erreur reponse:', error.response?.status, error.response?.data);
        
        // Si 401, rediriger vers login
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
        
        return Promise.reject(error);
    }
);

export default api;
