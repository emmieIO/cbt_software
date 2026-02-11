<script setup lang="ts">
import StaffLayout from '@/layouts/StaffLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        assignedClasses: number;
        pendingResults: number;
        questionBankCount: number;
    };
    schedule: Array<{
        id: number;
        title: string;
        time: string;
        location: string;
        type: string;
        color: string;
    }>;
}>();

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name || 'Staff');

const hubs = [
    {
        title: 'Question Management',
        description: 'Build and manage your assessment content.',
        items: [
            { name: 'Question Bank', href: '/staff/questions', icon: '📁' },
            { name: 'AI Question Lab', href: '/staff/questions/generate', icon: '🤖' },
            { name: 'Create New', href: '/staff/questions/create', icon: '✍️' },
        ]
    },
    {
        title: 'Exam Operations',
        description: 'Configure and monitor your examinations.',
        items: [
            { name: 'Manage Exams', href: '/staff/exams', icon: '📝' },
            { name: 'New Exam', href: '/staff/exams/create', icon: '➕' },
            { name: 'Results & Grading', href: '#', icon: '📊' },
        ]
    }
];
</script>

<template>
    <StaffLayout>
        <Head title="Staff Hub" />

        <div class="space-y-10">
            <!-- Page Header (From UI Concept) -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Teacher Hub</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400">Welcome back, {{ userName }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="'/staff/questions/generate'" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-600 uppercase shadow-sm transition-all hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        AI Lab
                    </Link>
                    <Link :href="'/staff/exams/create'" class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-black text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Exam
                    </Link>
                </div>
            </div>

            <!-- Welcome Hero Section -->
            <div class="relative overflow-hidden rounded-xl bg-primary px-12 py-16 text-white shadow-2xl shadow-primary/20">
                <div class="relative z-10 max-w-2xl">
                    <h1 class="text-5xl font-black tracking-tighter">Welcome, {{ userName }}</h1>
                    <p class="mt-4 text-xl font-medium text-white/70 leading-relaxed">
                        Your academic command center is ready. Manage your classes, build intelligent questions, and monitor performance.
                    </p>
                </div>
                <!-- Abstract Design -->
                <div class="absolute -top-24 -right-24 h-96 w-96 rounded-lg-full bg-white/10 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 h-64 w-64 rounded-lg-full bg-lemon-yellow/10 blur-2xl"></div>
            </div>

            <!-- Performance Grid -->
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                <div class="group rounded-xl bg-white p-10 shadow-sm border border-slate-100 transition-all hover:shadow-2xl hover:-translate-y-1">
                    <p class="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">Assigned Classes</p>
                    <h3 class="mt-4 text-6xl font-black text-slate-800 tracking-tighter">{{ stats.assignedClasses.toString().padStart(2, '0') }}</h3>
                    <div class="mt-6 flex items-center text-xs font-bold text-green-600">
                        <span class="mr-2 h-1.5 w-1.5 rounded-lg-full bg-green-500 animate-ping"></span>
                        Active this session
                    </div>
                </div>

                <div class="group rounded-xl bg-white p-10 shadow-sm border border-slate-100 transition-all hover:shadow-2xl hover:-translate-y-1">
                    <p class="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">Pending Results</p>
                    <h3 class="mt-4 text-6xl font-black text-orange-600 tracking-tighter">{{ stats.pendingResults.toString().padStart(2, '0') }}</h3>
                    <div class="mt-6 text-xs font-bold text-slate-400">Requires your attention</div>
                </div>

                <div class="group rounded-xl bg-slate-900 p-10 shadow-2xl transition-all hover:-translate-y-1">
                    <p class="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">Question Bank</p>
                    <h3 class="mt-4 text-6xl font-black text-primary tracking-tighter">{{ stats.questionBankCount }}</h3>
                    <div class="mt-6 text-xs text-primary/60 font-black uppercase tracking-widest">Verified Repository</div>
                </div>
            </div>

            <!-- Management Hubs -->
            <div class="space-y-10">
                <div v-for="hub in hubs" :key="hub.title" class="space-y-6">
                    <div class="ml-2">
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ hub.title }}</h3>
                        <p class="text-sm font-bold text-slate-400 tracking-wide">{{ hub.description }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <Link 
                            v-for="item in hub.items" 
                            :key="item.name"
                            :href="item.href"
                            class="group relative overflow-hidden rounded-xl border border-slate-100 bg-white p-8 transition-all hover:border-primary/20 hover:shadow-2xl hover:-translate-y-1"
                        >
                            <div class="relative z-10 flex items-center gap-6">
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-3xl transition-transform group-hover:scale-110 group-hover:rotate-3 group-hover:bg-primary/5">
                                    {{ item.icon }}
                                </div>
                                <div>
                                    <h4 class="text-lg font-black text-slate-800 leading-tight">{{ item.name }}</h4>
                                    <p class="text-[10px] font-black tracking-widest text-primary uppercase mt-2 opacity-0 group-hover:opacity-100 transition-opacity">Open Management &rarr;</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Schedule -->
            <div class="rounded-xl border border-slate-100 bg-white p-12 shadow-sm">
                <div class="mb-10 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900">Today's Schedule</h3>
                        <p class="text-sm font-bold text-slate-400 mt-1 uppercase tracking-widest">Upcoming Invitigilations</p>
                    </div>
                    <button class="rounded-xl bg-slate-50 px-6 py-3 text-[10px] font-black tracking-widest text-slate-500 uppercase transition-all hover:bg-slate-100">Full Calendar</button>
                </div>

                <div class="space-y-4">
                    <div v-for="item in schedule" :key="item.id" class="flex items-center justify-between p-6 rounded-xl border border-slate-50 hover:bg-slate-50/50 transition-colors cursor-pointer group">
                        <div class="flex items-center gap-6">
                            <div :class="['h-3 w-3 rounded-lg-full', item.color === 'blue' ? 'bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]' : 'bg-purple-500 shadow-[0_0_8px_rgba(168,85,247,0.5)]']"></div>
                            <div>
                                <h4 class="text-lg font-black text-slate-800">{{ item.title }}</h4>
                                <p class="text-xs font-bold text-slate-400">{{ item.time }} • {{ item.location }}</p>
                            </div>
                        </div>
                        <span class="rounded-lg-full bg-slate-100 px-4 py-1.5 text-[9px] font-black tracking-widest text-slate-500 uppercase">{{ item.type }}</span>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>

