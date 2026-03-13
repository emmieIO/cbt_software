<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store, update, destroy } from '@/actions/App/Http/Controllers/Admin/SchoolController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface Branch {
    id: string;
    name: string;
    slug: string;
    type: string;
    address: string | null;
    contact_email: string | null;
    contact_phone: string[] | null;
    is_active: boolean;
    users_count: number;
}

defineProps<{
    schools: Branch[];
}>();

const isModalOpen = ref(false);
const isEditing = ref(false);
const isDeleteModalOpen = ref(false);
const editingBranch = ref<Branch | null>(null);
const branchToDelete = ref<Branch | null>(null);

const form = useForm({
    name: '',
    type: 'primary',
    address: '',
    contact_email: '',
    contact_phone: [''],
    is_active: true,
});

const addPhoneField = () => {
    form.contact_phone.push('');
};

const removePhoneField = (index: number) => {
    if (form.contact_phone.length > 1) {
        form.contact_phone.splice(index, 1);
    } else {
        form.contact_phone[0] = '';
    }
};

const openCreateModal = () => {
    isEditing.value = false;
    editingBranch.value = null;
    form.reset();
    form.contact_phone = [''];
    isModalOpen.value = true;
};

const openEditModal = (branch: Branch) => {
    isEditing.value = true;
    editingBranch.value = branch;
    form.name = branch.name;
    form.type = branch.type;
    form.address = branch.address || '';
    form.contact_email = branch.contact_email || '';
    form.contact_phone = branch.contact_phone && branch.contact_phone.length > 0
        ? [...branch.contact_phone]
        : [''];
    form.is_active = branch.is_active;
    isModalOpen.value = true;
};

const confirmDelete = (branch: Branch) => {
    branchToDelete.value = branch;
    isDeleteModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value && editingBranch.value) {
        form.transform((data) => ({
            ...data,
            contact_phone: data.contact_phone.filter(p => p.trim() !== '')
        })).put(update(editingBranch.value.id).url, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.transform((data) => ({
            ...data,
            contact_phone: data.contact_phone.filter(p => p.trim() !== '')
        })).post(store().url, {
            onSuccess: () => closeModal(),
        });
    }
};

const handleDelete = () => {
    if (branchToDelete.value) {
        useForm({}).delete(destroy(branchToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                branchToDelete.value = null;
            },
        });
    }
};

const getTypeClasses = (type: string) => {
    switch (type) {
        case 'nursery':
            return 'bg-pink-100 text-pink-800';
        case 'secondary':
            return 'bg-indigo-100 text-indigo-800';
        default:
            return 'bg-orange-100 text-orange-800';
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Branch Management" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Infrastructure</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Campus Directory</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Institutional Branches • {{ schools.length }} Locations
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Register Branch
                </button>
            </div>

            <!-- Branches Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Branch Details</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Category</th>
                                        <th scope="col" class="hidden md:table-cell px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Contact Info</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="school in schools" :key="school.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="block text-sm font-semibold text-gray-800">{{ school.name }}</span>
                                            <span class="block text-xs text-gray-500 mt-0.5 truncate max-w-xs">{{ school.address || 'No address provided' }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-bold uppercase tracking-wider"
                                                :class="getTypeClasses(school.type)"
                                            >
                                                {{ school.type }}
                                            </span>
                                        </td>
                                        <td class="hidden md:table-cell px-6 py-4">
                                            <span class="block text-sm text-gray-800">{{ school.contact_email || 'N/A' }}</span>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <template v-if="school.contact_phone && school.contact_phone.length > 0">
                                                    <span 
                                                        v-for="(phone, idx) in school.contact_phone" 
                                                        :key="idx"
                                                        class="inline-block text-[10px] font-bold text-gray-500 bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100"
                                                    >
                                                        {{ phone }}
                                                    </span>
                                                </template>
                                                <span v-else class="text-xs text-gray-400">N/A</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span v-if="school.is_active" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-md text-xs font-medium bg-teal-100 text-teal-800">
                                                <span class="size-1.5 inline-block rounded-full bg-teal-500"></span>
                                                Active
                                            </span>
                                            <span v-else class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-md text-xs font-medium bg-red-100 text-red-800">
                                                <span class="size-1.5 inline-block rounded-full bg-red-500"></span>
                                                Inactive
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex justify-end items-center gap-x-2">
                                                <button @click="openEditModal(school)" class="text-gray-500 hover:text-primary transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                </button>
                                                <button @click="confirmDelete(school)" class="text-gray-500 hover:text-red-500 transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="schools.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            <p class="text-sm">No branches registered yet</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
                    <h3 class="font-semibold text-gray-800">{{ isEditing ? 'Update Branch' : 'Register New Branch' }}</h3>
                    <button @click="closeModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none">
                        <span class="sr-only">Close</span>
                        <svg class="flex-shrink-0 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-4 overflow-y-auto max-h-[calc(100vh-150px)]">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-800">Branch Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. Chrisland Primary, VGC"
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                            />
                            <p v-if="form.errors.name" class="text-sm text-red-600 mt-2">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-800 uppercase tracking-widest text-[10px]">Institutional Category</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button
                                    v-for="type in ['nursery', 'primary', 'secondary']"
                                    :key="type"
                                    type="button"
                                    @click="form.type = type"
                                    class="py-3 px-2 text-center text-[10px] font-bold uppercase rounded-lg border-2 transition-all shadow-sm"
                                    :class="form.type === type ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-gray-100 text-gray-400 hover:border-gray-200'"
                                >
                                    {{ type }}
                                </button>
                            </div>
                            <p v-if="form.errors.type" class="text-sm text-red-600 mt-2">{{ form.errors.type }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-800">Address</label>
                            <textarea
                                v-model="form.address"
                                rows="2"
                                placeholder="Enter physical address..."
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                            ></textarea>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-gray-800">Work Email</label>
                            <input
                                v-model="form.contact_email"
                                type="email"
                                placeholder="admin@branch.com"
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                            />
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-medium text-gray-800">Contact Numbers</label>
                                <button type="button" @click="addPhoneField" class="text-[10px] font-black text-primary uppercase hover:underline">+ Add Phone</button>
                            </div>

                            <div v-for="(phone, index) in form.contact_phone" :key="index" class="flex gap-2">
                                <div class="relative flex-1">
                                    <input
                                        v-model="form.contact_phone[index]"
                                        type="text"
                                        placeholder="+234..."
                                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                    />
                                </div>
                                <button
                                    v-if="form.contact_phone.length > 1 || form.contact_phone[0] !== ''"
                                    type="button"
                                    @click="removePhoneField(index)"
                                    class="p-3 text-gray-400 hover:text-red-500 transition-colors"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                            <p v-if="form.errors.contact_phone" class="text-sm text-red-600 mt-2">{{ form.errors.contact_phone }}</p>
                        </div>

                        <div class="flex items-center">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                id="is_active"
                                class="shrink-0 mt-0.5 border-gray-200 rounded text-primary focus:ring-primary"
                            />
                            <label for="is_active" class="text-sm text-gray-500 ms-3">Branch is active</label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-x-2">
                        <button
                            type="button"
                            @click="closeModal"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none"
                        >
                            {{ isEditing ? 'Save Changes' : 'Confirm Registration' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Deletion Confirmation Modal -->
        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Branch"
            :message="`Are you sure you want to delete ${branchToDelete?.name}? This action will permanently remove the branch from the system.`"
            confirm-label="Delete"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
