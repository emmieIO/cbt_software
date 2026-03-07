<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';

interface Option {
    id: string;
    content: string;
}

interface Question {
    id: string;
    content: string;
    options: Option[];
}

interface Attempt {
    id: string;
    started_at: string;
    exam: {
        id: string;
        title: string;
        duration: number;
        subject: { name: string } | null;
    };
}

const props = defineProps<{
    attempt: Attempt;
    questions: Question[];
    savedAnswers: Record<string, string>;
}>();

// State
const currentQuestionIndex = ref(0);
const answers = ref<Record<string, string>>({ ...props.savedAnswers });
const timeLeft = ref(0);
const timerInterval = ref<any>(null);
const violations = ref<number>(0);
const showViolationWarning = ref(false);
const isInExamHall = ref(false);
const isFullscreen = ref(false);

const currentQuestion = computed(() => props.questions[currentQuestionIndex.value]);

// Timer logic
const startTimer = () => {
    const startedAt = new Date(props.attempt.started_at).getTime();
    const durationMs = props.attempt.exam.duration * 60 * 1000;
    const endTime = startedAt + durationMs;

    const updateTimer = () => {
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            timeLeft.value = 0;
            clearInterval(timerInterval.value);
            submitExam();
            return;
        }

        timeLeft.value = distance;
    };

    updateTimer();
    timerInterval.value = setInterval(updateTimer, 1000);
};

