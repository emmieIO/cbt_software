<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { index as examIndex, results as resultsIndex } from '@/actions/App/Http/Controllers/Student/StudentController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';

const page = usePage();
const userName = computed(() => page.props.auth.user.name);

const props = defineProps<{
    availableExams: Array<{
        id: string;
        title: string;
        duration: number;
        subject: { name: string };
        questions_count: number;
        attempts: Array<{ id: string; status: string }>;
    }>;
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
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Student Hub</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400">Your examination command center</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="h-3 w-3 rounded-full bg-green-500 animate-pulse"></div>
                    <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">System Active</span>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="relative overflow-hidden rounded-xl bg-slate-900 px-12 py-16 text-white shadow-2xl">
                <div class="relative z-10 max-w-2xl">
                    <h1 class="text-5xl font-black tracking-tighter italic">Hello, {{ userName }}!</h1>
                    <p class="mt-4 text-xl font-medium text-slate-400 leading-relaxed">
                        Ready for your next challenge? Below are the live examinations currently available for your academic level.
                    </p>
                </div>
                <!-- Abstract Design -->
                <div class="absolute -top-24 -right-24 h-96 w-96 rounded-lg-full bg-primary/10 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 h-64 w-64 rounded-lg-full bg-lemon-yellow/5 blur-2xl"></div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <Link 
                    :href="examIndex().url"
                    class="rounded-xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-lg hover:border-primary/20 group"
                >
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase group-hover:text-primary transition-colors">Live Assessments</p>
                    <p class="mt-4 text-4xl font-black tracking-tighter text-primary">{{ availableExams.length }}</p>
                </Link>
                <Link 
                    :href="resultsIndex().url"
                    class="rounded-xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-lg hover:border-primary/20 group"
                >
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase group-hover:text-primary transition-colors">Completed Tests</p>
                    <p class="mt-4 text-4xl font-black tracking-tighter text-slate-800">--</p>
                </Link>
                <div class="rounded-xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-lg opacity-50 grayscale">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Global Rank</p>
                    <p class="mt-4 text-4xl font-black tracking-tighter text-slate-800">#--</p>
                </div>
            </div>

            <!-- Exam List -->
            <div class="space-y-6">
                <div class="flex items-center justify-between ml-2">
                    <h3 class="flex items-center gap-3 text-sm font-black tracking-[0.2em] text-slate-400 uppercase">
                        <div class="h-2 w-2 rounded-full bg-primary animate-ping"></div>
                        Ongoing Examinations
                    </h3>
                    <Link :href="examIndex().url" class="text-[10px] font-black tracking-widest text-primary uppercase hover:underline">View All Assessments &rarr;</Link>
                </div>

                <div v-if="availableExams.length === 0" class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white py-20 text-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-slate-400 uppercase tracking-widest italic">No active examinations at this hour.</p>
                </div>

                <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div 
                        v-for="exam in availableExams" 
                        :key="exam.id"
                        class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:border-primary/20 hover:shadow-2xl hover:-translate-y-1"
                    >
                        <div class="relative z-10 flex items-center justify-between">
                            <div class="flex items-center gap-6">
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/5 text-primary transition-transform group-hover:scale-110 group-hover:rotate-3">
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
                                        <span class="h-1 w-1 rounded-full bg-slate-200"></span>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ exam.questions_count }} QUESTIONS</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="exam.attempts.some(a => a.status === 'submitted')" class="flex items-center gap-2 rounded-xl bg-green-50 px-6 py-3 text-[10px] font-black tracking-widest text-green-600 uppercase border border-green-100">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                                Completed
                            </div>
                            <button 
                                v-else
                                @click="confirmStartExam(exam.id)"
                                class="rounded-xl bg-slate-900 px-6 py-3 text-[10px] font-black tracking-widest text-white uppercase shadow-lg transition-all hover:bg-black active:scale-95"
                            >
                                {{ exam.attempts.some(a => a.status === 'ongoing') ? 'Continue Exam' : 'Start Assessment' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pro Tip / Security Info -->
            <div class="rounded-2xl border-2 border-dashed border-primary/20 bg-primary/5 p-10 text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-white shadow-lg shadow-primary/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h4 class="text-sm font-black uppercase tracking-widest text-primary">Security Reminder</h4>
                <p class="mt-2 text-xs font-bold leading-relaxed text-slate-500 max-w-lg mx-auto">
                    Once an examination begins, you must not leave the window or switch browser tabs. All activities are monitored and logged for integrity assurance.
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
