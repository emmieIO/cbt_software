<script setup lang="ts">
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { index as examIndex, results as resultsIndex } from '@/actions/App/Http/Controllers/Student/StudentController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import StudentLayout from '@/layouts/StudentLayout.vue';

const page = usePage();
const userName = computed(() => page.props.auth.user.name);

defineProps<{
    availableExams: Array<{
        id: string;
        title: string;
        duration: number;
        subject: { name: string } | null;
        questions_count: number;
        attempts: Array<{ id: string; status: string }>;
    }>;
    completedExamsCount: number;
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
        <Head title="Student Hub" />

        <div class="space-y-10">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">Student Hub</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400">Your examination command center</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="h-3 w-3 animate-pulse rounded-full bg-green-500"></div>
                    <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">System Active</span>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="relative overflow-hidden rounded-xl bg-slate-900 px-6 py-10 md:px-12 md:py-16 text-white shadow-2xl">
                <div class="relative z-10 max-w-2xl">
                    <h1 class="text-3xl md:text-5xl font-black tracking-tighter italic">Hello, {{ userName }}!</h1>
                    <p class="mt-4 text-base md:text-xl leading-relaxed font-medium text-slate-400">
                        Ready for your next challenge? Below are the live examinations currently available for your academic level.
                    </p>
                </div>
                <!-- Abstract Design -->
                <div class="rounded-lg-full absolute -top-24 -right-24 h-64 w-64 md:h-96 md:w-96 bg-primary/10 blur-3xl"></div>
                <div class="rounded-lg-full absolute right-0 bottom-0 h-48 w-48 md:h-64 md:w-64 bg-lemon-yellow/5 blur-2xl"></div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-4 md:gap-6 md:grid-cols-2">
                <Link
                    :href="examIndex().url"
                    class="group rounded-xl border border-slate-100 bg-white p-6 md:p-8 shadow-sm transition-all hover:border-primary/20 hover:shadow-lg"
                >
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors group-hover:text-primary">
                        Live Assessments
                    </p>
                    <p class="mt-4 text-3xl md:text-4xl font-black tracking-tighter text-primary">{{ availableExams.length }}</p>
                </Link>
                <Link
                    :href="resultsIndex().url"
                    class="group rounded-xl border border-slate-100 bg-white p-6 md:p-8 shadow-sm transition-all hover:border-primary/20 hover:shadow-lg"
                >
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors group-hover:text-primary">
                        Completed Tests
                    </p>
                    <p class="mt-4 text-3xl md:text-4xl font-black tracking-tighter text-slate-800">{{ completedExamsCount }}</p>
                </Link>
            </div>

            <!-- Exam Table -->
            <div class="space-y-6">
                <div class="ml-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="flex items-center gap-3 text-sm font-black tracking-[0.2em] text-slate-400 uppercase">
                        <div class="h-2 w-2 animate-ping rounded-full bg-primary"></div>
                        Recent Assessments
                    </h3>
                    <Link :href="examIndex().url" class="text-[10px] font-black tracking-widest text-primary uppercase hover:underline"
                        >View All Assessments &rarr;</Link
                    >
                </div>

                <div
                    v-if="availableExams.length === 0"
                    class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white py-12 md:py-20 text-center"
                >
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="px-6 text-sm font-bold tracking-widest text-slate-400 uppercase italic">No active examinations at this hour.</p>
                </div>

                <div v-else class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="bg-[#FBFBFC]">
                                    <th class="px-8 py-5 text-[9px] font-black tracking-widest text-slate-400 uppercase">Examination</th>
                                    <th class="px-6 py-5 text-[9px] font-black tracking-widest text-slate-400 uppercase">Subject</th>
                                    <th class="px-6 py-5 text-[9px] font-black tracking-widest text-slate-400 uppercase">Details</th>
                                    <th class="px-8 py-5 text-right text-[9px] font-black tracking-widest text-slate-400 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="exam in availableExams.slice(0, 5)" :key="exam.id" class="group transition-all hover:bg-[#F8F9FB]">
                                    <td class="px-8 py-6 whitespace-nowrap">
                                        <div class="flex items-center gap-4">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="block truncate text-sm font-black text-slate-800">{{ exam.title }}</span>
                                                <span class="text-[10px] font-bold text-slate-400 uppercase">{{ exam.questions_count }} Questions</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <span class="rounded-lg bg-primary/5 px-2.5 py-1 text-[10px] font-black text-primary uppercase">
                                            {{ exam.subject?.name || 'Multi-Subject' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap text-[10px] font-black text-slate-500 uppercase">
                                        {{ exam.duration }} Mins Allotted
                                    </td>
                                    <td class="px-8 py-6 text-right whitespace-nowrap">
                                        <div
                                            v-if="exam.attempts.some((a) => a.status === 'submitted')"
                                            class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-[9px] font-black text-green-600 uppercase border border-green-100"
                                        >
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                            Completed
                                        </div>
                                        <button
                                            v-else
                                            @click="confirmStartExam(exam.id)"
                                            class="rounded-lg bg-slate-900 px-4 py-2 text-[9px] font-black tracking-widest text-white uppercase shadow-lg transition-all hover:bg-black active:scale-95"
                                        >
                                            {{ exam.attempts.some((a) => a.status === 'ongoing') ? 'Continue' : 'Start' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pro Tip / Security Info -->
            <div class="rounded-2xl border-2 border-dashed border-primary/20 bg-primary/5 p-10 text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-white shadow-lg shadow-primary/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </div>
                <h4 class="text-sm font-black tracking-widest text-primary uppercase">Security Reminder</h4>
                <p class="mx-auto mt-2 max-w-lg text-xs leading-relaxed font-bold text-slate-500">
                    Once an examination begins, you must not leave the window or switch browser tabs. All activities are monitored and logged for
                    integrity assurance.
                </p>
            </div>
        </div>

        <ConfirmationModal
            :show="isStartModalOpen"
            title="Begin Examination?"
            message="You are about to start this assessment. The timer will begin immediately once the interface loads. Ensure you have a stable internet connection."
            confirm-label="Start Now"
            variant="primary"
            @close="isStartModalOpen = false"
            @confirm="handleStartExam"
        />
    </StudentLayout>
</template>
