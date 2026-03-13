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
        subjectBreakdown: Array<{ name: string, count: number }>;
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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">System Dashboard</h1>
                    <p class="text-sm text-gray-500 mt-1 flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-teal-500"></span>
                        Status: {{ stats.systemStatus }} • Network Observability
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none">
                        Global Report
                    </button>
                    <Link href="/admin/school-setup/sessions" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                        Academic Calendar
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Enrolled Card -->
                <div class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="p-4 md:p-5">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Enrolled</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-800">{{ stats.totalStudents }}</h3>
                        </div>
                        <div class="mt-3 flex items-center text-xs font-medium text-teal-600">
                            Active Students
                        </div>
                    </div>
                </div>

                <!-- Faculty Card -->
                <div class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="p-4 md:p-5">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Verified Faculty</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-800">{{ stats.totalStaff }}</h3>
                        </div>
                        <div class="mt-3 text-xs font-medium text-blue-600">Authorized Personnel</div>
                    </div>
                </div>

                <!-- Questions Card -->
                <div class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="p-4 md:p-5">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Universal Bank</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-800">{{ stats.totalQuestions }}</h3>
                        </div>
                        <div class="mt-3 text-xs font-medium text-orange-600">Assessment Assets</div>
                    </div>
                </div>

                <!-- Live Exams Card -->
                <div class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="p-4 md:p-5">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Active Exams</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-800">{{ stats.activeExams }}</h3>
                        </div>
                        <div class="mt-3 text-xs font-medium text-teal-600">Concurrent Streams</div>
                    </div>
                </div>
            </div>

            <!-- Body Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-10">
                <!-- Left: Recent Activity -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
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
                                    <tr v-for="exam in recentExams" :key="exam.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-semibold text-gray-800">{{ exam.title }}</span>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ exam.subject }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-xs text-gray-600">{{ exam.target || 'Global' }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span :class="getStatusClass(exam.status)" class="inline-flex items-center py-1 px-2 rounded-md text-[10px] font-bold uppercase">
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                            <h3 class="font-semibold text-gray-800 mb-4">Curriculum Registry</h3>
                            <div class="space-y-3">
                                <Link href="/admin/curriculum/subjects" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group">
                                    <span class="text-sm font-medium text-gray-700">Manage Subjects</span>
                                    <span class="text-xs text-gray-400 group-hover:text-primary">{{ stats.totalSubjects }} Active</span>
                                </Link>
                                <Link href="/admin/curriculum/classes" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group">
                                    <span class="text-sm font-medium text-gray-700">Global Classes</span>
                                    <span class="text-xs text-gray-400 group-hover:text-primary">{{ stats.totalClasses }} Active</span>
                                </Link>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                            <h3 class="font-semibold text-gray-800 mb-4">Identity Control</h3>
                            <div class="space-y-3">
                                <Link href="/admin/users/students" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group">
                                    <span class="text-sm font-medium text-gray-700">Students Registry</span>
                                    <svg class="size-4 text-gray-400 group-hover:text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </Link>
                                <Link href="/admin/users/staff" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group">
                                    <span class="text-sm font-medium text-gray-700">Faculty Management</span>
                                    <svg class="size-4 text-gray-400 group-hover:text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Recent Access & Lab -->
                <div class="space-y-6">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="font-semibold text-gray-800">Recent Activity</h2>
                        </div>
                        <div class="p-6 space-y-5">
                            <div v-for="user in recentUsers" :key="user.id" class="flex items-center gap-3">
                                <div class="size-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500 uppercase border border-gray-200">
                                    {{ user.name.substring(0, 2) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ user.name }}</p>
                                    <p class="text-[10px] text-gray-500 uppercase tracking-wider">{{ user.role }} • {{ user.joined_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Seeding Box -->
                    <div class="bg-primary p-6 rounded-xl shadow-sm relative overflow-hidden group">
                        <div class="relative z-10">
                            <h3 class="text-lg font-bold text-white uppercase tracking-tight">Rapid Deployment</h3>
                            <p class="text-xs text-white/70 mt-1">Initialize bank seeding via AI Synapse.</p>
                            
                            <Link href="/staff/questions" class="mt-6 flex items-center justify-center gap-x-2 py-3 px-4 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold text-white transition-all">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                Open AI Laboratory
                            </Link>
                        </div>
                        <div class="absolute -right-10 -bottom-10 size-32 bg-white/5 rounded-full blur-2xl group-hover:scale-110 transition-transform"></div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
