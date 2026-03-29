<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    manageQuestions,
    edit as editExamAction,
    showHardCopy as showHardCopyAction,
    showAnswerSheet as showAnswerSheetAction,
} from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Exam {
    id: string;
    title: string;
    branch: string;
    school_id: string;
    subject?: { name: string; level: string } | null;
    school_class?: { name: string; level: string };
    academic_session?: { name: string };
    status: string;
    type: string;
    duration: number;
    start_time: string | null;
    end_time: string | null;
    instructions: string | null;
    description: string | null;
    questions: any[];
    compositions: Array<{
        id: string;
        subject_id: string;
        topic_id: string | null;
        subject: { name: string };
        topic?: { name: string };
        question_count: number;
        marks_per_question: number;
    }>;
}

const props = defineProps<{
    exam: Exam;
}>();

const page = usePage();
const branches = computed(() => (page.props as any).branches || {});
const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

// Stat Calculations
const totalRequiredQuestions = computed(() => {
    if (props.exam.compositions?.length) {
        return props.exam.compositions.reduce((acc, comp) => acc + comp.question_count, 0);
    }
    return props.exam.questions.length;
});

const totalPotentialMarks = computed(() => {
    if (props.exam.compositions?.length) {
        return props.exam.compositions.reduce((acc, comp) => acc + comp.question_count * comp.marks_per_question, 0);
    }
    return props.exam.questions.length * 1;
});

const allocationProgress = computed(() => {
    if (!props.exam.compositions?.length) return 100;
    const current = props.exam.questions.length;
    const required = totalRequiredQuestions.value;
    return Math.min(Math.round((current / required) * 100), 100);
});

