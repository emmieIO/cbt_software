<script setup lang="ts">
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { store, update, destroy } from '@/actions/App/Http/Controllers/Admin/SchoolClassController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import CustomSelect from '@/components/Form/CustomSelect.vue';

interface SchoolClass {
    id: string;
    name: string;
    slug: string;
    level: string;
    school_id: string;
    school?: { name: string; type: string };
}

const props = defineProps<{
    classes: SchoolClass[];
    levels: { value: string; label: string }[];
    filters: { school_id?: string };
}>();

const page = usePage();
const branches = computed(() => (page.props.branches as any) || {});
const branchOptions = computed(() => Object.values(branches.value));

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingClass = ref<SchoolClass | null>(null);

const form = useForm({
    name: '',
    level: '',
    school_id: '',
});

// Filtering logic: Restrict class levels based on selected school type
const filteredLevels = computed(() => {
    if (!form.school_id || !branches.value[form.school_id]) return props.levels;
    
    const schoolType = branches.value[form.school_id].type;
    return props.levels.filter(l => l.value === schoolType);
});

// Auto-set level if there's only one option for that school
watch(() => form.school_id, (newId) => {
    if (newId && branches.value[newId]) {
        const schoolType = branches.value[newId].type;
        form.level = schoolType;
    }
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
    form.level = cls.level;
    form.school_id = cls.school_id;
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingClass.value) {
        form.put(update(editingClass.value.id).url, {
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
const classToDelete = ref<SchoolClass | null>(null);

const confirmDelete = (cls: SchoolClass) => {
    classToDelete.value = cls;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (classToDelete.value) {
        useForm({}).delete(destroy(classToDelete.value.id).url, {
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
        <Head title="Class Hierarchy" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Academic Framework</span>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Class Hierarchies</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Class Hierarchies</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Define and organize institutional levels across campuses.
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    New Class Level
                </button>
            </div>

            <!-- Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Class Name</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Campus Branch</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Academic Level</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="cls in classes" :key="cls.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-semibold text-gray-800 uppercase tracking-tight">{{ cls.name }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-600">{{ cls.school?.name }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span 
                                                class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                                :class="cls.level === 'secondary' ? 'bg-indigo-100 text-indigo-800' : 'bg-orange-100 text-orange-800'"
                                            >
                                                {{ cls.level }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex justify-end items-center gap-x-2">
                                                <button @click="openEditModal(cls)" class="text-gray-500 hover:text-primary transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                </button>
                                                <button @click="confirmDelete(cls)" class="text-gray-500 hover:text-red-500 transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="classes.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                            <p class="text-sm">No class hierarchies defined yet.</p>
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
                    <h3 class="font-semibold text-gray-800 uppercase tracking-tight text-sm">{{ isEditing ? 'Update Hierarchy' : 'Define New Level' }}</h3>
                    <button @click="closeModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50">
                        <span class="sr-only">Close</span>
                        <svg class="flex-shrink-0 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit" class="p-6 overflow-y-auto max-h-[calc(100vh-150px)]">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-800 uppercase tracking-widest text-[10px]">Academic Branch</label>
                            <CustomSelect
                                v-model="form.school_id"
                                :options="branchOptions"
                                placeholder="Select Campus"
                                :error="form.errors.school_id"
                                size="md"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-800 uppercase tracking-widest text-[10px]">Class Nomenclature</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. Primary 1 or JSS 1"
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                            />
                            <p v-if="form.errors.name" class="text-sm text-red-600 mt-2">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-800 uppercase tracking-widest text-[10px]">Mandatory Academic Level</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button 
                                    v-for="level in filteredLevels"
                                    :key="level.value"
                                    type="button"
                                    @click="form.level = level.value"
                                    class="py-3 px-4 text-center text-xs font-bold uppercase rounded-lg border-2 transition-all shadow-sm"
                                    :class="form.level === level.value ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-gray-100 text-gray-400 hover:border-gray-200'"
                                >
                                    {{ level.label }}
                                </button>
                            </div>
                            <p v-if="form.errors.level" class="text-sm text-red-600 mt-2">{{ form.errors.level }}</p>
                            <p v-if="!form.school_id" class="text-[10px] text-orange-500 font-medium mt-2 italic">Please select a campus first to verify academic level.</p>
                        </div>
                    </div>
                    
                    <div class="mt-8 flex justify-end gap-x-2">
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
                            {{ isEditing ? 'Confirm Changes' : 'Create Hierarchy' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Hierarchy Level?"
            :message="`Are you sure you want to delete ${classToDelete?.name}? This will remove it from all campuses and cannot be undone.`"
            confirm-label="Delete Level"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
