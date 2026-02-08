<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';
import {
    create,
    edit,
    index as indexAction,
    generate,
    importMethod,
    exportMethod,
    downloadTemplate,
    toggleStatus,
    destroy,
    bulkDestroy,
} from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { AppPageProps } from '@/types';
import type { Question, Subject, SchoolClass, PaginatedData } from '@/types/academics';

const props = defineProps<{
    questions: PaginatedData<Question>;
    subjects: Subject[];
    classes: SchoolClass[];
    difficulties: { value: string; label: string }[];
    filters: {
        search?: string;
        subject_id?: string;
        school_class_id?: string;
        difficulty?: string;
    };
}>();

const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user.roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const isSeeding = computed(() => (page.props.auth as any).is_seeding);

// Bulk Selection
const selectedIds = ref<string[]>([]);
const isAllSelected = computed(() => props.questions.data.length > 0 && selectedIds.value.length === props.questions.data.length);

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.questions.data.map((q) => q.id);
    }
};

const isBulkDeleteModalOpen = ref(false);
const handleBulkDelete = () => {
    isBulkDeleteModalOpen.value = false;
    router.delete(bulkDestroy().url, {
        data: { ids: selectedIds.value },
        onSuccess: () => (selectedIds.value = []),
    });
};

// Row Expansion
const expandedRows = ref<Set<string>>(new Set());
const toggleRow = (id: string) => {
    if (expandedRows.value.has(id)) {
        expandedRows.value.delete(id);
    } else {
        expandedRows.value.add(id);
    }
};

// Dropdown State
const activeDropdown = ref<string | null>(null);
const toggleDropdown = (id: string) => {
    activeDropdown.value = activeDropdown.value === id ? null : id;
};

// Close dropdown on click outside
if (typeof window !== 'undefined') {
    window.addEventListener('click', () => {
        activeDropdown.value = null;
    });
}

// Import
const importForm = useForm({
    file: null as File | null,
});

const handleImport = () => {
    importForm.post(importMethod().url, {
        onSuccess: () => importForm.reset(),
    });
};

// Status Toggle
const handleToggleStatus = (question: Question) => {
    router.patch(
        toggleStatus(question.id).url,
        {},
        {
            preserveScroll: true,
        },
    );
};

// Single Delete
const isDeleteModalOpen = ref(false);
const questionToDelete = ref<Question | null>(null);

const confirmDelete = (question: Question) => {
    questionToDelete.value = question;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (questionToDelete.value) {
        router.delete(destroy(questionToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                questionToDelete.value = null;
            },
        });
    }
};

// Filters
const filterForm = ref({
    search: props.filters.search || '',
    subject_id: props.filters.subject_id || '',
    school_class_id: props.filters.school_class_id || '',
    difficulty: props.filters.difficulty || '',
});

watch(
    filterForm,
    debounce((value) => {
        router.get(indexAction().url, value, {
            preserveState: true,
            replace: true,
        });
    }, 300),
    { deep: true },
);

const clearFilters = () => {
    filterForm.value = {
        search: '',
        subject_id: '',
        school_class_id: '',
        difficulty: '',
    };
};
</script>

