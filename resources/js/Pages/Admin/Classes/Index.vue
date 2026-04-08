<script setup lang="ts">
import { Head, router, Link, useForm, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';
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
const page = usePage();
const canDeleteClass = computed(() => (page.props.auth.user as any).permissions?.includes('admin:delete_class'));

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
    router.get(
        index().url,
        {
            search: search.value,
            level: levelFilter.value,
        },
        { preserveState: true, replace: true },
    );
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
                <Link href="/admin/dashboard" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-800">Academic Framework</span>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-800">Global Classes</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Global Classes</h1>
                    <p class="mt-1 text-sm text-gray-500">Define academic levels available across all campuses.</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-2.5 text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Global Class
                </button>
            </div>

            <!-- Table Card -->
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
                                        placeholder="Search classes..."
                                        class="block w-full rounded-lg border-gray-200 px-3 py-2 ps-10 text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                    />
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="w-40">
                                        <select
                                            v-model="levelFilter"
                                            class="block w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                        >
                                            <option value="">All Levels</option>
                                            <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                                        </select>
                                    </div>
                                    <button
                                        v-if="search || levelFilter"
                                        @click="clearFilters"
                                        class="inline-flex items-center gap-x-2 rounded-lg border border-transparent px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 focus:outline-none"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium tracking-widest text-gray-500 uppercase">
                                            Class Name
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium tracking-widest text-gray-500 uppercase">
                                            Academic Level
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium tracking-widest text-gray-500 uppercase">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="cls in classes.data" :key="cls.id" class="transition-colors hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-semibold tracking-tight text-gray-800 uppercase">{{ cls.name }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span
                                                class="inline-flex items-center gap-x-1.5 rounded-full px-3 py-1 text-[10px] font-bold tracking-wider uppercase"
                                                :class="getLevelClasses(cls.level)"
                                            >
                                                {{ cls.level }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex items-center justify-end gap-x-2">
                                                <button
                                                    @click="openEditModal(cls)"
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
                                                    v-if="canDeleteClass"
                                                    @click="confirmDelete(cls)"
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
                                    <tr v-if="classes.data.length === 0">
                                        <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                            <p class="text-sm">No class hierarchies found.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div
                                v-if="classes.total > classes.per_page"
                                class="grid gap-3 border-t border-gray-200 px-6 py-4 md:flex md:items-center md:justify-between"
                            >
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Showing <span class="font-semibold text-gray-800">{{ classes.from }}</span> to
                                        <span class="font-semibold text-gray-800">{{ classes.to }}</span> of
                                        <span class="font-semibold text-gray-800">{{ classes.total }}</span>
                                    </p>
                                </div>

                                <div class="inline-flex gap-x-2">
                                    <Link
                                        v-for="link in classes.links"
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

        <!-- Create/Edit Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-80 flex items-center justify-center overflow-x-hidden overflow-y-auto p-4">
            <div @click="closeModal" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
            <div class="relative w-full max-w-lg rounded-xl border border-gray-200 bg-white shadow-lg">
                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50/50 px-4 py-3">
                    <h3 class="text-sm font-semibold tracking-tight text-gray-800 uppercase">
                        {{ isEditing ? 'Update Global Class' : 'Define New Global Class' }}
                    </h3>
                    <button
                        @click="closeModal"
                        type="button"
                        class="inline-flex size-8 items-center justify-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50"
                    >
                        <span class="sr-only">Close</span>
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="max-h-[calc(100vh-150px)] overflow-y-auto p-6">
                    <div class="space-y-6">
                        <div>
                            <label class="mb-2 block text-sm text-[10px] font-medium tracking-widest text-gray-800 uppercase"
                                >Class Nomenclature</label
                            >
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. Primary 1 or JSS 1"
                                class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                            />
                            <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm text-[10px] font-medium tracking-widest text-gray-800 uppercase"
                                >Mandatory Academic Level</label
                            >
                            <div class="grid grid-cols-3 gap-3">
                                <button
                                    v-for="level in levels"
                                    :key="level.value"
                                    type="button"
                                    @click="form.level = level.value"
                                    class="rounded-lg border-2 px-4 py-3 text-center text-xs font-bold uppercase shadow-sm transition-all"
                                    :class="
                                        form.level === level.value
                                            ? 'border-slate-900 bg-slate-900 text-white'
                                            : 'border-gray-100 bg-white text-gray-400 hover:border-gray-200'
                                    "
                                >
                                    {{ level.label }}
                                </button>
                            </div>
                            <p v-if="form.errors.level" class="mt-2 text-sm text-red-600">{{ form.errors.level }}</p>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-x-2 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-xs font-bold tracking-widest text-gray-500 uppercase transition-colors hover:text-gray-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-6 py-2.5 text-xs font-bold tracking-widest text-white uppercase shadow-sm transition-all active:scale-95 disabled:opacity-50"
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
