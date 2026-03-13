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
            prospective_class?: { name: string };
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
    return role.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};
</script>

<template>
    <component :is="Layout">
        <Head title="My Profile" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Page Header -->
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">Account Profile</h1>
                <p class="text-sm text-gray-500 mt-1">Review your institutional identity and access credentials.</p>
            </div>

            <!-- Identity Card -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 sm:p-10 flex flex-col sm:flex-row items-center gap-8">
                    <div class="size-24 bg-primary rounded-2xl flex items-center justify-center text-4xl font-bold text-white shadow-md">
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="text-center sm:text-start space-y-2">
                        <div class="flex flex-wrap justify-center sm:justify-start items-center gap-3">
                            <h2 class="text-3xl font-bold text-gray-800">{{ user.name }}</h2>
                            <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-semibold bg-primary/10 text-primary uppercase tracking-wider">
                                Verified {{ formatRole(user.roles[0] || 'User') }}
                            </span>
                        </div>
                        <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-6 text-sm text-gray-500 font-medium">
                            <span class="flex items-center gap-2">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                {{ user.email }}
                            </span>
                            <span class="hidden sm:block size-1 bg-gray-300 rounded-full"></span>
                            <span class="flex items-center gap-2 text-teal-600">
                                <span class="size-2 bg-teal-500 rounded-full animate-pulse"></span>
                                Account Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- System Context -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm flex flex-col">
                    <div class="p-4 border-b border-gray-200 bg-gray-50/50">
                        <h3 class="text-xs font-semibold text-gray-800 uppercase tracking-wider">System Credentials</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">Portal Username</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ user.username }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">Unique Identifier</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ user.id }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">Registration Date</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ formatDate(user.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Academic Scope -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm flex flex-col">
                    <div class="p-4 border-b border-gray-200 bg-gray-50/50">
                        <h3 class="text-xs font-semibold text-gray-800 uppercase tracking-wider">Institutional Context</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div v-if="user.school_id && (branches[user.school_id] || user.school)">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">Assigned Campus</p>
                            <p class="mt-1 text-sm font-semibold text-primary">
                                {{ branches[user.school_id]?.name || user.school?.name }}
                            </p>
                        </div>

                        <div v-if="user.school_class">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">Enrollment Class</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ user.school_class.name }}</p>
                        </div>

                        <div v-if="user.assignments && user.assignments.length > 0">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-widest mb-3">Verified Load</p>
                            <div class="space-y-2">
                                <div v-for="load in user.assignments" :key="load.id" class="p-3 bg-gray-50 border border-gray-100 rounded-lg flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-700">{{ load.subject?.name || 'Academic Coordinator' }}</span>
                                    <span class="text-[10px] font-bold text-primary bg-white border border-primary/10 px-2 py-0.5 rounded-md">
                                        {{ load.school_class?.name || load.prospective_class?.name }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">System Access Tier</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">
                                {{ formatRole(user.roles[0] || 'User') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warning Notice -->
            <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl flex gap-3">
                <svg class="size-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="text-xs text-blue-800 font-medium leading-relaxed">
                    To modify sensitive institutional data or credentials, please contact the Information Technology department.
                </p>
            </div>
        </div>
    </component>
</template>
