<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue';
import { ref } from 'vue';
import { index, store, importMethod, admit as processAdmit } from '@/actions/App/Http/Controllers/Admin/EntranceController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { PaginatedData, SchoolClass } from '@/types/academics';

interface CandidateUser {
    id: string;
    name: string;
    email: string;
    username: string;
    school_id: string | null;
    status: string;
    prospective_class?: { name: string };
    school_class?: { name: string };
}

const props = defineProps<{
    candidates: PaginatedData<CandidateUser>;
    classes: SchoolClass[];
    batches: { id: string; name: string }[];
    filters: {
        search?: string;
    };
}>();

const isModalOpen = ref(false);
const form = useForm('post', store().url, {
    name: '',
    email: '',
    username: '',
    school_id: '',
    school_class_id: '',
    prospective_class_id: '',
});

const openCreateModal = () => {
    form.reset();
    isModalOpen.value = true;
};

const submit = () => {
    form.submit({
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

// Admission Logic
const isAdmitModalOpen = ref(false);
const candidateToAdmit = ref<CandidateUser | null>(null);

const admitForm = useForm('post', '', {
    school_class_id: '',
});

const openAdmitModal = (user: CandidateUser) => {
    candidateToAdmit.value = user;
    admitForm.setData({ school_class_id: '' });
    isAdmitModalOpen.value = true;
};

const handleAdmit = () => {
    if (candidateToAdmit.value) {
        admitForm.submit({
            url: processAdmit(candidateToAdmit.value.id).url,
            onSuccess: () => {
                isAdmitModalOpen.value = false;
                candidateToAdmit.value = null;
                admitForm.reset();
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
        <Head title="Admissions & Prospective Students" />

        <div class="space-y-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight">Admissions Portal</h2>
                    <p class="mt-1 text-sm font-bold text-slate-400 uppercase tracking-widest">Prospective Students • {{ candidates.total }} Candidates</p>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="isImportModalOpen = true"
                        class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-3 text-xs font-black text-slate-600 uppercase shadow-sm transition-all hover:bg-slate-50 active:scale-95"
                    >
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Import Candidates
                    </button>
                    <button
                        @click="openCreateModal"
                        class="flex items-center gap-2 rounded-lg bg-primary px-6 py-3 text-xs font-black text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        New Application
                    </button>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="rounded-xl border border-slate-100 bg-white shadow-sm overflow-hidden">
                <!-- Search & Filters Container (From UI Concept) -->
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
                                placeholder="Search by name, email, or application number..."
                                class="h-12 w-full rounded-xl border-none bg-slate-50 pl-12 text-sm font-bold text-slate-700 transition-all focus:bg-white focus:ring-2 focus:ring-primary/10"
                            />
                        </div>
                        <button @click="handleSearch" class="h-12 px-8 rounded-xl bg-slate-900 text-xs font-black text-white uppercase tracking-widest transition-all hover:bg-black active:scale-95">Search Portal</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Applicant Details</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Application ID</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Exam Batch</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Status</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="user in candidates.data" :key="user.id" class="group transition-all hover:bg-[#F8F9FB]">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-lemon-yellow/10 text-primary font-black text-xs group-hover:bg-lemon-yellow group-hover:text-primary transition-colors">
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
                                <td class="px-6 py-6">
                                    <span v-if="user.prospective_class" class="inline-flex items-center rounded-lg bg-primary/5 px-3 py-1 text-[9px] font-black text-primary uppercase border border-primary/10 shadow-sm">
                                        {{ user.prospective_class.name }}
                                    </span>
                                    <span v-else class="text-[9px] font-black text-slate-300 uppercase tracking-widest">No Batch</span>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1 text-[9px] font-black text-blue-600 uppercase">
                                        <span class="h-1 w-1 rounded-full bg-blue-500"></span>
                                        Pending
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button @click="openAdmitModal(user)" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-[10px] font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95">
                                        Approve Admission
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between border-t border-slate-50 bg-white px-8 py-6">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">
                        Page {{ candidates.current_page }} • Records {{ candidates.from }}-{{ candidates.to }} of {{ candidates.total }}
                    </p>
                    <div class="flex gap-2">
                        <Link v-for="link in candidates.links" :key="link.label" :href="link.url || '#'" 
                            class="flex h-10 min-w-10 items-center justify-center rounded-lg text-xs font-black transition-all"
                            :class="[link.active ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-slate-50 text-slate-600 hover:bg-slate-100', !link.url && 'opacity-30 cursor-not-allowed pointer-events-none']">
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-xl overflow-hidden rounded-xl bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900 italic">New Student Application</h3>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Applicant Full Name</label>
                                <input v-model="form.name" @change="form.validate('name')" type="text" required placeholder="e.g. Jane Doe" :class="{'border-red-500': form.invalid('name')}" class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary" />
                                <div v-if="form.errors.name" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Email Address</label>
                                <input v-model="form.email" @change="form.validate('email')" type="email" required placeholder="jane@example.com" :class="{'border-red-500': form.invalid('email')}" class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary" />
                                <div v-if="form.errors.email" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.email }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Application ID / Username</label>
                                <input v-model="form.username" @change="form.validate('username')" type="text" required placeholder="APP/2026/001" :class="{'border-red-500': form.invalid('username')}" class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary" />
                                <div v-if="form.errors.username" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.username }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">External Application No.</label>
                                <input v-model="form.school_id" @change="form.validate('school_id')" type="text" required placeholder="EX-10234" :class="{'border-red-500': form.invalid('school_id')}" class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary" />
                                <div v-if="form.errors.school_id" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.school_id }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Target Admission Class</label>
                                <select v-model="form.school_class_id" @change="form.validate('school_class_id')" required :class="{'border-red-500': form.invalid('school_class_id')}" class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary">
                                    <option value="">Select Class</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                                <div v-if="form.errors.school_class_id" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.school_class_id }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Assign to Entrance Batch</label>
                                <select v-model="form.prospective_class_id" @change="form.validate('prospective_class_id')" required :class="{'border-red-500': form.invalid('prospective_class_id')}" class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary">
                                    <option value="">Select Batch</option>
                                    <option v-for="batch in batches" :key="batch.id" :value="batch.id">{{ batch.name }}</option>
                                </select>
                                <div v-if="form.errors.prospective_class_id" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.prospective_class_id }}</div>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button type="button" @click="closeModal" class="flex-1 rounded-lg border border-slate-100 py-4 text-xs font-black tracking-widest text-slate-400 uppercase hover:bg-slate-50 transition-all">Cancel</button>
                            <button type="submit" :disabled="form.processing" class="flex-1 rounded-lg bg-primary py-4 text-xs font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95">Enroll Applicant</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Admit Modal -->
            <div v-if="isAdmitModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="isAdmitModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-xl bg-white p-10 shadow-2xl">
                    <h3 class="mb-4 text-2xl font-black text-slate-900">Final Admission</h3>
                    <p class="mb-8 text-sm leading-relaxed font-bold text-slate-500">
                        Select the target class for <span class="font-black text-primary">{{ candidateToAdmit?.name }}</span> to complete enrollment.
                    </p>

                    <form @submit.prevent="handleAdmit" class="space-y-6">
                        <div>
                            <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Admit to Class</label>
                            <select
                                v-model="admitForm.school_class_id"
                                @change="admitForm.validate('school_class_id')"
                                required
                                :class="{'border-red-500': admitForm.invalid('school_class_id')}"
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            >
                                <option value="">Select Target Class</option>
                                <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                            </select>
                            <div v-if="admitForm.errors.school_class_id" class="mt-1 text-xs font-bold text-red-500">{{ admitForm.errors.school_class_id }}</div>
                        </div>

                        <div class="flex gap-3 border-t border-slate-50 pt-4">
                            <button
                                type="button"
                                @click="isAdmitModalOpen = false"
                                class="flex-1 rounded-xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="admitForm.processing"
                                class="flex-1 rounded-xl bg-green-600 py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-green-600/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            >
                                Confirm Admission
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import Modal -->
            <div v-if="isImportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="isImportModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-xl bg-white p-10 text-center shadow-2xl">
                    <h3 class="mb-4 text-2xl font-black text-slate-900">Batch Candidate Import</h3>
                    <p class="mb-8 px-4 text-sm leading-relaxed font-bold text-slate-500">
                        Upload an Excel/CSV file with columns: <br />
                        <span class="font-black text-primary">Name, Email, Username, Application_ID, Target_Class, Exam_Batch</span>
                    </p>
                    <form @submit.prevent="handleImport" class="space-y-6">
                        <label
                            class="group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-10 transition-all hover:border-primary hover:bg-white"
                            :class="{'border-red-500': importForm.invalid('file')}"
                        >
                            <span class="text-xs font-black tracking-widest text-slate-400 uppercase group-hover:text-primary">{{
                                importForm.file ? importForm.file.name : 'Select Candidate List'
                            }}</span>
                            <input type="file" class="hidden" @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null; importForm.validate('file')" />
                        </label>
                        <div v-if="importForm.errors.file" class="mt-1 text-xs font-bold text-red-500">{{ importForm.errors.file }}</div>
                        <button
                            type="submit"
                            :disabled="!importForm.file || importForm.processing"
                            class="w-full rounded-xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 disabled:opacity-50"
                        >
                            Process Import
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
