<script setup lang="ts">
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import { store, index } from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { AppPageProps } from '@/types';
import type { Subject } from '@/types/academics';

const props = defineProps<{
    subjects: Subject[];
    classes: any[];
    batches: any[];
    types: any[];
    difficulties: any[];
}>();

const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user.roles.includes('super_admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    subject_id: '',
    topic_id: '',
    school_class_id: '',
    prospective_class_id: '',
    content: '',
    explanation: '',
    type: 'multiple_choice',
    difficulty: 'medium',
    image: null as File | null,
    options: [
        { content: '', is_correct: true },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
    ],
});

const handleImageChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        form.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

const selectedSubject = computed(() => {
    return props.subjects.find((s) => s.id === (form.subject_id as string));
});

const availableClasses = computed(() => {
    if (!selectedSubject.value) return [];

    // Filter the global classes prop to only show those that match the selected subject's level
    // (e.g., if English is 'secondary', only show JSS1-SS3)
    return props.classes.filter((c) => String(c.level) === String(selectedSubject.value?.level));
});

const filteredTopics = computed(() => {
    if (!selectedSubject.value || !form.school_class_id) return [];

    const topics = (selectedSubject.value as any).topics || [];

    // Return explicitly mapped topics that match the selected subject AND the selected class
    return topics
        .filter((topic: any) => String(topic.school_class_id) === String(form.school_class_id))
        .map((topic: any) => ({
            id: topic.id,
            name: topic.name
        }));
});

watch(() => form.subject_id, () => {
    form.school_class_id = '';
    form.topic_id = '';
});

watch(() => form.school_class_id, () => {
    form.topic_id = '';
});

const addOption = () => {
    form.options.push({ content: '', is_correct: false });
};

const removeOption = (idx: number) => {
    if (form.options.length > 2) {
        form.options.splice(idx, 1);
    }
};

const setCorrectOption = (idx: number) => {
    form.options.forEach((opt, i) => {
        opt.is_correct = i === idx;
    });
};

const submit = () => {
    form.post(store().url, {
        onSuccess: () => {
            form.reset();
            imagePreview.value = null;
        },
    });
};
</script>

