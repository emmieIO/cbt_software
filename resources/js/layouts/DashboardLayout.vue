<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface NavItem {
    name: string;
    href: string;
    icon?: any;
    active?: boolean;
}

const props = defineProps<{
    title: string;
    navigation: NavItem[];
    logoutAction: string;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const userInitials = computed(() => {
    if (!user.value?.name) return '?';
    return user.value.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
});
</script>

<template>
    <div class="flex min-h-screen bg-slate-50 font-sans">
        <!-- Sidebar -->
        <aside class="hidden w-64 flex-col bg-primary text-white md:flex">
            <div class="flex h-20 items-center justify-center border-b border-white/10 px-6">
                <Link href="/">
                    <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-12 w-auto" />
                </Link>
            </div>
            
            <nav class="flex-1 space-y-1 p-4">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center rounded-xl px-4 py-3 font-medium transition-all group',
                        item.active 
                            ? 'bg-lemon-yellow/10 font-semibold text-lemon-yellow' 
                            : 'text-white/70 hover:bg-white/5 hover:text-white'
                    ]"
                >
                    <component 
                        v-if="item.icon" 
                        :is="item.icon" 
                        class="mr-3 h-6 w-6"
                        :class="item.active ? 'text-lemon-yellow' : 'text-white/50 group-hover:text-white'"
                    />
                    {{ item.name }}
                </Link>
            </nav>

            <div class="p-4">
                <Link
                    :href="logoutAction"
                    method="post"
                    as="button"
                    class="flex w-full items-center rounded-xl bg-white/5 px-4 py-3 font-medium text-white/70 transition-colors hover:bg-red-500/20 hover:text-red-200"
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
        <main class="flex-1 flex flex-col min-h-screen">
            <!-- Header -->
            <header class="flex h-20 items-center justify-between bg-white px-8 shadow-sm shrink-0">
                <div class="flex items-center md:hidden">
                    <img src="/assets/img/chrisland-school-logo.png" alt="Logo" class="h-10 w-auto" />
                </div>
                <div class="hidden md:block">
                    <h2 class="text-xl font-bold text-slate-800">{{ title }}</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex flex-col items-end mr-2 hidden sm:flex">
                        <span class="text-sm font-bold text-slate-700">{{ user?.name }}</span>
                        <span class="text-xs text-slate-500 capitalize">{{ user?.roles?.[0]?.name }}</span>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary font-bold text-white shadow-inner">
                        {{ userInitials }}
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="flex-1 overflow-y-auto">
                <slot />
            </div>
        </main>
    </div>
</template>
