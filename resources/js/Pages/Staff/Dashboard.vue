<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

defineProps<{
    stats: {
        assignedClasses: number;
        pendingResults: number;
        questionBankCount: number;
    };
    schedule: Array<{
        id: number;
        title: string;
        time: string;
        location: string;
        type: string;
        color: string;
    }>;
}>();

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name || 'Staff');
</script>

<template>
    <StaffLayout>
        <Head title="Staff Hub" />

        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Examiner Hub</h1>
                    <p class="mt-1 text-sm text-gray-500">Welcome back, {{ userName }}</p>
                </div>
            </div>

            <!-- Welcome Hero Section -->
            <div class="relative overflow-hidden rounded-xl bg-primary p-6 text-white shadow-sm md:p-10">
                <div class="relative z-10 max-w-2xl">
                    <h1 class="text-2xl font-semibold md:text-3xl">Welcome, {{ userName }}</h1>
                    <p class="mt-3 text-sm leading-relaxed text-white/80 md:text-base">
                        Your academic command center is ready. Manage your classes, build intelligent questions, and monitor performance.
                    </p>
                </div>
                <!-- Abstract Design -->
                <div class="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute right-0 bottom-0 h-48 w-48 rounded-full bg-white/5 blur-2xl"></div>
            </div>

            <!-- Performance Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:gap-6">
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:p-8">
                    <p class="text-xs font-semibold tracking-wider text-gray-400 uppercase">Assigned Classes</p>
                    <div class="mt-3 flex items-baseline gap-x-2">
                        <h3 class="text-3xl font-semibold text-gray-800 md:text-4xl">{{ stats.assignedClasses.toString().padStart(2, '0') }}</h3>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-medium text-teal-600">
                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-teal-500"></span>
                        Active this session
                    </div>
                </div>

                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:p-8">
                    <p class="text-xs font-semibold tracking-wider text-gray-400 uppercase">Pending Results</p>
                    <div class="mt-3 flex items-baseline gap-x-2">
                        <h3 class="text-3xl font-semibold text-orange-600 md:text-4xl">{{ stats.pendingResults.toString().padStart(2, '0') }}</h3>
                    </div>
                    <div class="mt-4 text-xs font-medium text-gray-500">Requires your attention</div>
                </div>

                <div class="flex flex-col rounded-xl border border-gray-800 bg-gray-900 p-6 shadow-sm md:p-8">
                    <p class="text-xs font-semibold tracking-wider text-gray-400 uppercase">Question Bank</p>
                    <div class="mt-3 flex items-baseline gap-x-2">
                        <h3 class="text-3xl font-semibold text-primary md:text-4xl">{{ stats.questionBankCount }}</h3>
                    </div>
                    <div class="mt-4 text-xs font-medium text-primary/80">Verified Repository</div>
                </div>
            </div>

            <!-- Schedule -->
            <div class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-6 md:p-8">
                    <h3 class="text-lg font-semibold text-gray-800">Upcoming Schedule</h3>
                    <p class="text-sm text-gray-500">Planned exams and assessments</p>
                </div>

                <div v-if="schedule.length > 0" class="divide-y divide-gray-200">
                    <div
                        v-for="item in schedule"
                        :key="item.id"
                        class="flex flex-col gap-4 p-5 transition-colors hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between md:p-6"
                    >
                        <div class="flex items-center gap-4">
                            <div :class="['h-2.5 w-2.5 shrink-0 rounded-full', item.color === 'blue' ? 'bg-blue-500' : 'bg-purple-500']"></div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800 md:text-base">{{ item.title }}</h4>
                                <p class="text-xs text-gray-500">{{ item.time }} • {{ item.location }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                            {{ item.type }}
                        </span>
                    </div>
                </div>

                <div v-else class="p-12 text-center">
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="h-6 w-6"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"
                            />
                        </svg>
                    </div>
                    <h4 class="text-base font-semibold text-gray-800">No upcoming exams</h4>
                    <p class="text-sm text-gray-500">Your schedule is clear for now.</p>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
