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
const preview = (examId: string, type: string) => window.open(`/exams/${examId}/preview/${type}`, '_blank');

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

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-4">Download Examination Papers</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div v-for="item in [{id:'questions',label:'Question Paper',desc:'For students',color:'primary'},{id:'answer-key',label:'Answer Key',desc:'MCQ answers',color:'green'},{id:'marking-guide',label:'Marking Guide',desc:'Theory marking scheme',color:'amber'}]" :key="item.id"
                            class="rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 text-center shadow-sm">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ item.label }}</h3>
                            <p class="mt-1 text-xs text-gray-500">{{ item.desc }}</p>
                            <div class="mt-3 flex justify-center gap-2">
                                <button @click="preview(exam.id, item.id)" class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50">View</button>
                                <button @click="download(exam.id, item.id)" class="rounded-lg bg-primary px-3 py-1.5 text-xs text-white hover:bg-primary/90">Download</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Selected Questions</h2></div>
                    <div class="p-5 space-y-3">
                        <div v-for="(q, i) in exam.mcqs" :key="q.id" class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3">
                            <p class="text-sm"><span class="font-bold text-gray-500">{{ i + 1 }}.</span> {{ q.content }}</p>
                            <div v-if="q.options" class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-1 text-xs text-gray-600 dark:text-gray-400">
                                <div v-for="(opt, oi) in q.options" :key="opt.id" :class="opt.is_correct ? 'font-semibold text-green-700' : ''">
                                    {{ ['A','B','C','D'][oi] }}. {{ opt.content }}
                                </div>
                            </div>
                        </div>
                        <div v-for="(q, i) in exam.theory" :key="q.id" class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3">
                            <p class="text-sm"><span class="font-bold text-gray-500">{{ exam.mcqs.length + i + 1 }}.</span> {{ q.content }}</p>
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
                                :class="selectedIds.has(q.id) ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                                <div class="flex size-5 shrink-0 items-center justify-center rounded border mt-0.5"
                                    :class="selectedIds.has(q.id) ? 'bg-primary border-primary text-white' : 'border-gray-300'">
                                    <svg v-if="selectedIds.has(q.id)" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-gray-900 dark:text-gray-100 line-clamp-2">{{ q.content }}</p>
                                    <div class="mt-1 flex flex-wrap gap-2 text-[10px] text-gray-500">
                                        <span class="rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5">{{ q.topic }}</span>
                                        <span class="rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 capitalize">{{ q.type }}</span>
                                        <span class="rounded px-1.5 py-0.5" :class="q.used_count >= 3 ? 'bg-red-100 text-red-700' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'">
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
    </AppLayout>
</template>
