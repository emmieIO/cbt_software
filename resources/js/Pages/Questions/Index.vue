<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ref, computed, watch } from 'vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Question {
    id: string;
    content: string;
    type: { value: string; label: string };
    level: { value: string; label: string };
    used_count: number;
    topic: { id: string; name: string; subject: { id: string; name: string } };
    creator: { name: string };
    options: Array<{ id: string; content: string; is_correct: boolean }>;
    created_at: string;
}

interface Subject {
    id: string;
    name: string;
    level?: string;
    topics: Array<{ id: string; name: string }>;
}

interface QuestionsPage {
    data: Question[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps<{
    questions: QuestionsPage;
    subjects: Subject[];
    filters: {
        search?: string;
        subject_id?: string;
        level?: string;
        overused?: string;
    };
    levels: Array<{ value: string; label: string }>;
}>();

const search = ref(props.filters.search || '');
const subjectId = ref(props.filters.subject_id || '');
const level = ref(props.filters.level || '');
const overused = ref(props.filters.overused || '');

watch([search, subjectId, level, overused], debounce(() => {
    router.get('/questions', {
        search: search.value || undefined,
        subject_id: subjectId.value || undefined,
        level: level.value || undefined,
        overused: overused.value || undefined,
    }, { preserveState: true, replace: true });
}, 300));

const filteredSubjects = computed(() => props.subjects.filter(s => !s.level || !level.value || s.level === level.value));

const deleteTarget = ref<string | null>(null);

const confirmDelete = () => {
    if (deleteTarget.value) {
        router.delete(`/questions/${deleteTarget.value}`, { preserveScroll: true });
        deleteTarget.value = null;
    }
};

const levelTag = (lvl: { value: string; label: string }) => {
    const value = typeof lvl === 'string' ? lvl : lvl?.value;
    const map: Record<string, string> = { lp: 'LP', hp: 'HP', js: 'JS', ss: 'SS' };
    return map[value] || value?.toUpperCase();
};
</script>

<template>
    <AppLayout>
        <Head title="Questions" />

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Question Bank</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Browse, create, and manage assessment questions.</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end">
                    <Link href="/questions/import" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:bg-gray-800/50">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        Import from Excel
                    </Link>
                    <Link href="/questions/batch/create" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:bg-gray-800/50">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Batch Create
                    </Link>
                    <Link href="/questions/create" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm dark:shadow-none dark:border-gray-700 transition-all hover:bg-primary/90">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        New Question
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 sm:flex-row sm:flex-wrap sm:items-center">
                <div class="relative w-full sm:max-w-md sm:flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input v-model="search" type="text" placeholder="Search questions..." class="w-full pl-9" />
                </div>
                <select v-model="level" class="w-full sm:w-auto sm:min-w-[140px]">
                    <option value="">All Levels</option>
                    <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                </select>
                <select v-model="subjectId" class="w-full sm:w-auto sm:min-w-[160px]">
                    <option value="">All Subjects</option>
                    <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <label class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300 cursor-pointer" title="Show only overused questions">
                    <input type="checkbox" v-model="overused" true-value="1" false-value="" class="size-3.5" />
                    Overused only
                </label>
            </div>

            <!-- Questions List -->
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-none dark:border-gray-700">
                <div class="divide-y divide-gray-100 sm:hidden">
                    <div v-for="q in questions.data" :key="q.id" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100" v-text="q.content" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ q.creator?.name || 'Unknown' }}</p>
                            </div>
                            <span class="inline-flex shrink-0 rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                                {{ levelTag(q.level) }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-gray-700 dark:text-gray-200 capitalize">
                                {{ q.type?.value?.replace('_', ' ') || q.type }}
                            </span>
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-gray-700 dark:text-gray-200">
                                {{ q.topic?.subject?.name || '-' }}
                            </span>
                            <span class="inline-flex rounded-full px-2.5 py-1" :class="q.used_count >= 3 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700 dark:text-gray-200'">
                                Used {{ q.used_count }}x
                            </span>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-medium">
                            <Link :href="`/questions/${q.id}/edit`" class="text-primary hover:underline">Edit</Link>
                            <button @click="deleteTarget = q.id" class="text-red-600 hover:underline">Delete</button>
                        </div>
                    </div>
                    <div v-if="questions.data.length === 0" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                        No questions found.
                        <Link href="/questions/create" class="font-medium text-primary hover:underline">Create one</Link>.
                    </div>
                </div>

                <div class="overflow-x-auto"><table class="hidden min-w-full divide-y divide-gray-200 sm:table">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Question</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Subject</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Type</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Level</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Use Count</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="q in questions.data" :key="q.id" class="hover:bg-gray-50 dark:bg-gray-800/50/50">
                            <td class="max-w-xs px-5 py-4">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100" v-text="q.content" />
                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ q.creator?.name || 'Unknown' }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ q.topic?.subject?.name || '-' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-200 capitalize">
                                    {{ q.type?.value?.replace('_', ' ') || q.type }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                                    {{ levelTag(q.level) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center gap-1" :class="q.used_count >= 3 ? 'font-bold text-red-600' : 'text-gray-600 dark:text-gray-300'">
                                    {{ q.used_count }}
                                    <span v-if="q.used_count >= 3" class="inline-flex items-center rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-700">Overused</span>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/questions/${q.id}/edit`" class="text-xs font-medium text-primary hover:underline">Edit</Link>
                                    <button @click="deleteTarget = q.id" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="questions.data.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                                No questions found.
                                <Link href="/questions/create" class="font-medium text-primary hover:underline">Create one</Link>.
                            </td>
                        </tr>
                    </tbody>
                </table></div>

                <!-- Pagination -->
                <div v-if="questions.last_page > 1" class="flex flex-col gap-3 border-t border-gray-200 dark:border-gray-700 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                        Page {{ questions.current_page }} of {{ questions.last_page }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link v-if="questions.prev_page_url" :href="questions.prev_page_url" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50">Previous</Link>
                        <Link v-if="questions.next_page_url" :href="questions.next_page_url" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <ConfirmationModal
        :show="!!deleteTarget"
        title="Delete Question"
        message="This action cannot be undone. All data associated with this question will be permanently removed."
        confirm-label="Delete"
        variant="danger"
        @close="deleteTarget = null"
        @confirm="confirmDelete"
    />
</template>
