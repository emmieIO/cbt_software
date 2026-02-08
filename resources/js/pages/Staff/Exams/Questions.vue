<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { updateQuestions, aiSelectQuestions } from '@/actions/App/Http/Controllers/Staff/ExamController';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Question {
    id: string;
    content: string;
    difficulty: string;
    type: string;
    topic: { name: string };
    options: any[];
}

interface Version {
    id: string;
    name: string;
}

interface Exam {
    id: string;
    title: string;
    subject: { name: string };
    school_class: { name: string };
}

const props = defineProps<{
    exam: Exam;
    version: Version;
    availableQuestions: Question[];
    selectedQuestionIds: string[];
}>();

const searchQuery = ref('');
const selectedIds = ref<string[]>([...props.selectedQuestionIds]);

// Keep local state in sync with server state (needed after AI shuffle)
watch(() => props.selectedQuestionIds, (newIds) => {
    selectedIds.value = [...newIds];
}, { deep: true });

const filteredQuestions = computed(() => {
    return props.availableQuestions.filter(q => 
        q.content.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        q.topic.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
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
    form.post(updateQuestions({ exam: props.exam.id, version: props.version.id }).url);
};

// AI Selection
const isAiModalOpen = ref(false);
const aiForm = useForm({
    count: 10,
});

const runAiSelection = () => {
    aiForm.post(aiSelectQuestions({ exam: props.exam.id, version: props.version.id }).url, {
        onSuccess: () => {
            isAiModalOpen.value = false;
        }
    });
};
</script>

<template>
    <StaffLayout>
        <Head :title="`Select Questions - ${version.name}`" />

        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="mb-4 flex items-center gap-2 text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        <Link href="/staff/exams" class="hover:text-primary">Exams</Link>
                        <span>/</span>
                        <Link :href="`/staff/exams/${exam.id}`" class="hover:text-primary">{{ exam.title }}</Link>
                        <span>/</span>
                        <span class="text-slate-600">{{ version.name }}</span>
                    </nav>
                    <h1 class="text-3xl font-black text-slate-900 italic">Select Questions</h1>
                    <p class="mt-1 text-sm font-bold text-slate-500">Allocating questions for {{ exam.subject.name }} ({{ exam.school_class.name }})</p>
                </div>

                <div class="flex items-center gap-3">
                    <button 
                        @click="isAiModalOpen = true"
                        class="flex h-12 items-center gap-2 rounded-2xl border-2 border-primary/20 bg-primary/5 px-6 text-[10px] font-black tracking-widest text-primary uppercase transition-all hover:bg-primary hover:text-white active:scale-95"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        AI Shuffler
                    </button>
                    <button 
                        @click="saveSelection"
                        :disabled="form.processing"
                        class="flex h-12 items-center gap-3 rounded-2xl bg-slate-900 px-8 text-[10px] font-black tracking-widest text-white uppercase shadow-xl transition-all hover:bg-black active:scale-95 disabled:opacity-50"
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
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5 text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Search by content or topic..."
                            class="h-16 w-full rounded-4xl border-none bg-white px-14 text-sm font-bold shadow-sm transition-all focus:ring-2 focus:ring-primary/20"
                        />
                    </div>

                    <div class="space-y-4">
                        <div 
                            v-for="question in filteredQuestions" 
                            :key="question.id"
                            @click="toggleQuestion(question.id)"
                            class="group relative cursor-pointer overflow-hidden rounded-[2.5rem] border-2 bg-white p-8 transition-all hover:shadow-xl"
                            :class="selectedIds.includes(question.id) ? 'border-primary bg-primary/5' : 'border-slate-100'"
                        >
                            <div class="flex items-start gap-6">
                                <div 
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-all"
                                    :class="selectedIds.includes(question.id) ? 'bg-primary text-white' : 'bg-slate-100 text-slate-300 group-hover:bg-slate-200'"
                                >
                                    <svg v-if="selectedIds.includes(question.id)" class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span v-else class="text-xs font-black italic">+</span>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-[9px] font-black tracking-widest text-slate-500 uppercase">{{ question.topic.name }}</span>
                                        <span :class="['rounded-lg px-2 py-0.5 text-[9px] font-black tracking-widest uppercase', 
                                            question.difficulty === 'easy' ? 'bg-green-100 text-green-700' : 
                                            question.difficulty === 'medium' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700']">
                                            {{ question.difficulty }}
                                        </span>
                                    </div>
                                    <p class="text-base font-black leading-relaxed text-slate-800 italic">{{ question.content }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selection Sidebar -->
                <div class="lg:col-span-4">
                    <div class="sticky top-8 space-y-6">
                        <div class="rounded-[2.5rem] bg-slate-900 p-8 text-white shadow-2xl">
                            <h3 class="mb-6 text-xl font-black italic">Selected Paper ({{ selectedIds.length }})</h3>
                            <div class="space-y-4 max-h-125 overflow-y-auto pr-2 custom-scrollbar">
                                <div 
                                    v-for="id in selectedIds" 
                                    :key="id"
                                    class="group flex items-center justify-between gap-3 rounded-2xl bg-white/5 p-4 transition-all hover:bg-white/10"
                                >
                                    <p class="line-clamp-1 text-xs font-bold text-slate-300">
                                        {{ availableQuestions.find(q => q.id === id)?.content }}
                                    </p>
                                    <button 
                                        @click="toggleQuestion(id)"
                                        class="shrink-0 text-slate-500 hover:text-red-400"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-if="selectedIds.length === 0" class="py-10 text-center text-xs font-bold text-slate-500 italic">
                                    No questions selected yet.
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[2.5rem] border-2 border-dashed border-slate-200 p-8 text-center">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 mx-auto">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Pro Tip</p>
                            <p class="mt-2 text-xs font-bold leading-relaxed text-slate-500 italic">
                                You can use the AI Shuffler to quickly build a balanced paper, then refine it manually.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Selection Modal -->
        <div v-if="isAiModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="isAiModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-[2.5rem] bg-white p-10 shadow-2xl">
                <div class="mb-8 flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary text-white shadow-lg shadow-primary/20">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-900">AI Shuffler</h3>
                        <p class="text-xs font-bold text-slate-400 italic">Auto-pick questions for this version</p>
                    </div>
                </div>

                <form @submit.prevent="runAiSelection" class="space-y-6">
                    <div>
                        <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase ml-1">Number of Questions</label>
                        <input
                            v-model="aiForm.count"
                            type="number"
                            min="1"
                            max="100"
                            required
                            class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                        />
                    </div>
                    <div class="flex gap-3 pt-4 border-t border-slate-50">
                        <button type="button" @click="isAiModalOpen = false" class="flex-1 rounded-2xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase">Cancel</button>
                        <button type="submit" :disabled="aiForm.processing" class="flex-1 rounded-2xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20">Generate Paper</button>
                    </div>
                </form>
            </div>
        </div>
    </StaffLayout>
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