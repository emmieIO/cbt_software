<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import RichContentViewer from '@/components/Questions/RichContentViewer.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    subjects: Array<{ id: string; name: string; level?: string | null; topics: Array<{ id: string; name: string; class_level?: string | null }> }>;
    levels: Array<{ value: string; label: string }>;
    classLevels: Record<string, Array<{ value: string; label: string }>>;
    examTitles: string[];
    exam?: any;
    initialForm?: {
        title: string;
        subject_id: string;
        level: string;
        class_level: string;
        instructions: string;
    };
}>();

const defaultClassLevel = (level: string) => props.classLevels[level]?.[0]?.value ?? '';
const form = ref(props.initialForm ?? { title: '', subject_id: '', level: 'js', class_level: defaultClassLevel('js'), instructions: 'Answer all questions carefully.' });
const totalQuestionCount = ref(12);
const submitting = ref(false);
const loadingPool = ref(false);
const poolError = ref('');

interface PoolQ {
    id: string;
    content: string;
    used_count: number;
    topic_id: string;
    topic: string;
    type: string;
    last_used_at: string | null;
    options?: Array<{ id: string; content: string; is_correct: boolean }>;
    marking_scheme?: Array<{ point: string; weight: number }>;
}

const pool = ref<{ mcqs: PoolQ[]; theory: PoolQ[] }>({ mcqs: [], theory: [] });
const selectedIds = ref<Set<string>>(new Set());
const topicQuestionCounts = ref<Record<string, number>>({});
const topicAllocationOpen = ref(false);
const topicSearch = ref('');
const poolTab = ref<'all' | 'mcq' | 'theory'>('all');
const isEditMode = computed(() => !!props.exam?.editable);
const isReadOnlyExam = computed(() => !!props.exam && !props.exam?.editable);
const previewUrl = ref<string | null>(null);
const previewTitle = ref('');
const previewFrame = ref<HTMLIFrameElement | null>(null);

const filteredSubjects = computed(() => props.subjects.filter((s: any) => !s.level || s.level === form.value.level));
const classLevelOptions = computed(() => props.classLevels[form.value.level] || []);
const examTitleOptions = computed(() =>
    form.value.title && !props.examTitles.includes(form.value.title)
        ? [form.value.title, ...props.examTitles]
        : props.examTitles,
);

const loadPool = async () => {
    if (!form.value.subject_id || !form.value.level || !form.value.class_level) return;
    loadingPool.value = true;
    poolError.value = '';
    try {
        const params = new URLSearchParams({
            subject_id: form.value.subject_id,
            level: form.value.level,
            class_level: form.value.class_level,
        });
        const res = await fetch(`/exams/pool?${params.toString()}`);
        const data = await res.json();
        pool.value = data;
        initializeTopicQuestionCounts();
        topicAllocationOpen.value = poolTopics.value.length <= 4;
    } catch {
        poolError.value = 'Failed to load question pool.';
    }
    loadingPool.value = false;
};

const poolTopics = computed(() => {
    const topics = new Map<string, { id: string; name: string; mcq_count: number; theory_count: number; total_count: number }>();

    [...pool.value.mcqs, ...pool.value.theory].forEach((question) => {
        const current = topics.get(question.topic_id) ?? {
            id: question.topic_id,
            name: question.topic,
            mcq_count: 0,
            theory_count: 0,
            total_count: 0,
        };

        if (question.type === 'mcq' || question.type === 'multiple_choice') current.mcq_count += 1;
        else current.theory_count += 1;
        current.total_count += 1;
        topics.set(question.topic_id, current);
    });

    return [...topics.values()].sort((a, b) => a.name.localeCompare(b.name));
});

const filteredPoolTopics = computed(() => {
    const search = topicSearch.value.trim().toLowerCase();
    if (!search) return poolTopics.value;
    return poolTopics.value.filter((topic) => topic.name.toLowerCase().includes(search));
});

