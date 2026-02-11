<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import type { Question } from '@/types/academics';

const props = defineProps<{
    attempt: {
        id: string;
        exam: {
            title: string;
            subject: { name: string };
            duration: number;
        };
        started_at: string;
        status: string;
    };
    questions: Question[];
}>();

const currentQuestionIndex = ref(0);
const selectedAnswers = ref<Record<string, string>>({}); // { question_id: option_id }

const currentQuestion = computed(() => props.questions[currentQuestionIndex.value]);

// Timer logic
const timeRemaining = ref(0);
let timerInterval: any = null;

const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

onMounted(() => {
    // Initialize timer
    const startTime = new Date(props.attempt.started_at).getTime();
    const durationMs = props.attempt.exam.duration * 60 * 1000;
    const endTime = startTime + durationMs;
    
    const updateTimer = () => {
        const now = new Date().getTime();
        const diff = Math.max(0, Math.floor((endTime - now) / 1000));
        timeRemaining.value = diff;
        
        if (diff <= 0) {
            submitExam(true);
        }
    };
    
    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const selectOption = (optionId: string) => {
    selectedAnswers.value[currentQuestion.value.id] = optionId;
};

const nextQuestion = () => {
    if (currentQuestionIndex.value < props.questions.length - 1) {
        currentQuestionIndex.value++;
    }
};

const prevQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--;
    }
};

