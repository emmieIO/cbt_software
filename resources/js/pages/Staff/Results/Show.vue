<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Attempt {
    id: string;
    score: number;
    submitted_at: string;
    metadata: {
        termination_reason?: string;
        violation_count?: number;
    } | null;
    user: {
        name: string;
        school_id: string | null;
        school_class?: { name: string };
    };
}

interface Exam {
    id: string;
    title: string;
    subject: { name: string };
}

const props = defineProps<{
    exam: Exam;
    attempts: Attempt[];
    totalQuestions: number;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const getPercentage = (score: number) => {
    if (props.totalQuestions === 0) return 0;
    return Math.round((score / props.totalQuestions) * 100);
};

// Analytics
const stats = computed(() => {
    if (props.attempts.length === 0) return { avg: 0, passRate: 0, alerts: 0, top: 0 };
    
    const scores = props.attempts.map(a => a.score);
    const avg = scores.reduce((a, b) => a + b, 0) / scores.length;
    const passes = props.attempts.filter(a => getPercentage(a.score) >= 50).length;
    const alerts = props.attempts.filter(a => !!a.metadata?.termination_reason).length;
    const top = Math.max(...scores);

    return {
        avg: Math.round(avg * 10) / 10,
        avgPerc: getPercentage(avg),
        passRate: Math.round((passes / props.attempts.length) * 100),
        alerts,
        top: top
    };
});

const handleExport = () => {
    // Logic for export could be added here or linked to a route
    window.print();
};
</script>

<template>
    <component :is="Layout">
        <Head :title="`${exam.title} - Results`" />

        <div class="space-y-10">
            <!-- Header -->
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <Link href="/staff/exams/results" class="mb-2 inline-flex items-center gap-2 text-[10px] font-black tracking-widest text-primary uppercase hover:underline">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Back to Results
                    </Link>
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">{{ exam.title }}</h1>
                    <p class="mt-1 text-sm font-bold tracking-widest text-slate-400 uppercase">{{ exam.subject.name }} • Performance Analytics</p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="handleExport"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-[10px] font-black tracking-widest text-slate-600 uppercase shadow-sm transition-all hover:bg-slate-50 active:scale-95"
                    >
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Report
                    </button>
                </div>
            </div>

            <!-- Analytics Grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-md">
                    <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Average Score</span>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-4xl font-black tracking-tighter text-slate-900">{{ stats.avg }}</span>
                        <span class="text-lg font-bold text-primary">{{ stats.avgPerc }}%</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-md">
                    <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Pass Rate</span>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-4xl font-black tracking-tighter" :class="stats.passRate >= 70 ? 'text-green-600' : 'text-orange-500'">{{ stats.passRate }}%</span>
                        <div class="h-2 w-2 rounded-full" :class="stats.passRate >= 70 ? 'bg-green-500' : 'bg-orange-500'"></div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-md">
                    <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Top Score</span>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-4xl font-black tracking-tighter text-slate-900">{{ stats.top }}</span>
                        <span class="text-sm font-bold text-slate-300">/ {{ totalQuestions }}</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-md" :class="{'ring-2 ring-red-100 bg-red-50/30': stats.alerts > 0}">
                    <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Integrity Alerts</span>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-4xl font-black tracking-tighter" :class="stats.alerts > 0 ? 'text-red-600' : 'text-slate-900'">{{ stats.alerts }}</span>
                        <span v-if="stats.alerts > 0" class="text-[10px] font-bold text-red-400 uppercase">Violations</span>
                    </div>
                </div>
            </div>

            <!-- Detailed Submissions Table -->
            <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-xl">
                <div class="bg-slate-50/50 px-8 py-5 border-b border-slate-100">
                    <h3 class="text-xs font-black tracking-[0.2em] text-slate-500 uppercase">Candidate Submissions • {{ attempts.length }} Records</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-[#FBFBFC]">
                                <th class="px-8 py-5 text-[9px] font-black tracking-widest text-slate-400 uppercase">Student Personnel</th>
                                <th class="px-6 py-5 text-center text-[9px] font-black tracking-widest text-slate-400 uppercase">Performance</th>
                                <th class="px-6 py-5 text-[9px] font-black tracking-widest text-slate-400 uppercase">Security Status</th>
                                <th class="px-8 py-5 text-right text-[9px] font-black tracking-widest text-slate-400 uppercase">Submitted At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="attempt in attempts" :key="attempt.id" class="group transition-all hover:bg-[#F8F9FB]" :class="{'bg-red-50/20': !!attempt.metadata?.termination_reason}">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-slate-800 group-hover:text-primary transition-colors">{{ attempt.user.name }}</span>
                                        <span class="text-[10px] font-bold tracking-tighter text-slate-400 uppercase">
                                            {{ attempt.user.school_id || 'NOT ASSIGNED' }} • {{ attempt.user.school_class?.name || 'CANDIDATE' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <div class="flex flex-col items-center">
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-lg font-black text-slate-900">{{ attempt.score }}</span>
                                            <span class="text-[10px] font-bold text-slate-300">/ {{ totalQuestions }}</span>
                                        </div>
                                        <div class="mt-2 flex items-center gap-2">
                                            <div class="h-1 w-16 overflow-hidden rounded-full bg-slate-100">
                                                <div
                                                    class="h-full transition-all"
                                                    :class="getPercentage(attempt.score) >= 50 ? 'bg-primary' : 'bg-orange-400'"
                                                    :style="{ width: `${getPercentage(attempt.score)}%` }"
                                                ></div>
                                            </div>
                                            <span class="text-[9px] font-black" :class="getPercentage(attempt.score) >= 50 ? 'text-primary' : 'text-orange-500'">
                                                {{ getPercentage(attempt.score) }}%
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <div v-if="attempt.metadata?.termination_reason" class="flex flex-col max-w-[200px]">
                                        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-red-100 px-2 py-0.5 text-[8px] font-black text-red-600 uppercase">
                                            <div class="h-1 w-1 animate-pulse rounded-full bg-red-500"></div>
                                            Violation
                                        </span>
                                        <span class="mt-1 text-[9px] leading-tight font-bold tracking-tight text-red-400 line-clamp-1 group-hover:line-clamp-none transition-all">
                                            {{ attempt.metadata.termination_reason }}
                                        </span>
                                    </div>
                                    <span v-else class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-[8px] font-black text-green-600 uppercase border border-green-100">
                                        <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Validated
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="text-[10px] font-black text-slate-600 uppercase">
                                            {{ new Date(attempt.submitted_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                                        </span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase">
                                            {{ new Date(attempt.submitted_at).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="attempts.length === 0">
                                <td colspan="4" class="px-8 py-24 text-center">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-300 mb-4">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </div>
                                    <p class="text-sm font-bold tracking-widest text-slate-400 uppercase italic">No active submissions recorded.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </component>
</template>

<style scoped>
@media print {
    :deep(nav), :deep(aside), :deep(header) {
        display: none !important;
    }
    :deep(main) {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
