<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { h, defineComponent, computed } from 'vue';
import { index as examIndex, create as createExam, results as resultsIndex } from '@/actions/App/Http/Controllers/Staff/ExamController';
import { logout } from '@/actions/App/Http/Controllers/Staff/StaffAuthController';
import StaffDashboardController from '@/actions/App/Http/Controllers/Staff/StaffDashboardController';
import {
    index as questionIndex,
    generate as aiLabGenerate,
    create as createQuestion,
} from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import { index as studentIndex } from '@/actions/App/Http/Controllers/Staff/StudentController';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import type { User } from '@/types/auth';
import type { NavSection } from '@/types/navigation';

withDefaults(
    defineProps<{
        wide?: boolean;
    }>(),
    {
        wide: false,
    },
);

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

const IconStudents = defineComponent({
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

const IconResults = defineComponent({
    render: () =>
        h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            }),
        ]),
});

const navigation: NavSection[] = [
    {
        section: 'Dashboard',
        items: [
            {
                name: 'Overview',
                href: StaffDashboardController().url,
                active: page.component === 'Staff/Dashboard',
                icon: IconDashboard,
            },
            {
                name: 'My Students',
                href: studentIndex().url,
                active: page.component === 'Staff/Students/Index',
                icon: IconStudents,
                permission: 'student:view',
            },
        ],
    },
    {
        section: 'Question Bank',
        items: [
            {
                name: 'All Questions',
                href: questionIndex().url,
                active: page.component === 'QuestionBank/Index',
                icon: IconBank,
                permission: 'bank:view',
            },
            {
                name: 'Add Question',
                href: createQuestion().url,
                active: page.component === 'QuestionBank/Create',
                icon: IconBank,
                permission: 'bank:create',
            },
            {
                name: 'AI Generator',
                href: aiLabGenerate().url,
                active: page.component === 'QuestionBank/Generate',
                icon: IconAI,
                permission: 'bank:use_ai',
            },
        ],
    },
    {
        section: 'Exams & Results',
        items: [
            {
                name: 'Manage Exams',
                href: examIndex().url,
                active: page.component === 'Staff/Exams/Index',
                icon: IconExams,
                permission: 'exam:view',
            },
            {
                name: 'Create Exam',
                href: createExam().url,
                active: page.component === 'Staff/Exams/Create',
                icon: IconExams,
                permission: 'exam:create',
            },
            {
                name: 'View Results',
                href: resultsIndex().url,
                active: page.component === 'Staff/Results/Index',
                icon: IconResults,
                permission: 'results:view',
            },
        ],
    },
];

const filteredNavigation = computed(() => {
    return navigation
        .map((section) => ({
            ...section,
            items: section.items.filter((item) => {
                if (!item.permission) return true;
                const userPermissions = (page.props.auth.user as User)?.permissions || [];
                return userPermissions.includes(item.permission);
            }),
        }))
        .filter((section) => section.items.length > 0);
});
</script>

<template>
    <DashboardLayout title="Examiner Console" :navigation="filteredNavigation" :logout-action="logout().url" :wide="wide">
        <slot />
    </DashboardLayout>
</template>
