<script setup lang="ts">
import { Link, router, useForm, usePoll } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { confirmStructure, identify } from '@/actions/App/Http/Controllers/CurriculumMappingController';
import { index as generatorIndex } from '@/actions/App/Http/Controllers/QuestionGenerationController';
import CurriculumMappingDetails from '@/components/CurriculumMappingDetails.vue';

type DetailsProps = InstanceType<typeof CurriculumMappingDetails>['$props'];
type Mapping = DetailsProps['mappings'][number];
type Passage = { chunk_id: string; excerpt: string };
type Item = { name: string; classification: 'subtopic' | 'section'; mapping_ids: string[]; evidence: Passage[]; included: boolean };
const props = defineProps<{
    document: DetailsProps['document'] & { title: string; active_chunk_version: number; embedding_status: string };
    mappings: DetailsProps['mappings'];
    subjects: DetailsProps['subjects'];
    classes: DetailsProps['classes'];
    chunks: DetailsProps['chunks'];
    canReview: boolean;
    canCreateTopic: boolean;
    reviewToken: string;
}>();
const form = useForm({
    chunk_version: props.document.active_chunk_version,
    review_token: props.reviewToken,
    subject_id: '',
    class_level: '',
    topic: { name: '', mapping_ids: [] as string[], evidence: [] as Passage[] },
    children: [] as Item[],
});
const selectedRoot = ref('');
const dirty = ref(false);
const justConfirmed = ref(false);
const subject = computed(() => props.subjects.find((item) => item.id === form.subject_id));
const normalize = (name: string) =>
    name
        .toLowerCase()
        .replaceAll('factorisation', 'factorization')
        .replace(/equations\b/g, 'equation')
        .replace(/[^\p{L}\p{N}\p{Sm}]+/gu, ' ')
        .trim();
const visible = computed(() => props.mappings.filter((item) => !item.merged_into_id && item.status !== 'rejected'));
const roots = computed(() => {
    const all = visible.value.filter((item) => item.classification === 'topic');
    const titleMatches = all.filter((item) => normalize(props.document.title).includes(normalize(item.detected_topic)));
    const parents = new Set(visible.value.map((item) => normalize(item.detected_parent_topic ?? '')).filter(Boolean));
    const candidates = titleMatches.length
        ? titleMatches
        : all.filter((item) => parents.has(normalize(item.detected_topic)) || item.status === 'confirmed');
    const deduped = new Map<string, Mapping>();
    for (const item of candidates.length ? candidates : all) {
        const key = `${normalize(item.subject?.name ?? item.detected_subject ?? item.subject_id ?? '')}|${item.class_level ?? item.detected_class}|${normalize(item.detected_topic)}`;
        if (!deduped.has(key)) deduped.set(key, item);
    }
    return [...deduped.values()];
});
const eligible = computed(
    () =>
        props.document.approval_status === 'approved' &&
        props.document.processing_status === 'completed' &&
        props.document.embedding_status === 'completed',
);
const detecting = computed(() => ['queued', 'processing'].includes(props.document.topic_detection_status ?? ''));
const { start, stop } = usePoll(4000, { only: ['document', 'curriculumMappings', 'curriculumReviewToken'] }, { autoStart: false });
watch(detecting, (value) => (value ? start() : stop()), { immediate: true });
const quotes = (items: Mapping[]): Passage[] => {
    const unique = new Map<string, Passage>();
    for (const mapping of items)
        for (const quote of mapping.evidence) {
            if (quote.knowledge_document_chunk_id && props.chunks.some((chunk) => chunk.id === quote.knowledge_document_chunk_id)) {
                unique.set(`${quote.knowledge_document_chunk_id}|${quote.excerpt}`, {
                    chunk_id: quote.knowledge_document_chunk_id,
                    excerpt: quote.excerpt,
                });
            }
        }
    return [...unique.values()];
};
const loadStructure = (rootId: string) => {
    const root = visible.value.find((item) => item.id === rootId);
    if (!root) return;
    const rootName = normalize(root.detected_topic);
    const rootClass = root.class_level ?? root.detected_class;
    const rootSubject = root.subject_id
        ? props.subjects.find((item) => item.id === root.subject_id)
        : props.subjects.find(
              (item) =>
                  normalize(item.name) === normalize(root.detected_subject ?? '') &&
                  (props.classes[item.level] ?? []).some((option) => option.value === rootClass),
          );
    const inContext = visible.value.filter(
        (item) =>
            (item.class_level ?? item.detected_class) === rootClass &&
            (item.subject_id
                ? item.subject_id === rootSubject?.id
                : normalize(item.detected_subject ?? '') === normalize(root.detected_subject ?? rootSubject?.name ?? '')),
    );
    const rootCopies = inContext.filter((item) => item.classification === 'topic' && normalize(item.detected_topic) === rootName);
    const grouped = new Map<string, Mapping[]>();
    for (const item of inContext) {
        if (rootCopies.includes(item)) continue;
        if (item.detected_parent_topic && normalize(item.detected_parent_topic) !== rootName) continue;
        if (!item.detected_parent_topic && roots.value.length > 1) continue;
        const key = normalize(item.detected_topic);
        grouped.set(key, [...(grouped.get(key) ?? []), item]);
    }
    form.subject_id = rootSubject?.id ?? '';
    form.class_level = rootClass ?? '';
    form.topic = { name: root.detected_topic, mapping_ids: rootCopies.map((item) => item.id), evidence: quotes(rootCopies) };
    form.children = [...grouped.values()].map((items) => ({
        name: items[0].detected_topic,
        classification:
            items[0].classification === 'section' ||
            /^(common misconceptions?|learning objectives?|worked examples?|guided practice|source notes?|application\s*:)/i.test(
                items[0].detected_topic,
            )
                ? 'section'
                : 'subtopic',
        mapping_ids: items.map((item) => item.id),
        evidence: quotes(items),
        included: !props.mappings.some(
            (item) =>
                item.status === 'rejected' &&
                normalize(item.detected_topic) === normalize(items[0].detected_topic) &&
                (item.class_level ?? item.detected_class) === rootClass,
        ),
    }));
    form.chunk_version = props.document.active_chunk_version;
    form.review_token = props.reviewToken;
    dirty.value = false;
    form.clearErrors();
};
watch(
    () => props.reviewToken,
    () => {
        if (dirty.value) return;
        selectedRoot.value = roots.value.find((item) => item.id === selectedRoot.value)?.id ?? (roots.value.length === 1 ? roots.value[0].id : '');
        if (selectedRoot.value) loadStructure(selectedRoot.value);
    },
    { immediate: true },
);
const submit = () =>
    form
        .transform((data) => ({
            ...data,
            children: data.children
                .filter((item) => item.included)
                .map(({ name, classification, mapping_ids, evidence }) => ({ name, classification, mapping_ids, evidence })),
        }))
        .post(confirmStructure.url(props.document.id), {
            preserveScroll: true,
            onSuccess: () => {
                dirty.value = false;
                justConfirmed.value = true;
                selectedRoot.value =
                    roots.value.find((item) => item.id === selectedRoot.value)?.id ??
                    roots.value.find((item) => normalize(item.detected_topic) === normalize(form.topic.name))?.id ??
                    '';
                if (selectedRoot.value) loadStructure(selectedRoot.value);
            },
        });
