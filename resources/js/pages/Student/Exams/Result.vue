<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import StudentLayout from '@/layouts/StudentLayout.vue';

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

const getGrade = computed(() => {
    const p = percentage.value;
    if (p >= 85) return { label: 'Distinction', color: 'text-primary' };
    if (p >= 70) return { label: 'Credit', color: 'text-primary' };
    if (p >= 50) return { label: 'Pass', color: 'text-blue-600' };
    return { label: 'Fail', color: 'text-red-600' };
});

const handlePrint = () => {
    window.print();
};
</script>

<template>
    <StudentLayout>
        <Head title="Official Result Slip" />

        <div class="mx-auto max-w-4xl py-12 px-4 print:p-0">
            <!-- Integrity Alert (Hidden on Print) -->
            <div
                v-if="hasViolation"
                class="animate-in slide-in-from-top-4 mb-8 flex items-center gap-5 rounded-2xl border-2 border-red-100 bg-red-50 p-6 shadow-xl print:hidden"
            >
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-600 text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-black tracking-widest text-red-600 uppercase">Integrity Termination</h4>
                    <p class="mt-1 text-xs leading-relaxed font-bold text-red-500">
                        This examination was automatically submitted due to a security violation:
                        <span class="font-black underline">{{ attempt.metadata?.termination_reason }}</span>.
                    </p>
                </div>
            </div>

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl print:border-none print:shadow-none">
                <!-- Result Slip Header -->
                <div class="relative flex flex-col items-center border-b border-slate-100 bg-[#F8F9FB] px-12 py-16 text-center">
                    <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-24 w-auto mb-8 grayscale print:grayscale-0" />
                    
                    <h1 class="text-4xl font-black tracking-tighter text-slate-900 uppercase italic">Official Result Slip</h1>
                    <div class="mt-4 flex items-center gap-3">
                        <span class="h-px w-8 bg-primary"></span>
                        <span class="text-[10px] font-black tracking-[0.3em] text-primary uppercase">Chrisland Schools CBT Infrastructure</span>
                        <span class="h-px w-8 bg-primary"></span>
                    </div>

                    <!-- Watermark -->
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.03]">
                        <img src="/assets/img/chrisland-school-logo.png" class="h-96 w-auto" />
                    </div>
                </div>

                <div class="p-12">
                    <!-- Score Matrix -->
                    <div class="grid grid-cols-1 gap-12 md:grid-cols-3">
                        <div class="flex flex-col items-center border-r border-slate-100 last:border-none md:pr-12 md:last:pr-0">
                            <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Raw Score</span>
                            <div class="mt-4 flex items-baseline gap-1">
                                <span class="text-6xl font-black tracking-tighter text-slate-900">{{ attempt.score }}</span>
                                <span class="text-xl font-bold text-slate-300">/ {{ totalQuestions }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-center border-r border-slate-100 last:border-none md:px-12 md:last:pr-0">
                            <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Performance</span>
                            <div class="mt-4">
                                <span class="text-6xl font-black tracking-tighter text-primary">{{ percentage }}%</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-center md:pl-12">
                            <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Assessment Grade</span>
                            <div class="mt-4">
                                <span class="text-4xl font-black italic tracking-tight" :class="getGrade.color">{{ getGrade.label }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Data Grid -->
                    <div class="mt-16 grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-6">
                            <div>
                                <label class="text-[9px] font-black tracking-widest text-slate-400 uppercase">Academic Subject</label>
                                <p class="mt-1 text-lg font-black text-slate-800 underline decoration-primary/20 decoration-2 underline-offset-4">{{ attempt.exam.subject.name }}</p>
                            </div>
                            <div>
                                <label class="text-[9px] font-black tracking-widest text-slate-400 uppercase">Examination Title</label>
                                <p class="mt-1 text-sm font-bold text-slate-600 uppercase tracking-tight">{{ attempt.exam.title }}</p>
                            </div>
                        </div>
                        <div class="space-y-6 md:text-right">
                            <div>
                                <label class="text-[9px] font-black tracking-widest text-slate-400 uppercase">Submission Reference</label>
                                <p class="mt-1 font-mono text-xs font-bold text-slate-500">{{ attempt.id.substring(0, 16).toUpperCase() }}</p>
                            </div>
                            <div>
                                <label class="text-[9px] font-black tracking-widest text-slate-400 uppercase">Certified Date</label>
                                <p class="mt-1 text-sm font-bold text-slate-600">{{ new Date(attempt.submitted_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer & Actions -->
                    <div class="mt-16 flex flex-col items-center border-t border-slate-50 pt-12">
                        <div class="flex flex-wrap justify-center gap-4 print:hidden w-full">
                            <button
                                @click="handlePrint"
                                class="flex flex-1 items-center justify-center gap-3 rounded-xl border-2 border-slate-100 py-4 px-8 text-xs font-black tracking-widest text-slate-600 uppercase transition-all hover:bg-slate-50 active:scale-95"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Print Result Slip
                            </button>
                            <Link
                                href="/student/dashboard"
                                class="flex-[2] flex items-center justify-center gap-3 rounded-xl bg-primary py-4 px-8 text-xs font-black tracking-widest text-white uppercase shadow-xl shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98]"
                            >
                                Return to Student Hub
                            </Link>
                        </div>

                        <div class="mt-12 text-center">
                            <p class="text-[10px] font-bold leading-relaxed text-slate-400 uppercase max-w-lg">
                                This document serves as a digital certification of performance for the specified examination. 
                                Alteration of this record is a punishable offense under school policy.
                            </p>
                            <p class="mt-4 text-[9px] font-black tracking-widest text-primary uppercase italic">&copy; Chrisland Schools Management System</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
@media print {
    :deep(nav), :deep(aside), :deep(header) {
        display: none !important;
    }
    :deep(main) {
        margin: 0 !important;
        padding: 0 !important;
    }
    .print\:hidden {
        display: none !important;
    }
}
</style>
