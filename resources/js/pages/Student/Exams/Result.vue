<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Attempt {
    id: string;
    score: number;
    submitted_at: string;
    metadata: {
        termination_reason?: string;
        violation_count?: number;
    } | null;
    exam: {
        title: string;
        subject: { name: string };
    };
}

const props = defineProps<{
    attempt: Attempt;
    totalQuestions: number;
}>();

const hasViolation = computed(() => !!props.attempt.metadata?.termination_reason);

const percentage = computed(() => {
    if (props.totalQuestions === 0) return 0;
    return Math.round((props.attempt.score / props.totalQuestions) * 100);
});

const isPassed = computed(() => percentage.value >= 50);

const statusColor = computed(() => isPassed.value ? 'text-green-600' : 'text-orange-600');
const statusBg = computed(() => isPassed.value ? 'bg-green-50' : 'bg-orange-50');
const statusBorder = computed(() => isPassed.value ? 'border-green-100' : 'border-orange-100');
</script>

<template>
    <StudentLayout>
        <Head title="Exam Result" />

        <div class="mx-auto max-w-3xl py-12">
            <!-- Violation Warning -->
            <div v-if="hasViolation" class="mb-8 overflow-hidden rounded-2xl border-2 border-red-200 bg-red-50 p-6 shadow-lg animate-in slide-in-from-top-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-600 text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black uppercase tracking-widest text-red-600">Integrity Termination</h4>
                        <p class="mt-1 text-xs font-bold text-red-500 leading-relaxed">
                            This examination was automatically submitted due to a security violation: <span class="font-black underline">{{ attempt.metadata?.termination_reason }}</span>.
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-2xl">
                <!-- Result Header -->
                <div class="bg-slate-900 p-12 text-center text-white">
                    <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-xl">
                        <svg v-if="isPassed" class="h-10 w-10 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg v-else class="h-10 w-10 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-black tracking-tight uppercase">Examination Complete</h2>
                    <p class="mt-2 text-slate-400 font-bold uppercase tracking-widest text-xs">Result Summary Generated</p>
                </div>

                <!-- Score Section -->
                <div class="p-12">
                    <div class="grid grid-cols-1 gap-10 md:grid-cols-2">
                        <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-slate-50 bg-slate-50/30 p-10">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Score</span>
                            <div class="mt-4 flex items-baseline">
                                <span class="text-7xl font-black tracking-tighter text-slate-900">{{ attempt.score }}</span>
                                <span class="ml-2 text-2xl font-bold text-slate-300">/ {{ totalQuestions }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-center justify-center rounded-2xl border-2 p-10 transition-all" :class="[statusBg, statusBorder]">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em]" :class="statusColor">Percentage</span>
                            <div class="mt-4 flex items-baseline">
                                <span class="text-7xl font-black tracking-tighter" :class="statusColor">{{ percentage }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Details List -->
                    <div class="mt-12 space-y-4">
                        <div class="flex items-center justify-between rounded-xl border border-slate-50 p-5">
                            <span class="text-xs font-black uppercase tracking-widest text-slate-400">Subject</span>
                            <span class="text-sm font-black text-slate-800">{{ attempt.exam.subject.name }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-slate-50 p-5">
                            <span class="text-xs font-black uppercase tracking-widest text-slate-400">Exam Title</span>
                            <span class="text-sm font-black text-slate-800">{{ attempt.exam.title }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-slate-50 p-5">
                            <span class="text-xs font-black uppercase tracking-widest text-slate-400">Submission Time</span>
                            <span class="text-sm font-black text-slate-800">{{ new Date(attempt.submitted_at).toLocaleString() }}</span>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="mt-12 flex flex-col items-center">
                        <Link 
                            href="/student/dashboard"
                            class="flex w-full items-center justify-center rounded-xl bg-primary py-5 text-sm font-black uppercase tracking-[0.2em] text-white shadow-xl shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98]"
                        >
                            Return to Dashboard
                        </Link>
                        <p class="mt-6 text-center text-xs font-bold text-slate-400 leading-relaxed">
                            Your performance has been recorded. For further inquiries regarding this assessment, please contact your subject lead or the academic administrator.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
