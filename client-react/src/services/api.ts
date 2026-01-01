import axios from 'axios';

const api = axios.create({
    baseURL: '/',
    headers: {
        'Content-Type': 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        if (token.includes(':')) {
            config.headers['Authorization'] = `Basic ${btoa(token)}`;
        } else {
            config.headers['Espo-Authorization'] = token;
        }
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            // Avoid window.location.reload() to allow app to handle it
        }
        return Promise.reject(error);
    }
);

export default api;