<template>
    <component :is="Layout">
        <Head title="Create Question" />

        <div class="max-w-7xl mx-auto pb-24">
            <div class="space-y-6 sm:space-y-10">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="hover:text-primary transition-colors">Dashboard</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <Link :href="index().url" class="hover:text-primary transition-colors">Question Bank</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span class="text-gray-800">Add Question</span>
                </nav>

                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Create Question</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Draft a new assessment item for the question bank
                        </p>
                    </div>
                    <div class="flex items-center gap-x-2">
                        <Link
                            :href="index().url"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none"
                        >
                            Cancel
                        </Link>
                        <button
                            @click="submit"
                            :disabled="form.processing"
                            class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                            Publish Question
                        </button>
                    </div>
                </div>

                <!-- Global Error Alert -->
                <div v-if="form.errors.options" class="bg-red-50 border border-red-200 text-sm text-red-800 rounded-lg p-4 flex items-center gap-3" role="alert">
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    {{ form.errors.options }}
                </div>

                <!-- 01. Classification Section -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                    <div class="mb-6 flex items-center gap-x-3">
                        <span class="size-8 flex items-center justify-center rounded-lg bg-primary/10 text-primary font-semibold text-sm">1</span>
                        <h2 class="text-lg font-semibold text-gray-800">Context & Classification</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <CustomSelect
                            v-model="form.subject_id"
                            label="Subject Area"
                            :options="subjects"
                            placeholder="Choose Subject"
                            :error="form.errors.subject_id"
                            size="md"
                        />
                        <CustomSelect
                            v-model="form.school_class_id"
                            label="Target Level"
                            :options="availableClasses"
                            placeholder="Choose Class"
                            :error="form.errors.school_class_id"
                            size="md"
                        />
                        <CustomSelect
                            v-model="form.topic_id"
                            label="Curriculum Topic"
                            :options="filteredTopics"
                            placeholder="Choose Topic"
                            :disabled="!form.subject_id || !form.school_class_id"
                            :error="form.errors.topic_id"
                            size="md"
                        />
                        <CustomSelect
                            v-if="batches && batches.length > 0"
                            v-model="form.prospective_class_id"
                            label="Entrance Cohort"
                            :options="batches"
                            placeholder="Regular Enrollment"
                            :error="form.errors.prospective_class_id"
                            size="md"
                        />
                    </div>
                </div>

                <!-- 02. Question Content Section -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-x-3">
                            <span class="size-8 flex items-center justify-center rounded-lg bg-primary/10 text-primary font-semibold text-sm">2</span>
                            <h2 class="text-lg font-semibold text-gray-800">Problem Statement <span class="text-red-500">*</span></h2>
                        </div>
                        <button type="button" @click="fileInput?.click()" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            {{ imagePreview ? 'Replace Image' : 'Add Image' }}
                        </button>
                    </div>

                    <div class="space-y-6">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="type in types" :key="type.value" type="button"
                                @click="form.type = type.value"
                                class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border transition-colors focus:outline-none"
                                :class="form.type === type.value ? 'bg-primary text-white border-transparent' : 'bg-white text-gray-800 border-gray-200 hover:bg-gray-50'"
                            >
                                {{ type.label }}
                            </button>
                        </div>

                        <textarea
                            v-model="form.content"
                            rows="4"
                            required
                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-lg font-medium focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                            :class="form.errors.content ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200'"
                            placeholder="Type your question here..."
                        ></textarea>
                        <p v-if="form.errors.content" class="text-xs text-red-600 mt-1">{{ form.errors.content }}</p>

                        <div v-if="imagePreview" class="relative inline-block overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                            <img :src="imagePreview" class="max-h-80 w-auto object-contain" />
                            <button @click="removeImage" type="button" class="absolute top-2 right-2 size-8 flex items-center justify-center rounded-lg bg-red-600 text-white shadow-sm hover:bg-red-700 transition-colors">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <input ref="fileInput" type="file" class="hidden" accept="image/*" @change="handleImageChange" />

                        <div class="pt-6 border-t border-gray-100">
                            <label class="block text-sm font-medium text-gray-800 mb-2">Explanation (Optional)</label>
                            <textarea
                                v-model="form.explanation"
                                rows="2"
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                placeholder="Provide a detailed explanation for the correct answer..."
                            ></textarea>
                            <p v-if="form.errors.explanation" class="text-xs text-red-600 mt-1">{{ form.errors.explanation }}</p>
                        </div>
                    </div>
                </div>

                <!-- 03. Answer Options Section -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-x-3">
                            <span class="size-8 flex items-center justify-center rounded-lg bg-primary/10 text-primary font-semibold text-sm">3</span>
                            <h2 class="text-lg font-semibold text-gray-800">Response Choices <span class="text-red-500">*</span></h2>
                        </div>
                        <button type="button" @click="addOption" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-none">
                            + Add Option
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div v-for="(option, index) in form.options" :key="index" class="space-y-1">
                            <div class="flex items-center gap-4 p-4 border rounded-lg bg-white hover:bg-gray-50 transition-colors" :class="form.errors[`options.${index}.content`] ? 'border-red-200' : 'border-gray-200'">
                                <button
                                    type="button"
                                    @click="setCorrectOption(index)"
                                    :class="[
                                        'size-10 shrink-0 flex items-center justify-center rounded-lg border-2 transition-all',
                                        option.is_correct
                                            ? 'border-green-500 bg-green-500 text-white'
                                            : 'border-gray-200 bg-white text-gray-400 hover:border-gray-300'
                                    ]"
                                >
                                    <span v-if="!option.is_correct" class="text-sm font-bold">{{ String.fromCharCode(65 + index) }}</span>
                                    <svg v-else class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                </button>

                                <div class="flex-1">
                                    <input
                                        v-model="option.content"
                                        type="text"
                                        required
                                        class="py-2 px-0 block w-full border-none bg-transparent text-sm font-medium focus:ring-0"
                                        :placeholder="`Enter option ${String.fromCharCode(65 + index)} content`"
                                    />
                                </div>

                                <button
                                    v-if="form.options.length > 2"
                                    type="button"
                                    @click="removeOption(index)"
                                    class="size-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                            <p v-if="form.errors[`options.${index}.content`]" class="text-[10px] text-red-600 px-1">{{ form.errors[`options.${index}.content`] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer Summary Bar -->
                <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="hidden md:flex items-center gap-4">
                        <div class="flex items-center gap-1.5" :class="form.content ? 'text-teal-600' : 'text-gray-400'">
                            <span class="size-2 rounded-full" :class="form.content ? 'bg-teal-600' : 'bg-gray-300'"></span>
                            <span class="text-xs font-medium">Question Content</span>
                        </div>
                        <div class="flex items-center gap-1.5" :class="form.options.some(o => o.content) ? 'text-teal-600' : 'text-gray-400'">
                            <span class="size-2 rounded-full" :class="form.options.some(o => o.content) ? 'bg-teal-600' : 'bg-gray-300'"></span>
                            <span class="text-xs font-medium">Answer Choices</span>
                        </div>
                        <div class="flex items-center gap-1.5" :class="form.subject_id ? 'text-teal-600' : 'text-gray-400'">
                            <span class="size-2 rounded-full" :class="form.subject_id ? 'bg-teal-600' : 'bg-gray-300'"></span>
                            <span class="text-xs font-medium">Taxonomy</span>
                        </div>
                    </div>
                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="w-full md:w-auto py-2.5 px-6 inline-flex items-center justify-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                    >
                        <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                        Publish to Bank
                    </button>
                </div>
            </div>
        </div>
    </component>
</template>

<style scoped>
/* Standard Clean Look */
textarea:focus, input:focus {
    outline: none !important;
}
</style>
