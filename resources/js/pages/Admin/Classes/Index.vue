<script setup lang="ts">
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ref, watch } from 'vue';
import { store, update, destroy, index } from '@/actions/App/Http/Controllers/Admin/SchoolClassController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { PaginatedData } from '@/types/academics';

interface SchoolClass {
    id: string;
    name: string;
    slug: string;
    level: string;
}

const props = defineProps<{
    classes: PaginatedData<SchoolClass>;
    levels: { value: string; label: string }[];
    filters: {
        search?: string;
        level?: string;
    };
}>();

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingClass = ref<SchoolClass | null>(null);

const form = useForm({
    name: '',
    level: 'primary',
});

// Filtering
const search = ref(props.filters.search || '');
const levelFilter = ref(props.filters.level || '');

const applyFilters = debounce(() => {
    router.get(index().url, {
        search: search.value,
        level: levelFilter.value,
    }, { preserveState: true, replace: true });
}, 300);

watch([search, levelFilter], () => applyFilters());

const clearFilters = () => {
    search.value = '';
    levelFilter.value = '';
};

const openCreateModal = () => {
    isEditing.value = false;
    editingClass.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (cls: SchoolClass) => {
    isEditing.value = true;
    editingClass.value = cls;
    form.name = cls.name;
    form.level = cls.level;
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingClass.value) {
        form.put(update(editingClass.value.id).url, {
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
const classToDelete = ref<SchoolClass | null>(null);

const confirmDelete = (cls: SchoolClass) => {
    classToDelete.value = cls;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (classToDelete.value) {
        router.delete(destroy(classToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                classToDelete.value = null;
            },
        });
    }
};

const getLevelClasses = (level: string) => {
    switch (level) {
        case 'nursery':
            return 'bg-pink-100 text-pink-800';
        case 'secondary':
            return 'bg-indigo-100 text-indigo-800';
        default:
            return 'bg-orange-100 text-orange-800';
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Class Hierarchy" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Academic Framework</span>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Global Classes</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Global Classes</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Define academic levels available across all campuses.
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                    Add Global Class
                </button>
            </div>

            <!-- Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <!-- Search & Filter Header -->
                            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200">
                                <div class="relative flex-1 max-w-md">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                    </div>
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Search classes..."
                                        class="py-2 px-3 ps-10 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                    />
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="w-40">
                                        <select 
                                            v-model="levelFilter"
                                            class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                        >
                                            <option value="">All Levels</option>
                                            <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                                        </select>
                                    </div>
                                    <button
                                        v-if="search || levelFilter"
                                        @click="clearFilters"
                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-red-600 hover:bg-red-50 focus:outline-none"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-widest">Class Name</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-widest">Academic Level</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-widest">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="cls in classes.data" :key="cls.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-semibold text-gray-800 uppercase tracking-tight">{{ cls.name }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span 
                                                class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                                :class="getLevelClasses(cls.level)"
                                            >
                                                {{ cls.level }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex justify-end items-center gap-x-2">
                                                <button @click="openEditModal(cls)" class="text-gray-500 hover:text-primary transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                </button>
                                                <button @click="confirmDelete(cls)" class="text-gray-500 hover:text-red-500 transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="classes.data.length === 0">
                                        <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                            <p class="text-sm">No class hierarchies found.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div v-if="classes.total > classes.per_page" class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Showing <span class="font-semibold text-gray-800">{{ classes.from }}</span> to <span class="font-semibold text-gray-800">{{ classes.to }}</span> of <span class="font-semibold text-gray-800">{{ classes.total }}</span>
                                    </p>
                                </div>

                                <div class="inline-flex gap-x-2">
                                    <Link
                                        v-for="link in classes.links"
                                        :key="link.label"
                                        :href="link.url || '#'"
                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 shadow-sm disabled:opacity-50 disabled:pointer-events-none focus:outline-none transition-all"
                                        :class="[
                                            link.active 
                                                ? 'bg-primary text-white border-transparent' 
                                                : 'bg-white text-gray-800 hover:bg-gray-50',
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

        <!-- Create/Edit Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-[80] overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
            <div @click="closeModal" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
            <div class="relative w-full max-w-lg bg-white rounded-xl shadow-lg border border-gray-200">
                <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 bg-gray-50/50">
                    <h3 class="font-semibold text-gray-800 uppercase tracking-tight text-sm">{{ isEditing ? 'Update Global Class' : 'Define New Global Class' }}</h3>
                    <button @click="closeModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50">
                        <span class="sr-only">Close</span>
                        <svg class="flex-shrink-0 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit" class="p-6 overflow-y-auto max-h-[calc(100vh-150px)]">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-800 uppercase tracking-widest text-[10px]">Class Nomenclature</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. Primary 1 or JSS 1"
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                            />
                            <p v-if="form.errors.name" class="text-sm text-red-600 mt-2">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-800 uppercase tracking-widest text-[10px]">Mandatory Academic Level</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button 
                                    v-for="level in levels"
                                    :key="level.value"
                                    type="button"
                                    @click="form.level = level.value"
                                    class="py-3 px-4 text-center text-xs font-bold uppercase rounded-lg border-2 transition-all shadow-sm"
                                    :class="form.level === level.value ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-gray-100 text-gray-400 hover:border-gray-200'"
                                >
                                    {{ level.label }}
                                </button>
                            </div>
                            <p v-if="form.errors.level" class="text-sm text-red-600 mt-2">{{ form.errors.level }}</p>
                        </div>
                    </div>
                    
                    <div class="mt-8 flex justify-end gap-x-2 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="py-2 px-4 text-xs font-bold uppercase tracking-widest text-gray-500 hover:text-gray-800 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="py-2.5 px-6 inline-flex items-center gap-x-2 text-xs font-bold uppercase tracking-widest rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover shadow-sm transition-all active:scale-95 disabled:opacity-50"
                        >
                            {{ isEditing ? 'Confirm Changes' : 'Create Global Class' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Global Class?"
            :message="`Are you sure you want to delete ${classToDelete?.name}? This will remove it from the entire system context and cannot be undone.`"
            confirm-label="Delete Permanent"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
