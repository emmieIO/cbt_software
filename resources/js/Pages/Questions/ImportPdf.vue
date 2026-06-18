<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RichContentViewer from '@/components/Questions/RichContentViewer.vue';
import AppLayout from '@/layouts/AppLayout.vue';

type PreviewRow = {
    index: number;
    valid: boolean;
    errors: string[];
    subject_name: string;
    topic_name: string;
    type: 'multiple_choice' | 'short_answer' | 'theory';
    content: string;
    image_url?: string | null;
    level?: 'lp' | 'hp' | 'js' | 'ss' | null;
    class_level?: string | null;
    options: string[];
    correct_answer: string | null;
    marking_scheme: Array<{ point: string; weight: number }>;
};

type PreviewPayload = {
    rows: PreviewRow[];
    total: number;
    valid: number;
    errors: number;
    new_subjects: string[];
    new_topics: string[];
};

const submitting = ref(false);
const confirming = ref(false);
const file = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const dragOver = ref(false);
const fallbackLevel = ref<'lp' | 'hp' | 'js' | 'ss'>('ss');
const selectedPreviewRow = ref<PreviewRow | null>(null);
const page = usePage();
const preview = computed(() => (page.props.flash as { preview?: PreviewPayload })?.preview ?? null);
const flashError = computed(() => (page.props.flash as { error?: string })?.error ?? null);
const hasPreview = computed(() => Boolean(preview.value?.rows?.length));
const hasEmptyPreview = computed(() => Boolean(preview.value && !preview.value.rows.length));
const validRows = computed(() => preview.value?.rows.filter((row) => row.valid) ?? []);
const errorRows = computed(() => preview.value?.rows.filter((row) => !row.valid) ?? []);

const levelLabels: Record<string, string> = {
    lp: 'Lower Primary',
    hp: 'Higher Primary',
    js: 'Junior Secondary',
    ss: 'Senior Secondary',
};

const classLabels: Record<string, Record<string, string>> = {
    lp: { '1': 'Primary 1', '2': 'Primary 2', '3': 'Primary 3' },
    hp: { '4': 'Primary 4', '5': 'Primary 5', '6': 'Primary 6' },
    js: { '7': 'JSS 1', '8': 'JSS 2', '9': 'JSS 3' },
    ss: { '10': 'SS 1', '11': 'SS 2', '12': 'SS 3' },
};

const typeLabels: Record<PreviewRow['type'], string> = {
    multiple_choice: 'Multiple Choice',
    short_answer: 'Short Answer',
    theory: 'Theory',
};

const typeClasses: Record<PreviewRow['type'], string> = {
    multiple_choice: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-200',
    short_answer: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200',
    theory: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200',
};

const formatLevel = (row: PreviewRow) => levelLabels[row.level || fallbackLevel.value] ?? 'Not set';
const formatClassLevel = (row: PreviewRow) => {
    if (!row.class_level) return 'Default';

    return classLabels[row.level || fallbackLevel.value]?.[row.class_level] ?? row.class_level;
};
const formatType = (type: PreviewRow['type']) => typeLabels[type] ?? type.replace('_', ' ');

const handleFile = (selected: File | null) => {
    if (selected && selected.name.endsWith('.pdf')) {
        file.value = selected;
    }
};

const onDrop = (event: DragEvent) => {
    dragOver.value = false;
    handleFile(event.dataTransfer?.files[0] || null);
};

const onFileChange = (event: Event) => {
    handleFile((event.target as HTMLInputElement)?.files?.[0] || null);
};

const openFilePicker = () => {
    fileInput.value?.click();
};

const upload = () => {
    if (!file.value) return;

    submitting.value = true;
    const fd = new FormData();
    fd.append('file', file.value);
    router.post('/questions/import/preview', fd, {
        onFinish: () => {
            submitting.value = false;
        },
    });
};

const confirmImport = () => {
    confirming.value = true;
    router.post(
        '/questions/import/confirm',
        { level: fallbackLevel.value },
        {
            onFinish: () => {
                confirming.value = false;
            },
        },
    );
};

