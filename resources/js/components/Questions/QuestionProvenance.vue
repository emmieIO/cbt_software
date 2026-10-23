<script setup lang="ts">
import { ref } from 'vue';
import { provenance } from '@/actions/App/Http/Controllers/QuestionController';
const props = defineProps<{ questionId: string }>();
const sources = ref<
    Array<{
        id: string;
        document_title: string;
        original_name: string;
        chunk_version: number;
        page_start: number | null;
        page_end: number | null;
        curriculum_snapshot: { subject: string; topic: string; subtopic: string | null; class_level: string };
    }>
>([]);
const loading = ref(false);
const error = ref('');
const load = async () => {
    if (sources.value.length || loading.value) return;
    loading.value = true;
    try {
        const response = await fetch(provenance.url(props.questionId), { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Source history is unavailable.');
        sources.value = (await response.json()).sources;
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Source history is unavailable.';
    } finally {
        loading.value = false;
    }
};
</script>
<template>
    <details class="border-y border-gray-200 py-4" @toggle="($event.target as HTMLDetailsElement).open && load()">
        <summary class="cursor-pointer text-sm font-bold text-gray-700">AI generation source history</summary>
        <p v-if="loading" class="mt-3 text-sm text-gray-500">Loading sources...</p>
        <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
        <div v-for="source in sources" :key="source.id" class="mt-4 text-sm text-gray-600">
            <p class="font-semibold">{{ source.document_title }}</p>
            <p class="mt-1 text-xs">
                {{ source.original_name }} · Version {{ source.chunk_version }} · Page {{ source.page_start ?? '?'
                }}<span v-if="source.page_end !== source.page_start">–{{ source.page_end }}</span>
            </p>
            <p class="mt-1 text-xs">
                {{ source.curriculum_snapshot.subject }} · {{ source.curriculum_snapshot.topic
                }}<span v-if="source.curriculum_snapshot.subtopic"> · {{ source.curriculum_snapshot.subtopic }}</span>
            </p>
        </div>
    </details>
</template>
