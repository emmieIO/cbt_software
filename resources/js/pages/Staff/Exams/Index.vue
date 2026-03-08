<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    create as createExamAction,
    show as showExamAction,
    edit as editExamAction,
} from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { PaginatedData } from '@/types/academics';

interface Exam {
    id: string;
    title: string;
    subject?: { name: string };
    school_class?: { name: string };
    prospective_class?: { name: string };
    academic_session: { name: string };
    status: string;
    type: string;
    branch: string;
    duration: number;
    questions_count: number;
    start_time: string | null;
}

const props = defineProps<{
    exams: PaginatedData<Exam>;
    filters: {
        status?: string;
        type?: string;
        branch?: string;
    };
}>();

const page = usePage();
const branches = computed(() => (page.props as any).branches || {});
const isAdmin = computed(() => (page.props.auth.user as any).roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

// Filters
const branchFilter = ref(props.filters.branch || '');
const applyFilters = () => {
    router.get(router.page.url, { 
        ...props.filters,
        branch: branchFilter.value 
    }, { preserveState: true });
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'live':
            return 'bg-green-500 text-white';
        case 'scheduled':
            return 'bg-blue-500 text-white';
        case 'closed':
            return 'bg-slate-500 text-white';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};
</script>

<template>
    <component :is="Layout">
        <Head title="My Examinations" />

        <div class="space-y-8">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="text-slate-500 transition-colors hover:text-slate-800">Dashboard</Link>
                <svg class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                <span class="text-slate-900">Examination Vault</span>
            </nav>

            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="group flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white transition-all hover:border-slate-900 hover:text-slate-900 active:scale-95">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                        </Link>
                        <h2 class="text-2xl md:text-3xl font-black tracking-tight text-slate-900 italic">Examination Vault</h2>
                    </div>
                    <p class="mt-2 text-xs md:text-sm font-bold text-slate-500 uppercase tracking-widest">Manage papers and student schedules.</p>
                </div>
                <Link
                    :href="createExamAction().url"
                    class="flex h-12 items-center justify-center gap-3 rounded-xl bg-primary px-6 text-xs md:text-sm font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 sm:w-auto"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    New Examination
                </Link>
            </div>

            <!-- Branch Filter -->
            <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                <div class="flex h-10 items-center gap-2 rounded-lg border border-slate-100 bg-slate-50 px-4">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <select 
                        v-model="branchFilter" 
                        @change="applyFilters"
                        class="cursor-pointer border-none bg-transparent text-[10px] font-black text-slate-600 uppercase focus:ring-0"
                    >
                        <option value="">All My Branches</option>
                        <option v-for="(info, key) in branches" :key="key" :value="key">{{ info.name }}</option>
                    </select>
                </div>
                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase italic">Filter vault by school</span>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="exam in exams.data"
                    :key="exam.id"
                    class="group relative overflow-hidden rounded-xl border border-slate-100 bg-white p-6 md:p-8 shadow-sm transition-all hover:shadow-xl"
                >
                    <div class="relative z-10">
                        <div class="mb-4 md:mb-6 flex items-start justify-between">
                            <span
                                class="rounded-lg-full px-2 py-0.5 md:px-3 md:py-1 text-[8px] md:text-[9px] font-black tracking-widest text-slate-900 uppercase"
                                :class="getStatusColor(exam.status)"
                            >
                                {{ exam.status }}
                            </span>
                            <span class="text-[9px] md:text-[10px] font-black tracking-tighter text-slate-300 uppercase">{{ exam.type }}</span>
                        </div>

                        <h3 class="mb-2 line-clamp-1 text-lg md:text-xl font-black text-slate-800">{{ exam.title }}</h3>
                        <div class="mb-4 md:mb-6 flex flex-wrap items-center gap-2">
                            <div v-if="branches[exam.branch]" class="inline-flex items-center rounded-lg border border-primary/10 bg-primary/5 px-2 py-0.5 text-[8px] font-black text-primary uppercase">
                                {{ branches[exam.branch].name }}
                            </div>
                            <span class="rounded-xl bg-slate-50 px-2 py-0.5 md:py-1 text-[9px] md:text-[10px] font-black text-slate-500 uppercase border border-slate-100">
                                {{ exam.subject?.name || 'Multi-Subject' }}
                            </span>
                            <div class="hidden sm:block rounded-lg-full h-1 w-1 bg-slate-200"></div>
                            <span class="text-[9px] md:text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                                {{ exam.type === 'entrance' ? (exam.prospective_class?.name || 'All Candidates') : (exam.school_class?.name || 'General') }}
                            </span>
                        </div>

                        <div class="mb-6 md:mb-8 grid grid-cols-2 gap-3 md:gap-4">
                            <div class="rounded-xl bg-slate-50 p-3 md:p-4">
                                <span class="mb-1 block text-[8px] md:text-[9px] font-black tracking-widest text-slate-400 uppercase">Time</span>
                                <span class="text-[10px] md:text-xs font-bold text-slate-700">{{ exam.duration }} Mins</span>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 md:p-4">
                                <span class="mb-1 block text-[8px] md:text-[9px] font-black tracking-widest text-slate-400 uppercase">Pool</span>
                                <span class="text-[10px] md:text-xs font-bold text-slate-700">{{ exam.questions_count }} Items</span>
                            </div>
                        </div>

                        <div class="flex gap-2 md:gap-3">
                            <Link
                                :href="editExamAction(exam.id).url"
                                class="flex h-12 w-12 md:h-14 md:w-14 shrink-0 items-center justify-center rounded-xl border-2 border-slate-100 text-slate-400 transition-all hover:border-primary hover:text-primary active:scale-90"
                            >
                                <svg class="h-4 w-4 md:h-5 md:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </Link>
                            <Link
                                :href="showExamAction(exam.id).url"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl border-2 border-slate-100 py-3 md:py-4 text-[10px] md:text-xs font-black tracking-widest text-slate-600 uppercase transition-all hover:border-primary hover:text-primary"
                            >
                                Configure
                                <svg class="h-3 w-3 md:h-4 md:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="exams.data.length === 0" class="col-span-full flex flex-col items-center justify-center py-12 md:py-20 opacity-30">
                    <svg class="mb-4 h-16 w-16 md:h-20 md:w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <p class="text-base md:text-lg font-bold tracking-widest uppercase">No Examinations Drafted</p>
                </div>
            </div>
        </div>
    </component>
</template>
