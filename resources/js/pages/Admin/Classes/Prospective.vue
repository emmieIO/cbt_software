<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue';
import { ref } from 'vue';
import ProspectiveClassController from '@/actions/App/Http/Controllers/Admin/ProspectiveClassController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface ProspectiveClass {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
}

const props = defineProps<{
    classes: ProspectiveClass[];
}>();

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingClass = ref<ProspectiveClass | null>(null);

const form = useForm('post', ProspectiveClassController.store().url, {
    name: '',
    description: '',
    is_active: true,
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
    form.setData({
        name: cls.name,
        description: cls.description || '',
        is_active: cls.is_active,
    });
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingClass.value) {
        form.submit({
            method: 'put',
            url: ProspectiveClassController.update(editingClass.value.id).url,
            onSuccess: () => closeModal(),
        });
    } else {
        form.submit({
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
            <!-- Page Header -->
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Entrance Batches</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400 uppercase tracking-widest">Prospective Students • {{ classes.length }} Batches</p>
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

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="cls in classes"
                    :key="cls.id"
                    class="group relative overflow-hidden rounded-xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-xl hover:shadow-primary/5"
                >
                    <div class="relative z-10 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1 space-y-4">
                            <div
                                class="inline-flex items-center whitespace-nowrap rounded-full bg-primary/5 border border-primary/10 px-3 py-1 text-[9px] font-black tracking-widest uppercase text-primary"
                            >
                                Prospective Batch
                            </div>
                            <h3 class="truncate text-2xl font-black text-slate-800 leading-tight group-hover:text-primary transition-colors" :title="cls.name">
                                {{ cls.name }}
                            </h3>
                            <p class="truncate text-xs font-bold text-slate-400" :title="cls.description || ''">
                                {{ cls.description || 'No description provided.' }}
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button
                                @click="openEditModal(cls)"
                                class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 active:scale-90 transition-all"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                @click="confirmDelete(cls)"
                                class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 active:scale-90 transition-all"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Decorative Background Icon -->
                    <div
                        class="pointer-events-none absolute -right-4 -bottom-4 opacity-[0.02] transition-transform duration-500 group-hover:scale-110 group-hover:rotate-12"
                    >
                        <svg class="h-32 w-32" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
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
                                @change="form.validate('name')"
                                type="text"
                                required
                                placeholder="e.g. 2026 Batch A - Morning"
                                :class="{'border-red-500': form.invalid('name')}"
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary/10"
                            />
                            <div v-if="form.errors.name" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Description</label>
                            <textarea
                                v-model="form.description"
                                @change="form.validate('description')"
                                rows="3"
                                placeholder="Additional details about this batch..."
                                :class="{'border-red-500': form.invalid('description')}"
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary/10"
                            ></textarea>
                            <div v-if="form.errors.description" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.description }}</div>
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
