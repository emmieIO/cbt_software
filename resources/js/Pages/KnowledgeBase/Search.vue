<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { index as documentsIndex, show as showDocument } from '@/actions/App/Http/Controllers/KnowledgeDocumentController';
import { search } from '@/actions/App/Http/Controllers/KnowledgeSearchController';
import AppLayout from '@/layouts/AppLayout.vue';

interface SearchResult {
    id: string;
    sequence: number;
    content: string;
    metadata: { page_start?: number; page_end?: number } | null;
    similarity: number;
    document: {
        id: string;
        title: string;
        document_type: string;
        original_name: string;
    };
}

const props = defineProps<{
    query: string;
    results: SearchResult[];
    retrieval: { candidate_count: number; similarity_threshold: number };
}>();

const form = useForm({ query: props.query });

const submit = () => {
    form.post(search.url(), { preserveScroll: true });
};

const pageRange = (result: SearchResult) => {
    const start = result.metadata?.page_start;
    const end = result.metadata?.page_end;

    if (!start) return 'Page unavailable';
    return start === end || !end ? `Page ${start}` : `Pages ${start}-${end}`;
};
</script>

<template>
    <AppLayout>
        <Head title="Semantic Search" />

        <div class="mx-auto max-w-5xl space-y-6">
            <div>
                <Link :href="documentsIndex.url()" class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-gray-500 hover:text-primary">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Knowledge Base
                </Link>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">Semantic Search</h1>
                <p class="mt-1 text-sm text-gray-500">Inspect matching chunks from approved, fully embedded documents.</p>
            </div>

            <form class="border-y border-gray-200 bg-white py-5" @submit.prevent="submit">
                <label for="knowledge-query" class="block text-sm font-semibold text-gray-700">Search query</label>
                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                    <input
                        id="knowledge-query"
                        v-model="form.query"
                        type="search"
                        required
                        maxlength="1000"
                        class="min-w-0 flex-1"
                        placeholder="How do quadratic equations relate to their roots?"
                    />
                    <button type="submit" class="btn-primary cursor-pointer justify-center" :disabled="form.processing">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        {{ form.processing ? 'Searching...' : 'Search' }}
                    </button>
                </div>
                <p v-if="form.errors.query" class="mt-2 text-xs font-medium text-red-600">{{ form.errors.query }}</p>
                <p class="mt-2 text-xs text-gray-500">
                    Showing up to {{ retrieval.candidate_count }} matches at {{ Math.round(retrieval.similarity_threshold * 100) }}% similarity or higher.
                </p>
            </form>

            <section v-if="query">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Results</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ results.length }} matching chunks for “{{ query }}”</p>
                    </div>
                </div>

                <div v-if="results.length" class="mt-4 divide-y divide-gray-100 border-y border-gray-200 bg-white">
                    <article v-for="result in results" :key="result.id" class="py-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <Link
                                    :href="showDocument.url(result.document.id)"
                                    class="cursor-pointer text-sm font-bold text-gray-900 hover:text-primary"
                                >
                                    {{ result.document.title }}
                                </Link>
                                <p class="mt-1 text-xs text-gray-500">Chunk {{ result.sequence }} · {{ pageRange(result) }} · {{ result.document.original_name }}</p>
                            </div>
                            <span class="bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-600/20 ring-inset">
                                {{ (result.similarity * 100).toFixed(1) }}%
                            </span>
                        </div>
                        <p class="mt-4 text-sm leading-6 whitespace-pre-wrap text-gray-700">{{ result.content }}</p>
                    </article>
                </div>
                <div v-else class="mt-4 border border-dashed border-gray-300 px-5 py-12 text-center text-sm text-gray-500">
                    No approved chunks met the configured similarity threshold.
                </div>
            </section>
        </div>
    </AppLayout>
</template>
