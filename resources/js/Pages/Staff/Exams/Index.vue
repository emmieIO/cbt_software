<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { create as createExamAction, show as showExamAction, edit as editExamAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { PaginatedData } from '@/types/academics';

interface Exam {
    id: string;
    title: string;
    subject?: { name: string };
    school_class?: { name: string };
    academic_session: { name: string };
    status: string;
    type: string;
    school_id: string | null;
    duration: number;
    questions_count: number;
    start_time: string | null;
}

const props = defineProps<{
    exams: PaginatedData<Exam>;
    filters: {
        status?: string;
        type?: string;
        school_id?: string;
    };
}>();

const page = usePage();
const branches = computed(() => (page.props as any).branches || {});
const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const schoolFilter = ref(props.filters.school_id || '');
const applyFilters = () => {
    router.get(
        page.url,
        {
            ...props.filters,
            school_id: schoolFilter.value,
        },
        { preserveState: true },
    );
};

const formatExamType = (type: string) => {
    return type
        .split('_')
        .join(' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const formatStartTime = (value: string | null) => {
    if (!value) return 'Not scheduled';

    try {
        return new Date(value).toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return 'Not scheduled';
    }
};

const getStatusClasses = (status: string) => {
    switch (status) {
        case 'live':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'scheduled':
            return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'closed':
            return 'bg-rose-100 text-rose-700 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};

const visibleExamCount = computed(() => props.exams.data.length);
const totalQuestionCount = computed(() => props.exams.data.reduce((total, exam) => total + exam.questions_count, 0));
const activeExamCount = computed(() => props.exams.data.filter((exam) => exam.status === 'live').length);
const scopedLabel = computed(() => {
    if (!schoolFilter.value || !branches.value[schoolFilter.value]) {
        return 'All My Branches';
    }

    return branches.value[schoolFilter.value].name;
});
</script>

<template>
    <component :is="Layout">
        <Head title="Examination Vault" />

        <div class="mx-auto max-w-7xl space-y-6 pb-12 sm:space-y-8">
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500">
                <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-900">Examination Vault</span>
            </nav>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-widest text-slate-500 uppercase">Assessment Control Center</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">Examination Vault</h1>
                    <p class="mt-1 text-sm text-slate-600">Track scheduling, branch coverage, and exam readiness from one operational view.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                        Scope: {{ scopedLabel }}
                    </div>
                    <Link
                        :href="createExamAction().url"
                        class="inline-flex items-center gap-2 rounded-lg border border-transparent bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Examination
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Visible Exams</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ visibleExamCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Within selected scope</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Live Assessments</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-700">{{ activeExamCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Currently active</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Active Branches</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ Object.keys(branches).length }}</p>
                    <p class="mt-1 text-xs text-slate-500">Mapped to your role</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Question Volume</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ totalQuestionCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Across visible exams</p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Vault Filters</p>
                        <p class="mt-1 text-xs text-slate-500">Limit the examination list by branch context.</p>
                    </div>
                    <div class="w-full sm:w-80">
                        <select
                            v-model="schoolFilter"
                            @change="applyFilters"
                            class="block w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                        >
                            <option value="">All My Branches</option>
                            <option v-for="(info, key) in branches" :key="key" :value="key">{{ info.name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-bold text-slate-900">Examination Register</h2>
                    <span class="text-xs font-semibold text-slate-500">{{ visibleExamCount }} records</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">Exam</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">Configuration</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">Schedule</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold tracking-wider text-slate-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="exam in exams.data" :key="exam.id" class="hover:bg-slate-50/80">
                                <td class="px-5 py-4">
                                    <p class="text-sm font-semibold text-slate-900">{{ exam.title }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        <span v-if="exam.school_id && branches[exam.school_id]" class="font-medium text-primary">{{ branches[exam.school_id].name }}</span>
                                        <span v-if="exam.school_id">•</span>
                                        <span>{{ exam.school_class?.name || 'General' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-xs text-slate-700"><span class="font-semibold">Subject:</span> {{ exam.subject?.name || 'Multi-Subject' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ formatExamType(exam.type) }} • {{ exam.duration }} mins • {{ exam.questions_count }} questions</p>
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-600">
                                    {{ formatStartTime(exam.start_time) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span :class="getStatusClasses(exam.status)" class="inline-flex rounded-md border px-2 py-1 text-[10px] font-bold uppercase">
                                        {{ exam.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <Link
                                            :href="editExamAction(exam.id).url"
                                            class="inline-flex size-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </Link>
                                        <Link
                                            :href="showExamAction(exam.id).url"
                                            class="inline-flex items-center rounded-lg border border-transparent bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary/90"
                                        >
                                            Configure
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="exams.data.length === 0">
                                <td colspan="5" class="px-6 py-14 text-center text-slate-500">
                                    <p class="text-sm font-semibold text-slate-700">No examinations found in this scope.</p>
                                    <p class="mt-1 text-xs text-slate-500">Adjust your branch filter or create a new examination to get started.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </component>
</template>
