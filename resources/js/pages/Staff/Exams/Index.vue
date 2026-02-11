<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    index as examsIndex, 
    create as createExamAction,
    show as showExamAction
} from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { PaginatedData } from '@/types/academics';

interface Exam {
    id: string;
    title: string;
    subject: { name: string };
    school_class?: { name: string };
    prospective_class?: { name: string };
    academic_session: { name: string };
    status: string;
    type: string;
    duration: number;
    questions_count: number;
    start_time: string | null;
}

defineProps<{
    exams: PaginatedData<Exam>;
    filters: any;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const getStatusColor = (status: string) => {
    switch (status) {
        case 'live': return 'bg-green-500 text-white';
        case 'scheduled': return 'bg-blue-500 text-white';
        case 'closed': return 'bg-slate-500 text-white';
        default: return 'bg-slate-100 text-slate-600';
    }
};
</script>

<template>
    <component :is="Layout">
        <Head title="My Examinations" />

        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">Examination Vault</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">Manage your papers and student schedules.</p>
                </div>
                <Link
                    :href="createExamAction().url"
                    class="flex h-12 items-center gap-3 rounded-xl bg-primary px-6 text-sm font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    New Examination
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div 
                    v-for="exam in exams.data" 
                    :key="exam.id"
                    class="group relative overflow-hidden rounded-xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-xl"
                >
                    <div class="relative z-10">
                        <div class="mb-6 flex items-start justify-between">
                            <span 
                                class="rounded-lg-full px-3 py-1 text-[9px] font-black tracking-widest uppercase text-slate-900"
                                :class="getStatusColor(exam.status)"
                            >
                                {{ exam.status }}
                            </span>
                            <span class="text-[10px] font-black text-slate-300 uppercase tracking-tighter">{{ exam.type }}</span>
                        </div>

                        <h3 class="mb-2 text-xl font-black text-slate-800 line-clamp-1">{{ exam.title }}</h3>
                        <div class="mb-6 flex items-center gap-2">
                            <span class="rounded-xl bg-primary/5 px-2 py-1 text-[10px] font-black text-primary uppercase">{{ exam.subject.name }}</span>
                            <div class="h-1 w-1 rounded-lg-full bg-slate-200"></div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                {{ exam.type === 'entrance' ? exam.prospective_class?.name : exam.school_class?.name }}
                            </span>
                        </div>

                        <div class="mb-8 grid grid-cols-2 gap-4">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Time</span>
                                <span class="text-xs font-bold text-slate-700">{{ exam.duration }} Mins</span>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Pool</span>
                                <span class="text-xs font-bold text-slate-700">{{ exam.questions_count }} Questions</span>
                            </div>
                        </div>

                        <Link 
                            :href="showExamAction(exam.id).url"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-slate-100 py-4 text-xs font-black tracking-widest text-slate-600 uppercase transition-all hover:border-primary hover:text-primary"
                        >
                            Configure Paper
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="exams.data.length === 0" class="col-span-full flex flex-col items-center justify-center py-20 opacity-30">
                    <svg class="mb-4 h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-lg font-bold uppercase tracking-widest">No Examinations Drafted</p>
                </div>
            </div>
        </div>
    </component>
</template>
