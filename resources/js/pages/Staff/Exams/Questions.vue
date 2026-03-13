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
    subject?: { name: string } | null;
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

        <div class="space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <nav class="mb-3 flex items-center gap-2 text-xs font-medium text-gray-500">
                        <Link href="/staff/exams" class="hover:text-primary transition-colors">Exams</Link>
                        <span class="text-gray-300">/</span>
                        <Link :href="`/staff/exams/${exam.id}`" class="hover:text-primary transition-colors">{{ exam.title }}</Link>
                        <span class="text-gray-300">/</span>
                        <span class="text-gray-800">Allocation</span>
                    </nav>
                    <h1 class="text-2xl font-semibold text-gray-800">Manage Questions</h1>
                    <div class="mt-1 flex items-center gap-3">
                        <p class="text-sm text-gray-500">
                            Allocating questions for
                            <span class="font-medium text-gray-700">{{ exam.subject?.name || 'Multi-Subject Assessment' }}</span>
                            <span class="ml-1 text-gray-400">({{ exam.type === 'entrance' ? exam.prospective_class?.name : exam.school_class?.name }})</span>
                        </p>
                        <div class="h-3 w-px bg-gray-200"></div>
                        <span class="text-xs font-medium text-gray-500">
                            {{ availableQuestions.length }} Total in Pool
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="isAiModalOpen = true"
                        class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        AI Shuffler
                    </button>
                    <button
                        @click="saveSelection"
                        :disabled="form.processing"
                        class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save ({{ selectedIds.length }})
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Search & List -->
                <div class="space-y-4 lg:col-span-8">
                    <div class="flex flex-col sm:flex-row gap-4">
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
                                placeholder="Search by content or topic..."
                                class="py-3 px-4 pl-11 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none bg-white shadow-sm"
                            />
                        </div>
                        
                        <div v-if="!exam.subject" class="w-full sm:w-64">
                            <select 
                                v-model="subjectFilter"
                                class="py-3 px-4 pr-9 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none bg-white shadow-sm"
                            >
                                <option value="">All Subjects</option>
                                <option v-for="s in availableSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="question in filteredQuestions"
                            :key="question.id"
                            @click="toggleQuestion(question.id)"
                            class="group cursor-pointer p-5 bg-white border rounded-xl shadow-sm transition-all hover:bg-gray-50"
                            :class="selectedIds.includes(question.id) ? 'border-primary ring-1 ring-primary' : 'border-gray-200'"
                        >
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border transition-all"
                                    :class="
                                        selectedIds.includes(question.id)
                                            ? 'bg-primary border-primary text-white'
                                            : 'bg-white border-gray-200 text-gray-400 group-hover:bg-gray-50'
                                    "
                                >
                                    <svg v-if="selectedIds.includes(question.id)" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span v-else class="text-xs font-semibold">+</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center py-0.5 px-2 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                            {{ question.topic.subject.name }}
                                        </span>
                                        <span class="inline-flex items-center py-0.5 px-2 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                            {{ question.topic.name }}
                                        </span>
                                        <span
                                            :class="[
                                                'inline-flex items-center py-0.5 px-2 rounded-full text-xs font-medium',
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
                                    <p class="text-sm font-medium text-gray-800 leading-relaxed">{{ question.content }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selection Sidebar -->
                <div class="lg:col-span-4">
                    <div class="sticky top-6 space-y-6">
                        <div class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                            <h3 class="mb-4 text-lg font-semibold text-gray-800">Selected Pool ({{ selectedIds.length }})</h3>
                            <div class="max-h-[500px] space-y-3 overflow-y-auto pr-1">
                                <div
                                    v-for="id in selectedIds"
                                    :key="id"
                                    class="group flex items-center justify-between gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg transition-all hover:bg-gray-100"
                                >
                                    <p class="line-clamp-1 text-xs text-gray-600">
                                        {{ availableQuestions.find((q) => q.id === id)?.content }}
                                    </p>
                                    <button @click="toggleQuestion(id)" class="shrink-0 text-gray-400 hover:text-red-500">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-if="selectedIds.length === 0" class="py-8 text-center text-sm text-gray-400">
                                    No questions selected yet.
                                </div>
                            </div>
                        </div>

                        <div class="p-5 text-center bg-white border border-dashed border-gray-200 rounded-xl">
                            <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-gray-50 text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pro Tip</p>
                            <p class="mt-2 text-xs text-gray-500 leading-relaxed">
                                You can use the AI Shuffler to quickly build a balanced pool, then refine it manually.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Selection Modal -->
        <div v-if="isAiModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div @click="isAiModalOpen = false" class="absolute inset-0 bg-gray-900/50 transition-opacity"></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-xl bg-white shadow-lg border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-white">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">AI Shuffler</h3>
                            <p class="text-sm text-gray-500">Auto-pick compliant questions</p>
                        </div>
                    </div>

                    <form @submit.prevent="runAiSelection" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Questions</label>
                            <input
                                v-model="aiForm.count"
                                type="number"
                                min="1"
                                max="100"
                                required
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none bg-gray-50"
                            />
                            <div v-if="aiForm.errors.count" class="mt-1 text-xs text-red-600">{{ aiForm.errors.count }}</div>
                        </div>
                        <div class="flex gap-x-2 pt-4">
                            <button
                                type="button"
                                @click="isAiModalOpen = false"
                                class="flex-1 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="aiForm.processing"
                                class="flex-1 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none"
                            >
                                Generate
                            </button>
                        </div>
                    </form>
                </div>
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
