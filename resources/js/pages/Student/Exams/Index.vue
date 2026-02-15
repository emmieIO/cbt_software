<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';

interface Exam {
    id: string;
    title: string;
    duration: number;
    subject: { name: string };
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

        <div class="space-y-10">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Available Assessments</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400 uppercase tracking-widest italic">All assessments for your current academic level</p>
                </div>
            </div>

            <!-- Exam Grid -->
            <div v-if="exams.length === 0" class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white py-24 text-center">
                <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-400">No active assessments found.</h3>
                <p class="mt-2 text-sm font-bold text-slate-400 uppercase tracking-widest">Please check back later or contact the admin.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div 
                    v-for="exam in exams" 
                    :key="exam.id"
                    class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:border-primary/20 hover:shadow-2xl hover:-translate-y-1"
                >
                    <div class="relative z-10 flex flex-col justify-between h-full">
                        <div class="flex items-center gap-6 mb-8">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/5 text-primary transition-transform group-hover:scale-110 group-hover:rotate-3">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-slate-800 leading-tight group-hover:text-primary transition-colors">{{ exam.title }}</h4>
                                <div class="mt-2 flex items-center gap-3">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ exam.subject.name }}</span>
                                    <span class="h-1 w-1 rounded-full bg-slate-200"></span>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-primary">{{ exam.duration }} MINS</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between border-t border-slate-50 pt-6">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ exam.questions_count }} TOTAL QUESTIONS</span>
                            
                            <div v-if="exam.attempts.some(a => a.status === 'submitted')" class="flex items-center gap-2 rounded-xl bg-green-50 px-6 py-3 text-[10px] font-black tracking-widest text-green-600 uppercase border border-green-100">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                                Completed
                            </div>
                            <button 
                                v-else
                                @click="confirmStartExam(exam.id)"
                                class="rounded-xl bg-slate-900 px-8 py-3 text-[10px] font-black tracking-widest text-white uppercase shadow-lg transition-all hover:bg-black active:scale-95"
                            >
                                {{ exam.attempts.some(a => a.status === 'ongoing') ? 'Continue Exam' : 'Start Assessment' }}
                            </button>
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
