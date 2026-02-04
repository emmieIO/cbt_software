<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { h, defineComponent } from 'vue';
import { logout, dashboard, index } from '@/actions/App/Http/Controllers/Staff/StaffController';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

const page = usePage();

const IconDashboard = defineComponent({
    render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' })
    ])
});

const IconClasses = defineComponent({
    render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01' })
    ])
});

const IconSchedule = defineComponent({
    render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' })
    ])
});

const IconBank = defineComponent({
    render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' })
    ])
});

const navigation = [
    {
        name: 'Dashboard',
        href: dashboard().url,
        active: page.component === 'Staff/Dashboard',
        icon: IconDashboard
    },
    {
        name: 'My Classes',
        href: '#',
        active: false,
        icon: IconClasses
    },
    {
        name: 'Exam Schedule',
        href: '#',
        active: false,
        icon: IconSchedule
    },
    {
        name: 'Question Bank',
        href: index().url,
        active: page.component.startsWith('Staff/Questions'),
        icon: IconBank
    },
];
</script>

<template>
    <DashboardLayout 
        title="Staff Portal" 
        :navigation="navigation" 
        :logout-action="logout().url"
    >
        <slot />
    </DashboardLayout>
</template>