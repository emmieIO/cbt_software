<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BatchQuestionRow from '@/components/Questions/BatchQuestionRow.vue';
import AppLayout from '@/layouts/AppLayout.vue';

type TopicOption = { id: string; name: string };
type SubjectOption = { id: string; name: string; level?: string | null; topics: TopicOption[] };
type LevelOption = { value: string; label: string };
type QuestionType = 'multiple_choice' | 'theory';
type QuestionOption = { content: string; is_correct: boolean };
type MarkingPoint = { point: string; weight: number };
type QuestionDraft = {
    id: number;
    type: QuestionType;
    subject_id: string;
    topic_id: string;
    level: string;
    content: string;
    options: QuestionOption[];
    marking_scheme: MarkingPoint[];
};
type RowErrors = Partial<Record<'subject_id' | 'topic_id' | 'content' | 'options' | 'marking_scheme', string>>;

const props = defineProps<{
    levels: LevelOption[];
    subjects: SubjectOption[];
}>();

const defaultLevel = props.levels[0]?.value ?? 'js';
const nextId = ref(2);
const submitting = ref(false);
const rowErrors = ref<Record<number, RowErrors>>({});

const createQuestion = (overrides: Partial<QuestionDraft> = {}): QuestionDraft => ({
    id: overrides.id ?? nextId.value++,
    type: overrides.type ?? 'multiple_choice',
    subject_id: overrides.subject_id ?? '',
    topic_id: overrides.topic_id ?? '',
    level: overrides.level ?? defaultLevel,
    content: overrides.content ?? '',
    options: overrides.options ? overrides.options.map((option) => ({ ...option })) : [
        { content: '', is_correct: false },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
    ],
    marking_scheme: overrides.marking_scheme ? overrides.marking_scheme.map((item) => ({ ...item })) : [{ point: '', weight: 1 }],
});

const questions = ref<QuestionDraft[]>([
    createQuestion({ id: 1 }),
]);

const questionCount = computed(() => questions.value.length);

const addQuestion = () => {
    questions.value.push(createQuestion());
};

const updateQuestion = (index: number, updatedQuestion: QuestionDraft) => {
    questions.value[index] = updatedQuestion;
};

const removeQuestion = (index: number) => {
    if (questions.value.length === 1) {
        questions.value[0] = createQuestion({ id: questions.value[0].id });
        delete rowErrors.value[questions.value[0].id];
        return;
    }

    const [removed] = questions.value.splice(index, 1);
    if (removed) {
        delete rowErrors.value[removed.id];
    }
};

const validateQuestion = (question: QuestionDraft): RowErrors => {
    const errors: RowErrors = {};

    if (!question.subject_id) errors.subject_id = 'Subject is required.';
    if (!question.topic_id) errors.topic_id = 'Topic is required.';
    if (!question.content.trim()) errors.content = 'Question text is required.';

    if (question.type === 'multiple_choice') {
        const hasEmptyOption = question.options.some((option) => !option.content.trim());
        const hasCorrectOption = question.options.some((option) => option.is_correct);

        if (hasEmptyOption || !hasCorrectOption) {
            errors.options = hasEmptyOption ? 'Fill all four options.' : 'Select the correct answer.';
        }
    } else if (!question.marking_scheme.some((item) => item.point.trim())) {
        errors.marking_scheme = 'Add at least one marking point.';
    }

    return errors;
};

const submit = () => {
    const nextErrors: Record<number, RowErrors> = {};

    const payload = questions.value.map((question) => {
        const errors = validateQuestion(question);
        if (Object.keys(errors).length) {
            nextErrors[question.id] = errors;
        }

        return {
            type: question.type,
            topic_id: question.topic_id,
            content: question.content.trim(),
            level: question.level,
            options: question.type === 'multiple_choice'
                ? question.options.map((option) => ({
                    content: option.content.trim(),
                    is_correct: option.is_correct,
                }))
                : [],
            marking_scheme: question.type === 'theory'
                ? question.marking_scheme
                    .filter((item) => item.point.trim())
                    .map((item) => ({
                        point: item.point.trim(),
                        weight: Math.max(1, Number(item.weight) || 1),
                    }))
                : [],
        };
    });

    rowErrors.value = nextErrors;

    if (Object.keys(nextErrors).length) {
        return;
    }

    submitting.value = true;
    router.post('/questions/bulk-store', { questions: payload }, {
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Batch Create Questions" />

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Batch Create Questions</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add simple question forms, remove the ones you do not need, then submit everything at once.</p>
                </div>
                <Link href="/questions" class="text-sm font-medium text-primary hover:underline">&larr; Back to questions</Link>
            </div>

            <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:shadow-none">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ questionCount }} {{ questionCount === 1 ? 'question' : 'questions' }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Each card is a normal question form.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" @click="addQuestion" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800/50">
                        + Add question
                    </button>
                    <button type="button" @click="submit" :disabled="submitting" class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-50">
                        <span v-if="submitting" class="inline-block size-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                        Create Many
                    </button>
                </div>
            </div>

            <div class="space-y-4">
                <BatchQuestionRow
                    v-for="(question, index) in questions"
                    :key="question.id"
                    :index="index"
                    :levels="levels"
                    :row="question"
                    :row-errors="rowErrors[question.id]"
                    :subjects="subjects"
                    @update-row="updateQuestion(index, $event)"
                    @remove="removeQuestion(index)"
                />
            </div>
        </div>
    </AppLayout>
</template>
