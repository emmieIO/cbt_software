<script setup lang="ts">
import { Head, router, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { index, update, destroy, importMethod } from '@/actions/App/Http/Controllers/Admin/StudentController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { PaginatedData } from '@/types/academics';

interface StudentUser {
    id: string;
    name: string;
    email: string;
    username: string;
    school_id: string | null;
    school_class_id: string | null;
    school_class?: { name: string };
    roles?: Array<{ id: number; name: string }>;
}

const props = defineProps<{
    students: PaginatedData<StudentUser>;
    classes: { id: string; name: string }[];
    roles: Array<{ id: number; name: string }>;
    filters: {
        search?: string;
        school_class_id?: string;
        school_id?: string;
    };
}>();

const page = usePage();
const branches = computed(() => {
    const rawBranches = (page.props as any).branches || {};
    return Object.entries(rawBranches).map(([id, info]: [string, any]) => ({
        id,
        name: info.name
    }));
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingStudent = ref<StudentUser | null>(null);

const form = useForm({
    name: '',
    email: '',
    username: '',
    school_id: '',
    school_class_id: '',
    role: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingStudent.value = null;
    form.reset();
    if (props.roles.length > 0) {
        form.role = props.roles[0].name;
    }
    isModalOpen.value = true;
};

const openEditModal = (user: StudentUser) => {
    isEditing.value = true;
    editingStudent.value = user;

    form.name = user.name;
    form.email = user.email;
    form.username = user.username;
    form.school_id = user.school_id || '';
    form.school_class_id = user.school_class_id || '';
    form.role = user.roles?.[0]?.name || '';

    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingStudent.value) {
        form.put(update(editingStudent.value.id).url, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(index().url, {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const isDeleteModalOpen = ref(false);
const studentToDelete = ref<StudentUser | null>(null);

const confirmDelete = (user: StudentUser) => {
    studentToDelete.value = user;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (studentToDelete.value) {
        router.delete(destroy(studentToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                studentToDelete.value = null;
            },
        });
    }
};

// Filters
const search = ref(props.filters.search || '');
const schoolFilter = ref(props.filters.school_id || '');
const classFilter = ref(props.filters.school_class_id || '');

const applyFilters = () => {
    router.get(index().url, {
        search: search.value,
        school_id: schoolFilter.value,
        school_class_id: classFilter.value
    }, { preserveState: true });
};

// Import
const isImportModalOpen = ref(false);
const importForm = useForm({
    file: null as File | null,
});

const handleImport = () => {
    importForm.post(importMethod().url, {
        onSuccess: () => {
            isImportModalOpen.value = false;
            importForm.reset();
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Student Management" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Students</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Student Directory</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ students.total }} active student enrollments across all branches
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        @click="isImportModalOpen = true"
                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                    >
                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Import Batch
                    </button>
                    <button
                        @click="openCreateModal"
                        class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Student
                    </button>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <!-- Search & Filters Container -->
                            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200">
                                <div class="relative flex-1 max-w-md">
                                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3">
                                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input
                                        v-model="search"
                                        @keyup.enter="applyFilters"
                                        type="text"
                                        placeholder="Search by name, email, or Admission ID..."
                                        class="py-2 px-3 ps-9 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                    />
                                </div>

                                <div class="inline-flex flex-wrap items-center gap-2">
                                    <div class="w-40">
                                        <CustomSelect
                                            v-model="schoolFilter"
                                            :options="branches"
                                            placeholder="All Branches"
                                            size="sm"
                                            @change="applyFilters"
                                        />
                                    </div>
                                    <div class="w-40">
                                        <CustomSelect
                                            v-model="classFilter"
                                            :options="classes"
                                            placeholder="All Classes"
                                            size="sm"
                                            @change="applyFilters"
                                        />
                                    </div>

                                    <button
                                        @click="applyFilters"
                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-gray-800 text-white hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                                    >
                                        Filter
                                    </button>
                                </div>
                            </div>

                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Student</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Branch</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Class</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Admission ID</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="user in students.data" :key="user.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-x-3">
                                                <div class="flex-shrink-0 size-10 flex items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-500 uppercase">
                                                    {{ user.name.substring(0, 2) }}
                                                </div>
                                                <div>
                                                    <span class="block text-sm font-semibold text-gray-800">{{ user.name }}</span>
                                                    <span class="block text-xs text-gray-500">{{ user.email }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span v-if="user.school_id" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-md text-xs font-medium bg-primary/10 text-primary">
                                                {{ (page.props as any).branches[user.school_id]?.name || 'Unknown' }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400">Global</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span v-if="user.school_class" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ user.school_class.name }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400">Unassigned</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ user.username || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex justify-end items-center gap-x-2">
                                                <button @click="openEditModal(user)" class="text-gray-500 hover:text-primary transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button @click="confirmDelete(user)" class="text-gray-500 hover:text-red-500 transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="students.data.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="size-8 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                                <p class="text-sm">No student records found</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Showing <span class="font-semibold text-gray-800">{{ students.from }}</span> to <span class="font-semibold text-gray-800">{{ students.to }}</span> of <span class="font-semibold text-gray-800">{{ students.total }}</span>
                                    </p>
                                </div>

                                <div class="inline-flex gap-x-2">
                                    <Link
                                        v-for="link in students.links"
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

            <!-- Create/Edit Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-[80] overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
                <div class="relative w-full max-w-lg bg-white rounded-xl shadow-lg border border-gray-200">
                    <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800">{{ isEditing ? 'Edit Student Details' : 'Enroll New Student' }}</h3>
                        <button @click="closeModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none">
                            <span class="sr-only">Close</span>
                            <svg class="flex-shrink-0 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-4 overflow-y-auto max-h-[calc(100vh-150px)]">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800">Full Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Enter Full Name"
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-600 mt-2">{{ form.errors.name }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2 text-gray-800">Email Address</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        required
                                        placeholder="student@example.com"
                                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                    />
                                    <p v-if="form.errors.email" class="text-sm text-red-600 mt-2">{{ form.errors.email }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2 text-gray-800">Admission Number</label>
                                    <input
                                        v-model="form.username"
                                        type="text"
                                        required
                                        placeholder="CHS/2026/001"
                                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                    />
                                    <p v-if="form.errors.username" class="text-sm text-red-600 mt-2">{{ form.errors.username }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <CustomSelect
                                        v-model="form.school_id"
                                        label="Campus Branch"
                                        :options="branches"
                                        placeholder="Choose Branch"
                                        size="md"
                                        :error="form.errors.school_id"
                                    />
                                </div>

                                <div>
                                    <CustomSelect
                                        v-model="form.school_class_id"
                                        label="Academic Class"
                                        :options="classes"
                                        placeholder="Choose Class"
                                        size="md"
                                        :error="form.errors.school_class_id"
                                    />
                                </div>
                            </div>

                            <div class="space-y-3">
                                <label class="block text-sm font-medium text-gray-800 uppercase tracking-widest text-[10px]">Candidate Access Level</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        v-for="role in roles" :key="role.id"
                                        type="button"
                                        @click="form.role = role.name"
                                        :class="[
                                            'flex h-12 items-center justify-center rounded-lg border shadow-sm px-4 py-2 text-[10px] font-semibold uppercase transition-all',
                                            form.role === role.name 
                                                ? 'bg-slate-900 border-slate-900 text-white' 
                                                : 'bg-white border-gray-200 text-gray-800 hover:bg-gray-50'
                                        ]"
                                    >
                                        {{ role.name.replace('_', ' ') }}
                                    </button>
                                </div>
                                <div v-if="form.errors.role" class="text-xs text-red-600 mt-1">{{ form.errors.role }}</div>
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
                                {{ isEditing ? 'Update Student' : 'Enroll Student' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import Modal -->
            <div v-if="isImportModalOpen" class="fixed inset-0 z-[80] overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
                <div @click="isImportModalOpen = false" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
                <div class="relative w-full max-w-md bg-white rounded-xl shadow-lg border border-gray-200">
                    <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800">Batch Enrollment</h3>
                        <button @click="isImportModalOpen = false" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none">
                            <span class="sr-only">Close</span>
                            <svg class="flex-shrink-0 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6">
                        <div class="text-center mb-6">
                            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500">
                                Upload a CSV/Excel file. Required columns: <span class="font-semibold text-primary">Name, Email, Username, Student_ID, Class_Name</span>
                            </p>
                        </div>

                        <form @submit.prevent="handleImport" class="space-y-4">
                            <label class="group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 p-6 transition-all hover:border-primary hover:bg-white">
                                <svg class="mb-3 size-8 text-gray-400 group-hover:text-primary transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <span class="text-xs font-medium text-gray-500 group-hover:text-primary transition-colors">{{ importForm.file ? importForm.file.name : 'Select data file' }}</span>
                                <input type="file" class="hidden" accept=".csv,.xlsx" @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null" />
                            </label>
                            <p v-if="importForm.errors.file" class="text-sm text-red-600">{{ importForm.errors.file }}</p>

                            <div class="flex gap-x-2">
                                <button type="button" @click="isImportModalOpen = false" class="flex-1 py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="!importForm.file || importForm.processing" class="flex-1 py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover disabled:opacity-50 disabled:pointer-events-none focus:outline-none">
                                    Start Enrollment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Remove Student Record?"
            :message="`Are you sure you want to permanently delete the account for ${studentToDelete?.name}? This action cannot be undone.`"
            confirm-label="Delete Permanent"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
