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
            return 'bg-green-100 text-green-700 border-green-200';
        case 'draft':
            return 'bg-gray-100 text-gray-700 border-gray-200';
        case 'closed':
            return 'bg-red-100 text-red-700 border-red-200';
        default:
            return 'bg-blue-100 text-blue-700 border-blue-200';
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="System Intelligence" />

        <div class="space-y-10">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Intelligence Center</h1>
                    <p class="text-sm font-semibold text-slate-400 uppercase tracking-widest mt-2 flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                        System Health: {{ stats.systemStatus }} • Network-wide Observability
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button class="px-5 py-3 bg-white border border-slate-200 rounded-xl text-xs font-black uppercase tracking-widest text-slate-600 hover:bg-slate-50 transition-all shadow-sm">
                        Export Global Report
                    </button>
                    <Link href="/admin/school-setup/sessions" class="px-5 py-3 bg-primary text-white rounded-xl text-xs font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-md shadow-primary/20">
                        Academic Calendar
                    </Link>
                </div>
            </div>

            <!-- Global High-Level Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Students Stat -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 size-24 bg-primary/5 rounded-full transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <div class="size-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-4">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Enrolled</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-1">{{ stats.totalStudents }}</h3>
                        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold text-green-600 bg-green-50 w-fit px-2 py-1 rounded-lg">
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7" /></svg>
                            Active Registry
                        </div>
                    </div>
                </div>

                <!-- Staff Stat -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 size-24 bg-blue-500/5 rounded-full transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <div class="size-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 mb-4">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M17 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Verified Personnel</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-1">{{ stats.totalStaff }}</h3>
                        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold text-blue-600 bg-blue-50 w-fit px-2 py-1 rounded-lg">
                            Authorized Access
                        </div>
                    </div>
                </div>

                <!-- Questions Stat -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 size-24 bg-amber-500/5 rounded-full transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <div class="size-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 mb-4">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Universal Bank</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-1">{{ stats.totalQuestions }}</h3>
                        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold text-amber-600 bg-amber-50 w-fit px-2 py-1 rounded-lg">
                            AI & Manual Assets
                        </div>
                    </div>
                </div>

                <!-- Live Exams Stat -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 size-24 bg-green-500/5 rounded-full transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <div class="size-12 bg-green-50 rounded-2xl flex items-center justify-center text-green-600 mb-4">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Live Assessments</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-1">{{ stats.activeExams }}</h3>
                        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold text-green-600 bg-green-50 w-fit px-2 py-1 rounded-lg">
                            Concurrent Streams
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Body -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Activity -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Recent Exams Table -->
                    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-50 flex items-center justify-between">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Global Examination Log</h3>
                            <Link href="/staff/exams" class="text-[10px] font-black text-primary uppercase hover:underline">View All</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Exam Blueprint</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Target Context</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Volume</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="exam in recentExams" :key="exam.id" class="hover:bg-slate-50/50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-black text-slate-800 group-hover:text-primary transition-colors uppercase">{{ exam.title }}</span>
                                                <span class="text-[10px] font-bold text-slate-400 mt-0.5">{{ exam.subject }} • {{ exam.date }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-[10px] font-black text-slate-500 uppercase">{{ exam.target || 'Global' }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span :class="getStatusClass(exam.status)" class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase border tracking-widest">
                                                {{ exam.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-xs font-black text-slate-800">{{ exam.attempts_count }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="recentExams.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">No Recent Assessment Activity</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Curriculum & Infrastructure Quick Control -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Curriculum Registry</h3>
                                <div class="size-8 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <Link href="/admin/curriculum/subjects" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl hover:bg-slate-100 transition-colors group">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 bg-white rounded-lg flex items-center justify-center text-slate-400 shadow-sm font-black text-[10px]">SUB</div>
                                        <span class="text-xs font-black text-slate-700 uppercase">Subjects</span>
                                    </div>
                                    <span class="text-xs font-black text-slate-400 group-hover:text-primary transition-colors">{{ stats.totalSubjects }} Active</span>
                                </Link>
                                <Link href="/admin/curriculum/classes" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl hover:bg-slate-100 transition-colors group">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 bg-white rounded-lg flex items-center justify-center text-slate-400 shadow-sm font-black text-[10px]">CLS</div>
                                        <span class="text-xs font-black text-slate-700 uppercase">Classes</span>
                                    </div>
                                    <span class="text-xs font-black text-slate-400 group-hover:text-primary transition-colors">{{ stats.totalClasses }} Active</span>
                                </Link>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Identity Control</h3>
                                <div class="size-8 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A10.003 10.003 0 0012 20c4.083 0 7.633-2.43 9.177-5.903L12 11l-9.177 3.097C4.367 17.57 7.917 20 12 20c.484 0 .96-.034 1.426-.101l.154-.03m-3.44-2.04L12 11m0 0l9.177-3.097a10.003 10.003 0 00-18.354 0L12 11z" /></svg>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <Link href="/admin/users/students" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl hover:bg-slate-100 transition-colors group">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 bg-white rounded-lg flex items-center justify-center text-slate-400 shadow-sm font-black text-[10px]">STD</div>
                                        <span class="text-xs font-black text-slate-700 uppercase">Students</span>
                                    </div>
                                    <span class="text-xs font-black text-slate-400 group-hover:text-primary transition-colors">Manage</span>
                                </Link>
                                <Link href="/admin/users/staff" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl hover:bg-slate-100 transition-colors group">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 bg-white rounded-lg flex items-center justify-center text-slate-400 shadow-sm font-black text-[10px]">FAC</div>
                                        <span class="text-xs font-black text-slate-700 uppercase">Faculty</span>
                                    </div>
                                    <span class="text-xs font-black text-slate-400 group-hover:text-primary transition-colors">Manage</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Recent Users & Shortcuts -->
                <div class="space-y-8">
                    <!-- Recent User Signups -->
                    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-50 flex items-center justify-between">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Recent Access</h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div v-for="user in recentUsers" :key="user.id" class="flex items-center gap-4 group">
                                <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-[10px] font-black text-slate-400 group-hover:bg-primary/5 group-hover:text-primary transition-all border border-slate-100">
                                    {{ user.name.substring(0, 2).toUpperCase() }}
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-black text-slate-800 uppercase truncate">{{ user.name }}</span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ user.role }} • {{ user.joined_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Utility Shortcuts -->
                    <div class="bg-primary p-8 rounded-[40px] shadow-2xl shadow-primary/20 relative overflow-hidden group">
                        <div class="absolute -right-10 -bottom-10 size-48 bg-white/5 rounded-full blur-3xl transition-transform group-hover:scale-110"></div>
                        <div class="relative">
                            <h3 class="text-lg font-black text-white uppercase tracking-tight">Rapid Deployment</h3>
                            <p class="text-[10px] font-bold text-white/60 uppercase tracking-[0.2em] mt-2">Initialize Core Assets</p>
                            
                            <div class="mt-8 space-y-3">
                                <Link href="/staff/questions/create" class="flex items-center justify-between p-4 bg-white/10 rounded-2xl hover:bg-white/20 transition-all group">
                                    <span class="text-[10px] font-black text-white uppercase tracking-widest">Manual Question</span>
                                    <svg class="size-4 text-white/40 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                                </Link>
                                <Link href="/staff/questions" class="flex items-center justify-between p-4 bg-white/10 rounded-2xl hover:bg-white/20 transition-all group">
                                    <span class="text-[10px] font-black text-white uppercase tracking-widest">AI Laboratory</span>
                                    <svg class="size-4 text-white/40 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                </Link>
                                <Link href="/staff/exams/create" class="flex items-center justify-between p-4 bg-white/10 rounded-2xl hover:bg-white/20 transition-all group">
                                    <span class="text-[10px] font-black text-white uppercase tracking-widest">New Assessment</span>
                                    <svg class="size-4 text-white/40 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.group:hover .group-hover\:text-primary {
    color: var(--color-primary);
}
</style>
