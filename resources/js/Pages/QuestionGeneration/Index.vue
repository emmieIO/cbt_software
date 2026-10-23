<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { show, store, sources as sourcePreview } from '@/actions/App/Http/Controllers/QuestionGenerationController';
import AppLayout from '@/layouts/AppLayout.vue';

interface Topic {
    id: string;
    name: string;
    class_level: string | null;
    document_count: number;
    subtopics: Array<{ id: string; name: string; document_count: number }>;
}
interface Subject {
    id: string;
    name: string;
    level: string;
    topics: Topic[];
}
interface Run {
    id: string;
    status: string;
    requested_count: number;
    valid_drafts_count: number;
    difficulty: string;
    created_at: string;
    subject: { name: string };
    topic: { name: string };
}

const props = defineProps<{
    subjects: Subject[];
    levels: Array<{ value: string; label: string }>;
    classLevels: Record<string, Array<{ value: string; label: string }>>;
    maxQuestions: number;
    runs: { data: Run[]; prev_page_url: string | null; next_page_url: string | null };
}>();

const form = useForm({
    level: '',
    class_level: '',
    subject_id: '',
    topic_id: '',
    subtopic_id: '',
    quantity: 5,
    type_distribution: { multiple_choice: 5, short_answer: 0, theory: 0 },
    difficulty: 'medium',
    additional_instructions: '',
});

const subjects = computed(() => props.subjects);
const subject = computed(() => subjects.value.find((item) => item.id === form.subject_id));
const topics = computed(() => (subject.value?.topics ?? []).filter((topic) => topic.class_level === form.class_level));
const distributionTotal = computed(() => Object.values(form.type_distribution).reduce((sum, value) => sum + Number(value || 0), 0));
watch(
    distributionTotal,
    (total) => {
        form.quantity = total;
    },
    { immediate: true, flush: 'sync' },
);
const validTotal = computed(() => distributionTotal.value >= 1 && distributionTotal.value <= props.maxQuestions);
const activeTab = ref('create');
const selectedClass = computed(() => props.classLevels[form.level]?.find((item) => item.value === form.class_level)?.label);
const selectedTopic = computed(() => topics.value.find((item) => item.id === form.topic_id));
watch(
    () => form.topic_id,
    () => {
        form.subtopic_id = '';
    },
);

