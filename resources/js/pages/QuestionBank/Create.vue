<script setup lang="ts">
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { store, index } from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { AppPageProps } from '@/types';
import type { Subject } from '@/types/academics';

const props = defineProps<{
    subjects: Subject[];
    classes: any[];
    types: any[];
    difficulties: any[];
}>();

const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user.roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const form = useForm({
    subject_id: '',
    topic_id: '',
    school_class_id: '',
    content: '',
    explanation: '',
    type: 'multiple_choice',
    difficulty: 'medium',
    options: [
        { content: '', is_correct: true },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
    ],
});

const selectedSubject = computed(() => {
    return props.subjects.find((s) => s.id === (form.subject_id as string));
});

const availableClasses = computed(() => {
    if (!selectedSubject.value) return [];

    // Get unique class IDs from the subject's topics
    const classIds = new Set((selectedSubject.value as any).topics.filter((t: any) => t.school_class_id).map((t: any) => t.school_class_id));

    return props.classes.filter((c) => classIds.has(c.id));
});

const filteredTopics = computed(() => {
    if (!selectedSubject.value || !form.school_class_id) return [];

    return (selectedSubject.value as any).topics.filter((topic: any) => !topic.school_class_id || topic.school_class_id === form.school_class_id);
});

watch(
    () => form.subject_id,
    () => {
        form.school_class_id = '';
        form.topic_id = '';
    },
);

watch(
    () => form.school_class_id,
    () => {
        form.topic_id = '';
    },
);

const addOption = () => {
    form.options.push({ content: '', is_correct: false });
};

const removeOption = (index: number) => {
    if (form.options.length > 2) {
        form.options.splice(index, 1);
    }
};

const setCorrectOption = (index: number) => {
    if (form.type === 'multiple_choice') {
        form.options.forEach((opt, i) => {
            opt.is_correct = i === index;
        });
    }
};

