<script setup lang="ts">
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { index, store, update, destroy, importMethod } from '@/actions/App/Http/Controllers/Admin/StudentController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { PaginatedData, SchoolClass } from '@/types/academics';

interface StudentUser {
    id: string;
    name: string;
    email: string;
    username: string;
    school_id: string | null;
    school_class_id: string | null;
    school_class?: SchoolClass;
}

const props = defineProps<{
    students: PaginatedData<StudentUser>;
    classes: SchoolClass[];
    filters: {
        search?: string;
        school_class_id?: string;
    };
}>();

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingStudent = ref<StudentUser | null>(null);

const form = useForm({
    name: '',
    email: '',
    username: '',
    school_id: '',
    school_class_id: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingStudent.value = null;
    form.reset();
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
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingStudent.value) {
        form.put(update(editingStudent.value.id).url, {
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
const filters = ref({
    search: props.filters.search || '',
    school_class_id: props.filters.school_class_id || '',
});

const applyFilters = () => {
    router.get(index().url, filters.value, { preserveState: true });
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

        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">Student Body</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">Manage enrolled students and their class assignments.</p>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="isImportModalOpen = true"
                        class="flex h-12 items-center gap-3 rounded-2xl border-2 border-slate-200 bg-white px-6 text-sm font-black tracking-wider text-slate-600 uppercase transition-all hover:border-primary hover:text-primary active:scale-95"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                        Import Excel
                    </button>
                    <button
                        @click="openCreateModal"
                        class="flex h-12 items-center gap-3 rounded-2xl bg-primary px-6 text-sm font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Student
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-4xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="filters.search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search by name, email, or Student ID..."
                        class="h-12 w-full rounded-2xl border-none bg-slate-50 pl-12 text-sm font-bold transition-all focus:bg-white focus:ring-2 focus:ring-primary/20"
                    />
                </div>
                <div class="w-64">
                    <select
                        v-model="filters.school_class_id"
                        @change="applyFilters"
                        class="h-12 w-full rounded-2xl border-none bg-slate-50 px-5 text-sm font-bold transition-all focus:bg-white focus:ring-2 focus:ring-primary/20"
                    >
                        <option value="">All Classes</option>
                        <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                    </select>
                </div>
                <button
                    @click="applyFilters"
                    class="h-12 rounded-2xl bg-slate-900 px-8 text-sm font-black tracking-widest text-white uppercase transition-all hover:bg-black active:scale-95"
                >
                    Apply
                </button>
            </div>

            <div class="overflow-hidden rounded-[2.5rem] border border-slate-100 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Student Name</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest whitespace-nowrap text-slate-400 uppercase">Class</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest whitespace-nowrap text-slate-400 uppercase">
                                    Student ID
                                </th>
                                <th class="px-8 py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="user in students.data" :key="user.id" class="group transition-all hover:bg-slate-50/80">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-xs font-black text-purple-600 uppercase"
                                        >
                                            {{ user.name.substring(0, 2) }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-slate-800">{{ user.name }}</h4>
                                            <p class="text-xs font-bold text-slate-400">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span
                                        v-if="user.school_class"
                                        class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1 text-[10px] font-black text-blue-600 uppercase"
                                    >
                                        {{ user.school_class.name }}
                                    </span>
                                    <span v-else class="text-[10px] font-bold tracking-widest text-slate-300 uppercase italic">Unassigned</span>
                                </td>
                                <td class="px-6 py-6 text-xs font-bold whitespace-nowrap text-slate-500">{{ user.school_id || 'N/A' }}</td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            @click="openEditModal(user)"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-primary hover:text-white"
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
                                            @click="confirmDelete(user)"
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
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-slate-50 px-8 py-6">
                    <p class="text-xs font-bold text-slate-400 italic">
                        Showing {{ students.from }} to {{ students.to }} of {{ students.total }} results.
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-for="link in students.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="flex h-9 items-center justify-center rounded-xl border px-4 text-xs font-black transition-all"
                            :class="[
                                link.active
                                    ? 'border-primary bg-primary text-white shadow-lg shadow-primary/20'
                                    : 'border-slate-100 bg-white text-slate-400 hover:bg-slate-50',
                                !link.url ? 'pointer-events-none opacity-50' : '',
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-xl overflow-hidden rounded-[2.5rem] bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900">{{ isEditing ? 'Edit Student Details' : 'Enroll New Student' }}</h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Student Full Name</label
                                >
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="e.g. Alice Wonderland"
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.name" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Email Address</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="alice.w@chrisland.com"
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.email" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.email }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Admission Number / Username</label
                                >
                                <input
                                    v-model="form.username"
                                    type="text"
                                    required
                                    placeholder="CHRIS/ADM/10234"
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.username" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.username }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Student ID (External)</label
                                >
                                <input
                                    v-model="form.school_id"
                                    type="text"
                                    placeholder="e.g. STUD-10234"
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.school_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.school_id }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Assign Class</label>
                                <select
                                    v-model="form.school_class_id"
                                    required
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="" disabled>Select Class</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                                <div v-if="form.errors.school_class_id" class="mt-2 text-xs font-bold text-red-500">
                                    {{ form.errors.school_class_id }}
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-3 border-t border-slate-50 pt-4">
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
                                {{ isEditing ? 'Update Student' : 'Enroll Student' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import Modal -->
            <div v-if="isImportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="isImportModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-[2.5rem] bg-white p-10 text-center shadow-2xl">
                    <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-3xl bg-primary/5 text-primary">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-2xl font-black text-slate-900">Batch Student Enrollment</h3>
                    <p class="mb-8 px-4 text-sm leading-relaxed font-bold text-slate-500">
                        Upload an Excel/CSV file with columns: <br /><span class="font-black text-primary"
                            >Name, Email, Username, Student_ID, Class_Name</span
                        >
                    </p>

                    <form @submit.prevent="handleImport" class="space-y-6">
                        <label
                            class="group relative flex cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-10 transition-all hover:border-primary hover:bg-white"
                        >
                            <svg
                                class="mb-4 h-10 w-10 text-slate-300 transition-colors group-hover:text-primary"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                />
                            </svg>
                            <span class="text-xs font-black tracking-widest text-slate-400 uppercase group-hover:text-primary">{{
                                importForm.file ? importForm.file.name : 'Select Enrollment File'
                            }}</span>
                            <input
                                type="file"
                                class="hidden"
                                accept=".csv,.xlsx"
                                @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null"
                            />
                        </label>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="isImportModalOpen = false"
                                class="flex-1 rounded-2xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="!importForm.file || importForm.processing"
                                class="flex-1 rounded-2xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            >
                                Start Enrollment
                            </button>
                        </div>
                    </form>
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
