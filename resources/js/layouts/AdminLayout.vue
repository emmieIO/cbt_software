<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { h, defineComponent } from 'vue';
import { logout, dashboard } from '@/actions/App/Http/Controllers/Admin/AdminController';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

const page = usePage();

const IconDashboard = defineComponent({
    render: () =>
        h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            }),
        ]),
});

const IconUsers = defineComponent({
    render: () =>
        h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            }),
        ]),
});

const IconExams = defineComponent({
    render: () =>
        h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01',
            }),
        ]),
});

const navigation = [
    {
        name: 'Dashboard',
        href: dashboard().url,
        active: page.component === 'Admin/Dashboard',
        icon: IconDashboard,
    },
    {
        name: 'Manage Users',
        href: '#',
        active: false,
        icon: IconUsers,
    },
    {
        name: 'Exams List',
        href: '#',
        active: false,
        icon: IconExams,
    },
];
</script>

<template>
    <DashboardLayout title="Admin Control Panel" :navigation="navigation" :logout-action="logout().url">
        <slot />
    </DashboardLayout>
</template>
