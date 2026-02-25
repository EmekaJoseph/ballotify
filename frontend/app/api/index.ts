import axios from 'axios';

// Create a custom axios instance
const api = axios.create({
    baseURL: 'http://localhost:8883/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Add a request interceptor to include the Bearer token
api.interceptors.request.use(
    (config) => {
        if (typeof window !== 'undefined') {
            const token = localStorage.getItem('auth_token');
            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Add a response interceptor for global error handling
api.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        // Handle unauthorized errors (401)
        if (error.response && error.response.status === 401) {
            if (typeof window !== 'undefined') {
                localStorage.removeItem('auth_token');
                // Optional: Redirect to login or emit event
                // window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export default api;
