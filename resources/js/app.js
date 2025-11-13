import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'; // <<-- IMPORTANTE: Usamos el helper oficial

// Quitamos todas las importaciones adicionales por ahora
// import { createPinia } from 'pinia';
// import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.mjs'; // Deja esta línea comentada
// import { route as ziggyRoute } from 'ziggy-js';

// const pinia = createPinia(); // Comentado

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'; // Usa el nombre de la app

createInertiaApp({
    title: (title) => `${title} - ${appName}`, // Usa el nombre de la app
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')), // <<-- Usamos el helper oficial
    setup({ el, App, props, plugin }) {
        // Quitamos toda la lógica adicional
        // const ziggyConfig = props.initialPage.props.ziggy;
        // window.route = (name, params, absolute) => ziggyRoute(name, params, absolute, ziggyConfig);
        
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            // .use(pinia) // Comentado
            // .use(ZiggyVue, ziggyConfig) // Comentado
            .mount(el);
    },
    progress: {
        color: '#4F46E5',
    },
});