<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/layouts/StudentLayout.vue';

interface Attempt {
    id: string;
    score: number;
    submitted_at: string;
    exam: {
        title: string;
        subject: { name: string } | null;
        questions_count: number;
    };
}

defineProps<{
    attempts: Attempt[];
}>();

const calculatePercentage = (score: number, questionsCount: number) => {
    if (!questionsCount) return 0;
    return Math.round((score / questionsCount) * 100);
};
</script>

<template>
    <StudentLayout>

        <Head title="My Examination Results" />

        <div class="mx-auto max-w-6xl space-y-8 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/student/dashboard" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-800">Academic History</span>
            </nav>

            <!-- Page Header -->
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Academic History</h1>
                    <p class="mt-1 text-sm text-gray-500">Performance records for all completed assessments</p>
                </div>
            </div>

            <!-- Results Table -->
            <div v-if="attempts.length === 0"
                class="flex flex-col items-center justify-center rounded-lg border border-slate-100 bg-white py-24 text-center shadow-sm">
                <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-xl bg-slate-50 text-slate-300">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800">No results found.</h3>
                <p class="mt-2 text-sm text-gray-500">You haven't completed any assessments yet.</p>
            </div>

            <div v-else class="overflow-hidden rounded-lg border border-slate-100 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-50 bg-slate-50/50">
                                <th class="px-6 py-4 text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">
                                    Assessment</th>
                                <th class="px-6 py-4 text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">
                                    Subject</th>
                                <th class="px-6 py-4 text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">
                                    Submitted On</th>
                                <th
                                    class="px-6 py-4 text-center text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">
                                    Final Score</th>
                                <th
                                    class="px-6 py-4 text-right text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="attempt in attempts" :key="attempt.id"
                                class="group transition-colors hover:bg-slate-50/50">
                                <td class="px-6 py-5">
                                    <span
                                        class="block text-sm font-bold text-gray-800 group-hover:text-primary transition-colors">
                                        {{ attempt.exam.title }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <span
                                        class="inline-flex rounded-md bg-slate-100 px-2.5 py-1 text-[10px] font-bold tracking-wider text-slate-600 uppercase">
                                        {{ attempt.exam.subject?.name || 'Multi-Subject' }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="text-xs font-medium text-gray-500">
                                        {{ new Date(attempt.submitted_at).toLocaleDateString(undefined, {
                                            day:
                                                '2-digit', month: 'short', year: 'numeric' }) }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <span class="text-sm font-bold leading-none">
                                        {{ calculatePercentage(attempt.score, attempt.exam.questions_count) }}%
                                    </span>
                                    <span
                                        class="mt-0.5 text-[7px] font-bold tracking-[0.2em] text-white/50 uppercase">Score</span>

                                </td>
                                <td class="px-6 py-5 text-right">
                                    <Link :href="`/student/exams/${attempt.id}/result`"
                                        class="inline-flex items-center gap-x-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold tracking-widest text-slate-800 uppercase shadow-sm transition-all hover:border-primary hover:bg-primary hover:text-white">
                                        Analysis
                                        <svg class="h-3 w-3 transition-transform group-hover:translate-x-0.5"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
