<script setup lang="ts">
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { processGeneration } from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { AppPageProps } from '@/types';
import type { Subject, SchoolClass, Topic } from '@/types/academics';

const props = defineProps<{
    subjects: (Subject & { topics: Topic[] })[];
    classes: SchoolClass[];
    batches: { id: string; name: string }[];
    types: { value: string; label: string }[];
    difficulties: { value: string; label: string }[];
}>();

const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user.permissions.includes('sys:manage_settings'));
const canCreateCrossLevel = computed(
    () => page.props.auth.user.permissions.includes('access:cross-level-authoring')
        || page.props.auth.user.permissions.includes('bank:create_cross_level')
        || page.props.auth.user.permissions.includes('exam:create_cross_level')
        || isAdmin.value,
);
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

// Pre-select tier based on user's school level
const userSchool = computed(() => (page.props as any).branches?.[(page.props.auth.user as any).school_id]);
const defaultTier = computed(() => userSchool.value?.type || 'primary');

const selectedTier = ref(defaultTier.value);

const form = useForm({
    subject_id: '',
    topic_id: '',
    school_class_id: '',
    count: 5,
    difficulty: 'medium',
});

// Dynamic filtering based on TIER
const filteredSubjectsForTier = computed(() => {
    if (canCreateCrossLevel.value) return props.subjects;

    return props.subjects.filter((s) => s.level === selectedTier.value);
});

const selectedSubject = computed(() => {
    return props.subjects.find((s) => s.id === form.subject_id);
});

const availableClasses = computed(() => {
    if (canCreateCrossLevel.value) return props.classes;

    return props.classes.filter((c) => c.level === selectedTier.value);
});

const filteredTopics = computed(() => {
    if (!selectedSubject.value || !form.school_class_id) return [];
    return selectedSubject.value.topics.filter((topic: Topic) => {
        return !topic.school_class_id || String(topic.school_class_id) === String(form.school_class_id);
    });
});

