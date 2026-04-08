<script setup lang="ts">
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';
import {
    create,
    edit,
    index as indexAction,
    importMethod,
    downloadTemplate,
    destroy,
} from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { Question, Subject, SchoolClass, PaginatedData } from '@/types/academics';

const props = defineProps<{
    questions: PaginatedData<Question>;
    subjects: Subject[];
    classes: SchoolClass[];
    difficulties: { value: string; label: string }[];
    levels: { value: string; label: string }[];
    filters: {
        search?: string;
        subject_id?: string;
        school_class_id?: string;
        difficulty?: string;
        level?: string;
    };
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));
const compactLevelTag = (level: string) => {
    const normalized = String(level).toLowerCase();
    if (normalized === 'primary') return 'Pry';
    if (normalized === 'secondary') return 'Sec';
    if (normalized === 'nursery') return 'Nur';

    return normalized.slice(0, 3).toUpperCase();
};

const subjectsWithOptions = computed(() => {
    return props.subjects.map((s: Subject) => ({
        ...s,
        name: `${s.name} (${compactLevelTag(typeof s.level === 'string' ? s.level : (s.level as any)?.value || '')})`,
    }));
});

const isSeeding = computed(() => (page.props.auth as any).is_seeding);

// Automatic Polling when seeding is in progress
router.poll(5000, {
    only: ['questions', 'auth', 'flash'],
});

const isRefreshing = ref(false);
const refresh = () => {
    isRefreshing.value = true;
    router.reload({
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

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

// Dropdown State
const activeDropdown = ref<string | null>(null);

// Close dropdown on click outside
if (typeof window !== 'undefined') {
    window.addEventListener('click', () => {
        activeDropdown.value = null;
    });
}

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
    level: props.filters.level || '',
});

// Computed available options based on level
const filteredSubjects = computed(() => {
    if (!filterForm.value.level) return subjectsWithOptions.value;
    return subjectsWithOptions.value.filter(s => {
        const lvl = typeof s.level === 'string' ? s.level : (s.level as any)?.value;
        return lvl === filterForm.value.level;
    });
});

const filteredClasses = computed(() => {
    if (!filterForm.value.level) return props.classes;
    return props.classes.filter(c => {
        const lvl = typeof c.level === 'string' ? c.level : (c.level as any)?.value;
        return lvl === filterForm.value.level;
    });
});

// Clear incompatible selected options if the level changes
watch(
    () => filterForm.value.level,
    (newLevel) => {
        if (!newLevel) return;

        if (filterForm.value.subject_id) {
            const subj = props.subjects.find(s => s.id === filterForm.value.subject_id);
            if (subj) {
                const lvl = typeof subj.level === 'string' ? subj.level : (subj.level as any)?.value;
                if (lvl !== newLevel) filterForm.value.subject_id = '';
            }
        }

        if (filterForm.value.school_class_id) {
            const cls = props.classes.find(c => c.id === filterForm.value.school_class_id);
            if (cls) {
                const lvl = typeof cls.level === 'string' ? cls.level : (cls.level as any)?.value;
                if (lvl !== newLevel) filterForm.value.school_class_id = '';
            }
        }
    }
);

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
        level: '',
    };
};

// Import
const isImportModalOpen = ref(false);
const importForm = useForm({
    file: null as File | null,
});

const handleImport = () => {
    importForm.post(importMethod().url, {
        onSuccess: () => {
            isImportModalOpen.value = false;
            importForm.reset();
            selectedIds.value = [];
        },
    });
};

