<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    approve,
    index,
    reprocess as reprocessDocument,
    retry as retryDocument,
    retryEmbedding as retryDocumentEmbedding,
} from '@/actions/App/Http/Controllers/KnowledgeDocumentController';
import CurriculumMappings from '@/components/CurriculumMappings.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface KnowledgeDocument {
    id: string;
    topic_detection_status: string;
    topic_detection_error: string | null;
    title: string;
    document_type: string;
    description: string | null;
    original_name: string;
    mime_type: string;
    extension: string;
    size_bytes: number;
    checksum: string;
    processing_status: string;
    approval_status: string;
    embedding_status: string;
    active_chunk_version: number;
    pending_chunk_version: number | null;
    reprocessing_status: string | null;
    reprocessing_error: string | null;
    processing_error: string | null;
    embedding_error: string | null;
    embedding_provider: string | null;
    embedding_model: string | null;
    embedding_dimensions: number | null;
    embedded_at: string | null;
    rejection_reason: string | null;
    approved_at: string | null;
    created_at: string;
    chunks_count: number;
    uploader: { name: string } | null;
    approver: { name: string } | null;
}

interface KnowledgeDocumentChunk {
    id: string;
    sequence: number;
    content: string;
    content_hash: string | null;
    estimated_token_count: number | null;
    metadata: { page_start?: number; page_end?: number } | null;
    embedding_provider: string | null;
    embedding_model: string | null;
    embedding_dimensions: number | null;
    embedded_at: string | null;
}

