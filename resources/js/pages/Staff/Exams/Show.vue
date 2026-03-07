<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { manageQuestions, edit as editExamAction, showHardCopy as showHardCopyAction, showAnswerSheet as showAnswerSheetAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Exam {
    id: string;
    title: string;
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
const isAdmin = computed(() => (page.props.auth.user as any).roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));
</script>

<template>
    <component :is="Layout">
        <Head :title="exam.title" />

        <div class="space-y-10">
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-400">
                <Link href="/staff/exams" class="text-slate-400 hover:text-slate-600 transition-colors">Exams</Link>
                <span class="text-slate-300">/</span>
                <span class="text-slate-500 font-bold">{{ exam.title }}</span>
            </nav>

            <div class="relative overflow-hidden rounded-xl bg-slate-900 px-10 py-12 text-white shadow-2xl">
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
                    <div class="space-y-6">
                        <div class="flex items-center gap-4">
                            <span class="rounded-full bg-primary px-3 py-1 text-[9px] font-black tracking-widest text-slate-900 uppercase">{{
                                exam.status
                            }}</span>
                            <h1 class="text-3xl font-black">{{ exam.title }}</h1>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-8 gap-y-4">
                            <div class="flex items-center gap-2 border-r border-white/10 pr-8 last:border-0 last:pr-0">
                                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Subject:</span>
                                <span class="text-sm font-bold text-lemon-yellow">
                                    {{ exam.type === 'entrance' ? 'Multi-Subject Assessment' : (exam.subject?.name || 'N/A') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 border-r border-white/10 pr-8 last:border-0 last:pr-0">
                                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                    {{ exam.type === 'entrance' ? 'Admission Batch:' : 'Class:' }}
                                </span>
                                <span class="text-sm font-bold text-lemon-yellow">
                                    {{ exam.type === 'entrance' ? exam.prospective_class?.name : exam.school_class?.name }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 border-r border-white/10 pr-8 last:border-0 last:pr-0">
                                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Time Allotted:</span>
                                <span class="text-sm font-bold text-lemon-yellow">{{ exam.duration }} Mins</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                        <a
                            :href="showHardCopyAction(exam.id).url"
                            target="_blank"
                            class="flex items-center gap-3 whitespace-nowrap rounded-xl border-2 border-white/20 bg-white/10 px-4 py-3 text-[10px] font-black tracking-widest text-white uppercase transition-all hover:bg-white hover:text-slate-900 active:scale-95 sm:px-6 sm:py-4 sm:text-xs"
                        >
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                />
                            </svg>
                            Hard Copy
                        </a>
                        <a
                            :href="showAnswerSheetAction(exam.id).url"
                            target="_blank"
                            class="flex items-center gap-3 whitespace-nowrap rounded-xl border-2 border-white/20 bg-white/10 px-4 py-3 text-[10px] font-black tracking-widest text-white uppercase transition-all hover:bg-white hover:text-slate-900 active:scale-95 sm:px-6 sm:py-4 sm:text-xs"
                        >
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                />
                            </svg>
                            Answer Sheet
                        </a>
                        <Link
                            :href="editExamAction(exam.id).url"
                            class="flex items-center gap-3 whitespace-nowrap rounded-xl border-2 border-white/20 bg-white/10 px-4 py-3 text-[10px] font-black tracking-widest text-white uppercase transition-all hover:bg-white hover:text-slate-900 active:scale-95 sm:px-6 sm:py-4 sm:text-xs"
                        >
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                            Edit
                        </Link>
                        <Link
                            :href="manageQuestions(exam.id).url"
                            class="group flex items-center gap-3 whitespace-nowrap rounded-xl bg-lemon-yellow px-4 py-3 text-[10px] font-black tracking-widest text-primary uppercase shadow-xl shadow-lemon-yellow/10 transition-all hover:scale-105 active:scale-95 sm:px-6 sm:py-4 sm:text-xs lg:px-8"
                        >
                            <svg class="h-4 w-4 transition-transform group-hover:rotate-12 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            {{ exam.questions.length > 0 ? 'Questions' : 'Allocate' }}
                        </Link>
                    </div>
                </div>
                <div class="rounded-full absolute -top-24 -right-24 h-64 w-64 bg-primary/20 blur-3xl"></div>
            </div>

            <!-- Blueprint Summary for Entrance -->
            <div v-if="exam.type === 'entrance' && exam.compositions?.length" class="space-y-6">
                <h3 class="flex items-center gap-3 text-xs font-black tracking-[0.2em] text-slate-400 uppercase italic">
                    <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Assessment Blueprint (Compositions)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="comp in exam.compositions" :key="comp.id" class="group relative bg-white p-6 rounded-2xl border border-slate-100 shadow-sm transition-all hover:border-primary/20 hover:shadow-lg">
                        <div class="space-y-3">
                            <span class="text-[10px] font-black tracking-widest text-primary uppercase block">{{ comp.subject?.name || 'Multi-Subject' }}</span>
                            <p class="text-[11px] font-bold text-slate-500 italic line-clamp-1">{{ comp.topic?.name || 'General Subject Pool' }}</p>
                            <div class="flex items-center justify-between pt-4 border-t border-slate-50 transition-colors group-hover:border-primary/10">
                                <span class="text-[9px] font-black text-slate-400 uppercase">Target:</span>
                                <span class="text-xs font-black text-slate-800 bg-slate-50 px-2 py-1 rounded-lg">{{ comp.question_count }} Questions</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="ml-2 flex items-center justify-between">
                    <h3 class="flex items-center gap-3 text-sm font-black tracking-[0.2em] text-slate-400 uppercase">
                        <div class="rounded-lg-full h-2 w-2 bg-primary"></div>
                        Allocated Questions ({{ exam.questions.length }})
                    </h3>
                </div>

                <div v-if="exam.questions.length > 0" class="grid grid-cols-1 gap-4">
                    <div
                        v-for="(question, index) in exam.questions"
                        :key="question.id"
                        class="group flex items-center justify-between rounded-2xl border border-slate-100 bg-white p-6 transition-all hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5"
                    >
                        <div class="flex items-start gap-6 w-full">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-50 text-sm font-black text-slate-400 transition-colors group-hover:bg-primary group-hover:text-white">
                                {{ index + 1 }}
                            </div>
                            <div class="w-full">
                                <p class="text-[15px] leading-relaxed font-bold text-slate-700">{{ question.content }}</p>
                                <div class="mt-4 flex flex-wrap items-center gap-4">
                                    <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-1.5 border border-slate-100 transition-colors group-hover:border-primary/10 group-hover:bg-primary/5">
                                        <span class="text-[9px] font-black tracking-[0.15em] text-slate-400 uppercase">Type:</span>
                                        <span class="text-[10px] font-black text-slate-600 uppercase">{{ question.type.replace('_', ' ') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-1.5 border border-slate-100 transition-colors group-hover:border-primary/10 group-hover:bg-primary/5">
                                        <span class="text-[9px] font-black tracking-[0.15em] text-slate-400 uppercase">Difficulty:</span>
                                        <span
                                            class="text-[10px] font-black uppercase"
                                            :class="{
                                                'text-emerald-500': question.difficulty === 'easy',
                                                'text-amber-500': question.difficulty === 'medium',
                                                'text-rose-500': question.difficulty === 'hard'
                                            }"
                                        >
                                            {{ question.difficulty }}
                                        </span>
                                    </div>
                                    <div v-if="question.topic" class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-1.5 border border-slate-100 transition-colors group-hover:border-primary/10 group-hover:bg-primary/5">
                                        <span class="text-[9px] font-black tracking-[0.15em] text-slate-400 uppercase">Topic:</span>
                                        <span class="text-[10px] font-black text-slate-600 uppercase truncate max-w-50">{{ question.topic.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 py-20 text-center"
                >
                    <div class="rounded-lg-full mb-4 flex h-20 w-20 items-center justify-center bg-white text-slate-200 shadow-sm">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                            />
                        </svg>
                    </div>
                    <h4 class="text-xl font-black tracking-widest text-slate-400 uppercase">No Questions Allocated</h4>
                    <p class="mt-2 text-sm font-bold text-slate-400">Click "Manage Questions" to start adding questions to this exam.</p>
                </div>
            </div>
        </div>
    </component>
</template>
