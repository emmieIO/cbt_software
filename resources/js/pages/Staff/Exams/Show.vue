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
    subject?: { name: string } | null;
    school_class?: { name: string };
    prospective_class?: { name: string };
    status: string;
    type: string;
    duration: number;
    questions: any[];
    compositions?: Array<{
        id: string;
        subject: { name: string } | null;
        topic?: { name: string };
        question_count: number;
    }>;
}

defineProps<{
    exam: Exam;
}>();

const page = usePage();
const branches = computed(() => (page.props as any).branches || {});
const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));
</script>

<template>
    <component :is="Layout">
        <Head :title="exam.title" />

        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="h-3 w-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <Link href="/staff/exams" class="hover:text-primary transition-colors">Vault</Link>
                <svg class="h-3 w-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Configuration</span>
            </nav>

            <div class="relative overflow-hidden rounded-xl bg-gray-900 p-6 md:p-10 text-white shadow-sm">
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="space-y-4">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-3">
                                <Link href="/staff/exams" class="inline-flex items-center justify-center size-8 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 transition-all">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                                </Link>
                                <span class="inline-flex items-center py-0.5 px-2 rounded-full text-xs font-medium bg-primary text-gray-900">
                                    {{ exam.status }}
                                </span>
                                <div v-if="branches[exam.branch]" class="inline-flex items-center py-0.5 px-2 rounded-full text-xs font-medium bg-white/10 text-white">
                                    {{ branches[exam.branch].name }}
                                </div>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-semibold">{{ exam.title }}</h1>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Subject:</span>
                                <span class="text-sm font-medium text-primary">
                                    {{ exam.type === 'entrance' ? 'Multi-Subject Assessment' : (exam.subject?.name || 'N/A') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    {{ exam.type === 'entrance' ? 'Admission Batch:' : 'Class:' }}
                                </span>
                                <span class="text-sm font-medium text-primary">
                                    {{ exam.type === 'entrance' ? exam.prospective_class?.name : exam.school_class?.name }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Time:</span>
                                <span class="text-sm font-medium text-primary">{{ exam.duration }} Mins</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 md:gap-3">
                        <a
                            :href="showHardCopyAction(exam.id).url"
                            target="_blank"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-semibold rounded-lg border border-white/20 bg-white/5 text-white hover:bg-white/10 disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Hard Copy
                        </a>
                        <a
                            :href="showAnswerSheetAction(exam.id).url"
                            target="_blank"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-semibold rounded-lg border border-white/20 bg-white/5 text-white hover:bg-white/10 disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            Answer Sheet
                        </a>
                        <Link
                            :href="editExamAction(exam.id).url"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-semibold rounded-lg border border-white/20 bg-white/5 text-white hover:bg-white/10 disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </Link>
                        <Link
                            :href="manageQuestions(exam.id).url"
                            class="py-2 px-4 inline-flex items-center gap-x-2 text-xs font-semibold rounded-lg border border-transparent bg-primary text-gray-900 hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            {{ exam.questions.length > 0 ? 'Questions' : 'Allocate' }}
                        </Link>
                    </div>
                </div>
                <div class="absolute -top-24 -right-24 h-64 w-64 bg-primary/10 blur-3xl rounded-full"></div>
            </div>

            <!-- Blueprint Summary for Entrance -->
            <div v-if="exam.type === 'entrance' && exam.compositions?.length" class="space-y-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Assessment Blueprint</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="comp in exam.compositions" :key="comp.id" class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm p-5 hover:border-primary/30 transition-colors">
                        <span class="text-xs font-semibold text-primary uppercase tracking-wider mb-1">{{ comp.subject?.name || 'Multi-Subject' }}</span>
                        <p class="text-xs font-medium text-gray-600 line-clamp-1 mb-4">{{ comp.topic?.name || 'General Subject Pool' }}</p>
                        <div class="mt-auto pt-3 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Target</span>
                            <span class="text-xs font-semibold text-gray-800">{{ comp.question_count }} Qs</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Allocated Questions ({{ exam.questions.length }})</h3>

                <div v-if="exam.questions.length > 0" class="space-y-3">
                    <div
                        v-for="(question, index) in exam.questions"
                        :key="question.id"
                        class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm p-5 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-start gap-4">
                            <div class="size-8 flex-shrink-0 flex items-center justify-center rounded-lg bg-gray-100 text-xs font-semibold text-gray-500">
                                {{ index + 1 }}
                            </div>
                            <div class="grow">
                                <p class="text-sm font-medium text-gray-800 leading-relaxed">{{ question.content }}</p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center py-1 px-2 rounded-lg text-xs font-medium bg-gray-100 text-gray-600 uppercase tracking-wider">
                                        {{ question.type.replace('_', ' ') }}
                                    </span>
                                    <span
                                        class="inline-flex items-center py-1 px-2 rounded-lg text-xs font-medium uppercase tracking-wider"
                                        :class="{
                                            'bg-teal-100 text-teal-800': question.difficulty === 'easy',
                                            'bg-blue-100 text-blue-800': question.difficulty === 'medium',
                                            'bg-red-100 text-red-800': question.difficulty === 'hard'
                                        }"
                                    >
                                        {{ question.difficulty }}
                                    </span>
                                    <span v-if="question.topic" class="inline-flex items-center py-1 px-2 rounded-lg text-xs font-medium bg-gray-100 text-gray-600 uppercase tracking-wider max-w-[200px] truncate">
                                        {{ question.topic.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="p-12 flex flex-col items-center justify-center bg-gray-50 border border-dashed border-gray-200 rounded-xl text-center"
                >
                    <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-white text-gray-400 shadow-sm mb-4">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h4 class="text-base font-semibold text-gray-800">No Questions Allocated</h4>
                    <p class="mt-1 text-sm text-gray-500">Click "Manage Questions" to start adding questions to this exam.</p>
                </div>
            </div>
        </div>
    </component>
</template>
