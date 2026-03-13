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
    batches: { id: string, name: string }[];
    types: { value: string; label: string }[];
    difficulties: { value: string; label: string }[];
}>();

const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user.permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const form = useForm({
    subject_id: '',
    topic_id: '',
    school_class_id: '',
    count: 5,
    difficulty: 'medium',
});

const selectedSubject = computed(() => {
    return props.subjects.find((s) => s.id === form.subject_id);
});

const availableClasses = computed(() => {
    if (!selectedSubject.value) return [];

    // Get unique class IDs from the subject's topics
    const classIds = new Set(selectedSubject.value.topics.filter((t: Topic) => t.school_class_id).map((t: Topic) => t.school_class_id));

    return props.classes.filter((c) => classIds.has(c.id));
});

const filteredTopics = computed(() => {
    if (!selectedSubject.value || !form.school_class_id) return [];
    return selectedSubject.value.topics.filter((topic: Topic) => topic.school_class_id === form.school_class_id);
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

    addLog('info', `Initializing AI Question Seeder for ${selectedSubject.value?.name}...`);
    addLog('info', 'Analyzing curriculum requirements and class level...');
    addLog('info', `Requesting ${form.count} ${form.difficulty} questions from the agent...`);

    form.post(processGeneration().url, {
        onSuccess: () => {
            isGenerating.value = false;
            addLog('success', 'AI Agent successfully initialized in the background.');
        },
        onError: () => {
            isGenerating.value = false;
            addLog('error', 'Validation failed.');
            Object.values(form.errors).forEach((err) => addLog('error', err || 'Unknown error'));
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
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-xl bg-primary p-6 md:p-10 text-white shadow-sm">
                <div class="relative z-10">
                    <div class="mb-4 flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 text-white">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-semibold">AI Question Lab</h1>
                    </div>
                    <p class="max-w-2xl text-sm md:text-base text-white/80 leading-relaxed font-medium">
                        Generate high-quality, curriculum-aligned questions using artificial intelligence. Specify your parameters and let the agent seed the bank.
                    </p>
                </div>

                <!-- Decorative Elements -->
                <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Configuration Panel -->
                <div class="lg:col-span-5">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 md:p-8">
                        <h3 class="mb-6 flex items-center gap-3 text-lg font-semibold text-gray-800">
                            <div class="h-2 w-2 rounded-full bg-primary"></div>
                            Lab Configuration
                        </h3>

                        <form @submit.prevent="startGeneration" class="space-y-5">
                            <!-- Subject & Class -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Subject</label>
                                    <select
                                        v-model="form.subject_id"
                                        required
                                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none bg-gray-50"
                                    >
                                        <option value="" disabled>Select Subject</option>
                                        <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                            {{ subject.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.subject_id" class="mt-1 text-xs text-red-600">{{ form.errors.subject_id }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Class Level</label>
                                    <select
                                        v-model="form.school_class_id"
                                        required
                                        :disabled="!selectedSubject"
                                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none bg-gray-50"
                                    >
                                        <option value="" disabled>Select Class</option>
                                        <option v-for="cls in availableClasses" :key="cls.id" :value="cls.id">
                                            {{ cls.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.school_class_id" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.school_class_id }}
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Topic</label>
                                    <select
                                        v-model="form.topic_id"
                                        required
                                        :disabled="!selectedSubject || !form.school_class_id"
                                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none bg-gray-50"
                                    >
                                        <option value="" disabled>Select Topic</option>
                                        <option v-for="topic in filteredTopics" :key="topic.id" :value="topic.id">
                                            {{ topic.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.topic_id" class="mt-1 text-xs text-red-600">{{ form.errors.topic_id }}</div>
                                </div>
                            </div>

                            <!-- Parameters -->
                            <div class="space-y-4 pt-4 border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <label class="text-sm font-medium text-gray-700">Generation Count</label>
                                    <span class="inline-flex items-center py-1 px-2 rounded-lg text-xs font-semibold bg-primary/10 text-primary">{{ form.count }} Questions</span>
                                </div>
                                <input
                                    v-model="form.count"
                                    type="range"
                                    min="1"
                                    max="20"
                                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary"
                                />
                                <div v-if="form.errors.count" class="mt-1 text-xs text-red-600">{{ form.errors.count }}</div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Difficulty</label>
                                    <div class="flex p-1 bg-gray-100 rounded-xl">
                                        <button
                                            v-for="diff in difficulties"
                                            :key="diff.value"
                                            type="button"
                                            @click="form.difficulty = diff.value"
                                            :class="[
                                                'flex-1 py-2 text-xs font-semibold rounded-lg transition-all',
                                                form.difficulty === diff.value
                                                    ? 'bg-white text-primary shadow-sm'
                                                    : 'text-gray-500 hover:text-gray-700',
                                            ]"
                                        >
                                            {{ diff.label }}
                                        </button>
                                    </div>
                                    <div v-if="form.errors.difficulty" class="mt-1 text-xs text-red-600">{{ form.errors.difficulty }}</div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="isGenerating || form.processing"
                                class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none"
                            >
                                <span v-if="isGenerating" class="animate-spin inline-block size-4 border-[3px] border-current border-t-transparent text-white rounded-full"></span>
                                <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                {{ isGenerating ? 'Seeding...' : 'Initialize AI Seeding' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Workspace / Results -->
                <div class="space-y-6 lg:col-span-7">
                    <!-- Generation Logs -->
                    <div class="flex flex-col h-full min-h-[500px] bg-gray-900 border border-gray-800 rounded-xl shadow-sm p-6 overflow-hidden">
                        <div class="mb-6 flex items-center justify-between">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Live Workspace Log</h3>
                            <div v-if="isGenerating" class="flex items-center gap-2">
                                <span class="size-1.5 rounded-full bg-primary animate-pulse"></span>
                                <span class="text-[10px] font-semibold text-primary uppercase tracking-wider">Agent Active</span>
                            </div>
                        </div>

                        <div class="flex-1 space-y-3 overflow-y-auto pr-2 custom-scrollbar">
                            <div v-if="generationLogs.length === 0" class="h-full flex flex-col items-center justify-center text-center p-8">
                                <div class="size-16 flex items-center justify-center rounded-xl bg-gray-800 text-gray-600 mb-4">
                                    <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-500 max-w-xs">
                                    Configure the lab and initialize the agent to see live generation logs.
                                </p>
                            </div>

                            <div
                                v-for="(log, idx) in generationLogs"
                                :key="idx"
                                class="p-3 rounded-lg border text-xs"
                                :class="[
                                    log.type === 'info'
                                        ? 'border-gray-800 bg-gray-800/50 text-gray-300'
                                        : log.type === 'success'
                                          ? 'border-teal-900/50 bg-teal-900/20 text-teal-400'
                                          : 'border-red-900/50 bg-red-900/20 text-red-400',
                                ]"
                            >
                                <div class="flex gap-2">
                                    <span class="text-[10px] font-mono opacity-50">{{ new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}) }}</span>
                                    <p class="font-medium leading-relaxed">{{ log.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tip Card -->
                    <div class="p-5 bg-blue-50 border border-blue-100 rounded-xl">
                        <div class="flex gap-4">
                            <div class="size-8 flex-shrink-0 flex items-center justify-center rounded-lg bg-blue-600 text-white">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-blue-900 mb-1">Pro Tip</h4>
                                <p class="text-xs text-blue-700 leading-relaxed font-medium">
                                    The AI agent uses West African curriculum standards. For subjects like Physics or Chemistry, ensure you select the appropriate class level (SS1-SS3) for accurate complexity.
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
    background: rgba(255, 255, 255, 0.05);
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
</style>
