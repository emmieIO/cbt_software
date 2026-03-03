<script setup lang="ts">
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { index, store, update, importMethod, admit as processAdmit } from '@/actions/App/Http/Controllers/Admin/EntranceController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { PaginatedData, SchoolClass } from '@/types/academics';

interface CandidateUser {
    id: string;
    name: string;
    email: string;
    username: string;
    school_id: string | null;
    status: string;
    prospective_class_id: string | null;
    school_class_id: string | null;
    prospective_class?: { 
        id: string;
        name: string;
        pass_percentage: number;
    };
    school_class?: { name: string };
    latest_attempt?: {
        score: number;
        exam: {
            questions_count?: number;
        };
    };
}

const props = defineProps<{
    candidates: PaginatedData<CandidateUser>;
    classes: SchoolClass[];
    batches: { id: string; name: string; pass_percentage: number }[];
    filters: {
        search?: string;
    };
}>();

// Helper to determine if a candidate is qualified for admission
const getQualificationStatus = (user: CandidateUser) => {
    if (!user.latest_attempt || !user.prospective_class) return { qualified: false, percentage: 0 };
    
    const total = user.latest_attempt.exam.questions_count || 0;
    if (total === 0) return { qualified: false, percentage: 0 };
    
    const percentage = Math.round((user.latest_attempt.score / total) * 100);
    const passMark = user.prospective_class.pass_percentage || 50;
    
    return {
        qualified: percentage >= passMark,
        percentage,
        passMark
    };
};

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingCandidate = ref<CandidateUser | null>(null);

