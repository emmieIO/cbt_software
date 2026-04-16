<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import StudentLayout from '@/layouts/StudentLayout.vue';
import type { User } from '@/types/auth';

const props = defineProps<{
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
const branchName = computed(() => {
    if (!props.user.school_id) return null;
    return branches.value[props.user.school_id]?.name || props.user.school?.name || null;
});
const primaryRole = computed(() => formatRole(props.user.roles[0] || 'User'));
const hasAssignments = computed(() => (props.user.assignments?.length || 0) > 0);

const Layout = computed(() => {
    if (permissions.value.includes('access:admin-portal')) return AdminLayout;
    if (permissions.value.includes('access:staff-portal')) return StaffLayout;
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

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="relative overflow-hidden rounded-2xl border border-gray-200 from-white via-white to-primary/5 shadow-sm">
                <div class="pointer-events-none absolute -top-16 -right-16 size-48 rounded-full bg-primary/10 blur-2xl"></div>
                <div class="relative flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                    <div class="flex items-center gap-4">
                        <div class="flex size-20 items-center justify-center rounded-2xl bg-primary text-3xl font-black text-white shadow-md">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <p class="text-xs font-black tracking-widest text-gray-500 uppercase">Account Profile</p>
                            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ user.name }}</h1>
                            <p class="mt-1 text-sm text-gray-600">{{ user.email }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-primary/15 bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                            {{ primaryRole }}
                        </span>
                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            {{ user.status || 'active' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-5 py-4">
                            <h3 class="text-sm font-bold text-gray-800">Identity Details</h3>
                            <p class="text-xs text-gray-500">Core account and access information</p>
                        </div>
                        <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2">
                            <div>
                                <p class="text-[11px] font-black tracking-widest text-gray-400 uppercase">Portal Username</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">{{ user.username }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-black tracking-widest text-gray-400 uppercase">System Role</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">{{ primaryRole }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-black tracking-widest text-gray-400 uppercase">User ID</p>
                                <p class="mt-1 break-all text-sm font-semibold text-gray-800">{{ user.id }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-black tracking-widest text-gray-400 uppercase">Joined</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">{{ formatDate(user.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-5 py-4">
                            <h3 class="text-sm font-bold text-gray-800">Institutional Context</h3>
                            <p class="text-xs text-gray-500">Campus and academic scope</p>
                        </div>
                        <div class="space-y-5 p-5">
                            <div v-if="branchName">
                                <p class="text-[11px] font-black tracking-widest text-gray-400 uppercase">Assigned Campus</p>
                                <p class="mt-1 text-sm font-semibold text-primary">{{ branchName }}</p>
                            </div>

                            <div v-if="user.school_class">
                                <p class="text-[11px] font-black tracking-widest text-gray-400 uppercase">Enrollment Class</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">{{ user.school_class.name }}</p>
                            </div>

                            <div v-if="hasAssignments">
                                <p class="mb-3 text-[11px] font-black tracking-widest text-gray-400 uppercase">Operational Load</p>
                                <div class="space-y-2">
                                    <div
                                        v-for="load in user.assignments"
                                        :key="load.id"
                                        class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2"
                                    >
                                        <span class="text-xs font-semibold text-gray-700">{{ load.subject?.name || 'Academic Coordinator' }}</span>
                                        <span class="rounded-md bg-white px-2 py-0.5 text-[10px] font-bold text-primary">{{ load.school_class?.name || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-[11px] font-black tracking-widest text-gray-400 uppercase">Account Status</p>
                        <div class="mt-3 flex items-center gap-2">
                            <span class="size-2 rounded-full" :class="user.status === 'inactive' ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                            <p class="text-sm font-semibold text-gray-800">{{ user.status === 'inactive' ? 'Inactive' : 'Active' }}</p>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Access and permissions are managed by administrators.</p>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-5">
                        <p class="text-[11px] font-black tracking-widest text-blue-700 uppercase">Need Changes?</p>
                        <p class="mt-2 text-sm leading-relaxed text-blue-900">
                            To update sensitive profile information, contact the Information Technology team.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
