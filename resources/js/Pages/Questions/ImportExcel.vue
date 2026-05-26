<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

type PreviewRow = {
    index: number;
    valid: boolean;
    errors: string[];
    subject_name: string;
    topic_name: string;
    type: 'multiple_choice' | 'theory';
    content: string;
    image_url?: string | null;
    level?: 'lp' | 'hp' | 'js' | 'ss' | null;
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
const page = usePage();
const preview = computed(() => ((page.props.flash as { preview?: PreviewPayload })?.preview ?? null));
const hasPreview = computed(() => Boolean(preview.value?.rows?.length));

const handleFile = (selected: File | null) => {
    if (selected && (selected.name.endsWith('.xlsx') || selected.name.endsWith('.csv'))) {
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
    router.post('/questions/import/confirm', {}, {
        onFinish: () => {
            confirming.value = false;
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Import Questions From Excel" />

        <div class="mx-auto max-w-4xl space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Import Questions From Excel</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Download the template, fill it in, then upload it for preview and import.</p>
                </div>
                <Link href="/questions" class="text-sm font-medium text-primary hover:underline">&larr; Back to questions</Link>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none">
                <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Step 1: Download template</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use the provided Excel template so your columns, including optional `image_url`, match the import format.</p>
                <a href="/questions/import/template" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-green-800/60 dark:text-gray-200 dark:hover:bg-green-950/55 sm:w-auto sm:justify-start">
                    Download Template (XLSX)
                </a>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none">
                <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Step 2: Upload file</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Accepted formats: `.xlsx`, `.csv`.</p>

                <div
                    @drop.prevent="onDrop"
                    @dragover.prevent="dragOver = true"
                    @dragleave.prevent="dragOver = false"
                    @click="openFilePicker"
                    class="mt-4 flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 border-dashed p-8 transition-colors"
                    :class="dragOver ? 'border-primary bg-primary/5' : 'border-gray-300 hover:border-gray-400 dark:border-green-800/60'"
                >
                    <div v-if="!file" class="text-center">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Drop file here, or <span class="text-primary">browse</span></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">.xlsx, .csv</p>
                    </div>
                    <div v-else class="text-center">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ file.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ (file.size / 1024).toFixed(1) }} KB</p>
                        <button @click.stop="file = null" type="button" class="mt-1 text-xs text-red-600 hover:underline">Remove</button>
                    </div>
                    <input ref="fileInput" type="file" accept=".xlsx,.csv" class="hidden" @change="onFileChange" />
                </div>

                <div class="mt-6 flex justify-stretch sm:justify-end">
                    <button
                        @click="upload"
                        :disabled="!file || submitting"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-50 sm:w-auto"
                    >
                        <span v-if="submitting" class="inline-block size-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                        Preview & Import
                    </button>
                </div>
            </div>

            <div v-if="hasPreview" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Step 3: Review preview</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ preview?.valid }} valid row(s), {{ preview?.errors }} row(s) with errors, {{ preview?.total }} total.
                        </p>
                    </div>
                    <button
                        @click="confirmImport"
                        :disabled="confirming || (preview?.errors ?? 0) > 0"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-50"
                    >
                        <span v-if="confirming" class="inline-block size-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                        Confirm Import
                    </button>
                </div>

                <div v-if="preview?.new_subjects?.length || preview?.new_topics?.length" class="mt-4 rounded-lg bg-gray-50 p-4 text-xs text-gray-600 dark:bg-green-950/60 dark:text-gray-300">
                    <p v-if="preview?.new_subjects?.length">New subjects: {{ preview.new_subjects.join(', ') }}</p>
                    <p v-if="preview?.new_topics?.length" class="mt-1">New topics: {{ preview.new_topics.join(', ') }}</p>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-green-950/60">
                            <tr>
                                <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">Row</th>
                                <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">Status</th>
                                <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">Subject</th>
                                <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">Topic</th>
                                <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">Type</th>
                                <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">Content</th>
                                <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">Image</th>
                                <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">Errors</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="row in preview?.rows ?? []" :key="`${row.index}-${row.content}`">
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ row.index }}</td>
                                <td class="px-3 py-2">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="row.valid ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'"
                                    >
                                        {{ row.valid ? 'Valid' : 'Error' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ row.subject_name }}</td>
                                <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ row.topic_name }}</td>
                                <td class="px-3 py-2">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold capitalize"
                                        :class="row.type === 'theory' ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-200'"
                                    >
                                        {{ row.type.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ row.content }}</td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ row.image_url || 'None' }}</td>
                                <td class="px-3 py-2 text-red-600 dark:text-red-300">{{ row.errors.join(' ') || 'None' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
