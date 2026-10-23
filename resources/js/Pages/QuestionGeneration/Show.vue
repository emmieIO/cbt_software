<script setup lang="ts">
import { Head, Link, router, useForm, usePoll, useRemember } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { update, review } from '@/actions/App/Http/Controllers/GeneratedQuestionDraftController';
import { index as bankIndex } from '@/actions/App/Http/Controllers/QuestionController';
import { index, retry, importDrafts } from '@/actions/App/Http/Controllers/QuestionGenerationController';
import RichContentEditor from '@/components/Questions/RichContentEditor.vue';
import RichContentViewer from '@/components/Questions/RichContentViewer.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Source {
    chunk_id: string;
    document_id: string;
    document_title: string;
    page_start: number;
    page_end: number;
    similarity: number;
    content: string;
}
interface Draft {
    reviewer: { name: string } | null;
    editor: { name: string } | null;
    importer: { name: string } | null;
    reviewed_at: string | null;
    review_status: 'draft' | 'approved' | 'rejected' | 'imported';
    revision: number;
    question_id: string | null;
    imported_at: string | null;
    import_errors: string[] | null;
    duplicate_warnings: Array<{ id: string; content: string }>;
    id: string;
    sequence: number;
    type: string;
    difficulty: string;
    content: string;
    explanation: string | null;
    expected_answer: string | null;
    options: Array<{ content: string; is_correct: boolean }> | null;
    marking_scheme: Array<{ point: string; weight: number }> | null;
    source_references: Source[];
    validation_errors: string[] | null;
    warnings: string[] | null;
    is_valid: boolean;
    edited_at: string | null;
}
interface Run {
    class_level: string;
    import_status: string;
    last_import_summary: {
        requested: number;
        imported: number;
        already_imported: number;
        skipped: number;
        failed: number;
        results: Array<{ status: string; draft_id: string; question_id?: string; errors?: string[] }>;
    } | null;
    id: string;
    status: string;
    requested_count: number;
    difficulty: string;
    type_distribution: Record<string, number>;
    additional_instructions: string | null;
    error: string | null;
    validation_errors: string[] | null;
    warnings: string[] | null;
    created_at: string;
    subject: { name: string };
    topic: { name: string };
    subtopic_name?: string | null;
    drafts: Draft[];
}
const props = defineProps<{ run: Run; canImport: boolean; canEditBank: boolean }>();
const selectedImports = ref(useRemember<string[]>([], `draft-import-${props.run.id}`));
const answerVerified = ref(false);
const reviewForm = useForm({ action: 'approve', revision: 0, answers_verified: false });
const importForm = useForm({ draft_ids: [] as string[] });
const approved = computed(() => props.run.drafts.filter((draft) => draft.review_status === 'approved'));
const reviewDraft = (draft: Draft, action: 'approve' | 'reject') => {
    Object.assign(reviewForm, { action, revision: draft.revision, answers_verified: answerVerified.value });
    reviewForm.post(review.url(draft.id), {
        preserveScroll: true,
        onSuccess: () => {
            answerVerified.value = false;
        },
    });
};
const importSelected = () => {
    importForm.draft_ids = [...selectedImports.value];
    importForm.post(importDrafts.url(props.run.id), {
        preserveScroll: true,
        onSuccess: () => {
            selectedImports.value = [];
        },
    });
};
const className = computed(() => {
    const value = Number(props.run.class_level);
    return value <= 6 ? `Primary ${value}` : value <= 9 ? `JSS ${value - 6}` : `SS ${value - 9}`;
});
const editing = ref<string | null>(null);
const selectedDraftId = ref<string | null>(props.run.drafts[0]?.id ?? null);
const visibleDrafts = computed(() => props.run.drafts.filter((draft) => draft.id === selectedDraftId.value));
const validCount = computed(() => props.run.drafts.filter((draft) => draft.is_valid).length);
watch(
    () => props.run.drafts,
    (drafts) => {
        if (!drafts.some((draft) => draft.id === selectedDraftId.value)) selectedDraftId.value = drafts[0]?.id ?? null;
        selectedImports.value = selectedImports.value.filter((id) => drafts.some((draft) => draft.id === id && draft.review_status === 'approved'));
        answerVerified.value = false;
    },
);
const selectDraft = (draft: Draft) => {
    selectedDraftId.value = draft.id;
    editing.value = null;
    answerVerified.value = false;
    reviewForm.clearErrors();
};
const plainText = (content: string) =>
    content
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
const terminal = computed(() => ['completed', 'partial', 'failed'].includes(props.run.status));
const { start, stop } = usePoll(2000, { only: ['run'] }, { autoStart: false });
watch(terminal, (done) => (done ? stop() : start()), { immediate: true });

