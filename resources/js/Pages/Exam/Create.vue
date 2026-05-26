<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    subjects: Array<{ id: string; name: string; topics: Array<{ id: string; name: string }> }>;
    levels: Array<{ value: string; label: string }>;
    exam?: any;
    initialForm?: {
        title: string;
        subject_id: string;
        level: string;
        instructions: string;
    };
}>();

const form = ref(props.initialForm ?? { title: '', subject_id: '', level: 'js', instructions: 'Answer all questions carefully.' });
const mcqCount = ref(10);
const theoryCount = ref(2);
const submitting = ref(false);
const loadingPool = ref(false);
const poolError = ref('');

interface PoolQ {
    id: string; content: string; used_count: number; topic: string;
    type: string; last_used_at: string | null;
    options?: Array<{ id: string; content: string; is_correct: boolean }>;
    marking_scheme?: Array<{ point: string; weight: number }>;
}

const pool = ref<{ mcqs: PoolQ[]; theory: PoolQ[] }>({ mcqs: [], theory: [] });
const selectedIds = ref<Set<string>>(new Set());
const poolTab = ref<'all' | 'mcq' | 'theory'>('all');
const isEditMode = computed(() => !!props.exam?.editable);
const isReadOnlyExam = computed(() => !!props.exam && !props.exam?.editable);
const previewUrl = ref<string | null>(null);
const previewTitle = ref('');
const previewFrame = ref<HTMLIFrameElement | null>(null);

const filteredSubjects = computed(() => props.subjects.filter((s: any) => !s.level || s.level === form.value.level));

const loadPool = async () => {
    if (!form.value.subject_id || !form.value.level) return;
    loadingPool.value = true;
    poolError.value = '';
    try {
        const res = await fetch(`/exams/pool?subject_id=${form.value.subject_id}&level=${form.value.level}`);
        const data = await res.json();
        pool.value = data;
    } catch {
        poolError.value = 'Failed to load question pool.';
    }
    loadingPool.value = false;
};

const autoSelect = () => {
    selectedIds.value = new Set();
    const leastUsedMcqs = [...pool.value.mcqs].sort((a, b) => a.used_count - b.used_count).slice(0, mcqCount.value);
    const leastUsedTheory = [...pool.value.theory].sort((a, b) => a.used_count - b.used_count).slice(0, theoryCount.value);
    leastUsedMcqs.forEach(q => selectedIds.value.add(q.id));
    leastUsedTheory.forEach(q => selectedIds.value.add(q.id));
};

const toggleQuestion = (id: string) => {
    const set = new Set(selectedIds.value);
    if (set.has(id)) set.delete(id); else set.add(id);
    selectedIds.value = set;
};

const allPoolQ = computed(() => {
    const items = [...pool.value.mcqs, ...pool.value.theory];
    if (poolTab.value === 'mcq') return pool.value.mcqs;
    if (poolTab.value === 'theory') return pool.value.theory;
    return items;
});

const generateExam = () => {
    if (selectedIds.value.size === 0) return;
    submitting.value = true;
    const payload = {
        title: form.value.title,
        instructions: form.value.instructions,
        question_ids: [...selectedIds.value],
    };

    const endpoint = isEditMode.value && props.exam?.id
        ? `/exams/${props.exam.id}/questions`
        : '/exams/generate';

    router.post(endpoint, payload, { onFinish: () => { submitting.value = false; } });
};

const download = (examId: string, type: string) => window.open(`/exams/${examId}/download/${type}`, '_blank');
const preview = (examId: string, type: string, label: string) => {
    if (type === 'questions') {
        window.open(`/exams/${examId}/preview-html/${type}`, '_blank', 'noopener');
        return;
    }

    previewUrl.value = `/exams/${examId}/preview-html/${type}`;
    previewTitle.value = label;
    document.body.style.overflow = 'hidden';
};

const closePreview = () => {
    previewUrl.value = null;
    previewTitle.value = '';
    document.body.style.overflow = '';
};

const printPreview = () => {
    previewFrame.value?.contentWindow?.print();
};

