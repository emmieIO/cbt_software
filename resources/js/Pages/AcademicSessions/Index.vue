<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { destroy, store, update } from '@/actions/App/Http/Controllers/AcademicSessionController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

type AcademicSession = {
    id: string;
    name: string;
    starts_at: string;
    ends_at: string;
    is_active: boolean;
    exams_count: number;
};

defineProps<{
    academicSessions: {
        data: AcademicSession[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();

const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);
const showForm = ref(false);
const editing = ref<AcademicSession | null>(null);
const deleteTarget = ref<AcademicSession | null>(null);
const form = ref({
    name: '',
    starts_at: '',
    ends_at: '',
    is_active: false,
});

const openCreate = () => {
    editing.value = null;
    form.value = { name: '', starts_at: '', ends_at: '', is_active: false };
    showForm.value = true;
};

const openEdit = (session: AcademicSession) => {
    editing.value = session;
    form.value = {
        name: session.name,
        starts_at: session.starts_at,
        ends_at: session.ends_at,
        is_active: session.is_active,
    };
    showForm.value = true;
};

const save = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
        },
    };

    if (editing.value) {
        router.put(update.url(editing.value), form.value, options);
        return;
    }

    router.post(store.url(), form.value, options);
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;

    router.delete(destroy.url(deleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Academic Sessions" />

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Academic Sessions</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage the sessions available when creating examinations.</p>
                </div>
                <button type="button" class="btn-primary w-full cursor-pointer justify-center sm:w-auto" @click="openCreate">
                    Add Session
                </button>
            </div>

            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showForm = false">
                <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-green-950">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                {{ editing ? 'Edit Academic Session' : 'New Academic Session' }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Only one session can be active at a time.</p>
                        </div>
                        <button type="button" class="cursor-pointer text-xl text-gray-500 hover:text-gray-800 dark:text-gray-300" @click="showForm = false">×</button>
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="save">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Session Name</label>
                            <input v-model="form.name" type="text" required class="mt-1" placeholder="2025/2026" />
                            <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name }}</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Start Date</label>
                                <input v-model="form.starts_at" type="date" required class="mt-1" />
                                <p v-if="errors.starts_at" class="mt-1 text-xs text-red-600">{{ errors.starts_at }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">End Date</label>
                                <input v-model="form.ends_at" type="date" required class="mt-1" />
                                <p v-if="errors.ends_at" class="mt-1 text-xs text-red-600">{{ errors.ends_at }}</p>
                            </div>
                        </div>
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <input v-model="form.is_active" type="checkbox" />
                            Set as active session
                        </label>
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" class="btn-secondary cursor-pointer" @click="showForm = false">Cancel</button>
                            <button type="submit" class="btn-primary cursor-pointer">Save Session</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-green-900/60">
                        <thead class="bg-gray-50 dark:bg-green-950/45">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Session</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Date Range</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Exams</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-green-900/50">
                            <tr v-for="session in academicSessions.data" :key="session.id" class="hover:bg-gray-50 dark:hover:bg-green-950/55">
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ session.name }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ session.starts_at }} to {{ session.ends_at }}</td>
                                <td class="px-5 py-4 text-center text-sm text-gray-600 dark:text-gray-300">{{ session.exams_count }}</td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="
                                            session.is_active
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200'
                                                : 'bg-gray-100 text-gray-600 dark:bg-green-900/60 dark:text-gray-300'
                                        "
                                    >
                                        {{ session.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-3">
                                        <button type="button" class="cursor-pointer text-xs font-medium text-primary hover:underline" @click="openEdit(session)">Edit</button>
                                        <button
                                            type="button"
                                            class="cursor-pointer text-xs font-medium text-red-600 hover:underline disabled:cursor-not-allowed disabled:text-gray-400 disabled:no-underline"
                                            :disabled="session.exams_count > 0"
                                            :title="session.exams_count > 0 ? 'Sessions used by exams cannot be deleted' : 'Delete session'"
                                            @click="deleteTarget = session"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="academicSessions.data.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">No academic sessions yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="academicSessions.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">Page {{ academicSessions.current_page }} of {{ academicSessions.last_page }}</p>
                <div class="flex gap-2">
                    <Link v-if="academicSessions.prev_page_url" :href="academicSessions.prev_page_url" class="btn-secondary">Previous</Link>
                    <Link v-if="academicSessions.next_page_url" :href="academicSessions.next_page_url" class="btn-secondary">Next</Link>
                </div>
            </div>
        </div>
    </AppLayout>

    <ConfirmationModal
        :show="!!deleteTarget"
        title="Delete Academic Session"
        message="This action cannot be undone."
        confirm-label="Delete"
        variant="danger"
        @close="deleteTarget = null"
        @confirm="confirmDelete"
    />
</template>
