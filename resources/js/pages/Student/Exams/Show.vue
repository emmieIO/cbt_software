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
    const elem = document.documentElement;
    if (elem.requestFullscreen) {
        elem.requestFullscreen().catch(() => {
            console.warn('Fullscreen request failed');
        });
    }
};

const logViolation = (type: string) => {
    if (isSubmitting.value) return;

    violations.value++;

    // Auto-submit immediately on violation
    isSubmitting.value = true;

    if (document.fullscreenElement) {
        document.exitFullscreen();
    }

    import('@inertiajs/vue3').then(({ router }) => {
        router.post(`/student/exams/${props.attempt.id}/submit`, {
            answers: answers.value,
            termination_reason: `Security Violation: ${type === 'tab_switch' ? 'Tab Switched' : 'Window Blurred'}`,
            violation_count: violations.value,
        });
    });
};

const handleVisibilityChange = () => {
    if (document.visibilityState === 'hidden') {
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
        document.exitFullscreen();
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
        document.exitFullscreen();
    }

    import('@inertiajs/vue3').then(({ router }) => {
        router.post(`/student/exams/${props.attempt.id}/submit`, {
            answers: answers.value,
        });
    });
};

onMounted(() => {
    startTimer();
    enterFullscreen();

    // Security Listeners
    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('blur', () => logViolation('window_blur'));

    // Prevent right-click
    document.addEventListener('contextmenu', (e) => e.preventDefault());

    // Prevent accidental back navigation
    window.onbeforeunload = () => 'Examination in progress. Are you sure you want to leave?';
});

onBeforeUnmount(() => {
    clearInterval(timerInterval.value);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    window.onbeforeunload = null;
});
</script>

<template>
    <div class="min-h-screen bg-[#F8F9FB] font-sans text-slate-900">
        <Head :title="attempt.exam.title" />

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
