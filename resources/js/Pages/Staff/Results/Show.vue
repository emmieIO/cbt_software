<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { showResultsPrint as resultsPrintAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Attempt {
    id: string;
    score: number;
    submitted_at: string;
    violations: Array<{ type: string; timestamp: string }> | null;
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
    subject: { name: string } | null;
}

const props = defineProps<{
    exam: Exam;
    attempts: Attempt[];
    totalQuestions: number;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const getPercentage = (score: number) => {
    const total = Number(props.totalQuestions) || 0;
    if (total === 0) return 0;
    return Math.round((score / total) * 100);
};

// Violation Log Modal
const isViolationModalOpen = ref(false);
const activeViolations = ref<Array<{ type: string; timestamp: string }>>([]);
const activeCandidateName = ref('');

const openViolationLog = (attempt: Attempt) => {
    activeCandidateName.value = attempt.user.name;
    activeViolations.value = attempt.violations || [];
    isViolationModalOpen.value = true;
};

const formatViolationDate = (timestamp: string) => {
    return new Date(timestamp).toLocaleString('en-NG', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const getViolationLabel = (type: string) => {
    switch (type) {
        case 'fullscreen_exit':
            return 'Fullscreen Exited';
        case 'tab_switch':
            return 'Tab Switched';
        case 'window_blur':
            return 'Lost Focus';
        default:
            return 'Security Breach';
    }
};

// Analytics
const stats = computed(() => {
    if (!props.attempts || props.attempts.length === 0) return { avg: 0, avgPerc: 0, passRate: 0, alerts: 0, top: 0 };

    const scores = props.attempts.map((a) => Number(a.score) || 0);
    const sum = scores.reduce((a, b) => a + b, 0);
    const avg = sum / scores.length;
    const passes = props.attempts.filter((a) => getPercentage(Number(a.score) || 0) >= 50).length;
    const alerts = props.attempts.filter((a) => (a.violations?.length || 0) > 0 || !!a.metadata?.termination_reason).length;
    const top = Math.max(...scores, 0);

    return {
        avg: Math.round(avg * 10) / 10,
        avgPerc: getPercentage(avg),
        passRate: Math.round((passes / props.attempts.length) * 100),
        alerts,
        top: top,
    };
});
</script>

<template>
    <component :is="Layout">
        <Head :title="`${exam.title} - Results`" />

        <div class="space-y-10">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <Link href="/staff/exams/results" class="mb-2 inline-flex items-center gap-2 text-xs font-medium text-primary hover:underline">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Results
                    </Link>
                    <h1 class="text-2xl font-semibold text-gray-800">{{ exam.title }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ exam.subject?.name || 'Multi-Subject' }} • Performance Analytics</p>
                </div>

                <div class="flex items-center gap-x-2">
                    <a
                        :href="resultsPrintAction(exam.id).url"
                        target="_blank"
                        class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                            />
                        </svg>
                        Print Report
                    </a>
                </div>
            </div>

            <!-- Analytics Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase">Average Score</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-semibold text-gray-800">{{ stats.avg }}</span>
                        <span class="text-sm font-medium text-primary">{{ stats.avgPerc }}%</span>
                    </div>
                </div>

                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase">Pass Rate</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-semibold" :class="stats.passRate >= 70 ? 'text-teal-600' : 'text-orange-500'"
                            >{{ stats.passRate }}%</span
                        >
                        <div class="h-2 w-2 rounded-full" :class="stats.passRate >= 70 ? 'bg-teal-500' : 'bg-orange-500'"></div>
                    </div>
                </div>

                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase">Top Score</h3>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-3xl font-semibold text-gray-800">{{ stats.top }}</span>
                        <span class="text-sm text-gray-400">/ {{ totalQuestions }}</span>
                    </div>
                </div>

                <div
                    class="flex flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:p-5"
                    :class="{ 'border-red-200 bg-red-50': stats.alerts > 0 }"
                >
                    <h3 class="text-xs font-semibold text-gray-500 uppercase" :class="stats.alerts > 0 ? 'text-red-600' : 'text-gray-500'">
                        Integrity Alerts
                    </h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-semibold" :class="stats.alerts > 0 ? 'text-red-600' : 'text-gray-800'">{{ stats.alerts }}</span>
                        <span v-if="stats.alerts > 0" class="text-xs font-medium text-red-500">Violations</span>
                    </div>
                </div>
            </div>

            <!-- Detailed Submissions Table -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="inline-block min-w-full p-1.5 align-middle">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <!-- Table Header -->
                            <div class="grid gap-3 border-b border-gray-200 px-6 py-4 md:flex md:items-center md:justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-800">Candidate Submissions</h2>
                                    <p class="text-sm text-gray-500">{{ attempts.length }} Records</p>
                                </div>
                            </div>

                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                                            Student Personnel
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Performance</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Security Status</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Submitted At</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr
                                        v-for="attempt in attempts"
                                        :key="attempt.id"
                                        class="transition-colors hover:bg-gray-50"
                                        :class="{ 'bg-red-50/50': !!attempt.metadata?.termination_reason }"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-semibold text-gray-800">{{ attempt.user.name }}</span>
                                                <span class="text-xs text-gray-500">
                                                    {{ attempt.user.school_id || 'NOT ASSIGNED' }} •
                                                    {{ attempt.user.school_class?.name || 'CANDIDATE' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col items-center">
                                                <div class="flex items-baseline gap-1">
                                                    <span class="text-base font-semibold text-gray-800">{{ attempt.score }}</span>
                                                    <span class="text-xs text-gray-400">/ {{ totalQuestions }}</span>
                                                </div>
                                                <div class="mt-1 flex items-center gap-x-2">
                                                    <div class="flex h-1.5 w-16 overflow-hidden rounded-full bg-gray-200">
                                                        <div
                                                            class="flex flex-col justify-center overflow-hidden transition-all"
                                                            :class="getPercentage(attempt.score) >= 50 ? 'bg-primary' : 'bg-orange-500'"
                                                            :style="{ width: `${getPercentage(attempt.score)}%` }"
                                                            role="progressbar"
                                                            :aria-valuenow="getPercentage(attempt.score)"
                                                            aria-valuemin="0"
                                                            aria-valuemax="100"
                                                        ></div>
                                                    </div>
                                                    <span
                                                        class="text-xs font-medium"
                                                        :class="getPercentage(attempt.score) >= 50 ? 'text-primary' : 'text-orange-600'"
                                                    >
                                                        {{ getPercentage(attempt.score) }}%
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div v-if="(attempt.violations?.length || 0) > 0" class="flex flex-col">
                                                <span
                                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800"
                                                >
                                                    <span class="inline-block size-1.5 rounded-full bg-red-800"></span>
                                                    {{ attempt.violations?.length || 0 }} Violations
                                                </span>
                                                <button
                                                    @click="openViolationLog(attempt)"
                                                    class="mt-1 text-start text-xs font-medium text-primary hover:underline"
                                                >
                                                    View Detailed Log
                                                </button>
                                            </div>
                                            <div v-else-if="attempt.metadata?.termination_reason" class="flex flex-col">
                                                <span
                                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800"
                                                >
                                                    <span class="inline-block size-1.5 rounded-full bg-red-800"></span>
                                                    Violation
                                                </span>
                                                <span class="mt-1 max-w-37.5 truncate text-xs text-red-600">
                                                    {{ attempt.metadata.termination_reason }}
                                                </span>
                                            </div>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-2.5 py-1 text-xs font-medium text-teal-800"
                                            >
                                                <svg class="size-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Validated
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end whitespace-nowrap">
                                            <div class="flex flex-col items-end">
                                                <span class="text-xs font-medium text-gray-800">
                                                    {{
                                                        new Date(attempt.submitted_at).toLocaleDateString('en-GB', {
                                                            day: '2-digit',
                                                            month: 'short',
                                                            year: 'numeric',
                                                        })
                                                    }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    {{
                                                        new Date(attempt.submitted_at).toLocaleTimeString('en-GB', {
                                                            hour: '2-digit',
                                                            minute: '2-digit',
                                                        })
                                                    }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="attempts.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <svg class="mx-auto mb-4 size-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                />
                                            </svg>
                                            <p class="text-sm font-medium text-gray-500">No active submissions recorded.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Violation Log Modal -->
        <div v-if="isViolationModalOpen" class="fixed inset-0 z-100 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div @click="isViolationModalOpen = false" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

                <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Security Violation Log</h3>
                            <p class="text-xs text-gray-500">Student: {{ activeCandidateName }}</p>
                        </div>
                        <button @click="isViolationModalOpen = false" class="text-gray-400 transition-colors hover:text-gray-500">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="custom-scrollbar max-h-[60vh] overflow-y-auto p-6">
                        <div v-if="activeViolations.length === 0" class="py-6 text-center">
                            <p class="text-sm text-gray-500">No violations recorded for this attempt.</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="(v, idx) in activeViolations"
                                :key="idx"
                                class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-3 transition-all hover:border-red-200 hover:bg-red-50"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-xs font-semibold text-red-600"
                                    >
                                        {{ idx + 1 }}
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-800">{{ getViolationLabel(v.type) }}</h4>
                                        <p class="text-xs text-gray-500">{{ v.type.replace('_', ' ') }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-medium text-gray-800">{{ formatViolationDate(v.timestamp) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            @click="isViolationModalOpen = false"
                            class="inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-black disabled:pointer-events-none disabled:opacity-50"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>

<style scoped>
@media print {
    :deep(nav),
    :deep(aside),
    :deep(header) {
        display: none !important;
    }
    :deep(main) {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
