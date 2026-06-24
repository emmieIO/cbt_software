<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    exams: {
        data: Array<{
            id: string;
            title: string;
            academic_session: string | null;
            subject_name: string;
            level: string;
            total_marks: number;
            questions_count: number;
            created_at: string;
            creator: { name: string };
            topics: string[];
        }>;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();

const selectedExamId = ref<string | null>(null);

const selectedExam = computed(() => props.exams.data.find((exam) => exam.id === selectedExamId.value) ?? null);

const openTopicsModal = (examId: string) => {
    selectedExamId.value = examId;
};

const closeTopicsModal = () => {
    selectedExamId.value = null;
};
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
                <Link href="/exams/create" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm dark:shadow-none dark:border-green-900/60 hover:bg-primary/90">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    New Exam
                </Link>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-green-900/60 bg-white dark:bg-green-950/60 shadow-sm dark:shadow-none dark:border-green-900/60">
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-green-950/45">
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
                        <tr v-for="exam in exams.data" :key="exam.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/40">
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ exam.title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">
                                    {{ exam.academic_session || 'Session not set' }} · by {{ exam.creator?.name }}
                                </p>
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
                                    <button
                                        type="button"
                                        @click="openTopicsModal(exam.id)"
                                        class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary transition hover:bg-primary/15"
                                    >
                                        <span>Topics</span>
                                        <span class="rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-semibold dark:bg-green-950/70">
                                            {{ exam.topics.length }}
                                        </span>
                                    </button>
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
                    <Link v-if="exams.prev_page_url" :href="exams.prev_page_url" class="rounded-lg border border-gray-200 dark:border-green-900/60 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-green-950/45">Previous</Link>
                    <Link v-if="exams.next_page_url" :href="exams.next_page_url" class="rounded-lg border border-gray-200 dark:border-green-900/60 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-green-950/45">Next</Link>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="selectedExam" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
                    <div class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" @click="closeTopicsModal"></div>

                    <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-green-900/60 dark:bg-green-950/70">
                        <div class="border-b border-gray-200 px-6 py-5 dark:border-green-900/60">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary">Topics Covered</p>
                                    <h2 class="mt-2 text-xl font-bold text-gray-900 dark:text-gray-100">{{ selectedExam.title }}</h2>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ selectedExam.subject_name }} · {{ selectedExam.level }} · {{ selectedExam.topics.length }} topics
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="closeTopicsModal"
                                    class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-green-900/60 dark:hover:text-gray-200"
                                >
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-6">
                            <div v-if="selectedExam.topics.length" class="flex flex-wrap gap-3">
                                <div
                                    v-for="topic in selectedExam.topics"
                                    :key="topic"
                                    class="inline-flex items-center rounded-full border border-primary/15 bg-primary/8 px-4 py-2 text-sm font-medium text-primary dark:border-primary/20 dark:bg-primary/10"
                                >
                                    {{ topic }}
                                </div>
                            </div>

                            <div v-else class="rounded-xl border border-dashed border-gray-200 px-6 py-10 text-center dark:border-green-900/60">
                                <p class="text-sm text-gray-500 dark:text-gray-400">No topic labels were found for this exam.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
