<script setup lang="ts">
import { computed } from 'vue';

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
    index: number;
    levels: LevelOption[];
    row: QuestionDraft;
    rowErrors?: RowErrors;
    subjects: SubjectOption[];
}>();

const emit = defineEmits<{
    remove: [];
    updateRow: [row: QuestionDraft];
}>();

const optionLabels = ['A', 'B', 'C', 'D'] as const;

const filteredSubjects = computed(() => props.subjects.filter((subject) => !subject.level || subject.level === props.row.level));
const selectedSubject = computed(() => props.subjects.find((subject) => subject.id === props.row.subject_id));
const filteredTopics = computed(() => selectedSubject.value?.topics ?? []);

const updateRow = (patch: Partial<QuestionDraft>) => {
    emit('updateRow', {
        ...props.row,
        ...patch,
    });
};

const onLevelChange = () => {
    updateRow({ subject_id: '', topic_id: '' });
};

const onSubjectChange = () => {
    updateRow({ topic_id: '' });
};

const setType = (type: QuestionType) => {
    if (type === 'multiple_choice') {
        updateRow({
            type,
            marking_scheme: [{ point: '', weight: 1 }],
        });
        return;
    }

    updateRow({
        type,
        options: [
            { content: '', is_correct: false },
            { content: '', is_correct: false },
            { content: '', is_correct: false },
            { content: '', is_correct: false },
        ],
    });
};

const setCorrectOption = (index: number) => {
    updateRow({
        options: props.row.options.map((option, optionIndex) => ({
            ...option,
            is_correct: optionIndex === index,
        })),
    });
};

const addMarkingPoint = () => {
    updateRow({
        marking_scheme: [...props.row.marking_scheme, { point: '', weight: 1 }],
    });
};

const removeMarkingPoint = (index: number) => {
    if (props.row.marking_scheme.length > 1) {
        updateRow({
            marking_scheme: props.row.marking_scheme.filter((_, pointIndex) => pointIndex !== index),
        });
    }
};
</script>

<template>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:shadow-none">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Question {{ index + 1 }}</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Fill this question like a normal form, then add or remove cards as needed.</p>
            </div>
            <button type="button" @click="emit('remove')" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-900/40 dark:hover:bg-red-950/30">
                Remove
            </button>
        </div>

        <div class="mt-5 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <div class="flex gap-4">
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 p-4 transition-colors"
                    :class="row.type === 'multiple_choice' ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'">
                    <input :checked="row.type === 'multiple_choice'" type="radio" class="text-primary" @change="setType('multiple_choice')" />
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Multiple Choice</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Four options with one correct answer</p>
                    </div>
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 p-4 transition-colors"
                    :class="row.type === 'theory' ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'">
                    <input :checked="row.type === 'theory'" type="radio" class="text-primary" @change="setType('theory')" />
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Theory</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Essay with marking scheme</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="mt-5 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Classification</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Level</label>
                    <select :value="row.level" class="mt-1" @change="updateRow({ level: ($event.target as HTMLSelectElement).value }); onLevelChange()">
                        <option v-for="levelOption in levels" :key="levelOption.value" :value="levelOption.value">{{ levelOption.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Subject</label>
                    <select :value="row.subject_id" class="mt-1" @change="updateRow({ subject_id: ($event.target as HTMLSelectElement).value }); onSubjectChange()">
                        <option value="" disabled>Select subject</option>
                        <option v-for="subject in filteredSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                    </select>
                    <p v-if="rowErrors?.subject_id" class="mt-1 text-xs text-red-600">{{ rowErrors.subject_id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Topic</label>
                    <select :key="`${row.level}-${row.subject_id}`" :value="row.topic_id" class="mt-1" @change="updateRow({ topic_id: ($event.target as HTMLSelectElement).value })">
                        <option value="" disabled>Select topic</option>
                        <option v-for="topic in filteredTopics" :key="topic.id" :value="topic.id">{{ topic.name }}</option>
                    </select>
                    <p v-if="rowErrors?.topic_id" class="mt-1 text-xs text-red-600">{{ rowErrors.topic_id }}</p>
                </div>
            </div>
        </div>

        <div class="mt-5 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Question</h3>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Question text</label>
                <textarea :value="row.content" rows="4" class="mt-1" placeholder="Enter the question text..." @input="updateRow({ content: ($event.target as HTMLTextAreaElement).value })" />
                <p v-if="rowErrors?.content" class="mt-1 text-xs text-red-600">{{ rowErrors.content }}</p>
            </div>
        </div>

        <div v-if="row.type === 'multiple_choice'" class="mt-5 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Answer Options</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Fill the options and select the correct answer.</p>
            <p v-if="rowErrors?.options" class="mt-2 text-xs text-red-600">{{ rowErrors.options }}</p>

            <div class="mt-4 space-y-3">
                <div v-for="(option, index) in row.options" :key="index" class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                    :class="option.is_correct ? 'border-green-300 bg-green-50 dark:bg-green-950/20' : ''">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        {{ optionLabels[index] }}
                    </span>
                    <input :value="option.content" type="text" class="flex-1" :placeholder="`Option ${optionLabels[index]}`" @input="updateRow({ options: row.options.map((currentOption, optionIndex) => optionIndex === index ? { ...currentOption, content: ($event.target as HTMLInputElement).value } : currentOption) })" />
                    <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <input :checked="option.is_correct" type="radio" @change="setCorrectOption(index)" />
                        Correct
                    </label>
                </div>
            </div>
        </div>

        <div v-else class="mt-5 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Marking Scheme</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Add one or more expected points.</p>
                </div>
                <button type="button" @click="addMarkingPoint" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800/50">
                    + Add point
                </button>
            </div>
            <p v-if="rowErrors?.marking_scheme" class="mt-2 text-xs text-red-600">{{ rowErrors.marking_scheme }}</p>

            <div class="mt-4 space-y-3">
                <div v-for="(item, index) in row.marking_scheme" :key="index" class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <input :value="item.point" type="text" class="flex-1" placeholder="Expected point..." @input="updateRow({ marking_scheme: row.marking_scheme.map((currentItem, itemIndex) => itemIndex === index ? { ...currentItem, point: ($event.target as HTMLInputElement).value } : currentItem) })" />
                    <input :value="item.weight" type="number" min="1" class="w-24" placeholder="Weight" @input="updateRow({ marking_scheme: row.marking_scheme.map((currentItem, itemIndex) => itemIndex === index ? { ...currentItem, weight: Number(($event.target as HTMLInputElement).value) || 1 } : currentItem) })" />
                    <button type="button" @click="removeMarkingPoint(index)" class="text-xs text-red-600 hover:underline">Remove</button>
                </div>
            </div>
        </div>
    </section>
</template>
