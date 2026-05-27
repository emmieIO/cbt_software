<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import RichContentEditor from '@/components/Questions/RichContentEditor.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    subjects: Array<{
        id: string;
        name: string;
        level?: string;
        topics: Array<{ id: string; name: string }>;
    }>;
    levels: Array<{ value: string; label: string }>;
}>();

const form = ref({
    type: 'multiple_choice',
    subject_id: '',
    topic_id: '',
    level: 'js',
    content: '',
    explanation: '',
    options: [
        { content: '', is_correct: false },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
    ],
    marking_scheme: [{ point: '', weight: 1 }],
    errors: {} as Record<string, string>,
});

const submitting = ref(false);
const success = ref('');
const imageFile = ref<File | null>(null);
const imagePreview = ref<string | null>(null);

const pickImage = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files?.[0]) {
        imageFile.value = target.files[0];
        imagePreview.value = URL.createObjectURL(target.files[0]);
    }
};

const removeImage = () => {
    imageFile.value = null;
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
        imagePreview.value = null;
    }
};

const filteredSubjects = computed(() => props.subjects.filter((s) => !s.level || s.level === form.value.level));
const selectedSubject = computed(() => filteredSubjects.value.find((s) => s.id === form.value.subject_id));
const filteredTopics = computed(() => selectedSubject.value?.topics || []);

const onLevelChange = () => {
    form.value.subject_id = '';
    form.value.topic_id = '';
};

const onSubjectChange = () => {
    form.value.topic_id = '';
};

const correctIndex = computed({
    get: () => form.value.options.findIndex((o) => o.is_correct),
    set: (idx: number) => {
        form.value.options.forEach((o, i) => {
            o.is_correct = i === idx;
        });
    },
});

const addSchemeRow = () => {
    form.value.marking_scheme.push({ point: '', weight: 1 });
};

const removeSchemeRow = (idx: number) => {
    if (form.value.marking_scheme.length > 1) {
        form.value.marking_scheme.splice(idx, 1);
    }
};

const richText = (value: string) => {
    const element = document.createElement('div');
    element.innerHTML = value || '';
    const latex = Array.from(element.querySelectorAll<HTMLElement>('[data-latex]')).map((node) => node.dataset.latex || '');
    return [element.textContent || '', ...latex].join(' ').replace(/\s+/g, ' ').trim();
};

