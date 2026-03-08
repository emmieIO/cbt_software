<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { store as storeAction, update as updateAction, destroy as destroyAction } from '@/actions/App/Http/Controllers/Admin/SchoolClassController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { SchoolClass } from '@/types/academics';

const props = defineProps<{
    classes: SchoolClass[];
    levels: { value: string; label: string }[];
    filters: {
        branch?: string;
    };
}>();

const page = usePage();
const branches = computed(() => (page.props as any).branches || {});

// Branch-aware logic
const selectedBranchKey = ref(props.filters.branch || null);

const branchStats = computed(() => {
    return Object.entries(branches.value).map(([key, info]: [string, any]) => ({
        key,
        name: info.name,
        address: info.address,
        classCount: props.classes.filter(c => c.branch === key).length
    }));
});

const filteredClasses = computed(() => {
    if (!selectedBranchKey.value) return [];
    return props.classes.filter(c => c.branch === selectedBranchKey.value);
});

const selectBranch = (key: string) => {
    selectedBranchKey.value = key;
    // We don't necessarily need a router reload here since we have all classes in props, 
    // but we update the URL for consistency if needed.
    router.get(router.page.url, { branch: key }, { preserveState: true, preserveScroll: true });
};

const clearBranchSelection = () => {
    selectedBranchKey.value = null;
    router.get(router.page.url, {}, { preserveState: true });
};

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingClass = ref<SchoolClass | null>(null);

