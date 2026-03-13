<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineProps<{
    stats: {
        totalStudents: number;
        totalStaff: number;
        totalClasses: number;
        totalSubjects: number;
        totalQuestions: number;
        totalExams: number;
    };
}>();

const iconClasses = "size-5 text-gray-400";
</script>

<template>
    <AdminLayout>
        <Head title="System Overview" />

        <div class="space-y-10">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">System Overview</h1>
                    <p class="text-sm text-gray-500 mt-1">Global metrics and quick access across the academic network.</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Students Card -->
                <div class="flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl p-4 md:p-5">
                    <div class="flex items-center gap-x-2">
                        <p class="text-xs uppercase tracking-wide text-gray-500 font-medium">Total Students</p>
                    </div>
                    <div class="mt-1 flex items-center gap-x-2">
                        <h3 class="text-xl sm:text-2xl font-medium text-gray-800">{{ stats.totalStudents }}</h3>
                    </div>
                </div>

                <!-- Staff Card -->
                <div class="flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl p-4 md:p-5">
                    <div class="flex items-center gap-x-2">
                        <p class="text-xs uppercase tracking-wide text-gray-500 font-medium">Verified Personnel</p>
                    </div>
                    <div class="mt-1 flex items-center gap-x-2">
                        <h3 class="text-xl sm:text-2xl font-medium text-gray-800">{{ stats.totalStaff }}</h3>
                    </div>
                </div>

                <!-- Exams Card -->
                <div class="flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl p-4 md:p-5">
                    <div class="flex items-center gap-x-2">
                        <p class="text-xs uppercase tracking-wide text-gray-500 font-medium">Assessments Drafted</p>
                    </div>
                    <div class="mt-1 flex items-center gap-x-2">
                        <h3 class="text-xl sm:text-2xl font-medium text-gray-800">{{ stats.totalExams }}</h3>
                    </div>
                </div>
            </div>

            <!-- Management Shortcuts -->
            <div class="space-y-4 border-t border-gray-200 pt-8">
                <h2 class="text-sm font-semibold text-gray-800 px-1">Infrastructure Control</h2>
                <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <Link href="/admin/users/students" class="group flex flex-col bg-white border border-gray-200 hover:border-primary/30 shadow-sm rounded-xl p-5 transition-all hover:shadow-md">
                        <div class="size-10 flex justify-center items-center bg-gray-50 rounded-lg group-hover:bg-primary/5 transition-colors">
                            <svg class="size-5 text-gray-400 group-hover:text-primary transition-colors" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-gray-800">Student Directory</h3>
                            <p class="mt-1 text-xs text-gray-500">Manage enrollment and academic profiles.</p>
                        </div>
                    </Link>

                    <Link href="/admin/users/staff" class="group flex flex-col bg-white border border-gray-200 hover:border-primary/30 shadow-sm rounded-xl p-5 transition-all hover:shadow-md">
                        <div class="size-10 flex justify-center items-center bg-gray-50 rounded-lg group-hover:bg-primary/5 transition-colors">
                            <svg class="size-5 text-gray-400 group-hover:text-primary transition-colors" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M17 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-gray-800">Staff Management</h3>
                            <p class="mt-1 text-xs text-gray-500">Control authorization and faculty access.</p>
                        </div>
                    </Link>

                    <Link href="/admin/school-setup/sessions" class="group flex flex-col bg-white border border-gray-200 hover:border-primary/30 shadow-sm rounded-xl p-5 transition-all hover:shadow-md">
                        <div class="size-10 flex justify-center items-center bg-gray-50 rounded-lg group-hover:bg-primary/5 transition-colors">
                            <svg class="size-5 text-gray-400 group-hover:text-primary transition-colors" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        </div>
                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-gray-800">Academic Sessions</h3>
                            <p class="mt-1 text-xs text-gray-500">Configure terms and active years.</p>
                        </div>
                    </Link>

                    <Link href="/staff/questions" class="group flex flex-col bg-white border border-gray-200 hover:border-primary/30 shadow-sm rounded-xl p-5 transition-all hover:shadow-md">
                        <div class="size-10 flex justify-center items-center bg-gray-50 rounded-lg group-hover:bg-primary/5 transition-colors">
                            <svg class="size-5 text-gray-400 group-hover:text-primary transition-colors" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7V3"/><path d="M3.3 11l2.8 2.8"/><path d="M17.9 13.8l2.8-2.8"/><path d="M12 21v-4"/><path d="M6.1 17.9l2.8-2.8"/><path d="M15.1 15.1l2.8 2.8"/><circle cx="12" cy="12" r="3"/></svg>
                        </div>
                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-gray-800">Question Lab</h3>
                            <p class="mt-1 text-xs text-gray-500">Repository access and AI generation.</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
