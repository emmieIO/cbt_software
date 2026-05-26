<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    stats: {
        totalQuestions: number;
        totalSubjects: number;
        totalTopics: number;
        totalUsers: number;
        flaggedQuestions: number;
    };
    recentQuestions: Array<{
        id: string;
        content: string;
        type: string;
        used_count: number;
        subject: string;
        created_by: string;
    }>;
}>();

const kpis = [
    { label: 'Total Questions', value: props.stats.totalQuestions, color: 'text-primary' },
    { label: 'Subjects', value: props.stats.totalSubjects, color: 'text-blue-600' },
    { label: 'Topics', value: props.stats.totalTopics, color: 'text-purple-600' },
    { label: 'Flagged (Overused)', value: props.stats.flaggedQuestions, color: 'text-red-600' },
];
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Overview of the question bank system.</p>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="kpi in kpis" :key="kpi.label" class="rounded-xl border border-gray-200 dark:border-green-900/60 bg-white dark:bg-green-950/60 p-5 shadow-sm dark:shadow-none dark:border-green-900/60">
                    <p class="text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">{{ kpi.label }}</p>
                    <p class="mt-2 text-3xl font-bold" :class="kpi.color">{{ kpi.value }}</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Link href="/questions/create" class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 dark:border-green-900/60 bg-white dark:bg-green-950/60 p-4 shadow-sm dark:shadow-none dark:border-green-900/60 transition-all hover:border-primary/30 hover:shadow-md">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Add Question</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Create a new MCQ or theory question</p>
                    </div>
                </Link>

                <Link href="/questions" class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 dark:border-green-900/60 bg-white dark:bg-green-950/60 p-4 shadow-sm dark:shadow-none dark:border-green-900/60 transition-all hover:border-primary/30 hover:shadow-md">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Browse Questions</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">View, edit, and manage the question bank</p>
                    </div>
                </Link>

                <Link href="/subjects" class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 dark:border-green-900/60 bg-white dark:bg-green-950/60 p-4 shadow-sm dark:shadow-none dark:border-green-900/60 transition-all hover:border-primary/30 hover:shadow-md">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Subjects & Topics</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Manage curriculum subjects and topics</p>
                    </div>
                </Link>
            </div>

            <!-- Recent Questions -->
            <div class="rounded-xl border border-gray-200 dark:border-green-900/60 bg-white dark:bg-green-950/60 shadow-sm dark:shadow-none dark:border-green-900/60">
                <div class="border-b border-gray-200 dark:border-green-900/60 px-5 py-4">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Recently Added Questions</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <div v-for="q in recentQuestions" :key="q.id" class="flex items-center gap-4 px-5 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ q.content }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">
                                {{ q.subject || 'No subject' }}
                                <span class="mx-1">&middot;</span>
                                <span class="capitalize">{{ q.type.replace('_', ' ') }}</span>
                                <span class="mx-1">&middot;</span>
                                Used {{ q.used_count }} times
                            </p>
                        </div>
                        <Link :href="`/questions/${q.id}/edit`" class="shrink-0 text-xs font-medium text-primary hover:underline">
                            Edit
                        </Link>
                    </div>
                    <p v-if="recentQuestions.length === 0" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                        No questions yet.
                        <Link href="/questions/create" class="font-medium text-primary hover:underline">Create the first one</Link>.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
