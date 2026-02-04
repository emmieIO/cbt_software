<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { logout, dashboard } from '@/actions/App/Http/Controllers/Student/StudentController';
import { usePage } from '@inertiajs/vue3';
import { h, defineComponent } from 'vue';

const page = usePage();

const IconDashboard = defineComponent({
    render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' })
    ])
});

const IconExams = defineComponent({
    render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01' })
    ])
});

const IconResults = defineComponent({
    render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' })
    ])
});

const navigation = [
    {
        name: 'Dashboard',
        href: dashboard().url,
        active: page.component === 'Student/Dashboard',
        icon: IconDashboard
    },
    {
        name: 'Available Exams',
        href: '#',
        active: false,
        icon: IconExams
    },
    {
        name: 'My Results',
        href: '#',
        active: false,
        icon: IconResults
    },
];
</script>

<template>
    <DashboardLayout 
        title="Student Portal" 
        :navigation="navigation" 
        :logout-action="logout().url"
    >
        <slot />
    </DashboardLayout>
</template>