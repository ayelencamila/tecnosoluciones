import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();

// Función para renovar la cookie XSRF-TOKEN silenciosamente vía Sanctum.
function refreshCsrfToken() {
    return fetch('/sanctum/csrf-cookie', { method: 'GET', credentials: 'same-origin' })
        .catch(() => fetch('/', { method: 'HEAD', credentials: 'same-origin' }));
}

// Manejar errores de sesión expirada (419) en navegaciones Inertia
// — renovar cookie CSRF y reintentar automáticamente
let isRefreshing419 = false;
router.on('invalid', (event) => {
    if (event.detail.response.status === 419 && !isRefreshing419) {
        event.preventDefault();
        isRefreshing419 = true;
        refreshCsrfToken()
            .finally(() => {
                isRefreshing419 = false;
                router.reload({ preserveState: true, preserveScroll: true });
            });
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(pinia)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});