<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { login as adminLogin } from '@/routes/admin';
import { login as staffLogin } from '@/routes/staff';
import { login as studentLogin } from '@/routes/student';

const page = usePage();
</script>

<template>
    <Head title="Welcome to Chrisland CBT Portal" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-primary p-4 font-sans text-white sm:p-6">
        <div class="w-full max-w-4xl space-y-8 text-center">
            <!-- Header Section -->
            <div class="flex flex-col items-center space-y-4">
                <img
                    src="/assets/img/chrisland-school-logo.png"
                    alt="Chrisland School Logo"
                    class="h-20 w-auto object-contain drop-shadow-2xl sm:h-32"
                />
                <h1 class="text-3xl font-semibold tracking-tighter sm:text-6xl">Chrisland Schools</h1>
                <p class="text-xs font-semibold tracking-[0.2em] text-lemon-yellow uppercase sm:text-xl">CBT INFRASTRUCTURE</p>
            </div>

            <!-- Authenticated View -->
            <div v-if="page.props.auth.user" class="mt-8 flex flex-col items-center space-y-6 sm:mt-12">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-md sm:p-10">
                    <h2 class="text-xl font-semibold text-slate-900 sm:text-3xl">Welcome back, {{ page.props.auth.user.name }}!</h2>
                    <p class="mt-2 text-sm text-slate-600 sm:text-lg">
                        You are currently signed in as a
                        <span class="font-semibold capitalize">{{
                            page.props.auth.user.permissions.includes('sys:manage_settings')
                                ? 'System Administrator'
                                : page.props.auth.user.permissions.includes('bank:view')
                                  ? 'Academic Staff'
                                  : 'Candidate'
                        }}</span
                        >.
                    </p>
                    <Link
                        :href="page.props.auth.dashboard_url || '#'"
                        class="mt-6 flex items-center justify-center rounded-xl bg-lemon-yellow px-8 py-4 text-lg font-semibold text-primary shadow-xl transition-all hover:scale-105 hover:bg-lemon-yellow/90 active:scale-95 sm:mt-10 sm:px-12 sm:py-5 sm:text-xl"
                    >
                        Go to your Dashboard &rarr;
                    </Link>
                </div>
            </div>

            <!-- Portal Entry Options (Guest View) -->
            <div v-else class="mt-8 grid grid-cols-1 gap-4 sm:mt-12 sm:gap-8 md:grid-cols-2">
                <!-- Candidate Portal Card -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 text-slate-900 shadow-sm transition-all hover:shadow-md sm:p-8"
                >
                    <div class="absolute top-0 right-0 p-4 opacity-10 transition-opacity group-hover:opacity-20">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-16 w-16 sm:h-24 sm:w-24"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path
                                d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"
                            />
                        </svg>
                    </div>
                    <div class="relative z-10 flex flex-col items-center space-y-4">
                        <h2 class="text-xl font-semibold uppercase sm:text-2xl">Candidate Portal</h2>
                        <p class="text-xs text-slate-600">Access your exams, results, and learning materials here.</p>
                        <Link
                            :href="studentLogin.url()"
                            class="mt-2 flex w-full items-center justify-center rounded-xl bg-lemon-yellow px-6 py-3.5 text-base font-semibold text-primary transition-all hover:scale-105 hover:bg-lemon-yellow/90 active:scale-95 sm:py-4 sm:text-lg"
                        >
                            Enter Exam Portal
                        </Link>
                    </div>
                </div>

                <!-- Examiner Portal Card -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 text-slate-900 shadow-sm transition-all hover:shadow-md sm:p-8"
                >
                    <div class="absolute top-0 right-0 p-4 text-primary opacity-10 transition-opacity group-hover:opacity-20">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-16 w-16 sm:h-24 sm:w-24"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.594-3.741z"
                            />
                        </svg>
                    </div>
                    <div class="relative z-10 flex flex-col items-center space-y-4">
                        <h2 class="text-xl font-semibold text-primary uppercase sm:text-2xl">Examiner Portal</h2>
                        <p class="text-xs text-slate-600">Manage candidates, questions, and view detailed reports.</p>
                        <Link
                            :href="staffLogin.url()"
                            class="mt-2 flex w-full items-center justify-center rounded-xl border-2 border-primary bg-transparent px-6 py-3.5 text-base font-semibold text-primary transition-all hover:scale-105 hover:bg-primary hover:text-white active:scale-95 sm:py-4 sm:text-lg"
                        >
                            Staff Login
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer Section -->
            <footer class="mt-12 flex flex-col items-center space-y-4 text-xs text-white/60 sm:mt-16">
                <p>&copy; {{ new Date().getFullYear() }} Chrisland Schools. All rights reserved.</p>
                <Link
                    v-if="!page.props.auth.user"
                    :href="adminLogin.url()"
                    class="inline-flex items-center gap-x-1.5 rounded-full border border-white/20 bg-white/5 px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest text-white/50 transition-all hover:border-lemon-yellow/40 hover:bg-white/10 hover:text-lemon-yellow"
                >
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.955 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.333 9-6.03 9-11.623 0-1.312-.209-2.57-.598-3.751A11.956 11.956 0 0112 2.714z" />
                    </svg>
                    System Administrator
                </Link>
            </footer>
        </div>
    </div>
</template>
