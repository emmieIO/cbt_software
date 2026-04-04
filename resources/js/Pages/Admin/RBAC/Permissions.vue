<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PermissionsOverview from '@/actions/App/Http/Controllers/Admin/PermissionOverviewController';
import { index as rolesIndex } from '@/actions/App/Http/Controllers/Admin/RoleController';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface Permission {
    id: number;
    name: string;
}

const props = defineProps<{
    permissions: Permission[];
}>();

const totalPermissions = computed(() => props.permissions.length);

const groupedPermissions = computed(() => {
    const groups: Record<string, Permission[]> = {};

    for (const permission of props.permissions) {
        const [domain] = permission.name.split(':');
        const key = domain || 'general';
        groups[key] ||= [];
        groups[key].push(permission);
    }

    return Object.entries(groups)
        .map(([key, list]) => ({
            key,
            label: key.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()),
            count: list.length,
            permissions: list.sort((a, b) => a.name.localeCompare(b.name)),
        }))
        .sort((a, b) => a.label.localeCompare(b.label));
});
</script>

<template>
    <AdminLayout>
        <Head title="System Permissions" />

        <div class="mx-auto max-w-7xl space-y-6 pb-12 sm:space-y-8">
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500">
                <Link href="/admin/dashboard" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-900">RBAC</span>
                <svg class="size-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-900">Permissions</span>
            </nav>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-widest text-slate-500 uppercase">Access Governance</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">System Permissions</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Reference list of built-in permissions used by the platform. These entries are managed in code and cannot be edited here.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="PermissionsOverview().url"
                        class="inline-flex items-center gap-x-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50 focus:outline-none"
                    >
                        <svg class="size-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M9 12l2 2 4-4m5.618-4.016A3.333 3.333 0 0118 3.333a3.333 3.333 0 01-3.333 3.333 3.333 3.333 0 01-3.334-3.333C11.333 3.333 11.333 3.333 11.333 3.333c0 .001 0 .001 0 .001a3.333 3.333 0 01-3.333 3.333 3.333 3.333 0 01-3.333-3.333 3.333 3.333 0 01-3.334 3.333C1.333 3.333 1.333 3.333 1.333 3.333"
                            />
                        </svg>
                        <span class="text-xs font-semibold tracking-wider uppercase">RBAC Overview</span>
                    </Link>
                    <div class="inline-flex items-center gap-x-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-slate-700">
                        <svg class="size-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span class="text-[10px] font-semibold tracking-widest uppercase">Read Only</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
                <aside class="space-y-4 xl:col-span-3">
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Permission Registry</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ totalPermissions }}</p>
                        <p class="mt-1 text-xs text-slate-500">Total built-in permission keys</p>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-bold text-slate-900">Domains</h2>
                        <div class="mt-3 space-y-2">
                            <div
                                v-for="group in groupedPermissions"
                                :key="group.key"
                                class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2"
                            >
                                <span class="text-xs font-semibold text-slate-700">{{ group.label }}</span>
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">{{ group.count }}</span>
                            </div>
                        </div>
                    </section>
                </aside>

                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-9">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-bold text-slate-900">Permission Keys</h2>
                        <p class="mt-1 text-xs text-slate-500">Grouped by domain for faster discovery during role mapping.</p>
                    </div>

                    <div class="max-h-[70vh] overflow-y-auto p-5">
                        <div class="space-y-5">
                            <div
                                v-for="group in groupedPermissions"
                                :key="group.key"
                                class="rounded-lg border border-slate-200 bg-slate-50/50 p-4"
                            >
                                <div class="mb-3 flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-slate-900">{{ group.label }}</h3>
                                    <span class="rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-600"
                                        >{{ group.count }} keys</span
                                    >
                                </div>

                                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                    <div
                                        v-for="permission in group.permissions"
                                        :key="permission.id"
                                        class="rounded-md border border-slate-200 bg-white px-3 py-2"
                                    >
                                        <p class="font-mono text-xs text-slate-800">{{ permission.name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:p-5">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="size-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ms-3">
                        <h4 class="text-sm font-semibold tracking-tight text-slate-800 uppercase">Developer Note</h4>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600">
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
