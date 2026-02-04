<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome" />
    <div class="flex min-h-screen flex-col items-center bg-primary p-6 text-white lg:justify-center lg:p-8">
        <div class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
            <main class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row">
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] text-slate-900 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20"
                >
                    <h1 class="mb-1 text-2xl font-bold">Let's get started</h1>
                    <p class="mb-2 text-slate-600">Laravel has an incredibly rich ecosystem. <br />We suggest starting with the following.</p>
                    <ul class="mb-4 flex flex-col lg:mb-6">
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:top-1/2 before:bottom-0 before:left-[0.4rem] before:border-l before:border-[#e3e3e0]"
                        >
                            <span class="relative bg-white py-1">
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-primary" />
                                </span>
                            </span>
                            <span>
                                Read the
                                <a
                                    href="https://laravel.com/docs"
                                    target="_blank"
                                    class="ml-1 inline-flex items-center space-x-1 font-medium underline underline-offset-4 transition-opacity hover:opacity-80"
                                >
                                    <span>Documentation</span>
                                    <svg
                                        width="10"
                                        height="11"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-2.5 w-2.5"
                                    >
                                        <path d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001" stroke="currentColor" stroke-linecap="square" />
                                    </svg>
                                </a>
                            </span>
                        </li>
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:top-0 before:bottom-1/2 before:left-[0.4rem] before:border-l before:border-[#e3e3e0]"
                        >
                            <span class="relative bg-white py-1">
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-primary" />
                                </span>
                            </span>
                            <span>
                                Watch video tutorials at
                                <a
                                    href="https://laracasts.com"
                                    target="_blank"
                                    class="ml-1 inline-flex items-center space-x-1 font-medium underline underline-offset-4 transition-opacity hover:opacity-80"
                                >
                                    <span>Laracasts</span>
                                    <svg
                                        width="10"
                                        height="11"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-2.5 w-2.5"
                                    >
                                        <path d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001" stroke="currentColor" stroke-linecap="square" />
                                    </svg>
                                </a>
                            </span>
                        </li>
                    </ul>
                    <ul class="flex gap-3 text-sm leading-normal">
                        <li>
                            <a
                                href="https://cloud.laravel.com"
                                target="_blank"
                                class="inline-block rounded-sm bg-primary px-5 py-1.5 text-sm leading-normal text-white transition-opacity hover:opacity-90"
                            >
                                Deploy now
                            </a>
                        </li>
                    </ul>
                </div>
                <div
                    class="relative -mb-px aspect-335/376 w-full shrink-0 overflow-hidden rounded-t-lg bg-lemon-yellow lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg"
                >
                    <div class="flex h-full flex-col items-center justify-center p-12">
                        <img src="/assets/img/chrisland-school-logo.png" alt="Chrisland School Logo" class="mb-6 h-32 w-auto object-contain" />
                        <h2 class="text-center text-4xl font-bold text-primary">CBT Software</h2>
                    </div>
                    <div
                        class="absolute inset-0 rounded-t-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:overflow-hidden lg:rounded-t-none lg:rounded-r-lg"
                    />
                </div>
            </main>
        </div>
    </div>
</template>
