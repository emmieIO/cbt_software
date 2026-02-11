<script setup lang="ts">
import { Head, usePage, Link } from '@inertiajs/vue3';
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
        status: string;
    };
}>();

const page = usePage();
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
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Account Profile</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400 uppercase tracking-widest">User Details • {{ roles[0] }} Identity</p>
                </div>
                <div class="flex items-center gap-3">
                    <button class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-600 uppercase shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.756 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.756 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.756 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.756 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.756 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.756 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.756 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Settings
                    </button>
                </div>
            </div>

            <!-- Header Card (Refined Chrisland Identity) -->
            <div class="relative overflow-hidden rounded-xl bg-white border border-slate-100 p-10 shadow-sm transition-all hover:shadow-md">
                <div class="relative z-10 flex flex-col gap-8 md:flex-row md:items-center">
                    <!-- Avatar with Primary Glow -->
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-xl bg-primary text-4xl font-black text-white shadow-xl shadow-primary/20 ring-4 ring-white">
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-4">
                            <h1 class="text-4xl font-black text-slate-900 tracking-tight">{{ user.name }}</h1>
                            <span class="rounded-lg bg-lemon-yellow px-3 py-1 text-[10px] font-black tracking-widest text-primary uppercase shadow-sm border border-lemon-yellow/20">
                                {{ roles[0] || 'User' }}
                            </span>
                        </div>
                        
                        <div class="mt-4 flex flex-wrap items-center gap-6">
                            <div class="flex items-center gap-2 text-sm font-bold text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ user.email }}
                            </div>
                            <div class="hidden h-1 w-1 rounded-full bg-slate-300 md:block"></div>
                            <div class="flex items-center gap-2 text-sm font-bold text-slate-400 uppercase tracking-widest">
                                <span class="h-2 w-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]"></span>
                                Account Verified
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 md:ml-auto">
                        <button class="rounded-lg bg-slate-50 px-4 py-2 text-[10px] font-black text-slate-400 uppercase tracking-widest transition-all hover:bg-slate-100 hover:text-slate-600">
                            View Activity
                        </button>
                    </div>
                </div>
                <!-- Subtle Branding Gradient -->
                <div class="absolute right-0 top-0 h-full w-1/3 bg-linear-to-l from-primary/[0.03] to-transparent pointer-events-none"></div>
            </div>

            <!-- Profile Details Grid -->
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                <!-- Personal Info -->
                <div class="rounded-xl border border-slate-100 bg-white overflow-hidden shadow-sm transition-all hover:shadow-md">
                    <div class="bg-slate-50/50 px-8 py-5 border-b border-slate-100">
                        <h3 class="text-xs font-black tracking-widest text-slate-800 uppercase flex items-center gap-2">
                            <div class="h-1.5 w-1.5 rounded-full bg-primary"></div>
                            Account Identity
                        </h3>
                    </div>
                    <div class="p-8 space-y-6">
                        <div>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Login Username</p>
                            <p class="mt-1 text-lg font-black text-slate-800">{{ user.username }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">System ID</p>
                            <p class="mt-1 text-lg font-black text-slate-800 tracking-tighter">{{ user.school_id || 'N/A' }}</p>
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
                <div class="rounded-xl border border-slate-100 bg-white overflow-hidden shadow-sm transition-all hover:shadow-md">
                    <div class="bg-slate-50/50 px-8 py-5 border-b border-slate-100">
                        <h3 class="text-xs font-black tracking-widest text-slate-800 uppercase flex items-center gap-2">
                            <div class="h-1.5 w-1.5 rounded-full bg-lemon-yellow"></div>
                            Institutional Context
                        </h3>
                    </div>
                    <div class="p-8 space-y-6">
                        <div v-if="user.school_class">
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Grade Assignment</p>
                            <p class="mt-1 text-lg font-black text-slate-800">{{ user.school_class.name }}</p>
                        </div>
                        <div v-else-if="roles.includes('staff')">
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Professional Designation</p>
                            <p class="mt-1 text-lg font-black text-slate-800 uppercase tracking-tighter">Academic Staff</p>
                        </div>
                        <div v-else-if="roles.includes('admin')">
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Administrative Scope</p>
                            <p class="mt-1 text-lg font-black text-slate-800 uppercase tracking-tighter">System Oversight</p>
                        </div>
                        
                        <div>
                            <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Enrollment Date</p>
                            <p class="mt-1 text-lg font-black text-slate-800 tracking-tighter">{{ formatDate(user.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Notice -->
            <div class="rounded-xl border border-blue-50 bg-blue-50/30 p-8 text-center border-dashed">
                <p class="text-sm font-bold text-blue-800 leading-relaxed max-w-2xl mx-auto uppercase tracking-wide">
                    Note: To modify sensitive account credentials or institutional records, please submit a request to the Information Technology office.
                </p>
            </div>
        </div>
    </component>
</template>