const form = useForm({
    revision: 1,
    type: 'multiple_choice',
    difficulty: 'medium',
    content: '',
    explanation: '',
    expected_answer: '',
    options: [] as Array<{ content: string; is_correct: boolean }>,
    marking_scheme: [] as Array<{ point: string; weight: number }>,
});
const beginEdit = (draft: Draft) => {
    editing.value = draft.id;
    form.clearErrors();
    Object.assign(form, {
        revision: draft.revision,
        type: draft.type,
        difficulty: draft.difficulty,
        content: draft.content,
        explanation: draft.explanation ?? '',
        expected_answer: draft.expected_answer ?? '',
        options: draft.options?.map((item) => ({ ...item })) ?? Array.from({ length: 4 }, () => ({ content: '', is_correct: false })),
        marking_scheme: draft.marking_scheme?.map((item) => ({ ...item })) ?? [{ point: '', weight: 1 }],
    });
};
const save = (draft: Draft) =>
    form
        .transform((data) => ({
            ...data,
            options: data.type === 'multiple_choice' ? data.options : null,
            marking_scheme: data.type === 'multiple_choice' ? null : data.marking_scheme,
            expected_answer: data.type === 'multiple_choice' ? null : data.expected_answer,
        }))
        .patch(update.url(draft.id), {
            preserveScroll: true,
            onSuccess: () => {
                editing.value = null;
            },
        });
const retryRun = () => router.post(retry.url(props.run.id), {}, { preserveScroll: true });
const setCorrect = (index: number) =>
    form.options.forEach((option, optionIndex) => {
        option.is_correct = optionIndex === index;
    });
const titleCase = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
const reviewStatusColor = (status: Draft['review_status']) =>
    ({ draft: 'text-yellow-700', approved: 'text-emerald-700', rejected: 'text-red-700', imported: 'text-blue-700' })[status];
const reviewStatusDot = (status: Draft['review_status']) =>
    ({ draft: 'bg-yellow-500', approved: 'bg-emerald-600', rejected: 'bg-red-600', imported: 'bg-blue-600' })[status];
const pageRange = (source: Source) =>
    source.page_start === source.page_end ? `Page ${source.page_start}` : `Pages ${source.page_start}-${source.page_end}`;
</script>

