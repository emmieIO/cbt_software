<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Handsontable from 'handsontable';
import { ref, onUnmounted, watch, nextTick } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import 'handsontable/styles/handsontable.css';

const props = defineProps<{
    levels: Array<{ value: string; label: string }>;
    subjectNames: string[];
    topicNames: string[];
}>();

const tab = ref<'spreadsheet' | 'upload'>('spreadsheet');
const fullscreen = ref(false);
const level = ref('js');
const submitting = ref(false);
const container = ref<HTMLDivElement | null>(null);
let hot: Handsontable | null = null;

const columns = [
    { title: 'Subject', data: 0, type: 'autocomplete', source: props.subjectNames || [], strict: false, width: 140, minWidth: 100 },
    { title: 'Topic', data: 1, type: 'autocomplete', source: props.topicNames || [], strict: false, width: 140, minWidth: 100 },
    { title: 'Type', data: 2, type: 'dropdown', source: ['MCQ', 'Theory'], width: 80, minWidth: 60 },
    { title: 'Question', data: 3, type: 'text', width: 300, minWidth: 150, wordWrap: true },
    { title: 'Option A', data: 4, type: 'text', width: 120, minWidth: 80 },
    { title: 'Option B', data: 5, type: 'text', width: 120, minWidth: 80 },
    { title: 'Option C', data: 6, type: 'text', width: 120, minWidth: 80 },
    { title: 'Option D', data: 7, type: 'text', width: 120, minWidth: 80 },
    { title: 'Correct', data: 8, type: 'dropdown', source: ['A', 'B', 'C', 'D'], width: 80, minWidth: 60 },
    { title: 'Marking Scheme', data: 9, type: 'text', width: 250, minWidth: 150, wordWrap: true },
    { title: '', data: 10, type: 'text', width: 50, minWidth: 20, readOnly: true, colHeader: '' },
];

const initTable = () => {
    if (!container.value) return;
    try {
        const rect = container.value.getBoundingClientRect();
        hot = new Handsontable(container.value, {
            data: [['', '', 'MCQ', '', '', '', '', '', '', '', '']],
            columns,
            rowHeaders: true,
            colHeaders: columns.map(c => c.title),
            contextMenu: ['undo', 'redo', 'clear_column', 'separator0', 'row_above', 'row_below', 'remove_row', 'separator1', 'alignment', 'copy', 'cut', 'paste'],
            manualColumnResize: true,
            manualRowResize: true,
            minSpareRows: 1,
            enterMoves: { row: 1, col: 0 },
            tabMoves: { row: 0, col: 1 },
            licenseKey: 'non-commercial-and-evaluation',
            height: rect.height,
            width: '100%',
            stretchH: 'all',
            stretchV: 'all',
            autoWrapRow: true,
            autoWrapCol: true,
            fillHandle: true,
            wordWrap: false,
            renderAllRows: false,
            undo: true,
            columnSorting: true,
        });
    } catch (e) {
        console.error('Handsontable init error:', e);
    }
};

let resizeObserver: ResizeObserver | null = null;

watch(fullscreen, async (val) => {
    if (val) {
        await nextTick();
        await new Promise(r => setTimeout(r, 150));
        initTable();
        if (container.value) {
            resizeObserver = new ResizeObserver(() => {
                if (hot && container.value) {
                    hot.updateSettings({ height: container.value.getBoundingClientRect().height });
                }
            });
            resizeObserver.observe(container.value);
        }
    } else {
        resizeObserver?.disconnect();
        resizeObserver = null;
        hot?.destroy();
        hot = null;
    }
});

onUnmounted(() => {
    resizeObserver?.disconnect();
    hot?.destroy();
});

onUnmounted(() => { hot?.destroy(); hot = null; });

// File upload
const file = ref<File | null>(null);
const dragOver = ref(false);

const handleFile = (f: File | null) => {
    if (f && (f.name.endsWith('.xlsx') || f.name.endsWith('.xls') || f.name.endsWith('.csv'))) file.value = f;
};

const upload = () => {
    if (!file.value) return;
    submitting.value = true;
    const fd = new FormData();
    fd.append('file', file.value);
    router.post('/questions/import/preview', fd, { onFinish: () => { submitting.value = false; } });
};

const downloadTemplate = () => window.open('/questions/import/template', '_blank');
</script>

