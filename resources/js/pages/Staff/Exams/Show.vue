<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { manageQuestions, edit as editExamAction, showHardCopy as showHardCopyAction, showAnswerSheet as showAnswerSheetAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Exam {
    id: string;
    title: string;
    branch: string;
    school_id: string;
    subject?: { name: string; level: string } | null;
    school_class?: { name: string; level: string };
    prospective_class?: { name: string };
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
        return props.exam.compositions.reduce((acc, comp) => acc + (comp.question_count * comp.marks_per_question), 0);
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
        case 'active': return 'bg-teal-100 text-teal-800 border-teal-200';
        case 'draft': return 'bg-gray-100 text-gray-800 border-gray-200';
        case 'closed': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const formatDate = (dateStr: string | null) => {
    if (!dateStr) return 'Not Scheduled';
    return new Date(dateStr).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <component :is="Layout">
        <Head :title="exam.title" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <Link href="/staff/exams" class="hover:text-primary transition-colors">Examinations Vault</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800 font-bold uppercase tracking-tight">Protocol #{{ exam.id.substring(0, 8) }}</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-x-2 mb-2">
                        <span :class="['inline-flex items-center py-1 px-2.5 rounded-md text-[10px] font-bold border uppercase tracking-widest', getStatusBadge(exam.status)]">
                            {{ exam.status }}
                        </span>
                        <span v-if="branches[exam.school_id]" class="inline-flex items-center py-1 px-2.5 rounded-md text-[10px] font-bold bg-white border border-gray-200 text-gray-500 uppercase tracking-widest">
                            {{ branches[exam.school_id].name }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800 uppercase tracking-tight">{{ exam.title }}</h1>
                    <p class="text-sm text-gray-500 mt-1 uppercase tracking-widest font-medium">{{ exam.academic_session?.name || 'Academic Session Unset' }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex rounded-lg shadow-sm">
                        <a :href="showHardCopyAction(exam.id).url" target="_blank" class="py-2.5 px-4 inline-flex justify-center items-center gap-2 -ms-px first:rounded-s-lg last:rounded-e-lg border border-gray-200 bg-white text-gray-700 align-middle hover:bg-gray-50 focus:z-10 transition-all text-xs font-bold uppercase">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            Paper
                        </a>
                        <a :href="showAnswerSheetAction(exam.id).url" target="_blank" class="py-2.5 px-4 inline-flex justify-center items-center gap-2 -ms-px first:rounded-s-lg last:rounded-e-lg border border-gray-200 bg-white text-gray-700 align-middle hover:bg-gray-50 focus:z-10 transition-all text-xs font-bold uppercase">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                            Key
                        </a>
                    </div>
                    <Link
                        :href="editExamAction(exam.id).url"
                        class="py-2.5 px-4 inline-flex items-center gap-x-2 text-xs font-bold rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 transition-all uppercase"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Settings
                    </Link>
                    <Link
                        :href="manageQuestions(exam.id).url"
                        class="py-2.5 px-4 inline-flex items-center gap-x-2 text-xs font-black rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover shadow-sm transition-all uppercase"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        Allocation
                    </Link>
                </div>
            </div>

            <!-- Performance Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                <div class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Category</p>
                    <div class="mt-3">
                        <h3 class="text-xl font-bold text-gray-800 uppercase italic">{{ exam.type }}</h3>
                    </div>
                </div>

                <div class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Mandatory Level</p>
                    <div class="mt-3">
                        <h3 class="text-xl font-bold text-gray-800 uppercase tracking-tight">{{ exam.school_class?.name || 'Open Tier' }}</h3>
                    </div>
                </div>

                <div class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Question Load</p>
                    <div class="mt-3 flex items-baseline gap-x-2">
                        <h3 class="text-3xl font-bold text-gray-800">{{ exam.questions.length }}</h3>
                        <span class="text-xs font-bold text-gray-400 uppercase">/ {{ totalRequiredQuestions }} Target</span>
                    </div>
                    <div class="mt-4 flex w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="flex flex-col justify-center overflow-hidden bg-primary transition duration-500" :style="{ width: allocationProgress + '%' }"></div>
                    </div>
                </div>

                <div class="flex flex-col bg-gray-900 border border-gray-800 rounded-xl shadow-sm p-6">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Score Potential</p>
                    <div class="mt-3 flex items-baseline gap-x-2">
                        <h3 class="text-3xl font-bold text-primary italic">{{ totalPotentialMarks }}</h3>
                        <span class="text-xs font-bold text-primary/60 uppercase">Points</span>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid lg:grid-cols-12 gap-6 sm:gap-10">
                <!-- Left: Syllabus & Directives -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Blueprint Registry -->
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Syllabus Breakdown</h3>
                            <span v-if="exam.compositions?.length" class="text-[10px] font-black text-gray-400 uppercase">{{ allocationProgress }}% Sync Active</span>
                        </div>
                        
                        <div class="p-6">
                            <div v-if="exam.compositions?.length" class="grid sm:grid-cols-2 gap-4">
                                <div v-for="comp in exam.compositions" :key="comp.id" class="p-4 bg-gray-50 border border-gray-200 rounded-xl hover:border-primary/30 transition-all">
                                    <div class="flex justify-between items-start mb-2">
                                        <h5 class="text-sm font-bold text-gray-800 uppercase tracking-tight italic">{{ comp.subject.name }}</h5>
                                        <span class="text-xs font-black text-primary">{{ comp.question_count }} Qs</span>
                                    </div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest truncate">{{ comp.topic?.name || 'General Syllabus Pool' }}</p>
                                    <div class="mt-3 flex items-center justify-between pt-3 border-t border-gray-200">
                                        <span class="text-[10px] font-bold text-gray-300 uppercase">Valuation</span>
                                        <span class="text-[10px] font-black text-gray-800 uppercase">{{ comp.marks_per_question }} Pts / Item</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-12">
                                <p class="text-sm font-bold text-gray-400 italic uppercase tracking-widest">Single-subject assessment logic.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Directives -->
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Candidate Directives</h3>
                        </div>
                        <div class="p-6">
                            <div class="p-6 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-600 leading-relaxed italic whitespace-pre-line">
                                {{ exam.instructions || 'Standard institutional examination behavioral protocols apply for all candidates during this session.' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Schedule & Audit -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Protocol Window -->
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-slate-900 border-b border-slate-800">
                            <h3 class="text-[10px] font-bold text-white uppercase tracking-[0.2em]">Protocol Window</h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="flex items-start gap-x-3">
                                <div class="size-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Session Launch</p>
                                    <p class="text-sm font-bold text-gray-800 uppercase tracking-tight italic">{{ formatDate(exam.start_time) }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-x-3 pt-6 border-t border-gray-100">
                                <div class="size-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Auto Termination</p>
                                    <p class="text-sm font-bold text-gray-800 uppercase tracking-tight italic">{{ formatDate(exam.end_time) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Audit Info -->
                    <div class="bg-teal-50 border border-teal-200 rounded-xl p-6">
                        <div class="flex items-center gap-x-3 mb-4">
                            <div class="size-8 rounded-lg bg-teal-100 text-teal-600 flex items-center justify-center shrink-0">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h4 class="text-[10px] font-black text-teal-800 uppercase tracking-widest">Protocol Audit</h4>
                        </div>
                        <p class="text-xs font-medium text-teal-700 leading-relaxed italic uppercase tracking-tight">
                            This assessment follows the <strong class="text-teal-900">biennial question rotation policy</strong>. Items selected will be compliant with the 2-year shuffle protocol.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pool List -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Allocated Registry ({{ exam.questions.length }})</h3>
                    <Link :href="manageQuestions(exam.id).url" class="text-[10px] font-black text-primary hover:text-primary-hover uppercase tracking-widest">Manage Pool</Link>
                </div>
                
                <div class="p-6">
                    <div v-if="exam.questions.length > 0" class="grid sm:grid-cols-2 gap-4">
                        <div v-for="(question, index) in exam.questions" :key="question.id" class="flex gap-4 p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition-all group">
                            <div class="size-8 flex-shrink-0 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-xs font-bold text-gray-400 group-hover:text-primary transition-all">
                                {{ (index + 1).toString().padStart(2, '0') }}
                            </div>
                            <div class="grow min-w-0">
                                <p class="text-sm font-semibold text-gray-800 leading-relaxed uppercase tracking-tight line-clamp-2">
                                    {{ question.content }}
                                </p>
                                <div class="mt-3 flex items-center gap-x-3">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">{{ question.type.replace('_', ' ') }}</span>
                                    <span class="size-1 rounded-full bg-gray-300"></span>
                                    <span :class="['text-[10px] font-bold uppercase tracking-tighter', question.difficulty === 'hard' ? 'text-red-500' : question.difficulty === 'easy' ? 'text-teal-500' : 'text-blue-500']">
                                        {{ question.difficulty }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-20 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                        <div class="size-16 mx-auto mb-4 bg-white rounded-xl flex items-center justify-center text-gray-300 shadow-sm border border-gray-100">
                            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        </div>
                        <h4 class="text-base font-semibold text-gray-800 uppercase tracking-tight italic">Repository Detached</h4>
                        <p class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-widest">Allocate valid test items to finalize this protocol.</p>
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
