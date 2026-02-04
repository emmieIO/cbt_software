<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Staff Dashboard" />

    <div class="flex min-h-screen bg-slate-50 font-sans">
        <!-- Sidebar -->
        <aside class="hidden w-64 flex-col bg-primary text-white md:flex">
            <div class="flex h-20 items-center justify-center border-b border-white/10 px-6">
                <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-12 w-auto" />
            </div>
            <nav class="flex-1 space-y-1 p-4">
                <Link href="#" class="flex items-center rounded-xl bg-lemon-yellow/10 px-4 py-3 font-semibold text-lemon-yellow">
                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                        />
                    </svg>
                    Dashboard
                </Link>
                <Link
                    href="#"
                    class="flex items-center rounded-xl px-4 py-3 font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white"
                >
                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01"
                        />
                    </svg>
                    My Classes
                </Link>
                <Link
                    href="#"
                    class="flex items-center rounded-xl px-4 py-3 font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white"
                >
                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                    Exam Schedule
                </Link>
                <Link
                    href="#"
                    class="flex items-center rounded-xl px-4 py-3 font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white"
                >
                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                    </svg>
                    Question Bank
                </Link>
            </nav>
            <div class="p-4">
                <Link
                    href="/"
                    class="flex items-center rounded-xl bg-white/5 px-4 py-3 font-medium text-white/70 transition-colors hover:bg-red-500/20 hover:text-red-200"
                >
                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                        />
                    </svg>
                    Logout
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Header -->
            <header class="flex h-20 items-center justify-between bg-white px-8 shadow-sm">
                <div class="flex items-center md:hidden">
                    <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-10 w-auto" />
                </div>
                <div class="hidden md:block">
                    <h2 class="text-xl font-bold text-slate-800">Staff Portal Dashboard</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-700 font-bold text-white">T</div>
                </div>
            </header>

            <!-- Dashboard Content -->
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
        </main>
    </div>
</template>
