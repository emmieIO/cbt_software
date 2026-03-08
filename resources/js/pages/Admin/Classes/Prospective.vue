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

        <div class="space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                <Link href="/admin/dashboard" class="text-slate-500 transition-colors hover:text-slate-800">Dashboard</Link>
                <svg class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                <span class="text-slate-900">Entrance Batches</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link href="/admin/dashboard" class="group flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white transition-all hover:border-slate-900 hover:text-slate-900 active:scale-95">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                        </Link>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 italic">Entrance Batches</h1>
                    </div>
                    <p class="mt-2 text-sm font-bold tracking-widest text-slate-400 uppercase">Prospective Students • {{ classes.length }} Batches</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="flex items-center gap-2 rounded-lg bg-primary px-6 py-3 text-xs font-black text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                    New Entrance Batch
                </button>
            </div>

            <!-- Branch Filter -->
            <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                <div class="flex h-10 items-center gap-2 rounded-lg border border-slate-100 bg-slate-50 px-4">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <select 
                        v-model="branchFilter" 
                        @change="applyFilters"
                        class="cursor-pointer border-none bg-transparent text-[10px] font-black text-slate-600 uppercase focus:ring-0"
                    >
                        <option value="">All Branches</option>
                        <option v-for="(info, key) in branches" :key="key" :value="key">{{ info.name }}</option>
                    </select>
                </div>
                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase italic">Filter by school location</span>
            </div>

            <!-- Main Table Card -->
            <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Batch Details</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">School Branch</th>
                                <th class="px-6 py-5 text-center text-[10px] font-black tracking-widest text-slate-400 uppercase">Pass Mark</th>
                                <th class="px-6 py-5 text-center text-[10px] font-black tracking-widest text-slate-400 uppercase">Status</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="cls in classes" :key="cls.id" class="group transition-all hover:bg-[#F8F9FB]">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/5 text-xs font-black text-primary">
                                            {{ cls.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-black text-slate-800">{{ cls.name }}</h4>
                                            <p class="truncate text-xs font-bold text-slate-400" :title="cls.description || ''">
                                                {{ cls.description || 'No description provided.' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span v-if="branches[cls.branch]" class="inline-flex items-center rounded-lg border border-slate-100 bg-slate-50 px-3 py-1 text-[9px] font-black text-slate-500 uppercase">
                                        {{ branches[cls.branch].name }}
                                    </span>
                                    <span v-else class="text-[9px] font-black text-slate-300 uppercase italic">Unknown Branch</span>
                                </td>
                                <td class="px-6 py-6 text-center">
                                    <span class="text-xs font-black text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200">
                                        {{ cls.pass_percentage }}%
                                    </span>
                                </td>
                                <td class="px-6 py-6 text-center">
                                    <span 
                                        v-if="cls.is_active"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-green-50 px-3 py-1 text-[9px] font-black text-green-600 uppercase border border-green-100"
                                    >
                                        <div class="h-1 w-1 rounded-full bg-green-500"></div>
                                        Active
                                    </span>
                                    <span 
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1 text-[9px] font-black text-slate-400 uppercase border border-slate-200"
                                    >
                                        Inactive
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right whitespace-nowrap">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            @click="openEditModal(cls)"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition-all hover:border-primary hover:text-primary active:scale-90 shadow-sm"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="confirmDelete(cls)"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition-all hover:border-red-200 hover:text-red-600 active:scale-90 shadow-sm"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="classes.length === 0">
                                <td colspan="5" class="px-8 py-24 text-center">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-300 mb-4">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold tracking-widest text-slate-400 uppercase italic">No entrance batches found.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-xl bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900 italic">{{ isEditing ? 'Edit Batch' : 'New Entrance Batch' }}</h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Batch Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Enter Batch Name (e.g. 2026 Batch A)"
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary/10"
                            />
                            <div v-if="form.errors.name" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">School Branch Location</label>
                            <select
                                v-model="form.branch"
                                required
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary/10"
                            >
                                <option v-for="(info, key) in branches" :key="key" :value="key">{{ info.name }}</option>
                            </select>
                            <div v-if="form.errors.branch" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.branch }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Description</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Additional details about this batch..."
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary/10"
                            ></textarea>
                            <div v-if="form.errors.description" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.description }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Admission Pass Percentage (%)</label>
                            <input
                                v-model="form.pass_percentage"
                                type="number"
                                min="0"
                                max="100"
                                required
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary/10"
                            />
                            <div v-if="form.errors.pass_percentage" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.pass_percentage }}</div>
                        </div>

                        <div v-if="isEditing" class="flex items-center gap-3">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                id="is_active"
                                class="h-5 w-5 rounded border-slate-300 text-primary focus:ring-primary/10"
                            />
                            <label for="is_active" class="text-sm font-bold text-slate-700">Batch is Active</label>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button
                                type="button"
                                @click="closeModal"
                                class="flex-1 rounded-xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            >
                                {{ isEditing ? 'Update Batch' : 'Create Batch' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

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
