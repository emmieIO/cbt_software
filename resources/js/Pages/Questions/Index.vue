<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ref, computed, watch } from 'vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import RichContentViewer from '@/components/Questions/RichContentViewer.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Question {
    id: string;
    content: string;
    explanation?: string | null;
    image_url?: string | null;
    type: { value: string; label: string };
    level: { value: string; label: string };
    class_level?: string | null;
    used_count: number;
    topic: { id: string; name: string; class_level?: string | null; subject: { id: string; name: string } };
    creator: { name: string };
    options: Array<{ id: string; content: string; is_correct: boolean }>;
    marking_scheme?: Array<{ point: string; weight: number }>;
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
        class_level?: string;
        overused?: string;
    };
    levels: Array<{ value: string; label: string }>;
    classLevels: Record<string, Array<{ value: string; label: string }>>;
}>();

const page = usePage();
const authUser = computed(() => (page.props.auth as any)?.user);
const canCreateQuestions = computed(() => Boolean(authUser.value?.can_create_questions));
const canEditQuestions = computed(() => Boolean(authUser.value?.can_edit_questions));
const search = ref(props.filters.search || '');
const subjectId = ref(props.filters.subject_id || '');
const level = ref(props.filters.level || '');
const classLevel = ref(props.filters.class_level || '');
const overused = ref(props.filters.overused || '');

