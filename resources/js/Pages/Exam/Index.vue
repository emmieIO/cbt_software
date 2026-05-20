<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
    exams: {
        data: Array<{
            id: string;
            title: string;
            subject_name: string;
            level: string;
            total_marks: number;
            questions_count: number;
            created_at: string;
            creator: { name: string };
        }>;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();
</script>

<template>
    <AppLayout>
        <Head title="Exams" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Examinations</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">View and manage previously created exams.</p>
                </div>
                <Link href="/exams/create" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm dark:shadow-none dark:border-gray-700 hover:bg-primary/90">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    New Exam
                </Link>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-none dark:border-gray-700">
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Exam</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Subject</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Level</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Questions</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Marks</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Date</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="exam in exams.data" :key="exam.id" class="hover:bg-gray-50 dark:bg-gray-800/50/50">
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ exam.title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">by {{ exam.creator?.name }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ exam.subject_name }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary uppercase">{{ exam.level }}</span>
                            </td>
                            <td class="px-5 py-4 text-center text-sm text-gray-600 dark:text-gray-300">{{ exam.questions_count }}</td>
                            <td class="px-5 py-4 text-center text-sm font-semibold text-gray-900 dark:text-gray-100">{{ exam.total_marks }}</td>
                            <td class="px-5 py-4 text-right text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ exam.created_at }}</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/exams/${exam.id}`" class="text-xs font-medium text-primary hover:underline">View</Link>
                                    <Link :href="`/exams/${exam.id}/edit-questions`" class="text-xs font-medium text-gray-700 hover:underline dark:text-gray-300">Edit Questions</Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="exams.data.length === 0">
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                                No exams yet.
                                <Link href="/exams/create" class="font-medium text-primary hover:underline">Create one</Link>.
                            </td>
                        </tr>
                    </tbody>
                </table></div>
            </div>

            <div v-if="exams.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Page {{ exams.current_page }} of {{ exams.last_page }}</p>
                <div class="flex gap-2">
                    <Link v-if="exams.prev_page_url" :href="exams.prev_page_url" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50">Previous</Link>
                    <Link v-if="exams.next_page_url" :href="exams.next_page_url" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50">Next</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
