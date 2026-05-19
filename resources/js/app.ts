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

history.scrollRestoration = 'auto';

// Toast store for global notifications
import { toastStore } from '@/stores/toast';

// Handle flash messages, errors, and scroll restoration
router.on('success', (event) => {
    const page = event.detail?.page;
    const flash = page?.props?.flash as any;
    if (flash?.success) {
        toastStore.add(flash.success, 'success');
    }
    if (flash?.error) {
        toastStore.add(flash.error, 'error');
    }

    // Show Inertia validation errors that weren't caught inline
    const errors = page?.props?.errors as Record<string, string> | undefined;
    if (errors && typeof errors === 'object' && Object.keys(errors).length > 0) {
        const firstError = Object.values(errors)[0];
        if (firstError) {
            toastStore.add(firstError, 'error');
        }
    }

    // Restore scroll
    const saved = scrollPositions.get(window.location.pathname);
    if (saved !== undefined) {
        requestAnimationFrame(() => window.scrollTo(0, saved));
    }
});

// Handle Inertia errors — covers 409 validation, 500 server errors, and failed requests
router.on('error', (event) => {
    const errors = event.detail?.errors;
    if (typeof errors === 'string' && errors) {
        toastStore.add(errors, 'error');
    } else if (typeof errors === 'object' && errors) {
        const vals = Object.values(errors) as string[];
        if (vals.length > 0 && vals[0]) {
            toastStore.add(vals[0], 'error');
        }
    }
});

// Handle invalid responses (status 409 Conflict — validation errors that stopped navigation)
router.on('invalid', (event) => {
    const response = event.detail?.response;
    if (response?.status === 409) {
        toastStore.add('Please correct the errors in the form.', 'error');
    }
});

// Remember scroll position before navigation
const scrollPositions = new Map<string, number>();

router.on('before', () => {
    scrollPositions.set(window.location.pathname, window.scrollY);
});

router.on('navigate', () => {
    setTimeout(() => {
        HSStaticMethods.autoInit();
    }, 100);
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#fdff00',
    },
});
