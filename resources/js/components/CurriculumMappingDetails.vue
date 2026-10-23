<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { identify, store, update } from '@/actions/App/Http/Controllers/CurriculumMappingController';
import { index as subjectsIndex } from '@/actions/App/Http/Controllers/SubjectController';

interface Subject {
    id: string;
    name: string;
    level: string;
    topics: Array<{ id: string; name: string; class_level: string; subtopics: Array<{ id: string; name: string }> }>;
}
interface Evidence {
    knowledge_document_chunk_id: string | null;
    excerpt: string;
    page_start: number | null;
    page_end: number | null;
}
interface Mapping {
    id: string;
    classification: string;
    detected_parent_topic: string | null;
    subtopic_id: string | null;
    merged_into_id: string | null;
    hierarchy_status: string;
    reviews: Array<{
        id: string;
        action: string;
        created_at: string;
        reviewer: { name: string } | null;
        before_snapshot: { detected_topic?: string; classification?: string; status?: string };
        after_snapshot: {
            detected_topic?: string;
            detected_parent_topic?: string;
            classification?: string;
            status?: string;
            review_note?: string;
            evidence?: Evidence[];
        };
    }>;
    subject_id: string | null;
    topic_id: string | null;
    class_level: string | null;
    detected_topic: string;
    detected_subject: string | null;
    detected_class: string | null;
    section_heading: string | null;
    status: string;
    evidence_status: string;
    provenance: string;
    reviewer: { name: string } | null;
    reviewed_at: string | null;
    evidence: Evidence[];
    subject: { name: string } | null;
    topic: { name: string } | null;
}
const props = defineProps<{
    document: {
        id: string;
        approval_status: string;
        processing_status: string;
        topic_detection_status?: string;
        topic_detection_error?: string | null;
    };
    mappings: Mapping[];
    subjects: Subject[];
    classes: Record<string, Array<{ value: string; label: string }>>;
    chunks: Array<{ id: string; sequence: number; excerpt: string; metadata: { page_start?: number; page_end?: number } | null }>;
    canReview: boolean;
    canCreateTopic: boolean;
}>();
const active = ref<Mapping | null>(null);
const formOpen = ref(false);
const submittingDetection = ref(false);
const form = useForm({
    action: 'confirm',
    classification: 'topic',
    suggested_name: '',
    subtopic_id: '',
    new_subtopic_name: '',
    merge_target_id: '',
    subject_id: '',
    class_level: '',
    topic_id: '',
    new_topic_name: '',
    review_note: '',
    evidence: [] as Array<{ chunk_id: string; excerpt: string }>,
});
const subject = computed(() => props.subjects.find((item) => item.id === form.subject_id));
const topics = computed(() => subject.value?.topics.filter((item) => item.class_level === form.class_level) ?? []);
const subtopics = computed(() => topics.value.find((item) => item.id === form.topic_id)?.subtopics ?? []);
const visibleMappings = computed(() => props.mappings.filter((item) => !item.merged_into_id));
const groups = computed(() => {
    const result = new Map<string, Mapping[]>();
    for (const item of visibleMappings.value) {
        const parent =
            item.classification === 'topic'
                ? (item.topic?.name ?? item.detected_topic)
                : (item.topic?.name ?? item.detected_parent_topic ?? 'Parent needs review');
        const key = `${item.subject?.name ?? item.detected_subject ?? 'Subject needs review'} · ${classLabel(item.class_level ?? item.detected_class)} · ${parent}`;
        result.set(key, [...(result.get(key) ?? []), item]);
    }
    return Array.from(result, ([label, items]) => ({
        label,
        items: items.sort((a, b) => Number(b.classification === 'topic') - Number(a.classification === 'topic')),
    }));
});
const mergeCandidates = computed(() =>
    visibleMappings.value.filter(
        (item) =>
            item.id !== active.value?.id &&
            item.status === 'suggested' &&
            !item.reviewed_at &&
            item.classification === active.value?.classification &&
            item.subject_id === active.value?.subject_id &&
            item.class_level === active.value?.class_level &&
            item.topic_id === active.value?.topic_id &&
            item.detected_parent_topic === active.value?.detected_parent_topic,
    ),
);
const eligible = computed(() => props.document.approval_status === 'approved' && props.document.processing_status === 'completed');
const detecting = computed(() => ['queued', 'processing'].includes(props.document.topic_detection_status ?? ''));
const openReview = (mapping: Mapping | null) => {
    active.value = mapping;
    form.reset();
    form.clearErrors();
    Object.assign(form, {
        classification: mapping?.classification ?? 'topic',
        suggested_name: mapping?.detected_topic ?? '',
        subtopic_id: mapping?.subtopic_id ?? '',
        new_subtopic_name: mapping?.classification === 'subtopic' ? mapping.detected_topic : '',
        subject_id: mapping?.subject_id ?? '',
        class_level: mapping?.class_level ?? '',
        topic_id: mapping?.topic_id ?? '',
        new_topic_name: mapping?.topic_id
            ? ''
            : mapping?.classification === 'topic'
              ? mapping.detected_topic
              : (mapping?.detected_parent_topic ?? ''),
        evidence:
            mapping?.evidence
                .filter((item) => props.chunks.some((chunk) => chunk.id === item.knowledge_document_chunk_id))
                .map((item) => ({ chunk_id: item.knowledge_document_chunk_id!, excerpt: item.excerpt })) ?? [],
    });
    formOpen.value = true;
};
const toggleEvidence = (chunk: (typeof props.chunks)[number], checked: boolean) => {
    form.evidence = checked
        ? [...form.evidence, { chunk_id: chunk.id, excerpt: chunk.excerpt }]
        : form.evidence.filter((item) => item.chunk_id !== chunk.id);
};
const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
        },
    };
    if (active.value) form.patch(update.url({ knowledge_document: props.document.id, curriculum_mapping: active.value.id }), options);
    else form.post(store.url(props.document.id), options);
};
const reject = (mapping: Mapping) =>
    router.patch(
        update.url({ knowledge_document: props.document.id, curriculum_mapping: mapping.id }),
        { action: 'reject' },
        { preserveScroll: true },
    );