const activeTopicCount = computed(() => poolTopics.value.filter((topic) => Number(topicQuestionCounts.value[topic.id] || 0) > 0).length);
const selectedTopicQuestionTotal = computed(() => poolTopics.value.reduce((sum, topic) => sum + Number(topicQuestionCounts.value[topic.id] || 0), 0));
const allocationIssue = computed(() => {
    if (!poolTopics.value.length) return '';
    if (selectedTopicQuestionTotal.value <= 0) return 'Set at least one topic question count before auto selecting.';
    if (selectedTopicQuestionTotal.value !== totalQuestionCount.value) {
        return `Topic counts add up to ${selectedTopicQuestionTotal.value}, while the target is ${totalQuestionCount.value}.`;
    }
    return '';
});

const initializeTopicQuestionCounts = () => {
    const topics = poolTopics.value;
    if (!topics.length) {
        topicQuestionCounts.value = {};
        return;
    }

    const target = Math.min(totalQuestionCount.value, pool.value.mcqs.length + pool.value.theory.length);
    const base = Math.floor(target / topics.length);
    let remainder = target - base * topics.length;
    const next: Record<string, number> = {};

    topics.forEach((topic) => {
        next[topic.id] = Math.min(topic.total_count, base + (remainder > 0 ? 1 : 0));
        remainder -= 1;
    });

    topicQuestionCounts.value = next;
};

const fitTopicQuestionCountsToTarget = () => {
    const topics = poolTopics.value;
    if (!topics.length) return;

    const next = Object.fromEntries(topics.map((topic) => [topic.id, Math.max(0, Number(topicQuestionCounts.value[topic.id] || 0))]));
    let current = Object.values(next).reduce((sum, count) => sum + count, 0);

    while (current < totalQuestionCount.value) {
        const topic = topics.find((item) => next[item.id] < item.total_count);
        if (!topic) break;
        next[topic.id] += 1;
        current += 1;
    }

    while (current > totalQuestionCount.value) {
        const topic = [...topics].reverse().find((item) => next[item.id] > 0);
        if (!topic) break;
        next[topic.id] -= 1;
        current -= 1;
    }

    topicQuestionCounts.value = next;
};

const resetTopicQuestionCounts = () => {
    initializeTopicQuestionCounts();
};

const syncTotalQuestionCountFromTopics = () => {
    totalQuestionCount.value = selectedTopicQuestionTotal.value;
};

const setTopicQuestionCount = (topicId: string, value: number, max: number) => {
    topicQuestionCounts.value = {
        ...topicQuestionCounts.value,
        [topicId]: Math.max(0, Math.min(max, Number(value || 0))),
    };
};

const selectByTopicUsageCounts = (questions: PoolQ[]) => {
    const selected: PoolQ[] = [];
    const selectedIds = new Set<string>();

    poolTopics.value.forEach((topic) => {
        const quota = Number(topicQuestionCounts.value[topic.id] || 0);
        if (quota <= 0) return;

        leastUsed(questions.filter((question) => question.topic_id === topic.id))
            .slice(0, quota)
            .forEach((question) => {
                selected.push(question);
                selectedIds.add(question.id);
            });
    });

    if (selected.length < totalQuestionCount.value) {
        leastUsed(questions)
            .filter((question) => !selectedIds.has(question.id))
            .slice(0, totalQuestionCount.value - selected.length)
            .forEach((question) => selected.push(question));
    }

    return selected;
};

const leastUsed = (questions: PoolQ[]) =>
    [...questions].sort((a, b) => {
        if (a.used_count !== b.used_count) return a.used_count - b.used_count;
        return a.topic.localeCompare(b.topic) || a.content.localeCompare(b.content);
    });

const autoSelect = () => {
    selectedIds.value = new Set();
    selectByTopicUsageCounts([...pool.value.mcqs, ...pool.value.theory]).forEach((q) => selectedIds.value.add(q.id));
};