const form = useForm({
    name: '',
    level: 'primary' as any,
    branch: 'primary_vgc',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingClass.value = null;
    form.reset();
    if (selectedBranchKey.value) {
        form.branch = selectedBranchKey.value;
    }
    isModalOpen.value = true;
};

const openEditModal = (cls: SchoolClass) => {
    isEditing.value = true;
    editingClass.value = cls;
    const levelValue = typeof cls.level === 'object' ? (cls.level as any).value : cls.level;

    form.name = cls.name;
    form.level = levelValue;
    form.branch = cls.branch || 'primary_vgc';

    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingClass.value) {
        form.put(updateAction(editingClass.value.id).url, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(storeAction().url, {
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

const getRawLevel = (cls: SchoolClass): string => {
    return typeof cls.level === 'object' ? (cls.level as any).value : cls.level;
};
</script>

<template>
    <AdminLayout>
        <Head title="Class Management" />

        <div class="space-y-8 md:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                <Link href="/admin/dashboard" class="text-slate-500 transition-colors hover:text-slate-800">Dashboard</Link>
                <svg class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                <span class="text-slate-900">Academic Structure</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <!-- Dynamic Back Button -->
                        <button 
                            v-if="selectedBranchKey"
                            @click="clearBranchSelection"
                            class="group flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white transition-all hover:border-slate-900 hover:text-slate-900 active:scale-95"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        <Link 
                            v-else
                            href="/admin/dashboard" 
                            class="group flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white transition-all hover:border-slate-900 hover:text-slate-900 active:scale-95"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                        </Link>
                        
                        <h1 class="text-2xl md:text-3xl font-black tracking-tight text-slate-900 italic">
                            {{ selectedBranchKey ? branches[selectedBranchKey].name : 'School Network' }}
                        </h1>
                    </div>
                    <p class="mt-2 text-[10px] md:text-sm font-bold tracking-widest text-slate-400 uppercase px-1">
                        {{ selectedBranchKey ? 'Managing academic levels and sections' : 'Overview of all Chrisland branches' }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="selectedBranchKey"
                        @click="clearBranchSelection"
                        class="flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-6 py-3 text-xs font-black text-slate-600 uppercase transition-all hover:bg-slate-50 active:scale-95 sm:w-auto"
                    >
                        Return to Network
                    </button>
                    <button
                        @click="openCreateModal"
                        class="flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3 text-xs font-black text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 sm:w-auto"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Class
                    </button>
                </div>
            </div>

            <!-- View 1: Branch Network Table -->
            <div v-if="!selectedBranchKey" class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">School Branch</th>
                            <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase text-center">Structure</th>
                            <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="branch in branchStats" :key="branch.key" class="group hover:bg-slate-50/30 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-slate-800">{{ branch.name }}</span>
                                    <span class="mt-1 text-[10px] font-bold text-slate-400 uppercase tracking-tight line-clamp-1">{{ branch.address }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-[10px] font-black text-slate-600 uppercase tracking-widest">
                                    {{ branch.classCount }} Classes
                                </span>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button 
                                    @click="selectBranch(branch.key)"
                                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-[10px] font-black tracking-widest text-white uppercase transition-all hover:scale-105 active:scale-95 shadow-lg shadow-slate-900/10"
                                >
                                    Manage Structure
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- View 2: Compact Class Table -->
            <div v-else class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase w-32">Level</th>
                            <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Class Designation</th>
                            <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="cls in filteredClasses" :key="cls.id" class="group hover:bg-slate-50/30 transition-colors">
                            <td class="px-8 py-5">
                                <span 
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[9px] font-black tracking-widest uppercase border"
                                    :class="{
                                        'bg-blue-50 text-blue-600 border-blue-100': getRawLevel(cls) === 'primary',
                                        'bg-purple-50 text-purple-600 border-purple-100': getRawLevel(cls) === 'secondary',
                                        'bg-amber-50 text-amber-600 border-amber-100': getRawLevel(cls) === 'nursery',
                                    }"
                                >
                                    {{ typeof cls.level === 'object' ? (cls.level as any).label : cls.level }}
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <span class="text-sm font-black text-slate-800 tracking-tight">{{ cls.name }}</span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-2">
                                    <button
                                        @click="openEditModal(cls)"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-400 transition-all hover:border-primary hover:text-primary active:scale-90 shadow-sm"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmDelete(cls)"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-400 transition-all hover:border-red-200 hover:text-red-600 active:scale-90 shadow-sm"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredClasses.length === 0">
                            <td colspan="3" class="px-8 py-12 text-center text-slate-400 italic text-xs font-bold tracking-widest uppercase opacity-50">
                                No classes configured for this branch
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-xl bg-white p-6 sm:p-10 shadow-2xl">
                    <h3 class="mb-6 md:mb-8 text-xl md:text-2xl font-black text-slate-900 italic underline decoration-primary decoration-4 underline-offset-8">{{ isEditing ? 'Edit Class' : 'Create New Class' }}</h3>

                    <form @submit.prevent="submit" class="space-y-5 md:space-y-6">
                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Class Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Enter Class Name (e.g. JSS 1)"
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-3.5 md:py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            />
                            <div v-if="form.errors.name" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">School Branch Location</label>
                            <select
                                v-model="form.branch"
                                required
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-3.5 md:py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            >
                                <option v-for="(info, key) in branches" :key="key" :value="key">{{ info.name }}</option>
                            </select>
                            <div v-if="form.errors.branch" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.branch }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Academic Level</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 md:gap-3">
                                <button
                                    v-for="level in levels"
                                    :key="level.value"
                                    type="button"
                                    @click="form.level = level.value"
                                    :class="[
                                        'rounded-xl py-3 md:py-3.5 text-[9px] md:text-[10px] font-black tracking-widest uppercase transition-all',
                                        form.level === level.value
                                            ? 'bg-primary text-white shadow-lg shadow-primary/20 border-primary'
                                            : 'bg-slate-50 text-slate-400 hover:bg-slate-100 border-transparent',
                                    ]"
                                    class="border-2"
                                >
                                    {{ level.label }}
                                </button>
                            </div>
                            <div v-if="form.errors.level" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.level }}</div>
                        </div>

                        <div class="flex gap-3 pt-2 md:pt-4">
                            <button
                                type="button"
                                @click="closeModal"
                                class="flex-1 rounded-xl border border-slate-100 py-3.5 md:py-4 text-xs md:text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-xl bg-primary py-3.5 md:py-4 text-xs md:text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
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
