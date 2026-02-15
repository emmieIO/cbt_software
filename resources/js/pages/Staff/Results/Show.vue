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
</script>

<template>
    <component :is="Layout">
        <Head :title="`${exam.title} - Results`" />

        <div class="space-y-10">
            <div class="flex items-center justify-between">
                <div>
                    <Link href="/staff/exams/results" class="text-[10px] font-black text-primary uppercase tracking-widest hover:underline mb-2 block">&larr; Back to Results</Link>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">{{ exam.title }}</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400 uppercase tracking-widest">{{ exam.subject.name }} • Performance Breakdown</p>
                </div>
                
                <div class="flex items-center gap-4">
                    <button class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-[10px] font-black uppercase tracking-widest text-slate-600 shadow-sm transition-all hover:bg-slate-50">
                        Export Excel
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Student Personnel</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase text-center">Score</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase text-center">Percentage</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Security Status</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="attempt in attempts" :key="attempt.id" class="group transition-all hover:bg-[#F8F9FB]">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-slate-800">{{ attempt.user.name }}</span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                            {{ attempt.user.school_id || 'N/A' }} • {{ attempt.user.school_class?.name || 'Candidate' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-6 text-center">
                                    <span class="text-lg font-black text-slate-900">{{ attempt.score }}</span>
                                    <span class="text-xs font-bold text-slate-300"> / {{ totalQuestions }}</span>
                                </td>
                                <td class="px-6 py-6 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <div class="h-1.5 w-16 rounded-full bg-slate-100 overflow-hidden">
                                            <div 
                                                class="h-full transition-all"
                                                :class="getPercentage(attempt.score) >= 50 ? 'bg-primary' : 'bg-orange-400'"
                                                :style="{ width: `${getPercentage(attempt.score)}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-xs font-black" :class="getPercentage(attempt.score) >= 50 ? 'text-primary' : 'text-orange-500'">
                                            {{ getPercentage(attempt.score) }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <div v-if="attempt.metadata?.termination_reason" class="flex flex-col">
                                        <span class="inline-flex items-center gap-1.5 text-[9px] font-black text-red-600 uppercase">
                                            <div class="h-1.5 w-1.5 rounded-full bg-red-500 animate-pulse"></div>
                                            Violation Detected
                                        </span>
                                        <span class="text-[9px] font-bold text-red-400 uppercase tracking-tighter leading-tight mt-0.5">
                                            {{ attempt.metadata.termination_reason }}
                                        </span>
                                    </div>
                                    <span v-else class="inline-flex items-center gap-1.5 text-[9px] font-black text-green-600 uppercase">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Clean Record
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        {{ new Date(attempt.submitted_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="attempts.length === 0">
                                <td colspan="5" class="px-8 py-20 text-center opacity-30 italic font-bold">No students have submitted this assessment yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </component>
</template>
