<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineProps<{
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

const getStatusClass = (status: string) => {
    switch (status.toLowerCase()) {
        case 'live':
        case 'active':
            return 'bg-teal-100 text-teal-800';
        case 'draft':
            return 'bg-gray-100 text-gray-800';
        case 'closed':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-blue-100 text-blue-800';
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="System Dashboard" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">System Dashboard</h1>
                    <p class="mt-1 flex items-center gap-2 text-sm text-gray-500">
                        <span class="flex h-2 w-2 rounded-full bg-teal-500"></span>
                        Status: {{ stats.systemStatus }} • Network Observability
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none"
                    >
                        Global Report
                    </button>
                    <Link
                        href="/admin/school-setup/sessions"
                        class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-2.5 text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none"
                    >
                        Academic Calendar
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
                <!-- Enrolled Card -->
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="p-4 md:p-5">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">Total Enrolled</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl font-bold text-gray-800 sm:text-2xl">{{ stats.totalStudents }}</h3>
                        </div>
                        <div class="mt-3 flex items-center text-xs font-medium text-teal-600">Active Students</div>
                    </div>
                </div>

                <!-- Faculty Card -->
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="p-4 md:p-5">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">Verified Faculty</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl font-bold text-gray-800 sm:text-2xl">{{ stats.totalStaff }}</h3>
                        </div>
                        <div class="mt-3 text-xs font-medium text-blue-600">Authorized Personnel</div>
                    </div>
                </div>

                <!-- Questions Card -->
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="p-4 md:p-5">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">Universal Bank</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl font-bold text-gray-800 sm:text-2xl">{{ stats.totalQuestions }}</h3>
                        </div>
                        <div class="mt-3 text-xs font-medium text-orange-600">Assessment Assets</div>
                    </div>
                </div>

                <!-- Live Exams Card -->
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="p-4 md:p-5">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">Active Exams</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl font-bold text-gray-800 sm:text-2xl">{{ stats.activeExams }}</h3>
                        </div>
                        <div class="mt-3 text-xs font-medium text-teal-600">Concurrent Streams</div>
                    </div>
                </div>
            </div>

            <!-- Body Grid -->
            <div class="grid grid-cols-1 gap-6 sm:gap-10 lg:grid-cols-3">
                <!-- Left: Recent Activity -->
                <div class="space-y-6 lg:col-span-2">
                    <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-800">Recent Assessments</h2>
                            <Link href="/staff/exams" class="text-sm font-medium text-primary hover:underline">View All</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Exam Title</th>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Context</th>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Attempts</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="exam in recentExams" :key="exam.id" class="transition-colors hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-semibold text-gray-800">{{ exam.title }}</span>
                                            <p class="mt-0.5 text-xs text-gray-500">{{ exam.subject }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-xs text-gray-600">{{ exam.target || 'Global' }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                :class="getStatusClass(exam.status)"
                                                class="inline-flex items-center rounded-md px-2 py-1 text-[10px] font-bold uppercase"
                                            >
                                                {{ exam.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-sm font-medium text-gray-800">{{ exam.attempts_count }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="recentExams.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">No recent assessments found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Infrastructure Quick Controls -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="mb-4 font-semibold text-gray-800">Curriculum Registry</h3>
                            <div class="space-y-3">
                                <Link
                                    href="/admin/curriculum/subjects"
                                    class="group flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100"
                                >
                                    <span class="text-sm font-medium text-gray-700">Manage Subjects</span>
                                    <span class="text-xs text-gray-400 group-hover:text-primary">{{ stats.totalSubjects }} Active</span>
                                </Link>
                                <Link
                                    href="/admin/curriculum/classes"
                                    class="group flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100"
                                >
                                    <span class="text-sm font-medium text-gray-700">Global Classes</span>
                                    <span class="text-xs text-gray-400 group-hover:text-primary">{{ stats.totalClasses }} Active</span>
                                </Link>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="mb-4 font-semibold text-gray-800">Identity Control</h3>
                            <div class="space-y-3">
                                <Link
                                    href="/admin/users/students"
                                    class="group flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100"
                                >
                                    <span class="text-sm font-medium text-gray-700">Students Registry</span>
                                    <svg class="size-4 text-gray-400 group-hover:text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                                <Link
                                    href="/admin/users/staff"
                                    class="group flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100"
                                >
                                    <span class="text-sm font-medium text-gray-700">Faculty Management</span>
                                    <svg class="size-4 text-gray-400 group-hover:text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Recent Access & Lab -->
                <div class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h2 class="font-semibold text-gray-800">Recent Activity</h2>
                        </div>
                        <div class="space-y-5 p-6">
                            <div v-for="user in recentUsers" :key="user.id" class="flex items-center gap-3">
                                <div
                                    class="flex size-8 items-center justify-center rounded-full border border-gray-200 bg-gray-100 text-xs font-bold text-gray-500 uppercase"
                                >
                                    {{ user.name.substring(0, 2) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-800">{{ user.name }}</p>
                                    <p class="text-[10px] tracking-wider text-gray-500 uppercase">{{ user.role }} • {{ user.joined_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Seeding Box -->
                    <div class="group relative overflow-hidden rounded-xl bg-primary p-6 shadow-sm">
                        <div class="relative z-10">
                            <h3 class="text-lg font-bold tracking-tight text-white uppercase">Rapid Deployment</h3>
                            <p class="mt-1 text-xs text-white/70">Initialize bank seeding via AI Synapse.</p>

                            <Link
                                href="/staff/questions"
                                class="mt-6 flex items-center justify-center gap-x-2 rounded-lg bg-white/10 px-4 py-3 text-sm font-semibold text-white transition-all hover:bg-white/20"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Open AI Laboratory
                            </Link>
                        </div>
                        <div
                            class="absolute -right-10 -bottom-10 size-32 rounded-full bg-white/5 blur-2xl transition-transform group-hover:scale-110"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
