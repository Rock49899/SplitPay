import api from './api';


const register = (payload) => api.post('register', payload);

export default { register };
