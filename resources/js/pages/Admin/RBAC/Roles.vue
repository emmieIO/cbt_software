<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
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
    permissions: Permission[];
}

defineProps<{
    roles: Role[];
    permissions: Permission[];
}>();

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingRole = ref<Role | null>(null);

const form = useForm({
    name: '',
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
    const index = form.permissions.indexOf(permissionName);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permissionName);
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Role Management" />

        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">System Roles</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">Define access levels and assign permissions to user roles.</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="flex h-12 items-center gap-3 rounded-2xl bg-primary px-6 text-sm font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Role
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div
                    v-for="role in roles"
                    :key="role.id"
                    class="group relative overflow-hidden rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-xl hover:shadow-primary/5"
                >
                    <div class="relative z-10 flex h-full flex-col">
                        <div class="mb-6 flex items-start justify-between">
                            <div>
                                <h3 class="text-2xl font-black tracking-tight text-slate-800 uppercase">{{ role.name }}</h3>
                                <span class="text-[10px] font-black tracking-widest text-primary uppercase"
                                    >{{ role.permissions.length }} Permissions Assigned</span
                                >
                            </div>
                            <div class="flex gap-2" v-if="role.name !== 'admin'">
                                <button
                                    @click="openEditModal(role)"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-primary hover:text-white"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </button>
                                <button
                                    @click="confirmDelete(role)"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-red-500 hover:text-white"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <div
                                v-else
                                class="flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-[10px] font-black tracking-widest text-amber-600 uppercase"
                            >
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                System Locked
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="permission in role.permissions"
                                :key="permission.id"
                                class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-1.5 text-[10px] font-bold text-slate-500 uppercase"
                            >
                                {{ permission.name }}
                            </span>
                            <span v-if="role.permissions.length === 0" class="text-xs font-medium text-slate-400 italic"
                                >No permissions assigned.</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-2xl overflow-hidden rounded-[2.5rem] bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900">{{ isEditing ? 'Edit Role' : 'Create New Role' }}</h3>

                    <form @submit.prevent="submit" class="space-y-8">
                        <div>
                            <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Role Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. subject_lead"
                                class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            />
                            <div v-if="form.errors.name" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="mb-4 ml-1 block text-center text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                >Permissions Mapping</label
                            >
                            <div class="custom-scrollbar grid max-h-64 grid-cols-2 gap-3 overflow-y-auto pr-2 md:grid-cols-3">
                                <button
                                    v-for="permission in permissions"
                                    :key="permission.id"
                                    type="button"
                                    @click="togglePermission(permission.name)"
                                    :class="[
                                        'flex items-center justify-center rounded-xl border px-2 py-3 text-[9px] font-black tracking-widest uppercase transition-all',
                                        form.permissions.includes(permission.name)
                                            ? 'border-primary bg-primary text-white shadow-lg shadow-primary/20'
                                            : 'border-slate-100 bg-white text-slate-400 hover:border-primary hover:text-primary',
                                    ]"
                                >
                                    {{ permission.name }}
                                </button>
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
