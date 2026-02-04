<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome to Chrisland CBT Portal" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-primary p-6 font-sans text-white">
        <div class="w-full max-w-4xl space-y-8 text-center">
            <!-- Header Section -->
            <div class="flex flex-col items-center space-y-4">
                <img src="/assets/img/chrisland-school-logo.png" alt="Chrisland School Logo" class="h-32 w-auto object-contain drop-shadow-lg" />
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Chrisland Schools</h1>
                <p class="text-xl font-medium text-lemon-yellow">Computer Based Testing Portal</p>
            </div>

            <!-- Portal Entry Options -->
            <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-2">
                <!-- Student Portal Card -->
                <div
                    class="group relative overflow-hidden rounded-2xl bg-white p-8 text-slate-900 shadow-2xl transition-all hover:-translate-y-1 hover:shadow-lemon-yellow/20"
                >
                    <div class="absolute top-0 right-0 p-4 opacity-10 transition-opacity group-hover:opacity-20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                        <h2 class="text-2xl font-bold">Student Portal</h2>
                        <p class="text-slate-600">Access your exams, results, and learning materials here.</p>
                        <Link
                            href="#"
                            class="mt-4 flex w-full items-center justify-center rounded-xl bg-lemon-yellow px-6 py-4 text-lg font-bold text-primary transition-all hover:scale-105 hover:bg-lemon-yellow/90 active:scale-95"
                        >
                            Enter Exam Portal
                        </Link>
                    </div>
                </div>

                <!-- Staff/Admin Portal Card -->
                <div
                    class="group relative overflow-hidden rounded-2xl border-2 border-lemon-yellow bg-primary/50 p-8 shadow-2xl backdrop-blur-sm transition-all hover:-translate-y-1"
                >
                    <div class="absolute top-0 right-0 p-4 text-lemon-yellow opacity-10 transition-opacity group-hover:opacity-20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.594-3.741z"
                            />
                        </svg>
                    </div>
                    <div class="relative z-10 flex flex-col items-center space-y-4">
                        <h2 class="text-2xl font-bold text-lemon-yellow">Staff & Admin</h2>
                        <p class="text-white/80">Manage students, questions, and view detailed reports.</p>
                        <Link
                            href="#"
                            class="mt-4 flex w-full items-center justify-center rounded-xl border-2 border-lemon-yellow bg-transparent px-6 py-4 text-lg font-bold text-lemon-yellow transition-all hover:scale-105 hover:bg-lemon-yellow hover:text-primary active:scale-95"
                        >
                            Administrative Login
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer Section -->
            <footer class="mt-16 text-sm text-white/60">
                <p>&copy; {{ new Date().getFullYear() }} Chrisland Schools. All rights reserved.</p>
            </footer>
        </div>
    </div>
</template>