const availableClasses = computed(() =>
    (props.classLevels[subject.value?.level ?? ''] ?? []).filter((item) => subject.value?.topics.some((topic) => topic.class_level === item.value)),
);
const resetCurriculum = () => {
    form.level = subject.value?.level ?? '';
    form.class_level = availableClasses.value[0]?.value ?? '';
    form.topic_id = '';
};
interface CurriculumSource {
    document_id: string;
    document_title: string;
    evidence: Array<{ chunk_id: string; excerpt: string; page_start: number | null; page_end: number | null }>;
}
const curriculumSources = ref<CurriculumSource[]>([]);
const loadingSources = ref(false);
const sourceError = ref('');
watch(
    () => [form.topic_id, form.subtopic_id],
    async ([topicId, subtopicId], _, onCleanup) => {
        curriculumSources.value = [];
        sourceError.value = '';
        loadingSources.value = false;
        if (!topicId) return;
        const controller = new AbortController();
        onCleanup(() => controller.abort());
        loadingSources.value = true;
        try {
            const response = await fetch(sourcePreview.url({ query: { topic_id: topicId, subtopic_id: subtopicId || undefined } }), {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Source preview could not be loaded. Select the topic again to retry.');
            const payload = await response.json();
            if (!controller.signal.aborted) curriculumSources.value = payload.sources;
        } catch (error) {
            if (!controller.signal.aborted) sourceError.value = error instanceof Error ? error.message : 'Source preview unavailable.';
        } finally {
            if (!controller.signal.aborted) loadingSources.value = false;
        }
    },
);
const resetTopic = () => {
    form.topic_id = '';
};
const submit = () => form.post(store.url());
const titleCase = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
const dateTime = (value: string) => new Intl.DateTimeFormat('en-NG', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
const statusClass = (status: string) =>
    ({
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        partial: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        failed: 'bg-red-50 text-red-700 ring-red-600/20',
        generating: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        retrieving: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        pending: 'bg-gray-100 text-gray-700 ring-gray-600/20',
    })[status] ?? 'bg-gray-100 text-gray-700 ring-gray-600/20';
</script>

<template>
    <AppLayout>
        <Head title="AI Question Generator" />
        <div class="generation-workspace relative mx-auto max-w-7xl space-y-6">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Question generator</h1>
                    <p class="mt-2 text-sm text-gray-500">Prepare questions from your approved teaching materials.</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600"
                    ><span class="size-1.5 rounded-full" :class="subjects.length ? 'bg-primary' : 'bg-gray-400'"></span>
                    {{ subjects.length ? 'Confirmed curriculum sources' : 'Curriculum coverage required' }}</span
                >
            </header>

            <nav class="flex gap-7 border-b border-gray-200" aria-label="Generator views">
                <button
                    v-for="tab in [
                        { value: 'create', label: 'New generation' },
                        { value: 'history', label: 'Generation history' },
                    ]"
                    :key="tab.value"
                    type="button"
                    class="-mb-px border-b-2 px-0 py-3 text-sm font-bold transition-colors"
                    :class="activeTab === tab.value ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-900'"
                    :aria-current="activeTab === tab.value ? 'page' : undefined"
                    @click="activeTab = tab.value"
                >
                    {{ tab.label }}
                </button>
            </nav>
            <div v-if="activeTab === 'create'" class="grid items-start gap-8 xl:grid-cols-[minmax(0,1fr)_280px]">
                <form class="min-w-0" @submit.prevent="submit">
                    <section class="generation-section">
                        <div class="section-heading">
                            <span class="section-number">01</span>
                            <h2>Curriculum</h2>
                        </div>
                        <div class="grid gap-x-5 gap-y-5 sm:grid-cols-2">
                            <label class="block text-sm font-semibold text-gray-700"
                                >Subject
                                <select v-model="form.subject_id" required class="mt-2 w-full" @change="resetCurriculum">
                                    <option value="" disabled>Select subject</option>
                                    <option v-for="item in subjects" :key="item.id" :value="item.id">
                                        {{ item.name }} · {{ levels.find((level) => level.value === item.level)?.label }}
                                    </option>
                                </select>
                            </label>
                            <label class="block text-sm font-semibold text-gray-700"
                                >Class
                                <select v-model="form.class_level" :disabled="!form.subject_id" class="mt-2 w-full" @change="resetTopic">
                                    <option value="" disabled>Select class</option>
                                    <option v-for="item in availableClasses" :key="item.value" :value="item.value">{{ item.label }}</option>
                                </select>
                            </label>
                            <label class="block text-sm font-semibold text-gray-700 sm:col-span-2"
                                >Curriculum topic
                                <span v-if="form.subject_id" class="ml-2 text-xs font-medium text-gray-400">{{ topics.length }} available</span>
                                <select
                                    v-model="form.topic_id"
                                    required
                                    :disabled="!form.subject_id"
                                    class="mt-2 w-full disabled:bg-gray-50 disabled:text-gray-400"
                                >
                                    <option value="" disabled>Select topic</option>
                                    <option v-for="item in topics" :key="item.id" :value="item.id">
                                        {{ item.name }} · {{ item.document_count }} document{{ item.document_count === 1 ? '' : 's' }}
                                    </option>
                                </select>
                            </label>
                        </div>
                        <p v-if="!subjects.length" class="mt-4 border-l-2 border-amber-400 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            No confirmed curriculum coverage is available. A curriculum reviewer must map an approved, embedded document to its
                            subject, class and topics first.
                        </p>
                        <label v-if="form.topic_id" class="mt-5 block text-sm font-semibold text-gray-700"
                            >Subtopic
                            <select v-model="form.subtopic_id" class="mt-2">
                                <option value="">Entire topic</option>
                                <option v-for="item in selectedTopic?.subtopics ?? []" :key="item.id" :value="item.id">
                                    {{ item.name }} · {{ item.document_count }} sources
                                </option>
                            </select>
                        </label>
                        <div v-if="form.topic_id" class="mt-5 border-t border-gray-200 pt-5">
                            <h3 class="text-sm font-bold text-gray-800">
                                Supporting curriculum
                                <span class="ml-2 text-xs font-medium text-gray-500"
                                    >{{ curriculumSources.length }} document{{ curriculumSources.length === 1 ? '' : 's' }}</span
                                >
                            </h3>
                            <div v-if="loadingSources" class="mt-3 h-20 animate-pulse rounded-md bg-gray-100"></div>
                            <p v-else-if="sourceError" class="mt-3 text-sm text-red-600">{{ sourceError }}</p>
                            <p v-else-if="!curriculumSources.length" class="mt-3 text-sm text-amber-700">
                                This topic no longer has eligible confirmed coverage. Select another topic or ask a reviewer to check its mappings.
                            </p>
                            <details
                                v-for="source in curriculumSources"
                                :key="source.document_id"
                                class="mt-3 rounded-md border border-gray-200 bg-white"
                            >
                                <summary class="cursor-pointer px-4 py-3 text-xs font-bold text-gray-800">
                                    {{ source.document_title
                                    }}<span class="ml-2 font-medium text-gray-400"
                                        >{{ source.evidence.length }} passage{{ source.evidence.length === 1 ? '' : 's' }}</span
                                    >
                                </summary>
                                <div class="divide-y divide-gray-100 border-t border-gray-100 px-4">
                                    <blockquote
                                        v-for="evidence in source.evidence"
                                        :key="evidence.chunk_id"
                                        class="py-3 text-xs leading-6 text-gray-600"
                                    >
                                        <span class="block text-[11px] font-bold text-gray-400">{{
                                            evidence.page_start ? `Page ${evidence.page_start}` : 'Page unavailable'
                                        }}</span
                                        >{{ evidence.excerpt }}
                                    </blockquote>
                                </div>
                            </details>
                        </div>
                    </section>

                    <section class="generation-section">
                        <div class="section-heading">
                            <span class="section-number">02</span>
                            <h2>Question composition</h2>
                        </div>
                        <div class="grid grid-cols-2 items-start gap-4 sm:grid-cols-4">
                            <label class="flex min-w-0 flex-col gap-2 text-xs font-semibold text-gray-700"
                                ><span class="min-h-5 leading-5">Total questions</span>
                                <input :value="distributionTotal" type="number" readonly class="h-11 w-full bg-gray-50" />
                            </label>
                            <label class="flex min-w-0 flex-col gap-2 text-xs font-semibold text-gray-700"
                                ><span class="min-h-5 leading-5">Multiple choice</span>
                                <input
                                    v-model.number="form.type_distribution.multiple_choice"
                                    type="number"
                                    min="0"
                                    :max="maxQuestions"
                                    class="h-11 w-full"
                                />
                            </label>
                            <label class="flex min-w-0 flex-col gap-2 text-xs font-semibold text-gray-700"
                                ><span class="min-h-5 leading-5">Short answer</span>
                                <input
                                    v-model.number="form.type_distribution.short_answer"
                                    type="number"
                                    min="0"
                                    :max="maxQuestions"
                                    class="h-11 w-full"
                                />
                            </label>
                            <label class="flex min-w-0 flex-col gap-2 text-xs font-semibold text-gray-700"
                                ><span class="min-h-5 leading-5">Theory</span>
                                <input v-model.number="form.type_distribution.theory" type="number" min="0" :max="maxQuestions" class="h-11 w-full" />
                            </label>
                        </div>
                        <p class="mt-3 text-xs" :class="validTotal ? 'text-gray-500' : 'font-semibold text-red-600'" aria-live="polite">
                            {{
                                distributionTotal < 1
                                    ? 'Choose at least one question.'
                                    : distributionTotal > maxQuestions
                                      ? `Total exceeds the ${maxQuestions}-question limit.`
                                      : `Maximum ${maxQuestions} questions per request.`
                            }}
                        </p>
                        <fieldset class="mt-6 max-w-lg">
                            <legend class="text-sm font-semibold text-gray-700">Difficulty</legend>
                            <div class="mt-2 grid grid-cols-4 gap-1 rounded-md border border-gray-200 bg-gray-100 p-1">
                                <label v-for="value in ['easy', 'medium', 'hard', 'mixed']" :key="value" class="relative min-w-0 cursor-pointer">
                                    <input v-model="form.difficulty" name="generation-difficulty" type="radio" :value="value" class="peer sr-only" />
                                    <span
                                        class="flex h-9 items-center justify-center rounded px-2 text-center text-xs font-bold capitalize transition-colors peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary"
                                        :class="
                                            form.difficulty === value
                                                ? 'bg-white text-primary shadow-sm ring-1 ring-black/5'
                                                : 'text-gray-500 hover:text-gray-800'
                                        "
                                        >{{ value }}</span
                                    >
                                </label>
                            </div>
                        </fieldset>
                    </section>

                    <section class="generation-section">
                        <div class="section-heading">
                            <span class="section-number">03</span>
                            <h2>Teacher's brief <span class="ml-2 text-xs font-medium text-gray-400">Optional</span></h2>
                        </div>
                        <label class="block text-sm font-semibold text-gray-700"
                            ><span class="sr-only">Additional instructions</span>
                            <textarea
                                v-model="form.additional_instructions"
                                rows="3"
                                maxlength="1000"
                                class="mt-2 w-full"
                                placeholder="Optional focus, wording, or assessment guidance"
                            ></textarea>
                        </label>
                    </section>

                    <div v-if="Object.keys(form.errors).length" class="mt-4 border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 py-5">
                        <span class="text-xs font-medium text-gray-500">Up to {{ maxQuestions }} questions per request</span>
                        <button
                            type="submit"
                            class="inline-flex h-11 items-center justify-center gap-3 rounded-md bg-primary px-5 text-sm font-bold text-white shadow-sm hover:bg-primary/90"
                            :disabled="form.processing || !validTotal || loadingSources || !curriculumSources.length"
                        >
                            {{ form.processing ? 'Queuing...' : 'Generate drafts' }}
                        </button>
                    </div>
                </form>
                <aside class="border-l border-gray-200 pl-6 xl:sticky xl:top-6">
                    <h2 class="text-xs font-bold text-gray-400 uppercase">Generation brief</h2>
                    <p v-if="selectedTopic" class="mt-3 text-xs font-semibold text-primary">
                        {{ selectedTopic.document_count }} supporting document{{ selectedTopic.document_count === 1 ? '' : 's' }}
                    </p>
                    <dl class="mt-5 space-y-5 text-sm">
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Subject</dt>
                            <dd class="mt-1 font-bold text-gray-900">{{ subject?.name || 'Not selected' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Class</dt>
                            <dd class="mt-1 font-bold text-gray-900">{{ selectedClass || 'Not selected' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Topic</dt>
                            <dd class="mt-1 font-bold break-words text-gray-900">{{ selectedTopic?.name || 'Not selected' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-6 flex items-baseline gap-2 border-t border-gray-200 pt-5">
                        <span class="text-3xl font-bold text-gray-900">{{ form.quantity || 0 }}</span
                        ><span class="text-sm font-medium text-gray-500">questions</span>
                    </div>
                    <div class="mt-4 space-y-2 text-xs text-gray-600">
                        <div class="flex justify-between">
                            <span>Multiple choice</span><strong>{{ form.type_distribution.multiple_choice || 0 }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span>Short answer</span><strong>{{ form.type_distribution.short_answer || 0 }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span>Theory</span><strong>{{ form.type_distribution.theory || 0 }}</strong>
                        </div>
                    </div>
                    <p class="mt-6 border-t border-gray-200 pt-4 text-xs leading-5 text-gray-500">
                        Drafts are saved for your review before they enter the question bank.
                    </p>
                </aside>
            </div>

            <section v-else>
                <div class="flex items-end justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Generation history</h2>
                        <p class="mt-1 text-sm text-gray-500">Your requests and saved draft previews.</p>
                    </div>
                </div>
                <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 bg-white">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-3">Topic</th>
                                <th class="px-4 py-3">Request</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Created</th>
                                <th class="px-4 py-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="run in runs.data" :key="run.id">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-gray-900">{{ run.topic.name }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ run.subject.name }}</p>
                                </td>
                                <td class="px-4 py-4 text-gray-600">{{ run.requested_count }} · {{ titleCase(run.difficulty) }}</td>
                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded px-2 py-1 text-xs font-semibold whitespace-nowrap ring-1 ring-inset"
                                        :class="statusClass(run.status)"
                                        >{{ titleCase(run.status)
                                        }}<span v-if="run.valid_drafts_count"> · {{ run.valid_drafts_count }} valid</span></span
                                    >
                                </td>
                                <td class="px-4 py-4 text-gray-500">{{ dateTime(run.created_at) }}</td>
                                <td class="px-4 py-4 text-right">
                                    <Link
                                        :href="show.url(run.id)"
                                        class="inline-flex rounded-md border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-700 hover:border-primary hover:text-primary"
                                        >Open preview</Link
                                    >
                                </td>
                            </tr>
                            <tr v-if="!runs.data.length">
                                <td colspan="5" class="px-4 py-10 text-center text-gray-500">No generation requests yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <Link v-if="runs.prev_page_url" :href="runs.prev_page_url" class="btn-secondary cursor-pointer">Previous</Link
                    ><Link v-if="runs.next_page_url" :href="runs.next_page_url" class="btn-secondary cursor-pointer">Next</Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.generation-section {
    padding: 24px 0;
    border-bottom: 1px solid #e5e7eb;
}
.generation-section:first-child {
    padding-top: 4px;
}
.generation-section:last-of-type {
    border-bottom: 0;
}
.section-heading {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}
.section-heading h2 {
    font-size: 14px;
    font-weight: 700;
    color: #111827;
}
.section-number {
    font-size: 11px;
    font-weight: 700;
    color: #6b7280;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 4px 6px;
}
.generation-workspace input,
.generation-workspace select,
.generation-workspace textarea {
    box-shadow: none;
    border-radius: 6px;
}
</style>
