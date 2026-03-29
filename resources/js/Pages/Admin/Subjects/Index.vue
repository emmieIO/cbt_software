<script setup lang="ts">
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ref, computed, watch } from 'vue';
import { store, update, destroy, index } from '@/actions/App/Http/Controllers/Admin/SubjectController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { PaginatedData } from '@/types/academics';

interface Subject {
    id: string;
    name: string;
    description: string | null;
    level: string;
    topics_count: number;
}

const props = defineProps<{
    subjects: PaginatedData<Subject>;
    counts: {
        nursery: number;
        primary: number;
        secondary: number;
    };
    filters: {
        level?: string;
        search?: string;
    };
}>();

const selectedLevel = ref<string | null>(props.filters.level || null);
const search = ref(props.filters.search || '');

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingSubject = ref<Subject | null>(null);

const form = useForm({
    name: '',
    description: '',
    level: 'primary',
});

// Level Summaries for Overview
const levelStats = computed(() => {
    const levels = ['nursery', 'primary', 'secondary'];
    return levels.map((level) => ({
        id: level,
        name: level.charAt(0).toUpperCase() + level.slice(1),
        count: (props.counts as any)[level] || 0,
        iconBg:
            level === 'nursery'
                ? 'bg-pink-100 text-pink-600'
                : level === 'secondary'
                  ? 'bg-indigo-100 text-indigo-600'
                  : 'bg-orange-100 text-orange-600',
    }));
});

// Filtering
const applyFilters = debounce(() => {
    router.get(
        index().url,
        {
            level: selectedLevel.value,
            search: search.value,
        },
        { preserveState: true, replace: true },
    );
}, 300);

watch(selectedLevel, () => applyFilters());
watch(search, () => applyFilters());

const clearFilters = () => {
    selectedLevel.value = null;
    search.value = '';
};

const openCreateModal = () => {
    isEditing.value = false;
    editingSubject.value = null;
    form.reset();
    if (selectedLevel.value) form.level = selectedLevel.value;
    isModalOpen.value = true;
};

