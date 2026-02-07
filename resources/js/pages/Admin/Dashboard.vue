<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
</script>

<template>
    <AdminLayout>
        <Head title="Admin Dashboard" />

        <div class="p-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                <!-- Stats Card -->
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Total Students</p>
                    <p class="mt-2 text-3xl font-bold text-primary">1,240</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Total Staff</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800">86</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Active Exams</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">4</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">System Status</p>
                    <p class="mt-2 text-3xl font-bold text-blue-600">Healthy</p>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Recent Activity -->
                <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                        <h3 class="font-bold text-slate-800">Recent System Logs</h3>
                    </div>
                    <div class="divide-y divide-slate-50 p-6">
                        <div class="py-3">
                            <p class="text-sm text-slate-600"><span class="font-bold">admin_root</span> updated Mathematics exam settings</p>
                            <p class="text-xs text-slate-400">2 minutes ago</p>
                        </div>
                        <div class="py-3">
                            <p class="text-sm text-slate-600"><span class="font-bold">staff_user</span> uploaded 50 new questions to English Bank</p>
                            <p class="text-xs text-slate-400">15 minutes ago</p>
                        </div>
                        <div class="py-3">
                            <p class="text-sm text-slate-600">Automated backup completed successfully</p>
                            <p class="text-xs text-slate-400">1 hour ago</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                        <h3 class="font-bold text-slate-800">Quick Actions</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-4 p-6">
                        <button class="rounded-xl bg-slate-50 p-4 text-center transition-colors hover:bg-slate-100">
                            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            <span class="text-sm font-bold text-slate-700">Add Student</span>
                        </button>
                        <button class="rounded-xl bg-slate-50 p-4 text-center transition-colors hover:bg-slate-100">
                            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 text-green-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>
                            <span class="text-sm font-bold text-slate-700">System Report</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
