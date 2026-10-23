<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { index, show, store } from '@/actions/App/Http/Controllers/KnowledgeDocumentController';
import { index as semanticSearch } from '@/actions/App/Http/Controllers/KnowledgeSearchController';
import AppLayout from '@/layouts/AppLayout.vue';

interface KnowledgeDocument {
    id: string;
    title: string;
    document_type: string;
    original_name: string;
    size_bytes: number;
    processing_status: string;
    approval_status: string;
    embedding_status: string;
    chunks_count: number;
    created_at: string;
    uploader: { name: string } | null;
}

interface DocumentsPage {
    data: KnowledgeDocument[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

const props = defineProps<{
    documents: DocumentsPage;
    filters: {
        search?: string;
        document_type?: string;
        processing_status?: string;
        approval_status?: string;
        embedding_status?: string;
    };
    documentTypes: string[];
}>();

const page = usePage();
const canManage = computed(() => Boolean((page.props.auth as any)?.user?.can_manage_knowledge_base));
const uploadOpen = ref(false);
const filters = useForm({
    search: props.filters.search ?? '',
    document_type: props.filters.document_type ?? '',
    processing_status: props.filters.processing_status ?? '',
    approval_status: props.filters.approval_status ?? '',
    embedding_status: props.filters.embedding_status ?? '',
});
const upload = useForm<{
    title: string;
    document_type: string;
    description: string;
    file: File | null;
}>({
    title: '',
    document_type: 'curriculum',
    description: '',
    file: null,
});

const hasFilters = computed(() => Object.values(filters.data()).some(Boolean));

const applyFilters = () => {
    router.get(index.url(), filters.data(), { preserveState: true, replace: true });
};

const clearFilters = () => {
    filters.reset();
    router.get(index.url(), {}, { preserveState: true, replace: true });
};

const submitUpload = () => {
    upload.post(store.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            upload.reset();
            uploadOpen.value = false;
        },
    });
};

const selectFile = (event: Event) => {
    upload.file = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const titleCase = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (character) => character.toUpperCase());
const fileSize = (bytes: number) => (bytes < 1_048_576 ? `${Math.ceil(bytes / 1024)} KB` : `${(bytes / 1_048_576).toFixed(1)} MB`);
const date = (value: string) => new Intl.DateTimeFormat('en-NG', { dateStyle: 'medium' }).format(new Date(value));
const statusClass = (status: string) =>
    ({
        pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        processing: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        approved: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        failed: 'bg-red-50 text-red-700 ring-red-600/20',
        rejected: 'bg-red-50 text-red-700 ring-red-600/20',
    })[status] ?? 'bg-gray-50 text-gray-700 ring-gray-600/20';
</script>

