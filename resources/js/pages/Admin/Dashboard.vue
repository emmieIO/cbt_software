<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineProps<{
    stats: {
        totalStudents: number;
        totalStaff: number;
        activeExams: number;
        systemStatus: string;
    };
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
            <div class="grid grid-cols-1 gap-4 md:gap-6 md:grid-cols-2 lg:grid-cols-4">
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
        </div>
    </AdminLayout>
</template>
