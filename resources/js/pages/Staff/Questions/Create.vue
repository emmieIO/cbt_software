<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { storeQuestion, index } from '@/actions/App/Http/Controllers/Staff/StaffController';
import StaffLayout from '@/layouts/StaffLayout.vue';

const props = defineProps<{
    subjects: any[];
    classes: any[];
    types: any[];
    difficulties: any[];
}>();

const form = useForm({
    subject_id: '',
    topic_id: '',
    school_class_id: '',
    content: '',
    explanation: '',
    type: 'multiple_choice',
    difficulty: 'medium',
    is_active: true,
    options: [
        { content: '', is_correct: true },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
    ],
});

const selectedSubject = computed(() => {
    return props.subjects.find((s) => s.id === parseInt(form.subject_id as string));
});

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
    form.post(storeQuestion().url, {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <StaffLayout>
        <Head title="Create Question" />

        <div class="p-8">
            <div class="w-full">
                <!-- Back Button -->
                <div class="mb-8">
                    <Link 
                        :href="index().url" 
                        class="text-sm font-bold text-slate-500 hover:text-primary flex items-center transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Back to Repository
                    </Link>
                </div>

                <div class="mb-8 bg-white p-8 border-b border-slate-200">
                    <div class="flex items-center justify-between mb-10">
                        <h3 class="text-3xl font-bold text-slate-900">Create New Question</h3>
                        <p class="text-sm text-slate-500">Define your question metadata, content, and options.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-10">
                        <!-- Metadata Row -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Subject</label>
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
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Topic</label>
                                <select
                                    v-model="form.topic_id"
                                    required
                                    :disabled="!selectedSubject"
                                    class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3.5 text-sm transition-all focus:border-primary focus:ring-primary disabled:opacity-50"
                                >
                                    <option value="" disabled>Select Topic</option>
                                    <option v-for="topic in selectedSubject?.topics" :key="topic.id" :value="topic.id">
                                        {{ topic.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Target Class</label>
                                <select
                                    v-model="form.school_class_id"
                                    required
                                    class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3.5 text-sm transition-all focus:border-primary focus:ring-primary"
                                >
                                    <option value="" disabled>Select Class</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                                        {{ cls.name }} ({{ cls.level }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Type & Difficulty Toggles -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="p-6 bg-slate-50/50 rounded-2xl border border-slate-100">
                                <label class="block text-sm font-semibold text-slate-700 mb-4">Question Type</label>
                                <div class="flex gap-4">
                                    <button
                                        v-for="type in types"
                                        :key="type.value"
                                        type="button"
                                        @click="form.type = type.value"
                                        :class="[
                                            'flex-1 rounded-xl py-3.5 text-sm font-bold transition-all border-2',
                                            form.type === type.value
                                                ? 'bg-primary border-primary text-white shadow-md'
                                                : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'
                                        ]"
                                    >
                                        {{ type.label }}
                                    </button>
                                </div>
                            </div>

                            <div class="p-6 bg-slate-50/50 rounded-2xl border border-slate-100">
                                <label class="block text-sm font-semibold text-slate-700 mb-4">Difficulty Level</label>
                                <div class="flex gap-4">
                                    <button
                                        v-for="diff in difficulties"
                                        :key="diff.value"
                                        type="button"
                                        @click="form.difficulty = diff.value"
                                        :class="[
                                            'flex-1 rounded-xl py-3.5 text-sm font-bold transition-all border-2',
                                            form.difficulty === diff.value
                                                ? 'bg-primary border-primary text-white shadow-md'
                                                : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'
                                        ]"
                                    >
                                        {{ diff.label }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                            <div class="space-y-8">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-3">Question Content</label>
                                    <textarea
                                        v-model="form.content"
                                        rows="10"
                                        required
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-5 py-5 text-sm transition-all focus:border-primary focus:ring-primary"
                                        placeholder="Enter your question here..."
                                    ></textarea>
                                    <div v-if="form.errors.content" class="mt-2 text-xs text-red-600 font-medium">{{ form.errors.content }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-3">Explanation (Optional)</label>
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
                                        class="text-xs font-bold text-primary hover:underline flex items-center bg-primary/5 px-3 py-1.5 rounded-lg transition-colors hover:bg-primary/10"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                        Add Option
                                    </button>
                                </div>
                                <div v-if="form.errors.options" class="mt-1 text-xs text-red-600 font-medium">{{ form.errors.options }}</div>

                                <div class="space-y-5">
                                    <div v-for="(option, index) in form.options" :key="index" class="flex gap-4 group">
                                        <div class="flex-1 relative">
                                            <input
                                                v-model="option.content"
                                                type="text"
                                                required
                                                class="block w-full rounded-xl border-slate-200 bg-slate-50 px-5 py-4 text-sm pr-12 transition-all focus:border-primary focus:ring-primary"
                                                :placeholder="`Option ${index + 1}`"
                                            />
                                            <button
                                                v-if="form.options.length > 2"
                                                type="button"
                                                @click="removeOption(index)"
                                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100"
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
                                                'w-36 rounded-xl text-xs font-bold transition-all border-2 shrink-0',
                                                option.is_correct
                                                    ? 'bg-green-500 border-green-500 text-white shadow-md'
                                                    : 'bg-white border-slate-200 text-slate-400 hover:border-slate-300'
                                            ]"
                                        >
                                            {{ option.is_correct ? 'Correct Answer' : 'Mark Correct' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end pt-10 border-t border-slate-100">
                            <div class="gap-4 flex">
                                <Link
                                    :href="index().url"
                                    class="px-8 py-4 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex items-center justify-center rounded-xl bg-primary px-16 py-4 text-lg font-bold text-white shadow-xl transition-all hover:scale-105 hover:bg-primary/90 active:scale-95 disabled:opacity-50"
                                >
                                    <span v-if="form.processing" class="mr-2 animate-spin">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
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
    </StaffLayout>
</template>
