<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StaffLayout from '@/layouts/StaffLayout.vue';
</script>

<template>
    <StaffLayout>
        <Head title="Staff Dashboard" />

        <div class="p-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Stats Card -->
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Assigned Classes</p>
                    <p class="mt-2 text-3xl font-bold text-primary">6</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Pending Results</p>
                    <p class="mt-2 text-3xl font-bold text-orange-600">3</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Active Invigilations</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800">1</p>
                </div>
            </div>

            <!-- Recent Activity Placeholder -->
            <div class="mt-8 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                    <h3 class="font-bold text-slate-800">Upcoming Exams Invigilation</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 p-4">
                        <div class="flex items-center space-x-4">
                            <div class="rounded-lg bg-slate-100 p-3 text-slate-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900">Physics - SS2 Blue</h4>
                                <p class="text-sm text-slate-500">Today, 2:00 PM • Hall A</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 uppercase">Upcoming</span>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>