watch(
    [search, subjectId, level, classLevel, overused],
    debounce(() => {
        router.get(
            '/questions',
            {
                search: search.value || undefined,
                subject_id: subjectId.value || undefined,
                level: level.value || undefined,
                class_level: classLevel.value || undefined,
                overused: overused.value || undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300),
);

const filteredSubjects = computed(() => props.subjects.filter((s) => !s.level || !level.value || s.level === level.value));
const classLevelOptions = computed(() => props.classLevels[level.value] || []);

watch(level, () => {
    classLevel.value = '';
    subjectId.value = '';
});

const deleteTarget = ref<string | null>(null);
const previewQuestion = ref<Question | null>(null);

const confirmDelete = () => {
    if (deleteTarget.value) {
        router.delete(`/questions/${deleteTarget.value}`, { preserveScroll: true });
        deleteTarget.value = null;
    }
};

const questionTypeValue = (type: Question['type'] | string) => (typeof type === 'string' ? type : type?.value);

const questionTypeLabel = (type: Question['type'] | string) =>
    (typeof type === 'string' ? type : type?.label || type?.value || '').replace(/_/g, ' ');

const questionTypeClass = (type: Question['type'] | string) =>
    ({
        multiple_choice: 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-200',
        short_answer: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200',
        theory: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200',
    })[questionTypeValue(type)] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700/40 dark:text-gray-200';

const levelTag = (lvl: { value: string; label: string }) => {
    const value = typeof lvl === 'string' ? lvl : lvl?.value;
    const map: Record<string, string> = { lp: 'LP', hp: 'HP', js: 'JS', ss: 'SS' };
    return map[value] || value?.toUpperCase();
};

const classTag = (question: Question) => {
    const value = question.class_level || question.topic?.class_level;
    const questionLevel = typeof question.level === 'string' ? question.level : question.level?.value;

    return value ? (props.classLevels[questionLevel]?.find((option) => option.value === value)?.label ?? value) : 'All classes';
};

const markingTotal = (question: Question | null) => question?.marking_scheme?.reduce((sum, item) => sum + Number(item.weight || 0), 0) ?? 0;
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
                    <Link v-if="canCreateQuestions" href="/questions/import" class="btn-secondary">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                            />
                        </svg>
                        Import from Excel
                    </Link>
                    <Link v-if="canCreateQuestions" href="/questions/import/pdf" class="btn-secondary">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12h6m-6 4h6M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z"
                            />
                        </svg>
                        Import from PDF
                    </Link>
                    <Link v-if="canCreateQuestions" href="/questions/batch/create" class="btn-secondary">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Batch Create
                    </Link>
                    <Link
                        v-if="canCreateQuestions"
                        href="/questions/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-primary/90 dark:border-green-900/60 dark:shadow-none"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        New Question
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:flex-row sm:flex-wrap sm:items-center dark:border-green-900/60 dark:bg-green-950/60"
            >
                <div class="relative w-full sm:max-w-md sm:flex-1">
                    <svg
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input v-model="search" type="text" placeholder="Search questions..." class="w-full pl-9" />
                </div>
                <select v-model="level" class="w-full sm:w-auto sm:min-w-[140px]">
                    <option value="">All Levels</option>
                    <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                </select>
                <select v-model="classLevel" class="w-full sm:w-auto sm:min-w-[140px]" :disabled="!level">
                    <option value="">All Classes</option>
                    <option v-for="classOption in classLevelOptions" :key="classOption.value" :value="classOption.value">{{ classOption.label }}</option>
                </select>
                <select v-model="subjectId" class="w-full sm:w-auto sm:min-w-[160px]">
                    <option value="">All Subjects</option>
                    <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <label class="flex cursor-pointer items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300" title="Show only overused questions">
                    <input type="checkbox" v-model="overused" true-value="1" false-value="" class="size-3.5" />
                    Overused only
                </label>
            </div>

            <!-- Questions List -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none"
            >
                <div class="divide-y divide-gray-100 sm:hidden">
                    <div v-for="q in questions.data" :key="q.id" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <RichContentViewer :content="q.content" truncate />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ q.creator?.name || 'Unknown' }}</p>
                            </div>
                            <span class="inline-flex shrink-0 rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                                {{ levelTag(q.level) }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="inline-flex rounded-full px-2.5 py-1 font-medium capitalize" :class="questionTypeClass(q.type)">
                                {{ questionTypeLabel(q.type) }}
                            </span>
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-gray-700 dark:text-gray-200">
                                {{ q.topic?.subject?.name || '-' }}
                            </span>
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-gray-700 dark:text-gray-200">
                                {{ classTag(q) }}
                            </span>
                            <span
                                class="inline-flex rounded-full px-2.5 py-1"
                                :class="q.used_count >= 3 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700 dark:text-gray-200'"
                            >
                                Used {{ q.used_count }}x
                            </span>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-medium">
                            <button @click="previewQuestion = q" class="text-primary hover:underline">View</button>
                            <Link v-if="canEditQuestions" :href="`/questions/${q.id}/edit`" class="text-primary hover:underline">Edit</Link>
                            <button v-if="canEditQuestions" @click="deleteTarget = q.id" class="text-red-600 hover:underline">Delete</button>
                        </div>
                    </div>
                    <div
                        v-if="questions.data.length === 0"
                        class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500"
                    >
                        No questions found.
                        <Link v-if="canCreateQuestions" href="/questions/create" class="font-medium text-primary hover:underline">Create one</Link>.
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="hidden min-w-full divide-y divide-gray-200 sm:table">
                        <thead class="bg-gray-50 dark:bg-green-950/45">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400 dark:text-gray-500"
                                >
                                    Question
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400 dark:text-gray-500"
                                >
                                    Subject
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400 dark:text-gray-500"
                                >
                                    Type
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400 dark:text-gray-500"
                                >
                                    Level
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400 dark:text-gray-500"
                                >
                                    Class
                                </th>
                                <th
                                    class="px-5 py-3 text-center text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400 dark:text-gray-500"
                                >
                                    Use Count
                                </th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400 dark:text-gray-500"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="q in questions.data" :key="q.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/40">
                                <td class="max-w-xs px-5 py-4">
                                    <RichContentViewer :content="q.content" truncate />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ q.creator?.name || 'Unknown' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ q.topic?.subject?.name || '-' }}</td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                        :class="questionTypeClass(q.type)"
                                    >
                                        {{ questionTypeLabel(q.type) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                                        {{ levelTag(q.level) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ classTag(q) }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1"
                                        :class="q.used_count >= 3 ? 'font-bold text-red-600' : 'text-gray-600 dark:text-gray-300'"
                                    >
                                        {{ q.used_count }}
                                        <span
                                            v-if="q.used_count >= 3"
                                            class="inline-flex items-center rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-700"
                                            >Overused</span
                                        >
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="previewQuestion = q" class="text-xs font-medium text-primary hover:underline">View</button>
                                        <Link v-if="canEditQuestions" :href="`/questions/${q.id}/edit`" class="text-xs font-medium text-primary hover:underline">Edit</Link>
                                        <button v-if="canEditQuestions" @click="deleteTarget = q.id" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="questions.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                                    No questions found.
                                    <Link v-if="canCreateQuestions" href="/questions/create" class="font-medium text-primary hover:underline">Create one</Link>.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="questions.last_page > 1"
                    class="flex flex-col gap-3 border-t border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-green-900/60"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                        Page {{ questions.current_page }} of {{ questions.last_page }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-if="questions.prev_page_url"
                            :href="questions.prev_page_url"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 dark:border-green-900/60 dark:bg-green-950/45 dark:text-gray-300"
                            >Previous</Link
                        >
                        <Link
                            v-if="questions.next_page_url"
                            :href="questions.next_page_url"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 dark:border-green-900/60 dark:bg-green-950/45 dark:text-gray-300"
                            >Next</Link
                        >
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

    <div v-if="previewQuestion" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-6">
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="previewQuestion = null"></div>
        <section
            class="relative flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-green-900/60 dark:bg-green-950"
        >
            <header class="border-b border-gray-200 bg-gray-50/80 px-5 py-4 sm:px-6 dark:border-green-900/60 dark:bg-green-950/70">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold tracking-wide text-primary uppercase">Question Preview</p>
                        <h2 class="mt-1 truncate text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ previewQuestion.topic?.subject?.name || '-' }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ previewQuestion.topic?.name || '-' }} · {{ previewQuestion.creator?.name || 'Unknown' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-xl leading-none text-gray-500 hover:bg-gray-50 hover:text-gray-800 dark:border-green-900/60 dark:bg-green-950/55 dark:text-gray-200 dark:hover:bg-green-900/55"
                        aria-label="Close preview"
                        @click="previewQuestion = null"
                    >
                        ×
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-green-900/60 dark:bg-green-950/55">
                        <p class="font-semibold text-gray-400 uppercase">Type</p>
                        <span
                            class="mt-1 inline-flex rounded-full px-2.5 py-1 font-semibold capitalize"
                            :class="questionTypeClass(previewQuestion.type)"
                        >
                            {{ questionTypeLabel(previewQuestion.type) }}
                        </span>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-green-900/60 dark:bg-green-950/55">
                        <p class="font-semibold text-gray-400 uppercase">Level</p>
                        <span class="mt-1 inline-flex rounded-full bg-primary/10 px-2.5 py-1 font-semibold text-primary">
                            {{ levelTag(previewQuestion.level) }}
                        </span>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-green-900/60 dark:bg-green-950/55">
                        <p class="font-semibold text-gray-400 uppercase">Class</p>
                        <p class="mt-1 text-sm font-bold text-gray-800 dark:text-gray-100">{{ classTag(previewQuestion) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-green-900/60 dark:bg-green-950/55">
                        <p class="font-semibold text-gray-400 uppercase">Usage</p>
                        <p class="mt-1 text-sm font-bold text-gray-800 dark:text-gray-100">{{ previewQuestion.used_count }} time(s)</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-green-900/60 dark:bg-green-950/55">
                        <p class="font-semibold text-gray-400 uppercase">Marks</p>
                        <p class="mt-1 text-sm font-bold text-gray-800 dark:text-gray-100">
                            {{ questionTypeValue(previewQuestion.type) === 'multiple_choice' ? 1 : markingTotal(previewQuestion) }}
                        </p>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <main class="space-y-5">
                        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-green-900/60 dark:bg-green-950/55">
                            <div class="border-b border-gray-200 px-4 py-3 dark:border-green-900/60">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Question</h3>
                            </div>
                            <div class="p-4">
                                <div class="rounded-lg bg-gray-50 p-4 dark:bg-green-950/55">
                                    <RichContentViewer :content="previewQuestion.content" />
                                </div>
                                <img
                                    v-if="previewQuestion.image_url"
                                    :src="previewQuestion.image_url"
                                    alt="Question image"
                                    class="mt-4 max-h-80 w-full rounded-lg border border-gray-200 object-contain dark:border-green-900/60"
                                />
                            </div>
                        </section>

                        <section
                            v-if="questionTypeValue(previewQuestion.type) === 'multiple_choice'"
                            class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-green-900/60 dark:bg-green-950/55"
                        >
                            <div class="border-b border-gray-200 px-4 py-3 dark:border-green-900/60">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Answer Options</h3>
                            </div>
                            <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">
                                <div
                                    v-for="(option, index) in previewQuestion.options"
                                    :key="option.id"
                                    class="flex gap-3 rounded-lg border p-3 transition-colors"
                                    :class="
                                        option.is_correct
                                            ? 'border-emerald-300 bg-emerald-50 ring-1 ring-emerald-200 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:ring-emerald-500/20'
                                            : 'border-gray-200 bg-white dark:border-green-900/60 dark:bg-green-950/55'
                                    "
                                >
                                    <span
                                        class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                        :class="
                                            option.is_correct
                                                ? 'bg-emerald-600 text-white dark:bg-emerald-500'
                                                : 'bg-gray-100 text-gray-600 dark:bg-green-900/70 dark:text-gray-200'
                                        "
                                    >
                                        {{ ['A', 'B', 'C', 'D'][index] }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <RichContentViewer :content="option.content" />
                                        <p
                                            v-if="option.is_correct"
                                            class="mt-2 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold tracking-wide text-emerald-700 uppercase dark:bg-emerald-500/15 dark:text-emerald-200"
                                        >
                                            Correct answer
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section
                            v-if="previewQuestion.explanation"
                            class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-green-900/60 dark:bg-green-950/55"
                        >
                            <div class="border-b border-gray-200 px-4 py-3 dark:border-green-900/60">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Explanation</h3>
                            </div>
                            <div class="p-4">
                                <RichContentViewer :content="previewQuestion.explanation" />
                            </div>
                        </section>
                    </main>

                    <aside class="space-y-5">
                        <section
                            v-if="questionTypeValue(previewQuestion.type) !== 'multiple_choice'"
                            class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-green-900/60 dark:bg-green-950/55"
                        >
                            <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-green-900/60">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Marking Scheme</h3>
                                <span class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
                                    {{ markingTotal(previewQuestion) }} marks
                                </span>
                            </div>
                            <div class="space-y-2 p-4">
                                <div
                                    v-for="(item, index) in previewQuestion.marking_scheme || []"
                                    :key="index"
                                    class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-green-900/60 dark:bg-green-950/55"
                                >
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <span class="text-xs font-bold text-gray-400">Point {{ index + 1 }}</span>
                                        <span class="shrink-0 rounded-full bg-primary/10 px-2 py-1 text-xs font-semibold text-primary">
                                            {{ item.weight }} mark(s)
                                        </span>
                                    </div>
                                    <RichContentViewer :content="item.point" />
                                </div>
                                <p v-if="!(previewQuestion.marking_scheme || []).length" class="text-sm text-gray-500 dark:text-gray-400">
                                    No marking scheme provided.
                                </p>
                            </div>
                        </section>

                        <section class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-green-900/60 dark:bg-green-950/55">
                            <h3 class="text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400">Details</h3>
                            <dl class="mt-3 space-y-3">
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase">Subject</dt>
                                    <dd class="mt-0.5 font-medium text-gray-800 dark:text-gray-100">
                                        {{ previewQuestion.topic?.subject?.name || '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase">Topic</dt>
                                    <dd class="mt-0.5 font-medium text-gray-800 dark:text-gray-100">{{ previewQuestion.topic?.name || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase">Created By</dt>
                                    <dd class="mt-0.5 font-medium text-gray-800 dark:text-gray-100">
                                        {{ previewQuestion.creator?.name || 'Unknown' }}
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    </aside>
                </div>
            </div>

            <footer
                class="flex flex-col-reverse gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6 dark:border-green-900/60 dark:bg-green-950/70"
            >
                <button type="button" class="btn-secondary justify-center" @click="previewQuestion = null">Close</button>
                    <Link v-if="canEditQuestions" :href="`/questions/${previewQuestion.id}/edit`" class="btn-primary justify-center">Edit Question</Link>
            </footer>
        </section>
    </div>
</template>
