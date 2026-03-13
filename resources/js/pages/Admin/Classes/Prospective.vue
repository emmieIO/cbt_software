<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ProspectiveClassController from '@/actions/App/Http/Controllers/Admin/ProspectiveClassController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface ProspectiveClass {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    branch: string;
    pass_percentage: number;
    is_active: boolean;
}

const props = defineProps<{
    classes: ProspectiveClass[];
    filters: {
        branch?: string;
    };
}>();

const page = usePage();
const branches = computed(() => (page.props as any).branches || {});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingClass = ref<ProspectiveClass | null>(null);

const form = useForm({
    name: '',
    description: '',
    pass_percentage: 50,
    is_active: true,
    branch: 'primary_vgc',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingClass.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (cls: ProspectiveClass) => {
    isEditing.value = true;
    editingClass.value = cls;

    form.name = cls.name;
    form.description = cls.description || '';
    form.pass_percentage = cls.pass_percentage;
    form.is_active = cls.is_active;
    form.branch = cls.branch || 'primary_vgc';

    isModalOpen.value = true;
};

// Filters
const branchFilter = ref(props.filters.branch || '');
const applyFilters = () => {
    router.get(router.page.url, { branch: branchFilter.value }, { preserveState: true });
};

const submit = () => {
    if (isEditing.value && editingClass.value) {
        form.put(ProspectiveClassController.update(editingClass.value.id).url, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(ProspectiveClassController.store().url, {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const isDeleteModalOpen = ref(false);
const classToDelete = ref<ProspectiveClass | null>(null);

const confirmDelete = (cls: ProspectiveClass) => {
    classToDelete.value = cls;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (classToDelete.value) {
        router.delete(ProspectiveClassController.destroy(classToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                classToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Entrance Batches" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Entrance Batches</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link href="/admin/dashboard" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Entrance Batches</h1>
                        <p class="text-sm text-gray-500 mt-1">Prospective Students • {{ classes.length }} Batches</p>
                    </div>
                </div>
                <button
                    @click="openCreateModal"
                    class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Entrance Batch
                </button>
            </div>

            <!-- Branch Filter -->
            <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="sm:w-64">
                        <label class="sr-only">Filter by branch</label>
                        <select 
                            v-model="branchFilter" 
                            @change="applyFilters"
                            class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <option value="">All Branches</option>
                            <option v-for="(info, key) in branches" :key="key" :value="key">{{ info.name }}</option>
                        </select>
                    </div>
                    <span class="text-xs font-medium text-gray-500 uppercase">Filter by school location</span>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Batch Details</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">School Branch</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pass Mark</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="cls in classes" :key="cls.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-x-3">
                                                <div class="size-8 flex items-center justify-center rounded-lg bg-primary/10 text-xs font-semibold text-primary">
                                                    {{ cls.name.substring(0, 2).toUpperCase() }}
                                                </div>
                                                <div>
                                                    <span class="block text-sm font-semibold text-gray-800">{{ cls.name }}</span>
                                                    <span class="block text-xs text-gray-500 mt-0.5 line-clamp-1" :title="cls.description || ''">
                                                        {{ cls.description || 'No description provided.' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span v-if="branches[cls.branch]" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ branches[cls.branch].name }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400">Unknown Branch</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center py-1 px-2 rounded-md text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                                {{ cls.pass_percentage }}%
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span v-if="cls.is_active" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-md text-xs font-medium bg-teal-100 text-teal-800">
                                                <span class="size-1.5 inline-block rounded-full bg-teal-500"></span>
                                                Active
                                            </span>
                                            <span v-else class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                                Inactive
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex justify-end items-center gap-x-2">
                                                <button @click="openEditModal(cls)" class="text-gray-500 hover:text-primary transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button @click="confirmDelete(cls)" class="text-gray-500 hover:text-red-500 transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="classes.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="size-8 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                                <p class="text-sm">No entrance batches found</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-[80] overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
                <div class="relative w-full max-w-lg bg-white rounded-xl shadow-lg border border-gray-200">
                    <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800">{{ isEditing ? 'Edit Batch' : 'New Entrance Batch' }}</h3>
                        <button @click="closeModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none">
                            <span class="sr-only">Close</span>
                            <svg class="flex-shrink-0 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-4 overflow-y-auto max-h-[calc(100vh-150px)]">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800">Batch Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Enter Batch Name (e.g. 2026 Batch A)"
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                />
                                <div v-if="form.errors.name" class="text-sm text-red-600 mt-2">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800">School Branch Location</label>
                                <select
                                    v-model="form.branch"
                                    required
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                >
                                    <option v-for="(info, key) in branches" :key="key" :value="key">{{ info.name }}</option>
                                </select>
                                <div v-if="form.errors.branch" class="text-sm text-red-600 mt-2">{{ form.errors.branch }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Additional details about this batch..."
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                ></textarea>
                                <div v-if="form.errors.description" class="text-sm text-red-600 mt-2">{{ form.errors.description }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800">Admission Pass Percentage (%)</label>
                                <input
                                    v-model="form.pass_percentage"
                                    type="number"
                                    min="0"
                                    max="100"
                                    required
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                />
                                <div v-if="form.errors.pass_percentage" class="text-sm text-red-600 mt-2">{{ form.errors.pass_percentage }}</div>
                            </div>

                            <div v-if="isEditing" class="flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    id="is_active"
                                    class="shrink-0 mt-0.5 border-gray-200 rounded text-primary focus:ring-primary"
                                />
                                <label for="is_active" class="text-sm text-gray-500 ms-3">Batch is Active</label>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-x-2">
                            <button
                                type="button"
                                @click="closeModal"
                                class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                            >
                                {{ isEditing ? 'Update Batch' : 'Create Batch' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Remove Batch?"
            :message="`Are you sure you want to delete ${classToDelete?.name}? This action cannot be undone and will only work if no candidates are assigned.`"
            confirm-label="Delete Permanent"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
