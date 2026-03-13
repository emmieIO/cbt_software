<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Option {
    id: string;
    content: string;
}

interface Answer {
    id: string;
    question_id: string;
    option_id: string;
    is_correct: boolean;
    question: {
        id: string;
        content: string;
        options: Option[];
    };
    option: Option;
}

interface Attempt {
    id: string;
    score: number;
    submitted_at: string;
    metadata: any;
    answers: Answer[];
}

const props = defineProps<{
    exam: {
        id: string;
        title: string;
        subject: { name: string } | null;
    };
    student: {
        id: string;
        name: string;
        username: string;
    };
    attempt: Attempt;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const totalQuestions = computed(() => props.attempt.answers.length);
const percentage = computed(() => {
    if (totalQuestions.value === 0) return 0;
    return Math.round((props.attempt.score / totalQuestions.value) * 100);
});
</script>

<template>
    <component :is="Layout">
        <Head :title="`Result Details - ${student.name}`" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <Link :href="`/staff/exams/${exam.id}/results`" class="mb-2 inline-flex items-center gap-2 text-xs font-medium text-primary hover:underline">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Back to Exam Results
                    </Link>
                    <h1 class="text-2xl font-semibold text-gray-800">Candidate Script Review</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ exam.title }} • {{ student.name }}</p>
                </div>
            </div>

            <!-- Performance Card -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 sm:p-8 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-6">
                        <div class="size-20 bg-gray-50 rounded-2xl flex items-center justify-center text-2xl font-bold text-gray-400 border border-gray-100">
                            {{ student.name.charAt(0) }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">{{ student.name }}</h2>
                            <p class="text-sm text-gray-500 font-medium">Admission ID: {{ student.username }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-8 border-l border-gray-100 pl-8 hidden md:flex">
                        <div class="text-center">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Final Score</p>
                            <p class="text-2xl font-bold text-gray-800">{{ attempt.score }} <span class="text-sm text-gray-400">/ {{ totalQuestions }}</span></p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Percentage</p>
                            <p class="text-2xl font-bold" :class="percentage >= 50 ? 'text-primary' : 'text-orange-500'">{{ percentage }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Answer Breakdown -->
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Response Breakdown</h3>
                    <div class="h-px flex-1 bg-gray-100"></div>
                </div>

                <div v-for="(answer, idx) in attempt.answers" :key="answer.id" class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="size-8 shrink-0 flex items-center justify-center rounded-lg bg-gray-50 text-xs font-bold text-gray-400 border border-gray-100">
                            {{ idx + 1 }}
                        </div>
                        <div class="flex-1">
                            <p class="text-base font-medium text-gray-800">{{ answer.question.content }}</p>
                        </div>
                        <div v-if="answer.is_correct" class="shrink-0 text-primary">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div v-else class="shrink-0 text-red-500">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pl-12">
                        <div 
                            v-for="opt in answer.question.options" 
                            :key="opt.id"
                            class="p-3 rounded-lg text-sm font-medium border"
                            :class="[
                                opt.id === answer.option_id ? (answer.is_correct ? 'bg-primary/5 border-primary/20 text-primary' : 'bg-red-50 border-red-100 text-red-600') : 'bg-gray-50 border-gray-100 text-gray-500'
                            ]"
                        >
                            <span class="mr-2 opacity-50">{{ opt.id === answer.option_id ? '●' : '○' }}</span>
                            {{ opt.content }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
