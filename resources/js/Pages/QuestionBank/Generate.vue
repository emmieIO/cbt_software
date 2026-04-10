<script setup lang="ts">
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { processGeneration } from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { AppPageProps } from '@/types';
import type { SchoolClass, Subject, Topic } from '@/types/academics';

type SelectOption = {
    id: string;
    name: string;
    badge?: string;
};

const props = defineProps<{
    subjects: (Subject & { topics: Topic[] })[];
    classes: SchoolClass[];
    batches: { id: string; name: string }[];
    types: { value: string; label: string }[];
    difficulties: { value: string; label: string }[];
}>();

const page = usePage<AppPageProps>();
const userPermissions = computed(() => page.props.auth.user.permissions);
const isAdmin = computed(() => userPermissions.value.includes('sys:manage_settings'));
const canCreateCrossLevel = computed(
    () => userPermissions.value.includes('access:cross-level-authoring')
        || userPermissions.value.includes('bank:create_cross_level')
        || userPermissions.value.includes('exam:create_cross_level')
        || isAdmin.value,
);
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const compactLevelTag = (level: string) => {
    const normalized = String(level).toLowerCase();

    if (normalized === 'primary') return 'Primary';
    if (normalized === 'secondary') return 'Secondary';
    if (normalized === 'nursery') return 'Nursery';

    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
};

const levelOptions = computed<SelectOption[]>(() => {
    const levels = Array.from(
        new Set([
            ...props.subjects.map((subject) => String(subject.level)),
            ...props.classes.map((schoolClass) => String(schoolClass.level)),
        ]),
    ).filter(Boolean);

    return levels.map((level) => ({
        id: level,
        name: level.toUpperCase(),
    }));
});

const defaultLevel = computed(() => {
    if (levelOptions.value.length === 1) {
        return levelOptions.value[0].id;
    }

    if (!canCreateCrossLevel.value && levelOptions.value.length > 0) {
        return levelOptions.value[0].id;
    }

    return '';
});

const selectedLevel = ref(defaultLevel.value);

const form = useForm({
    subject_id: '',
    topic_id: '',
    school_class_id: '',
    type: 'multiple_choice',
    count: 5,
    difficulty: 'medium',
});

const availableClasses = computed<SelectOption[]>(() => {
    if (!selectedLevel.value) return [];

    return props.classes
        .filter((schoolClass) => String(schoolClass.level) === String(selectedLevel.value))
        .map((schoolClass) => ({
            id: schoolClass.id,
            name: schoolClass.name,
            badge: compactLevelTag(String(schoolClass.level)),
        }));
});

const availableSubjects = computed<SelectOption[]>(() => {
    if (!selectedLevel.value) return [];

    return props.subjects
        .filter((subject) => String(subject.level) === String(selectedLevel.value))
        .map((subject) => ({
            id: subject.id,
            name: subject.name,
            badge: compactLevelTag(String(subject.level)),
        }));
});

const selectedClass = computed(() => props.classes.find((schoolClass) => schoolClass.id === form.school_class_id));
const selectedSubject = computed(() => props.subjects.find((subject) => subject.id === form.subject_id));
const selectedTopic = computed(() => filteredTopics.value.find((topic) => topic.id === form.topic_id));
const selectedType = computed(() => props.types.find((type) => type.value === form.type));
const selectedDifficulty = computed(() => props.difficulties.find((difficulty) => difficulty.value === form.difficulty));

const filteredTopics = computed<SelectOption[]>(() => {
    if (!selectedSubject.value || !form.school_class_id) return [];

    return (selectedSubject.value.topics || [])
        .filter((topic) => String(topic.school_class_id) === String(form.school_class_id))
        .map((topic) => ({
            id: topic.id,
            name: topic.name,
        }));
});

watch(
    () => selectedLevel.value,
    () => {
        form.school_class_id = '';
        form.subject_id = '';
        form.topic_id = '';
    },
);

watch(
    () => form.school_class_id,
    () => {
        form.topic_id = '';
    },
);

watch(
    () => form.subject_id,
    () => {
        form.topic_id = '';
    },
);

const isGenerating = ref(false);
const generationLogs = ref<{ type: 'info' | 'success' | 'error'; message: string }[]>([]);

const addLog = (type: 'info' | 'success' | 'error', message: string) => {
    generationLogs.value.unshift({ type, message });
};

const configurationReady = computed(() => {
    return Boolean(selectedLevel.value && form.school_class_id && form.subject_id && form.topic_id && form.type && form.difficulty);
});

