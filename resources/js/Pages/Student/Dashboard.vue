<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import StudentLayout from '@/layouts/StudentLayout.vue';

interface Exam {
    id: string;
    title: string;
    start_time: string;
    duration: number;
    subject: { name: string } | null;
}

defineProps<{
    upcomingExams: Exam[];
    recentResults: any[];
    stats: {
        examsTaken: number;
        averageScore: number;
        pendingExams: number;
    };
}>();

const isStartModalOpen = ref(false);
const examToStart = ref<string | null>(null);

const confirmStartExam = (examId: string) => {
    examToStart.value = examId;
    isStartModalOpen.value = true;
};

const handleStartExam = () => {
    if (examToStart.value) {
        router.post(`/student/exams/${examToStart.value}/start`);
        isStartModalOpen.value = false;
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <StudentLayout>
        <Head title="Student Dashboard" />

        <div class="space-y-10">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Welcome Back</h1>
                <p class="mt-1 text-sm text-gray-500">Review your upcoming assessments and recent academic performance.</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                    <p class="text-xs font-semibold tracking-wider text-gray-400 uppercase">Assessments Completed</p>
                    <div class="mt-2 flex items-center gap-x-2">
                        <h3 class="text-2xl font-bold text-gray-800">{{ stats.examsTaken }}</h3>
                    </div>
                </div>

                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                    <p class="text-xs font-semibold tracking-wider text-gray-400 uppercase">Average Performance</p>
                    <div class="mt-2 flex items-center gap-x-2">
                        <h3 class="text-2xl font-bold text-primary">{{ stats.averageScore }}%</h3>
                    </div>
                </div>

                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                    <p class="text-xs font-semibold tracking-wider text-gray-400 uppercase">Pending Schedule</p>
                    <div class="mt-2 flex items-center gap-x-2">
                        <h3 class="text-2xl font-bold text-orange-600">{{ stats.pendingExams }}</h3>
                    </div>
                </div>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                <!-- Upcoming Exams -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-sm font-bold tracking-wider text-gray-800 uppercase">Immediate Schedule</h2>
                        <Link href="/student/exams" class="text-xs font-semibold text-primary hover:underline">View Full Calendar</Link>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="exam in upcomingExams"
                            :key="exam.id"
                            class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-primary/30"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <span
                                        class="mb-2 inline-flex items-center gap-1.5 rounded-md bg-primary/10 px-2 py-1 text-[10px] font-bold text-primary uppercase"
                                    >
                                        {{ exam.subject?.name || 'Multi-Subject' }}
                                    </span>
                                    <h3 class="text-base font-bold text-gray-800">{{ exam.title }}</h3>
                                    <div class="mt-2 flex items-center gap-4 text-xs font-medium text-gray-500">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                            {{ formatDate(exam.start_time) }}
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                            {{ exam.duration }} Mins
                                        </span>
                                    </div>
                                </div>
                                <button
                                    @click="confirmStartExam(exam.id)"
                                    class="rounded-lg bg-gray-900 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-black"
                                >
                                    Access Hall
                                </button>
                            </div>
                        </div>

                        <div v-if="upcomingExams.length === 0" class="rounded-xl border border-dashed border-gray-200 bg-gray-50 py-12 text-center">
                            <svg class="mx-auto mb-3 size-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            <p class="text-sm font-medium text-gray-400">No examinations scheduled for you currently.</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-sm font-bold tracking-wider text-gray-800 uppercase">Recent Performance</h2>
                        <Link href="/student/results" class="text-xs font-semibold text-primary hover:underline">Transcript Archive</Link>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">Assessment</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Score</th>
                                    <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="result in recentResults" :key="result.id" class="transition-colors hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-bold text-gray-800">{{ result.exam.title }}</span>
                                        <p class="text-xs text-gray-500">{{ result.exam.subject?.name || 'Multi-Subject' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="text-sm font-bold"
                                            :class="result.score >= result.exam.questions_count / 2 ? 'text-primary' : 'text-orange-600'"
                                        >
                                            {{ result.score }} / {{ result.exam.questions_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-end">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-md bg-teal-100 px-2 py-1 text-[10px] font-bold text-teal-800 uppercase"
                                        >
                                            Released
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="recentResults.length === 0">
                                    <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-400">No results released yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isStartModalOpen"
            title="Begin Assessment?"
            message="You are about to start this examination. Once you proceed, the timer will begin. Ensure your environment is quiet and your connection is stable."
            confirm-label="Begin Now"
            variant="primary"
            @close="isStartModalOpen = false"
            @confirm="handleStartExam"
        />
    </StudentLayout>
</template>
