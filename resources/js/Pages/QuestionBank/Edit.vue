<script setup lang="ts">
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, watch, onMounted, ref } from 'vue';
import { update, index } from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { AppPageProps } from '@/types';
import type { Subject } from '@/types/academics';

const props = defineProps<{
    question: any; // Using any to avoid complex nested type issues for now
    subjects: Subject[];
    classes: any[];
    types: any[];
    difficulties: any[];
}>();

const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user.roles.includes('super_admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const compactLevelTag = (level: string) => {
    const normalized = String(level).toLowerCase();
    if (normalized === 'primary') return 'Primary';
    if (normalized === 'secondary') return 'Secondary';
    if (normalized === 'nursery') return 'Nursery';

    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
};
const resolveQuestionImageSrc = (imagePath: string | null | undefined) => {
    if (!imagePath) return null;
    if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) return imagePath;
    return `/storage/${imagePath}`;
};
const levelOptions = computed(() => {
    const levels = Array.from(new Set([...props.subjects.map((s) => String(s.level)), ...props.classes.map((c) => String(c.level))])).filter(Boolean);

    return levels.map((level) => ({
        id: level,
        name: level.toUpperCase(),
    }));
});

// Initialize preview from props
if (props.question.image_path) {
    imagePreview.value = resolveQuestionImageSrc(props.question.image_path);
}

const form = useForm({
    subject_id: props.question.topic?.subject?.id || '',
    topic_id: props.question.topic_id,
    school_class_id: props.question.school_class_id,
    content: props.question.content,
    explanation: props.question.explanation || '',
    type: props.question.type,
    difficulty: props.question.difficulty,
    image: null as File | null,
    remove_image: false,
    options: props.question.options.map((opt: any) => ({
        id: opt.id,
        content: opt.content,
        is_correct: !!opt.is_correct,
    })),
});

const initialLevel =
    props.question.topic?.subject?.level ||
    props.classes.find((c: any) => String(c.id) === String(props.question.school_class_id))?.level ||
    '';
const selectedLevel = ref(String(initialLevel || (levelOptions.value.length === 1 ? levelOptions.value[0].id : '')));

// Watch for prop changes just in case
watch(
    () => props.question.image_path,
    (newPath) => {
        if (newPath && !form.image) {
            imagePreview.value = resolveQuestionImageSrc(newPath);
        }
    },
    { immediate: true },
);

const handleImageChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        form.image = file;
        form.remove_image = false;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeImage = () => {
    form.image = null;
    form.remove_image = true;
    imagePreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

const subjectsWithOptions = computed(() => {
    const subjects = selectedLevel.value
        ? props.subjects.filter((s) => String(s.level) === String(selectedLevel.value))
        : [];

    return subjects.map((s) => ({
        ...s,
        name: s.name,
        badge: compactLevelTag(String(s.level)),
    }));
});

const selectedSubject = computed(() => {
    return props.subjects.find((s) => s.id === (form.subject_id as string));
});

const availableClasses = computed(() => {
    if (!selectedLevel.value) return [];

    return props.classes.filter((c) => String(c.level) === String(selectedLevel.value));
});

const filteredTopics = computed(() => {
    if (!selectedSubject.value || !form.school_class_id) return [];

    const topics = (selectedSubject.value as any).topics || [];

    // Return explicitly mapped topics that match the selected subject AND the selected class
    return topics
        .filter((topic: any) => String(topic.school_class_id) === String(form.school_class_id))
        .map((topic: any) => ({
            id: topic.id,
            name: topic.name,
        }));
});

// Avoid clearing on mount
const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

watch(
    () => selectedLevel.value,
    (newVal, oldVal) => {
        if (isMounted.value && oldVal !== '' && newVal !== oldVal) {
            form.subject_id = '';
            form.school_class_id = '';
            form.topic_id = '';
        }
    },
);

watch(
    () => form.subject_id,
    (newVal, oldVal) => {
        if (isMounted.value && oldVal !== '') {
            form.school_class_id = '';
            form.topic_id = '';
        }
    },
);

watch(
    () => form.school_class_id,
    (newVal, oldVal) => {
        if (isMounted.value && oldVal !== '') {
            form.topic_id = '';
        }
    },
);

const addOption = () => {
    form.options.push({ id: undefined as any, content: '', is_correct: false });
};

const removeOption = (idx: number) => {
    if (form.options.length > 2) {
        form.options.splice(idx, 1);
    }
};

const setCorrectOption = (idx: number) => {
    form.options.forEach((opt: any, i: number) => {
        opt.is_correct = i === idx;
    });
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(update(props.question.id).url);
};
</script>

<template>
    <component :is="Layout">
        <Head title="Edit Question" />

        <div class="mx-auto max-w-7xl pb-24">
            <div class="space-y-6 sm:space-y-10">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="transition-colors hover:text-primary">Dashboard</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <Link :href="index().url" class="transition-colors hover:text-primary">Question Bank</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-800">Edit Item</span>
                </nav>

                <!-- Page Header -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Edit Question</h1>
                        <p class="mt-1 text-sm text-gray-500">Ref #{{ question.id.substring(0, 8) }} • Modify assessment item details</p>
                    </div>
                    <div class="flex items-center gap-x-2">
                        <Link
                            :href="index().url"
                            class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none"
                        >
                            Cancel
                        </Link>
                        <button
                            @click="submit"
                            :disabled="form.processing"
                            class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-2.5 text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                        >
                            <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                            Update Question
                        </button>
                    </div>
                </div>

                <!-- Global Error Alert -->
                <div
                    v-if="form.errors.options"
                    class="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                    role="alert"
                >
                    <svg
                        class="size-4 shrink-0"
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" x2="12" y1="8" y2="12" />
                        <line x1="12" x2="12.01" y1="16" y2="16" />
                    </svg>
                    {{ form.errors.options }}
                </div>

                <!-- 01. Classification Section -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-x-3">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">1</span>
                        <h2 class="text-lg font-semibold text-gray-800">Context & Classification</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <CustomSelect
                            v-model="selectedLevel"
                            label="Academic Level"
                            :options="levelOptions"
                            placeholder="Choose Level"
                            size="md"
                        />
                        <CustomSelect
                            v-model="form.subject_id"
                            label="Subject Area"
                            :options="subjectsWithOptions"
                            placeholder="Choose Subject"
                            :disabled="!selectedLevel"
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
                    </div>
                </div>

                <!-- 02. Question Content Section -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-x-3">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">2</span>
                            <h2 class="text-lg font-semibold text-gray-800">Problem Statement <span class="text-red-500">*</span></h2>
                        </div>
                        <button
                            type="button"
                            @click="fileInput?.click()"
                            class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            {{ imagePreview ? 'Replace Image' : 'Add Image' }}
                        </button>
                    </div>

                    <div class="space-y-6">
                        <textarea
                            v-model="form.content"
                            rows="5"
                            required
                            class="block w-full rounded-lg border-gray-200 px-4 py-3 text-lg font-medium focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                            :class="form.errors.content ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200'"
                            placeholder="Update question content..."
                        ></textarea>
                        <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>

                        <div v-if="imagePreview" class="relative inline-block overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                            <img :src="imagePreview" class="max-h-96 w-auto object-contain" />
                            <button
                                @click="removeImage"
                                type="button"
                                class="absolute top-2 right-2 flex size-8 items-center justify-center rounded-lg bg-red-600 text-white shadow-sm transition-colors hover:bg-red-700"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <input ref="fileInput" type="file" class="hidden" accept="image/*" @change="handleImageChange" />

                        <div class="border-t border-gray-100 pt-6">
                            <label class="mb-2 block text-sm font-medium text-gray-800">Explanation (Optional)</label>
                            <textarea
                                v-model="form.explanation"
                                rows="2"
                                class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                placeholder="Update explanation..."
                            ></textarea>
                            <p v-if="form.errors.explanation" class="mt-1 text-xs text-red-600">{{ form.errors.explanation }}</p>
                        </div>
                    </div>
                </div>

                <!-- 03. Answer Options Section -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-x-3">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">3</span>
                            <h2 class="text-lg font-semibold text-gray-800">Response Choices <span class="text-red-500">*</span></h2>
                        </div>
                        <button
                            type="button"
                            @click="addOption"
                            class="inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-200 focus:outline-none"
                        >
                            + Add Option
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div v-for="(option, index) in form.options" :key="index" class="space-y-1">
                            <div
                                class="flex items-center gap-4 rounded-lg border bg-white p-4 transition-colors hover:bg-gray-50"
                                :class="(form.errors as any)[`options.${index}.content`] ? 'border-red-200' : 'border-gray-200'"
                            >
                                <button
                                    type="button"
                                    @click="setCorrectOption(Number(index))"
                                    :class="[
                                        'flex size-10 shrink-0 items-center justify-center rounded-lg border-2 transition-all',
                                        option.is_correct
                                            ? 'border-green-500 bg-green-500 text-white'
                                            : 'border-gray-200 bg-white text-gray-400 hover:border-gray-300',
                                    ]"
                                >
                                    <span v-if="!option.is_correct" class="text-sm font-bold">{{ String.fromCharCode(65 + Number(index)) }}</span>
                                    <svg v-else class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>

                                <div class="flex-1">
                                    <input
                                        v-model="option.content"
                                        type="text"
                                        required
                                        class="block w-full border-none bg-transparent px-0 py-2 text-sm font-medium focus:ring-0"
                                        :placeholder="`Enter content for choice ${String.fromCharCode(65 + Number(index))}`"
                                    />
                                </div>

                                <button
                                    v-if="form.options.length > 2"
                                    type="button"
                                    @click="removeOption(Number(index))"
                                    class="flex size-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="(form.errors as any)[`options.${index}.content`]" class="px-1 text-[10px] text-red-600">
                                {{ (form.errors as any)[`options.${index}.content`] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 04. Configuration Section -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-x-3">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">4</span>
                        <h2 class="text-lg font-semibold text-gray-800">Question Settings</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                        <!-- Type -->
                        <div class="space-y-4">
                            <label class="block text-sm font-medium text-gray-800">Question Format</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="type in types"
                                    :key="type.value"
                                    type="button"
                                    @click="form.type = type.value"
                                    class="inline-flex items-center gap-x-2 rounded-lg border px-4 py-2 text-sm font-medium transition-colors focus:outline-none"
                                    :class="
                                        form.type === type.value
                                            ? 'border-transparent bg-primary text-white'
                                            : 'border-gray-200 bg-white text-gray-800 hover:bg-gray-50'
                                    "
                                >
                                    {{ type.label }}
                                </button>
                            </div>
                            <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
                        </div>

                        <!-- Difficulty -->
                        <div class="space-y-4">
                            <label class="block text-sm font-medium text-gray-800">Difficulty Level</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="diff in difficulties"
                                    :key="diff.value"
                                    type="button"
                                    @click="form.difficulty = diff.value"
                                    class="inline-flex items-center gap-x-2 rounded-lg border px-4 py-2 text-sm font-medium transition-colors focus:outline-none"
                                    :class="
                                        form.difficulty === diff.value
                                            ? 'border-transparent bg-primary text-white'
                                            : 'border-gray-200 bg-white text-gray-800 hover:bg-gray-50'
                                    "
                                >
                                    {{ diff.label }}
                                </button>
                            </div>
                            <p v-if="form.errors.difficulty" class="mt-1 text-xs text-red-600">{{ form.errors.difficulty }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Bar -->
                <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="hidden items-center gap-2 text-xs text-gray-500 md:flex">
                        <span>Updating record</span>
                        <span>•</span>
                        <span class="font-mono text-primary">{{ question.id.substring(0, 8) }}</span>
                    </div>
                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="hover:bg-primary-hover inline-flex w-full items-center justify-center gap-x-2 rounded-lg border border-transparent bg-primary px-10 py-3 text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none disabled:pointer-events-none disabled:opacity-50 md:w-auto"
                    >
                        <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                        Save Modifications
                    </button>
                </div>
            </div>
        </div>
    </component>
</template>

<style scoped>
/* Standard Clean Look */
textarea:focus,
input:focus {
    outline: none !important;
}
</style>
