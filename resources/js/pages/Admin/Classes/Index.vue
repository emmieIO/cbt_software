<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store as storeAction, update as updateAction, destroy as destroyAction } from '@/actions/App/Http/Controllers/Admin/SchoolClassController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { SchoolClass } from '@/types/academics';

defineProps<{
    classes: SchoolClass[];
    levels: { value: string; label: string }[];
}>();

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingClass = ref<SchoolClass | null>(null);

const form = useForm({
    name: '',
    level: 'primary' as any,
});

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
    // Extract raw string value from Enum object if necessary
    form.level = typeof cls.level === 'object' ? (cls.level as any).value : cls.level;
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingClass.value) {
        const action = updateAction(editingClass.value.id);
        form.put(action.url, {
            onSuccess: () => closeModal(),
        });
    } else {
        const action = storeAction();
        form.post(action.url, {
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
        const action = destroyAction(classToDelete.value.id);
        router.delete(action.url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                classToDelete.value = null;
            },
        });
    }
};

// Helper to get raw level string for template comparisons
const getRawLevel = (cls: SchoolClass): string => {
    return typeof cls.level === 'object' ? (cls.level as any).value : cls.level;
};
</script>

<template>
    <AdminLayout>
        <Head title="Class Management" />

        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">School Classes</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">Manage grade levels and academic sections.</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="flex h-12 items-center gap-3 rounded-2xl bg-primary px-6 text-sm font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Class
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="cls in classes"
                    :key="cls.id"
                    class="group relative overflow-hidden rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-xl hover:shadow-primary/5"
                >
                    <div class="relative z-10 flex items-start justify-between">
                        <div class="space-y-4">
                            <div
                                class="inline-flex items-center rounded-full px-3 py-1 text-[10px] font-black tracking-widest uppercase"
                                :class="getRawLevel(cls) === 'primary' ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600'"
                            >
                                {{ typeof cls.level === 'object' ? (cls.level as any).label : cls.level }}
                            </div>
                            <h3 class="text-2xl font-black text-slate-800">{{ cls.name }}</h3>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="openEditModal(cls)"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-primary hover:text-white"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </button>
                            <button
                                @click="confirmDelete(cls)"
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-red-500 hover:text-white"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Decorative Background Icon -->
                    <div
                        class="pointer-events-none absolute -right-4 -bottom-4 opacity-[0.03] transition-transform duration-500 group-hover:scale-110"
                    >
                        <svg class="h-32 w-32" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z" />
                            <path d="M3.88 12.88L12 17l8.12-4.12V17.12L12 21.24l-8.12-4.12V12.88z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-[2.5rem] bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900">{{ isEditing ? 'Edit Class' : 'Create New Class' }}</h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Class Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. JSS 1"
                                class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            />
                            <div v-if="form.errors.name" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Academic Level</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    v-for="level in levels"
                                    :key="level.value"
                                    type="button"
                                    @click="form.level = level.value"
                                    :class="[
                                        'rounded-xl py-3.5 text-[10px] font-black tracking-widest uppercase transition-all',
                                        form.level === level.value
                                            ? 'bg-primary text-white shadow-lg shadow-primary/20'
                                            : 'bg-slate-50 text-slate-400 hover:bg-slate-100',
                                    ]"
                                >
                                    {{ level.label }}
                                </button>
                            </div>
                            <div v-if="form.errors.level" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.level }}</div>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button
                                type="button"
                                @click="closeModal"
                                class="flex-1 rounded-2xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-2xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            >
                                {{ isEditing ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Class?"
            :message="`Are you sure you want to delete ${classToDelete?.name}? This action cannot be undone.`"
            confirm-label="Delete Permanent"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