const submit = () => {
    submitting.value = true;
    form.value.errors = {};
    success.value = '';

    const fd = new FormData();
    fd.append('type', form.value.type);
    fd.append('topic_id', form.value.topic_id);
    fd.append('content', form.value.content);
    fd.append('level', form.value.level);
    fd.append('explanation', form.value.explanation || '');

    if (imageFile.value) {
        fd.append('image', imageFile.value);
    }

    if (form.value.type === 'theory') {
        const scheme = form.value.marking_scheme.filter((s) => richText(s.point));
        if (scheme.length === 0) {
            form.value.errors.marking_scheme = 'Add at least one marking point.';
            submitting.value = false;
            return;
        }
        fd.append('marking_scheme', JSON.stringify(scheme));
    } else {
        if (form.value.options.some((option) => !richText(option.content))) {
            form.value.errors.options = 'Fill all four options.';
            submitting.value = false;
            return;
        }
        if (correctIndex.value === -1) {
            form.value.errors.options = 'Select the correct answer.';
            submitting.value = false;
            return;
        }
        fd.append('options', JSON.stringify(form.value.options));
    }

    router.post('/questions', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onSuccess: () => {
            success.value = 'Question created successfully.';
            form.value.content = '';
            form.value.explanation = '';
            removeImage();
            form.value.options = form.value.options.map(() => ({ content: '', is_correct: false }));
            form.value.marking_scheme = [{ point: '', weight: 1 }];
        },
        onError: (err) => {
            form.value.errors = err as Record<string, string>;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Create Question" />

        <div class="mx-auto max-w-5xl space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Create Question</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Add a new question to the bank.</p>
                </div>
                <Link href="/questions" class="text-sm font-medium text-primary hover:underline">&larr; Back to questions</Link>
            </div>

            <div v-if="success" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ success }}</div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Question Type -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none">
                    <div class="flex flex-col gap-4 lg:flex-row">
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border-2 p-4 transition-colors"
                            :class="
                                form.type === 'multiple_choice'
                                    ? 'border-primary bg-primary/5'
                                    : 'border-gray-200 hover:border-gray-300 dark:border-green-800/60 dark:border-green-900/60'
                            "
                        >
                            <input v-model="form.type" type="radio" value="multiple_choice" class="text-primary" />
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Multiple Choice</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Four options with one correct answer</p>
                            </div>
                        </label>
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border-2 p-4 transition-colors"
                            :class="
                                form.type === 'theory'
                                    ? 'border-primary bg-primary/5'
                                    : 'border-gray-200 hover:border-gray-300 dark:border-green-800/60 dark:border-green-900/60'
                            "
                        >
                            <input v-model="form.type" type="radio" value="theory" class="text-primary" />
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Theory</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Essay with marking scheme</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Classification -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Classification</h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Level</label>
                            <select v-model="form.level" class="mt-1" @change="onLevelChange">
                                <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Subject</label>
                            <select v-model="form.subject_id" class="mt-1" @change="onSubjectChange">
                                <option value="" disabled>Select subject</option>
                                <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <p v-if="!filteredSubjects.length" class="mt-1 text-xs text-amber-600">No subjects for this level.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Topic</label>
                            <select v-model="form.topic_id" required class="mt-1">
                                <option value="" disabled>Select topic</option>
                                <option v-for="t in filteredTopics" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                            <p v-if="form.errors.topic_id" class="mt-1 text-xs text-red-600">{{ form.errors.topic_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Question Content -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Question</h2>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Question Text</label>
                            <RichContentEditor v-model="form.content" class="mt-1" placeholder="Enter the question text..." />
                            <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Image (optional)</label>
                            <div class="mt-1 flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-4">
                                <label class="btn-secondary flex cursor-pointer items-center gap-2 dark:bg-green-950/45">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                    Choose Image
                                    <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="pickImage" />
                                </label>
                                <span v-if="imageFile" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ imageFile.name }}</span>
                                <button v-if="imagePreview" @click="removeImage" type="button" class="text-xs text-red-600 hover:underline">
                                    Remove
                                </button>
                            </div>
                            <div v-if="imagePreview" class="mt-2">
                                <img :src="imagePreview" class="max-h-40 rounded-lg border border-gray-200 object-contain dark:border-green-900/60" />
                            </div>
                            <p v-if="form.errors.image" class="mt-1 text-xs text-red-600">{{ form.errors.image }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Explanation (optional)</label>
                            <RichContentEditor v-model="form.explanation" class="mt-1" placeholder="Explain the correct answer..." compact />
                        </div>
                    </div>
                </div>

                <!-- MCQ Options -->
                <div
                    v-if="form.type === 'multiple_choice'"
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none"
                >
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Answer Options</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Select the correct answer.</p>
                    <p v-if="form.errors.options" class="mt-2 text-xs text-red-600">{{ form.errors.options }}</p>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="(option, idx) in form.options"
                            :key="idx"
                            class="flex flex-wrap items-center gap-3 rounded-lg border p-3"
                            :class="option.is_correct ? 'border-green-300 bg-green-50' : 'border-gray-200 dark:border-green-900/60'"
                        >
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600 dark:text-gray-300"
                            >
                                {{ ['A', 'B', 'C', 'D'][idx] }}
                            </span>
                            <RichContentEditor
                                v-model="option.content"
                                class="min-w-64 flex-1"
                                :placeholder="`Option ${['A', 'B', 'C', 'D'][idx]}`"
                                compact
                            />
                            <input type="radio" :value="idx" :checked="correctIndex === idx" @change="correctIndex = idx" />
                            <span class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Correct</span>
                        </div>
                    </div>
                </div>

                <!-- Theory Marking Scheme -->
                <div
                    v-if="form.type === 'theory'"
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Marking Scheme</h2>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Expected points and their weights.</p>
                        </div>
                        <button
                            type="button"
                            @click="addSchemeRow"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-green-900/60 dark:bg-green-950/45 dark:text-gray-300"
                        >
                            + Add Point
                        </button>
                    </div>
                    <p v-if="form.errors.marking_scheme" class="mt-2 text-xs text-red-600">{{ form.errors.marking_scheme }}</p>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="(row, idx) in form.marking_scheme"
                            :key="idx"
                            class="flex flex-col gap-3 rounded-lg border border-gray-200 p-3 sm:flex-row sm:items-center dark:border-green-900/60"
                        >
                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-500">#{{ idx + 1 }}</span>
                            <RichContentEditor v-model="row.point" class="min-w-64 flex-1" placeholder="Expected point..." compact />
                            <input
                                v-model.number="row.weight"
                                type="number"
                                min="1"
                                class="w-full rounded-lg border-gray-200 text-center text-sm sm:w-20 dark:border-green-900/60"
                                placeholder="Weight"
                            />
                            <button type="button" @click="removeSchemeRow(idx)" class="text-xs text-red-600 hover:underline">Remove</button>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <Link href="/questions" class="btn-secondary dark:bg-green-950/45">Cancel</Link>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary/90 disabled:opacity-50 dark:border-green-900/60 dark:shadow-none"
                    >
                        <span v-if="submitting" class="inline-block size-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                        Create Question
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