const closePreviewDialog = () => {
    selectedPreviewRow.value = null;
};
</script>

<template>
    <AppLayout>
        <Head title="Import Questions From PDF" />

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">AI PDF Question Extraction</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Upload a PDF document and our AI will automatically extract and structure the questions for you.
                    </p>
                </div>
                <Link href="/questions" class="text-sm font-medium text-primary hover:underline">&larr; Back to questions</Link>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Upload PDF</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Accepted formats: `.pdf` only.</p>
                    </div>
                    <div class="flex max-w-xl flex-col gap-3 rounded-lg bg-gray-50 px-4 py-3 text-xs text-gray-600 sm:flex-row sm:items-center sm:justify-between dark:bg-green-950/45 dark:text-gray-300">
                        <span>Download a sample PDF showing the question layout the importer reads best.</span>
                        <a
                            href="/questions/import/pdf-template"
                            class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-white sm:w-auto dark:border-green-800/60 dark:text-gray-100 dark:hover:bg-green-950"
                        >
                            Download Sample PDF
                        </a>
                    </div>
                </div>

                <div v-if="flashError" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200">
                    {{ flashError }}
                </div>

                <div
                    @drop.prevent="onDrop"
                    @dragover.prevent="dragOver = true"
                    @dragleave.prevent="dragOver = false"
                    @click="openFilePicker"
                    class="mt-4 flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 border-dashed p-8 transition-colors"
                    :class="dragOver ? 'border-primary bg-primary/5' : 'border-gray-300 hover:border-gray-400 dark:border-green-800/60'"
                >
                    <div v-if="!file" class="text-center">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                            Drop PDF here, or <span class="text-primary">browse</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">.pdf</p>
                    </div>
                    <div v-else class="text-center">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ file.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ (file.size / 1024).toFixed(1) }} KB</p>
                        <button @click.stop="file = null" type="button" class="mt-1 text-xs text-red-600 hover:underline">Remove</button>
                    </div>
                    <input ref="fileInput" type="file" accept=".pdf" class="hidden" @change="onFileChange" />
                </div>

                <div class="mt-6 flex justify-stretch sm:justify-end">
                    <button
                        @click="upload"
                        :disabled="!file || submitting"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-50 sm:w-auto"
                    >
                        <span v-if="submitting" class="inline-block size-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                        Extract Questions
                    </button>
                </div>
            </div>

            <section
                v-if="hasPreview || hasEmptyPreview"
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none"
            >
                <div class="border-b border-gray-200 bg-gray-50/70 px-6 py-5 dark:border-green-900/60 dark:bg-green-950/45">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-primary">Preview</p>
                            <h2 class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">Review Extracted Questions</h2>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Check the AI extraction before importing. Questions with errors must be fixed before confirmation.</p>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end xl:justify-end">
                            <div class="w-full sm:w-64">
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Fallback level for rows without level</label>
                                <select v-model="fallbackLevel" class="mt-1">
                                    <option value="lp">Lower Primary</option>
                                    <option value="hp">Higher Primary</option>
                                    <option value="js">Junior Secondary</option>
                                    <option value="ss">Senior Secondary</option>
                                </select>
                            </div>
                            <button
                                v-if="hasPreview"
                                @click="confirmImport"
                                :disabled="confirming || (preview?.errors ?? 0) > 0"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-50 sm:w-auto"
                            >
                                <span v-if="confirming" class="inline-block size-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                Confirm Import
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div v-if="hasEmptyPreview" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-5 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
                        No questions were found in this PDF. Try a clearer text-based PDF, or use the Excel import template for this document.
                    </div>

                    <div v-if="hasPreview" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-green-900/60 dark:bg-green-950/35">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Total Extracted</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ preview?.total ?? 0 }}</p>
                        </div>
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                            <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-200">Ready To Import</p>
                            <p class="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ validRows.length }}</p>
                        </div>
                        <div
                            class="rounded-lg border px-4 py-3"
                            :class="
                                errorRows.length
                                    ? 'border-red-200 bg-red-50 dark:border-red-500/20 dark:bg-red-500/10'
                                    : 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/20 dark:bg-emerald-500/10'
                            "
                        >
                            <p class="text-xs font-semibold" :class="errorRows.length ? 'text-red-700 dark:text-red-200' : 'text-emerald-700 dark:text-emerald-200'">Needs Attention</p>
                            <p class="mt-1 text-2xl font-bold" :class="errorRows.length ? 'text-red-900 dark:text-red-100' : 'text-emerald-900 dark:text-emerald-100'">{{ errorRows.length }}</p>
                        </div>
                    </div>

                    <div
                        v-if="hasPreview && (preview?.new_subjects?.length || preview?.new_topics?.length)"
                        class="mt-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-xs text-gray-600 dark:border-green-800/60 dark:bg-green-950/45 dark:text-gray-300"
                    >
                        <p class="font-semibold text-gray-800 dark:text-gray-100">New items detected</p>
                        <p v-if="preview?.new_subjects?.length" class="mt-1">Subjects: {{ preview.new_subjects.join(', ') }}</p>
                        <p v-if="preview?.new_topics?.length" class="mt-1">Topics: {{ preview.new_topics.join(', ') }}</p>
                    </div>

                    <div v-if="hasPreview" class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-green-900/60 dark:bg-green-950/35">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-green-900/60">
                                <thead class="bg-gray-50 dark:bg-green-950/45">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400">No.</th>
                                        <th class="min-w-[280px] px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400">Question</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400">Subject</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400">Topic</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400">Class</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-green-900/50">
                                    <tr
                                        v-for="row in preview?.rows ?? []"
                                        :key="`${row.index}-${row.content}`"
                                        class="bg-white align-top hover:bg-gray-50 dark:bg-green-950/20 dark:hover:bg-green-950/45"
                                    >
                                        <td class="px-4 py-4 text-sm font-semibold text-gray-500 dark:text-gray-400">{{ row.index }}</td>
                                        <td class="max-w-md px-4 py-4">
                                            <RichContentViewer class="line-clamp-2 text-sm font-medium text-gray-900 dark:text-gray-100" :content="row.content || 'Question content missing'" truncate />
                                            <p v-if="row.errors.length" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">{{ row.errors.length }} issue(s)</p>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold whitespace-nowrap" :class="typeClasses[row.type]">
                                                {{ formatType(row.type) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-gray-700 dark:text-gray-200">{{ row.subject_name || '-' }}</td>
                                        <td class="px-4 py-4 text-gray-700 dark:text-gray-200">{{ row.topic_name || '-' }}</td>
                                        <td class="px-4 py-4 text-gray-700 whitespace-nowrap dark:text-gray-200">{{ formatClassLevel(row) }}</td>
                                        <td class="px-4 py-4">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap"
                                                :class="
                                                    row.valid
                                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                                                        : 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'
                                                "
                                            >
                                                {{ row.valid ? 'Ready' : 'Needs review' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <button
                                                type="button"
                                                class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-green-800/60 dark:text-gray-100 dark:hover:bg-green-950"
                                                @click="selectedPreviewRow = row"
                                            >
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <div v-if="selectedPreviewRow" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-6">
                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="closePreviewDialog"></div>
                <section
                    class="relative flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-green-900/60 dark:bg-green-950"
                >
                    <header class="border-b border-gray-200 bg-gray-50/80 px-5 py-4 sm:px-6 dark:border-green-900/60 dark:bg-green-950/70">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-xs font-bold tracking-wide text-primary uppercase">Question {{ selectedPreviewRow.index }}</p>
                                <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">Extracted Question Preview</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ selectedPreviewRow.subject_name || '-' }} · {{ selectedPreviewRow.topic_name || '-' }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-gray-200 bg-white text-xl leading-none text-gray-500 hover:bg-gray-50 hover:text-gray-800 dark:border-green-900/60 dark:bg-green-950/55 dark:text-gray-200 dark:hover:bg-green-900/55"
                                aria-label="Close preview"
                                @click="closePreviewDialog"
                            >
                                ×
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-green-900/60 dark:bg-green-950/55">
                                <p class="font-semibold text-gray-400 uppercase">Type</p>
                                <span class="mt-1 inline-flex rounded-full border px-2.5 py-1 font-semibold" :class="typeClasses[selectedPreviewRow.type]">
                                    {{ formatType(selectedPreviewRow.type) }}
                                </span>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-green-900/60 dark:bg-green-950/55">
                                <p class="font-semibold text-gray-400 uppercase">Level</p>
                                <p class="mt-1 font-bold text-gray-800 dark:text-gray-100">{{ formatLevel(selectedPreviewRow) }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-green-900/60 dark:bg-green-950/55">
                                <p class="font-semibold text-gray-400 uppercase">Class</p>
                                <p class="mt-1 font-bold text-gray-800 dark:text-gray-100">{{ formatClassLevel(selectedPreviewRow) }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-green-900/60 dark:bg-green-950/55">
                                <p class="font-semibold text-gray-400 uppercase">Status</p>
                                <p class="mt-1 font-bold" :class="selectedPreviewRow.valid ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'">
                                    {{ selectedPreviewRow.valid ? 'Ready' : 'Needs review' }}
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
                                            <RichContentViewer :content="selectedPreviewRow.content || 'Question content missing'" />
                                        </div>
                                        <img
                                            v-if="selectedPreviewRow.image_url"
                                            :src="selectedPreviewRow.image_url"
                                            alt="Question image"
                                            class="mt-4 max-h-80 w-full rounded-lg border border-gray-200 object-contain dark:border-green-900/60"
                                        />
                                    </div>
                                </section>

                                <section
                                    v-if="selectedPreviewRow.options?.length"
                                    class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-green-900/60 dark:bg-green-950/55"
                                >
                                    <div class="border-b border-gray-200 px-4 py-3 dark:border-green-900/60">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Options</h3>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">
                                        <div
                                            v-for="(option, optionIndex) in selectedPreviewRow.options"
                                            :key="`${selectedPreviewRow.index}-option-${optionIndex}`"
                                            class="flex gap-3 rounded-lg border border-gray-200 bg-white p-3 dark:border-green-900/60 dark:bg-green-950/55"
                                        >
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600 dark:bg-green-900/70 dark:text-gray-200">
                                                {{ ['A', 'B', 'C', 'D'][optionIndex] }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <RichContentViewer :content="option" />
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    v-if="selectedPreviewRow.marking_scheme?.length"
                                    class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-green-900/60 dark:bg-green-950/55"
                                >
                                    <div class="border-b border-gray-200 px-4 py-3 dark:border-green-900/60">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Marking Scheme</h3>
                                    </div>
                                    <div class="space-y-2 p-4">
                                        <div
                                            v-for="(point, pointIndex) in selectedPreviewRow.marking_scheme"
                                            :key="`${selectedPreviewRow.index}-mark-${pointIndex}`"
                                            class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-green-900/60 dark:bg-green-950/55"
                                        >
                                            <div class="mb-2 flex items-center justify-between gap-3">
                                                <span class="text-xs font-bold text-gray-400">Point {{ pointIndex + 1 }}</span>
                                                <span class="shrink-0 rounded-full bg-primary/10 px-2 py-1 text-xs font-semibold text-primary">{{ point.weight }} mark(s)</span>
                                            </div>
                                            <RichContentViewer :content="point.point" />
                                        </div>
                                    </div>
                                </section>
                            </main>

                            <aside class="space-y-5">
                                <section class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-green-900/60 dark:bg-green-950/55">
                                    <h3 class="text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400">Answer</h3>
                                    <RichContentViewer class="mt-3 font-semibold text-gray-900 dark:text-gray-100" :content="selectedPreviewRow.correct_answer || 'Not provided'" />
                                </section>

                                <section
                                    v-if="selectedPreviewRow.errors.length"
                                    class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-200"
                                >
                                    <h3 class="text-xs font-bold tracking-wide uppercase">Errors</h3>
                                    <ul class="mt-3 space-y-1">
                                        <li v-for="error in selectedPreviewRow.errors" :key="`${selectedPreviewRow.index}-${error}`">- {{ error }}</li>
                                    </ul>
                                </section>
                            </aside>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
