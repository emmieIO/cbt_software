<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { index as questionGenerationsIndex } from '@/actions/App/Http/Controllers/QuestionGenerationController';
import ToastList from '@/components/ToastList.vue';
import { index as knowledgeDocumentsIndex } from '@/routes/knowledge-documents';
import { show as showProfile } from '@/routes/profile';
import { create as createQuestion } from '@/routes/questions';

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);
const userInitials = computed(() =>
    (user.value?.name ?? '')
        .split(' ')
        .map((part: string) => part.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase(),
);
const sidebarOpen = ref(false);
const sidebarCollapsed = ref(localStorage.getItem('sidebarCollapsed') === 'true');
const accountMenuOpen = ref(false);
const accountMenuRef = ref<HTMLElement | null>(null);

const pageLabels: Record<string, string> = {
    dashboard: 'Dashboard',
    questions: 'Questions',
    'questions/batch/create': 'Batch Import',
    'questions/create': 'New Question',
    subjects: 'Subjects',
    topics: 'Topics',
    'knowledge-documents': 'Knowledge Base',
    'question-generations': 'AI Question Generator',
    exams: 'Exams',
    'exam-titles': 'Exam Titles',
    'academic-sessions': 'Academic Sessions',
    'exams/create': 'Create Exam',
    users: 'Users',
    export: 'Export',
    profile: 'Profile',
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

const currentPageTitle = computed(() => breadcrumbs.value.at(-1)?.label ?? 'Dashboard');

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem('sidebarCollapsed', String(sidebarCollapsed.value));
};

interface NavItem {
    name: string;
    href: string;
    section: string;
    icon: string;
    adminOnly?: boolean;
    permission?: string;
}

const navItems: NavItem[] = [
    {
        name: 'Dashboard',
        href: '/dashboard',
        section: 'Workspace',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    },
    {
        name: 'Questions',
        href: '/questions',
        section: 'Workspace',
        icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    },
    { name: 'Exams', href: '/exams', section: 'Workspace', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
    {
        name: 'Exam Titles',
        href: '/exam-titles',
        section: 'Administration',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z',
        adminOnly: true,
    },
    {
        name: 'Academic Sessions',
        href: '/academic-sessions',
        section: 'Administration',
        icon: 'M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z',
        adminOnly: true,
    },
    {
        name: 'Subjects',
        href: '/subjects',
        section: 'Curriculum',
        icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    },
    {
        name: 'Topics',
        href: '/topics',
        section: 'Curriculum',
        icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
    },
    {
        name: 'Knowledge Base',
        href: knowledgeDocumentsIndex.url(),
        section: 'Curriculum',
        icon: 'M9 12h6m-6 4h6M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v6h6',
        permission: 'can_access_knowledge_base',
    },
    {
        name: 'AI Question Generator',
        href: questionGenerationsIndex.url(),
        section: 'Curriculum',
        icon: 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5m4.75-11.396c.251.023.501.052.75.088m-.75-.088a24.301 24.301 0 00-5.4.092M5 14.5l-1.5 2.25h15L17 14.5m-12 0h12m0 0-4.091-4.091a2.25 2.25 0 01-.659-1.591V3.104m0 0c-.251.023-.501.052-.75.088m.75-.088a24.301 24.301 0 015.4.092',
        permission: 'can_generate_questions',
    },
    {
        name: 'Users',
        href: '/users',
        section: 'Administration',
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        adminOnly: true,
    },
];

const visibleNav = computed(() => {
    if (user.value?.role === 'admin') return navItems;
    return navItems.filter((item) => !item.adminOnly && (!item.permission || Boolean(user.value?.[item.permission])));
});

const navSections = computed(() =>
    ['Workspace', 'Curriculum', 'Administration']
        .map((name) => ({ name, items: visibleNav.value.filter((item) => item.section === name) }))
        .filter((section) => section.items.length > 0),
);

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
    localStorage.removeItem('darkMode');
    document.documentElement.classList.remove('dark');
    document.addEventListener('click', handleDocumentClick);
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('click', handleDocumentClick);
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <div class="flex h-dvh overflow-hidden overscroll-none bg-gray-50 dark:bg-[color:var(--color-canvas-dark)]">
        <ToastList />
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden dark:bg-black/70" @click="sidebarOpen = false" />

        <aside
            class="fixed inset-y-0 left-0 z-50 flex min-h-0 w-68 shrink-0 flex-col overflow-hidden border-r border-white/10 bg-[#124b29] shadow-2xl transition-[width,transform] duration-200 lg:static lg:translate-x-0 lg:shadow-[8px_0_30px_rgba(8,65,23,0.08)]"
            :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'lg:w-20' : 'lg:w-68']"
        >
            <div
                class="flex h-20 shrink-0 items-center gap-3.5 border-b border-white/10 px-5"
                :class="sidebarCollapsed ? 'lg:justify-center lg:px-3' : ''"
            >
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white p-1.5 shadow-sm ring-1 ring-black/5">
                    <img src="/assets/img/chrisland-school-logo.png" alt="Chrisland Schools" class="max-h-full w-auto" />
                </div>
                <div class="min-w-0 leading-tight" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                    <span class="block truncate text-sm font-bold text-white">Chrisland Schools</span>
                    <span class="mt-1 block truncate text-[11px] font-medium tracking-[0.08em] text-emerald-200/55 uppercase"
                        >Assessment Operations</span
                    >
                </div>
                <button
                    type="button"
                    class="ml-auto flex size-8 items-center justify-center rounded-lg text-white/50 hover:bg-white/10 hover:text-white lg:hidden"
                    aria-label="Close navigation"
                    @click="sidebarOpen = false"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain px-3 py-5" aria-label="Primary navigation">
                <section v-for="section in navSections" :key="section.name">
                    <p
                        class="mb-2 px-3 text-[10px] font-semibold tracking-[0.1em] text-white/35 uppercase"
                        :class="sidebarCollapsed ? 'lg:sr-only' : ''"
                    >
                        {{ section.name }}
                    </p>
                    <div class="space-y-1">
                        <Link
                            v-for="item in section.items"
                            :key="item.name"
                            :href="item.href"
                            :title="sidebarCollapsed ? item.name : undefined"
                            prefetch
                            class="group relative flex h-11 items-center gap-3 rounded-lg px-2.5 text-sm font-medium transition-all duration-150"
                            :class="[
                                isActive(item.href)
                                    ? 'bg-[#f2f8f3] text-[#123c20] shadow-[0_6px_20px_rgba(0,0,0,0.16)]'
                                    : 'text-white/65 hover:bg-white/[0.07] hover:text-white',
                                sidebarCollapsed ? 'lg:justify-center lg:px-0' : '',
                            ]"
                        >
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-md transition-colors"
                                :class="
                                    isActive(item.href)
                                        ? 'bg-[#dcecdf] text-[#0b5225]'
                                        : 'text-white/45 group-hover:bg-white/[0.06] group-hover:text-white/80'
                                "
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                </svg>
                            </span>
                            <span class="truncate" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ item.name }}</span>
                            <span
                                v-if="isActive(item.href)"
                                class="ml-auto size-1.5 rounded-full bg-[#16833a]"
                                :class="sidebarCollapsed ? 'lg:hidden' : ''"
                            />
                        </Link>
                    </div>
                </section>
            </nav>

            <div class="shrink-0 border-t border-white/10 p-3">
                <Link
                    :href="showProfile.url()"
                    class="group flex h-12 w-full items-center gap-3 rounded-lg px-2 transition hover:bg-white/[0.07]"
                    :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                    title="Open profile"
                >
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-xs font-bold text-[#14532d] shadow-sm ring-1 ring-white/20 transition group-hover:bg-white"
                    >
                        {{ userInitials }}
                    </span>
                    <span class="min-w-0 flex-1 text-left" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                        <span class="block text-xs font-semibold text-white">My profile</span>
                        <span class="mt-0.5 block text-[10px] text-white/40">Account settings</span>
                    </span>
                    <svg
                        class="size-4 shrink-0 text-white/30 transition group-hover:translate-x-0.5 group-hover:text-white/60"
                        :class="sidebarCollapsed ? 'lg:hidden' : ''"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>
        </aside>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden dark:bg-[color:var(--color-canvas-dark)]">
            <header
                class="flex h-20 shrink-0 items-center gap-2 border-b border-gray-200/80 bg-white/95 px-3 backdrop-blur-sm sm:gap-4 sm:px-5 lg:px-7 dark:border-green-950 dark:bg-green-950/95"
            >
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="flex size-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 lg:hidden"
                    aria-label="Open navigation"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <button
                    type="button"
                    class="hidden size-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 lg:flex"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    @click="toggleSidebar"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16M4 19h16M4 12h10" />
                        <path stroke-linecap="round" stroke-linejoin="round" :d="sidebarCollapsed ? 'M17 9l3 3-3 3' : 'M20 9l-3 3 3 3'" />
                    </svg>
                </button>

                <div class="hidden h-8 w-px bg-gray-200 lg:block"></div>

                <div class="min-w-0">
                    <h1 class="truncate text-sm font-bold text-gray-950 sm:text-base">{{ currentPageTitle }}</h1>
                    <div class="mt-0.5 hidden min-w-0 items-center gap-1.5 overflow-hidden text-[11px] text-gray-400 sm:flex">
                        <Link href="/dashboard" class="shrink-0 font-medium text-gray-400 hover:text-primary">Home</Link>
                        <template v-for="(crumb, i) in breadcrumbs" :key="i">
                            <svg class="size-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="truncate" :class="i === breadcrumbs.length - 1 ? 'text-gray-600' : ''">{{ crumb.label }}</span>
                        </template>
                    </div>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
                    <Link
                        v-if="user?.can_create_questions"
                        :href="createQuestion.url()"
                        class="hidden h-9 items-center gap-2 rounded-lg bg-primary px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-primary/90 md:inline-flex"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        New question
                    </Link>
                    <div class="relative" ref="accountMenuRef">
                        <button
                            @click="toggleAccountMenu"
                            class="flex items-center gap-2.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-left shadow-sm transition hover:border-gray-300 hover:bg-gray-50 dark:border-green-900/60 dark:bg-green-950/60 dark:hover:bg-green-900/70"
                        >
                            <div class="flex size-7 items-center justify-center rounded-md bg-primary/10 text-[10px] font-bold text-primary">
                                {{ userInitials }}
                            </div>
                            <div class="hidden min-w-0 sm:block">
                                <p class="max-w-[120px] truncate text-xs font-medium text-gray-700 dark:text-gray-200">{{ user?.name }}</p>
                                <p class="text-[11px] text-gray-500 capitalize dark:text-gray-400">{{ user?.role }}</p>
                            </div>
                            <svg
                                class="size-3.5 text-gray-400 dark:text-gray-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            v-if="accountMenuOpen"
                            class="absolute right-0 z-50 mt-2 w-52 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-green-900/60 dark:bg-green-950"
                        >
                            <div class="border-b border-gray-100 px-4 py-3 dark:border-green-900/60">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ user?.name }}</p>
                                <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">@{{ user?.username }}</p>
                            </div>

                            <div class="p-2">
                                <Link
                                    :href="showProfile.url()"
                                    @click="closeAccountMenu"
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-green-900/70"
                                >
                                    <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M5.121 17.804A9 9 0 1118.879 17.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                    <span>Profile settings</span>
                                </Link>

                                <button
                                    @click="logout"
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                        />
                                    </svg>
                                    <span>Logout</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main
                scroll-region
                class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-transparent px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-7 dark:bg-green-950/20 dark:text-gray-100"
            >
                <slot />
            </main>
        </div>
    </div>
</template>
