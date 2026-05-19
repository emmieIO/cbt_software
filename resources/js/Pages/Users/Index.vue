<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const isAdmin = (page.props.auth as any)?.user?.role === 'admin';

defineProps<{
    users: {
        data: Array<{
            id: string;
            name: string;
            username: string;
            email: string;
            role: string;
            role: string;
            created_at: string;
        }>;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();

const showForm = ref(false);
const editing = ref<string | null>(null);
const form = ref({
    name: '',
    username: '',
    email: '',
    password: '',
    role: 'uploader',
});

const openCreate = () => {
    editing.value = null;
    form.value = { name: '', username: '', email: '', password: '', role: 'uploader' };
    showForm.value = true;
};

const openEdit = (user: any) => {
    editing.value = user.id;
    form.value = {
        name: user.name,
        username: user.username,
        email: user.email,
        password: '',
        role: user.role,
    };
    showForm.value = true;
};

const save = () => {
    if (editing.value) {
        router.put(`/users/${editing.value}`, form.value, {
            preserveScroll: true,
            onSuccess: () => { showForm.value = false; },
        });
    } else {
        router.post('/users', form.value, {
            preserveScroll: true,
            onSuccess: () => { showForm.value = false; },
        });
    }
};

const deleteTarget = ref<string | null>(null);

const confirmDelete = () => {
    if (deleteTarget.value) {
        router.delete(`/users/${id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Users" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Users</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Manage system users.</p>
                </div>
                <button v-if="isAdmin" @click="openCreate" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm dark:shadow-none dark:border-gray-700 hover:bg-primary/90">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add User
                </button>
            </div>

            <!-- Form Modal -->
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showForm = false">
                <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ editing ? 'Edit User' : 'New User' }}</h2>
                    <form @submit.prevent="save" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Full Name</label>
                            <input v-model="form.name" type="text" required class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Username</label>
                            <input v-model="form.username" type="text" required class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
                            <input v-model="form.email" type="email" required class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ editing ? 'Password (leave blank to keep)' : 'Password' }}</label>
                            <input v-model="form.password" type="password" :required="!editing" minlength="6" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Role</label>
                            <select v-model="form.role" class="mt-1">
                                <option value="uploader">Uploader</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showForm = false" class="rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50">Cancel</button>
                            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-none dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Name</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Username</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Role</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:bg-gray-800/50/50">
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ user.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ user.email }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ user.username }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="user.role === 'admin' ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-700 dark:text-gray-200'">
                                    {{ user.role }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div v-if="isAdmin" class="flex justify-end gap-2">
                                    <button @click="openEdit(user)" class="text-xs font-medium text-primary hover:underline">Edit</button>
                                    <button @click="deleteTarget = user.id" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>

    <ConfirmationModal
        :show="!!deleteTarget"
        title="Delete User"
        message="This action cannot be undone."
        confirm-label="Delete"
        variant="danger"
        @close="deleteTarget = null"
        @confirm="confirmDelete"
    />
</template>