const detect = () =>
    router.post(
        identify.url(props.document.id),
        {},
        {
            preserveScroll: true,
            onStart: () => {
                submittingDetection.value = true;
            },
            onFinish: () => {
                submittingDetection.value = false;
            },
        },
    );
const classLabel = (value: string | null) =>
    Object.values(props.classes)
        .flat()
        .find((item) => item.value === value)?.label ?? 'Class not identified';
</script>

<template>
    <section class="border-t border-gray-200 pt-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-gray-900">Individual reviews and history</h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ visibleMappings.filter((item) => item.status === 'confirmed' && item.evidence_status === 'current').length }} confirmed ·
                    {{ visibleMappings.filter((item) => item.status === 'suggested' || item.evidence_status === 'needs_review').length }} awaiting
                    review
                </p>
            </div>
            <div v-if="canReview" class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="btn-secondary rounded-md px-3 py-2 text-xs"
                    :disabled="!eligible || detecting || submittingDetection"
                    @click="detect"
                >
                    {{
                        detecting
                            ? 'Identifying topics...'
                            : document.topic_detection_status === 'partial'
                              ? 'Continue topic identification'
                              : 'Identify topics'
                    }}</button
                ><button type="button" class="btn-primary rounded-md px-3 py-2 text-xs" :disabled="!eligible" @click="openReview(null)">
                    Add mapping
                </button>
            </div>
        </div>
        <p v-if="!eligible" class="mt-4 text-sm text-amber-700">
            Document approval and completed processing are required to confirm curriculum coverage.
        </p>
        <p v-if="document.topic_detection_error" class="mt-4 border-l-2 border-red-400 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ document.topic_detection_error }}
        </p>
        <p v-if="document.topic_detection_status === 'partial'" class="mt-3 text-xs text-gray-500">
            Identification paused at a saved checkpoint. Continue to scan the remaining source passages.
        </p>
        <div v-if="mappings.length" class="mt-5 divide-y divide-gray-200 border-y border-gray-200">
            <section v-for="group in groups" :key="group.label" class="py-3">
                <h3 class="border-b border-gray-100 py-3 text-sm font-bold text-gray-800">{{ group.label }}</h3>
                <article
                    v-for="mapping in group.items"
                    :key="mapping.id"
                    class="py-5"
                    :class="mapping.classification === 'topic' ? '' : 'ml-5 border-l-2 border-gray-100 pl-4'"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-gray-900">{{ mapping.detected_topic }}</h3>
                            <p class="mt-1 text-xs font-semibold text-gray-500">
                                {{
                                    mapping.classification === 'topic'
                                        ? 'Main topic'
                                        : mapping.classification === 'subtopic'
                                          ? 'Subtopic'
                                          : 'Supporting section'
                                }}<span v-if="mapping.hierarchy_status === 'needs_review'"> · Hierarchy needs review</span>
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ mapping.subject?.name ?? mapping.detected_subject ?? 'Subject not identified' }} ·
                                {{ classLabel(mapping.class_level ?? mapping.detected_class) }}
                            </p>
                            <p v-if="mapping.section_heading" class="mt-1 text-xs text-gray-500">{{ mapping.section_heading }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded px-2 py-1 text-xs font-semibold capitalize"
                                :class="
                                    mapping.evidence_status === 'needs_review'
                                        ? 'bg-amber-50 text-amber-700'
                                        : mapping.status === 'confirmed'
                                          ? 'bg-emerald-50 text-emerald-700'
                                          : 'bg-gray-100 text-gray-600'
                                "
                                >{{ mapping.evidence_status === 'needs_review' ? 'Evidence needs review' : mapping.status }}</span
                            ><button
                                v-if="canReview && eligible"
                                type="button"
                                class="rounded-md border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-700 hover:border-primary"
                                @click="openReview(mapping)"
                            >
                                Review</button
                            ><button
                                v-if="canReview && mapping.status !== 'rejected'"
                                type="button"
                                class="px-2 py-1.5 text-xs font-semibold text-red-600"
                                @click="reject(mapping)"
                            >
                                Reject
                            </button>
                        </div>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-3 text-[11px] text-gray-400">
                        <span>{{ mapping.provenance === 'ai' ? 'AI suggestion' : 'Manually added' }}</span
                        ><span v-if="mapping.reviewer"
                            >Reviewed by {{ mapping.reviewer.name }} · {{ new Date(mapping.reviewed_at!).toLocaleDateString() }}</span
                        >
                    </div>
                    <details class="mt-3">
                        <summary class="cursor-pointer text-xs font-semibold text-primary">
                            Supporting passages ({{ mapping.evidence.length }})
                        </summary>
                        <blockquote
                            v-for="(item, index) in mapping.evidence"
                            :key="index"
                            class="mt-3 border-l-2 border-gray-200 pl-4 text-sm leading-6 text-gray-600"
                        >
                            <span class="mb-1 block text-[11px] font-bold text-gray-400"
                                >{{ item.page_start ? `Page ${item.page_start}` : 'Page unavailable'
                                }}{{ item.knowledge_document_chunk_id ? '' : ' · Previous version' }}</span
                            >{{ item.excerpt }}
                        </blockquote>
                    </details>
                    <details v-if="mapping.reviews?.length" class="mt-3">
                        <summary class="cursor-pointer text-xs font-semibold text-gray-500">Review history ({{ mapping.reviews.length }})</summary>
                        <details v-for="review in mapping.reviews" :key="review.id" class="mt-3 border-l border-gray-200 pl-3 text-xs text-gray-500">
                            <summary class="cursor-pointer">
                                {{ review.action.replaceAll('_', ' ') }} · {{ review.reviewer?.name ?? 'System' }} ·
                                {{ new Date(review.created_at).toLocaleString() }}
                            </summary>
                            <p class="mt-2">
                                {{ review.before_snapshot.detected_topic ?? 'New mapping' }} ({{ review.before_snapshot.status ?? 'New' }}) →
                                {{ review.after_snapshot.detected_topic }} ({{ review.after_snapshot.status }})
                            </p>
                            <p class="mt-1 capitalize">
                                {{ review.after_snapshot.classification }} · {{ review.after_snapshot.detected_parent_topic ?? 'Main topic' }}
                            </p>
                            <p v-if="review.after_snapshot.review_note" class="mt-2">{{ review.after_snapshot.review_note }}</p>
                            <blockquote
                                v-for="(quote, index) in review.after_snapshot.evidence ?? []"
                                :key="index"
                                class="mt-2 border-l border-gray-100 pl-3 leading-5"
                            >
                                {{ quote.excerpt }}
                            </blockquote>
                        </details>
                    </details>
                </article>
            </section>
        </div>
        <div v-else class="mt-5 border border-dashed border-gray-300 px-5 py-8 text-center text-sm text-gray-500">
            {{
                detecting
                    ? 'Topic identification is in progress.'
                    : 'No curriculum topics have been mapped. Identify topics or add a mapping to begin.'
            }}
        </div>

        <Teleport to="body"
            ><div v-if="formOpen" class="fixed inset-0 z-[70] flex justify-end bg-black/30" @click.self="formOpen = false">
                <section
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="mapping-review-title"
                    class="flex h-dvh w-full max-w-2xl flex-col bg-white shadow-xl"
                    @keydown.esc="formOpen = false"
                >
                    <header class="flex shrink-0 items-center justify-between border-b border-gray-200 px-6 py-5">
                        <h2 id="mapping-review-title" class="text-base font-bold">
                            {{ active ? 'Review curriculum mapping' : 'Add curriculum mapping' }}
                        </h2>
                        <button type="button" class="size-8 text-xl text-gray-500" aria-label="Close review" @click="formOpen = false">×</button>
                    </header>
                    <form class="min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain px-6 py-5" @submit.prevent="submit">
                        <label class="block text-sm font-semibold"
                            >Classification
                            <select v-model="form.classification" class="mt-2">
                                <option value="topic">Main topic</option>
                                <option value="subtopic">Subtopic</option>
                                <option value="section">Supporting lesson section</option>
                            </select>
                        </label>
                        <label class="block text-sm font-semibold"
                            >Suggestion name<input v-model="form.suggested_name" class="mt-2" maxlength="255"
                        /></label>
                        <label class="block text-sm font-semibold"
                            >Subject<select
                                v-model="form.subject_id"
                                required
                                class="mt-2"
                                @change="
                                    form.class_level = '';
                                    form.topic_id = '';
                                "
                            >
                                <option value="" disabled>Select subject</option>
                                <option v-for="item in subjects" :key="item.id" :value="item.id">
                                    {{ item.name }} ({{ item.level.toUpperCase() }})
                                </option>
                            </select></label
                        >
                        <Link v-if="canCreateTopic" :href="subjectsIndex.url()" class="inline-flex text-xs font-semibold text-primary hover:underline"
                            >Manage subjects</Link
                        >
                        <label class="block text-sm font-semibold"
                            >Class<select v-model="form.class_level" required :disabled="!subject" class="mt-2" @change="form.topic_id = ''">
                                <option value="" disabled>Select class</option>
                                <option v-for="item in classes[subject?.level ?? ''] ?? []" :key="item.value" :value="item.value">
                                    {{ item.label }}
                                </option>
                            </select></label
                        >
                        <label class="block text-sm font-semibold"
                            >Existing topic<select v-model="form.topic_id" class="mt-2">
                                <option value="">{{ canCreateTopic ? 'Create or match a new topic below' : 'Select topic' }}</option>
                                <option v-for="item in topics" :key="item.id" :value="item.id">{{ item.name }}</option>
                            </select></label
                        >
                        <label v-if="!form.topic_id && canCreateTopic" class="block text-sm font-semibold"
                            >New topic name<input v-model="form.new_topic_name" type="text" required maxlength="255" class="mt-2"
                        /></label>
                        <template v-if="form.classification !== 'topic'">
                            <label class="block text-sm font-semibold"
                                >{{ form.classification === 'subtopic' ? 'Existing subtopic' : 'Subtopic (optional)' }}
                                <select v-model="form.subtopic_id" class="mt-2">
                                    <option value="">{{ form.classification === 'subtopic' ? 'Create or match below' : 'Parent topic only' }}</option>
                                    <option v-for="item in subtopics" :key="item.id" :value="item.id">{{ item.name }}</option>
                                </select>
                            </label>
                            <label
                                v-if="form.classification === 'subtopic' && !form.subtopic_id && canCreateTopic"
                                class="block text-sm font-semibold"
                                >New subtopic name<input v-model="form.new_subtopic_name" class="mt-2" maxlength="255"
                            /></label>
                        </template>
                        <fieldset
                            v-if="active && active.status === 'suggested' && !active.reviewed_at && mergeCandidates.length"
                            class="border-y border-gray-200 py-4"
                        >
                            <label class="block text-sm font-semibold"
                                >Merge into suggestion<select v-model="form.merge_target_id" class="mt-2">
                                    <option value="">Select duplicate</option>
                                    <option v-for="item in mergeCandidates" :key="item.id" :value="item.id">{{ item.detected_topic }}</option>
                                </select></label
                            >
                            <button
                                type="button"
                                class="btn-secondary mt-3 rounded-md text-xs"
                                :disabled="!form.merge_target_id || form.processing"
                                @click="
                                    form.action = 'merge';
                                    submit();
                                "
                            >
                                Merge evidence
                            </button>
                        </fieldset>
                        <fieldset>
                            <legend class="text-sm font-bold">Source evidence</legend>
                            <p class="mt-1 text-xs text-gray-500">{{ form.evidence.length }} passages selected</p>
                            <label
                                v-for="(item, index) in form.evidence"
                                :key="`${item.chunk_id}-${index}`"
                                class="mt-3 block text-xs font-semibold text-gray-600"
                                >Passage {{ index + 1 }}
                                <textarea v-model="item.excerpt" rows="3" class="mt-2" minlength="12" maxlength="2000"></textarea>
                            </label>
                            <div class="mt-3 max-h-72 divide-y divide-gray-100 overflow-y-auto rounded-md border border-gray-200">
                                <label v-for="chunk in chunks" :key="chunk.id" class="flex cursor-pointer items-start gap-3 p-3"
                                    ><input
                                        type="checkbox"
                                        class="mt-1 shrink-0"
                                        :checked="form.evidence.some((item) => item.chunk_id === chunk.id)"
                                        @change="toggleEvidence(chunk, ($event.target as HTMLInputElement).checked)"
                                    /><span class="min-w-0"
                                        ><span class="block text-xs font-bold text-gray-700"
                                            >Chunk {{ chunk.sequence }} ·
                                            {{ chunk.metadata?.page_start ? `Page ${chunk.metadata.page_start}` : 'Page unavailable' }}</span
                                        ><span class="mt-1 line-clamp-3 block text-xs leading-5 text-gray-500">{{ chunk.excerpt }}</span></span
                                    ></label
                                >
                            </div>
                        </fieldset>
                        <label class="block text-sm font-semibold"
                            >Review note<textarea v-model="form.review_note" rows="2" maxlength="2000" class="mt-2"></textarea>
                        </label>
                        <div v-if="Object.keys(form.errors).length" class="bg-red-50 p-3 text-sm text-red-700">
                            <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
                        </div>
                        <div class="sticky bottom-0 flex justify-end gap-2 border-t border-gray-200 bg-white py-4">
                            <button
                                v-if="active?.status === 'suggested'"
                                type="button"
                                class="btn-secondary rounded-md"
                                :disabled="form.processing"
                                @click="
                                    form.action = 'save';
                                    submit();
                                "
                            >
                                Save changes
                            </button>
                            <button type="button" class="btn-secondary rounded-md" @click="formOpen = false">Cancel</button
                            ><button type="submit" class="btn-primary rounded-md" :disabled="form.processing" @click="form.action = 'confirm'">
                                {{ form.processing ? 'Saving...' : 'Confirm coverage' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div></Teleport
        >
    </section>
</template>
