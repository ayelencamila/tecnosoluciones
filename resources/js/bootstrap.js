import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configurar CSRF token para Laravel
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Configurar withCredentials para mantener cookies de sesión
window.axios.defaults.withCredentials = true;

// Interceptor para manejar errores 419 (CSRF token expirado)
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            // Recargar la página para obtener nuevo token CSRF
            window.location.reload();
        }
        return Promise.reject(error);
    }
);



