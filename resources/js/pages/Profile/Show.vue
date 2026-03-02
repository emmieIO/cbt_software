<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import StudentLayout from '@/layouts/StudentLayout.vue';
import type { User } from '@/types/auth';

const props = defineProps<{
    user: User & {
        username: string;
        school_id: string | null;
        school_class?: { name: string };
        assignments?: Array<{
            id: string;
            subject: { name: string };
            school_class?: { name: string };
            prospective_class?: { name: string };
        }>;
        status: string;
    };
}>();

const roles = computed(() => props.user.roles || []);

const Layout = computed(() => {
    if (roles.value.includes('admin')) return AdminLayout;
    if (roles.value.includes('staff') || roles.value.includes('subject_lead')) return StaffLayout;
    return StudentLayout;
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <component :is="Layout">
        <Head title="My Profile" />

        <div class="space-y-10">
            <!-- Page Header (From UI Concept) -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">Account Profile</h1>
                    <p class="mt-1 text-sm font-bold tracking-widest text-slate-400 uppercase">User Details • {{ roles[0] }} Identity</p>
                </div>
            </div>

            <!-- Header Card (Refined Chrisland Identity) -->
            <div class="relative overflow-hidden rounded-xl border border-slate-100 bg-white p-10 shadow-sm transition-all hover:shadow-md">
                <div class="relative z-10 flex flex-col gap-8 md:flex-row md:items-center">
                    <!-- Avatar with Primary Glow -->
                    <div
                        class="flex h-24 w-24 shrink-0 items-center justify-center rounded-xl bg-primary text-4xl font-black text-white shadow-xl ring-4 shadow-primary/20 ring-white"
                    >
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>

                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-4">
                            <h1 class="text-4xl font-black tracking-tight text-slate-900">{{ user.name }}</h1>
                            <span
                                class="rounded-lg border border-lemon-yellow/20 bg-lemon-yellow px-3 py-1 text-[10px] font-black tracking-widest text-primary uppercase shadow-sm"
                            >
                                {{ roles[0] || 'User' }}
                            </span>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-6">
                            <div class="flex items-center gap-2 text-sm font-bold text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                    />
                                </svg>
                                {{ user.email }}
                            </div>
                            <div class="hidden h-1 w-1 rounded-full bg-slate-300 md:block"></div>
                            <div class="flex items-center gap-2 text-sm font-bold tracking-widest text-slate-400 uppercase">
                                <span class="h-2 w-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]"></span>
                                Account Verified
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Subtle Branding Gradient -->
                <div class="pointer-events-none absolute top-0 right-0 h-full w-1/3 bg-linear-to-l from-primary/[0.03] to-transparent"></div>
            </div>

            <!-- Profile Details Grid -->
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                <!-- Personal Info -->
                <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm transition-all hover:shadow-md">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-8 py-5">
                        <h3 class="flex items-center gap-2 text-xs font-black tracking-widest text-slate-800 uppercase">
                            <div class="h-1.5 w-1.5 rounded-full bg-primary"></div>
                            Account Identity
                        </h3>
                    </div>
                    <div class="space-y-6 p-8">
                        <div>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Login Username</p>
                            <p class="mt-1 text-lg font-black text-slate-800">{{ user.username }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">System ID</p>
                            <p class="mt-1 text-lg font-black tracking-tighter text-slate-800">{{ user.school_id || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Verification Status</p>
                            <p class="mt-1 inline-flex items-center gap-2 text-lg font-black text-green-600">
                                <span class="h-2 w-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]"></span>
                                {{ user.status || 'Active' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Academic Info -->
                <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm transition-all hover:shadow-md">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-8 py-5">
                        <h3 class="flex items-center gap-2 text-xs font-black tracking-widest text-slate-800 uppercase">
                            <div class="h-1.5 w-1.5 rounded-full bg-lemon-yellow"></div>
                            Institutional Context
                        </h3>
                    </div>
                    <div class="space-y-6 p-8">
                        <div v-if="user.school_class">
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Regular Class</p>
                            <p class="mt-1 text-lg font-black text-slate-800">{{ user.school_class.name }}</p>
                        </div>

                        <div v-if="user.assignments && user.assignments.length > 0">
                            <p class="mb-3 text-[10px] font-black tracking-widest text-slate-400 uppercase">Teaching Assignments</p>
                            <div class="space-y-3">
                                <div
                                    v-for="load in user.assignments"
                                    :key="load.id"
                                    class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 p-4"
                                >
                                    <span class="text-xs font-black text-slate-700">{{ load.subject.name }}</span>
                                    <span
                                        class="rounded-lg border border-primary/10 bg-primary/5 px-2 py-1 text-[9px] font-black text-primary uppercase"
                                    >
                                        {{ load.school_class?.name || load.prospective_class?.name }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="roles.includes('staff')">
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Professional Designation</p>
                            <p class="mt-1 text-lg font-black tracking-tighter text-slate-800 uppercase">Academic Staff</p>
                        </div>

                        <div v-else-if="roles.includes('admin')">
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Administrative Scope</p>
                            <p class="mt-1 text-lg font-black tracking-tighter text-slate-800 uppercase">System Oversight</p>
                        </div>

                        <div>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Account Registered</p>
                            <p class="mt-1 text-lg font-black tracking-tighter text-slate-800">{{ formatDate(user.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Notice -->
            <div class="rounded-xl border border-dashed border-blue-50 bg-blue-50/30 p-8 text-center">
                <p class="mx-auto max-w-2xl text-sm leading-relaxed font-bold tracking-wide text-blue-800 uppercase">
                    Note: To modify sensitive account credentials or institutional records, please submit a request to the Information Technology
                    office.
                </p>
            </div>
        </div>
    </component>
</template>
