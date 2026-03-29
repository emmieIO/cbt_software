<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PermissionsOverview from '@/actions/App/Http/Controllers/Admin/PermissionOverviewController';
import { index as rolesIndex } from '@/actions/App/Http/Controllers/Admin/RoleController';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface Permission {
    id: number;
    name: string;
}

defineProps<{
    permissions: Permission[];
}>();
</script>

<template>
    <AdminLayout>
        <Head title="System Permissions" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-800">RBAC</span>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-800">Permissions</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">System Permissions</h1>
                    <p class="mt-1 text-sm text-gray-500">A read-only repository of permissions hard-coded into the application logic.</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="PermissionsOverview().url"
                        class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        <svg class="size-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M9 12l2 2 4-4m5.618-4.016A3.333 3.333 0 0118 3.333a3.333 3.333 0 01-3.333 3.333 3.333 3.333 0 01-3.334-3.333C11.333 3.333 11.333 3.333 11.333 3.333c0 .001 0 .001 0 .001a3.333 3.333 0 01-3.333 3.333 3.333 3.333 0 01-3.333-3.333 3.333 3.333 0 01-3.334 3.333C1.333 3.333 1.333 3.333 1.333 3.333"
                            />
                        </svg>
                        <span class="text-xs font-semibold tracking-wider uppercase">Governance Overview</span>
                    </Link>
                    <div class="inline-flex items-center gap-x-1.5 rounded-lg border border-amber-100 bg-amber-50 px-3 py-1.5 text-amber-600">
                        <svg class="size-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span class="text-[10px] font-semibold tracking-widest uppercase">System Locked</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                <div
                    v-for="permission in permissions"
                    :key="permission.id"
                    class="group flex flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-all hover:shadow-md md:p-5"
                >
                    <div class="flex items-center gap-3">
                        <div class="size-2 rounded-full bg-gray-300 transition-colors group-hover:bg-primary"></div>
                        <span class="text-xs font-semibold tracking-widest text-gray-800 uppercase">{{ permission.name }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 md:p-6">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="size-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <div class="ms-3">
                        <h4 class="text-sm font-semibold tracking-tight text-gray-800 uppercase">Developer Note</h4>
                        <p class="mt-2 text-xs leading-relaxed text-gray-500">
                            Permissions are immutable via the dashboard to ensure system stability. New permissions must be defined in code and seeded
                            during feature development. Admins can still map these permissions to custom roles in the
                            <Link :href="rolesIndex().url" class="font-semibold text-primary hover:underline">System Roles</Link> section.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
