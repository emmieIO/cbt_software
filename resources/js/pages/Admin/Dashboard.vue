<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

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

        <div class="space-y-8 md:space-y-10">
            <!-- Page Header -->
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Admin Hub</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400">Managing Chrisland CBT Infrastructure</p>
                </div>
            </div>

            <!-- Hero Stats Section -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-slate-900 p-6 md:p-8 text-white shadow-2xl transition-all hover:-translate-y-1">
                    <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">Total Students</p>
                    <p class="mt-4 text-3xl md:text-4xl font-bold tracking-tighter text-white">
                        {{ stats.totalStudents.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-white p-6 md:p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">
                    <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">Staff Strength</p>
                    <p class="mt-4 text-3xl md:text-4xl font-bold tracking-tighter text-slate-800">
                        {{ stats.totalStaff.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-white p-6 md:p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">
                    <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">Live Exams</p>
                    <p class="mt-4 text-3xl md:text-4xl font-bold tracking-tighter text-green-600">
                        {{ stats.activeExams }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-white p-6 md:p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">
                    <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">System Status</p>
                    <div class="mt-4 flex items-center gap-3">
                        <div class="rounded-lg-full h-3 w-3 animate-pulse bg-blue-500"></div>
                        <p class="text-2xl md:text-3xl font-bold tracking-tighter text-blue-600 uppercase">{{ stats.systemStatus }}</p>
                    </div>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="overflow-hidden rounded-xl border border-slate-100 bg-white p-6 md:p-10 shadow-sm">
                <div class="mb-6 md:mb-8 flex items-center justify-between">
                    <h3 class="text-lg md:text-xl font-bold tracking-tight text-slate-900">Recent System Logs</h3>
                    <span class="rounded-lg-full bg-slate-50 px-3 py-1 md:px-4 md:py-1.5 text-[10px] font-bold tracking-widest text-slate-400 uppercase"
                        >Live Monitor</span
                    >
                </div>
                <div class="divide-y divide-slate-50">
                    <div
                        v-for="activity in recentActivity"
                        :key="activity.id"
                        class="group flex cursor-pointer items-center justify-between rounded-xl px-2 py-4 md:px-4 md:py-5 transition-colors hover:bg-slate-50/50"
                    >
                        <div class="flex items-center gap-3 md:gap-4">
                            <div class="rounded-lg-full h-2 w-2 shrink-0 bg-slate-300 transition-colors group-hover:bg-primary"></div>
                            <p class="text-xs md:text-sm font-bold text-slate-600">
                                <span class="font-bold text-slate-900">{{ activity.user }}</span>
                                {{ activity.action }}
                            </p>
                        </div>
                        <span class="ml-2 shrink-0 text-[10px] font-bold tracking-widest text-slate-300 uppercase">{{ activity.time }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
