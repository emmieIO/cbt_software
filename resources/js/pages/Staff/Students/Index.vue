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

        <div class="space-y-10">
            <!-- Page Header -->
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-slate-900 italic">My Students</h1>
                    <p class="mt-1 text-[10px] md:text-sm font-bold tracking-widest text-slate-400 uppercase">
                        Students in your assigned classes • {{ students.total }} Records
                    </p>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
                <!-- Search & Filters Container -->
                <div class="border-b border-slate-50 bg-white p-4 md:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                class="h-11 md:h-12 w-full rounded-xl border-none bg-slate-50 pl-11 md:pl-12 text-sm font-bold text-slate-700 transition-all focus:bg-white focus:ring-2 focus:ring-primary/10"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex h-11 md:h-12 flex-1 items-center gap-2 rounded-xl border border-slate-100 bg-slate-50 px-3 md:px-4 sm:flex-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                    />
                                </svg>
                                <select
                                    v-model="filterForm.school_class_id"
                                    @change="applyFilters"
                                    class="cursor-pointer border-none bg-transparent text-[10px] md:text-xs font-black text-slate-600 uppercase focus:ring-0"
                                >
                                    <option value="">All My Classes</option>
                                    <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>

                            <button
                                @click="applyFilters"
                                class="h-11 md:h-12 rounded-xl bg-slate-900 px-6 md:px-8 text-[10px] md:text-xs font-black tracking-widest text-white uppercase transition-all hover:bg-black active:scale-95"
                            >
                                Filter
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto scrollbar-thin">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-4 md:px-8 py-4 md:py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase whitespace-nowrap">Student Profile</th>
                                <th class="px-4 md:px-6 py-4 md:py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase whitespace-nowrap">Class</th>
                                <th class="px-4 md:px-6 py-4 md:py-5 text-[10px] font-black tracking-widest whitespace-nowrap text-slate-400 uppercase whitespace-nowrap">
                                    Admission ID
                                </th>
                                <th class="px-4 md:px-8 py-4 md:py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase whitespace-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="user in students.data" :key="user.id" class="group transition-all hover:bg-[#F8F9FB]">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-50 text-xs font-black text-slate-400 transition-colors group-hover:bg-primary/5 group-hover:text-primary"
                                        >
                                            {{ user.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm leading-none font-black text-slate-800">{{ user.name }}</h4>
                                            <p class="mt-1 text-xs font-bold text-slate-400">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span
                                        v-if="user.school_class"
                                        class="inline-flex items-center rounded-lg border border-blue-100 bg-blue-50 px-3 py-1 text-[9px] font-black text-blue-600 uppercase shadow-sm"
                                    >
                                        {{ user.school_class.name }}
                                    </span>
                                    <span v-else class="text-[9px] font-black tracking-widest text-slate-300 uppercase">Unassigned</span>
                                </td>
                                <td class="px-6 py-6 text-xs font-bold tracking-tighter whitespace-nowrap text-slate-500 uppercase">
                                    {{ user.school_id || 'N/A' }}
                                </td>
                                <td class="px-8 py-6 text-right whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-green-50 px-3 py-1 text-[9px] font-black text-green-600 uppercase shadow-sm border border-green-100">
                                        <div class="h-1 w-1 rounded-full bg-green-500"></div>
                                        Active
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="students.data.length === 0">
                                <td colspan="4" class="px-8 py-24 text-center">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-300 mb-4">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold tracking-widest text-slate-400 uppercase italic">No students found in your assigned classes.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between border-t border-slate-50 bg-white px-8 py-6">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase italic">
                        Page {{ students.current_page }} • Results {{ students.from }}-{{ students.to }} of {{ students.total }}
                    </p>
                    <div class="flex gap-2">
                        <button
                            v-for="link in students.links"
                            :key="link.label"
                            @click="router.get(link.url || '#', filterForm.data(), { preserveState: true })"
                            class="flex h-10 min-w-10 items-center justify-center rounded-lg text-xs font-black transition-all"
                            :class="[
                                link.active ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-slate-50 text-slate-600 hover:bg-slate-100',
                                !link.url && 'pointer-events-none cursor-not-allowed opacity-30',
                            ]"
                        >
                            <span v-html="link.label" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
