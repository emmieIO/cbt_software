<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineProps<{
    stats: {
        totalStudents: number;
        totalStaff: number;
        totalCandidates: number;
        totalQuestions: number;
        activeExams: number;
        systemStatus: string;
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
</script>

<template>
    <AdminLayout>
        <Head title="Admin Dashboard" />

        <div class="space-y-8 md:space-y-10">
            <!-- Page Header -->
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">Admin Hub</h1>
                    <p class="mt-1 text-sm font-bold tracking-widest text-slate-400 uppercase">Managing Chrisland CBT Infrastructure</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-3 py-1.5 text-[10px] font-black tracking-widest text-green-600 uppercase">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
                        System {{ stats.systemStatus }}
                    </span>
                </div>
            </div>

            <!-- Hero Stats Section -->
            <div class="grid grid-cols-1 gap-4 md:gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <div class="rounded-2xl bg-slate-900 p-6 md:p-8 text-white shadow-2xl transition-all hover:-translate-y-1">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Students</p>
                    <p class="mt-4 text-3xl md:text-4xl font-black tracking-tighter text-white">
                        {{ stats.totalStudents.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 md:p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Staff</p>
                    <p class="mt-4 text-3xl md:text-4xl font-black tracking-tighter text-slate-800">
                        {{ stats.totalStaff.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 md:p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Candidates</p>
                    <p class="mt-4 text-3xl md:text-4xl font-black tracking-tighter text-primary">
                        {{ stats.totalCandidates.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 md:p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Questions Bank</p>
                    <p class="mt-4 text-3xl md:text-4xl font-black tracking-tighter text-slate-800">
                        {{ stats.totalQuestions.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 md:p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Active Exams</p>
                    <p class="mt-4 text-3xl md:text-4xl font-black tracking-tighter text-green-600">
                        {{ stats.activeExams }}
                    </p>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Recent Exams -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="flex items-center justify-between ml-2">
                        <h3 class="text-sm font-black tracking-[0.2em] text-slate-400 uppercase">
                            Recent Examinations
                        </h3>
                        <Link href="/staff/exams" class="text-[10px] font-black tracking-widest text-primary uppercase hover:underline">View All &rarr;</Link>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-xl">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-left">
                                <thead>
                                    <tr class="bg-[#FBFBFC]">
                                        <th class="px-6 py-5 text-[9px] font-black tracking-widest text-slate-400 uppercase whitespace-nowrap">Examination</th>
                                        <th class="px-6 py-5 text-[9px] font-black tracking-widest text-slate-400 uppercase whitespace-nowrap">Target</th>
                                        <th class="px-6 py-5 text-[9px] font-black tracking-widest text-slate-400 uppercase whitespace-nowrap">Attempts</th>
                                        <th class="px-6 py-5 text-right text-[9px] font-black tracking-widest text-slate-400 uppercase whitespace-nowrap">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="exam in recentExams" :key="exam.id" class="group transition-all hover:bg-[#F8F9FB]">
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="min-w-0">
                                                <span class="block truncate text-sm font-black text-slate-800">{{ exam.title }}</span>
                                                <span class="text-[10px] font-bold text-slate-400 uppercase">{{ exam.subject }} • {{ exam.date }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-[9px] font-black tracking-widest text-slate-600 uppercase">
                                                {{ exam.target || 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <span class="text-sm font-black text-slate-800">{{ exam.attempts_count }}</span>
                                            <span class="text-[9px] ml-1 font-bold text-slate-400 uppercase">Subs</span>
                                        </td>
                                        <td class="px-6 py-5 text-right whitespace-nowrap">
                                            <span 
                                                class="inline-flex items-center rounded-lg border px-2.5 py-1 text-[9px] font-black uppercase tracking-widest"
                                                :class="{
                                                    'border-green-200 bg-green-50 text-green-600': exam.status === 'live',
                                                    'border-blue-200 bg-blue-50 text-blue-600': exam.status === 'closed',
                                                    'border-slate-200 bg-slate-50 text-slate-600': exam.status === 'draft',
                                                }"
                                            >
                                                {{ exam.status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="recentExams.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-sm font-bold text-slate-400 uppercase tracking-widest">
                                            No recent exams found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between ml-2">
                        <h3 class="text-sm font-black tracking-[0.2em] text-slate-400 uppercase">
                            Recent Registrations
                        </h3>
                        <Link href="/admin/users/students" class="text-[10px] font-black tracking-widest text-primary uppercase hover:underline">Manage &rarr;</Link>
                    </div>

                    <div class="rounded-2xl border border-slate-100 bg-white shadow-xl overflow-hidden">
                        <div class="divide-y divide-slate-50">
                            <div v-for="user in recentUsers" :key="user.id" class="flex items-center gap-4 p-5 transition-all hover:bg-[#F8F9FB]">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-xs font-black text-primary uppercase">
                                    {{ user.name.substring(0, 2) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-black text-slate-800">{{ user.name }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[9px] font-bold tracking-widest text-slate-400 uppercase">{{ user.role }}</span>
                                        <span class="h-1 w-1 rounded-full bg-slate-200"></span>
                                        <span class="text-[9px] font-bold text-slate-400">{{ user.joined_at }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="recentUsers.length === 0" class="p-8 text-center text-xs font-bold text-slate-400 uppercase tracking-widest">
                                No recent users.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions Grid -->
            <div class="space-y-6">
                <div class="ml-2">
                    <h3 class="text-sm font-black tracking-[0.2em] text-slate-400 uppercase">
                        Quick Actions
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Link href="/admin/users/entrance" class="group flex items-center gap-4 rounded-2xl border border-slate-100 bg-white p-6 transition-all hover:border-primary/20 hover:shadow-xl">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary transition-transform group-hover:scale-110">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-800">Add Candidates</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">Import or Create</p>
                        </div>
                    </Link>
                    <Link href="/admin/curriculum/subjects" class="group flex items-center gap-4 rounded-2xl border border-slate-100 bg-white p-6 transition-all hover:border-primary/20 hover:shadow-xl">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary transition-transform group-hover:scale-110">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-800">Manage Subjects</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">Academic Config</p>
                        </div>
                    </Link>
                    <Link href="/admin/users/teaching-loads" class="group flex items-center gap-4 rounded-2xl border border-slate-100 bg-white p-6 transition-all hover:border-primary/20 hover:shadow-xl">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary transition-transform group-hover:scale-110">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-800">Assign Workloads</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">Teacher Allocation</p>
                        </div>
                    </Link>
                    <Link href="/staff/questions" class="group flex items-center gap-4 rounded-2xl border border-slate-100 bg-white p-6 transition-all hover:border-primary/20 hover:shadow-xl">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary transition-transform group-hover:scale-110">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-800">Question Bank</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">Repository Access</p>
                        </div>
                    </Link>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
