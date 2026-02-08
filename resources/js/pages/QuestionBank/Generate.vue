<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { processGeneration } from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { AppPageProps } from '@/types';
import type { Subject, SchoolClass, Topic } from '@/types/academics';

const props = defineProps<{
    subjects: (Subject & { topics: Topic[] })[];
    classes: SchoolClass[];
    types: { value: string; label: string }[];
    difficulties: { value: string; label: string }[];
}>();

const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user.roles.includes('admin'));
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
        onFinish: () => {
            // isGenerating handled in onSuccess/onError for better UX
        },
        onError: (errors) => {
            isGenerating.value = false;
            addLog('error', 'Failed to initialize AI Agent.');
            Object.values(errors).forEach((err) => addLog('error', err as string));
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

        <div class="space-y-10">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-[2.5rem] bg-primary px-10 py-12 shadow-2xl shadow-primary/20">
                <div class="relative z-10">
                    <div class="mb-4 flex items-center gap-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-lemon-yellow backdrop-blur-xl"
                        >
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-black text-white">AI Question Lab</h1>
                    </div>
                    <p class="max-w-2xl text-lg font-medium text-white/70">
                        Generate high-quality, curriculum-aligned questions using artificial intelligence. Specify your parameters and let the agent
                        seed the bank.
                    </p>
                </div>

                <!-- Decorative Elements -->
                <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute right-20 -bottom-20 h-64 w-64 rounded-full bg-lemon-yellow/20 blur-3xl"></div>
            </div>

            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                <!-- Configuration Panel -->
                <div class="space-y-8 lg:col-span-5">
                    <div class="rounded-[2.5rem] border border-slate-100 bg-white p-10 shadow-sm">
                        <h3 class="mb-8 flex items-center gap-3 text-xl font-black text-slate-800">
                            <div class="h-2 w-2 rounded-full bg-primary"></div>
                            Lab Configuration
                        </h3>

                        <form @submit.prevent="startGeneration" class="space-y-8">
                            <!-- Subject & Class -->
                            <div class="space-y-6">
                                <div>
                                    <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                        >Target Subject</label
                                    >
                                    <select
                                        v-model="form.subject_id"
                                        required
                                        class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                    >
                                        <option value="" disabled>Select Subject</option>
                                        <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                            {{ subject.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                        >Target Class Level</label
                                    >
                                    <select
                                        v-model="form.school_class_id"
                                        required
                                        :disabled="!selectedSubject"
                                        class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary disabled:opacity-50"
                                    >
                                        <option value="" disabled>Select Class</option>
                                        <option v-for="cls in availableClasses" :key="cls.id" :value="cls.id">
                                            {{ cls.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                        >Target Topic</label
                                    >
                                    <select
                                        v-model="form.topic_id"
                                        required
                                        :disabled="!selectedSubject || !form.school_class_id"
                                        class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary disabled:opacity-50"
                                    >
                                        <option value="" disabled>Select Topic</option>
                                        <option v-for="topic in filteredTopics" :key="topic.id" :value="topic.id">
                                            {{ topic.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Parameters -->
                            <div class="space-y-6 border-t border-slate-50 pt-6">
                                <div class="flex items-center justify-between">
                                    <label class="ml-1 text-[10px] font-black tracking-widest text-slate-400 uppercase">Generation Count</label>
                                    <span class="rounded-lg bg-primary/5 px-3 py-1 text-xs font-black text-primary">{{ form.count }} Questions</span>
                                </div>
                                <input
                                    v-model="form.count"
                                    type="range"
                                    min="1"
                                    max="10"
                                    class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-slate-100 accent-primary"
                                />

                                <div>
                                    <label class="mb-4 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                        >Target Difficulty</label
                                    >
                                    <div class="flex gap-2 rounded-2xl border border-slate-100 bg-slate-50 p-1">
                                        <button
                                            v-for="diff in difficulties"
                                            :key="diff.value"
                                            type="button"
                                            @click="form.difficulty = diff.value"
                                            :class="[
                                                'flex-1 rounded-xl py-3 text-[10px] font-black tracking-wider uppercase transition-all',
                                                form.difficulty === diff.value
                                                    ? 'bg-white text-primary shadow-sm ring-1 ring-slate-200'
                                                    : 'text-slate-400 hover:text-slate-600',
                                            ]"
                                        >
                                            {{ diff.label }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="isGenerating"
                                class="flex w-full items-center justify-center gap-3 rounded-2xl bg-primary py-5 text-sm font-black tracking-[0.2em] text-white uppercase shadow-xl shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50"
                            >
                                <span v-if="isGenerating" class="h-5 w-5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                {{ isGenerating ? 'Seeding in Progress...' : 'Initialize AI Seeding' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Workspace / Results -->
                <div class="space-y-8 lg:col-span-7">
                    <!-- Generation Logs -->
                    <div class="flex min-h-100 flex-col rounded-[2.5rem] border border-white/5 bg-slate-900 p-8 shadow-2xl shadow-slate-900/30">
                        <div class="mb-6 flex items-center justify-between">
                            <h3 class="text-xs font-black tracking-[0.3em] text-slate-500 uppercase">Live Workspace Log</h3>
                            <div v-if="isGenerating" class="flex items-center gap-2">
                                <div class="h-1.5 w-1.5 animate-pulse rounded-full bg-primary"></div>
                                <span class="text-[10px] font-black tracking-widest text-primary uppercase">Agent Active</span>
                            </div>
                        </div>

                        <div class="custom-scrollbar flex-1 space-y-4 overflow-y-auto pr-2">
                            <div v-if="generationLogs.length === 0" class="flex h-full flex-col items-center justify-center p-10 text-center">
                                <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-3xl bg-white/5 text-slate-700">
                                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-slate-600">
                                    Configure the lab and initialize the agent to see live generation logs.
                                </p>
                            </div>

                            <div
                                v-for="(log, idx) in generationLogs"
                                :key="idx"
                                class="animate-in slide-in-from-right-4 rounded-2xl border p-4 transition-all duration-500"
                                :class="[
                                    log.type === 'info'
                                        ? 'border-white/5 bg-white/5 text-slate-300'
                                        : log.type === 'success'
                                          ? 'border-green-500/20 bg-green-500/10 text-green-400'
                                          : 'border-red-500/20 bg-red-500/10 text-red-400',
                                ]"
                            >
                                <div class="flex gap-3">
                                    <span class="text-[10px] font-black uppercase opacity-40">[{{ new Date().toLocaleTimeString() }}]</span>
                                    <p class="text-xs leading-relaxed font-bold">{{ log.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tip Card -->
                    <div class="rounded-3xl border border-primary/10 bg-primary/5 p-8">
                        <div class="flex gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h4 class="mb-1 text-sm font-black tracking-wider text-primary uppercase">Pro Tip</h4>
                                <p class="text-xs leading-relaxed font-bold text-primary/60">
                                    The AI agent uses West African curriculum standards. For subjects like Physics or Chemistry, ensure you select the
                                    appropriate class level (SS1-SS3) for accurate complexity.
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