<template>
    <component :is="Layout">
        <Head title="Question Repository" />

        <div class="w-full space-y-10">
            <!-- AI Processing Banner -->
            <div
                v-if="isSeeding"
                class="animate-in fade-in slide-in-from-top-4 relative overflow-hidden rounded-3xl border border-primary/20 bg-primary px-8 py-6 shadow-xl shadow-primary/10"
            >
                <div class="relative z-10 flex flex-col items-center justify-between gap-6 sm:flex-row">
                    <div class="flex items-start gap-5">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-lemon-yellow backdrop-blur-xl">
                            <svg class="h-8 w-8 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-lg font-black tracking-tight text-white">AI Seeding in Progress</h4>
                            <p class="text-sm font-medium text-white/70">
                                The agent is currently generating questions for <span class="font-black text-lemon-yellow">{{ isSeeding.topic }}</span
                                >.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col items-center gap-3 sm:items-end">
                        <div
                            class="flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-[10px] font-black tracking-widest text-white uppercase backdrop-blur-md"
                        >
                            <div class="h-1.5 w-1.5 animate-ping rounded-full bg-lemon-yellow"></div>
                            Estimated: ~5-10 Minutes
                        </div>
                        <p class="text-[10px] font-bold text-white/40 italic">Check back shortly or refresh to see results.</p>
                    </div>
                </div>
                <!-- Decorative BG -->
                <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/5 blur-2xl"></div>
            </div>

            <div class="w-full space-y-8">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">Question Bank</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">Review and manage the existing question repository.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Refresh Button -->
                    <button
                        @click="router.reload({ only: ['questions', 'auth', 'flash'] })"
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 transition-all hover:bg-slate-200 hover:text-primary active:scale-95"
                        title="Refresh Data"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                    </button>

                    <!-- Tools Group -->
                    <div
                        v-if="(page.props.auth.user as any).permissions.includes('export questions')"
                        class="flex items-center gap-2 rounded-2xl bg-slate-100 p-1.5"
                    >
                        <a
                            :href="downloadTemplate().url"
                            class="flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-xs font-black tracking-wider text-slate-600 uppercase shadow-sm transition-all hover:text-primary active:scale-95"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            Template
                        </a>
                        <a
                            :href="exportMethod().url"
                            class="flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-black tracking-wider text-slate-600 uppercase transition-all hover:bg-white hover:text-primary active:scale-95"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Export
                        </a>
                    </div>

                    <!-- Import Form -->
                    <form
                        v-if="(page.props.auth.user as any).permissions.includes('create questions')"
                        @submit.prevent="handleImport"
                        class="flex items-center gap-2"
                    >
                        <label
                            class="flex h-11 cursor-pointer items-center gap-2 rounded-2xl border-2 border-dashed border-slate-200 px-4 text-xs font-black tracking-wider text-slate-400 uppercase transition-all hover:border-primary hover:text-primary"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                />
                            </svg>
                            {{ importForm.file ? importForm.file.name.substring(0, 10) + '...' : 'Import' }}
                            <input
                                type="file"
                                class="hidden"
                                accept=".csv,.xlsx"
                                @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null"
                            />
                        </label>
                        <button
                            v-if="importForm.file"
                            type="submit"
                            :disabled="importForm.processing"
                            class="h-11 rounded-2xl bg-primary px-4 text-xs font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                        >
                            Upload
                        </button>
                    </form>

                    <Link
                        v-if="(page.props.auth.user as any).permissions.includes('use ai lab')"
                        :href="generate().url"
                        class="flex h-11 items-center gap-2 rounded-2xl border-2 border-primary/20 bg-primary/5 px-6 text-xs font-black tracking-wider text-primary uppercase transition-all hover:bg-primary hover:text-white active:scale-95"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                            />
                        </svg>
                        AI Lab
                    </Link>

                    <Link
                        v-if="(page.props.auth.user as any).permissions.includes('create questions')"
                        :href="create().url"
                        class="flex h-11 items-center gap-2 rounded-2xl bg-primary px-6 text-xs font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add New
                    </Link>
                </div>
            </div>

            <!-- Repository Table -->
            <div class="overflow-hidden rounded-[2.5rem] border border-slate-100 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-8">
                    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <div>
                                <h3 class="text-xl font-black tracking-tight text-slate-800">Questions</h3>
                                <p class="text-xs font-bold text-slate-400">Total Available: {{ questions.total || 0 }}</p>
                            </div>
                            <div v-if="selectedIds.length > 0" class="flex items-center gap-3 rounded-2xl border border-red-100 bg-red-50 px-4 py-2">
                                <span class="text-xs font-black tracking-wider text-red-600 uppercase">{{ selectedIds.length }} Selected</span>
                                <button
                                    @click="isBulkDeleteModalOpen = true"
                                    class="rounded-lg bg-red-600 px-3 py-1 text-[10px] font-black tracking-widest text-white uppercase transition-all hover:bg-red-700 active:scale-95"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>

                        <!-- Search -->
                        <div class="relative w-full sm:w-72">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Search questions..."
                                class="h-11 w-full rounded-2xl border-none bg-slate-100 pl-11 text-xs font-bold transition-all placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-primary/20"
                            />
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2 rounded-2xl bg-slate-50 p-1 shadow-inner">
                            <select
                                v-model="filterForm.subject_id"
                                class="border-none bg-transparent text-[10px] font-black tracking-widest text-slate-500 uppercase focus:ring-0"
                            >
                                <option value="">All Subjects</option>
                                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                            </select>
                            <div class="h-4 w-px bg-slate-200"></div>
                            <select
                                v-model="filterForm.school_class_id"
                                class="border-none bg-transparent text-[10px] font-black tracking-widest text-slate-500 uppercase focus:ring-0"
                            >
                                <option value="">All Classes</option>
                                <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                            </select>
                            <div class="h-4 w-px bg-slate-200"></div>
                            <select
                                v-model="filterForm.difficulty"
                                class="border-none bg-transparent text-[10px] font-black tracking-widest text-slate-500 uppercase focus:ring-0"
                            >
                                <option value="">All Difficulties</option>
                                <option v-for="diff in difficulties" :key="diff.value" :value="diff.value">{{ diff.label }}</option>
                            </select>
                        </div>

                        <button
                            v-if="filterForm.search || filterForm.subject_id || filterForm.school_class_id || filterForm.difficulty"
                            @click="clearFilters"
                            class="group flex items-center gap-2 text-[10px] font-black tracking-widest text-slate-400 uppercase hover:text-primary"
                        >
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 transition-colors group-hover:bg-primary group-hover:text-white"
                            >
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            Clear Filters
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="w-12 px-8 py-5">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        @change="toggleSelectAll"
                                        class="rounded-lg border-slate-200 text-primary focus:ring-primary/20"
                                    />
                                </th>
                                <th class="w-10 px-2 py-5"></th>
                                <th class="w-full px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Question Content</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest whitespace-nowrap text-slate-400 uppercase">Context</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest whitespace-nowrap text-slate-400 uppercase">Class</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <template v-for="question in questions.data" :key="question.id">
                                <tr
                                    class="group cursor-pointer transition-all hover:bg-slate-50/80"
                                    :class="{ 'bg-primary/3': expandedRows.has(question.id) }"
                                    @click="toggleRow(question.id)"
                                >
                                    <td class="px-8 py-6" @click.stop>
                                        <input
                                            type="checkbox"
                                            v-model="selectedIds"
                                            :value="question.id"
                                            class="rounded-lg border-slate-200 text-primary focus:ring-primary/20"
                                        />
                                    </td>
                                    <td class="px-2 py-6 text-center">
                                        <svg
                                            class="h-4 w-4 text-slate-300 transition-all duration-300 group-hover:text-primary"
                                            :class="{ 'rotate-90 text-primary': expandedRows.has(question.id) }"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </td>
                                    <td class="px-6 py-6">
                                        <div class="flex flex-col gap-2">
                                            <p class="line-clamp-1 text-sm font-black text-slate-800 transition-all group-hover:line-clamp-none">
                                                {{ question.content }}
                                            </p>
                                            <div class="flex items-center gap-2">
                                                <span
                                                    :class="[
                                                        'rounded-lg px-2 py-0.5 text-[10px] font-black tracking-widest uppercase',
                                                        question.difficulty === 'easy'
                                                            ? 'bg-green-100 text-green-700'
                                                            : question.difficulty === 'medium'
                                                              ? 'bg-blue-100 text-blue-700'
                                                              : 'bg-red-100 text-red-700',
                                                    ]"
                                                    >{{ question.difficulty }}</span
                                                >
                                                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">{{
                                                    question.type.replace('_', ' ')
                                                }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black text-slate-700">{{ question.topic.subject.name }}</span>
                                            <span class="text-[10px] font-bold text-slate-400">• {{ question.topic.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <span class="text-xs font-black text-slate-700">{{ question.school_class.name }}</span>
                                    </td>
                                    <td class="px-8 py-6 text-right" @click.stop>
                                        <div class="relative inline-block text-left">
                                            <button
                                                @click="toggleDropdown(question.id)"
                                                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all hover:bg-slate-100 active:scale-90"
                                                :class="activeDropdown === question.id ? 'bg-slate-100 text-primary' : 'text-slate-400'"
                                            >
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- Action Dropdown -->
                                            <div
                                                v-if="activeDropdown === question.id"
                                                class="absolute right-0 z-30 mt-2 w-64 origin-top-right rounded-2xl bg-white p-2 shadow-2xl ring-1 ring-black/5"
                                            >
                                                <button
                                                    @click="handleToggleStatus(question)"
                                                    class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-left text-xs font-black tracking-wider uppercase transition-colors hover:bg-slate-50"
                                                    :class="question.is_active ? 'text-green-600' : 'text-slate-400'"
                                                >
                                                    <div
                                                        class="h-2 w-2 rounded-full"
                                                        :class="
                                                            question.is_active ? 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]' : 'bg-slate-300'
                                                        "
                                                    ></div>
                                                    {{ question.is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                                <Link
                                                    :href="edit(question.id).url"
                                                    class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-left text-xs font-black tracking-wider text-slate-600 uppercase transition-colors hover:bg-slate-50"
                                                >
                                                    <svg class="h-4 w-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                        />
                                                    </svg>
                                                    Edit Question
                                                </Link>
                                                <div class="my-1 h-px bg-slate-100"></div>
                                                <button
                                                    @click="confirmDelete(question)"
                                                    class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-left text-xs font-black tracking-wider text-red-600 uppercase transition-colors hover:bg-red-50"
                                                >
                                                    <svg class="h-4 w-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                        />
                                                    </svg>
                                                    Delete Permanent
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="expandedRows.has(question.id)" class="bg-slate-50/50">
                                    <td colspan="7" class="px-20 py-8">
                                        <div class="space-y-6">
                                            <div>
                                                <h4 class="mb-2 text-xs font-bold tracking-wider text-slate-400 uppercase">Question Text</h4>
                                                <p class="text-base leading-relaxed whitespace-pre-wrap text-slate-700">{{ question.content }}</p>
                                            </div>

                                            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                                                <div>
                                                    <h4 class="mb-3 text-xs font-bold tracking-wider text-slate-400 uppercase">Options</h4>
                                                    <div class="space-y-2">
                                                        <div
                                                            v-for="option in question.options"
                                                            :key="option.id"
                                                            class="flex items-center gap-3 rounded-xl border p-3"
                                                            :class="
                                                                option.is_correct
                                                                    ? 'border-green-200 bg-green-50 text-green-700'
                                                                    : 'border-slate-100 bg-white text-slate-600'
                                                            "
                                                        >
                                                            <div
                                                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                                                :class="option.is_correct ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-500'"
                                                            >
                                                                <svg v-if="option.is_correct" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path
                                                                        fill-rule="evenodd"
                                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                        clip-rule="evenodd"
                                                                    />
                                                                </svg>
                                                                <span v-else>•</span>
                                                            </div>
                                                            <span class="text-sm font-medium">{{ option.content }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div v-if="question.explanation">
                                                    <h4 class="mb-3 text-xs font-bold tracking-wider text-slate-400 uppercase">Explanation</h4>
                                                    <div
                                                        class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm leading-relaxed text-blue-800 italic"
                                                    >
                                                        {{ question.explanation }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="questions.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-lg text-slate-400 italic">
                                    No questions found in the repository yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between border-t border-slate-100 p-8">
                    <p class="text-sm text-slate-500">
                        Showing <span class="font-bold text-slate-700">{{ questions.from || 0 }}</span> to
                        <span class="font-bold text-slate-700">{{ questions.to || 0 }}</span> of
                        <span class="font-bold text-slate-700">{{ questions.total || 0 }}</span> questions
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-for="link in questions.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="rounded-lg px-4 py-2 text-sm font-medium transition-all"
                            :class="[
                                link.active
                                    ? 'bg-primary text-white shadow-md'
                                    : 'border border-slate-200 bg-white text-slate-600 hover:border-slate-300',
                                !link.url ? 'cursor-not-allowed opacity-50' : '',
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmations -->
        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Question?"
            :message="`Are you sure you want to permanently delete this question? This action cannot be undone.`"
            confirm-label="Delete Permanent"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />

        <ConfirmationModal
            :show="isBulkDeleteModalOpen"
            title="Bulk Delete?"
            :message="`Are you sure you want to delete ${selectedIds.length} selected questions? This action is permanent.`"
            confirm-label="Delete All Selected"
            variant="danger"
            @close="isBulkDeleteModalOpen = false"
            @confirm="handleBulkDelete"
        />
    </component>
</template>
