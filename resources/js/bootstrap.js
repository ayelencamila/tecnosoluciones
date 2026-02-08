import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configurar withCredentials para que axios envíe cookies (incluida XSRF-TOKEN)
// y para que las respuestas las almacenen.
window.axios.defaults.withCredentials = true;

// NOTA: NO configuramos X-CSRF-TOKEN manualmente.
// Laravel acepta CSRF tokens por tres vías (en orden de prioridad):
//   1. _token input field
//   2. X-CSRF-TOKEN header (espera token plain-text de sesión)
//   3. X-XSRF-TOKEN header (espera token ENCRIPTADO de la cookie)
//
// El problema con X-CSRF-TOKEN del <meta> tag es que en una SPA (Inertia),
// el meta tag se genera en la primera carga y NUNCA se actualiza.
// Si la sesión se regenera, el meta tag queda obsoleto y Laravel rechaza
// el request con 419 — SIN siquiera evaluar X-XSRF-TOKEN.
//
// Axios, con withCredentials=true, automáticamente lee la cookie XSRF-TOKEN
// (que Laravel renueva en cada response) y la envía como X-XSRF-TOKEN.
// Este mecanismo es siempre fresco y no tiene el problema de desincronización.

// Interceptor para manejar errores 419 (CSRF token expirado)
// Si ocurre un 419, renueva la cookie XSRF-TOKEN vía Sanctum y reintenta.
let isRefreshingAxios = false;
let failedQueue = [];

function processQueue(error) {
    failedQueue.forEach(({ config, resolve, reject }) => {
        if (error) {
            reject(error);
        } else {
            resolve(window.axios(config));
        }
    });
    failedQueue = [];
}

window.axios.interceptors.response.use(
    response => response,
    async error => {
        const originalRequest = error.config;

        if (error.response?.status === 419 && !originalRequest._retry) {
            originalRequest._retry = true;

            if (isRefreshingAxios) {
                return new Promise((resolve, reject) => {
                    failedQueue.push({ config: originalRequest, resolve, reject });
                });
            }

            isRefreshingAxios = true;

            try {
                // Renovar la cookie XSRF-TOKEN vía Sanctum
                await fetch('/sanctum/csrf-cookie', { method: 'GET', credentials: 'same-origin' })
                    .catch(() => fetch('/', { method: 'HEAD', credentials: 'same-origin' }));

                processQueue(null);
                return window.axios(originalRequest);
            } catch (refreshError) {
                processQueue(refreshError);
                return Promise.reject(refreshError);
            } finally {
                isRefreshingAxios = false;
            }
        }

        return Promise.reject(error);
    }
);



