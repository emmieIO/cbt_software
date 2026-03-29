<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { PaginatedData } from '@/types/academics';

interface Exam {
    id: string;
    title: string;
    branch: string;
    subject: { name: string } | null;
    school_class?: { name: string };
    attempts_count: number;
    type: string;
}

const props = defineProps<{
    exams: PaginatedData<Exam>;
    filters: {
        branch?: string;
    };
}>();

const page = usePage();
const branches = computed(() => (page.props as any).branches || {});
const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

// Filters
const branchFilter = ref(props.filters.branch || '');
const applyFilters = () => {
    router.get(page.url, { branch: branchFilter.value }, { preserveState: true });
};
</script>

<template>
    <component :is="Layout">
        <Head title="Examination Results" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-800">Results Dashboard</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Results & Analytics</h1>
                    <p class="mt-1 text-sm text-gray-500">Select an examination to review student performance • {{ exams.data.length }} Records</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="w-full sm:max-w-xs">
                    <label class="sr-only">Filter by Branch</label>
                    <select
                        v-model="branchFilter"
                        @change="applyFilters"
                        class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                    >
                        <option value="">All My Branches</option>
                        <option v-for="(info, key) in branches" :key="key" :value="key">{{ info.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Results Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="inline-block min-w-full p-1.5 align-middle">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Exam Details</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Class/Level</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Submissions</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="exam in exams.data" :key="exam.id" class="transition-colors hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-semibold text-gray-800">{{ exam.title }}</span>
                                                <div class="mt-0.5 flex items-center gap-x-2">
                                                    <span v-if="branches[exam.branch]" class="text-xs font-medium text-primary">
                                                        {{ branches[exam.branch].name }}
                                                    </span>
                                                    <span class="text-xs text-gray-400">•</span>
                                                    <span class="text-xs text-gray-500">
                                                        {{ exam.subject?.name || 'Multi-Subject' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-800">
                                                {{ exam.school_class?.name || 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span
                                                class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-800"
                                            >
                                                {{ exam.attempts_count }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <Link
                                                :href="`/staff/exams/${exam.id}/results`"
                                                class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-3 py-2 text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none"
                                            >
                                                View Results
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="exams.data.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="mb-3 size-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                                    />
                                                </svg>
                                                <p class="text-sm">No examinations found</p>
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