const formattedTime = computed(() => {
    const minutes = Math.floor((timeLeft.value % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeLeft.value % (1000 * 60)) / 1000);
    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

const isTimeLow = computed(() => timeLeft.value < 5 * 60 * 1000); // 5 minutes

// Security Logic
const enterFullscreen = () => {
    const elem = document.documentElement as any;
    const requestMethod = elem.requestFullscreen || elem.webkitRequestFullscreen || elem.mozRequestFullScreen || elem.msRequestFullscreen;
    
    if (requestMethod) {
        requestMethod.call(elem).catch((err: any) => {
            console.error(`Error attempting to enable full-screen mode: ${err.message}`);
        });
    }
};

const handleFullscreenHallEntry = () => {
    enterFullscreen();
    isInExamHall.value = true;
};

const handleFullscreenChange = () => {
    const wasFullscreen = isFullscreen.value;
    isFullscreen.value = !!document.fullscreenElement;

    // If they were in the hall and exited fullscreen, log it as a violation
    if (isInExamHall.value && wasFullscreen && !isFullscreen.value && !isSubmitting.value) {
        logViolation('fullscreen_exit');
    }
};

const handleKeydown = (e: KeyboardEvent) => {
    if (!isInExamHall.value || isSubmitting.value) return;

    // Prevent Esc key if possible (browsers usually protect this, but we try)
    if (e.key === 'Escape' && isFullscreen.value) {
        e.preventDefault();
        return false;
    }

    // Prevent F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
    if (
        e.key === 'F12' || 
        (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) ||
        (e.ctrlKey && e.key === 'u')
    ) {
        e.preventDefault();
        return false;
    }
};

const logViolation = (type: string) => {
    if (isSubmitting.value || !isInExamHall.value) return;

    violations.value++;

    // Show the blocking alert
    showViolationWarning.value = true;

    // Optional: Log to backend immediately if you have a logging endpoint
    // This provides a paper trail for the invigilator
};

const handleVisibilityChange = () => {
    if (document.visibilityState === 'hidden' && isInExamHall.value) {
        logViolation('tab_switch');
    }
};

// Navigation
const goToQuestion = (index: number) => {
    if (index >= 0 && index < props.questions.length) {
        currentQuestionIndex.value = index;
    }
};

const selectOption = (questionId: string, optionId: string) => {
    answers.value[questionId] = optionId;

    // Auto-save to backend
    import('@inertiajs/vue3').then(({ router }) => {
        router.patch(
            `/student/exams/${props.attempt.id}/answer`,
            {
                question_id: questionId,
                option_id: optionId,
            },
            {
                preserveScroll: true,
                preserveState: true,
                only: [],
            },
        );
    });
};

// Submission
const isSubmitting = ref(false);
const isSubmitModalOpen = ref(false);

const confirmSubmit = () => {
    isSubmitModalOpen.value = true;
};

const handleFinalSubmit = () => {
    if (isSubmitting.value) return;

    isSubmitting.value = true;
    isSubmitModalOpen.value = false;

    // Exit fullscreen on submit
    if (document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
    }

    // Using Inertia router directly
    import('@inertiajs/vue3').then(({ router }) => {
        router.post(`/student/exams/${props.attempt.id}/submit`, {
            answers: answers.value,
        });
    });
};

const submitExam = () => {
    // This is called automatically by timer or after confirmation
    if (isSubmitting.value) return;

    isSubmitting.value = true;

    if (document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
    }

    import('@inertiajs/vue3').then(({ router }) => {
        router.post(`/student/exams/${props.attempt.id}/submit`, {
            answers: answers.value,
        });
    });
};

onMounted(() => {
    startTimer();
    
    // Security Listeners
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);
    
    window.addEventListener('blur', () => logViolation('window_blur'));
    window.addEventListener('keydown', handleKeydown);

    // Prevent right-click
    document.addEventListener('contextmenu', (e) => e.preventDefault());

    // Prevent accidental back navigation
    window.onbeforeunload = () => 'Examination in progress. Are you sure you want to leave?';
});

onBeforeUnmount(() => {
    clearInterval(timerInterval.value);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.removeEventListener('mozfullscreenchange', handleFullscreenChange);
    document.removeEventListener('MSFullscreenChange', handleFullscreenChange);
    
    window.removeEventListener('blur', () => logViolation('window_blur'));
    window.removeEventListener('keydown', handleKeydown);
    window.onbeforeunload = null;
});
</script>

<template>
    <div class="min-h-screen bg-[#F8F9FB] font-sans text-slate-900 overflow-hidden">
        <Head :title="attempt.exam.title" />

        <!-- Exam Hall Entry Overlay -->
        <div v-if="!isInExamHall" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/95 backdrop-blur-md p-6">
            <div class="max-w-md w-full rounded-3xl bg-white p-10 shadow-2xl text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-slate-900">Secure Examination Hall</h2>
                <p class="mt-4 text-slate-500 font-medium leading-relaxed">
                    By entering, you agree to follow all examination rules. The assessment will be conducted in <strong>Fullscreen Mode</strong>. Exiting or switching tabs will be logged as a violation.
                </p>
                <button
                    @click="handleFullscreenHallEntry"
                    class="mt-10 w-full rounded-2xl bg-primary py-5 text-sm font-black tracking-widest text-white uppercase shadow-xl shadow-primary/20 transition-all hover:scale-[1.02] active:scale-95"
                >
                    Enter Examination Hall
                </button>
            </div>
        </div>

        <!-- Fullscreen Enforcement Overlay (The Wall) -->
        <div 
            v-if="isInExamHall && !isFullscreen && !isSubmitting" 
            class="fixed inset-0 z-[200] flex items-center justify-center bg-red-600 p-6 text-white overflow-hidden select-none"
        >
            <div class="text-center max-w-xl">
                <div class="mx-auto mb-8 h-32 w-32 flex items-center justify-center rounded-full bg-white/20 animate-pulse">
                    <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h2 class="text-5xl font-black uppercase tracking-tighter italic leading-none">Security Lock Active</h2>
                <p class="mt-8 text-2xl font-bold leading-relaxed">
                    Access to examination content has been revoked because you exited Fullscreen Mode.
                </p>
                
                <div class="mt-10 inline-flex flex-col items-center rounded-3xl bg-black/20 px-10 py-6 backdrop-blur-sm">
                    <span class="text-[10px] font-black tracking-[0.3em] uppercase opacity-60">Violation Count</span>
                    <span class="text-4xl font-black tabular-nums">{{ violations }}</span>
                </div>

                <p class="mt-10 text-sm font-black tracking-widest uppercase opacity-75 animate-bounce">
                    Re-enter Fullscreen to Resume
                </p>
                
                <button
                    @click="enterFullscreen"
                    class="mt-6 w-full rounded-2xl bg-white px-12 py-6 text-base font-black tracking-widest text-red-600 uppercase shadow-2xl transition-all hover:scale-105 active:scale-95"
                >
                    Return to Exam
                </button>
            </div>
        </div>

        <!-- Exam Header -->
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white px-6 py-4 shadow-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-10 w-auto" />
                    <div>
                        <h1 class="text-lg font-black tracking-tight text-slate-900">{{ attempt.exam.title }}</h1>
                        <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                            {{ attempt.exam.subject?.name || 'Multi-Subject Assessment' }} • {{ questions.length }} Questions
                        </p>
                    </div>
                </div>

                <!-- Timer -->
                <div
                    class="flex items-center space-x-3 rounded-xl px-6 py-3 transition-all"
                    :class="isTimeLow ? 'animate-pulse bg-red-50 text-red-600' : 'bg-slate-900 text-white'"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-xl font-black tracking-tighter tabular-nums">{{ formattedTime }}</span>
                </div>

                <button
                    @click="confirmSubmit"
                    class="rounded-xl bg-primary px-8 py-3 text-xs font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                >
                    Submit Test
                </button>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-10">
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                <!-- Main Question Display -->
                <div class="lg:col-span-8">
                    <div class="rounded-2xl border border-slate-100 bg-white p-10 shadow-xl">
                        <!-- Question Progress -->
                        <div class="mb-8 flex items-center justify-between">
                            <span class="text-xs font-black tracking-widest text-primary uppercase"
                                >Question {{ currentQuestionIndex + 1 }} of {{ questions.length }}</span
                            >
                            <div class="h-1.5 w-48 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full bg-primary transition-all duration-500"
                                    :style="{ width: `${((currentQuestionIndex + 1) / questions.length) * 100}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Question Content -->
                        <div class="mb-12">
                            <h2 class="text-2xl leading-snug font-black text-slate-800">
                                {{ currentQuestion.content }}
                            </h2>
                        </div>

                        <!-- Options -->
                        <div class="space-y-4">
                            <button
                                v-for="(option, idx) in currentQuestion.options"
                                :key="option.id"
                                @click="selectOption(currentQuestion.id, option.id)"
                                class="group flex w-full items-center space-x-4 rounded-2xl border-2 p-6 text-left transition-all"
                                :class="
                                    answers[currentQuestion.id] === option.id
                                        ? 'border-primary bg-primary/5'
                                        : 'border-slate-100 hover:border-slate-200 hover:bg-slate-50'
                                "
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border-2 font-black transition-all"
                                    :class="
                                        answers[currentQuestion.id] === option.id
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-slate-200 text-slate-400 group-hover:border-primary group-hover:text-primary'
                                    "
                                >
                                    {{ String.fromCharCode(65 + idx) }}
                                </div>
                                <span class="text-lg font-bold text-slate-700">{{ option.content }}</span>
                            </button>
                        </div>

                        <!-- Footer Navigation -->
                        <div class="mt-12 flex items-center justify-between border-t border-slate-50 pt-8">
                            <button
                                @click="goToQuestion(currentQuestionIndex - 1)"
                                :disabled="currentQuestionIndex === 0"
                                class="flex items-center space-x-2 text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:text-slate-900 disabled:opacity-30"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>Previous</span>
                            </button>

                            <div class="flex items-center gap-2">
                                <span v-if="answers[currentQuestion.id]" class="text-[10px] font-black tracking-widest text-green-500 uppercase"
                                    >Answered</span
                                >
                                <span v-else class="text-[10px] font-black tracking-widest text-amber-500 uppercase">Not Answered</span>
                            </div>

                            <button
                                v-if="currentQuestionIndex < questions.length - 1"
                                @click="goToQuestion(currentQuestionIndex + 1)"
                                class="flex items-center space-x-2 text-sm font-black tracking-widest text-primary uppercase transition-all hover:translate-x-1"
                            >
                                <span>Next Question</span>
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            <button
                                v-else
                                @click="confirmSubmit"
                                class="flex items-center space-x-2 text-sm font-black tracking-widest text-green-600 uppercase transition-all hover:scale-105"
                            >
                                <span>Finish Test</span>
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: Navigation Grid -->
                <div class="lg:col-span-4">
                    <div class="sticky top-32 space-y-6">
                        <div class="rounded-2xl border border-slate-100 bg-white p-8 shadow-xl">
                            <h3 class="mb-6 text-sm font-black tracking-widest text-slate-400 uppercase">Question Navigator</h3>
                            <div class="grid grid-cols-5 gap-3">
                                <button
                                    v-for="(q, idx) in questions"
                                    :key="q.id"
                                    @click="goToQuestion(idx)"
                                    class="flex h-10 w-full items-center justify-center rounded-lg text-xs font-black transition-all"
                                    :class="[
                                        currentQuestionIndex === idx ? 'ring-2 ring-primary ring-offset-2' : '',
                                        answers[q.id]
                                            ? 'bg-primary text-white shadow-lg shadow-primary/20'
                                            : 'bg-slate-100 text-slate-400 hover:bg-slate-200',
                                    ]"
                                >
                                    {{ idx + 1 }}
                                </button>
                            </div>

                            <div class="mt-8 border-t border-slate-50 pt-6">
                                <div class="flex items-center justify-between text-[10px] font-black tracking-widest uppercase">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full bg-primary"></div>
                                        <span class="text-slate-400">Answered</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full bg-slate-200"></div>
                                        <span class="text-slate-400">Remaining</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Focus Reminder -->
                        <div class="rounded-2xl border-2 border-dashed border-slate-200 p-8 text-center">
                            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                            </div>
                            <p class="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">Security Mode Active</p>
                            <p class="mt-2 text-xs leading-relaxed font-bold text-slate-500">
                                Leaving this page or switching tabs may be flagged as a security violation.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <ConfirmationModal
            :show="isSubmitModalOpen"
            title="Submit Your Test?"
            message="Are you sure you want to finish and submit your exam? You cannot change your answers once you proceed."
            confirm-label="Submit Now"
            variant="primary"
            @close="isSubmitModalOpen = false"
            @confirm="handleFinalSubmit"
        />

        <!-- Security Violation Modal -->
        <ConfirmationModal
            :show="showViolationWarning"
            title="Security Alert!"
            message="A security violation has been detected (tab switch or window blur). This incident has been logged and reported to the invigilator. Please return to the exam immediately."
            confirm-label="Return to Exam"
            variant="danger"
            @close="showViolationWarning = false"
            @confirm="
                () => {
                    showViolationWarning = false;
                    enterFullscreen();
                }
            "
        />
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
