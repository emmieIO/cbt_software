<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StaffLayout from '@/layouts/StaffLayout.vue';
</script>

<template>
    <StaffLayout>
        <Head title="Staff Dashboard" />

        <div class="space-y-10">
            <!-- Welcome Header -->
            <div class="relative overflow-hidden rounded-[2.5rem] bg-primary px-10 py-12 shadow-2xl shadow-primary/20">
                <div class="relative z-10">
                    <h1 class="text-3xl font-black text-white md:text-4xl">Welcome back, Staff</h1>
                    <p class="mt-2 max-w-xl text-lg font-medium text-white/60">
                        Manage your classes, questions, and monitor real-time exam performance from your command center.
                    </p>
                </div>

                <!-- Abstract Background Shapes -->
                <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
                <div class="absolute right-20 -bottom-20 h-64 w-64 rounded-full bg-lemon-yellow/5 blur-3xl"></div>
            </div>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                <!-- Stats Card: Assigned Classes -->
                <div class="group relative overflow-hidden rounded-4xl bg-white p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-black tracking-[0.2em] text-slate-400 uppercase">Assigned Classes</p>
                            <h3 class="mt-4 text-5xl font-black tracking-tight text-slate-800">06</h3>
                        </div>
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/5 text-primary transition-colors group-hover:bg-primary group-hover:text-white"
                        >
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-6 flex items-center text-xs font-bold text-green-600">
                        <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                        Active this term
                    </div>
                </div>

                <!-- Stats Card: Pending Results -->
                <div class="group relative overflow-hidden rounded-4xl bg-white p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-black tracking-[0.2em] text-slate-400 uppercase">Pending Results</p>
                            <h3 class="mt-4 text-5xl font-black tracking-tight text-orange-600">03</h3>
                        </div>
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 transition-colors group-hover:bg-orange-600 group-hover:text-white"
                        >
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-6 flex items-center text-xs font-bold text-orange-500">
                        <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Requires attention
                    </div>
                </div>

                <!-- Stats Card: Questions -->
                <div class="group relative overflow-hidden rounded-4xl bg-white p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-black tracking-[0.2em] text-slate-400 uppercase">Question Bank</p>
                            <h3 class="mt-4 text-5xl font-black tracking-tight text-indigo-600">124</h3>
                        </div>
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white"
                        >
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-6 flex items-center text-xs font-bold text-indigo-500">
                        <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                        Verified repository
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="rounded-[2.5rem] border border-slate-100 bg-white p-10 shadow-sm">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black tracking-tight text-slate-800">Today's Schedule</h3>
                        <p class="text-sm font-bold text-slate-400">Your upcoming exam invigilations and class sessions.</p>
                    </div>
                    <button
                        class="rounded-xl bg-slate-100 px-6 py-3 text-xs font-black tracking-widest text-slate-600 uppercase transition-all hover:bg-slate-200 active:scale-95"
                    >
                        View Full Calendar
                    </button>
                </div>

                <div class="space-y-4">
                    <div
                        class="group flex items-center justify-between rounded-3xl border border-slate-100 p-6 transition-all hover:border-primary/20 hover:bg-primary/2"
                    >
                        <div class="flex items-center gap-6">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 transition-transform group-hover:scale-110"
                            >
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-slate-900">Physics - SS2 Blue</h4>
                                <div class="mt-1 flex items-center gap-2 text-sm font-bold text-slate-400">
                                    <span>Today, 2:00 PM</span>
                                    <div class="h-1 w-1 rounded-full bg-slate-300"></div>
                                    <span>Examination Hall A</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="rounded-full bg-blue-100 px-5 py-2 text-[10px] font-black tracking-widest text-blue-700 uppercase"
                                >Invigilation</span
                            >
                            <button
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-primary hover:text-white"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div
                        class="group flex items-center justify-between rounded-3xl border border-slate-100 p-6 transition-all hover:border-primary/20 hover:bg-primary/2"
                    >
                        <div class="flex items-center gap-6">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 transition-transform group-hover:scale-110"
                            >
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-slate-900">General Math - SS1 Green</h4>
                                <div class="mt-1 flex items-center gap-2 text-sm font-bold text-slate-400">
                                    <span>Today, 4:30 PM</span>
                                    <div class="h-1 w-1 rounded-full bg-slate-300"></div>
                                    <span>Classroom 4B</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="rounded-full bg-purple-100 px-5 py-2 text-[10px] font-black tracking-widest text-purple-700 uppercase"
                                >Class Session</span
                            >
                            <button
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-primary hover:text-white"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
