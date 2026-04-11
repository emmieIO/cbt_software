<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import StudentLayout from '@/layouts/StudentLayout.vue';

interface Exam {
    id: string;
    title: string;
    duration: number;
    subject: { name: string } | null;
    questions_count: number;
    start_time: string | null;
    end_time: string | null;
    availability_status: 'available' | 'ongoing' | 'completed' | 'scheduled' | 'missed' | 'expired';
    attempts: Array<{ id: string; status: string }>;
}

defineProps<{
    exams: Exam[];
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

const formatSchedule = (value: string | null) => {
    if (!value) return 'No fixed time';

    return new Date(value).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const statusBadgeClasses: Record<Exam['availability_status'], string> = {
    available: 'bg-primary/10 text-primary',
    ongoing: 'bg-blue-100 text-blue-800',
    completed: 'bg-teal-100 text-teal-800',
    scheduled: 'bg-slate-100 text-slate-700',
    missed: 'bg-rose-100 text-rose-700',
    expired: 'bg-orange-100 text-orange-700',
};

const statusLabel: Record<Exam['availability_status'], string> = {
    available: 'Available',
    ongoing: 'In Progress',
    completed: 'Completed',
    scheduled: 'Scheduled',
    missed: 'Missed',
    expired: 'Expired',
};
</script>

<template>
    <StudentLayout>
        <Head title="Available Examinations" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/student/dashboard" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-800">Available Assessments</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Available Assessments</h1>
                    <p class="mt-1 text-sm text-gray-500">All assessments for your current academic level</p>
                </div>
            </div>

            <!-- Exam Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="inline-block min-w-full p-1.5 align-middle">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                                            Assessment Details
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Subject</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Configuration</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Window</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="exam in exams" :key="exam.id" class="transition-colors hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-x-3">
                                                <div class="flex size-8 flex-shrink-0 items-center justify-center rounded bg-primary/5 text-primary">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01"
                                                        />
                                                    </svg>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-800">{{ exam.title }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex items-center gap-x-1.5 rounded-full bg-primary/5 px-2.5 py-1 text-xs font-medium text-primary"
                                            >
                                                {{ exam.subject?.name || 'Multi-Subject' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                            {{ exam.duration }} Mins • {{ exam.questions_count }} Questions
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                            <div class="space-y-1">
                                                <div>{{ formatSchedule(exam.start_time) }}</div>
                                                <span :class="statusBadgeClasses[exam.availability_status]" class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase">
                                                    {{ statusLabel[exam.availability_status] }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div
                                                v-if="exam.availability_status === 'completed'"
                                                class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1.5 text-xs font-medium text-teal-800"
                                            >
                                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Completed
                                            </div>
                                            <div
                                                v-else-if="exam.availability_status === 'missed'"
                                                class="inline-flex items-center gap-x-1.5 rounded-full bg-rose-100 px-3 py-1.5 text-xs font-medium text-rose-700"
                                            >
                                                Missed
                                            </div>
                                            <div
                                                v-else-if="exam.availability_status === 'expired'"
                                                class="inline-flex items-center gap-x-1.5 rounded-full bg-orange-100 px-3 py-1.5 text-xs font-medium text-orange-700"
                                            >
                                                Expired
                                            </div>
                                            <div
                                                v-else-if="exam.availability_status === 'scheduled'"
                                                class="inline-flex items-center gap-x-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700"
                                            >
                                                Not Yet Open
                                            </div>
                                            <button
                                                v-else
                                                @click="confirmStartExam(exam.id)"
                                                class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-3 py-2 text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none"
                                            >
                                                {{ exam.availability_status === 'ongoing' ? 'Continue Exam' : 'Start Assessment' }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="exams.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="mb-3 size-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                                <p class="text-sm font-medium">No active assessments found at the moment.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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
