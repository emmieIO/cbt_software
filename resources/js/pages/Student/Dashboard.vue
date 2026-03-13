<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/layouts/StudentLayout.vue';

interface Exam {
    id: string;
    title: string;
    start_time: string;
    duration: number;
    subject: { name: string };
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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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
                <p class="text-sm text-gray-500 mt-1">Review your upcoming assessments and recent academic performance.</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div class="flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl p-4 md:p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Assessments Completed</p>
                    <div class="mt-2 flex items-center gap-x-2">
                        <h3 class="text-2xl font-bold text-gray-800">{{ stats.examsTaken }}</h3>
                    </div>
                </div>

                <div class="flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl p-4 md:p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Average Performance</p>
                    <div class="mt-2 flex items-center gap-x-2">
                        <h3 class="text-2xl font-bold text-primary">{{ stats.averageScore }}%</h3>
                    </div>
                </div>

                <div class="flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl p-4 md:p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pending Schedule</p>
                    <div class="mt-2 flex items-center gap-x-2">
                        <h3 class="text-2xl font-bold text-orange-600">{{ stats.pendingExams }}</h3>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Upcoming Exams -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Immediate Schedule</h2>
                        <Link href="/student/exams" class="text-xs font-semibold text-primary hover:underline">View Full Calendar</Link>
                    </div>

                    <div class="space-y-3">
                        <div v-for="exam in upcomingExams" :key="exam.id" class="bg-white border border-gray-200 shadow-sm rounded-xl p-5 hover:border-primary/30 transition-all">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 py-1 px-2 rounded-md text-[10px] font-bold bg-primary/10 text-primary uppercase mb-2">
                                        {{ exam.subject.name }}
                                    </span>
                                    <h3 class="text-base font-bold text-gray-800">{{ exam.title }}</h3>
                                    <div class="mt-2 flex items-center gap-4 text-xs text-gray-500 font-medium">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ formatDate(exam.start_time) }}
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ exam.duration }} Mins
                                        </span>
                                    </div>
                                </div>
                                <Link :href="`/student/exams/${exam.id}`" class="py-2 px-4 text-xs font-bold rounded-lg bg-gray-900 text-white hover:bg-black transition-colors">
                                    Access Hall
                                </Link>
                            </div>
                        </div>

                        <div v-if="upcomingExams.length === 0" class="py-12 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <svg class="size-10 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <p class="text-sm font-medium text-gray-400">No examinations scheduled for you currently.</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Recent Performance</h2>
                        <Link href="/student/results" class="text-xs font-semibold text-primary hover:underline">Transcript Archive</Link>
                    </div>

                    <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">Assessment</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Score</th>
                                    <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="result in recentResults" :key="result.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-bold text-gray-800">{{ result.exam.title }}</span>
                                        <p class="text-xs text-gray-500">{{ result.exam.subject.name }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm font-bold" :class="result.score >= (result.exam.questions_count / 2) ? 'text-primary' : 'text-orange-600'">
                                            {{ result.score }} / {{ result.exam.questions_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-end">
                                        <span class="inline-flex items-center gap-1.5 py-1 px-2 rounded-md text-[10px] font-bold bg-teal-100 text-teal-800 uppercase">
                                            Released
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="recentResults.length === 0">
                                    <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-400">
                                        No results released yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
