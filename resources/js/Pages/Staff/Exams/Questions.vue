<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { updateQuestions, aiSelectQuestions } from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Question {
    id: string;
    content: string;
    difficulty: string;
    type: string;
    topic: {
        id: string;
        name: string;
        subject: { id: string; name: string; level: string };
    };
    options: any[];
}

interface Exam {
    id: string;
    title: string;
    subject?: { id: string; name: string; level: string } | null;
    school_class?: { name: string; level: string };
    type: string;
    compositions: Array<{
        subject_id: string;
        topic_id: string | null;
        question_count: number;
        subject: { name: string };
        topic?: { name: string };
    }>;
}

const props = defineProps<{
    exam: Exam;
    availableQuestions: Question[];
    selectedQuestionIds: string[];
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const searchQuery = ref('');
const subjectFilter = ref('');
const selectedIds = ref<string[]>([...props.selectedQuestionIds]);

// Extract unique subjects from available questions
const availableSubjects = computed(() => {
    const subjects = new Map();
    props.availableQuestions.forEach((q) => {
        const s = q.topic.subject;
        subjects.set(s.id, s.name);
    });
    return Array.from(subjects.entries()).map(([id, name]) => ({ id, name }));
});

// Blueprint Tracker Logic
const blueprintStatus = computed(() => {
    const counts: Record<string, number> = {};

    // Count selected questions by subject or subject+topic
    selectedIds.value.forEach((id) => {
        const q = props.availableQuestions.find((available) => available.id === id);
        if (q) {
            const sId = q.topic.subject.id;
            counts[sId] = (counts[sId] || 0) + 1;

            // Also track by topic if needed for specific blueprint rules
            const tId = q.topic.id;
            counts[`topic_${tId}`] = (counts[`topic_${tId}`] || 0) + 1;
        }
    });

    if (props.exam.compositions && props.exam.compositions.length > 0) {
        return props.exam.compositions.map((comp) => {
            const current = comp.topic_id ? counts[`topic_${comp.topic_id}`] || 0 : counts[comp.subject_id] || 0;
            return {
                name: comp.subject.name + (comp.topic ? ` (${comp.topic.name})` : ''),
                required: comp.question_count,
                current: current,
                isMet: current >= comp.question_count,
            };
        });
    }

    // Fallback for single subject exams
    return [];
});

const isBlueprintFullyMet = computed(() => {
    if (!blueprintStatus.value.length) return true;
    return blueprintStatus.value.every((s) => s.isMet);
});

// Keep local state in sync with server state (needed after AI shuffle)
watch(
    () => props.selectedQuestionIds,
    (newIds) => {
        selectedIds.value = [...newIds];
    },
    { deep: true },
);

const filteredQuestions = computed(() => {
    return props.availableQuestions.filter((q) => {
        const matchesSearch =
            q.content.toLowerCase().includes(searchQuery.value.toLowerCase()) || q.topic.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesSubject = !subjectFilter.value || q.topic.subject.id === subjectFilter.value;

        return matchesSearch && matchesSubject;
    });
});

const toggleQuestion = (id: string) => {
    const index = selectedIds.value.indexOf(id);
    if (index === -1) {
        selectedIds.value.push(id);
    } else {
        selectedIds.value.splice(index, 1);
    }
};

const form = useForm({
    question_ids: [] as string[],
});

const saveSelection = () => {
    form.question_ids = selectedIds.value;
    form.post(updateQuestions(props.exam.id).url);
};

// AI Selection
const isAiModalOpen = ref(false);
const aiForm = useForm({
    count: 10,
});

const runAiSelection = () => {
    aiForm.post(aiSelectQuestions(props.exam.id).url, {
        onSuccess: () => {
            isAiModalOpen.value = false;
        },
    });
};

const getLevelBadgeClasses = (level?: string) => {
    switch (level) {
        case 'nursery':
            return 'bg-pink-100 text-pink-800';
        case 'secondary':
            return 'bg-indigo-100 text-indigo-800';
        default:
            return 'bg-orange-100 text-orange-800';
    }
};
</script>

<template>
    <component :is="Layout">
        <Head title="Allocate Assessment Questions" />

        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <nav class="mb-3 flex items-center gap-2 text-xs font-medium text-gray-500">
                        <Link href="/staff/exams" class="transition-colors hover:text-primary">Exams</Link>
                        <span class="text-gray-300">/</span>
                        <Link :href="`/staff/exams/${exam.id}`" class="transition-colors hover:text-primary">{{ exam.title }}</Link>
                        <span class="text-gray-300">/</span>
                        <span class="text-gray-800">Allocation</span>
                    </nav>
                    <h1 class="text-2xl font-semibold text-gray-800">Question Allocation</h1>
                    <div class="mt-1 flex items-center gap-3">
                        <p class="text-sm text-gray-500">
                            Context:
                            <span class="font-bold tracking-tight text-gray-700 uppercase">{{
                                exam.subject?.name || 'Multi-Subject Blueprint'
                            }}</span>
                            <span
                                class="ml-2 rounded border border-current px-2 py-0.5 text-[10px] font-black tracking-widest uppercase opacity-80"
                                :class="getLevelBadgeClasses(exam.subject?.level || exam.school_class?.level)"
                            >
                                {{ exam.subject?.level || exam.school_class?.level || 'Institutional' }}
                            </span>
                        </p>
                        <div class="h-3 w-px bg-gray-200"></div>
                        <span class="text-xs font-medium text-gray-500"> {{ availableQuestions.length }} Total in Pool </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="isAiModalOpen = true"
                        class="inline-flex items-center gap-x-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black tracking-widest text-slate-800 uppercase shadow-sm transition-all hover:bg-slate-50 active:scale-95"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        AI Selector
                    </button>
                    <button
                        @click="saveSelection"
                        :disabled="form.processing"
                        class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-xl border border-transparent bg-primary px-6 py-2.5 text-xs font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all active:scale-95 disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Commit Pool ({{ selectedIds.length }})
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Search & List -->
                <div class="space-y-4 lg:col-span-8">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="relative flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search assessment bank by content or topic..."
                                class="block w-full rounded-xl border-gray-200 px-4 py-3 pl-11 text-sm font-semibold tracking-tight text-gray-800 uppercase shadow-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div v-if="!exam.subject || availableSubjects.length > 1" class="w-full sm:w-64">
                            <select
                                v-model="subjectFilter"
                                class="block w-full rounded-xl border-gray-200 bg-white px-4 py-3 pr-9 text-sm font-black tracking-tight text-slate-800 uppercase shadow-sm focus:border-primary focus:ring-primary"
                            >
                                <option value="">Subject: All Areas</option>
                                <option v-for="s in availableSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Blueprint Status Bar -->
                    <div v-if="blueprintStatus.length" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Blueprint Integrity Status</h3>
                            <span v-if="isBlueprintFullyMet" class="flex items-center gap-1 text-[10px] font-black text-teal-600 uppercase">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Protocol Requirements Met
                            </span>
                            <span v-else class="flex animate-pulse items-center gap-1 text-[10px] font-black text-orange-600 uppercase">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                                Allocation Incomplete
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <div
                                v-for="status in blueprintStatus"
                                :key="status.name"
                                class="inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 text-[10px] font-black uppercase transition-all"
                                :class="status.isMet ? 'border-teal-200 bg-teal-50 text-teal-700' : 'border-slate-200 bg-white text-slate-400'"
                            >
                                <svg
                                    v-if="status.isMet"
                                    class="size-3 text-teal-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="3"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span v-else class="size-1.5 animate-ping rounded-full bg-orange-400"></span>
                                {{ status.name }}: {{ status.current }} / {{ status.required }}
                            </div>
                        </div>
                    </div>

                    <!-- Question Grid -->
                    <div class="space-y-4">
                        <div
                            v-for="question in filteredQuestions"
                            :key="question.id"
                            @click="toggleQuestion(question.id)"
                            class="group cursor-pointer rounded-2xl border-2 bg-white p-6 shadow-sm transition-all hover:bg-slate-50/50"
                            :class="selectedIds.includes(question.id) ? 'border-primary bg-primary/5' : 'border-slate-50'"
                        >
                            <div class="flex items-start gap-5">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border-2 transition-all"
                                    :class="
                                        selectedIds.includes(question.id)
                                            ? 'border-primary bg-primary text-white shadow-lg shadow-primary/20'
                                            : 'border-slate-100 bg-white text-slate-300 group-hover:border-primary group-hover:text-primary'
                                    "
                                >
                                    <svg v-if="selectedIds.includes(question.id)" class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span v-else class="text-xl font-bold">+</span>
                                </div>
                                <div class="flex-1 space-y-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span
                                            class="inline-flex items-center rounded border border-current px-2 py-0.5 text-[10px] font-black tracking-widest uppercase"
                                            :class="getLevelBadgeClasses(question.topic.subject.level)"
                                        >
                                            {{ question.topic.subject.name }}
                                        </span>
                                        <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                            Topic: {{ question.topic.name }}
                                        </span>
                                        <div class="flex-1"></div>
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded px-2 py-0.5 text-[10px] font-black tracking-widest uppercase',
                                                question.difficulty === 'easy'
                                                    ? 'bg-teal-100 text-teal-800'
                                                    : question.difficulty === 'medium'
                                                      ? 'bg-blue-100 text-blue-800'
                                                      : 'bg-red-100 text-red-800',
                                            ]"
                                        >
                                            {{ question.difficulty }}
                                        </span>
                                    </div>
                                    <p class="text-base leading-relaxed font-semibold tracking-tight text-slate-800">
                                        {{ question.content }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="filteredQuestions.length === 0"
                            class="rounded-3xl border border-dashed border-slate-200 bg-white py-20 text-center"
                        >
                            <p class="text-sm font-black tracking-widest text-slate-400 uppercase">Pool Empty for criteria</p>
                            <p class="mt-1 text-xs text-slate-300 uppercase">Adjust filters or add questions to the bank</p>
                        </div>
                    </div>
                </div>

                <!-- Selection Sidebar -->
                <div class="lg:col-span-4">
                    <div class="sticky top-6 space-y-6">
                        <div class="flex flex-col rounded-3xl border border-slate-800 bg-slate-900 p-6 text-white shadow-2xl">
                            <div class="mb-6 flex items-center justify-between">
                                <h3 class="text-xs font-black tracking-[0.2em] text-slate-500 uppercase">Allocation Pool</h3>
                                <span class="rounded-full border border-primary/30 bg-primary/20 px-3 py-1 text-[10px] font-black text-primary"
                                    >{{ selectedIds.length }} Items</span
                                >
                            </div>

                            <div class="custom-scrollbar max-h-[500px] space-y-3 overflow-y-auto pr-2">
                                <div
                                    v-for="id in selectedIds"
                                    :key="id"
                                    class="group flex items-center justify-between gap-4 rounded-2xl border border-white/5 bg-white/5 p-4 transition-all hover:bg-white/10"
                                >
                                    <p class="line-clamp-2 text-xs font-bold tracking-tight text-slate-300 uppercase">
                                        {{ availableQuestions.find((q) => q.id === id)?.content }}
                                    </p>
                                    <button
                                        @click="toggleQuestion(id)"
                                        class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-red-500/10 text-red-400 transition-all hover:bg-red-500 hover:text-white"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-if="selectedIds.length === 0" class="py-12 text-center">
                                    <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-2xl bg-white/5 text-slate-600">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                            />
                                        </svg>
                                    </div>
                                    <p class="text-[10px] leading-loose font-black tracking-widest text-slate-500 uppercase">
                                        Awaiting Selections...
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative overflow-hidden rounded-3xl bg-blue-600 p-8 text-white shadow-xl shadow-blue-500/20">
                            <div class="relative z-10">
                                <div
                                    class="mb-4 flex size-12 items-center justify-center rounded-2xl border border-white/30 bg-white/20 backdrop-blur-xl"
                                >
                                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <h4 class="mb-1 text-[10px] font-black tracking-[0.2em] text-white/60 uppercase">CBT Protocol</h4>
                                <p class="text-xs leading-relaxed font-bold tracking-tight uppercase">
                                    The pool only contains questions matching the
                                    <strong class="text-white">{{ exam.subject?.level || exam.school_class?.level || 'Institutional' }}</strong> tier.
                                </p>
                            </div>
                            <div
                                class="absolute -right-8 -bottom-8 size-24 rounded-full bg-white/10 blur-2xl transition-transform group-hover:scale-150"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Selection Modal -->
        <div v-if="isAiModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div @click="isAiModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-[32px] border border-slate-100 bg-white shadow-2xl">
                <div class="p-8 md:p-10">
                    <div class="mb-10 flex items-center gap-5">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-white shadow-lg shadow-primary/30">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black tracking-tighter text-slate-800 uppercase italic">AI Shuffler</h3>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Balanced Protocol Selection</p>
                        </div>
                    </div>

                    <form @submit.prevent="runAiSelection" class="space-y-8">
                        <div>
                            <label class="mb-3 block px-1 text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase"
                                >Quantity Requirement</label
                            >
                            <input
                                v-model="aiForm.count"
                                type="number"
                                min="1"
                                max="100"
                                required
                                class="block w-full rounded-2xl border-none bg-slate-50 px-6 py-4 text-lg font-black text-slate-800 shadow-inner transition-all focus:ring-4 focus:ring-primary/10"
                            />
                            <div v-if="aiForm.errors.count" class="mt-2 text-[10px] font-bold text-red-500 uppercase">{{ aiForm.errors.count }}</div>
                        </div>

                        <div class="flex gap-x-3 pt-4">
                            <button
                                type="button"
                                @click="isAiModalOpen = false"
                                class="flex-1 px-4 py-4 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:text-slate-800"
                            >
                                Abort
                            </button>
                            <button
                                type="submit"
                                :disabled="aiForm.processing"
                                class="hover:bg-primary-hover inline-flex flex-[2] items-center justify-center gap-x-2 rounded-2xl border border-transparent bg-primary px-4 py-4 text-[10px] font-black tracking-widest text-white uppercase shadow-xl shadow-primary/30 active:scale-95 disabled:opacity-50"
                            >
                                Execute Shuffle
                            </button>
                        </div>
                    </form>
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
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
</style>