interface ChunksPage {
    data: KnowledgeDocumentChunk[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

type CoverageProps = InstanceType<typeof CurriculumMappings>['$props'];
const props = defineProps<{
    document: KnowledgeDocument;
    chunks: ChunksPage;
    curriculumMappings: CoverageProps['mappings'];
    curriculumReviewToken: string;
    curriculumSubjects: CoverageProps['subjects'];
    curriculumClasses: CoverageProps['classes'];
    evidenceChunks: CoverageProps['chunks'];
}>();
const page = usePage();
const retrying = ref(false);
const approving = ref(false);
const retryingEmbedding = ref(false);
const reprocessing = ref(false);
const canApprove = computed(() => Boolean((page.props.auth as any)?.user?.can_approve_knowledge_documents));
const canManage = computed(() => Boolean((page.props.auth as any)?.user?.can_manage_knowledge_base));
const canReviewCurriculum = computed(() => Boolean((page.props.auth as any)?.user?.can_review_curriculum));
const canCreateTopic = computed(() => Boolean((page.props.auth as any)?.user?.can_create_questions));

const titleCase = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (character) => character.toUpperCase());
const fileSize = (bytes: number) => (bytes < 1_048_576 ? `${Math.ceil(bytes / 1024)} KB` : `${(bytes / 1_048_576).toFixed(1)} MB`);
const dateTime = (value: string | null) =>
    value ? new Intl.DateTimeFormat('en-NG', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'Not set';
const statusClass = (status: string) =>
    ({
        pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        processing: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        embedding: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        approved: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        failed: 'bg-red-50 text-red-700 ring-red-600/20',
        rejected: 'bg-red-50 text-red-700 ring-red-600/20',
    })[status] ?? 'bg-gray-50 text-gray-700 ring-gray-600/20';

const retryProcessing = () => {
    router.post(
        retryDocument.url(props.document.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (retrying.value = true),
            onFinish: () => (retrying.value = false),
        },
    );
};

const approveDocument = () => {
    router.post(
        approve.url(props.document.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (approving.value = true),
            onFinish: () => (approving.value = false),
        },
    );
};

const retryEmbedding = () => {
    router.post(
        retryDocumentEmbedding.url(props.document.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (retryingEmbedding.value = true),
            onFinish: () => (retryingEmbedding.value = false),
        },
    );
};

const reprocess = () => {
    router.post(
        reprocessDocument.url(props.document.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (reprocessing.value = true),
            onFinish: () => (reprocessing.value = false),
        },
    );
};

const pageRange = (chunk: KnowledgeDocumentChunk) => {
    const start = chunk.metadata?.page_start;
    const end = chunk.metadata?.page_end;

    if (!start) return 'Page unavailable';
    return start === end || !end ? `Page ${start}` : `Pages ${start}–${end}`;
};
</script>

<template>
    <AppLayout>
        <Head :title="document.title" />

        <div class="mx-auto max-w-6xl space-y-6">
            <div>
                <Link
                    :href="index.url()"
                    class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-gray-500 hover:text-primary"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Knowledge Base
                </Link>
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-primary uppercase">{{ titleCase(document.document_type) }}</p>
                        <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ document.title }}</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500 dark:text-gray-400">
                            {{ document.description || 'No description was provided.' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap ring-1 ring-inset"
                            :class="statusClass(document.processing_status)"
                            >Processing: {{ titleCase(document.processing_status) }}</span
                        >
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap ring-1 ring-inset"
                            :class="statusClass(document.approval_status)"
                            >Approval: {{ titleCase(document.approval_status) }}</span
                        >
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap ring-1 ring-inset"
                            :class="statusClass(document.embedding_status)"
                            >Embeddings: {{ titleCase(document.embedding_status) }}</span
                        >
                        <span
                            v-if="document.reprocessing_status"
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap ring-1 ring-inset"
                            :class="statusClass(document.reprocessing_status)"
                            >Reprocessing: {{ titleCase(document.reprocessing_status) }}</span
                        >
                        <button
                            v-if="canApprove && document.processing_status === 'completed' && document.approval_status !== 'approved'"
                            type="button"
                            class="btn-primary cursor-pointer px-3 py-1.5 text-xs"
                            :disabled="approving"
                            @click="approveDocument"
                        >
                            {{ approving ? 'Approving...' : 'Approve document' }}
                        </button>
                        <button
                            v-if="canApprove && document.approval_status === 'approved' && document.embedding_status === 'failed'"
                            type="button"
                            class="btn-secondary cursor-pointer px-3 py-1.5 text-xs"
                            :disabled="retryingEmbedding"
                            @click="retryEmbedding"
                        >
                            {{ retryingEmbedding ? 'Queueing...' : 'Retry embeddings' }}
                        </button>
                        <button
                            v-if="
                                canManage &&
                                document.processing_status === 'completed' &&
                                !['processing', 'embedding'].includes(document.reprocessing_status ?? '')
                            "
                            type="button"
                            class="btn-secondary cursor-pointer px-3 py-1.5 text-xs"
                            :disabled="reprocessing"
                            @click="reprocess"
                        >
                            {{ reprocessing ? 'Queueing...' : 'Reprocess document' }}
                        </button>
                        <button
                            v-if="document.processing_status === 'failed'"
                            type="button"
                            class="btn-secondary cursor-pointer px-3 py-1.5 text-xs"
                            :disabled="retrying"
                            @click="retryProcessing"
                        >
                            {{ retrying ? 'Queueing...' : 'Retry processing' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-8 border-t border-gray-200 pt-6 lg:grid-cols-[minmax(0,1fr)_320px] dark:border-green-900">
                <section>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">Source document</h2>
                    <dl class="mt-4 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Original filename</dt>
                            <dd class="mt-1 text-sm font-medium break-all text-gray-900 dark:text-gray-100">{{ document.original_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">File type</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ document.extension.toUpperCase() }} · {{ document.mime_type }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">File size</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ fileSize(document.size_bytes) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Chunks</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ document.chunks_count }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold text-gray-500 uppercase">SHA-256 checksum</dt>
                            <dd class="mt-1 font-mono text-xs break-all text-gray-700 dark:text-gray-300">{{ document.checksum }}</dd>
                        </div>
                    </dl>

                    <div
                        v-if="document.processing_error || document.embedding_error || document.reprocessing_error || document.rejection_reason"
                        class="mt-7 border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm text-red-800"
                    >
                        {{ document.processing_error || document.embedding_error || document.reprocessing_error || document.rejection_reason }}
                    </div>
                </section>

                <aside class="border-l border-gray-200 pl-0 lg:pl-6 dark:border-green-900">
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">Record</h2>
                    <dl class="mt-4 space-y-5">
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Uploaded by</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ document.uploader?.name ?? 'Unknown user' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Uploaded</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ dateTime(document.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Approved by</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ document.approver?.name ?? 'Not approved' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Approved at</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ dateTime(document.approved_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Embedding model</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ document.embedding_model || 'Not embedded' }}
                                <span v-if="document.embedding_dimensions"> · {{ document.embedding_dimensions }} dimensions</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Ready for retrieval</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ dateTime(document.embedded_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Active index version</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ document.active_chunk_version }}</dd>
                        </div>
                    </dl>
                    <p class="mt-7 border-t border-gray-200 pt-4 text-xs leading-5 text-gray-500 dark:border-green-900">
                        The original file is held in private application storage and is not publicly addressable.
                    </p>
                </aside>
            </div>

            <CurriculumMappings
                :document="document"
                :mappings="curriculumMappings"
                :review-token="curriculumReviewToken"
                :subjects="curriculumSubjects"
                :classes="curriculumClasses"
                :chunks="evidenceChunks"
                :can-review="canReviewCurriculum"
                :can-create-topic="canCreateTopic"
            />

            <section class="border-t border-gray-200 pt-6 dark:border-green-900">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">Extracted chunks</h2>
                        <p class="mt-1 text-sm text-gray-500">Ordered text prepared for the next knowledge-base milestone.</p>
                    </div>
                    <span class="text-xs font-semibold text-gray-500">{{ document.chunks_count }} total</span>
                </div>

                <div v-if="chunks.data.length" class="mt-4 space-y-3">
                    <details
                        v-for="chunk in chunks.data"
                        :key="chunk.id"
                        class="group border border-gray-200 bg-white dark:border-green-900 dark:bg-green-950/30"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 marker:content-none [&::-webkit-details-marker]:hidden"
                        >
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Chunk {{ chunk.sequence }}</h3>
                                <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500">
                                    <span>{{ pageRange(chunk) }}</span>
                                    <span>{{ chunk.estimated_token_count ?? 'Unknown' }} estimated tokens</span>
                                    <span>{{ chunk.embedded_at ? 'Embedded' : 'Not embedded' }}</span>
                                </div>
                            </div>
                            <svg
                                class="size-4 shrink-0 text-gray-400 transition-transform group-open:rotate-180"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="border-t border-gray-100 px-5 py-4 dark:border-green-900/70">
                            <p class="text-sm leading-6 whitespace-pre-wrap text-gray-700 dark:text-gray-200">{{ chunk.content }}</p>
                            <p v-if="chunk.content_hash" class="mt-4 font-mono text-[10px] break-all text-gray-400">{{ chunk.content_hash }}</p>
                        </div>
                    </details>
                </div>
                <div v-else class="mt-4 border border-dashed border-gray-300 px-5 py-10 text-center text-sm text-gray-500 dark:border-green-800">
                    <span v-if="document.processing_status === 'processing'">Text extraction is currently in progress.</span>
                    <span v-else-if="document.processing_status === 'failed'">No chunks are available because processing failed.</span>
                    <span v-else>No extracted chunks are available yet.</span>
                </div>

                <div v-if="chunks.last_page > 1" class="mt-4 flex items-center justify-between text-sm">
                    <span class="text-gray-500">Page {{ chunks.current_page }} of {{ chunks.last_page }}</span>
                    <div class="flex gap-2">
                        <Link
                            v-if="chunks.prev_page_url"
                            :href="chunks.prev_page_url"
                            preserve-scroll
                            class="btn-secondary cursor-pointer px-3 py-1.5"
                            >Previous</Link
                        >
                        <Link
                            v-if="chunks.next_page_url"
                            :href="chunks.next_page_url"
                            preserve-scroll
                            class="btn-secondary cursor-pointer px-3 py-1.5"
                            >Next</Link
                        >
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