const getDifficultyClasses = (difficulty: string) => {
    switch (difficulty) {
        case 'easy':
            return 'bg-teal-100 text-teal-800';
        case 'medium':
            return 'bg-blue-100 text-blue-800';
        case 'hard':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <component :is="Layout">
        <Head title="Question Bank" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-800">Question Bank</span>
            </nav>

            <!-- AI Processing Banner -->
            <div v-if="isSeeding" class="rounded-xl border border-primary/20 bg-primary p-4 shadow-sm md:p-6">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="flex items-center gap-4">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-white/10 text-white backdrop-blur-sm">
                            <svg class="size-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-white">AI Seeding in Progress</h4>
                            <p class="text-sm text-white/80">
                                Generating for <span class="font-bold underline decoration-dotted underline-offset-4">{{ isSeeding.topic }}</span
                                >.
                            </p>
                        </div>
                    </div>
                    <div class="inline-flex items-center gap-x-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white uppercase">
                        <span class="inline-block size-1.5 animate-ping rounded-full bg-white"></span>
                        Processing Batch
                    </div>
                </div>
            </div>

            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Universal Bank</h1>
                    <p class="mt-1 text-sm text-gray-500">Repository • {{ questions.total }} Verified Items</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="refresh"
                        :disabled="isRefreshing"
                        class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        <svg class="size-4" :class="{ 'animate-spin': isRefreshing }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        Refresh
                    </button>
                    <Link
                        v-if="(page.props.auth.user as any).permissions.includes('bank:create')"
                        href="/staff/questions/batch"
                        class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            />
                        </svg>
                        Spreadsheet
                    </Link>
                    <button
                        v-if="(page.props.auth.user as any).permissions.includes('bank:create')"
                        @click="isImportModalOpen = true"
                        class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                        Import
                    </button>
                    <Link
                        v-if="(page.props.auth.user as any).permissions.includes('bank:create')"
                        :href="create().url"
                        class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-2.5 text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add New
                    </Link>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="inline-block min-w-full p-1.5 align-middle">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <!-- Filters Header -->
                            <div class="grid gap-3 border-b border-gray-200 px-6 py-4 md:flex md:items-center md:justify-between">
                                <div class="relative max-w-md flex-1">
                                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        v-model="filterForm.search"
                                        type="text"
                                        placeholder="Search repository..."
                                        class="block w-full rounded-lg border-gray-200 px-4 py-3 ps-10 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                    />
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="w-36">
                                        <select
                                            v-model="filterForm.level"
                                            class="block w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                        >
                                            <option value="">All Levels</option>
                                            <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                                        </select>
                                    </div>
                                    <div class="w-40">
                                        <select
                                            v-model="filterForm.school_class_id"
                                            class="block w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                        >
                                            <option value="">All Classes</option>
                                            <option v-for="c in filteredClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
                                        </select>
                                    </div>
                                    <div class="w-40">
                                        <select
                                            v-model="filterForm.subject_id"
                                            class="block w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                        >
                                            <option value="">All Subjects</option>
                                            <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                                        </select>
                                    </div>
                                    <div class="w-40">
                                        <select
                                            v-model="filterForm.difficulty"
                                            class="block w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                        >
                                            <option value="">All Difficulties</option>
                                            <option v-for="d in difficulties" :key="d.value" :value="d.value">{{ d.label }}</option>
                                        </select>
                                    </div>
                                    <button
                                        v-if="filterForm.search || filterForm.subject_id || filterForm.school_class_id || filterForm.difficulty || filterForm.level"
                                        @click="clearFilters"
                                        class="inline-flex items-center gap-x-2 rounded-lg border border-transparent px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 focus:bg-red-50 focus:outline-none"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <!-- Table -->
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start">
                                            <div class="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    :checked="isAllSelected"
                                                    @change="toggleSelectAll"
                                                    class="shrink-0 rounded border-gray-200 text-primary focus:ring-primary"
                                                />
                                            </div>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Question Details</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Level</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Context</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Difficulty</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="question in questions.data" :key="question.id" class="transition-colors hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    v-model="selectedIds"
                                                    :value="question.id"
                                                    class="shrink-0 rounded border-gray-200 text-primary focus:ring-primary"
                                                />
                                            </div>
                                        </td>
                                        <td class="max-w-md px-6 py-4">
                                            <div class="flex items-start gap-x-3">
                                                <div
                                                    class="flex size-8 shrink-0 items-center justify-center rounded bg-gray-100 text-[10px] font-bold text-gray-500 uppercase"
                                                >
                                                    {{ question.type.charAt(0) }}
                                                </div>
                                                <div class="flex min-w-0 flex-col">
                                                    <p class="line-clamp-2 text-sm font-medium text-gray-800">{{ question.content }}</p>
                                                    <div class="mt-1 flex items-center gap-x-2">
                                                        <span class="text-xs text-gray-500 capitalize">{{ question.type.replace('_', ' ') }}</span>
                                                        <span v-if="question.creator" class="text-xs font-medium text-primary"
                                                            >Contributed by {{ question.creator.name }}</span
                                                        >
                                                    </div>
                                                </div>
                                                <img
                                                    v-if="question.image_path"
                                                    :src="`/storage/${question.image_path}`"
                                                    class="size-10 rounded border border-gray-100 object-cover"
                                                />
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-[11px] font-medium text-gray-600 uppercase tracking-wide">
                                                {{ question.school_class.level }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-semibold text-gray-800">{{ question.topic.subject.name }}</span>
                                                <span class="text-xs text-gray-500"
                                                    >{{ question.school_class.name }} • {{ question.topic.name }}</span
                                                >
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span
                                                class="inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                                                :class="getDifficultyClasses(question.difficulty)"
                                            >
                                                {{ question.difficulty }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex items-center justify-end gap-x-2">
                                                <Link
                                                    v-if="(page.props.auth.user as any).permissions.includes('bank:edit')"
                                                    :href="edit(question.id).url"
                                                    class="inline-flex size-8 items-center justify-center gap-x-2 rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                                                >
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                                        />
                                                    </svg>
                                                </Link>
                                                <button
                                                    v-if="(page.props.auth.user as any).permissions.includes('bank:delete')"
                                                    @click="confirmDelete(question)"
                                                    class="inline-flex size-8 items-center justify-center gap-x-2 rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-red-50 hover:text-red-500 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                                                >
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="questions.data.length === 0">
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="mb-3 size-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                                    />
                                                </svg>
                                                <p class="text-sm">No questions found matching your criteria</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div class="grid gap-3 border-t border-gray-200 px-6 py-4 md:flex md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Page <span class="font-semibold text-gray-800">{{ questions.current_page }}</span> of
                                        <span class="font-semibold text-gray-800">{{ questions.last_page }}</span>
                                    </p>
                                </div>

                                <div class="inline-flex gap-x-2">
                                    <Link
                                        v-for="link in questions.links"
                                        :key="link.label"
                                        :href="link.url || '#'"
                                        class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium shadow-sm transition-all focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                                        :class="[
                                            link.active ? 'border-transparent bg-primary text-white' : 'bg-white text-gray-800 hover:bg-gray-50',
                                            !link.url && 'pointer-events-none opacity-50',
                                        ]"
                                    >
                                        <span v-html="link.label" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <div v-if="isImportModalOpen" class="fixed inset-0 z-80 flex items-center justify-center overflow-x-hidden overflow-y-auto p-4">
            <div @click="isImportModalOpen = false" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
            <div class="relative w-full max-w-md rounded-xl border border-gray-200 bg-white shadow-lg">
                <div class="p-6 text-center">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Batch Import</h3>
                    <p class="mt-2 text-sm text-gray-500">Upload your questions in bulk using our template.</p>

                    <div class="mt-4">
                        <a :href="downloadTemplate().url" class="text-sm font-medium text-primary hover:underline">Download Template</a>
                    </div>

                    <form @submit.prevent="handleImport" class="mt-6 space-y-4">
                        <label class="block cursor-pointer rounded-lg border-2 border-dashed border-gray-200 p-8 transition-colors hover:bg-gray-50">
                            <input type="file" class="hidden" @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null" />
                            <div class="flex flex-col items-center">
                                <svg class="mb-2 size-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                    />
                                </svg>
                                <span class="text-sm text-gray-500">{{ importForm.file ? importForm.file.name : 'Select file (CSV, XLSX)' }}</span>
                            </div>
                        </label>
                        <p v-if="importForm.errors.file" class="text-xs text-red-500">{{ importForm.errors.file }}</p>

                        <div class="mt-6 flex gap-x-3">
                            <button
                                type="button"
                                @click="isImportModalOpen = false"
                                class="flex-1 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-800 hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="!importForm.file || importForm.processing"
                                class="hover:bg-primary-hover flex-1 rounded-lg border border-transparent bg-primary px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                            >
                                Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Question?"
            message="This action is permanent and cannot be undone."
            confirm-label="Delete"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </component>
</template>
