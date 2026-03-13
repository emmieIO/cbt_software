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
        subject: { name: string } | null;
    };
}

const props = defineProps<{
    attempt: Attempt;
    totalQuestions: number;
}>();

const hasViolation = computed(() => !!props.attempt.metadata?.termination_reason);

const percentage = computed(() => {
    const total = Number(props.totalQuestions) || 0;
    if (total === 0) return 0;
    return Math.round((Number(props.attempt.score) / total) * 100);
});

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
                class="animate-in slide-in-from-top-4 mb-8 flex items-center gap-x-4 rounded-xl border border-red-200 bg-red-50 p-4 print:hidden"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600 text-white shadow-sm">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-red-800">Integrity Termination</h4>
                    <p class="mt-1 text-sm text-red-700">
                        This examination was automatically submitted due to a security violation:
                        <span class="font-bold underline">{{ attempt.metadata?.termination_reason }}</span>.
                    </p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm print:border-none print:shadow-none">
                <!-- Result Slip Header -->
                <div class="relative flex flex-col items-center border-b border-gray-100 bg-gray-50 px-12 py-12 text-center">
                    <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-20 w-auto mb-6 grayscale print:grayscale-0" />
                    
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900">Official Result Slip</h1>
                    <div class="mt-4 flex items-center gap-x-3">
                        <span class="h-px w-8 bg-primary/30"></span>
                        <span class="text-xs font-semibold tracking-wider text-primary uppercase">Chrisland Schools CBT Infrastructure</span>
                        <span class="h-px w-8 bg-primary/30"></span>
                    </div>

                    <!-- Watermark -->
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.03]">
                        <img src="/assets/img/chrisland-school-logo.png" class="h-96 w-auto" />
                    </div>
                </div>

                <div class="p-8 sm:p-12">
                    <!-- Score Matrix -->
                    <div class="grid grid-cols-1 gap-12 md:grid-cols-3">
                        <div class="flex flex-col items-center border-b border-gray-100 pb-8 last:border-none md:border-b-0 md:border-r md:pb-0 md:pr-12 md:last:border-none md:last:pr-0">
                            <span class="text-xs font-semibold tracking-widest text-gray-400 uppercase">Raw Score</span>
                            <div class="mt-4 flex items-baseline gap-1">
                                <span class="text-6xl font-bold tracking-tight text-gray-900">{{ attempt.score }}</span>
                                <span class="text-xl font-semibold text-gray-300">/ {{ totalQuestions }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-center border-b border-gray-100 pb-8 last:border-none md:border-b-0 md:border-r md:pb-0 md:px-12 md:last:border-none md:last:pr-0">
                            <span class="text-xs font-semibold tracking-widest text-gray-400 uppercase">Performance</span>
                            <div class="mt-4">
                                <span class="text-6xl font-bold tracking-tight text-primary">{{ percentage }}%</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-center md:pl-12">
                            <span class="text-xs font-semibold tracking-widest text-gray-400 uppercase">Assessment Grade</span>
                            <div class="mt-4 text-center">
                                <span class="text-4xl font-bold tracking-tight block" :class="getGrade.color">{{ getGrade.label }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Data Grid -->
                    <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-2">
                        <div class="space-y-6">
                            <div>
                                <label class="text-xs font-semibold tracking-widest text-gray-400 uppercase">Academic Subject</label>
                                <p class="mt-1 text-lg font-bold text-gray-800 underline decoration-primary/20 decoration-2 underline-offset-4">{{ attempt.exam.subject?.name || 'Multi-Subject Assessment' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold tracking-widest text-gray-400 uppercase">Examination Title</label>
                                <p class="mt-1 text-sm font-semibold text-gray-600 tracking-tight">{{ attempt.exam.title }}</p>
                            </div>
                        </div>
                        <div class="space-y-6 md:text-right">
                            <div>
                                <label class="text-xs font-semibold tracking-widest text-gray-400 uppercase">Submission Reference</label>
                                <p class="mt-1 font-mono text-xs font-medium text-gray-500">{{ attempt.id.substring(0, 16).toUpperCase() }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold tracking-widest text-gray-400 uppercase">Certified Date</label>
                                <p class="mt-1 text-sm font-semibold text-gray-600">{{ new Date(attempt.submitted_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer & Actions -->
                    <div class="mt-16 flex flex-col items-center border-t border-gray-100 pt-12">
                        <div class="flex flex-wrap justify-center gap-4 print:hidden w-full">
                            <button
                                @click="handlePrint"
                                class="inline-flex flex-1 items-center justify-center gap-x-2 rounded-lg border border-gray-200 bg-white px-6 py-4 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50 focus:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50 transition-all"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Print Result Slip
                            </button>
                            <Link
                                href="/student/dashboard"
                                class="inline-flex flex-[2] items-center justify-center gap-x-2 rounded-lg border border-transparent bg-primary px-6 py-4 text-sm font-semibold text-white hover:bg-primary/90 focus:bg-primary/90 focus:outline-none disabled:pointer-events-none disabled:opacity-50 shadow-sm"
                            >
                                Return to Student Hub
                            </Link>
                        </div>

                        <div class="mt-12 text-center">
                            <p class="text-xs font-medium leading-relaxed text-gray-400 max-w-lg">
                                This document serves as a digital certification of performance for the specified examination. 
                                Alteration of this record is a punishable offense under school policy.
                            </p>
                            <p class="mt-4 text-[10px] font-semibold tracking-widest text-primary uppercase ">&copy; Chrisland Schools Management System</p>
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
