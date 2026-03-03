<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';
import {
    create,
    edit,
    index as indexAction,
    exportMethod,
    destroy,
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
    batches: { id: string, name: string }[];
    difficulties: { value: string; label: string }[];
    filters: {
        search?: string;
        subject_id?: string;
        school_class_id?: string;
        prospective_class_id?: string;
        difficulty?: string;
    };
}>();

const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user.roles.includes('admin'));
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
</script>

<template>
    <component :is="Layout">
        <Head title="Question Repository" />

        <div class="w-full space-y-8 md:space-y-10">
            <!-- AI Processing Banner -->
            <div
                v-if="isSeeding"
                class="animate-in fade-in slide-in-from-top-4 relative overflow-hidden rounded-xl border border-primary/20 bg-primary px-5 py-4 md:px-8 md:py-6 shadow-xl shadow-primary/10"
            >
                <div class="relative z-10 flex flex-col items-center justify-between gap-4 sm:flex-row md:gap-6">
                    <div class="flex items-start gap-4 md:gap-5">
                        <div class="flex h-10 w-10 md:h-14 md:w-14 shrink-0 items-center justify-center rounded-xl bg-white/10 text-lemon-yellow backdrop-blur-xl">
                            <svg class="h-6 w-6 md:h-8 md:w-8 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-base md:text-lg font-black tracking-tight text-white">AI Seeding in Progress</h4>
                            <p class="text-xs md:text-sm leading-relaxed font-medium text-white/70">
                                Generating for
                                <span class="font-black text-lemon-yellow underline decoration-dotted underline-offset-4">{{ isSeeding.topic }}</span
                                >.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div
                            class="flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 md:px-4 md:py-2 text-[9px] md:text-[10px] font-black tracking-widest text-white uppercase backdrop-blur-md"
                        >
                            <div class="h-1 w-1 md:h-1.5 md:w-1.5 animate-ping rounded-full bg-lemon-yellow"></div>
                            Processing Batch
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Header -->
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-slate-900">Question Bank</h1>
                    <p class="mt-1 text-[10px] md:text-sm font-bold tracking-widest text-slate-400 uppercase">Repository • {{ questions.total }} Verified Items</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-3">
                        <button
                            @click="refresh"
                            :disabled="isRefreshing"
                            class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 md:px-5 md:py-3 text-[10px] md:text-xs font-black text-slate-600 uppercase shadow-sm transition-all hover:bg-slate-50 active:scale-95 disabled:opacity-50 sm:flex-none"
                        >
                            <svg
                                class="h-4 w-4 text-slate-400"
                                :class="{ 'animate-spin': isRefreshing }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                            {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
                        </button>
                        <a
                            v-if="(page.props.auth.user as any).permissions.includes('export questions')"
                            :href="exportMethod().url"
                            class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 md:px-5 md:py-3 text-[10px] md:text-xs font-black text-slate-600 uppercase shadow-sm transition-all hover:bg-slate-50 active:scale-95 sm:flex-none"
                        >
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Export
                        </a>
                    </div>
                    <Link
                        v-if="(page.props.auth.user as any).permissions.includes('create questions')"
                        :href="create().url"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-5 py-3 md:px-6 md:py-3 text-[10px] md:text-xs font-black text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 sm:w-auto"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        Add New
                    </Link>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
                <!-- Search & Filters Container -->
                <div class="border-b border-slate-50 bg-white p-4 md:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                        <!-- Left: Search -->
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </span>
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Search repository..."
                                class="h-11 md:h-12 w-full rounded-xl border-none bg-slate-50 pl-11 md:pl-12 text-sm font-bold text-slate-700 transition-all focus:bg-white focus:ring-2 focus:ring-primary/10"
                            />
                        </div>

                        <!-- Right: Filters -->
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex h-11 md:h-12 w-full sm:w-auto flex-1 items-center gap-2 rounded-xl border border-slate-100 bg-slate-50 px-3 md:px-4">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                    />
                                </svg>
                                <select
                                    v-model="filterForm.subject_id"
                                    class="flex-1 cursor-pointer border-none bg-transparent text-[10px] md:text-xs font-black text-slate-600 uppercase focus:ring-0"
                                >
                                    <option value="">Subjects</option>
                                    <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                                <div class="mx-1.5 md:mx-2 h-4 w-px bg-slate-200"></div>
                                <select
                                    v-model="filterForm.school_class_id"
                                    class="flex-1 cursor-pointer border-none bg-transparent text-[10px] md:text-xs font-black text-slate-600 uppercase focus:ring-0"
                                >
                                    <option value="">Classes</option>
                                    <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                                <div v-if="batches && batches.length > 0" class="mx-1.5 md:mx-2 h-4 w-px bg-slate-200"></div>
                                <select
                                    v-if="batches && batches.length > 0"
                                    v-model="filterForm.prospective_class_id"
                                    class="flex-1 cursor-pointer border-none bg-transparent text-[10px] md:text-xs font-black text-slate-600 uppercase focus:ring-0"
                                >
                                    <option value="">Batches</option>
                                    <option v-for="b in batches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>

                            <button
                                v-if="filterForm.search || filterForm.subject_id || filterForm.school_class_id || filterForm.prospective_class_id"
                                @click="clearFilters"
                                class="flex h-11 md:h-12 items-center gap-2 rounded-xl px-3 md:px-4 text-[10px] md:text-xs font-black text-slate-400 uppercase transition-all hover:bg-red-50 hover:text-red-500"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="w-10 md:w-12 px-4 md:px-8 py-4 md:py-5">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        @change="toggleSelectAll"
                                        class="rounded border-slate-200 text-primary focus:ring-primary/20"
                                    />
                                </th>
                                <th class="px-4 md:px-6 py-4 md:py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase whitespace-nowrap">Question Details</th>
                                <th class="px-4 md:px-6 py-4 md:py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase whitespace-nowrap">Context</th>
                                <th class="px-4 md:px-6 py-4 md:py-5 text-center text-[10px] font-black tracking-widest text-slate-400 uppercase whitespace-nowrap">Difficulty</th>
                                <th class="px-4 md:px-8 py-4 md:py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="question in questions.data" :key="question.id" class="group transition-all hover:bg-[#F8F9FB]">
                                <td class="px-4 md:px-8 py-4 md:py-6 whitespace-nowrap">
                                    <input
                                        type="checkbox"
                                        v-model="selectedIds"
                                        :value="question.id"
                                        class="rounded border-slate-200 text-primary focus:ring-primary/20"
                                    />
                                </td>
                                <td class="max-w-xs md:max-w-xl px-4 md:px-6 py-4 md:py-6 whitespace-nowrap">
                                    <div class="flex items-center gap-3 md:gap-4">
                                        <div
                                            class="flex h-8 w-8 md:h-10 md:w-10 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-[10px] md:text-xs font-black text-slate-400"
                                        >
                                            {{ question.type.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-xs md:text-sm leading-relaxed font-black text-slate-800"
                                            >
                                                {{ question.content }}
                                            </p>
                                            <div class="mt-1 flex items-center gap-2">
                                                <span class="text-[9px] font-bold tracking-widest text-slate-400 uppercase">{{
                                                    question.type.replace('_', ' ')
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 md:py-6 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <p class="text-[10px] md:text-xs font-black text-slate-700 uppercase">{{ question.topic.subject.name }}</p>
                                            <span
                                                v-if="question.prospective_class"
                                                class="rounded-full bg-primary/10 px-2 py-0.5 text-[7px] md:text-[8px] font-black tracking-widest text-primary uppercase"
                                            >
                                                {{ question.prospective_class.name }}
                                            </span>
                                        </div>
                                        <p class="text-[8px] md:text-[10px] font-bold tracking-tight text-slate-400 uppercase">
                                            {{ question.school_class.name }} • {{ question.topic.name }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 md:py-6 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-lg px-2 py-0.5 md:px-2.5 md:py-1 text-[8px] md:text-[9px] font-black tracking-widest uppercase shadow-sm',
                                            question.difficulty === 'easy'
                                                ? 'border border-green-100 bg-green-50 text-green-600'
                                                : question.difficulty === 'medium'
                                                  ? 'border border-blue-100 bg-blue-50 text-blue-600'
                                                  : 'border border-red-100 bg-red-50 text-red-600',
                                        ]"
                                    >
                                        {{ question.difficulty }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-8 py-4 md:py-6 text-right whitespace-nowrap">
                                    <div class="relative inline-block">
                                        <button
                                            @click.stop="toggleDropdown(question.id)"
                                            class="flex h-9 w-9 md:h-10 md:w-10 items-center justify-center rounded-lg text-slate-400 transition-all hover:bg-slate-100 hover:text-slate-600 active:scale-90"
                                        >
                                            <svg class="h-4 w-4 md:h-5 md:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2.5"
                                                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                                                />
                                            </svg>
                                        </button>
                                        <div
                                            v-if="activeDropdown === question.id"
                                            class="absolute right-0 z-50 mt-2 w-40 md:w-48 origin-top-right rounded-lg bg-white p-1 shadow-2xl ring-1 ring-black/5"
                                        >
                                            <Link
                                                :href="edit(question.id).url"
                                                class="flex items-center gap-2 rounded-md px-3 py-2 text-[9px] md:text-[10px] font-black text-slate-600 uppercase hover:bg-slate-50"
                                                >Edit</Link
                                            >
                                            <button
                                                @click="confirmDelete(question)"
                                                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-[9px] md:text-[10px] font-black text-red-600 uppercase hover:bg-red-50"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col gap-4 items-center justify-between border-t border-slate-50 bg-white px-6 md:px-8 py-4 md:py-6 sm:flex-row">
                    <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                        Page <span class="font-black text-slate-900">{{ questions.current_page }}</span> of {{ questions.last_page }}
                    </p>
                    <div class="flex flex-wrap justify-center gap-1.5 md:gap-2">
                        <Link
                            v-for="link in questions.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="flex h-8 md:h-10 min-w-[2rem] md:min-w-[2.5rem] items-center justify-center rounded-lg text-[10px] md:text-xs font-black transition-all"
                            :class="[
                                link.active ? 'bg-primary text-white shadow-lg' : 'bg-slate-50 text-slate-600 hover:bg-slate-100',
                                !link.url && 'cursor-not-allowed opacity-30',
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
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
