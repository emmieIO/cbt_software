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

        <div class="space-y-10">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">Teacher Hub</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400">Welcome back, {{ userName }}</p>
                </div>
            </div>

            <!-- Welcome Hero Section -->
            <div class="relative overflow-hidden rounded-xl bg-primary px-12 py-16 text-white shadow-2xl shadow-primary/20">
                <div class="relative z-10 max-w-2xl">
                    <h1 class="text-5xl font-black tracking-tighter">Welcome, {{ userName }}</h1>
                    <p class="mt-4 text-xl leading-relaxed font-medium text-white/70">
                        Your academic command center is ready. Manage your classes, build intelligent questions, and monitor performance.
                    </p>
                </div>
                <!-- Abstract Design -->
                <div class="rounded-lg-full absolute -top-24 -right-24 h-96 w-96 bg-white/10 blur-3xl"></div>
                <div class="rounded-lg-full absolute right-0 bottom-0 h-64 w-64 bg-lemon-yellow/10 blur-2xl"></div>
            </div>

            <!-- Performance Grid -->
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                <div class="group rounded-xl border border-slate-100 bg-white p-10 shadow-sm transition-all hover:-translate-y-1 hover:shadow-2xl">
                    <p class="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">Assigned Classes</p>
                    <h3 class="mt-4 text-6xl font-black tracking-tighter text-slate-800">{{ stats.assignedClasses.toString().padStart(2, '0') }}</h3>
                    <div class="mt-6 flex items-center text-xs font-bold text-green-600">
                        <span class="rounded-lg-full mr-2 h-1.5 w-1.5 animate-ping bg-green-500"></span>
                        Active this session
                    </div>
                </div>

                <div class="group rounded-xl border border-slate-100 bg-white p-10 shadow-sm transition-all hover:-translate-y-1 hover:shadow-2xl">
                    <p class="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">Pending Results</p>
                    <h3 class="mt-4 text-6xl font-black tracking-tighter text-orange-600">{{ stats.pendingResults.toString().padStart(2, '0') }}</h3>
                    <div class="mt-6 text-xs font-bold text-slate-400">Requires your attention</div>
                </div>

                <div class="group rounded-xl bg-slate-900 p-10 shadow-2xl transition-all hover:-translate-y-1">
                    <p class="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">Question Bank</p>
                    <h3 class="mt-4 text-6xl font-black tracking-tighter text-primary">{{ stats.questionBankCount }}</h3>
                    <div class="mt-6 text-xs font-black tracking-widest text-primary/60 uppercase">Verified Repository</div>
                </div>
            </div>

            <!-- Schedule -->
            <div class="rounded-xl border border-slate-100 bg-white p-12 shadow-sm">
                <div class="mb-10 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900">Upcoming Schedule</h3>
                        <p class="mt-1 text-sm font-bold tracking-widest text-slate-400 uppercase">Upcoming Exams</p>
                    </div>
                    <button
                        class="rounded-xl bg-slate-50 px-6 py-3 text-[10px] font-black tracking-widest text-slate-500 uppercase transition-all hover:bg-slate-100"
                    >
                        Full Calendar
                    </button>
                </div>

                <div v-if="schedule.length > 0" class="space-y-4">
                    <div
                        v-for="item in schedule"
                        :key="item.id"
                        class="group flex cursor-pointer items-center justify-between rounded-xl border border-slate-50 p-6 transition-colors hover:bg-slate-50/50"
                    >
                        <div class="flex items-center gap-6">
                            <div
                                :class="[
                                    'rounded-lg-full h-3 w-3',
                                    item.color === 'blue'
                                        ? 'bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]'
                                        : 'bg-purple-500 shadow-[0_0_8px_rgba(168,85,247,0.5)]',
                                ]"
                            ></div>
                            <div>
                                <h4 class="text-lg font-black text-slate-800">{{ item.title }}</h4>
                                <p class="text-xs font-bold text-slate-400">{{ item.time }} • {{ item.location }}</p>
                            </div>
                        </div>
                        <span class="rounded-lg-full bg-slate-100 px-4 py-1.5 text-[9px] font-black tracking-widest text-slate-500 uppercase">{{
                            item.type
                        }}</span>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="mb-4 rounded-full bg-slate-50 p-6">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-10 w-10 text-slate-300"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-bold text-slate-900">No upcoming exams</h4>
                    <p class="mt-1 text-sm text-slate-400 font-medium">Your schedule is clear for now.</p>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
