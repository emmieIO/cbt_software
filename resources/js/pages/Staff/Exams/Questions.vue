<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { updateQuestions, aiSelectQuestions } from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface Question {
    id: string;
    content: string;
    difficulty: string;
    type: string;
    topic: { 
        name: string;
        subject: { id: string; name: string };
    };
    options: any[];
}

interface Exam {
    id: string;
    title: string;
    subject?: { name: string };
    school_class?: { name: string };
    prospective_class?: { name: string };
    type: string;
}

const props = defineProps<{
    exam: Exam;
    availableQuestions: Question[];
    selectedQuestionIds: string[];
}>();

const searchQuery = ref('');
const subjectFilter = ref('');
const selectedIds = ref<string[]>([...props.selectedQuestionIds]);

// Extract unique subjects from available questions
const availableSubjects = computed(() => {
    const subjects = new Map();
    props.availableQuestions.forEach(q => {
        const s = q.topic.subject;
        subjects.set(s.id, s.name);
    });
    return Array.from(subjects.entries()).map(([id, name]) => ({ id, name }));
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
        const matchesSearch = q.content.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                             q.topic.name.toLowerCase().includes(searchQuery.value.toLowerCase());
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
</script>

<template>
    <AdminLayout>
        <Head title="Select Exam Questions" />

        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="mb-4 flex items-center gap-2 text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        <Link href="/staff/exams" class="hover:text-primary">Exams</Link>
                        <span>/</span>
                        <Link :href="`/staff/exams/${exam.id}`" class="hover:text-primary">{{ exam.title }}</Link>
                        <span>/</span>
                        <span class="text-slate-600">Allocation</span>
                    </nav>
                    <h1 class="text-3xl font-black text-slate-900">Manage Questions</h1>
                    <p class="mt-1 text-sm font-bold text-slate-500">
                        Allocating questions for 
                        <span class="text-primary">{{ exam.subject?.name || 'Multi-Subject Assessment' }}</span> 
                        ({{ exam.type === 'entrance' ? exam.prospective_class?.name : exam.school_class?.name }})
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="isAiModalOpen = true"
                        class="flex h-12 items-center gap-2 rounded-xl border-2 border-primary/20 bg-primary/5 px-6 text-[10px] font-black tracking-widest text-primary uppercase transition-all hover:bg-primary hover:text-white active:scale-95"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        AI Shuffler
                    </button>
                    <button
                        @click="saveSelection"
                        :disabled="form.processing"
                        class="flex h-12 items-center gap-3 rounded-xl bg-slate-900 px-8 text-[10px] font-black tracking-widest text-white uppercase shadow-xl transition-all hover:bg-black active:scale-95 disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Allocation ({{ selectedIds.length }})
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Search & List -->
                <div class="space-y-6 lg:col-span-8">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="relative flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by content or topic..."
                                class="h-16 w-full rounded-xl border-none bg-white px-14 text-sm font-bold shadow-sm transition-all focus:ring-2 focus:ring-primary/20"
                            />
                        </div>
                        
                        <div v-if="!exam.subject" class="w-full sm:w-64">
                            <select 
                                v-model="subjectFilter"
                                class="h-16 w-full rounded-xl border-none bg-white px-6 text-sm font-bold shadow-sm transition-all focus:ring-2 focus:ring-primary/20"
                            >
                                <option value="">All Subjects</option>
                                <option v-for="s in availableSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="question in filteredQuestions"
                            :key="question.id"
                            @click="toggleQuestion(question.id)"
                            class="group relative cursor-pointer overflow-hidden rounded-xl border-2 bg-white p-8 transition-all hover:shadow-xl"
                            :class="selectedIds.includes(question.id) ? 'border-primary bg-primary/5' : 'border-slate-100'"
                        >
                            <div class="flex items-start gap-6">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-all"
                                    :class="
                                        selectedIds.includes(question.id)
                                            ? 'bg-primary text-white'
                                            : 'bg-slate-100 text-slate-300 group-hover:bg-slate-200'
                                    "
                                >
                                    <svg v-if="selectedIds.includes(question.id)" class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span v-else class="text-xs font-black">+</span>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-xl bg-primary/10 px-2 py-0.5 text-[9px] font-black tracking-widest text-primary uppercase">
                                            {{ question.topic.subject.name }}
                                        </span>
                                        <span class="rounded-xl bg-slate-100 px-2 py-0.5 text-[9px] font-black tracking-widest text-slate-500 uppercase">
                                            {{ question.topic.name }}
                                        </span>
                                        <span
                                            :class="[
                                                'rounded-xl px-2 py-0.5 text-[9px] font-black tracking-widest uppercase',
                                                question.difficulty === 'easy'
                                                    ? 'bg-green-100 text-green-700'
                                                    : question.difficulty === 'medium'
                                                      ? 'bg-blue-100 text-blue-700'
                                                      : 'bg-red-100 text-red-700',
                                            ]"
                                        >
                                            {{ question.difficulty }}
                                        </span>
                                    </div>
                                    <p class="text-base leading-relaxed font-black text-slate-800">{{ question.content }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selection Sidebar -->
                <div class="lg:col-span-4">
                    <div class="sticky top-8 space-y-6">
                        <div class="rounded-xl bg-slate-900 p-8 text-white shadow-2xl">
                            <h3 class="mb-6 text-xl font-black">Selected Pool ({{ selectedIds.length }})</h3>
                            <div class="custom-scrollbar max-h-125 space-y-4 overflow-y-auto pr-2">
                                <div
                                    v-for="id in selectedIds"
                                    :key="id"
                                    class="group flex items-center justify-between gap-3 rounded-xl bg-white/5 p-4 transition-all hover:bg-white/10"
                                >
                                    <p class="line-clamp-1 text-xs font-bold text-slate-300">
                                        {{ availableQuestions.find((q) => q.id === id)?.content }}
                                    </p>
                                    <button @click="toggleQuestion(id)" class="shrink-0 text-slate-500 hover:text-red-400">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-if="selectedIds.length === 0" class="py-10 text-center text-xs font-bold text-slate-500">
                                    No questions selected yet.
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border-2 border-dashed border-slate-200 p-8 text-center">
                            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-slate-400">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Pro Tip</p>
                            <p class="mt-2 text-xs leading-relaxed font-bold text-slate-500">
                                You can use the AI Shuffler to quickly build a balanced pool, then refine it manually.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Selection Modal -->
        <div v-if="isAiModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="isAiModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
            <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-xl bg-white p-10 shadow-2xl">
                <div class="mb-8 flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-white shadow-lg shadow-primary/20">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-900">AI Shuffler</h3>
                        <p class="text-xs font-bold text-slate-400">Auto-pick compliant questions</p>
                    </div>
                </div>

                <form @submit.prevent="runAiSelection" class="space-y-6">
                    <div>
                        <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Number of Questions</label>
                        <input
                            v-model="aiForm.count"
                            type="number"
                            min="1"
                            max="100"
                            required
                            class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                        />
                        <div v-if="aiForm.errors.count" class="mt-1 text-xs text-red-600">{{ aiForm.errors.count }}</div>
                    </div>
                    <div class="flex gap-3 border-t border-slate-50 pt-4">
                        <button
                            type="button"
                            @click="isAiModalOpen = false"
                            class="flex-1 rounded-xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="aiForm.processing"
                            class="flex-1 rounded-xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20"
                        >
                            Generate Selection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
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