<template>
    <AppLayout>
        <Head :title="`${run.topic.name} drafts`" />
        <div class="draft-workspace mx-auto max-w-7xl space-y-6">
            <header>
                <Link :href="index.url()" class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-gray-500 hover:text-primary"
                    >← AI Question Generator</Link
                >
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-bold tracking-wide text-primary uppercase">{{ run.subject.name }}</p>
                        <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ run.topic.name }}</h1>
                        <p v-if="run.subtopic_name" class="mt-1 text-sm font-semibold text-gray-600">{{ run.subtopic_name }}</p>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ className }} · {{ run.requested_count }} questions · {{ titleCase(run.difficulty) }} difficulty
                        </p>
                    </div>
                    <span class="inline-flex self-start rounded-md bg-white px-3 py-1.5 text-xs font-bold text-gray-700 ring-1 ring-gray-200">{{
                        titleCase(run.status)
                    }}</span>
                </div>
            </header>

            <div v-if="!terminal" class="border-l-4 border-blue-500 bg-blue-50 px-4 py-4 text-sm text-blue-800">
                <p class="font-semibold">Generation in progress</p>
                <p class="mt-1">The page refreshes automatically while approved knowledge is retrieved and drafts are checked.</p>
            </div>
            <div v-if="run.error" class="border-l-4 border-red-500 bg-red-50 px-4 py-4 text-sm text-red-800">
                <p class="font-semibold">Generation could not be completed</p>
                <p class="mt-1">{{ run.error }}</p>
                <button
                    v-if="run.status === 'failed' && !run.drafts.length"
                    type="button"
                    class="mt-3 cursor-pointer font-bold underline"
                    @click="retryRun"
                >
                    Retry generation
                </button>
            </div>

            <section v-if="run.last_import_summary" aria-label="Import summary" class="border-y border-gray-200 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-600">
                        <strong class="font-semibold text-gray-900">{{ run.last_import_summary.imported }} questions imported</strong>
                        <span v-if="run.last_import_summary.already_imported">
                            · {{ run.last_import_summary.already_imported }} already imported</span
                        >
                        <span v-if="run.last_import_summary.skipped"> · {{ run.last_import_summary.skipped }} skipped</span>
                        <span v-if="run.last_import_summary.failed" class="text-red-700"> · {{ run.last_import_summary.failed }} failed</span>
                    </p>
                    <Link
                        v-if="run.last_import_summary.imported || run.last_import_summary.already_imported"
                        :href="bankIndex.url({ query: { generation_run_id: run.id } })"
                        class="text-sm font-semibold text-primary hover:underline"
                        >View imported questions</Link
                    >
                </div>
                <details v-if="run.last_import_summary.failed || run.last_import_summary.skipped" class="mt-3 text-sm">
                    <summary class="cursor-pointer font-semibold text-gray-600">Review import issues</summary>
                    <template v-for="result in run.last_import_summary.results" :key="result.draft_id">
                        <p v-for="error in result.errors ?? []" :key="error" class="mt-2 text-red-700">{{ error }}</p>
                    </template>
                </details>
            </section>
            <div v-if="run.drafts.length && canImport" class="flex flex-wrap items-center justify-between gap-3 border-y border-gray-200 py-3">
                <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-gray-700"
                    ><input
                        type="checkbox"
                        :checked="approved.length > 0 && selectedImports.length === approved.length"
                        :disabled="!approved.length"
                        @change="selectedImports = ($event.target as HTMLInputElement).checked ? approved.map((draft) => draft.id) : []"
                    />Select approved ({{ approved.length }})</label
                >
                <button
                    type="button"
                    class="btn-primary cursor-pointer rounded-md"
                    :disabled="!selectedImports.length || importForm.processing || editing !== null"
                    @click="importSelected"
                >
                    {{ importForm.processing ? 'Importing...' : `Import selected (${selectedImports.length})` }}
                </button>
                <p v-for="(error, key) in importForm.errors" :key="key" class="w-full text-sm text-red-700">{{ error }}</p>
            </div>
            <div v-if="run.validation_errors?.length" class="border-l-4 border-amber-500 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                <p class="font-semibold">The result needs review</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li v-for="error in run.validation_errors" :key="error">{{ error }}</li>
                </ul>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-y border-gray-200 py-3 text-xs text-gray-500">
                <span
                    ><strong class="text-gray-800">{{ run.drafts.length }} drafts</strong><span class="mx-3 text-gray-300">/</span
                    >{{ validCount }} passed checks<span class="mx-3 text-gray-300">/</span>{{ run.drafts.length - validCount }} need correction</span
                >
                <span>Review answers and marking before use.</span>
            </div>

            <div v-if="run.drafts.length" class="grid items-start gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
                <nav class="min-w-0 lg:sticky lg:top-5 lg:flex lg:max-h-[calc(100dvh-9rem)] lg:flex-col" aria-label="Generated questions">
                    <h2 class="mb-3 shrink-0 text-xs font-bold text-gray-400 uppercase">Questions</h2>
                    <div
                        scroll-region
                        class="flex gap-2 overflow-x-auto pb-2 lg:min-h-0 lg:flex-col lg:gap-1 lg:overflow-y-auto lg:overscroll-contain"
                    >
                        <div v-for="item in run.drafts" :key="item.id" class="flex items-start gap-2">
                            <input
                                v-if="canImport"
                                v-model="selectedImports"
                                type="checkbox"
                                :value="item.id"
                                :disabled="item.review_status !== 'approved'"
                                :aria-label="`Select question ${item.sequence} for import`"
                                class="mt-4 shrink-0"
                            />
                            <button
                                type="button"
                                class="flex min-w-52 flex-1 cursor-pointer items-start gap-3 rounded-md border p-3 text-left transition-colors lg:min-w-0"
                                :class="selectedDraftId === item.id ? 'border-primary/20 bg-primary/5' : 'border-transparent hover:bg-gray-100'"
                                :aria-current="selectedDraftId === item.id ? 'true' : undefined"
                                @click="selectDraft(item)"
                            >
                                <span
                                    class="flex size-7 shrink-0 items-center justify-center rounded border text-xs font-bold"
                                    :class="
                                        selectedDraftId === item.id
                                            ? 'border-primary/20 bg-white text-primary'
                                            : 'border-gray-200 bg-white text-gray-500'
                                    "
                                    >{{ String(item.sequence).padStart(2, '0') }}</span
                                >
                                <span class="min-w-0 flex-1"
                                    ><span class="block text-xs font-bold text-gray-800">{{ titleCase(item.type) }}</span
                                    ><span class="mt-1 line-clamp-2 block text-xs leading-5 font-medium text-gray-500">{{
                                        plainText(item.content)
                                    }}</span
                                    ><span
                                        class="mt-2 flex items-center gap-1.5 text-[10px] font-semibold"
                                        :class="reviewStatusColor(item.review_status)"
                                        ><span class="size-1.5 rounded-full" :class="reviewStatusDot(item.review_status)"></span
                                        >{{ titleCase(item.review_status) }}</span
                                    ></span
                                >
                            </button>
                        </div>
                    </div>
                </nav>
                <section class="min-w-0 rounded-lg border border-gray-200 bg-white">
                    <article v-for="draft in visibleDrafts" :key="draft.id" class="p-5 sm:p-7">
                        <p class="mb-3 text-xs text-gray-500">
                            {{ className }} · {{ titleCase(draft.difficulty) }} difficulty<span v-if="draft.reviewer">
                                · {{ draft.review_status === 'imported' ? 'Approved' : titleCase(draft.review_status) }} by
                                {{ draft.reviewer.name }}</span
                            ><span v-if="draft.importer"> · Imported by {{ draft.importer.name }}</span
                            ><span v-if="draft.editor"> · Edited by {{ draft.editor.name }}</span>
                        </p>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-gray-900">Question {{ draft.sequence }}</span
                                ><span class="rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">{{ titleCase(draft.type) }}</span
                                ><span
                                    class="rounded px-2 py-1 text-xs font-semibold"
                                    :class="draft.is_valid ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
                                    >{{ draft.is_valid ? 'Validated' : 'Needs correction' }}</span
                                >
                                <span class="text-xs font-semibold capitalize" :class="reviewStatusColor(draft.review_status)">{{
                                    draft.review_status
                                }}</span>
                            </div>
                            <button
                                v-if="draft.review_status !== 'imported'"
                                type="button"
                                class="rounded-md border border-gray-200 px-3 py-2 text-xs font-bold text-gray-700 hover:border-primary hover:text-primary"
                                @click="editing === draft.id ? (editing = null) : beginEdit(draft)"
                            >
                                {{ editing === draft.id ? 'Cancel' : 'Edit draft' }}
                            </button>
                        </div>

                        <form v-if="editing === draft.id" class="mt-6 space-y-5 border-t border-gray-100 pt-6" @submit.prevent="save(draft)">
                            <p v-if="draft.review_status === 'approved'" class="text-sm text-amber-700">
                                Saving changes returns this question to Draft for another review.
                            </p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="text-sm font-semibold text-gray-700"
                                    >Type<select v-model="form.type" class="mt-2 w-full">
                                        <option value="multiple_choice">Multiple choice</option>
                                        <option value="short_answer">Short answer</option>
                                        <option value="theory">Theory</option>
                                    </select></label
                                ><label class="text-sm font-semibold text-gray-700"
                                    >Difficulty<select v-model="form.difficulty" class="mt-2 w-full">
                                        <option value="easy">Easy</option>
                                        <option value="medium">Medium</option>
                                        <option value="hard">Hard</option>
                                    </select></label
                                >
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-700">Question</label
                                ><RichContentEditor v-model="form.content" class="mt-2" />
                            </div>
                            <template v-if="form.type === 'multiple_choice'"
                                ><div v-for="(option, optionIndex) in form.options" :key="optionIndex" class="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        name="correct-option"
                                        :aria-label="`Correct option ${String.fromCharCode(65 + optionIndex)}`"
                                        :checked="option.is_correct"
                                        class="mt-4"
                                        @change="setCorrect(optionIndex)"
                                    />
                                    <div class="min-w-0 flex-1"><RichContentEditor v-model="option.content" compact /></div></div
                            ></template>
                            <template v-else
                                ><div>
                                    <label class="text-sm font-semibold text-gray-700">Expected answer</label
                                    ><RichContentEditor v-model="form.expected_answer" class="mt-2" />
                                </div>
                                <div>
                                    <div class="flex items-center justify-between">
                                        <label class="text-sm font-semibold text-gray-700">Marking scheme</label
                                        ><button
                                            type="button"
                                            class="cursor-pointer text-xs font-bold text-primary"
                                            @click="form.marking_scheme.push({ point: '', weight: 1 })"
                                        >
                                            Add point
                                        </button>
                                    </div>
                                    <div
                                        v-for="(item, itemIndex) in form.marking_scheme"
                                        :key="itemIndex"
                                        class="mt-3 grid grid-cols-[1fr_80px] gap-3"
                                    >
                                        <RichContentEditor v-model="item.point" compact /><input
                                            v-model.number="item.weight"
                                            type="number"
                                            min="1"
                                            max="100"
                                            aria-label="Weight"
                                        />
                                    </div></div
                            ></template>
                            <div>
                                <label class="text-sm font-semibold text-gray-700">Explanation</label
                                ><RichContentEditor v-model="form.explanation" class="mt-2" compact />
                            </div>
                            <div v-if="Object.keys(form.errors).length" class="bg-red-50 px-4 py-3 text-sm text-red-700">
                                <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
                            </div>
                            <button type="submit" class="btn-primary cursor-pointer" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Save draft' }}
                            </button>
                        </form>

                        <div v-else class="mt-5">
                            <RichContentViewer :content="draft.content" class="question-stem" />
                            <ol v-if="draft.options?.length" class="mt-6 space-y-2 text-sm text-gray-700">
                                <li
                                    v-for="(option, index) in draft.options"
                                    :key="index"
                                    class="flex items-start gap-3 rounded-md border px-4 py-3"
                                    :class="
                                        option.is_correct ? 'border-emerald-200 bg-emerald-50/60 text-emerald-800' : 'border-gray-200 text-gray-700'
                                    "
                                >
                                    <span class="pt-0.5 text-xs font-bold" :class="option.is_correct ? 'text-emerald-700' : 'text-gray-400'">{{
                                        String.fromCharCode(65 + index)
                                    }}</span
                                    ><RichContentViewer :content="option.content" class="min-w-0 flex-1" /><span
                                        v-if="option.is_correct"
                                        class="shrink-0 pt-0.5 text-[10px] font-bold text-emerald-700"
                                        >Correct</span
                                    >
                                </li>
                            </ol>
                            <div v-if="draft.expected_answer" class="mt-4 border-l-2 border-gray-200 pl-4">
                                <p class="text-xs font-bold text-gray-500 uppercase">Expected answer</p>
                                <RichContentViewer :content="draft.expected_answer" class="mt-1 text-sm" />
                            </div>
                            <h3 v-if="draft.marking_scheme?.length" class="mt-6 text-xs font-bold text-gray-500 uppercase">Marking scheme</h3>
                            <ol v-if="draft.marking_scheme?.length" class="mt-3 list-decimal space-y-3 pl-5 text-sm text-gray-700">
                                <li v-for="item in draft.marking_scheme" :key="item.point">
                                    <RichContentViewer :content="item.point" class="inline" />
                                    <span class="text-xs text-gray-500">({{ item.weight }} mark<span v-if="item.weight !== 1">s</span>)</span>
                                </li>
                            </ol>
                            <div v-if="draft.explanation" class="mt-6 border-t border-gray-100 pt-5">
                                <h3 class="text-xs font-bold text-gray-500 uppercase">Explanation</h3>
                                <RichContentViewer :content="draft.explanation" class="mt-2 text-sm text-gray-600" />
                            </div>
                        </div>

                        <div v-if="!editing" class="mt-6 border-t border-gray-200 pt-4" :class="draft.review_status !== 'imported' ? 'min-h-36' : ''">
                            <div v-if="draft.review_status === 'approved'" class="flex flex-wrap items-center justify-between gap-3">
                                <p role="status" class="text-sm font-bold text-emerald-700">Approved</p>
                                <button
                                    type="button"
                                    class="btn-secondary cursor-pointer rounded-md"
                                    :disabled="reviewForm.processing"
                                    @click="reviewDraft(draft, 'reject')"
                                >
                                    Reject draft
                                </button>
                            </div>
                            <template v-else-if="draft.review_status !== 'imported'">
                                <label class="flex cursor-pointer items-start gap-2 text-sm text-gray-700"
                                    ><input v-model="answerVerified" type="checkbox" class="mt-1 shrink-0" /><span
                                        >I have checked the correct answer, explanation and marking scheme, including any mathematical
                                        calculations.</span
                                    ></label
                                >
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="btn-primary cursor-pointer rounded-md"
                                        :disabled="!answerVerified || reviewForm.processing"
                                        @click="reviewDraft(draft, 'approve')"
                                    >
                                        Approve draft</button
                                    ><button
                                        type="button"
                                        class="btn-secondary cursor-pointer rounded-md"
                                        :disabled="reviewForm.processing"
                                        @click="reviewDraft(draft, 'reject')"
                                    >
                                        Reject draft
                                    </button>
                                </div>
                            </template>
                            <Link
                                v-else
                                :href="bankIndex.url({ query: { question_id: draft.question_id } })"
                                class="text-sm font-bold text-primary underline"
                                >View in question bank</Link
                            >
                            <p v-for="(error, key) in reviewForm.errors" :key="key" class="mt-2 text-sm text-red-700">{{ error }}</p>
                        </div>
                        <div v-if="draft.duplicate_warnings?.length" class="mt-4 border-l-2 border-amber-400 bg-amber-50 p-3 text-sm text-amber-800">
                            <p class="font-semibold">Similar questions already exist</p>
                            <Link
                                v-for="item in draft.duplicate_warnings"
                                :key="item.id"
                                :href="bankIndex.url({ query: { question_id: item.id } })"
                                class="mt-2 block underline"
                                >{{ item.content }}</Link
                            >
                        </div>
                        <p v-for="error in draft.import_errors ?? []" :key="error" class="mt-2 text-sm text-red-700">{{ error }}</p>

                        <div v-if="draft.validation_errors?.length || draft.warnings?.length" class="mt-4 space-y-2 text-sm">
                            <p v-for="error in draft.validation_errors" :key="error" class="text-red-700">{{ error }}</p>
                            <p v-for="warning in draft.warnings" :key="warning" class="text-amber-700">{{ warning }}</p>
                        </div>
                        <details class="mt-5 border-t border-gray-100 pt-4">
                            <summary class="cursor-pointer text-xs font-bold text-gray-600">Sources ({{ draft.source_references.length }})</summary>
                            <div class="mt-3 space-y-3">
                                <div v-for="source in draft.source_references" :key="source.chunk_id" class="bg-gray-50 px-4 py-3">
                                    <div class="flex flex-wrap justify-between gap-2 text-xs">
                                        <span class="font-bold text-gray-800">{{ source.document_title }} · {{ pageRange(source) }}</span
                                        ><span class="font-semibold text-emerald-700">{{ (source.similarity * 100).toFixed(1) }}% match</span>
                                    </div>
                                    <p class="mt-2 text-xs leading-5 whitespace-pre-wrap text-gray-600">{{ source.content }}</p>
                                </div>
                            </div>
                        </details>
                    </article>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.question-stem {
    font-size: 16px;
    font-weight: 600;
    line-height: 1.8;
    color: #111827;
}
.draft-workspace :deep(.rich-content-viewer p) {
    margin-top: 0;
    margin-bottom: 0;
}
</style>