<template>
    <AppLayout>
        <Head title="Knowledge Base" />

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-primary uppercase">Curriculum resources</p>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Knowledge Base</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage approved source documents for future question generation.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link v-if="canManage" :href="semanticSearch.url()" class="btn-secondary cursor-pointer justify-center">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Semantic search
                    </Link>
                    <button v-if="canManage" type="button" class="btn-primary cursor-pointer justify-center" @click="uploadOpen = !uploadOpen">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4 4 4M4 15v4a1 1 0 001 1h14a1 1 0 001-1v-4" />
                    </svg>
                    Upload document
                    </button>
                </div>
            </div>

            <form
                v-if="uploadOpen"
                class="border-y border-gray-200 bg-white py-6 dark:border-green-900 dark:bg-green-950/30"
                @submit.prevent="submitUpload"
            >
                <div class="grid gap-5 lg:grid-cols-[1fr_240px]">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="document-title" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Title</label>
                            <input id="document-title" v-model="upload.title" type="text" required maxlength="255" class="mt-1" />
                            <p v-if="upload.errors.title" class="mt-1 text-xs text-red-600">{{ upload.errors.title }}</p>
                        </div>
                        <div>
                            <label for="document-type" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Document type</label>
                            <select id="document-type" v-model="upload.document_type" required class="mt-1 cursor-pointer">
                                <option v-for="type in documentTypes" :key="type" :value="type">{{ titleCase(type) }}</option>
                            </select>
                            <p v-if="upload.errors.document_type" class="mt-1 text-xs text-red-600">{{ upload.errors.document_type }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="document-description" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Description</label>
                            <textarea id="document-description" v-model="upload.description" rows="3" maxlength="2000" class="mt-1" />
                            <p v-if="upload.errors.description" class="mt-1 text-xs text-red-600">{{ upload.errors.description }}</p>
                        </div>
                    </div>
                    <div>
                        <label for="document-file" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Source file</label>
                        <label
                            for="document-file"
                            class="mt-1 flex min-h-32 cursor-pointer flex-col items-center justify-center border border-dashed border-gray-300 bg-gray-50 px-4 text-center hover:border-primary dark:border-green-800 dark:bg-green-950/50"
                        >
                            <svg class="size-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M7 16a4 4 0 01-.88-7.9A5 5 0 1115.9 6 5 5 0 0117 15.9M15 13l-3-3m0 0-3 3m3-3v12"
                                />
                            </svg>
                            <span class="mt-2 max-w-full truncate text-xs font-semibold text-gray-700 dark:text-gray-200">{{
                                upload.file?.name ?? 'Choose a document'
                            }}</span>
                            <span class="mt-1 text-[11px] text-gray-500">PDF, Word, text, Markdown, RTF or ODT. 25 MB max.</span>
                        </label>
                        <input
                            id="document-file"
                            type="file"
                            class="sr-only"
                            accept=".pdf,.doc,.docx,.txt,.md,.rtf,.odt"
                            required
                            @change="selectFile"
                        />
                        <p v-if="upload.errors.file" class="mt-1 text-xs text-red-600">{{ upload.errors.file }}</p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-secondary cursor-pointer" @click="uploadOpen = false">Cancel</button>
                    <button type="submit" class="btn-primary cursor-pointer" :disabled="upload.processing">
                        {{ upload.processing ? 'Uploading...' : 'Save document' }}
                    </button>
                </div>
            </form>

            <form
                class="grid gap-3 border-b border-gray-200 pb-5 sm:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_160px_160px_160px_160px_auto] dark:border-green-900"
                @submit.prevent="applyFilters"
            >
                <input v-model="filters.search" type="search" placeholder="Search title or filename" aria-label="Search documents" />
                <select v-model="filters.document_type" class="cursor-pointer" aria-label="Document type">
                    <option value="">All document types</option>
                    <option v-for="type in documentTypes" :key="type" :value="type">{{ titleCase(type) }}</option>
                </select>
                <select v-model="filters.processing_status" class="cursor-pointer" aria-label="Processing status">
                    <option value="">All processing states</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
                <select v-model="filters.approval_status" class="cursor-pointer" aria-label="Approval status">
                    <option value="">All approval states</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select v-model="filters.embedding_status" class="cursor-pointer" aria-label="Embedding status">
                    <option value="">All embedding states</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="btn-secondary cursor-pointer">Filter</button>
                    <button
                        v-if="hasFilters"
                        type="button"
                        class="cursor-pointer text-sm font-semibold text-gray-500 hover:text-gray-900"
                        @click="clearFilters"
                    >
                        Clear
                    </button>
                </div>
            </form>

            <div class="overflow-hidden border border-gray-200 bg-white dark:border-green-900 dark:bg-green-950/30">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-green-900">
                        <thead class="bg-gray-50 dark:bg-green-950/60">
                            <tr class="text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase">
                                <th class="px-5 py-3">Document</th>
                                <th class="px-5 py-3">Processing</th>
                                <th class="px-5 py-3">Approval</th>
                                <th class="px-5 py-3">Embeddings</th>
                                <th class="px-5 py-3">Uploaded</th>
                                <th class="px-5 py-3 text-right"><span class="sr-only">View</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-green-900/70">
                            <tr v-for="document in documents.data" :key="document.id" class="hover:bg-gray-50/70 dark:hover:bg-green-950/60">
                                <td class="px-5 py-4">
                                    <Link
                                        :href="show.url(document.id)"
                                        class="cursor-pointer font-semibold text-gray-900 hover:text-primary dark:text-gray-100"
                                        >{{ document.title }}</Link
                                    >
                                    <p class="mt-1 max-w-md truncate text-xs text-gray-500">
                                        {{ titleCase(document.document_type) }} · {{ document.original_name }} · {{ fileSize(document.size_bytes) }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold whitespace-nowrap ring-1 ring-inset"
                                        :class="statusClass(document.processing_status)"
                                        >Processing: {{ titleCase(document.processing_status) }}</span
                                    >
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold whitespace-nowrap ring-1 ring-inset"
                                        :class="statusClass(document.approval_status)"
                                        >Approval: {{ titleCase(document.approval_status) }}</span
                                    >
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold whitespace-nowrap ring-1 ring-inset"
                                        :class="statusClass(document.embedding_status)"
                                        >Embeddings: {{ titleCase(document.embedding_status) }}</span
                                    >
                                </td>
                                <td class="px-5 py-4 text-xs whitespace-nowrap text-gray-500">
                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ document.uploader?.name ?? 'Unknown user' }}</span>
                                    <span aria-hidden="true"> · </span>
                                    <span>{{ date(document.created_at) }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <Link :href="show.url(document.id)" class="cursor-pointer text-sm font-semibold text-primary hover:underline"
                                        >View</Link
                                    >
                                </td>
                            </tr>
                            <tr v-if="documents.data.length === 0">
                                <td colspan="6" class="px-5 py-14 text-center text-sm text-gray-500">No knowledge documents match this view.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-if="documents.last_page > 1"
                    class="flex items-center justify-between border-t border-gray-200 px-5 py-3 text-sm dark:border-green-900"
                >
                    <span class="text-gray-500">Page {{ documents.current_page }} of {{ documents.last_page }}</span>
                    <div class="flex gap-2">
                        <Link v-if="documents.prev_page_url" :href="documents.prev_page_url" class="btn-secondary cursor-pointer px-3 py-1.5"
                            >Previous</Link
                        >
                        <Link v-if="documents.next_page_url" :href="documents.next_page_url" class="btn-secondary cursor-pointer px-3 py-1.5"
                            >Next</Link
                        >
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
