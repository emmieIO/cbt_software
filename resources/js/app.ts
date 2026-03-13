import 'preline/dist/index.js';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { HSStaticMethods } from 'preline';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';

import '../css/app.css';

// @ts-expect-error: HSStaticMethods is globally defined by Preline
window.HSStaticMethods = HSStaticMethods;

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
