<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { h, defineComponent } from 'vue';
import { logout } from '@/actions/App/Http/Controllers/Staff/StaffAuthController';
import StaffDashboardController from '@/actions/App/Http/Controllers/Staff/StaffDashboardController';
import { index as examIndex } from '@/actions/App/Http/Controllers/Staff/ExamController';
import { index as questionIndex, generate as aiLabGenerate } from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
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

const IconBank = defineComponent({
    render: () =>
        h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            }),
        ]),
});

const IconAI = defineComponent({
    render: () =>
        h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
            }),
        ]),
});

const navigation = [
    {
        name: 'Dashboard',
        href: StaffDashboardController().url,
        active: page.component === 'Staff/Dashboard',
        icon: IconDashboard,
    },
    {
        name: 'AI Question Lab',
        href: aiLabGenerate().url,
        active: page.component === 'QuestionBank/Generate',
        icon: IconAI,
        permission: 'use ai lab',
    },
    {
        name: 'Exam Management',
        href: examIndex().url,
        active: page.component.startsWith('Staff/Exams/'),
        icon: IconExams,
    },
    {
        name: 'Question Bank',
        href: questionIndex().url,
        active: page.component === 'QuestionBank/Index',
        icon: IconBank,
    },
].filter((item) => !item.permission || (page.props.auth.user as any).permissions.includes(item.permission));
</script>

<template>
    <DashboardLayout title="Staff Portal" :navigation="navigation" :logout-action="logout().url">
        <slot />
    </DashboardLayout>
</template>
