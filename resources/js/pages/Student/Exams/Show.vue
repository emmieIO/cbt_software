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
    image_path?: string;
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
const violations = ref<Array<{ type: string; timestamp: string }>>([]);
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
    // We set a tiny delay to allow the browser to initiate the transition 
    // before we start enforcing the fullscreen state
    setTimeout(() => {
        isInExamHall.value = true;
        isFullscreen.value = !!document.fullscreenElement;
    }, 300);
};

const handleFullscreenChange = () => {
    const wasFullscreen = isFullscreen.value;
    isFullscreen.value = !!document.fullscreenElement;

    // If they were in the hall and exited fullscreen, log it and prompt for submission
    if (isInExamHall.value && wasFullscreen && !isFullscreen.value && !isSubmitting.value) {
        isExitTriggered.value = true;
        logViolation('fullscreen_exit');
        isSubmitModalOpen.value = true;
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

const violationReason = ref('');

const logViolation = (type: string) => {
    if (isSubmitting.value || !isInExamHall.value) return;

    // Track detailed violation
    violations.value.push({
        type,
        timestamp: new Date().toISOString(),
    });
    
    // Set descriptive reason based on type
    switch(type) {
        case 'fullscreen_exit': violationReason.value = 'Fullscreen was exited.'; break;
        case 'tab_switch': violationReason.value = 'Browser tab was switched.'; break;
        case 'window_blur': violationReason.value = 'Examination window lost focus.'; break;
        default: violationReason.value = 'Security protocol breach.';
    }

    // Only show the general alert if we're not currently prompting for exit-submission
    if (type !== 'fullscreen_exit' && type !== 'tab_switch') {
        showViolationWarning.value = true;
    }
};

const handleVisibilityChange = () => {
    if (document.visibilityState === 'hidden' && isInExamHall.value && !isSubmitting.value) {
        isExitTriggered.value = true;
        logViolation('tab_switch');
        isSubmitModalOpen.value = true;
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
const isExitTriggered = ref(false);

const handleCancelSubmit = () => {
    isSubmitModalOpen.value = false;
    if (isExitTriggered.value && !isFullscreen.value) {
        // If they cancelled but are still not in fullscreen, 
        // the red security wall will be visible, which is correct.
        // We ensure they stay in the hall state.
    }
    isExitTriggered.value = false;
};
const confirmSubmit = () => {
    isExitTriggered.value = false;
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
            violations: violations.value,
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
            violations: violations.value,
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

    // Prevent right-click, copy, and cut
    document.addEventListener('contextmenu', (e) => e.preventDefault());
    document.addEventListener('copy', (e) => {
        if (isInExamHall.value) {
            e.preventDefault();
            return false;
        }
    });
    document.addEventListener('cut', (e) => {
        if (isInExamHall.value) {
            e.preventDefault();
            return false;
        }
    });

    // Prevent drag and drop of content
    document.addEventListener('dragstart', (e) => {
        if (isInExamHall.value) {
            e.preventDefault();
            return false;
        }
    });

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
    <div 
        class="min-h-screen bg-gray-50 font-sans text-gray-900 overflow-hidden"
        :class="isInExamHall ? 'select-none' : ''"
    >
        <Head :title="attempt.exam.title" />

        <!-- Exam Hall Entry Overlay -->
        <div v-if="!isInExamHall" class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/95 backdrop-blur-sm p-6">
            <div class="max-w-md w-full rounded-2xl bg-white p-8 shadow-lg text-center border border-gray-200">
                <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900">Secure Examination Hall</h2>
                <p class="mt-4 text-gray-600 text-sm leading-relaxed">
                    By entering, you agree to follow all examination rules. The assessment will be conducted in <strong>Fullscreen Mode</strong>. Exiting or switching tabs will be logged as a violation.
                </p>
                <button
                    @click="handleFullscreenHallEntry"
                    class="mt-8 w-full inline-flex items-center justify-center gap-x-2 rounded-xl bg-primary px-4 py-4 text-sm font-semibold text-white hover:bg-primary/90 focus:bg-primary/90 focus:outline-none disabled:pointer-events-none disabled:opacity-50 shadow-sm"
                >
                    Enter Examination Hall
                </button>
            </div>
        </div>

        <!-- Fullscreen Enforcement Overlay (The Wall) -->
        <div 
            v-if="isInExamHall && !isFullscreen && !isSubmitting" 
            class="fixed inset-0 z-[80] flex flex-col items-center justify-center bg-red-600 p-6 text-white select-none overflow-hidden"
        >
            <div class="flex h-full w-full max-w-lg flex-col items-center justify-between py-6">
                <!-- Header Icon -->
                <div class="shrink-0 h-20 w-20 flex items-center justify-center rounded-full bg-white/20 animate-pulse">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                
                <!-- Main Message -->
                <div class="text-center space-y-3">
                    <h2 class="text-3xl font-bold uppercase tracking-tight">Security Lock Active</h2>
                    <p class="text-lg font-medium opacity-90">Access revoked: {{ violationReason }}</p>
                </div>
                
                <!-- Violation Info -->
                <div class="flex flex-col items-center rounded-2xl bg-black/20 px-8 py-4 backdrop-blur-sm border border-white/10">
                    <span class="text-xs font-semibold uppercase opacity-60">Violations</span>
                    <span class="text-3xl font-bold tabular-nums">{{ violations.length }}</span>
                </div>

                <!-- Policy & Call to Action -->
                <div class="w-full space-y-6 text-center">
                    <div class="p-4 rounded-xl bg-white/10 border border-white/10 mx-auto max-w-sm">
                        <p class="text-xs font-medium opacity-80 leading-tight">
                            Exiting fullscreen is a security breach. You must return to fullscreen to resume or submit your test.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase opacity-60 animate-bounce">
                            Action Required
                        </p>
                        <button
                            @click="enterFullscreen"
                            class="inline-flex w-full items-center justify-center gap-x-2 rounded-xl bg-white px-4 py-4 text-sm font-bold text-red-600 hover:bg-gray-100 focus:outline-none transition-all shadow-lg"
                        >
                            Return to Exam
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam Header -->
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white px-6 py-4 shadow-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <div class="flex items-center gap-x-4">
                    <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-10 w-auto" />
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">{{ attempt.exam.title }}</h1>
                        <p class="text-xs font-medium text-gray-500">
                            {{ attempt.exam.subject?.name || 'Multi-Subject Assessment' }} • {{ questions.length }} Questions
                        </p>
                    </div>
                </div>

                <!-- Timer -->
                <div
                    class="flex items-center gap-x-3 rounded-xl px-6 py-2.5 transition-all"
                    :class="isTimeLow ? 'animate-pulse bg-red-50 text-red-600' : 'bg-gray-900 text-white'"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-xl font-bold tracking-tight tabular-nums">{{ formattedTime }}</span>
                </div>

                <button
                    @click="confirmSubmit"
                    class="inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary/90 focus:bg-primary/90 focus:outline-none disabled:pointer-events-none disabled:opacity-50 shadow-md"
                >
                    Submit Test
                </button>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Main Question Display -->
                <div class="lg:col-span-8">
                    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
                        <!-- Question Progress -->
                        <div class="mb-8 flex items-center justify-between">
                            <span class="text-xs font-semibold text-primary"
                                >Question {{ currentQuestionIndex + 1 }} of {{ questions.length }}</span
                            >
                            <div class="h-1.5 w-48 overflow-hidden rounded-full bg-gray-100">
                                <div
                                    class="h-full bg-primary transition-all duration-500"
                                    :style="{ width: `${((currentQuestionIndex + 1) / questions.length) * 100}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Question Content -->
                        <div class="mb-10">
                            <div v-if="currentQuestion.image_path" class="mb-8 overflow-hidden rounded-xl border border-gray-200">
                                <img :src="`/storage/${currentQuestion.image_path}`" class="max-h-[400px] w-full object-contain bg-gray-50" />
                            </div>
                            <h2 class="text-xl leading-relaxed font-semibold text-gray-800">
                                {{ currentQuestion.content }}
                            </h2>
                        </div>

                        <!-- Options -->
                        <div class="space-y-3">
                            <button
                                v-for="(option, idx) in currentQuestion.options"
                                :key="option.id"
                                @click="selectOption(currentQuestion.id, option.id)"
                                class="group flex w-full items-center gap-x-4 rounded-xl border p-5 text-left transition-all"
                                :class="
                                    answers[currentQuestion.id] === option.id
                                        ? 'border-primary bg-primary/5'
                                        : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                "
                            >
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border-2 font-semibold transition-all"
                                    :class="
                                        answers[currentQuestion.id] === option.id
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-gray-200 text-gray-400 group-hover:border-primary group-hover:text-primary'
                                    "
                                >
                                    {{ String.fromCharCode(65 + idx) }}
                                </div>
                                <span class="text-base font-medium text-gray-700">{{ option.content }}</span>
                            </button>
                        </div>

                        <!-- Footer Navigation -->
                        <div class="mt-10 flex items-center justify-between border-t border-gray-100 pt-8">
                            <button
                                @click="goToQuestion(currentQuestionIndex - 1)"
                                :disabled="currentQuestionIndex === 0"
                                class="inline-flex items-center gap-x-2 text-sm font-semibold text-gray-500 hover:text-gray-800 transition-all disabled:opacity-30 disabled:pointer-events-none"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>Previous</span>
                            </button>

                            <div class="flex items-center gap-2">
                                <span v-if="answers[currentQuestion.id]" class="text-xs font-semibold text-emerald-500"
                                    >Answered</span
                                >
                                <span v-else class="text-xs font-semibold text-amber-500">Not Answered</span>
                            </div>

                            <button
                                v-if="currentQuestionIndex < questions.length - 1"
                                @click="goToQuestion(currentQuestionIndex + 1)"
                                class="inline-flex items-center gap-x-2 text-sm font-semibold text-primary hover:translate-x-1 transition-all"
                            >
                                <span>Next Question</span>
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            <button
                                v-else
                                @click="confirmSubmit"
                                class="inline-flex items-center gap-x-2 text-sm font-semibold text-emerald-600 hover:scale-105 transition-all"
                            >
                                <span>Finish Test</span>
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: Navigation Grid -->
                <div class="lg:col-span-4">
                    <div class="sticky top-28 space-y-6">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="mb-6 text-sm font-semibold text-gray-500 uppercase tracking-wider">Question Navigator</h3>
                            <div class="grid grid-cols-5 gap-2">
                                <button
                                    v-for="(q, idx) in questions"
                                    :key="q.id"
                                    @click="goToQuestion(idx)"
                                    class="inline-flex h-10 items-center justify-center rounded-lg text-sm font-semibold transition-all border"
                                    :class="[
                                        currentQuestionIndex === idx ? 'border-primary ring-1 ring-primary' : 'border-transparent',
                                        answers[q.id]
                                            ? 'bg-primary text-white'
                                            : 'bg-gray-100 text-gray-500 hover:bg-gray-200',
                                    ]"
                                >
                                    {{ idx + 1 }}
                                </button>
                            </div>

                            <div class="mt-8 border-t border-gray-100 pt-6">
                                <div class="flex items-center justify-between text-xs font-semibold text-gray-400">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full bg-primary"></div>
                                        <span>Answered</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full bg-gray-200"></div>
                                        <span>Remaining</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Focus Reminder -->
                        <div class="rounded-xl border-2 border-dashed border-gray-200 p-8 text-center bg-white/50">
                            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gray-50 text-gray-400">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Security Mode Active</p>
                            <p class="mt-2 text-xs leading-relaxed font-medium text-gray-500">
                                Leaving this page or switching tabs may be flagged as a security violation.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <ConfirmationModal
            :show="isSubmitModalOpen"
            :title="isExitTriggered ? 'Submit and Exit?' : 'Submit Your Test?'"
            :message="isExitTriggered 
                ? 'You have attempt to leave the secure examination environment. If you are finished, you may submit your test now. Otherwise, click cancel and return to fullscreen to continue.' 
                : 'Are you sure you want to finish and submit your exam? You cannot change your answers once you proceed.'"
            confirm-label="Submit Now"
            variant="primary"
            @close="handleCancelSubmit"
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
