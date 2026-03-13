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

// Filters
const schoolFilter = ref(props.filters.school_id || '');
const applyFilters = () => {
    router.get(page.url, { 
        ...props.filters,
        school_id: schoolFilter.value 
    }, { preserveState: true });
};

const getStatusClasses = (status: string) => {
    switch (status) {
        case 'live':
            return 'bg-teal-100 text-teal-800';
        case 'scheduled':
            return 'bg-blue-100 text-blue-800';
        case 'closed':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <component :is="Layout">
        <Head title="Examination Vault" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Examination Vault</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Examination Vault</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Manage papers and student schedules • {{ exams.data.length }} Records
                    </p>
                </div>
                <Link
                    :href="createExamAction().url"
                    class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    New Examination
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="w-full sm:max-w-xs">
                    <label class="sr-only">Filter by Branch</label>
                    <select 
                        v-model="schoolFilter" 
                        @change="applyFilters"
                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                    >
                        <option value="">All My Branches</option>
                        <option v-for="(info, key) in branches" :key="key" :value="key">{{ info.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Exams Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Exam Details</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Configuration</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="exam in exams.data" :key="exam.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-semibold text-gray-800">{{ exam.title }}</span>
                                                <div class="flex items-center gap-x-2 mt-0.5">
                                                    <span v-if="exam.school_id && branches[exam.school_id]" class="text-xs text-primary font-medium">
                                                        {{ branches[exam.school_id].name }}
                                                    </span>
                                                    <span v-if="exam.school_id" class="text-xs text-gray-400">•</span>
                                                    <span class="text-xs text-gray-500">
                                                        {{ exam.type === 'entrance' ? (exam.prospective_class?.name || 'All Candidates') : (exam.school_class?.name || 'General') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-xs text-gray-800"><span class="font-medium">Subject:</span> {{ exam.subject?.name || 'Multi-Subject' }}</span>
                                                <span class="text-xs text-gray-500 mt-0.5">{{ exam.duration }} Mins • {{ exam.questions_count }} Questions</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span 
                                                class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-md text-xs font-medium capitalize"
                                                :class="getStatusClasses(exam.status)"
                                            >
                                                {{ exam.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex justify-end items-center gap-x-2">
                                                <Link 
                                                    :href="editExamAction(exam.id).url" 
                                                    class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50"
                                                >
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                </Link>
                                                <Link 
                                                    :href="showExamAction(exam.id).url" 
                                                    class="py-1.5 px-3 inline-flex items-center gap-x-2 text-xs font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                                                >
                                                    Configure
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="exams.data.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="size-8 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                <p class="text-sm">No examinations drafted yet</p>
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
    </component>
</template>
