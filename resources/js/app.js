import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { route as ziggyRoute } from 'ziggy-js';

const pinia = createPinia();

createInertiaApp({
    title: (title) => `${title} - TecnoSoluciones`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const ziggyConfig = props.initialPage.props.ziggy;
        
        // Make route helper available globally
        window.route = (name, params, absolute) => ziggyRoute(name, params, absolute, ziggyConfig);
        
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue, ziggyConfig)
            .mount(el);
    },
    progress: {
        color: '#4F46E5',
        showSpinner: true,
    },
});