const form = useForm({
    name: '',
    email: '',
    username: '',
    school_class_id: '',
    prospective_class_id: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingCandidate.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (user: CandidateUser) => {
    isEditing.value = true;
    editingCandidate.value = user;
    
    form.name = user.name;
    form.email = user.email;
    form.username = user.username;
    form.school_class_id = user.school_class_id || '';
    form.prospective_class_id = user.prospective_class_id || '';
    
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingCandidate.value) {
        form.put(update(editingCandidate.value.id).url, {
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

// Admission Logic
const isAdmitModalOpen = ref(false);
const candidateToAdmit = ref<CandidateUser | null>(null);

const admitForm = useForm({
    school_class_id: '',
});

const openAdmitModal = (user: CandidateUser) => {
    candidateToAdmit.value = user;
    admitForm.school_class_id = '';
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
        <Head title="Admissions & Prospective Students" />

        <div class="space-y-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">Admissions Portal</h2>
                    <p class="mt-1 text-sm font-bold tracking-widest text-slate-400 uppercase">
                        Prospective Students • {{ candidates.total }} Candidates
                    </p>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="isImportModalOpen = true"
                        class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-3 text-xs font-black text-slate-600 uppercase shadow-sm transition-all hover:bg-slate-50 active:scale-95"
                    >
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
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
            <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
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
                                placeholder="Search by name, email, or application number..."
                                class="h-12 w-full rounded-xl border-none bg-slate-50 pl-12 text-sm font-bold text-slate-700 transition-all focus:bg-white focus:ring-2 focus:ring-primary/10"
                            />
                        </div>
                        <button
                            @click="handleSearch"
                            class="h-12 rounded-xl bg-slate-900 px-8 text-xs font-black tracking-widest text-white uppercase transition-all hover:bg-black active:scale-95"
                        >
                            Search Portal
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Applicant Details</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Application ID</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Target Class</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Exam Batch</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest whitespace-nowrap text-slate-400 uppercase">
                                    Entrance Score
                                </th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Status</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="user in candidates.data" :key="user.id" class="group transition-all hover:bg-[#F8F9FB]">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-lemon-yellow/10 text-xs font-black text-primary transition-colors group-hover:bg-lemon-yellow group-hover:text-primary"
                                        >
                                            {{ user.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm leading-none font-black text-slate-800">{{ user.name }}</h4>
                                            <p class="mt-1 text-xs font-bold text-slate-400">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span
                                        class="inline-flex items-center rounded-lg border border-slate-100 bg-slate-50 px-3 py-1 text-[9px] font-black text-slate-600 uppercase shadow-sm"
                                    >
                                        {{ user.school_id || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-6 text-[10px] font-black text-slate-500 uppercase">
                                    {{ user.school_class?.name || 'Not Set' }}
                                </td>
                                <td class="px-6 py-6">
                                    <span
                                        v-if="user.prospective_class"
                                        class="inline-flex flex-col"
                                    >
                                        <span class="text-[10px] font-black text-slate-700 uppercase">{{ user.prospective_class.name }}</span>
                                        <span class="text-[8px] font-bold text-slate-400 uppercase">Pass Mark: {{ user.prospective_class.pass_percentage }}%</span>
                                    </span>
                                    <span v-else class="text-[9px] font-black tracking-widest text-slate-300 uppercase">No Batch</span>
                                </td>
                                <td class="px-6 py-6">
                                    <div v-if="user.latest_attempt" class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span 
                                                class="text-xs font-black"
                                                :class="getQualificationStatus(user).qualified ? 'text-green-600' : 'text-red-500'"
                                            >
                                                {{ getQualificationStatus(user).percentage }}%
                                            </span>
                                            <span class="text-[9px] font-bold text-slate-300 uppercase">({{ user.latest_attempt.score }} / {{ user.latest_attempt.exam.questions_count }})</span>
                                        </div>
                                        <div class="mt-1 h-1 w-20 overflow-hidden rounded-full bg-slate-100">
                                            <div 
                                                class="h-full transition-all duration-1000"
                                                :class="getQualificationStatus(user).qualified ? 'bg-green-500' : 'bg-red-400'"
                                                :style="{ width: `${getQualificationStatus(user).percentage}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                    <span v-else class="text-[9px] font-black tracking-widest text-slate-300 uppercase italic">Awaiting Exam</span>
                                </td>
                                <td class="px-6 py-6">
                                    <span
                                        v-if="user.latest_attempt && getQualificationStatus(user).qualified"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-green-50 px-3 py-1 text-[9px] font-black text-green-600 uppercase"
                                    >
                                        <span class="h-1 w-1 rounded-full bg-green-500"></span>
                                        Qualified
                                    </span>
                                    <span
                                        v-else-if="user.latest_attempt"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1 text-[9px] font-black text-red-600 uppercase"
                                    >
                                        <span class="h-1 w-1 rounded-full bg-red-500"></span>
                                        Not Qualified
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1 text-[9px] font-black text-blue-600 uppercase"
                                    >
                                        <span class="h-1 w-1 rounded-full bg-blue-500"></span>
                                        Pending
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openEditModal(user)"
                                            class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-400 transition-all hover:bg-slate-100 hover:text-slate-600 active:scale-90"
                                            title="Manage Batch / Details"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="openAdmitModal(user)"
                                            :disabled="!getQualificationStatus(user).qualified"
                                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-[10px] font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all enabled:hover:scale-105 enabled:active:scale-95 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:shadow-none"
                                            :title="getQualificationStatus(user).qualified ? 'Approve Admission' : 'Score below threshold'"
                                        >
                                            Approve Admission
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between border-t border-slate-50 bg-white px-8 py-6">
                    <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase italic">
                        Page {{ candidates.current_page }} • Records {{ candidates.from }}-{{ candidates.to }} of {{ candidates.total }}
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-for="link in candidates.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="flex h-10 min-w-10 items-center justify-center rounded-lg text-xs font-black transition-all"
                            :class="[
                                link.active ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-slate-50 text-slate-600 hover:bg-slate-100',
                                !link.url && 'pointer-events-none cursor-not-allowed opacity-30',
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-xl overflow-hidden rounded-xl bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900 italic">{{ isEditing ? 'Update Application' : 'New Student Application' }}</h3>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Applicant Full Name</label
                                >
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Enter Candidate Full Name"
                                    class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.name" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                            </div>

                            <div class="col-span-2">
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Email Address</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="Enter Candidate Email Address"
                                    class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.email" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.email }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Application ID / Username</label
                                >
                                <input
                                    v-model="form.username"
                                    type="text"
                                    placeholder="Auto-generated if blank"
                                    class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.username" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.username }}</div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Target Admission Class</label
                                >
                                <select
                                    v-model="form.school_class_id"
                                    class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="">Select Class</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                                <div v-if="form.errors.school_class_id" class="mt-1 text-xs font-bold text-red-500">
                                    {{ form.errors.school_class_id }}
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Assign to Entrance Batch</label
                                >
                                <select
                                    v-model="form.prospective_class_id"
                                    class="w-full rounded-lg border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="">Select Batch</option>
                                    <option v-for="batch in batches" :key="batch.id" :value="batch.id">{{ batch.name }}</option>
                                </select>
                                <div v-if="form.errors.prospective_class_id" class="mt-1 text-xs font-bold text-red-500">
                                    {{ form.errors.prospective_class_id }}
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button
                                type="button"
                                @click="closeModal"
                                class="flex-1 rounded-lg border border-slate-100 py-4 text-xs font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-lg bg-primary py-4 text-xs font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                            >
                                {{ isEditing ? 'Save Changes' : 'Enroll Applicant' }}
                            </button>
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
                                required
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            >
                                <option value="">Select Target Class</option>
                                <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                            </select>
                            <div v-if="admitForm.errors.school_class_id" class="mt-1 text-xs font-bold text-red-500">
                                {{ admitForm.errors.school_class_id }}
                            </div>
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
                        <span class="font-black text-primary">Name, Email, Application_ID, Target_Class, Exam_Batch</span>
                    </p>
                    <form @submit.prevent="handleImport" class="space-y-6">
                        <label
                            class="group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-10 transition-all hover:border-primary hover:bg-white"
                        >
                            <span class="text-xs font-black tracking-widest text-slate-400 uppercase group-hover:text-primary">{{
                                importForm.file ? importForm.file.name : 'Select Candidate List'
                            }}</span>
                            <input type="file" class="hidden" @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null" />
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
