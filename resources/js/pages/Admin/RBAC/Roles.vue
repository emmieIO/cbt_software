<script setup lang="ts">
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store, update, destroy } from '@/actions/App/Http/Controllers/Admin/RoleController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    category: string;
    permissions: Permission[];
}

defineProps<{
    roles: Role[];
    permissions: Permission[];
}>();

const categories = [
    { id: 'admin', name: 'System Admin' },
    { id: 'staff', name: 'Academic Staff' },
    { id: 'student', name: 'Student / Candidate' },
];

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingRole = ref<Role | null>(null);

const form = useForm({
    name: '',
    category: 'staff',
    permissions: [] as string[],
});

const openCreateModal = () => {
    isEditing.value = false;
    editingRole.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (role: Role) => {
    isEditing.value = true;
    editingRole.value = role;

    form.name = role.name;
    form.category = role.category;
    form.permissions = role.permissions.map((p) => p.name);

    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingRole.value) {
        form.put(update(editingRole.value.id).url, {
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
const roleToDelete = ref<Role | null>(null);

const confirmDelete = (role: Role) => {
    roleToDelete.value = role;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (roleToDelete.value) {
        router.delete(destroy(roleToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                roleToDelete.value = null;
            },
        });
    }
};

const togglePermission = (permissionName: string) => {
    const permissions = [...form.permissions];
    const index = permissions.indexOf(permissionName);
    if (index > -1) {
        permissions.splice(index, 1);
    } else {
        permissions.push(permissionName);
    }
    form.permissions = permissions;
};
</script>

<template>
    <AdminLayout>
        <Head title="Role Management" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">RBAC</span>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Roles</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">System Roles</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Define access templates and assign permissions by functional category.
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Role
                </button>
            </div>

            <!-- Roles Grid by Category -->
            <div v-for="cat in categories" :key="cat.id" class="space-y-4">
                <div class="flex items-center gap-4">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ cat.name }} Roles</h2>
                    <div class="h-px flex-1 bg-gray-100"></div>
                </div>
                
                <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-2">
                    <div
                        v-for="role in roles.filter(r => r.category === cat.id)"
                        :key="role.id"
                        class="group flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl hover:shadow-md transition-all"
                    >
                        <div class="p-4 md:p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800 uppercase tracking-tight">{{ role.name.replace('_', ' ') }}</h3>
                                    <p class="text-xs font-medium text-primary mt-1 uppercase tracking-wider"
                                        >{{ role.permissions.length }} Permissions Assigned</p
                                    >
                                </div>
                                <div class="flex gap-x-1" v-if="role.name !== 'super_admin'">
                                    <button
                                        @click="openEditModal(role)"
                                        class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmDelete(role)"
                                        class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent text-gray-500 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:bg-red-50 disabled:opacity-50 disabled:pointer-events-none"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <div
                                    v-else
                                    class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full bg-amber-50 text-amber-600 border border-amber-100"
                                >
                                    <svg class="size-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span class="text-[10px] font-semibold tracking-widest uppercase">System Locked</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="permission in role.permissions"
                                    :key="permission.id"
                                    class="inline-flex items-center gap-x-1.5 py-1 px-2 rounded-md text-[10px] font-medium bg-gray-100 text-gray-800 border border-gray-200 uppercase tracking-wider"
                                >
                                    {{ permission.name }}
                                </span>
                                <span v-if="role.permissions.length === 0" class="text-xs font-medium text-gray-400">No permissions assigned.</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="roles.filter(r => r.category === cat.id).length === 0" class="col-span-full py-8 text-center bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                        <p class="text-sm text-gray-400 font-medium">No roles defined for this category.</p>
                    </div>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-[80] overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
                <div class="relative w-full max-w-lg bg-white rounded-xl shadow-lg border border-gray-200">
                    <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800">{{ isEditing ? 'Edit Role Details' : 'Create New Role' }}</h3>
                        <button @click="closeModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none">
                            <span class="sr-only">Close</span>
                            <svg class="flex-shrink-0 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-4 overflow-y-auto max-h-[calc(100vh-150px)]">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800 uppercase tracking-widest text-[10px]">Role Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Enter Role Name (e.g. subject_lead)"
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-600 mt-2">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800 uppercase tracking-widest text-[10px]">Role Category</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        v-for="cat in categories"
                                        :key="cat.id"
                                        type="button"
                                        @click="form.category = cat.id"
                                        :class="[
                                            'py-2 px-3 text-center text-[10px] font-semibold rounded-lg border shadow-sm transition-all uppercase tracking-wider',
                                            form.category === cat.id
                                                ? 'bg-slate-900 border-slate-900 text-white'
                                                : 'bg-white border-gray-200 text-gray-800 hover:bg-gray-50',
                                        ]"
                                    >
                                        {{ cat.id }}
                                    </button>
                                </div>
                                <p v-if="form.errors.category" class="text-sm text-red-600 mt-2">{{ form.errors.category }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800 uppercase tracking-widest text-[10px] text-center border-t border-gray-100 pt-4">Permissions Mapping</label>
                                <div class="custom-scrollbar grid grid-cols-2 gap-2 max-h-64 overflow-y-auto pr-1">
                                    <button
                                        v-for="permission in permissions"
                                        :key="permission.id"
                                        type="button"
                                        @click="togglePermission(permission.name)"
                                        :class="[
                                            'py-2 px-3 inline-flex items-center justify-center gap-x-2 text-[10px] font-semibold rounded-lg border shadow-sm disabled:opacity-50 disabled:pointer-events-none focus:outline-none transition-all uppercase tracking-wider',
                                            form.permissions.includes(permission.name)
                                                ? 'bg-primary border-primary text-white'
                                                : 'bg-white border-gray-200 text-gray-800 hover:bg-gray-50',
                                        ]"
                                    >
                                        {{ permission.name }}
                                    </button>
                                </div>
                                <p v-if="form.errors.permissions" class="text-sm text-red-600 mt-2 text-center">{{ form.errors.permissions }}</p>
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
                                {{ isEditing ? 'Update Role' : 'Create Role' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Role?"
            :message="`Are you sure you want to delete the ${roleToDelete?.name} role? Users assigned to this role will lose their permissions.`"
            confirm-label="Delete Role"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
