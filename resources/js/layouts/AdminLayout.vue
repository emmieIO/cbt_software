<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { h, defineComponent, computed } from 'vue';
import { index as sessionsIndex } from '@/actions/App/Http/Controllers/Admin/AcademicSessionController';
import { logout, dashboard } from '@/actions/App/Http/Controllers/Admin/AdminController';
import { index as entranceIndex } from '@/actions/App/Http/Controllers/Admin/EntranceController';
import { index as permissionsIndex } from '@/actions/App/Http/Controllers/Admin/PermissionController';
import { index as promotionIndex } from '@/actions/App/Http/Controllers/Admin/PromotionController';
import { index as rolesIndex } from '@/actions/App/Http/Controllers/Admin/RoleController';
import { index as classesIndex } from '@/actions/App/Http/Controllers/Admin/SchoolClassController';
import { index as staffIndex } from '@/actions/App/Http/Controllers/Admin/StaffController';
import { index as studentIndex } from '@/actions/App/Http/Controllers/Admin/StudentController';
import { index as subjectsIndex } from '@/actions/App/Http/Controllers/Admin/SubjectController';
import { index as topicsIndex } from '@/actions/App/Http/Controllers/Admin/TopicController';
import { index as questionsIndex, generate as aiLabGenerate } from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
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

const IconSchool = defineComponent({
    render: () =>
        h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            }),
        ]),
});

const IconSubject = defineComponent({
    render: () =>
        h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            }),
        ]),
});

const IconTopic = defineComponent({
    render: () =>
        h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
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
        href: dashboard().url,
        active: page.component === 'Admin/Dashboard',
        icon: IconDashboard,
    },
    {
        name: 'School Classes',
        href: classesIndex().url,
        active: page.component === 'Admin/Classes/Index',
        icon: IconSchool,
        permission: 'manage school setup',
    },
    {
        name: 'Academic Subjects',
        href: subjectsIndex().url,
        active: page.component === 'Admin/Subjects/Index',
        icon: IconSubject,
        permission: 'manage curriculum',
    },
    {
        name: 'Curriculum Topics',
        href: topicsIndex().url,
        active: page.component === 'Admin/Topics/Index',
        icon: IconTopic,
        permission: 'manage curriculum',
    },
    {
        name: 'Academic Sessions',
        href: sessionsIndex().url,
        active: page.component === 'Admin/Settings/Sessions',
        icon: IconDashboard,
        permission: 'manage school setup',
    },
    {
        name: 'Question Bank',
        href: questionsIndex().url,
        active: page.component === 'QuestionBank/Index',
        icon: IconBank,
        permission: 'manage question bank',
    },
    {
        name: 'AI Question Lab',
        href: aiLabGenerate().url,
        active: page.component === 'QuestionBank/Generate',
        icon: IconAI,
        permission: 'use ai lab',
    },
    {
        name: 'System Roles',
        href: rolesIndex().url,
        active: page.component === 'Admin/RBAC/Roles',
        icon: IconUsers,
        permission: 'manage settings',
    },
    {
        name: 'Permissions',
        href: permissionsIndex().url,
        active: page.component === 'Admin/RBAC/Permissions',
        icon: IconDashboard,
        permission: 'manage settings',
    },
    {
        name: 'Staff Management',
        href: staffIndex().url,
        active: page.component === 'Admin/Users/Staff',
        icon: IconUsers,
        permission: 'manage users',
    },
    {
        name: 'Student Management',
        href: studentIndex().url,
        active: page.component === 'Admin/Users/Students',
        icon: IconUsers,
        permission: 'manage users',
    },
    {
        name: 'Student Promotion',
        href: promotionIndex().url,
        active: page.component === 'Admin/Users/Promotion',
        icon: IconSchool,
        permission: 'manage enrollment',
    },
    {
        name: 'Entrance Examination',
        href: entranceIndex().url,
        active: page.component === 'Admin/Users/Candidates',
        icon: IconExams,
        permission: 'manage users',
    },
    {
        name: 'Exams List',
        href: '#',
        active: false,
        icon: IconExams,
        permission: 'view exams',
    },
];

const filteredNavigation = computed(() => {
    return navigation.filter((item) => {
        if (!item.permission) return true;
        const userPermissions = (page.props.auth.user as any).permissions || [];
        return userPermissions.includes(item.permission);
    });
});
</script>

<template>
    <DashboardLayout title="Admin Control Panel" :navigation="filteredNavigation" :logout-action="logout().url">
        <slot />
    </DashboardLayout>
</template>
