import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import 'preline/dist/index.js';
import { HSStaticMethods } from 'preline';

// @ts-ignore
window.HSStaticMethods = HSStaticMethods;

import '../css/app.css';

const appName = import.meta.env.VITE_APP_NAME || 'Chrisland CBT Software.';

router.on('navigate', () => {
    setTimeout(() => {
        HSStaticMethods.autoInit();
    }, 100);
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#fdff00',
    },
});