<template>
    <Head title="Batch Import" />

    <div v-if="fullscreen" class="flex h-screen flex-col bg-white dark:bg-gray-800">
        <!-- Fullscreen top bar -->
        <div class="flex shrink-0 items-center gap-4 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
            <button @click="fullscreen = false" class="rounded-lg p-2 text-gray-500 dark:text-gray-400 dark:text-gray-500 hover:bg-gray-100" title="Exit fullscreen">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Quick Entry</span>
            <span class="text-xs text-gray-400 dark:text-gray-500">Enter one question per row</span>

            <div class="ml-auto flex items-center gap-2">
                <button @click="hot?.undo()" class="rounded-lg border border-gray-300 dark:border-gray-600 px-2.5 py-1.5 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50" title="Undo (Ctrl+Z)">↩</button>
                <button @click="hot?.redo()" class="rounded-lg border border-gray-300 dark:border-gray-600 px-2.5 py-1.5 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50" title="Redo (Ctrl+Y)">↪</button>
                <span class="text-xs text-gray-300 dark:text-gray-600">|</span>
                <select v-model="level" class="w-auto border-gray-200 dark:border-gray-700 text-sm">
                    <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                </select>
                <button @click="hot?.alter('insert_row_below')" class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50">+ Row</button>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ hot?.countRows() || 0 }} rows</span>
                <button @click="submitSpreadsheet" :disabled="submitting"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-1.5 text-sm font-semibold text-white shadow-sm dark:shadow-none dark:border-gray-700 hover:bg-primary/90 disabled:opacity-50">
                    <span v-if="submitting" class="inline-block size-3 animate-spin rounded-full border-2 border-white border-t-transparent" />
                    Import
                </button>
                <Link href="/questions" class="text-sm font-medium text-primary hover:underline">Done</Link>
            </div>
        </div>

        <!-- Handsontable container -->
        <div ref="container" class="flex-1 w-full" style="min-height:300px"></div>
    </div>

    <!-- Normal (non-fullscreen) view with AppLayout -->
    <AppLayout v-else>

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Batch Import Questions</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Quickly add multiple questions using the spreadsheet or upload a file.</p>
                </div>
                <Link href="/questions" class="text-sm font-medium text-primary hover:underline">&larr; Back to questions</Link>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 p-1">
                <button @click="tab = 'spreadsheet'"
                    class="flex-1 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors"
                    :class="tab === 'spreadsheet' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm dark:shadow-none dark:border-gray-700' : 'text-gray-500 dark:text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:text-gray-200'">
                    <svg class="-ml-1 mr-1.5 inline size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Quick Entry
                    <button @click.stop="fullscreen = true" class="ml-2 rounded p-0.5 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:text-gray-300" title="Fullscreen">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </button>
                </button>
                <button @click="tab = 'upload'"
                    class="flex-1 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors"
                    :class="tab === 'upload' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm dark:shadow-none dark:border-gray-700' : 'text-gray-500 dark:text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:text-gray-200'">
                    <svg class="-ml-1 mr-1.5 inline size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    File Upload
                </button>
            </div>

            <!-- Level selector -->
            <div class="flex items-center gap-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm dark:shadow-none dark:border-gray-700">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Level:</label>
                <select v-model="level" class="w-auto min-w-[200px]">
                    <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                </select>
                <span class="text-xs text-gray-400 dark:text-gray-500">All questions and new subjects will use this level.</span>
            </div>

            <!-- Spreadsheet tab prompt -->
            <div v-if="tab === 'spreadsheet'" class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 py-20">
                <svg class="size-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Open Spreadsheet Editor</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Enter questions in a fullscreen Excel-like table with keyboard navigation and copy-paste.</p>
                <button @click="fullscreen = true"
                    class="mt-6 inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm dark:shadow-none dark:border-gray-700 hover:bg-primary/90">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    Open Fullscreen
                </button>
            </div>

            <!-- Upload tab -->
            <div v-if="tab === 'upload'" class="space-y-6">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm dark:shadow-none dark:border-gray-700">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Download Template</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Download the Excel template, fill it in, then upload.</p>
                    <button @click="downloadTemplate" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:bg-gray-800/50">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download Template (XLSX)
                    </button>
                </div>

                <div @drop.prevent="handleFile($event.dataTransfer.files[0])" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
                    @click="$refs.file.click()"
                    class="flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 border-dashed p-8 transition-colors"
                    :class="dragOver ? 'border-primary bg-primary/5' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'">
                    <svg class="size-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <div v-if="!file" class="text-center">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Drop file here, or <span class="text-primary">browse</span></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">.xlsx, .xls, .csv</p>
                    </div>
                    <div v-else class="text-center">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ file.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ (file.size / 1024).toFixed(1) }} KB</p>
                        <button @click.stop="file = null" type="button" class="mt-1 text-xs text-red-600 hover:underline">Remove</button>
                    </div>
                    <input ref="file" type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="handleFile($event.target.files?.[0] || null)" />
                </div>

                <div class="flex justify-end">
                    <button @click="upload" :disabled="!file || submitting"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm dark:shadow-none dark:border-gray-700 hover:bg-primary/90 disabled:opacity-50">
                        Preview & Import
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