const submit = () => {
    form.post(store().url, {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <component :is="Layout">
        <Head title="Create Question" />

        <div class="p-8">
            <div class="w-full">
                <!-- Back Button -->
                <div class="mb-8">
                    <Link :href="index().url" class="flex items-center text-sm font-bold text-slate-500 transition-colors hover:text-primary">
                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Repository
                    </Link>
                </div>

                <div class="mb-8 border-b border-slate-200 bg-white p-8">
                    <div class="mb-10 flex items-center justify-between">
                        <h3 class="text-3xl font-bold text-slate-900">Create New Question</h3>
                        <p class="text-sm text-slate-500">Define your question metadata, content, and options.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-10">
                        <!-- Metadata Row -->
                        <div class="grid grid-cols-1 gap-10 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Subject</label>
                                <select
                                    v-model="form.subject_id"
                                    required
                                    class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3.5 text-sm transition-all focus:border-primary focus:ring-primary"
                                >
                                    <option value="" disabled>Select Subject</option>
                                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                        {{ subject.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Topic</label>
                                <select
                                    v-model="form.topic_id"
                                    required
                                    :disabled="!selectedSubject"
                                    class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3.5 text-sm transition-all focus:border-primary focus:ring-primary disabled:opacity-50"
                                >
                                    <option value="" disabled>Select Topic</option>
                                    <option v-for="topic in filteredTopics" :key="topic.id" :value="topic.id">
                                        {{ topic.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Target Class</label>
                                <select
                                    v-model="form.school_class_id"
                                    required
                                    :disabled="!selectedSubject"
                                    class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3.5 text-sm transition-all focus:border-primary focus:ring-primary disabled:opacity-50"
                                >
                                    <option value="" disabled>Select Class</option>
                                    <option v-for="cls in availableClasses" :key="cls.id" :value="cls.id">{{ cls.name }} ({{ cls.level }})</option>
                                </select>
                            </div>
                        </div>

                        <!-- Type & Difficulty Toggles -->
                        <div class="grid grid-cols-1 gap-10 md:grid-cols-2">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-6">
                                <label class="mb-4 block text-sm font-semibold text-slate-700">Question Type</label>
                                <div class="flex gap-4">
                                    <button
                                        v-for="type in types"
                                        :key="type.value"
                                        type="button"
                                        @click="form.type = type.value"
                                        :class="[
                                            'flex-1 rounded-xl border-2 py-3.5 text-sm font-bold transition-all',
                                            form.type === type.value
                                                ? 'border-primary bg-primary text-white shadow-md'
                                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300',
                                        ]"
                                    >
                                        {{ type.label }}
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-6">
                                <label class="mb-4 block text-sm font-semibold text-slate-700">Difficulty Level</label>
                                <div class="flex gap-4">
                                    <button
                                        v-for="diff in difficulties"
                                        :key="diff.value"
                                        type="button"
                                        @click="form.difficulty = diff.value"
                                        :class="[
                                            'flex-1 rounded-xl border-2 py-3.5 text-sm font-bold transition-all',
                                            form.difficulty === diff.value
                                                ? 'border-primary bg-primary text-white shadow-md'
                                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300',
                                        ]"
                                    >
                                        {{ diff.label }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content Grid -->
                        <div class="grid grid-cols-1 gap-16 lg:grid-cols-2">
                            <div class="space-y-8">
                                <div>
                                    <label class="mb-3 block text-sm font-semibold text-slate-700">Question Content</label>
                                    <textarea
                                        v-model="form.content"
                                        rows="10"
                                        required
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-5 py-5 text-sm transition-all focus:border-primary focus:ring-primary"
                                        placeholder="Enter your question here..."
                                    ></textarea>
                                    <div v-if="form.errors.content" class="mt-2 text-xs font-medium text-red-600">{{ form.errors.content }}</div>
                                </div>

                                <div>
                                    <label class="mb-3 block text-sm font-semibold text-slate-700">Explanation (Optional)</label>
                                    <textarea
                                        v-model="form.explanation"
                                        rows="4"
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-5 py-5 text-sm transition-all focus:border-primary focus:ring-primary"
                                        placeholder="Explain why the correct answer is right..."
                                    ></textarea>
                                </div>
                            </div>

                            <div class="space-y-8">
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-semibold text-slate-700">Answer Options</label>
                                    <button
                                        type="button"
                                        @click="addOption"
                                        class="flex items-center rounded-lg bg-primary/5 px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary/10 hover:underline"
                                    >
                                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Add Option
                                    </button>
                                </div>
                                <div v-if="form.errors.options" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.options }}</div>

                                <div class="space-y-5">
                                    <div v-for="(option, index) in form.options" :key="index" class="group flex gap-4">
                                        <div class="relative flex-1">
                                            <input
                                                v-model="option.content"
                                                type="text"
                                                required
                                                class="block w-full rounded-xl border-slate-200 bg-slate-50 px-5 py-4 pr-12 text-sm transition-all focus:border-primary focus:ring-primary"
                                                :placeholder="`Option ${index + 1}`"
                                            />
                                            <button
                                                v-if="form.options.length > 2"
                                                type="button"
                                                @click="removeOption(index)"
                                                class="absolute top-1/2 right-4 -translate-y-1/2 text-slate-300 opacity-0 transition-colors group-hover:opacity-100 hover:text-red-500"
                                            >
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            @click="setCorrectOption(index)"
                                            :class="[
                                                'w-36 shrink-0 rounded-xl border-2 text-xs font-bold transition-all',
                                                option.is_correct
                                                    ? 'border-green-500 bg-green-500 text-white shadow-md'
                                                    : 'border-slate-200 bg-white text-slate-400 hover:border-slate-300',
                                            ]"
                                        >
                                            {{ option.is_correct ? 'Correct Answer' : 'Mark Correct' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end border-t border-slate-100 pt-10">
                            <div class="flex gap-4">
                                <Link :href="index().url" class="px-8 py-4 text-sm font-bold text-slate-500 transition-colors hover:text-slate-700">
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex items-center justify-center rounded-xl bg-primary px-16 py-4 text-lg font-bold text-white shadow-xl transition-all hover:scale-105 hover:bg-primary/90 active:scale-95 disabled:opacity-50"
                                >
                                    <span v-if="form.processing" class="mr-2 animate-spin">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24">
                                            <circle
                                                class="opacity-25"
                                                cx="12"
                                                cy="12"
                                                r="10"
                                                stroke="currentColor"
                                                stroke-width="4"
                                                fill="none"
                                            ></circle>
                                            <path
                                                class="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                            ></path>
                                        </svg>
                                    </span>
                                    Publish to Repository
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </component>
</template>
