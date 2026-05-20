<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const submitting = ref(false);
const file = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const dragOver = ref(false);

const handleFile = (selected: File | null) => {
    if (selected && (selected.name.endsWith('.xlsx') || selected.name.endsWith('.xls') || selected.name.endsWith('.csv'))) {
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

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:shadow-none">
                <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Step 1: Download template</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use the provided Excel template so your columns match the import format.</p>
                <a href="/questions/import/template" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800/50 sm:w-auto sm:justify-start">
                    Download Template (XLSX)
                </a>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:shadow-none">
                <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Step 2: Upload file</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Accepted formats: `.xlsx`, `.xls`, `.csv`.</p>

                <div
                    @drop.prevent="onDrop"
                    @dragover.prevent="dragOver = true"
                    @dragleave.prevent="dragOver = false"
                    @click="openFilePicker"
                    class="mt-4 flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 border-dashed p-8 transition-colors"
                    :class="dragOver ? 'border-primary bg-primary/5' : 'border-gray-300 hover:border-gray-400 dark:border-gray-600'"
                >
                    <div v-if="!file" class="text-center">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Drop file here, or <span class="text-primary">browse</span></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">.xlsx, .xls, .csv</p>
                    </div>
                    <div v-else class="text-center">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ file.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ (file.size / 1024).toFixed(1) }} KB</p>
                        <button @click.stop="file = null" type="button" class="mt-1 text-xs text-red-600 hover:underline">Remove</button>
                    </div>
                    <input ref="fileInput" type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="onFileChange" />
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
        </div>
    </AppLayout>
</template>
