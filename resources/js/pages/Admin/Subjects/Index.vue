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
    return levels.map(level => ({
        id: level,
        name: level.charAt(0).toUpperCase() + level.slice(1),
        count: (props.counts as any)[level] || 0,
        iconBg: level === 'nursery' ? 'bg-pink-100 text-pink-600' : (level === 'secondary' ? 'bg-indigo-100 text-indigo-600' : 'bg-orange-100 text-orange-600')
    }));
});

// Filtering
const applyFilters = debounce(() => {
    router.get(index().url, { 
        level: selectedLevel.value,
        search: search.value 
    }, { preserveState: true, replace: true });
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
                <Link href="/admin/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <button @click="selectedLevel = null" class="hover:text-primary transition-colors" :class="!selectedLevel ? 'text-gray-800 font-bold' : ''">Curriculum Registry</button>
                <template v-if="selectedLevel">
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span class="text-gray-800 font-bold uppercase tracking-tight">{{ selectedLevel }}</span>
                </template>
            </nav>

            <!-- 1. TIER OVERVIEW (Cards) -->
            <div v-if="!selectedLevel && !search" class="space-y-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Curriculum Vault</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Select an academic tier to manage specialized subjects and syllabi.
                    </p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="stat in levelStats" 
                        :key="stat.id"
                        class="group flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl transition-all hover:shadow-md"
                    >
                        <div class="p-4 md:p-8 flex-1">
                            <div class="size-12 rounded-lg flex items-center justify-center mb-6" :class="stat.iconBg">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 uppercase tracking-tight">
                                {{ stat.name }} School
                            </h3>
                            <p class="mt-2 text-gray-500 text-sm leading-relaxed">
                                Portal for global subjects within the {{ stat.name.toLowerCase() }} framework.
                            </p>
                            <div class="mt-4 inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ stat.count }} Active Subjects
                            </div>
                        </div>
                        <div class="bg-gray-50 border-t border-gray-200 rounded-b-xl py-3 px-4 md:px-8">
                            <button 
                                @click="selectedLevel = stat.id"
                                class="w-full inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-hover focus:outline-none transition-all"
                            >
                                Open Registry
                                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. SUBJECT LIST (Standard Preline Layout) -->
            <div v-else class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <button @click="clearFilters" class="size-9 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-primary transition-all shadow-sm">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        <div>
                            <h1 class="text-xl font-bold text-gray-800 uppercase tracking-tight">
                                {{ selectedLevel ? `${selectedLevel} Subjects` : 'All Subjects' }}
                            </h1>
                            <p class="text-xs text-gray-500 mt-1 uppercase tracking-widest">{{ subjects.total }} Global Records</p>
                        </div>
                    </div>
                    <button
                        @click="openCreateModal"
                        class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover transition-all shadow-sm active:scale-95"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                        Add Subject
                    </button>
                </div>

                <!-- Main Table Card -->
                <div class="flex flex-col">
                    <div class="-m-1.5 overflow-x-auto">
                        <div class="p-1.5 min-w-full inline-block align-middle">
                            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                                <!-- Search & Filter Header -->
                                <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200">
                                    <div class="relative flex-1 max-w-md">
                                        <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3">
                                            <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="search"
                                            type="text"
                                            placeholder="Search subjects..."
                                            class="py-2 px-3 ps-9 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                        />
                                    </div>

                                    <div class="inline-flex gap-x-2">
                                        <select 
                                            v-model="selectedLevel"
                                            class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
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
                                            <th scope="col" class="px-6 py-3 text-start text-[10px] font-bold text-gray-400 uppercase tracking-widest">Syllabus Identity</th>
                                            <th scope="col" class="px-6 py-3 text-start text-[10px] font-bold text-gray-400 uppercase tracking-widest">Academic Level</th>
                                            <th scope="col" class="px-6 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">Topics</th>
                                            <th scope="col" class="px-6 py-3 text-end text-[10px] font-bold text-gray-400 uppercase tracking-widest">Control</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="subject in subjects.data" :key="subject.id" class="hover:bg-gray-50 transition-colors group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-x-4">
                                                    <div class="size-10 flex-shrink-0 flex items-center justify-center rounded-lg bg-gray-50 text-[10px] font-bold text-gray-400 group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                                        {{ subject.name.substring(0, 2).toUpperCase() }}
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-sm font-semibold text-gray-800 uppercase tracking-tight">{{ subject.name }}</span>
                                                        <span class="text-xs text-gray-400 line-clamp-1 max-w-sm">
                                                            {{ subject.description || 'No detailed syllabus summary provided.' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center py-1 px-2.5 rounded-md text-xs font-medium uppercase"
                                                    :class="subject.level === 'nursery' ? 'bg-pink-100 text-pink-800' : (subject.level === 'secondary' ? 'bg-indigo-100 text-indigo-800' : 'bg-orange-100 text-orange-800')"
                                                >
                                                    {{ subject.level }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="text-xs font-medium text-gray-600">{{ subject.topics_count }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-end text-sm font-medium">
                                                <div class="flex justify-end items-center gap-x-2">
                                                    <button @click="openEditModal(subject)" class="text-gray-500 hover:text-primary transition-colors focus:outline-none">
                                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                    </button>
                                                    <button @click="confirmDelete(subject)" class="text-gray-500 hover:text-red-500 transition-colors focus:outline-none">
                                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="subjects.data.length === 0">
                                            <td colspan="4" class="px-6 py-20 text-center">
                                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">No subjects matching your criteria.</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Pagination -->
                                <div v-if="subjects.total > subjects.per_page" class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200">
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Showing <span class="font-semibold text-gray-800">{{ subjects.from }}</span> to <span class="font-semibold text-gray-800">{{ subjects.to }}</span> of <span class="font-semibold text-gray-800">{{ subjects.total }}</span>
                                        </p>
                                    </div>

                                    <div class="inline-flex gap-x-2">
                                        <Link
                                            v-for="link in subjects.links"
                                            :key="link.label"
                                            :href="link.url || '#'"
                                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                                            :class="[
                                                link.active ? 'bg-gray-100' : '',
                                                !link.url && 'opacity-50 pointer-events-none',
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
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-[80] overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
            <div @click="closeModal" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
            <div class="relative w-full max-w-lg bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 bg-gray-50/50">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">{{ isEditing ? 'Edit Subject' : 'New Subject' }}</h3>
                    <button @click="closeModal" type="button" class="size-8 inline-flex justify-center items-center rounded-lg bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-all active:scale-90">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Subject Nomenclature</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="e.g. CORE MATHEMATICS"
                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm font-medium text-gray-800 focus:border-primary focus:ring-primary disabled:opacity-50 uppercase tracking-tight"
                        />
                        <div v-if="form.errors.name" class="text-xs text-red-500 mt-2 font-bold uppercase tracking-wide">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Mandatory Academic Tier</label>
                        <div class="flex p-1 bg-gray-50 rounded-lg border border-gray-200">
                            <button
                                v-for="level in ['nursery', 'primary', 'secondary']"
                                :key="level"
                                type="button"
                                @click="form.level = level"
                                class="flex-1 py-2 text-[10px] font-black uppercase rounded-md transition-all"
                                :class="form.level === level 
                                    ? 'bg-white text-gray-800 shadow-sm border border-gray-200' 
                                    : 'text-gray-400 hover:text-gray-600'"
                            >
                                {{ level }}
                            </button>
                        </div>
                        <div v-if="form.errors.level" class="text-xs text-red-500 mt-2 font-bold uppercase tracking-wide">{{ form.errors.level }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Curriculum Context (Optional)</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            placeholder="Provide a high-level summary..."
                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm font-medium text-gray-600 focus:border-primary focus:ring-primary disabled:opacity-50"
                        ></textarea>
                        <div v-if="form.errors.description" class="text-xs text-red-500 mt-2 font-bold uppercase tracking-wide">{{ form.errors.description }}</div>
                    </div>

                    <div class="pt-4 flex justify-end gap-x-2 border-t border-gray-100">
                        <button type="button" @click="closeModal" class="py-2 px-4 text-xs font-bold uppercase tracking-widest text-gray-500 hover:text-gray-800 transition-colors">Abort</button>
                        <button type="submit" :disabled="form.processing" class="py-2.5 px-6 inline-flex items-center gap-x-2 text-xs font-bold uppercase tracking-widest rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover shadow-sm transition-all active:scale-95 disabled:opacity-50">
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
