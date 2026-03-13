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
</script>

<template>
    <StudentLayout>
        <Head title="Available Examinations" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/student/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Available Assessments</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Available Assessments</h1>
                    <p class="text-sm text-gray-500 mt-1">All assessments for your current academic level</p>
                </div>
            </div>

            <!-- Exam Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Assessment Details</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Subject</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Configuration</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="exam in exams" :key="exam.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-x-3">
                                                <div class="size-8 flex-shrink-0 flex items-center justify-center rounded bg-primary/5 text-primary">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01" /></svg>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-800">{{ exam.title }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-primary/5 text-primary">
                                                {{ exam.subject?.name || 'Multi-Subject' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                            {{ exam.duration }} Mins • {{ exam.questions_count }} Questions
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div v-if="exam.attempts.some((a) => a.status === 'submitted')" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                                Completed
                                            </div>
                                            <button
                                                v-else
                                                @click="confirmStartExam(exam.id)"
                                                class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                                            >
                                                {{ exam.attempts.some((a) => a.status === 'ongoing') ? 'Continue Exam' : 'Start Assessment' }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="exams.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="size-8 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
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