const getStatusBadge = (status: string) => {
    switch (status.toLowerCase()) {
        case 'active':
        case 'live':
            return 'bg-teal-100 text-teal-800 border-teal-200';
        case 'draft':
            return 'bg-gray-100 text-gray-800 border-gray-200';
        case 'closed':
            return 'bg-red-100 text-red-800 border-red-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getCategoryLabel = (type: string) => {
    switch (type.toLowerCase()) {
        case 'ca':
            return 'Continuous Assessment';
        case 'terminal':
            return 'Terminal Examination';
        case 'entrance':
            return 'Entrance Assessment';
        default:
            return type.replace('_', ' ');
    }
};

const formatDate = (dateStr: string | null) => {
    if (!dateStr) return 'Not Scheduled';
    return new Date(dateStr).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <component :is="Layout">
        <Head :title="exam.title" />

        <div class="mx-auto max-w-7xl pb-24">
            <div class="space-y-6 sm:space-y-10">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="transition-colors hover:text-primary">Dashboard</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <Link href="/staff/exams" class="transition-colors hover:text-primary">Examinations Vault</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-800 font-medium">Protocol #{{ exam.id.substring(0, 8) }}</span>
                </nav>

                <!-- Page Header -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="mb-2 flex items-center gap-x-2">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-md border px-2.5 py-1 text-[10px] font-bold tracking-widest uppercase',
                                    getStatusBadge(exam.status),
                                ]"
                            >
                                {{ exam.status }}
                            </span>
                            <span
                                v-if="branches[exam.school_id]"
                                class="inline-flex items-center rounded-md border border-gray-200 bg-white px-2.5 py-1 text-[10px] font-bold tracking-widest text-gray-500 uppercase"
                            >
                                {{ branches[exam.school_id].name }}
                            </span>
                        </div>
                        <h1 class="text-2xl font-semibold text-gray-800">{{ exam.title }}</h1>
                        <p class="mt-1 text-sm text-gray-500 uppercase tracking-widest">
                            {{ exam.academic_session?.name || 'Academic Session Unset' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="inline-flex rounded-lg shadow-sm">
                            <a
                                :href="showHardCopyAction(exam.id).url"
                                target="_blank"
                                class="-ms-px inline-flex items-center justify-center gap-2 border border-gray-200 bg-white px-4 py-2 align-middle text-xs font-semibold text-gray-700 uppercase transition-all first:rounded-s-lg last:rounded-e-lg hover:bg-gray-50 focus:z-10"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                    />
                                </svg>
                                Print Paper
                            </a>
                            <a
                                :href="showAnswerSheetAction(exam.id).url"
                                target="_blank"
                                class="-ms-px inline-flex items-center justify-center gap-2 border border-gray-200 bg-white px-4 py-2 align-middle text-xs font-semibold text-gray-700 uppercase transition-all first:rounded-s-lg last:rounded-e-lg hover:bg-gray-50 focus:z-10"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01"
                                    />
                                </svg>
                                Answer Key
                            </a>
                        </div>
                        <Link
                            :href="editExamAction(exam.id).url"
                            class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-800 uppercase shadow-sm transition-all hover:bg-gray-50"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                            Settings
                        </Link>
                        <Link
                            :href="manageQuestions(exam.id).url"
                            class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-2 text-xs font-semibold text-white uppercase shadow-sm transition-all"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Allocation
                        </Link>
                    </div>
                </div>

                <!-- Performance Grid -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                    <!-- Category Card -->
                    <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="p-4 md:p-5">
                            <div class="flex items-center gap-x-2">
                                <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">Category</p>
                            </div>
                            <div class="mt-1 flex items-center gap-x-2">
                                <h3 class="text-xl font-bold text-gray-800 sm:text-2xl capitalize">{{ getCategoryLabel(exam.type) }}</h3>
                            </div>
                            <div class="mt-3 flex items-center text-xs font-medium text-blue-600 uppercase tracking-widest">Protocol Type</div>
                        </div>
                    </div>
                    <!-- Level Card -->
                    <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="p-4 md:p-5">
                            <div class="flex items-center gap-x-2">
                                <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">Target Level</p>
                            </div>
                            <div class="mt-1 flex items-center gap-x-2">
                                <h3 class="text-xl font-bold text-gray-800 sm:text-2xl">{{ exam.school_class?.name || 'Open Tier' }}</h3>
                            </div>
                            <div class="mt-3 flex items-center text-xs font-medium text-teal-600 uppercase tracking-widest">Mandatory Class</div>
                        </div>
                    </div>

                    <!-- Question Load Card -->
                    <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="p-4 md:p-5">
                            <div class="flex items-center gap-x-2">
                                <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">Question Load</p>
                            </div>
                            <div class="mt-1 flex items-baseline gap-x-2">
                                <h3 class="text-xl font-bold text-gray-800 sm:text-2xl">{{ exam.questions.length }}</h3>
                                <span class="text-xs font-semibold text-gray-400 uppercase">/ {{ totalRequiredQuestions }} Target</span>
                            </div>
                            <div class="mt-4 flex h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                <div
                                    class="flex flex-col justify-center overflow-hidden bg-primary transition duration-500"
                                    :style="{ width: allocationProgress + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Potential Card -->
                    <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="p-4 md:p-5">
                            <div class="flex items-center gap-x-2">
                                <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">Score Potential</p>
                            </div>
                            <div class="mt-1 flex items-baseline gap-x-2">
                                <h3 class="text-xl font-bold text-gray-800 sm:text-2xl">{{ totalPotentialMarks }}</h3>
                                <span class="text-xs font-semibold text-gray-400 uppercase">Points</span>
                            </div>
                            <div class="mt-3 flex items-center text-xs font-medium text-orange-600 uppercase tracking-widest">Maximum Valuation</div>
                        </div>
                    </div>
                </div>
                <!-- Content Grid -->
                <div class="grid gap-6 sm:gap-10 lg:grid-cols-12">
                    <!-- Left: Syllabus & Directives -->
                    <div class="space-y-6 lg:col-span-8">
                        <!-- Blueprint Registry -->
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4">
                                <h3 class="text-sm font-bold tracking-widest text-gray-800 uppercase">Syllabus Breakdown</h3>
                                <span v-if="exam.compositions?.length" class="text-[10px] font-black text-gray-400 uppercase"
                                    >{{ allocationProgress }}% Sync Active</span
                                >
                            </div>

                            <div class="p-6">
                                <div v-if="exam.compositions?.length" class="grid gap-4 sm:grid-cols-2">
                                    <div
                                        v-for="comp in exam.compositions"
                                        :key="comp.id"
                                        class="rounded-xl border border-gray-200 bg-gray-50 p-4 transition-all hover:border-primary/30"
                                    >
                                        <div class="mb-2 flex items-start justify-between">
                                            <h5 class="text-sm font-bold tracking-tight text-gray-800 uppercase italic">{{ comp.subject.name }}</h5>
                                            <span class="text-xs font-black text-primary">{{ comp.question_count }} Qs</span>
                                        </div>
                                        <p class="truncate text-[10px] font-bold tracking-widest text-gray-400 uppercase">
                                            {{ comp.topic?.name || 'General Syllabus Pool' }}
                                        </p>
                                        <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-3">
                                            <span class="text-[10px] font-bold text-gray-300 uppercase">Valuation</span>
                                            <span class="text-[10px] font-black text-gray-800 uppercase">{{ comp.marks_per_question }} Pts / Item</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="py-12 text-center">
                                    <p class="text-sm font-bold tracking-widest text-gray-400 uppercase italic">Single-subject assessment logic.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Directives -->
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                                <h3 class="text-sm font-bold tracking-widest text-gray-800 uppercase">Candidate Directives</h3>
                            </div>
                            <div class="p-6">
                                <div
                                    class="rounded-xl border border-gray-200 bg-gray-50 p-6 text-sm leading-relaxed whitespace-pre-line text-gray-600 italic font-medium"
                                >
                                    {{
                                        exam.instructions ||
                                        'Standard institutional examination behavioral protocols apply for all candidates during this session.'
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Schedule & Audit -->
                    <div class="space-y-6 lg:col-span-4">
                        <!-- Protocol Window -->
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-slate-800 bg-slate-900 px-6 py-4">
                                <h3 class="text-[10px] font-bold tracking-[0.2em] text-white uppercase">Protocol Window</h3>
                            </div>
                            <div class="space-y-6 p-6">
                                <div class="flex items-start gap-x-3">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="mb-1 text-[10px] font-bold tracking-widest text-gray-400 uppercase">Session Launch</p>
                                        <p class="text-sm font-bold tracking-tight text-gray-800 uppercase italic">{{ formatDate(exam.start_time) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-x-3 border-t border-gray-100 pt-6">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="mb-1 text-[10px] font-bold tracking-widest text-gray-400 uppercase">Auto Termination</p>
                                        <p class="text-sm font-bold tracking-tight text-gray-800 uppercase italic">{{ formatDate(exam.end_time) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Audit Info -->
                        <div class="rounded-xl border border-teal-200 bg-teal-50 p-6">
                            <div class="mb-4 flex items-center gap-x-3">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-teal-100 text-teal-600">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <h4 class="text-[10px] font-black tracking-widest text-teal-800 uppercase">Protocol Audit</h4>
                            </div>
                            <p class="text-xs leading-relaxed font-medium tracking-tight text-teal-700 uppercase italic">
                                This assessment follows the <strong class="text-teal-900">biennial question rotation policy</strong>. Items selected will
                                be compliant with the 2-year shuffle protocol.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pool List -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-sm font-bold tracking-widest text-gray-800 uppercase">Allocated Registry ({{ exam.questions.length }})</h3>
                        <Link
                            :href="manageQuestions(exam.id).url"
                            class="hover:text-primary-hover text-[10px] font-black tracking-widest text-primary uppercase transition-colors"
                            >Manage Pool</Link
                        >
                    </div>

                    <div class="p-6">
                        <div v-if="exam.questions.length > 0" class="grid gap-4 sm:grid-cols-2">
                            <div
                                v-for="(question, index) in exam.questions"
                                :key="question.id"
                                class="group flex gap-4 rounded-xl border border-gray-200 p-4 transition-all hover:bg-gray-50"
                            >
                                <div
                                    class="flex size-8 flex-shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-xs font-bold text-gray-400 transition-all group-hover:text-primary"
                                >
                                    {{ (index + 1).toString().padStart(2, '0') }}
                                </div>
                                <div class="min-w-0 grow">
                                    <div class="mb-1 flex items-center gap-2">
                                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[9px] font-black tracking-widest text-slate-500 uppercase">
                                            {{ question.topic?.subject?.name || 'N/A' }}
                                        </span>
                                    </div>
                                    <p class="line-clamp-2 text-sm leading-relaxed font-semibold tracking-tight text-gray-800">
                                        {{ question.content }}
                                    </p>
                                    <div class="mt-3 flex items-center gap-x-3">
                                        <span class="text-[10px] font-bold tracking-tighter text-gray-400 uppercase">{{
                                            question.type.replace('_', ' ')
                                        }}</span>
                                        <span class="size-1 rounded-full bg-gray-300"></span>
                                        <span
                                            :class="[
                                                'text-[10px] font-bold tracking-tighter uppercase',
                                                question.difficulty === 'hard'
                                                    ? 'text-red-500'
                                                    : question.difficulty === 'easy'
                                                      ? 'text-teal-500'
                                                      : 'text-blue-500',
                                            ]"
                                        >
                                            {{ question.difficulty }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 py-20 text-center">
                            <div
                                class="mx-auto mb-4 flex size-16 items-center justify-center rounded-xl border border-gray-100 bg-white text-gray-300 shadow-sm"
                            >
                                <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            <h4 class="text-base font-semibold tracking-tight text-gray-800 uppercase italic">Repository Detached</h4>
                            <p class="mt-1 text-[10px] font-bold tracking-widest text-gray-400 uppercase">
                                Allocate valid test items to finalize this protocol.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
