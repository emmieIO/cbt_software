<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps<{
    stats: {
        totalStudents: number;
        totalStaff: number;
        totalCandidates: number;
        totalQuestions: number;
        totalExams: number;
        activeExams: number;
        totalBranches: number;
        totalClasses: number;
        totalSubjects: number;
        systemStatus: string;
        subjectBreakdown: Array<{ name: string; count: number }>;
    };
    recentExams: Array<{
        id: string;
        title: string;
        status: string;
        type: string;
        subject: string;
        target: string;
        attempts_count: number;
        date: string;
    }>;
    recentUsers: Array<{
        id: string;
        name: string;
        email: string;
        role: string;
        joined_at: string;
    }>;
}>();

const kpis = computed(() => [
    {
        label: 'Students',
        value: props.stats.totalStudents,
        tone: 'text-slate-900',
        meta: 'Total enrolled',
    },
    {
        label: 'Staff',
        value: props.stats.totalStaff,
        tone: 'text-slate-900',
        meta: 'Active examiners',
    },
    {
        label: 'Question Bank',
        value: props.stats.totalQuestions,
        tone: 'text-slate-900',
        meta: 'Published items',
    },
    {
        label: 'Live Exams',
        value: props.stats.activeExams,
        tone: 'text-emerald-700',
        meta: 'Currently running',
    },
]);

const getStatusClass = (status: string) => {
    switch (status.toLowerCase()) {
        case 'live':
        case 'active':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'draft':
            return 'bg-slate-100 text-slate-700 border-slate-200';
        case 'closed':
            return 'bg-rose-100 text-rose-700 border-rose-200';
        default:
            return 'bg-blue-100 text-blue-700 border-blue-200';
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="System Dashboard" />

        <div class="space-y-6 sm:space-y-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-widest text-slate-500 uppercase">Administration</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">System Dashboard</h1>
                    <p class="mt-1 text-sm text-slate-600">Operational overview for branches, assessments, and users.</p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        <span class="size-2 rounded-full bg-emerald-500"></span>
                        {{ stats.systemStatus }}
                    </span>
                    <Link
                        href="/admin/school-setup/sessions"
                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Academic Calendar
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="kpi in kpis" :key="kpi.label" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ kpi.label }}</p>
                    <p class="mt-2 text-3xl font-bold" :class="kpi.tone">{{ kpi.value }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ kpi.meta }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <h2 class="text-sm font-bold text-slate-900">Recent Assessments</h2>
                            <Link href="/staff/exams" class="text-xs font-semibold text-primary hover:underline">View all</Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">Assessment</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">Target</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">Status</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold tracking-wider text-slate-500 uppercase">Attempts</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="exam in recentExams" :key="exam.id" class="hover:bg-slate-50/80">
                                        <td class="px-5 py-4">
                                            <p class="text-sm font-semibold text-slate-900">{{ exam.title }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">{{ exam.subject }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-xs text-slate-600">{{ exam.target || 'Global' }}</td>
                                        <td class="px-5 py-4">
                                            <span :class="getStatusClass(exam.status)" class="inline-flex rounded-md border px-2 py-1 text-[10px] font-bold uppercase">
                                                {{ exam.status }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-right text-sm font-semibold text-slate-900">{{ exam.attempts_count }}</td>
                                    </tr>
                                    <tr v-if="recentExams.length === 0">
                                        <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">No recent assessments available.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-sm font-bold text-slate-900">Academic Registry</h3>
                            <p class="mt-1 text-xs text-slate-500">Core curriculum assets</p>
                            <div class="mt-4 space-y-2">
                                <Link href="/admin/curriculum/subjects" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <span>Subjects</span>
                                    <span class="text-xs font-semibold text-slate-500">{{ stats.totalSubjects }}</span>
                                </Link>
                                <Link href="/admin/curriculum/topics" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <span>Topics</span>
                                    <span class="text-xs font-semibold text-slate-500">Review</span>
                                </Link>
                                <Link href="/admin/school-setup/classes" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <span>Classes</span>
                                    <span class="text-xs font-semibold text-slate-500">{{ stats.totalClasses }}</span>
                                </Link>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-sm font-bold text-slate-900">Identity & Access</h3>
                            <p class="mt-1 text-xs text-slate-500">Manage users and permissions</p>
                            <div class="mt-4 space-y-2">
                                <Link href="/admin/users/students" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <span>Students</span>
                                    <span class="text-xs font-semibold text-slate-500">Directory</span>
                                </Link>
                                <Link href="/admin/users/staff" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <span>Staff</span>
                                    <span class="text-xs font-semibold text-slate-500">Directory</span>
                                </Link>
                                <Link href="/admin/rbac/permissions" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <span>Permissions</span>
                                    <span class="text-xs font-semibold text-slate-500">RBAC</span>
                                </Link>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h2 class="text-sm font-bold text-slate-900">Recent User Activity</h2>
                        </div>
                        <div class="space-y-4 p-5">
                            <div v-for="user in recentUsers" :key="user.id" class="flex items-center gap-3">
                                <div class="flex size-9 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-xs font-bold text-slate-600">
                                    {{ user.name.substring(0, 2) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ user.name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ user.role }} • {{ user.joined_at }}</p>
                                </div>
                            </div>
                            <p v-if="recentUsers.length === 0" class="text-sm text-slate-500">No recent user activity.</p>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-900">System Snapshot</h3>
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600">Branches</span>
                                <span class="font-semibold text-slate-900">{{ stats.totalBranches }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600">Total Exams</span>
                                <span class="font-semibold text-slate-900">{{ stats.totalExams }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600">Candidates</span>
                                <span class="font-semibold text-slate-900">{{ stats.totalCandidates }}</span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
