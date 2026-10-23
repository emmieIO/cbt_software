<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import RichContentViewer from '@/components/Questions/RichContentViewer.vue';
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

const page = usePage();
const authUser = computed(() => (page.props.auth as any)?.user);
const canCreateQuestions = computed(() => Boolean(authUser.value?.can_create_questions));
const canEditQuestions = computed(() => Boolean(authUser.value?.can_edit_questions));

const kpis = [
    { label: 'Total Questions', value: props.stats.totalQuestions, color: 'bg-primary' },
    { label: 'Subjects', value: props.stats.totalSubjects, color: 'bg-blue-500' },
    { label: 'Topics', value: props.stats.totalTopics, color: 'bg-amber-500' },
    { label: 'Overused', value: props.stats.flaggedQuestions, color: 'bg-red-500' },
];

const questionTypeClass = (type: string) =>
    ({
        multiple_choice: 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-200',
        short_answer: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200',
        theory: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200',
    })[type] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700/40 dark:text-gray-200';
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-primary uppercase">Workspace overview</p>
                    <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor the question bank and continue common tasks.</p>
                </div>
                <Link v-if="canCreateQuestions" href="/questions/create" class="btn-primary justify-center sm:justify-start">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    New Question
                </Link>
            </div>

            <div
                class="grid grid-cols-2 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm lg:grid-cols-4 dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none"
            >
                <div
                    v-for="kpi in kpis"
                    :key="kpi.label"
                    class="border-r border-b border-gray-200 px-4 py-5 last:border-r-0 lg:border-b-0 dark:border-green-900/60"
                >
                    <div class="flex items-center gap-2">
                        <span class="size-2 rounded-full" :class="kpi.color"></span>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ kpi.label }}</p>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ kpi.value }}</p>
                </div>
            </div>

            <div
                class="grid grid-cols-1 gap-px overflow-hidden rounded-lg border border-gray-200 bg-gray-200 sm:grid-cols-2 dark:border-green-900/60 dark:bg-green-900/60"
            >
                <Link
                    href="/questions"
                    class="group flex items-center gap-3 bg-white p-4 hover:bg-gray-50 dark:bg-green-950/60 dark:hover:bg-green-950"
                >
                    <div class="flex size-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Browse Questions</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Search, review, and manage questions</p>
                    </div>
                    <svg
                        class="ml-auto size-4 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-primary"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>

                <Link
                    href="/subjects"
                    class="group flex items-center gap-3 bg-white p-4 hover:bg-gray-50 dark:bg-green-950/60 dark:hover:bg-green-950"
                >
                    <div
                        class="flex size-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                            />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Subjects & Topics</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Maintain curriculum classifications</p>
                    </div>
                    <svg
                        class="ml-auto size-4 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-primary"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-green-900/60">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Recently added</h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Latest questions added to the bank</p>
                    </div>
                    <Link href="/questions" class="text-xs font-semibold text-primary hover:underline">View all</Link>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-green-900/60">
                    <div v-for="q in recentQuestions" :key="q.id" class="flex items-center gap-4 px-5 py-3.5">
                        <div class="min-w-0 flex-1">
                            <RichContentViewer class="font-medium" :content="q.content" truncate />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ q.subject || 'No subject' }}
                                <span class="mx-1">&middot;</span>
                                <span class="inline-flex rounded-full px-2 py-0.5 font-medium capitalize" :class="questionTypeClass(q.type)">
                                    {{ q.type.replace('_', ' ') }}
                                </span>
                                <span class="mx-1">&middot;</span>
                                Used {{ q.used_count }} times
                            </p>
                        </div>
                        <Link
                            v-if="canEditQuestions"
                            :href="`/questions/${q.id}/edit`"
                            class="shrink-0 text-xs font-medium text-primary hover:underline"
                        >
                            Edit
                        </Link>
                    </div>
                    <p v-if="recentQuestions.length === 0" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        No questions yet.
                        <Link v-if="canCreateQuestions" href="/questions/create" class="font-medium text-primary hover:underline"
                            >Create the first one</Link
                        >.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
