<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{
    availableExams: Array<{
        id: string;
        title: string;
        duration: number;
        subject: { name: string };
    }>;
}>();

const startExam = (examId: string) => {
    if (confirm('Are you sure you want to start this exam? The timer will begin immediately.')) {
        router.post(`/student/exams/${examId}/start`);
    }
};
</script>

<template>
    <StudentLayout>
        <Head title="Student Dashboard" />

        <div class="p-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Stats Card -->
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Available Exams</p>
                    <p class="mt-2 text-3xl font-bold text-primary">{{ availableExams.length }}</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Completed</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800">--</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Avg. Score</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800">--%</p>
                </div>
            </div>

            <!-- Active Exams -->
            <div class="mt-8 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                    <h3 class="font-bold text-slate-800">Ongoing Exams</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div v-if="availableExams.length === 0" class="py-12 text-center text-slate-400 italic">
                        No live exams available for your class at this moment.
                    </div>
                    
                    <div 
                        v-for="exam in availableExams" 
                        :key="exam.id"
                        class="flex items-center justify-between rounded-xl border border-primary/20 bg-primary/5 p-4"
                    >
                        <div class="flex items-center space-x-4">
                            <div class="rounded-lg bg-primary p-3 text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900">{{ exam.title }}</h4>
                                <p class="text-sm text-slate-500">{{ exam.subject.name }} • {{ exam.duration }} minutes</p>
                            </div>
                        </div>
                        <button 
                            @click="startExam(exam.id)"
                            class="rounded-lg bg-primary px-6 py-2 font-bold text-white transition-transform hover:scale-105 active:scale-95"
                        >
                            Start Exam
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
