<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    attempt: {
        id: string;
        exam: {
            title: string;
            subject: { name: string };
        };
        score: number;
        submitted_at: string;
    };
    totalQuestions: number;
}>();

const percentage = computed(() => {
    return Math.round((props.attempt.score / props.totalQuestions) * 100);
});

const getMessage = computed(() => {
    if (percentage.value >= 70) return 'Outstanding Performance!';
    if (percentage.value >= 50) return 'Well Done!';
    return 'Attempt Completed.';
});

const getColorClass = computed(() => {
    if (percentage.value >= 70) return 'text-green-600';
    if (percentage.value >= 50) return 'text-primary';
    return 'text-orange-600';
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-inter flex items-center justify-center p-6 text-slate-900">
        <Head title="Exam Result" />

        <div class="w-full max-w-2xl">
            <div class="rounded-[3rem] bg-white p-12 shadow-2xl shadow-slate-200 border border-slate-100 text-center space-y-10">
                
                <div class="space-y-2">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-primary/10 text-primary mb-6">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h1 class="text-4xl font-black tracking-tight text-slate-900">{{ getMessage }}</h1>
                    <p class="text-lg font-medium text-slate-400">You have successfully submitted your exam.</p>
                </div>

                <div class="py-10 border-y border-slate-50 space-y-8">
                    <div>
                        <p class="text-xs font-black tracking-[0.2em] text-slate-400 uppercase mb-4">Final Score</p>
                        <div class="flex items-baseline justify-center gap-2">
                            <span :class="['text-8xl font-black tracking-tighter', getColorClass]">
                                {{ attempt.score }}
                            </span>
                            <span class="text-3xl font-bold text-slate-300">/ {{ totalQuestions }}</span>
                        </div>
                    </div>

                    <div class="flex justify-center gap-12 text-left">
                        <div>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Percentage</p>
                            <p class="text-xl font-black text-slate-800">{{ percentage }}%</p>
                        </div>
                        <div class="w-px bg-slate-100 h-10"></div>
                        <div>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Subject</p>
                            <p class="text-xl font-black text-slate-800">{{ attempt.exam.subject.name }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="p-6 rounded-3xl bg-slate-50 border border-slate-100 text-left">
                        <h4 class="text-xs font-black tracking-widest text-slate-900 uppercase mb-2">Exam Summary</h4>
                        <p class="text-sm font-bold text-slate-500 leading-relaxed">
                            Title: {{ attempt.exam.title }} <br>
                            Submitted: {{ new Date(attempt.submitted_at).toLocaleString() }}
                        </p>
                    </div>

                    <Link 
                        href="/student/dashboard"
                        class="block w-full rounded-2xl bg-slate-900 py-5 text-sm font-black tracking-widest text-white uppercase shadow-xl transition-all hover:scale-[1.02] active:scale-95"
                    >
                        Back to Dashboard
                    </Link>
                </div>

            </div>
        </div>
    </div>
</template>