const openEditModal = (subject: Subject) => {
    isEditing.value = true;
    editingSubject.value = subject;
    form.name = subject.name;
    form.description = subject.description || '';
    form.level = subject.level;
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingSubject.value) {
        form.put(update(editingSubject.value.id).url, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(store().url, {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const isDeleteModalOpen = ref(false);
const subjectToDelete = ref<Subject | null>(null);

const confirmDelete = (subject: Subject) => {
    subjectToDelete.value = subject;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (subjectToDelete.value) {
        router.delete(destroy(subjectToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                subjectToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Curriculum Directory" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <button
                    @click="selectedLevel = null"
                    class="transition-colors hover:text-primary"
                    :class="!selectedLevel ? 'font-bold text-gray-800' : ''"
                >
                    Curriculum Registry
                </button>
                <template v-if="selectedLevel">
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="font-bold tracking-tight text-gray-800 uppercase">{{ selectedLevel }}</span>
                </template>
            </nav>

            <!-- 1. TIER OVERVIEW (Cards) -->
            <div v-if="!selectedLevel && !search" class="space-y-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Curriculum Vault</h1>
                    <p class="mt-1 text-sm text-gray-500">Select an academic tier to manage specialized subjects and syllabi.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="stat in levelStats"
                        :key="stat.id"
                        class="group flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:shadow-md"
                    >
                        <div class="flex-1 p-4 md:p-8">
                            <div class="mb-6 flex size-12 items-center justify-center rounded-lg" :class="stat.iconBg">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                    />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold tracking-tight text-gray-800 uppercase">{{ stat.name }} School</h3>
                            <p class="mt-2 text-sm leading-relaxed text-gray-500">
                                Portal for global subjects within the {{ stat.name.toLowerCase() }} framework.
                            </p>
                            <div
                                class="mt-4 inline-flex items-center gap-x-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-800"
                            >
                                {{ stat.count }} Active Subjects
                            </div>
                        </div>
                        <div class="rounded-b-xl border-t border-gray-200 bg-gray-50 px-4 py-3 md:px-8">
                            <button
                                @click="selectedLevel = stat.id"
                                class="hover:text-primary-hover inline-flex w-full items-center justify-center gap-x-2 rounded-lg border border-transparent text-sm font-semibold text-primary transition-all focus:outline-none"
                            >
                                Open Registry
                                <svg
                                    class="size-4 flex-shrink-0"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. SUBJECT LIST (Standard Preline Layout) -->
            <div v-else class="space-y-6">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-4">
                        <button
                            @click="clearFilters"
                            class="flex size-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 shadow-sm transition-all hover:text-primary"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-gray-800 uppercase">
                                {{ selectedLevel ? `${selectedLevel} Subjects` : 'All Subjects' }}
                            </h1>
                            <p class="mt-1 text-xs tracking-widest text-gray-500 uppercase">{{ subjects.total }} Global Records</p>
                        </div>
                    </div>
                    <button
                        @click="openCreateModal"
                        class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all active:scale-95"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Subject
                    </button>
                </div>

                <!-- Main Table Card -->
                <div class="flex flex-col">
                    <div class="-m-1.5 overflow-x-auto">
                        <div class="inline-block min-w-full p-1.5 align-middle">
                            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <!-- Search & Filter Header -->
                                <div class="grid gap-3 border-b border-gray-200 px-6 py-4 md:flex md:items-center md:justify-between">
                                    <div class="relative max-w-md flex-1">
                                        <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                                            <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2.5"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                                />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="search"
                                            type="text"
                                            placeholder="Search subjects..."
                                            class="block w-full rounded-lg border-gray-200 px-3 py-2 ps-9 text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                        />
                                    </div>

                                    <div class="inline-flex gap-x-2">
                                        <select
                                            v-model="selectedLevel"
                                            class="block w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                        >
                                            <option :value="null">All Levels</option>
                                            <option value="nursery">Nursery</option>
                                            <option value="primary">Primary</option>
                                            <option value="secondary">Secondary</option>
                                        </select>
                                    </div>
                                </div>

                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-start text-[10px] font-bold tracking-widest text-gray-400 uppercase"
                                            >
                                                Syllabus Identity
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-start text-[10px] font-bold tracking-widest text-gray-400 uppercase"
                                            >
                                                Academic Level
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-center text-[10px] font-bold tracking-widest text-gray-400 uppercase"
                                            >
                                                Topics
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-end text-[10px] font-bold tracking-widest text-gray-400 uppercase">
                                                Control
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="subject in subjects.data" :key="subject.id" class="group transition-colors hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-x-4">
                                                    <div
                                                        class="flex size-10 flex-shrink-0 items-center justify-center rounded-lg bg-gray-50 text-[10px] font-bold text-gray-400 transition-colors group-hover:bg-primary/10 group-hover:text-primary"
                                                    >
                                                        {{ subject.name.substring(0, 2).toUpperCase() }}
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-sm font-semibold tracking-tight text-gray-800 uppercase">{{
                                                            subject.name
                                                        }}</span>
                                                        <span class="line-clamp-1 max-w-sm text-xs text-gray-400">
                                                            {{ subject.description || 'No detailed syllabus summary provided.' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span
                                                    class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium uppercase"
                                                    :class="
                                                        subject.level === 'nursery'
                                                            ? 'bg-pink-100 text-pink-800'
                                                            : subject.level === 'secondary'
                                                              ? 'bg-indigo-100 text-indigo-800'
                                                              : 'bg-orange-100 text-orange-800'
                                                    "
                                                >
                                                    {{ subject.level }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="text-xs font-medium text-gray-600">{{ subject.topics_count }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-end text-sm font-medium">
                                                <div class="flex items-center justify-end gap-x-2">
                                                    <button
                                                        @click="openEditModal(subject)"
                                                        class="text-gray-500 transition-colors hover:text-primary focus:outline-none"
                                                    >
                                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                                            />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        @click="confirmDelete(subject)"
                                                        class="text-gray-500 transition-colors hover:text-red-500 focus:outline-none"
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
                                        <tr v-if="subjects.data.length === 0">
                                            <td colspan="4" class="px-6 py-20 text-center">
                                                <p class="text-xs font-bold tracking-widest text-gray-400 uppercase">
                                                    No subjects matching your criteria.
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Pagination -->
                                <div
                                    v-if="subjects.total > subjects.per_page"
                                    class="grid gap-3 border-t border-gray-200 px-6 py-4 md:flex md:items-center md:justify-between"
                                >
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Showing <span class="font-semibold text-gray-800">{{ subjects.from }}</span> to
                                            <span class="font-semibold text-gray-800">{{ subjects.to }}</span> of
                                            <span class="font-semibold text-gray-800">{{ subjects.total }}</span>
                                        </p>
                                    </div>

                                    <div class="inline-flex gap-x-2">
                                        <Link
                                            v-for="link in subjects.links"
                                            :key="link.label"
                                            :href="link.url || '#'"
                                            class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                                            :class="[link.active ? 'bg-gray-100' : '', !link.url && 'pointer-events-none opacity-50']"
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
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-[80] flex items-center justify-center overflow-x-hidden overflow-y-auto p-4">
            <div @click="closeModal" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50/50 px-4 py-3">
                    <h3 class="text-sm font-bold tracking-widest text-gray-800 uppercase">{{ isEditing ? 'Edit Subject' : 'New Subject' }}</h3>
                    <button
                        @click="closeModal"
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-lg bg-gray-50 text-gray-400 transition-all hover:bg-gray-100 hover:text-gray-600 active:scale-90"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6 p-6">
                    <div>
                        <label class="mb-2 block text-xs font-bold text-gray-500 uppercase">Subject Nomenclature</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="e.g. CORE MATHEMATICS"
                            class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm font-medium tracking-tight text-gray-800 uppercase focus:border-primary focus:ring-primary disabled:opacity-50"
                        />
                        <div v-if="form.errors.name" class="mt-2 text-xs font-bold tracking-wide text-red-500 uppercase">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold text-gray-500 uppercase">Mandatory Academic Tier</label>
                        <div class="flex rounded-lg border border-gray-200 bg-gray-50 p-1">
                            <button
                                v-for="level in ['nursery', 'primary', 'secondary']"
                                :key="level"
                                type="button"
                                @click="form.level = level"
                                class="flex-1 rounded-md py-2 text-[10px] font-black uppercase transition-all"
                                :class="
                                    form.level === level
                                        ? 'border border-gray-200 bg-white text-gray-800 shadow-sm'
                                        : 'text-gray-400 hover:text-gray-600'
                                "
                            >
                                {{ level }}
                            </button>
                        </div>
                        <div v-if="form.errors.level" class="mt-2 text-xs font-bold tracking-wide text-red-500 uppercase">
                            {{ form.errors.level }}
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold text-gray-500 uppercase">Curriculum Context (Optional)</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            placeholder="Provide a high-level summary..."
                            class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm font-medium text-gray-600 focus:border-primary focus:ring-primary disabled:opacity-50"
                        ></textarea>
                        <div v-if="form.errors.description" class="mt-2 text-xs font-bold tracking-wide text-red-500 uppercase">
                            {{ form.errors.description }}
                        </div>
                    </div>

                    <div class="flex justify-end gap-x-2 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-xs font-bold tracking-widest text-gray-500 uppercase transition-colors hover:text-gray-800"
                        >
                            Abort
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-6 py-2.5 text-xs font-bold tracking-widest text-white uppercase shadow-sm transition-all active:scale-95 disabled:opacity-50"
                        >
                            {{ isEditing ? 'Save Changes' : 'Confirm Entry' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Purge Subject Record?"
            :message="`Are you sure you want to delete ${subjectToDelete?.name}? This action is irreversible.`"
            confirm-label="Purge Permanently"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
