<script setup lang="ts">
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';
import {
    create,
    edit,
    index as indexAction,
    exportMethod,
    importMethod,
    downloadTemplate,
    destroy,
} from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { Question, Subject, SchoolClass, PaginatedData, Batch } from '@/types/academics';

const props = defineProps<{
    questions: PaginatedData<Question>;
    subjects: Subject[];
    classes: SchoolClass[];
    batches: Batch[];
    difficulties: { value: string; label: string }[];
    filters: {
        search?: string;
        subject_id?: string;
        school_class_id?: string;
        prospective_class_id?: string;
        difficulty?: string;
    };
}>();

const page = usePage();
const branches = computed(() => (page.props as any).branches || {});
const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const isSeeding = computed(() => (page.props.auth as any).is_seeding);

const isRefreshing = ref(false);
const refresh = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['questions'],
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
const toggleDropdown = (id: string) => {
    activeDropdown.value = activeDropdown.value === id ? null : id;
};

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
    prospective_class_id: props.filters.prospective_class_id || '',
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
        prospective_class_id: '',
        difficulty: '',
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
                <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Question Bank</span>
            </nav>

            <!-- AI Processing Banner -->
            <div
                v-if="isSeeding"
                class="bg-primary rounded-xl p-4 md:p-6 shadow-sm border border-primary/20"
            >
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-white/10 text-white backdrop-blur-sm">
                            <svg class="size-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-white">AI Seeding in Progress</h4>
                            <p class="text-sm text-white/80">Generating for <span class="font-bold underline decoration-dotted underline-offset-4">{{ isSeeding.topic }}</span>.</p>
                        </div>
                    </div>
                    <div class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-white/10 text-white uppercase">
                        <span class="size-1.5 inline-block rounded-full bg-white animate-ping"></span>
                        Processing Batch
                    </div>
                </div>
            </div>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Universal Bank</h1>
                    <p class="text-sm text-gray-500 mt-1">Repository • {{ questions.total }} Verified Items</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="refresh"
                        :disabled="isRefreshing"
                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                    >
                        <svg class="size-4" :class="{ 'animate-spin': isRefreshing }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Refresh
                    </button>
                    <Link
                        v-if="(page.props.auth.user as any).permissions.includes('bank:create')"
                        href="/staff/questions/batch"
                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        Spreadsheet
                    </Link>
                    <button
                        v-if="(page.props.auth.user as any).permissions.includes('bank:create')"
                        @click="isImportModalOpen = true"
                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Import
                    </button>
                    <Link
                        v-if="(page.props.auth.user as any).permissions.includes('bank:create')"
                        :href="create().url"
                        class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Add New
                    </Link>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <!-- Filters Header -->
                            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200">
                                <div class="relative flex-1 max-w-md">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                    </div>
                                    <input
                                        v-model="filterForm.search"
                                        type="text"
                                        placeholder="Search repository..."
                                        class="py-3 px-4 ps-10 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                    />
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="w-40">
                                        <select 
                                            v-model="filterForm.subject_id"
                                            class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                        >
                                            <option value="">All Subjects</option>
                                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                                        </select>
                                    </div>
                                    <div class="w-40">
                                        <select 
                                            v-model="filterForm.school_class_id"
                                            class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                        >
                                            <option value="">All Classes</option>
                                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                                        </select>
                                    </div>
                                    <button
                                        v-if="filterForm.search || filterForm.subject_id || filterForm.school_class_id"
                                        @click="clearFilters"
                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-red-600 hover:bg-red-50 focus:outline-none focus:bg-red-50"
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
                                                    class="shrink-0 border-gray-200 rounded text-primary focus:ring-primary"
                                                />
                                            </div>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Question Details</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Context</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Difficulty</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="question in questions.data" :key="question.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    v-model="selectedIds"
                                                    :value="question.id"
                                                    class="shrink-0 border-gray-200 rounded text-primary focus:ring-primary"
                                                />
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 max-w-md">
                                            <div class="flex items-start gap-x-3">
                                                <div class="size-8 flex-shrink-0 flex items-center justify-center rounded bg-gray-100 text-[10px] font-bold text-gray-500 uppercase">
                                                    {{ question.type.charAt(0) }}
                                                </div>
                                                <div class="flex flex-col min-w-0">
                                                    <p class="text-sm text-gray-800 font-medium line-clamp-2">{{ question.content }}</p>
                                                    <div class="flex items-center gap-x-2 mt-1">
                                                        <span class="text-xs text-gray-500 capitalize">{{ question.type.replace('_', ' ') }}</span>
                                                        <span v-if="question.creator" class="text-xs text-primary font-medium">Contributed by {{ question.creator.name }}</span>
                                                    </div>
                                                </div>
                                                <img v-if="question.image_path" :src="`/storage/${question.image_path}`" class="size-10 rounded border border-gray-100 object-cover" />
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-semibold text-gray-800">{{ question.topic.subject.name }}</span>
                                                <span class="text-xs text-gray-500">{{ question.school_class.name }} • {{ question.topic.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span 
                                                class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-xs font-medium capitalize"
                                                :class="getDifficultyClasses(question.difficulty)"
                                            >
                                                {{ question.difficulty }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex justify-end items-center gap-x-2">
                                                <Link 
                                                    v-if="(page.props.auth.user as any).permissions.includes('bank:edit')"
                                                    :href="edit(question.id).url"
                                                    class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50"
                                                >
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                </Link>
                                                <button 
                                                    v-if="(page.props.auth.user as any).permissions.includes('bank:delete')"
                                                    @click="confirmDelete(question)"
                                                    class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-red-50 hover:text-red-500 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                                                >
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="questions.data.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="size-8 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                                <p class="text-sm">No questions found matching your criteria</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Page <span class="font-semibold text-gray-800">{{ questions.current_page }}</span> of <span class="font-semibold text-gray-800">{{ questions.last_page }}</span>
                                    </p>
                                </div>

                                <div class="inline-flex gap-x-2">
                                    <Link
                                        v-for="link in questions.links"
                                        :key="link.label"
                                        :href="link.url || '#'"
                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                                        :class="[
                                            link.active ? 'bg-primary text-white hover:bg-primary-hover border-transparent' : '',
                                            !link.url && 'opacity-50 pointer-events-none'
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
        <div v-if="isImportModalOpen" class="fixed inset-0 z-[80] overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
            <div @click="isImportModalOpen = false" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
            <div class="relative w-full max-w-md bg-white rounded-xl shadow-lg border border-gray-200">
                <div class="p-6 text-center">
                    <div class="size-16 mx-auto mb-4 bg-primary/10 text-primary rounded-full flex items-center justify-center">
                        <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Batch Import</h3>
                    <p class="text-sm text-gray-500 mt-2">Upload your questions in bulk using our template.</p>
                    
                    <div class="mt-4">
                        <a :href="downloadTemplate().url" class="text-sm font-medium text-primary hover:underline">Download Template</a>
                    </div>

                    <form @submit.prevent="handleImport" class="mt-6 space-y-4">
                        <label class="block border-2 border-dashed border-gray-200 rounded-lg p-8 cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="file" class="hidden" @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null" />
                            <div class="flex flex-col items-center">
                                <svg class="size-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                <span class="text-sm text-gray-500">{{ importForm.file ? importForm.file.name : 'Select file (CSV, XLSX)' }}</span>
                            </div>
                        </label>
                        <p v-if="importForm.errors.file" class="text-xs text-red-500">{{ importForm.errors.file }}</p>

                        <div class="flex gap-x-3 mt-6">
                            <button type="button" @click="isImportModalOpen = false" class="flex-1 py-2.5 px-4 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 hover:bg-gray-50">Cancel</button>
                            <button type="submit" :disabled="!importForm.file || importForm.processing" class="flex-1 py-2.5 px-4 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover disabled:opacity-50">Upload</button>
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
