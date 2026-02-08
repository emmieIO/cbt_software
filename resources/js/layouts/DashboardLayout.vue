<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { markAsRead, markAllAsRead } from '@/actions/App/Http/Controllers/NotificationController';
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
const currentSession = computed(() => (page.props as any).academic_session);
const notifications = computed(() => (page.props.auth as any).notifications || []);
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

// Watch for new notifications to trigger toasts
watch(
    notifications,
    (newVal, oldVal) => {
        if (newVal.length > oldVal.length) {
            const newNotif = newVal[0]; // Get the latest one
            if (newNotif.data?.type === 'success' || newNotif.data?.title?.includes('Completed')) {
                toastStore.add(newNotif.data.message, 'success');
            } else if (newNotif.data?.type === 'error') {
                toastStore.add(newNotif.data.message, 'error');
            }
        }
    },
    { deep: true },
);

const isMobileMenuOpen = ref(false);
const isNotificationsOpen = ref(false);

const handleMarkAsRead = (id: string) => {
    router.post(
        markAsRead(id).url,
        {},
        {
            preserveScroll: true,
        },
    );
};

const handleMarkAllAsRead = () => {
    router.post(
        markAllAsRead().url,
        {},
        {
            preserveScroll: true,
        },
    );
};

// Close notifications on click outside
if (typeof window !== 'undefined') {
    window.addEventListener('click', () => {
        isNotificationsOpen.value = false;
    });
}
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
            <nav class="sidebar-scrollbar flex-1 space-y-1.5 overflow-y-auto px-4 py-6">
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
                            <div v-if="currentSession" class="flex items-center gap-2">
                                <span class="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">Session:</span>
                                <span class="rounded-lg bg-primary/5 px-2 py-1 text-xs font-black text-primary">{{ currentSession.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center gap-6">
                    <!-- Notifications -->
                    <div class="relative" @click.stop>
                        <button
                            @click="isNotificationsOpen = !isNotificationsOpen"
                            class="relative flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition-all hover:bg-slate-200 active:scale-90"
                        >
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                />
                            </svg>
                            <span
                                v-if="notifications.length > 0"
                                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-black text-white ring-4 ring-white"
                            >
                                {{ notifications.length }}
                            </span>
                        </button>

                        <!-- Notification Dropdown -->
                        <div
                            v-if="isNotificationsOpen"
                            class="absolute right-0 z-50 mt-4 w-80 origin-top-right overflow-hidden rounded-4xl bg-white shadow-2xl ring-1 ring-black/5"
                        >
                            <div class="flex items-center justify-between bg-slate-50 px-6 py-4">
                                <h3 class="text-sm font-black tracking-wider text-slate-800 uppercase">Notifications</h3>
                                <div class="flex items-center gap-4">
                                    <button
                                        @click="router.reload({ only: ['auth'] })"
                                        class="text-slate-400 transition-colors hover:text-primary"
                                        title="Refresh Notifications"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="3"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="notifications.length > 0"
                                        @click="handleMarkAllAsRead"
                                        class="text-[10px] font-black tracking-widest text-primary uppercase hover:underline"
                                    >
                                        Clear All
                                    </button>
                                </div>
                            </div>

                            <div class="custom-scrollbar max-h-100 overflow-y-auto">
                                <div v-if="notifications.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-50 text-slate-300">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H4a2 2 0 00-2 2v7m18 0v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2m18 0l-2.586-2.586a2 2 0 00-2.828 0L12 14.586 8.414 11a2 2 0 00-2.828 0L4 12"
                                            />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-400">All caught up!</p>
                                </div>

                                <div
                                    v-for="notification in notifications"
                                    :key="notification.id"
                                    class="group relative border-b border-slate-50 p-6 transition-colors hover:bg-slate-50"
                                >
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-lg"
                                            :class="
                                                notification.data.type === 'success'
                                                    ? 'bg-green-500 text-white shadow-green-500/20'
                                                    : 'bg-primary text-white shadow-primary/20'
                                            "
                                        >
                                            <svg
                                                v-if="notification.data.type === 'success'"
                                                class="h-5 w-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="text-xs font-black tracking-wider text-slate-800 uppercase">{{ notification.data.title }}</h4>
                                            <p class="mt-1 text-xs leading-relaxed font-bold text-slate-500">{{ notification.data.message }}</p>
                                            <span class="mt-2 block text-[10px] font-black text-slate-300 uppercase">{{
                                                new Date(notification.created_at).toLocaleTimeString()
                                            }}</span>
                                        </div>
                                        <button
                                            @click="handleMarkAsRead(notification.id)"
                                            class="absolute top-4 right-4 h-6 w-6 rounded-lg text-slate-200 transition-colors group-hover:text-slate-300 hover:bg-white hover:text-slate-400"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Section -->
                    <div class="flex items-center gap-4 border-l border-slate-200 pl-6">
                        <div class="hidden flex-col items-end sm:flex">
                            <span class="text-sm font-black text-slate-800">{{ user?.name }}</span>
                            <span class="text-[10px] font-bold tracking-wider text-primary uppercase">{{ user?.roles?.[0] }}</span>
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

.sidebar-scrollbar::-webkit-scrollbar {
    display: none;
}
.sidebar-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
