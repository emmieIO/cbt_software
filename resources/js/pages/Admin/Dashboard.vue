<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Admin Dashboard" />

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
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                    </svg>
                    Manage Users
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
                    Exams List
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
                    <h2 class="text-xl font-bold text-slate-800">Admin Control Panel</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-800 font-bold text-white">A</div>
                </div>
            </header>

            <!-- Dashboard Content -->
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
        </main>
    </div>
</template>