const parseQuestionLead = (content: string) => {
    const match = content.match(/^([^:]+):\s*(Question\s+\d+\s+on\s+[^.?!]+[.?!])\s*(.*)$/i);

    if (!match) {
        return {
            subject: null,
            context: null,
            body: content,
        };
    }

    return {
        subject: match[1].trim(),
        context: match[2].trim(),
        body: match[3].trim(),
    };
};

onMounted(async () => {
    if (!isEditMode.value || !props.initialForm?.subject_id || !props.initialForm.level) return;

    selectedIds.value = new Set(props.exam?.selected_question_ids ?? []);
    await loadPool();
});
</script>

<template>
    <AppLayout>
        <Head :title="exam ? 'Exam: ' + exam.title : 'Create Exam'" />

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ exam ? exam.title : 'Examination Configuration' }}
                </h1>
                <div class="flex items-center gap-2">
                    <Link v-if="isReadOnlyExam" :href="`/exams/${exam.id}/edit-questions`" class="btn-secondary">Edit Questions</Link>
                    <button v-if="exam" @click="router.get('/exams/create')" class="btn-secondary">New Exam</button>
                </div>
            </div>

            <!-- Already generated exam view -->
            <template v-if="isReadOnlyExam">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="card p-4"><p class="text-xs font-semibold text-gray-500 uppercase">Subject</p><p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ exam.subject }}</p></div>
                    <div class="card p-4"><p class="text-xs font-semibold text-gray-500 uppercase">Level</p><p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ exam.level }}</p></div>
                    <div class="card p-4"><p class="text-xs font-semibold text-gray-500 uppercase">Questions</p><p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ exam.mcq_count + exam.theory_count }}</p></div>
                    <div class="card p-4"><p class="text-xs font-semibold text-gray-500 uppercase">Marks</p><p class="mt-1 text-lg font-bold text-primary">{{ exam.totalMarks }}</p></div>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-green-900/60 bg-white dark:bg-green-950/60 p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-4">Download Examination Papers</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div v-for="item in [{id:'questions',label:'Question Paper',desc:'For students'},{id:'answer-sheet',label:'Answer Sheet',desc:'MCQ response sheet'},{id:'answer-key',label:'Answer Key',desc:'MCQ answers'},{id:'marking-guide',label:'Marking Guide',desc:'Theory marking scheme'}]" :key="item.id"
                            class="rounded-xl border-2 border-gray-200 dark:border-green-900/60 bg-white dark:bg-green-950/60 p-6 text-center shadow-sm">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ item.label }}</h3>
                            <p class="mt-1 text-xs text-gray-500">{{ item.desc }}</p>
                            <div class="mt-3 flex justify-center gap-2">
                                <button @click="preview(exam.id, item.id, item.label)" class="rounded-lg border border-gray-300 dark:border-green-800/60 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50">View</button>
                                <button @click="download(exam.id, item.id)" class="rounded-lg bg-primary px-3 py-1.5 text-xs text-white hover:bg-primary/90">Download</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header flex items-center justify-between gap-3">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Selected Questions</h2>
                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                            {{ exam.mcq_count + exam.theory_count }} total
                        </span>
                    </div>
                    <div class="p-5 space-y-6">
                        <div v-if="exam.mcqs.length" class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Multiple Choice</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Objective questions and answer options.</p>
                                </div>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-200">
                                    {{ exam.mcqs.length }} questions
                                </span>
                            </div>

                            <div
                                v-for="(q, i) in exam.mcqs"
                                :key="q.id"
                                class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50/80 shadow-sm dark:border-green-900/60 dark:bg-green-950/45"
                            >
                                <div class="flex items-start gap-4 p-4">
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">
                                        {{ i + 1 }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div v-if="parseQuestionLead(q.content).subject || parseQuestionLead(q.content).context" class="mb-2 flex flex-wrap gap-2">
                                            <span
                                                v-if="parseQuestionLead(q.content).subject"
                                                class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-semibold text-primary"
                                            >
                                                {{ parseQuestionLead(q.content).subject }}
                                            </span>
                                            <span
                                                v-if="parseQuestionLead(q.content).context"
                                                class="inline-flex items-center rounded-full bg-gray-200 px-2.5 py-1 text-[11px] font-medium text-gray-700 dark:bg-green-900/70 dark:text-gray-200"
                                            >
                                                {{ parseQuestionLead(q.content).context }}
                                            </span>
                                        </div>
                                        <p class="text-sm leading-6 text-gray-800 dark:text-gray-100">
                                            {{ parseQuestionLead(q.content).body }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-if="q.options"
                                    class="grid grid-cols-1 gap-2 border-t border-gray-200 bg-white/70 px-4 py-4 dark:border-green-900/60 dark:bg-green-950/60 sm:grid-cols-2"
                                >
                                    <div
                                        v-for="(opt, oi) in q.options"
                                        :key="opt.id"
                                        class="flex items-start gap-3 rounded-lg border px-3 py-2.5 text-xs transition-colors"
                                        :class="opt.is_correct
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200'
                                            : 'border-gray-200 bg-white text-gray-600 dark:border-green-900/60 dark:bg-green-950/55 dark:text-gray-300'"
                                    >
                                        <span
                                            class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                            :class="opt.is_correct
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200'
                                                : 'bg-gray-100 text-gray-600 dark:bg-green-900/70 dark:text-gray-200'"
                                        >
                                            {{ ['A', 'B', 'C', 'D'][oi] }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p>{{ opt.content }}</p>
                                            <p v-if="opt.is_correct" class="mt-1 text-[10px] font-semibold uppercase tracking-wide">Correct answer</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="exam.theory.length" class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Theory</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Long-form questions for written responses.</p>
                                </div>
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">
                                    {{ exam.theory.length }} questions
                                </span>
                            </div>

                            <div
                                v-for="(q, i) in exam.theory"
                                :key="q.id"
                                class="rounded-xl border border-gray-200 bg-gray-50/80 p-4 shadow-sm dark:border-green-900/60 dark:bg-green-950/45"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-500 text-sm font-bold text-white">
                                        {{ exam.mcqs.length + i + 1 }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div v-if="parseQuestionLead(q.content).subject || parseQuestionLead(q.content).context" class="mb-2 flex flex-wrap gap-2">
                                            <span
                                                v-if="parseQuestionLead(q.content).subject"
                                                class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-semibold text-primary"
                                            >
                                                {{ parseQuestionLead(q.content).subject }}
                                            </span>
                                            <span
                                                v-if="parseQuestionLead(q.content).context"
                                                class="inline-flex items-center rounded-full bg-gray-200 px-2.5 py-1 text-[11px] font-medium text-gray-700 dark:bg-green-900/70 dark:text-gray-200"
                                            >
                                                {{ parseQuestionLead(q.content).context }}
                                            </span>
                                        </div>
                                        <p class="text-sm leading-6 text-gray-800 dark:text-gray-100">
                                            {{ parseQuestionLead(q.content).body }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Configuration (when no exam yet) -->
            <template v-else>
                <div class="card p-6">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Exam Details</h2>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Exam Title</label>
                            <input v-model="form.title" type="text" class="mt-1" placeholder="e.g., First Term Examination" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Level</label>
                            <select v-model="form.level" class="mt-1" @change="form.subject_id = ''; pool = { mcqs: [], theory: [] }">
                                <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Subject</label>
                            <select v-model="form.subject_id" class="mt-1" @change="pool = { mcqs: [], theory: [] }">
                                <option value="" disabled>Select subject</option>
                                <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <p v-if="!filteredSubjects.length" class="mt-1 text-xs text-amber-600">No subjects for this level.</p>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Instructions</label>
                            <textarea v-model="form.instructions" rows="2" class="mt-1"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Pool browser -->
                <div v-if="form.subject_id && form.level" class="card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Question Pool</h2>
                        <button @click="loadPool" :disabled="loadingPool" class="rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-white hover:bg-primary/90 disabled:opacity-50">
                            {{ loadingPool ? 'Loading...' : 'Browse Question Pool' }}
                        </button>
                    </div>
                    <p v-if="poolError" class="text-xs text-red-600 mb-3">{{ poolError }}</p>

                    <template v-if="pool.mcqs.length || pool.theory.length">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="text-xs text-gray-500">Total: {{ pool.mcqs.length + pool.theory.length }} questions ({{ pool.mcqs.length }} MCQ, {{ pool.theory.length }} Theory)</span>
                            <div class="flex gap-2 ml-auto">
                                <label class="text-xs text-gray-500">MCQ:</label>
                                <input v-model.number="mcqCount" type="number" min="0" :max="pool.mcqs.length" class="w-16 text-xs py-1" />
                                <label class="text-xs text-gray-500">Theory:</label>
                                <input v-model.number="theoryCount" type="number" min="0" :max="pool.theory.length" class="w-16 text-xs py-1" />
                                <button @click="autoSelect" class="rounded-lg border border-primary px-3 py-1 text-xs font-medium text-primary hover:bg-primary/5">Auto Select (Least Used)</button>
                            </div>
                        </div>

                        <div class="flex gap-2 mb-3">
                            <button @click="poolTab = 'all'" class="rounded-full px-3 py-1 text-xs font-medium" :class="poolTab === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600'">All</button>
                            <button @click="poolTab = 'mcq'" class="rounded-full px-3 py-1 text-xs font-medium" :class="poolTab === 'mcq' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600'">MCQ ({{ pool.mcqs.length }})</button>
                            <button @click="poolTab = 'theory'" class="rounded-full px-3 py-1 text-xs font-medium" :class="poolTab === 'theory' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600'">Theory ({{ pool.theory.length }})</button>
                            <span class="ml-auto text-xs font-semibold text-gray-600 dark:text-gray-300">{{ selectedIds.size }} selected</span>
                        </div>

                        <div class="grid grid-cols-1 gap-2 max-h-96 overflow-y-auto">
                            <div v-for="q in allPoolQ" :key="q.id"
                                @click="toggleQuestion(q.id)"
                                class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition-colors"
                                :class="selectedIds.has(q.id) ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-green-900/60 hover:border-gray-300'">
                                <div class="flex size-5 shrink-0 items-center justify-center rounded border mt-0.5"
                                    :class="selectedIds.has(q.id) ? 'bg-primary border-primary text-white' : 'border-gray-300'">
                                    <svg v-if="selectedIds.has(q.id)" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-gray-900 dark:text-gray-100 line-clamp-2">{{ q.content }}</p>
                                    <div class="mt-1 flex flex-wrap gap-2 text-[10px] text-gray-500">
                                        <span class="rounded bg-gray-100 dark:bg-green-900/70 px-1.5 py-0.5">{{ q.topic }}</span>
                                        <span class="rounded bg-gray-100 dark:bg-green-900/70 px-1.5 py-0.5 capitalize">{{ q.type }}</span>
                                        <span class="rounded px-1.5 py-0.5" :class="q.used_count >= 3 ? 'bg-red-100 text-red-700' : 'bg-gray-100 dark:bg-green-900/70 text-gray-500'">
                                            Used {{ q.used_count }}x
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div v-else-if="!loadingPool" class="py-8 text-center text-sm text-gray-500">
                        Click "Browse Question Pool" to see available questions for this subject and level.
                    </div>
                </div>

                <!-- Generate -->
                <div v-if="selectedIds.size > 0" class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">{{ selectedIds.size }} question{{ selectedIds.size !== 1 ? 's' : '' }} selected</span>
                    <button @click="generateExam" :disabled="submitting || !form.title || selectedIds.size === 0"
                        class="inline-flex items-center gap-2 rounded-xl btn-primary disabled:opacity-50">
                        <span v-if="submitting" class="inline-block size-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                        {{ isEditMode ? 'Save Exam Questions' : 'Generate Exam' }}
                    </button>
                </div>
            </template>
        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="previewUrl" class="fixed inset-0 z-[120] flex flex-col bg-slate-950/90 backdrop-blur-sm">
                    <div class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-3 text-white sm:px-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/60">Document Preview</p>
                            <h2 class="text-lg font-semibold">{{ previewTitle }}</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="printPreview"
                                class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-900 transition hover:bg-white/90"
                            >
                                Print
                            </button>
                            <button
                                type="button"
                                @click="closePreview"
                                class="rounded-lg border border-white/15 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10"
                            >
                                Close
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 p-3 sm:p-4">
                        <iframe
                            ref="previewFrame"
                            :src="previewUrl"
                            class="h-full w-full rounded-xl border border-white/10 bg-white shadow-2xl"
                            title="Document preview"
                        />
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
