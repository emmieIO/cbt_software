<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    stats: {
        totalStudents: number;
        totalStaff: number;
        activeExams: number;
        systemStatus: string;
    };
    recentActivity: Array<{
        id: number;
        user: string;
        action: string;
        time: string;
    }>;
}>();
</script>

<template>
    <AdminLayout>
        <Head title="Admin Dashboard" />

        <div class="space-y-10">
            <!-- Page Header (From UI Concept) -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Admin Hub</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400">Managing Chrisland CBT Infrastructure</p>
                </div>
                <div class="flex items-center gap-3">
                    <button class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-600 uppercase shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        System Export
                    </button>
                    <Link href="/admin/users/students" class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-black text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        Quick Enroll
                    </Link>
                </div>
            </div>

            <!-- Hero Stats Section -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                <div class="rounded-xl bg-slate-900 p-8 text-white shadow-2xl transition-all hover:-translate-y-1">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Total Students</p>
                    <p class="mt-4 text-4xl font-black tracking-tighter text-primary">
                        {{ stats.totalStudents.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-xl bg-white p-8 shadow-sm border border-slate-100 transition-all hover:-translate-y-1 hover:shadow-xl">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Staff Strength</p>
                    <p class="mt-4 text-4xl font-black tracking-tighter text-slate-800">
                        {{ stats.totalStaff.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-xl bg-white p-8 shadow-sm border border-slate-100 transition-all hover:-translate-y-1 hover:shadow-xl">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Live Exams</p>
                    <p class="mt-4 text-4xl font-black tracking-tighter text-green-600">
                        {{ stats.activeExams }}
                    </p>
                </div>
                <div class="rounded-xl bg-white p-8 shadow-sm border border-slate-100 transition-all hover:-translate-y-1 hover:shadow-xl">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">System Status</p>
                    <div class="mt-4 flex items-center gap-3">
                        <div class="h-3 w-3 rounded-lg-full bg-blue-500 animate-pulse"></div>
                        <p class="text-3xl font-black text-blue-600 tracking-tighter uppercase">{{ stats.systemStatus }}</p>
                    </div>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="rounded-xl border border-slate-100 bg-white p-10 shadow-sm overflow-hidden">
                <div class="mb-8 flex items-center justify-between">
                    <h3 class="text-xl font-black tracking-tight text-slate-900">Recent System Logs</h3>
                    <span class="rounded-lg-full bg-slate-50 px-4 py-1.5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Live Monitor</span>
                </div>
                <div class="divide-y divide-slate-50">
                    <div v-for="activity in recentActivity" :key="activity.id" class="flex items-center justify-between py-5 group cursor-pointer hover:bg-slate-50/50 rounded-xl px-4 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="h-2 w-2 rounded-lg-full bg-slate-300 group-hover:bg-primary transition-colors"></div>
                            <p class="text-sm font-bold text-slate-600">
                                <span class="text-slate-900 font-black">{{ activity.user }}</span> 
                                {{ activity.action }}
                            </p>
                        </div>
                        <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">{{ activity.time }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>