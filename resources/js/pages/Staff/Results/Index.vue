<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { PaginatedData } from '@/types/academics';


interface Exam {
    id: string;
    title: string;
    branch: string;
    subject: { name: string } | null;
    school_class?: { name: string };
    prospective_class?: { name: string };
    attempts_count: number;
    type: string;
}

const props = defineProps<{
    exams: PaginatedData<Exam>;
    branches: Record<string, { name: string; address: string; phones: string }>;
    filters: {
        branch?: string;
    };
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

// Filters
const branchFilter = ref(props.filters.branch || '');
const applyFilters = () => {
    router.get(router.page.url, { branch: branchFilter.value }, { preserveState: true });
};
</script>

<template>
    <component :is="Layout">
        <Head title="Examination Results" />

        <div class="space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="text-slate-500 transition-colors hover:text-slate-800">Dashboard</Link>
                <svg class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                <span class="text-slate-900">Results Dashboard</span>
            </nav>

            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="group flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white transition-all hover:border-slate-900 hover:text-slate-900 active:scale-95">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                        </Link>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900 italic">Results & Analytics</h1>
                    </div>
                    <p class="mt-2 text-sm font-bold tracking-widest text-slate-400 uppercase px-1">
                        Select an examination to review student performance.
                    </p>
                </div>
            </div>

            <!-- Branch Filter -->
            <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                <div class="flex h-10 items-center gap-2 rounded-lg border border-slate-100 bg-slate-50 px-4">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <select 
                        v-model="branchFilter" 
                        @change="applyFilters"
                        class="cursor-pointer border-none bg-transparent text-[10px] font-black text-slate-600 uppercase focus:ring-0"
                    >
                        <option value="">All My Branches</option>
                        <option v-for="(info, key) in branches" :key="key" :value="key">{{ info.name }}</option>
                    </select>
                </div>
                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase italic">Filter analytics by school</span>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="exam in exams.data"
                    :key="exam.id"
                    class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:border-primary/20 hover:shadow-2xl"
                >
                    <div class="relative z-10 flex h-full flex-col">
                        <div class="mb-6">
                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                <span class="rounded-xl bg-primary/5 px-2 py-1 text-[9px] font-black tracking-widest text-primary uppercase border border-primary/10">{{
                                    exam.subject?.name || 'Multi-Subject'
                                }}</span>
                                <div v-if="branches[exam.branch]" class="inline-flex items-center rounded-xl border border-slate-100 bg-slate-50 px-2 py-1 text-[8px] font-black text-slate-500 uppercase">
                                    {{ branches[exam.branch].name }}
                                </div>
                            </div>
                            <h3 class="mt-3 line-clamp-2 text-xl leading-tight font-black text-slate-800 transition-colors group-hover:text-primary">
                                {{ exam.title }}
                            </h3>
                            <p class="mt-2 text-[10px] font-bold tracking-tighter text-slate-400 uppercase">
                                {{ exam.type === 'entrance' ? exam.prospective_class?.name : exam.school_class?.name }}
                            </p>
                        </div>

                        <div class="mt-auto flex items-center justify-between border-t border-slate-50 pt-6">
                            <div class="flex flex-col">
                                <span class="text-2xl font-black tracking-tighter text-slate-900">{{ exam.attempts_count }}</span>
                                <span class="text-[9px] font-black tracking-widest text-slate-400 uppercase">Submissions</span>
                            </div>

                            <Link
                                :href="`/staff/exams/${exam.id}/results`"
                                class="rounded-xl bg-slate-900 px-6 py-3 text-[10px] font-black tracking-widest text-white uppercase shadow-lg transition-all hover:bg-black active:scale-95"
                            >
                                View Results &rarr;
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-if="exams.data.length === 0" class="col-span-full py-24 text-center opacity-30">
                    <p class="text-lg font-bold tracking-widest uppercase">No Examinations Found</p>
                </div>
            </div>
        </div>
    </component>
</template>
