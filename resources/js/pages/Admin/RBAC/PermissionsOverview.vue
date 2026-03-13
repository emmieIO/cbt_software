<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface PermissionDetail {
    name: string;
    description: string;
    usage: string;
    impact: string;
}

defineProps<{
    groups: Record<string, PermissionDetail[]>;
}>();

const getImpactColor = (impact: string) => {
    const imp = impact.toLowerCase();
    if (imp.includes('critical')) return 'text-red-600 bg-red-50 border-red-100';
    if (imp.includes('high')) return 'text-orange-600 bg-orange-50 border-orange-100';
    if (imp.includes('efficiency') || imp.includes('reporting')) return 'text-primary bg-primary/5 border-primary/10';
    return 'text-slate-600 bg-slate-50 border-slate-100';
};
</script>

<template>
    <AdminLayout>
        <Head title="Governance & Permission Architecture" />

        <div class="space-y-10 pb-20">
            <!-- Board Review Header -->
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-8">
                <div class="max-w-2xl">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-white shadow-md">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A3.333 3.333 0 0118 3.333a3.333 3.333 0 01-3.333 3.333 3.333 3.333 0 01-3.334-3.333C11.333 3.333 11.333 3.333 11.333 3.333c0 .001 0 .001 0 .001a3.333 3.333 0 01-3.333 3.333 3.333 3.333 0 01-3.333-3.333 3.333 3.333 0 01-3.334 3.333C1.333 3.333 1.333 3.333 1.333 3.333" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900">Governance Architecture</h1>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">Security Protocols & Access Matrix</h2>
                    <p class="text-sm leading-relaxed text-gray-500">
                        This document provides a full disclosure of the Chrisland CBT security layers. 
                        All system permissions are mapped below by operational group, impact level, and implementation scope.
                    </p>
                </div>
                <div class="shrink-0">
                    <div class="rounded-xl border border-gray-200 p-4 bg-white shadow-sm text-center">
                        <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Status</span>
                        <span class="text-sm font-bold text-primary">Board Verification v1.1</span>
                    </div>
                </div>
            </div>

            <!-- Grouped Permissions -->
            <div v-for="(items, groupName) in groups" :key="groupName" class="space-y-6">
                <div class="flex items-center gap-4">
                    <h3 class="text-xs font-bold tracking-widest text-gray-400 uppercase whitespace-nowrap">{{ groupName }}</h3>
                    <div class="h-px w-full bg-gray-100"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div 
                        v-for="permission in items" 
                        :key="permission.name"
                        class="group relative flex flex-col rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all hover:border-primary/50 hover:shadow-md"
                    >
                        <!-- Header -->
                        <div class="mb-6 flex items-start justify-between">
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold tracking-wider text-primary uppercase">Permission Key</span>
                                <h3 class="text-base font-semibold text-gray-900 font-mono">{{ permission.name }}</h3>
                            </div>
                            <div 
                                :class="[
                                    'rounded-full px-2.5 py-1 text-xs font-semibold uppercase border transition-all',
                                    getImpactColor(permission.impact)
                                ]"
                            >
                                {{ permission.impact.split(':')[0] }}
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="flex-1 space-y-4">
                            <p class="text-sm text-gray-600">
                                {{ permission.description }}
                            </p>
                            
                            <div class="rounded-lg bg-gray-50 p-4 border border-gray-100">
                                <span class="block text-xs font-semibold tracking-wider text-gray-400 uppercase mb-2">Scope</span>
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-1.5 rounded-full bg-primary/40"></div>
                                    <span class="text-xs font-bold text-gray-700 uppercase">{{ permission.usage }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Security Statement -->
            <div class="rounded-xl bg-gray-900 p-8 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10 max-w-3xl">
                    <h3 class="text-xl font-bold tracking-tight mb-4">Security & Audit Integrity</h3>
                    <p class="text-sm font-medium text-gray-400 leading-relaxed">
                        The Chrisland CBT Software employs a rigorous multi-guard authentication system. 
                        Permissions are immutable once assigned to a role unless authorized by a Super Admin. 
                        Technical Note: Permissions are isolated across Admin, Staff, and Student guards to prevent cross-portal privilege escalation.
                    </p>
                </div>
                <svg class="absolute right-0 bottom-0 h-48 w-48 text-white/5 -mb-12 -mr-12" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                </svg>
            </div>
        </div>
    </AdminLayout>
</template>
