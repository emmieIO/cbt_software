<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import ToastList from '@/components/ToastList.vue';

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);
const sidebarOpen = ref(false);
const darkMode = ref(localStorage.getItem('darkMode') === 'true');
const accountMenuOpen = ref(false);
const accountMenuRef = ref<HTMLElement | null>(null);

const pageLabels: Record<string, string> = {
    dashboard: 'Dashboard',
    questions: 'Questions',
    'questions/batch/create': 'Batch Import',
    'questions/create': 'New Question',
    subjects: 'Subjects',
    topics: 'Topics',
    exams: 'Exams',
    'exam-titles': 'Exam Titles',
    'academic-sessions': 'Academic Sessions',
    'exams/create': 'Create Exam',
    users: 'Users',
    export: 'Export',
};

const breadcrumbs = computed(() => {
    const path = page.url.split('?')[0];
    const segments = path.split('/').filter(Boolean);
    const crumbs: Array<{ label: string; href?: string }> = [];
    let acc = '';

    segments.forEach((seg, i) => {
        if (/^[0-9a-f]{26}$/.test(seg) || seg === 'download' || seg === 'preview') {
            return;
        }

        acc += '/' + seg;
        const fullKey = segments.slice(0, i + 1).join('/');
        const label = pageLabels[fullKey] || pageLabels[seg] || seg.charAt(0).toUpperCase() + seg.slice(1);
        crumbs.push({ label, href: i < segments.length - 1 ? acc : undefined });
    });

    return crumbs;
});

const toggleDark = () => {
    darkMode.value = !darkMode.value;
    localStorage.setItem('darkMode', String(darkMode.value));
    document.documentElement.classList.toggle('dark', darkMode.value);
};

watch(
    darkMode,
    (val) => {
        document.documentElement.classList.toggle('dark', val);
    },
    { immediate: true },
);

const navItems = [
    { name: 'Dashboard', href: '/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Questions', href: '/questions', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
    { name: 'Exams', href: '/exams', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
    { name: 'Exam Titles', href: '/exam-titles', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z', adminOnly: true },
    { name: 'Academic Sessions', href: '/academic-sessions', icon: 'M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z', adminOnly: true },
    { name: 'Subjects', href: '/subjects', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
    { name: 'Topics', href: '/topics', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
    { name: 'Users', href: '/users', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', adminOnly: true },
];

const visibleNav = computed(() => {
    if (user.value?.role === 'admin') return navItems;
    return navItems.filter((n) => !n.adminOnly);
});

const isActive = (href: string) => page.url.startsWith(href);

const logout = () => {
    accountMenuOpen.value = false;
    router.post('/logout');
};

const toggleAccountMenu = () => {
    accountMenuOpen.value = !accountMenuOpen.value;
};

const closeAccountMenu = () => {
    accountMenuOpen.value = false;
};

const handleDocumentClick = (event: MouseEvent) => {
    if (accountMenuRef.value && !accountMenuRef.value.contains(event.target as Node)) {
        closeAccountMenu();
    }
};

const handleEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        closeAccountMenu();
    }
};

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('click', handleDocumentClick);
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-[color:var(--color-canvas-dark)]">
        <ToastList />
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden dark:bg-black/70" @click="sidebarOpen = false" />

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-[rgb(8,65,23)] transition-transform lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 items-center gap-3 border-b border-white/10 px-6">
                <img src="/assets/img/chrisland-school-logo.png" alt="Chrisland Schools" class="h-9 w-auto" />
                <div class="text-sm font-semibold leading-tight text-white">
                    <span class="block">Question Bank</span>
                    <span class="block text-xs text-white/60">Chrisland Schools</span>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                <Link
                    v-for="item in visibleNav"
                    :key="item.name"
                    :href="item.href"
                    class="group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150"
                    :class="isActive(item.href) ? 'bg-white/15 text-white shadow-sm' : 'text-white/70 hover:bg-white/10 hover:text-white'"
                >
                    <span v-if="isActive(item.href)" class="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-r-full bg-lemon-yellow" />
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    <span class="truncate">{{ item.name }}</span>
                </Link>
            </nav>

            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex size-9 items-center justify-center rounded-full bg-white/20 text-sm font-bold text-white">
                        {{ user?.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ user?.name }}</p>
                        <p class="truncate text-xs text-white/60 capitalize">{{ user?.role }}</p>
                    </div>
                    <button @click="toggleDark" class="rounded-lg p-2 text-white/60 transition-colors hover:bg-white/10 hover:text-white" :title="darkMode ? 'Light mode' : 'Dark mode'">
                        <svg v-if="!darkMode" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg v-else class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>
                    <button @click="logout" class="rounded-lg p-2 text-white/60 transition-colors hover:bg-white/10 hover:text-white" title="Logout">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <div class="flex flex-1 flex-col overflow-hidden dark:bg-[color:var(--color-canvas-dark)]">
            <header class="flex h-16 items-center gap-2 border-b border-gray-200 bg-white px-3 dark:border-green-950 dark:bg-green-950 sm:gap-4 sm:px-4 lg:px-6">
                <button @click="sidebarOpen = !sidebarOpen" class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-green-950/55 lg:hidden">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="flex min-w-0 items-center gap-1 overflow-hidden text-xs text-gray-500 dark:text-gray-400 sm:text-sm">
                    <Link href="/dashboard" class="shrink-0 transition-colors hover:text-gray-700 dark:hover:text-gray-200">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </Link>
                    <template v-for="(crumb, i) in breadcrumbs" :key="i">
                        <span class="shrink-0">›</span>
                        <Link v-if="crumb.href" :href="crumb.href" class="truncate transition-colors hover:text-gray-900 dark:hover:text-gray-100">{{ crumb.label }}</Link>
                        <span v-else class="truncate font-medium text-gray-900 dark:text-gray-100">{{ crumb.label }}</span>
                    </template>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
                    <div class="relative" ref="accountMenuRef">
                        <button
                            @click="toggleAccountMenu"
                            class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-left transition hover:bg-gray-50 dark:border-green-900/60 dark:bg-green-950/60 dark:hover:bg-green-900/70"
                        >
                            <div class="flex size-7 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                {{ user?.name?.charAt(0)?.toUpperCase() }}
                            </div>
                            <div class="hidden min-w-0 sm:block">
                                <p class="max-w-[120px] truncate text-xs font-medium text-gray-700 dark:text-gray-200">{{ user?.name }}</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 capitalize">{{ user?.role }}</p>
                            </div>
                            <svg class="size-3.5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            v-if="accountMenuOpen"
                            class="absolute right-0 z-50 mt-2 w-52 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-green-900/60 dark:bg-green-950"
                        >
                            <div class="border-b border-gray-100 px-4 py-3 dark:border-green-900/60">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ user?.name }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ user?.role }}</p>
                            </div>

                            <div class="p-2">
                                <button
                                    @click="
                                        toggleDark();
                                        closeAccountMenu();
                                    "
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-green-900/70"
                                >
                                    <svg v-if="!darkMode" class="size-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                    </svg>
                                    <svg v-else class="size-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span>{{ darkMode ? 'Switch to light mode' : 'Switch to dark mode' }}</span>
                                </button>

                                <button
                                    @click="logout"
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Logout</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto bg-transparent p-3 dark:bg-green-950/20 dark:text-gray-100 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
