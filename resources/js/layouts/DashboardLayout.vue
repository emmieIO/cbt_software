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
    permission?: string;
}

interface NavSection {
    section: string;
    items: NavItem[];
}

defineProps<{
    title: string;
    navigation: NavSection[];
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
const isProfileDropdownOpen = ref(false);

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

// Close dropdowns on click outside
if (typeof window !== 'undefined') {
    window.addEventListener('click', () => {
        isNotificationsOpen.value = false;
        isProfileDropdownOpen.value = false;
    });
}
</script>

<template>
    <div class="flex min-h-screen bg-[#F8F9FB] font-sans selection:bg-primary/10 selection:text-primary">
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
            <div class="flex h-20 items-center px-8">
                <Link href="/" class="group flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-8 w-auto object-contain" />
                        <span class="text-xl font-black tracking-tight text-white">CHRISLAND</span>
                    </div>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="sidebar-scrollbar flex-1 space-y-8 overflow-y-auto px-4 py-6">
                <div v-for="section in navigation" :key="section.section" class="space-y-2">
                    <h3 class="px-4 text-[10px] font-black tracking-[0.2em] text-white/30 uppercase">
                        {{ section.section }}
                    </h3>
                    <div class="space-y-1">
                        <Link
                            v-for="item in section.items"
                            :key="item.name"
                            :href="item.href"
                            :class="[
                                'group flex items-center rounded-xl px-4 py-3 text-sm font-bold transition-all duration-200',
                                item.active ? 'bg-white/10 text-white shadow-sm' : 'text-white/60 hover:bg-white/5 hover:text-white',
                            ]"
                        >
                            <component
                                v-if="item.icon"
                                :is="item.icon"
                                class="mr-3 h-5 w-5 shrink-0 transition-transform duration-300"
                                :class="item.active ? 'text-white' : 'text-white/40 group-hover:text-white/80'"
                            />
                            {{ item.name }}
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Bottom Actions -->
            <div class="mt-auto border-t border-white/5 p-6">
                <Link
                    :href="logoutAction"
                    method="post"
                    as="button"
                    class="group flex w-full items-center px-4 py-3 text-sm font-bold text-white/60 transition-all duration-300 hover:text-white"
                >
                    <svg
                        class="mr-3 h-5 w-5 opacity-40 transition-transform group-hover:opacity-100"
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
                    Logout
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="relative flex flex-1 flex-col overflow-hidden md:ml-72">
            <!-- Top Navigation Bar -->
            <header
                class="sticky top-0 z-40 flex h-20 shrink-0 items-center justify-between bg-white px-6 md:px-10 border-b border-slate-100"
            >
                <div class="flex flex-1 items-center gap-8">
                    <!-- Mobile Toggle -->
                    <button
                        @click="isMobileMenuOpen = true"
                        class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 md:hidden"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Breadcrumb/Title Context -->
                    <div class="hidden md:flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-black text-slate-800">{{ title }}</h2>
                    </div>

                    <!-- Global Search Bar -->
                    <div class="hidden max-w-xl flex-1 lg:block">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                type="text"
                                placeholder="Search here..."
                                class="h-11 w-full rounded-xl border-none bg-[#F3F4F6] pl-12 text-sm font-bold text-slate-600 transition-all placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-primary/10"
                            />
                        </div>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center gap-4">
                    <!-- Notifications -->
                    <div class="relative" @click.stop>
                        <button
                            @click="isNotificationsOpen = !isNotificationsOpen"
                            class="relative flex h-11 w-11 items-center justify-center rounded-xl text-slate-400 transition-all hover:bg-slate-50 active:scale-90"
                        >
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span v-if="notifications.length > 0" class="absolute right-2.5 top-2.5 flex h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                        </button>

                        <!-- Dropdown (Minimal) -->
                        <div v-if="isNotificationsOpen" class="absolute right-0 z-50 mt-4 w-80 overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
                            <div class="flex items-center justify-between bg-slate-50 px-6 py-4">
                                <h3 class="text-xs font-black tracking-widest text-slate-800 uppercase">Notifications</h3>
                                <button v-if="notifications.length > 0" @click="handleMarkAllAsRead" class="text-[10px] font-black text-primary uppercase hover:underline">Clear All</button>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <div v-if="notifications.length === 0" class="py-12 text-center text-sm font-bold text-slate-400">All caught up!</div>
                                <div v-for="notification in notifications" :key="notification.id" class="border-b border-slate-50 p-5 transition-colors hover:bg-slate-50">
                                    <p class="text-xs font-bold text-slate-800">{{ notification.data.title }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ notification.data.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="relative ml-2 border-l border-slate-100 pl-6" @click.stop>
                        <button 
                            @click="isProfileDropdownOpen = !isProfileDropdownOpen"
                            class="group flex items-center gap-3 transition-all active:scale-95"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 ring-2 ring-transparent transition-all group-hover:ring-primary/20">
                                <img v-if="user.avatar" :src="user.avatar" class="h-full w-full rounded-full object-cover" />
                                <span v-else class="text-xs font-black text-slate-500">{{ userInitials }}</span>
                            </div>
                            <div class="hidden flex-col items-start lg:flex">
                                <span class="text-sm font-black text-slate-800 leading-none group-hover:text-primary transition-colors">{{ user.name }}</span>
                                <span class="mt-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ user.roles?.[0] }}</span>
                            </div>
                            <svg 
                                class="h-4 w-4 text-slate-400 transition-transform" 
                                :class="{ 'rotate-180': isProfileDropdownOpen }"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Profile Dropdown Menu -->
                        <div 
                            v-if="isProfileDropdownOpen" 
                            class="absolute right-0 z-50 mt-4 w-56 overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                        >
                            <div class="bg-slate-50/50 p-4">
                                <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Signed in as</p>
                                <p class="truncate text-xs font-black text-slate-800">{{ user.email }}</p>
                            </div>
                            <div class="p-2">
                                <Link 
                                    href="/profile" 
                                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-xs font-black text-slate-600 uppercase transition-colors hover:bg-slate-50 hover:text-primary"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    My Profile
                                </Link>
                                <Link 
                                    href="#" 
                                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-xs font-black text-slate-600 uppercase transition-colors hover:bg-slate-50 hover:text-primary"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.756 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.756 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.756 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.756 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.756 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.756 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.756 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Settings
                                </Link>
                            </div>
                            <div class="border-t border-slate-50 p-2">
                                <Link 
                                    :href="logoutAction" 
                                    method="post" 
                                    as="button"
                                    class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-xs font-black text-red-600 uppercase transition-colors hover:bg-red-50"
                                >
                                    <svg class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Sign Out
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="flex-1 overflow-y-auto">
                <div class="mx-auto min-h-full max-w-7xl p-6 md:p-10">
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