const toggleQuestion = (id: string) => {
    const set = new Set(selectedIds.value);
    if (set.has(id)) set.delete(id);
    else set.add(id);
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
        class_level: form.value.class_level,
        question_ids: [...selectedIds.value],
    };

    const endpoint = isEditMode.value && props.exam?.id ? `/exams/${props.exam.id}/questions` : '/exams/generate';

    router.post(endpoint, payload, {
        onFinish: () => {
            submitting.value = false;
        },
    });
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

const questionTypeClass = (type: string) =>
    ({
        mcq: 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-200',
        multiple_choice: 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-200',
        short_answer: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200',
        theory: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200',
    })[type] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700/40 dark:text-gray-200';

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
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:grid-cols-4">
                    <div class="card p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Subject</p>
                        <p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ exam.subject }}</p>
                    </div>
                    <div class="card p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Level</p>
                        <p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ exam.level }}</p>
                    </div>
                    <div class="card p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Class</p>
                        <p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ exam.class_level || 'All' }}</p>
                    </div>
                    <div class="card p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Questions</p>
                        <p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ exam.mcq_count + exam.theory_count }}</p>
                    </div>
                    <div class="card p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Marks</p>
                        <p class="mt-1 text-lg font-bold text-primary">{{ exam.totalMarks }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-green-900/60 dark:bg-green-950/60">
                    <h2 class="mb-4 text-sm font-bold text-gray-900 dark:text-gray-100">Download Examination Papers</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="item in [
                                { id: 'questions', label: 'Question Paper', desc: 'For students' },
                                { id: 'answer-sheet', label: 'Answer Sheet', desc: 'MCQ response sheet' },
                                { id: 'answer-key', label: 'Answer Key', desc: 'MCQ answers' },
                                { id: 'marking-guide', label: 'Marking Guide', desc: 'Theory marking scheme' },
                            ]"
                            :key="item.id"
                            class="rounded-xl border-2 border-gray-200 bg-white p-6 text-center shadow-sm dark:border-green-900/60 dark:bg-green-950/60"
                        >
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ item.label }}</h3>
                            <p class="mt-1 text-xs text-gray-500">{{ item.desc }}</p>
                            <div class="mt-3 flex justify-center gap-2">
                                <button
                                    @click="preview(exam.id, item.id, item.label)"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:border-green-800/60 dark:bg-green-950/45 dark:text-gray-200 dark:hover:bg-green-950/70"
                                >
                                    View
                                </button>
                                <button
                                    @click="download(exam.id, item.id)"
                                    class="rounded-lg bg-primary px-3 py-1.5 text-xs text-white hover:bg-primary/90"
                                >
                                    Download
                                </button>
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
                    <div class="space-y-6 p-5">
                        <div v-if="exam.mcqs.length" class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Multiple Choice</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Objective questions and answer options.</p>
                                </div>
                                <span
                                    class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-200"
                                >
                                    {{ exam.mcqs.length }} questions
                                </span>
                            </div>

                            <div
                                v-for="(q, i) in exam.mcqs"
                                :key="q.id"
                                class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50/80 shadow-sm dark:border-green-900/60 dark:bg-green-950/45"
                            >
                                <div class="flex items-start gap-4 p-4">
                                    <div
                                        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white"
                                    >
                                        {{ i + 1 }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div
                                            v-if="parseQuestionLead(q.content).subject || parseQuestionLead(q.content).context"
                                            class="mb-2 flex flex-wrap gap-2"
                                        >
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
                                        <RichContentViewer :content="parseQuestionLead(q.content).body" />
                                    </div>
                                </div>
                                <div
                                    v-if="q.options"
                                    class="grid grid-cols-1 gap-2 border-t border-gray-200 bg-white/70 px-4 py-4 sm:grid-cols-2 dark:border-green-900/60 dark:bg-green-950/60"
                                >
                                    <div
                                        v-for="(opt, oi) in q.options"
                                        :key="opt.id"
                                        class="flex items-start gap-3 rounded-lg border px-3 py-2.5 text-xs transition-colors"
                                        :class="
                                            opt.is_correct
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200'
                                                : 'border-gray-200 bg-white text-gray-600 dark:border-green-900/60 dark:bg-green-950/55 dark:text-gray-300'
                                        "
                                    >
                                        <span
                                            class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                            :class="
                                                opt.is_correct
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200'
                                                    : 'bg-gray-100 text-gray-600 dark:bg-green-900/70 dark:text-gray-200'
                                            "
                                        >
                                            {{ ['A', 'B', 'C', 'D'][oi] }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <RichContentViewer :content="opt.content" />
                                            <p v-if="opt.is_correct" class="mt-1 text-[10px] font-semibold tracking-wide uppercase">Correct answer</p>
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
                                <span
                                    class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-200"
                                >
                                    {{ exam.theory.length }} questions
                                </span>
                            </div>

                            <div
                                v-for="(q, i) in exam.theory"
                                :key="q.id"
                                class="rounded-xl border border-gray-200 bg-gray-50/80 p-4 shadow-sm dark:border-green-900/60 dark:bg-green-950/45"
                            >
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-500 text-sm font-bold text-white"
                                    >
                                        {{ exam.mcqs.length + i + 1 }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div
                                            v-if="parseQuestionLead(q.content).subject || parseQuestionLead(q.content).context"
                                            class="mb-2 flex flex-wrap gap-2"
                                        >
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
                                        <RichContentViewer :content="parseQuestionLead(q.content).body" />
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
                    <div class="flex items-start gap-3">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">1</div>
                        <div>
                            <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">Exam Details</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose the exam context before loading eligible questions.</p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Exam Title</label>
                            <select v-model="form.title" required class="mt-1">
                                <option value="" disabled>Select exam title</option>
                                <option v-for="title in examTitleOptions" :key="title" :value="title">{{ title }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Level</label>
                            <select
                                v-model="form.level"
                                class="mt-1"
                                @change="
                                    form.class_level = defaultClassLevel(form.level);
                                    form.subject_id = '';
                                    pool = { mcqs: [], theory: [] };
                                    topicQuestionCounts = {};
                                "
                            >
                                <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Class Level</label>
                            <select
                                v-model="form.class_level"
                                class="mt-1"
                                @change="
                                    pool = { mcqs: [], theory: [] };
                                    topicQuestionCounts = {};
                                "
                            >
                                <option v-for="classLevel in classLevelOptions" :key="classLevel.value" :value="classLevel.value">{{ classLevel.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Subject</label>
                            <select
                                v-model="form.subject_id"
                                class="mt-1"
                                @change="
                                    pool = { mcqs: [], theory: [] };
                                    topicQuestionCounts = {};
                                "
                            >
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
                <div v-if="form.subject_id && form.level && form.class_level" class="card p-6">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">2</div>
                            <div>
                                <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">Question Pool</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Load questions, set topic coverage, then auto-select or pick manually.</p>
                            </div>
                        </div>
                        <button
                            @click="loadPool"
                            :disabled="loadingPool"
                            class="btn-primary justify-center px-4 py-2 text-xs"
                        >
                            {{ loadingPool ? 'Loading...' : 'Browse Question Pool' }}
                        </button>
                    </div>
                    <p v-if="poolError" class="mb-3 text-xs text-red-600">{{ poolError }}</p>

                    <template v-if="pool.mcqs.length || pool.theory.length">
                        <div class="mb-4 space-y-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="rounded-lg bg-primary/8 px-3 py-2 text-xs font-medium text-primary dark:bg-primary/10 dark:text-primary-light">
                                    {{ pool.mcqs.length + pool.theory.length }} available questions
                                    <span class="text-primary/70">({{ pool.mcqs.length }} MCQ, {{ pool.theory.length }} Written)</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <label class="text-xs text-gray-500">Questions:</label>
                                    <input
                                        v-model.number="totalQuestionCount"
                                        type="number"
                                        min="1"
                                        :max="pool.mcqs.length + pool.theory.length"
                                        class="w-20 py-1 text-xs"
                                        @change="fitTopicQuestionCountsToTarget"
                                    />
                                    <button
                                        @click="autoSelect"
                                        :disabled="selectedTopicQuestionTotal <= 0"
                                        class="inline-flex items-center rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Auto Select
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50/70 dark:border-green-900/60 dark:bg-green-950/45">
                                <button
                                    type="button"
                                    class="group flex w-full cursor-pointer flex-col gap-3 px-4 py-3 text-left transition hover:bg-white dark:hover:bg-green-950/70 sm:flex-row sm:items-center sm:justify-between"
                                    @click="topicAllocationOpen = !topicAllocationOpen"
                                >
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Topic Allocation</h3>
                                            <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-gray-500 ring-1 ring-gray-200 dark:bg-green-950/60 dark:ring-green-900/60">
                                                Click to {{ topicAllocationOpen ? 'close' : 'edit' }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ activeTopicCount }} active topic{{ activeTopicCount === 1 ? '' : 's' }} of {{ poolTopics.length }}.
                                            Least-used questions are picked inside each topic.
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                            :class="
                                                selectedTopicQuestionTotal === totalQuestionCount
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200'
                                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200'
                                            "
                                        >
                                            {{ selectedTopicQuestionTotal }}/{{ totalQuestionCount }} questions
                                        </span>
                                        <svg
                                            class="size-5 text-gray-500 transition-transform group-hover:text-primary"
                                            :class="topicAllocationOpen ? 'rotate-180' : ''"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </button>

                                <div v-if="topicAllocationOpen" class="border-t border-gray-200 p-4 dark:border-green-900/60">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="relative lg:max-w-xs lg:flex-1">
                                            <input v-model="topicSearch" type="search" placeholder="Search topics..." class="w-full py-1.5 pl-8 text-xs" />
                                            <svg class="absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
                                            </svg>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                class="btn-secondary rounded-lg px-3 py-1.5 text-xs"
                                                @click.stop="resetTopicQuestionCounts"
                                            >
                                                Equal Split
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg border border-primary bg-white px-3 py-1.5 text-xs font-semibold text-primary shadow-sm transition hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-50 dark:border-primary/60 dark:bg-green-950/60 dark:text-primary-light"
                                                :disabled="selectedTopicQuestionTotal <= 0"
                                                @click.stop="fitTopicQuestionCountsToTarget"
                                            >
                                                Fit to Target
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-green-900/60 dark:bg-green-950/60 dark:text-gray-200"
                                                @click.stop="syncTotalQuestionCountFromTopics"
                                            >
                                                Use Topic Total
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-3 max-h-64 space-y-2 overflow-y-auto pr-1">
                                        <div
                                            v-for="topic in filteredPoolTopics"
                                            :key="topic.id"
                                            class="grid grid-cols-[minmax(0,1fr)_5rem] items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-green-900/60 dark:bg-green-950/60"
                                        >
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">{{ topic.name }}</p>
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                                    {{ topic.mcq_count }} MCQ | {{ topic.theory_count }} written
                                                </p>
                                            </div>
                                            <div class="relative">
                                                <input
                                                    :value="topicQuestionCounts[topic.id] || 0"
                                                    type="number"
                                                    min="0"
                                                    :max="topic.total_count"
                                                    class="w-full pr-6 py-1 text-right text-xs"
                                                    @input="setTopicQuestionCount(topic.id, Number(($event.target as HTMLInputElement).value), topic.total_count)"
                                                />
                                                <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-400">qs</span>
                                            </div>
                                        </div>
                                        <p v-if="filteredPoolTopics.length === 0" class="py-4 text-center text-xs text-gray-500 dark:text-gray-400">No topics match your search.</p>
                                    </div>

                                    <p v-if="allocationIssue" class="mt-2 text-xs text-amber-600 dark:text-amber-300">{{ allocationIssue }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 flex gap-2">
                            <button
                                @click="poolTab = 'all'"
                                class="rounded-lg border px-3 py-2 text-xs font-semibold transition-colors"
                                :class="
                                    poolTab === 'all'
                                        ? 'border-primary bg-primary text-white shadow-sm'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50 dark:border-green-900/60 dark:bg-green-950/60 dark:text-gray-200 dark:hover:bg-green-900/70'
                                "
                            >
                                All
                            </button>
                            <button
                                @click="poolTab = 'mcq'"
                                class="rounded-lg border px-3 py-2 text-xs font-semibold transition-colors"
                                :class="
                                    poolTab === 'mcq'
                                        ? 'border-primary bg-primary text-white shadow-sm'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50 dark:border-green-900/60 dark:bg-green-950/60 dark:text-gray-200 dark:hover:bg-green-900/70'
                                "
                            >
                                MCQ ({{ pool.mcqs.length }})
                            </button>
                            <button
                                @click="poolTab = 'theory'"
                                class="rounded-lg border px-3 py-2 text-xs font-semibold transition-colors"
                                :class="
                                    poolTab === 'theory'
                                        ? 'border-primary bg-primary text-white shadow-sm'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50 dark:border-green-900/60 dark:bg-green-950/60 dark:text-gray-200 dark:hover:bg-green-900/70'
                                "
                            >
                                Theory ({{ pool.theory.length }})
                            </button>
                            <span class="ml-auto text-xs font-semibold text-gray-600 dark:text-gray-300">{{ selectedIds.size }} selected</span>
                        </div>

                        <div class="grid max-h-96 grid-cols-1 gap-2 overflow-y-auto">
                            <div
                                v-for="q in allPoolQ"
                                :key="q.id"
                                @click="toggleQuestion(q.id)"
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-all hover:-translate-y-0.5 hover:shadow-sm"
                                :class="
                                    selectedIds.has(q.id)
                                        ? 'border-primary bg-primary/8 ring-1 ring-primary/20'
                                        : 'border-gray-200 bg-white hover:border-primary/40 dark:border-green-900/60 dark:bg-green-950/45'
                                "
                            >
                                <div
                                    class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-md border transition"
                                    :class="selectedIds.has(q.id) ? 'border-primary bg-primary text-white' : 'border-gray-300 bg-white group-hover:border-primary dark:bg-green-950/60'"
                                >
                                    <svg
                                        v-if="selectedIds.has(q.id)"
                                        class="size-3"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="3"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <RichContentViewer :content="q.content" truncate />
                                    <div class="mt-1 flex flex-wrap gap-2 text-[10px] text-gray-500">
                                        <span class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700 dark:bg-green-900/70 dark:text-gray-200">{{
                                            q.topic
                                        }}</span>
                                        <span class="rounded px-1.5 py-0.5 font-semibold capitalize" :class="questionTypeClass(q.type)">{{
                                            q.type.replace('_', ' ')
                                        }}</span>
                                        <span
                                            class="rounded px-1.5 py-0.5"
                                            :class="q.used_count >= 3 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500 dark:bg-green-900/70'"
                                        >
                                            Used {{ q.used_count }}x
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div v-else-if="!loadingPool" class="py-8 text-center text-sm text-gray-500">
                        Click "Browse Question Pool" to see available questions for this subject, level, and class.
                    </div>
                </div>

                <!-- Generate -->
                <div v-if="selectedIds.size > 0" class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">{{ selectedIds.size }} question{{ selectedIds.size !== 1 ? 's' : '' }} selected</span>
                    <button
                        @click="generateExam"
                        :disabled="submitting || !form.title || selectedIds.size === 0"
                        class="btn-primary inline-flex items-center gap-2 rounded-xl disabled:opacity-50"
                    >
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
                            <p class="text-xs font-semibold tracking-[0.24em] text-white/60 uppercase">Document Preview</p>
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
