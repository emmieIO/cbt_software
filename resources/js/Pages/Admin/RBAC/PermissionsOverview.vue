<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface PermissionDetail {
    name: string;
    description: string;
    usage: string;
    impact: string;
}

const props = defineProps<{
    groups: Record<string, PermissionDetail[]>;
}>();

const groupedEntries = computed(() => {
    return Object.entries(props.groups)
        .map(([groupName, items]) => ({
            key: groupName,
            label: groupName.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()),
            items: [...items].sort((a, b) => a.name.localeCompare(b.name)),
        }))
        .sort((a, b) => a.label.localeCompare(b.label));
});

const totalPermissions = computed(() => groupedEntries.value.reduce((total, group) => total + group.items.length, 0));

const criticalCount = computed(() =>
    groupedEntries.value.reduce(
        (total, group) => total + group.items.filter((item) => item.impact.toLowerCase().includes('critical')).length,
        0,
    ),
);

const highCount = computed(() =>
    groupedEntries.value.reduce((total, group) => total + group.items.filter((item) => item.impact.toLowerCase().includes('high')).length, 0),
);

const getImpactColor = (impact: string) => {
    const value = impact.toLowerCase();
    if (value.includes('critical')) return 'bg-rose-100 text-rose-700 border-rose-200';
    if (value.includes('high')) return 'bg-amber-100 text-amber-700 border-amber-200';
    if (value.includes('efficiency') || value.includes('reporting')) return 'bg-blue-100 text-blue-700 border-blue-200';
    return 'bg-slate-100 text-slate-700 border-slate-200';
};
</script>

<template>
    <AdminLayout>
        <Head title="RBAC Overview" />

        <div class="mx-auto max-w-7xl space-y-6 pb-12 sm:space-y-8">
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500">
                <Link href="/admin/dashboard" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <Link href="/admin/rbac/permissions" class="transition-colors hover:text-primary">Permissions</Link>
                <svg class="size-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-900">RBAC Overview</span>
            </nav>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-widest text-slate-500 uppercase">Access Governance</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">RBAC Overview</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Operational summary of permission domains, risk intensity, and usage scope across the platform.
                    </p>
                </div>
                <Link
                    href="/admin/rbac/permissions"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Permission Registry
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Permission Domains</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ groupedEntries.length }}</p>
                    <p class="mt-1 text-xs text-slate-500">Grouped control areas</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Total Keys</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ totalPermissions }}</p>
                    <p class="mt-1 text-xs text-slate-500">All permission definitions</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Critical Keys</p>
                    <p class="mt-2 text-3xl font-bold text-rose-700">{{ criticalCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Highest impact controls</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">High Keys</p>
                    <p class="mt-2 text-3xl font-bold text-amber-700">{{ highCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Elevated impact controls</p>
                </div>
            </div>

            <div class="space-y-6">
                <section
                    v-for="group in groupedEntries"
                    :key="group.key"
                    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">{{ group.label }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500">{{ group.items.length }} permission keys</p>
                        </div>
                        <span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600">Domain</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="permission in group.items"
                            :key="permission.name"
                            class="rounded-lg border border-slate-200 bg-slate-50/40 p-4"
                        >
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <p class="font-mono text-xs font-semibold text-slate-900">{{ permission.name }}</p>
                                <span :class="getImpactColor(permission.impact)" class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase">
                                    {{ permission.impact.split(':')[0] }}
                                </span>
                            </div>

                            <p class="text-xs leading-relaxed text-slate-600">{{ permission.description }}</p>

                            <div class="mt-3 rounded-md border border-slate-200 bg-white px-2.5 py-2">
                                <p class="text-[10px] font-semibold tracking-wider text-slate-500 uppercase">Usage Scope</p>
                                <p class="mt-1 text-xs font-semibold text-slate-800">{{ permission.usage }}</p>
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:p-5">
                <h3 class="text-sm font-bold text-slate-900">Compliance Note</h3>
                <p class="mt-2 text-xs leading-relaxed text-slate-600">
                    Permission keys are managed in source code and deployed through controlled releases. Role assignment should be reviewed periodically
                    to maintain least-privilege access across Admin, Staff, and Student contexts.
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