const detect = () => router.post(identify.url(props.document.id), {}, { preserveScroll: true });
const addSubtopic = () => {
    form.children.push({ name: '', classification: 'subtopic', mapping_ids: [], evidence: [], included: true });
    dirty.value = true;
};
const toggleQuote = (item: { evidence: Passage[] }, chunk: DetailsProps['chunks'][number], checked: boolean) => {
    item.evidence = checked
        ? [...item.evidence, { chunk_id: chunk.id, excerpt: chunk.excerpt }]
        : item.evidence.filter((quote) => quote.chunk_id !== chunk.id);
    dirty.value = true;
};
</script>

<template>
    <section class="border-t border-gray-200 pt-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-bold text-gray-900">Curriculum structure</h2>
            <button
                v-if="canReview"
                type="button"
                class="btn-secondary cursor-pointer rounded-md px-3 py-2 text-xs"
                :disabled="!eligible || detecting"
                @click="detect"
            >
                {{
                    detecting
                        ? 'Identifying curriculum...'
                        : document.topic_detection_status === 'partial'
                          ? 'Continue identification'
                          : 'Identify curriculum'
                }}
            </button>
        </div>
        <p v-if="!eligible" class="mt-4 text-sm text-amber-700">
            This document must be approved, processed and embedded before curriculum confirmation.
        </p>
        <p v-if="document.topic_detection_error" class="mt-4 text-sm text-red-700">{{ document.topic_detection_error }}</p>
        <div
            v-if="justConfirmed"
            role="status"
            class="mt-4 flex flex-wrap items-center justify-between gap-3 border-l-2 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
            <span>Curriculum structure confirmed.</span><Link :href="generatorIndex.url()" class="font-bold underline">Open question generator</Link>
        </div>
        <form v-if="visible.length" class="mt-5" @submit.prevent="submit" @input="dirty = true">
            <label v-if="roots.length > 1" class="mb-5 block text-sm font-semibold text-gray-700"
                >Main topic to review
                <select v-model="selectedRoot" class="mt-2" @change="loadStructure(selectedRoot)">
                    <option value="" disabled>Select main topic</option>
                    <option v-for="item in roots" :key="item.id" :value="item.id">{{ item.detected_topic }}</option>
                </select>
            </label>
            <template v-if="selectedRoot">
                <fieldset :disabled="!canReview || form.processing || !eligible" class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-semibold text-gray-700"
                            >Subject<select v-model="form.subject_id" required class="mt-2" @change="form.class_level = ''">
                                <option value="" disabled>Select subject</option>
                                <option v-for="item in subjects" :key="item.id" :value="item.id">
                                    {{ item.name }} ({{ item.level.toUpperCase() }})
                                </option>
                            </select></label
                        >
                        <label class="block text-sm font-semibold text-gray-700"
                            >Class<select v-model="form.class_level" required class="mt-2">
                                <option value="" disabled>Select class</option>
                                <option v-for="item in classes[subject?.level ?? ''] ?? []" :key="item.value" :value="item.value">
                                    {{ item.label }}
                                </option>
                            </select></label
                        >
                    </div>
                    <label class="block text-sm font-semibold text-gray-700"
                        >Main topic<input v-model="form.topic.name" type="text" required maxlength="255" class="mt-2"
                    /></label>
                    <details class="text-xs text-gray-500">
                        <summary class="cursor-pointer font-semibold">Main topic evidence ({{ form.topic.evidence.length }})</summary>
                        <textarea
                            v-for="(quote, index) in form.topic.evidence"
                            :key="index"
                            v-model="quote.excerpt"
                            :aria-label="`Main topic passage ${index + 1}`"
                            rows="3"
                            class="mt-3"
                            minlength="12"
                            maxlength="2000"
                        ></textarea>
                        <label v-for="chunk in chunks" :key="chunk.id" class="mt-3 flex cursor-pointer items-start gap-2"
                            ><input
                                type="checkbox"
                                :checked="form.topic.evidence.some((quote) => quote.chunk_id === chunk.id)"
                                @change="toggleQuote(form.topic, chunk, ($event.target as HTMLInputElement).checked)"
                            /><span>Chunk {{ chunk.sequence }} · {{ chunk.excerpt.slice(0, 100) }}</span></label
                        >
                    </details>
                    <div class="border-y border-gray-200">
                        <div class="flex items-center justify-between py-3">
                            <h3 class="text-sm font-bold text-gray-800">Subtopics</h3>
                            <button v-if="canReview" type="button" class="cursor-pointer text-xs font-bold text-primary" @click="addSubtopic">
                                Add subtopic
                            </button>
                        </div>
                        <article
                            v-for="(item, index) in form.children"
                            :key="item.mapping_ids.join(',') || `new-${index}`"
                            class="border-t border-gray-100 py-4"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    v-model="item.included"
                                    type="checkbox"
                                    :aria-label="`Include ${item.name || 'new subtopic'}`"
                                    class="mt-3 shrink-0"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_180px]">
                                        <input
                                            v-model="item.name"
                                            type="text"
                                            :aria-label="`Subtopic ${index + 1} name`"
                                            :required="item.included"
                                            maxlength="255"
                                            :disabled="!item.included"
                                        />
                                        <select v-model="item.classification" :aria-label="`Subtopic ${index + 1} type`" :disabled="!item.included">
                                            <option value="subtopic">Subtopic</option>
                                            <option value="section">Supporting section</option>
                                        </select>
                                    </div>
                                    <details class="mt-2 text-xs text-gray-500">
                                        <summary class="cursor-pointer">
                                            Evidence ({{ item.evidence.length }})<span v-if="item.mapping_ids.length > 1">
                                                · {{ item.mapping_ids.length }} suggestions combined</span
                                            >
                                        </summary>
                                        <textarea
                                            v-for="(quote, quoteIndex) in item.evidence"
                                            :key="quoteIndex"
                                            v-model="quote.excerpt"
                                            :aria-label="`Subtopic ${index + 1} passage ${quoteIndex + 1}`"
                                            :disabled="!item.included"
                                            class="mt-3"
                                            rows="3"
                                            minlength="12"
                                            maxlength="2000"
                                        ></textarea>
                                        <label v-for="chunk in chunks" :key="chunk.id" class="mt-3 flex cursor-pointer items-start gap-2"
                                            ><input
                                                type="checkbox"
                                                :checked="item.evidence.some((quote) => quote.chunk_id === chunk.id)"
                                                @change="toggleQuote(item, chunk, ($event.target as HTMLInputElement).checked)"
                                            /><span>Chunk {{ chunk.sequence }} · {{ chunk.excerpt.slice(0, 100) }}</span></label
                                        >
                                    </details>
                                </div>
                            </div>
                        </article>
                    </div>
                </fieldset>
                <div
                    v-if="Object.keys(form.errors).length"
                    role="alert"
                    class="mt-4 border-l-2 border-red-400 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
                </div>
                <div v-if="canReview" class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <span class="text-xs text-gray-500"
                        >{{ form.children.filter((item) => item.included && item.classification === 'subtopic').length }} subtopics selected</span
                    >
                    <button type="submit" class="btn-primary cursor-pointer rounded-md" :disabled="form.processing || !eligible || detecting">
                        {{ form.processing ? 'Confirming...' : 'Confirm Curriculum Structure' }}
                    </button>
                </div>
            </template>
        </form>
        <p v-else class="mt-5 py-5 text-sm text-gray-500">
            {{ detecting ? 'Identifying the main topic and subtopics...' : 'No curriculum suggestions yet.' }}
        </p>
        <details class="mt-6 border-t border-gray-200 pt-4">
            <summary class="cursor-pointer text-xs font-semibold text-gray-500">Individual reviews and history</summary>
            <CurriculumMappingDetails v-bind="props" />
        </details>
    </section>
</template>