const submitExam = (isAuto = false) => {
    if (!isAuto && !confirm('Are you sure you want to submit your exam?')) {
        return;
    }
    
    if (timerInterval) clearInterval(timerInterval);
    
    router.post(`/student/exams/${props.attempt.id}/submit`, {
        answers: selectedAnswers.value
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-inter text-slate-900">
        <Head :title="attempt.exam.title" />

        <!-- Header -->
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white shadow-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div>
                    <h1 class="text-xl font-black tracking-tight text-slate-900">{{ attempt.exam.title }}</h1>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ attempt.exam.subject.name }}</p>
                </div>

                <div class="flex items-center gap-8">
                    <!-- Timer -->
                    <div class="flex items-center gap-3 rounded-xl bg-slate-900 px-6 py-3 text-white shadow-xl shadow-slate-200">
                        <svg class="h-5 w-5 text-lemon-yellow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-mono text-2xl font-black tracking-tighter">{{ formatTime(timeRemaining) }}</span>
                    </div>

                    <button 
                        @click="submitExam(false)"
                        class="rounded-xl bg-primary px-8 py-3 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                    >
                        Finish Exam
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl p-8 lg:p-12">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-3">
                
                <!-- Main Question Area -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Question Card -->
                    <div class="rounded-xl bg-white p-10 shadow-sm border border-slate-100">
                        <div class="mb-8 flex items-center justify-between">
                            <span class="rounded-full bg-slate-100 px-4 py-1.5 text-[10px] font-black tracking-widest text-slate-500 uppercase">
                                Question {{ currentQuestionIndex + 1 }} of {{ questions.length }}
                            </span>
                            <span :class="[
                                'rounded-xl px-3 py-1 text-[10px] font-black tracking-widest uppercase',
                                currentQuestion.difficulty === 'easy' ? 'bg-green-100 text-green-700' : 
                                currentQuestion.difficulty === 'medium' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'
                            ]">
                                {{ currentQuestion.difficulty }}
                            </span>
                        </div>

                        <h2 class="text-2xl font-bold leading-relaxed text-slate-800 mb-12">
                            {{ currentQuestion.content }}
                        </h2>

                        <!-- Options -->
                        <div class="space-y-4">
                            <button
                                v-for="(option, index) in currentQuestion.options"
                                :key="option.id"
                                @click="selectOption(option.id)"
                                :class="[
                                    'group flex w-full items-center gap-6 rounded-xl border-2 p-6 text-left transition-all duration-200',
                                    selectedAnswers[currentQuestion.id] === option.id
                                        ? 'border-primary bg-primary/2 ring-4 ring-primary/5'
                                        : 'border-slate-100 bg-white hover:border-slate-300 hover:bg-slate-50'
                                ]"
                            >
                                <div :class="[
                                    'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-lg font-black transition-all',
                                    selectedAnswers[currentQuestion.id] === option.id
                                        ? 'bg-primary text-white'
                                        : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-slate-600'
                                ]">
                                    {{ String.fromCharCode(65 + index) }}
                                </div>
                                <span :class="[
                                    'text-lg font-bold transition-all',
                                    selectedAnswers[currentQuestion.id] === option.id ? 'text-slate-900' : 'text-slate-600'
                                ]">
                                    {{ option.content }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="flex items-center justify-between">
                        <button 
                            @click="prevQuestion"
                            :disabled="currentQuestionIndex === 0"
                            class="flex items-center gap-3 rounded-xl bg-white px-8 py-4 text-sm font-black tracking-widest text-slate-600 uppercase shadow-sm border border-slate-100 transition-all hover:bg-slate-50 active:scale-95 disabled:opacity-30 disabled:pointer-events-none"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                            </svg>
                            Previous
                        </button>

                        <button 
                            @click="nextQuestion"
                            v-if="currentQuestionIndex < questions.length - 1"
                            class="flex items-center gap-3 rounded-xl bg-white px-8 py-4 text-sm font-black tracking-widest text-slate-600 uppercase shadow-sm border border-slate-100 transition-all hover:bg-slate-50 active:scale-95"
                        >
                            Next Question
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <button 
                            @click="submitExam(false)"
                            v-else
                            class="rounded-xl bg-primary px-12 py-4 text-sm font-black tracking-widest text-white uppercase shadow-xl shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                        >
                            Final Submission
                        </button>
                    </div>
                </div>

                <!-- Sidebar / Question Grid -->
                <div class="space-y-8">
                    <div class="rounded-xl bg-white p-8 shadow-sm border border-slate-100">
                        <h3 class="mb-6 text-sm font-black tracking-widest text-slate-400 uppercase">Question Navigator</h3>
                        
                        <div class="grid grid-cols-5 gap-3">
                            <button
                                v-for="(_, index) in questions"
                                :key="index"
                                @click="currentQuestionIndex = index"
                                :class="[
                                    'flex h-10 w-10 items-center justify-center rounded-xl text-xs font-black transition-all',
                                    currentQuestionIndex === index 
                                        ? 'bg-primary text-white ring-4 ring-primary/10 scale-110' 
                                        : (selectedAnswers[questions[index].id] 
                                            ? 'bg-green-100 text-green-700' 
                                            : 'bg-slate-50 text-slate-400 hover:bg-slate-100')
                                ]"
                            >
                                {{ index + 1 }}
                            </button>
                        </div>

                        <div class="mt-8 border-t border-slate-50 pt-6">
                            <div class="flex items-center justify-between text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                <span>Answered: {{ Object.keys(selectedAnswers).length }} / {{ questions.length }}</span>
                            </div>
                            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div 
                                    class="h-full bg-primary transition-all duration-500" 
                                    :style="{ width: `${(Object.keys(selectedAnswers).length / questions.length) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="rounded-xl bg-lemon-yellow/5 p-8 border border-lemon-yellow/10">
                        <h4 class="mb-4 text-xs font-black tracking-widest text-slate-900 uppercase flex items-center gap-2">
                            <svg class="h-4 w-4 text-lemon-yellow" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            Instructions
                        </h4>
                        <ul class="space-y-3 text-xs font-bold text-slate-600 leading-relaxed">
                            <li>• Select one answer per question.</li>
                            <li>• You can navigate between questions at any time.</li>
                            <li>• The exam will auto-submit when the timer hits 0:00.</li>
                            <li>• Do not close this tab until you have submitted.</li>
                        </ul>
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>

<style scoped>
.font-mono {
    font-family: 'JetBrains Mono', monospace;
}
</style>
