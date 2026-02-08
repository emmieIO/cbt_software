<script setup lang="ts">
import { Head, useForm, router, Link } from '@inertiajs/vue3';
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
}

const props = defineProps<{
    candidates: PaginatedData<CandidateUser>;
    classes: SchoolClass[];
    filters: {
        search?: string;
    };
}>();

const isModalOpen = ref(false);
const form = useForm({
    name: '',
    email: '',
    username: '',
    school_id: '',
});

const openCreateModal = () => {
    form.reset();
    isModalOpen.value = true;
};

const submit = () => {
    form.post(store().url, {
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
const admitForm = useForm({
    school_class_id: '',
});

const openAdmitModal = (user: CandidateUser) => {
    candidateToAdmit.value = user;
    isAdmitModalOpen.value = true;
};

const handleAdmit = () => {
    if (candidateToAdmit.value) {
        admitForm.post(processAdmit(candidateToAdmit.value.id).url, {
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
        <Head title="Entrance Examination Candidates" />

        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">Entrance Candidates</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">Manage prospective students and admission testing.</p>
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
                        Import Candidates
                    </button>
                    <button
                        @click="openCreateModal"
                        class="flex h-12 items-center gap-3 rounded-2xl bg-primary px-6 text-sm font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Candidate
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
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text"
                        placeholder="Search by name, email, or Registration ID..."
                        class="h-12 w-full rounded-2xl border-none bg-slate-50 pl-12 text-sm font-bold transition-all focus:bg-white focus:ring-2 focus:ring-primary/20"
                    />
                </div>
                <button
                    @click="handleSearch"
                    class="h-12 rounded-2xl bg-slate-900 px-8 text-sm font-black tracking-widest text-white uppercase transition-all hover:bg-black active:scale-95"
                >
                    Search
                </button>
            </div>

            <div class="overflow-hidden rounded-[2.5rem] border border-slate-100 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Candidate Name</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest whitespace-nowrap text-slate-400 uppercase">
                                    Registration ID
                                </th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest whitespace-nowrap text-slate-400 uppercase">Username</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="user in candidates.data" :key="user.id" class="group transition-all hover:bg-slate-50/80">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-lemon-yellow/20 text-xs font-black text-primary uppercase"
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
                                        class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-600 uppercase"
                                    >
                                        {{ user.school_id || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-6 text-xs font-bold text-slate-500">{{ user.username }}</td>
                                <td class="px-8 py-6 text-right">
                                    <button
                                        @click="openAdmitModal(user)"
                                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-[10px] font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                                    >
                                        Admit Student
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-slate-50 px-8 py-6">
                    <p class="text-xs font-bold text-slate-400 italic">
                        Showing {{ candidates.from }} to {{ candidates.to }} of {{ candidates.total }} results.
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-for="link in candidates.links"
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

            <!-- Create Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-xl overflow-hidden rounded-[2.5rem] bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900">Enroll Entrance Candidate</h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Full Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="e.g. prospective student"
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.name" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Email</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Username</label>
                                <input
                                    v-model="form.username"
                                    type="text"
                                    required
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                            </div>

                            <div class="col-span-2">
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Registration/App Number</label
                                >
                                <input
                                    v-model="form.school_id"
                                    type="text"
                                    required
                                    placeholder="e.g. APP/2024/001"
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
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
                                Enroll Candidate
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Admit Modal -->
            <div v-if="isAdmitModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="isAdmitModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-[2.5rem] bg-white p-10 shadow-2xl">
                    <h3 class="mb-4 text-2xl font-black text-slate-900">Final Admission</h3>
                    <p class="mb-8 text-sm leading-relaxed font-bold text-slate-500 italic">
                        Select the target class for <span class="font-black text-primary">{{ candidateToAdmit?.name }}</span> to complete enrollment.
                    </p>

                    <form @submit.prevent="handleAdmit" class="space-y-6">
                        <div>
                            <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Admit to Class</label>
                            <select
                                v-model="admitForm.school_class_id"
                                required
                                class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            >
                                <option value="">Select Target Class</option>
                                <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                            </select>
                        </div>

                        <div class="flex gap-3 border-t border-slate-50 pt-4">
                            <button
                                type="button"
                                @click="isAdmitModalOpen = false"
                                class="flex-1 rounded-2xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="admitForm.processing"
                                class="flex-1 rounded-2xl bg-green-600 py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-green-600/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
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
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-[2.5rem] bg-white p-10 text-center shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900">Batch Candidate Import</h3>
                    <form @submit.prevent="handleImport" class="space-y-6">
                        <label
                            class="group relative flex cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-10 transition-all hover:border-primary hover:bg-white"
                        >
                            <span class="text-xs font-black tracking-widest text-slate-400 uppercase group-hover:text-primary">{{
                                importForm.file ? importForm.file.name : 'Select Candidate List'
                            }}</span>
                            <input type="file" class="hidden" @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null" />
                        </label>
                        <button
                            type="submit"
                            :disabled="!importForm.file"
                            class="w-full rounded-2xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20"
                        >
                            Process Import
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
