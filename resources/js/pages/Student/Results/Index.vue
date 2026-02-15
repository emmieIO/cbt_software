<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Attempt {
    id: string;
    score: number;
    submitted_at: string;
    exam: {
        title: string;
        subject: { name: string };
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
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Academic History</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400 uppercase tracking-widest italic">Performance records for all completed assessments</p>
                </div>
            </div>

            <!-- Results Grid -->
            <div v-if="attempts.length === 0" class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white py-24 text-center">
                <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-400">No results found.</h3>
                <p class="mt-2 text-sm font-bold text-slate-400 uppercase tracking-widest">You haven't completed any assessments yet.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-6">
                <div 
                    v-for="attempt in attempts" 
                    :key="attempt.id"
                    class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:border-primary/20 hover:shadow-2xl"
                >
                    <div class="relative z-10 flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-white font-black text-2xl">
                                {{ attempt.score }}
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-slate-800 leading-tight group-hover:text-primary transition-colors">{{ attempt.exam.title }}</h4>
                                <p class="mt-1 text-xs font-bold text-slate-400 uppercase tracking-widest">{{ attempt.exam.subject.name }} • Submitted on {{ new Date(attempt.submitted_at).toLocaleDateString() }}</p>
                            </div>
                        </div>
                        
                        <Link 
                            :href="`/student/exams/${attempt.id}/result`"
                            class="rounded-xl border-2 border-slate-100 px-6 py-3 text-[10px] font-black tracking-widest text-slate-600 uppercase transition-all hover:bg-slate-900 hover:text-white hover:border-slate-900 active:scale-95"
                        >
                            View Details
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
