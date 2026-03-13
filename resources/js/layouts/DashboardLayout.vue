<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, watch, onMounted } from 'vue';
import { markAllAsRead } from '@/actions/App/Http/Controllers/NotificationController';
import ToastList from '@/components/ToastList.vue';
import { toastStore } from '@/stores/toast';

interface NavItem {
    name: string;
    href: string;
    icon?: any;
    active?: boolean;
    permission?: string;
    external?: boolean;
}

interface NavSection {
    section: string;
    items: NavItem[];
}

withDefaults(defineProps<{
    title: string;
    navigation: NavSection[];
    logoutAction: string;
    wide?: boolean;
}>(), {
    wide: false
});

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user || {});
const notifications = computed(() => (page.props.auth as any).notifications || []);
const academicSession = computed(() => (page.props as any).academic_session);
const userInitials = computed(() => {
    if (!user.value?.name) return '?';
    return user.value.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
});

// Re-initialize Preline on initial mount
onMounted(() => {
    setTimeout(() => {
        // @ts-expect-error: HSStaticMethods is globally defined by Preline
        if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
    }, 100);
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

const handleMarkAllAsRead = () => {
    router.post(
        markAllAsRead().url,
        {},
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <div class="flex min-h-screen bg-[#F8F9FB] font-sans selection:bg-primary/10 selection:text-primary">
        <!-- Toast System -->
        <ToastList />

        <!-- Sidebar -->
        <aside
            id="application-sidebar"
            class="hs-overlay hs-overlay-open:translate-x-0 -translate-x-full fixed top-0 start-0 bottom-0 z-60 w-72 bg-primary text-white transition-all duration-300 transform md:block md:translate-x-0 md:end-auto md:bottom-0 hidden"
            v-if="!wide"
        >
            <div class="flex flex-col h-full">
                <!-- Logo Section -->
                <div class="flex h-20 items-center px-8 shrink-0">
                    <Link href="/" class="group flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-8 w-auto object-contain" />
                            <span class="text-xl font-black tracking-tight text-white uppercase">Chrisland</span>
                        </div>
                    </Link>
                </div>

                <!-- Navigation -->
                <nav class="hs-accordion-group flex-1 flex flex-col space-y-8 overflow-y-auto px-4 py-6 sidebar-scrollbar" data-hs-accordion-always-open>
                    <div v-for="section in navigation" :key="section.section" class="space-y-2">
                        <h3 class="px-4 text-[10px] font-black tracking-[0.2em] text-white/30 uppercase">
                            {{ section.section }}
                        </h3>
                        <div class="space-y-1">
                            <template v-for="item in section.items" :key="item.name">
                                <component
                                    :is="item.external ? 'a' : Link"
                                    :href="item.href"
                                    :preserve-scroll="!item.external"
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
                                </component>
                            </template>
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
            </div>
        </aside>

        <!-- Main Content -->
        <main 
            :class="[
                'relative flex flex-1 flex-col overflow-hidden transition-all duration-300',
                !wide ? 'md:ps-72' : 'ps-0'
            ]"
        >
            <!-- Top Navigation Bar -->
            <header class="sticky top-0 z-50 flex h-20 shrink-0 items-center justify-between border-b border-slate-100 bg-white px-6 md:px-10">
                <div class="flex flex-1 items-center gap-8">
                    <!-- Mobile Toggle -->
                    <button
                        type="button"
                        class="md:hidden flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600"
                        data-hs-overlay="#application-sidebar"
                        aria-controls="application-sidebar"
                        aria-label="Toggle navigation"
                        v-if="!wide"
                    >
                        <span class="sr-only">Toggle Navigation</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Breadcrumb/Title Context -->
                    <div class="flex items-center gap-2 md:gap-3">
                        <div class="hidden h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 md:flex shadow-inner">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                />
                            </svg>
                        </div>
                        <div>
                            <h2 class="truncate text-sm md:text-lg font-black text-slate-800 uppercase tracking-tight">{{ title }}</h2>
                        </div>
                    </div>

                    <!-- Academic Session Indicator -->
                    <div class="hidden xl:block">
                        <div v-if="academicSession" class="flex items-center gap-2 rounded-2xl bg-slate-50 px-5 py-2.5 border border-slate-100 shadow-sm">
                            <div class="h-2 w-2 rounded-full bg-primary animate-pulse"></div>
                            <span class="text-[9px] font-black tracking-[0.2em] text-slate-400 uppercase mr-1">Session:</span>
                            <span class="text-xs font-black text-slate-800">{{ academicSession.name }}</span>
                            <div class="mx-2 h-4 w-px bg-slate-200"></div>
                            <span class="text-[9px] font-black tracking-[0.2em] text-slate-400 uppercase mr-1">Term:</span>
                            <span class="text-xs font-black text-slate-800">{{ academicSession.term_label }}</span>
                        </div>
                        <div v-else class="flex items-center gap-2 rounded-2xl bg-red-50 px-5 py-2.5 border border-red-100 shadow-sm">
                            <div class="h-2 w-2 rounded-full bg-red-500"></div>
                            <span class="text-[9px] font-black tracking-[0.2em] text-red-500 uppercase mr-2">No Active Session</span>
                            <Link v-if="(page.props.auth.user as any).permissions.includes('sys:manage_settings')" href="/admin/school-setup/sessions" class="text-[10px] font-black text-red-600 underline uppercase hover:text-red-700">Setup Now</Link>
                        </div>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center gap-2 md:gap-4">
                    <!-- Notifications Dropdown -->
                    <div class="hs-dropdown relative inline-flex [--placement:bottom-right]">
                        <button id="hs-dropdown-notifications" type="button" class="hs-dropdown-toggle relative flex h-10 w-10 md:h-11 md:w-11 items-center justify-center rounded-2xl text-slate-400 transition-all hover:bg-slate-50 hover:text-slate-600 active:scale-95 shadow-sm">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span v-if="notifications.length > 0" class="absolute top-2.5 right-2.5 flex h-2 w-2 rounded-full bg-red-500 ring-4 ring-white"></span>
                        </button>

                        <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[320px] bg-white shadow-2xl rounded-3xl p-2 mt-2 after:h-4 after:absolute after:-bottom-4 after:start-0 after:w-full before:h-4 before:absolute before:-top-4 before:start-0 before:w-full z-100" aria-labelledby="hs-dropdown-notifications">
                            <div class="bg-slate-50/50 rounded-2xl p-4 flex items-center justify-between mb-2">
                                <h3 class="text-xs font-black tracking-widest text-slate-800 uppercase">Alert Center</h3>
                                <button v-if="notifications.length > 0" @click="handleMarkAllAsRead" class="text-[9px] font-black text-primary uppercase hover:underline">Mark Read</button>
                            </div>
                            <div class="max-h-80 overflow-y-auto sidebar-scrollbar px-2 pb-2">
                                <div v-if="notifications.length === 0" class="py-12 text-center">
                                    <div class="h-12 w-12 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-300">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0l-8 4-8-4" /></svg>
                                    </div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No New Alerts</p>
                                </div>
                                <div v-for="notification in notifications" :key="notification.id" class="p-4 rounded-2xl transition-colors hover:bg-slate-50 border border-transparent hover:border-slate-100 mb-1 last:mb-0">
                                    <p class="text-xs font-black text-slate-800">{{ notification.data.title }}</p>
                                    <p class="mt-1 text-[11px] font-medium text-slate-500 leading-relaxed">{{ notification.data.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Dropdown -->
                    <div class="hs-dropdown relative inline-flex [--placement:bottom-right] border-l border-slate-100 ps-4 md:ps-6">
                        <button id="hs-dropdown-profile" type="button" class="hs-dropdown-toggle flex items-center gap-3 transition-all active:scale-95 group">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-50 ring-4 ring-transparent transition-all group-hover:ring-primary/5 shadow-sm border border-slate-100">
                                <img v-if="user.avatar" :src="user.avatar" class="h-full w-full rounded-2xl object-cover" />
                                <span v-else class="text-xs font-black text-slate-400">{{ userInitials }}</span>
                            </div>
                            <div class="hidden flex-col items-start lg:flex text-start">
                                <span class="text-[13px] leading-none font-black text-slate-800 uppercase tracking-tight">{{ user.name }}</span>
                                <span class="mt-1 text-[9px] font-black tracking-widest text-slate-400 uppercase">{{ user.roles?.[0]?.replace('_', ' ') }}</span>
                            </div>
                            <svg class="h-4 w-4 text-slate-300 transition-transform duration-300 hs-dropdown-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[240px] bg-white shadow-2xl rounded-3xl p-2 mt-2 after:h-4 after:absolute after:-bottom-4 after:start-0 after:w-full before:h-4 before:absolute before:-top-4 before:start-0 before:w-full z-100" aria-labelledby="hs-dropdown-profile">
                            <div class="bg-slate-50 rounded-2xl p-4 mb-2">
                                <p class="text-[9px] font-black tracking-widest text-slate-400 uppercase mb-0.5">Authenticated As</p>
                                <p class="truncate text-xs font-black text-slate-800 tracking-tight">{{ user.email }}</p>
                            </div>
                            <div class="space-y-1">
                                <Link href="/profile" class="flex items-center gap-3 rounded-xl px-4 py-3 text-[10px] font-black text-slate-600 uppercase tracking-widest transition-colors hover:bg-slate-50 hover:text-primary">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    Security Profile
                                </Link>
                                <div class="h-px bg-slate-50 mx-2"></div>
                                <Link :href="logoutAction" method="post" as="button" class="w-full flex items-center gap-3 rounded-xl px-4 py-3 text-[10px] font-black text-red-500 uppercase tracking-widest transition-colors hover:bg-red-50">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    Terminate Session
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="flex-1 overflow-y-auto">
                <div 
                    :class="[
                        'mx-auto min-h-full p-6 md:p-10 md:pb-32 transition-all duration-300',
                        !wide ? 'max-w-7xl' : 'max-w-full'
                    ]"
                >
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.sidebar-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.sidebar-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.sidebar-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
.sidebar-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
}
</style>
