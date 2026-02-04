<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Student Dashboard" />

    <div class="flex min-h-screen bg-slate-50 font-sans">
        <!-- Sidebar -->
        <aside class="hidden w-64 flex-col bg-primary text-white md:flex">
            <div class="flex h-20 items-center justify-center border-b border-white/10 px-6">
                <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-12 w-auto brightness-0 invert" />
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
                    Available Exams
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
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                    My Results
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
                    <h2 class="text-xl font-bold text-slate-800">Welcome back, Student!</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary font-bold text-white">S</div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="p-8">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <!-- Stats Card -->
                    <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                        <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Active Exams</p>
                        <p class="mt-2 text-3xl font-bold text-primary">2</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                        <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Completed</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">12</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                        <p class="text-sm font-medium tracking-wider text-slate-500 uppercase">Avg. Score</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">84%</p>
                    </div>
                </div>

                <!-- Active Exams Placeholder -->
                <div class="mt-8 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                        <h3 class="font-bold text-slate-800">Ongoing Exams</h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between rounded-xl border border-primary/20 bg-primary/5 p-4">
                            <div class="flex items-center space-x-4">
                                <div class="rounded-lg bg-primary p-3 text-white">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900">Mathematics Mock Exam</h4>
                                    <p class="text-sm text-slate-500">Duration: 60 minutes • 40 Questions</p>
                                </div>
                            </div>
                            <button class="rounded-lg bg-primary px-6 py-2 font-bold text-white transition-transform hover:scale-105 active:scale-95">
                                Start Exam
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