const startGeneration = () => {
    isGenerating.value = true;
    generationLogs.value = [];

    addLog('info', `Config locked for ${selectedClass.value?.name || 'selected class'} ${selectedSubject.value?.name || 'subject'}.`);
    addLog('info', `Topic focus: ${selectedTopic.value?.name || 'Pending topic'} (${String(selectedLevel.value).toUpperCase()}).`);
    addLog('info', `Requesting ${form.count} ${selectedDifficulty.value?.label || form.difficulty} ${selectedType.value?.label || form.type} items.`);

    form.post(processGeneration().url, {
        onSuccess: () => {
            isGenerating.value = false;
            addLog('success', 'AI generation has been queued successfully. The question bank will update after processing.');
        },
        onError: () => {
            isGenerating.value = false;
            addLog('error', 'Configuration validation failed. Review the setup section and try again.');
        },
    });
};
</script>

<template>
    <component :is="Layout">
        <Head title="AI Question Lab" />

        <div class="space-y-6 pb-24">
            <div class="rounded-2xl border border-primary/15 bg-gradient-to-r from-primary/10 via-primary/5 to-white p-6 shadow-sm md:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-primary/15 bg-white/80 px-3 py-1 text-[11px] font-black tracking-[0.2em] text-primary uppercase">
                            AI Assisted Authoring
                        </div>
                        <h1 class="text-2xl font-semibold text-gray-900">AI Question Lab</h1>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600">
                            Build a clean academic setup first, then let the seeder generate questions that match the chosen level, class, subject,
                            topic, type, and difficulty.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-white/80 bg-white/90 px-4 py-3 shadow-sm">
                            <p class="text-[10px] font-black tracking-[0.18em] text-gray-400 uppercase">Level Scope</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">
                                {{ canCreateCrossLevel ? 'Multi-Level' : selectedLevel ? compactLevelTag(selectedLevel) : 'Locked by School' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-white/80 bg-white/90 px-4 py-3 shadow-sm">
                            <p class="text-[10px] font-black tracking-[0.18em] text-gray-400 uppercase">Question Type</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ selectedType?.label || 'Choose Type' }}</p>
                        </div>
                        <div class="rounded-xl border border-white/80 bg-white/90 px-4 py-3 shadow-sm">
                            <p class="text-[10px] font-black tracking-[0.18em] text-gray-400 uppercase">Batch Size</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ form.count }} questions</p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="Object.keys(form.errors).length > 0"
                class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm"
            >
                Please review the configuration fields highlighted below. The selected level, class, subject, and topic must all belong together.
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
                <div class="space-y-6 xl:col-span-7">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="mb-6 flex items-center gap-3">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">1</span>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Configuration</h2>
                                <p class="text-sm text-gray-500">Choose the academic context before you start the AI seeder.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <CustomSelect
                                v-model="selectedLevel"
                                label="Academic Level"
                                :options="levelOptions"
                                placeholder="Choose Level"
                                :disabled="!canCreateCrossLevel && levelOptions.length === 1"
                                size="md"
                            />
                            <CustomSelect
                                v-model="form.school_class_id"
                                label="Class"
                                :options="availableClasses"
                                placeholder="Choose Class"
                                :disabled="!selectedLevel"
                                :error="form.errors.school_class_id"
                                size="md"
                            />
                            <CustomSelect
                                v-model="form.subject_id"
                                label="Subject"
                                :options="availableSubjects"
                                placeholder="Choose Subject"
                                :disabled="!selectedLevel"
                                :error="form.errors.subject_id"
                                size="md"
                            />
                            <CustomSelect
                                v-model="form.topic_id"
                                label="Topic"
                                :options="filteredTopics"
                                placeholder="Choose Topic"
                                :disabled="!form.subject_id || !form.school_class_id"
                                :error="form.errors.topic_id"
                                size="md"
                            />
                        </div>

                        <div
                            v-if="!canCreateCrossLevel"
                            class="mt-5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800"
                        >
                            Your AI generation scope is restricted to your assigned school level for cleaner curriculum alignment.
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="mb-6 flex items-center gap-3">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">2</span>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Generation Rules</h2>
                                <p class="text-sm text-gray-500">Set the kind of questions the AI is allowed to produce.</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="mb-3 block text-xs font-bold tracking-widest text-gray-500 uppercase">Question Type</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button
                                        v-for="type in types"
                                        :key="type.value"
                                        type="button"
                                        @click="form.type = type.value"
                                        class="rounded-xl border px-4 py-3 text-sm font-semibold transition-colors"
                                        :class="
                                            form.type === type.value
                                                ? 'border-primary bg-primary text-white'
                                                : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                        "
                                    >
                                        {{ type.label }}
                                    </button>
                                </div>
                                <p v-if="form.errors.type" class="mt-2 text-xs text-red-600">{{ form.errors.type }}</p>
                            </div>

                            <div>
                                <label class="mb-3 block text-xs font-bold tracking-widest text-gray-500 uppercase">Difficulty</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <button
                                        v-for="difficulty in difficulties"
                                        :key="difficulty.value"
                                        type="button"
                                        @click="form.difficulty = difficulty.value"
                                        class="rounded-xl border px-4 py-3 text-sm font-semibold transition-colors"
                                        :class="
                                            form.difficulty === difficulty.value
                                                ? 'border-primary bg-primary text-white'
                                                : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                        "
                                    >
                                        {{ difficulty.label }}
                                    </button>
                                </div>
                                <p v-if="form.errors.difficulty" class="mt-2 text-xs text-red-600">{{ form.errors.difficulty }}</p>
                            </div>

                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <label class="text-xs font-bold tracking-widest text-gray-500 uppercase">Question Volume</label>
                                    <span class="text-sm font-semibold text-primary">{{ form.count }} items</span>
                                </div>
                                <input
                                    v-model.number="form.count"
                                    type="range"
                                    min="1"
                                    max="20"
                                    class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 accent-primary"
                                />
                                <p class="mt-3 text-xs text-gray-500">Keep smaller batches for tighter quality control, especially on narrow topics.</p>
                                <p v-if="form.errors.count" class="mt-2 text-xs text-red-600">{{ form.errors.count }}</p>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="startGeneration"
                        :disabled="isGenerating || form.processing || !configurationReady"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50"
                    >
                        <span
                            v-if="isGenerating || form.processing"
                            class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent"
                        ></span>
                        <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        {{ isGenerating || form.processing ? 'Starting AI Seeding...' : 'Seed Question Bank' }}
                    </button>
                </div>

                <div class="space-y-6 xl:col-span-5">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-3">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">3</span>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Request Summary</h2>
                                <p class="text-sm text-gray-500">A quick sanity check before the background job starts.</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                <p class="text-[10px] font-black tracking-[0.18em] text-gray-400 uppercase">Level</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">{{ selectedLevel ? compactLevelTag(selectedLevel) : 'Not selected' }}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                <p class="text-[10px] font-black tracking-[0.18em] text-gray-400 uppercase">Class and Subject</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">
                                    {{ selectedClass?.name || 'Choose class' }}<span class="text-gray-400"> • </span>{{ selectedSubject?.name || 'Choose subject' }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                <p class="text-[10px] font-black tracking-[0.18em] text-gray-400 uppercase">Topic</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">{{ selectedTopic?.name || 'Choose topic' }}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                <p class="text-[10px] font-black tracking-[0.18em] text-gray-400 uppercase">Rules</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">
                                    {{ selectedType?.label || 'Choose type' }} • {{ selectedDifficulty?.label || 'Choose difficulty' }} •
                                    {{ form.count }} items
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex min-h-[24rem] flex-col rounded-xl border border-gray-200 bg-gray-50 p-6 shadow-inner">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-xs font-bold tracking-widest text-gray-400 uppercase">Generation Activity</h3>
                            <span v-if="isGenerating" class="text-[10px] font-black tracking-[0.18em] text-primary uppercase">Running</span>
                        </div>

                        <div class="custom-scrollbar flex-1 space-y-3 overflow-y-auto">
                            <div v-if="generationLogs.length === 0" class="flex h-full flex-col items-center justify-center text-center text-gray-400">
                                <div class="mb-4 flex size-14 items-center justify-center rounded-2xl border border-gray-200 bg-white shadow-sm">
                                    <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <p class="text-xs font-bold tracking-widest uppercase">Awaiting Configuration</p>
                            </div>

                            <div
                                v-for="(log, index) in generationLogs"
                                :key="index"
                                class="rounded-xl border bg-white px-4 py-3 shadow-sm"
                                :class="[log.type === 'error' ? 'border-red-100' : log.type === 'success' ? 'border-emerald-100' : 'border-gray-100']"
                            >
                                <p class="text-[11px] font-semibold leading-5 text-gray-700">{{ log.message }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.08);
    border-radius: 9999px;
}
</style>
