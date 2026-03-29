<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import StudentLayout from '@/layouts/StudentLayout.vue';
import type { User } from '@/types/auth';

defineProps<{
    user: User & {
        username: string;
        school_id: string | null;
        school?: { name: string };
        school_class?: { name: string };
        roles: string[];
        assignments?: Array<{
            id: string;
            subject: { name: string };
            school_class?: { name: string };
        }>;
        status: string;
    };
}>();

const page = usePage();
const branches = computed(() => (page.props as any).branches || {});
const permissions = computed(() => (page.props.auth.user as any).permissions || []);

const Layout = computed(() => {
    if (permissions.value.includes('sys:manage_settings')) return AdminLayout;
    if (permissions.value.includes('bank:view')) return StaffLayout;
    return StudentLayout;
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatRole = (role: string) => {
    return role
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};
</script>

<template>
    <component :is="Layout">
        <Head title="My Profile" />

        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Page Header -->
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">Account Profile</h1>
                <p class="mt-1 text-sm text-gray-500">Review your institutional identity and access credentials.</p>
            </div>

            <!-- Identity Card -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col items-center gap-8 p-6 sm:flex-row sm:p-10">
                    <div class="flex size-24 items-center justify-center rounded-2xl bg-primary text-4xl font-bold text-white shadow-md">
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="space-y-2 text-center sm:text-start">
                        <div class="flex flex-wrap items-center justify-center gap-3 sm:justify-start">
                            <h2 class="text-3xl font-bold text-gray-800">{{ user.name }}</h2>
                            <span
                                class="inline-flex items-center gap-x-1.5 rounded-full bg-primary/10 px-3 py-1.5 text-xs font-semibold tracking-wider text-primary uppercase"
                            >
                                Verified {{ formatRole(user.roles[0] || 'User') }}
                            </span>
                        </div>
                        <div class="flex flex-col items-center gap-2 text-sm font-medium text-gray-500 sm:flex-row sm:gap-6">
                            <span class="flex items-center gap-2">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                    />
                                </svg>
                                {{ user.email }}
                            </span>
                            <span class="hidden size-1 rounded-full bg-gray-300 sm:block"></span>
                            <span class="flex items-center gap-2 text-teal-600">
                                <span class="size-2 animate-pulse rounded-full bg-teal-500"></span>
                                Account Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- System Context -->
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 bg-gray-50/50 p-4">
                        <h3 class="text-xs font-semibold tracking-wider text-gray-800 uppercase">System Credentials</h3>
                    </div>
                    <div class="space-y-6 p-6">
                        <div>
                            <p class="text-xs font-medium tracking-widest text-gray-400 uppercase">Portal Username</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ user.username }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium tracking-widest text-gray-400 uppercase">Unique Identifier</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ user.id }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium tracking-widest text-gray-400 uppercase">Registration Date</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ formatDate(user.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Academic Scope -->
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 bg-gray-50/50 p-4">
                        <h3 class="text-xs font-semibold tracking-wider text-gray-800 uppercase">Institutional Context</h3>
                    </div>
                    <div class="space-y-6 p-6">
                        <div v-if="user.school_id && (branches[user.school_id] || user.school)">
                            <p class="text-xs font-medium tracking-widest text-gray-400 uppercase">Assigned Campus</p>
                            <p class="mt-1 text-sm font-semibold text-primary">
                                {{ branches[user.school_id]?.name || user.school?.name }}
                            </p>
                        </div>

                        <div v-if="user.school_class">
                            <p class="text-xs font-medium tracking-widest text-gray-400 uppercase">Enrollment Class</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ user.school_class.name }}</p>
                        </div>

                        <div v-if="user.assignments && user.assignments.length > 0">
                            <p class="mb-3 text-xs font-medium tracking-widest text-gray-400 uppercase">Verified Load</p>
                            <div class="space-y-2">
                                <div
                                    v-for="load in user.assignments"
                                    :key="load.id"
                                    class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 p-3"
                                >
                                    <span class="text-xs font-semibold text-gray-700">{{ load.subject?.name || 'Academic Coordinator' }}</span>
                                    <span class="rounded-md border border-primary/10 bg-white px-2 py-0.5 text-[10px] font-bold text-primary">
                                        {{ load.school_class?.name || 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-medium tracking-widest text-gray-400 uppercase">System Access Tier</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">
                                {{ formatRole(user.roles[0] || 'User') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warning Notice -->
            <div class="flex gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4">
                <svg class="size-5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <p class="text-xs leading-relaxed font-medium text-blue-800">
                    To modify sensitive institutional data or credentials, please contact the Information Technology department.
                </p>
            </div>
        </div>
    </component>
</template>
