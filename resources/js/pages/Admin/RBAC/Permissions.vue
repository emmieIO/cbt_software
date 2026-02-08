<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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

        <div class="space-y-8">
            <div class="flex items-center justify-between border-b border-slate-100 pb-8">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">System Permissions</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">A read-only repository of permissions hard-coded into the application logic.</p>
                </div>
                <div
                    class="flex items-center gap-2 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-2 text-[10px] font-black tracking-widest text-amber-600 uppercase"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    System Locked
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                <div
                    v-for="permission in permissions"
                    :key="permission.id"
                    class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition-all hover:border-primary/20"
                >
                    <div class="flex items-center gap-3">
                        <div class="h-2 w-2 rounded-full bg-slate-200 transition-colors group-hover:bg-primary"></div>
                        <span class="text-[11px] font-black tracking-widest text-slate-700 uppercase">{{ permission.name }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-400 shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black tracking-tight text-slate-900 uppercase">Developer Note</h4>
                        <p class="mt-1 text-xs leading-relaxed font-medium text-slate-500">
                            Permissions are immutable via the dashboard to ensure system stability. New permissions must be defined in code and seeded
                            during feature development. Admins can still map these permissions to custom roles in the
                            <Link :href="rolesIndex().url" class="font-black text-primary hover:underline">System Roles</Link> section.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