watch(selectedTier, () => {
    if (canCreateCrossLevel.value) return;

    form.subject_id = '';
    form.school_class_id = '';
    form.topic_id = '';
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

const isGenerating = ref(false);
const generationLogs = ref<{ type: 'info' | 'success' | 'error'; message: string }[]>([]);

const startGeneration = () => {
    isGenerating.value = true;
    generationLogs.value = [];

    addLog('info', `Initializing AI Agent for ${selectedSubject.value?.name}...`);
    addLog(
        'info',
        canCreateCrossLevel.value
            ? 'Context Scoping: Cross-Level Authoring (Primary + Secondary).'
            : `Context Scoping: ${selectedTier.value.toUpperCase()} Tier.`,
    );
    addLog('info', `Requesting ${form.count} ${form.difficulty} questions.`);

    form.post(processGeneration().url, {
        onSuccess: () => {
            isGenerating.value = false;
            addLog('success', 'AI Generation successfully started in background.');
        },
        onError: () => {
            isGenerating.value = false;
            addLog('error', 'Synapse failure: Check parameters.');
        },
    });
};

const addLog = (type: 'info' | 'success' | 'error', message: string) => {
    generationLogs.value.unshift({ type, message });
};
</script>

<template>
    <component :is="Layout">
        <Head title="AI Question Lab" />

        <div class="space-y-6">
            <!-- Header Section (Standard Preline Style) -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:p-10">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-center">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">AI Question Lab</h1>
                            <p class="mt-1 text-sm text-gray-500">Generate high-quality assessment items using context-aware AI.</p>
                        </div>
                    </div>
                    <div v-if="isGenerating" class="flex items-center gap-3 rounded-lg border border-primary/10 bg-primary/5 px-4 py-2">
                        <span class="relative flex h-3 w-3">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"></span>
                            <span class="relative inline-flex h-3 w-3 rounded-full bg-primary"></span>
                        </span>
                        <span class="text-xs font-bold tracking-widest text-primary uppercase">Agent Active</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Parameters (Preline Form Style) -->
                <div class="lg:col-span-5">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:p-8">
                        <div class="mb-8 flex items-center gap-3 border-b border-gray-100 pb-4">
                            <h3 class="text-sm font-bold tracking-widest text-gray-800 uppercase">Configuration</h3>
                        </div>

                        <form @submit.prevent="startGeneration" class="space-y-6">
                            <!-- Tier Selector -->
                            <div v-if="!canCreateCrossLevel">
                                <label class="mb-3 block text-xs font-bold text-gray-500 uppercase">Academic Tier</label>
                                <div class="flex rounded-lg border border-gray-200 bg-gray-50 p-1">
                                    <button
                                        v-for="tier in ['nursery', 'primary', 'secondary']"
                                        :key="tier"
                                        type="button"
                                        @click="selectedTier = tier"
                                        class="flex-1 rounded-md py-2 text-[10px] font-black uppercase transition-all"
                                        :class="
                                            selectedTier === tier
                                                ? 'border border-gray-200 bg-white text-gray-800 shadow-sm'
                                                : 'text-gray-400 hover:text-gray-600'
                                        "
                                    >
                                        {{ tier }}
                                    </button>
                                </div>
                            </div>
                            <div v-else class="rounded-lg border border-teal-100 bg-teal-50 px-3 py-2">
                                <p class="text-[10px] font-black tracking-wider text-teal-800 uppercase">
                                    Cross-Level Scope Enabled: Primary + Secondary
                                </p>
                            </div>

                            <!-- Context -->
                            <div class="space-y-4">
                                <div>
                                    <label class="mb-2 block text-xs font-bold text-gray-500 uppercase">Target Subject</label>
                                    <select
                                        v-model="form.subject_id"
                                        required
                                        class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                    >
                                        <option value="" disabled>Select Subject</option>
                                        <option v-for="subject in filteredSubjectsForTier" :key="subject.id" :value="subject.id">
                                            {{ subject.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="mb-2 block text-xs font-bold text-gray-500 uppercase">Class Level</label>
                                        <select
                                            v-model="form.school_class_id"
                                            required
                                            :disabled="!selectedTier"
                                            class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                        >
                                            <option value="" disabled>Level</option>
                                            <option v-for="cls in availableClasses" :key="cls.id" :value="cls.id">
                                                {{ cls.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-xs font-bold text-gray-500 uppercase">Topic</label>
                                        <select
                                            v-model="form.topic_id"
                                            required
                                            :disabled="!form.subject_id || !form.school_class_id"
                                            class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                        >
                                            <option value="" disabled>Topic</option>
                                            <option v-for="topic in filteredTopics" :key="topic.id" :value="topic.id">
                                                {{ topic.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Counts -->
                            <div class="border-t border-gray-100 pt-4">
                                <div class="mb-4 flex items-center justify-between">
                                    <label class="text-xs font-bold text-gray-500 uppercase">Question Volume</label>
                                    <span class="text-sm font-bold text-primary">{{ form.count }} Items</span>
                                </div>
                                <input
                                    v-model="form.count"
                                    type="range"
                                    min="1"
                                    max="20"
                                    class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-100 accent-primary"
                                />
                            </div>

                            <!-- Complexity -->
                            <div>
                                <label class="mb-3 block text-xs font-bold text-gray-500 uppercase">Difficulty Level</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <button
                                        v-for="diff in difficulties"
                                        :key="diff.value"
                                        type="button"
                                        @click="form.difficulty = diff.value"
                                        class="rounded-lg border-2 py-2.5 text-[10px] font-black uppercase transition-all"
                                        :class="
                                            form.difficulty === diff.value
                                                ? 'border-primary bg-primary text-white'
                                                : 'border-gray-100 bg-white text-gray-400 hover:border-gray-200'
                                        "
                                    >
                                        {{ diff.label }}
                                    </button>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="isGenerating || form.processing"
                                class="hover:bg-primary-hover inline-flex w-full items-center justify-center gap-x-2 rounded-xl border border-transparent bg-primary px-4 py-3.5 text-sm font-bold text-white shadow-sm transition-all disabled:opacity-50"
                            >
                                <span
                                    v-if="isGenerating"
                                    class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent text-white"
                                ></span>
                                <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                {{ isGenerating ? 'Initializing AI...' : 'Seed Question Bank' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Live Log (Preline Card style) -->
                <div class="space-y-6 lg:col-span-7">
                    <div class="flex min-h-125 flex-col rounded-xl border border-gray-200 bg-gray-50 p-6 shadow-inner">
                        <div class="mb-6 flex items-center justify-between">
                            <h3 class="text-xs font-bold tracking-widest text-gray-400 uppercase">Generation Activity</h3>
                            <div v-if="isGenerating" class="flex items-center gap-2">
                                <div class="size-1.5 animate-pulse rounded-full bg-primary"></div>
                                <span class="text-[10px] font-bold text-primary uppercase">Synchronizing</span>
                            </div>
                        </div>

                        <div class="custom-scrollbar flex-1 space-y-3 overflow-y-auto">
                            <div v-if="generationLogs.length === 0" class="flex h-full flex-col items-center justify-center text-center opacity-50">
                                <div
                                    class="mb-4 flex size-16 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-300 shadow-sm"
                                >
                                    <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <p class="text-xs font-bold tracking-widest text-gray-400 uppercase">Agent Standby</p>
                            </div>

                            <div
                                v-for="(log, idx) in generationLogs"
                                :key="idx"
                                class="rounded-xl border bg-white p-4 shadow-sm transition-all"
                                :class="[log.type === 'info' ? 'border-gray-100' : log.type === 'success' ? 'border-teal-100' : 'border-red-100']"
                            >
                                <div class="flex gap-4">
                                    <span class="font-mono text-[10px] text-gray-400">{{
                                        new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                                    }}</span>
                                    <p class="text-xs font-bold tracking-tight text-gray-600 uppercase">{{ log.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informational Card -->
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-6">
                        <div class="flex gap-4">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h4 class="mb-1 text-xs font-bold text-blue-900 uppercase">Tier-Aware Intelligence</h4>
                                <p class="text-xs leading-relaxed font-medium text-blue-700">
                                    AI generation respects your scope automatically. Staff with cross-level permission can author for both primary and
                                    secondary without tier restrictions.
                                </p>
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
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}
</style>
