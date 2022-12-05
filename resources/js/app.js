import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import Layout from "@/Layouts/Layout.vue"
import { Inertia } from '@inertiajs/inertia';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'));
        page.then(module => {
            module.default.layout = module.default.layout || Layout
        })
        return page;
    },
    setup ({ el, app, props, plugin }) {
        return createApp({ render: () => h(app, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .mount(el);
    },
});

InertiaProgress.init({ color: '#4B5563', delay: 250, showSpinner: false, });

// window.addEventListener('popstate', (event) => {
//     event.stopImmediatePropagation();

//     Inertia.reload({
//         preserveState: false,
//         preserveScroll: false,
//         replace: true,
//         onSuccess: (page) => Inertia.setPage(page),
//         onError: () => window.location.href = event.state.url,
//     });
// });
