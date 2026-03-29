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
    };
}

defineProps<{
    attempts: Attempt[];
}>();
</script>

<template>
    <StudentLayout>
        <Head title="My Examination Results" />

        <div class="space-y-10">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Academic History</h1>
                    <p class="mt-1 text-sm font-semibold tracking-widest text-slate-400 uppercase">
                        Performance records for all completed assessments
                    </p>
                </div>
            </div>

            <!-- Results Grid -->
            <div
                v-if="attempts.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white py-24 text-center shadow-sm"
            >
                <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-gray-50 text-slate-300">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-slate-400">No results found.</h3>
                <p class="mt-2 text-sm font-semibold tracking-widest text-slate-400 uppercase">You haven't completed any assessments yet.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-6">
                <div
                    v-for="attempt in attempts"
                    :key="attempt.id"
                    class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-8 shadow-sm transition-all hover:shadow-md"
                >
                    <div class="relative z-10 flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div
                                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-2xl font-semibold text-white"
                            >
                                {{ attempt.score }}
                            </div>
                            <div>
                                <h4 class="text-xl leading-tight font-semibold text-slate-800 transition-colors group-hover:text-primary">
                                    {{ attempt.exam.title }}
                                </h4>
                                <p class="mt-1 text-xs font-semibold tracking-widest text-slate-400 uppercase">
                                    {{ attempt.exam.subject?.name || 'Multi-Subject' }} • Submitted on
                                    {{ new Date(attempt.submitted_at).toLocaleDateString() }}
                                </p>
                            </div>
                        </div>

                        <Link
                            :href="`/student/exams/${attempt.id}/result`"
                            class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold tracking-widest text-gray-800 uppercase shadow-sm hover:bg-gray-50 disabled:opacity-50"
                        >
                            View Details
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
