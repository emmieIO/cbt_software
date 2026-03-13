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
    return props.subjects.filter(s => s.level === selectedTier.value);
});

const selectedSubject = computed(() => {
    return props.subjects.find((s) => s.id === form.subject_id);
});

const availableClasses = computed(() => {
    return props.classes.filter(c => c.level === selectedTier.value);
});

const filteredTopics = computed(() => {
    if (!selectedSubject.value || !form.school_class_id) return [];
    return selectedSubject.value.topics.filter((topic: Topic) => String(topic.school_class_id) === String(form.school_class_id));
});

watch(selectedTier, () => {
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
    addLog('info', `Context Scoping: ${selectedTier.value.toUpperCase()} Tier.`);
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
            <div class="bg-white border border-gray-200 rounded-xl p-6 md:p-10 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">AI Question Lab</h1>
                            <p class="text-sm text-gray-500 mt-1">Generate high-quality assessment items using context-aware AI.</p>
                        </div>
                    </div>
                    <div v-if="isGenerating" class="flex items-center gap-3 px-4 py-2 bg-primary/5 rounded-lg border border-primary/10">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-primary"></span>
                        </span>
                        <span class="text-xs font-bold text-primary uppercase tracking-widest">Agent Active</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Parameters (Preline Form Style) -->
                <div class="lg:col-span-5">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 md:p-8">
                        <div class="flex items-center gap-3 mb-8 pb-4 border-b border-gray-100">
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Configuration</h3>
                        </div>

                        <form @submit.prevent="startGeneration" class="space-y-6">
                            <!-- Tier Selector -->
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-3">Academic Tier</label>
                                <div class="flex p-1 bg-gray-50 rounded-lg border border-gray-200">
                                    <button
                                        v-for="tier in ['nursery', 'primary', 'secondary']"
                                        :key="tier"
                                        type="button"
                                        @click="selectedTier = tier"
                                        class="flex-1 py-2 text-[10px] font-black uppercase rounded-md transition-all"
                                        :class="selectedTier === tier 
                                            ? 'bg-white text-gray-800 shadow-sm border border-gray-200' 
                                            : 'text-gray-400 hover:text-gray-600'"
                                    >
                                        {{ tier }}
                                    </button>
                                </div>
                            </div>

                            <!-- Context -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Target Subject</label>
                                    <select
                                        v-model="form.subject_id"
                                        required
                                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                    >
                                        <option value="" disabled>Select Subject</option>
                                        <option v-for="subject in filteredSubjectsForTier" :key="subject.id" :value="subject.id">
                                            {{ subject.name }}
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Class Level</label>
                                        <select
                                            v-model="form.school_class_id"
                                            required
                                            :disabled="!selectedTier"
                                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                        >
                                            <option value="" disabled>Level</option>
                                            <option v-for="cls in availableClasses" :key="cls.id" :value="cls.id">
                                                {{ cls.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Topic</label>
                                        <select
                                            v-model="form.topic_id"
                                            required
                                            :disabled="!form.subject_id || !form.school_class_id"
                                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
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
                            <div class="pt-4 border-t border-gray-100">
                                <div class="flex items-center justify-between mb-4">
                                    <label class="text-xs font-bold text-gray-500 uppercase">Question Volume</label>
                                    <span class="text-sm font-bold text-primary">{{ form.count }} Items</span>
                                </div>
                                <input
                                    v-model="form.count"
                                    type="range"
                                    min="1"
                                    max="20"
                                    class="w-full h-2 bg-gray-100 rounded-lg appearance-none cursor-pointer accent-primary"
                                />
                            </div>

                            <!-- Complexity -->
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-3">Difficulty Level</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <button
                                        v-for="diff in difficulties"
                                        :key="diff.value"
                                        type="button"
                                        @click="form.difficulty = diff.value"
                                        class="py-2.5 text-[10px] font-black uppercase rounded-lg border-2 transition-all"
                                        :class="form.difficulty === diff.value
                                            ? 'bg-primary border-primary text-white'
                                            : 'bg-white border-gray-100 text-gray-400 hover:border-gray-200'"
                                    >
                                        {{ diff.label }}
                                    </button>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="isGenerating || form.processing"
                                class="w-full py-3.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-bold rounded-xl border border-transparent bg-primary text-white hover:bg-primary-hover disabled:opacity-50 transition-all shadow-sm"
                            >
                                <span v-if="isGenerating" class="animate-spin inline-block size-4 border-[3px] border-current border-t-transparent text-white rounded-full"></span>
                                <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                {{ isGenerating ? 'Initializing AI...' : 'Seed Question Bank' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Live Log (Preline Card style) -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 min-h-[500px] flex flex-col shadow-inner">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Generation Activity</h3>
                            <div v-if="isGenerating" class="flex items-center gap-2">
                                <div class="size-1.5 rounded-full bg-primary animate-pulse"></div>
                                <span class="text-[10px] font-bold text-primary uppercase">Synchronizing</span>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto space-y-3 custom-scrollbar">
                            <div v-if="generationLogs.length === 0" class="h-full flex flex-col items-center justify-center text-center opacity-50">
                                <div class="size-16 bg-white border border-gray-200 rounded-2xl flex items-center justify-center text-gray-300 mb-4 shadow-sm">
                                    <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Agent Standby</p>
                            </div>

                            <div
                                v-for="(log, idx) in generationLogs"
                                :key="idx"
                                class="p-4 rounded-xl border bg-white shadow-sm transition-all"
                                :class="[
                                    log.type === 'info' ? 'border-gray-100' : (log.type === 'success' ? 'border-teal-100' : 'border-red-100')
                                ]"
                            >
                                <div class="flex gap-4">
                                    <span class="text-[10px] font-mono text-gray-400">{{ new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                                    <p class="text-xs font-bold text-gray-600 uppercase tracking-tight">{{ log.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informational Card -->
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-6">
                        <div class="flex gap-4">
                            <div class="size-10 flex-shrink-0 flex items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-blue-900 uppercase mb-1">Tier-Aware Intelligence</h4>
                                <p class="text-xs text-blue-700 leading-relaxed font-medium">
                                    The AI Laboratory is now synchronized with your institutional levels. Selecting a tier will automatically filter subjects and class levels to maintain academic precision.
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
