<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/Staff/StudentController';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { PaginatedData, SchoolClass } from '@/types/academics';

interface StudentUser {
    id: string;
    name: string;
    email: string;
    username: string;
    school_id: string | null;
    school_class_id: string | null;
    school_class?: SchoolClass;
}

const props = defineProps<{
    students: PaginatedData<StudentUser>;
    classes: SchoolClass[];
    filters: {
        search?: string;
        school_class_id?: string;
    };
}>();

// Filters
const filterForm = useForm({
    search: props.filters.search || '',
    school_class_id: props.filters.school_class_id || '',
});

const applyFilters = () => {
    router.get(index().url, filterForm.data(), { preserveState: true });
};
</script>

<template>
    <StaffLayout>
        <Head title="My Students" />

        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">My Students</h1>
                    <p class="mt-1 text-sm text-gray-500">Students in your assigned classes • {{ students.total }} Records</p>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <!-- Search & Filters Container -->
                <div class="border-b border-gray-200 p-4 md:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </span>
                            <input
                                v-model="filterForm.search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Search by name, email, or admission ID..."
                                class="block w-full rounded-lg border-gray-200 bg-gray-50 px-4 py-2 pl-11 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <select
                                v-model="filterForm.school_class_id"
                                @change="applyFilters"
                                class="block w-full rounded-lg border-gray-200 bg-gray-50 px-4 py-2 pr-9 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50 sm:w-auto"
                            >
                                <option value="">All My Classes</option>
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>

                            <button
                                @click="applyFilters"
                                class="inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50"
                            >
                                Filter
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                    Student Profile
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Class</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                    Admission ID
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="user in students.data" :key="user.id" class="transition-colors hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xs font-semibold text-gray-500"
                                        >
                                            {{ user.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-800">{{ user.name }}</div>
                                            <div class="text-xs text-gray-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        v-if="user.school_class"
                                        class="inline-flex items-center gap-x-1.5 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800"
                                    >
                                        {{ user.school_class.name }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">Unassigned</span>
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                    {{ user.school_id || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-2.5 py-1 text-xs font-medium text-teal-800"
                                    >
                                        <span class="inline-block size-1.5 rounded-full bg-teal-800"></span>
                                        Active
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="students.data.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <p class="text-sm text-gray-500">No students found in your assigned classes.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="grid gap-3 border-t border-gray-200 px-6 py-4 md:flex md:items-center md:justify-between">
                    <div>
                        <p class="text-sm text-gray-600">
                            Showing <span class="font-semibold text-gray-800">{{ students.from }}</span> to
                            <span class="font-semibold text-gray-800">{{ students.to }}</span> of
                            <span class="font-semibold text-gray-800">{{ students.total }}</span> results
                        </p>
                    </div>

                    <div>
                        <div class="inline-flex gap-x-2">
                            <button
                                v-for="link in students.links"
                                :key="link.label"
                                @click="router.get(link.url || '#', filterForm.data(), { preserveState: true })"
                                class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 disabled:pointer-events-none disabled:opacity-50"
                                :class="[link.active ? 'bg-gray-100' : '', !link.url && 'cursor-not-allowed opacity-50']"
                            >
                                <span v-html="link.label" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
