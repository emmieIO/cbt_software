<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
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
        academic_session?: { name: string };
        term?: string;
    };
}

const props = defineProps<{
    attempt: Attempt;
    totalQuestions: number;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user as any);

const hasViolation = computed(() => !!props.attempt.metadata?.termination_reason);

const percentage = computed(() => {
    const total = Number(props.totalQuestions) || 0;
    if (total === 0) return 0;
    return Math.round((Number(props.attempt.score) / total) * 100);
});

const getGrade = computed(() => {
    const p = percentage.value;
    if (p >= 85) return 'DISTINCTION';
    if (p >= 70) return 'CREDIT';
    if (p >= 50) return 'PASS';
    return 'FAIL';
});

const handlePrint = () => {
    window.print();
};

const formatIssueDate = (dateString: string) => {
    const date = new Date(dateString);

    if (Number.isNaN(date.getTime())) {
        return 'Invalid date';
    }

    try {
        return date.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return date.toISOString().slice(0, 10);
    }
};
</script>

<template>
    <StudentLayout>
        <Head title="Official Examination Result" />

        <div class="mx-auto max-w-5xl px-4 py-12 print:p-0">
            <!-- Security Alert (Hidden on Print) -->
            <div
                v-if="hasViolation"
                class="mb-8 flex items-center gap-x-4 border-2 border-red-600 bg-red-50 p-4 print:hidden"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center bg-red-600 text-white">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-black text-red-800 uppercase">Integrity Alert</h4>
                    <p class="text-xs font-bold text-red-700">Exam terminated prematurely: {{ attempt.metadata?.termination_reason }}</p>
                </div>
            </div>

            <!-- THE FLAT TRANSCRIPT -->
            <div class="border-2 border-slate-900 bg-white p-8 sm:p-16 print:border-none">
                
                <!-- INSTITUTIONAL HEADER -->
                <div class="text-center border-b-4 border-slate-900 pb-10">
                    <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="mx-auto mb-6 h-32 w-auto" />
                    <h1 class="font-serif text-5xl font-black tracking-tighter text-slate-900 uppercase">Chrisland Schools</h1>
                    <p class="mt-2 text-[11px] font-black tracking-[0.5em] text-slate-600 uppercase">Academic Assessment & Certification</p>
                    
                    <div class="mt-8 inline-block border-2 border-slate-900 px-10 py-2">
                        <h2 class="text-sm font-black tracking-[0.3em] text-slate-900 uppercase">Official Statement of Result</h2>
                    </div>
                </div>

                <!-- DOCUMENT META -->
                <div class="mt-12 flex justify-between items-end border-b-2 border-slate-900 pb-4">
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Candidate Identification</p>
                        <p class="text-lg font-black text-slate-900 uppercase">{{ user.name }}</p>
                        <p class="font-mono text-sm font-bold text-slate-700 uppercase">UID: {{ user.username }}</p>
                    </div>
                    <div class="text-right space-y-1">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Document Ref</p>
                        <p class="font-mono text-sm font-bold text-slate-700 uppercase">{{ attempt.id.substring(0, 12) }}</p>
                        <p class="text-[10px] font-bold text-slate-900 uppercase">{{ attempt.exam.academic_session?.name }} SESSION</p>
                    </div>
                </div>

                <!-- MAIN RESULT TABLE -->
                <div class="mt-12">
                    <table class="w-full border-collapse border-2 border-slate-900">
                        <thead class="bg-slate-900 text-white">
                            <tr>
                                <th class="border border-slate-900 px-6 py-4 text-left text-[11px] font-black tracking-widest uppercase">Assessment Component</th>
                                <th class="border border-slate-900 px-6 py-4 text-center text-[11px] font-black tracking-widest uppercase w-32">Raw Score</th>
                                <th class="border border-slate-900 px-6 py-4 text-center text-[11px] font-black tracking-widest uppercase w-32">Weight %</th>
                                <th class="border border-slate-900 px-6 py-4 text-right text-[11px] font-black tracking-widest uppercase w-40">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b-2 border-slate-900">
                                <td class="border border-slate-900 px-6 py-8">
                                    <p class="text-xl font-black text-slate-900 uppercase">{{ attempt.exam.subject?.name || 'Integrated Studies' }}</p>
                                    <p class="mt-1 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">{{ attempt.exam.title }}</p>
                                </td>
                                <td class="border border-slate-900 px-6 py-8 text-center">
                                    <p class="text-xl font-black text-slate-900">{{ attempt.score }} <span class="text-sm text-slate-400">/ {{ totalQuestions }}</span></p>
                                </td>
                                <td class="border border-slate-900 px-6 py-8 text-center bg-slate-50">
                                    <p class="text-4xl font-black text-slate-900">{{ percentage }}%</p>
                                </td>
                                <td class="border border-slate-900 px-6 py-8 text-right">
                                    <span class="text-xl font-black tracking-tighter text-slate-900">{{ getGrade }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- AUTHENTICATION GRID -->
                <div class="mt-20 grid grid-cols-1 gap-20 md:grid-cols-2">
                    <div class="space-y-12">
                        <div class="border-t-2 border-slate-900 pt-2 text-center">
                            <p class="text-[10px] font-black uppercase text-slate-900 tracking-[0.3em]">Registrar / Exams Officer</p>
                        </div>
                    </div>
                    <div class="flex flex-col justify-end text-right space-y-4">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Verification ID</p>
                            <p class="font-mono text-xs font-bold text-slate-400 uppercase break-all">{{ attempt.id }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Date of Issue</p>
                            <p class="text-xs font-bold text-slate-900 uppercase">{{ formatIssueDate(attempt.submitted_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTION BAR (HIDDEN ON PRINT) -->
            <div class="mt-12 flex flex-col gap-4 sm:flex-row print:hidden">
                <button
                    @click="handlePrint"
                    class="inline-flex flex-1 items-center justify-center gap-x-3 bg-slate-900 px-8 py-5 text-sm font-black text-white transition-all hover:bg-black active:scale-95 shadow-xl shadow-slate-200"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print Official Copy
                </button>
                <Link
                    href="/student/dashboard"
                    class="inline-flex flex-1 items-center justify-center gap-x-3 border-2 border-slate-900 bg-white px-8 py-5 text-sm font-black text-slate-900 transition-all hover:bg-slate-50"
                >
                    Return to Dashboard
                </Link>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&display=swap');

@media print {
    :deep(nav),
    :deep(aside),
    :deep(header) {
        display: none !important;
    }
    :deep(main) {
        margin: 0 !important;
        padding: 0 !important;
    }
    .print\:hidden {
        display: none !important;
    }
    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background: white !important;
    }
}
</style>
