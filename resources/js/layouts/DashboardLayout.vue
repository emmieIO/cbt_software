<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ToastList from '@/components/ToastList.vue';
import { toastStore } from '@/stores/toast';
import type { User } from '@/types';

interface NavItem {
    name: string;
    href: string;
    icon?: any;
    active?: boolean;
}

defineProps<{
    title: string;
    navigation: NavItem[];
    logoutAction: string;
}>();

const page = usePage();
const user = computed<User>(() => page.props.auth.user);
const userInitials = computed(() => {
    if (!user.value?.name) return '?';
    return user.value.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
});

// Watch for flash messages
watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) {
            toastStore.add(flash.success, 'success');
        }
        if (flash?.error) {
            toastStore.add(flash.error, 'error');
        }
    },
    { immediate: true, deep: true },
);

const isMobileMenuOpen = ref(false);
</script>

<template>
    <div class="flex min-h-screen bg-slate-50 font-sans selection:bg-primary/10 selection:text-primary">
        <!-- Toast System -->
        <ToastList />

        <!-- Mobile Sidebar Overlay -->
        <div
            v-if="isMobileMenuOpen"
            @click="isMobileMenuOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm transition-opacity md:hidden"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-primary text-white transition-transform duration-300 ease-in-out md:translate-x-0',
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Logo Section -->
            <div class="flex h-24 items-center px-8">
                <Link href="/" class="group flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 p-2 backdrop-blur-md transition-all group-hover:rotate-3 group-hover:bg-white/20"
                    >
                        <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-full w-auto object-contain" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-lg leading-none font-black tracking-tight text-white">CHRISLAND</span>
                        <span class="text-[10px] font-bold tracking-[0.2em] text-lemon-yellow/80 uppercase">CBT Portal</span>
                    </div>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-1.5 overflow-y-auto px-4 py-6">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'group relative flex items-center overflow-hidden rounded-2xl px-5 py-3.5 text-sm font-bold transition-all duration-200',
                        item.active ? 'bg-white/15 text-lemon-yellow shadow-lg shadow-black/10' : 'text-white/60 hover:bg-white/5 hover:text-white',
                    ]"
                >
                    <!-- Active Indicator Line -->
                    <div
                        v-if="item.active"
                        class="absolute top-1/2 left-0 h-6 w-1 -translate-y-1/2 rounded-r-full bg-lemon-yellow shadow-[0_0_12px_rgba(253,255,0,0.5)]"
                    ></div>

                    <component
                        v-if="item.icon"
                        :is="item.icon"
                        class="mr-4 h-6 w-6 shrink-0 transition-transform duration-300 group-hover:scale-110"
                        :class="item.active ? 'text-lemon-yellow' : 'text-white/30 group-hover:text-white/80'"
                    />
                    {{ item.name }}
                </Link>
            </nav>

            <!-- Bottom Actions -->
            <div class="mt-auto space-y-4 border-t border-white/5 p-6">
                <Link
                    :href="logoutAction"
                    method="post"
                    as="button"
                    class="group flex w-full items-center justify-between rounded-2xl bg-white/5 px-5 py-4 text-sm font-bold text-white/60 transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-xl hover:shadow-red-500/30"
                >
                    <div class="flex items-center">
                        <svg
                            class="mr-4 h-5 w-5 opacity-40 transition-transform group-hover:translate-x-1 group-hover:opacity-100"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                            />
                        </svg>
                        Sign Out
                    </div>
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="relative flex flex-1 flex-col overflow-hidden md:ml-72">
            <!-- Glass Header -->
            <header
                class="sticky top-0 z-40 flex h-20 shrink-0 items-center justify-between border-b border-slate-200/60 bg-white/80 px-6 backdrop-blur-xl md:px-10"
            >
                <div class="flex items-center gap-4">
                    <!-- Mobile Toggle -->
                    <button
                        @click="isMobileMenuOpen = true"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition-all hover:bg-slate-50 md:hidden"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="hidden md:block">
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-black tracking-tight text-slate-800">{{ title }}</h2>
                            <div class="h-1.5 w-1.5 rounded-full bg-slate-300"></div>
                            <span class="text-xs font-bold tracking-widest text-slate-400 uppercase">Overview</span>
                        </div>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center gap-6">
                    <!-- Search Bar (UI Only) -->
                    <div class="hidden lg:relative lg:block">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            type="text"
                            placeholder="Search everything..."
                            class="h-11 w-72 rounded-2xl border-none bg-slate-100 pl-11 text-sm font-medium transition-all placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-primary/20"
                        />
                    </div>

                    <!-- Profile Section -->
                    <div class="flex items-center gap-4 border-l border-slate-200 pl-6">
                        <div class="hidden flex-col items-end sm:flex">
                            <span class="text-sm font-black text-slate-800">{{ user?.name }}</span>
                            <span class="text-[10px] font-bold tracking-wider text-primary uppercase">{{ user?.roles?.[0]?.name }}</span>
                        </div>
                        <div
                            class="group relative flex h-12 w-12 cursor-pointer items-center justify-center rounded-2xl bg-linear-to-r from-primary to-primary/80 p-0.5 shadow-lg ring-4 shadow-primary/20 ring-white transition-all hover:scale-105 active:scale-95"
                        >
                            <div
                                class="flex h-full w-full items-center justify-center rounded-[14px] bg-primary/10 text-sm font-black text-white backdrop-blur-sm"
                            >
                                {{ userInitials }}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Scrollable Area -->
            <div class="custom-scrollbar flex-1 overflow-x-hidden overflow-y-auto">
                <div class="mx-auto min-h-full p-6 md:p-10">
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
