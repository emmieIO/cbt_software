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

        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <Link
                        :href="`/staff/exams/${exam.id}/results`"
                        class="mb-2 inline-flex items-center gap-2 text-xs font-medium text-primary hover:underline"
                    >
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Exam Results
                    </Link>
                    <h1 class="text-2xl font-semibold text-gray-800">Candidate Script Review</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ exam.title }} • {{ student.name }}</p>
                </div>
                <div>
                    <a
                        :href="`/staff/exams/${exam.id}/results/${student.id}/print`"
                        target="_blank"
                        class="inline-flex items-center gap-x-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-800 shadow-sm transition-all hover:bg-gray-50"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Official Result Slip
                    </a>
                </div>
            </div>

            <!-- Performance Card -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col items-center justify-between gap-6 p-6 sm:p-8 md:flex-row">
                    <div class="flex items-center gap-6">
                        <div
                            class="flex size-20 items-center justify-center rounded-2xl border border-gray-100 bg-gray-50 text-2xl font-bold text-gray-400"
                        >
                            {{ student.name.charAt(0) }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">{{ student.name }}</h2>
                            <p class="text-sm font-medium text-gray-500">Admission ID: {{ student.username }}</p>
                        </div>
                    </div>

                    <div class="flex hidden items-center gap-8 border-l border-gray-100 pl-8 md:flex">
                        <div class="text-center">
                            <p class="text-xs font-semibold tracking-widest text-gray-400 uppercase">Final Score</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ attempt.score }} <span class="text-sm text-gray-400">/ {{ totalQuestions }}</span>
                            </p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-semibold tracking-widest text-gray-400 uppercase">Percentage</p>
                            <p class="text-2xl font-bold" :class="percentage >= 50 ? 'text-primary' : 'text-orange-500'">{{ percentage }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Answer Breakdown -->
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <h3 class="text-xs font-bold tracking-widest text-gray-400 uppercase">Response Breakdown</h3>
                    <div class="h-px flex-1 bg-gray-100"></div>
                </div>

                <div
                    v-for="(answer, idx) in attempt.answers"
                    :key="answer.id"
                    class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-gray-100 bg-gray-50 text-xs font-bold text-gray-400"
                        >
                            {{ idx + 1 }}
                        </div>
                        <div class="flex-1">
                            <p class="text-base font-medium text-gray-800">{{ answer.question.content }}</p>
                        </div>
                        <div v-if="answer.is_correct" class="shrink-0 text-primary">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div v-else class="shrink-0 text-red-500">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 pl-12 md:grid-cols-2">
                        <div
                            v-for="opt in answer.question.options"
                            :key="opt.id"
                            class="rounded-lg border p-3 text-sm font-medium"
                            :class="[
                                opt.id === answer.option_id
                                    ? answer.is_correct
                                        ? 'border-primary/20 bg-primary/5 text-primary'
                                        : 'border-red-100 bg-red-50 text-red-600'
                                    : 'border-gray-100 bg-gray-50 text-gray-500',
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
