<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue';
import { ref } from 'vue';
import { index, store, update, destroy, importMethod } from '@/actions/App/Http/Controllers/Admin/StaffController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { PaginatedData } from '@/types/academics';

interface StaffUser {
    id: string;
    name: string;
    email: string;
    username: string;
    school_id: string | null;
}

const props = defineProps<{
    staff: PaginatedData<StaffUser>;
    filters: {
        search?: string;
    };
}>();

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingStaff = ref<StaffUser | null>(null);

const form = useForm('post', store().url, {
    name: '',
    email: '',
    username: '',
    school_id: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingStaff.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (user: StaffUser) => {
    isEditing.value = true;
    editingStaff.value = user;
    form.setData({
        name: user.name,
        email: user.email,
        username: user.username,
        school_id: user.school_id || '',
    });
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingStaff.value) {
        form.submit({
            method: 'put',
            url: update(editingStaff.value.id).url,
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
const staffToDelete = ref<StaffUser | null>(null);

const confirmDelete = (user: StaffUser) => {
    staffToDelete.value = user;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (staffToDelete.value) {
        router.delete(destroy(staffToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                staffToDelete.value = null;
            },
        });
    }
};

// Search
const search = ref(props.filters.search || '');
const handleSearch = () => {
    router.get(index().url, { search: search.value }, { preserveState: true });
};

// Import
const isImportModalOpen = ref(false);
const importForm = useForm('post', importMethod().url, {
    file: null as File | null,
});

const handleImport = () => {
    importForm.submit({
        onSuccess: () => {
            isImportModalOpen.value = false;
            importForm.reset();
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Staff Management" />

        <div class="space-y-10">
            <!-- Page Header -->
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Staff Personnel</h1>
                    <p class="mt-1 text-sm font-bold text-slate-400 uppercase tracking-widest">Academic & Admin Staff • {{ staff.total }} Records</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        @click="isImportModalOpen = true"
                        class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-3 text-xs font-black text-slate-600 uppercase shadow-sm transition-all hover:bg-slate-50 active:scale-95"
                    >
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Import Batch
                    </button>
                    <button
                        @click="openCreateModal"
                        class="flex items-center gap-2 rounded-lg bg-primary px-6 py-3 text-xs font-black text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Personnel
                    </button>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="rounded-xl border border-slate-100 bg-white shadow-sm overflow-hidden">
                <!-- Search & Filters Container -->
                <div class="border-b border-slate-50 bg-white p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                v-model="search"
                                @keyup.enter="handleSearch"
                                type="text"
                                placeholder="Search by name, email, or Staff ID..."
                                class="h-12 w-full rounded-xl border-none bg-slate-50 pl-12 text-sm font-bold text-slate-700 transition-all focus:bg-white focus:ring-2 focus:ring-primary/10"
                            />
                        </div>
                        <button @click="handleSearch" class="h-12 px-8 rounded-xl bg-slate-900 text-xs font-black text-white uppercase tracking-widest transition-all hover:bg-black active:scale-95">Search Directory</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Staff Member</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">System ID</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Login Username</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="user in staff.data" :key="user.id" class="group transition-all hover:bg-[#F8F9FB]">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-50 text-slate-400 font-black text-xs group-hover:bg-primary/5 group-hover:text-primary transition-colors">
                                            {{ user.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-slate-800 leading-none">{{ user.name }}</h4>
                                            <p class="mt-1 text-xs font-bold text-slate-400">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="inline-flex items-center rounded-lg bg-slate-50 px-3 py-1 text-[9px] font-black text-slate-600 uppercase border border-slate-100 shadow-sm">
                                        {{ user.school_id || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-6 text-xs font-bold text-slate-500 uppercase tracking-tighter">{{ user.username }}</td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button @click="openEditModal(user)" class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 active:scale-90 transition-all">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="confirmDelete(user)" class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 active:scale-90 transition-all">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between border-t border-slate-50 bg-white px-8 py-6">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">
                        Page {{ staff.current_page }} • Results {{ staff.from }}-{{ staff.to }} of {{ staff.total }}
                    </p>
                    <div class="flex gap-2">
                        <Link v-for="link in staff.links" :key="link.label" :href="link.url || '#'" 
                            class="flex h-10 min-w-10 items-center justify-center rounded-lg text-xs font-black transition-all"
                            :class="[link.active ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-slate-50 text-slate-600 hover:bg-slate-100', !link.url && 'opacity-30 cursor-not-allowed pointer-events-none']">
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-xl overflow-hidden rounded-xl bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900">{{ isEditing ? 'Edit Staff Details' : 'Add New Staff' }}</h3>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Full Name</label>
                                <input v-model="form.name" @change="form.validate('name')" type="text" required :class="{'border-red-500': form.invalid('name')}" class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary" />
                                <div v-if="form.errors.name" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Email Address</label>
                                <input v-model="form.email" @change="form.validate('email')" type="email" required placeholder="smith.j@school.com" :class="{'border-red-500': form.invalid('email')}" class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary" />
                                <div v-if="form.errors.email" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.email }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Username</label>
                                <input v-model="form.username" @change="form.validate('username')" type="text" required placeholder="jsmith" :class="{'border-red-500': form.invalid('username')}" class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary" />
                                <div v-if="form.errors.username" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.username }}</div>
                            </div>

                            <div class="col-span-2">
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Staff ID (External)</label>
                                <input v-model="form.school_id" @change="form.validate('school_id')" type="text" placeholder="e.g. CHRIS/STF/2024/001" :class="{'border-red-500': form.invalid('school_id')}" class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary" />
                                <div v-if="form.errors.school_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.school_id }}</div>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button type="button" @click="closeModal" class="flex-1 rounded-lg border border-slate-100 py-4 text-xs font-black tracking-widest text-slate-400 uppercase hover:bg-slate-50 transition-all">Cancel</button>
                            <button type="submit" :disabled="form.processing" class="flex-1 rounded-lg bg-primary py-4 text-xs font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50">{{ isEditing ? 'Update Personnel' : 'Create Account' }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import Modal -->
            <div v-if="isImportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="isImportModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-xl bg-white p-10 text-center shadow-2xl">
                    <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-xl bg-primary/5 text-primary">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 00-2-2H5a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-2xl font-black text-slate-900">Batch Staff Import</h3>
                    <p class="mb-8 px-4 text-sm leading-relaxed font-bold text-slate-500">
                        Upload an Excel/CSV file with columns: <br /><span class="text-primary">Name, Email, Username, Staff_ID</span>
                    </p>

                    <form @submit.prevent="handleImport" class="space-y-6">
                        <label class="group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-10 transition-all hover:border-primary hover:bg-white" :class="{'border-red-500': importForm.invalid('file')}">
                            <svg class="mb-4 h-10 w-10 text-slate-300 transition-colors group-hover:text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <span class="text-xs font-black tracking-widest text-slate-400 uppercase group-hover:text-primary">{{ importForm.file ? importForm.file.name : 'Select Data File' }}</span>
                            <input type="file" class="hidden" accept=".csv,.xlsx" @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null; importForm.validate('file')" />
                        </label>
                        <div v-if="importForm.errors.file" class="mt-1 text-xs font-bold text-red-500">{{ importForm.errors.file }}</div>

                        <div class="flex gap-3">
                            <button type="button" @click="isImportModalOpen = false" class="flex-1 rounded-lg border border-slate-100 py-4 text-xs font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50">Cancel</button>
                            <button type="submit" :disabled="!importForm.file || importForm.processing" class="flex-1 rounded-lg bg-primary py-4 text-xs font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50">Start Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Staff Record?"
            :message="`Are you sure you want to permanently delete the account for ${staffToDelete?.name}? This action cannot be undone.`"
            confirm-label="Delete Permanent"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
